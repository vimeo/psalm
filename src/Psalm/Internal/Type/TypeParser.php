<?php

namespace Psalm\Internal\Type;

use InvalidArgumentException;
use LogicException;
use Psalm\Codebase;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ArrayAnalyzer;
use Psalm\Internal\Type\ParseTree\CallableParamTree;
use Psalm\Internal\Type\ParseTree\CallableTree;
use Psalm\Internal\Type\ParseTree\CallableWithReturnTypeTree;
use Psalm\Internal\Type\ParseTree\ConditionalTree;
use Psalm\Internal\Type\ParseTree\EncapsulationTree;
use Psalm\Internal\Type\ParseTree\FieldEllipsis;
use Psalm\Internal\Type\ParseTree\GenericTree;
use Psalm\Internal\Type\ParseTree\IndexedAccessTree;
use Psalm\Internal\Type\ParseTree\IntersectionTree;
use Psalm\Internal\Type\ParseTree\KeyedArrayPropertyTree;
use Psalm\Internal\Type\ParseTree\KeyedArrayTree;
use Psalm\Internal\Type\ParseTree\MethodTree;
use Psalm\Internal\Type\ParseTree\MethodWithReturnTypeTree;
use Psalm\Internal\Type\ParseTree\NullableTree;
use Psalm\Internal\Type\ParseTree\TemplateAsTree;
use Psalm\Internal\Type\ParseTree\UnionTree;
use Psalm\Internal\Type\ParseTree\Value;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableKeyedArray;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntMask;
use Psalm\Type\Atomic\TIntMaskOf;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TPropertiesOf;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTemplatePropertiesOf;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\Atomic\TTypeAlias;
use Psalm\Type\Atomic\TUnknownClassString;
use Psalm\Type\Atomic\TValueOf;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_shift;
use function array_unique;
use function array_unshift;
use function array_values;
use function assert;
use function constant;
use function count;
use function defined;
use function end;
use function explode;
use function get_class;
use function in_array;
use function is_int;
use function is_numeric;
use function preg_match;
use function preg_replace;
use function reset;
use function stripslashes;
use function strlen;
use function strpos;
use function strtolower;
use function strtr;
use function substr;

/**
 * @psalm-suppress InaccessibleProperty Allowed during construction
 * @internal
 */
final class TypeParser
{
    /**
     * Parses a string type representation
     *
     * @param  list<array{0: string, 1: int, 2?: string}> $type_tokens
     * @param  array<string, array<string, Union>> $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     */
    public static function parseTokens(
        array $type_tokens,
        ?int $analysis_php_version_id = null,
        array $template_type_map = [],
        array $type_aliases = [],
        bool $from_docblock = false
    ): Union {
        if (count($type_tokens) === 1) {
            $only_token = $type_tokens[0];

            // Note: valid identifiers can include class names or $this
            if (!preg_match('@^(\$this|\\\\?[a-zA-Z_\x7f-\xff][\\\\\-0-9a-zA-Z_\x7f-\xff]*)$@', $only_token[0])) {
                if (!is_numeric($only_token[0])
                    && strpos($only_token[0], '\'') !== false
                    && strpos($only_token[0], '"') !== false
                ) {
                    throw new TypeParseTreeException("Invalid type '$only_token[0]'");
                }
            } else {
                $only_token[0] = TypeTokenizer::fixScalarTerms($only_token[0], $analysis_php_version_id);

                $atomic = Atomic::create(
                    $only_token[0],
                    $analysis_php_version_id,
                    $template_type_map,
                    $type_aliases,
                    0,
                    strlen($only_token[0]),
                    isset($only_token[2]) && $only_token[2] !== $only_token[0] ? $only_token[2] : null,
                    $from_docblock,
                );

                return new Union([$atomic], ['from_docblock' => $from_docblock]);
            }
        }

        $parse_tree = (new ParseTreeCreator($type_tokens))->create();
        $codebase = ProjectAnalyzer::getInstance()->getCodebase();
        $parsed_type = self::getTypeFromTree(
            $parse_tree,
            $codebase,
            $analysis_php_version_id,
            $template_type_map,
            $type_aliases,
            $from_docblock,
        );

        if (!($parsed_type instanceof Union)) {
            $parsed_type = new Union([$parsed_type], ['from_docblock' => $from_docblock]);
        }

        return $parsed_type;
    }

