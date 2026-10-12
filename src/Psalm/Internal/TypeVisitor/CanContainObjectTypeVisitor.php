<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;
use Psalm\Type\Union;

/** @internal */
final class CanContainObjectTypeVisitor extends TypeVisitor
{
    private bool $contains_object_type = false;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Codebase $codebase,
    ) {
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        // an array holds objects only where its keys or values, which are traversed into, can be objects
        if (($type instanceof Union
            && ($type->hasObjectType() || $type->hasIterable() || $type->hasMixed())
        ) || ($type instanceof Atomic
            && ($type->isObjectType()
                || $type instanceof TIterable
                || $type->hasTraversableInterface($this->codebase)
                || $type instanceof TMixed)
        )) {
            $this->contains_object_type = true;
            return self::STOP_TRAVERSAL;
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->contains_object_type;
    }
}
