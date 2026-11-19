<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * Collects the closures capturing variables by reference that a value is, or contains, like the elements of an
 * array or what a closure returns.
 *
 * @internal
 */
final class ClosureByRefUsesCollector extends TypeVisitor
{
    /** @var list<TClosure> */
    private array $closures = [];

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TClosure && $type->byref_uses) {
            $this->closures[] = $type;
        }

        return null;
    }

    /**
     * @psalm-pure
     * @return list<TClosure>
     */
    public static function collect(?TypeNode $type): array
    {
        if ($type === null) {
            return [];
        }

        $collector = new self();
        $collector->traverse($type);

        return $collector->closures;
    }
}
