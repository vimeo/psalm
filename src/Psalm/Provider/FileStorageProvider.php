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
        $file_path = strtolower($file_path);

        self::$storage[$file_path] = $storage = new FileStorage();

        $storage->file_path = $file_path;

        return $storage;
    }

    /**
     * @return void
     */
    public function deleteAll()
    {
        self::$storage = [];
    }
}
