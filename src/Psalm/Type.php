<?php
namespace Psalm;

use Psalm\Exception\TypeParseTreeException;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
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
     * @var array<string, bool>
     */
    public static $PSALM_RESERVED_WORDS = [
        'int' => true,
        'string' => true,
        'float' => true,
        'bool' => true,
        'false' => true,
        'true' => true,
        'object' => true,
        'empty' => true,
        'callable' => true,
        'array' => true,
        'iterable' => true,
        'null' => true,
        'mixed' => true,
        'numeric-string' => true,
        'class-string' => true,
        'boolean' => true,
        'integer' => true,
        'double' => true,
        'real' => true,
        'resource' => true,
        'void' => true,
        'self' => true,
        'static' => true,
        'scalar' => true,
        'numeric' => true,
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private static $memoized_tokens = [];

    /**
     * Parses a string type representation
     *
     * @param  string $type_string
     * @param  bool   $php_compatible
     * @param  array<string, string> $template_types
     *
     * @return Union
     */
    public static function parseString($type_string, $php_compatible = false, array $template_types = [])
    {
        // remove all unacceptable characters
        $type_string = preg_replace('/\?(?=[a-zA-Z])/', 'null|', $type_string);

        if (preg_match('/[^A-Za-z0-9\-_\\\\&|\? \<\>\{\}=:\.,\]\[\(\)\$]/', trim($type_string))) {
            throw new TypeParseTreeException('Unrecognised character in type');
        }

        $type_tokens = self::tokenize($type_string);

        if (count($type_tokens) === 1) {
            $only_token = $type_tokens[0];

            // Note: valid identifiers can include class names or $this
            if (!preg_match('@^(\$this$|[a-zA-Z_\x7f-\xff])@', $only_token)) {
                throw new TypeParseTreeException("Invalid type '$only_token'");
            }

            $only_token = self::fixScalarTerms($only_token, $php_compatible);

            return new Union([Atomic::create($only_token, $php_compatible, $template_types)]);
        }

        try {
            $parse_tree = ParseTree::createFromTokens($type_tokens);
            $parsed_type = self::getTypeFromTree($parse_tree, $php_compatible, $template_types);
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
     * @param  array<string, string> $template_types
     *
     * @return  Atomic|TArray|TGenericObject|ObjectLike|Union
     */
    private static function getTypeFromTree(ParseTree $parse_tree, $php_compatible, array $template_types)
    {
        if ($parse_tree instanceof ParseTree\GenericTree) {
            $generic_type = $parse_tree->value;

            $generic_params = array_map(
                /**
                 * @return Union
                 */
                function (ParseTree $child_tree) use ($template_types) {
                    $tree_type = self::getTypeFromTree($child_tree, false, $template_types);

                    return $tree_type instanceof Union ? $tree_type : new Union([$tree_type]);
                },
                $parse_tree->children
            );

            $generic_type_value = self::fixScalarTerms($generic_type, false);

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

        if ($parse_tree instanceof ParseTree\UnionTree) {
            $union_types = array_map(
                /**
                 * @return Atomic
                 */
                function (ParseTree $child_tree) use ($template_types) {
                    $atomic_type = self::getTypeFromTree($child_tree, false, $template_types);

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

        if ($parse_tree instanceof ParseTree\IntersectionTree) {
            $intersection_types = array_map(
                /**
                 * @return Atomic
                 */
                function (ParseTree $child_tree) use ($template_types) {
                    $atomic_type = self::getTypeFromTree($child_tree, false, $template_types);

                    if (!$atomic_type instanceof Atomic) {
                        throw new TypeParseTreeException(
                            'Intersection types cannot contain unions'
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

            return $first_type;
        }

        if ($parse_tree instanceof ParseTree\ObjectLikeTree) {
            $properties = [];

            $type = $parse_tree->value;

            foreach ($parse_tree->children as $i => $property_branch) {
                if (!$property_branch instanceof ParseTree\ObjectLikePropertyTree) {
                    $property_type = self::getTypeFromTree($property_branch, false, $template_types);
                    $property_maybe_undefined = false;
                    $property_key = (string)$i;
                } elseif (count($property_branch->children) === 1) {
                    $property_type = self::getTypeFromTree($property_branch->children[0], false, $template_types);
                    $property_maybe_undefined = $property_branch->possibly_undefined;
                    $property_key = $property_branch->value;
                } else {
                    throw new \InvalidArgumentException(
                        'Unexpected number of property parts (' . count($property_branch->children) . ')'
                    );
                }

                if (!$property_type instanceof Union) {
                    $property_type = new Union([$property_type]);
                }

                if ($property_maybe_undefined) {
                    $property_type->possibly_undefined = true;
                }

                $properties[$property_key] = $property_type;
            }

            if ($type !== 'array') {
                throw new \InvalidArgumentException('Object-like type must be array');
            }

            if (!$properties) {
                throw new \InvalidArgumentException('No properties supplied for ObjectLike');
            }

            return new ObjectLike($properties);
        }

        if ($parse_tree instanceof ParseTree\CallableWithReturnTypeTree) {
            $callable_type = self::getTypeFromTree($parse_tree->children[0], false, $template_types);

            if (!$callable_type instanceof TCallable && !$callable_type instanceof Type\Atomic\Fn) {
                throw new \InvalidArgumentException('Parsing callable tree node should return TCallable');
            }

            if (!isset($parse_tree->children[1])) {
                throw new \InvalidArgumentException('Invalid return type');
            }

            $return_type = self::getTypeFromTree($parse_tree->children[1], false, $template_types);

            $callable_type->return_type = $return_type instanceof Union ? $return_type : new Union([$return_type]);

            return $callable_type;
        }

        if ($parse_tree instanceof ParseTree\CallableTree) {
            $params = array_map(
                /**
                 * @return FunctionLikeParameter
                 */
                function (ParseTree $child_tree) use ($template_types) {
                    $is_variadic = false;
                    $is_optional = false;

                    if ($child_tree instanceof ParseTree\CallableParamTree) {
                        $tree_type = self::getTypeFromTree($child_tree->children[0], false, $template_types);
                        $is_variadic = $child_tree->variadic;
                        $is_optional = $child_tree->has_default;
                    } else {
                        $tree_type = self::getTypeFromTree($child_tree, false, $template_types);
                    }

                    $tree_type = $tree_type instanceof Union ? $tree_type : new Union([$tree_type]);

                    return new FunctionLikeParameter(
                        '',
                        false,
                        $tree_type,
                        null,
                        null,
                        $is_optional,
                        false,
                        $is_variadic
                    );
                },
                $parse_tree->children
            );

            if (in_array(strtolower($parse_tree->value), ['closure', '\closure'], true)) {
                return new Type\Atomic\Fn('Closure', $params);
            }

            return new TCallable($parse_tree->value, $params);
        }

        if ($parse_tree instanceof ParseTree\EncapsulationTree) {
            return self::getTypeFromTree($parse_tree->children[0], false, $template_types);
        }

        if (!$parse_tree instanceof ParseTree\Value) {
            throw new \InvalidArgumentException('Unrecognised parse tree type ' . get_class($parse_tree));
        }

        $atomic_type = self::fixScalarTerms($parse_tree->value, $php_compatible);

        return Atomic::create($atomic_type, $php_compatible, $template_types);
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

        $chars = str_split($return_type);
        for ($i = 0, $c = count($chars); $i < $c; ++$i) {
            $char = $chars[$i];

            if ($was_char) {
                $return_type_tokens[++$rtc] = '';
            }

            if ($char === '<'
                || $char === '>'
                || $char === '|'
                || $char === '?'
                || $char === ','
                || $char === '{'
                || $char === '}'
                || $char === '['
                || $char === ']'
                || $char === '('
                || $char === ')'
                || $char === ' '
                || $char === '&'
                || $char === ':'
                || $char === '='
            ) {
                if ($return_type_tokens[$rtc] === '') {
                    $return_type_tokens[$rtc] = $char;
                } else {
                    $return_type_tokens[++$rtc] = $char;
                }

                $was_char = true;
            } elseif ($char === '.') {
                if ($i + 2 > $c || $chars[$i + 1] !== '.' || $chars[$i + 2] !== '.') {
                    throw new TypeParseTreeException('Unexpected token ' . $char);
                }

                if ($return_type_tokens[$rtc] === '') {
                    $return_type_tokens[$rtc] = '...';
                } else {
                    $return_type_tokens[++$rtc] = '...';
                }

                $was_char = true;

                $i += 2;
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
        $return_type_tokens = self::tokenize($return_type);

        for ($i = 0, $l = count($return_type_tokens); $i < $l; $i++) {
            $return_type_token = $return_type_tokens[$i];

            if (in_array(
                $return_type_token,
                ['<', '>', '|', '?', ',', '{', '}', ':', '[', ']', '(', ')', '&'],
                true
            )) {
                continue;
            }

            if (isset($return_type_tokens[$i + 1]) && $return_type_tokens[$i + 1] === ':') {
                continue;
            }

            $return_type_tokens[$i] = $return_type_token = self::fixScalarTerms($return_type_token);

            if (isset(self::$PSALM_RESERVED_WORDS[$return_type_token])) {
                continue;
            }

            if (isset($template_types[$return_type_token])) {
                continue;
            }

            if (isset($return_type_tokens[$i + 1])) {
                $next_char = $return_type_tokens[$i + 1];
                if ($next_char === ':') {
                    continue;
                }

                if ($next_char === '?' && isset($return_type_tokens[$i + 2]) && $return_type_tokens[$i + 2] === ':') {
                    continue;
                }
            }

            if ($return_type_token[0] === '$') {
                continue;
            }

            $return_type_tokens[$i] = self::getFQCLNFromString(
                $return_type_token,
                $aliases
            );
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
     * @param bool $from_calculation
     * @param int|null $value
     *
     * @return Type\Union
     */
    public static function getInt($from_calculation = false, int $value = null)
    {
        if ($value !== null) {
            $union = new Union([new TLiteralInt($value)]);
        } else {
            $union = new Union([new TInt()]);
        }

        $union->from_calculation = $from_calculation;

        return $union;
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
     * @param string|null $value
     *
     * @return Type\Union
     */
    public static function getString(string $value = null)
    {
        if ($value !== null) {
            $type = new TLiteralString($value);
        } else {
            $type = new TString();
        }

        return new Union([$type]);
    }

    /**
     * @param string $class_type
     *
     * @return Type\Union
     */
    public static function getClassString($class_type = 'object')
    {
        $type = new TClassString($class_type);

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
     * @param bool $from_isset
     *
     * @return Type\Union
     */
    public static function getMixed($from_isset = false)
    {
        $type = new TMixed($from_isset);

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getEmpty()
    {
        $type = new TEmpty();

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
     * @param float|null $value
     *
     * @return Type\Union
     */
    public static function getFloat(float $value = null)
    {
        if ($value !== null) {
            $type = new TLiteralFloat($value);
        } else {
            $type = new TFloat();
        }

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
        $array_type = new TArray(
            [
                new Type\Union([new TEmpty]),
                new Type\Union([new TEmpty]),
            ]
        );

        $array_type->count = 0;

        return new Type\Union([
            $array_type,
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
        if ($type_1->isMixedNotFromIsset() || $type_2->isMixedNotFromIsset()) {
            $combined_type = Type::getMixed();
        } else {
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

            if ($type_1->from_calculation || $type_2->from_calculation) {
                $combined_type->from_calculation = true;
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

        $has_null = false;
        $has_mixed = false;
        $has_non_mixed = false;

        foreach ($types as $type) {
            $from_docblock = $from_docblock || $type->from_docblock;

            $result = self::scrapeTypeProperties($type, $combination);

            if ($type instanceof TNull) {
                $has_null = true;
            }

            if ($type instanceof TMixed) {
                $has_mixed = true;
            } else {
                $has_non_mixed = true;
            }

            if ($result) {
                if ($from_docblock) {
                    $result->from_docblock = true;
                }

                return $result;
            }
        }

        if ($has_null && $has_mixed) {
            return Type::getMixed();
        }

        if (!$has_non_mixed) {
            return Type::getMixed(true);
        }

        if (count($combination->value_types) === 1
            && !count($combination->objectlike_entries)
            && !count($combination->type_params)
            && !$combination->strings
            && !$combination->ints
            && !$combination->floats
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

        if ($combination->class_string_types) {
            $new_types[] = new TClassString(implode('|', $combination->class_string_types));
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

                $array_type = new TArray($generic_type_params);

                if ($combination->array_counts && count($combination->array_counts) === 1) {
                    $array_type->count = array_keys($combination->array_counts)[0];
                }

                $new_types[] = $array_type;
            } elseif (!isset($combination->value_types[$generic_type])) {
                $new_types[] = new TGenericObject($generic_type, $generic_type_params);
            }
        }

        if ($combination->strings) {
            $new_types = array_merge($new_types, $combination->strings);
        }

        if ($combination->ints) {
            $new_types = array_merge($new_types, $combination->ints);
        }

        if ($combination->floats) {
            $new_types = array_merge($new_types, $combination->floats);
        }

        foreach ($combination->value_types as $type) {
            if (!($type instanceof TEmpty)
                || (count($combination->value_types) === 1
                    && !count($new_types))
            ) {
                $new_types[] = $type;
            }
        }

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
            if ($type->from_isset) {
                return null;
            }

            return Type::getMixed();
        }

        // deal with false|bool => bool
        if (($type instanceof TFalse || $type instanceof TTrue) && isset($combination->value_types['bool'])) {
            return null;
        }

        if (get_class($type) === TBool::class && isset($combination->value_types['false'])) {
            unset($combination->value_types['false']);
        }

        if (get_class($type) === TBool::class && isset($combination->value_types['true'])) {
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

            if ($type instanceof TArray && $combination->array_counts !== null) {
                if ($type->count === null) {
                    $combination->array_counts = null;
                } else {
                    $combination->array_counts[$type->count] = true;
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
                        = $existing_objectlike_entries || $candidate_property_type->possibly_undefined;
                } else {
                    $combination->objectlike_entries[$candidate_property_name] = Type::combineUnionTypes(
                        $value_type,
                        $candidate_property_type
                    );
                }

                unset($possibly_undefined_entries[$candidate_property_name]);
            }

            if ($combination->array_counts !== null) {
                $combination->array_counts[count($type->properties)] = true;
            }

            foreach ($possibly_undefined_entries as $type) {
                $type->possibly_undefined = true;
            }
        } elseif ($type instanceof TClassString) {
            if (!isset($combination->class_string_types['object'])) {
                $class_string_types = explode('|', $type->class_type);

                foreach ($class_string_types as $class_string_type) {
                    $combination->class_string_types[strtolower($class_string_type)] = $class_string_type;
                }
            }
        } else {
            if ($type instanceof TString) {
                if ($type instanceof TLiteralString
                    && $combination->strings !== null
                    && count($combination->strings) < 20
                ) {
                    $combination->strings[] = $type;
                } else {
                    $combination->strings = null;
                    $combination->value_types[$type_key] = $type;
                }
            } elseif ($type instanceof TInt) {
                if ($type instanceof TLiteralInt
                    && $combination->ints !== null
                    && count($combination->ints) < 20
                ) {
                    $combination->ints[] = $type;
                } else {
                    $combination->ints = null;
                    $combination->value_types[$type_key] = $type;
                }
            } elseif ($type instanceof TFloat) {
                if ($type instanceof TLiteralFloat
                    && $combination->floats !== null
                    && count($combination->floats) < 20
                ) {
                    $combination->floats[] = $type;
                } else {
                    $combination->floats = null;
                    $combination->value_types[$type_key] = $type;
                }
            } else {
                $combination->value_types[$type_key] = $type;
            }
        }
    }
}
