<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Tests\TestCase;

use function array_keys;
use function array_map;
use function getcwd;

use const DIRECTORY_SEPARATOR;

/**
 * A class declared in several files belongs to the file a single process keeps, however the scanner's processes
 * finish.
 */
final class DuplicateClassWinnerTest extends TestCase
{
    private const SOURCES = [
        'a' => '<?php
namespace Lib;
if (!class_exists(Lazy::class, false)) {
    final class Lazy { public function trans(string $id): string { return $id; } }
}',
        'b' => '<?php
namespace Lib;
if (!class_exists(Lazy::class, false)) {
    final class Lazy { /** @return mixed */ public function trans(string $id) { return $id; } }
}',
    ];

    private string $dir;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->dir = (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'duplicate_class' . DIRECTORY_SEPARATOR;
        $this->project_analyzer->getCodebase()->config->throw_exception = false;

        // the issues of the project files are the ones reported
        foreach (['a', 'b'] as $name) {
            $this->project_analyzer->getCodebase()->analyzer->addFilesToAnalyze(
                [$this->dir . $name . '.php' => $this->dir . $name . '.php'],
            );
        }

        ClassLikeStorageProvider::deleteAll();
        FileStorageProvider::deleteAll();
        IssueBuffer::clear();
    }

    /**
     * @param list<string> $arrival the processes, in the order they finished in (w1 scanned a, w2 scanned b)
     */
    #[DataProvider('providerArrival')]
    public function testPoolMergeKeepsTheDeclarationInTheSmallestPath(array $arrival): void
    {
        $processes = [
            'w1' => $this->scanAlone('a'),
            'w2' => $this->scanAlone('b'),
        ];

        foreach ($arrival as $process) {
            [$file_path, $classlike_storages] = $processes[$process];
            $codebase = $this->project_analyzer->getCodebase();
            $codebase->classlike_storage_provider->addMore(
                $classlike_storages,
                [],
                $codebase->analysis_php_version_id,
            );
            $codebase->scanner->addThreadData([[], [], [], [], [], ['lib\lazy' => $file_path], [], [], [], [], []]);
        }

        $winner_file = $this->dir . 'a.php';
        $loser_file = $this->dir . 'b.php';

        self::assertSame($processes['w1'][1]['lib\lazy'], ClassLikeStorageProvider::getAll()['lib\lazy']);
        self::assertSame(
            $winner_file,
            $this->project_analyzer->getCodebase()->scanner->getClassLikeFilePath('lib\lazy'),
        );

        $issues = IssueBuffer::getIssuesData();
        self::assertSame([$loser_file], array_keys($issues));
        self::assertCount(1, $issues[$loser_file]);
        self::assertSame('DuplicateClass', $issues[$loser_file][0]->type);
        self::assertSame(
            'Class Lib\Lazy has already been defined in ' . $winner_file,
            $issues[$loser_file][0]->message,
        );
    }

    /**
     * Declarations in files whose issues are not reported are alternatives, like polyfills of a dependency: the
     * one in the smallest path is kept, and the classes extending it are still analysed.
     *
     * @param list<string> $arrival the processes, in the order they finished in (w1 scanned a, w2 scanned b)
     */
    #[DataProvider('providerArrival')]
    public function testPoolMergeOfDeclarationsInUnreportedFilesIsNotADuplicate(array $arrival): void
    {
        $this->dir = (string) getcwd() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'duplicate_class'
            . DIRECTORY_SEPARATOR;

        $processes = [
            'w1' => $this->scanAlone('a'),
            'w2' => $this->scanAlone('b'),
        ];

        foreach ($arrival as $process) {
            [$file_path, $classlike_storages] = $processes[$process];
            $codebase = $this->project_analyzer->getCodebase();
            $codebase->classlike_storage_provider->addMore(
                $classlike_storages,
                [],
                $codebase->analysis_php_version_id,
            );
            $codebase->scanner->addThreadData([[], [], [], [], [], ['lib\lazy' => $file_path], [], [], [], [], []]);
        }

        $kept = ClassLikeStorageProvider::getAll()['lib\lazy'];

        self::assertSame($processes['w1'][1]['lib\lazy'], $kept);
        self::assertFalse($kept->has_visitor_issues);
        self::assertSame([], IssueBuffer::getIssuesData());
    }

