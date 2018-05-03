<?php
namespace Psalm\Provider;

use Psalm\Storage\FileStorage;

class FileStorageProvider
{
    /**
     * A list of data useful to analyse files
     * Storing this statically is much faster (at least in PHP 7.2.1)
     *
     * @var array<string, FileStorage>
     */
    private static $storage = [];

    /**
     * @var FileStorageCacheProvider
     */
    public $cache;

    public function __construct(FileStorageCacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param  string $file_path
     *
     * @return FileStorage
     */
    public function get($file_path)
    {
        $file_path = strtolower($file_path);

        if (!isset(self::$storage[$file_path])) {
            throw new \InvalidArgumentException('Could not get file storage for ' . $file_path);
        }

        return self::$storage[$file_path];
    }

    /**
     * @param  string $file_path
     * @param  string $file_contents
     *
     * @return bool
     */
    public function has($file_path, $file_contents)
    {
        $file_path = strtolower($file_path);

        if (isset(self::$storage[$file_path])) {
            return true;
        }

        $cached_value = $this->cache->getLatestFromCache($file_path, $file_contents);

        if (!$cached_value) {
            return false;
        }

        self::$storage[$file_path] = $cached_value;

        return true;
    }

    /**
     * @return array<string, FileStorage>
     */
    public function getAll()
    {
        return self::$storage;
    }

    /**
     * @param  string $file_path
     *
     * @return FileStorage
     */
    public function create($file_path)
    {
        $file_path_lc = strtolower($file_path);

        self::$storage[$file_path_lc] = $storage = new FileStorage($file_path);

        return $storage;
    }

    /**
     * @return void
     */
    public static function deleteAll()
    {
        self::$storage = [];
    }
}
