<?php
namespace Psalm;

use Psalm\Exception\TypeParseTreeException;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericIterable;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TGenericParam;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TVoid;
use Psalm\Internal\Type\ParseTree;
use Psalm\Internal\Type\TypeCombination;
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
        'mysql-escaped-string' => true,
        'html-escaped-string' => true,
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
        'no-return' => true,
        'never-return' => true,
        'never-returns' => true,
        'array-key' => true,
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
     * @param  array<string, Union> $template_type_map
     *
     * @return Union
     */
    public static function parseString(
        $type_string,
        $php_compatible = false,
        array $template_type_map = []
    ) {
        return self::parseTokens(self::tokenize($type_string), $php_compatible, $template_type_map);
    }

    /**
     * Parses a string type representation
     *
     * @param  array<int, string> $type_tokens
     * @param  bool   $php_compatible
     * @param  array<string, Union> $template_type_map
     *
     * @return Union
     */
    public static function parseTokens(
        array $type_tokens,
        $php_compatible = false,
        array $template_type_map = []
    ) {
        if (count($type_tokens) === 1) {
            $only_token = $type_tokens[0];

            // Note: valid identifiers can include class names or $this
            if (!preg_match('@^(\$this|\\\\?[a-zA-Z_\x7f-\xff][\\\\\-0-9a-zA-Z_\x7f-\xff]*)$@', $only_token)) {
                throw new TypeParseTreeException("Invalid type '$only_token'");
            }

            $only_token = self::fixScalarTerms($only_token, $php_compatible);

            return new Union([Atomic::create($only_token, $php_compatible, $template_type_map)]);
        }

        try {
            $parse_tree = ParseTree::createFromTokens($type_tokens);
            $parsed_type = self::getTypeFromTree($parse_tree, $php_compatible, $template_type_map);
        } catch (TypeParseTreeException $e) {
            throw $e;
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
     * @param  array<string, Union> $template_type_map
     *
     * @return  Atomic|TArray|TGenericObject|ObjectLike|Union
     */
    public static function getTypeFromTree(
        ParseTree $parse_tree,
        $php_compatible = false,
        array $template_type_map = []
    ) {
        if ($parse_tree instanceof ParseTree\GenericTree) {
            $generic_type = $parse_tree->value;

            $generic_params = array_map(
                /**
                 * @return Union
                 */
                function (ParseTree $child_tree) use ($template_type_map) {
                    $tree_type = self::getTypeFromTree($child_tree, false, $template_type_map);

                    return $tree_type instanceof Union ? $tree_type : new Union([$tree_type]);
                },
                $parse_tree->children
            );

            $generic_type_value = self::fixScalarTerms($generic_type, false);

            if ($generic_type_value === 'array' && count($generic_params) === 1) {
                array_unshift($generic_params, new Union([new TArrayKey]));
            } elseif (($generic_type_value === 'Generator' || $generic_type_value === 'iterable')
                && count($generic_params) === 1
            ) {
                array_unshift($generic_params, new Union([new TMixed]));
            }

            if (!$generic_params) {
                throw new TypeParseTreeException('No generic params provided for type');
            }

            if ($generic_type_value === 'array') {
                return new TArray($generic_params);
            }

            if ($generic_type_value === 'iterable') {
                return new TGenericIterable($generic_params);
            }

            if ($generic_type_value === 'class-string') {
                $class_name = (string) $generic_params[0];

                if (isset($template_type_map[$class_name])) {
                    return self::getGenericParamClass($class_name, $template_type_map[$class_name]);
                }

                return new TClassString($class_name);
            }

            return new TGenericObject($generic_type_value, $generic_params);
        }

        if ($parse_tree instanceof ParseTree\UnionTree) {
            $has_null = false;

            $atomic_types = [];

            foreach ($parse_tree->children as $child_tree) {
                if ($child_tree instanceof ParseTree\NullableTree) {
                    $atomic_type = self::getTypeFromTree($child_tree->children[0], false, $template_type_map);
                    $has_null = true;
                } else {
                    $atomic_type = self::getTypeFromTree($child_tree, false, $template_type_map);
                }

                if ($atomic_type instanceof Union) {
                    foreach ($atomic_type->getTypes() as $type) {
                        $atomic_types[] = $type;
                    }

                    continue;
                }

                $atomic_types[] = $atomic_type;
            }

            if ($has_null) {
                $atomic_types[] = new TNull;
            }

            return TypeCombination::combineTypes($atomic_types);
        }

        if ($parse_tree instanceof ParseTree\IntersectionTree) {
            $intersection_types = array_map(
                /**
                 * @return Atomic
                 */
                function (ParseTree $child_tree) use ($template_type_map) {
                    $atomic_type = self::getTypeFromTree($child_tree, false, $template_type_map);

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
                if (!$intersection_type instanceof TNamedObject
                    && !$intersection_type instanceof TGenericParam
                    && !$intersection_type instanceof TIterable
                ) {
                    throw new TypeParseTreeException(
                        'Intersection types must all be objects, ' . get_class($intersection_type) . ' provided'
                    );
                }
            }

            /** @var TNamedObject|TGenericParam */
            $first_type = array_shift($intersection_types);

            /** @var array<int, TNamedObject|TGenericParam> $intersection_types */
            $first_type->extra_types = $intersection_types;

            return $first_type;
        }

        if ($parse_tree instanceof ParseTree\ObjectLikeTree) {
            $properties = [];

            $type = $parse_tree->value;

            foreach ($parse_tree->children as $i => $property_branch) {
                if (!$property_branch instanceof ParseTree\ObjectLikePropertyTree) {
                    $property_type = self::getTypeFromTree($property_branch, false, $template_type_map);
                    $property_maybe_undefined = false;
                    $property_key = (string)$i;
                } elseif (count($property_branch->children) === 1) {
                    $property_type = self::getTypeFromTree($property_branch->children[0], false, $template_type_map);
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
                throw new TypeParseTreeException('Unexpected brace character');
            }

            if (!$properties) {
                throw new TypeParseTreeException('No properties supplied for ObjectLike');
            }

            return new ObjectLike($properties);
        }

        if ($parse_tree instanceof ParseTree\CallableWithReturnTypeTree) {
            $callable_type = self::getTypeFromTree($parse_tree->children[0], false, $template_type_map);

            if (!$callable_type instanceof TCallable && !$callable_type instanceof Type\Atomic\Fn) {
                throw new \InvalidArgumentException('Parsing callable tree node should return TCallable');
            }

            if (!isset($parse_tree->children[1])) {
                throw new TypeParseTreeException('Invalid return type');
            }

            $return_type = self::getTypeFromTree($parse_tree->children[1], false, $template_type_map);

            $callable_type->return_type = $return_type instanceof Union ? $return_type : new Union([$return_type]);

            return $callable_type;
        }

        if ($parse_tree instanceof ParseTree\CallableTree) {
            $params = array_map(
                /**
                 * @return FunctionLikeParameter
                 */
                function (ParseTree $child_tree) use ($template_type_map) {
                    $is_variadic = false;
                    $is_optional = false;

                    if ($child_tree instanceof ParseTree\CallableParamTree) {
                        $tree_type = self::getTypeFromTree($child_tree->children[0], false, $template_type_map);
                        $is_variadic = $child_tree->variadic;
                        $is_optional = $child_tree->has_default;
                    } else {
                        $tree_type = self::getTypeFromTree($child_tree, false, $template_type_map);
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
            return self::getTypeFromTree($parse_tree->children[0], false, $template_type_map);
        }

        if ($parse_tree instanceof ParseTree\NullableTree) {
            $non_nullable_type = self::getTypeFromTree($parse_tree->children[0], false, $template_type_map);

            if ($non_nullable_type instanceof Union) {
                $non_nullable_type->addType(new TNull);
                return $non_nullable_type;
            }

            if ($non_nullable_type instanceof Atomic) {
                return TypeCombination::combineTypes([
                    new TNull,
                    $non_nullable_type
                ]);
            }

            throw new \UnexpectedValueException(
                'Was expecting an atomic or union type, got ' . get_class($non_nullable_type)
            );
        }

        if ($parse_tree instanceof ParseTree\MethodTree
            || $parse_tree instanceof ParseTree\MethodWithReturnTypeTree
        ) {
            throw new TypeParseTreeException('Misplaced brackets');
        }

        if (!$parse_tree instanceof ParseTree\Value) {
            throw new \InvalidArgumentException('Unrecognised parse tree type ' . get_class($parse_tree));
        }

        if ($parse_tree->value[0] === '"' || $parse_tree->value[0] === '\'') {
            return new TLiteralString(substr($parse_tree->value, 1, -1));
        }

        if (strpos($parse_tree->value, '::')) {
            list($fq_classlike_name, $const_name) = explode('::', $parse_tree->value);

            if (isset($template_type_map[$fq_classlike_name]) && $const_name === 'class') {
                return self::getGenericParamClass($fq_classlike_name, $template_type_map[$fq_classlike_name]);
            }

            if ($const_name === 'class') {
                return new Atomic\TLiteralClassString($fq_classlike_name);
            }

            return new Atomic\TScalarClassConstant($fq_classlike_name, $const_name);
        }

        if (preg_match('/^\-?(0|[1-9][0-9]*)$/', $parse_tree->value)) {
            return new TLiteralInt((int) $parse_tree->value);
        }

        if (!preg_match('@^(\$this|\\\\?[a-zA-Z_\x7f-\xff][\\\\\-0-9a-zA-Z_\x7f-\xff]*)$@', $parse_tree->value)) {
            throw new TypeParseTreeException('Invalid type \'' . $parse_tree->value . '\'');
        }

        $atomic_type = self::fixScalarTerms($parse_tree->value, $php_compatible);

        return Atomic::create($atomic_type, $php_compatible, $template_type_map);
    }

    private static function getGenericParamClass(string $param_name, Union $as) : Atomic\TGenericParamClass
    {
        if ($as->hasMixed()) {
            return new Atomic\TGenericParamClass(
                $param_name,
                'object'
            );
        }

        if (!$as->isSingle()) {
            throw new TypeParseTreeException(
                'Invalid templated classname \'' . $as . '\''
            );
        }

        foreach ($as->getTypes() as $t) {
            if ($t instanceof TObject) {
                return new Atomic\TGenericParamClass(
                    $param_name
                );
            }

            if ($t instanceof TIterable) {
                if ($t instanceof TGenericIterable) {
                    $traversable = new TGenericObject(
                        'Traversable',
                        $t->type_params
                    );

                    $as->substitute(new Union([$t]), new Union([$traversable]));
                    return new Atomic\TGenericParamClass(
                        $param_name,
                        $traversable->value,
                        new Union([$traversable])
                    );
                }

                $traversable = new TNamedObject('Traversable');
                $as->substitute(new Union([$t]), new Union([$traversable]));

                return new Atomic\TGenericParamClass(
                    $param_name,
                    $traversable->value,
                    new Union([$traversable])
                );
            }

            if (!$t instanceof TNamedObject) {
                throw new TypeParseTreeException(
                    'Invalid templated classname \'' . $t . '\''
                );
            }

            return new Atomic\TGenericParamClass(
                $param_name,
                $t->value,
                new Union([$t])
            );
        }

        throw new \LogicException('Should never get here');
    }

    /**
     * @param  string $string_type
     * @param  bool   $ignore_space
     *
     * @return array<int,string>
     */
    public static function tokenize($string_type, $ignore_space = true)
    {
        $type_tokens = [''];
        $was_char = false;
        $quote_char = null;
        $escaped = false;

        if (isset(self::$memoized_tokens[$string_type])) {
            return self::$memoized_tokens[$string_type];
        }

        // index of last type token
        $rtc = 0;

        $chars = str_split($string_type);
        for ($i = 0, $c = count($chars); $i < $c; ++$i) {
            $char = $chars[$i];

            if (!$quote_char && $char === ' ' && $ignore_space) {
                continue;
            }

            if ($was_char) {
                $type_tokens[++$rtc] = '';
            }

            if ($quote_char) {
                if ($char === $quote_char && $i > 1 && !$escaped) {
                    $quote_char = null;

                    $type_tokens[$rtc] .= $char;
                    $was_char = true;

                    continue;
                }

                $was_char = false;

                if ($char === '\\'
                    && !$escaped
                    && $i < $c - 1
                    && ($chars[$i + 1] === $quote_char || $chars[$i + 1] === '\\')
                ) {
                    $escaped = true;
                    continue;
                }

                $escaped = false;

                $type_tokens[$rtc] .= $char;

                continue;
            }

            if ($char === '"' || $char === '\'') {
                if ($type_tokens[$rtc] === '') {
                    $type_tokens[$rtc] = $char;
                } else {
                    $type_tokens[++$rtc] = $char;
                }

                $quote_char = $char;

                $was_char = false;
                continue;
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
                || $char === '='
            ) {
                if ($type_tokens[$rtc] === '') {
                    $type_tokens[$rtc] = $char;
                } else {
                    $type_tokens[++$rtc] = $char;
                }

                $was_char = true;

                continue;
            }

            if ($char === ':') {
                if ($i + 1 < $c && $chars[$i + 1] === ':') {
                    if ($type_tokens[$rtc] === '') {
                        $type_tokens[$rtc] = '::';
                    } else {
                        $type_tokens[++$rtc] = '::';
                    }

                    $was_char = true;

                    $i++;

                    continue;
                }

                if ($type_tokens[$rtc] === '') {
                    $type_tokens[$rtc] = ':';
                } else {
                    $type_tokens[++$rtc] = ':';
                }

                $was_char = true;

                continue;
            }

            if ($char === '.') {
                if ($i + 2 > $c || $chars[$i + 1] !== '.' || $chars[$i + 2] !== '.') {
                    throw new TypeParseTreeException('Unexpected token ' . $char);
                }

                if ($type_tokens[$rtc] === '') {
                    $type_tokens[$rtc] = '...';
                } else {
                    $type_tokens[++$rtc] = '...';
                }

                $was_char = true;

                $i += 2;

                continue;
            }

            $type_tokens[$rtc] .= $char;
            $was_char = false;
        }

        self::$memoized_tokens[$string_type] = $type_tokens;

        return $type_tokens;
    }

    /**
     * @param  string                       $string_type
     * @param  Aliases                      $aliases
     * @param  array<string, string>|null   $template_type_map
     * @param  array<string, array<int, string>>|null   $type_aliases
     *
     * @return array<int, string>
     */
    public static function fixUpLocalType(
        $string_type,
        Aliases $aliases,
        array $template_type_map = null,
        array $type_aliases = null
    ) {
        $type_tokens = self::tokenize($string_type);

        for ($i = 0, $l = count($type_tokens); $i < $l; $i++) {
            $string_type_token = $type_tokens[$i];

            if (in_array(
                $string_type_token,
                ['<', '>', '|', '?', ',', '{', '}', ':', '::', '[', ']', '(', ')', '&'],
                true
            )) {
                continue;
            }

            if ($string_type_token[0] === '"'
                || $string_type_token[0] === '\''
                || $string_type_token === '0'
                || preg_match('/[1-9]/', $string_type_token[0])
            ) {
                continue;
            }

            if (isset($type_tokens[$i + 1]) && $type_tokens[$i + 1] === ':') {
                continue;
            }

            if ($i > 0 && $type_tokens[$i - 1] === '::') {
                continue;
            }

            $type_tokens[$i] = $string_type_token = self::fixScalarTerms($string_type_token);

            if (isset(self::$PSALM_RESERVED_WORDS[$string_type_token])) {
                continue;
            }

            if (isset($template_type_map[$string_type_token])) {
                continue;
            }

            if (isset($type_tokens[$i + 1])) {
                $next_char = $type_tokens[$i + 1];
                if ($next_char === ':') {
                    continue;
                }

                if ($next_char === '?' && isset($type_tokens[$i + 2]) && $type_tokens[$i + 2] === ':') {
                    continue;
                }
            }

            if ($string_type_token[0] === '$') {
                continue;
            }

            if (isset($type_aliases[$string_type_token])) {
                $replacement_tokens = $type_aliases[$string_type_token];

                array_unshift($replacement_tokens, '(');
                array_push($replacement_tokens, ')');

                $diff = count($replacement_tokens) - 1;

                array_splice($type_tokens, $i, 1, $replacement_tokens);

                $i += $diff;
                $l += $diff;
            } else {
                $type_tokens[$i] = self::getFQCLNFromString(
                    $string_type_token,
                    $aliases
                );
            }
        }

        return $type_tokens;
    }

    /**
     * @param  string                   $class
     * @param  Aliases                  $aliases
     *
     * @return string
     */
    public static function getFQCLNFromString($class, Aliases $aliases)
    {
        if ($class === '') {
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
    public static function getInt($from_calculation = false, $value = null)
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
    public static function getString($value = null)
    {
        if ($value !== null) {
            $type = new TLiteralString($value);
        } else {
            $type = new TString();
        }

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getSingleLetter()
    {
        $type = new TSingleLetter;

        return new Union([$type]);
    }

    /**
     * @param string $extends
     *
     * @return Type\Union
     */
    public static function getClassString($extends = 'object')
    {
        return new Union([new TClassString($extends)]);
    }

    /**
     * @param string $class_type
     *
     * @return Type\Union
     */
    public static function getLiteralClassString($class_type)
    {
        $type = new TLiteralClassString($class_type);

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
     * @param bool $from_loop_isset
     *
     * @return Type\Union
     */
    public static function getMixed($from_loop_isset = false)
    {
        $type = new TMixed($from_loop_isset);

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
    public static function getFloat($value = null)
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
    public static function getArrayKey()
    {
        $type = new TArrayKey();

        return new Union([$type]);
    }

    /**
     * @return Type\Union
     */
    public static function getArray()
    {
        $type = new TArray(
            [
                new Type\Union([new TArrayKey]),
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
     * @param  int    $literal_limit any greater number of literal types than this
     *                               will be merged to a scalar
     *
     * @return Union
     */
    public static function combineUnionTypes(
        Union $type_1,
        Union $type_2,
        Codebase $codebase = null,
        bool $overwrite_empty_array = false,
        bool $allow_mixed_union = true,
        int $literal_limit = 500
    ) {
        if ($type_1->isVanillaMixed() && $type_2->isVanillaMixed()) {
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

            $combined_type = TypeCombination::combineTypes(
                array_merge(
                    array_values($type_1->getTypes()),
                    array_values($type_2->getTypes())
                ),
                $codebase,
                $overwrite_empty_array,
                $allow_mixed_union,
                $literal_limit
            );

            if (!$type_1->initialized || !$type_2->initialized) {
                $combined_type->initialized = false;
            }

            if ($type_1->possibly_undefined_from_try || $type_2->possibly_undefined_from_try) {
                $combined_type->possibly_undefined_from_try = true;
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
}
