<?php

declare(strict_types=1);

namespace Psalm\Tests\FileUpdates;

use FilesystemIterator;
use Override;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CacheMode;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\IssueBuffer;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_keys;
use function getcwd;
use function is_dir;
use function rmdir;
use function strpos;
use function substr;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function var_export;

use const DIRECTORY_SEPARATOR;

final class CachedStorageTest extends TestCase
{
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->file_provider = new FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new ParserCacheProvider($config, '', CacheMode::Memory),
            new FileStorageCacheProvider($config, '', CacheMode::Memory),
            new ClassLikeStorageCacheProvider($config, '', CacheMode::Memory),
            new FileReferenceCacheProvider($config, '', CacheMode::Memory),
            new ProjectCacheProvider(),
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
        );
        $this->project_analyzer->setPhpVersion('7.3', 'tests');
    }

    public function testValidInclude(): void
    {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $this->project_analyzer->getCodebase()->diff_methods = true;

        $codebase = $this->project_analyzer->getCodebase();

        $vendor_files = [
            (string) getcwd() . DIRECTORY_SEPARATOR . 'V1.php' => '<?php
                namespace AnotherPackage;
                interface StorageInterface {
                   /** @psalm-mutation-free */
                   public function getRecord(): OperationInterface;
                }',
            (string) getcwd() . DIRECTORY_SEPARATOR . 'V2.php' => '<?php
                namespace AnotherPackage;
                interface OperationInterface {
                   public function getResult(): ResultInterface;
                }',
            (string) getcwd() . DIRECTORY_SEPARATOR . 'V3.php' => '<?php
                namespace AnotherPackage;
                interface ResultInterface {}',
        ];

        $analyzable_files = [
            (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                use AnotherPackage\StorageInterface;
                class C {
                    /** @var ?StorageInterface */
                    private $storage;
                    public function zugzug() : void {
                        if (!$this->storage) {
                            return;
                        }
                        $result = $this->storage->getRecord()->getResult();
                    }
                }',
        ];

        foreach ($vendor_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
            $codebase->scanner->addFilesToShallowScan([$file_path => $file_path]);
        }

        foreach ($analyzable_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $codebase->reloadFiles($this->project_analyzer, array_keys($analyzable_files + $vendor_files));

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
    }

    public function testRenamedInterfaceMethodIsReportedOnTheImplementer(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->diff_methods = true;
        $guard = (string) getcwd() . DIRECTORY_SEPARATOR . 'Guard.php';
        $request_guard = (string) getcwd() . DIRECTORY_SEPARATOR . 'RequestGuard.php';

        $this->file_provider->registerFile($guard, '<?php
            namespace App;
            interface Guard { public function guest(): bool; }');
        $this->file_provider->registerFile($request_guard, '<?php
            namespace App;
            final class RequestGuard implements Guard { public function guest(): bool { return true; } }');

        $codebase->addFilesToAnalyze([$guard => $guard, $request_guard => $request_guard]);
        $codebase->scanFiles();
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $this->file_provider->registerFile($guard, '<?php
            namespace App;
            interface Guard { public function isGuest(): bool; }');

        $codebase->reloadFiles($this->project_analyzer, [$guard]);
        $codebase->addFilesToAnalyze([$request_guard => $request_guard]);

        $codebase->config->throw_exception = false;
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false, true);

        $issue_types = [];
        foreach (IssueBuffer::getIssuesDataForFile($request_guard) as $issue) {
            $issue_types[] = $issue->type;
        }

        self::assertSame(['UnimplementedInterfaceMethod'], $issue_types);
    }

    public function testRescannedParentGetsWhatItsChildrenWriteIntoIt(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->diff_methods = true;
        $base = (string) getcwd() . DIRECTORY_SEPARATOR . 'Base.php';
        $child = (string) getcwd() . DIRECTORY_SEPARATOR . 'Child.php';

        $base_code = '<?php
            namespace App;
            abstract class Base { public function foo(): int { return 1; } }';

        $this->file_provider->registerFile($base, $base_code);
        $this->file_provider->registerFile($child, '<?php
            namespace App;
            final class Child extends Base { public function foo(): int { return 2; } }');

        $codebase->addFilesToAnalyze([$base => $base, $child => $child]);
        $codebase->scanFiles();
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        // the edit only adds a comment
        $this->file_provider->registerFile($base, '<?php // edited' . "\n" . substr($base_code, 6));
        $codebase->reloadFiles($this->project_analyzer, [$base]);

        $base_storage = $codebase->classlike_storage_provider->get('app\base');

        self::assertTrue($base_storage->methods['foo']->overridden_downstream);
        self::assertTrue($base_storage->methods['foo']->overridden_somewhere);
        self::assertArrayHasKey('app\child', $base_storage->dependent_classlikes);
        self::assertNotEmpty($codebase->file_storage_provider->get($base)->required_by_file_paths);
    }

    public function testReloadedIncluderDoesNotKeepTheOldFunctionsOfTheFileItIncludes(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->diff_methods = true;
        $functions = (string) getcwd() . DIRECTORY_SEPARATOR . 'functions.php';
        $includer = (string) getcwd() . DIRECTORY_SEPARATOR . 'includer.php';

        $this->file_provider->registerFile($functions, '<?php function foo(): int { return 1; }');
        $this->file_provider->registerFile($includer, '<?php require_once ' . var_export($functions, true) . ';');

        $codebase->addFilesToAnalyze([$functions => $functions, $includer => $includer]);
        $codebase->scanFiles();
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $this->file_provider->registerFile($functions, '<?php function bar(): int { return 1; }');
        $codebase->reloadFiles($this->project_analyzer, [$functions, $includer], true);

        $declared = array_keys($codebase->file_storage_provider->get($includer)->declaring_function_ids);

        self::assertSame(['bar'], $declared);
    }

    public function testStubbedClassIsReportedAfterItsInterfaceMethodIsRenamed(): void
    {
        $cache_directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('psalm-13779-', true);

        try {
            $project_analyzer = $this->newPersistentProjectAnalyzer($cache_directory);
            $codebase = $project_analyzer->getCodebase();
            $guard = (string) getcwd() . DIRECTORY_SEPARATOR . 'Guard.php';
            $request_guard = (string) getcwd() . DIRECTORY_SEPARATOR . 'RequestGuard.php';
            $stub = (string) getcwd() . DIRECTORY_SEPARATOR . 'RequestGuard.phpstub';

            $this->registerGuardFiles($guard, $request_guard);
            $this->file_provider->registerFile($stub, '<?php
                namespace App;
                final class RequestGuard { public function guest(): bool {} }');

            $codebase->addFilesToAnalyze([$guard => $guard, $request_guard => $request_guard]);
            $codebase->scanFiles();
            $codebase->analyzer->analyzeFiles($project_analyzer, 1, false);

            // the stub moves the class's stmt_location to its path, but its cache entry stays under the file
            // that declared it
            $codebase->register_stub_files = true;
            $codebase->scanner->addStubFileToDeepScan($stub);
            $codebase->scanFiles();
            $codebase->register_stub_files = false;

            $codebase->reloadFiles($project_analyzer, [$this->renameGuardMethod($guard)]);

            $issue_types = $this->analyzeRequestGuard($project_analyzer, $request_guard);
        } finally {
            self::removeDirectory($cache_directory);
        }

        self::assertSame(['UnimplementedInterfaceMethod'], $issue_types);
    }

    public function testInvalidatedClassIsReportedAfterItsInterfaceMethodIsRenamed(): void
    {
        $cache_directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('psalm-13779-', true);

        try {
            $project_analyzer = $this->newPersistentProjectAnalyzer($cache_directory);
            $codebase = $project_analyzer->getCodebase();
            $guard = (string) getcwd() . DIRECTORY_SEPARATOR . 'Guard.php';
            $request_guard = (string) getcwd() . DIRECTORY_SEPARATOR . 'RequestGuard.php';

            $this->registerGuardFiles($guard, $request_guard);

            $codebase->addFilesToAnalyze([$guard => $guard, $request_guard => $request_guard]);
            $codebase->scanFiles();
            $codebase->analyzer->analyzeFiles($project_analyzer, 1, false);

            // as the language server does for the files it was asked to analyze
            $codebase->reloadFiles($project_analyzer, [$this->renameGuardMethod($guard), $request_guard], true);

            $issue_types = $this->analyzeRequestGuard($project_analyzer, $request_guard);
        } finally {
            self::removeDirectory($cache_directory);
        }

        self::assertSame(['UnimplementedInterfaceMethod'], $issue_types);
    }

    /**
     * Storages are read back from the disk, while the ones the array cache holds are populated in place
     */
    private function newPersistentProjectAnalyzer(string $cache_directory): ProjectAnalyzer
    {
        $config = new TestConfig();
        $config->cache_directory = $cache_directory;

        $project_analyzer = new ProjectAnalyzer($config, new Providers(
            $this->file_provider,
            new ParserCacheProvider($config, '', CacheMode::Memory),
            new FileStorageCacheProvider($config, ''),
            new ClassLikeStorageCacheProvider($config, ''),
            new FileReferenceCacheProvider($config, '', CacheMode::Memory),
            new ProjectCacheProvider(),
        ));
        $project_analyzer->setPhpVersion('7.3', 'tests');
        $project_analyzer->getCodebase()->diff_methods = true;

        return $project_analyzer;
    }

    private function registerGuardFiles(string $guard, string $request_guard): void
    {
        $this->file_provider->registerFile($guard, '<?php
            namespace App;
            interface Guard { public function guest(): bool; }');
        $this->file_provider->registerFile($request_guard, '<?php
            namespace App;
            final class RequestGuard implements Guard { public function guest(): bool { return true; } }');
    }

    private function renameGuardMethod(string $guard): string
    {
        $this->file_provider->registerFile($guard, '<?php
            namespace App;
            interface Guard { public function isGuest(): bool; }');

        return $guard;
    }

    /** @return list<string> */
    private function analyzeRequestGuard(ProjectAnalyzer $project_analyzer, string $request_guard): array
    {
        $codebase = $project_analyzer->getCodebase();
        $codebase->addFilesToAnalyze([$request_guard => $request_guard]);
        $codebase->config->throw_exception = false;
        $codebase->analyzer->analyzeFiles($project_analyzer, 1, false, true);

        $issue_types = [];
        foreach (IssueBuffer::getIssuesDataForFile($request_guard) as $issue) {
            $issue_types[] = $issue->type;
        }

        return $issue_types;
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        // Config::removeCacheDirectory() deadlocks here: it waits for an exclusive lock, which the live caches of
        // this process hold as shared
        /** @var iterable<SplFileInfo> $entries */
        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($entries as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($directory);
    }
}
