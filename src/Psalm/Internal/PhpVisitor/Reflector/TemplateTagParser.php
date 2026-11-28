<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use PhpParser\Comment\Doc;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Storage\TemplateVariance;

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
use function strpos;
use function strtolower;
use function substr;
use function substr_count;
use function trim;

/**
 * Parses the `@template`, `@template-covariant` and `@template-contravariant` tags of a docblock, with their
 * `@psalm-` and `@phpstan-` forms: `T`, `T of Upper`, `T super Lower`, both in either order, each with an optional
 * `= Default` (`as` may stand for `of`), and followed by an optional description.
 *
 * @internal
 * @psalm-immutable
 */
final class TemplateTagParser
{
    /**
     * The templates of the docblock in order of first declaration in any form, each from its preferred form
     * (`@psalm-`, then `@phpstan-`, then the plain tag), as `[name, modifier, upper bound, variance, offset of the
     * first declaration in the docblock]`, and their defaults and lower bounds. A bound the parser cannot split from
     * the description comes with what follows it, for the scanner to report.
     *
     * @param array<string, TemplateVariance> $tags the tags to read, and the variance of their templates
     * @param array<string, array<string, array{string, ?string, ?string, TemplateVariance, int}>> $templates
     *     the purity templates already read, by name and form
     * @return array{
     *     list<array{string, ?string, ?string, TemplateVariance, int}>,
     *     array<string, array{type: string, line_number: int}>,
     *     array<string, array{type: string, line_number: int}>
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
        $lower_bounds = [];

        foreach ($tags as $tag => $variance) {
            foreach ($parsed_docblock->combined_tags[$tag] ?? [] as $offset => $template_line) {
                [$name, $bound, $lower_bound, $default] = self::parse($template_line, $tag);

                $source_prefix = 'none';
                if (isset($parsed_docblock->tags['psalm-' . $tag][$offset])) {
                    $source_prefix = 'psalm';
                } elseif (isset($parsed_docblock->tags['phpstan-' . $tag][$offset])) {
                    $source_prefix = 'phpstan';
                }

                $docblock_offset = $offset - $comment->getStartFilePos();
                $templates[$name][$source_prefix]
                    = [$name, $bound === null ? null : 'of', $bound, $variance, $docblock_offset];
                $line_number = $comment->getStartLine() + substr_count($comment->getText(), "\n", 0, $docblock_offset);

                if ($default !== null) {
                    $defaults[$name][$source_prefix] = ['type' => $default, 'line_number' => $line_number];
                }

                if ($lower_bound !== null) {
                    $lower_bounds[$name][$source_prefix] = ['type' => $lower_bound, 'line_number' => $line_number];
                }
            }
        }

        $declared_at = [];

        foreach ($templates as $name => $entries) {
            foreach ($entries as $entry) {
                $declared_at[$name] = min($declared_at[$name] ?? $entry[4], $entry[4]);
            }
        }

        // a bound may use the templates declared before it, in whichever tag form they are written
        asort($declared_at);

        $chosen_templates = [];
        $chosen_defaults = [];
        $chosen_lower_bounds = [];

        foreach ($declared_at as $name => $offset) {
            foreach (['psalm', 'phpstan', 'none'] as $source_prefix) {
                if (isset($templates[$name][$source_prefix])) {
                    [$template_name, $modifier, $bound, $variance] = $templates[$name][$source_prefix];
                    $chosen_templates[] = [$template_name, $modifier, $bound, $variance, $offset];

                    if (isset($defaults[$name][$source_prefix])) {
                        $chosen_defaults[$name] = $defaults[$name][$source_prefix];
                    }

                    if (isset($lower_bounds[$name][$source_prefix])) {
                        $chosen_lower_bounds[$name] = $lower_bounds[$name][$source_prefix];
                    }

                    break;
                }
            }
        }

        return [$chosen_templates, $chosen_defaults, $chosen_lower_bounds];
    }

    /**
     * @return array{non-empty-string, ?string, ?string, ?string} the name, upper bound, lower bound and default: an
     *     empty lower bound or default for `super` or `=` with nothing after it, which the scanner reports for that
     *     template only
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

        $rest = implode(' ', $parts);
        $bounds = [];

        // an upper (`of`, `as`) and a lower (`super`) bound clause, in either order: a repeated `super` is an error, a
        // repeated upper bound clause or a capitalised keyword after the first clause is description
        // (`@template T of Animal Super class`)
        while (true) {
            $space = strpos($rest, ' ');
            $word = $space === false ? $rest : substr($rest, 0, $space);

            if ($bounds === []) {
                $word = strtolower($word);
            }

            if ($word !== 'of' && $word !== 'as' && $word !== 'super') {
                break;
            }

            $keyword = $word === 'super' ? 'super' : 'of';
            $clause = $space === false ? '' : substr($rest, $space + 1);

            if (isset($bounds[$keyword])) {
                if ($keyword === 'super') {
                    throw new IncorrectDocblockException('Repeated super clause in @' . $tag . ' tag');
                }

                break;
            }

            if ($clause === '') {
                if ($keyword === 'super') {
                    $bounds['super'] = '';
                }

                $rest = '';

                break;
            }

            $equals = self::findDefaultSeparator($clause);

            if ($equals !== null) {
                $bounds[$keyword] = rtrim(substr($clause, 0, $equals));
                $rest = substr($clause, $equals);

                break;
            }

            try {
                $type = CommentAnalyzer::splitDocLine($clause)[0];
            } catch (DocblockParseException) {
                $type = null;
            }

            if ($type === null || !str_starts_with($clause, $type)) {
                $bounds[$keyword] = $clause;
                $rest = '';

                break;
            }

            $bounds[$keyword] = $type;
            $rest = ltrim(substr($clause, strlen($type)));
        }

        $default = null;

        if (str_starts_with($rest, '=')) {
            $default = ltrim(substr($rest, 1));

            if ($default !== '') {
                try {
                    $default = CommentAnalyzer::splitDocLine($default)[0];
                } catch (DocblockParseException) {
                    // left whole, for the scanner to report as an invalid type
                }
            }
        }

        return [$name, $bounds['of'] ?? null, $bounds['super'] ?? null, $default];
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
