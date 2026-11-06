<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor\Reflector;

use Exception;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Internal\Type\ParseTree\MethodParamTree;
use Psalm\Internal\Type\ParseTree\MethodTree;
use Psalm\Internal\Type\ParseTree\MethodWithReturnTypeTree;
use Psalm\Internal\Type\ParseTreeCreator;
use Psalm\Internal\Type\TypeParser;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Type\Union;

use function array_shift;
use function count;
use function explode;
use function implode;
use function preg_match;
use function preg_replace;
use function str_contains;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function substr_count;
use function trim;

/**
 * Turns the signature of a `@method` (or `@psalm-overload`) tag into the declaration of a method
 *
 * @internal
 */
final class PseudoMethodParser
{
    /**
     * @param array<string, array<string, Union>> $purity_template_map the purity templates of the class
     * @param array<string, mixed> $param_template_map the templates the parameter types may use
     * @param Doc $node_doc_comment the docblock of $node
     * @param int $offset the offset of the tag in the docblock
     * @throws DocblockParseException
     */
    public static function parse(
        Codebase $codebase,
        Aliases $aliases,
        Node $node,
        Doc $node_doc_comment,
        Doc $comment,
        string $method_entry,
        int $offset,
        array $purity_template_map,
        array $param_template_map,
    ): ClassMethod {
        $method_entry = (string) preg_replace('/[ \t]+/', ' ', trim($method_entry));

        $docblock_lines = [];

        $is_static = false;

        $has_return = false;

        $doc_line_parts = CommentAnalyzer::splitDocLine($method_entry);

        if (count($doc_line_parts) > 2
            && $doc_line_parts[0] === 'static'
            && !strpos($doc_line_parts[1], '(')
        ) {
            $is_static = true;
            array_shift($doc_line_parts);
            $method_entry = implode(' ', $doc_line_parts);
            $doc_line_parts = CommentAnalyzer::splitDocLine($method_entry);
        }

        // `static` before the name is either the return type or, if the method has a
        // return type after its parameters (`static foo(): int`), the modifier
        $leading_static = false;

        if (!preg_match('/^([a-z_A-Z][a-z_0-9A-Z]+) *\(/', $method_entry, $matches)) {
            if (count($doc_line_parts) > 1) {
                $leading_static = $doc_line_parts[0] === 'static';
                $docblock_lines[] = '@return ' . array_shift($doc_line_parts);
                $has_return = true;

                $method_entry = implode(' ', $doc_line_parts);
            }
        }

        $method_entry = trim((string) preg_replace('/\/\/.*/', '', $method_entry));

        $method_entry = (string) preg_replace(
            '/array\(([0-9a-zA-Z_\'\" ]+,)*([0-9a-zA-Z_\'\" ]+)\)/',
            '[]',
            $method_entry,
        );

        // Find the method's closing parenthesis by tracking nesting depth,
        // so parenthesized union types like ('a'|'b') don't terminate the scan early.
        $method_open_paren = strpos($method_entry, '(');
        if ($method_open_paren !== false) {
            $depth = 0;
            $method_close_paren = null;
            for ($i = $method_open_paren, $len = strlen($method_entry); $i < $len; ++$i) {
                $char = $method_entry[$i];
                if ($char === "'" || $char === '"') {
                    $close = strpos($method_entry, $char, $i + 1);
                    if ($close !== false) {
                        $i = $close;
                    }
                    continue;
                }
                if ($char === '(') {
                    ++$depth;
                } elseif ($char === ')') {
                    --$depth;
                    if ($depth === 0) {
                        $method_close_paren = $i;
                        break;
                    }
                }
            }

            if ($method_close_paren !== null) {
                $after_paren = substr($method_entry, $method_close_paren + 1);
                $method_entry = substr($method_entry, 0, $method_close_paren + 1);

                // Optionally consume return type annotation after the closing paren, which
                // is a whole type (`: Closure[pure](): int`), and may be followed by a description
                if (preg_match('/^ ?: ?(\S.*)$/s', $after_paren, $return_matches)
                    && isset($return_matches[1])
                ) {
                    $method_entry .= ':' . CommentAnalyzer::splitDocLine($return_matches[1])[0];
                }
            }
        }

        $method_entry = str_replace([', ', '( '], [',', '('], $method_entry);
        $method_entry = (string) preg_replace('/ (?!(\$|\.\.\.|&))/', '', trim($method_entry));

        // replace array bracket contents, but not the purity of a type (`Closure[pure]`)
        $method_entry = (string) preg_replace(
            '/(?<!\w)\[([0-9a-zA-Z_\'\" ]+,)*([0-9a-zA-Z_\'\" ]+)\]/',
            '[]',
            $method_entry,
        );

        if (!$method_entry) {
            throw new DocblockParseException('No @method entry specified');
        }

        try {
            $method_tokens = TypeTokenizer::getFullyQualifiedTokens(
                $method_entry,
                $aliases,
                $purity_template_map,
            );

            if ($param_template_map !== $purity_template_map) {
                $method_tokens = self::keepTemplatesInParamTypes(
                    $method_tokens,
                    TypeTokenizer::getFullyQualifiedTokens($method_entry, $aliases, $param_template_map),
                );
            }

            $parse_tree_creator = new ParseTreeCreator($method_tokens);

            $method_tree = $parse_tree_creator->create();
        } catch (TypeParseTreeException $e) {
            throw new DocblockParseException(
                $method_entry . ' is not a valid method: '
                . $e->getMessage(),
                0,
                $e,
            );
        }

        if (!$method_tree instanceof MethodWithReturnTypeTree
            && !$method_tree instanceof MethodTree) {
            throw new DocblockParseException($method_entry . ' is not a valid method');
        }

        if ($method_tree instanceof MethodWithReturnTypeTree) {
            if ($leading_static) {
                $is_static = true;
                $has_return = false;
                $docblock_lines = [];
            }

            if (!$has_return) {
                $docblock_lines[] = '@return ' . TypeParser::getTypeFromTree(
                    $method_tree->children[1],
                    $codebase,
                    null,
                    $purity_template_map,
                )->toNamespacedString($aliases->namespace, $aliases->uses, null, false);
            }

            $method_tree = $method_tree->children[0];
        }

        if (!$method_tree instanceof MethodTree) {
            throw new DocblockParseException($method_entry . ' is not a valid method');
        }

        $args = [];

        foreach ($method_tree->children as $method_tree_child) {
            if (!$method_tree_child instanceof MethodParamTree) {
                throw new DocblockParseException($method_entry . ' is not a valid method');
            }

            $args[] = ($method_tree_child->byref ? '&' : '')
                . ($method_tree_child->variadic ? '...' : '')
                . $method_tree_child->name
                . ($method_tree_child->default != '' ? ' = ' . $method_tree_child->default : '');


            if ($method_tree_child->children) {
                try {
                    $param_type = TypeParser::getTypeFromTree(
                        $method_tree_child->children[0],
                        $codebase,
                        null,
                        $purity_template_map,
                    );
                } catch (Exception $e) {
                    throw new DocblockParseException(
                        'Badly-formatted @method string ' . $method_entry . ' - ' . $e,
                    );
                }

                $param_type_string = $param_type->toNamespacedString('\\', [], null, false);
                $docblock_lines[] = '@param ' . $param_type_string . ' '
                    . ($method_tree_child->variadic ? '...' : '')
                    . $method_tree_child->name;
            }
        }

        $function_string = 'function ' . $method_tree->value . '(' . implode(', ', $args) . ')';

        if ($is_static) {
            $function_string = 'static ' . $function_string;
        }

        $function_docblock = $docblock_lines ? "/**\n * " . implode("\n * ", $docblock_lines) . "\n*/\n" : "";

        $php_string = '<?php class A { ' . $function_docblock . ' public ' . $function_string . '{} }';

        try {
            $statements = StatementsProvider::parseStatements(
                $php_string,
                $codebase->analysis_php_version_id,
            );
        } catch (Exception) {
            throw new DocblockParseException('Badly-formatted @method string ' . $method_entry);
        }

        if (!$statements
            || !$statements[0] instanceof Class_
            || !isset($statements[0]->stmts[0])
            || !$statements[0]->stmts[0] instanceof ClassMethod
        ) {
            throw new DocblockParseException('Badly-formatted @method string ' . $method_entry);
        }

        /** @var Doc */
        $node_doc_comment = $node->getDocComment();

        $method_offset = self::getMethodOffset($comment, $method_entry);

        $statements[0]->stmts[0]->setAttribute('startLine', $node_doc_comment->getStartLine() + $method_offset);
        $statements[0]->stmts[0]->setAttribute('startFilePos', $node_doc_comment->getStartFilePos());
        $statements[0]->stmts[0]->setAttribute('endFilePos', $node->getAttribute('startFilePos'));

        if ($doc_comment = $statements[0]->stmts[0]->getDocComment()) {
            $statements[0]->stmts[0]->setDocComment(
                new Doc(
                    $doc_comment->getText(),
                    $comment->getStartLine() + substr_count(
                        $comment->getText(),
                        "\n",
                        0,
                        $offset - $comment->getStartFilePos(),
                    ),
                    $node_doc_comment->getStartFilePos(),
                ),
            );
        }

        return $statements[0]->stmts[0];
    }

