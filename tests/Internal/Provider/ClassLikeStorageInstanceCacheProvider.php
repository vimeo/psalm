<?php
namespace Psalm\Tests\Internal\Provider;

use Psalm\Config;
use Psalm\Storage\ClassLikeStorage;

class ClassLikeStorageInstanceCacheProvider extends \Psalm\Internal\Provider\ClassLikeStorageCacheProvider
{
    /** @var array<string, ClassLikeStorage> */
    private $cache = [];

    public function __construct()
    {
    }

    /**
     * @param  string|null $file_path
     * @param  string|null $file_contents
     *
     * @return void
     */
    public function writeToCache(ClassLikeStorage $storage, $file_path, $file_contents)
    {
        $fq_classlike_name_lc = strtolower($storage->name);
        $this->cache[$fq_classlike_name_lc] = $storage;
    }

    /**
     * @param  string  $fq_classlike_name_lc
     * @param  string|null $file_path
     * @param  string|null $file_contents
     *
     * @return ClassLikeStorage
     */
    public function getLatestFromCache($fq_classlike_name_lc, $file_path, $file_contents)
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
     * @return ClassLikeStorage|null
     */
    private function loadFromCache($fq_classlike_name_lc)
    {
        return $this->cache[$fq_classlike_name_lc] ?? null;
    }
}
