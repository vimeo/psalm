<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use function array_search;
use function array_shift;
use function array_slice;
use function array_unshift;
use function implode;
use function strpos;
use function substr;

/**
 * @internal
 */
final class TemplateTagParser
{
    /**
     * Splits a tokenized `@template` tag into its name, its remaining modifier/bound
     * tokens, and its default type string (from a trailing `= DefaultType`), if any.
     *
     * `$tokens` is the whitespace-tokenized `@template` tag, name included, e.g.
     * `['T', 'of', 'Foo', '=', 'Bar']` for `@template T of Foo = Bar`. It also handles
     * `@template T=Bar` (no spaces around the `=`), where whitespace tokenizing leaves
     * the name and default glued into a single `T=Bar` token.
     *
     * @param list<string> $tokens
     * @return array{0: string, 1: list<string>, 2: ?string} The template name, the
     *     remaining tokens with any `= Default` suffix removed, and the default type
     *     string (or null if there was no default).
     */
    public static function splitDefault(array $tokens): array
    {
        $name = array_shift($tokens) ?? '';

        $glued_eq_pos = strpos($name, '=');
        if ($glued_eq_pos !== false && $glued_eq_pos > 0) {
            // `@template T=Bar`: no space around `=`, so the name and the default type
            // end up glued into a single token; there is no room for a bound here
            $glued_default = substr($name, $glued_eq_pos + 1);
            $name = substr($name, 0, $glued_eq_pos);

            if ($glued_default !== '') {
                array_unshift($tokens, $glued_default);
            }

            return [$name, [], implode(' ', $tokens) ?: null];
        }

        $eq_pos = array_search('=', $tokens, true);

        if ($eq_pos === false) {
            return [$name, $tokens, null];
        }

        $default_tokens = array_slice($tokens, $eq_pos + 1);
        $default_type_string = implode(' ', $default_tokens) ?: null;

        return [$name, array_slice($tokens, 0, $eq_pos), $default_type_string];
    }
}
