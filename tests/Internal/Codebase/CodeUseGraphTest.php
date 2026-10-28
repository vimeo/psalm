<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Closure;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PhpParser\Node\Scalar\String_;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileSource;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\Codebase\MutationLevelResolver;
use Psalm\Storage\Capabilities;
use Psalm\Tests\TestCase;
use ReflectionProperty;

use function array_keys;
use function array_slice;
use function count;

/**
 * @psalm-import-type MutationInfo from MutationLevelResolver
 * @psalm-import-type CapabilitySet from Capabilities
 */
final class CodeUseGraphTest extends TestCase
{
    /**
     * Nothing is treated as external code in these unit tests.
     *
     * @return Closure(string): bool
     * @psalm-pure
     */
    private static function notExternal(): Closure
    {
        return static fn(string $_node_id): bool => false;
    }

    public function testResolvesCyclesRecursively(): void
    {
        $graph = new CodeUseGraph();
        // method ids (with ::) so they are not free-function entry points
        $used_a = CodeUseGraph::functionLikeNode('used\\c::a');
        $used_b = CodeUseGraph::functionLikeNode('used\\c::b');
        $unused_a = CodeUseGraph::functionLikeNode('unused\\c::a');
        $unused_b = CodeUseGraph::functionLikeNode('unused\\c::b');

        $graph->addEdge($used_a, $used_b);
        $graph->addEdge($used_b, $used_a);
        $graph->addEdge($unused_a, $unused_b);
        $graph->addEdge($unused_b, $unused_a);
        $graph->markAsPublicApi($used_a);

        $graph->resolve(self::notExternal());

        self::assertTrue($graph->isUsed($used_a));
        self::assertTrue($graph->isUsed($used_b));
        self::assertFalse($graph->isUsed($unused_a));
        self::assertFalse($graph->isUsed($unused_b));
    }

    public function testUsedReturnUsesFunction(): void
    {
        $graph = new CodeUseGraph();
        $function = CodeUseGraph::functionLikeNode('a\\c::m');
        $return = CodeUseGraph::functionLikeReturnNode('a\\c::m');
        $graph->addEdge($return, $function, CodeUseGraph::EDGE_RETURN);
        $graph->markAsPublicApi($return);

        $graph->resolve(self::notExternal());

        self::assertTrue($graph->isUsed($return));
        self::assertTrue($graph->isUsed($function));
        self::assertSame([$return => true], $graph->getReferencingNodes($function));
    }

    public function testCanResolveAgainAfterGraphChanges(): void
    {
        $graph = new CodeUseGraph();
        $a = CodeUseGraph::functionLikeNode('a\\c::m');
        $b = CodeUseGraph::functionLikeNode('b\\c::m');
        $graph->markAsPublicApi($a);
        $graph->resolve(self::notExternal());
        self::assertFalse($graph->isUsed($b));

        $graph->addEdge($a, $b);
        $graph->resolve(self::notExternal());

        self::assertTrue($graph->isUsed($b));
    }

    public function testWriteEdgeDoesNotMarkPropertyUsed(): void
    {
        $graph = new CodeUseGraph();
        $property = CodeUseGraph::propertyNode('a\\c', 'value');

        // a read from top-level code of /read.php, a write from /write.php
        $graph->addReference($property, null, null, CodeUseGraph::EDGE_USE, '/read.php');
        $graph->addReference($property, null, null, CodeUseGraph::EDGE_WRITE, '/write.php');

        $graph->resolve(self::notExternal());
        self::assertTrue($graph->isUsed($property), 'a read keeps the property used');

        // dropping the only read leaves just the write, which must not keep it used
        $graph->removeReferencesFromFile('/read.php');
        $graph->resolve(self::notExternal());

        self::assertFalse($graph->isUsed($property), 'a write alone does not use the property');
        self::assertSame(
            [CodeUseGraph::fileNode('/write.php') => true],
            $graph->getReferencingNodes($property),
        );
    }

    /**
     * @param lowercase-string $method_id
     */
    private static function addMethodReference(
        CodeUseGraph $graph,
        string $method_id,
        string $target_node,
        string $file_path,
    ): void {
        $context = new Context();
        $context->calling_method_id = $method_id;

        $graph->addReference($target_node, $context, null, CodeUseGraph::EDGE_USE, $file_path);
    }

