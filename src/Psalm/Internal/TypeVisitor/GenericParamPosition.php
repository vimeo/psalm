<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

/**
 * How the position of a type param of a generic object relates to the position of the generic object
 *
 * @internal
 */
enum GenericParamPosition
{
    /** The position of the generic object: a covariant type param, or a covariant call-site variance */
    case Same;
    /** An invariant type param of a method signature: neither input nor output */
    case Invariant;
    /** No position, for an invariant type param outside a method signature, or a `*` */
    case None;
    /** The opposite position: a `contravariant` call-site variance */
    case Opposite;
}
