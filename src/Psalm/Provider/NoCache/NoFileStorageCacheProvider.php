<?php
namespace Psalm\Provider\NoCache;

use Psalm\Storage\FileStorage;

class NoFileStorageCacheProvider extends \Psalm\Provider\FileStorageCacheProvider
{
    public function __construct()
    {
    }

    /**
     * @param  string $file_path
     * @param  string $file_contents
     *
     * @return void
     */
    public function writeToCache(FileStorage $storage, $file_contents)
    {
    }

    /**
     * @param  string $file_path
     * @param  string $file_contents
     *
     * @return FileStorage|null
     */
    public function getLatestFromCache($file_path, $file_contents)
    {
    }

    public function removeCacheForFile($file_path)
    {
    }
}
