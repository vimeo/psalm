<?php
namespace Psalm;

use Webmozart\PathUtil\Path;
use function preg_match;

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

function getMemoryLimitInBytes(): int
{
    $limit = ini_get('memory_limit');
    // for unlimited = -1
    if ($limit < 0) {
        return -1;
    }

    if (preg_match('/^(\d+)(\D?)$/', $limit, $matches)) {
        $limit = (int)$matches[1];
        switch (strtoupper($matches[2] ?? '')) {
            case 'G': {
                $limit *= 1024 * 1024 * 1024;
                break;
            }
            case 'M': {
                $limit *= 1024 * 1024;
                break;
            }
            case 'K': {
                $limit *= 1024;
                break;
            }
        }
    }

    return (int)$limit;
}
