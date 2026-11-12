<?php

declare(strict_types=1);

namespace Psalm\Internal\FileManipulation;

use Psalm\CodeLocation;
use Psalm\CodeLocation\DocblockTypeLocation;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FileProvider;
use Psalm\IssueBuffer;

use function array_key_last;
use function array_merge;
use function array_pop;
use function array_slice;
use function array_values;
use function preg_match;
use function strlen;
use function strpos;
use function strrpos;
use function substr;
use function substr_replace;

/**
 * @internal
 * @psalm-capabilities read-props|write-this-props|write-props|write-refs|read-globals|write-globals
 */
final class FileManipulationBuffer
{
    /** @var array<string, FileManipulation[]> */
    private static array $file_manipulations = [];

    /**
     * One entry per open IssueBuffer recording level, innermost last. A manipulation added while a level is open
     * waits there until the level is committed (see bubbleUp()) or dropped (see stopRecording()),
     * like the issues of that level.
     *
     * @var array<int, array<string, array<string, FileManipulation>>>
     */
    private static array $recorded_manipulations = [];

    /** @var CodeMigration[] */
    private static array $code_migrations = [];

    /**
     * @param FileManipulation[] $file_manipulations
     * @psalm-capabilities read-props|write-this-props|write-refs|read-globals|write-globals
     */
    public static function add(string $file_path, array $file_manipulations): void
    {
        // the levels are dropped by stopRecording(), but not by a cache clear that closes them
        $level = IssueBuffer::isRecording() ? array_key_last(self::$recorded_manipulations) : null;

        if ($level !== null) {
            foreach ($file_manipulations as $file_manipulation) {
                self::$recorded_manipulations[$level][$file_path][$file_manipulation->getKey()] = $file_manipulation;
            }

            return;
        }

        if (!isset(self::$file_manipulations[$file_path])) {
            self::$file_manipulations[$file_path] = [];
        }

        foreach ($file_manipulations as $file_manipulation) {
            self::$file_manipulations[$file_path][$file_manipulation->getKey()] = $file_manipulation;
        }
    }

    /**
     * Opens a level that holds back the manipulations added until it is committed or dropped
     *
     * @internal Use IssueBuffer::startRecording()
     * @param int $recording_level the IssueBuffer recording level being opened
     * @psalm-capabilities read-props|write-this-props|write-refs|read-globals|write-globals
     */
    public static function startRecording(int $recording_level): void
    {
        // any level left from before is a stale one: IssueBuffer starts at 1
        self::$recorded_manipulations = [...array_slice(self::$recorded_manipulations, 0, $recording_level - 1), []];
    }

    /**
     * Closes the innermost level and drops the manipulations that are still in it
     *
     * @internal Use IssueBuffer::stopRecording()
     * @psalm-capabilities read-props|write-this-props|write-refs|read-globals|write-globals
     */
    public static function stopRecording(): void
    {
        array_pop(self::$recorded_manipulations);
    }

    /**
     * Takes the manipulations out of the innermost level, to commit them with bubbleUp() once the level is closed
     *
     * @return array<string, array<string, FileManipulation>>
     * @psalm-capabilities read-props|write-this-props|write-refs|read-globals|write-globals
     */
    public static function clearRecordingLevel(): array
    {
        $level = array_key_last(self::$recorded_manipulations);

        if ($level === null) {
            return [];
        }

        $recorded_manipulations = self::$recorded_manipulations[$level];
        self::$recorded_manipulations[$level] = [];

        return $recorded_manipulations;
    }

    /**
     * Commits manipulations taken out by clearRecordingLevel() to the innermost open level,
     * or to the file when none is open
     *
     * @param array<string, array<string, FileManipulation>> $recorded_manipulations
     * @psalm-capabilities read-props|write-this-props|write-refs|read-globals|write-globals
     */
    public static function bubbleUp(array $recorded_manipulations): void
    {
        foreach ($recorded_manipulations as $file_path => $file_manipulations) {
            self::add($file_path, array_values($file_manipulations));
        }
    }

    /**
     * @param CodeMigration[] $code_migrations
     * @psalm-external-mutation-free
     */
    public static function addCodeMigrations(array $code_migrations): void
    {
        self::$code_migrations = array_merge(self::$code_migrations, $code_migrations);
    }

