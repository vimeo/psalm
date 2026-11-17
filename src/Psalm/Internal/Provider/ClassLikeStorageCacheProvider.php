<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\CacheMode;
use Psalm\Storage\ClassLikeStorage;

use function hash;
use function strtolower;

/**
 * @internal
 */
final class ClassLikeStorageCacheProvider
{
    /** @var Cache<ClassLikeStorage> */
    private readonly Cache $cache;

    public function __construct(Config $config, string $composerLock, CacheMode $mode = CacheMode::Persistent)
    {
        $this->cache = new Cache($config, 'classlike_cache', [$composerLock], $mode);
    }

    public function consolidate(): void
    {
        $this->cache->consolidate();
    }

    public function writeToCache(ClassLikeStorage $storage, string $file_path, string $file_contents): void
    {
        $fq_classlike_name_lc = strtolower($storage->name);

        $this->cache->saveItem($file_path."\0".$fq_classlike_name_lc, $storage, hash('xxh128', $file_contents));
    }

    /**
     * Drops the storage that the cache holds in memory, which may have been populated in place since it was
     * saved. The copy on disk is from before that.
     *
     * @param lowercase-string $fq_classlike_name_lc
     * @param string $file_path the file that declared the class-like, which keys its entry
     * @psalm-capabilities read-props|write-props|write-refs
     */
    public function evict(string $fq_classlike_name_lc, string $file_path): void
    {
        $this->cache->evictItem($file_path."\0".$fq_classlike_name_lc);
    }

    /**
     * @param lowercase-string $fq_classlike_name_lc
     */
    public function getLatestFromCache(
        string $fq_classlike_name_lc,
        ?string $file_path,
        string $file_contents,
    ): ?ClassLikeStorage {
        return $this->cache->getItem(
            $file_path."\0".$fq_classlike_name_lc,
            hash('xxh128', $file_contents),
        );
    }
}
