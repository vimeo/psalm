<?php

namespace Psalm;

use Psalm\Type\Atomic;
use Psalm\Type\Generic;
use Psalm\Type\Union;
use Psalm\Type\ParseTree;

abstract class Type
{
    /**
     * Parses a string type representation
     * @param  string $type_string
     * @return Union
     */
    public static function parseString($type_string)
    {
        if (strpos($type_string, '[') !== false) {
            $type_string = self::convertSquareBrackets($type_string);
        }

        $type_string = str_replace('?', 'null|', $type_string);

        $type_tokens = self::tokenize($type_string);

        if (count($type_tokens) === 1) {
            $type_tokens[0] = self::fixScalarTerms($type_tokens[0]);

            return new Union([new Atomic($type_tokens[0])]);
        }

        // We construct a parse tree corresponding to the type
        $parse_tree = new ParseTree(null, null);

        $current_parent = null;
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
                    if (!$current_parent || $current_parent->value !== ParseTree::GENERIC) {
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

        if (!($parsed_type instanceof Union)) {
            $parsed_type = new Union([$parsed_type]);
        }

        return $parsed_type;
    }

    public static function fixScalarTerms($type_string)
    {
        if (in_array(
            strtolower($type_string),
            ['numeric', 'int', 'float', 'string', 'bool', 'true', 'false', 'null', 'array', 'object', 'mixed', 'resource']
        )) {
            return strtolower($type_string);
        }
        elseif ($type_string === 'boolean') {
            return 'bool';
        }
        elseif ($type_string === 'integer') {
            return 'int';
        }
        elseif ($type_string === 'double' || $type_string === 'real') {
            return 'float';
        }

        return $type_string;
    }

    private static function getTypeFromTree(ParseTree $parse_tree)
    {
        if ($parse_tree->value === ParseTree::GENERIC) {
            $generic_type = array_shift($parse_tree->children);

            $generic_params = array_map(
                function (ParseTree $child_tree) {
                    $tree_type = self::getTypeFromTree($child_tree);
                    return $tree_type instanceof Union ? $tree_type : new Union([$tree_type]);
                },
                $parse_tree->children
            );

            if (!$generic_params) {
                throw new \InvalidArgumentException('No generic params provided for type');
            }

            return new Generic(self::fixScalarTerms($generic_type->value), $generic_params);
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

        return new Atomic(self::fixScalarTerms($parse_tree->value));
    }

    /**
     * @return array<string>
     */
    public static function tokenize($return_type)
    {
        $return_type_tokens = [''];
        $was_char = false;

        foreach (str_split($return_type) as $char) {
            if ($was_char) {
                $return_type_tokens[] = '';
            }

            if ($char === '<' || $char === '>' || $char === '|' || $char === '?') {
                if ($return_type_tokens[count($return_type_tokens) - 1] === '') {
                    $return_type_tokens[count($return_type_tokens) - 1] = $char;
                }
                else {
                    $return_type_tokens[] = $char;
                }

                $was_char = true;
            }
            else {
                $return_type_tokens[count($return_type_tokens) - 1] .= $char;
                $was_char = false;
            }
        }

        return $return_type_tokens;
    }

    public static function convertSquareBrackets($type)
    {
        return preg_replace_callback(
            '/([a-zA-Z\<\>\\\\_]+)((\[\])+)/',
            function ($matches) {
                $inner_type = $matches[1];

                $dimensionality = strlen($matches[2]) / 2;

                for ($i = 0; $i < $dimensionality; $i++) {
                    $inner_type = 'array<' . $inner_type . '>';
                }

                return $inner_type;
            },
            $type
        );
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

    public static function getNull($enclose_with_union = true)
    {
        $type = new Atomic('null');

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

    public static function getBool($enclose_with_union = true)
    {
        $type = new Atomic('bool');

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

    public static function getVoid($enclose_with_union = true)
    {
        $type = new Atomic('void');

        if ($enclose_with_union) {
            return new Union([$type]);
        }

        return $type;
    }

    public static function getFalse($enclose_with_union = true)
    {
        $type = new Atomic('false');

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
            return isset($this->types['mixed']);
        }
    }

    public function isNull()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'null';
        }

        if ($this instanceof Union) {
            return count($this->types) === 1 && isset($this->types['null']);
        }
    }

    public function isString()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'string';
        }

        if ($this instanceof Union) {
            return isset($this->types['string']);
        }
    }

