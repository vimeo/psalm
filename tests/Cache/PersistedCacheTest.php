<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Closure;
use Fiber;
use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\RuntimeCaches;
use RuntimeException;

use function basename;
use function file_exists;
use function file_put_contents;
use function glob;
use function ini_get;
use function ini_set;
use function is_dir;
use function rmdir;
use function str_ends_with;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

final class PersistedCacheTest extends TestCase
{
    private string $cache_directory;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        RuntimeCaches::clearAll();

        $this->cache_directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('psalm-cache-test-', true);
    }

    #[Override]
    protected function tearDown(): void
    {
        self::remove($this->cache_directory);

        RuntimeCaches::clearAll();

        parent::tearDown();
    }

    private static function remove(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (!is_dir($path)) {
            unlink($path);

            return;
        }

        foreach (self::listing($path . DIRECTORY_SEPARATOR . '*') as $entry) {
            self::remove($entry);
        }

        rmdir($path);
    }

    /** @return list<string> */
    private function cacheFiles(): array
    {
        return self::listing($this->cache_directory . DIRECTORY_SEPARATOR . '*/test/*/*');
    }

    /** @return list<string> */
    private static function listing(string $pattern): array
    {
        $entries = glob($pattern);

        return $entries === false ? [] : $entries;
    }


    /** @return Cache<array> */
    private function createCache(string $attributes = ''): Cache
    {

        $config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            <<<XML
                <?xml version="1.0"?>
                <psalm cacheDirectory="{$this->cache_directory}" {$attributes}>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>
                XML,
        );

        return new Cache($config, 'test');
    }

    public function testItemThatCannotBeSerializedThrowsAndKeepsThePreviousEntry(): void
    {
        $cache = $this->createCache();
        $cache->saveItem('key', ['serializable'], 'hash1');

        try {
            $cache->saveItem('key', [Closure::fromCallable('strlen')], 'hash2');
            self::fail('Expected an exception');
        } catch (RuntimeException $e) {
            self::assertStringStartsWith("Could not serialize the cache entry for 'key'. Cause: ", $e->getMessage());
            self::assertStringContainsString('Closure', $e->getMessage());
            self::assertNotNull($e->getPrevious());
        }

        self::assertSame(['serializable'], $this->createCache()->getItem('key', 'hash1'));
    }

    /**
     * Stack overflows only became catchable in PHP 8.3, earlier versions crash instead.
     *
     * @requires PHP >= 8.3
     */
    public function testTooDeeplyNestedItemThrowsWithAHint(): void
    {
        $previous = (string) ini_get('fiber.stack_size');
        ini_set('fiber.stack_size', '256K');

        try {
            $item = [];
            for ($i = 0; $i < 5_000; $i++) {
                $item = [$item];
            }

            // igbinary does not guard against stack overflows, the process would crash instead
            $cache = $this->createCache('serializer="php"');
            $fiber = new Fiber(static fn() => $cache->saveItem("src/deep.php\0deep", $item, 'hash'));

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessageMatches(
                "/^Could not serialize the cache entry for 'src\\/deep\\.php', 'deep'\\. .*fiber\\.stack_size.* Cause: Maximum call stack size/",
            );

            $fiber->start();
        } finally {
            ini_set('fiber.stack_size', $previous);
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function providerUnreadableItem(): iterable
    {
        yield 'garbage' => ['', 'not a serialized value'];
        yield 'empty, php serializer' => ['serializer="php" compressor="off"', ''];
        yield 'empty, igbinary serializer' => ['serializer="igbinary" compressor="off"', ''];
    }

    /** @dataProvider providerUnreadableItem */
    public function testUnreadableItemThrows(string $attributes, string $contents): void
    {
        $this->createCache($attributes)->saveItem('key', ['serializable'], 'hash1');

        foreach ($this->cacheFiles() as $file) {
            if (!str_ends_with($file, '.hash') && basename($file) !== 'lock') {
                file_put_contents($file, $contents);
            }
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches(
            "/^Could not unserialize the cache entry for 'key' from .+\\. .+--clear-cache.+ Cause: ./",
        );

        $this->createCache($attributes)->getItem('key', 'hash1');
    }

    public function testUnreadableConsolidatedCacheThrows(): void
    {
        $cache = $this->createCache();
        $cache->saveItem('key', ['serializable'], 'hash1');
        $cache->consolidate();

        foreach ($this->cacheFiles() as $file) {
            if (basename($file) === 'consolidated') {
                file_put_contents($file, 'not a serialized value');
            }
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches(
            '/^Could not unserialize the consolidated cache .+consolidated\\. .+--clear-cache.+ Cause: ./',
        );

        $this->createCache();
    }
}
