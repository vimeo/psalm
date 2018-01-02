<?php
namespace Psalm\FileManipulation;

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

        $file_manipulations = [];

        foreach (self::$file_manipulations[$file_path] as $file_manipulation) {
            $file_manipulations[$file_manipulation->start] = $file_manipulation;
        }

        return $file_manipulations;
    }
}
