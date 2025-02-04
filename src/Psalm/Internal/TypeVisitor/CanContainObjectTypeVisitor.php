<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Psalm\Codebase;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;
use Psalm\Type\Union;

/** @internal */
final class CanContainObjectTypeVisitor extends TypeVisitor
{
    private bool $contains_object_type = false;

    public function __construct(
        private readonly Codebase $codebase,
    ) {
    }

    protected function enterNode(TypeNode $type): ?int
    {
        if (($type instanceof Union
            && ($type->hasObjectType() || $type->hasIterable() || $type->hasMixed())
        ) || ($type instanceof Atomic
            && ($type->isObjectType() || $type->isIterable($this->codebase) || $type instanceof TMixed)
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
