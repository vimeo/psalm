<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\RuntimeCaches;

use function file_put_contents;
use function glob;
use function hash;
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
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to isolate Psalm from PHPUnit cli arguments
        global $argv;
        $argv = [];
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        RuntimeCaches::clearAll();

        $this->cache_directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . uniqid('psalm-cache-hash-test-', true);
    }

    #[Override]
    protected function tearDown(): void
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
     * Callers such as ParserCacheProvider store the previous file contents as the hash and
     * compare getHash() against the current contents to decide whether the file changed.
     */
    public function testGetHashReturnsTheStoredHashAndNotThePayload(): void
    {
        $key = 'some/file/path.php';
        $hash = '<?php echo "the previous contents of the file";';

        $this->newCache()->saveItem($key, ['the payload', 'which is not the hash'], $hash);

        // A fresh instance, so the in-memory cache cannot mask a bad disk read.
        $this->assertSame($hash, $this->newCache()->getHash($key));
    }

    public function testGetHashRoundTripsEmptyAndBinaryHashes(): void
    {
        $writer = $this->newCache();
        $writer->saveItem('empty', ['payload'], '');
        $writer->saveItem("binary/\x00\xff", ['payload'], "\x00\x01\xfe\xff hash \x00");

        $reader = $this->newCache();
        $this->assertSame('', $reader->getHash('empty'));
        $this->assertSame("\x00\x01\xfe\xff hash \x00", $reader->getHash("binary/\x00\xff"));
    }

    public function testGetHashReturnsNullForAnUnknownKey(): void
    {
        $this->assertNull($this->newCache()->getHash('never/written.php'));
    }

    /** The header is written before the payload, so an interrupted write can leave only the header. */
    public function testGetHashReturnsNullWhenThePayloadIsMissing(): void
    {
        $key = 'orphan/header.php';

        $this->newCache()->saveItem($key, ['payload'], 'the hash');
        unlink($this->entryPath($key));

        $this->assertNull($this->newCache()->getHash($key));
    }

    #[DataProvider('provideCorruptHeaders')]
    public function testGetHashReturnsNullForACorruptHeader(string $header): void
    {
        $key = 'corrupt/header.php';

        $this->newCache()->saveItem($key, ['payload'], 'the hash');
        file_put_contents($this->entryPath($key) . '.hash', $header);

        $this->assertNull($this->newCache()->getHash($key));
    }

    /**
     * @return iterable<string, list{string}>
     * @psalm-pure
     */
    public static function provideCorruptHeaders(): iterable
    {
        $key = 'corrupt/header.php';

        yield 'empty' => [''];
        yield 'shorter than the length prefix' => ["\x01\x02"];
        yield 'length prefix only' => [pack('V', 8)];
        yield 'declared length overruns the header' => [pack('V', 4096) . 'the hash' . $key];
        yield 'total length one byte short' => [pack('V', 8) . 'the hash' . substr($key, 1)];
        yield 'total length one byte long' => [pack('V', 8) . 'the hash' . $key . 'x'];
    }

    private function entryPath(string $key): string
    {
        $files = glob($this->cache_directory . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'test_subdir'
            . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . hash('xxh128', $key) . '.hash');

        $this->assertNotFalse($files);
        $this->assertCount(1, $files);

        return substr($files[0], 0, -5);
    }
}
