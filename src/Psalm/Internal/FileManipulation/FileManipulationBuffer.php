<?php
namespace Psalm\Internal\FileManipulation;

use Psalm\FileManipulation;
use Psalm\Internal\Provider\FileProvider;

/**
 * @internal
 */
class FileManipulationBuffer
{
    /** @var array<string, FileManipulation[]> */
    private static $file_manipulations = [];

    /** @var CodeMigration[] */
    private static $code_migrations = [];

    /**
     * @param string $file_path
     * @param FileManipulation[] $file_manipulations
     *
     * @return void
     */
    public static function add($file_path, array $file_manipulations)
    {
        if (!isset(self::$file_manipulations[$file_path])) {
            self::$file_manipulations[$file_path] = [];
        }

        foreach ($file_manipulations as $file_manipulation) {
            self::$file_manipulations[$file_path][$file_manipulation->getKey()] = $file_manipulation;
        }
    }

    /** @param CodeMigration[] $code_migrations */
    public static function addCodeMigrations(array $code_migrations) : void
    {
        self::$code_migrations = array_merge(self::$code_migrations, $code_migrations);
    }

    /**
     * @return array{int, int}
     */
    private static function getCodeOffsets(
        string $source_file_path,
        int $source_start,
        int $source_end
    ) : array {
        if (!isset(self::$file_manipulations[$source_file_path])) {
            return [0, 0];
        }

        $start_offset = 0;
        $middle_offset = 0;

        foreach (self::$file_manipulations[$source_file_path] as $fm) {
            if ($fm->end < $source_start) {
                $start_offset += strlen($fm->insertion_text) - $fm->end + $fm->start;
            } elseif ($fm->start > $source_start
                && $fm->end < $source_end
            ) {
                $middle_offset += strlen($fm->insertion_text) - $fm->end + $fm->start;
            }
        }

        return [$start_offset, $middle_offset];
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
    public static function getManipulationsForFile($file_path)
    {
        if (!isset(self::$file_manipulations[$file_path])) {
            return [];
        }

        return self::$file_manipulations[$file_path];
    }

    /**
     * @param string $file_path
     *
     * @return array<string, FileManipulation[]>
     */
    public static function getMigrationManipulations(FileProvider $file_provider)
    {
        $code_migration_manipulations = [];

        foreach (self::$code_migrations as $code_migration) {
            list($start_offset, $middle_offset) = self::getCodeOffsets(
                $code_migration->source_file_path,
                $code_migration->source_start,
                $code_migration->source_end
            );

            if (!isset($code_migration_manipulations[$code_migration->source_file_path])) {
                $code_migration_manipulations[$code_migration->source_file_path] = [];
            }

            if (!isset($code_migration_manipulations[$code_migration->destination_file_path])) {
                $code_migration_manipulations[$code_migration->destination_file_path] = [];
            }

            $code_migration_manipulations[$code_migration->source_file_path][]
                = new FileManipulation(
                    $code_migration->source_start + $start_offset,
                    $code_migration->source_end + $middle_offset,
                    ''
                );

            list($destination_start_offset) = self::getCodeOffsets(
                $code_migration->destination_file_path,
                $code_migration->destination_start,
                $code_migration->destination_start
            );

            $manipulation = new FileManipulation(
                $code_migration->destination_start + $destination_start_offset,
                $code_migration->destination_start + $destination_start_offset,
                PHP_EOL . substr(
                    $file_provider->getContents($code_migration->source_file_path),
                    $code_migration->source_start + $start_offset,
                    $code_migration->source_end - $code_migration->source_start + $middle_offset
                ) . PHP_EOL
            );

            $code_migration_manipulations[$code_migration->destination_file_path][$manipulation->getKey()]
                = $manipulation;
        }

        return $code_migration_manipulations;
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
        self::$code_migrations = [];
    }
}
