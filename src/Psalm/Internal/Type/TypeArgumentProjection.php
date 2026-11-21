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

    /** An argument the use of the class left out (`Pair`): the bound of the template, which a `*` fits */
    case Omitted;
}
