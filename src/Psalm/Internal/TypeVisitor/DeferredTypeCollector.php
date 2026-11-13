<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TGlobalConstant;
use Psalm\Type\Atomic\TIntMask;
use Psalm\Type\Atomic\TIntersection;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

use function array_any;

/**
 * Collects the types whose checks were deferred until the class constants, global constants and type aliases they
 * refer to are known: intersections, and int-masks of global constants
 *
 * @internal
 */
final class DeferredTypeCollector extends TypeVisitor
{
    /**
     * @var list<TIntersection|TIntMask>
     */
    private array $types = [];

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TIntersection
            || ($type instanceof TIntMask
                && array_any($type->values, static fn($value): bool => $value instanceof TGlobalConstant))
        ) {
            $this->types[] = $type;

            return self::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    /**
     * @return list<TIntersection|TIntMask>
     * @psalm-mutation-free
     */
    public function getTypes(): array
    {
        return $this->types;
    }
}
