<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\IssueBuffer;
use Psalm\Progress\Progress;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;

use function basename;
use function microtime;

use const DIRECTORY_SEPARATOR;

/**
 * A file scanned from the cache must find its class-likes as a fresh scan of it does.
 */
final class ScanFromCacheTest extends TestCase
{
    private const POLYFILL = <<<'PHP'
        <?php
        class JsonException extends Exception
        {
            public const POLYFILL = 1;
        }
        PHP;

    private const FORKING = 'Forking process for scanning';

    private const DECLARES_FOO = <<<'PHP'
        <?php
        class Foo {}
        PHP;

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
     * A project file polyfilling a native class, scanned from the cache after the autoload files made Psalm reflect
     * the native class: the storage in memory is the reflection of the native class, not the file's.
     */
    public function testPolyfillOfNativeClassNamedByAutoloadFile(): void
    {
        $polyfill = $this->srcPath('JsonException.php');

        [$config, $file_provider, $providers] = $this->createProviders();
        $file_provider->registerFile($polyfill, self::POLYFILL);

        // no autoload file names the native class yet, so nothing is reflected and the polyfill has no duplicate
        self::assertSame([], $this->analyze($config, $providers));

        $config_with_autoloader = $this->createConfig('autoloader_names_native_class.php');
        $fresh_providers = $this->createFreshProviders($config_with_autoloader, $file_provider);
        $fresh = $this->analyze($config_with_autoloader, $fresh_providers);

        self::assertSame(['JsonException.php:DuplicateClass'], $fresh);
        self::assertSame($fresh, $this->analyze($config_with_autoloader, $providers));
    }

    /**
     * The class was declared by one file only when that file's storage was cached; another file declares it now.
     * The scan order of a fresh scan decides which declaration is the duplicate.
     */
    public function testClassDeclaredByAnotherFileSinceTheStorageWasCached(): void
    {
        [$config, $file_provider, $providers] = $this->createProviders();

        $file_provider->registerFile($this->srcPath('B.php'), self::DECLARES_FOO);
        self::assertSame([], $this->analyze($config, $providers));

        $file_provider->registerFile($this->srcPath('A.php'), self::DECLARES_FOO);

        $fresh = $this->analyze($config, $this->createFreshProviders($config, $file_provider));

        self::assertSame(['B.php:DuplicateClass'], $fresh);
        self::assertSame($fresh, $this->analyze($config, $providers));
    }

    /**
     * The file's own storage is still in memory when the file is scanned from the cache again: it is kept, not
     * scanned afresh.
     */
    public function testStorageInMemoryOfTheSameFileIsKept(): void
    {
        [$config, $file_provider, $providers] = $this->createProviders();

        $file_provider->registerFile($this->srcPath('B.php'), self::DECLARES_FOO);
        self::assertSame([], $this->analyze($config, $providers));

        $storage = ClassLikeStorageProvider::getAll()['foo'];

        self::assertSame([], $this->analyzeKeepingStorages($config, $providers));
        self::assertSame($storage, ClassLikeStorageProvider::getAll()['foo']);
    }

    /**
     * The storages of $cached files were cached, then the contents of $changed of them changed and $uncached files
     * came: the scan is sequential, as the pool size test says it must be, and it finds all the classes.
     *
     * @param int<1, max> $cached
     * @param int<0, max> $uncached
     * @param int<0, max> $changed
     */
    #[DataProvider('provideScansNotWorthForking')]
    public function testFilesAreScannedWithoutForking(int $cached, int $uncached, int $changed): void
    {
        $progress = new DebugMessagesProgress();

        $this->scanAfterCaching($cached, $uncached, $changed, $progress);

        self::assertSame(0, $progress->countMessagesContaining(self::FORKING));
        self::assertArrayHasKey('class0', ClassLikeStorageProvider::getAll());
        self::assertArrayHasKey('class' . ($cached + $uncached - 1), ClassLikeStorageProvider::getAll());
    }

    /**
     * @return iterable<string, array{int<1, max>, int<0, max>, int<0, max>}> the files with a cached storage, the
     *     files without one, the files with a cached storage whose contents changed
     * @psalm-pure
     */
    public static function provideScansNotWorthForking(): iterable
    {
        yield 'all files with a cached storage' => [600, 0, 0];
        yield 'a few files without a cached storage' => [500, 100, 0];
        yield '556 files, 256 without a cached storage' => [300, 256, 0];
        yield '512 files, 511 without a cached storage' => [1, 511, 0];
        yield 'a few files changed' => [600, 0, 100];
        yield '256 of 600 files changed' => [600, 0, 256];
    }

    /**
     * The run ends where the processes would start: the test starts none.
     *
     * @param int<1, max> $cached
     * @param int<0, max> $uncached
     * @param int<0, max> $changed
     * @see provideScansNotWorthForking
     */
    #[DataProvider('provideScansWorthForking')]
    public function testFilesAreScannedByForking(int $cached, int $uncached, int $changed): void
    {
        $this->expectExceptionMessage(self::FORKING);

        $this->scanAfterCaching($cached, $uncached, $changed, new DebugMessagesProgress(self::FORKING));
    }

