<?php

namespace Psalm\Internal\LanguageServer\Provider;

use Psalm\Internal\Provider\ClassLikeStorageCacheProvider as InternalClassLikeStorageCacheProvider;
use Psalm\Storage\ClassLikeStorage;
use UnexpectedValueException;

use function strtolower;

/**
 * @internal
 */
final class ClassLikeStorageCacheProvider extends InternalClassLikeStorageCacheProvider
{
    /** @var array<lowercase-string, ClassLikeStorage> */
    private array $cache = [];

    public function __construct()
    {
    }

    public function writeToCache(ClassLikeStorage $storage, ?string $file_path, ?string $file_contents): void
    {
        $fq_classlike_name_lc = strtolower($storage->name);
        $this->cache[$fq_classlike_name_lc] = $storage;
    }

    /**
     * @param lowercase-string $fq_classlike_name_lc
     */
    public function getLatestFromCache(
        string $fq_classlike_name_lc,
        ?string $file_path,
        ?string $file_contents
    ): ClassLikeStorage {
        $cached_value = $this->loadFromCache($fq_classlike_name_lc);

        if (!$cached_value) {
            throw new UnexpectedValueException('Should be in cache');
        }

        return $cached_value;
    }

    /**
     * @param lowercase-string $fq_classlike_name_lc
     */
    private function loadFromCache(string $fq_classlike_name_lc): ?ClassLikeStorage
    {
        return $this->cache[$fq_classlike_name_lc] ?? null;
    }
}
