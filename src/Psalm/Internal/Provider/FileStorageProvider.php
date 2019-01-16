<?php
namespace Psalm\Internal\Provider;

use Psalm\Storage\FileStorage;

/**
 * @internal
 */
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
     * A list of data useful to analyse new files
     * Storing this statically is much faster (at least in PHP 7.2.1)
     *
     * @var array<string, FileStorage>
     */
    private static $new_storage = [];

    /**
     * @var ?FileStorageCacheProvider
     */
    public $cache;

    public function __construct(FileStorageCacheProvider $cache = null)
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
     *
     * @return void
     */
    public function remove($file_path)
    {
        unset(self::$storage[strtolower($file_path)]);
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

        if (!$this->cache) {
            return false;
        }

        $cached_value = $this->cache->getLatestFromCache($file_path, $file_contents);

        if (!$cached_value) {
            return false;
        }

        self::$storage[$file_path] = $cached_value;
        self::$new_storage[$file_path] = $cached_value;

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
     * @return array<string, FileStorage>
     */
    public function getNew()
    {
        return self::$new_storage;
    }

    /**
     * @param array<string, FileStorage> $more
     * @return void
     */
    public function addMore(array $more)
    {
        self::$new_storage = array_merge(self::$new_storage, $more);
        self::$storage = array_merge(self::$storage, $more);
    }

    /**
     * @param  string $file_path
     *
     * @return FileStorage
     */
    public function create($file_path)
    {
        $file_path_lc = strtolower($file_path);

        $storage = new FileStorage($file_path);
        self::$storage[$file_path_lc] = $storage;
        self::$new_storage[$file_path_lc] = $storage;

        return $storage;
    }

    /**
     * @return void
     */
    public static function deleteAll()
    {
        self::$storage = [];
    }

    /**
     * @return void
     */
    public static function populated()
    {
        self::$new_storage = [];
    }
}
