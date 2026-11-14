<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\Scanner;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\IssueBuffer;
use Psalm\Progress\Phase;
use Psalm\Progress\Progress;
use Psalm\Storage\FileStorage;
use Psalm\Tests\Internal\Provider\HashRecordingFileProvider;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;
use RuntimeException;

use function array_combine;
use function array_fill_keys;
use function array_slice;
use function basename;
use function microtime;
use function sprintf;
use function str_contains;

use const DIRECTORY_SEPARATOR;

final class ScanFromCacheTest extends TestCase
{
    public const FORKING = 'Forking process for scanning';

    private const FILES = 600;

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
     * @param positive-int $max_pool_size
     * @param int<0, max> $file_count
     * @param int<0, max> $uncached_count
     * @param positive-int $expected
     */
    #[DataProvider('provideScanPoolSizes')]
    public function testScanPoolSize(int $max_pool_size, int $file_count, int $uncached_count, int $expected): void
    {
        self::assertSame($expected, Scanner::getScanPoolSize($max_pool_size, $file_count, $uncached_count));
    }

    /**
     * Max pool size, files, files without a cached storage, pool size
     *
     * @return iterable<string, array{positive-int, int<0, max>, int<0, max>, positive-int}>
     * @psalm-pure
     */
    public static function provideScanPoolSizes(): iterable
    {
        yield '512 files' => [8, 512, 512, 1];
        yield '513 files' => [8, 513, 513, 3];
        yield '1024 files' => [8, 1024, 1024, 4];
        yield '1024 files, pool of 3' => [3, 1024, 1024, 3];
        yield '300 of 2699 files without a storage' => [8, 2699, 300, 2];
        yield '300 of 2700 files without a storage' => [8, 2700, 300, 1];
    }

    /**
     * Counting the files without a cached storage stops once the pool is the largest, which more of them can't change
     */
    public function testScanPoolSizeNeverShrinks(): void
    {
        foreach ([1, 2, 3, 8] as $max_pool_size) {
            foreach ([512, 513, 600, 1800, 3000] as $file_count) {
                $previous = 1;

                for ($uncached_count = 0; $uncached_count <= $file_count; ++$uncached_count) {
                    $pool_size = Scanner::getScanPoolSize($max_pool_size, $file_count, $uncached_count);

                    self::assertGreaterThanOrEqual($previous, $pool_size);
                    self::assertLessThanOrEqual($max_pool_size, $pool_size);

                    $previous = $pool_size;
                }
            }
        }
    }

    /**
     * Every file has a storage cached for other contents: the scan reads the files in path order until there are
     * enough of them for the largest pool.
     *
     * @param positive-int $scan_threads
     * @param positive-int $files
     * @param positive-int $read
     */
    #[DataProvider('provideFilesReadForThePoolSize')]
    public function testFilesAreReadUntilThePoolIsTheLargest(int $scan_threads, int $files, int $read): void
    {
        $config = $this->createConfig();
        $paths = $this->paths($files);
        $file_provider = new HashRecordingFileProvider(array_fill_keys($paths, '<?php // changed'));
        $providers = $this->providersWithStaleStorages($config, $file_provider, $paths);

        self::assertTrue($this->scanPaths($config, $providers, $scan_threads, $paths));
        self::assertSame(array_slice($paths, 0, $read), $file_provider->hashed);
    }

    /**
     * @return iterable<string, array{positive-int, positive-int, positive-int}>
     * @psalm-pure
     */
    public static function provideFilesReadForThePoolSize(): iterable
    {
        yield 'pool of 2, more than 256 files' => [2, 600, 257];
        yield 'pool of 2, more than one in nine files' => [2, 3000, 334];
        yield 'pool of 8' => [8, 1800, 1793];
    }

    /**
     * There is no cached storage to compare the contents of the files with, as in a run without a cache.
     */
    public function testNoFileIsReadWithoutACachedStorage(): void
    {
        $config = $this->createConfig();
        $paths = $this->paths(600);
        $file_provider = new HashRecordingFileProvider(array_fill_keys($paths, '<?php'));
        $providers = $this->providersFor($config, $file_provider, new FileStorageCacheProvider($config, '', false));

        self::assertTrue($this->scanPaths($config, $providers, 2, $paths));
        self::assertSame([], $file_provider->hashed);
    }

