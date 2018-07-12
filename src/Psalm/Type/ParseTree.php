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
     * @param  array<int, string>  $type_tokens
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
                case ']':
                    throw new TypeParseTreeException('Unexpected token ' . $type_token);

                case '[':
                    if ($current_leaf instanceof ParseTree\Root) {
                        throw new TypeParseTreeException('Unexpected token ' . $type_token);
                    }

                    if ($next_token !== ']') {
                        throw new TypeParseTreeException('Unexpected token ' . $type_token);
                    }

                    $current_parent = $current_leaf->parent;

                    $new_parent_leaf = new ParseTree\GenericTree('array', $current_parent);
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
                    if ($last_token === '(' && $current_leaf instanceof ParseTree\CallableTree) {
                        break;
                    }

                    do {
                        if ($current_leaf->parent === null
                            || $current_leaf->parent instanceof ParseTree\CallableWithReturnTypeTree
                            || $current_leaf->parent instanceof ParseTree\MethodWithReturnTypeTree
                        ) {
                            break;
                        }

                        $current_leaf = $current_leaf->parent;
                    } while (!$current_leaf instanceof ParseTree\EncapsulationTree
                        && !$current_leaf instanceof ParseTree\CallableTree
                        && !$current_leaf instanceof ParseTree\MethodTree);

                    break;

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
                    if ($current_leaf instanceof ParseTree\Root) {
                        throw new TypeParseTreeException('Unexpected token ' . $type_token);
                    }

                    if (!$current_leaf->parent) {
                        throw new TypeParseTreeException('Cannot parse comma without a parent node');
                    }

                    $current_parent = $current_leaf->parent;

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
                    if ($last_token === '...' || $last_token === '=') {
                        throw new TypeParseTreeException('Cannot have duplicate tokens');
                    }

                    $current_parent = $current_leaf->parent;

                    if ($current_leaf instanceof ParseTree\MethodTree && $type_token === '...') {
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
                        throw new TypeParseTreeException('Unexpected token ' . $type_token);
                    }

                    if ($current_parent instanceof ParseTree\CallableParamTree) {
                        throw new TypeParseTreeException('Cannot have variadic param with a default');
                    }

                    $new_leaf = new ParseTree\CallableParamTree($current_parent);
                    $new_leaf->has_default = $type_token === '=';
                    $new_leaf->variadic = $type_token === '...';
                    $new_leaf->children = [$current_leaf];

                    $current_leaf->parent = $new_leaf;

                    array_pop($current_parent->children);
                    $current_parent->children[] = $new_leaf;

                    $current_leaf = $new_leaf;

                    break;

                case ':':
                    if ($current_leaf instanceof ParseTree\Root) {
                        throw new TypeParseTreeException('Unexpected token ' . $type_token);
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
                    $new_parent_leaf->possibly_undefined = $last_token === '?';
                    $current_leaf->parent = $new_parent_leaf;

                    array_pop($current_parent->children);
                    $current_parent->children[] = $new_parent_leaf;

                    $current_leaf = $new_parent_leaf;

                    break;

                case ' ':
                    if ($current_leaf instanceof ParseTree\Root) {
                        throw new TypeParseTreeException('Unexpected space');
                    }

                    $current_parent = $current_leaf->parent;

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
                    if ($next_token !== ':') {
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

                    break;

                case '|':
                    if ($current_leaf instanceof ParseTree\Root) {
                        throw new TypeParseTreeException('Unexpected token ' . $type_token);
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
                        throw new TypeParseTreeException('Unexpected token ' . $type_token);
                    }

                    if ($current_parent && $current_parent instanceof ParseTree\UnionTree) {
                        $current_leaf = $current_parent;
                        break;
                    }

                    if ($current_parent && $current_parent instanceof ParseTree\IntersectionTree) {
                        $current_leaf = $current_parent;
                        $current_parent = $current_leaf->parent;
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

                default:
                    $new_parent = !$current_leaf instanceof ParseTree\Root ? $current_leaf : null;

                    if ($current_leaf instanceof ParseTree\MethodTree && $type_token[0] === '$') {
                        self::createMethodParam($current_leaf, $current_leaf, $type_tokens, $type_token, $i);
                        break;
                    }

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
                            if (in_array(strtolower($type_token), ['closure', 'callable', '\closure'])) {
                                $new_leaf = new ParseTree\CallableTree(
                                    $type_token,
                                    $new_parent
                                );
                            } elseif ($type_token !== 'array'
                                && $type_token[0] !== '\\'
                                && $current_leaf instanceof ParseTree\Root
                            ) {
                                $new_leaf = new ParseTree\MethodTree(
                                    $type_token,
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

                            if (!$nexter_token || !preg_match('/^[A-Z_]+$/', $nexter_token)) {
                                throw new TypeParseTreeException(
                                    'Invalid class constant ' . $nexter_token
                                );
                            }

                            $new_leaf = new ParseTree\Value(
                                $type_token . '::' . $nexter_token,
                                $new_parent
                            );

                            $i += 2;

                            break;

                        default:
                            if ($type_token === '$this') {
                                $type_token = 'static';
                            }

                            $new_leaf = new ParseTree\Value(
                                $type_token,
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

        return $parse_tree;
    }

    /**
     * @param  ParseTree          &$current_leaf
     * @param  ParseTree          $current_parent
     * @param  array<int, string> $type_tokens
     * @param  string             $current_token
     * @param  int                &$i
     *
     * @return void
     */
    private static function createMethodParam(
        ParseTree &$current_leaf,
        ParseTree $current_parent,
        array $type_tokens,
        $current_token,
        &$i
    ) {
        $byref = false;
        $variadic = false;
        $has_default = false;
        $default = '';

        $c = count($type_tokens);

        if ($current_token === '&') {
            throw new TypeParseTreeException('Magic args cannot be passed by reference');
        }

        if ($current_token === '...') {
            $variadic = true;

            ++$i;
            $current_token = $i < $c ? $type_tokens[$i] : null;
        }

        if (!$current_token || $current_token[0] !== '$') {
            throw new TypeParseTreeException('Unexpected token after space ' . $current_token);
        }

        $new_parent_leaf = new ParseTree\MethodParamTree(
            $current_token,
            $byref,
            $variadic,
            $current_parent
        );

        for ($j = $i + 1; $j < $c; ++$j) {
            $ahead_type_token = $type_tokens[$j];

            if ($ahead_type_token === ','
                || ($ahead_type_token === ')' && $type_tokens[$j - 1] !== '(')
            ) {
                $i = $j - 1;
                break;
            }

            if ($has_default) {
                $default .= $ahead_type_token;
            }

            if ($ahead_type_token === '=') {
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
