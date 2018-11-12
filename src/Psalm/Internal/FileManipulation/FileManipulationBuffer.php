<?php
namespace Psalm\Internal\FileManipulation;

use Psalm\FileManipulation;

class FileManipulationBuffer
{
    /** @var array<string, FileManipulation[]> */
    private static $file_manipulations = [];

    /**
     * @param string $file_path
     * @param FileManipulation[] $file_manipulations
     *
     * @return void
     */
    public static function add($file_path, array $file_manipulations)
    {
        self::$file_manipulations[$file_path] = isset(self::$file_manipulations[$file_path])
            ? array_merge(self::$file_manipulations[$file_path], $file_manipulations)
            : $file_manipulations;
    }

    /**
     * @param string $file_path
     *
     * @return FileManipulation[]
     */
    public static function getForFile($file_path)
    {
        if (!isset(self::$file_manipulations[$file_path])) {
            return [];
        }

        return self::$file_manipulations[$file_path];
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$file_manipulations = [];
    }
}
