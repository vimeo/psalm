<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Internal\Type\PurityWildcard;
use Psalm\Type\Atomic;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * Finds the `_` purity anywhere in a type.
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
        if ($type instanceof Atomic && PurityWildcard::isPlaceholder($type)) {
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