    /**
     * @param  array<string, array<string, Union>> $template_type_map
     * @param  array<string, TypeAlias>            $type_aliases
     * @return  Atomic|Union
     */
    public static function getTypeFromTree(
        ParseTree $parse_tree,
        Codebase  $codebase,
        ?int      $analysis_php_version_id = null,
        array     $template_type_map = [],
        array     $type_aliases = [],
        bool      $from_docblock = false
    ): TypeNode {
        if ($parse_tree instanceof GenericTree) {
            return self::getTypeFromGenericTree(
                $parse_tree,
                $codebase,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );
        }

        if ($parse_tree instanceof UnionTree) {
            return self::getTypeFromUnionTree(
                $parse_tree,
                $codebase,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );
        }

        if ($parse_tree instanceof IntersectionTree) {
            return self::getTypeFromIntersectionTree(
                $parse_tree,
                $codebase,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );
        }

        if ($parse_tree instanceof KeyedArrayTree) {
            return self::getTypeFromKeyedArrayTree(
                $parse_tree,
                $codebase,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );
        }

        if ($parse_tree instanceof CallableWithReturnTypeTree) {
            $callable_type = self::getTypeFromTree(
                $parse_tree->children[0],
                $codebase,
                null,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );

            if (!$callable_type instanceof TCallable && !$callable_type instanceof TClosure) {
                throw new InvalidArgumentException('Parsing callable tree node should return TCallable');
            }

            if (!isset($parse_tree->children[1])) {
                throw new TypeParseTreeException('Invalid return type');
            }

            $return_type = self::getTypeFromTree(
                $parse_tree->children[1],
                $codebase,
                null,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );

            $callable_type->return_type = $return_type instanceof Union
                ? $return_type
                : new Union([$return_type], ['from_docblock' => $from_docblock])
            ;

            return $callable_type;
        }

        if ($parse_tree instanceof CallableTree) {
            return self::getTypeFromCallableTree(
                $parse_tree,
                $codebase,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );
        }

        if ($parse_tree instanceof EncapsulationTree) {
            if (!$parse_tree->terminated) {
                throw new TypeParseTreeException('Unterminated parentheses');
            }

            if (!isset($parse_tree->children[0])) {
                throw new TypeParseTreeException('Empty parentheses');
            }

            return self::getTypeFromTree(
                $parse_tree->children[0],
                $codebase,
                null,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );
        }

        if ($parse_tree instanceof NullableTree) {
            if (!isset($parse_tree->children[0])) {
                throw new TypeParseTreeException('Misplaced question mark');
            }

            $non_nullable_type = self::getTypeFromTree(
                $parse_tree->children[0],
                $codebase,
                null,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );

            if ($non_nullable_type instanceof Union) {
                $non_nullable_type = $non_nullable_type->getBuilder()->addType(new TNull($from_docblock))->freeze();

                return $non_nullable_type;
            }

            return TypeCombiner::combine([
                new TNull($from_docblock),
                $non_nullable_type,
            ]);
        }

        if ($parse_tree instanceof MethodTree
            || $parse_tree instanceof MethodWithReturnTypeTree
        ) {
            throw new TypeParseTreeException('Misplaced brackets');
        }

        if ($parse_tree instanceof IndexedAccessTree) {
            return self::getTypeFromIndexAccessTree($parse_tree, $template_type_map, $from_docblock);
        }

        if ($parse_tree instanceof TemplateAsTree) {
            $result = new TTemplateParam(
                $parse_tree->param_name,
                new Union([new TNamedObject($parse_tree->as)]),
                'class-string-map',
                [],
                $from_docblock,
            );
            return $result;
        }

        if ($parse_tree instanceof ConditionalTree) {
            $template_param_name = $parse_tree->condition->param_name;

            if (!isset($template_type_map[$template_param_name])) {
                throw new TypeParseTreeException('Unrecognized template \'' . $template_param_name . '\'');
            }

            if (count($parse_tree->children) !== 2) {
                throw new TypeParseTreeException('Invalid conditional');
            }

            $first_class = array_keys($template_type_map[$template_param_name])[0];

            $conditional_type = self::getTypeFromTree(
                $parse_tree->condition->children[0],
                $codebase,
                null,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );

            $if_type = self::getTypeFromTree(
                $parse_tree->children[0],
                $codebase,
                null,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );

            $else_type = self::getTypeFromTree(
                $parse_tree->children[1],
                $codebase,
                null,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );

            if ($conditional_type instanceof Atomic) {
                $conditional_type = new Union([$conditional_type], ['from_docblock' => $from_docblock]);
            }

            if ($if_type instanceof Atomic) {
                $if_type = new Union([$if_type], ['from_docblock' => $from_docblock]);
            }

            if ($else_type instanceof Atomic) {
                $else_type = new Union([$else_type], ['from_docblock' => $from_docblock]);
            }

            return new TConditional(
                $template_param_name,
                $first_class,
                $template_type_map[$template_param_name][$first_class],
                $conditional_type,
                $if_type,
                $else_type,
                $from_docblock,
            );
        }

        if (!$parse_tree instanceof Value) {
            throw new InvalidArgumentException('Unrecognised parse tree type ' . get_class($parse_tree));
        }

        if ($parse_tree->value[0] === '"' || $parse_tree->value[0] === '\'') {
            return Type::getAtomicStringFromLiteral(substr($parse_tree->value, 1, -1), $from_docblock);
        }

        if (strpos($parse_tree->value, '::')) {
            [$fq_classlike_name, $const_name] = explode('::', $parse_tree->value);

            if (isset($template_type_map[$fq_classlike_name]) && $const_name === 'class') {
                $first_class = array_keys($template_type_map[$fq_classlike_name])[0];

                return self::getGenericParamClass(
                    $fq_classlike_name,
                    $template_type_map[$fq_classlike_name][$first_class],
                    $first_class,
                    $from_docblock,
                );
            }

            if ($const_name === 'class') {
                return new TLiteralClassString($fq_classlike_name, false, $from_docblock);
            }

            return new TClassConstant($fq_classlike_name, $const_name, $from_docblock);
        }

        if (preg_match('/^\-?(0|[1-9][0-9]*)(\.[0-9]{1,})$/', $parse_tree->value)) {
            return new TLiteralFloat((float) $parse_tree->value, $from_docblock);
        }

        if (preg_match('/^\-?(0|[1-9]([0-9_]*[0-9])?)$/', $parse_tree->value)) {
            return new TLiteralInt((int) strtr($parse_tree->value, ['_' => '']), $from_docblock);
        }

        if (!preg_match('@^(\$this|\\\\?[a-zA-Z_\x7f-\xff][\\\\\-0-9a-zA-Z_\x7f-\xff]*)$@', $parse_tree->value)) {
            throw new TypeParseTreeException('Invalid type \'' . $parse_tree->value . '\'');
        }

        $atomic_type_string = TypeTokenizer::fixScalarTerms($parse_tree->value, $analysis_php_version_id);

        return Atomic::create(
            $atomic_type_string,
            $analysis_php_version_id,
            $template_type_map,
            $type_aliases,
            $parse_tree->offset_start,
            $parse_tree->offset_end,
            $parse_tree->text,
            $from_docblock,
        );
    }

