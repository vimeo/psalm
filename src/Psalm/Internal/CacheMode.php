<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Psalm\Config;

/**
 * Where a cache keeps its entries
 *
 * @internal
 */
enum CacheMode
{
    /** In the cache directory of the config */
    case Persistent;

    /** Read from the cache directory and never written: no directory, lock or entry is created, saved items stay in memory */
    case ReadOnly;

    /** In memory only */
    case Memory;

    /** The cache directory of the config: only a persistent cache creates it */
    public function getDirectory(Config $config): ?string
    {
        return match ($this) {
            self::Persistent => $config->getCacheDirectory(),
            self::ReadOnly => $config->cache_directory,
            self::Memory => null,
        };
    }
}
