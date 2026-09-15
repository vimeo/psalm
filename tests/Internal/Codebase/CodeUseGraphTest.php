<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Closure;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestCodeUseGraph;

final class CodeUseGraphTest extends TestCase
{
    /**
     * @return Closure(string): bool
     */
    private static function noExtraRoots(): Closure
    {
        return static fn(string $_node_id): bool => false;
    }

    public function testResolvesCyclesRecursively(): void
    {
        $graph = new TestCodeUseGraph(self::noExtraRoots());
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

        $graph->resolve();

        self::assertTrue($graph->isUsed($used_a));
        self::assertTrue($graph->isUsed($used_b));
        self::assertFalse($graph->isUsed($unused_a));
        self::assertFalse($graph->isUsed($unused_b));
    }

    public function testUsedReturnUsesFunction(): void
    {
        $graph = new TestCodeUseGraph(self::noExtraRoots());
        $function = CodeUseGraph::functionLikeNode('a\\c::m');
        $return = CodeUseGraph::functionLikeReturnNode('a\\c::m');
        $graph->addEdge($return, $function, CodeUseGraph::EDGE_RETURN);
        $graph->markAsPublicApi($return);

        $graph->resolve();

        self::assertTrue($graph->isUsed($return));
        self::assertTrue($graph->isUsed($function));
        self::assertSame([$return => true], $graph->getReferencingNodes($function));
    }

    public function testCanResolveAgainAfterGraphChanges(): void
    {
        $graph = new TestCodeUseGraph(self::noExtraRoots());
        $a = CodeUseGraph::functionLikeNode('a\\c::m');
        $b = CodeUseGraph::functionLikeNode('b\\c::m');
        $graph->markAsPublicApi($a);
        $graph->resolve();
        self::assertFalse($graph->isUsed($b));

        $graph->addEdge($a, $b);
        $graph->resolve();

        self::assertTrue($graph->isUsed($b));
    }

    public function testWriteEdgeDoesNotMarkPropertyUsed(): void
    {
        $graph = new TestCodeUseGraph(self::noExtraRoots());
        $property = CodeUseGraph::propertyNode('a\\c', 'value');

        // a read from top-level code of /read.php, a write from /write.php
        $graph->addReference($property, null, null, CodeUseGraph::EDGE_USE, '/read.php');
        $graph->addReference($property, null, null, CodeUseGraph::EDGE_WRITE, '/write.php');

        $graph->resolve();
        self::assertTrue($graph->isUsed($property), 'a read keeps the property used');

        // dropping the only read leaves just the write, which must not keep it used
        $graph->removeReferencesFromFile('/read.php');
        $graph->resolve();

        self::assertFalse($graph->isUsed($property), 'a write alone does not use the property');
        self::assertSame(
            [CodeUseGraph::fileNode('/write.php') => true],
            $graph->getReferencingNodes($property),
        );
    }

    public function testExternalCallerMarksTargetUsed(): void
    {
        $method = CodeUseGraph::functionLikeNode('a\\c::m');
        $external = CodeUseGraph::functionLikeNode('vendor\\c::caller');

        // the caller belongs to code outside the project, so what it calls is used
        $graph = new TestCodeUseGraph(static fn(string $node_id): bool => $node_id === $external);
        $graph->addEdge($external, $method);

        $graph->resolve();

        self::assertTrue($graph->isUsed($method));
    }

    public function testFreeFunctionIsNotARootByItself(): void
    {
        $graph = new TestCodeUseGraph(self::noExtraRoots());
        $function = CodeUseGraph::functionLikeNode('some_free_fn');
        $target = CodeUseGraph::classNode('used\\only\\by\\fn');
        $graph->addEdge($function, $target);

        $graph->resolve();

        // a free function is no longer a root: it (and what it references) is
        // used only when the function is itself referenced
        self::assertFalse($graph->isUsed($function));
        self::assertFalse($graph->isUsed($target));
    }
}
