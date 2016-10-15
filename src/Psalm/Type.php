<?php

namespace Psalm;

use Psalm\Type\Atomic;
use Psalm\Type\Generic;
use Psalm\Type\Union;
use Psalm\Type\ObjectLike;
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

            if ($type_tokens[0] === 'array') {
                return Type::getArray();
            }

            return new Union([new Atomic($type_tokens[0])]);
        }

        $parse_tree = ParseTree::createFromTokens($type_tokens);

        $parsed_type = self::getTypeFromTree($parse_tree);

        if (!($parsed_type instanceof Union)) {
            $parsed_type = new Union([$parsed_type]);
        }

        return $parsed_type;
    }

    /**
     * @param  string $type_string
     * @return string
     */
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

            $generic_type_value = self::fixScalarTerms($generic_type->value);

            if ($generic_type_value === 'array' && count($generic_params) === 1) {
                array_unshift($generic_params, Type::getMixed());
            }

            if (!$generic_params) {
                throw new \InvalidArgumentException('No generic params provided for type');
            }

            if ($generic_type_value === 'array') {
                return new Type\GenericArray($generic_type_value, $generic_params);
            }

            return new Generic($generic_type_value, $generic_params);
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

        if ($parse_tree->value === ParseTree::OBJECT_LIKE) {
            $properties = [];

            $type = array_shift($parse_tree->children);

            foreach ($parse_tree->children as $property_branch) {
                $property_type = self::getTypeFromTree($property_branch->children[1]);
                if (!$property_type instanceof Union) {
                    $property_type = new Union([$property_type]);
                }
                $properties[$property_branch->children[0]->value] = $property_type;
            }

            return new ObjectLike($type, $properties);
        }

        $atomic_type = self::fixScalarTerms($parse_tree->value);

        if ($atomic_type === 'array') {
            return self::getArray()->types['array'];
        }

        return new Atomic($atomic_type);
    }

    /**
     * @return array<int,string>
     */
    public static function tokenize($return_type)
    {
        $return_type_tokens = [''];
        $was_char = false;
        $return_type = str_replace(' ', '', $return_type);

        foreach (str_split($return_type) as $char) {
            if ($was_char) {
                $return_type_tokens[] = '';
            }

            if ($char === '<' || $char === '>' || $char === '|' || $char === '?' || $char === ',' || $char === '{' || $char === '}' || $char === ':') {
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

    /** @return string */
    public static function convertSquareBrackets($type)
    {
        return preg_replace_callback(
            '/([a-zA-Z\<\>\\\\_]+)((\[\])+)/',
            function ($matches) {
                $inner_type = $matches[1];

                $dimensionality = strlen($matches[2]) / 2;

                for ($i = 0; $i < $dimensionality; $i++) {
                    $inner_type = 'array<int, ' . $inner_type . '>';
                }

                return $inner_type;
            },
            $type
        );
    }

    /** @return Type\Union */
    public static function getInt()
    {
        $type = new Atomic('int');

        return new Union([$type]);
    }

    /** @return Type\Union */
    public static function getString()
    {
        $type = new Atomic('string');

        return new Union([$type]);
    }

    /** @return Type\Union */
    public static function getNull()
    {
        $type = new Atomic('null');

        return new Union([$type]);
    }

    /** @return Type\Union */
    public static function getMixed()
    {
        $type = new Atomic('mixed');

        return new Union([$type]);
    }

    /** @return Type\Union */
    public static function getBool()
    {
        $type = new Atomic('bool');

        return new Union([$type]);
    }

    /** @return Type\Union */
    public static function getFloat()
    {
        $type = new Atomic('float');

        return new Union([$type]);
    }

    /** @return Type\Union */
    public static function getObject()
    {
        $type = new Atomic('object');

        return new Union([$type]);
    }

    /** @return Type\Union */
    public static function getObjectLike()
    {
        $type = new Atomic('object-like');

        return new Union([$type]);
    }

    /** @return Type\Union */
    public static function getArray()
    {
        $type = new Type\GenericArray(
            'array',
            [
                Type::getMixed(),
                Type::getMixed()
            ]
        );

        return new Union([$type]);
    }

    /** @return Type\Union */
    public static function getEmptyArray()
    {
        return new Type\Union([
            new Type\GenericArray(
                'array',
                [
                    new Type\Union([new Type\Atomic('empty')]),
                    new Type\Union([new Type\Atomic('empty')])
                ]
            )
        ]);
    }

    /** @return Type\Union */
    public static function getVoid()
    {
        $type = new Atomic('void');

        return new Union([$type]);
    }

    /** @return Type\Union */
    public static function getFalse()
    {
        $type = new Atomic('false');

        return new Union([$type]);
    }

    /** @return bool */
    public function isMixed()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'mixed';
        }

        if ($this instanceof Union) {
            return isset($this->types['mixed']);
        }
    }

    /** @return bool */
    public function isNull()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'null';
        }

        if ($this instanceof Union) {
            return count($this->types) === 1 && isset($this->types['null']);
        }
    }

    /** @return bool */
    public function isVoid()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'void';
        }

        if ($this instanceof Union) {
            return isset($this->types['void']);
        }
    }

    /** @return bool */
    public function isEmpty()
    {
        if ($this instanceof Atomic) {
            return $this->value === 'empty';
        }

        if ($this instanceof Union) {
            return isset($this->types['empty']);
        }
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
                        // index of last param
                        $i = count($context_type->type_params) - 1;

                        if ($context_type->type_params[$i]->isEmpty()) {
                            $context_type->type_params[$i] = $redefined_atomic_type->type_params[$i];
                        }
                        else {
                            $context_type->type_params[$i] = Type::combineUnionTypes(
                                $redefined_atomic_type->type_params[$i],
                                $context_type->type_params[$i]
                            );
                        }

                        if ($i) {
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

        $key_types = [];

        /** @var array<string,array<string,Union>> */
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

            if ($type instanceof Generic) {
                $value_type_param_index = count($type->type_params) - 1;
                $value_types[$type->value][(string) $type->type_params[$value_type_param_index]] = $type->type_params[$value_type_param_index];

                if ($value_type_param_index) {
                    $key_types[$type->value][(string) $type->type_params[0]] = $type->type_params[0];
                }
            }
            elseif ($type instanceof ObjectLike) {
                foreach ($type->properties as $candidate_property_name => $candidate_property_type) {
                    if (!isset($value_types[$type->value][$candidate_property_name])) {
                        $value_types[$type->value][$candidate_property_name] = $candidate_property_type;
                    }
                    else {
                        $value_types[$type->value][$candidate_property_name] = Type::combineUnionTypes(
                            $value_types[$type->value][$candidate_property_name],
                            $candidate_property_type
                        );
                    }
                }
            }
            else {
                if ($type->value === 'array') {
                    throw new \InvalidArgumentException('Cannot have a non-generic array');
                }

                $value_types[$type->value][(string) $type] = null;
            }
        }

        if (count($value_types) === 1) {
            if (isset($value_types['false'])) {
                return self::getBool();
            }
        }

        $new_types = [];

        foreach ($value_types as $generic_type => $value_type) {
            // special case for ObjectLike where $value_type is actually an array of properties
            if ($generic_type === 'object-like') {
                $new_types[] = new ObjectLike('object-like', $value_type);
                continue;
            }

            $key_type = isset($key_types[$generic_type]) ? $key_types[$generic_type] : [];

            // if we're merging an empty array with an object-like, clobber empty array
            if ($generic_type === 'array'
                && isset($value_types['object-like'])
                && count($value_type) === 1
                && isset($value_type['empty'])
                && count($key_type) === 1
                && (isset($key_type['empty']) || isset($key_type['string']))
            ) {

                continue;
            }

            $expanded_key_types = [];

            foreach ($key_type as $expandable_key_type) {
                $expanded_key_types = array_merge($expanded_key_types, array_values($expandable_key_type->types));
            }

            if (count($value_type) === 1) {
                $value_type_param = array_values($value_type)[0];
                $generic_type_params = [$value_type_param];

                // if we're continuing, also add the correspoinding key type param if it exists
                if ($expanded_key_types) {
                    array_unshift($generic_type_params, self::combineTypes($expanded_key_types));
                }

                $new_types[] = $value_type_param ? new Generic($generic_type, $generic_type_params) : new Atomic($generic_type);
                continue;
            }

            $expanded_value_types = [];

            $has_null = false;

            foreach ($value_type as $expandable_value_type) {
                if ($expandable_value_type) {
                    $has_null = $has_null || $expandable_value_type->isNullable();
                    $expanded_value_types = array_merge($expanded_value_types, array_values($expandable_value_type->types));
                }
                else {
                    $expanded_value_types = [Type::getMixed()->types['mixed']];
                }
            }

            $generic_type_params = [self::combineTypes($expanded_value_types)];

            if ($expanded_key_types) {
                array_unshift($generic_type_params, self::combineTypes($expanded_key_types));
            }

            // we have a generic type with
            $new_types[] = new Generic($generic_type, $generic_type_params);
        }

        $new_types = array_values($new_types);
        return new Union($new_types);
    }
}
