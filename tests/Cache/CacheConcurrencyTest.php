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
use function explode;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function flock;
use function fopen;
use function getenv;
use function getmypid;
use function glob;
use function hash;
use function in_array;
use function is_dir;
use function microtime;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function readlink;
use function realpath;
use function scandir;
use function str_starts_with;
use function stream_get_contents;
use function substr;
use function sys_get_temp_dir;
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
     * reader in the middle of getItem() does, while the writer is told to replace the entry. The
     * header is only inspected once the writer holds it open, so a writer that truncated it on open is caught.
     */
    public function testWriterWaitingForAReaderDoesNotEmptyTheEntry(): void
    {
        $key = 'some/file/path.php';

        $this->newCache()->saveItem($key, ['old payload'], 'old hash');

        $header_path = $this->entryPath($key) . '.hash';
        $reader_lock = fopen($header_path, 'r');
        assert($reader_lock !== false);
        $this->assertTrue(flock($reader_lock, LOCK_SH));

        $writer = $this->startWriter($key, 'new hash');

        try {
            // A writer that has the header open and is still running is waiting for the lock held here.
            $this->waitUntilOpened($writer, $header_path);
            $this->assertTrue(proc_get_status($writer)['running'], 'the writer exited without waiting for the lock');

            $this->assertNotSame('', file_get_contents($header_path), 'the header was truncated by a waiting writer');
            $this->assertSame(['old payload'], $this->newCache()->getItem($key, 'old hash'));
        } finally {
            flock($reader_lock, LOCK_UN);
            fclose($reader_lock);

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

    /** @return resource the writer process */
    private function startWriter(string $key, string $hash)
    {
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        $script = <<<'PHP'
            require getenv('AUTOLOAD');
            $config = Psalm\Config::loadFromXML(getenv('BASE_DIR'), getenv('CONFIG_XML'));
            $cache = new Psalm\Internal\Cache($config, 'test_subdir');
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
            [],
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
        if ($process === false) {
            self::fail('Failed to start the writer process');
        }

        return $process;
    }

    /** @param resource $process */
    private function waitUntilOpened($process, string $path): void
    {
        $pid = proc_get_status($process)['pid'];
        $real_path = realpath($path);
        $this->assertNotFalse($real_path);

        if (self::listOpenFiles((int) getmypid()) === null) {
            self::markTestSkipped('Neither /proc nor lsof is available to see the files the writer has open');
        }

        $deadline = microtime(true) + 10.0;
        while (!in_array($real_path, self::listOpenFiles($pid) ?? [], true)) {
            $this->assertTrue(proc_get_status($process)['running'], 'the writer exited before opening the header');
            $this->assertLessThan($deadline, microtime(true), 'the writer never opened the header');
            usleep(2_000);
        }
    }

    /**
     * @return list<string>|null the paths a process has open, null if this system cannot tell
     */
    private static function listOpenFiles(int $pid): ?array
    {
        $paths = [];

        if (is_dir('/proc/self/fd')) {
            // a process that has exited has no directory to list
            $entries = @scandir("/proc/$pid/fd");
            foreach ($entries === false ? [] : $entries as $entry) {
                $target = @readlink("/proc/$pid/fd/$entry");
                if ($target !== false) {
                    $paths[] = $target;
                }
            }

            return $paths;
        }

        $pipes = [];
        $lsof = proc_open(
            ['lsof', '-p', (string) $pid, '-Fn'],
            [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
        );
        if ($lsof === false) {
            return null;
        }

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $exit_code = proc_close($lsof);

        // lsof exits with 1 when it finds nothing, as for a process that has exited
        if ($output === false || ($exit_code !== 0 && $exit_code !== 1)) {
            return null;
        }

        // -Fn prints one "n<path>" line per open file
        foreach (explode("\n", $output) as $line) {
            if (str_starts_with($line, 'n')) {
                $paths[] = substr($line, 1);
            }
        }

        return $paths;
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
