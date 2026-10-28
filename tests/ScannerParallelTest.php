<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\Scanner;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Scanner\UnresolvedConstant\ClassConstant;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\Progress\RecordingProgress;
use Psalm\Type;
use ReflectionMethod;
use Symfony\Component\Filesystem\Filesystem;

use function array_fill_keys;
use function define;
use function defined;
use function extension_loaded;
use function in_array;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;

/**
 * The files an autoloader includes are scanned by several processes, which can't follow the order of the files:
 * what a file makes of the constants and functions of the files before it must not depend on that.
 */
final class ScannerParallelTest extends TestCase
{
    /** more files than a scan forks for */
    private const FILES = 70;

    private RecordingProgress $progress;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->progress = new RecordingProgress();

        $this->project_analyzer = new ProjectAnalyzer(
            $this->testConfig,
            new Providers($this->file_provider, new FakeParserCacheProvider()),
            null,
            [],
            1,
            3,
            $this->progress,
        );
    }

    /**
     * @return iterable<string, array{
     *     array<int, string>,
     *     array<string, string>,
     *     array<lowercase-string, string>,
     *     array<string, string>,
     *     ?bool,
     * }>
     * @psalm-pure
     */
    public static function provideAutoloadFiles(): iterable
    {
        yield 'the first declaration of a function counts' => [
            [
                1 => '<?php function dup(): int { return 1; }',
                self::FILES => '<?php if (!function_exists("dup")) { function dup(): string { return ""; } }',
            ],
            [],
            ['dup' => 'int'],
            [],
            false,
        ];

        yield 'the first declaration of a function in a file counts' => [
            [
                1 => '<?php if (!function_exists("dup")) { function dup(): int { return 1; } }'
                    . ' if (!function_exists("dup")) { function dup(): string { return ""; } }',
            ],
            [],
            ['dup' => 'int'],
            [],
            false,
        ];

        yield 'a function an earlier file declared is left alone' => [
            [
                1 => '<?php function dup(): int { return 1; }',
                2 => '<?php function dup(): float { return 1.0; }',
                3 => '<?php function dup(): string { return ""; }',
            ],
            [],
            ['dup' => 'int'],
            [],
            false,
        ];

        yield 'a constant defined the same by two files' => [
            [
                1 => '<?php define("SAME", "a");',
                self::FILES => '<?php if (!defined("SAME")) { define("SAME", "a"); }',
            ],
            ['SAME' => "'a'"],
            [],
            [],
            false,
        ];

        // the processes agree on the constant if each scans one of the last files, so a rescan isn't certain
        yield 'the last definition of a constant counts' => [
            [
                1 => '<?php define("TWICE", "first");',
                2 => '<?php if (!defined("TWICE")) { define("TWICE", "first"); }',
                3 => '<?php if (!defined("TWICE")) { define("TWICE", "first"); }',
                self::FILES - 2 => '<?php if (!defined("TWICE")) { define("TWICE", "second"); }',
                self::FILES - 1 => '<?php if (!defined("TWICE")) { define("TWICE", "second"); }',
                self::FILES => '<?php if (!defined("TWICE")) { define("TWICE", "second"); }',
            ],
            ['TWICE' => "'second'"],
            [],
            [],
            null,
        ];

        // every file but the first one looks BASE up, so a process other than the one that scans the first file does
        $derived = [1 => '<?php define("BASE", (/** @var non-empty-string */ "abc"));'];
        for ($i = 2; $i <= self::FILES; $i++) {
            $derived[$i] = sprintf('<?php define("DERIVED_%d", BASE);', $i);
        }

        yield 'a constant defined from the type of a constant of another file' => [
            $derived,
            ['BASE' => 'non-empty-string', 'DERIVED_2' => 'non-empty-string', 'DERIVED_70' => 'non-empty-string'],
            [],
            [],
            true,
        ];

        yield 'a constant defined from a constant of the same file' => [
            [
                1 => '<?php define("BASE", (/** @var non-empty-string */ "abc")); define("DERIVED", BASE);',
            ],
            ['BASE' => 'non-empty-string', 'DERIVED' => 'non-empty-string'],
            [],
            [],
            false,
        ];

        yield 'a constant defined from a constant of PHP' => [
            [
                1 => '<?php define("FLAGS", E_ALL | E_STRICT); define("MAX", PHP_INT_MAX);',
                2 => '<?php define("FLAGS_2", E_ALL | E_STRICT); define("MAX_2", PHP_INT_MAX);',
            ],
            ['FLAGS' => '32767', 'MAX_2' => 'int<1, max>'],
            [],
            [],
            false,
        ];

        yield 'an unresolved constant defined the same by two files' => [
            [
                1 => '<?php const CASE_A = Suit::Hearts;',
                self::FILES => '<?php const CASE_A = Suit::Hearts;',
            ],
            [],
            [],
            ['CASE_A' => 'Hearts'],
            false,
        ];

        yield 'the last definition of an unresolved constant counts' => [
            [
                1 => '<?php const CASE_A = Suit::Hearts;',
                2 => '<?php const CASE_A = Suit::Hearts;',
                3 => '<?php const CASE_A = Suit::Hearts;',
                self::FILES - 2 => '<?php const CASE_A = Suit::Spades;',
                self::FILES - 1 => '<?php const CASE_A = Suit::Spades;',
                self::FILES => '<?php const CASE_A = Suit::Spades;',
            ],
            [],
            [],
            ['CASE_A' => 'Spades'],
            null,
        ];
    }

    /**
     * @param array<int, string> $files the contents of the autoloaded files by position, the others declare a function
     * @param array<string, string> $constants the ids of the types the constants have afterwards
     * @param array<lowercase-string, string> $functions the ids of the return types the functions have afterwards
     * @param array<string, string> $unresolved the class constants the unresolved constants refer to afterwards
     * @param ?bool $rescanned whether the files had to be scanned again in order, if it is certain
     */
    #[DataProvider('provideAutoloadFiles')]
    public function testAutoloadFilesAreRegisteredInOrder(
        array $files,
        array $constants,
        array $functions,
        array $unresolved,
        ?bool $rescanned,
    ): void {
        $this->scanAutoloadedFiles($files);

        $codebase = $this->project_analyzer->getCodebase();

        $this->assertTrue(in_array('Forking process for scanning' . "\n", $this->progress->debug_messages, true));

        if ($rescanned !== null) {
            $this->assertSame(
                $rescanned,
                in_array('Scanning the files again, one after another' . "\n", $this->progress->debug_messages, true),
            );
        }

        foreach ($constants as $name => $type_id) {
            $this->assertSame($type_id, $codebase->getStubbedConstantType($name)?->getId(), $name);
        }

        foreach ($unresolved as $name => $case) {
            $value = $codebase->getUnresolvedGlobalConstant($name);
            $this->assertInstanceOf(ClassConstant::class, $value, $name);
            $this->assertSame($case, $value->name, $name);
        }

        foreach ($functions as $function_id => $return_type_id) {
            $this->assertSame(
                $return_type_id,
                $codebase->functions->getAllStubbedFunctions()[$function_id]->return_type?->getId(),
                $function_id,
            );
        }
    }

    /**
     * @return iterable<string, array{array<array-key, list<string>>, array<array-key, list<string>>, bool}>
     * @psalm-pure
     */
    public static function provideProcessesDefiningAndLookingUpConstants(): iterable
    {
        yield 'a process looks up a constant only it defines' => [[0 => ['X']], [0 => ['X']], false];

        yield 'a constant nobody defines' => [[0 => ['Y']], [0 => ['Y']], false];

        yield 'a process looks up a constant another one defines' => [[0 => ['X'], 1 => []], [1 => ['X']], true];

        yield 'a constant two processes define the same, one of them looks it up' => [
            [0 => ['X'], 1 => ['X']],
            [1 => ['X']],
            true,
        ];

        yield 'a constant two processes define the same, the first looks it up' => [
            [0 => ['X'], 1 => ['X']],
            [0 => ['X']],
            true,
        ];
    }

    /**
     * @param array<array-key, list<string>> $defined the constants each process defines, as `int`
     * @param array<array-key, list<string>> $looked_up the constants each process looks up without knowing them
     */
    #[DataProvider('provideProcessesDefiningAndLookingUpConstants')]
    public function testDependsOnScanOrderWhenAnotherProcessDefinesALookedUpConstant(
        array $defined,
        array $looked_up,
        bool $expected,
    ): void {
        $pool_data = [];
        foreach ($defined + $looked_up as $process => $_) {
            $pool_data[$process] = [
                'global_constants' => array_fill_keys($defined[$process] ?? [], Type::getInt()),
                'unresolved_global_constants' => [],
                'missed_global_constants' => array_fill_keys($looked_up[$process] ?? [], true),
            ];
        }

        $this->assertSame(
            $expected,
            (new ReflectionMethod(Scanner::class, 'dependsOnScanOrder'))->invoke(null, $pool_data),
        );
    }

    public function testTheStorageOfAllFilesIsCachedOnceTheScanIsAccepted(): void
    {
        $cache_directory = sys_get_temp_dir() . '/' . uniqid('ScannerParallelTest_');
        $this->testConfig->cache_directory = $cache_directory;

        try {
            $this->project_analyzer = new ProjectAnalyzer(
                $this->testConfig,
                new Providers(
                    $this->file_provider,
                    new FakeParserCacheProvider(),
                    new FileStorageCacheProvider($this->testConfig, '', true),
                ),
                null,
                [],
                1,
                3,
                $this->progress,
            );

            // a constant that nothing defines
            $files = [2 => '<?php define("A", UNKNOWN);'];
            $this->scanAutoloadedFiles($files);

            $this->assertFalse(
                in_array('Scanning the files again, one after another' . "\n", $this->progress->debug_messages, true),
            );

            $cache = $this->project_analyzer->getCodebase()->file_storage_provider->cache;
            $this->assertNotNull($cache);

            for ($i = 1; $i <= self::FILES; $i++) {
                $path = sprintf('/autoload/f%02d.php', $i);
                $this->assertNotNull(
                    $cache->getLatestFromCache($path, $this->file_provider->getContents($path)),
                    $path,
                );
            }
        } finally {
            (new Filesystem())->remove($cache_directory);
        }
    }

    public function testClosuresAreNotGlobalFunctions(): void
    {
        $this->scanAutoloadedFiles([
            1 => '<?php function named(): int { return 1; } $closure = function (): int { return 2; };'
                . ' $arrow = fn(): int => 3;',
        ]);

        $functions = $this->project_analyzer->getCodebase()->functions->getAllStubbedFunctions();

        $this->assertArrayHasKey('named', $functions);
        foreach ($functions as $function_id => $function) {
            $this->assertNotNull($function->cased_name, $function_id);
        }
    }

    public function testScanRecordsTheConstantsItDoesNotKnowYet(): void
    {
        $codebase = $this->project_analyzer->getCodebase();

        $this->file_provider->registerFile(
            '/autoload/a.php',
            '<?php define("KNOWN", 1); define("A", UNKNOWN . E_ALL); define("B", KNOWN); define("C", PHP_INT_MAX);',
        );
        $codebase->scanner->addFileToDeepScan('/autoload/a.php');

        $codebase->register_autoload_files = true;
        $codebase->scanner->scanFiles($codebase->classlikes);
        $codebase->register_autoload_files = false;

        $this->assertSame(['UNKNOWN' => true], $codebase->getMissedGlobalConstants());
    }

    public function testScanOfProjectFilesDoesNotRecordConstants(): void
    {
        $codebase = $this->project_analyzer->getCodebase();

        $this->file_provider->registerFile('/src/a.php', '<?php define("A", UNKNOWN);');
        $codebase->scanner->addFileToDeepScan('/src/a.php');
        $codebase->scanner->scanFiles($codebase->classlikes);

        $this->assertSame([], $codebase->getMissedGlobalConstants());
    }

    public function testInternalConstantsAreNotTheOnesCodeDefined(): void
    {
        if (!defined('PSALM_TEST_USER_CONSTANT')) {
            define('PSALM_TEST_USER_CONSTANT', null);
        }

        $config = new TestConfig();
        $config->collectPredefinedConstants();

        $this->assertTrue($config->isInternalConstant('E_ALL'));
        $this->assertFalse($config->isInternalConstant('PSALM_TEST_USER_CONSTANT'));
        $this->assertFalse($config->isInternalConstant('PSALM_TEST_NO_SUCH_CONSTANT'));
    }

    /**
     * @param array<int, string> $files the contents of the autoloaded files by position, the others declare a function
     */
    private function scanAutoloadedFiles(array $files): void
    {
        if (!extension_loaded('pcntl') || !extension_loaded('posix')) {
            $this->markTestSkipped('Psalm scans in one process without pcntl and posix');
        }

        $codebase = $this->project_analyzer->getCodebase();

        for ($i = 1; $i <= self::FILES; $i++) {
            $path = sprintf('/autoload/f%02d.php', $i);
            $this->file_provider->registerFile(
                $path,
                $files[$i] ?? sprintf('<?php function filler%d(): int { return %d; }', $i, $i),
            );
            $codebase->scanner->addFileToDeepScan($path);
        }

        $codebase->register_autoload_files = true;
        $codebase->scanner->scanFiles($codebase->classlikes, 3);
        $codebase->register_autoload_files = false;
    }
}
