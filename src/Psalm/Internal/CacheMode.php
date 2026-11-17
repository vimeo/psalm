<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Psalm\Config;
use Psalm\Internal\Cache\AnalysisIdentity;

use const DIRECTORY_SEPARATOR;

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

    /**
     * The directory of the analysis identity of the config in its cache directory, where the caches keep their
     * entries: only a persistent cache creates it
     */
    public function getIdentityDirectory(Config $config): ?string
    {
        $cache_directory = $this->getDirectory($config);
        if ($cache_directory === null) {
            return null;
        }

        $identity = AnalysisIdentity::of($config);

        return $this === self::Persistent
            ? $identity->createDirectory($cache_directory)
            : $cache_directory . DIRECTORY_SEPARATOR . $identity->getFingerprint();
    }
}
