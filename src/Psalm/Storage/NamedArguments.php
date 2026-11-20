<?php

declare(strict_types=1);

namespace Psalm\Storage;

/**
 * How the arguments of a function or method can be passed, from the tag of its docblock, its class or the method it
 * overrides
 *
 * @psalm-immutable
 * @api
 */
enum NamedArguments
{
    /** `@no-named-arguments`, or internal code when `allowInternalNamedArgumentsCalls` is off: names may change */
    case NOT_ALLOWED;

    /** `@arguments-by-name`: every argument is passed by name, so parameter order may change */
    case REQUIRED;

    /** `@optional-arguments-by-name`: the arguments of optional parameters are passed by name */
    case REQUIRED_FOR_OPTIONAL;

    /** `@stable-parameter-names`: parameter names and order are both stable */
    case STABLE;
}