    private static function getGenericParamClass(
        string $param_name,
        Union &$as,
        string $defining_class,
        bool $from_docblock = false
    ): TTemplateParamClass {
        if ($as->hasMixed()) {
            return new TTemplateParamClass(
                $param_name,
                'object',
                null,
                $defining_class,
                $from_docblock,
            );
        }

        foreach ($as->getAtomicTypes() as $t) {
            if ($t instanceof TObject) {
                return new TTemplateParamClass(
                    $param_name,
                    'object',
                    null,
                    $defining_class,
                    $from_docblock,
                );
            }

            if ($t instanceof TIterable) {
                $traversable = new TGenericObject(
                    'Traversable',
                    $t->type_params,
                    false,
                    false,
                    [],
                    $from_docblock,
                );

                $as = $as->getBuilder()->substitute(new Union([$t]), new Union([$traversable]))->freeze();

                return new TTemplateParamClass(
                    $param_name,
                    $traversable->value,
                    $traversable,
                    $defining_class,
                    $from_docblock,
                );
            }

            if ($t instanceof TTemplateParam) {
                $t_atomic_type = count($t->as->getAtomicTypes()) === 1 ? $t->as->getSingleAtomic() : null;

                if (!$t_atomic_type instanceof TNamedObject) {
                    $t_atomic_type = null;
                }

                return new TTemplateParamClass(
                    $t->param_name,
                    $t_atomic_type->value ?? 'object',
                    $t_atomic_type,
                    $t->defining_class,
                    $from_docblock,
                );
            }

            if (!$t instanceof TNamedObject) {
                throw new TypeParseTreeException(
                    'Invalid templated classname \'' . $t->getId() . '\'',
                );
            }

            return new TTemplateParamClass(
                $param_name,
                $t->value,
                $t,
                $defining_class,
                $from_docblock,
            );
        }

        throw new LogicException('Should never get here');
    }

    /**
     * @param  non-empty-list<int>  $potential_ints
     * @return  non-empty-list<TLiteralInt>
     */
    public static function getComputedIntsFromMask(array $potential_ints, bool $from_docblock = false): array
    {
        /** @var list<int> */
        $potential_values = [];

        foreach ($potential_ints as $ith) {
            $new_values = [];

            $new_values[] = $ith;

            if ($ith !== 0) {
                foreach ($potential_values as $potential_value) {
                    $new_values[] = $ith | $potential_value;
                }
            }

            $potential_values = [...$new_values, ...$potential_values];
        }

        array_unshift($potential_values, 0);
        $potential_values = array_unique($potential_values);

        return array_map(
            static fn($int): TLiteralInt => new TLiteralInt($int, $from_docblock),
            array_values($potential_values),
        );
    }