    public function testNoFileWithAStorageInMemoryIsRead(): void
    {
        $config = $this->createConfig();
        $paths = $this->paths(600);
        $file_provider = new HashRecordingFileProvider(array_fill_keys($paths, '<?php // changed'));
        $providers = $this->providersWithStaleStorages($config, $file_provider, $paths);

        foreach (array_slice($paths, 0, 300) as $path) {
            $providers->file_storage_provider->create($path);
        }

        self::assertTrue($this->scanPaths($config, $providers, 2, $paths));
        self::assertSame(array_slice($paths, 300, 257), $file_provider->hashed);
    }

    /**
     * 600 files were scanned and cached, then $changed of them changed: the scan with two threads forks only when
     * more than 256 files have no cached storage for their contents.
     *
     * @param int<0, 600> $changed
     */
    #[DataProvider('provideChangedFiles')]
    public function testScanForksForFilesWithoutACachedStorage(int $changed, bool $forks): void
    {
        [$config, $file_provider, $providers] = $this->createProviders();

        $this->registerClasses($file_provider, self::FILES, '');
        $this->scan($config, $providers, 1);
        $this->registerClasses($file_provider, $changed, ' // changed');

        if ($forks) {
            $this->expectExceptionMessage(self::FORKING);
        }

        $this->scan($config, $providers, 2);

        self::assertArrayHasKey('class599', ClassLikeStorageProvider::getAll());
    }

    /**
     * @return iterable<string, array{int<0, 600>, bool}>
     * @psalm-pure
     */
    public static function provideChangedFiles(): iterable
    {
        yield 'no file changed' => [0, false];
        yield '256 files changed' => [256, false];
        yield '257 files changed' => [257, true];
    }

    public function testFileStorageInMemoryIsCached(): void
    {
        $file_provider = new HashRecordingFileProvider([]);
        $provider = new FileStorageProvider();
        $provider->create('/src/A.php');

        self::assertTrue($provider->isCached('/src/A.php', $file_provider));
        self::assertTrue($provider->isCached('/SRC/a.php', $file_provider));
        self::assertFalse($provider->isCached('/src/B.php', $file_provider));
        self::assertSame([], $file_provider->hashed);
    }

    public function testFileWithoutACachedStorageIsNotRead(): void
    {
        $cache = new FileStorageCacheProvider(new TestConfig(), '', false);
        $cache->writeToCache(new FileStorage('/src/A.php'), 'old');
        $file_provider = new HashRecordingFileProvider(['/src/B.php' => 'new']);

        self::assertFalse((new FileStorageProvider($cache))->isCached('/src/B.php', $file_provider));
        self::assertSame([], $file_provider->hashed);
    }

    public function testFileStorageCachedForTheContents(): void
    {
        $cache = new FileStorageCacheProvider(new TestConfig(), '', false);
        $cache->writeToCache(new FileStorage('/src/A.php'), 'old');
        $provider = new FileStorageProvider($cache);
        $file_provider = new HashRecordingFileProvider(['/SRC/a.php' => 'old', '/src/A.php' => 'new']);

        self::assertTrue($provider->isCached('/SRC/a.php', $file_provider));
        self::assertFalse($provider->isCached('/src/A.php', $file_provider));
        self::assertSame(['/SRC/a.php', '/src/A.php'], $file_provider->hashed);
    }

    /**
     * An autoload file makes Psalm reflect the native class before the cached polyfill is scanned.
     */
    public function testCachedPolyfillOfANativeClass(): void
    {
        [$config, $file_provider, $providers] = $this->createProviders();
        $file_provider->registerFile(
            $this->srcPath('JsonException.php'),
            '<?php class JsonException extends Exception { public const POLYFILL = 1; }',
        );

        self::assertSame([], $this->analyze($config, $providers));
        self::assertSame(
            ['JsonException.php:DuplicateClass'],
            $this->analyze($this->createConfig('autoloader_names_native_class.php'), $providers),
        );
    }