    /**
     * @return array{int, int}
     * @psalm-external-mutation-free
     */
    private static function getCodeOffsets(
        string $source_file_path,
        int $source_start,
        int $source_end,
    ): array {
        if (!isset(self::$file_manipulations[$source_file_path])) {
            return [0, 0];
        }

        $start_offset = 0;
        $middle_offset = 0;

        foreach (self::$file_manipulations[$source_file_path] as $fm) {
            $offset = strlen($fm->insertion_text) - $fm->end + $fm->start;

            if ($fm->end < $source_start) {
                $start_offset += $offset;
                $middle_offset += $offset;
            } elseif ($fm->start > $source_start
                && $fm->end < $source_end
            ) {
                $middle_offset += $offset;
            }
        }

        return [$start_offset, $middle_offset];
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs|read-globals|write-globals
     */
    public static function addForCodeLocation(
        CodeLocation $code_location,
        string $replacement_text,
        bool $swallow_newlines = false,
    ): void {
        $bounds = $code_location->getSnippetBounds();

        if ($swallow_newlines) {
            $project_analyzer = ProjectAnalyzer::getInstance();

            $codebase = $project_analyzer->getCodebase();

            $file_contents = $codebase->getFileContents($code_location->file_path);

            if ($bounds[0] >= 2
                && ($file_contents[$bounds[0] - 1] ?? null) === "\n"
                && ($file_contents[$bounds[0] - 2] ?? null) === "\n"
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
                    $replacement_text,
                ),
            ],
        );
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs|read-globals|write-globals
     */
    public static function addVarAnnotationToRemove(DocblockTypeLocation $code_location): void
    {
        $bounds = $code_location->getSelectionBounds();

        $project_analyzer = ProjectAnalyzer::getInstance();

        $codebase = $project_analyzer->getCodebase();

        $file_contents = $codebase->getFileContents($code_location->file_path);

        $comment_start = strrpos($file_contents, '/**', $bounds[0] - strlen($file_contents));

        if ($comment_start === false) {
            return;
        }

        $comment_end = strpos($file_contents, '*/', $bounds[1]);

        if ($comment_end === false) {
            return;
        }

        $comment_end += 2;

        $comment_text = substr($file_contents, $comment_start, $comment_end - $comment_start);

        $var_type_comment_start = $bounds[0] - $comment_start;
        $var_type_comment_end = $bounds[1] - $comment_start;

        $var_start = strrpos($comment_text, '@var', $var_type_comment_start - strlen($comment_text));
        $var_end = strpos($comment_text, "\n", $var_type_comment_end);

        if ($var_start && $var_end) {
            $var_start = strrpos($comment_text, "\n", $var_start - strlen($comment_text)) ?: $var_start;
            $comment_text = substr_replace($comment_text, '', $var_start, $var_end - $var_start);
            if (preg_match('@^/\*\*\n(\s*\*\s*\n)*\s*\*?\*/$@', $comment_text)) {
                $comment_text = '';
            }
        } else {
            $comment_text = '';
        }

        self::add(
            $code_location->file_path,
            [
                new FileManipulation(
                    $comment_start,
                    $comment_end,
                    $comment_text,
                    false,
                    $comment_text === '',
                ),
            ],
        );
    }

    /**
     * @return FileManipulation[]
     * @psalm-external-mutation-free
     */
    public static function getManipulationsForFile(string $file_path): array
    {
        return self::$file_manipulations[$file_path] ?? [];
    }

    /**
     * @return array<string, FileManipulation[]>
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs|read-globals|write-globals
     */
    public static function getMigrationManipulations(FileProvider $file_provider): array
    {
        $code_migration_manipulations = [];

        foreach (self::$code_migrations as $code_migration) {
            [$start_offset, $middle_offset] = self::getCodeOffsets(
                $code_migration->source_file_path,
                $code_migration->source_start,
                $code_migration->source_end,
            );

            if (!isset($code_migration_manipulations[$code_migration->source_file_path])) {
                $code_migration_manipulations[$code_migration->source_file_path] = [];
            }

            if (!isset($code_migration_manipulations[$code_migration->destination_file_path])) {
                $code_migration_manipulations[$code_migration->destination_file_path] = [];
            }

            $delete_file_manipulation = new FileManipulation(
                $code_migration->source_start + $start_offset,
                $code_migration->source_end + $middle_offset,
                '',
            );

            $code_migration_manipulations[$code_migration->source_file_path][] = $delete_file_manipulation;

            [$destination_start_offset] = self::getCodeOffsets(
                $code_migration->destination_file_path,
                $code_migration->destination_start,
                $code_migration->destination_start,
            );

            $manipulation = new FileManipulation(
                $code_migration->destination_start + $destination_start_offset,
                $code_migration->destination_start + $destination_start_offset,
                "\n" . substr(
                    $file_provider->getContents($code_migration->source_file_path),
                    $delete_file_manipulation->start,
                    $delete_file_manipulation->end - $delete_file_manipulation->start,
                ) . "\n",
            );

            $code_migration_manipulations[$code_migration->destination_file_path][$manipulation->getKey()]
                = $manipulation;
        }

        return $code_migration_manipulations;
    }

    /**
     * @return array<string, FileManipulation[]>
     * @psalm-external-mutation-free
     */
    public static function getAll(): array
    {
        return self::$file_manipulations;
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function clearCache(): void
    {
        self::$file_manipulations = [];
        self::$code_migrations = [];
    }
}
