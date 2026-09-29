<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Internal\Type\PurityWildcard;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

/**
 * Replaces every `_` purity in a type, at any depth, with a given purity.
 *
 * @internal
 */
final class PurityWildcardBinder extends MutableTypeVisitor
{
    /**
     * @psalm-capabilities read-props
     */
    public function __construct(
        private readonly Union $purity,
    ) {
    }

    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (($type instanceof TClosure || $type instanceof TCallable)
            && PurityWildcard::isPlaceholder($type->purity)
        ) {
            $type = $type->setPurity($this->purity);
        }

        return null;
    }
}
