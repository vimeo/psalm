<?php
namespace Psalm;

use Psalm\Exception\TypeParseTreeException;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TVoid;
use Psalm\Type\ParseTree;
use Psalm\Type\TypeCombination;
use Psalm\Type\Union;

abstract class Type
{
    /**
     * @var array<string, array<int, string>>
     */
    private static $memoized_tokens = [];

    /**
     * Parses a string type representation
     *
     * @param  string $type_string
     * @param  bool   $php_compatible
     *
     * @return Union
     */
    public static function parseString($type_string, $php_compatible = false)
    {
        // remove all unacceptable characters
        $type_string = preg_replace('/[^A-Za-z0-9\-_\\\\&|\? \<\>\{\}:,\]\[\(\)\$]/', '', trim($type_string));

        if (strpos($type_string, '[') !== false) {
            $type_string = self::convertSquareBrackets($type_string);
        }

        $type_string = preg_replace('/\?(?=[a-zA-Z])/', 'null|', $type_string);

        if (preg_match('/[\[\]()]/', $type_string)) {
            throw new TypeParseTreeException('Invalid characters in type');
        }

        $type_tokens = self::tokenize($type_string);

        if (count($type_tokens) === 1) {
            $type_tokens[0] = self::fixScalarTerms($type_tokens[0], $php_compatible);

            return new Union([Atomic::create($type_tokens[0], $php_compatible)]);
        }

        try {
            $parse_tree = ParseTree::createFromTokens($type_tokens);
            $parsed_type = self::getTypeFromTree($parse_tree, $php_compatible);
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
     * @param  bool   $php_compatible
     *
     * @return string
     */
    private static function fixScalarTerms($type_string, $php_compatible = false)
    {
        $type_string_lc = strtolower($type_string);

        switch ($type_string_lc) {
            case 'int':
            case 'void':
            case 'float':
            case 'string':
            case 'bool':
            case 'callable':
            case 'iterable':
            case 'array':
            case 'object':
            case 'numeric':
            case 'true':
            case 'false':
            case 'null':
            case 'mixed':
            case 'resource':
                return $type_string_lc;
        }

        switch ($type_string) {
            case 'boolean':
                return $php_compatible ? $type_string : 'bool';

            case 'integer':
                return $php_compatible ? $type_string : 'int';

            case 'double':
            case 'real':
                return $php_compatible ? $type_string : 'float';
        }

        return $type_string;
    }

    /**
     * @param  ParseTree $parse_tree
     * @param  bool      $php_compatible
     *
     * @return  Atomic|TArray|TGenericObject|ObjectLike|Union
     */
    private static function getTypeFromTree(ParseTree $parse_tree, $php_compatible)
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
                    $tree_type = self::getTypeFromTree($child_tree, false);

                    return $tree_type instanceof Union ? $tree_type : new Union([$tree_type]);
                },
                $parse_tree->children
            );

            if (!$generic_type->value) {
                throw new \InvalidArgumentException('Generic type must have a value');
            }

            $generic_type_value = self::fixScalarTerms($generic_type->value, false);

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
                    $atomic_type = self::getTypeFromTree($child_tree, false);

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

        if ($parse_tree->value === ParseTree::INTERSECTION) {
            $intersection_types = array_map(
                /**
                 * @return Atomic
                 */
                function (ParseTree $child_tree) {
                    $atomic_type = self::getTypeFromTree($child_tree, false);

                    if (!$atomic_type instanceof Atomic) {
                        throw new \UnexpectedValueException(
                            'Was expecting an atomic type, got ' . get_class($atomic_type)
                        );
                    }

                    return $atomic_type;
                },
                $parse_tree->children
            );

            foreach ($intersection_types as $intersection_type) {
                if (!$intersection_type instanceof TNamedObject) {
                    throw new TypeParseTreeException('Intersection types must all be objects');
                }
            }

            /** @var TNamedObject[] $intersection_types */
            $first_type = array_shift($intersection_types);

            $first_type->extra_types = $intersection_types;

            return new Type\Union([$first_type]);
        }