    public function testRemovingReferencesFromFileLeavesOtherFilesAndSeesLaterNodes(): void
    {
        $graph = new CodeUseGraph();
        $target = CodeUseGraph::propertyNode('t\\c', 'value');
        $method_a = CodeUseGraph::functionLikeNode('a\\c::m');
        $method_b = CodeUseGraph::functionLikeNode('b\\c::m');
        $method_a2 = CodeUseGraph::functionLikeNode('a\\c::n');

        self::addMethodReference($graph, 'a\\c::m', $target, '/a.php');
        self::addMethodReference($graph, 'b\\c::m', $target, '/b.php');

        $graph->removeReferencesFromFile('/a.php');

        self::assertSame([$method_b => true], $graph->getReferencingNodes($target));

        // a node added to the file after the removal must be found by the next one
        self::addMethodReference($graph, 'a\\c::n', $target, '/a.php');
        self::assertSame([$method_b => true, $method_a2 => true], $graph->getReferencingNodes($target));

        $graph->removeReferencesFromFile('/a.php');

        self::assertSame([$method_b => true], $graph->getReferencingNodes($target));
        self::assertNull($graph->getNodeFile($method_a));
        self::assertNull($graph->getNodeFile($method_a2));
        self::assertSame('/b.php', $graph->getNodeFile($method_b));
    }

    public function testFileIndexIsNotRebuiltBetweenRemovals(): void
    {
        $graph = new CodeUseGraph();
        $target = CodeUseGraph::propertyNode('t\\c', 'value');

        self::addMethodReference($graph, 'a\\c::m', $target, '/a.php');
        self::addMethodReference($graph, 'b\\c::m', $target, '/b.php');

        $index = new ReflectionProperty(CodeUseGraph::class, 'file_nodes');

        $graph->removeReferencesFromFile('/a.php');
        self::assertIsArray($index->getValue($graph), 'the index is built by the first removal');

        $graph->removeReferencesFromFile('/b.php');
        self::assertSame([], $index->getValue($graph), 'removals update the index instead of dropping it');

        self::addMethodReference($graph, 'c\\c::m', $target, '/c.php');
        self::assertSame(
            [
                '/c.php' => [CodeUseGraph::functionLikeNode('c\\c::m') => true],
            ],
            $index->getValue($graph),
            'additions update the index instead of dropping it',
        );
    }

    public function testCacheDataRoundTripsIntoEmptyAndPopulatedGraphs(): void
    {
        $target = CodeUseGraph::propertyNode('t\\c', 'value');
        $other = CodeUseGraph::propertyNode('t\\c', 'other');
        $method_a = CodeUseGraph::functionLikeNode('a\\c::m');
        $method_b = CodeUseGraph::functionLikeNode('b\\c::m');

        $source = new CodeUseGraph();
        self::addMethodReference($source, 'a\\c::m', $target, '/a.php');
        $source->addReference($target, null, null, CodeUseGraph::EDGE_WRITE, '/w.php');

        $empty = new CodeUseGraph();
        $empty->loadCacheData($source->getCacheData());

        self::assertSame($source->getReferencingNodes($target), $empty->getReferencingNodes($target));
        self::assertSame('/a.php', $empty->getNodeFile($method_a));

        // nodes added after loading are indexed on both sides, and files can still be invalidated
        self::addMethodReference($empty, 'b\\c::m', $other, '/b.php');
        self::assertSame([$method_b => true], $empty->getReferencingNodes($other));
        $empty->removeReferencesFromFile('/a.php');
        self::assertSame([CodeUseGraph::fileNode('/w.php') => true], $empty->getReferencingNodes($target));

        $populated = new CodeUseGraph();
        self::addMethodReference($populated, 'b\\c::m', $target, '/b.php');
        $populated->loadCacheData($source->getCacheData());

        self::assertSame(
            [$method_b => true, $method_a => true, CodeUseGraph::fileNode('/w.php') => true],
            $populated->getReferencingNodes($target),
        );
        self::assertSame('/b.php', $populated->getNodeFile($method_b));
    }