    /**
     * The tokens of a `@method` entry, with the templates of the class left unqualified in its parameter types:
     * those are printed fully qualified into the docblock of a pseudo-method parsed in the class's template scope,
     * where only an unqualified name is read back as the template. The defaults (PHP expressions) and the return
     * type (printed against the namespace and the imports) keep their qualified names, as an unqualified template
     * there would be read as a same-named import alias.
     *
     * @param list<array{0: string, 1: int, 2?: string}> $tokens
     * @param list<array{0: string, 1: int, 2?: string}> $template_tokens the same entry, tokenized with the
     *                                                                   class's templates in scope
     * @return list<array{0: string, 1: int, 2?: string}>
     * @psalm-pure
     */
    private static function keepTemplatesInParamTypes(array $tokens, array $template_tokens): array
    {
        $depth = 0;
        $in_default = false;

        foreach ($tokens as $i => [$token]) {
            // in a default, `<` and `>` are operators
            if ($token === '(' || $token === '{' || $token === '[' || ($token === '<' && !$in_default)) {
                ++$depth;
            } elseif ($token === ')' || $token === '}' || $token === ']' || ($token === '>' && !$in_default)) {
                if (--$depth === 0) {
                    // the end of the parameter list
                    break;
                }
            } elseif ($depth === 1 && ($token === '=' || $token === ',')) {
                $in_default = $token === '=';
            } elseif ($depth > 0 && !$in_default) {
                $tokens[$i] = $template_tokens[$i];
            }
        }

        return $tokens;
    }

    /**
     * @psalm-pure
     */
    private static function getMethodOffset(Doc $comment, string $method_entry): int
    {
        $lines = explode("\n", $comment->getText());
        $method_offset = 0;

        foreach ($lines as $i => $line) {
            if (str_contains($line, $method_entry)) {
                $method_offset = $i;
                break;
            }
        }

        return $method_offset;
    }
}
