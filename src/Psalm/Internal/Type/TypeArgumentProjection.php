<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

/**
 * What a type argument of a generic object stands for besides the type it holds, see TGenericObject::getProjection()
 *
 * @internal
 */
enum TypeArgumentProjection
{
    /** The `*` of `Pair<int, *>`: some unknown type within the bound of the template, which the argument holds */
    case Star;

    /** The `covariant X` of `Box<covariant X>`: some unknown type within `X`, which the argument holds */
    case Covariant;

    /** The `contravariant X` of `Box<contravariant X>`: some unknown type that `X`, which the argument holds, is within */
    case Contravariant;

    /** An argument the use of the class left out (`Pair`): the bound of the template, which a `*` fits */
    case Omitted;

    /**
     * Whether the argument is one unknown type, so that what is read from it and what is written to it differ
     *
     * @psalm-mutation-free
     */
    public function isUnknown(): bool
    {
        return $this !== self::Omitted;
    }
}