    /**
     * @param  array<string, array<string, Union>> $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     * @return Atomic|Union
     * @throws TypeParseTreeException
     * @psalm-suppress ComplexMethod to be refactored
     */
    private static function getTypeFromGenericTree(
        GenericTree $parse_tree,
        Codebase $codebase,
        array $template_type_map,
        array $type_aliases,
        bool $from_docblock = false
    ) {
        $generic_type = $parse_tree->value;

        $generic_params = [];

        foreach ($parse_tree->children as $i => $child_tree) {
            $tree_type = self::getTypeFromTree(
                $child_tree,
                $codebase,
                null,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );

            if ($generic_type === 'class-string-map'
                && $i === 0
            ) {
                if ($tree_type instanceof TTemplateParam) {
                    $template_type_map[$tree_type->param_name] = ['class-string-map' => $tree_type->as];
                } elseif ($tree_type instanceof TNamedObject) {
                    $template_type_map[$tree_type->value] = ['class-string-map' => Type::getObject()];
                }
            }

            $generic_params[] = $tree_type instanceof Union
                ? $tree_type
                : new Union([$tree_type], ['from_docblock' => $from_docblock])
            ;
        }

        $generic_type_value = TypeTokenizer::fixScalarTerms($generic_type);

        if (($generic_type_value === 'array'
                || $generic_type_value === 'non-empty-array'
                || $generic_type_value === 'associative-array')
            && count($generic_params) === 1
        ) {
            array_unshift($generic_params, new Union([new TArrayKey($from_docblock)]));
        } elseif (count($generic_params) === 1
            && in_array(
                $generic_type_value,
                ['iterable', 'Traversable', 'Iterator', 'IteratorAggregate', 'arraylike-object'],
                true,
            )
        ) {
            array_unshift($generic_params, new Union([new TMixed(false, $from_docblock)]));
        } elseif ($generic_type_value === 'Generator') {
            if (count($generic_params) === 1) {
                array_unshift($generic_params, new Union([new TMixed(false, $from_docblock)]));
            }

            for ($i = 0, $l = 4 - count($generic_params); $i < $l; ++$i) {
                $generic_params[] = new Union([new TMixed(false, $from_docblock)]);
            }
        }

        if (!$generic_params) {
            throw new TypeParseTreeException('No generic params provided for type');
        }

        if ($generic_type_value === 'array'
            || $generic_type_value === 'associative-array'
            || $generic_type_value === 'non-empty-array'
        ) {
            if ($generic_type_value !== 'non-empty-array') {
                $generic_type_value = 'array';
            }

            if ($generic_params[0]->isMixed()) {
                $generic_params[0] = Type::getArrayKey($from_docblock);
            }

            if (count($generic_params) !== 2) {
                throw new TypeParseTreeException('Too many template parameters for '.$generic_type_value);
            }

            if ($type_aliases !== []) {
                $intersection_types = self::resolveTypeAliases(
                    $codebase,
                    $generic_params[0]->getAtomicTypes(),
                );

                if ($intersection_types !== []) {
                    $generic_params[0] = $generic_params[0]->setTypes($intersection_types);
                }
            }

            foreach ($generic_params[0]->getAtomicTypes() as $key => $atomic_type) {
                if ($atomic_type instanceof TLiteralString
                    && ($string_to_int = ArrayAnalyzer::getLiteralArrayKeyInt($atomic_type->value)) !== false
                ) {
                    $builder = $generic_params[0]->getBuilder();
                    $builder->removeType($key);
                    $generic_params[0] = $builder->addType(new TLiteralInt($string_to_int, $from_docblock))->freeze();
                    continue;
                }

                if ($atomic_type instanceof TInt
                    || $atomic_type instanceof TString
                    || $atomic_type instanceof TArrayKey
                    || $atomic_type instanceof TClassConstant // @todo resolve and check types
                    || $atomic_type instanceof TMixed
                    || $atomic_type instanceof TNever
                    || $atomic_type instanceof TTemplateParam
                    || $atomic_type instanceof TTemplateIndexedAccess
                    || $atomic_type instanceof TTemplateValueOf
                    || $atomic_type instanceof TTemplateKeyOf
                    || $atomic_type instanceof TTemplateParamClass
                    || $atomic_type instanceof TTypeAlias
                    || $atomic_type instanceof TValueOf
                    || $atomic_type instanceof TConditional
                    || $atomic_type instanceof TKeyOf
                    || !$from_docblock
                ) {
                    continue;
                }

                if ($codebase->register_stub_files || $codebase->register_autoload_files) {
                    $builder = $generic_params[0]->getBuilder();
                    $builder->removeType($key);

                    if (count($generic_params[0]->getAtomicTypes()) <= 1) {
                        $builder = $builder->addType(new TArrayKey($from_docblock));
                    }

                    $generic_params[0] = $builder->freeze();
                    continue;
                }

                throw new TypeParseTreeException('Invalid array key type ' . $atomic_type->getKey());
            }

            return $generic_type_value === 'array'
                ? new TArray($generic_params, $from_docblock)
                : new TNonEmptyArray($generic_params, null, null, 'non-empty-array', $from_docblock)
            ;
        }

        if ($generic_type_value === 'arraylike-object') {
            $array_acccess = new TGenericObject('ArrayAccess', $generic_params, false, false, [], $from_docblock);
            $countable = new TNamedObject('Countable', false, false, [], $from_docblock);
            return new TGenericObject(
                'Traversable',
                $generic_params,
                false,
                false,
                [
                    $array_acccess->getKey() => $array_acccess,
                    $countable->getKey() => $countable,
                ],
                $from_docblock,
            );
        }

        if ($generic_type_value === 'iterable') {
            if (count($generic_params) > 2) {
                throw new TypeParseTreeException('Too many template parameters for iterable');
            }
            return new TIterable($generic_params, [], $from_docblock);
        }

        if ($generic_type_value === 'list') {
            if (count($generic_params) > 1) {
                throw new TypeParseTreeException('Too many template parameters for list');
            }
            return Type::getListAtomic($generic_params[0], $from_docblock);
        }

        if ($generic_type_value === 'non-empty-list') {
            return Type::getNonEmptyListAtomic($generic_params[0], $from_docblock);
        }

        if ($generic_type_value === 'class-string'
            || $generic_type_value === 'interface-string'
            || $generic_type_value === 'enum-string'
        ) {
            $class_name = $generic_params[0]->getId(false);

            if (isset($template_type_map[$class_name])) {
                $first_class = array_keys($template_type_map[$class_name])[0];

                return self::getGenericParamClass(
                    $class_name,
                    $template_type_map[$class_name][$first_class],
                    $first_class,
                    $from_docblock,
                );
            }

            $types = [];
            foreach ($generic_params[0]->getAtomicTypes() as $type) {
                if ($type instanceof TNamedObject) {
                    $types[] = new TClassString($type->value, $type, false, false, false, $from_docblock);
                    continue;
                }

                if ($type instanceof TCallableObject) {
                    $types[] = new TUnknownClassString($type, false, $from_docblock);
                    continue;
                }

                throw new TypeParseTreeException('class-string param can only target to named or callable objects');
            }

            assert(
                $types !== [],
                'Since `Union` cannot be empty and all non-supported atomics lead to thrown exception,'
                .' we can safely assert that the types array is non-empty.',
            );

            return new Union($types);
        }

        if ($generic_type_value === 'class-string-map') {
            if (count($generic_params) !== 2) {
                throw new TypeParseTreeException(
                    'There should only be two params for class-string-map, '
                    . count($generic_params) . ' provided',
                );
            }

            $template_marker_parts = array_values($generic_params[0]->getAtomicTypes());

            $template_marker = $template_marker_parts[0];

            $template_as_type = null;

            if ($template_marker instanceof TNamedObject) {
                $template_param_name = $template_marker->value;
            } elseif ($template_marker instanceof TTemplateParam) {
                $template_param_name = $template_marker->param_name;
                $template_as_type = $template_marker->as->getSingleAtomic();

                if (!$template_as_type instanceof TNamedObject) {
                    throw new TypeParseTreeException(
                        'Unrecognised as type',
                    );
                }
            } else {
                throw new TypeParseTreeException(
                    'Unrecognised class-string-map templated param',
                );
            }

            return new TClassStringMap(
                $template_param_name,
                $template_as_type,
                $generic_params[1],
                $from_docblock,
            );
        }

        if (in_array($generic_type_value, TPropertiesOf::tokenNames())) {
            if (count($generic_params) !== 1) {
                throw new TypeParseTreeException($generic_type_value . ' requires exactly one parameter.');
            }

            $param_name = (string) $generic_params[0];

            if (isset($template_type_map[$param_name])
                && ($defining_class = array_key_first($template_type_map[$param_name])) !== null
            ) {
                $template_param = $generic_params[0]->getSingleAtomic();
                if (!$template_param instanceof TTemplateParam) {
                    throw new TypeParseTreeException(
                        $generic_type_value . '<' . $param_name . '> must be a TTemplateParam.',
                    );
                }
                if ($template_param->getIntersectionTypes()) {
                    throw new TypeParseTreeException(
                        $generic_type_value . '<' . $param_name . '> must be a TTemplateParam'
                        . ' with no intersection types.',
                    );
                }

                return new TTemplatePropertiesOf(
                    $param_name,
                    $defining_class,
                    $template_param,
                    TPropertiesOf::filterForTokenName($generic_type_value),
                    $from_docblock,
                );
            }

            $param_union_types = array_values($generic_params[0]->getAtomicTypes());

            if (count($param_union_types) > 1) {
                throw new TypeParseTreeException('Union types are not allowed in ' . $generic_type_value . ' param');
            }

            if (!$param_union_types[0] instanceof TNamedObject) {
                throw new TypeParseTreeException('Param should be a named object in ' . $generic_type_value);
            }

            return new TPropertiesOf(
                $param_union_types[0],
                TPropertiesOf::filterForTokenName($generic_type_value),
                $from_docblock,
            );
        }

        if ($generic_type_value === 'key-of') {
            $param_name = $generic_params[0]->getId(false);

            if (isset($template_type_map[$param_name])
                && ($defining_class = array_key_first($template_type_map[$param_name])) !== null
            ) {
                return new TTemplateKeyOf(
                    $param_name,
                    $defining_class,
                    $generic_params[0],
                    $from_docblock,
                );
            }

            if (!TKeyOf::isViableTemplateType($generic_params[0])) {
                throw new TypeParseTreeException(
                    'Untemplated key-of param ' . $param_name . ' should be an array',
                );
            }

            return new TKeyOf($generic_params[0], $from_docblock);
        }

        if ($generic_type_value === 'value-of') {
            $param_name = $generic_params[0]->getId(false);

            if (isset($template_type_map[$param_name])
                && ($defining_class = array_key_first($template_type_map[$param_name])) !== null
            ) {
                return new TTemplateValueOf(
                    $param_name,
                    $defining_class,
                    $generic_params[0],
                    $from_docblock,
                );
            }

            if (!TValueOf::isViableTemplateType($generic_params[0])) {
                throw new TypeParseTreeException(
                    'Untemplated value-of param ' . $param_name . ' should be an array',
                );
            }

            return new TValueOf($generic_params[0]);
        }

        if ($generic_type_value === 'int-mask') {
            $atomic_types = [];

            foreach ($generic_params as $generic_param) {
                if (!$generic_param->isSingle()) {
                    throw new TypeParseTreeException(
                        'int-mask types must all be non-union',
                    );
                }

                $generic_param_atomics = $generic_param->getAtomicTypes();

                $atomic_type = reset($generic_param_atomics);

                if ($atomic_type instanceof TNamedObject) {
                    if (defined($atomic_type->value)) {
                        /** @var mixed */
                        $constant_value = constant($atomic_type->value);

                        if (!is_int($constant_value)) {
                            throw new TypeParseTreeException(
                                'int-mask types must all be integer values',
                            );
                        }

                        $atomic_type = new TLiteralInt($constant_value, $from_docblock);
                    } else {
                        throw new TypeParseTreeException(
                            'int-mask types must all be integer values',
                        );
                    }
                }

                if (!$atomic_type instanceof TLiteralInt
                    && !($atomic_type instanceof TClassConstant
                        && strpos($atomic_type->const_name, '*') === false)
                ) {
                    throw new TypeParseTreeException(
                        'int-mask types must all be integer values or scalar class constants',
                    );
                }

                $atomic_types[] = $atomic_type;
            }

            $potential_ints = [];

            foreach ($atomic_types as $atomic_type) {
                if (!$atomic_type instanceof TLiteralInt) {
                    return new TIntMask($atomic_types, $from_docblock);
                }

                $potential_ints[] = $atomic_type->value;
            }

            return new Union(self::getComputedIntsFromMask($potential_ints, $from_docblock));
        }

        if ($generic_type_value === 'int-mask-of') {
            $param_union_types = array_values($generic_params[0]->getAtomicTypes());

            if (count($param_union_types) > 1) {
                throw new TypeParseTreeException('Union types are not allowed in value-of type');
            }

            $param_type = $param_union_types[0];

            if (!$param_type instanceof TClassConstant
                && !$param_type instanceof TValueOf
                && !$param_type instanceof TKeyOf
            ) {
                throw new TypeParseTreeException(
                    'Invalid reference passed to int-mask-of',
                );
            } elseif ($param_type instanceof TClassConstant
                && strpos($param_type->const_name, '*') === false
            ) {
                throw new TypeParseTreeException(
                    'Class constant passed to int-mask-of must be a wildcard type',
                );
            }

            return new TIntMaskOf($param_type, $from_docblock);
        }

        if ($generic_type_value === 'int') {
            if (count($generic_params) !== 2) {
                throw new TypeParseTreeException('int range must have 2 params');
            }
            assert(count($parse_tree->children) === 2);

            $get_int_range_bound = static function (
                ParseTree $parse_tree,
                Union $generic_param,
                string $bound_name
            ): ?int {
                if (!$parse_tree instanceof Value
                    || count($generic_param->getAtomicTypes()) > 1
                    || (!$generic_param->getSingleAtomic() instanceof TLiteralInt
                        && $parse_tree->value !== $bound_name
                        && $parse_tree->text !== $bound_name
                    )
                ) {
                    throw new TypeParseTreeException(
                        "Invalid type \"{$generic_param->getId()}\" as int $bound_name boundary",
                    );
                }
                $generic_param_atomic = $generic_param->getSingleAtomic();
                return $generic_param_atomic instanceof TLiteralInt ? $generic_param_atomic->value : null;
            };

            $min_bound = $get_int_range_bound($parse_tree->children[0], $generic_params[0], TIntRange::BOUND_MIN);
            $max_bound = $get_int_range_bound($parse_tree->children[1], $generic_params[1], TIntRange::BOUND_MAX);

            if ($min_bound === null && $max_bound === null) {
                return new TInt($from_docblock);
            }

            if (is_int($min_bound) && is_int($max_bound) && $min_bound > $max_bound) {
                throw new TypeParseTreeException(
                    "Min bound can't be greater than max bound, int<$min_bound, $max_bound> given",
                );
            }

            if (is_int($min_bound) && is_int($max_bound) && $min_bound > $max_bound) {
                throw new TypeParseTreeException(
                    "Min bound can't be greater than max bound, int<$min_bound, $max_bound> given",
                );
            }

            return new TIntRange($min_bound, $max_bound, $from_docblock);
        }

        if (isset(TypeTokenizer::PSALM_RESERVED_WORDS[$generic_type_value])
            && $generic_type_value !== 'self'
            && $generic_type_value !== 'static'
        ) {
            throw new TypeParseTreeException('Cannot create generic object with reserved word');
        }

        return new TGenericObject($generic_type_value, $generic_params, false, false, [], $from_docblock);
    }

