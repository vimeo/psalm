<?php

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\ImmutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * @internal
 */
class ContainsStaticVisitor extends ImmutableTypeVisitor
{
    private bool $contains_static = false;

    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TNamedObject && ($type->value === 'static' || $type->is_static)) {
            $this->contains_static = true;
            return TypeVisitor::STOP_TRAVERSAL;
        }
        return null;
    }

    public function matches(): bool
    {
        return $this->contains_static;
    }
}
