<?php
namespace Psalm;

use Symfony\Component\Filesystem\Path;

/**
 * @deprecated Use {@see Webmozart\PathUtil\Path::isAbsolute}. No longer used by Psalm, going to be removed in Psalm 5
 */
function isAbsolutePath(string $path): bool
{
    return Path::isAbsolute($path);
}
