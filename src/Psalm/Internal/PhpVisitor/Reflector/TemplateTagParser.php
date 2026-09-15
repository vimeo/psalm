<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use function array_search;
use function array_slice;
use function implode;

/**
 * @internal
 */
final class TemplateTagParser
{
    /**
     * Splits the `= DefaultType` suffix off a tokenized `@template` tag, if present.
     *
     * `$template_type` is the whitespace-tokenized remainder of an `@template` tag after
     * the template name has been shifted off (so it may still carry a modifier like
     * `of`/`as`/`super` and its bound ahead of the `=`).
     *
     * @param list<string> $template_type
     * @return array{0: list<string>, 1: ?string} The tokens with any `= Default` suffix
     *     removed, and the default type string (or null if there was no default).
     */
    public static function splitDefault(array $template_type): array
    {
        $eq_pos = array_search('=', $template_type, true);

        if ($eq_pos === false) {
            return [$template_type, null];
        }

        $default_tokens = array_slice($template_type, $eq_pos + 1);
        $default_type_string = implode(' ', $default_tokens) ?: null;

        return [array_slice($template_type, 0, $eq_pos), $default_type_string];
    }
}