    /**
     * @param  array<string, array<string, Union>> $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     * @throws TypeParseTreeException
     */
    private static function getTypeFromUnionTree(
        UnionTree $parse_tree,
        Codebase $codebase,
        array $template_type_map,
        array $type_aliases,
        bool $from_docblock
    ): Union {
        $has_null = false;

        $atomic_types = [];

        foreach ($parse_tree->children as $child_tree) {
            if ($child_tree instanceof NullableTree) {
                if (!isset($child_tree->children[0])) {
                    throw new TypeParseTreeException('Invalid ? character');
                }

                $atomic_type = self::getTypeFromTree(
                    $child_tree->children[0],
                    $codebase,
                    null,
                    $template_type_map,
                    $type_aliases,
                    $from_docblock,
                );
                $has_null = true;
            } else {
                $atomic_type = self::getTypeFromTree(
                    $child_tree,
                    $codebase,
                    null,
                    $template_type_map,
                    $type_aliases,
                    $from_docblock,
                );
            }

            if ($atomic_type instanceof Union) {
                foreach ($atomic_type->getAtomicTypes() as $type) {
                    $atomic_types[] = $type;
                }

                continue;
            }

            $atomic_types[] = $atomic_type;
        }

        if ($has_null) {
            $atomic_types[] = new TNull($from_docblock);
        }

        if (!$atomic_types) {
            throw new TypeParseTreeException(
                'No atomic types found',
            );
        }

        return TypeCombiner::combine($atomic_types);
    }

