<?php

namespace Psalm\Internal\Type;

use Psalm\Aliases;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Type\TypeAlias\InlineTypeAlias;
use Psalm\Type;

use function array_splice;
use function array_unshift;
use function count;
use function in_array;
use function is_numeric;
use function preg_match;
use function preg_replace;
use function str_split;
use function strlen;
use function strpos;
use function strtolower;

/**
 * @internal
 */
class TypeTokenizer
{
    /**
     * @var array<string, bool>
     */
    public const PSALM_RESERVED_WORDS = [
        'int' => true,
        'string' => true,
        'float' => true,
        'bool' => true,
        'false' => true,
        'true' => true,
        'object' => true,
        'empty' => true,
        'callable' => true,
        'array' => true,
        'non-empty-array' => true,
        'non-empty-string' => true,
        'non-falsy-string' => true,
        'iterable' => true,
        'null' => true,
        'mixed' => true,
        'numeric-string' => true,
        'class-string' => true,
        'interface-string' => true,
        'enum-string' => true,
        'trait-string' => true,
        'callable-string' => true,
        'callable-array' => true,
        'callable-object' => true,
        'stringable-object' => true,
        'pure-callable' => true,
        'pure-Closure' => true,
        'literal-string' => true,
        'non-empty-literal-string' => true,
        'lowercase-string' => true,
        'non-empty-lowercase-string' => true,
        'positive-int' => true,
        'literal-int' => true,
        'boolean' => true,
        'integer' => true,
        'double' => true,
        'real' => true,
        'resource' => true,
        'void' => true,
        'self' => true,
        'static' => true,
        'scalar' => true,
        'numeric' => true,
        'no-return' => true,
        'never-return' => true,
        'never-returns' => true,
        'never' => true,
        'array-key' => true,
        'key-of' => true,
        'value-of' => true,
        'properties-of' => true,
        'public-properties-of' => true,
        'protected-properties-of' => true,
        'private-properties-of' => true,
        'non-empty-countable' => true,
        'list' => true,
        'non-empty-list' => true,
        'class-string-map' => true,
        'open-resource' => true,
        'closed-resource' => true,
        'associative-array' => true,
        'arraylike-object' => true,
        'int-mask' => true,
        'int-mask-of' => true,
    ];

    /**
     * @var array<string, list<array{0: string, 1: int}>>
     */
    private static $memoized_tokens = [];

