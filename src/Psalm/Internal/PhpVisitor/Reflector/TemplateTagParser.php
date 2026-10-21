<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use Psalm\Exception\DocblockParseException;
use Psalm\Internal\Analyzer\CommentAnalyzer;

use function array_shift;
use function array_slice;
use function count;
use function implode;
use function in_array;
use function strpos;
use function strtolower;
use function substr;
use function trim;

/**
 * @internal
 * @psalm-pure
 */
final class TemplateTagParser
{
    /**
     * Splits a tokenized `@template` tag into its name, its remaining modifier/bound
     * tokens, and its default type string (from `= DefaultType`), if any.
     *
     * `$tokens` is the whitespace-tokenized `@template` tag, name included, e.g.
     * `['T', 'of', 'Foo', '=', 'Bar']` for `@template T of Foo = Bar`. The `=` is the token
     * right after the bound (or after the name, when there is none): one further on in a
     * description is not a default. `@template T=Bar` (no spaces around the `=`) is handled
     * too: whitespace tokenizing leaves the name and default glued into a single token.
     *
     * @param list<string> $tokens
     * @return array{0: string, 1: list<string>, 2: ?string} The template name, the
     *     remaining tokens with any `= Default` suffix removed, and the default type
     *     string (or null if there was no default).
     * @psalm-pure
     */
    public static function splitDefault(array $tokens): array
    {
        $name = array_shift($tokens) ?? '';

        $glued_eq_pos = strpos($name, '=');
        if ($glued_eq_pos !== false && $glued_eq_pos > 0) {
            $default = trim(substr($name, $glued_eq_pos + 1) . ' ' . implode(' ', $tokens));

            return [substr($name, 0, $glued_eq_pos), [], $default !== '' ? $default : null];
        }

        // what follows the bound, token for token: the bound itself is normalized by splitDocLine()
        $after_bound = $tokens;

        if (count($tokens) > 1 && in_array(strtolower($tokens[0]), ['as', 'super', 'of'], true)) {
            try {
                $after_bound = array_slice(CommentAnalyzer::splitDocLine(implode(' ', array_slice($tokens, 1))), 1);
            } catch (DocblockParseException) {
                // reported where the bound is parsed
                return [$name, $tokens, null];
            }
        }

        if (($after_bound[0] ?? null) !== '=') {
            return [$name, $tokens, null];
        }

        $default = implode(' ', array_slice($after_bound, 1));

        return [
            $name,
            array_slice($tokens, 0, count($tokens) - count($after_bound)),
            $default !== '' ? $default : null,
        ];
    }
}
