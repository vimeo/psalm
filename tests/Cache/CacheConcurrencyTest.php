<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\RuntimeCaches;

use function assert;
use function dirname;
use function fclose;
use function fgets;
use function file_get_contents;
use function file_put_contents;
use function flock;
use function fopen;
use function getenv;
use function glob;
use function hash;
use function proc_close;
use function proc_open;
use function stream_get_contents;
use function substr;
use function sys_get_temp_dir;
use function trim;
use function uniqid;
use function usleep;

use const DIRECTORY_SEPARATOR;
use const LOCK_SH;
use const LOCK_UN;
use const PHP_BINARY;

final class CacheConcurrencyTest extends TestCase
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
            . uniqid('psalm-cache-concurrency-test-', true);
    }

    #[Override]
    protected function tearDown(): void
    {
        RuntimeCaches::clearAll();

        Config::removeCacheDirectory($this->cache_directory);

        parent::tearDown();
    }

    /**
     * @return non-empty-string
     * @psalm-pure
     */
    private static function configXml(string $cache_directory): string
    {
        return <<<XML
            <?xml version="1.0"?>
            <psalm cacheDirectory="{$cache_directory}">
                <projectFiles>
                    <directory name="src" />
                </projectFiles>
            </psalm>
            XML;
    }

    private function newCache(): Cache
    {
        $config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            self::configXml($this->cache_directory),
        );

        return new Cache($config, 'test_subdir');
    }

    /**
     * A writer that is waiting for a reader to finish must leave the entry readable.
     *
     * The writer runs in its own process (flock() conflicts between two descriptors of one process
     * would block this test forever). The test keeps a shared lock on the entry's header, as a
     * reader in the middle of getItem() does, while the writer is told to replace the entry.
     */
    public function testWriterWaitingForAReaderDoesNotEmptyTheEntry(): void
    {
        $key = 'some/file/path.php';

        $this->newCache()->saveItem($key, ['old payload'], 'old hash');

        $header_path = $this->entryPath($key) . '.hash';
        $reader_lock = fopen($header_path, 'r');
        assert($reader_lock !== false);
        $this->assertTrue(flock($reader_lock, LOCK_SH));

        [$writer, $stdout] = $this->startWriter($key, 'new hash');

        try {
            $this->assertSame('started', trim((string) fgets($stdout)));
            // Give the writer time to run up to the lock it has to wait for.
            usleep(300_000);

            $this->assertNotSame('', file_get_contents($header_path), 'the header was truncated by a waiting writer');
            $this->assertSame(['old payload'], $this->newCache()->getItem($key, 'old hash'));
        } finally {
            flock($reader_lock, LOCK_UN);
            fclose($reader_lock);

            stream_get_contents($stdout);
            fclose($stdout);
            $this->assertSame(0, proc_close($writer));
        }

        $this->assertSame(['new payload'], $this->newCache()->getItem($key, 'new hash'));
    }

    /** A header cut short by a crashed writer is a cache miss, not an exception. */
    public function testTruncatedHeaderIsAMiss(): void
    {
        $key = 'some/file/path.php';

        $this->newCache()->saveItem($key, ['payload'], 'hash');
        file_put_contents($this->entryPath($key) . '.hash', '');

        $this->assertNull($this->newCache()->getItem($key, 'hash'));
    }

    /** @return array{resource, resource} the writer process and its stdout pipe */
    private function startWriter(string $key, string $hash): array
    {
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        $script = <<<'PHP'
            require getenv('AUTOLOAD');
            $config = Psalm\Config::loadFromXML(getenv('BASE_DIR'), getenv('CONFIG_XML'));
            $cache = new Psalm\Internal\Cache($config, 'test_subdir');
            echo "started\n";
            fflush(STDOUT);
            $cache->saveItem(getenv('KEY'), ['new payload'], getenv('HASH'));
            PHP;

        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                '-d',
                'display_errors=stderr',
                '-r',
                $script,
            ],
            [1 => ['pipe', 'w']],
            $pipes,
            null,
            [
                ...getenv(),
                'AUTOLOAD' => $autoload,
                'BASE_DIR' => __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
                'CONFIG_XML' => self::configXml($this->cache_directory),
                'KEY' => $key,
                'HASH' => $hash,
            ],
        );
        if ($process === false || !isset($pipes[1])) {
            self::fail('Failed to start the writer process');
        }

        return [$process, $pipes[1]];
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