    public function isVoid()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'void';
        }

        if ($this instanceof Union) {
            return isset($this->types['void']);
        }
    }

    public function isNumeric()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'numeric';
        }

        if ($this instanceof Union) {
            return isset($this->types['numeric']);
        }
    }

    public function isScalar()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'scalar';
        }

        if ($this instanceof Union) {
            return isset($this->types['scalar']);
        }
    }

    public function isResource()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'resource';
        }

        if ($this instanceof Union) {
            return isset($this->types['resource']);
        }
    }

    public function isCallable()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'callable';
        }

        if ($this instanceof Union) {
            return isset($this->types['callable']);
        }
    }

    public function isEmpty()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'empty';
        }

        if ($this instanceof Union) {
            return isset($this->types['empty']);
        }
    }

    public function isObject()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'object';
        }

        if ($this instanceof Union) {
            return isset($this->types['object']);
        }
    }

    public function isArray()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'array';
        }

        if ($this instanceof Union) {
            return isset($this->types['array']);
        }
    }

    public function isNullable()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'null';
        }

        if ($this instanceof Union) {
            return isset($this->types['null']);
        }

        return false;
    }

    public function hasGeneric()
    {
        if ($this instanceof Union) {
            foreach ($this->types as $type) {
                if ($type instanceof Generic) {
                    return true;
                }
            }

            return false;
        }

        return $this instanceof Generic;
    }

    public function isScalarType()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'int' ||
                    $this->value === 'string' ||
                    $this->value === 'float' ||
                    $this->value === 'bool' ||
                    $this->value === 'false';
        }

        return false;
    }

    public function isNumericType()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'int' ||
                    $this->value === 'float';
        }

        return false;
    }

    public function isObjectType()
    {
        return $this->isObject() || (!$this->isScalarType() && !$this->isCallable() && !$this->isArray());
    }

    /**
     * @param  array<Union> $redefined_vars
     * @param  Context      $context
     * @return void
     */
    public static function redefineGenericUnionTypes(array $redefined_vars, Context $context)
    {
        foreach ($redefined_vars as $var_name => $redefined_union_type) {
            foreach ($redefined_union_type->types as $redefined_atomic_type) {
                foreach ($context->vars_in_scope[$var_name]->types as $context_type) {
                    if ($context_type instanceof Type\Generic &&
                        $redefined_atomic_type instanceof Type\Generic &&
                        $context_type->value === $redefined_atomic_type->value
                    ) {
                        if ($context_type->type_params[0]->isEmpty()) {
                            $context_type->type_params[0] = $redefined_atomic_type->type_params[0];
                        }
                        else {
                            $context_type->type_params[0] = Type::combineUnionTypes(
                                $redefined_atomic_type->type_params[0],
                                $context_type->type_params[0]
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Combines two union types into one
     * @param  Union  $type_1
     * @param  Union  $type_2
     * @return Union
     */
    public static function combineUnionTypes(Union $type_1, Union $type_2)
    {
        return self::combineTypes(array_merge(array_values($type_1->types), array_values($type_2->types)));
    }

    /**
     * Combines types together
     * so int + string = int|string
     * so array<int> + array<string> = array<int|string>
     * and array<int> + string = array<int>|string
     * and array<empty> + array<empty> = array<empty>
     * and array<string> + array<empty> = array<string>
     * and array + array<string> = array<mixed>
     *
     * @param  array<Atomic>    $types
     * @return Union
     */
    public static function combineTypes(array $types)
    {
        if (in_array(null, $types)) {
            return Type::getMixed();
        }

        if (count($types) === 1) {
            if ($types[0]->value === 'false') {
                $types[0]->value = 'bool';
            }

            return new Union([$types[0]]);
        }

        if (!$types) {
            throw new \InvalidArgumentException('You must pass at least one type to combineTypes');
        }

        $value_types = [];

        foreach ($types as $type) {
            if ($type instanceof Union) {
                throw new \InvalidArgumentException('Union type not expected here');
            }

            // if we see the magic empty value and there's more than one type, ignore it
            if ($type->value === 'empty') {
                continue;
            }

            if ($type->value === 'mixed') {
                return Type::getMixed();
            }

            if ($type->value === 'void') {
                $type->value = 'null';
            }

            // deal with false|bool => bool
            if ($type->value === 'false' && isset($value_types['bool'])) {
                continue;
            }
            elseif ($type->value === 'bool' && isset($value_types['false'])) {
                unset($value_types['false']);
            }

            if (!isset($value_types[$type->value])) {
                $value_types[$type->value] = [];
            }

            // @todo this doesn't support multiple type params right now
            $value_types[$type->value][(string) $type] = $type instanceof Generic ? $type->type_params[0] : null;
        }

        if (count($value_types) === 1) {
            if (isset($value_types['false'])) {
                return self::getBool();
            }
        }

        $new_types = [];

        foreach ($value_types as $key => $value_type) {
            if (count($value_type) === 1) {
                $value_type_param = array_values($value_type)[0];
                $new_types[] = $value_type_param ? new Generic($key, [$value_type_param]) : new Atomic($key);
                continue;
            }

            $expanded_value_types = [];

            foreach ($value_types[$key] as $expandable_value_type) {
                if ($expandable_value_type instanceof Union) {
                    $expanded_value_types = array_merge($expanded_value_types, array_values($expandable_value_type->types));
                    continue;
                }

                $expanded_value_types[] = $expandable_value_type;
            }

            // we have a generic type with
            $new_types[] = new Generic($key, [self::combineTypes($expanded_value_types)]);
        }

        $new_types = array_values($new_types);
        return new Union($new_types);
    }
}
