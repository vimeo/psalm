<?php
namespace Psalm;

use function array_filter;
use function explode;
use function implode;
use function in_array;
use function min;
use function preg_match;
use function preg_match_all;
use const PREG_OFFSET_CAPTURE;
use function preg_replace;
use const PREG_SET_ORDER;
use Psalm\Exception\DocblockParseException;
use function rtrim;
use function str_repeat;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function trim;

class DocComment
{
    private const PSALM_ANNOTATIONS = [
        'return', 'param', 'template', 'var', 'type',
        'template-covariant', 'property', 'property-read', 'property-write', 'method',
        'assert', 'assert-if-true', 'assert-if-false', 'suppress',
        'ignore-nullable-return', 'override-property-visibility',
        'override-method-visibility', 'seal-properties', 'seal-methods',
        'generator-return', 'ignore-falsable-return', 'variadic', 'pure',
        'ignore-variable-method', 'ignore-variable-property', 'internal',
        'taint-sink', 'taint-source', 'assert-untainted', 'scope-this',
        'mutation-free', 'external-mutation-free', 'immutable', 'readonly',
        'remove-taint', 'allow-private-mutation', 'readonly-allow-private-mutation',
        'yield', 'trace',
    ];

    /**
     * @var bool
     */
    private static $shouldAddNewLineBetweenAnnotations = true;

    /**
     * Parse a docblock comment into its parts.
     *
     * Taken from advanced api docmaker, which was taken from
     * https://github.com/facebook/libphutil/blob/master/src/parser/docblock/PhutilDocblockParser.php
     *
     * @param  string  $docblock
     * @param  int     $line_number
     * @param  bool    $preserve_format
     *
     * @return array Array of the main comment and specials
     * @psalm-return array{description:string, specials:array<string, array<int, string>>}
     */
    public static function parse($docblock, $line_number = null, $preserve_format = false)
    {
        // Strip off comments.
        $docblock = trim($docblock);
        $docblock = preg_replace('@^/\*\*@', '', $docblock);
        $docblock = preg_replace('@\*/$@', '', $docblock);
        $docblock = preg_replace('@^[ \t]*\*@m', '', $docblock);

        // Normalize multi-line @specials.
        $lines = explode("\n", $docblock);

        $line_map = [];

        $last = false;
        foreach ($lines as $k => $line) {
            if (preg_match('/^\s?@\w/i', $line)) {
                $last = $k;
            } elseif (preg_match('/^\s*$/', $line)) {
                $last = false;
            } elseif ($last !== false) {
                $old_last_line = $lines[$last];
                $lines[$last] = rtrim($old_last_line)
                    . ($preserve_format || trim($old_last_line) === '@return' ? "\n" . $line : ' ' . trim($line));

                if ($line_number) {
                    $old_line_number = $line_map[$old_last_line];
                    unset($line_map[$old_last_line]);
                    $line_map[$lines[$last]] = $old_line_number;
                }

                unset($lines[$k]);
            }

            if ($line_number) {
                $line_map[$line] = $line_number++;
            }
        }

        $special = [];

        if ($preserve_format) {
            foreach ($lines as $m => $line) {
                if (preg_match('/^\s?@([\w\-:]+)[\t ]*(.*)$/sm', $line, $matches)) {
                    list($full_match, $type, $data) = $matches;

                    $docblock = str_replace($full_match, '', $docblock);

                    if (empty($special[$type])) {
                        $special[$type] = [];
                    }

                    $line_number = $line_map && isset($line_map[$full_match]) ? $line_map[$full_match] : (int)$m;

                    $special[$type][$line_number] = rtrim($data);
                }
            }
        } else {
            $docblock = implode("\n", $lines);

            // Parse @specials.
            if (preg_match_all('/^\s?@([\w\-:]+)[\t ]*([^\n]*)/m', $docblock, $matches, PREG_SET_ORDER)) {
                $docblock = preg_replace('/^\s?@([\w\-:]+)\s*([^\n]*)/m', '', $docblock);
                /** @var string[] $match */
                foreach ($matches as $m => $match) {
                    list($_, $type, $data) = $match;

                    if (empty($special[$type])) {
                        $special[$type] = [];
                    }

                    $line_number = $line_map && isset($line_map[$_]) ? $line_map[$_] : (int)$m;

                    $special[$type][$line_number] = $data;
                }
            }
        }

        $docblock = str_replace("\t", '  ', $docblock);

        // Smush the whole docblock to the left edge.
        $min_indent = 80;
        $indent = 0;
        foreach (array_filter(explode("\n", $docblock)) as $line) {
            for ($ii = 0; $ii < strlen($line); ++$ii) {
                if ($line[$ii] != ' ') {
                    break;
                }
                ++$indent;
            }

            $min_indent = min($indent, $min_indent);
        }

        $docblock = preg_replace('/^' . str_repeat(' ', $min_indent) . '/m', '', $docblock);
        $docblock = rtrim($docblock);

        // Trim any empty lines off the front, but leave the indent level if there
        // is one.
        $docblock = preg_replace('/^\s*\n/', '', $docblock);

        foreach ($special as $special_key => $_) {
            if (substr($special_key, 0, 6) === 'psalm-') {
                $special_key = substr($special_key, 6);

                if (!in_array(
                    $special_key,
                    self::PSALM_ANNOTATIONS,
                    true
                )) {
                    throw new DocblockParseException('Unrecognised annotation @psalm-' . $special_key);
                }
            }
        }

        return [
            'description' => $docblock,
            'specials' => $special,
        ];
    }

