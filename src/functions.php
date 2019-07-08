<?php
namespace Psalm;

use InvalidArgumentException;
use Webmozart\PathUtil\Path;
use function preg_match;
use function sprintf;

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
