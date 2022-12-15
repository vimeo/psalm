<?php

namespace Psalm\Type;

interface TypeNode
{
    /** @internal Should only be used by the TypeVisitor */
    public function visit(TypeVisitor $visitor): bool;

    /**
     * @param static $node
     * @param-out static $node
     * @internal Should only be used by the MutableTypeVisitor
     */
    public static function visitMutable(MutableTypeVisitor $visitor, &$node, bool $cloned): bool;
}