        if ($parse_tree->value === ParseTree::OBJECT_LIKE) {
            $properties = [];

            $type = array_shift($parse_tree->children);

            foreach ($parse_tree->children as $i => $property_branch) {
                if ($property_branch->value !== ParseTree::OBJECT_PROPERTY) {
                    $property_type = self::getTypeFromTree($property_branch, false);
                    $property_maybe_undefined = false;
                    $property_key = (string)$i;
                } elseif (count($property_branch->children) === 2) {
                    $property_type = self::getTypeFromTree($property_branch->children[1], false);
                    $property_maybe_undefined = $property_branch->possibly_undefined;
                    $property_key = (string)($property_branch->children[0]->value);
                } else {
                    throw new \InvalidArgumentException('Unexpected number of property parts');
                }

                if (!$property_type instanceof Union) {
                    $property_type = new Union([$property_type]);
                }

                if ($property_maybe_undefined) {
                    $property_type->possibly_undefined = true;
                }

                $properties[$property_key] = $property_type;
            }

            if ($type->value !== 'array') {
                throw new \InvalidArgumentException('Object-like type must be array');
            }

            if (!$properties) {
                throw new \InvalidArgumentException('No properties supplied for ObjectLike');
            }

            return new ObjectLike($properties);
        }

        $atomic_type = self::fixScalarTerms($parse_tree->value, $php_compatible);

