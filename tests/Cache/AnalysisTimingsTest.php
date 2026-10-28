<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
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
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;

use function array_keys;
use function extension_loaded;
use function microtime;
use function str_repeat;
use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;

final class AnalysisTimingsTest extends TestCase
{
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

    private static function configWithCacheDirectory(): Config
    {
        $config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            <<<'XML'
                <?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>
                XML,
        );
        $config->cache_directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'psalm-timings-test-' . uniqid('', true);

        return $config;
    }

    public function testTimingsRoundTripThroughDiskCache(): void
    {
        $config = self::configWithCacheDirectory();
        // read the timings back from disk rather than from the in-memory copy
        $config->array_cache = false;

        $provider = new FileReferenceCacheProvider($config, 'lock');

        self::assertSame([], $provider->getCachedAnalysisTimings());

        $provider->setCachedAnalysisTimings(['/a.php' => 0.25, '/b.php' => 7.5]);

        self::assertSame(['/a.php' => 0.25, '/b.php' => 7.5], $provider->getCachedAnalysisTimings());
    }

    public function testTimingsAreNotPersistedWithoutDiskCache(): void
    {
        $config = self::configWithCacheDirectory();

        $provider = new FileReferenceCacheProvider($config, 'lock', false);
        $provider->setCachedAnalysisTimings(['/a.php' => 1.5]);

        self::assertSame(['/a.php' => 1.5], $provider->getCachedAnalysisTimings());
        self::assertDirectoryDoesNotExist((string) $config->cache_directory);
    }

    public function testParallelAnalysisRecordsTimingsAndPrunesDeletedFiles(): void
    {
        if (!extension_loaded('pcntl') || !extension_loaded('posix')) {
            $this->markTestSkipped('parallel analysis needs pcntl, and amphp tears its forked workers down with posix_kill');
        }

        $config = self::configWithCacheDirectory();
        $config->setIncludeCollector(new IncludeCollector());

        $file_provider = new FakeFileProvider();
        $reference_cache = new FileReferenceCacheProvider($config, 'lock');
        $providers = new Providers(
            $file_provider,
            new ParserCacheProvider($config, '', false),
            new FileStorageCacheProvider($config, '', false),
            new ClassLikeStorageCacheProvider($config, '', false),
            $reference_cache,
            new ProjectCacheProvider(),
        );

        $paths = [];
        foreach (['A', 'B', 'C'] as $name) {
            $path = $config->base_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $name . '.php';
            $file_provider->registerFile($path, '<?php final class ' . $name . ' {}');
            $paths[] = $path;
        }

        // files outside the analysed set keep their history only while they exist
        $outside_project = $config->base_dir . DIRECTORY_SEPARATOR . 'other' . DIRECTORY_SEPARATOR . 'E.php';
        $file_provider->registerFile($outside_project, '<?php final class E {}');
        $reference_cache->setCachedAnalysisTimings([$outside_project => 9.0, '/vanished.php' => 3.0]);

        $this->analyse($config, $providers);

        $timings = $reference_cache->getCachedAnalysisTimings();
        foreach ($paths as $path) {
            self::assertArrayHasKey($path, $timings);
            self::assertGreaterThan(0.0, $timings[$path]);
        }

        $file_provider->deleteFile($paths[0]);
        $file_provider->registerFile($paths[1], '<?php final class B { public function foo(): void {} }');
        $file_provider->registerFile(
            $config->base_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'D.php',
            '<?php final class D {}',
        );

        $this->analyse($config, $providers);

        $timings_after = $reference_cache->getCachedAnalysisTimings();
        self::assertArrayNotHasKey($paths[0], $timings_after);
        self::assertSame(9.0, $timings_after[$outside_project] ?? null, 'history of a file not analysed this run is kept');
        self::assertArrayNotHasKey('/vanished.php', $timings_after);
        self::assertArrayHasKey($config->base_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'D.php', $timings_after);
    }

    public function testFilesAreDispatchedLongestFirst(): void
    {
        $config = self::configWithCacheDirectory();
        $file_provider = new FakeFileProvider();
        $providers = new Providers(
            $file_provider,
            new ParserCacheProvider($config, '', false),
            new FileStorageCacheProvider($config, '', false),
            new ClassLikeStorageCacheProvider($config, '', false),
            new FileReferenceCacheProvider($config, 'lock'),
            new ProjectCacheProvider(),
        );
        $project_analyzer = new ProjectAnalyzer($config, $providers);

        // x1 and x4 have history (51 s for 50 bytes), so x2 is expected to cost 102 s and x3 10.2 s
        $file_provider->registerFile('/x1.php', str_repeat('a', 10));
        $file_provider->registerFile('/x2.php', str_repeat('a', 100));
        $file_provider->registerFile('/x3.php', str_repeat('a', 10));
        $file_provider->registerFile('/x4.php', str_repeat('a', 40));

        $analyzer = $project_analyzer->getCodebase()->analyzer;
        $analyzer->addFilesToAnalyze([
            '/x1.php' => '/x1.php',
            '/x2.php' => '/x2.php',
            '/x3.php' => '/x3.php',
            '/x4.php' => '/x4.php',
        ]);

        $order = $analyzer->getFilesLongestFirst(['/x1.php' => 50.0, '/x4.php' => 1.0]);

        self::assertSame(['/x2.php', '/x1.php', '/x3.php', '/x4.php'], array_keys($order));

        // the size of a file being edited in the language server is that of its unsaved contents
        $file_provider->addTemporaryFileChanges('/x3.php', str_repeat('a', 500));

        $order = $analyzer->getFilesLongestFirst(['/x1.php' => 50.0, '/x4.php' => 1.0]);

        self::assertSame(['/x3.php', '/x2.php', '/x1.php', '/x4.php'], array_keys($order));
    }

    private function analyse(Config $config, Providers $providers): void
    {
        RuntimeCaches::clearAll();

        $project_analyzer = new ProjectAnalyzer($config, $providers, threads: 2);
        $project_analyzer->check($config->base_dir);
        $project_analyzer->finish(microtime(true), PSALM_VERSION);
    }
}
