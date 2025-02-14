<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Storage\ClassLikeStorage;
use UnexpectedValueException;

use function strtolower;

final class ClassLikeStorageInstanceCacheProvider extends ClassLikeStorageCacheProvider
{
    /** @var array<lowercase-string, ClassLikeStorage> */
    private array $cache = [];

    public function __construct()
    {
    }

    #[Override]
    public function writeToCache(ClassLikeStorage $storage, ?string $file_path, ?string $file_contents): void
    {
        $fq_classlike_name_lc = strtolower($storage->name);
        $this->cache[$fq_classlike_name_lc] = $storage;
    }

    /**
     * @param lowercase-string $fq_classlike_name_lc
     */
    #[Override]
    public function getLatestFromCache(string $fq_classlike_name_lc, ?string $file_path, ?string $file_contents): ClassLikeStorage
    {
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
