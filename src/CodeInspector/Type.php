<?php

namespace CodeInspector;

use CodeInspector\Type\Atomic;
use CodeInspector\Type\Generic;
use CodeInspector\Type\Union;
use CodeInspector\Type\ParseTree;

abstract class Type
{
    /**
     * Parses a string type representation
     * @param  string $string
     * @return self
     */
    public static function parseString($type_string)
    {
        $type_tokens = TypeChecker::tokenize($type_string);

        if (count($type_tokens) === 1) {
            return new Atomic($type_tokens[0]);
        }

        // We construct a parse tree corresponding to the type
        $parse_tree = new ParseTree(null, null);

        $current_leaf = $parse_tree;

        while ($type_tokens) {
            $type_token = array_shift($type_tokens);

            switch ($type_token) {
                case '<':
                    $current_parent = $current_leaf->parent;
                    $new_parent_leaf = new ParseTree(ParseTree::GENERIC, $current_parent);
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
                    while ($current_leaf->value !== ParseTree::GENERIC) {
                        if ($current_leaf->parent === null) {
                            throw new \InvalidArgumentException('Cannot parse generic type');
                        }

                        $current_leaf = $current_leaf->parent;
                    }

                    break;

                case ',':
                    if ($current_parent->value !== ParseTree::GENERIC) {
                        throw new \InvalidArgumentException('Cannot parse comma in non-generic type');
                    }
                    break;

                case '|':
                    $current_parent = $current_leaf->parent;

                    if ($current_parent && $current_parent->value === ParseTree::UNION) {
                        continue;
                    }

                    $new_parent_leaf = new ParseTree(ParseTree::UNION, $current_parent);
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

                    $new_leaf = new ParseTree($type_token, $current_leaf->parent);
                    $current_leaf->parent->children[] = $new_leaf;

                    $current_leaf = $new_leaf;
            }
        }

        return self::getTypeFromTree($parse_tree);
    }

    private static function getTypeFromTree(ParseTree $parse_tree)
    {
        if ($parse_tree->value === ParseTree::GENERIC) {
            $generic_type = array_shift($parse_tree->children);

            $generic_params = array_map(
                function (ParseTree $child_tree) {
                    return self::getTypeFromTree($child_tree);
                },
                $parse_tree->children
            );

            if (!$generic_params) {
                throw new \InvalidArgumentException('No generic params provided for type');
            }

            return new Generic($generic_type->value, $generic_params);
        }

        if ($parse_tree->value === ParseTree::UNION) {
            $union_types = array_map(
                function (ParseTree $child_tree) {
                    return self::getTypeFromTree($child_tree);
                },
                $parse_tree->children
            );

            return new Union($union_types);
        }

        return new Atomic($parse_tree->value);
    }
}