    public function testMergedNodesAreFoundByALaterFileRemoval(): void
    {
        $target = CodeUseGraph::propertyNode('t\\c', 'value');
        $method_a = CodeUseGraph::functionLikeNode('a\\c::m');
        $method_b = CodeUseGraph::functionLikeNode('b\\c::m');
        $method_c = CodeUseGraph::functionLikeNode('c\\c::m');

        $merged = new CodeUseGraph();
        self::addMethodReference($merged, 'b\\c::m', $target, '/b.php');

        $loaded_source = new CodeUseGraph();
        self::addMethodReference($loaded_source, 'c\\c::m', $target, '/c.php');

        $graph = new CodeUseGraph();
        self::addMethodReference($graph, 'a\\c::m', $target, '/a.php');

        // builds the file index
        $graph->removeReferencesFromFile('/unrelated.php');

        $graph->addGraph($merged);
        $graph->loadCacheData($loaded_source->getCacheData());

        self::assertSame(
            [$method_a => true, $method_b => true, $method_c => true],
            $graph->getReferencingNodes($target),
        );

        $graph->removeReferencesFromFile('/b.php');
        self::assertSame([$method_a => true, $method_c => true], $graph->getReferencingNodes($target));
        self::assertNull($graph->getNodeFile($method_b));

        $graph->removeReferencesFromFile('/c.php');
        self::assertSame([$method_a => true], $graph->getReferencingNodes($target));
        self::assertNull($graph->getNodeFile($method_c));
        self::assertSame('/a.php', $graph->getNodeFile($method_a));
    }

    public function testRemovingAFileRemovesAllItsNodes(): void
    {
        $graph = new CodeUseGraph();
        $target_one = CodeUseGraph::propertyNode('t\\c', 'one');
        $target_two = CodeUseGraph::propertyNode('t\\c', 'two');
        $method_a = CodeUseGraph::functionLikeNode('a\\c::m');
        $method_a2 = CodeUseGraph::functionLikeNode('a\\c::n');
        $method_a3 = CodeUseGraph::functionLikeNode('a\\c::o');
        $method_b = CodeUseGraph::functionLikeNode('b\\c::m');

        self::addMethodReference($graph, 'a\\c::m', $target_one, '/a.php');
        self::addMethodReference($graph, 'a\\c::n', $target_one, '/a.php');
        self::addMethodReference($graph, 'a\\c::n', $target_two, '/a.php');
        self::addMethodReference($graph, 'a\\c::o', $target_two, '/a.php');
        self::addMethodReference($graph, 'b\\c::m', $target_one, '/b.php');
        self::addMethodReference($graph, 'b\\c::m', $target_two, '/b.php');

        $graph->removeReferencesFromFile('/a.php');

        self::assertSame([$method_b => true], $graph->getReferencingNodes($target_one));
        self::assertSame([$method_b => true], $graph->getReferencingNodes($target_two));
        self::assertNull($graph->getNodeFile($method_a));
        self::assertNull($graph->getNodeFile($method_a2));
        self::assertNull($graph->getNodeFile($method_a3));
        self::assertSame('/b.php', $graph->getNodeFile($method_b));
    }

    public function testRemovingAFileKeepsTheNodesAskedFor(): void
    {
        $graph = new CodeUseGraph();
        $target_one = CodeUseGraph::propertyNode('t\\c', 'one');
        $target_two = CodeUseGraph::propertyNode('t\\c', 'two');
        $method_a = CodeUseGraph::functionLikeNode('a\\c::m');
        $method_a2 = CodeUseGraph::functionLikeNode('a\\c::n');
        $method_a3 = CodeUseGraph::functionLikeNode('a\\c::o');

        self::addMethodReference($graph, 'a\\c::m', $target_one, '/a.php');
        self::addMethodReference($graph, 'a\\c::n', $target_one, '/a.php');
        self::addMethodReference($graph, 'a\\c::n', $target_two, '/a.php');
        self::addMethodReference($graph, 'a\\c::o', $target_two, '/a.php');

        $graph->removeReferencesFromFile('/a.php', [$method_a2 => true, $method_a3 => true]);

        self::assertSame([$method_a2 => true], $graph->getReferencingNodes($target_one));
        self::assertSame([$method_a2 => true, $method_a3 => true], $graph->getReferencingNodes($target_two));
        self::assertNull($graph->getNodeFile($method_a));
        self::assertSame('/a.php', $graph->getNodeFile($method_a2));
        self::assertSame('/a.php', $graph->getNodeFile($method_a3));
    }

    public function testExternalCallerMarksTargetUsed(): void
    {
        $graph = new CodeUseGraph();
        $method = CodeUseGraph::functionLikeNode('a\\c::m');
        $external = CodeUseGraph::functionLikeNode('vendor\\c::caller');
        $graph->addEdge($external, $method);

        // the caller belongs to code outside the project, so what it calls is used
        $graph->resolve(static fn(string $node_id): bool => $node_id === $external);

        self::assertTrue($graph->isUsed($method));
    }

