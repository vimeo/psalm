<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TIntersection;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * Collects the intersections of a type that were deferred until the class constants and type aliases they refer to
 * are known
 *
 * @internal
 */
final class DeferredIntersectionCollector extends TypeVisitor
{
    /**
     * @var list<TIntersection>
     */
    private array $intersections = [];

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if (!$type instanceof TIntersection) {
            return null;
        }

        $this->intersections[] = $type;

        return self::DONT_TRAVERSE_CHILDREN;
    }

    /**
     * @return list<TIntersection>
     * @psalm-mutation-free
     */
    public function getIntersections(): array
    {
        return $this->intersections;
    }
}
