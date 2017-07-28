<?php
namespace Psalm\Provider;

use Psalm\Checker\FileChecker;
use Psalm\Storage\FileStorage;

class FileStorageProvider
{
    /**
     * @param  string $file_path
     *
     * @return FileStorage
     */
    public function get($file_path)
    {
        $file_path = strtolower($file_path);

        if (!isset(FileChecker::$storage[$file_path])) {
            throw new \InvalidArgumentException('Could not get storage for ' . $file_path);
        }

        return FileChecker::$storage[$file_path];
    }

    /**
     * @return array<string, FileStorage>
     */
    public function getAll()
    {
        return FileChecker::$storage;
    }

    /**
     * @param  string $file_path
     *
     * @return FileStorage
     */
    public function create($file_path)
    {
        $file_path = strtolower($file_path);

        FileChecker::$storage[$file_path] = $storage = new FileStorage();

        $storage->file_path = $file_path;

        return $storage;
    }
}
