<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Internal\Type\PurityWildcard;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * Finds a closure or callable with the `_` purity anywhere in a type.
 *
 * @internal
 */
final class PurityWildcardFinder extends TypeVisitor
{
    private bool $found = false;

    /**
     * @psalm-external-mutation-free
     * @return self::STOP_TRAVERSAL|null
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if (($type instanceof TClosure || $type instanceof TCallable)
            && PurityWildcard::isPlaceholder($type->purity)
        ) {
            $this->found = true;

            return self::STOP_TRAVERSAL;
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->found;
    }
}
