<?php
namespace Psalm\Type;

use Psalm\Exception\TypeParseTreeException;

class ParseTree
{
    /**
     * @var array<int, ParseTree>
     */
    public $children = [];

    /**
     * @var null|ParseTree
     */
    public $parent;

    /**
     * @var bool
     */
    public $possibly_undefined = false;

    /**
     * @param ParseTree|null $parent
     */
    public function __construct(ParseTree $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Create a parse tree from a tokenised type
     *
     * @param  array<string>  $type_tokens
     *
     * @return self
     */
    public static function createFromTokens(array $type_tokens)
    {
        // We construct a parse tree corresponding to the type
        $parse_tree = new ParseTree\Root();

        $current_leaf = $parse_tree;

        for ($i = 0, $c = count($type_tokens); $i < $c; ++$i) {
            $last_token = $i > 0 ? $type_tokens[$i - 1] : null;
            $type_token = $type_tokens[$i];
            $next_token = $i + 1 < $c ? $type_tokens[$i + 1] : null;

            switch ($type_token) {
                case '<':
                case '{':
                    throw new TypeParseTreeException('Unexpected token');

                case '>':
                    do {
                        if ($current_leaf->parent === null) {
                            throw new TypeParseTreeException('Cannot parse generic type');
                        }

                        $current_leaf = $current_leaf->parent;
                    } while (!$current_leaf instanceof ParseTree\GenericTree);

                    break;

                case '}':
                    do {
                        if ($current_leaf->parent === null) {
                            throw new TypeParseTreeException('Cannot parse array type');
                        }

                        $current_leaf = $current_leaf->parent;
                    } while (!$current_leaf instanceof ParseTree\ObjectLikeTree);

                    break;

                case ',':
                    if (!$current_leaf->parent) {
                        throw new TypeParseTreeException('Cannot parse comma without a parent node');
                    }

                    $current_parent = $current_leaf->parent;

                    $context_node = $current_leaf;

                    if ($context_node instanceof ParseTree\GenericTree
                        || $context_node instanceof ParseTree\ObjectLikeTree
                    ) {
                        $context_node = $context_node->parent;
                    }

                    while ($context_node
                        && !$context_node instanceof ParseTree\GenericTree
                        && !$context_node instanceof ParseTree\ObjectLikeTree
                    ) {
                        $context_node = $context_node->parent;
                    }

                    if (!$context_node) {
                        throw new TypeParseTreeException('Cannot parse comma in non-generic/array type');
                    }

                    $current_leaf = $context_node;

                    break;

                case ':':
                    $current_parent = $current_leaf->parent;

                    if ($current_parent && $current_parent instanceof ParseTree\ObjectLikePropertyTree) {
                        continue;
                    }

                    if (!$current_parent) {
                        throw new TypeParseTreeException('Cannot process colon without parent');
                    }

                    if (!$current_leaf instanceof ParseTree\Value) {
                        throw new TypeParseTreeException('Unexpected LHS of property');
                    }

                    $new_parent_leaf = new ParseTree\ObjectLikePropertyTree($current_leaf->value, $current_parent);
                    $new_parent_leaf->possibly_undefined = $last_token === '?';
                    $current_leaf->parent = $new_parent_leaf;

                    array_pop($current_parent->children);
                    $current_parent->children[] = $new_parent_leaf;

                    $current_leaf = $new_parent_leaf;

                    break;

                case '?':
                    break;

                case '|':
                    $current_parent = $current_leaf->parent;

                    if ($current_parent && $current_parent instanceof ParseTree\UnionTree) {
                        $current_leaf = $current_parent;
                        continue;
                    }

                    $new_parent_leaf = new ParseTree\UnionTree($current_parent);
                    $new_parent_leaf->children = [$current_leaf];
                    $current_leaf->parent = $new_parent_leaf;

                    if ($current_parent) {
                        array_pop($current_parent->children);
                        $current_parent->children[] = $new_parent_leaf;
                    } else {
                        $parse_tree = $new_parent_leaf;
                    }

                    $current_leaf = $new_parent_leaf;

                    break;

                case '&':
                    $current_parent = $current_leaf->parent;

                    if ($current_parent && $current_parent instanceof ParseTree\IntersectionTree) {
                        continue;
                    }

                    $new_parent_leaf = new ParseTree\IntersectionTree($current_parent);
                    $new_parent_leaf->children = [$current_leaf];
                    $current_leaf->parent = $new_parent_leaf;

                    if ($current_parent) {
                        array_pop($current_parent->children);
                        $current_parent->children[] = $new_parent_leaf;
                    } else {
                        $parse_tree = $new_parent_leaf;
                    }

                    $current_leaf = $new_parent_leaf;

                    break;

                default:
                    $new_parent = !$current_leaf instanceof ParseTree\Root ? $current_leaf : null;

                    switch ($next_token) {
                        case '<':
                            $new_leaf = new ParseTree\GenericTree(
                                $type_token,
                                $new_parent
                            );
                            ++$i;
                            break;

                        case '{':
                            $new_leaf = new ParseTree\ObjectLikeTree(
                                $type_token,
                                $new_parent
                            );
                            ++$i;
                            break;

                        case '(':
                            throw new TypeParseTreeException('Cannot process bracket yet');

                        default:
                            $new_leaf = new ParseTree\Value(
                                $type_token,
                                $new_parent
                            );
                            break;
                    }

                    if ($current_leaf instanceof ParseTree\Root) {
                        $current_leaf = $parse_tree = $new_leaf;
                        continue;
                    }

                    if ($new_leaf->parent) {
                        $new_leaf->parent->children[] = $new_leaf;
                    }

                    $current_leaf = $new_leaf;
            }
        }

        return $parse_tree;
    }
}