    /**
     * Tokenises a type string into an array of tuples where the first element
     * contains the string token and the second element contains its offset,
     *
     * @return list<array{string, int}>
     *
     * @psalm-suppress PossiblyUndefinedIntArrayOffset
     */
    public static function tokenize(string $string_type, bool $ignore_space = true): array
    {
        $type_tokens = [['', 0]];
        $was_char = false;
        $quote_char = null;
        $escaped = false;

        if (isset(self::$memoized_tokens[$string_type])) {
            return self::$memoized_tokens[$string_type];
        }

        // index of last type token
        $rtc = 0;

        $chars = str_split($string_type);
        $was_space = false;

        for ($i = 0, $c = count($chars); $i < $c; ++$i) {
            $char = $chars[$i];

            if (!$quote_char && $char === ' ' && $ignore_space) {
                $was_space = true;
                continue;
            }

            if ($was_space
                && ($char === '$'
                    || ($char === '.'
                        && ($chars[$i + 1] ?? null) === '.'
                        && ($chars[$i + 2] ?? null) === '.'
                        && ($chars[$i + 3] ?? null) === '$'))
            ) {
                $type_tokens[++$rtc] = [' ', $i - 1];
                $type_tokens[++$rtc] = ['', $i];
            } elseif ($was_space
                && ($char === 'a' || $char === 'i')
                && ($chars[$i + 1] ?? null) === 's'
                && ($chars[$i + 2] ?? null) === ' '
            ) {
                $type_tokens[++$rtc] = [$char . 's', $i - 1];
                $type_tokens[++$rtc] = ['', ++$i];
                $was_char = false;
                continue;
            } elseif ($was_char) {
                $type_tokens[++$rtc] = ['', $i];
            }

            if ($quote_char) {
                if ($char === $quote_char && $i > 0 && !$escaped) {
                    $quote_char = null;

                    $type_tokens[$rtc][0] .= $char;
                    $was_char = true;

                    continue;
                }

                $was_char = false;

                if ($char === '\\'
                    && !$escaped
                    && $i < $c - 1
                    && ($chars[$i + 1] === $quote_char || $chars[$i + 1] === '\\')
                ) {
                    $escaped = true;
                    continue;
                }

                $escaped = false;

                $type_tokens[$rtc][0] .= $char;

                continue;
            }

            if ($char === '"' || $char === '\'') {
                if ($type_tokens[$rtc][0] === '') {
                    $type_tokens[$rtc] = [$char, $i];
                } else {
                    $type_tokens[++$rtc] = [$char, $i];
                }

                $quote_char = $char;

                $was_char = false;
                $was_space = false;

                continue;
            }

            if ($char === '<'
                || $char === '>'
                || $char === '|'
                || $char === '?'
                || $char === ','
                || $char === '{'
                || $char === '}'
                || $char === '['
                || $char === ']'
                || $char === '('
                || $char === ')'
                || $char === ' '
                || $char === '&'
                || $char === '='
            ) {
                if ($char === '('
                    && $type_tokens[$rtc][0] === 'func_num_args'
                    && isset($chars[$i + 1])
                    && $chars[$i + 1] === ')'
                ) {
                    $type_tokens[$rtc][0] = 'func_num_args()';
                    ++$i;

                    continue;
                }

                if ($type_tokens[$rtc][0] === '') {
                    $type_tokens[$rtc] = [$char, $i];
                } else {
                    $type_tokens[++$rtc] = [$char, $i];
                }

                $was_char = true;
                $was_space = false;

                continue;
            }

            if ($char === ':') {
                if ($i + 1 < $c && $chars[$i + 1] === ':') {
                    if ($type_tokens[$rtc][0] === '') {
                        $type_tokens[$rtc] = ['::', $i];
                    } else {
                        $type_tokens[++$rtc] = ['::', $i];
                    }

                    $was_char = true;
                    $was_space = false;

                    ++$i;

                    continue;
                }

                if ($type_tokens[$rtc][0] === '') {
                    $type_tokens[$rtc] = [':', $i];
                } else {
                    $type_tokens[++$rtc] = [':', $i];
                }

                $was_char = true;
                $was_space = false;

                continue;
            }

            if ($char === '.') {
                if ($i + 1 < $c
                    && is_numeric($chars[$i + 1])
                    && $i > 0
                    && is_numeric($chars[$i - 1])
                ) {
                    $type_tokens[$rtc][0] .= $char;
                    $was_char = false;
                    $was_space = false;

                    continue;
                }

                if ($i + 2 > $c || $chars[$i + 1] !== '.' || $chars[$i + 2] !== '.') {
                    throw new TypeParseTreeException('Unexpected token ' . $char);
                }

                if ($type_tokens[$rtc][0] === '') {
                    $type_tokens[$rtc] = ['...', $i];
                } else {
                    $type_tokens[++$rtc] = ['...', $i];
                }

                $was_char = true;
                $was_space = false;

                $i += 2;

                continue;
            }

            $type_tokens[$rtc][0] .= $char;
            $was_char = false;
            $was_space = false;
        }

        /** @var list<array{0: string, 1: int}> $type_tokens */
        self::$memoized_tokens[$string_type] = $type_tokens;

        return $type_tokens;
    }

    /**
     * @psalm-pure
     */
    public static function fixScalarTerms(
        string $type_string,
        ?int $analysis_php_version_id = null
    ): string {
        $type_string_lc = strtolower($type_string);

        switch ($type_string_lc) {
            case 'int':
            case 'void':
            case 'float':
            case 'string':
            case 'bool':
            case 'callable':
            case 'iterable':
            case 'array':
            case 'object':
            case 'true':
            case 'false':
            case 'null':
            case 'mixed':
                return $type_string_lc;
        }

        switch ($type_string) {
            case 'boolean':
                return $analysis_php_version_id !== null ? $type_string : 'bool';

            case 'integer':
                return $analysis_php_version_id !== null ? $type_string : 'int';

            case 'double':
            case 'real':
                return $analysis_php_version_id !== null ? $type_string : 'float';
        }

        return $type_string;
    }

