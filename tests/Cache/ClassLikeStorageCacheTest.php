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
use Psalm\IssueBuffer;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;

use function microtime;

use const DIRECTORY_SEPARATOR;

/**
 * A file storage that comes from the cache is only usable together with the cached storages of the class-likes the
 * file declares. When one of them can't be found, the file has to be scanned again.
 */
final class ClassLikeStorageCacheTest extends TestCase
{
    private const CODE = <<<'PHP'
        <?php
        final class Sqlite
        {
            public function f(): int
            {
                return 'a';
            }
        }
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
     * The same file, renamed from `SQLite.php` to `Sqlite.php` (what a case insensitive file system and an
     * autoloader that names the file after the class can make look like the same file): the file storage cache
     * doesn't tell the paths apart.
     */
    public function testFileRenamedToAnotherCase(): void
    {
        [$config, $file_provider, $providers] = $this->createProviders();

        $dir = $config->base_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

        $file_provider->registerFile($dir . 'SQLite.php', self::CODE);
        $cold = $this->analyze($config, $providers);

        $file_provider->deleteFile($dir . 'SQLite.php');
        $file_provider->registerFile($dir . 'Sqlite.php', self::CODE);

        self::assertSame(['InvalidReturnStatement', 'InvalidReturnType'], $cold);

        // the cached storage has the other spelling of the path: the file is scanned again, and its new storage cached
        self::assertSame($cold, $this->analyze($config, $providers));
        self::assertSame($dir . 'Sqlite.php', $providers->file_storage_provider->get($dir . 'Sqlite.php')->file_path);
        self::assertSame(
            $dir . 'Sqlite.php',
            $providers->file_storage_provider->cache?->getLatestFromCache($dir . 'Sqlite.php', self::CODE)?->file_path,
        );
    }

    public function testClassLikeStorageCacheLost(): void
    {
        [$config, $file_provider, $providers] = $this->createProviders();

        $file_provider->registerFile(
            $config->base_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Sqlite.php',
            self::CODE,
        );
        $cold = $this->analyze($config, $providers);

        $providers_without_classlikes = new Providers(
            $file_provider,
            new ParserCacheProvider($config, '', false),
            $providers->file_storage_provider->cache,
            new ClassLikeStorageCacheProvider($config, '', false),
            new FileReferenceCacheProvider($config, '', false),
            new ProjectCacheProvider(),
        );

        self::assertSame(['InvalidReturnStatement', 'InvalidReturnType'], $cold);
        self::assertSame($cold, $this->analyze($config, $providers_without_classlikes));
    }

    /**
     * @return array{Config, FakeFileProvider, Providers}
     */
    private function createProviders(): array
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
        $config->setIncludeCollector(new IncludeCollector());

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
     * @return list<string> the types of the issues
     */
    private function analyze(Config $config, Providers $providers): array
    {
        RuntimeCaches::clearAll();

        $project_analyzer = new ProjectAnalyzer($config, $providers);
        $project_analyzer->check($config->base_dir);
        $project_analyzer->finish(microtime(true), PSALM_VERSION);

        $types = [];

        foreach (IssueBuffer::getIssuesData() as $file_issues) {
            foreach ($file_issues as $issue) {
                $types[] = $issue->type;
            }
        }

        return $types;
    }
}
