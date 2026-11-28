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

use function array_map;
use function basename;
use function explode;
use function microtime;
use function sort;
use function sprintf;

use const DIRECTORY_SEPARATOR;

/**
 * The lower bound of a template (`@template T super Cat`) survives the storage caches, and a changed declaration
 * (`super Animal`, then `of Cat`) takes effect: each step reports what a cold run of the same files reports. The
 * file using the template changes at every analysis, so the steps do not depend on a changed declaration
 * invalidating the files that use it.
 */
final class TemplateLowerBoundCacheTest extends TestCase
{
    private const ANIMALS = <<<'PHP'
        <?php
        class Animal {}
        final class Cat extends Animal {}
        PHP;

    private const SINK = <<<'PHP'
        <?php
        /** @template %s */
        final class Sink {}
        PHP;

    private const USES = <<<'PHP'
        <?php
        // analysis %d
        /** @param Sink<Animal> $s */
        function takesAnimalSink(Sink $s): void {}

        /** @param Sink<Cat> $s */
        function takesCatSink(Sink $s): void {}
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

    public function testChangedBoundsReportWhatAColdRunReports(): void
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

        $src = $config->base_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

        $warm = self::newProviders($config);

        $steps = [
            // Sink<Animal> and Sink<Cat> are supertypes of Cat
            'T super Cat' => [],
            // Cat is below the lower bound Animal
            'T super Animal' => ['Uses.php: InvalidTemplateParam'],
            // Animal is above the upper bound Cat
            'T of Cat' => ['Uses.php: InvalidTemplateParam'],
        ];

        $analysis = 0;

        foreach ($steps as $template => $expected) {
            $sink = sprintf(self::SINK, $template);
            $files = [
                $src . 'Animals.php' => self::ANIMALS,
                $src . 'Sink.php' => $sink,
                $src . 'Uses.php' => sprintf(self::USES, ++$analysis),
            ];

            $cold = $this->analyze($config, self::newProviders($config), $files);

            self::assertSame(
                $expected,
                array_map(static fn(string $issue): string => explode(' - ', $issue)[0], $cold),
                $template,
            );

            // the analysis after the declaration changed
            self::assertSame($cold, $this->analyze($config, $warm, $files), $template);

            // the declaration unchanged, its storage from the cache
            $files[$src . 'Uses.php'] = sprintf(self::USES, ++$analysis);
            self::assertSame($cold, $this->analyze($config, $warm, $files), $template);
        }
    }

    private static function newProviders(Config $config): Providers
    {
        return new Providers(
            new FakeFileProvider(),
            new ParserCacheProvider($config, '', CacheMode::Memory),
            new FileStorageCacheProvider($config, '', CacheMode::Memory),
            new ClassLikeStorageCacheProvider($config, '', CacheMode::Memory),
            new FileReferenceCacheProvider($config, '', CacheMode::Memory),
            new ProjectCacheProvider(),
        );
    }

    /**
     * @param array<string, string> $files the contents of the files, by path
     * @return list<string> the issues, as `file: type - message`, sorted
     */
    private function analyze(Config $config, Providers $providers, array $files): array
    {
        $file_provider = $providers->file_provider;
        self::assertInstanceOf(FakeFileProvider::class, $file_provider);

        foreach ($files as $file_path => $contents) {
            $file_provider->registerFile($file_path, $contents);
        }

        RuntimeCaches::clearAll();

        $project_analyzer = new ProjectAnalyzer($config, $providers);
        $project_analyzer->check($config->base_dir);
        $project_analyzer->finish(microtime(true), PSALM_VERSION);

        $issues = [];

        foreach (IssueBuffer::getIssuesData() as $file_issues) {
            foreach ($file_issues as $issue) {
                $issues[] = basename($issue->file_name) . ': ' . $issue->type . ' - ' . $issue->message;
            }
        }

        sort($issues);

        return $issues;
    }
}
