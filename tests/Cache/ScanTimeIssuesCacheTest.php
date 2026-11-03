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

        // in-memory caches: the warm run gets the storages of the cold one instead of scanning the files again
        $providers = new Providers(
            $file_provider,
            new ParserCacheProvider($config, '', false),
            new FileStorageCacheProvider($config, '', false),
            new ClassLikeStorageCacheProvider($config, '', false),
            new FileReferenceCacheProvider($config, '', false),
            new ProjectCacheProvider(),
        );

        $cold = $this->analyze($config, $providers, $php_version);

        sort($expected_issues);

        self::assertSame($expected_issues, $cold, 'cold run');
        self::assertSame($cold, $this->analyze($config, $providers, $php_version), 'warm run');
    }

    /**
     * Each case is the files by name, the PHP version, the issues as `Type file:line:column`, and optionally a stub.
     * Each case groups the issues that need the same PHP version.
     *
     * @return iterable<string, array{array<string, string>, string, list<string>}|array{array<string, string>, string, list<string>, string}>
     * @psalm-pure
     */
    public static function provideScanTimeIssues(): iterable
    {
        // the untyped constant C is suppressed
        yield 'classLike' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    abstract class A {
                        public const B = 0;
                        public const B = 1;
                        /** @psalm-suppress MissingClassConstType */
                        public const C = 0;
                        public int $x = 0;
                        public int $x = 1;
                    }
                    #[AllowDynamicProperties]
                    final readonly class D {}
                    final readonly class R {
                        public $x;
                    }
                    PHP,
            ],
            '8.3',
            [
                'MissingClassConstType A.php:3:18',
                'DuplicateConstant A.php:4:18',
                'DuplicateProperty A.php:8:5',
                'InvalidAttribute A.php:10:3',
                'MissingPropertyType A.php:13:12',
                'MissingPropertyType A.php:13:5',
            ],
        ];

        // stored in the class-like
        yield 'enum' => [
            [
                'E.php' => <<<'PHP'
                    <?php
                    enum E {
                        case A;
                        case A;
                    }
                    enum F: int {
                        case A = 1.5;
                    }
                    enum G {
                        const B = 0;
                        case B;
                    }
                    PHP,
            ],
            '8.3',
            [
                'DuplicateEnumCase E.php:4:5',
                'InvalidEnumCaseValue E.php:7:5',
                'InvalidEnumCaseValue E.php:7:5',
                'DuplicateConstant E.php:11:5',
            ],
        ];

        // a private final method, and a promoted property documented twice
        yield 'method' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {
                        private final function f(): void {}
                        /** @param int $x */
                        public function __construct(
                            /** @var int */
                            public $x,
                        ) {}
                    }
                    PHP,
            ],
            '8.1',
            ['PrivateFinalMethod A.php:3:5', 'InvalidDocblock A.php:7:9'],
        ];

        // a taint that can't be registered is in the docblock parsed, or in the storage built
        yield 'taintDocblocks' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {
                        /** @psalm-taint-source (html) */
                        public function f(): string {
                            return 'a';
                        }
                        /** @psalm-taint-sink ~(html|(css)) $a */
                        public function g(string $a): string {
                            return $a;
                        }
                        /**
                         * @psalm-taint-unescape ~(html|(css))
                         * @psalm-taint-escape ~(html|(js))
                         */
                        public function h(): void {}
                    }
                    PHP,
            ],
            '8.1',
            [
                'InvalidDocblock A.php:4:5',
                'InvalidDocblock A.php:8:5',
                'InvalidDocblock A.php:15:5',
                'InvalidDocblock A.php:15:5',
            ],
        ];

        // the PHP version predates the syntax
        yield 'oldPhpVersion' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    interface I {}
                    interface J {}
                    final class A {
                        public function f(int|string $a): void {}
                        public function g(I&J $a): void {}
                        public function h(I&int $a): void {}
                    }
                    trait T {
                        public const B = 0;
                    }
                    function strlen(string $s): int {
                        return 0;
                    }
                    PHP,
            ],
            '7.4',
            [
                'ParseError A.php:5:23',
                'ParseError A.php:6:23',
                'ParseError A.php:7:23',
                'ParseError A.php:7:23',
                'ConstantDeclarationInTrait A.php:10:5',
                'DuplicateFunction A.php:12:1',
            ],
        ];

        // no class uses the trait, so its methods aren't analysed
        yield 'unusedTrait' => [
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

        // the duplicate method makes the analysis skip the whole file
        yield 'skippedFile' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    function strlen(string $s): int {
                        return 0;
                    }
                    final class A {
                        private final function f(): void {}
                        public function g(): void {}
                        public function g(): void {}
                    }
                    PHP,
            ],
            '8.1',
            ['DuplicateFunction A.php:2:1', 'PrivateFinalMethod A.php:6:5', 'DuplicateMethod A.php:8:5'],
        ];

        // the class declared again by another file makes the analysis skip it, not its file
        yield 'skippedClassLike' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {
                        private final function f(): void {}
                    }
                    PHP,
                'B.php' => <<<'PHP'
                    <?php
                    final class A {}
                    PHP,
            ],
            '8.1',
            ['PrivateFinalMethod A.php:3:5', 'DuplicateClass B.php:2:13'],
        ];

        // the early returns of the analysis of the class-likes don't lose the issues found while scanning them
        yield 'earlyReturns' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A extends UndefinedParent {
                        private final function f(): void {}
                    }
                    final class B implements UndefinedInterface {
                        private final function g(): void {}
                    }
                    interface I extends UndefinedI {
                        public const B = 0;
                        public const B = 1;
                    }
                    PHP,
            ],
            '8.3',
            [
                'UndefinedClass A.php:2:23',
                'PrivateFinalMethod A.php:3:5',
                'UndefinedClass A.php:5:26',
                'PrivateFinalMethod A.php:6:5',
                'MissingClassConstType A.php:9:18',
                'DuplicateConstant A.php:10:18',
            ],
        ];

        // the issues found while scanning a stub aren't reported, as the stubs aren't analysed
        yield 'stubIssuesAreNotReported' => [
            [
                'A.php' => <<<'PHP'
                    <?php
                    final class A {}
                    PHP,
            ],
            '8.3',
            [],
            'stubs/ScanIssues.phpstub',
        ];
    }

    /**
     * @return list<string> the issues as `Type file:line:column`, sorted
     */
    private function analyze(Config $config, Providers $providers, string $php_version): array
    {
        RuntimeCaches::clearAll();

        $project_analyzer = new ProjectAnalyzer($config, $providers);
        $project_analyzer->setPhpVersion($php_version, 'tests');
        $project_analyzer->check($config->base_dir);
        $project_analyzer->finish(microtime(true), PSALM_VERSION);

        $issues = [];

        foreach (IssueBuffer::getIssuesData() as $file_issues) {
            foreach ($file_issues as $issue) {
                $issues[] = $issue->type . ' ' . basename($issue->file_name) . ':' . $issue->line_from . ':'
                    . $issue->column_from;
            }
        }

        sort($issues);

        return $issues;
    }
}