    public function testClassDeclaredByAnotherFileSinceTheStorageWasCached(): void
    {
        [$config, $file_provider, $providers] = $this->createProviders();

        $file_provider->registerFile($this->srcPath('B.php'), '<?php class Foo {}');
        self::assertSame([], $this->analyze($config, $providers));

        $file_provider->registerFile($this->srcPath('A.php'), '<?php class Foo {}');
        self::assertSame(['B.php:DuplicateClass'], $this->analyze($config, $providers));
    }

    /**
     * @param positive-int $scan_threads
     * @param list<string> $paths
     * @return bool whether the scan forked
     */
    private function scanPaths(Config $config, Providers $providers, int $scan_threads, array $paths): bool
    {
        $codebase = (new ProjectAnalyzer($config, $providers, scanThreads: $scan_threads, progress: self::endAtFork()))
            ->getCodebase();

        $codebase->scanner->addFilesToDeepScan(array_combine($paths, $paths));

        try {
            $codebase->scanFiles($scan_threads);
        } catch (RuntimeException $e) {
            if (!str_contains($e->getMessage(), self::FORKING)) {
                throw $e;
            }

            return true;
        }

        return false;
    }

    /**
     * @param int<0, max> $count
     * @return list<string>
     * @psalm-capabilities read-props
     */
    private function paths(int $count): array
    {
        $paths = [];

        for ($i = 0; $i < $count; ++$i) {
            $paths[] = $this->srcPath(sprintf('File%04d.php', $i));
        }

        return $paths;
    }

    /**
     * Providers with a storage of each of the files cached for other contents than theirs
     *
     * @param list<string> $paths
     */
    private function providersWithStaleStorages(Config $config, FileProvider $file_provider, array $paths): Providers
    {
        $cache = new FileStorageCacheProvider($config, '', false);

        foreach ($paths as $path) {
            $cache->writeToCache(new FileStorage($path), '<?php');
        }

        return $this->providersFor($config, $file_provider, $cache);
    }

    /**
     * @param positive-int $scan_threads
     */
    private function scan(Config $config, Providers $providers, int $scan_threads): void
    {
        RuntimeCaches::clearAll();

        $codebase = (new ProjectAnalyzer($config, $providers, scanThreads: $scan_threads, progress: self::endAtFork()))
            ->getCodebase();

        $paths = [];

        for ($i = 0; $i < self::FILES; ++$i) {
            $path = $this->srcPath('Class' . $i . '.php');
            $paths[$path] = $path;
        }

        $codebase->scanner->addFilesToDeepScan($paths);
        $codebase->scanFiles($scan_threads);
    }

    /**
     * Ends the run where the scan would fork, as the test can't
     *
     * @psalm-pure
     */
    private static function endAtFork(): Progress
    {
        return new class extends Progress {
            /**
             * @psalm-pure
             */
            #[Override]
            public function debug(string $message): void
            {
                if (str_contains($message, ScanFromCacheTest::FORKING)) {
                    throw new RuntimeException($message);
                }
            }

            /**
             * @psalm-mutation-free
             */
            #[Override]
            public function startPhase(Phase $phase, int $threads = 1): void
            {
            }

            /**
             * @psalm-mutation-free
             */
            #[Override]
            public function expand(int $number_of_tasks): void
            {
            }

            /**
             * @psalm-mutation-free
             */
            #[Override]
            public function taskDone(int $level): void
            {
            }

            /**
             * @psalm-mutation-free
             */
            #[Override]
            public function finish(): void
            {
            }

            /**
             * @psalm-mutation-free
             */
            #[Override]
            public function alterFileDone(string $file_name): void
            {
            }
        };
    }

    private function registerClasses(FakeFileProvider $file_provider, int $count, string $suffix): void
    {
        for ($i = 0; $i < $count; ++$i) {
            $file_provider->registerFile(
                $this->srcPath('Class' . $i . '.php'),
                '<?php class Class' . $i . ' {}' . $suffix,
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

        return [
            $config,
            $file_provider,
            $this->providersFor($config, $file_provider, new FileStorageCacheProvider($config, '', false)),
        ];
    }

    private function providersFor(
        Config $config,
        FileProvider $file_provider,
        FileStorageCacheProvider $file_storage_cache,
    ): Providers {
        return new Providers(
            $file_provider,
            new ParserCacheProvider($config, '', false),
            $file_storage_cache,
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
