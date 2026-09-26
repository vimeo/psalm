<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Closure;
use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\RuntimeCaches;

use function basename;
use function file_exists;
use function file_put_contents;
use function glob;
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
    private function createCache(): Cache
    {
        $config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            <<<XML
                <?xml version="1.0"?>
                <psalm cacheDirectory="{$this->cache_directory}">
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>
                XML,
        );

        return new Cache($config, 'test');
    }

    public function testItemThatCannotBeSerializedIsSkippedInsteadOfAbortingTheRun(): void
    {
        $cache = $this->createCache();
        $cache->saveItem('key', ['serializable'], 'hash1');

        // a closure cannot be serialized, just like an AST nested too deeply for the serializer
        // to walk within the available call stack
        $cache->saveItem('key', [Closure::fromCallable('strlen')], 'hash2');

        self::assertNull($cache->getItem('key', 'hash2'));
        self::assertSame(['serializable'], $this->createCache()->getItem('key', 'hash1'));
    }

    public function testUnreadableItemIsTreatedAsCacheMiss(): void
    {
        $this->createCache()->saveItem('key', ['serializable'], 'hash1');

        foreach ($this->cacheFiles() as $file) {
            if (!str_ends_with($file, '.hash') && basename($file) !== 'lock') {
                file_put_contents($file, 'not a serialized value');
            }
        }

        self::assertNull($this->createCache()->getItem('key', 'hash1'));
    }
}
