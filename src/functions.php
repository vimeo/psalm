<?php
namespace Psalm;

use Webmozart\PathUtil\Path;

/**
 * @param string $path
 *
 * @deprecated Use {@see Webmozart\PathUtil\Path::isAbsolute}. No longer used by Psalm, going to be removed in Psalm 5
 */
function isAbsolutePath($path): bool
{
    return Path::isAbsolute($path);
}
