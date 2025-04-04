<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use AssertionError;
use Override;
use Psalm\Internal\DataFlow\DataFlowNode;

/**
 * @internal
 */
final class CombinedFlowGraph extends DataFlowGraph
{
    public function __construct(
        public readonly VariableUseGraph $variable_use_graph,
        public readonly TaintFlowGraph $taint_flow_graph,
    ) {
    }
    #[Override]
    public function addNode(DataFlowNode $node): void
    {
        $this->variable_use_graph->addNode($node);
        $this->taint_flow_graph->addNode($node);
    }
    #[Override]
    public function addPath(
        DataFlowNode $from,
        DataFlowNode $to,
        string $path_type,
        int $added_taints = 0,
        int $removed_taints = 0,
    ): void {
        $this->variable_use_graph->addPath($from, $to, $path_type, $added_taints, $removed_taints);
        $this->taint_flow_graph->addPath($from, $to, $path_type, $added_taints, $removed_taints);
    }

    public function addSource(DataFlowNode $node): void
    {
        $this->taint_flow_graph->addSource($node);
    }

    public function addSink(DataFlowNode $node): void
    {
        $this->taint_flow_graph->addSink($node);
    }

    #[Override]
    public function summarizeEdges(): never
    {
        throw new AssertionError("Unreachable");
    }

    #[Override]
    public function getEdgeStats(): never
    {
        throw new AssertionError("Unreachable");
    }
}
