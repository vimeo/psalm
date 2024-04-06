<?php

namespace Psalm;

use PhpParser\Comment\Doc;
use Psalm\Exception\DocblockParseException;
use Psalm\Internal\Scanner\DocblockParser;
use Psalm\Internal\Scanner\ParsedDocblock;

use function explode;
use function in_array;
use function preg_match;
use function strlen;
use function strpos;
use function strspn;
use function substr;
use function trim;

final class DocComment
{
    public const PSALM_ANNOTATIONS = [
        'return', 'param', 'template', 'var', 'type',
        'template-covariant', 'property', 'property-read', 'property-write', 'method',
        'assert', 'assert-if-true', 'assert-if-false', 'suppress',
        'ignore-nullable-return', 'override-property-visibility',
        'override-method-visibility', 'seal-properties', 'seal-methods',
        'no-seal-properties', 'no-seal-methods',
        'ignore-falsable-return', 'variadic', 'pure',
        'ignore-variable-method', 'ignore-variable-property', 'internal',
        'taint-sink', 'taint-source', 'assert-untainted', 'scope-this',
        'mutation-free', 'external-mutation-free', 'immutable', 'readonly',
        'allow-private-mutation', 'readonly-allow-private-mutation',
        'yield', 'trace', 'import-type', 'flow', 'taint-specialize', 'taint-escape',
        'taint-unescape', 'self-out', 'consistent-constructor', 'stub-override',
        'require-extends', 'require-implements', 'param-out', 'ignore-var',
        'consistent-templates', 'if-this-is', 'this-out', 'check-type', 'check-type-exact',
        'api', 'inheritors',
    ];

    /**
     * Parse a docblock comment into its parts.
     */
    public static function parsePreservingLength(Doc $docblock, bool $no_psalm_error = false): ParsedDocblock
    {
        $parsed_docblock = DocblockParser::parse(
            $docblock->getText(),
            $docblock->getStartFilePos(),
        );

        if ($no_psalm_error) {
            return $parsed_docblock;
        }

        foreach ($parsed_docblock->tags as $special_key => $_) {
            if (strpos($special_key, 'psalm-') === 0) {
                $special_key = substr($special_key, 6);

                if (!in_array(
                    $special_key,
                    self::PSALM_ANNOTATIONS,
                    true,
                )) {
                    throw new DocblockParseException('Unrecognised annotation @psalm-' . $special_key);
                }
            }
        }

        return $parsed_docblock;
    }

    /**
     * @psalm-pure
     * @return array<int,string>
     */
    public static function parseSuppressList(string $suppress_entry): array
    {
        preg_match(
            '/
                (?(DEFINE)
                    # either a single issue or comma separated list of issues
                    (?<issue_list> (?&issue) \s* , \s* (?&issue_list) | (?&issue) )

                    # definition of a single issue
                    (?<issue> [A-Za-z0-9_-]+ )
                )
                ^ (?P<issues> (?&issue_list) ) (?P<description> .* ) $
            /xm',
            $suppress_entry,
            $matches,
        );

        if (!isset($matches['issues'])) {
            return [];
        }

        $issue_offset = 0;
        $ret = [];

        foreach (explode(',', $matches['issues']) as $suppressed_issue) {
            $issue_offset += strspn($suppressed_issue, "\t\n\f\r ");
            $ret[$issue_offset] = trim($suppressed_issue);
            $issue_offset += strlen($suppressed_issue) + 1;
        }

        return $ret;
    }
}
