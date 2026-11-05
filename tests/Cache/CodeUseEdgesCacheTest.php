<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
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

use function basename;
use function microtime;
use function sort;

use const DIRECTORY_SEPARATOR;

/**
 * The code use graph only records its edges when the run reads them (`--diff`, unused code detection or reference
 * lookups), and a cache saved without them must never be used by a run that needs them.
 */
final class CodeUseEdgesCacheTest extends TestCase
{
    private const NEEDS_NOTHING = 'nothing';
    private const NEEDS_DIFF = 'diff';
    private const NEEDS_UNUSED_CODE = 'unused code';
    private const NEEDS_LOCATIONS = 'locations';

    private const CALLEE = '<?php
        final class A {
            public function f(): int {
                return 1;
            }
        }';

    private const CALLEE_CHANGED = '<?php
        final class A {
            public function f(): string {
                return "a";
            }
        }';

    private const CALLER = '<?php
        function g(A $a): string {
            return $a->f();
        }';

    private const CALLER_IGNORING_THE_RESULT = '<?php
        function h(A $a): void {
            $a->f();
        }';

    private Config $config;

    private FakeFileProvider $files;

    private ParserCacheProvider $parser_cache;

    private FileStorageCacheProvider $file_storage_cache;

    private ClassLikeStorageCacheProvider $classlike_storage_cache;

    private FileReferenceCacheProvider $reference_cache;

    private ProjectCacheProvider $project_cache;

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

