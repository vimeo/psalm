<?php
namespace Psalm\Tests\Internal\Provider;

use Psalm\Storage\FileStorage;
use function strtolower;

class FileStorageInstanceCacheProvider extends \Psalm\Internal\Provider\FileStorageCacheProvider
{
    /** @var array<string, FileStorage> */
    private $cache = [];

    public function __construct()
    {
    }

    /**
     * @return void
     */
    public function writeToCache(FileStorage $storage, string $file_contents)
    {
        $file_path = strtolower($storage->file_path);
        $this->cache[$file_path] = $storage;
    }

    public function getLatestFromCache(string $file_path, string $file_contents): ?FileStorage
    {
        $cached_value = $this->loadFromCache(strtolower($file_path));

        if (!$cached_value) {
            return null;
        }

        return $cached_value;
    }

    /**
     * @return void
     */
    public function removeCacheForFile(string $file_path)
    {
        unset($this->cache[strtolower($file_path)]);
    }

    private function loadFromCache(string $file_path): ?FileStorage
    {
        return $this->cache[strtolower($file_path)] ?? null;
    }
}