    /**
     * @param  array<string, array<string, Union>> $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     * @throws TypeParseTreeException
     */
    private static function getTypeFromIntersectionTree(
        IntersectionTree $parse_tree,
        Codebase $codebase,
        array $template_type_map,
        array $type_aliases,
        bool $from_docblock
    ): Atomic {
        $intersection_types = [];

        foreach ($parse_tree->children as $name => $child_tree) {
            $atomic_type = self::getTypeFromTree(
                $child_tree,
                $codebase,
                null,
                $template_type_map,
                $type_aliases,
                $from_docblock,
            );

            if (!$atomic_type instanceof Atomic) {
                throw new TypeParseTreeException(
                    'Intersection types cannot contain unions',
                );
            }

            $intersection_types[$name] = $atomic_type;
        }

        if ($intersection_types === []) {
            return new TMixed();
        }

        $first_type = reset($intersection_types);
        $last_type = end($intersection_types);

        $onlyTKeyedArray = $first_type instanceof TKeyedArray
            || $last_type instanceof TKeyedArray;

        foreach ($intersection_types as $intersection_type) {
            if (!$intersection_type instanceof TKeyedArray
                && ($intersection_type !== $first_type
                    || !$first_type instanceof TArray)
                && ($intersection_type !== $last_type
                    || !$last_type instanceof TArray)
            ) {
                $onlyTKeyedArray = false;
                break;
            }
        }

        if ($onlyTKeyedArray) {
            /**
             * @var array<TKeyedArray> $intersection_types
             * @var TKeyedArray $first_type
             * @var TKeyedArray $last_type
             */
            return self::getTypeFromKeyedArrays(
                $codebase,
                $intersection_types,
                $first_type,
                $last_type,
                $from_docblock,
            );
        }

        $keyed_intersection_types = self::extractKeyedIntersectionTypes(
            $codebase,
            $intersection_types,
        );

        $intersect_static = false;

        if (isset($keyed_intersection_types['static'])) {
            unset($keyed_intersection_types['static']);
            $intersect_static = true;
        }

        if ($keyed_intersection_types === [] && $intersect_static) {
            return new TNamedObject('static', false, false, [], $from_docblock);
        }

        $first_type = array_shift($keyed_intersection_types);

        // Keyed array intersection are merged together and are not combinable with object-types
        if ($first_type instanceof TKeyedArray) {
            // assume all types are keyed arrays
            array_unshift($keyed_intersection_types, $first_type);
            /** @var TKeyedArray $last_type */
            $last_type = end($keyed_intersection_types);

            /** @var array<TKeyedArray> $keyed_intersection_types */
            return self::getTypeFromKeyedArrays(
                $codebase,
                $keyed_intersection_types,
                $first_type,
                $last_type,
                $from_docblock,
            );
        }

        if ($intersect_static
            && $first_type instanceof TNamedObject
        ) {
            $first_type->is_static = true;
        }

        if ($keyed_intersection_types) {
            /** @var non-empty-array<string,TIterable|TNamedObject|TCallableObject|TTemplateParam|TObjectWithProperties> $keyed_intersection_types */
            return $first_type->setIntersectionTypes($keyed_intersection_types);
        }

        return $first_type;
    }

    /**
     * @param  array<string, array<string, Union>> $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     * @return TCallable|TClosure
     * @throws TypeParseTreeException
     */
    private static function getTypeFromCallableTree(
        CallableTree $parse_tree,
        Codebase $codebase,
        array $template_type_map,
        array $type_aliases,
        bool $from_docblock
    ) {
        $params = [];

        foreach ($parse_tree->children as $child_tree) {
            $is_variadic = false;
            $is_optional = false;
            $param_name = '';

            if ($child_tree instanceof CallableParamTree) {
                if (isset($child_tree->children[0])) {
                    $tree_type = self::getTypeFromTree(
                        $child_tree->children[0],
                        $codebase,
                        null,
                        $template_type_map,
                        $type_aliases,
                        $from_docblock,
                    );
                } else {
                    $tree_type = new TMixed(false, $from_docblock);
                }

                $is_variadic = $child_tree->variadic;
                $is_optional = $child_tree->has_default;
                $param_name = $child_tree->name ?? '';
            } else {
                if ($child_tree instanceof Value && strpos($child_tree->value, '$') > 0) {
                    $child_tree->value = preg_replace('/(.+)\$.*/', '$1', $child_tree->value);
                }

                $tree_type = self::getTypeFromTree(
                    $child_tree,
                    $codebase,
                    null,
                    $template_type_map,
                    $type_aliases,
                    $from_docblock,
                );
            }

            $param = new FunctionLikeParameter(
                $param_name,
                false,
                $tree_type instanceof Union ? $tree_type : new Union([$tree_type]),
                null,
                null,
                null,
                $is_optional,
                false,
                $is_variadic,
            );

            $params[] = $param;
        }

        $pure = strpos($parse_tree->value, 'pure-') === 0 ? true : null;

        if (in_array(strtolower($parse_tree->value), ['closure', '\closure', 'pure-closure'], true)) {
            return new TClosure('Closure', $params, null, $pure, [], [], $from_docblock);
        }

        return new TCallable('callable', $params, null, $pure, $from_docblock);
    }

