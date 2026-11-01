<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Internal\DataFlow\TaintSource;
use Psalm\Tests\TestCase;
use ReflectionProperty;

use function count;
use function function_exists;
use function memory_get_peak_usage;
use function memory_get_usage;
use function memory_reset_peak_usage;

final class TaintFlowGraphTest extends TestCase
{
    public function testAddGraphKeepsTheFirstEntryForEachId(): void
    {
        $first = new DataFlowNode('a', 'first', null);
        $second = new DataFlowNode('a', 'second', null);
        $other = new DataFlowNode('b', 'other', null);

        $graph = new TaintFlowGraph();
        $graph->addNode($first);
        $graph->addSource(new TaintSource('source', 'first source', null));
        // A sink is also stored as a node.
        $graph->addSink(new TaintSink('sink', 'first sink', null));

        $taint = new TaintFlowGraph();
        $taint->addNode($second);
        $taint->addNode($other);
        $taint->addSource(new TaintSource('source', 'second source', null));
        $taint->addSource(new TaintSource('other_source', 'other source', null));
        $taint->addSink(new TaintSink('sink', 'second sink', null));
        $taint->addPath($second, $other, 'arg');

        $graph->addGraph($taint);

        $this->assertSame(
            [
                'nodes' => 3,
                'sources' => 2,
                'sinks' => 1,
            ],
            [
                'nodes' => count($this->getEntries($graph, 'nodes')),
                'sources' => count($this->getEntries($graph, 'sources')),
                'sinks' => count($this->getEntries($graph, 'sinks')),
            ],
        );
        $this->assertSame('first', $this->getEntries($graph, 'nodes')['a']->label);
        $this->assertSame('other', $this->getEntries($graph, 'nodes')['b']->label);
        $this->assertSame('first source', $this->getEntries($graph, 'sources')['source']->label);
        $this->assertSame('other source', $this->getEntries($graph, 'sources')['other_source']->label);
        $this->assertSame('first sink', $this->getEntries($graph, 'sinks')['sink']->label);
        $this->assertSame([['a', 'b']], $graph->summarizeEdges());
    }

    /**
     * Each analyzed function merges its own small graph into the worker's graph.
     * That merge must not copy the nodes the worker has already collected.
     */
    public function testAddGraphDoesNotCopyTheNodesAlreadyMerged(): void
    {
        if (!function_exists('memory_reset_peak_usage')) {
            $this->markTestSkipped('memory_reset_peak_usage() needs PHP 8.2');
        }

        $graph = new TaintFlowGraph();
        $node = new DataFlowNode('node', 'node', null);

        $before = memory_get_usage();
        $big = new TaintFlowGraph();
        for ($i = 0; $i < 50_000; $i++) {
            $big->addNode(new DataFlowNode('node' . $i, 'node', null));
        }
        $graph->addGraph($big);
        unset($big);
        $merged_size = memory_get_usage() - $before;

        $small = new TaintFlowGraph();
        $small->addNode($node);

        /** @psalm-suppress UnusedFunctionCall it resets the peak as a side effect */
        memory_reset_peak_usage();
        $before = memory_get_peak_usage();
        $graph->addGraph($small);
        $growth = memory_get_peak_usage() - $before;

        // Copying the 50,000-entry nodes array would need several MB here.
        $this->assertLessThan(
            $merged_size / 20,
            $growth,
            'addGraph() copied the arrays it merges into',
        );
    }

    /**
     * @return array<string, DataFlowNode>
     */
    private function getEntries(TaintFlowGraph $graph, string $property): array
    {
        $entries = (new ReflectionProperty($graph, $property))->getValue($graph);
        $this->assertIsArray($entries);

        /** @var array<string, DataFlowNode> */
        return $entries;
    }
}
