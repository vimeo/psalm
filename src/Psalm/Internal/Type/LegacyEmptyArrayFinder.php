<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use function array_reverse;
use function ctype_space;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function str_contains;
use function strlen;
use function substr;
use function substr_replace;

use const PREG_OFFSET_CAPTURE;
use const PREG_SET_ORDER;

/**
 * Finds the `array`, `associative-array` and `list` types of a docblock with an `empty` type parameter, e.g.
 * `array<empty, empty>` or `list<empty>`: only the empty array is of them, which `array{}` says.
 *
 * Only the types of the tags that declare one are searched. Assertion tags are left out: nested in an assertion,
 * `empty` is meant as the values `empty()` is true for (Webmozart's `allIsEmpty()`).
 *
 * @internal
 * @psalm-immutable
 */
final class LegacyEmptyArrayFinder
{
    private const TYPE_TAG_REGEX = '/^[ \t]*(?:\/\*\*|\*)?[ \t]*@(?:psalm-|phpstan-)?'
        . '(var|param|return|property|property-read|property-write|method|param-out|self-out|this-out|if-this-is'
        . '|extends|implements|use|template-extends|template-implements|template-use|mixin'
        . '|require-extends|require-implements|type|template|template-covariant|template-contravariant)'
        . '(?![\w-])/m';

    /**
     * @return list<array{int, int}> the start and end offsets of each type in $docblock
     * @psalm-pure
     */
    public static function find(string $docblock): array
    {
        if (!str_contains($docblock, 'empty')
            || (int) preg_match_all(self::TYPE_TAG_REGEX, $docblock, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === 0
        ) {
            return [];
        }

        $found = [];

        foreach ($tags as [[$tag_text, $tag_start], [$tag_name]]) {
            $offset = self::skipSpaces($docblock, $tag_start + strlen($tag_text));

            if ($tag_name === 'method') {
                $offset += strlen(self::matchAt('/static\s+(?=\S)/A', $docblock, $offset) ?? '');
            } elseif ($tag_name === 'type' || str_contains($tag_name, 'template')) {
                // the type follows the name of the alias or template, and `=`, `of`, `as` or `super`
                $name = self::matchAt('/[\w\\\\]+\s*(?:(?:=|of\s|as\s|super\s)\s*)?/A', $docblock, $offset);

                if ($name === null) {
                    continue;
                }

                $offset += strlen($name);
            }

            $found = [...$found, ...self::findInType($docblock, $offset)];
        }

        return $found;
    }

    /**
     * $docblock with array{} in place of each type find() finds
     *
     * @psalm-pure
     */
    public static function replace(string $docblock): string
    {
        foreach (array_reverse(self::find($docblock)) as [$start, $end]) {
            $docblock = substr_replace($docblock, 'array{}', $start, $end - $start);
        }

        return $docblock;
    }

    /**
     * Searches the type starting at $offset, which ends at the first space outside brackets that doesn't separate
     * the members of a union or intersection or a callable's return type
     *
     * @return list<array{int, int}>
     * @psalm-pure
     */
    private static function findInType(string $docblock, int $offset): array
    {
        $found = [];
        $depth = 0;
        $length = strlen($docblock);

        for ($i = $offset; $i < $length; $i++) {
            $char = $docblock[$i];

            if ($char === '\'' || $char === '"') {
                $i = self::skipString($docblock, $i);
            } elseif ($char === '<' || $char === '{' || $char === '(' || $char === '[') {
                $depth++;
            } elseif ($char === '>' || $char === '}' || $char === ')' || $char === ']') {
                if ($depth === 0) {
                    return $found;
                }

                $depth--;
            } elseif ($depth === 0 && $char === '*' && ($docblock[$i + 1] ?? '') === '/') {
                return $found;
            } elseif ($depth === 0 && ctype_space($char)) {
                $next = self::skipSpaces($docblock, $i);
                $before = $docblock[$i - 1];
                $after = $docblock[$next] ?? '';

                if ($before !== '|' && $before !== '&' && $before !== ':' && $after !== '|' && $after !== '&') {
                    return $found;
                }

                $i = $next - 1;
            } elseif (($i === $offset || !preg_match('/[\w\\\\$-]/', $docblock[$i - 1]))
                && ($opening = self::matchAt('/(?:array|associative-array|list)\s*</iA', $docblock, $i)) !== null
            ) {
                $end = self::findEmptyParamGenericEnd($docblock, $i + strlen($opening));

                if ($end !== null) {
                    $found[] = [$i, $end];
                    $i = $end - 1;
                }
            }
        }

        return $found;
    }

    /**
     * @return ?int the offset after the closing `>`, if one of the parameters is `empty`
     * @psalm-pure
     */
    private static function findEmptyParamGenericEnd(string $docblock, int $offset): ?int
    {
        $depth = 0;
        $param_start = $offset;
        $has_empty_param = false;
        $length = strlen($docblock);

        for ($i = $offset; $i < $length; $i++) {
            $char = $docblock[$i];

            if ($char === '\'' || $char === '"') {
                $i = self::skipString($docblock, $i);
            } elseif ($char === '<' || $char === '{' || $char === '(' || $char === '[') {
                $depth++;
            } elseif ($char === '>' || $char === '}' || $char === ')' || $char === ']' || $char === ',') {
                if ($depth > 0) {
                    if ($char !== ',') {
                        $depth--;
                    }

                    continue;
                }

                if ($char !== ',' && $char !== '>') {
                    return null;
                }

                // a parameter can span lines, each starting with the docblock's `*`
                $param = (string) preg_replace('/[\s*]+/', '', substr($docblock, $param_start, $i - $param_start));
                $has_empty_param = $has_empty_param || $param === 'empty' || $param === '\empty';

                if ($char === '>') {
                    return $has_empty_param ? $i + 1 : null;
                }

                $param_start = $i + 1;
            }
        }

        return null;
    }

    /**
     * @return int the offset of the quote closing the string opened at $offset
     * @psalm-pure
     */
    private static function skipString(string $docblock, int $offset): int
    {
        $quote = $docblock[$offset];
        $length = strlen($docblock);

        for ($i = $offset + 1; $i < $length && $docblock[$i] !== $quote; $i++) {
            if ($docblock[$i] === '\\') {
                $i++;
            }
        }

        return $i;
    }

    /**
     * @param non-empty-string $pattern
     * @return ?string the text $pattern matches at $offset
     * @psalm-pure
     */
    private static function matchAt(string $pattern, string $docblock, int $offset): ?string
    {
        preg_match($pattern, $docblock, $matches, 0, $offset);

        return $matches[0] ?? null;
    }

    /**
     * @psalm-pure
     */
    private static function skipSpaces(string $docblock, int $offset): int
    {
        $length = strlen($docblock);

        while ($offset < $length && ctype_space($docblock[$offset])) {
            $offset++;
        }

        return $offset;
    }
}
