<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\TestCase;

use function sys_get_temp_dir;
use function uniqid;

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
}
