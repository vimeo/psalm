<?php
namespace Psalm;

use Webmozart\PathUtil\Path;

/**
 * @param string $path
 * @return bool
 *
 * @deprecated Use {@see Webmozart\PathUtil\Path::isAbsolute} instead
 */
function isAbsolutePath($path)
{
    return Path::isAbsolute($path);
}