    /**
     * The project's declaration wins over a dependency's, even in a larger path, and is not a duplicate.
     *
     * @param list<string> $arrival the processes, in the order they finished in (w1 scanned a, w2 scanned b)
     */
    #[DataProvider('providerArrival')]
    public function testPoolMergeKeepsTheProjectDeclarationOverADependencyOne(array $arrival): void
    {
        $processes = [
            'w1' => $this->scanAlone('a', (string) getcwd() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR),
            'w2' => $this->scanAlone('b'),
        ];

        foreach ($arrival as $process) {
            [$file_path, $classlike_storages] = $processes[$process];
            $codebase = $this->project_analyzer->getCodebase();
            $codebase->classlike_storage_provider->addMore(
                $classlike_storages,
                [],
                $codebase->analysis_php_version_id,
            );
            $codebase->scanner->addThreadData([[], [], [], [], [], ['lib\lazy' => $file_path], [], [], [], [], []]);
        }

        $kept = ClassLikeStorageProvider::getAll()['lib\lazy'];

        self::assertSame($processes['w2'][1]['lib\lazy'], $kept);
        self::assertFalse($kept->has_visitor_issues);
        self::assertSame(
            $this->dir . 'b.php',
            $this->project_analyzer->getCodebase()->scanner->getClassLikeFilePath('lib\lazy'),
        );
        self::assertSame([], IssueBuffer::getIssuesData());
    }

    /**
     * The project's declaration replaces a dependency's that the merging process scanned before the pool.
     */
    public function testPoolMergeKeepsTheProjectDeclarationOverADependencyOneScannedBeforeThePool(): void
    {
        [$worker_file, $worker_storages] = $this->scanAlone('b');
        $codebase = $this->project_analyzer->getCodebase();
        $earlier_file = (string) getcwd() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'a.php';

        $this->file_provider->registerFile($earlier_file, self::SOURCES['a']);
        $codebase->scanner->addFileToShallowScan($earlier_file);
        $codebase->scanner->scanFiles($codebase->classlikes);
        $earlier = ClassLikeStorageProvider::getAll();

        $codebase->classlike_storage_provider->addMore($worker_storages, $earlier, $codebase->analysis_php_version_id);
        $codebase->scanner->addThreadData([[], [], [], [], [], ['lib\lazy' => $worker_file], [], [], [], [], []]);

        $kept = ClassLikeStorageProvider::getAll()['lib\lazy'];

        self::assertSame($worker_storages['lib\lazy'], $kept);
        self::assertFalse($kept->has_visitor_issues);
        self::assertSame($worker_file, $codebase->scanner->getClassLikeFilePath('lib\lazy'));
        self::assertSame([], IssueBuffer::getIssuesData());
    }

    /**
     * @return iterable<string, array{list<string>}>
     * @psalm-pure
     */
    public static function providerArrival(): iterable
    {
        yield 'process with the smallest path finishes first' => [['w1', 'w2']];
        yield 'process with the smallest path finishes last' => [['w2', 'w1']];
    }

    /**
     * A class the merging process scanned before the pool (an autoloaded file, an earlier scan pass) keeps its
     * declaration, as in a single process, even when a worker found one in a smaller path.
     */
    public function testPoolMergeKeepsTheDeclarationScannedBeforeThePool(): void
    {
        [$worker_file, $worker_storages] = $this->scanAlone('a');
        $codebase = $this->project_analyzer->getCodebase();
        $earlier_file = $this->dir . 'b.php';

        $this->file_provider->registerFile($earlier_file, self::SOURCES['b']);
        $codebase->scanner->addFileToShallowScan($earlier_file);
        $codebase->scanner->scanFiles($codebase->classlikes);
        $earlier = ClassLikeStorageProvider::getAll();

        $codebase->classlike_storage_provider->addMore($worker_storages, $earlier, $codebase->analysis_php_version_id);
        $codebase->scanner->addThreadData([[], [], [], [], [], ['lib\lazy' => $worker_file], [], [], [], [], []]);

        self::assertSame($earlier['lib\lazy'], ClassLikeStorageProvider::getAll()['lib\lazy']);
        self::assertSame($earlier_file, $codebase->scanner->getClassLikeFilePath('lib\lazy'));

        $issues = IssueBuffer::getIssuesData();
        self::assertSame([$worker_file], array_keys($issues));
        self::assertCount(1, $issues[$worker_file]);
        self::assertSame('DuplicateClass', $issues[$worker_file][0]->type);
        self::assertSame(
            'Class Lib\Lazy has already been defined in ' . $earlier_file,
            $issues[$worker_file][0]->message,
        );
    }

