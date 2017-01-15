<?php
namespace Psalm;

use Psalm\Exception\TypeParseTreeException;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\Generic;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TVoid;
use Psalm\Type\ParseTree;
use Psalm\Type\TypeCombination;
use Psalm\Type\Union;

abstract class Type
{
    /**
     * Parses a string type representation
     *
     * @param  string $type_string
     *
     * @return Union
     */
    public static function parseString($type_string)
    {
        // remove all unacceptable characters
        $type_string = preg_replace('/[^A-Za-z0-9_\\\\|\? \<\>\{\}:,\]\[\(\)\$]/', '', trim($type_string));

        if (strpos($type_string, '[') !== false) {
            $type_string = self::convertSquareBrackets($type_string);
        }

        $type_string = str_replace('?', 'null|', $type_string);

        $type_tokens = self::tokenize($type_string);

        if (count($type_tokens) === 1) {
            $type_tokens[0] = self::fixScalarTerms($type_tokens[0]);

            return new Union([Atomic::create($type_tokens[0])]);
        }

        try {
            $parse_tree = ParseTree::createFromTokens($type_tokens);
            $parsed_type = self::getTypeFromTree($parse_tree);
        } catch (TypeParseTreeException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TypeParseTreeException($e->getMessage());
        }

        if (!($parsed_type instanceof Union)) {
            $parsed_type = new Union([$parsed_type]);
        }

        return $parsed_type;
    }

    /**
     * @param  string $type_string
     *
     * @return string
     */
    public static function fixScalarTerms($type_string)
    {
        if (in_array(
            strtolower($type_string),
            [
                'numeric',
                'int',
                'void',
                'float',
                'string',
                'bool',
                'true',
                'false',
                'null',
                'array',
                'object',
                'mixed',
                'resource',
                'callable',
            ],
            true
        )) {
            return strtolower($type_string);
        } elseif ($type_string === 'boolean') {
            return 'bool';
        } elseif ($type_string === 'integer') {
            return 'int';
        } elseif ($type_string === 'double' || $type_string === 'real') {
            return 'float';
        }

        return $type_string;
    }

    /**
     * @param   ParseTree $parse_tree
     *
     * @return  Atomic|TArray|TGenericObject|ObjectLike|Union
     */
    private static function getTypeFromTree(ParseTree $parse_tree)
    {
        if (!$parse_tree->value) {
            throw new \InvalidArgumentException('Parse tree must have a value');
        }

        if ($parse_tree->value === ParseTree::GENERIC) {
            $generic_type = array_shift($parse_tree->children);

            $generic_params = array_map(
                /**
                 * @return Union
                 */
                function (ParseTree $child_tree) {
                    $tree_type = self::getTypeFromTree($child_tree);

                    return $tree_type instanceof Union ? $tree_type : new Union([$tree_type]);
                },
                $parse_tree->children
            );

            if (!$generic_type->value) {
                throw new \InvalidArgumentException('Generic type must have a value');
            }

            $generic_type_value = self::fixScalarTerms($generic_type->value);

            if (($generic_type_value === 'array' || $generic_type_value === 'Generator') &&
                count($generic_params) === 1
            ) {
                array_unshift($generic_params, new Union([new TMixed]));
            }

            if (!$generic_params) {
                throw new \InvalidArgumentException('No generic params provided for type');
            }

            if ($generic_type_value === 'array') {
                return new TArray($generic_params);
            }

            return new TGenericObject($generic_type_value, $generic_params);
        }

        if ($parse_tree->value === ParseTree::UNION) {
            $union_types = array_map(
                /**
                 * @return Atomic
                 */
                function (ParseTree $child_tree) {
                    $atomic_type = self::getTypeFromTree($child_tree);

                    if (!$atomic_type instanceof Atomic) {
                        throw new \UnexpectedValueException(
                            'Was expecting an atomic type, got ' . get_class($atomic_type)
                        );
                    }

                    return $atomic_type;
                },
                $parse_tree->children
            );

            return self::combineTypes($union_types);
        }

        if ($parse_tree->value === ParseTree::OBJECT_LIKE) {
            $properties = [];

            $type = array_shift($parse_tree->children);

            foreach ($parse_tree->children as $property_branch) {
                $property_type = self::getTypeFromTree($property_branch->children[1]);
                if (!$property_type instanceof Union) {
                    $property_type = new Union([$property_type]);
                }
                $properties[(string)($property_branch->children[0]->value)] = $property_type;
            }

            if ($type->value !== 'array') {
                throw new \InvalidArgumentException('Object-like type must be array');
            }

            return new ObjectLike($properties);
        }

        $atomic_type = self::fixScalarTerms($parse_tree->value);

        return Atomic::create($atomic_type);
    }

