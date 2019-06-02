<?php
namespace Psalm;

use InvalidArgumentException;

/**
 * @param string $path
 *
 * @return bool
 */
function isAbsolutePath($path)
{
    // Optional wrapper(s).
    $regex = '%^(?<wrappers>(?:[[:print:]]{2,}://)*)';

    // Optional root prefix.
    $regex .= '(?<root>(?:[[:alpha:]]:/|/)?)';

    // Actual path.
    $regex .= '(?<path>(?:[[:print:]]*))$%';

    $parts = [];

    if (!preg_match($regex, $path, $parts)) {
        throw new InvalidArgumentException(sprintf('Path is not valid, "%s" given.', $path));
    }

    if ('' !== $parts['root']) {
        return true;
    }

    return false;
}
