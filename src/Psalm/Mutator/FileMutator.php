<?php
namespace Psalm\Mutator;

use Psalm\Checker\CommentChecker;

class FileMutator
{
    /**
     * A list of return types, keyed by file
     *
     * @var array<string, array<int, array<string>>>
     */
    protected static $docblock_return_types = [];

    /**
     * Adds a docblock to the given file
     *
     * @param   string      $file_path
     * @param   int         $line_number
     * @param   string      $docblock
     * @param   string      $new_type
     * @param   string      $phpdoc_type
     * @return  void
     */
    public static function addDocblockReturnType($file_path, $line_number, $docblock, $new_type, $phpdoc_type)
    {
        $new_type = str_replace(['<mixed, mixed>', '<empty, empty>'], '', $new_type);

        self::$docblock_return_types[$file_path][$line_number] = [$docblock, $new_type, $phpdoc_type];
    }

    /**
     * @param  string $file_path
     * @return void
     */
    public static function updateDocblocks($file_path)
    {
        if (!isset(self::$docblock_return_types[$file_path])) {
            return;
        }

        $line_upset = 0;

        $file_lines = explode(PHP_EOL, (string)file_get_contents($file_path));

        $file_docblock_updates = self::$docblock_return_types[$file_path];

        foreach ($file_docblock_updates as $line_number => $type) {
            self::updateDocblock($file_lines, $line_number, $line_upset, $type[0], $type[1], $type[2]);
        }

        file_put_contents($file_path, implode(PHP_EOL, $file_lines));

        echo 'Added/updated ' . count($file_docblock_updates) . ' docblocks in ' . $file_path . PHP_EOL;
    }

    /**
     * @param  array<int, string>   $file_lines
     * @param  int                  $line_number
     * @param  int                  $line_upset
     * @param  string               $existing_docblock
     * @param  string               $type
     * @param  string               $phpdoc_type
     * @return void
     */
    public static function updateDocblock(
        array &$file_lines,
        $line_number,
        &$line_upset,
        $existing_docblock,
        $type,
        $phpdoc_type
    ) {
        $line_number += $line_upset;
        $function_line = $file_lines[$line_number - 1];
        $left_padding = str_replace(ltrim($function_line), '', $function_line);

        $parsed_docblock = [];
        $existing_line_count = $existing_docblock ? substr_count($existing_docblock, PHP_EOL) + 1 : 0;

        if ($existing_docblock) {
            $parsed_docblock = CommentChecker::parseDocComment($existing_docblock);
        } else {
            $parsed_docblock['description'] = '';
        }

        $parsed_docblock['specials']['return'] = [$phpdoc_type];

        if ($type !== $phpdoc_type) {
            $parsed_docblock['specials']['psalm-return'] = [$type];
        }

        $new_docblock_lines = CommentChecker::renderDocComment($parsed_docblock, $left_padding);

        $line_upset += count($new_docblock_lines) - $existing_line_count;

        array_splice($file_lines, $line_number - $existing_line_count - 1, $existing_line_count, $new_docblock_lines);
    }
}
