<?php

namespace Psalm\Type;

interface TypeNode
{
    /** @internal Should only be used by the ImmutableTypeVisitor */
    public function visit(ImmutableTypeVisitor $visitor): bool;
    /**
     * @param static $node
     * @param-out static $node
     * @internal Should only be used by the TypeVisitor
     */
    public static function visitMutable(TypeVisitor $visitor, &$node, bool $cloned): bool;
}
