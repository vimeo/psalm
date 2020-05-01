<?php
namespace Psalm\Internal\Type;

use function array_pop;
use function count;
use function in_array;
use function preg_match;
use Psalm\Exception\TypeParseTreeException;
use function strlen;
use function strtolower;

/**
 * @internal
 */
class ParseTree
{
    /**
     * @var list<ParseTree>
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

    public function __destruct()
    {
        $this->parent = null;
    }

    public function cleanParents() : void
    {
        foreach ($this->children as $child) {
            $child->cleanParents();
        }

        $this->parent = null;
    }

    /**
     * Create a parse tree from a tokenised type
     *
     * @param  array<int, array{0: string, 1: int}>  $type_tokens
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

            switch ($type_token[0]) {
                case '<':
                case '{':
                case ']':
                    throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);

                case '[':
                    if ($current_leaf instanceof ParseTree\Root) {
                        throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);
                    }

                    $indexed_access = false;

                    if (!$next_token || $next_token[0] !== ']') {
                        $next_next_token = $i + 2 < $c ? $type_tokens[$i + 2] : null;

                        if ($next_next_token !== null && $next_next_token[0] === ']') {
                            $indexed_access = true;
                            ++$i;
                        } else {
                            throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);
                        }
                    }

                    $current_parent = $current_leaf->parent;

                    if ($indexed_access) {
                        if ($next_token === null) {
                            throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);
                        }

                        $new_parent_leaf = new ParseTree\IndexedAccessTree($next_token[0], $current_parent);
                    } else {
                        if ($current_leaf instanceof ParseTree\ObjectLikePropertyTree) {
                            throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);
                        }

                        $new_parent_leaf = new ParseTree\GenericTree('array', $current_parent);
                    }

                    $current_leaf->parent = $new_parent_leaf;
                    $new_parent_leaf->children = [$current_leaf];

                    if ($current_parent) {
                        array_pop($current_parent->children);
                        $current_parent->children[] = $new_parent_leaf;
                    } else {
                        $parse_tree = $new_parent_leaf;
                    }

                    $current_leaf = $new_parent_leaf;
                    ++$i;
                    break;

                case '(':
                    if ($current_leaf instanceof ParseTree\Value) {
                        throw new TypeParseTreeException('Unrecognised token (');
                    }

                    $new_parent = !$current_leaf instanceof ParseTree\Root ? $current_leaf : null;

                    $new_leaf = new ParseTree\EncapsulationTree(
                        $new_parent
                    );

                    if ($current_leaf instanceof ParseTree\Root) {
                        $current_leaf = $parse_tree = $new_leaf;
                        break;
                    }

                    if ($new_leaf->parent) {
                        $new_leaf->parent->children[] = $new_leaf;
                    }

                    $current_leaf = $new_leaf;
                    break;

                case ')':
                    if ($last_token !== null
                        && $last_token[0] === '('
                        && $current_leaf instanceof ParseTree\CallableTree
                    ) {
                        break;
                    }

                    do {
                        if ($current_leaf->parent === null) {
                            break;
                        }

                        $current_leaf = $current_leaf->parent;
                    } while (!$current_leaf instanceof ParseTree\EncapsulationTree
                        && !$current_leaf instanceof ParseTree\CallableTree
                        && !$current_leaf instanceof ParseTree\MethodTree);

                    if ($current_leaf instanceof ParseTree\EncapsulationTree
                        || $current_leaf instanceof ParseTree\CallableTree
                    ) {
                        $current_leaf->terminated = true;
                    }

                    break;

                case '>':
                    do {
                        if ($current_leaf->parent === null) {
                            throw new TypeParseTreeException('Cannot parse generic type');
                        }

                        $current_leaf = $current_leaf->parent;
                    } while (!$current_leaf instanceof ParseTree\GenericTree);

                    $current_leaf->terminated = true;

                    break;

                case '}':
                    do {
                        if ($current_leaf->parent === null) {
                            throw new TypeParseTreeException('Cannot parse array type');
                        }

                        $current_leaf = $current_leaf->parent;
                    } while (!$current_leaf instanceof ParseTree\ObjectLikeTree);

                    $current_leaf->terminated = true;

                    break;

                case ',':
                    if ($current_leaf instanceof ParseTree\Root) {
                        throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);
                    }

                    if (!$current_leaf->parent) {
                        throw new TypeParseTreeException('Cannot parse comma without a parent node');
                    }

                    $context_node = $current_leaf;

                    if ($context_node instanceof ParseTree\GenericTree
                        || $context_node instanceof ParseTree\ObjectLikeTree
                        || $context_node instanceof ParseTree\CallableTree
                        || $context_node instanceof ParseTree\MethodTree
                    ) {
                        $context_node = $context_node->parent;
                    }

                    while ($context_node
                        && !$context_node instanceof ParseTree\GenericTree
                        && !$context_node instanceof ParseTree\ObjectLikeTree
                        && !$context_node instanceof ParseTree\CallableTree
                        && !$context_node instanceof ParseTree\MethodTree
                    ) {
                        $context_node = $context_node->parent;
                    }

                    if (!$context_node) {
                        throw new TypeParseTreeException('Cannot parse comma in non-generic/array type');
                    }

                    $current_leaf = $context_node;

                    break;

                case '...':
                case '=':
                    if ($last_token && ($last_token[0] === '...' || $last_token[0] === '=')) {
                        throw new TypeParseTreeException('Cannot have duplicate tokens');
                    }

                    $current_parent = $current_leaf->parent;

                    if ($current_leaf instanceof ParseTree\MethodTree && $type_token[0] === '...') {
                        self::createMethodParam($current_leaf, $current_leaf, $type_tokens, $type_token, $i);
                        break;
                    }

                    while ($current_parent
                        && !$current_parent instanceof ParseTree\CallableTree
                        && !$current_parent instanceof ParseTree\CallableParamTree
                    ) {
                        $current_leaf = $current_parent;
                        $current_parent = $current_parent->parent;
                    }

                    if (!$current_parent || !$current_leaf) {
                        if ($current_leaf instanceof ParseTree\CallableTree
                            && $type_token[0] === '...'
                        ) {
                            $current_parent = $current_leaf;
                        } else {
                            throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);
                        }
                    }

                    if ($current_parent instanceof ParseTree\CallableParamTree) {
                        throw new TypeParseTreeException('Cannot have variadic param with a default');
                    }

                    $new_leaf = new ParseTree\CallableParamTree($current_parent);
                    $new_leaf->has_default = $type_token[0] === '=';
                    $new_leaf->variadic = $type_token[0] === '...';

                    if ($current_parent !== $current_leaf) {
                        $new_leaf->children = [$current_leaf];
                        $current_leaf->parent = $new_leaf;

                        array_pop($current_parent->children);
                        $current_parent->children[] = $new_leaf;
                    } else {
                        $current_parent->children[] = $new_leaf;
                    }

                    $current_leaf = $new_leaf;

                    break;

                case ':':
                    if ($current_leaf instanceof ParseTree\Root) {
                        throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);
                    }

                    $current_parent = $current_leaf->parent;

                    if ($current_leaf instanceof ParseTree\CallableTree) {
                        $new_parent_leaf = new ParseTree\CallableWithReturnTypeTree($current_parent);
                        $current_leaf->parent = $new_parent_leaf;
                        $new_parent_leaf->children = [$current_leaf];

                        if ($current_parent) {
                            array_pop($current_parent->children);
                            $current_parent->children[] = $new_parent_leaf;
                        } else {
                            $parse_tree = $new_parent_leaf;
                        }

                        $current_leaf = $new_parent_leaf;
                        break;
                    }

                    if ($current_leaf instanceof ParseTree\MethodTree) {
                        $new_parent_leaf = new ParseTree\MethodWithReturnTypeTree($current_parent);
                        $current_leaf->parent = $new_parent_leaf;
                        $new_parent_leaf->children = [$current_leaf];

                        if ($current_parent) {
                            array_pop($current_parent->children);
                            $current_parent->children[] = $new_parent_leaf;
                        } else {
                            $parse_tree = $new_parent_leaf;
                        }

                        $current_leaf = $new_parent_leaf;
                        break;
                    }

                    if ($current_parent && $current_parent instanceof ParseTree\ObjectLikePropertyTree) {
                        break;
                    }

                    while (($current_parent instanceof ParseTree\UnionTree
                            || $current_parent instanceof ParseTree\CallableWithReturnTypeTree)
                        && $current_leaf->parent
                    ) {
                        $current_leaf = $current_leaf->parent;
                        $current_parent = $current_leaf->parent;
                    }

                    if ($current_parent && $current_parent instanceof ParseTree\ConditionalTree) {
                        if (count($current_parent->children) > 1) {
                            throw new TypeParseTreeException('Cannot process colon in conditional twice');
                        }

                        $current_leaf = $current_parent;
                        $current_parent = $current_parent->parent;
                        break;
                    }

                    if (!$current_parent) {
                        throw new TypeParseTreeException('Cannot process colon without parent');
                    }

                    if (!$current_leaf instanceof ParseTree\Value) {
                        throw new TypeParseTreeException('Unexpected LHS of property');
                    }

                    if (!$current_parent instanceof ParseTree\ObjectLikeTree) {
                        throw new TypeParseTreeException('Saw : outside of object-like array');
                    }

                    $new_parent_leaf = new ParseTree\ObjectLikePropertyTree($current_leaf->value, $current_parent);
                    $new_parent_leaf->possibly_undefined = $last_token !== null && $last_token[0] === '?';
                    $current_leaf->parent = $new_parent_leaf;

                    array_pop($current_parent->children);
                    $current_parent->children[] = $new_parent_leaf;

                    $current_leaf = $new_parent_leaf;

                    break;

                case ' ':
                    if ($current_leaf instanceof ParseTree\Root) {
                        throw new TypeParseTreeException('Unexpected space');
                    }

                    if ($current_leaf instanceof ParseTree\ObjectLikeTree) {
                        break;
                    }

                    $current_parent = $current_leaf->parent;

                    if ($current_parent instanceof ParseTree\CallableTree) {
                        break;
                    }

                    while ($current_parent && !$current_parent instanceof ParseTree\MethodTree) {
                        $current_leaf = $current_parent;
                        $current_parent = $current_parent->parent;
                    }

                    if (!$current_parent instanceof ParseTree\MethodTree || !$next_token) {
                        throw new TypeParseTreeException('Unexpected space');
                    }

                    ++$i;

                    self::createMethodParam($current_leaf, $current_parent, $type_tokens, $next_token, $i);

                    break;

                case '?':
                    if ($next_token === null || $next_token[0] !== ':') {
                        while (($current_leaf instanceof ParseTree\Value
                                || $current_leaf instanceof ParseTree\UnionTree
                                || ($current_leaf instanceof ParseTree\ObjectLikeTree
                                    && $current_leaf->terminated)
                                || ($current_leaf instanceof ParseTree\GenericTree
                                    && $current_leaf->terminated)
                                || ($current_leaf instanceof ParseTree\EncapsulationTree
                                    && $current_leaf->terminated)
                                || ($current_leaf instanceof ParseTree\CallableTree
                                    && $current_leaf->terminated)
                                || $current_leaf instanceof ParseTree\IntersectionTree)
                            && $current_leaf->parent
                        ) {
                            $current_leaf = $current_leaf->parent;
                        }

                        if ($current_leaf instanceof ParseTree\TemplateIsTree && $current_leaf->parent) {
                            $current_parent = $current_leaf->parent;

                            $new_leaf = new ParseTree\ConditionalTree(
                                $current_leaf,
                                $current_leaf->parent
                            );

                            $current_leaf->parent = $new_leaf;

                            array_pop($current_parent->children);
                            $current_parent->children[] = $new_leaf;
                            $current_leaf = $new_leaf;
                        } else {
                            $new_parent = !$current_leaf instanceof ParseTree\Root ? $current_leaf : null;

                            $new_leaf = new ParseTree\NullableTree(
                                $new_parent
                            );

                            if ($current_leaf instanceof ParseTree\Root) {
                                $current_leaf = $parse_tree = $new_leaf;
                                break;
                            }

                            if ($new_leaf->parent) {
                                $new_leaf->parent->children[] = $new_leaf;
                            }

                            $current_leaf = $new_leaf;
                        }
                    }

                    break;

                case '|':
                    if ($current_leaf instanceof ParseTree\Root) {
                        throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);
                    }

                    $current_parent = $current_leaf->parent;

                    if ($current_parent instanceof ParseTree\CallableWithReturnTypeTree) {
                        $current_leaf = $current_parent;
                        $current_parent = $current_parent->parent;
                    }

                    if ($current_parent instanceof ParseTree\NullableTree) {
                        $current_leaf = $current_parent;
                        $current_parent = $current_parent->parent;
                    }

                    if ($current_leaf instanceof ParseTree\UnionTree) {
                        throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);
                    }

                    if ($current_parent && $current_parent instanceof ParseTree\UnionTree) {
                        $current_leaf = $current_parent;
                        break;
                    }

                    if ($current_parent && $current_parent instanceof ParseTree\IntersectionTree) {
                        $current_leaf = $current_parent;
                        $current_parent = $current_leaf->parent;
                    }

                    if ($current_parent instanceof ParseTree\TemplateIsTree) {
                        $new_parent_leaf = new ParseTree\UnionTree($current_leaf);
                        $new_parent_leaf->children = [$current_leaf];
                        $new_parent_leaf->parent = $current_parent;
                        $current_leaf->parent = $new_parent_leaf;
                    } else {
                        $new_parent_leaf = new ParseTree\UnionTree($current_parent);
                        $new_parent_leaf->children = [$current_leaf];
                        $current_leaf->parent = $new_parent_leaf;
                    }

                    if ($current_parent) {
                        array_pop($current_parent->children);
                        $current_parent->children[] = $new_parent_leaf;
                    } else {
                        $parse_tree = $new_parent_leaf;
                    }

                    $current_leaf = $new_parent_leaf;

                    break;

                case '&':
                    if ($current_leaf instanceof ParseTree\Root) {
                        throw new TypeParseTreeException(
                            'Unexpected &'
                        );
                    }

                    $current_parent = $current_leaf->parent;

                    if ($current_leaf instanceof ParseTree\MethodTree) {
                        self::createMethodParam($current_leaf, $current_leaf, $type_tokens, $type_token, $i);
                        break;
                    }

                    if ($current_parent && $current_parent instanceof ParseTree\IntersectionTree) {
                        $current_leaf = $current_parent;
                        break;
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

                case 'is':
                case 'as':
                    if ($i > 0) {
                        $current_parent = $current_leaf->parent;

                        if ($current_parent) {
                            array_pop($current_parent->children);
                        }

                        if ($type_token[0] === 'as') {
                            if (!$current_leaf instanceof ParseTree\Value
                                || !$current_parent instanceof ParseTree\GenericTree
                                || !$next_token
                            ) {
                                throw new TypeParseTreeException('Unexpected token ' . $type_token[0]);
                            }

                            $current_leaf = new ParseTree\TemplateAsTree(
                                $current_leaf->value,
                                $next_token[0],
                                $current_parent
                            );

                            $current_parent->children[] = $current_leaf;
                            ++$i;
                        } elseif ($current_leaf instanceof ParseTree\Value) {
                            $current_leaf = new ParseTree\TemplateIsTree(
                                $current_leaf->value,
                                $current_parent
                            );

                            if ($current_parent) {
                                $current_parent->children[] = $current_leaf;
                            }
                        }

                        break;
                    }

                    // falling through for methods named 'as' or 'is'

                default:
                    $new_parent = !$current_leaf instanceof ParseTree\Root ? $current_leaf : null;

                    if ($current_leaf instanceof ParseTree\MethodTree && $type_token[0][0] === '$') {
                        self::createMethodParam($current_leaf, $current_leaf, $type_tokens, $type_token, $i);
                        break;
                    }

                    switch ($next_token[0] ?? null) {
                        case '<':
                            $new_leaf = new ParseTree\GenericTree(
                                $type_token[0],
                                $new_parent
                            );
                            ++$i;
                            break;

                        case '{':
                            $new_leaf = new ParseTree\ObjectLikeTree(
                                $type_token[0],
                                $new_parent
                            );
                            ++$i;
                            break;

                        case '(':
                            if (in_array(strtolower($type_token[0]), ['closure', 'callable', '\closure'], true)) {
                                $new_leaf = new ParseTree\CallableTree(
                                    $type_token[0],
                                    $new_parent
                                );
                            } elseif ($type_token[0] !== 'array'
                                && $type_token[0][0] !== '\\'
                                && $current_leaf instanceof ParseTree\Root
                            ) {
                                $new_leaf = new ParseTree\MethodTree(
                                    $type_token[0],
                                    $new_parent
                                );
                            } else {
                                throw new TypeParseTreeException(
                                    'Bracket must be preceded by “Closure”, “callable” or a valid @method name'
                                );
                            }

                            ++$i;
                            break;

                        case '::':
                            $nexter_token = $i + 2 < $c ? $type_tokens[$i + 2] : null;

                            if (!$nexter_token
                                || (!preg_match('/^([a-zA-Z_][a-zA-Z_0-9]*\*?|\*)$/', $nexter_token[0])
                                    && strtolower($nexter_token[0]) !== 'class')
                            ) {
                                throw new TypeParseTreeException(
                                    'Invalid class constant ' . ($nexter_token[0] ?? '<empty>')
                                );
                            }

                            $new_leaf = new ParseTree\Value(
                                $type_token[0] . '::' . $nexter_token[0],
                                $type_token[1],
                                $type_token[1] + 2 + strlen($nexter_token[0]),
                                $new_parent
                            );

                            $i += 2;

                            break;

                        default:
                            if ($type_token[0] === '$this') {
                                $type_token[0] = 'static';
                            }

                            $new_leaf = new ParseTree\Value(
                                $type_token[0],
                                $type_token[1],
                                $type_token[1] + strlen($type_token[0]),
                                $new_parent
                            );
                            break;
                    }

                    if ($current_leaf instanceof ParseTree\Root) {
                        $current_leaf = $parse_tree = $new_leaf;
                        break;
                    }

                    if ($new_leaf->parent) {
                        $new_leaf->parent->children[] = $new_leaf;
                    }

                    $current_leaf = $new_leaf;
                    break;
            }
        }

        $parse_tree->cleanParents();

        if ($current_leaf !== $parse_tree
            && ($parse_tree instanceof ParseTree\GenericTree
                || $parse_tree instanceof ParseTree\CallableTree
                || $parse_tree instanceof ParseTree\ObjectLikeTree)
        ) {
            throw new TypeParseTreeException(
                'Unterminated bracket'
            );
        }

        return $parse_tree;
    }

    /**
     * @param  ParseTree          &$current_leaf
     * @param  ParseTree          $current_parent
     * @param  array<int, array{0: string, 1: int}> $type_tokens
     * @param  array{0: string, 1: int} $current_token
     * @param  int                &$i
     *
     * @return void
     */
    private static function createMethodParam(
        ParseTree &$current_leaf,
        ParseTree $current_parent,
        array $type_tokens,
        array $current_token,
        &$i
    ) {
        $byref = false;
        $variadic = false;
        $has_default = false;
        $default = '';

        $c = count($type_tokens);

        if ($current_token[0] === '&') {
            throw new TypeParseTreeException('Magic args cannot be passed by reference');
        }

        if ($current_token[0] === '...') {
            $variadic = true;

            ++$i;
            $current_token = $i < $c ? $type_tokens[$i] : null;
        }

        if (!$current_token || $current_token[0][0] !== '$') {
            throw new TypeParseTreeException('Unexpected token after space');
        }

        $new_parent_leaf = new ParseTree\MethodParamTree(
            $current_token[0],
            $byref,
            $variadic,
            $current_parent
        );

        for ($j = $i + 1; $j < $c; ++$j) {
            $ahead_type_token = $type_tokens[$j];

            if ($ahead_type_token[0] === ','
                || ($ahead_type_token[0] === ')' && $type_tokens[$j - 1][0] !== '(')
            ) {
                $i = $j - 1;
                break;
            }

            if ($has_default) {
                $default .= $ahead_type_token[0];
            }

            if ($ahead_type_token[0] === '=') {
                $has_default = true;
                continue;
            }

            if ($j === $c - 1) {
                throw new TypeParseTreeException('Unterminated method');
            }
        }

        $new_parent_leaf->default = $default;

        if ($current_leaf !== $current_parent) {
            $new_parent_leaf->children = [$current_leaf];
            $current_leaf->parent = $new_parent_leaf;
            array_pop($current_parent->children);
        }

        $current_parent->children[] = $new_parent_leaf;

        $current_leaf = $new_parent_leaf;
    }
}
