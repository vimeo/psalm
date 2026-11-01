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
use function strtolower;

use const DIRECTORY_SEPARATOR;

/**
 * A class declared in several files belongs to the file with the smallest path, however the files are ordered
 * and however the scanner's processes finish.
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

    private const POLYFILL_SOURCES = [
        'a' => '<?php
namespace Lib;
class Foo {}',
        'b' => '<?php
namespace Lib;
class Foo {}',
    ];

    private string $dir;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->dir = (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'duplicate_class' . DIRECTORY_SEPARATOR;
        $this->project_analyzer->getCodebase()->config->throw_exception = false;

        // the issues of the project files are the ones reported
        foreach (['a', 'b', 'known'] as $name) {
            $this->project_analyzer->getCodebase()->analyzer->addFilesToAnalyze(
                [$this->dir . $name . '.php' => $this->dir . $name . '.php'],
            );
        }

        ClassLikeStorageProvider::deleteAll();
        FileStorageProvider::deleteAll();
        IssueBuffer::clear();
    }

    /**
     * @param list<string> $scan_order
     */
    #[DataProvider('providerOrder')]
    public function testSingleProcessKeepsTheDeclarationInTheSmallestPath(array $scan_order): void
    {
        $codebase = $this->project_analyzer->getCodebase();

        foreach ($scan_order as $key) {
            $this->file_provider->registerFile($this->dir . $key . '.php', self::SOURCES[$key]);
            $codebase->scanner->addFileToShallowScan($this->dir . $key . '.php');
        }

        $codebase->scanner->scanFiles($codebase->classlikes);

        $this->assertWinner('lib\lazy', 'Lib\Lazy', 'a', 'b');

        $files = FileStorageProvider::getAll();
        $a = $files[strtolower($this->dir . 'a.php')];
        $b = $files[strtolower($this->dir . 'b.php')];
        self::assertArrayHasKey('lib\lazy', $a->classlikes_in_file);
        self::assertArrayNotHasKey('lib\lazy', $b->classlikes_in_file);
        self::assertTrue($b->has_visitor_issues);
    }

    /**
     * @return iterable<string, array{list<string>}>
     * @psalm-pure
     */
    public static function providerOrder(): iterable
    {
        yield 'smallest path first' => [['a', 'b']];
        yield 'smallest path last' => [['b', 'a']];
    }

    /**
     * @param list<string> $arrival the processes, in the order they finished in (w1 scanned a, w2 scanned b)
     */
    #[DataProvider('providerArrival')]
    public function testPoolMergeKeepsTheDeclarationInTheSmallestPath(array $arrival): void
    {
        $processes = [
            'w1' => $this->scanAlone('a', self::SOURCES['a']),
            'w2' => $this->scanAlone('b', self::SOURCES['b']),
        ];

        foreach ($arrival as $process) {
            $this->mergeProcess($processes[$process][0], $processes[$process][1], 'lib\lazy');
        }

        $this->assertWinner('lib\lazy', 'Lib\Lazy', 'a', 'b');
        self::assertSame($processes['w1'][1]['lib\lazy'], ClassLikeStorageProvider::getAll()['lib\lazy']);
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
     * A native class the stubs date after the analysed PHP version is replaced by a project definition of it.
     *
     * @param list<string> $scan_order
     */
    #[DataProvider('providerOrder')]
    public function testSingleProcessPolyfillReplacesTheStub(array $scan_order): void
    {
        $codebase = $this->project_analyzer->getCodebase();

        $this->registerStub();

        foreach ($scan_order as $key) {
            $this->file_provider->registerFile($this->dir . $key . '.php', self::POLYFILL_SOURCES[$key]);
            $codebase->scanner->addFileToShallowScan($this->dir . $key . '.php');
        }

        $codebase->scanner->scanFiles($codebase->classlikes);

        $this->assertPolyfillWinner();
    }

    /**
     * @param list<string> $arrival
     */
    #[DataProvider('providerArrival')]
    public function testPoolMergePolyfillReplacesTheStub(array $arrival): void
    {
        $processes = [
            'w1' => $this->scanAlone('a', self::POLYFILL_SOURCES['a']),
            'w2' => $this->scanAlone('b', self::POLYFILL_SOURCES['b']),
        ];

        $this->registerStub();

        foreach ($arrival as $process) {
            $this->mergeProcess($processes[$process][0], $processes[$process][1], 'lib\foo');
        }

        $this->assertPolyfillWinner();
    }

    /**
     * @param list<string> $registration_order
     */
    #[DataProvider('providerStubOrder')]
    public function testStubsKeepTheOrderTheyAreRegisteredIn(array $registration_order, string $first): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->register_stub_files = true;

        foreach ($registration_order as $key) {
            $this->file_provider->registerFile($this->dir . $key . '.php', self::POLYFILL_SOURCES[$key]);
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

    private function assertWinner(string $name_lc, string $name, string $winner, string $loser): void
    {
        $winner_file = $this->dir . $winner . '.php';
        $loser_file = $this->dir . $loser . '.php';

        self::assertSame($winner_file, ClassLikeStorageProvider::getAll()[$name_lc]->location?->file_path);
        self::assertSame($winner_file, $this->project_analyzer->getCodebase()->scanner->getClassLikeFilePath($name_lc));

        $issues = IssueBuffer::getIssuesData();
        self::assertSame([$loser_file], array_keys($issues));
        self::assertCount(1, $issues[$loser_file]);
        self::assertSame('DuplicateClass', $issues[$loser_file][0]->type);
        self::assertSame('Class ' . $name . ' has already been defined in ' . $winner_file, $issues[$loser_file][0]->message);
    }

    private function assertPolyfillWinner(): void
    {
        $foo = ClassLikeStorageProvider::getAll()['lib\foo'];
        self::assertTrue($foo->user_defined);
        self::assertFalse($foo->stubbed);

        $this->assertWinner('lib\foo', 'Lib\Foo', 'a', 'b');
    }

    /**
     * Registers the stub of a native class that is not there yet in the analysed PHP version, as the codebase
     * holds it once the stubs are registered.
     */
    private function registerStub(): void
    {
        $codebase = $this->project_analyzer->getCodebase();

        [, $storages] = $this->scanAlone('known', '<?php namespace Lib; class Foo {}');

        $stub = $storages['lib\foo'];
        $stub->stubbed = true;
        $stub->user_defined = false;
        $stub->since_php_version_id = 999_900;

        $codebase->classlike_storage_provider->addMore($storages);
        $codebase->scanner->setClassLikeFilePath('lib\foo', $this->dir . 'known.php');
    }

    /**
     * Scans a file as a process that has seen nothing else would: its storages, then forgotten.
     *
     * @return array{string, array<string, ClassLikeStorage>}
     */
    private function scanAlone(string $key, string $contents): array
    {
        $codebase = $this->project_analyzer->getCodebase();
        $file_path = $this->dir . $key . '.php';

        $this->file_provider->registerFile($file_path, $contents);
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

    /**
     * Merges what a process scanned the way the scanner does when the process finishes.
     *
     * @param array<string, ClassLikeStorage> $classlike_storages
     */
    private function mergeProcess(string $file_path, array $classlike_storages, string $name_lc): void
    {
        $codebase = $this->project_analyzer->getCodebase();

        $codebase->classlike_storage_provider->addMore($classlike_storages);
        $codebase->scanner->addThreadData([[], [], [], [], [], [$name_lc => $file_path], [], [], []]);
    }
}
