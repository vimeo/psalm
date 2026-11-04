<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Cache;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\IssueBuffer;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;
use ReflectionProperty;
use RuntimeException;

use function array_diff;
use function array_values;
use function chmod;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function function_exists;
use function glob;
use function hash;
use function ini_set;
use function is_writable;
use function memory_get_usage;
use function microtime;
use function mkdir;
use function pack;
use function pcntl_fork;
use function pcntl_signal;
use function pcntl_waitpid;
use function pcntl_wexitstatus;
use function pcntl_wifexited;
use function posix_getrlimit;
use function posix_setrlimit;
use function preg_match;
use function restore_error_handler;
use function scandir;
use function set_error_handler;
use function sort;
use function str_repeat;
use function str_replace;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const GLOB_ONLYDIR;
use const POSIX_RLIMIT_FSIZE;
use const POSIX_RLIMIT_INFINITY;
use const SIGXFSZ;
use const SIG_DFL;
use const SIG_IGN;
use const WNOHANG;

final class CacheTest extends TestCase
{
    private const KEY = 'src/A.php';

    /** One directory for the class, a cache directory under it for each test */
    private static string $cache_root;

    private string $cache_directory;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to isolate Psalm from PHPUnit cli arguments
        global $argv;
        $argv = [];

        self::$cache_root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'psalm-cache-test-' . uniqid('', true);
    }

    #[Override]
    public static function tearDownAfterClass(): void
    {
        Config::removeCacheDirectory(self::$cache_root);

        parent::tearDownAfterClass();
    }

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        RuntimeCaches::clearAll();

        $this->cache_directory = self::$cache_root . DIRECTORY_SEPARATOR . uniqid('', true);
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function tearDown(): void
    {
        RuntimeCaches::clearAll();

        parent::tearDown();
    }

    private function newConfig(): TestConfig
    {
        $config = new TestConfig();
        $config->cache_directory = $this->cache_directory;

        return $config;
    }

    /** @return Cache<string> */
    private function newCache(bool $array_cache = true): Cache
    {
        $config = $this->newConfig();
        $config->array_cache = $array_cache;

        return new Cache($config, 'test');
    }

    private function newParserCacheProvider(): ParserCacheProvider
    {
        return new ParserCacheProvider($this->newConfig(), '');
    }

    /** The directory of the cache: <cache directory>/<subdir>/<config hash> */
    private function entryDirectory(): string
    {
        $paths = glob($this->cache_directory . '/*/*', GLOB_ONLYDIR);

        self::assertNotFalse($paths);
        self::assertCount(1, $paths);

        return $paths[0];
    }

    /** The file of self::KEY */
    private function entryPath(): string
    {
        $path = $this->entryDirectory() . '/' . hash('xxh128', self::KEY);
        self::assertFileExists($path);

        return $path;
    }

    /** @return list<string> sorted */
    private static function namesIn(string $directory): array
    {
        $names = scandir($directory);
        self::assertNotFalse($names);
        $names = array_values(array_diff($names, ['.', '..']));
        sort($names);

        return $names;
    }

    /**
     * The errors an operation raises, as seen by an error handler that does not honor the `@` operator.
     *
     * @return list<string>
     */
    private static function errorsRaisedBy(callable $operation): array
    {
        $errors = [];
        set_error_handler(static function (int $code, string $message) use (&$errors): bool {
            $errors[] = $message;

            return true;
        });

        try {
            $operation();
        } finally {
            restore_error_handler();
        }

        return $errors;
    }

    /**
     * @param array<string, list<IssueData>> $issue_data
     * @return array<string, list<string>>
     * @psalm-mutation-free
     */
    private static function normalizeIssueData(array $issue_data): array
    {
        $return = [];
        foreach ($issue_data as $issue_data_per_file) {
            foreach ($issue_data_per_file as $one_issue_data) {
                $file_name = str_replace(DIRECTORY_SEPARATOR, '/', $one_issue_data->file_name);
                $return[$file_name][] = $one_issue_data->type . ': ' . $one_issue_data->message;
            }
        }

        return $return;
    }

    /**
     * @param list<array{
     *     files: array<string, string|null>,
     *     issues?: array<string, list<string>>,
     * }> $interactions
     */
    #[DataProvider('provideCacheInteractions')]
    public function testCacheInteractions(
        array $interactions,
    ): void {
        $config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            <<<'XML'
                <?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>
                XML,
        );
        $config->setIncludeCollector(new IncludeCollector());

        $file_provider = new FakeFileProvider();
        $providers = new Providers(
            $file_provider,
            new ParserCacheProvider($config, '', false),
            new FileStorageCacheProvider($config, '', false),
            new ClassLikeStorageCacheProvider($config, '', false),
            new FileReferenceCacheProvider($config, '', false),
            new ProjectCacheProvider(),
        );

        foreach ($interactions as $interaction) {
            foreach ($interaction['files'] as $file_path => $file_contents) {
                $file_path = $config->base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file_path);
                if ($file_contents === null) {
                    $file_provider->deleteFile($file_path);
                } else {
                    $file_provider->registerFile($file_path, $file_contents);
                }
            }

            RuntimeCaches::clearAll();

            $start_time = microtime(true);
            $project_analyzer = new ProjectAnalyzer($config, $providers);
            $project_analyzer->check($config->base_dir, true);
            $project_analyzer->finish($start_time, PSALM_VERSION);

            $issues = self::normalizeIssueData(IssueBuffer::getIssuesData());
            self::assertSame($interaction['issues'] ?? [], $issues);
        }
    }

    #[DataProvider('provideSaveItemRetention')]
    public function testSaveItemRetention(bool $persistent, bool $array_cache, bool $retained): void
    {
        $config = $this->newConfig();
        $config->array_cache = $array_cache;

        /** @var Cache<string> $cache */
        $cache = new Cache($config, 'test', [], $persistent);
        $cache->saveItem('key', 'item', 'hash');

        $in_memory = new ReflectionProperty(Cache::class, 'cache');
        self::assertSame($retained, $in_memory->getValue($cache) !== []);
        self::assertSame('item', $cache->getItem('key', 'hash'));
    }

    /**
     * @return iterable<string, array{bool, bool, bool}>
     * @psalm-pure
     */
    public static function provideSaveItemRetention(): iterable
    {
        yield 'persistent with array cache' => [true, true, true];
        yield 'persistent without array cache' => [true, false, false];
        yield 'not persistent without array cache' => [false, false, true];
    }

    /** Callers such as ParserCacheProvider keep the previous file contents as the hash to compare them */
    public function testGetHashReturnsTheStoredHashAndNotThePayload(): void
    {
        $this->newCache()->saveItem(self::KEY, 'the payload', 'the hash');

        // A fresh instance, so the in-memory cache cannot mask a bad disk read
        self::assertSame('the hash', $this->newCache()->getHash(self::KEY));
    }

    /** getHash() is called for every file of a run, so it must not read the payload */
    public function testGetHashReadsOnlyTheHeader(): void
    {
        $this->newCache()->saveItem(self::KEY, str_repeat('payload', 100), 'the hash');

        $path = $this->entryPath();
        $entry = file_get_contents($path);
        self::assertNotFalse($entry);
        file_put_contents($path, substr($entry, 0, 28 + strlen('the hash') + strlen(self::KEY) + 5));

        $cache = $this->newCache();
        self::assertSame('the hash', $cache->getHash(self::KEY));
        self::assertNull($cache->getItem(self::KEY, 'the hash'));
    }

    public function testMissesRaiseNoWarning(): void
    {
        $cache = $this->newCache();

        self::assertSame([], self::errorsRaisedBy(static function () use ($cache): void {
            self::assertNull($cache->getHash(self::KEY));
            self::assertNull($cache->getItem(self::KEY));
            self::assertNull($cache->getItem(self::KEY, null));
            self::assertFalse($cache->hasItem(self::KEY));
        }));
    }

    /** A write that fails must leave the previous entry as it was, and no temporary file behind */
    public function testFailedWriteKeepsThePreviousEntry(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            // chmod() only toggles the read-only attribute there, which does not stop files being created in it
            self::markTestSkipped('A directory cannot be made unwritable on Windows');
        }

        $cache = $this->newCache();
        $cache->saveItem(self::KEY, 'old payload', 'old hash');

        $directory = $this->entryDirectory();
        $names = self::namesIn($directory);
        chmod($directory, 0555);
        if (is_writable($directory)) {
            chmod($directory, 0755);
            self::markTestSkipped('The directory stays writable (running as root?)');
        }

        try {
            @$cache->saveItem(self::KEY, 'new payload', 'new hash');
            self::fail('The write must be reported');
        } catch (RuntimeException) {
            // the entry could not be written
        } finally {
            chmod($directory, 0755);
        }

        self::assertSame($names, self::namesIn($directory));
        self::assertNull($this->newCache()->getItem(self::KEY, 'new hash'));
        self::assertSame('old hash', $this->newCache()->getHash(self::KEY));
        self::assertSame('old payload', $this->newCache()->getItem(self::KEY, 'old hash'));
    }

    /** A full disk or a file size limit: the writer must report it and not leave the half-written file */
    public function testShortWriteIsReportedAndLeavesNoTemporaryFile(): void
    {
        if (!function_exists('posix_setrlimit') || !function_exists('pcntl_signal')) {
            self::markTestSkipped('pcntl and posix are required to limit the size of a file');
        }

        $cache = $this->newCache();
        $cache->saveItem(self::KEY, 'old payload', 'old hash');
        $names = self::namesIn($this->entryDirectory());

        $limits = posix_getrlimit();
        self::assertNotFalse($limits);
        $soft = $limits['soft filesize'] === 'unlimited' ? POSIX_RLIMIT_INFINITY : (int) $limits['soft filesize'];
        $hard = $limits['hard filesize'] === 'unlimited' ? POSIX_RLIMIT_INFINITY : (int) $limits['hard filesize'];

        try {
            // writing beyond the limit fails with an error instead of killing the process
            pcntl_signal(SIGXFSZ, SIG_IGN);
            self::assertTrue(posix_setrlimit(POSIX_RLIMIT_FSIZE, 1024, $hard));

            // PHP also reports the failed write as a notice
            set_error_handler(static fn(): bool => true);
            try {
                $cache->saveItem(self::KEY, str_repeat('x', 100_000), 'new hash');
                self::fail('The write must be reported');
            } catch (RuntimeException) {
                // the record could not be written
            } finally {
                restore_error_handler();
            }
        } finally {
            self::assertTrue(posix_setrlimit(POSIX_RLIMIT_FSIZE, $soft, $hard));
            pcntl_signal(SIGXFSZ, SIG_DFL);
        }

        self::assertSame($names, self::namesIn($this->entryDirectory()));
        self::assertSame('old payload', $this->newCache()->getItem(self::KEY, 'old hash'));
    }

    /**
     * Where an open file cannot be replaced (Windows) the rename fails. Here the entry is a directory.
     * The write is skipped without a warning or an exception, and without a temporary file.
     */
    public function testWriteIsSkippedWhenTheEntryCannotBeReplaced(): void
    {
        $cache = $this->newCache();
        $directory = $this->entryDirectory();
        mkdir($directory . '/' . hash('xxh128', self::KEY));

        self::assertSame([], self::errorsRaisedBy(static function () use ($cache): void {
            $cache->saveItem(self::KEY, 'payload', 'hash');
        }));

        $expected = [hash('xxh128', self::KEY), 'lock'];
        sort($expected);
        self::assertSame($expected, self::namesIn($directory));
        // the next lookup in this process does not have to read the disk for it
        self::assertSame('payload', $cache->getItem(self::KEY, 'hash'));
    }

    /** A writer that died before renaming leaves its file behind: it is not the entry, whatever it holds */
    public function testTemporaryFileIsNeverReadAsTheEntry(): void
    {
        $this->newCache()->saveItem(self::KEY, 'the payload', 'the hash');
        $path = $this->entryPath();
        $record = file_get_contents($path);
        self::assertNotFalse($record);
        unlink($path);
        file_put_contents($path . '.0123456789abcdef.tmp', $record);

        $cache = $this->newCache();
        self::assertNull($cache->getHash(self::KEY));
        self::assertNull($cache->getItem(self::KEY, 'the hash'));
        self::assertNull($cache->getItem(self::KEY, null));
        self::assertFalse($cache->hasItem(self::KEY));
    }

    /**
     * A writer that died mid-write leaves a file that is empty or shorter than it declares. Reading,
     * consolidating and checking for changes must treat it as a miss and not throw.
     */
    #[DataProvider('provideUnusableEntries')]
    public function testUnusableEntryIsAMiss(string $entry): void
    {
        $this->newParserCacheProvider()->saveStatementsToCache(self::KEY, 'the hash', [], []);
        file_put_contents($this->entryPath(), $entry);

        $provider = $this->newParserCacheProvider();

        self::assertNull($provider->getHash(self::KEY));
        self::assertNull($provider->loadStatementsFromCache(self::KEY, null));
        self::assertNull($provider->loadStatementsFromCache(self::KEY, 'the hash'));
        self::assertTrue($provider->hasChanged(self::KEY, 'the hash'));

        $provider->consolidate();

        self::assertNull($this->newParserCacheProvider()->getHash(self::KEY));
    }

    /**
     * @return iterable<string, list{string}>
     * @psalm-pure
     */
    public static function provideUnusableEntries(): iterable
    {
        $checksum = str_repeat("\0", 16);

        yield 'empty' => [''];
        yield 'shorter than the prefix' => ["\x01\x02"];
        yield 'prefix only' => [pack('VVV', 8, strlen(self::KEY), 0) . $checksum];
        yield 'declared hash length overruns the file' => [
            pack('VVV', 4096, strlen(self::KEY), 0) . $checksum . 'the hash' . self::KEY,
        ];
        yield 'declared hash length of 4 GiB' => [
            pack('VVV', 0xFFFFFFFF, strlen(self::KEY), 0) . $checksum . 'the hash' . self::KEY,
        ];
        yield 'key one byte short' => [
            pack('VVV', 8, strlen(self::KEY), 0) . $checksum . 'the hash' . substr(self::KEY, 1),
        ];
        yield 'key of another length' => [
            pack('VVV', 8, strlen(self::KEY) - 1, 0) . $checksum . 'the hash' . substr(self::KEY, 1),
        ];
        yield 'other key' => [
            pack('VVV', 8, strlen(self::KEY), 0) . $checksum . 'the hash' . 'src/B.php',
        ];
    }

    /**
     * A header that declares a few gigabytes must not make the reader allocate them: that is a fatal
     * error, not a miss. The memory limit is lowered in a child process, where a fatal error is no
     * more than the status it exits with.
     */
    public function testDeclaredLengthIsNotAllocatedBeforeItIsKnownToBeInTheFile(): void
    {
        if (!function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork() is unavailable');
        }

        $this->newCache()->saveItem(self::KEY, 'the payload', 'the hash');
        file_put_contents(
            $this->entryPath(),
            pack('VVV', 0xFFFFFFFF, strlen(self::KEY), 0) . str_repeat("\0", 16) . 'the hash' . self::KEY,
        );
        $cache = $this->newCache();

        $pid = pcntl_fork();
        self::assertNotSame(-1, $pid);
        if ($pid === 0) {
            // the child must never fall through to PHPUnit
            $status = 1;
            try {
                ini_set('memory_limit', (string) (memory_get_usage() + 32 * 1024 * 1024));
                self::assertNull($cache->getHash(self::KEY));
                self::assertNull($cache->getItem(self::KEY, null));
                $status = 0;
            } finally {
                exit($status);
            }
        }

        pcntl_waitpid($pid, $wait_status);
        self::assertTrue(pcntl_wifexited($wait_status));
        self::assertSame(0, pcntl_wexitstatus($wait_status));
    }

    /** A crashed writer, or a full disk, can leave any prefix of an entry behind. */
    public function testEveryPrefixOfAnEntryIsAMiss(): void
    {
        $this->newCache()->saveItem(self::KEY, 'the payload', 'the hash');

        $path = $this->entryPath();
        $entry = file_get_contents($path);
        self::assertNotFalse($entry);

        // getHash() reads the header only, so it cannot tell that the payload after it is cut short
        $header_length = 28 + strlen('the hash') + strlen(self::KEY);
        for ($length = 0; $length < strlen($entry); ++$length) {
            file_put_contents($path, substr($entry, 0, $length));

            $cache = $this->newCache();
            self::assertNull($cache->getItem(self::KEY, 'the hash'), "prefix of $length bytes");
            self::assertNull($cache->getItem(self::KEY, null), "prefix of $length bytes");
            self::assertSame(
                $length < $header_length ? null : 'the hash',
                $cache->getHash(self::KEY),
                "prefix of $length bytes",
            );
        }

        file_put_contents($path, $entry);
        self::assertSame('the payload', $this->newCache()->getItem(self::KEY, 'the hash'));
    }

    /** Two writers that interleave, or a reader that overlaps a writer, can see bytes of different entries */
    public function testEntryWithAChangedPayloadByteIsAMiss(): void
    {
        $this->newCache()->saveItem(self::KEY, 'the payload', 'the hash');

        $path = $this->entryPath();
        $entry = file_get_contents($path);
        self::assertNotFalse($entry);

        $last = strlen($entry) - 1;
        $entry[$last] = $entry[$last] === 'x' ? 'y' : 'x';
        file_put_contents($path, $entry);

        self::assertNull($this->newCache()->getItem(self::KEY, 'the hash'));
        self::assertNull($this->newCache()->getItem(self::KEY, null));
    }

    /**
     * Another process rewrites an entry over and over while this one reads it. Every read must either
     * miss or return the item that belongs to the hash it came with: never an exception or a warning, and
     * never the hash of one write with the payload of another.
     */
    public function testReadingWhileAnotherProcessRewritesTheEntry(): void
    {
        if (!function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork() is unavailable');
        }

        // built before forking so that the child does not set anything up; the reader keeps
        // nothing in memory, so that every read goes to the disk
        $writer = $this->newCache();
        $reader = $this->newCache(false);
        $writer->saveItem(self::KEY, self::item(0), 'hash 0');

        $writes = 3_000;
        $pid = pcntl_fork();
        self::assertNotSame(-1, $pid);
        if ($pid === 0) {
            // the child must never fall through to PHPUnit
            $status = 1;
            try {
                for ($i = 1; $i <= $writes; ++$i) {
                    $writer->saveItem(self::KEY, self::item($i), 'hash ' . $i);
                }
                $status = 0;
            } finally {
                exit($status);
            }
        }

        $hits = 0;
        $wait_status = 0;
        $errors = self::errorsRaisedBy(static function () use ($reader, $pid, &$hits, &$wait_status): void {
            do {
                $done = pcntl_waitpid($pid, $wait_status, WNOHANG) === $pid;

                for ($i = 0; $i < 50; ++$i) {
                    $hash = $reader->getHash(self::KEY);
                    self::assertTrue($hash === null || preg_match('/^hash \d+$/', $hash) === 1);

                    $item = $reader->getItem(self::KEY, $hash);
                    if ($item === null) {
                        continue;
                    }

                    ++$hits;
                    $written = (int) explode(':', $item, 2)[0];
                    self::assertSame(self::item($written), $item);
                    if ($hash !== null) {
                        self::assertSame('hash ' . $written, $hash);
                    }
                }
            } while (!$done);
        });

        self::assertSame([], $errors);
        self::assertTrue(pcntl_wifexited($wait_status) && pcntl_wexitstatus($wait_status) === 0);
        self::assertGreaterThan(0, $hits);
        self::assertSame(self::item($writes), $this->newCache()->getItem(self::KEY, 'hash ' . $writes));
    }

    /**
     * Processes that write the same entry at the same time: what is left is one of the items that were
     * written, whole, and no file but the entry (and the lock) remains.
     */
    public function testConcurrentWritersLeaveAValidEntry(): void
    {
        if (!function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork() is unavailable');
        }

        $writers = 4;
        $writes = 1_500;
        $caches = [];
        for ($w = 0; $w < $writers; ++$w) {
            $caches[$w] = $this->newCache();
        }

        $pids = [];
        foreach ($caches as $w => $cache) {
            $pid = pcntl_fork();
            self::assertNotSame(-1, $pid);
            if ($pid === 0) {
                // the child must never fall through to PHPUnit
                $status = 1;
                try {
                    for ($i = 1; $i <= $writes; ++$i) {
                        $written = $i * $writers + $w;
                        $cache->saveItem(self::KEY, self::item($written), 'hash ' . $written);
                    }
                    $status = 0;
                } finally {
                    exit($status);
                }
            }
            $pids[] = $pid;
        }

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $wait_status);
            self::assertTrue(pcntl_wifexited($wait_status) && pcntl_wexitstatus($wait_status) === 0);
        }

        $reader = $this->newCache();
        $hash = $reader->getHash(self::KEY);
        self::assertNotNull($hash);
        $item = $reader->getItem(self::KEY, $hash);
        self::assertNotNull($item, 'the entry is torn');
        $written = (int) explode(':', $item, 2)[0];
        self::assertSame(self::item($written), $item);
        self::assertSame('hash ' . $written, $hash);

        $expected = [hash('xxh128', self::KEY), 'lock'];
        sort($expected);
        self::assertSame($expected, self::namesIn($this->entryDirectory()));
    }

    /**
     * Items of different sizes, so that a rewrite changes the length of the entry
     *
     * @psalm-pure
     */
    private static function item(int $i): string
    {
        return $i . ':' . str_repeat('x', ($i * 7) % 20_000);
    }

    public function testExistingCacheDirectoryDoesNotRaiseAWarning(): void
    {
        $this->newCache();

        $errors = [];
        set_error_handler(static function (int $code, string $message) use (&$errors): bool {
            $errors[] = $message;

            return true;
        });

        try {
            // the second instance finds the directory created by the first one
            $this->newCache();
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $errors);
    }

    /**
     * @return iterable<string, list{
     *     list<array{
     *         files: array<string, string|null>,
     *         issues?: array<string, list<string>>,
     *     }>,
     * }>
     * @psalm-pure
     */
    public static function provideCacheInteractions(): iterable
    {
        yield 'deletedFileInvalidatesReferencingMethod' => [
            [
                [
                    'files' => [
                        'src/A.php' => <<<'PHP'
                            <?php
                            final class A {
                                public function do(B $b): void
                                {
                                    $b->do();
                                }
                            }
                            PHP,
                        'src/B.php' => <<<'PHP'
                            <?php
                            final class B {
                                public function do(): void
                                {
                                    echo 'B';
                                }
                            }
                            PHP,
                    ],
                ],
                [
                    'files' => [
                        'src/B.php' => null,
                    ],
                    'issues' => [
                        'src/A.php' => [
                            'UndefinedClass: Class, interface or enum named B does not exist',
                        ],
                    ],
                ],
            ],
        ];

        yield 'classPropertyTypeChangeInvalidatesReferencingMethod' => [
            [
                [
                    'files' => [
                        'src/A.php' => <<<'PHP'
                            <?php
                            final class A {
                                public function foo(B $b): int
                                {
                                    return $b->value;
                                }
                            }
                            PHP,
                        'src/B.php' => <<<'PHP'
                            <?php
                            final class B {
                                public ?int $value = 0;
                            }
                            PHP,
                    ],
                    'issues' => [
                        'src/A.php' => [
                            "NullableReturnStatement: The declared return type 'int' for A::foo is not nullable, but the function returns 'int|null'",
                            "InvalidNullableReturnType: The declared return type 'int' for A::foo is not nullable, but 'int|null' contains null",
                        ],
                    ],
                ],
                [
                    'files' => [
                        'src/B.php' => <<<'PHP'
                            <?php
                            final class B {
                                public int $value = 0;
                            }
                            PHP,
                    ],
                    'issues' => [],
                ],
            ],
        ];

        yield 'classDocblockChange' => [
            [
                [
                    'files' => [
                        'src/A.php' => <<<'PHP'
                            <?php

                            /**
                             * @template T
                             */
                            final class A {
                                /**
                                 * @param T $baz
                                 */
                                public function foo($baz): void
                                {
                                }
                            }
                        PHP,
                        'src/B.php' => <<<'PHP'
                            <?php

                            final class B {
                                public function foo(): void
                                {
                                    (new A)->foo(1);
                                }
                            }
                        PHP,
                    ],
                    'issues' => [],
                ],
                [
                    'files' => [
                        'src/A.php' => <<<'PHP'
                            <?php

                            /**
                             * @template K
                             */
                            final class A {
                                /**
                                 * @param T $baz
                                 */
                                public function foo($baz): void
                                {
                                }
                            }
                        PHP,
                    ],
                    'issues' => [
                        'src/A.php' => [
                            "UndefinedDocblockClass: Docblock-defined class, interface or enum named T does not exist",
                        ],
                        'src/B.php' => [
                            "InvalidArgument: Argument 1 of A::foo expects T, but 1 provided",
                        ],
                    ],
                ],
            ],
        ];

        yield 'constructorPropertyPromotionChange' => [
            [
                [
                    'files' => [
                        'src/A.php' => <<<'PHP'
                            <?php
                            final class A {
                                public function __construct(private string $foo)
                                {
                                }
                                public function bar(): string
                                {
                                    return $this->foo;
                                }
                            }
                            PHP,
                    ],
                    'issues' => [],
                ],
                [
                    'files' => [
                        'src/A.php' => <<<'PHP'
                            <?php
                            final class A
                            {
                                public function __construct()
                                {
                                }
                                public function bar(): string
                                {
                                    return $this->foo;
                                }
                            }
                            PHP,
                    ],
                    'issues' => [
                        'src/A.php' => [
                            "UndefinedThisPropertyFetch: Instance property A::\$foo is not defined",
                            "MixedReturnStatement: Could not infer a return type",
                        ],
                    ],
                ],
            ],
        ];
    }
}
