<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

/**
 * @internal
 */
final class TaintSource extends DataFlowNode
{
    public static function fromNode(DataFlowNode $node, int $taints): self
    {
        $v = new self(
            $node->unspecialized_id ?? $node->id,
            $node->label,
            $node->code_location,
            $node->specialization_key,
            $node->taints,
        );
        $v->taints = $taints;
        return $v;
    }
}