    /**
     * @param  string $return_type
     *
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

            if ($char === '<' ||
                $char === '>' ||
                $char === '|' ||
                $char === '?' ||
                $char === ',' ||
                $char === '{' ||
                $char === '}' ||
                $char === ':'
            ) {
                if ($return_type_tokens[count($return_type_tokens) - 1] === '') {
                    $return_type_tokens[count($return_type_tokens) - 1] = $char;
                } else {
                    $return_type_tokens[] = $char;
                }

                $was_char = true;
            } else {
                $return_type_tokens[count($return_type_tokens) - 1] .= $char;
                $was_char = false;
            }
        }

        return $return_type_tokens;
    }

    /**
     * @param  string $type
     *
     * @return string
     */
    public static function convertSquareBrackets($type)
    {
        $class_chars = '[a-zA-Z0-9\<\>\\\\_]+';

        return preg_replace_callback(
            '/(' . $class_chars . '|' . '\((' . $class_chars . '(\|' . $class_chars . ')*' . ')\))((\[\])+)/',
            /**
             * @return string
             */
            function (array $matches) {
                $inner_type = str_replace(['(', ')'], '', (string)$matches[1]);

                $dimensionality = strlen((string)$matches[4]) / 2;

                for ($i = 0; $i < $dimensionality; ++$i) {
                    $inner_type = 'array<mixed,' . $inner_type . '>';
                }

                return $inner_type;
            },
            $type
        );
    }