    /**
     * @return iterable<string, array{int<1, max>, int<0, max>, int<0, max>}> the files with a cached storage, the
     *     files without one, the files with a cached storage whose contents changed
     * @psalm-pure
     */
    public static function provideScansWorthForking(): iterable
    {
        yield '513 files, 512 without a cached storage' => [1, 512, 0];
        yield '557 files, 257 without a cached storage' => [300, 257, 0];
        yield '600 files, 599 without a cached storage' => [1, 599, 0];
        yield '257 of 600 files changed' => [600, 0, 257];
        yield 'all 600 files changed' => [600, 0, 600];
        yield '150 changed and 107 new of 650 files' => [543, 107, 150];
    }

    /**
     * Scans the files in a sequential run first, then all of them with scan threads, without analysing any.
     *
     * @param int<1, max> $cached
     * @param int<0, max> $uncached
     * @param int<0, max> $changed
     */
    private function scanAfterCaching(int $cached, int $uncached, int $changed, Progress $progress): void
    {
        [$config, $file_provider, $providers] = $this->createProviders();
        $this->registerClasses($file_provider, 0, $cached);
        $this->scan($config, $providers, $cached, new DebugMessagesProgress(), 1);

        $this->registerClasses($file_provider, 0, $changed, ' // changed');
        $this->registerClasses($file_provider, $cached, $cached + $uncached);
        $this->scan($config, $providers, $cached + $uncached, $progress, 4);
    }

    /**
     * @param int<1, max> $files the files `Class0.php` and the following
     * @param int<1, max> $scan_threads
     */
    private function scan(Config $config, Providers $providers, int $files, Progress $progress, int $scan_threads): void
    {
        RuntimeCaches::clearAll();

        $codebase = (new ProjectAnalyzer($config, $providers, scanThreads: $scan_threads, progress: $progress))
            ->getCodebase();

        $paths = [];

        for ($i = 0; $i < $files; ++$i) {
            $path = $this->srcPath('Class' . $i . '.php');
            $paths[$path] = $path;
        }

        $codebase->scanner->addFilesToDeepScan($paths);
        $codebase->scanFiles($scan_threads);
    }

    private function registerClasses(FakeFileProvider $file_provider, int $first, int $end, string $suffix = ''): void
    {
        for ($i = $first; $i < $end; ++$i) {
            $file_provider->registerFile(
                $this->srcPath('Class' . $i . '.php'),
                ($i === 0
                    ? '<?php final class Class0 { public function f(): int { return "a"; } }'
                    : '<?php final class Class' . $i . ' {}') . $suffix,
            );
        }
    }

    /** @psalm-pure */
    private function srcPath(string $name): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
            . $name;
    }

    private function createConfig(?string $autoloader = null): Config
    {
        $config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            '<?xml version="1.0"?>'
            . '<psalm' . ($autoloader !== null ? ' autoloader="' . $autoloader . '"' : '') . '>'
            . '<projectFiles><directory name="src" /></projectFiles>'
            . '<issueHandlers>'
            . '<MissingClassConstType errorLevel="suppress" />'
            . '<ClassMustBeFinal errorLevel="suppress" />'
            . '</issueHandlers>'
            . '</psalm>',
        );
        $config->setIncludeCollector(new IncludeCollector());

        return $config;
    }

    /**
     * @return array{Config, FakeFileProvider, Providers}
     */
    private function createProviders(): array
    {
        $config = $this->createConfig();
        $file_provider = new FakeFileProvider();

        return [$config, $file_provider, $this->createFreshProviders($config, $file_provider)];
    }

    private function createFreshProviders(Config $config, FakeFileProvider $file_provider): Providers
    {
        return new Providers(
            $file_provider,
            new ParserCacheProvider($config, '', false),
            new FileStorageCacheProvider($config, '', false),
            new ClassLikeStorageCacheProvider($config, '', false),
            new FileReferenceCacheProvider($config, '', false),
            new ProjectCacheProvider(),
        );
    }

    /**
     * @return list<string> `file:type` of the issues
     */
    private function analyze(Config $config, Providers $providers): array
    {
        RuntimeCaches::clearAll();

        return $this->analyzeKeepingStorages($config, $providers);
    }

    /**
     * @return list<string> `file:type` of the issues
     */
    private function analyzeKeepingStorages(Config $config, Providers $providers): array
    {
        IssueBuffer::clear();

        $project_analyzer = new ProjectAnalyzer($config, $providers);
        $project_analyzer->check($config->base_dir);
        $project_analyzer->finish(microtime(true), PSALM_VERSION);

        $issues = [];

        foreach (IssueBuffer::getIssuesData() as $file_issues) {
            foreach ($file_issues as $issue) {
                $issues[] = basename($issue->file_path) . ':' . $issue->type;
            }
        }

        return $issues;
    }
}
