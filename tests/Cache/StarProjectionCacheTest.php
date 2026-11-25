<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CacheMode;
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
use Psalm\Type\Union;

use function microtime;
use function serialize;
use function unserialize;

use const DIRECTORY_SEPARATOR;

/**
 * The `*` of a type argument is kept by the storages that come from the cache
 */
final class StarProjectionCacheTest extends TestCase
{
    private const CODE = <<<'PHP'
        <?php
        /** @template T of object */
        final class Box {}

        /** @param Box<DateTime> $b */
        function takesDate(Box $b): void {}

        /** @param Box<*> $b */
        function f(Box $b): void {
            takesDate($b);
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

    public function testStarProjectionSurvivesTheCache(): void
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
        $providers = new Providers(
            $file_provider,
            new ParserCacheProvider($config, '', CacheMode::Memory),
            new FileStorageCacheProvider($config, '', CacheMode::Memory),
            new ClassLikeStorageCacheProvider($config, '', CacheMode::Memory),
            new FileReferenceCacheProvider($config, '', CacheMode::Memory),
            new ProjectCacheProvider(),
        );

        $file_path = $config->base_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Box.php';
        $file_provider->registerFile($file_path, self::CODE);

        $cold = $this->analyze($config, $providers);
        self::assertSame(['ArgumentTypeCoercion'], $cold);
        self::assertSame($cold, $this->analyze($config, $providers));

        $cached = $providers->file_storage_provider->cache?->getLatestFromCache($file_path, self::CODE);
        self::assertNotNull($cached);

        $type = $cached->functions['f']->params[0]->type;
        self::assertInstanceOf(Union::class, $type);
        self::assertSame('Box<*>', $type->getId());

        /** @var Union $unserialized */
        $unserialized = unserialize(serialize($type));
        self::assertSame('Box<*>', $unserialized->getId());
        self::assertTrue($type->equals($unserialized));
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
