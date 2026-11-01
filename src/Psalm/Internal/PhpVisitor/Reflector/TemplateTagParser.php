<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use PhpParser\Comment\Doc;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Scanner\ParsedDocblock;

use function array_keys;
use function array_shift;
use function asort;
use function count;
use function implode;
use function in_array;
use function ltrim;
use function min;
use function preg_split;
use function rtrim;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function substr_count;
use function trim;

/**
 * Parses the `@template` and `@template-covariant` tags of a docblock, with their `@psalm-` and `@phpstan-`
 * forms: `T`, `T of Bound`, `T = Default` or `T of Bound = Default` (`as` and `super` may stand for `of`),
 * each followed by an optional description.
 *
 * @internal
 * @psalm-immutable
 */
final class TemplateTagParser
{
    /**
     * The templates of the docblock, each from its preferred form (`@psalm-`, then `@phpstan-`, then the plain tag),
     * as `[name, modifier, bound, covariant, offset in the docblock]`, their defaults, and their names in order of
     * first declaration in any form. The bound comes with what follows it, as the scanner cuts the description off.
     *
     * @param array<string, bool> $tags the tags to read, and whether their templates are covariant
     * @param array<string, array<string, array{string, ?string, ?string, bool, int}>> $templates the purity
     *     templates already read, by name and form
     * @return array{
     *     list<array{string, ?string, ?string, bool, int}>,
     *     array<string, array{type: string, line_number: int}>,
     *     list<string>
     * }
     * @throws IncorrectDocblockException
     * @psalm-capabilities read-props
     */
    public static function parseTags(
        ParsedDocblock $parsed_docblock,
        Doc $comment,
        array $tags,
        array $templates,
    ): array {
        $defaults = [];

        foreach ($tags as $tag => $covariant) {
            foreach ($parsed_docblock->combined_tags[$tag] ?? [] as $offset => $template_line) {
                [$name, $modifier, $bound, $default] = self::parse($template_line, $tag);

                $source_prefix = 'none';
                if (isset($parsed_docblock->tags['psalm-' . $tag][$offset])) {
                    $source_prefix = 'psalm';
                } elseif (isset($parsed_docblock->tags['phpstan-' . $tag][$offset])) {
                    $source_prefix = 'phpstan';
                }

                $docblock_offset = $offset - $comment->getStartFilePos();
                $templates[$name][$source_prefix] = [$name, $modifier, $bound, $covariant, $docblock_offset];

                if ($default !== null) {
                    $defaults[$name][$source_prefix] = [
                        'type' => $default,
                        'line_number' => $comment->getStartLine()
                            + substr_count($comment->getText(), "\n", 0, $docblock_offset),
                    ];
                }
            }
        }

        $chosen_templates = [];
        $chosen_defaults = [];
        $declared_at = [];

        foreach ($templates as $name => $entries) {
            foreach ($entries as $entry) {
                $declared_at[$name] = min($declared_at[$name] ?? $entry[4], $entry[4]);
            }

            foreach (['psalm', 'phpstan', 'none'] as $source_prefix) {
                if (isset($entries[$source_prefix])) {
                    $chosen_templates[] = $entries[$source_prefix];

                    if (isset($defaults[$name][$source_prefix])) {
                        $chosen_defaults[$name] = $defaults[$name][$source_prefix];
                    }

                    break;
                }
            }
        }

        asort($declared_at);

        return [$chosen_templates, $chosen_defaults, array_keys($declared_at)];
    }

    /**
     * @return array{non-empty-string, ?string, ?string, ?string} the name, modifier, bound and default: an empty
     *     string for `=` with nothing after it, which the scanner reports for that template only
     * @throws IncorrectDocblockException
     * @psalm-pure
     */
    private static function parse(string $line, string $tag): array
    {
        $parts = preg_split('/[\s]+/', CommentAnalyzer::sanitizeDocblockType($line)) ?: [];
        $name = array_shift($parts);

        if ($name === null || $name === '') {
            throw new IncorrectDocblockException('Empty @' . $tag . ' tag');
        }

        $modifier = null;

        if (count($parts) > 1 && in_array(strtolower($parts[0]), ['as', 'super', 'of'], true)) {
            $modifier = strtolower(array_shift($parts));
        }

        $rest = implode(' ', $parts);
        $default = null;

        $equals = $modifier === null
            ? (str_starts_with($rest, '=') ? 0 : null)
            : self::findDefaultSeparator($rest);

        if ($equals !== null) {
            $default = ltrim(substr($rest, $equals + 1));

            if ($default !== '') {
                try {
                    $default = CommentAnalyzer::splitDocLine($default)[0];
                } catch (DocblockParseException) {
                    // left whole, for the scanner to report as an invalid type
                }
            }

            $rest = rtrim(substr($rest, 0, $equals));
        }

        return [$name, $modifier, $modifier === null ? null : $rest, $default];
    }

    /**
     * The offset of the `=` that follows the bound type, if any: one outside brackets and quotes
     * (`callable(int=): void` and `'a=b'` are types, and `\'` and `\\` are escaped in quotes as in
     * {@see CommentAnalyzer::splitDocLine()}), with nothing but the bound type before it.
     *
     * @psalm-pure
     */
    private static function findDefaultSeparator(string $rest): ?int
    {
        $depth = 0;
        $quote = null;
        $escaped = false;

        for ($i = 0, $l = strlen($rest); $i < $l; $i++) {
            $char = $rest[$i];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === $quote) {
                    $quote = null;
                } elseif ($char === '\\' && in_array($rest[$i + 1] ?? '', [$quote, '\\'], true)) {
                    $escaped = true;
                }
            } elseif ($char === '\'' || $char === '"') {
                $quote = $char;
            } elseif ($char === '(' || $char === '<' || $char === '{' || $char === '[') {
                $depth++;
            } elseif ($char === ')' || $char === '>' || $char === '}' || $char === ']') {
                $depth--;
            } elseif ($char === '=' && $depth === 0) {
                $bound = trim(substr($rest, 0, $i));

                try {
                    return $bound !== '' && count(CommentAnalyzer::splitDocLine($bound)) === 1 ? $i : null;
                } catch (DocblockParseException) {
                    return null;
                }
            }
        }

        return null;
    }
}
