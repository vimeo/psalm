<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Tests\TestCase;

use function array_keys;
use function getcwd;

use const DIRECTORY_SEPARATOR;

/**
 * A class declared in several files belongs to the file with the smallest path, however the scanner's processes
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
            $codebase->classlike_storage_provider->addMore($classlike_storages);
            $codebase->scanner->addThreadData([[], [], [], [], [], ['lib\lazy' => $file_path], [], [], []]);
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
     * @return iterable<string, array{list<string>}>
     * @psalm-pure
     */
    public static function providerArrival(): iterable
    {
        yield 'process with the smallest path finishes first' => [['w1', 'w2']];
        yield 'process with the smallest path finishes last' => [['w2', 'w1']];
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
    private function scanAlone(string $key): array
    {
        $codebase = $this->project_analyzer->getCodebase();
        $file_path = $this->dir . $key . '.php';

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
