<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

/**
 * @internal
 */
final class TaintSource extends DataFlowNode
{
    public static function fromNode(DataFlowNode $node): self
    {
        return new self(
            $node->id,
            $node->label,
            $node->code_location,
            $node->specialization_key,
            $node->taints,
        );
    }
}
