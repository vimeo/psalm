<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Internal\Type\PurityWildcard;
use Psalm\Storage\Capabilities;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

/**
 * Replaces every `_` purity in a type, at any depth, with `impure`.
 *
 * @internal
 */
final class PurityWildcardStripper extends MutableTypeVisitor
{
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (($type instanceof TClosure || $type instanceof TCallable)
            && PurityWildcard::isPlaceholder($type->purity)
        ) {
            $type = $type->setPurity(Capabilities::ALL);
        }

        return null;
    }
}
