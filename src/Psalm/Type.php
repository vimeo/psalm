<?php
namespace Psalm;

use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_push;
use function array_shift;
use function array_splice;
use function array_unshift;
use function array_values;
use function count;
use function explode;
use function get_class;
use function implode;
use function in_array;
use function is_numeric;
use function preg_match;
use function preg_quote;
use function preg_replace;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Type\ParseTree;
use Psalm\Internal\Type\TypeCombination;
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
use Psalm\Type\Atomic\TGenericObject;
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
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TVoid;
use Psalm\Type\Union;
use function str_split;
use function stripos;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

abstract class Type
{
    /**
     * @var array<string, bool>
     */
    const PSALM_RESERVED_WORDS = [
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
        'non-empty-array' => true,
        'iterable' => true,
        'null' => true,
        'mixed' => true,
        'numeric-string' => true,
        'class-string' => true,
        'callable-string' => true,
        'trait-string' => true,
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
        'key-of' => true,
        'value-of' => true,
        'non-empty-countable' => true,
    ];

    /**
     * @var array<string, array<int, array{0: string, 1: int}>>
     */
    private static $memoized_tokens = [];

    /**
     * Parses a string type representation
     *
     * @param  string $type_string
     * @param  array{int,int}|null   $php_version
     * @param  array<string, array<string, array{Type\Union}>> $template_type_map
     *
     * @return Union
     */
    public static function parseString(
        $type_string,
        array $php_version = null,
        array $template_type_map = []
    ) {
        return self::parseTokens(self::tokenize($type_string), $php_version, $template_type_map);
    }

    /**
     * Parses a string type representation
     *
     * @param  array<int, array{0: string, 1: int}> $type_tokens
     * @param  array{int,int}|null   $php_version
     * @param  array<string, array<string, array{Type\Union}>> $template_type_map
     *
     * @return Union
     */
    public static function parseTokens(
        array $type_tokens,
        array $php_version = null,
        array $template_type_map = []
    ) {
        if (count($type_tokens) === 1) {
            $only_token = $type_tokens[0];

            // Note: valid identifiers can include class names or $this
            if (!preg_match('@^(\$this|\\\\?[a-zA-Z_\x7f-\xff][\\\\\-0-9a-zA-Z_\x7f-\xff]*)$@', $only_token[0])) {
                throw new TypeParseTreeException("Invalid type '$only_token[0]'");
            }

            $only_token[0] = self::fixScalarTerms($only_token[0], $php_version);

            $atomic = Atomic::create($only_token[0], $php_version, $template_type_map);
            $atomic->offset_start = 0;
            $atomic->offset_end = strlen($only_token[0]);

            return new Union([$atomic]);
        }

        $parse_tree = ParseTree::createFromTokens($type_tokens);
        $parsed_type = self::getTypeFromTree($parse_tree, $php_version, $template_type_map);

        if (!($parsed_type instanceof Union)) {
            $parsed_type = new Union([$parsed_type]);
        }

        return $parsed_type;
    }

    /**
     * @param  string $type_string
     * @param  array{int,int}|null   $php_version
     *
     * @return string
     */
    private static function fixScalarTerms(
        string $type_string,
        ?array $php_version = null
    ) : string {
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
                return $type_string_lc;
        }

        switch ($type_string) {
            case 'boolean':
                return $php_version !== null ? $type_string : 'bool';

            case 'integer':
                return $php_version !== null ? $type_string : 'int';

            case 'double':
            case 'real':
                return $php_version !== null ? $type_string : 'float';
        }