    /**
     * Parse a docblock comment into its parts.
     *
     * Taken from advanced api docmaker, which was taken from
     * https://github.com/facebook/libphutil/blob/master/src/parser/docblock/PhutilDocblockParser.php
     *
     * @param  \PhpParser\Comment\Doc  $docblock
     * @param  bool    $preserve_format
     *
     * @return array Array of the main comment and specials
     * @psalm-return array{description:string, specials:array<string, array<int, string>>}
     */
    public static function parsePreservingLength(\PhpParser\Comment\Doc $docblock)
    {
        $docblock = $docblock->getText();

        // Strip off comments.
        $docblock = trim($docblock);

        $docblock = preg_replace('@^/\*\*@', '', $docblock);
        $docblock = preg_replace('@\*\*?/$@', '', $docblock);

        // Normalize multi-line @specials.
        $lines = explode("\n", $docblock);

        $special = [];

        $last = false;
        foreach ($lines as $k => $line) {
            if (preg_match('/^[ \t]*\*?\s*@\w/i', $line)) {
                $last = $k;
            } elseif (preg_match('/^\s*\r?$/', $line)) {
                $last = false;
            } elseif ($last !== false) {
                $old_last_line = $lines[$last];
                $lines[$last] = $old_last_line . "\n" . $line;

                unset($lines[$k]);
            }
        }

        $line_offset = 0;

        foreach ($lines as $line) {
            $original_line_length = strlen($line);

            $line = str_replace("\r", '', $line);

            if (preg_match('/^[ \t]*\*?\s*@([\w\-:]+)[\t ]*(.*)$/sm', $line, $matches, PREG_OFFSET_CAPTURE)) {
                /** @var array<int, array{string, int}> $matches */
                list($full_match_info, $type_info, $data_info) = $matches;

                list($full_match) = $full_match_info;
                list($type) = $type_info;
                list($data, $data_offset) = $data_info;

                if (strpos($data, '*')) {
                    $data = rtrim(preg_replace('/^[ \t]*\*\s*$/m', '', $data));
                }

                $docblock = str_replace($full_match, '', $docblock);

                if (empty($special[$type])) {
                    $special[$type] = [];
                }

                $data_offset += $line_offset;

                $special[$type][$data_offset + 3] = $data;
            }

            $line_offset += $original_line_length + 1;
        }

        $docblock = str_replace("\t", '  ', $docblock);

        // Smush the whole docblock to the left edge.
        $min_indent = 80;
        $indent = 0;
        foreach (array_filter(explode("\n", $docblock)) as $line) {
            for ($ii = 0; $ii < strlen($line); ++$ii) {
                if ($line[$ii] != ' ') {
                    break;
                }
                ++$indent;
            }

            $min_indent = min($indent, $min_indent);
        }

        $docblock = preg_replace('/^' . str_repeat(' ', $min_indent) . '/m', '', $docblock);
        $docblock = rtrim($docblock);

        // Trim any empty lines off the front, but leave the indent level if there
        // is one.
        $docblock = preg_replace('/^\s*\n/', '', $docblock);

        foreach ($special as $special_key => $_) {
            if (substr($special_key, 0, 6) === 'psalm-') {
                $special_key = substr($special_key, 6);

                if (!in_array(
                    $special_key,
                    self::PSALM_ANNOTATIONS,
                    true
                )) {
                    throw new DocblockParseException('Unrecognised annotation @psalm-' . $special_key);
                }
            }
        }

        return [
            'description' => $docblock,
            'specials' => $special,
        ];
    }

    /**
     * @param  array{description:string,specials:array<string,array<string>>} $parsed_doc_comment
     * @param  string                                                         $left_padding
     *
     * @return string
     */
    public static function render(array $parsed_doc_comment, $left_padding)
    {
        $doc_comment_text = '/**' . "\n";

        $description_lines = null;

        $trimmed_description = trim($parsed_doc_comment['description']);

        if (!empty($trimmed_description)) {
            $description_lines = explode("\n", $parsed_doc_comment['description']);

            foreach ($description_lines as $line) {
                $doc_comment_text .= $left_padding . ' *' . (trim($line) ? ' ' . $line : '') . "\n";
            }
        }

        if ($description_lines && $parsed_doc_comment['specials']) {
            $doc_comment_text .= $left_padding . ' *' . "\n";
        }

        if ($parsed_doc_comment['specials']) {
            $last_type = null;

            foreach ($parsed_doc_comment['specials'] as $type => $lines) {
                if ($last_type !== null
                    && $last_type !== 'psalm-return'
                    && static::shouldAddNewLineBetweenAnnotations()
                ) {
                    $doc_comment_text .= $left_padding . ' *' . "\n";
                }

                foreach ($lines as $line) {
                    $doc_comment_text .= $left_padding . ' * @' . $type . ' '
                        . str_replace("\n", "\n" . $left_padding . ' *', $line) . "\n";
                }

                $last_type = $type;
            }
        }

        $doc_comment_text .= $left_padding . ' */' . "\n" . $left_padding;

        return $doc_comment_text;
    }

    private static function shouldAddNewLineBetweenAnnotations(): bool
    {
        return static::$shouldAddNewLineBetweenAnnotations;
    }

    /**
     * Sets whether a new line should be added between the annotations or not.
     *
     * @param bool $should
     */
    public static function addNewLineBetweenAnnotations(bool $should = true): void
    {
        static::$shouldAddNewLineBetweenAnnotations = $should;
    }
}
