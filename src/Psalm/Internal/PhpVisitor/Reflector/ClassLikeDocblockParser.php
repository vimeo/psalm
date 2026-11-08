<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use Psalm\Aliases;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Scanner\ClassLikeDocblockComment;
use Psalm\Internal\Scanner\DocblockParser;
use Psalm\Storage\Capabilities;
use Psalm\Storage\CapabilitiesParseException;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Union;

use function array_column;
use function array_fill_keys;
use function array_key_first;
use function count;
use function preg_match;
use function preg_replace;
use function reset;
use function str_replace;
use function strlen;
use function substr_count;
use function trim;

/**
 * @internal
 */
final class ClassLikeDocblockParser
{
    /**
     * @throws DocblockParseException if there was a problem parsing the docblock
     */
    public static function parse(
        Node $node,
        Doc $comment,
        Aliases $aliases,
    ): ClassLikeDocblockComment {
        $parsed_docblock = DocComment::parsePreservingLength($comment);
        $codebase = ProjectAnalyzer::getInstance()->getCodebase();

        $info = new ClassLikeDocblockComment();

        $templates = [];

        if (isset($parsed_docblock->tags['psalm-purity-template'])) {
            // a purity template is a covariant template whose values are capability sets
            foreach ($parsed_docblock->tags['psalm-purity-template'] as $offset => $purity_template_line) {
                foreach (PurityTemplateParser::parse($purity_template_line) as $purity_template) {
                    $templates[$purity_template['name']]['psalm'] = [
                        $purity_template['name'],
                        'of',
                        $purity_template['bound'],
                        true,
                        $offset - $comment->getStartFilePos(),
                    ];
                    $info->purity_templates[] = $purity_template['name'];

                    if ($purity_template['default'] !== null) {
                        $info->purity_template_defaults[$purity_template['name']] = $purity_template['default'];
                    }

                    if ($purity_template['lower'] !== null) {
                        $info->purity_template_lower_bounds[$purity_template['name']] = $purity_template['lower'];
                    }
                }
            }
        }

        [$info->templates, $info->template_defaults] = TemplateTagParser::parseTags(
            $parsed_docblock,
            $comment,
            ['template' => false, 'template-covariant' => true],
            $templates,
        );

        if (isset($parsed_docblock->combined_tags['extends'])) {
            foreach ($parsed_docblock->combined_tags['extends'] as $template_line) {
                $doc_line_parts = CommentAnalyzer::splitDocLine($template_line);
                $doc_line_parts[0] = CommentAnalyzer::sanitizeDocblockType($doc_line_parts[0]);
                $info->template_extends[] = $doc_line_parts[0];
            }
        }

        $extension_requirements = $parsed_docblock->tags['psalm-require-extends']
            ?? $parsed_docblock->tags['phpstan-require-extends']
            ?? [];

        if (count($extension_requirements) > 0) {
            $info->extension_requirement = CommentAnalyzer::sanitizeDocblockType(
                $extension_requirements[array_key_first($extension_requirements)],
            );
        }

        $implementation_requirements = $parsed_docblock->tags['psalm-require-implements']
            ?? $parsed_docblock->tags['phpstan-require-implements']
            ?? [];

        foreach ($implementation_requirements as $implementation_requirement) {
            $info->implementation_requirements[] = CommentAnalyzer::sanitizeDocblockType(
                $implementation_requirement,
            );
        }

        if (isset($parsed_docblock->combined_tags['implements'])) {
            foreach ($parsed_docblock->combined_tags['implements'] as $template_line) {
                $doc_line_parts = CommentAnalyzer::splitDocLine($template_line);
                $doc_line_parts[0] = CommentAnalyzer::sanitizeDocblockType($doc_line_parts[0]);
                $info->template_implements[] = $doc_line_parts[0];
            }
        }

        if (isset($parsed_docblock->tags['psalm-yield'])) {
            $yield = (string) reset($parsed_docblock->tags['psalm-yield']);

            $info->yield = CommentAnalyzer::sanitizeDocblockType($yield);
        }

        if (isset($parsed_docblock->tags['deprecated'])) {
            $info->deprecated = true;
        }

        if (isset($parsed_docblock->tags['since'])) {
            $since = trim((string) reset($parsed_docblock->tags['since']));
            // Only a PHP-version `@since` (major in 4/5/7/8, e.g. `8.5`) is meaningful here; the
            // caller additionally restricts this to stub files, since `@since` is commonly used
            // with a project version rather than the PHP version.
            if (preg_match('/^([4578])\.(\d)(\.\d+)?(\s+PHP)?$/i', $since, $since_match)
                && isset($since_match[1], $since_match[2])
            ) {
                $info->since_php_major_version = (int) $since_match[1];
                $info->since_php_minor_version = (int) $since_match[2];
            }
        }

        if (isset($parsed_docblock->tags['internal'])) {
            $info->internal = true;
        }

        if (isset($parsed_docblock->tags['final'])) {
            $info->final = true;
        }

        if (isset($parsed_docblock->tags['psalm-consistent-constructor'])) {
            $info->consistent_constructor = true;
        }

        if (isset($parsed_docblock->tags['psalm-consistent-templates'])) {
            $info->consistent_templates = true;
        }

        if (count($info->psalm_internal = DocblockParser::handlePsalmInternal($parsed_docblock)) !== 0) {
            $info->internal = true;
        }

        if (isset($parsed_docblock->tags['mixin'])) {
            foreach ($parsed_docblock->tags['mixin'] as $rawMixin) {
                $mixin = trim($rawMixin);
                $doc_line_parts = CommentAnalyzer::splitDocLine($mixin);
                $mixin = $doc_line_parts[0];

                if ($mixin) {
                    $info->mixins[] = $mixin;
                } else {
                    throw new DocblockParseException('@mixin annotation used without specifying class');
                }
            }
        }

        foreach (['', 'psalm-'] as $prefix) {
            if (isset($parsed_docblock->tags[$prefix . 'seal-properties'])) {
                $info->sealed_properties = true;
            }
            if (isset($parsed_docblock->tags[$prefix . 'no-seal-properties'])) {
                $info->sealed_properties = false;
            }

            if (isset($parsed_docblock->tags[$prefix . 'seal-methods'])) {
                $info->sealed_methods = true;
            }
            if (isset($parsed_docblock->tags[$prefix . 'no-seal-methods'])) {
                $info->sealed_methods = false;
            }
        }

        if (isset($parsed_docblock->tags['psalm-inheritors'])) {
            foreach ($parsed_docblock->tags['psalm-inheritors'] as $template_line) {
                $doc_line_parts = CommentAnalyzer::splitDocLine($template_line);
                $doc_line_parts[0] = CommentAnalyzer::sanitizeDocblockType($doc_line_parts[0]);
                $info->inheritors = $doc_line_parts[0];
            }
        }

        $info->capabilities = Capabilities::ALL;

        if (isset($parsed_docblock->tags['psalm-pure'])
        ) {
            $info->capabilities = Capabilities::NONE;
            $info->has_mutations_annotation = true;
        } elseif (isset($parsed_docblock->tags['psalm-immutable'])
            || isset($parsed_docblock->tags['psalm-mutation-free'])
        ) {
            $info->capabilities = Capabilities::MUTATION_FREE;
            $info->has_mutations_annotation = true;
        } elseif (isset($parsed_docblock->tags['psalm-external-mutation-free'])) {
            $info->capabilities = Capabilities::EXTERNAL_MUTATION_FREE;
            $info->has_mutations_annotation = true;
        } elseif (isset($parsed_docblock->tags['psalm-mutable'])) {
            $info->capabilities = Capabilities::ALL;
            $info->has_mutations_annotation = true;
        } elseif (isset($parsed_docblock->tags['psalm-capabilities'])) {
            $info->capabilities = Capabilities::NONE;
            $info->has_mutations_annotation = true;

            foreach ($parsed_docblock->tags['psalm-capabilities'] as $capabilities_line) {
                try {
                    $info->capabilities |= Capabilities::fromList($capabilities_line);
                } catch (CapabilitiesParseException) {
                    // a purity type, possibly through type aliases: resolved by the scanner
                    $info->capabilities_expressions[] = trim($capabilities_line);
                }
            }
        }

        if (isset($parsed_docblock->tags['psalm-taint-specialize'])) {
            $info->taint_specialize = true;
        }

        if (isset($parsed_docblock->tags['psalm-cli-only'])) {
            $info->cli_only = true;
        }

        if (isset($parsed_docblock->tags['psalm-taint-property-bag'])) {
            $property_bag = trim((string) reset($parsed_docblock->tags['psalm-taint-property-bag']));
            if (preg_match('/^\$([A-Za-z_]\w*)$/', $property_bag, $matches) !== 1 || !isset($matches[1])) {
                throw new IncorrectDocblockException(
                    '@psalm-taint-property-bag "' . $property_bag . '" does not name a property, like $properties',
                );
            }

            $info->taint_property_bag = $matches[1];
        }

        if (isset($parsed_docblock->tags['psalm-override-property-visibility'])) {
            $info->override_property_visibility = true;
        }

        if (isset($parsed_docblock->tags['psalm-override-method-visibility'])) {
            $info->override_method_visibility = true;
        }

        if (isset($parsed_docblock->tags['psalm-suppress'])) {
            foreach ($parsed_docblock->tags['psalm-suppress'] as $offset => $suppress_entry) {
                foreach (DocComment::parseSuppressList($suppress_entry) as $issue_offset => $suppressed_issue) {
                    $info->suppressed_issues[$issue_offset + $offset] = $suppressed_issue;
                }
            }
        }

        $imported_types = ($parsed_docblock->tags['phpstan-import-type'] ?? []) +
            ($parsed_docblock->tags['psalm-import-type'] ?? []);

        foreach ($imported_types as $offset => $imported_type_entry) {
            $info->imported_types[] = [
                'line_number' => $comment->getStartLine() + substr_count(
                    $comment->getText(),
                    "\n",
                    0,
                    $offset - $comment->getStartFilePos(),
                ),
                'start_offset' => $offset,
                'end_offset' => $offset + strlen($imported_type_entry),
                'parts' => CommentAnalyzer::splitDocLine($imported_type_entry),
            ];
        }

        if (isset($parsed_docblock->combined_tags['method'])) {
            if ($info->sealed_methods === null) {
                $info->sealed_methods = true;
            }
            // the purity templates of the class, for `Closure[P]` in a signature: only their names
            // matter here, the types are printed back into the docblock of a pseudo-method
            $purity_template_map = [];

            foreach ($info->purity_templates as $purity_template) {
                $purity_template_map[$purity_template] = ['' => new Union([new TCapabilities(Capabilities::ALL)])];
            }

            // the class's other templates, for its parameter types
            $param_template_map = $purity_template_map + array_fill_keys(array_column($info->templates, 0), true);

            foreach ($parsed_docblock->combined_tags['method'] as $offset => $method_entry) {
                /** @var Doc $node_doc_comment */
                $node_doc_comment = $node->getDocComment();

                $method_node = PseudoMethodParser::parse(
                    $codebase,
                    $aliases,
                    $node,
                    $node_doc_comment,
                    $comment,
                    $method_entry,
                    $offset,
                    $purity_template_map,
                    $param_template_map,
                );

                $method_node->setAttribute(
                    'psalm_tag',
                    isset($parsed_docblock->tags['psalm-method'][$offset]),
                );

                $info->methods[] = $method_node;
            }
        }

        if (isset($parsed_docblock->tags['psalm-stub-override'])) {
            $info->stub_override = true;
        }

        if ($parsed_docblock->description) {
            $info->description = $parsed_docblock->description;
        }

        $info->public_api = isset($parsed_docblock->tags['psalm-api']) || isset($parsed_docblock->tags['api']);

        if (isset($parsed_docblock->tags['property'])
            && $codebase->config->docblock_property_types_seal_properties
            && $info->sealed_properties === null
        ) {
            $info->sealed_properties = true;
        }

        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'property');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'psalm-property');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'property-read');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'psalm-property-read');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'property-write');
        self::addMagicPropertyToInfo($comment, $info, $parsed_docblock->tags, 'psalm-property-write');

        return $info;
    }

    /**
     * @param array<string, array<int, string>> $specials
     * @param 'property'|'psalm-property'|'property-read'|
     *     'psalm-property-read'|'property-write'|'psalm-property-write' $property_tag
     * @throws DocblockParseException
     */
    private static function addMagicPropertyToInfo(
        Doc $comment,
        ClassLikeDocblockComment $info,
        array $specials,
        string $property_tag,
    ): void {
        $magic_property_comments = $specials[$property_tag] ?? [];

        foreach ($magic_property_comments as $offset => $property) {
            $line_parts = CommentAnalyzer::splitDocLine($property);

            if (count($line_parts) === 1 && isset($line_parts[0][0]) && $line_parts[0][0] === '$') {
                continue;
            }

            if (count($line_parts) > 1) {
                if (preg_match('/^&?\$[A-Za-z0-9_\x80-\xff]+,?$/', $line_parts[1])
                    && $line_parts[0][0] !== '{'
                ) {
                    $line_parts[1] = str_replace('&', '', $line_parts[1]);

                    $line_parts[1] = (string) preg_replace('/,$/', '', $line_parts[1], 1);

                    $end = $offset + strlen($line_parts[0]);

                    $line_parts[0] = CommentAnalyzer::sanitizeDocblockType($line_parts[0]);

                    if ($line_parts[0] === ''
                        || ($line_parts[0][0] === '$'
                            && !preg_match('/^\$this(\||$)/', $line_parts[0]))
                    ) {
                        throw new IncorrectDocblockException('Misplaced variable');
                    }

                    $name = trim($line_parts[1]);

                    if (!preg_match('/^\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/', $name)) {
                        throw new DocblockParseException('Badly-formatted @property name');
                    }

                    $info->properties[] = [
                        'name' => $name,
                        'type' => $line_parts[0],
                        'line_number' => $comment->getStartLine() + substr_count(
                            $comment->getText(),
                            "\n",
                            0,
                            $offset - $comment->getStartFilePos(),
                        ),
                        'tag' => $property_tag,
                        'start' => $offset,
                        'end' => $end,
                    ];
                }
            } else {
                throw new DocblockParseException('Badly-formatted @property');
            }
        }
    }
}
