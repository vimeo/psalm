<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

/**
 * Keeps only the merged shape of the shapes a keyed array holds apart
 *
 * @internal
 */
final class DisjointShapeCollapser extends MutableTypeVisitor
{
    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TKeyedArray && $type->disjoint_shapes !== null) {
            $type = $type->setDisjointShapes([]);
        }

        return null;
    }
}
