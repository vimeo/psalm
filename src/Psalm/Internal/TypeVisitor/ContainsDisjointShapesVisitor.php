<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * @internal
 */
final class ContainsDisjointShapesVisitor extends TypeVisitor
{
    private bool $contains_disjoint_shapes = false;

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TKeyedArray && $type->disjoint_shapes !== null) {
            $this->contains_disjoint_shapes = true;
            return self::STOP_TRAVERSAL;
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->contains_disjoint_shapes;
    }
}