        return Atomic::create($atomic_type, $php_compatible);
    }

    /**
     * @param  string $return_type
     * @param  bool   $ignore_space
     *
     * @return array<int,string>
     */
    public static function tokenize($return_type, $ignore_space = true)
    {
        $return_type_tokens = [''];
        $was_char = false;

        if ($ignore_space) {
            $return_type = str_replace(' ', '', $return_type);
        }

        if (isset(self::$memoized_tokens[$return_type])) {
            return self::$memoized_tokens[$return_type];
        }

        // index of last type token
        $rtc = 0;

        foreach (str_split($return_type) as $char) {
            if ($was_char) {
                $return_type_tokens[++$rtc] = '';
            }

            if ($char === '<' ||
                $char === '>' ||
                $char === '|' ||
                $char === '?' ||
                $char === ',' ||
                $char === '{' ||
                $char === '}' ||
                $char === '[' ||
                $char === ']' ||
                $char === ' ' ||
                $char === '&' ||
                $char === ':'
            ) {
                if ($return_type_tokens[$rtc] === '') {
                    $return_type_tokens[$rtc] = $char;
                } else {
                    $return_type_tokens[++$rtc] = $char;
                }

                $was_char = true;
            } else {
                $return_type_tokens[$rtc] .= $char;
                $was_char = false;
            }
        }

        self::$memoized_tokens[$return_type] = $return_type_tokens;

        return $return_type_tokens;
    }

    /**
     * @param  string                       $return_type
     * @param  Aliases                      $aliases
     * @param  array<string, string>|null   $template_types
     *
     * @return string
     */
    public static function fixUpLocalType(
        $return_type,
        Aliases $aliases,
        array $template_types = null
    ) {
        if (strpos($return_type, '[') !== false) {
            $return_type = self::convertSquareBrackets($return_type);
        }

        $return_type_tokens = self::tokenize($return_type);

        foreach ($return_type_tokens as $i => &$return_type_token) {
            if (in_array($return_type_token, ['<', '>', '|', '?', ',', '{', '}', ':'], true)) {
                continue;
            }

            if (isset($return_type_tokens[$i + 1]) && $return_type_tokens[$i + 1] === ':') {
                continue;
            }

            $return_type_token = self::fixScalarTerms($return_type_token);

            if ($return_type_token[0] === strtoupper($return_type_token[0]) &&
                !isset($template_types[$return_type_token])
            ) {
                if ($return_type_token[0] === '$') {
                    if ($return_type === '$this') {
                        $return_type_token = 'static';
                    }

                    continue;
                }

                $return_type_token = self::getFQCLNFromString(
                    $return_type_token,
                    $aliases
                );
            }
        }

        return implode('', $return_type_tokens);
    }

    /**
     * @param  string                   $class
     * @param  Aliases                  $aliases
     *
     * @return string
     */
    public static function getFQCLNFromString($class, Aliases $aliases)
    {
        if (empty($class)) {
            throw new \InvalidArgumentException('$class cannot be empty');
        }

        if ($class[0] === '\\') {
            return substr($class, 1);
        }

        $imported_namespaces = $aliases->uses;

        if (strpos($class, '\\') !== false) {
            $class_parts = explode('\\', $class);
            $first_namespace = array_shift($class_parts);

            if (isset($imported_namespaces[strtolower($first_namespace)])) {
                return $imported_namespaces[strtolower($first_namespace)] . '\\' . implode('\\', $class_parts);
            }
        } elseif (isset($imported_namespaces[strtolower($class)])) {
            return $imported_namespaces[strtolower($class)];
        }

        $namespace = $aliases->namespace;

        return ($namespace ? $namespace . '\\' : '') . $class;
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
    public static function getNumeric()
    {
        $type = new TNumeric;

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
    public static function getClassString()
    {
        $type = new TClassString;

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
     * @return Type\Union
     */
    public static function getTrue()
    {
        $type = new TTrue;

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getResource()
    {
        return new Union([new TResource]);
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
        if ($type_1->isMixed() || $type_2->isMixed()) {
            return Type::getMixed();
        }

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

        $combined_type = self::combineTypes(
            array_merge(
                array_values($type_1->getTypes()),
                array_values($type_2->getTypes())
            )
        );

        if (!$type_1->initialized || !$type_2->initialized) {
            $combined_type->initialized = false;
        }

        if ($type_1->from_docblock || $type_2->from_docblock) {
            $combined_type->from_docblock = true;
        }

        if ($type_1->ignore_nullable_issues || $type_2->ignore_nullable_issues) {
            $combined_type->ignore_nullable_issues = true;
        }

        if ($type_1->ignore_falsable_issues || $type_2->ignore_falsable_issues) {
            $combined_type->ignore_falsable_issues = true;
        }

        if ($both_failed_reconciliation) {
            $combined_type->failed_reconciliation = true;
        }

        if ($type_1->possibly_undefined || $type_2->possibly_undefined) {
            $combined_type->possibly_undefined = true;
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
            $union_type = new Union([$types[0]]);

            if ($types[0]->from_docblock) {
                $union_type->from_docblock = true;
            }

            return $union_type;
        }

        if (!$types) {
            throw new \InvalidArgumentException('You must pass at least one type to combineTypes');
        }

        $combination = new TypeCombination();

        $from_docblock = false;

        foreach ($types as $type) {
            $from_docblock = $from_docblock || $type->from_docblock;

            $result = self::scrapeTypeProperties($type, $combination);

            if ($result) {
                if ($from_docblock) {
                    $result->from_docblock = true;
                }

                return $result;
            }
        }

        if (count($combination->value_types) === 1
            && !count($combination->objectlike_entries)
            && !count($combination->type_params)
        ) {
            if (isset($combination->value_types['false'])) {
                $union_type = Type::getFalse();

                if ($from_docblock) {
                    $union_type->from_docblock = true;
                }

                return $union_type;
            }

            if (isset($combination->value_types['true'])) {
                $union_type = Type::getTrue();

                if ($from_docblock) {
                    $union_type->from_docblock = true;
                }

                return $union_type;
            }
        } elseif (isset($combination->value_types['void'])) {
            unset($combination->value_types['void']);

            // if we're merging with another type, we cannot represent it in PHP
            $from_docblock = true;

            if (!isset($combination->value_types['null'])) {
                $combination->value_types['null'] = new TNull();
            }
        }

        if (isset($combination->value_types['true']) && isset($combination->value_types['false'])) {
            unset($combination->value_types['true'], $combination->value_types['false']);

            $combination->value_types['bool'] = new TBool();
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
                if ($combination->objectlike_entries) {
                    $objectlike_generic_type = null;

                    $objectlike_keys = [];

                    foreach ($combination->objectlike_entries as $property_name => $property_type) {
                        if ($objectlike_generic_type) {
                            $objectlike_generic_type = Type::combineUnionTypes(
                                $property_type,
                                $objectlike_generic_type
                            );
                        } else {
                            $objectlike_generic_type = clone $property_type;
                        }

                        if (is_int($property_name)) {
                            if (!isset($objectlike_keys['int'])) {
                                $objectlike_keys['int'] = new TInt;
                            }
                        } else {
                            if (!isset($objectlike_keys['string'])) {
                                $objectlike_keys['string'] = new TString;
                            }
                        }
                    }

                    if (!$objectlike_generic_type) {
                        throw new \InvalidArgumentException('Cannot be null');
                    }

                    $objectlike_generic_type->possibly_undefined = false;

                    $objectlike_key_type = new Type\Union(array_values($objectlike_keys));

                    $generic_type_params[0] = Type::combineUnionTypes(
                        $generic_type_params[0],
                        $objectlike_key_type
                    );
                    $generic_type_params[1] = Type::combineUnionTypes(
                        $generic_type_params[1],
                        $objectlike_generic_type
                    );
                }

                $new_types[] = new TArray($generic_type_params);
            } elseif (!isset($combination->value_types[$generic_type])) {
                $new_types[] = new TGenericObject($generic_type, $generic_type_params);
            }
        }

        foreach ($combination->value_types as $type) {
            if (!($type instanceof TEmpty)
                || (count($combination->value_types) === 1
                    && !count($new_types))
            ) {
                $new_types[] = $type;
            }
        }

        $new_types = array_values($new_types);

        $union_type = new Union($new_types);

        if ($from_docblock) {
            $union_type->from_docblock = true;
        }

        return $union_type;
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
        if (($type instanceof TFalse || $type instanceof TTrue) && isset($combination->value_types['bool'])) {
            return null;
        }

        if (get_class($type) === 'Psalm\\Type\\Atomic\\TBool' && isset($combination->value_types['false'])) {
            unset($combination->value_types['false']);
        }

        if (get_class($type) === 'Psalm\\Type\\Atomic\\TBool' && isset($combination->value_types['true'])) {
            unset($combination->value_types['true']);
        }

        $type_key = $type->getKey();

        if ($type instanceof TArray || $type instanceof TGenericObject) {
            foreach ($type->type_params as $i => $type_param) {
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
            $existing_objectlike_entries = (bool) $combination->objectlike_entries;
            $possibly_undefined_entries = $combination->objectlike_entries;

            foreach ($type->properties as $candidate_property_name => $candidate_property_type) {
                $value_type = isset($combination->objectlike_entries[$candidate_property_name])
                    ? $combination->objectlike_entries[$candidate_property_name]
                    : null;

                if (!$value_type) {
                    $combination->objectlike_entries[$candidate_property_name] = clone $candidate_property_type;
                    // it's possibly undefined if there are existing objectlike entries
                    $combination->objectlike_entries[$candidate_property_name]->possibly_undefined
                        = $existing_objectlike_entries;
                } else {
                    $combination->objectlike_entries[$candidate_property_name] = Type::combineUnionTypes(
                        $value_type,
                        $candidate_property_type
                    );
                }

                unset($possibly_undefined_entries[$candidate_property_name]);
            }

            foreach ($possibly_undefined_entries as $type) {
                $type->possibly_undefined = true;
            }
        } else {
            $combination->value_types[$type_key] = $type;
        }
    }
}