    /**
     * @param  array<string, array<string, Union>> $template_type_map
     * @throws TypeParseTreeException
     */
    private static function getTypeFromIndexAccessTree(
        IndexedAccessTree $parse_tree,
        array $template_type_map,
        bool $from_docblock
    ): TTemplateIndexedAccess {
        if (!isset($parse_tree->children[0]) || !$parse_tree->children[0] instanceof Value) {
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

        if (!$offset_defining_class
            && isset($offset_template_data[''])
            && $offset_template_data['']->isSingle()
        ) {
            $offset_template_type = $offset_template_data['']->getSingleAtomic();

            if ($offset_template_type instanceof TTemplateKeyOf) {
                $offset_defining_class = $offset_template_type->defining_class;
            }
        }

        $array_defining_class = array_keys($template_type_map[$array_param_name])[0];

        if ($offset_defining_class !== $array_defining_class
            && strpos($offset_defining_class, 'fn-') !== 0
        ) {
            throw new TypeParseTreeException('Template params are defined in different locations');
        }

        return new TTemplateIndexedAccess(
            $array_param_name,
            $offset_param_name,
            $array_defining_class,
            $from_docblock,
        );
    }

    /**
     * @param  array<string, array<string, Union>> $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     * @return TCallableKeyedArray|TKeyedArray|TObjectWithProperties|TArray
     * @throws TypeParseTreeException
     */
    private static function getTypeFromKeyedArrayTree(
        KeyedArrayTree $parse_tree,
        Codebase $codebase,
        array $template_type_map,
        array $type_aliases,
        bool $from_docblock
    ) {
        $properties = [];
        $class_strings = [];

        $type = $parse_tree->value;

        $had_optional = false;
        $had_explicit = false;
        $had_implicit = false;

        $previous_property_key = -1;

        $is_list = true;

        $sealed = true;

        $extra_params = null;

        $last_property_branch = end($parse_tree->children);
        if ($last_property_branch instanceof GenericTree
            && $last_property_branch->value === ''
        ) {
            $extra_params = $last_property_branch->children;
            array_pop($parse_tree->children);
        }

        foreach ($parse_tree->children as $i => $property_branch) {
            $class_string = false;

            if ($property_branch instanceof FieldEllipsis) {
                if ($i !== count($parse_tree->children) - 1) {
                    throw new TypeParseTreeException(
                        'Unexpected ...',
                    );
                }

                $sealed = false;

                break;
            }

            if (!$property_branch instanceof KeyedArrayPropertyTree) {
                $property_type = self::getTypeFromTree(
                    $property_branch,
                    $codebase,
                    null,
                    $template_type_map,
                    $type_aliases,
                    $from_docblock,
                );
                $property_maybe_undefined = false;
                $property_key = $i;
                $had_implicit = true;
            } elseif (count($property_branch->children) === 1) {
                $property_type = self::getTypeFromTree(
                    $property_branch->children[0],
                    $codebase,
                    null,
                    $template_type_map,
                    $type_aliases,
                    $from_docblock,
                );
                $property_maybe_undefined = $property_branch->possibly_undefined;
                if (strpos($property_branch->value, '::')) {
                    [$fq_classlike_name, $const_name] = explode('::', $property_branch->value);
                    if ($const_name === 'class') {
                        $property_key = $fq_classlike_name;
                        $class_string = true;
                    } elseif ($property_branch->value[0] === '"' || $property_branch->value[0] === "'") {
                        $property_key = $property_branch->value;
                    } else {
                        throw new TypeParseTreeException(
                            ':: in array key is only allowed for ::class',
                        );
                    }
                } else {
                    $property_key = $property_branch->value;
                }
                if ($is_list && (
                        ArrayAnalyzer::getLiteralArrayKeyInt($property_key) === false
                        || ($had_optional && !$property_maybe_undefined)
                        || $type === 'array'
                        || $type === 'callable-array'
                        || $previous_property_key != ($property_key - 1)
                    )
                ) {
                    $is_list = false;
                }
                $had_explicit = true;
                $previous_property_key = $property_key;

                if ($property_key[0] === '\'' || $property_key[0] === '"') {
                    $property_key = stripslashes(substr($property_key, 1, -1));
                }
            } else {
                throw new TypeParseTreeException(
                    'Missing property type',
                );
            }

            if (!$property_type instanceof Union) {
                $property_type = new Union([$property_type], ['from_docblock' => $from_docblock]);
            }

            if ($property_maybe_undefined) {
                $property_type->possibly_undefined = true;
                $had_optional = true;
            }

            if (isset($properties[$property_key])) {
                throw new TypeParseTreeException("Duplicate key $property_key detected");
            }

            $properties[$property_key] = $property_type;
            if ($class_string) {
                $class_strings[$property_key] = true;
            }
        }

        if ($had_explicit && $had_implicit) {
            throw new TypeParseTreeException('Cannot mix explicit and implicit keys');
        }

        if ($type === 'object') {
            return new TObjectWithProperties($properties, [], [], $from_docblock);
        }

        $callable = strpos($type, 'callable-') === 0;
        $class = TKeyedArray::class;
        if ($callable) {
            $class = TCallableKeyedArray::class;
            $type = substr($type, 9);
        }

        if ($callable && !$properties) {
            throw new TypeParseTreeException('A callable array cannot be empty');
        }

        if ($type !== 'array' && $type !== 'list') {
            throw new TypeParseTreeException('Unexpected brace character');
        }

        if ($type === 'list' && !$is_list) {
            throw new TypeParseTreeException('A list shape cannot describe a non-list');
        }

        if (!$properties) {
            return new TArray([Type::getNever($from_docblock), Type::getNever($from_docblock)], $from_docblock);
        }

        if ($extra_params) {
            if ($is_list && count($extra_params) !== 1) {
                throw new TypeParseTreeException('Must have exactly one extra field!');
            }
            if (!$is_list && count($extra_params) !== 2) {
                throw new TypeParseTreeException('Must have exactly two extra fields!');
            }
            $final_extra_params = $is_list ? [Type::getListKey(true)] : [];
            foreach ($extra_params as $child_tree) {
                $child_type = self::getTypeFromTree(
                    $child_tree,
                    $codebase,
                    null,
                    $template_type_map,
                    $type_aliases,
                    $from_docblock,
                );
                if ($child_type instanceof Atomic) {
                    $child_type = new Union([$child_type]);
                }
                $final_extra_params []= $child_type;
            }
            $extra_params = $final_extra_params;
        }
        return new $class(
            $properties,
            $class_strings,
            $extra_params ?? ($sealed
                ? null
                : [$is_list ? Type::getListKey() : Type::getArrayKey(), Type::getMixed()]
            ),
            $is_list,
            $from_docblock,
        );
    }

    /**
     * @param TNamedObject|TObjectWithProperties|TCallableObject|TIterable|TTemplateParam|TKeyedArray $intersection_type
     */
    private static function extractIntersectionKey(Atomic $intersection_type): string
    {
        return $intersection_type instanceof TIterable || $intersection_type instanceof TKeyedArray
            ? $intersection_type->getId()
            : $intersection_type->getKey();
    }

    /**
     * @param non-empty-array<Atomic> $intersection_types
     * @return non-empty-array<string,TIterable|TNamedObject|TCallableObject|TTemplateParam|TObjectWithProperties|TKeyedArray>
     */
    private static function extractKeyedIntersectionTypes(
        Codebase $codebase,
        array $intersection_types
    ): array {
        $keyed_intersection_types = [];
        $callable_intersection = null;
        $any_object_type_found = $any_array_found = false;

        $normalized_intersection_types = self::resolveTypeAliases(
            $codebase,
            $intersection_types,
        );

        foreach ($normalized_intersection_types as $intersection_type) {
            if ($intersection_type instanceof TKeyedArray
                && !$intersection_type instanceof TCallableKeyedArray
            ) {
                $any_array_found = true;

                if ($any_object_type_found) {
                    throw new TypeParseTreeException(
                        'The intersection type must not mix array and object types!',
                    );
                }

                $keyed_intersection_types[self::extractIntersectionKey($intersection_type)] = $intersection_type;
                continue;
            }

            $any_object_type_found = true;

            if ($intersection_type instanceof TIterable
                || $intersection_type instanceof TNamedObject
                || $intersection_type instanceof TTemplateParam
                || $intersection_type instanceof TObjectWithProperties
            ) {
                $keyed_intersection_types[self::extractIntersectionKey($intersection_type)] = $intersection_type;
                continue;
            }

            if (get_class($intersection_type) === TObject::class) {
                continue;
            }

            if ($intersection_type instanceof TCallable) {
                if ($callable_intersection !== null) {
                    throw new TypeParseTreeException(
                        'The intersection type must not contain more than one callable type!',
                    );
                }
                $callable_intersection = $intersection_type;
                continue;
            }

            throw new TypeParseTreeException(
                'Intersection types must be all objects, '
                . get_class($intersection_type) . ' provided',
            );
        }

        if ($callable_intersection !== null) {
            $callable_object_type = new TCallableObject(
                $callable_intersection->from_docblock,
                $callable_intersection,
            );

            $keyed_intersection_types[self::extractIntersectionKey($callable_object_type)] = $callable_object_type;
        }

        if ($any_object_type_found && $any_array_found) {
            throw new TypeParseTreeException(
                'Intersection types must be all objects or all keyed array.',
            );
        }

        assert($keyed_intersection_types !== []);

        return $keyed_intersection_types;
    }

    /**
     * @param array<Atomic> $intersection_types
     * @return array<Atomic>
     */
    private static function resolveTypeAliases(Codebase $codebase, array $intersection_types): array
    {
        $normalized_intersection_types = [];
        $modified = false;
        foreach ($intersection_types as $intersection_type) {
            if (!$intersection_type instanceof TTypeAlias
                || !$codebase->classlike_storage_provider->has($intersection_type->declaring_fq_classlike_name)
            ) {
                $normalized_intersection_types[] = [$intersection_type];
                continue;
            }

            $expanded_intersection_type = TypeExpander::expandAtomic(
                $codebase,
                $intersection_type,
                null,
                null,
                null,
                true,
                false,
                false,
                true,
                true,
                true,
            );

            $modified = $modified || $expanded_intersection_type[0] !== $intersection_type;
            $normalized_intersection_types[] = $expanded_intersection_type;
        }

        if ($modified === false) {
            return $intersection_types;
        }

        return self::resolveTypeAliases(
            $codebase,
            array_merge(...$normalized_intersection_types),
        );
    }

    /**
     * @param array<TKeyedArray> $intersection_types
     * @param TKeyedArray|TArray $first_type
     * @param TKeyedArray|TArray $last_type
     */
    private static function getTypeFromKeyedArrays(
        Codebase $codebase,
        array $intersection_types,
        Atomic $first_type,
        Atomic $last_type,
        bool $from_docblock
    ): Atomic {
        /** @var non-empty-array<string|int, Union> */
        $properties = [];

        if ($first_type instanceof TArray) {
            array_shift($intersection_types);
        } elseif ($last_type instanceof TArray) {
            array_pop($intersection_types);
        }

        $all_sealed = true;

        foreach ($intersection_types as $intersection_type) {
            if ($intersection_type->fallback_params !== null) {
                $all_sealed = false;
            }

            foreach ($intersection_type->properties as $property => $property_type) {
                if (!array_key_exists($property, $properties)) {
                    $properties[$property] = $property_type;
                    continue;
                }

                $new_type = Type::intersectUnionTypes(
                    $properties[$property],
                    $property_type,
                    $codebase,
                );

                if ($new_type === null) {
                    throw new TypeParseTreeException(
                        'Incompatible intersection types for "' . $property . '", '
                        . $properties[$property] . ' and ' . $property_type
                        . ' provided',
                    );
                }
                $properties[$property] = $new_type;
            }
        }

        $first_or_last_type = $first_type instanceof TArray
            ? $first_type
            : ($last_type instanceof TArray ? $last_type : null);

        $fallback_params = null;

        if ($first_or_last_type !== null) {
            $fallback_params = [
                $first_or_last_type->type_params[0],
                $first_or_last_type->type_params[1],
            ];
        } elseif (!$all_sealed) {
            $fallback_params = [Type::getArrayKey(), Type::getMixed()];
        }

        return new TKeyedArray(
            $properties,
            null,
            $fallback_params,
            false,
            $from_docblock,
        );
    }
}
