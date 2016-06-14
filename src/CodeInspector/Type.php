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
    public static function parseString($type_string, $enclose_with_union = true)
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

        $parsed_type = self::getTypeFromTree($parse_tree);

        if ($enclose_with_union && !($parsed_type instanceof Union)) {
            $parsed_type = new Union([$parsed_type]);
        }

        return $parsed_type;
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

            $is_empty = count($generic_params) === 1 && $generic_params[0]->value === 'empty';

            return new Generic($generic_type->value, $generic_params, $is_empty);
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

    public static function getInt($enclose_with_union = true)
    {
        $type = new Atomic('int');

        if ($enclose_with_union) {
            return new Union([$type]);
        }

        return $type;
    }

    public static function getString($enclose_with_union = true)
    {
        $type = new Atomic('string');

        if ($enclose_with_union) {
            return new Union([$type]);
        }

        return $type;
    }

    public static function getMixed($enclose_with_union = true)
    {
        $type = new Atomic('mixed');

        if ($enclose_with_union) {
            return new Union([$type]);
        }

        return $type;
    }

    public function getBool($enclose_with_union = true)
    {
        $type = new Atomic('bool');

        if ($enclose_with_union) {
            return new Union([$type]);
        }

        return $type;
    }

    public static function getDouble($enclose_with_union = true)
    {
        $type = new Atomic('double');

        if ($enclose_with_union) {
            return new Union([$type]);
        }

        return $type;
    }

    public static function getFloat($enclose_with_union = true)
    {
        $type = new Atomic('float');

        if ($enclose_with_union) {
            return new Union([$type]);
        }

        return $type;
    }

    public static function getObject($enclose_with_union = true)
    {
        $type = new Atomic('object');

        if ($enclose_with_union) {
            return new Union([$type]);
        }

        return $type;
    }

    public static function getArray($enclose_with_union = true)
    {
        $type = new Atomic('array');

        if ($enclose_with_union) {
            return new Union([$type]);
        }

        return $type;
    }

    public function isMixed()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'mixed';
        }

        if ($this instanceof Union) {
            return $this->types[0]->isMixed();
        }
    }

    public static function combineTypes(Union $type_1, Union $type_2)
    {
        if (!$type_1->isMixed && !$type_2->isMixed()) {
            $mapped_types = [];

            foreach ($type_1->types as $type) {
                $mapped_types[(string) $type] = $type;
            }

            foreach ($type_2->types as $type) {
                $mapped_types[(string) $type] = $type;
            }

            $new_types = array_values($mapped_types);
            return new Union($new_types);
        }

        return Type::getMixed();
    }
}
