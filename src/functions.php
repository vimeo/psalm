<?php
namespace Psalm;

use Symfony\Component\Filesystem\Path;

/**
 * @deprecated Use {@see Symfony\Component\Filesystem\Path::isAbsolute}.
 *             No longer used by Psalm, going to be removed in Psalm 5
 */
function isAbsolutePath(string $path): bool
{
    return Path::isAbsolute($path);
}