    /**
     * @param  array<string, mixed>|null       $template_type_map
     * @param  array<string, TypeAlias>|null   $type_aliases
     *
     * @return list<array{0: string, 1: int, 2?: string}>
     */
    public static function getFullyQualifiedTokens(
        string $string_type,
        Aliases $aliases,
        ?array $template_type_map = null,
        ?array $type_aliases = null,
        ?string $self_fqcln = null,
        ?string $parent_fqcln = null,
        bool $allow_assertions = false
    ): array {
        $type_tokens = self::tokenize($string_type);

        for ($i = 0, $l = count($type_tokens); $i < $l; ++$i) {
            $string_type_token = $type_tokens[$i];

            if (in_array(
                $string_type_token[0],
                [
                    '<', '>', '|', '?', ',', '{', '}', ':', '::', '[', ']', '(', ')', '&', '=', '...', 'as', 'is',
                ],
                true
            )) {
                continue;
            }

            if ($string_type_token[0][0] === '\\'
                && strlen($string_type_token[0]) === 1
            ) {
                throw new TypeParseTreeException("Backslash \"\\\" has to be part of class name.");
            }

            if ($string_type_token[0][0] === '"'
                || $string_type_token[0][0] === '\''
                || preg_match('/[0-9]/', $string_type_token[0][0])
            ) {
                continue;
            }

            if ($string_type_token[0][0] === '-' && is_numeric($string_type_token[0])) {
                continue;
            }

            if (isset($type_tokens[$i + 1])
                && $type_tokens[$i + 1][0] === ':'
                && isset($type_tokens[$i - 1])
                && ($type_tokens[$i - 1][0] === '{' || $type_tokens[$i - 1][0] === ',')
            ) {
                continue;
            }

            if ($i > 0 && $type_tokens[$i - 1][0] === '::') {
                continue;
            }

            if (strpos($string_type_token[0], '$')) {
                $string_type_token[0] = preg_replace('/(.+)\$.*/', '$1', $string_type_token[0]);
            }

            $fixed_token = !isset($type_tokens[$i + 1]) || $type_tokens[$i + 1][0] !== '('
                ? self::fixScalarTerms($string_type_token[0])
                : $string_type_token[0];

            $type_tokens[$i][0] = $fixed_token;
            $string_type_token[0] = $fixed_token;

            if ($string_type_token[0] === 'self' && $self_fqcln) {
                $type_tokens[$i][0] = $self_fqcln;
                continue;
            }

            if ($string_type_token[0] === 'parent' && $parent_fqcln) {
                $type_tokens[$i][0] = $parent_fqcln;
                continue;
            }

            if (isset(self::PSALM_RESERVED_WORDS[$string_type_token[0]])) {
                continue;
            }

            if (isset($template_type_map[$string_type_token[0]])) {
                continue;
            }

            if ($i > 1
                && ($type_tokens[$i - 2][0] === 'class-string-map')
                && ($type_tokens[$i - 1][0] === '<')
            ) {
                $template_type_map[$string_type_token[0]] = true;
                continue;
            }

            if (isset($type_tokens[$i + 1])
                && isset($type_tokens[$i - 1])
                && ($type_tokens[$i - 1][0] === '{' || $type_tokens[$i - 1][0] === ',')
            ) {
                $next_char = $type_tokens[$i + 1][0];

                if ($next_char === ':') {
                    continue;
                }

                if ($next_char === '?' && isset($type_tokens[$i + 2]) && $type_tokens[$i + 2][0] === ':') {
                    continue;
                }
            }

            if ($string_type_token[0][0] === '$' || $string_type_token[0][0] === ' ') {
                continue;
            }

            if (isset($type_tokens[$i + 1]) && $type_tokens[$i + 1][0] === '(') {
                continue;
            }

            if ($allow_assertions && $string_type_token[0] === 'falsy') {
                $type_tokens[$i][0] = 'false-y';
                continue;
            }

            if ($string_type_token[0] === 'func_num_args()'
                || $string_type_token[0] === 'PHP_MAJOR_VERSION'
                || $string_type_token[0] === 'PHP_VERSION_ID'
            ) {
                continue;
            }

            $type_tokens[$i][2] = $string_type_token[0];

            if (isset($type_aliases[$string_type_token[0]])) {
                $type_alias = $type_aliases[$string_type_token[0]];

                if ($type_alias instanceof InlineTypeAlias) {
                    $replacement_tokens = $type_alias->replacement_tokens;

                    array_unshift($replacement_tokens, ['(', $i]);
                    $replacement_tokens[] = [')', $i];

                    $diff = count($replacement_tokens) - 1;

                    array_splice($type_tokens, $i, 1, $replacement_tokens);

                    $i += $diff;
                    $l += $diff;
                }
            } else {
                $type_tokens[$i][0] = Type::getFQCLNFromString(
                    $string_type_token[0],
                    $aliases
                );
            }
        }

        /** @var list<array{0: string, 1: int, 2?: string}> */
        return $type_tokens;
    }

    public static function clearCache(): void
    {
        self::$memoized_tokens = [];
    }
}
