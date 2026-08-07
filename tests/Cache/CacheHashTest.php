<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\TestCase;

use function file_put_contents;
use function glob;
use function pack;
use function substr;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

final class CacheHashTest extends TestCase
{
    private string $cache_directory;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        RuntimeCaches::clearAll();

        $this->cache_directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . uniqid('psalm-cache-hash-test-', true);
    }

    #[Override]
    public function tearDown(): void
    {
        RuntimeCaches::clearAll();

        Config::removeCacheDirectory($this->cache_directory);

        parent::tearDown();
    }

    private function newCache(): Cache
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

        return new Cache($config, 'test_subdir');
    }

    /**
     * getHash() must return the hash stored alongside the item, not the
     * serialized payload. Callers such as ParserCacheProvider store the raw
     * file contents as the "hash" and compare the result against the current
     * file contents to decide whether a file changed, so returning the payload
     * makes every file look modified.
     */
    public function testGetHashReturnsTheStoredHashAndNotThePayload(): void
    {
        $key = 'some/file/path.php';
        $hash = '<?php echo "the previous contents of the file";';
        $item = ['the serialized payload', 'which is not the hash'];

        $writer = $this->newCache();
        $writer->saveItem($key, $item, $hash);

        // A fresh instance, so the in-memory cache cannot mask a bad disk read.
        $reader = $this->newCache();

        $this->assertSame($hash, $reader->getHash($key));
    }

    public function testGetHashReturnsNullForAnUnknownKey(): void
    {
        $cache = $this->newCache();

        $this->assertNull($cache->getHash('never/written.php'));
    }

    public function testGetHashRoundTripsAnEmptyHash(): void
    {
        $key = 'empty/hash.php';

        $writer = $this->newCache();
        $writer->saveItem($key, ['payload'], '');

        $reader = $this->newCache();

        $this->assertSame('', $reader->getHash($key));
    }

    /**
     * The header is written before the payload, so an interrupted write can leave a
     * header describing an item that is not there. getItem() requires both files;
     * getHash() must agree with it rather than report a hash for an unloadable entry.
     */
    public function testGetHashReturnsNullWhenThePayloadIsMissing(): void
    {
        $key = 'orphan/header.php';

        $writer = $this->newCache();
        $writer->saveItem($key, ['payload'], 'the hash');

        unlink($this->itemPath($key));

        $this->assertNull($this->newCache()->getHash($key));
    }

    /** @dataProvider provideCorruptHeaders */
    public function testGetHashReturnsNullForACorruptHeader(string $header): void
    {
        $key = 'corrupt/header.php';

        $writer = $this->newCache();
        $writer->saveItem($key, ['payload'], 'the hash');

        file_put_contents($this->itemPath($key) . '.hash', $header);

        $this->assertNull($this->newCache()->getHash($key));
    }

    /** @return iterable<string, list{string}> */
    public static function provideCorruptHeaders(): iterable
    {
        $key = 'corrupt/header.php';

        yield 'empty' => [''];
        yield 'shorter than the length prefix' => ["\x01\x02"];
        yield 'length prefix only' => [pack('V', 8)];
        yield 'declared length overruns the header' => [pack('V', 4096) . 'the hash' . $key];
        yield 'trailing key belongs to another entry' => [
            pack('V', 8) . 'the hash' . 'some/other/key.php',
        ];
    }

    private function itemPath(string $key): string
    {
        $files = glob($this->cache_directory . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR
            . 'test_subdir' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.hash');

        $this->assertNotFalse($files);
        $this->assertCount(1, $files, 'expected exactly one cached item');

        return substr($files[0], 0, -5);
    }
}
