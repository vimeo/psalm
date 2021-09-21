<?php
namespace Psalm\Tests\Internal\Provider;

use Psalm\Storage\ClassLikeStorage;

use function strtolower;

class ClassLikeStorageInstanceCacheProvider extends \Psalm\Internal\Provider\ClassLikeStorageCacheProvider
{
    /** @var array<string, ClassLikeStorage> */
    private $cache = [];

    public function __construct()
    {
    }

    public function writeToCache(ClassLikeStorage $storage, ?string $file_path, ?string $file_contents): void
    {
        $fq_classlike_name_lc = strtolower($storage->name);
        $this->cache[$fq_classlike_name_lc] = $storage;
    }

    public function getLatestFromCache(string $fq_classlike_name_lc, ?string $file_path, ?string $file_contents): ClassLikeStorage
    {
        $cached_value = $this->loadFromCache($fq_classlike_name_lc);

        if (!$cached_value) {
            throw new \UnexpectedValueException('Should be in cache');
        }

        return $cached_value;
    }

    /**
     * @param  string  $fq_classlike_name_lc
     *
     */
    private function loadFromCache($fq_classlike_name_lc): ?ClassLikeStorage
    {
        return $this->cache[$fq_classlike_name_lc] ?? null;
    }
}
