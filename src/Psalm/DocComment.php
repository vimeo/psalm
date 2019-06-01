<?php

namespace Psalm;

use Psalm\Exception\DocblockParseException;

class DocComment
{
    /**
     * Parse a docblock comment into its parts.
     *
     * Taken from advanced api docmaker, which was taken from
     * https://github.com/facebook/libphutil/blob/master/src/parser/docblock/PhutilDocblockParser.php
     *
     * @param  string|\PhpParser\Comment\Doc  $docblock
     * @param  bool    $preserve_format
     *
     * @return array Array of the main comment and specials
     * @psalm-return array{description:string, specials:array<string, array<int, string>>}
     * @psalm-suppress PossiblyUnusedParam
     */
    /**
     * Parse a docblock comment into its parts.
     *
     * Taken from advanced api docmaker, which was taken from
     * https://github.com/facebook/libphutil/blob/master/src/parser/docblock/PhutilDocblockParser.php
     *
     * @param  string|\PhpParser\Comment\Doc  $docblock
     * @param  bool    $preserve_format
     *
     * @return array Array of the main comment and specials
     * @psalm-return array{description:string, specials:array<string, array<int, string>>}
     * @psalm-suppress PossiblyUnusedParam
     */
    public static function parse($docblock, ?int $line_number = null)
    {
        if (!is_string($docblock)) {
            $docblock = $docblock->getText();
        }

        // Strip off comments.
        $docblock = trim($docblock);

        $docblock = preg_replace('@^/\*\*@', '', $docblock, -1, $count);

        $start_offset = 3;

        $docblock = preg_replace('@\*/$@', '', $docblock);

        // Normalize multi-line @specials.
        $lines = explode("\n", $docblock);

        $char_map = [];
        $cursor = 0;
        $line_offset = 0;

        foreach ($lines as $k => $line) {
            $trimmed_line = preg_replace('@^[ \t]*\*@', '', $line);
            $start_offset += strlen($line) - strlen($trimmed_line);

            $lines[$k] = $trimmed_line;

            while ($cursor < $line_offset + strlen($trimmed_line)) {
                $char_map[$cursor] = $cursor + $start_offset;
                ++$cursor;
            }

            $char_map[$cursor] = $cursor + $start_offset;
            ++$cursor;

            $line_offset += strlen($trimmed_line) + 1;
        }

        $docblock = implode("\n", $lines);

        $special = [];

        $last = false;
        foreach ($lines as $k => $line) {
            if (preg_match('/^\s?@\w/i', $line)) {
                $last = $k;
            } elseif (preg_match('/^\s*$/', $line)) {
                $last = false;
            } elseif ($last !== false) {
                $old_last_line = $lines[$last];
                $lines[$last] = $old_last_line . "\n" . $line;

                unset($lines[$k]);
            }
        }

        $line_offset = 0;

        foreach ($lines as $line) {
            if (preg_match('/^\s?@([\w\-:]+)[\t ]*(.*)$/sm', $line, $matches, PREG_OFFSET_CAPTURE)) {
                /** @var array<int, array{string, int}> $matches */
                list($full_match_info, $type_info, $data_info) = $matches;

                list($full_match) = $full_match_info;
                list($type) = $type_info;
                list($data, $data_offset) = $data_info;

                $docblock = str_replace($full_match, '', $docblock);

                if (empty($special[$type])) {
                    $special[$type] = [];
                }

                $data_offset += $line_offset;

                $special[$type][$char_map[$data_offset]] = $data;
            }

            $line_offset += strlen($line) + 1;
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
                    [
                        'return', 'param', 'template', 'var', 'type',
                        'template-covariant', 'property', 'method',
                        'assert', 'assert-if-true', 'assert-if-false', 'suppress',
                        'ignore-nullable-return', 'override-property-visibility',
                        'override-method-visibility', 'seal-properties', 'seal-methods',
                        'generator-return', 'ignore-falsable-return', 'variadic',
                        'ignore-variable-method', 'ignore-variable-property', 'internal',
                    ]
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
        $docblock = preg_replace('@\*/$@', '', $docblock);

        // Normalize multi-line @specials.
        $lines = explode("\n", $docblock);

        $special = [];

        $last = false;
        foreach ($lines as $k => $line) {
            if (preg_match('/^[ \t]*\*?\s?@\w/i', $line)) {
                $last = $k;
            } elseif (preg_match('/^\s*$/', $line)) {
                $last = false;
            } elseif ($last !== false) {
                $old_last_line = $lines[$last];
                $lines[$last] = $old_last_line . "\n" . $line;

                unset($lines[$k]);
            }
        }

        $line_offset = 0;

        foreach ($lines as $line) {
            if (preg_match('/^[ \t]*\*?\s?@([\w\-:]+)[\t ]*(.*)$/sm', $line, $matches, PREG_OFFSET_CAPTURE)) {
                /** @var array<int, array{string, int}> $matches */
                list($full_match_info, $type_info, $data_info) = $matches;

                list($full_match) = $full_match_info;
                list($type) = $type_info;
                list($data, $data_offset) = $data_info;

                $docblock = str_replace($full_match, '', $docblock);

                if (empty($special[$type])) {
                    $special[$type] = [];
                }

                $data_offset += $line_offset;

                $special[$type][$data_offset + 3] = $data;
            }

            $line_offset += strlen($line) + 1;
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
                    [
                        'return', 'param', 'template', 'var', 'type',
                        'template-covariant', 'property', 'method',
                        'assert', 'assert-if-true', 'assert-if-false', 'suppress',
                        'ignore-nullable-return', 'override-property-visibility',
                        'override-method-visibility', 'seal-properties', 'seal-methods',
                        'generator-return', 'ignore-falsable-return', 'variadic',
                        'ignore-variable-method', 'ignore-variable-property', 'internal',
                    ]
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
                if ($last_type !== null && $last_type !== 'psalm-return') {
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
}
