<?php
namespace Psalm\Internal\FileManipulation;

use Psalm\FileManipulation;

/**
 * @internal
 */
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
     * @return void
     */
    public static function addForCodeLocation(
        \Psalm\CodeLocation $code_location,
        string $replacement_text,
        bool $swallow_newlines = false
    ) {
        $bounds = $code_location->getSnippetBounds();

        if ($swallow_newlines) {
            $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();

            $codebase = $project_analyzer->getCodebase();

            $file_contents = $codebase->getFileContents($code_location->file_path);

            if (($file_contents[$bounds[0] - 1] ?? null) === PHP_EOL
                && ($file_contents[$bounds[0] - 2] ?? null) === PHP_EOL
            ) {
                $bounds[0] -= 2;
            }
        }

        self::add(
            $code_location->file_path,
            [
                new FileManipulation(
                    $bounds[0],
                    $bounds[1],
                    $replacement_text
                )
            ]
        );
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
     * @return array<string, FileManipulation[]>
     */
    public static function getAll()
    {
        return self::$file_manipulations;
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$file_manipulations = [];
    }
}
