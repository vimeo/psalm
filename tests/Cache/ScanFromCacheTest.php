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
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;
use RuntimeException;

use function basename;
use function hash;
use function microtime;
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
        $provider = new FileStorageProvider();
        $provider->create('/src/A.php');

        self::assertTrue($provider->isCached('/src/A.php', ''));
        self::assertTrue($provider->isCached('/SRC/a.php', ''));
        self::assertFalse($provider->isCached('/src/B.php', ''));
    }

    public function testFileStorageCachedForTheContents(): void
    {
        $cache = new FileStorageCacheProvider(new TestConfig(), '', false);
        $cache->writeToCache(new FileStorage('/src/A.php'), 'old');
        $provider = new FileStorageProvider($cache);

        self::assertTrue($provider->isCached('/SRC/a.php', hash('xxh128', 'old')));
        self::assertFalse($provider->isCached('/src/A.php', hash('xxh128', 'new')));
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
             * @psalm-mutation-free
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
            new Providers(
                $file_provider,
                new ParserCacheProvider($config, '', false),
                new FileStorageCacheProvider($config, '', false),
                new ClassLikeStorageCacheProvider($config, '', false),
                new FileReferenceCacheProvider($config, '', false),
                new ProjectCacheProvider(),
            ),
        ];
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
