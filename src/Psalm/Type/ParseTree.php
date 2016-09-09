<?php

namespace Psalm\Type;

class ParseTree
{
    const GENERIC = '<>';
    const UNION = '|';

    /** @var array<ParseTree> */
    public $children;

    /** @var string|null */
    public $value;

    /** @var null|ParseTree */
    public $parent;

    /**
     * @param string|null    $value
     * @param ParseTree|null $parent
     */
    public function __construct($value, ParseTree $parent = null)
    {
        $this->value = $value;
        $this->parent = $parent;
        $this->children = [];
    }

    /**
     * Create a parse tree from a tokenised type
     * @param  array<string>  $type_tokens
     * @return self
     */
    public static function createFromTokens(array $type_tokens)
    {
        // We construct a parse tree corresponding to the type
        $parse_tree = new self(null, null);

        $current_parent = null;
        $current_leaf = $parse_tree;

        while ($type_tokens) {
            $type_token = array_shift($type_tokens);

            switch ($type_token) {
                case '<':
                    $current_parent = $current_leaf->parent;
                    $new_parent_leaf = new self(ParseTree::GENERIC, $current_parent);
                    $new_parent_leaf->children = [$current_leaf];
                    $current_leaf->parent = $new_parent_leaf;

                    if ($current_parent) {
                        array_pop($current_parent->children);
                        $current_parent->children[] = $new_parent_leaf;
                    }
                    else {
                        $parse_tree = $new_parent_leaf;
                    }

                    break;

                case '>':
                    do {
                        if ($current_leaf->parent === null) {
                            throw new \InvalidArgumentException('Cannot parse generic type');
                        }

                        $current_leaf = $current_leaf->parent;
                    }
                    while ($current_leaf->value !== self::GENERIC);

                    break;

                case ',':
                    $current_parent = $current_leaf->parent;
                    if (!$current_parent || $current_parent->value !== self::GENERIC) {
                        throw new \InvalidArgumentException('Cannot parse comma in non-generic type');
                    }

                    break;

                case '|':
                    $current_parent = $current_leaf->parent;

                    if ($current_parent && $current_parent->value === ParseTree::UNION) {
                        continue;
                    }

                    $new_parent_leaf = new self(self::UNION, $current_parent);
                    $new_parent_leaf->children = [$current_leaf];
                    $current_leaf->parent = $new_parent_leaf;

                    if ($current_parent) {
                        array_pop($current_parent->children);
                        $current_parent->children[] = $new_parent_leaf;
                    }
                    else {
                        $parse_tree = $new_parent_leaf;
                    }

                    break;

                default:
                    if ($current_leaf->value === null) {
                        $current_leaf->value = $type_token;
                        continue;
                    }

                    $new_leaf = new self($type_token, $current_leaf->parent);
                    $current_leaf->parent->children[] = $new_leaf;

                    $current_leaf = $new_leaf;
            }
        }

        return $parse_tree;
    }
}