    /**
     * A worker that rescans the file of the declaration held before the pool (a shallow scan queued for a deep
     * one) does not hand the class to a later worker's declaration in a smaller path.
     */
    public function testPoolMergeKeepsTheDeclarationScannedBeforeThePoolWhenAWorkerRescansIt(): void
    {
        [$worker_file, $worker_storages] = $this->scanAlone('a');
        [$earlier_file, $rescanned_storages] = $this->scanAlone('b');
        $codebase = $this->project_analyzer->getCodebase();

        $codebase->classlike_storage_provider->addMore(
            ['lib\lazy' => clone $rescanned_storages['lib\lazy']],
            [],
            $codebase->analysis_php_version_id,
        );
        $earlier = ClassLikeStorageProvider::getAll();

        foreach ([$rescanned_storages, $worker_storages] as $storages) {
            $codebase->classlike_storage_provider->addMore($storages, $earlier, $codebase->analysis_php_version_id);
        }

        self::assertSame(
            $earlier_file,
            ClassLikeStorageProvider::getAll()['lib\lazy']->location?->file_path,
        );

        $issues = IssueBuffer::getIssuesData();
        self::assertSame([$worker_file], array_keys($issues));
        self::assertSame('DuplicateClass', $issues[$worker_file][0]->type);
    }

    /**
     * A project declaration of a native class replaces its stub only below the PHP version the stub's `@since`
     * gives, as in a single process.
     */
    #[DataProvider('providerPolyfillVersion')]
    public function testPoolMergeReplacesAStubbedNativeClassOnlyBelowItsSince(
        int $analysis_php_version_id,
        bool $replaced,
    ): void {
        [, $worker_storages] = $this->scanAlone('a');
        [, $native_storages] = $this->scanAlone('b');
        $codebase = $this->project_analyzer->getCodebase();

        $native_storage = $native_storages['lib\lazy'];
        $native_storage->stubbed = true;
        $native_storage->user_defined = false;
        $native_storage->since_php_version_id = 8_02_00;
        $codebase->classlike_storage_provider->addMore($native_storages, [], $analysis_php_version_id);
        $earlier = ClassLikeStorageProvider::getAll();

        $codebase->classlike_storage_provider->addMore($worker_storages, $earlier, $analysis_php_version_id);

        self::assertSame(
            $replaced ? $worker_storages['lib\lazy'] : $native_storage,
            ClassLikeStorageProvider::getAll()['lib\lazy'],
        );
        self::assertSame(
            $replaced ? [] : ['DuplicateClass'],
            array_map(
                static fn(IssueData $issue): string => $issue->type,
                IssueBuffer::getIssuesData()[$this->dir . 'a.php'] ?? [],
            ),
        );
    }

    /**
     * @return iterable<string, array{int, bool}>
     * @psalm-pure
     */
    public static function providerPolyfillVersion(): iterable
    {
        yield 'below the since: polyfill' => [8_01_00, true];
        yield 'at the since: duplicate' => [8_02_00, false];
        yield 'above the since: duplicate' => [8_03_00, false];
    }

    /**
     * Stubs are not sorted: a later stub refines an earlier one.
     *
     * @param list<string> $registration_order
     */
    #[DataProvider('providerStubOrder')]
    public function testStubsKeepTheOrderTheyAreRegisteredIn(array $registration_order, string $first): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->register_stub_files = true;

        foreach ($registration_order as $key) {
            $this->file_provider->registerFile($this->dir . $key . '.php', '<?php namespace Lib; class Foo {}');
            $codebase->scanner->addFileToShallowScan($this->dir . $key . '.php');
        }

        $codebase->scanner->scanFiles($codebase->classlikes);

        self::assertSame($this->dir . $first . '.php', ClassLikeStorageProvider::getAll()['lib\foo']->location?->file_path);
        self::assertSame([], IssueBuffer::getIssuesData());
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     * @psalm-pure
     */
    public static function providerStubOrder(): iterable
    {
        yield 'the stub with the smallest path first' => [['a', 'b'], 'a'];
        yield 'the stub with the smallest path last' => [['b', 'a'], 'b'];
    }

    /**
     * Scans a file as a process that has seen nothing else would: its storages, then forgotten.
     *
     * @return array{string, array<string, ClassLikeStorage>}
     */
    private function scanAlone(string $key, ?string $dir = null): array
    {
        $codebase = $this->project_analyzer->getCodebase();
        $file_path = ($dir ?? $this->dir) . $key . '.php';

        $this->file_provider->registerFile($file_path, self::SOURCES[$key]);
        $codebase->scanner->addFileToShallowScan($file_path);
        $codebase->scanner->scanFiles($codebase->classlikes);

        $classlike_storages = ClassLikeStorageProvider::getAll();

        foreach (array_keys($classlike_storages) as $name_lc) {
            $codebase->scanner->removeClassLike($name_lc);
        }

        ClassLikeStorageProvider::deleteAll();
        FileStorageProvider::deleteAll();
        IssueBuffer::clear();

        return [$file_path, $classlike_storages];
    }
}