        return $type_string;
    }

    /**
     * @param  ParseTree $parse_tree
     * @param  array{int,int}|null   $php_version
     * @param  array<string, array<string, array{Type\Union}>> $template_type_map
     *
     * @return  Atomic|TArray|TGenericObject|ObjectLike|Union
     */
    public static function getTypeFromTree(
        ParseTree $parse_tree,
        array $php_version = null,
        array $template_type_map = []
    ) {
        if ($parse_tree instanceof ParseTree\GenericTree) {
            $generic_type = $parse_tree->value;

            $generic_params = array_map(
                /**
                 * @return Union
                 */
                function (ParseTree $child_tree) use ($template_type_map) {
                    $tree_type = self::getTypeFromTree($child_tree, null, $template_type_map);

                    return $tree_type instanceof Union ? $tree_type : new Union([$tree_type]);
                },
                $parse_tree->children
            );

            $generic_type_value = self::fixScalarTerms($generic_type);

            if (($generic_type_value === 'array' || $generic_type_value === 'non-empty-array')
                && count($generic_params) === 1
            ) {
                array_unshift($generic_params, new Union([new TArrayKey]));
            } elseif (($generic_type_value === 'iterable' || $generic_type_value === 'Traversable')
                && count($generic_params) === 1
            ) {
                array_unshift($generic_params, new Union([new TMixed]));
            } elseif ($generic_type_value === 'Generator') {
                if (count($generic_params) === 1) {
                    array_unshift($generic_params, new Union([new TMixed]));
                }

                for ($i = 0, $l = 4 - count($generic_params); $i < $l; ++$i) {
                    $generic_params[] = new Union([new TMixed]);
                }
            }

            if (!$generic_params) {
                throw new TypeParseTreeException('No generic params provided for type');
            }

            if ($generic_type_value === 'array') {
                return new TArray($generic_params);
            }

            if ($generic_type_value === 'non-empty-array') {
                return new Type\Atomic\TNonEmptyArray($generic_params);
            }

            if ($generic_type_value === 'iterable') {
                return new TIterable($generic_params);
            }

            if ($generic_type_value === 'class-string') {
                $class_name = (string) $generic_params[0];

                if (isset($template_type_map[$class_name])) {
                    $first_class = array_keys($template_type_map[$class_name])[0];

                    return self::getGenericParamClass(
                        $class_name,
                        $template_type_map[$class_name][$first_class][0],
                        $first_class
                    );
                }

                $param_union_types = array_values($generic_params[0]->getTypes());

                if (count($param_union_types) > 1) {
                    throw new TypeParseTreeException('Union types are not allowed in class string param');
                }

                if (!$param_union_types[0] instanceof TNamedObject) {
                    throw new TypeParseTreeException('Class string param should be a named object');
                }

                return new TClassString($class_name, $param_union_types[0]);
            }

            if ($generic_type_value === 'key-of') {
                $param_name = (string) $generic_params[0];

                if (isset($template_type_map[$param_name])) {
                    $defining_class = array_keys($template_type_map[$param_name])[0];

                    return new Atomic\TTemplateKeyOf(
                        $param_name,
                        $defining_class
                    );
                }

                $param_union_types = array_values($generic_params[0]->getTypes());

                if (count($param_union_types) > 1) {
                    throw new TypeParseTreeException('Union types are not allowed in key-of type');
                }

                if (!$param_union_types[0] instanceof Atomic\TScalarClassConstant) {
                    throw new TypeParseTreeException(
                        'Untemplated key-of param ' . $param_name . ' should be a class constant'
                    );
                }

                return new Atomic\TKeyOfClassConstant(
                    $param_union_types[0]->fq_classlike_name,
                    $param_union_types[0]->const_name
                );
            }

            if ($generic_type_value === 'value-of') {
                $param_name = (string) $generic_params[0];

                if (isset($template_type_map[$param_name])) {
                    $defining_class = array_keys($template_type_map[$param_name])[0];

                    return new Atomic\TTemplateKeyOf(
                        $param_name,
                        $defining_class
                    );
                }

                $param_union_types = array_values($generic_params[0]->getTypes());

                if (count($param_union_types) > 1) {
                    throw new TypeParseTreeException('Union types are not allowed in value-of type');
                }

                if (!$param_union_types[0] instanceof Atomic\TScalarClassConstant) {
                    throw new TypeParseTreeException(
                        'Untemplated value-of param ' . $param_name . ' should be a class constant'
                    );
                }

                return new Atomic\TValueOfClassConstant(
                    $param_union_types[0]->fq_classlike_name,
                    $param_union_types[0]->const_name
                );
            }

            if (isset(self::PSALM_RESERVED_WORDS[$generic_type_value])
                && $generic_type_value !== 'self'
                && $generic_type_value !== 'static'
            ) {
                throw new TypeParseTreeException('Cannot create generic object with reserved word');
            }

            return new TGenericObject($generic_type_value, $generic_params);
        }

        if ($parse_tree instanceof ParseTree\UnionTree) {
            $has_null = false;

            $atomic_types = [];

            foreach ($parse_tree->children as $child_tree) {
                if ($child_tree instanceof ParseTree\NullableTree) {
                    $atomic_type = self::getTypeFromTree($child_tree->children[0], null, $template_type_map);
                    $has_null = true;
                } else {
                    $atomic_type = self::getTypeFromTree($child_tree, null, $template_type_map);
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
                    $atomic_type = self::getTypeFromTree($child_tree, null, $template_type_map);

                    if (!$atomic_type instanceof Atomic) {
                        throw new TypeParseTreeException(
                            'Intersection types cannot contain unions'
                        );
                    }

                    return $atomic_type;
                },
                $parse_tree->children
            );

            $keyed_intersection_types = [];

            foreach ($intersection_types as $intersection_type) {
                if (!$intersection_type instanceof TIterable
                    && !$intersection_type instanceof TNamedObject
                    && !$intersection_type instanceof TTemplateParam
                    && !$intersection_type instanceof TObjectWithProperties
                ) {
                    throw new TypeParseTreeException(
                        'Intersection types must all be objects, ' . get_class($intersection_type) . ' provided'
                    );
                }

                $keyed_intersection_types[
                    $intersection_type instanceof TIterable
                        ? $intersection_type->getId()
                        : $intersection_type->getKey()
                    ] = $intersection_type;
            }

            $first_type = array_shift($keyed_intersection_types);

            $first_type->extra_types = $keyed_intersection_types;

            return $first_type;
        }

        if ($parse_tree instanceof ParseTree\ObjectLikeTree) {
            $properties = [];

            $type = $parse_tree->value;

            foreach ($parse_tree->children as $i => $property_branch) {
                if (!$property_branch instanceof ParseTree\ObjectLikePropertyTree) {
                    $property_type = self::getTypeFromTree($property_branch, null, $template_type_map);
                    $property_maybe_undefined = false;
                    $property_key = (string)$i;
                } elseif (count($property_branch->children) === 1) {
                    $property_type = self::getTypeFromTree($property_branch->children[0], null, $template_type_map);
                    $property_maybe_undefined = $property_branch->possibly_undefined;
                    $property_key = $property_branch->value;
                } else {
                    throw new TypeParseTreeException(
                        'Missing property type'
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

            if ($type !== 'array' && $type !== 'object') {
                throw new TypeParseTreeException('Unexpected brace character');
            }

            if (!$properties) {
                throw new TypeParseTreeException('No properties supplied for ObjectLike');
            }

            if ($type === 'object') {
                return new TObjectWithProperties($properties);
            }

            return new ObjectLike($properties);
        }

        if ($parse_tree instanceof ParseTree\CallableWithReturnTypeTree) {
            $callable_type = self::getTypeFromTree($parse_tree->children[0], null, $template_type_map);

            if (!$callable_type instanceof TCallable && !$callable_type instanceof Type\Atomic\TFn) {
                throw new \InvalidArgumentException('Parsing callable tree node should return TCallable');
            }

            if (!isset($parse_tree->children[1])) {
                throw new TypeParseTreeException('Invalid return type');
            }

            $return_type = self::getTypeFromTree($parse_tree->children[1], null, $template_type_map);

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
                        $tree_type = self::getTypeFromTree($child_tree->children[0], null, $template_type_map);
                        $is_variadic = $child_tree->variadic;
                        $is_optional = $child_tree->has_default;
                    } else {
                        if ($child_tree instanceof ParseTree\Value && strpos($child_tree->value, '$') > 0) {
                            $child_tree->value = preg_replace('/(.+)\$.*/', '$1', $child_tree->value);
                        }

                        $tree_type = self::getTypeFromTree($child_tree, null, $template_type_map);
                    }

                    $tree_type = $tree_type instanceof Union ? $tree_type : new Union([$tree_type]);

                    $param = new FunctionLikeParameter(
                        '',
                        false,
                        $tree_type,
                        null,
                        null,
                        $is_optional,
                        false,
                        $is_variadic
                    );

                    // type is not authoratative
                    $param->signature_type = null;

                    return $param;
                },
                $parse_tree->children
            );

            if (in_array(strtolower($parse_tree->value), ['closure', '\closure'], true)) {
                return new Type\Atomic\TFn('Closure', $params);
            }

            return new TCallable($parse_tree->value, $params);
        }

        if ($parse_tree instanceof ParseTree\EncapsulationTree) {
            return self::getTypeFromTree($parse_tree->children[0], null, $template_type_map);
        }

        if ($parse_tree instanceof ParseTree\NullableTree) {
            if (!isset($parse_tree->children[0])) {
                throw new TypeParseTreeException('Misplaced question mark');
            }

            $non_nullable_type = self::getTypeFromTree($parse_tree->children[0], null, $template_type_map);

            if ($non_nullable_type instanceof Union) {
                $non_nullable_type->addType(new TNull);

                return $non_nullable_type;
            }

            if ($non_nullable_type instanceof Atomic) {
                return TypeCombination::combineTypes([
                    new TNull,
                    $non_nullable_type,
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

        if ($parse_tree instanceof ParseTree\IndexedAccessTree) {
            if (!isset($parse_tree->children[0]) || !$parse_tree->children[0] instanceof ParseTree\Value) {
                throw new TypeParseTreeException('Unrecognised indexed access');
            }

            $offset_param_name = $parse_tree->value;
            $array_param_name = $parse_tree->children[0]->value;

            if (!isset($template_type_map[$offset_param_name])) {
                throw new TypeParseTreeException('Unrecognised template param ' . $offset_param_name);
            }

            if (!isset($template_type_map[$array_param_name])) {
                throw new TypeParseTreeException('Unrecognised template param ' . $array_param_name);
            }

            $offset_template_data = $template_type_map[$offset_param_name];

            $offset_defining_class = array_keys($offset_template_data)[0];

            if (!$offset_defining_class && $offset_template_data[''][0]->isSingle()) {
                $offset_template_type = array_values($offset_template_data[''][0]->getTypes())[0];

                if ($offset_template_type instanceof Type\Atomic\TTemplateKeyOf) {
                    $offset_defining_class = (string) $offset_template_type->defining_class;
                }
            }

            $array_defining_class = array_keys($template_type_map[$array_param_name])[0];

            if ($offset_defining_class !== $array_defining_class) {
                throw new TypeParseTreeException('Template params are defined in different locations');
            }

            return new Atomic\TTemplateIndexedAccess(
                $array_param_name,
                $offset_param_name,
                $array_defining_class
            );
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
                $first_class = array_keys($template_type_map[$fq_classlike_name])[0];

                return self::getGenericParamClass(
                    $fq_classlike_name,
                    $template_type_map[$fq_classlike_name][$first_class][0],
                    $first_class
                );
            }

            if ($const_name === 'class') {
                return new Atomic\TLiteralClassString($fq_classlike_name);
            }

            return new Atomic\TScalarClassConstant($fq_classlike_name, $const_name);
        }

        if (preg_match('/^\-?(0|[1-9][0-9]*)(\.[0-9]{1,})$/', $parse_tree->value)) {
            return new TLiteralFloat((float) $parse_tree->value);
        }

        if (preg_match('/^\-?(0|[1-9][0-9]*)$/', $parse_tree->value)) {
            return new TLiteralInt((int) $parse_tree->value);
        }

        if (!preg_match('@^(\$this|\\\\?[a-zA-Z_\x7f-\xff][\\\\\-0-9a-zA-Z_\x7f-\xff]*)$@', $parse_tree->value)) {
            throw new TypeParseTreeException('Invalid type \'' . $parse_tree->value . '\'');
        }

        $atomic_type_string = self::fixScalarTerms($parse_tree->value, $php_version);

        $atomic_type = Atomic::create($atomic_type_string, $php_version, $template_type_map);

        $atomic_type->offset_start = $parse_tree->offset_start;
        $atomic_type->offset_end = $parse_tree->offset_end;

        return $atomic_type;
    }

    private static function getGenericParamClass(
        string $param_name,
        Union $as,
        string $defining_class = null
    ) : Atomic\TTemplateParamClass {
        if ($as->hasMixed()) {
            return new Atomic\TTemplateParamClass(
                $param_name,
                'object',
                null,
                $defining_class
            );
        }

        if (!$as->isSingle()) {
            throw new TypeParseTreeException(
                'Invalid templated classname \'' . $as . '\''
            );
        }

        foreach ($as->getTypes() as $t) {
            if ($t instanceof TObject) {
                return new Atomic\TTemplateParamClass(
                    $param_name,
                    'object',
                    null,
                    $defining_class
                );
            }

            if ($t instanceof TIterable) {
                $traversable = new TGenericObject(
                    'Traversable',
                    $t->type_params
                );

                $as->substitute(new Union([$t]), new Union([$traversable]));

                return new Atomic\TTemplateParamClass(
                    $param_name,
                    $traversable->value,
                    $traversable,
                    $defining_class
                );
            }

            if (!$t instanceof TNamedObject) {
                throw new TypeParseTreeException(
                    'Invalid templated classname \'' . $t . '\''
                );
            }

            return new Atomic\TTemplateParamClass(
                $param_name,
                $t->value,
                $t,
                $defining_class
            );
        }

        throw new \LogicException('Should never get here');
    }

    /**
     * @param  string $string_type
     * @param  bool   $ignore_space
     *
     * @return array<int, array{0: string, 1: int}>
     */
    public static function tokenize($string_type, $ignore_space = true)
    {
        $type_tokens = [['', 0]];
        $was_char = false;
        $quote_char = null;
        $escaped = false;

        if (isset(self::$memoized_tokens[$string_type])) {
            return self::$memoized_tokens[$string_type];
        }

        // index of last type token
        $rtc = 0;

        $chars = str_split($string_type);
        $was_space = false;

        for ($i = 0, $c = count($chars); $i < $c; ++$i) {
            $char = $chars[$i];

            if (!$quote_char && $char === ' ' && $ignore_space) {
                $was_space = true;
                continue;
            }

            if ($was_space && ($char === '$' || $char === '.')) {
                $type_tokens[++$rtc] = [' ', $i - 1];
                $type_tokens[++$rtc] = ['', $i];
            } elseif ($was_char) {
                $type_tokens[++$rtc] = ['', $i];
            }

            if ($quote_char) {
                if ($char === $quote_char && $i > 1 && !$escaped) {
                    $quote_char = null;

                    $type_tokens[$rtc][0] .= $char;
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

                $type_tokens[$rtc][0] .= $char;

                continue;
            }

            if ($char === '"' || $char === '\'') {
                if ($type_tokens[$rtc][0] === '') {
                    $type_tokens[$rtc] = [$char, $i];
                } else {
                    $type_tokens[++$rtc] = [$char, $i];
                }

                $quote_char = $char;

                $was_char = false;
                $was_space = false;

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
                if ($type_tokens[$rtc][0] === '') {
                    $type_tokens[$rtc] = [$char, $i];
                } else {
                    $type_tokens[++$rtc] = [$char, $i];
                }

                $was_char = true;
                $was_space = false;

                continue;
            }

            if ($char === ':') {
                if ($i + 1 < $c && $chars[$i + 1] === ':') {
                    if ($type_tokens[$rtc][0] === '') {
                        $type_tokens[$rtc] = ['::', $i];
                    } else {
                        $type_tokens[++$rtc] = ['::', $i];
                    }

                    $was_char = true;
                    $was_space = false;

                    ++$i;

                    continue;
                }

                if ($type_tokens[$rtc][0] === '') {
                    $type_tokens[$rtc] = [':', $i];
                } else {
                    $type_tokens[++$rtc] = [':', $i];
                }

                $was_char = true;
                $was_space = false;

                continue;
            }

            if ($char === '.') {
                if ($i + 1 < $c
                    && is_numeric($chars[$i + 1])
                    && $i > 0
                    && is_numeric($chars[$i - 1])
                ) {
                    $type_tokens[$rtc][0] .= $char;
                    $was_char = false;
                    $was_space = false;

                    continue;
                }

                if ($i + 2 > $c || $chars[$i + 1] !== '.' || $chars[$i + 2] !== '.') {
                    throw new TypeParseTreeException('Unexpected token ' . $char);
                }

                if ($type_tokens[$rtc][0] === '') {
                    $type_tokens[$rtc] = ['...', $i];
                } else {
                    $type_tokens[++$rtc] = ['...', $i];
                }

                $was_char = true;
                $was_space = false;

                $i += 2;

                continue;
            }

            $type_tokens[$rtc][0] .= $char;
            $was_char = false;
            $was_space = false;
        }

        self::$memoized_tokens[$string_type] = $type_tokens;

        return $type_tokens;
    }

    /**
     * @param  array<string, mixed>|null    $template_type_map
     * @param  array<string, array<int, array{0: string, 1: int}>>|null   $type_aliases
     *
     * @return array<int, array{0: string, 1: int}>
     */
    public static function fixUpLocalType(
        string $string_type,
        Aliases $aliases,
        array $template_type_map = null,
        array $type_aliases = null,
        ?string $self_fqcln = null,
        ?string $parent_fqcln = null
    ) {
        $type_tokens = self::tokenize($string_type);

        for ($i = 0, $l = count($type_tokens); $i < $l; ++$i) {
            $string_type_token = $type_tokens[$i];

            if (in_array(
                $string_type_token[0],
                [
                    '<', '>', '|', '?', ',', '{', '}', ':', '::', '[', ']', '(', ')', '&', '=', '...',
                ],
                true
            )) {
                continue;
            }

            if ($string_type_token[0][0] === '\\'
                && strlen($string_type_token[0]) === 1
            ) {
                throw new TypeParseTreeException("Backslash \"\\\" has to be part of class name.");
            }

            if ($string_type_token[0][0] === '"'
                || $string_type_token[0][0] === '\''
                || $string_type_token[0] === '0'
                || preg_match('/[1-9]/', $string_type_token[0][0])
            ) {
                continue;
            }

            if (isset($type_tokens[$i + 1]) && $type_tokens[$i + 1][0] === ':') {
                continue;
            }

            if ($i > 0 && $type_tokens[$i - 1][0] === '::') {
                continue;
            }

            if (strpos($string_type_token[0], '$')) {
                $string_type_token[0] = preg_replace('/(.+)\$.*/', '$1', $string_type_token[0]);
            }

            $type_tokens[$i][0]
                = $string_type_token[0]
                = self::fixScalarTerms($string_type_token[0]);

            if ($string_type_token[0] === 'self' && $self_fqcln) {
                $type_tokens[$i][0] = $self_fqcln;
                continue;
            }

            if ($string_type_token[0] === 'parent' && $parent_fqcln) {
                $type_tokens[$i][0] = $parent_fqcln;
                continue;
            }

            if (isset(self::PSALM_RESERVED_WORDS[$string_type_token[0]])) {
                continue;
            }

            if (isset($template_type_map[$string_type_token[0]])) {
                continue;
            }

            if (isset($type_tokens[$i + 1])) {
                $next_char = $type_tokens[$i + 1][0];
                if ($next_char === ':') {
                    continue;
                }

                if ($next_char === '?' && isset($type_tokens[$i + 2]) && $type_tokens[$i + 2][0] === ':') {
                    continue;
                }
            }

            if ($string_type_token[0][0] === '$' || $string_type_token[0][0] === ' ') {
                continue;
            }

            if (isset($type_tokens[$i + 1]) && $type_tokens[$i + 1][0] === '(') {
                continue;
            }

            if (isset($type_aliases[$string_type_token[0]])) {
                $replacement_tokens = $type_aliases[$string_type_token[0]];

                array_unshift($replacement_tokens, ['(', $i]);
                array_push($replacement_tokens, [')', $i]);

                $diff = count($replacement_tokens) - 1;

                array_splice($type_tokens, $i, 1, $replacement_tokens);

                $i += $diff;
                $l += $diff;
            } else {
                $type_tokens[$i][0] = self::getFQCLNFromString(
                    $string_type_token[0],
                    $aliases
                );
            }
        }

        return $type_tokens;
    }

    public static function getFQCLNFromString(
        string $class,
        Aliases $aliases
    ) : string {
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
     * @param  array<string, string> $aliased_classes
     */
    public static function getStringFromFQCLN(
        string $value,
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class
    ) : string {
        if ($value === $this_class) {
            return 'self';
        }

        if (isset($aliased_classes[strtolower($value)])) {
            return $aliased_classes[strtolower($value)];
        }

        if ($namespace && stripos($value, $namespace . '\\') === 0) {
            $candidate = preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $value
            );

            $candidate_parts = explode('\\', $candidate);

            if (!isset($aliased_classes[strtolower($candidate_parts[0])])) {
                return $candidate;
            }
        } elseif (!$namespace && stripos($value, '\\') === false) {
            return $value;
        }

        if (strpos($value, '\\')) {
            $parts = explode('\\', $value);

            $suffix = array_pop($parts);

            while ($parts) {
                $left = implode('\\', $parts);

                if (isset($aliased_classes[strtolower($left)])) {
                    return $aliased_classes[strtolower($left)] . '\\' . $suffix;
                }

                $suffix = array_pop($parts) . '\\' . $suffix;
            }
        }

        return '\\' . $value;
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
        $type = null;

        if ($value !== null) {
            $config = \Psalm\Config::getInstance();

            if ($config->string_interpreters) {
                foreach ($config->string_interpreters as $string_interpreter) {
                    if ($type = $string_interpreter::getTypeFromValue($value)) {
                        break;
                    }
                }
            }

            if (!$type && strlen($value) < $config->max_string_length) {
                $type = new TLiteralString($value);
            }
        }

        if (!$type) {
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
        return new Union([
            new TClassString(
                $extends,
                $extends === 'object'
                    ? null
                    : new TNamedObject($extends)
            ),
        ]);
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
        if ($type_1 === $type_2) {
            return $type_1;
        }

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

            if ($type_1->had_template && $type_2->had_template) {
                $combined_type->had_template = true;
            }

            if ($both_failed_reconciliation) {
                $combined_type->failed_reconciliation = true;
            }

            if ($type_1->tainted || $type_2->tainted) {
                $combined_type->tainted = $type_1->tainted & $type_2->tainted;
            }
        }

        if ($type_1->possibly_undefined || $type_2->possibly_undefined) {
            $combined_type->possibly_undefined = true;
        }

        if ($type_1->sources || $type_2->sources) {
            $combined_type->sources = \array_unique(
                array_merge($type_1->sources ?: [], $type_2->sources ?: [])
            );
        }

        return $combined_type;
    }

    /**
     * Combines two union types into one via an intersection
     *
     * @param  Union  $type_1
     * @param  Union  $type_2
     *
     * @return Union
     */
    public static function intersectUnionTypes(
        Union $type_1,
        Union $type_2
    ) {
        if ($type_1->isMixed() && $type_2->isMixed()) {
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

            if ($type_1->isMixed() && !$type_2->isMixed()) {
                $combined_type = clone $type_2;
            } elseif (!$type_1->isMixed() && $type_2->isMixed()) {
                $combined_type = clone $type_1;
            } else {
                $combined_type = clone $type_1;

                foreach ($combined_type->getTypes() as $t1_key => $type_1_atomic) {
                    foreach ($type_2->getTypes() as $type_2_atomic) {
                        if (($type_1_atomic instanceof TIterable
                                || $type_1_atomic instanceof TNamedObject
                                || $type_1_atomic instanceof TTemplateParam
                                || $type_1_atomic instanceof TObjectWithProperties)
                            && ($type_2_atomic instanceof TIterable
                                || $type_2_atomic instanceof TNamedObject
                                || $type_2_atomic instanceof TTemplateParam
                                || $type_2_atomic instanceof TObjectWithProperties)
                        ) {
                            if (!$type_1_atomic->extra_types) {
                                $type_1_atomic->extra_types = [];
                            }

                            $type_2_atomic_clone = clone $type_2_atomic;

                            $type_2_atomic_clone->extra_types = [];

                            $type_1_atomic->extra_types[$type_2_atomic_clone->getKey()] = $type_2_atomic_clone;

                            $type_2_atomic_intersection_types = $type_2_atomic->getIntersectionTypes();

                            if ($type_2_atomic_intersection_types) {
                                foreach ($type_2_atomic_intersection_types as $type_2_intersection_type) {
                                    $type_1_atomic->extra_types[$type_2_intersection_type->getKey()]
                                        = clone $type_2_intersection_type;
                                }
                            }
                        }

                        if ($type_1_atomic instanceof TObject && $type_2_atomic instanceof TNamedObject) {
                            $combined_type->removeType($t1_key);
                            $combined_type->addType(clone $type_2_atomic);
                        }
                    }
                }
            }

            if (!$type_1->initialized && !$type_2->initialized) {
                $combined_type->initialized = false;
            }

            if ($type_1->possibly_undefined_from_try && $type_2->possibly_undefined_from_try) {
                $combined_type->possibly_undefined_from_try = true;
            }

            if ($type_1->from_docblock && $type_2->from_docblock) {
                $combined_type->from_docblock = true;
            }

            if ($type_1->from_calculation && $type_2->from_calculation) {
                $combined_type->from_calculation = true;
            }

            if ($type_1->ignore_nullable_issues && $type_2->ignore_nullable_issues) {
                $combined_type->ignore_nullable_issues = true;
            }

            if ($type_1->ignore_falsable_issues && $type_2->ignore_falsable_issues) {
                $combined_type->ignore_falsable_issues = true;
            }

            if ($both_failed_reconciliation) {
                $combined_type->failed_reconciliation = true;
            }
        }

        if ($type_1->possibly_undefined && $type_2->possibly_undefined) {
            $combined_type->possibly_undefined = true;
        }

        return $combined_type;
    }

    public static function clearCache() : void
    {
        self::$memoized_tokens = [];
    }
}
