<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Tests\TestCase;

use function array_keys;
use function array_map;
use function getcwd;
use function strtolower;

use const DIRECTORY_SEPARATOR;

/**
 * The scanner pool merges what its processes scanned, whichever finished first: the result must not depend on it.
 */
final class ScannerStorageMergeTest extends TestCase
{
    private string $dir;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->dir = (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'scanner_merge' . DIRECTORY_SEPARATOR;
        $this->project_analyzer->getCodebase()->config->throw_exception = false;

        // the issues of the project files are the ones reported
        foreach (['a', 'b', 'c', 'q', 'r', 'x', 'known'] as $name) {
            $this->project_analyzer->getCodebase()->analyzer->addFilesToAnalyze(
                [$this->dir . $name . '.php' => $this->dir . $name . '.php'],
            );
        }
        ClassLikeStorageProvider::deleteAll();
        FileStorageProvider::deleteAll();
        IssueBuffer::clear();
    }

    /**
     * @param list<string> $scan_order the files, in the order of the files the scanner was given
     * @param list<string> $arrival the processes, in the order they finished in (w1 took c and b, w2 took a)
     * @param list<string> $expected_classlikes
     * @param list<string> $expected_files
     */
    #[DataProvider('providerMerge')]
    public function testMergeDoesNotDependOnTheOrderTheProcessesFinishIn(
        array $scan_order,
        array $arrival,
        string $winner,
        string $loser,
        array $expected_classlikes,
        array $expected_files,
    ): void {
        $sources = [
            'a' => '<?php
class Foo {}
class AOnly {}',
            'b' => '<?php
class Foo {}
class BOnly {}',
            'c' => '<?php
class COnly {}',
        ];

        $scanned = [];

        // each process scans its files without having seen the other process's
        foreach (['c', 'b', 'a'] as $key) {
            $scanned[$key] = $this->scanAlone($this->dir . $key . '.php', $sources[$key]);
        }

        $processes = [
            'w1' => $this->poolData([$scanned['c'], $scanned['b']]),
            'w2' => $this->poolData([$scanned['a']]),
        ];

        $files_to_scan = [];

        foreach ($scan_order as $key) {
            $files_to_scan[$this->dir . $key . '.php'] = $this->dir . $key . '.php';
        }

        $this->merge(array_map(static fn(string $process): array => $processes[$process], $arrival), $files_to_scan);

        $winner_file = $this->dir . $winner . '.php';
        $loser_file = $this->dir . $loser . '.php';

        $classlikes = ClassLikeStorageProvider::getAll();
        self::assertSame($expected_classlikes, array_keys($classlikes));
        self::assertSame($scanned[$winner][1]['foo'], $classlikes['foo']);
        self::assertTrue($classlikes['foo']->has_visitor_issues);
        self::assertFalse($classlikes['aonly']->has_visitor_issues);
        self::assertFalse($classlikes['bonly']->has_visitor_issues);
        self::assertFalse($classlikes['conly']->has_visitor_issues);

        $files = FileStorageProvider::getAll();
        self::assertSame(
            array_map(fn(string $key): string => strtolower($this->dir . $key . '.php'), $expected_files),
            array_keys($files),
        );
        self::assertTrue($files[strtolower($loser_file)]->has_visitor_issues);
        self::assertFalse($files[strtolower($winner_file)]->has_visitor_issues);
        self::assertFalse($files[strtolower($this->dir . 'c.php')]->has_visitor_issues);
        self::assertArrayNotHasKey('foo', $files[strtolower($loser_file)]->classlikes_in_file);
        self::assertArrayHasKey('foo', $files[strtolower($winner_file)]->classlikes_in_file);
        self::assertArrayHasKey($loser . 'only', $files[strtolower($loser_file)]->classlikes_in_file);

        self::assertSame($winner_file, $this->project_analyzer->getCodebase()->scanner->getClassLikeFilePath('foo'));

        $issues = IssueBuffer::getIssuesData();
        self::assertSame([$loser_file], array_keys($issues));
        self::assertCount(1, $issues[$loser_file]);
        $issue = $issues[$loser_file][0];
        self::assertSame('DuplicateClass', $issue->type);
        self::assertSame('Class Foo has already been defined in ' . $winner_file, $issue->message);
        self::assertSame(2, $issue->line_from);
        self::assertSame('Foo', $issue->selected_text);
    }

    /**
     * @return iterable<string, array{list<string>, list<string>, string, string, list<string>, list<string>}>
     * @psalm-pure
     */
    public static function providerMerge(): iterable
    {
        $a_first = ['a', 'b', 'c'];
        $b_first = ['b', 'a', 'c'];

        $a_first_classlikes = ['foo', 'aonly', 'bonly', 'conly'];
        $b_first_classlikes = ['foo', 'bonly', 'aonly', 'conly'];

        yield 'first declaration wins, process with it finishes last' => [
            $a_first, ['w1', 'w2'], 'a', 'b', $a_first_classlikes, $a_first,
        ];

        yield 'first declaration wins, process with it finishes first' => [
            $a_first, ['w2', 'w1'], 'a', 'b', $a_first_classlikes, $a_first,
        ];

        yield 'second file first, process with the duplicate finishes last' => [
            $b_first, ['w2', 'w1'], 'b', 'a', $b_first_classlikes, $b_first,
        ];

        yield 'second file first, process with the duplicate finishes first' => [
            $b_first, ['w1', 'w2'], 'b', 'a', $b_first_classlikes, $b_first,
        ];
    }

