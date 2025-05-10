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

    #[Override]
    public function getLatestFromCache(string $fq_classlike_name_lc, ?string $file_path, string $file_contents): ClassLikeStorage
    {
        $cached_value = $this->cache[strtolower($fq_classlike_name_lc)] ?? null;

        if (!$cached_value) {
            throw new UnexpectedValueException('Should be in cache');
        }

        return $cached_value;
    }
}