    /**
     * @return Type\Union
     */
    public static function getInt()
    {
        $type = new TInt;

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getString()
    {
        $type = new TString;

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getNull()
    {
        $type = new TNull;

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getMixed()
    {
        $type = new TMixed;

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getBool()
    {
        $type = new TBool;

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getFloat()
    {
        $type = new TFloat;

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getObject()
    {
        $type = new TObject;

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getClosure()
    {
        $type = new TNamedObject('Closure');

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getArray()
    {
        $type = new TArray(
            [
                new Type\Union([new TMixed]),
                new Type\Union([new TMixed]),
            ]
        );

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getEmptyArray()
    {
        return new Type\Union([
            new TArray(
                [
                    new Type\Union([new TEmpty]),
                    new Type\Union([new TEmpty]),
                ]
            ),
        ]);
    }

    /**
     * @return Type\Union
     */
    public static function getVoid()
    {
        $type = new TVoid;

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getFalse()
    {
        $type = new TFalse;

        return new Union([$type]);
    }

    /**
     * @param  array<string, Union> $redefined_vars
     * @param  Context              $context
     *
     * @return void
     */
    public static function redefineGenericUnionTypes(array $redefined_vars, Context $context)
    {
        foreach ($redefined_vars as $var_name => $redefined_union_type) {
            foreach ($redefined_union_type->types as $redefined_atomic_type) {
                foreach ($context->vars_in_scope[$var_name]->types as $context_type) {
                    if ($context_type instanceof Type\Atomic\TArray &&
                        $redefined_atomic_type instanceof Type\Atomic\TArray
                    ) {
                        if ($context_type->type_params[1]->isEmpty()) {
                            $context_type->type_params[1] = $redefined_atomic_type->type_params[1];
                        } else {
                            $context_type->type_params[1] = Type::combineUnionTypes(
                                $redefined_atomic_type->type_params[1],
                                $context_type->type_params[1]
                            );
                        }

                        if ($context_type->type_params[0]->isEmpty()) {
                            $context_type->type_params[0] = $redefined_atomic_type->type_params[0];
                        } else {
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
     *
     * @param  Union  $type_1
     * @param  Union  $type_2
     *
     * @return Union
     */
    public static function combineUnionTypes(Union $type_1, Union $type_2)
    {
        $both_failed_reconciliation = false;

        if ($type_1->failed_reconciliation) {
            if ($type_2->failed_reconciliation) {
                $both_failed_reconciliation = true;
            } else {
                return $type_2;
            }
        } elseif ($type_2->failed_reconciliation) {
            return $type_1;
        }

        $combined_type = self::combineTypes(array_merge(array_values($type_1->types), array_values($type_2->types)));

        if (!$type_1->initialized || !$type_2->initialized) {
            $combined_type->initialized = false;
        }

        if ($type_1->from_docblock || $type_2->from_docblock) {
            $combined_type->from_docblock = true;
        }

        if ($type_1->ignore_nullable_issues || $type_2->ignore_nullable_issues) {
            $combined_type->ignore_nullable_issues = true;
        }

        if ($both_failed_reconciliation) {
            $combined_type->failed_reconciliation = true;
        }

        return $combined_type;
    }

    /**
     * Combines types together
     *  - so `int + string = int|string`
     *  - so `array<int> + array<string> = array<int|string>`
     *  - and `array<int> + string = array<int>|string`
     *  - and `array<empty> + array<empty> = array<empty>`
     *  - and `array<string> + array<empty> = array<string>`
     *  - and `array + array<string> = array<mixed>`
     *
     * @param  array<Atomic>    $types
     *
     * @return Union
     * @psalm-suppress TypeCoercion
     */
    public static function combineTypes(array $types)
    {
        if (in_array(null, $types, true)) {
            return Type::getMixed();
        }

        if (count($types) === 1) {
            if ($types[0] instanceof TFalse) {
                $types[0] = new TBool;
            }

            return new Union([$types[0]]);
        }

        if (!$types) {
            throw new \InvalidArgumentException('You must pass at least one type to combineTypes');
        }

        $combination = new TypeCombination();

        foreach ($types as $type) {
            $result = self::scrapeTypeProperties($type, $combination);

            if ($result) {
                return $result;
            }
        }

        if (count($combination->value_types) === 1
            && !count($combination->objectlike_entries)
            && !count($combination->type_params)
        ) {
            if (isset($combination->value_types['false'])) {
                return Type::getBool();
            }
        } elseif (isset($combination->value_types['void'])) {
            unset($combination->value_types['void']);

            if (!isset($combination->value_types['null'])) {
                $combination->value_types['null'] = new TNull();
            }
        }

        $new_types = [];

        if (count($combination->objectlike_entries) &&
            (!isset($combination->type_params['array'])
                || $combination->type_params['array'][1]->isEmpty())
        ) {
            $new_types[] = new ObjectLike($combination->objectlike_entries);

            // if we're merging an empty array with an object-like, clobber empty array
            unset($combination->type_params['array']);
        }

        foreach ($combination->type_params as $generic_type => $generic_type_params) {
            if ($generic_type === 'array') {
                $new_types[] = new TArray($generic_type_params);
            } elseif (!isset($combination->value_types[$generic_type])) {
                $new_types[] = new TGenericObject($generic_type, $generic_type_params);
            }
        }

        foreach ($combination->value_types as $generic_type => $type) {
            if (!($type instanceof TEmpty)
                || (count($combination->value_types) === 1
                    && !count($new_types))
            ) {
                $new_types[] = $type;
            }
        }

        $new_types = array_values($new_types);

        return new Union($new_types);
    }

    /**
     * @param  Atomic  $type
     * @param  TypeCombination $combination
     *
     * @return null|Union
     */
    public static function scrapeTypeProperties(Atomic $type, TypeCombination $combination)
    {
        if ($type instanceof TMixed) {
            return Type::getMixed();
        }

        // deal with false|bool => bool
        if ($type instanceof TFalse && isset($combination->value_types['bool'])) {
            return null;
        } elseif ($type instanceof TBool && isset($combination->value_types['false'])) {
            unset($combination->value_types['false']);
        }

        $type_key = $type->getKey();

        if ($type instanceof TArray || $type instanceof TGenericObject) {
            for ($i = 0; $i < count($type->type_params); ++$i) {
                $type_param = $type->type_params[$i];

                if (isset($combination->type_params[$type_key][$i])) {
                    $combination->type_params[$type_key][$i] = Type::combineUnionTypes(
                        $combination->type_params[$type_key][$i],
                        $type_param
                    );
                } else {
                    $combination->type_params[$type_key][$i] = $type_param;
                }
            }
        } elseif ($type instanceof ObjectLike) {
            foreach ($type->properties as $candidate_property_name => $candidate_property_type) {
                $value_type = isset($combination->objectlike_entries[$candidate_property_name])
                    ? $combination->objectlike_entries[$candidate_property_name]
                    : null;

                if (!$value_type) {
                    $combination->objectlike_entries[$candidate_property_name] = $candidate_property_type;
                } else {
                    $combination->objectlike_entries[$candidate_property_name] = Type::combineUnionTypes(
                        $value_type,
                        $candidate_property_type
                    );
                }
            }
        } else {
            $combination->value_types[$type_key] = $type;
        }
    }
}
