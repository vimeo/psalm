<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Closure;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\MethodIdentifier;
use Psalm\Interner;
use Psalm\Tests\TestCase;

final class CodeUseGraphTest extends TestCase
{
    /**
     * Nothing is treated as external code in these unit tests.
     *
     * @return Closure(string): bool
     */
    private static function notExternal(): Closure
    {
        return static fn(string $_node_id): bool => false;
    }

    /**
     * @psalm-pure
     */
    private static function method(string $fq_class_name, string $method_name): MethodIdentifier
    {
        return new MethodIdentifier(Interner::intern($fq_class_name), Interner::intern($method_name));
    }

    public function testResolvesCyclesRecursively(): void
    {
        $graph = new CodeUseGraph();
        // method ids (with ::) so they are not free-function entry points
        $used_a = CodeUseGraph::functionLikeNode(self::method('used\\c', 'a'));
        $used_b = CodeUseGraph::functionLikeNode(self::method('used\\c', 'b'));
        $unused_a = CodeUseGraph::functionLikeNode(self::method('unused\\c', 'a'));
        $unused_b = CodeUseGraph::functionLikeNode(self::method('unused\\c', 'b'));

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
        $function = CodeUseGraph::functionLikeNode(self::method('a\\c', 'm'));
        $return = CodeUseGraph::functionLikeReturnNode(self::method('a\\c', 'm'));
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
        $a = CodeUseGraph::functionLikeNode(self::method('a\\c', 'm'));
        $b = CodeUseGraph::functionLikeNode(self::method('b\\c', 'm'));
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
        $property = CodeUseGraph::propertyNode(Interner::intern('a\\c'), Interner::intern('value'));

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

    public function testExternalCallerMarksTargetUsed(): void
    {
        $graph = new CodeUseGraph();
        $method = CodeUseGraph::functionLikeNode(self::method('a\\c', 'm'));
        $external = CodeUseGraph::functionLikeNode(self::method('vendor\\c', 'caller'));
        $graph->addEdge($external, $method);

        // the caller belongs to code outside the project, so what it calls is used
        $graph->resolve(static fn(string $node_id): bool => $node_id === $external);

        self::assertTrue($graph->isUsed($method));
    }
}
