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
use Psalm\Report\ReportOptions;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use ReflectionMethod;
use ReflectionProperty;

use function file_put_contents;
use function gc_collect_cycles;
use function glob;
use function hash;
use function substr;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

/**
 * How ProjectAnalyzer::getDiffFiles() classifies a file depending on the state of its
 * parser cache entry.
 */
final class DiffFilesTest extends TestCase
{
    private string $cache_directory;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to stop Psalm seeing the phpunit arguments
        global $argv;
        $argv = [];
    }

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();
        $this->cache_directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . uniqid('psalm-diff-files-test-', true);
    }

    #[Override]
    public function tearDown(): void
    {
        RuntimeCaches::clearAll();
        // The analyzer and its codebase reference each other; free them so the parser cache
        // releases its lock before the directory is removed.
        gc_collect_cycles();

        Config::removeCacheDirectory($this->cache_directory);

        parent::tearDown();
    }

    public function testUnchangedFileIsNotReported(): void
    {
        $this->cacheFile('unchanged.php', '<?php // same');

        $this->assertSame([], $this->getDiffFiles(['unchanged.php' => '<?php // same']));
    }

    public function testChangedFileIsReported(): void
    {
        $this->cacheFile('changed.php', '<?php // before');

        $this->assertSame(['changed.php'], $this->getDiffFiles(['changed.php' => '<?php // after']));
    }

    public function testNeverCachedFileIsNotReported(): void
    {
        $this->assertSame([], $this->getDiffFiles(['new.php' => '<?php // new']));
    }

    /** An orphan header without a payload is an interrupted first write, not a cached file. */
    public function testFileWithOnlyAHeaderIsNotReported(): void
    {
        $this->cacheFile('orphan.php', '<?php // before');
        unlink($this->itemPath('orphan.php'));

        $this->assertSame([], $this->getDiffFiles(['orphan.php' => '<?php // after']));
    }

    /**
     * With statements cached but their recorded contents unreadable, there is no way to
     * know whether the file changed, so it must not be trusted as unchanged.
     */
    public function testFileWithADamagedHeaderIsReported(): void
    {
        $this->cacheFile('damaged.php', '<?php // same');
        file_put_contents($this->itemPath('damaged.php') . '.hash', "\x01\x02");

        $this->assertSame(['damaged.php'], $this->getDiffFiles(['damaged.php' => '<?php // same']));
    }

    public function testFileWithAMissingHeaderIsReported(): void
    {
        $this->cacheFile('headless.php', '<?php // same');
        unlink($this->itemPath('headless.php') . '.hash');

        $this->assertSame(['headless.php'], $this->getDiffFiles(['headless.php' => '<?php // same']));
    }

    private function getConfig(): Config
    {
        $config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            <<<XML
                <?xml version="1.0"?>
                <psalm cacheDirectory="{$this->cache_directory}">
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>
                XML,
        );
        $config->setIncludeCollector(new IncludeCollector());

        return $config;
    }

    private function cacheFile(string $file_path, string $contents): void
    {
        (new ParserCacheProvider($this->getConfig(), ''))->saveStatementsToCache($file_path, $contents, []);
    }

    /**
     * @param array<string, string> $files current contents, keyed by path
     * @return list<string>
     */
    private function getDiffFiles(array $files): array
    {
        $config = $this->getConfig();
        $project_analyzer = new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                // A fresh instance, so nothing written by cacheFile() is served from memory.
                new ParserCacheProvider($config, ''),
                new FileStorageCacheProvider($config, '', false),
                new ClassLikeStorageCacheProvider($config, '', false),
                new FileReferenceCacheProvider($config, '', false),
                new ProjectCacheProvider(),
            ),
            new ReportOptions(),
        );

        foreach ($files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
        }

        $project_files = [];
        foreach ($files as $file_path => $_) {
            $project_files[$file_path] = $file_path;
        }
        (new ReflectionProperty(ProjectAnalyzer::class, 'project_files'))
            ->setValue($project_analyzer, $project_files);

        /** @var list<string> */
        $diff_files = (new ReflectionMethod(ProjectAnalyzer::class, 'getDiffFiles'))->invoke($project_analyzer);

        // The new analyzer registered itself as the singleton; put the base one back so it can
        // be freed in tearDown.
        ProjectAnalyzer::$instance = $this->project_analyzer;

        return $diff_files;
    }

    private function itemPath(string $file_path): string
    {
        $files = glob($this->cache_directory . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR
            . 'php-parser' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . hash('xxh128', $file_path) . '.hash');

        $this->assertNotFalse($files);
        $this->assertCount(1, $files, "expected exactly one cached item for $file_path");

        return substr($files[0], 0, -5);
    }
}