    public function testStoragesOfFilesNotScannedAreAddedAfterTheScannedOnesInAStableOrder(): void
    {
        $codebase = $this->project_analyzer->getCodebase();

        $scanned = $this->scanAlone($this->dir . 'a.php', '<?php class AOnly {}');
        $carried_r = $this->scanAlone($this->dir . 'r.php', '<?php class ROnly {}');
        $carried_q = $this->scanAlone($this->dir . 'q.php', '<?php class QOnly {}');

        $this->merge(
            [
                $this->poolData([$carried_r]),
                $this->poolData([$carried_q, $scanned]),
            ],
            [$this->dir . 'a.php' => $this->dir . 'a.php'],
        );

        self::assertSame(['aonly', 'qonly', 'ronly'], array_keys(ClassLikeStorageProvider::getAll()));
        self::assertSame(
            [
                strtolower($this->dir . 'a.php'),
                strtolower($this->dir . 'q.php'),
                strtolower($this->dir . 'r.php'),
            ],
            array_keys(FileStorageProvider::getAll()),
        );
        self::assertSame([], IssueBuffer::getIssuesData());
        self::assertSame($this->dir . 'a.php', $codebase->scanner->getClassLikeFilePath('aonly'));
    }

    public function testStoragesTheCodebaseKnowsAlreadyAreNotReplacedButTheirIssuesAreKept(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $known_file = strtolower($this->dir . 'known.php');

        [$known_file_storage, $known_storages] = $this->scanAlone(
            $this->dir . 'known.php',
            '<?php class Known {} class Quiet {}',
        );

        // the process forked from the codebase carries copies of what it knew, and flagged one of them
        $copy_file_storage = clone $known_file_storage;
        $copy_storages = [
            'known' => clone $known_storages['known'],
            'quiet' => clone $known_storages['quiet'],
        ];
        $copy_storages['known']->has_visitor_issues = true;

        $codebase->classlike_storage_provider->addMore($known_storages);
        $codebase->file_storage_provider->addMore([$known_file => $known_file_storage]);

        $this->merge([[
            'file_storage' => [$known_file => $copy_file_storage],
            'classlike_storage' => $copy_storages,
        ]], []);

        self::assertSame($known_storages['known'], ClassLikeStorageProvider::getAll()['known']);
        self::assertSame($known_storages['quiet'], ClassLikeStorageProvider::getAll()['quiet']);
        self::assertTrue($known_storages['known']->has_visitor_issues);
        self::assertFalse($known_storages['quiet']->has_visitor_issues);
        self::assertSame($known_file_storage, FileStorageProvider::getAll()[$known_file]);
        self::assertSame([], IssueBuffer::getIssuesData());
    }

    public function testTheSameClassOutsideTheScannedFilesInSeveralProcessesIsAddedOnceInStableOrder(): void
    {
        [, $storages] = $this->scanAlone($this->dir . 'x.php', '<?php class Zed {} class Alpha {}');

        // the file is not among the ones scanned: the processes loaded it for another reason
        $copy_storages = ['alpha' => clone $storages['alpha'], 'zed' => clone $storages['zed']];

        $this->merge([
            ['file_storage' => [], 'classlike_storage' => $storages],
            ['file_storage' => [], 'classlike_storage' => $copy_storages],
        ], []);

        $classlikes = ClassLikeStorageProvider::getAll();
        self::assertSame(['alpha', 'zed'], array_keys($classlikes));
        self::assertSame($storages['alpha'], $classlikes['alpha']);
        self::assertSame([], IssueBuffer::getIssuesData());
    }

    /**
     * Scans a file as a process that has seen nothing else would: its storages, then forgotten.
     *
     * @return array{FileStorage, array<string, ClassLikeStorage>}
     */
    private function scanAlone(string $file_path, string $contents): array
    {
        $codebase = $this->project_analyzer->getCodebase();

        $this->file_provider->registerFile($file_path, $contents);
        $codebase->scanner->addFileToShallowScan($file_path);
        $codebase->scanner->scanFiles($codebase->classlikes);

        $file_storage = $codebase->file_storage_provider->get($file_path);
        $classlike_storages = ClassLikeStorageProvider::getAll();

        ClassLikeStorageProvider::deleteAll();
        FileStorageProvider::deleteAll();
        IssueBuffer::clear();

        return [$file_storage, $classlike_storages];
    }

    /**
     * @param list<array{FileStorage, array<string, ClassLikeStorage>}> $scanned the files of a process
     * @return array{file_storage: array<lowercase-string, FileStorage>, classlike_storage: array<string, ClassLikeStorage>}
     * @psalm-capabilities read-props
     */
    private function poolData(array $scanned): array
    {
        $data = ['file_storage' => [], 'classlike_storage' => []];

        foreach ($scanned as [$file_storage, $classlike_storages]) {
            $data['file_storage'][strtolower($file_storage->file_path)] = $file_storage;
            $data['classlike_storage'] += $classlike_storages;
        }

        return $data;
    }

    /**
     * @param list<array{file_storage: array<lowercase-string, FileStorage>, classlike_storage: array<string, ClassLikeStorage>}> $pool_results
     * @param array<string, string> $files_to_scan
     */
    private function merge(array $pool_results, array $files_to_scan): void
    {
        $this->project_analyzer->getCodebase()->scanner->addScannedStorages($pool_results, $files_to_scan);
    }
}
