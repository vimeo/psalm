<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Closure;
use Psalm\CodeLocation\Raw;
use Psalm\Context;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\Codebase\MutationLevelResolver;
use Psalm\Tests\TestCase;
use ReflectionProperty;

use function array_keys;
use function basename;

/**
 * @psalm-import-type MutationInfo from MutationLevelResolver
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

    public function testGraphWithoutEdgesRecordsNeitherEdgesNorTheirFilesAndLocations(): void
    {
        $target = CodeUseGraph::propertyNode('t\\c', 'value');
        $method = CodeUseGraph::functionLikeNode('a\\c::m');
        $graph = new CodeUseGraph(collect_locations: true, record_edges: false);

        $context = new Context();
        $context->calling_method_id = 'a\\c::m';
        $graph->addReference($target, $context, new Raw('', '/a.php', 'a.php', 0, 0));
        $graph->addEdge($method, $target);
        $graph->markAsPublicApi($method);

        self::assertSame([], $graph->getAllReferences());
        self::assertSame([], $graph->getReferenceLocations($target));
        self::assertNull($graph->getNodeFile($method));

        $cache_data = $graph->getCacheData();
        self::assertSame([], $cache_data['edges']);
        self::assertSame([], $cache_data['node_files']);
        self::assertFalse($cache_data['edges_recorded']);
    }

    public function testGraphRecordingEdgesDoesNotRecordANodeUsingItself(): void
    {
        $method = CodeUseGraph::functionLikeNode('a\\c::m');
        $graph = new CodeUseGraph(record_edges: true);

        $graph->addEdge($method, $method);

        self::assertSame([], $graph->getAllReferences());
    }

    public function testGraphWithEdgesNotesTheFilesOfItsReferencesOnly(): void
    {
        $method = CodeUseGraph::functionLikeNode('a\\c::m');
        $graph = new CodeUseGraph();
        self::addMethodReference($graph, 'a\\c::m', CodeUseGraph::propertyNode('t\\c', 'value'), '/a.php');

        self::assertTrue($graph->getCacheData()['edges_recorded']);

        // the file of a node with mutation info comes from its references
        $other_method = CodeUseGraph::functionLikeNode('b\\c::m');
        $graph->addMutationInfo($other_method, self::mutationInfo('/b.php'));
        self::assertSame('/a.php', $graph->getNodeFile($method));
        self::assertNull($graph->getNodeFile($other_method));
    }

    public function testCachedEdgesAreNotLoadedIntoAGraphWithoutEdges(): void
    {
        $target = CodeUseGraph::propertyNode('t\\c', 'value');
        $method = CodeUseGraph::functionLikeNode('a\\c::m');

        $source = new CodeUseGraph();
        self::addMethodReference($source, 'a\\c::m', $target, '/a.php');
        $source->addMutationInfo($method, self::mutationInfo('/a.php'));

        $graph = new CodeUseGraph(record_edges: false);
        $graph->loadCacheData($source->getCacheData());

        self::assertSame([], $graph->getAllReferences());
        self::assertSame([], $graph->getCacheData()['edges']);
        self::assertSame('/a.php', $graph->getNodeFile($method));
        self::assertArrayHasKey($method, $graph->getMutationInfo());
    }

    public function testMutationInfoOfAGraphWithoutEdgesIsRemovedWithItsFile(): void
    {
        $method = CodeUseGraph::functionLikeNode('a\\c::m');
        $other_method = CodeUseGraph::functionLikeNode('b\\c::m');
        $graph = new CodeUseGraph(record_edges: false);

        // builds the index of the files, which the nodes added later must be found in
        $graph->removeReferencesFromFile('/none.php');

        $graph->addMutationInfo($method, self::mutationInfo('/a.php'));
        $graph->addMutationInfo($other_method, self::mutationInfo('/b.php'));
        self::assertSame('/a.php', $graph->getNodeFile($method));

        // the first file seen stays
        $graph->addMutationInfo($method, self::mutationInfo('/moved.php'));
        self::assertSame('/a.php', $graph->getNodeFile($method));

        $graph->removeReferencesFromFile('/a.php');

        self::assertSame([$other_method], array_keys($graph->getMutationInfo()));
    }

    /**
     * @return MutationInfo
     * @psalm-pure
     */
    private static function mutationInfo(string $file_path): array
    {
        return [
            'intrinsic' => 0,
            'allowed' => 0,
            'callees' => [],
            'default_intrinsic' => 0,
            'default_callees' => [],
            'location' => new Raw('', $file_path, basename($file_path), 0, 0),
            'cased_name' => 'm',
            'suppressed_issues' => [],
            'class' => null,
            'start' => 0,
            'fresh' => true,
            'report' => true,
        ];
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
}
