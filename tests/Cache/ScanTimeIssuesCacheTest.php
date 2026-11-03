<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
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

use function basename;
use function microtime;
use function sort;

use const DIRECTORY_SEPARATOR;

/**
 * The issues found while a file is scanned must be reported again when its storage is loaded from the cache instead
 * of scanning the file.
 */
final class ScanTimeIssuesCacheTest extends TestCase
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
     * @param array<string, string> $files
     * @param list<string> $expected_issues
     * @param string $stub a stub of the config, relative to the base directory of the config
     */
    #[DataProvider('provideScanTimeIssues')]
    public function testScanTimeIssuesAreReportedFromCachedStorage(
        array $files,
        string $php_version,
        array $expected_issues,
        string $stub = '',
    ): void {
        $config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>'
                . ($stub !== '' ? '<stubs><file name="' . $stub . '" /></stubs>' : '')
                . '</psalm>',
        );
        $config->setIncludeCollector(new IncludeCollector());

        $file_provider = new FakeFileProvider();

        foreach ($files as $file_name => $code) {
            $file_provider->registerFile(
                $config->base_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $file_name,
                $code,
            );
        }

        $providers = new Providers(
            $file_provider,
            new ParserCacheProvider($config, '', false),
            new FileStorageCacheProvider($config, '', false),
            new ClassLikeStorageCacheProvider($config, '', false),
            new FileReferenceCacheProvider($config, '', false),
            new ProjectCacheProvider(),
        );

        $runs = [];

        foreach (['cold', 'warm'] as $run) {
            RuntimeCaches::clearAll();

            $project_analyzer = new ProjectAnalyzer($config, $providers);
            $project_analyzer->setPhpVersion($php_version, 'tests');
            $project_analyzer->check($config->base_dir);
            $project_analyzer->finish(microtime(true), PSALM_VERSION);

            $issues = [];

            foreach (IssueBuffer::getIssuesData() as $file_issues) {
                foreach ($file_issues as $issue) {
                    $issues[] = $issue->type . ' ' . basename($issue->file_name) . ':' . $issue->line_from . ':' . $issue->column_from;
                }
            }

            sort($issues);
            $runs[$run] = $issues;
        }

        sort($expected_issues);

        self::assertSame($expected_issues, $runs['cold'], 'cold run');
        self::assertSame($runs['cold'], $runs['warm']);
    }

    /**
     * Each case is the files by name, the PHP version, the issues as `Type file:line:column`, and optionally a stub.
     *
     * @return iterable<string, array{array<string, string>, string, list<string>}|array{array<string, string>, string, list<string>, string}>
     * @psalm-pure
     */
    public static function provideScanTimeIssues(): iterable
    {
        yield 'missingClassConstType' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    abstract class A {
                        public const B = 0;
                    }
                    PHP,
            ],
            '8.3',
            ['MissingClassConstType A.php:3:18'],
        ];

        yield 'suppressedMissingClassConstType' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    abstract class A {
                        /** @psalm-suppress MissingClassConstType */
                        public const B = 0;
                        public const C = 0;
                    }
                    PHP,
            ],
            '8.3',
            ['MissingClassConstType A.php:5:18'],
        ];

        yield 'constantDeclarationInTrait' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    trait T {
                        public const B = 0;
                    }
                    PHP,
            ],
            '8.1',
            ['ConstantDeclarationInTrait A.php:3:5'],
        ];

        yield 'duplicateConstant' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {
                        public const B = 0;
                        public const B = 1;
                    }
                    PHP,
            ],
            '8.1',
            ['DuplicateConstant A.php:4:18'],
        ];

        yield 'duplicateEnumCase' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    enum E {
                        case A;
                        case A;
                    }
                    PHP,
            ],
            '8.1',
            ['DuplicateEnumCase A.php:4:5'],
        ];

        yield 'invalidEnumCaseValue' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    enum E: int {
                        case A = 1.5;
                    }
                    PHP,
            ],
            '8.1',
            ['InvalidEnumCaseValue A.php:3:5', 'InvalidEnumCaseValue A.php:3:5'],
        ];

        yield 'duplicateProperty' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {
                        public int $x = 0;
                        public int $x = 1;
                    }
                    PHP,
            ],
            '8.1',
            ['DuplicateProperty A.php:4:5'],
        ];

        yield 'missingPropertyTypeInReadonlyClass' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final readonly class A {
                        public $x;
                    }
                    PHP,
            ],
            '8.2',
            ['MissingPropertyType A.php:3:12', 'MissingPropertyType A.php:3:5'],
        ];

        yield 'privateFinalMethod' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {
                        private final function f(): void {}
                    }
                    PHP,
            ],
            '8.1',
            ['PrivateFinalMethod A.php:3:5'],
        ];

        yield 'privateFinalMethodInUnusedTrait' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    trait T {
                        private final function f(): void {}
                    }
                    PHP,
            ],
            '8.1',
            ['PrivateFinalMethod A.php:3:5'],
        ];

        yield 'privateFinalMethodInClassWithDuplicateMethod' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {
                        private final function f(): void {}
                        public function g(): void {}
                        public function g(): void {}
                    }
                    PHP,
            ],
            '8.1',
            ['PrivateFinalMethod A.php:3:5', 'DuplicateMethod A.php:5:5'],
        ];

        yield 'allowDynamicPropertiesOnReadonlyClass' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    #[AllowDynamicProperties]
                    final readonly class A {}
                    PHP,
            ],
            '8.2',
            ['InvalidAttribute A.php:2:3'],
        ];

        yield 'unionTypeBeforePhp8' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {
                        public function f(int|string $a): void {}
                    }
                    PHP,
            ],
            '7.4',
            ['ParseError A.php:3:23'],
        ];

        yield 'intersectionTypeBeforePhp81' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    interface I {}
                    interface J {}
                    final class A {
                        public function f(I&J $a): void {}
                    }
                    PHP,
            ],
            '8.0',
            ['ParseError A.php:5:23'],
        ];

        yield 'scalarInIntersectionType' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    interface I {}
                    final class A {
                        public function f(I&int $a): void {}
                    }
                    PHP,
            ],
            '8.1',
            ['ParseError A.php:4:23'],
        ];

        yield 'redefinedCoreFunction' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    function strlen(string $s): int {
                        return 0;
                    }
                    PHP,
            ],
            '8.1',
            ['DuplicateFunction A.php:2:1'],
        ];

        yield 'promotedPropertyDocumentedTwice' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {
                        /** @param int $x */
                        public function __construct(
                            /** @var int */
                            public $x,
                        ) {}
                    }
                    PHP,
            ],
            '8.1',
            ['InvalidDocblock A.php:6:9'],
        ];

        yield 'duplicateClass' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {}
                    PHP,
                'B.php' => <<<'PHP'
                    <?php
                    final class A {}
                    PHP,
            ],
            '8.1',
            ['DuplicateClass B.php:2:13'],
        ];

        yield 'conditionalTaintSource' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {
                        /** @psalm-taint-source (html) */
                        public function f(): string {
                            return 'a';
                        }
                    }
                    PHP,
            ],
            '8.1',
            ['InvalidDocblock A.php:4:5'],
        ];

        yield 'invalidTaintComplementInSink' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {
                        /** @psalm-taint-sink ~(html|(css)) $a */
                        public function f(string $a): string {
                            return $a;
                        }
                    }
                    PHP,
            ],
            '8.1',
            ['InvalidDocblock A.php:4:5'],
        ];

        yield 'stubRepeatingDeclarations' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {}
                    PHP,
            ],
            '8.3',
            [],
            'stubs/RefinedClass.phpstub',
        ];
    }
}