        $this->config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>',
        );
        $this->config->setIncludeCollector(new IncludeCollector());

        $this->files = new FakeFileProvider();
        $this->registerFiles(self::CALLEE);
        $this->createCaches();
    }

    public function testRunNeedingNothingRecordsNoEdges(): void
    {
        $run = $this->analyze(self::NEEDS_NOTHING);

        self::assertFalse($run['record_edges']);
        self::assertSame(['InvalidReturnStatement B.php:3:20', 'InvalidReturnType B.php:2:27'], $run['issues']);

        $cached = $this->reference_cache->getCachedCodeUseGraph();
        self::assertSame([], $cached['edges'] ?? null);
        self::assertFalse($cached['edges_recorded'] ?? null);
    }

    /**
     * @return iterable<string, array{string}>
     * @psalm-pure
     */
    public static function provideRunsNeedingEdges(): iterable
    {
        yield 'diff' => [self::NEEDS_DIFF];
        yield 'unused code' => [self::NEEDS_UNUSED_CODE];
        yield 'locations' => [self::NEEDS_LOCATIONS];
    }

    #[DataProvider('provideRunsNeedingEdges')]
    public function testRunNeedingEdgesRecordsThem(string $needs): void
    {
        $run = $this->analyze($needs);

        self::assertTrue($run['record_edges']);

        $cached = $this->reference_cache->getCachedCodeUseGraph();
        self::assertNotSame([], $cached['edges'] ?? []);
        self::assertTrue($cached['edges_recorded'] ?? null);
    }

    /**
     * The references of A.php come from the edges: with none cached `--diff` would not analyse B.php again after
     * the change of A.php, and keep reporting its stale issue.
     */
    public function testDiffRunAfterARunNeedingNothingMatchesAFreshRun(): void
    {
        $this->analyze(self::NEEDS_NOTHING);

        $this->registerFiles(self::CALLEE_CHANGED);
        $run = $this->analyze(self::NEEDS_DIFF);

        self::assertTrue($run['record_edges']);
        self::assertSame([], $run['issues']);
        self::assertSame($this->freshRun(self::NEEDS_DIFF)['issues'], $run['issues']);
        self::assertTrue($this->reference_cache->getCachedCodeUseGraph()['edges_recorded'] ?? null);
    }

    public function testDiffRunAfterANothingRunThatReusedCachedEdgesMatchesAFreshRun(): void
    {
        $this->analyze(self::NEEDS_UNUSED_CODE);
        $this->analyze(self::NEEDS_NOTHING);

        $cached = $this->reference_cache->getCachedCodeUseGraph();
        self::assertSame([], $cached['edges'] ?? null);
        self::assertFalse($cached['edges_recorded'] ?? null);

        $this->registerFiles(self::CALLEE_CHANGED);
        $run = $this->analyze(self::NEEDS_DIFF);

        self::assertSame([], $run['issues']);
        self::assertSame($this->freshRun(self::NEEDS_DIFF)['issues'], $run['issues']);
    }

    public function testUnusedCodeRunAfterARunNeedingNothingMatchesAFreshRun(): void
    {
        $this->analyze(self::NEEDS_NOTHING);

        $this->registerFiles(self::CALLEE_CHANGED);
        $run = $this->analyze(self::NEEDS_UNUSED_CODE);
        $fresh = $this->freshRun(self::NEEDS_UNUSED_CODE);

        self::assertTrue($run['record_edges']);
        self::assertSame($fresh['issues'], $run['issues']);
        self::assertNotSame([], $this->reference_cache->getCachedCodeUseGraph()['edges'] ?? []);
    }

    public function testReferenceLookupAfterARunNeedingNothingMatchesAFreshRun(): void
    {
        $this->analyze(self::NEEDS_NOTHING);

        $run = $this->analyze(self::NEEDS_LOCATIONS);
        $fresh = $this->freshRun(self::NEEDS_LOCATIONS);

        self::assertTrue($run['record_edges']);
        self::assertSame(['C.php:3:17'], $run['references']);
        self::assertSame($fresh['references'], $run['references']);
    }

    public function testRunNeedingNothingReusesTheCacheOfARunNeedingNothing(): void
    {
        $this->analyze(self::NEEDS_NOTHING);

        $this->registerFiles(self::CALLEE_CHANGED);
        $run = $this->analyze(self::NEEDS_NOTHING);

        self::assertFalse($run['record_edges']);
        self::assertSame([], $run['issues']);
        self::assertSame($this->freshRun(self::NEEDS_NOTHING)['issues'], $run['issues']);
        self::assertFalse($this->reference_cache->getCachedCodeUseGraph()['edges_recorded'] ?? null);
    }

    public function testRunNeedingNothingAfterARunNeedingEdgesDropsThem(): void
    {
        $this->analyze(self::NEEDS_UNUSED_CODE);

        $this->registerFiles(self::CALLEE_CHANGED);
        $run = $this->analyze(self::NEEDS_NOTHING);

        self::assertFalse($run['record_edges']);
        self::assertSame($this->freshRun(self::NEEDS_NOTHING)['issues'], $run['issues']);

        $cached = $this->reference_cache->getCachedCodeUseGraph();
        self::assertSame([], $cached['edges'] ?? null);
        self::assertFalse($cached['edges_recorded'] ?? null);
    }

    private function registerFiles(string $callee): void
    {
        $src = $this->config->base_dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

        $this->files->registerFile($src . 'A.php', $callee);
        $this->files->registerFile($src . 'B.php', self::CALLER);
        $this->files->registerFile($src . 'C.php', self::CALLER_IGNORING_THE_RESULT);
    }

    /**
     * In-memory caches, which the runs share like the on-disk ones.
     */
    private function createCaches(): void
    {
        $this->parser_cache = new ParserCacheProvider($this->config, '', false);
        $this->file_storage_cache = new FileStorageCacheProvider($this->config, '', false);
        $this->classlike_storage_cache = new ClassLikeStorageCacheProvider($this->config, '', false);
        $this->reference_cache = new FileReferenceCacheProvider($this->config, '', false);
        $this->project_cache = new ProjectCacheProvider();
    }

    /**
     * The run of the current files with empty caches.
     *
     * @return array{issues: list<string>, record_edges: bool, references: list<string>}
     */
    private function freshRun(string $needs): array
    {
        return $this->analyzeWith(
            new Providers(
                $this->files,
                new ParserCacheProvider($this->config, '', false),
                new FileStorageCacheProvider($this->config, '', false),
                new ClassLikeStorageCacheProvider($this->config, '', false),
                new FileReferenceCacheProvider($this->config, '', false),
                new ProjectCacheProvider(),
            ),
            $needs,
        );
    }

    /**
     * A run in a new process: the providers are new, the caches are the ones of the earlier runs.
     *
     * @return array{issues: list<string>, record_edges: bool, references: list<string>}
     */
    private function analyze(string $needs): array
    {
        return $this->analyzeWith(
            new Providers(
                $this->files,
                $this->parser_cache,
                $this->file_storage_cache,
                $this->classlike_storage_cache,
                $this->reference_cache,
                $this->project_cache,
            ),
            $needs,
        );
    }

    /**
     * @return array{issues: list<string>, record_edges: bool, references: list<string>}
     */
    private function analyzeWith(Providers $providers, string $needs): array
    {
        RuntimeCaches::clearAll();

        $project_analyzer = new ProjectAnalyzer($this->config, $providers);
        $project_analyzer->setPhpVersion('8.3', 'tests');
        $codebase = $project_analyzer->getCodebase();

        if ($needs === self::NEEDS_UNUSED_CODE) {
            $codebase->reportUnusedCode();
        } elseif ($needs === self::NEEDS_LOCATIONS) {
            $codebase->collectLocations();
        }

        $project_analyzer->check($this->config->base_dir, $needs === self::NEEDS_DIFF);
        $project_analyzer->finish(microtime(true), PSALM_VERSION);

        $issues = [];

        foreach (IssueBuffer::getIssuesData() as $file_issues) {
            foreach ($file_issues as $issue) {
                $issues[] = $issue->type . ' ' . basename($issue->file_name) . ':' . $issue->line_from . ':'
                    . $issue->column_from;
            }
        }

        sort($issues);

        $references = [];

        if ($needs === self::NEEDS_LOCATIONS) {
            foreach ($codebase->findReferencesToMethod('A::f') as $location) {
                $references[] = basename($location->file_name) . ':' . $location->getLineNumber() . ':'
                    . $location->getColumn();
            }

            sort($references);
        }

        return [
            'issues' => $issues,
            'record_edges' => $codebase->code_use_graph->record_edges,
            'references' => $references,
        ];
    }
}
