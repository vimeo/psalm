<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * @internal
 */
final class ContainsAtomicVisitor extends TypeVisitor
{
    private bool $contains = false;

    /**
     * @param list<class-string<Atomic>> $atomic_classes
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly array $atomic_classes,
    ) {
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        foreach ($this->atomic_classes as $atomic_class) {
            if ($type instanceof $atomic_class) {
                $this->contains = true;

                return self::STOP_TRAVERSAL;
            }
        }

        return null;
    }

    /**
     * @psalm-mutation-free
     */
    public function matches(): bool
    {
        return $this->contains;
    }
}
