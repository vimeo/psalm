<?php
namespace Psalm\Provider\NoCache;

use Psalm\Storage\ClassLikeStorage;

class NoClassLikeStorageCacheProvider extends \Psalm\Provider\ClassLikeStorageCacheProvider
{
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
        throw new \LogicException('nothing here');
    }
}
