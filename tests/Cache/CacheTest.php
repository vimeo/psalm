<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use InvalidArgumentException;
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

use function chmod;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function flock;
use function fopen;
use function function_exists;
use function glob;
use function hash;
use function is_writable;
use function microtime;
use function pack;
use function pcntl_fork;
use function pcntl_waitpid;
use function pcntl_wexitstatus;
use function pcntl_wifexited;
use function restore_error_handler;
use function set_error_handler;
use function str_replace;
use function substr;
use function sys_get_temp_dir;
use function uniqid;
use function usleep;

use const DIRECTORY_SEPARATOR;
use const LOCK_SH;
use const LOCK_UN;

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
    private function newCache(): Cache
    {
        return new Cache($this->newConfig(), 'test');
    }

    private function newParserCacheProvider(): ParserCacheProvider
    {
        return new ParserCacheProvider($this->newConfig(), '');
    }

    /** The header file of self::KEY: <cache directory>/<subdir>/<config hash>/<hash of the key>.hash */
    private function headerPath(): string
    {
        $paths = glob($this->cache_directory . '/*/*/' . hash('xxh128', self::KEY) . '.hash');

        self::assertNotFalse($paths);
        self::assertCount(1, $paths);

        return $paths[0];
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

    /** A writer killed mid-write must not leave a valid header of the new contents in front of the old payload */
    public function testFailedWriteDoesNotLeaveANewHeaderInFrontOfTheOldPayload(): void
    {
        $this->newCache()->saveItem(self::KEY, 'old payload', 'old hash');

        $payload_path = substr($this->headerPath(), 0, -5);
        chmod($payload_path, 0444);
        if (is_writable($payload_path)) {
            chmod($payload_path, 0644);
            self::markTestSkipped('The payload stays writable (running as root?)');
        }

        try {
            @$this->newCache()->saveItem(self::KEY, 'new payload', 'new hash');
        } catch (InvalidArgumentException) {
            // the payload could not be written
        } finally {
            chmod($payload_path, 0644);
        }

        self::assertNull($this->newCache()->getItem(self::KEY, 'new hash'));
        self::assertNull($this->newCache()->getHash(self::KEY));
    }

    /**
     * A writer that died mid-write leaves a header that is empty or shorter than it declares. Reading,
     * consolidating and checking for changes must treat it as a miss and not throw.
     */
    #[DataProvider('provideUnusableHeaders')]
    public function testUnusableHeaderIsAMiss(string $header): void
    {
        $this->newParserCacheProvider()->saveStatementsToCache(self::KEY, 'the hash', [], []);
        file_put_contents($this->headerPath(), $header);

        $provider = $this->newParserCacheProvider();

        self::assertNull($provider->getHash(self::KEY));
        self::assertNull($provider->loadStatementsFromCache(self::KEY, null));
        self::assertNull($provider->loadStatementsFromCache(self::KEY, 'the hash'));
        self::assertTrue($provider->hasChanged(self::KEY, 'the hash'));

        $provider->consolidate();
    }

    /**
     * @return iterable<string, list{string}>
     * @psalm-pure
     */
    public static function provideUnusableHeaders(): iterable
    {
        yield 'empty' => [''];
        yield 'length prefix only' => [pack('V', 8)];
        yield 'declared length overruns the header' => [pack('V', 4096) . 'the hash' . self::KEY];
        yield 'key cut short' => [pack('V', 8) . 'the hash' . substr(self::KEY, 0, 4)];
    }

    /**
     * A writer waiting for a reader must not empty the header it is about to replace: truncating it on open
     * shows readers an empty header for as long as the reader holds the lock. On a very slow machine the child
     * may not reach the header within the polling window and the bug goes unnoticed, but the test never fails
     * spuriously.
     */
    public function testWriterWaitingForAReaderDoesNotEmptyTheHeader(): void
    {
        if (!function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork() is unavailable');
        }

        // built before forking so the child reaches the header within the polling window
        $writer = $this->newCache();
        $writer->saveItem(self::KEY, 'old payload', 'old hash');

        $header_path = $this->headerPath();
        $old_header = file_get_contents($header_path);
        $reader = fopen($header_path, 'r');
        self::assertNotFalse($reader);
        self::assertTrue(flock($reader, LOCK_SH));

        $pid = pcntl_fork();
        self::assertNotSame(-1, $pid);
        if ($pid === 0) {
            // the child must never fall through to PHPUnit
            $status = 1;
            try {
                $writer->saveItem(self::KEY, 'new payload', 'new hash');
                $status = 0;
            } finally {
                exit($status);
            }
        }

        try {
            for ($i = 0; $i < 20; $i++) {
                usleep(10_000);
                self::assertSame($old_header, file_get_contents($header_path), 'the header changed under the reader');
            }
        } finally {
            flock($reader, LOCK_UN);
            fclose($reader);
        }

        pcntl_waitpid($pid, $wait_status);
        self::assertTrue(pcntl_wifexited($wait_status) && pcntl_wexitstatus($wait_status) === 0);
        self::assertSame('new payload', $this->newCache()->getItem(self::KEY, 'new hash'));
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
