<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use function count;
use function in_array;
use function preg_match_all;
use function preg_replace;
use function str_contains;
use function stripos;
use function strlen;
use function strtolower;
use function substr;

use const PREG_OFFSET_CAPTURE;

/**
 * Finds the `array`, `associative-array` and `list` types of a docblock with an `empty` type parameter, e.g.
 * `array<empty, empty>` or `list<empty>`: only the empty array is of them, which `array{}` says.
 *
 * Assertion tags are left out: nested in an assertion, `empty` is meant as the values `empty()` is true for
 * (Webmozart's `allIsEmpty()`).
 *
 * @internal
 */
final class LegacyEmptyArrayFinder
{
    /**
     * @return list<array{int, int}> the start and end offsets of each type in $docblock
     * @psalm-pure
     */
    public static function find(string $docblock): array
    {
        if (stripos($docblock, 'empty') === false) {
            return [];
        }

        preg_match_all(
            '/(?<![\w\\\\$-])(?:array|associative-array|list)\s*</i',
            $docblock,
            $matches,
            PREG_OFFSET_CAPTURE,
        );

        $found = [];
        $previous_end = 0;

        foreach ($matches[0] as [$opening, $start]) {
            if ($start < $previous_end) {
                continue;
            }

            $end = self::findEmptyParamGenericEnd($docblock, $start + strlen($opening));

            if ($end === null || self::isOutsideTypeTag($docblock, $start)) {
                continue;
            }

            $found[] = [$start, $end];
            $previous_end = $end;
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
                for ($i++; $i < $length && $docblock[$i] !== $char; $i++) {
                    if ($docblock[$i] === '\\') {
                        $i++;
                    }
                }
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
                $param = substr($docblock, $param_start, $i - $param_start);
                $param = strtolower((string) preg_replace('/[\s*]+/', '', $param));
                $has_empty_param = $has_empty_param || in_array($param, ['empty', '\empty'], true);

                if ($char === '>') {
                    return $has_empty_param ? $i + 1 : null;
                }

                $param_start = $i + 1;
            }
        }

        return null;
    }

    /**
     * Whether the type at $offset is in the docblock's description or in an assertion tag
     *
     * @psalm-pure
     */
    private static function isOutsideTypeTag(string $docblock, int $offset): bool
    {
        if (!preg_match_all('/^[ \t]*(?:\/\*\*|\*)?[ \t]*@([\w\\\\:-]+)/m', substr($docblock, 0, $offset), $tags)) {
            return true;
        }

        return str_contains(strtolower($tags[1][count($tags[1]) - 1]), 'assert');
    }
}
