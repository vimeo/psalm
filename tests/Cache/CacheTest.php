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

use function array_keys;
use function microtime;
use function restore_error_handler;
use function set_error_handler;
use function str_replace;
use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;
use const E_WARNING;

final class CacheTest extends TestCase
{
    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to isolate Psalm from PHPUnit cli arguments
        global $argv;
        $argv = [];
    }

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        RuntimeCaches::clearAll();
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
        $config->setCustomErrorLevel(
            'MissingImmutableAnnotation',
            Config::REPORT_SUPPRESS,
        );
        $config->setCustomErrorLevel(
            'MissingInterfaceImmutableAnnotation',
            Config::REPORT_SUPPRESS,
        );
        $config->setCustomErrorLevel(
            'MissingPureAnnotation',
            Config::REPORT_SUPPRESS,
        );
        $config->setCustomErrorLevel(
            'MissingAbstractPureAnnotation',
            Config::REPORT_SUPPRESS,
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
        $config = new TestConfig();
        $config->cache_directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'psalm-cache-test-' . uniqid('', true);
        $config->array_cache = $array_cache;

        /** @var Cache<string> $cache */
        $cache = new Cache($config, 'test', [], $persistent);
        $cache->saveItem('key', 'item', 'hash');

        $in_memory = new ReflectionProperty(Cache::class, 'cache');
        self::assertSame($retained, $in_memory->getValue($cache) !== []);
        self::assertSame('item', $cache->getItem('key', 'hash'));
    }

    public function testConsolidatedEntriesAreOnlyUnserializedWhenUsed(): void
    {
        $config = $this->createCacheConfig();

        $cache = $this->openCache($config);
        $cache->saveItem('a', 'item a', 'hash a');
        $cache->saveItem('b', 'item b', 'hash b');
        $cache->consolidate();
        unset($cache);

        $cache = $this->openCache($config);
        $in_memory = new ReflectionProperty(Cache::class, 'cache');
        self::assertSame([], $in_memory->getValue($cache));

        self::assertSame('hash b', $cache->getHash('b'));
        self::assertNull($cache->getHash('missing'));
        self::assertNull($cache->getItem('a', 'stale hash'));
        self::assertSame([], $in_memory->getValue($cache));

        self::assertSame('item a', $cache->getItem('a', 'hash a'));
        $in_memory_entries = $in_memory->getValue($cache);
        self::assertIsArray($in_memory_entries);
        self::assertSame(['a'], array_keys($in_memory_entries));
        self::assertSame('item a', $cache->getItem('a', null));
    }

    public function testConsolidatingAgainKeepsEntriesNobodyUsed(): void
    {
        $config = $this->createCacheConfig();

        $cache = $this->openCache($config);
        $cache->saveItem('used', 'old', 'old hash');
        $cache->saveItem('unused', 'item', 'hash');
        $cache->consolidate();
        unset($cache);

        $cache = $this->openCache($config);
        // the hash is unchanged, so this must not replace the consolidated item
        $cache->saveItem('unused', 'ignored', 'hash');
        $cache->saveItem('used', 'new', 'new hash');
        $cache->saveItem('added', 'added item', 'added hash');
        $cache->consolidate();
        unset($cache);

        $cache = $this->openCache($config);
        self::assertSame('item', $cache->getItem('unused', 'hash'));
        self::assertSame('new', $cache->getItem('used', 'new hash'));
        self::assertNull($cache->getItem('used', 'old hash'));
        self::assertSame('added item', $cache->getItem('added', 'added hash'));
    }

    /**
     * @return Cache<string>
     */
    private function openCache(TestConfig $config): Cache
    {
        // Cache creates its directory unless it exists, which PHPUnit would report as a warning
        set_error_handler(static fn(): bool => true, E_WARNING);
        try {
            return new Cache($config, 'test', [], true);
        } finally {
            restore_error_handler();
        }
    }

    private function createCacheConfig(): TestConfig
    {
        $config = new TestConfig();
        $config->cache_directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'psalm-cache-test-' . uniqid('', true);
        $config->array_cache = true;

        return $config;
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