    /**
     * @param array<string, bool> $callees
     * @param CapabilitySet $intrinsic
     * @return MutationInfo
     */
    private static function mutationInfo(
        string $name,
        string $root,
        bool $inherited = false,
        int $intrinsic = Capabilities::NONE,
        array $callees = [],
        bool $fresh = true,
    ): array {
        $declaring_file = new class ('/src/Declaring.php', new Aliases()) implements FileSource {
            public function __construct(private string $path, private Aliases $aliases)
            {
            }

            #[Override]
            public function getFileName(): string
            {
                return $this->path;
            }

            #[Override]
            public function getFilePath(): string
            {
                return $this->path;
            }

            #[Override]
            public function getRootFileName(): string
            {
                return $this->path;
            }

            #[Override]
            public function getRootFilePath(): string
            {
                return $this->path;
            }

            #[Override]
            public function getAliases(): Aliases
            {
                return $this->aliases;
            }
        };

        return [
            'intrinsic' => $intrinsic,
            'allowed' => Capabilities::NONE,
            'callees' => $callees,
            'default_intrinsic' => Capabilities::NONE,
            'default_callees' => [],
            'location' => new CodeLocation($declaring_file, new String_('x')),
            'cased_name' => $name,
            'suppressed_issues' => [],
            'class' => null,
            'start' => 0,
            'fresh' => $fresh,
            'root' => $root,
            'inherited' => $inherited,
            'report' => true,
        ];
    }

    /**
     * Candidates analysed for the same function-like declared in /src/Declaring.php, with the name of the one that
     * has to win. The merge has to give the same result for every order the workers' graphs are merged in.
     *
     * @return iterable<string, array{list<MutationInfo>, string}>
     */
    public static function providerMutationInfoPrecedence(): iterable
    {
        $declaring = '/src/Declaring.php';

        yield 'analysis in the declaring file over the analysis when included' => [
            [
                self::mutationInfo('own', $declaring),
                self::mutationInfo('included', '/zzz/Includer.php', false, Capabilities::ALL),
            ],
            'own',
        ];

        yield 'analysis when included over the analysis of an inherited body' => [
            [
                self::mutationInfo('included', '/aaa/Includer.php'),
                self::mutationInfo('inherited', '/zzz/Includer.php', true, Capabilities::ALL),
            ],
            'included',
        ];

        yield 'analysis in the declaring file over the analysis of an inherited body' => [
            [
                self::mutationInfo('own', $declaring),
                self::mutationInfo('inherited', $declaring, true, Capabilities::ALL),
            ],
            'own',
        ];

        yield 'greater root file among the analyses when included' => [
            [
                self::mutationInfo('b', '/src/B.php', false, Capabilities::NONE),
                self::mutationInfo('a', '/src/A.php', false, Capabilities::ALL),
            ],
            'b',
        ];

        yield 'greater intrinsic level among analyses of one root' => [
            [
                self::mutationInfo('low', '/src/Root.php', true, Capabilities::READ_PROPS, ['z' => false]),
                self::mutationInfo('high', '/src/Root.php', true, Capabilities::IO, ['a' => false]),
            ],
            'high',
        ];

        yield 'greater callees among analyses of one root and level' => [
            [
                self::mutationInfo('x', '/src/Root.php', true, Capabilities::IO, ['x' => false]),
                self::mutationInfo('y', '/src/Root.php', true, Capabilities::IO, ['y' => false]),
            ],
            'y',
        ];

        yield 'callees are compared as a list' => [
            [
                self::mutationInfo('short', '/src/Root.php', true, Capabilities::IO, ['a' => false]),
                self::mutationInfo('long', '/src/Root.php', true, Capabilities::IO, ['a' => false, 'b' => true]),
            ],
            'long',
        ];

        yield 'trait method analysed in three classes using it' => [
            [
                self::mutationInfo('first', '/src/Declaring.php', true, Capabilities::READ_PROPS, ['m' => false]),
                self::mutationInfo('second', '/src/Declaring.php', true, Capabilities::WRITE_PROPS, ['k' => false]),
                self::mutationInfo('third', '/src/Declaring.php', true, Capabilities::WRITE_PROPS, ['n' => false]),
            ],
            'third',
        ];

        yield 'fresh analysis over one of a previous run' => [
            [
                self::mutationInfo('stale', $declaring, false, Capabilities::NONE, [], false),
                self::mutationInfo('fresh', '/src/Includer.php', true, Capabilities::ALL),
            ],
            'fresh',
        ];

        yield 'fresh analysis over one of a previous run of a greater root' => [
            [
                self::mutationInfo('stale', '/zzz/Root.php', false, Capabilities::ALL, [], false),
                self::mutationInfo('fresh', '/aaa/Root.php', false, Capabilities::NONE),
            ],
            'fresh',
        ];
    }

    /**
     * @param list<MutationInfo> $infos
     * @return list<list<MutationInfo>>
     * @psalm-pure
     */
    private static function permutations(array $infos): array
    {
        if (count($infos) <= 1) {
            return [$infos];
        }

        $permutations = [];

        foreach ($infos as $i => $info) {
            $rest = [...array_slice($infos, 0, $i), ...array_slice($infos, $i + 1)];

            foreach (self::permutations($rest) as $permutation) {
                $permutations[] = [$info, ...$permutation];
            }
        }

        return $permutations;
    }

    /**
     * @param list<MutationInfo> $infos
     */
    #[DataProvider('providerMutationInfoPrecedence')]
    public function testMergingWorkerGraphsPicksTheSameMutationInfoInAnyOrder(array $infos, string $winner): void
    {
        $node_id = CodeUseGraph::functionLikeNode('a\\trait_::m');

        $expected_levels = null;

        foreach (self::permutations($infos) as $permutation) {
            $merged = new CodeUseGraph();

            foreach ($permutation as $info) {
                $worker_graph = new CodeUseGraph();
                $worker_graph->addMutationInfo($node_id, $info);
                $merged->addGraph($worker_graph);
            }

            $order = '';

            foreach ($permutation as $info) {
                $order .= $info['cased_name'] . ' ';
            }

            self::assertSame([$node_id], array_keys($merged->getMutationInfo()), $order);
            self::assertSame($winner, $merged->getMutationInfo()[$node_id]['cased_name'], 'merged in order: ' . $order);

            $expected_levels ??= $merged->getMutationLevels();
            self::assertSame($expected_levels, $merged->getMutationLevels(), 'merged in order: ' . $order);
        }
    }

    public function testAnalysisWithEqualPrecedenceReplacesTheRecordedOne(): void
    {
        $graph = new CodeUseGraph();
        $node_id = CodeUseGraph::functionLikeNode('a\\c::m');

        $graph->addMutationInfo($node_id, self::mutationInfo('before', '/src/Root.php', true, Capabilities::IO));
        $graph->addMutationInfo($node_id, self::mutationInfo('after', '/src/Root.php', true, Capabilities::IO));

        self::assertSame('after', $graph->getMutationInfo()[$node_id]['cased_name']);
    }

    public function testStaleMutationInfoNeverReplacesFreshOne(): void
    {
        $graph = new CodeUseGraph();
        $node_id = CodeUseGraph::functionLikeNode('a\\c::m');

        $graph->addMutationInfo($node_id, self::mutationInfo('fresh', '/src/Root.php', true));
        $graph->addMutationInfo(
            $node_id,
            self::mutationInfo('stale', '/src/Declaring.php', false, Capabilities::ALL, [], false),
        );

        self::assertSame('fresh', $graph->getMutationInfo()[$node_id]['cased_name']);
    }

    public function testMutationLevelsFollowTheMutationInfoThatWon(): void
    {
        $graph = new CodeUseGraph();
        $node_id = CodeUseGraph::functionLikeNode('a\\c::m');

        $graph->addMutationInfo($node_id, self::mutationInfo('included', '/src/Includer.php', false, Capabilities::IO));
        self::assertSame([$node_id => Capabilities::IO], $graph->getMutationLevels());

        // the analysis in the declaring file replaces it
        $graph->addMutationInfo(
            $node_id,
            self::mutationInfo('own', '/src/Declaring.php', false, Capabilities::READ_PROPS),
        );
        self::assertSame([$node_id => Capabilities::READ_PROPS], $graph->getMutationLevels());

        // one that does not replace it leaves them as they are
        $graph->addMutationInfo($node_id, self::mutationInfo('inherited', '/src/Includer.php', true, Capabilities::ALL));
        self::assertSame([$node_id => Capabilities::READ_PROPS], $graph->getMutationLevels());
    }
}
