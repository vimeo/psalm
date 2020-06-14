<?php
namespace Psalm\Internal\Analyzer;

use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Codebase;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\GetClassT;
use Psalm\Type\Atomic\GetTypeT;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\THtmlEscapedString;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTraitString;
use Psalm\Type\Atomic\TTrue;
use function get_class;
use function array_merge;
use function strtolower;
use function in_array;
use function array_values;
use function count;
use function is_string;
use function array_fill;
use function array_keys;
use function array_reduce;
use function end;
use function array_unique;

/**
 * @internal
 */
class TypeAnalyzer
{
    /**
     * Does the input param type match the given param type
     */
    public static function isContainedBy(
        Codebase $codebase,
        Type\Union $input_type,
        Type\Union $container_type,
        bool $ignore_null = false,
        bool $ignore_false = false,
        ?TypeComparisonResult $union_comparison_result = null,
        bool $allow_interface_equality = false
    ) : bool {
        if ($union_comparison_result) {
            $union_comparison_result->scalar_type_match_found = true;
        }

        if ($input_type->possibly_undefined && !$container_type->possibly_undefined) {
            return false;
        }

        if ($container_type->hasMixed() && !$container_type->isEmptyMixed()) {
            return true;
        }

        $container_has_template = $container_type->hasTemplateOrStatic();

        $input_atomic_types = \array_reverse($input_type->getAtomicTypes());

        while ($input_type_part = \array_pop($input_atomic_types)) {
            if ($input_type_part instanceof TNull && $ignore_null) {
                continue;
            }

            if ($input_type_part instanceof TFalse && $ignore_false) {
                continue;
            }

            if ($input_type_part instanceof TTemplateParam
                && !$container_has_template
                && !$input_type_part->extra_types
            ) {
                $input_atomic_types = array_merge($input_type_part->as->getAtomicTypes(), $input_atomic_types);
                continue;
            }

            $type_match_found = false;
            $scalar_type_match_found = false;
            $all_to_string_cast = true;

            $all_type_coerced = null;
            $all_type_coerced_from_mixed = null;
            $all_type_coerced_from_as_mixed = null;

            $some_type_coerced = false;
            $some_type_coerced_from_mixed = false;

            if ($input_type_part instanceof TArrayKey
                && ($container_type->hasInt() && $container_type->hasString())
            ) {
                continue;
            }

            foreach ($container_type->getAtomicTypes() as $container_type_part) {
                if ($ignore_null
                    && $container_type_part instanceof TNull
                    && !$input_type_part instanceof TNull
                ) {
                    continue;
                }

                if ($ignore_false
                    && $container_type_part instanceof TFalse
                    && !$input_type_part instanceof TFalse
                ) {
                    continue;
                }

                if ($union_comparison_result) {
                    $atomic_comparison_result = new TypeComparisonResult();
                } else {
                    $atomic_comparison_result = null;
                }

                $is_atomic_contained_by = self::isAtomicContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    true,
                    $atomic_comparison_result
                );

                if ($input_type_part instanceof TMixed
                    && $input_type->from_template_default
                    && $input_type->from_docblock
                    && $atomic_comparison_result
                    && $atomic_comparison_result->type_coerced_from_mixed
                ) {
                    $atomic_comparison_result->type_coerced_from_as_mixed = true;
                }

                if ($atomic_comparison_result) {
                    if ($atomic_comparison_result->scalar_type_match_found !== null) {
                        $scalar_type_match_found = $atomic_comparison_result->scalar_type_match_found;
                    }

                    if ($union_comparison_result
                        && $atomic_comparison_result->type_coerced_from_scalar !== null
                    ) {
                        $union_comparison_result->type_coerced_from_scalar
                            = $atomic_comparison_result->type_coerced_from_scalar;
                    }

                    if ($is_atomic_contained_by
                        && $union_comparison_result
                        && $atomic_comparison_result->replacement_atomic_type
                    ) {
                        if (!$union_comparison_result->replacement_union_type) {
                            $union_comparison_result->replacement_union_type = clone $input_type;
                        }

                        $union_comparison_result->replacement_union_type->removeType($input_type->getKey());

                        $union_comparison_result->replacement_union_type->addType(
                            $atomic_comparison_result->replacement_atomic_type
                        );
                    }
                }

                if ($input_type_part instanceof TNumeric
                    && $container_type->hasString()
                    && $container_type->hasInt()
                    && $container_type->hasFloat()
                ) {
                    $scalar_type_match_found = false;
                    $is_atomic_contained_by = true;
                }

                if ($atomic_comparison_result) {
                    if ($atomic_comparison_result->type_coerced) {
                        $some_type_coerced = true;
                    }

                    if ($atomic_comparison_result->type_coerced_from_mixed) {
                        $some_type_coerced_from_mixed = true;
                    }

                    if ($atomic_comparison_result->type_coerced !== true || $all_type_coerced === false) {
                        $all_type_coerced = false;
                    } else {
                        $all_type_coerced = true;
                    }

                    if ($atomic_comparison_result->type_coerced_from_mixed !== true
                        || $all_type_coerced_from_mixed === false
                    ) {
                        $all_type_coerced_from_mixed = false;
                    } else {
                        $all_type_coerced_from_mixed = true;
                    }

                    if ($atomic_comparison_result->type_coerced_from_as_mixed !== true
                        || $all_type_coerced_from_as_mixed === false
                    ) {
                        $all_type_coerced_from_as_mixed = false;
                    } else {
                        $all_type_coerced_from_as_mixed = true;
                    }
                }

                if ($is_atomic_contained_by) {
                    $type_match_found = true;

                    if ($atomic_comparison_result) {
                        if ($atomic_comparison_result->to_string_cast !== true) {
                            $all_to_string_cast = false;
                        }
                    }

                    $all_type_coerced_from_mixed = false;
                    $all_type_coerced_from_as_mixed = false;
                    $all_type_coerced = false;
                }
            }

            if ($union_comparison_result) {
                // only set this flag if we're definite that the only
                // reason the type match has been found is because there
                // was a __toString cast
                if ($all_to_string_cast && $type_match_found) {
                    $union_comparison_result->to_string_cast = true;
                }

                if ($all_type_coerced) {
                    $union_comparison_result->type_coerced = true;
                }

                if ($all_type_coerced_from_mixed) {
                    $union_comparison_result->type_coerced_from_mixed = true;

                    if (($input_type->from_template_default && $input_type->from_docblock)
                        || $all_type_coerced_from_as_mixed
                    ) {
                        $union_comparison_result->type_coerced_from_as_mixed = true;
                    }
                }
            }

            if (!$type_match_found) {
                if ($union_comparison_result) {
                    if ($some_type_coerced) {
                        $union_comparison_result->type_coerced = true;
                    }

                    if ($some_type_coerced_from_mixed) {
                        $union_comparison_result->type_coerced_from_mixed = true;

                        if (($input_type->from_template_default && $input_type->from_docblock)
                            || $all_type_coerced_from_as_mixed
                        ) {
                            $union_comparison_result->type_coerced_from_as_mixed = true;
                        }
                    }

                    if (!$scalar_type_match_found) {
                        $union_comparison_result->scalar_type_match_found = false;
                    }
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Used for comparing signature typehints, uses PHP's light contravariance rules
     *
     * @param  ?Type\Union  $input_type
     * @param  Type\Union   $container_type
     *
     * @return bool
     */
    public static function isContainedByInPhp(
        ?Type\Union $input_type,
        Type\Union $container_type
    ) {
        if (!$input_type) {
            return false;
        }

        if ($input_type->getId() === $container_type->getId()) {
            return true;
        }

        if ($input_type->isNullable() && !$container_type->isNullable()) {
            return false;
        }

        $input_type_not_null = clone $input_type;
        $input_type_not_null->removeType('null');

        $container_type_not_null = clone $container_type;
        $container_type_not_null->removeType('null');

        if ($input_type_not_null->getId() === $container_type_not_null->getId()) {
            return true;
        }

        if ($input_type_not_null->hasArray() && $container_type_not_null->hasType('iterable')) {
            return true;
        }

        return false;
    }

    /**
     * Used for comparing docblock types to signature types before we know about all types
     *
     * @param  Type\Union   $input_type
     * @param  Type\Union   $container_type
     */
    public static function isSimplyContainedBy(
        Type\Union $input_type,
        Type\Union $container_type
    ) : bool {
        if ($input_type->getId() === $container_type->getId()) {
            return true;
        }

        if ($input_type->isNullable() && !$container_type->isNullable()) {
            return false;
        }

        $input_type_not_null = clone $input_type;
        $input_type_not_null->removeType('null');

        $container_type_not_null = clone $container_type;
        $container_type_not_null->removeType('null');

        foreach ($input_type->getAtomicTypes() as $input_key => $input_type_part) {
            foreach ($container_type->getAtomicTypes() as $container_key => $container_type_part) {
                if (get_class($container_type_part) === TNamedObject::class
                    && $input_type_part instanceof TNamedObject
                    && $input_type_part->value === $container_type_part->value
                ) {
                    continue 2;
                }

                if ($input_key === $container_key) {
                    continue 2;
                }
            }

            return false;
        }



        return true;
    }

    /**
     * Does the input param type match the given param type
     *
     * @param  Type\Union   $input_type
     * @param  Type\Union   $container_type
     * @param  bool         $ignore_null
     * @param  bool         $ignore_false
     *
     * @return bool
     */
    public static function canBeContainedBy(
        Codebase $codebase,
        Type\Union $input_type,
        Type\Union $container_type,
        $ignore_null = false,
        $ignore_false = false,
        array &$matching_input_keys = []
    ) {
        if ($container_type->hasMixed()) {
            return true;
        }

        if ($input_type->possibly_undefined && !$container_type->possibly_undefined) {
            return false;
        }

        foreach ($container_type->getAtomicTypes() as $container_type_part) {
            if ($container_type_part instanceof TNull && $ignore_null) {
                continue;
            }

            if ($container_type_part instanceof TFalse && $ignore_false) {
                continue;
            }

            foreach ($input_type->getAtomicTypes() as $input_type_part) {
                $atomic_comparison_result = new TypeComparisonResult();
                $is_atomic_contained_by = self::isAtomicContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    false,
                    false,
                    $atomic_comparison_result
                );

                if (($is_atomic_contained_by && !$atomic_comparison_result->to_string_cast)
                    || $atomic_comparison_result->type_coerced_from_mixed
                ) {
                    $matching_input_keys[$input_type_part->getKey()] = true;
                }
            }
        }

        return !!$matching_input_keys;
    }

    /**
     * Can any part of the $type1 be equal to any part of $type2
     *
     * @return bool
     */
    public static function canExpressionTypesBeIdentical(
        Codebase $codebase,
        Type\Union $type1,
        Type\Union $type2
    ) {
        if ($type1->hasMixed() || $type2->hasMixed()) {
            return true;
        }

        if ($type1->isNullable() && $type2->isNullable()) {
            return true;
        }

        foreach ($type1->getAtomicTypes() as $type1_part) {
            foreach ($type2->getAtomicTypes() as $type2_part) {
                $first_comparison_result = new TypeComparisonResult();
                $second_comparison_result = new TypeComparisonResult();

                $either_contains = (self::isAtomicContainedBy(
                    $codebase,
                    $type1_part,
                    $type2_part,
                    true,
                    false,
                    $first_comparison_result
                )
                    && !$first_comparison_result->to_string_cast
                ) || (self::isAtomicContainedBy(
                    $codebase,
                    $type2_part,
                    $type1_part,
                    true,
                    false,
                    $second_comparison_result
                )
                    && !$second_comparison_result->to_string_cast
                ) || ($first_comparison_result->type_coerced
                    && $second_comparison_result->type_coerced
                );

                if ($either_contains) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  Codebase       $codebase
     * @param  TNamedObject|TTemplateParam|TIterable  $input_type_part
     * @param  TNamedObject|TTemplateParam|TIterable  $container_type_part
     * @param  bool           $allow_interface_equality
     *
     * @return bool
     */
    private static function isObjectContainedByObject(
        Codebase $codebase,
        $input_type_part,
        $container_type_part,
        $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result
    ) {
        $intersection_input_types = $input_type_part->extra_types ?: [];
        $intersection_input_types[$input_type_part->getKey(false)] = $input_type_part;

        if ($input_type_part instanceof TTemplateParam) {
            foreach ($input_type_part->as->getAtomicTypes() as $g) {
                if ($g instanceof TNamedObject && $g->extra_types) {
                    $intersection_input_types = array_merge(
                        $intersection_input_types,
                        $g->extra_types
                    );
                }
            }
        }

        $intersection_container_types = $container_type_part->extra_types ?: [];
        $intersection_container_types[$container_type_part->getKey(false)] = $container_type_part;

        if ($container_type_part instanceof TTemplateParam) {
            foreach ($container_type_part->as->getAtomicTypes() as $g) {
                if ($g instanceof TNamedObject && $g->extra_types) {
                    $intersection_container_types = array_merge(
                        $intersection_container_types,
                        $g->extra_types
                    );
                }
            }
        }

        foreach ($intersection_container_types as $container_type_key => $intersection_container_type) {
            $container_was_static = false;

            if ($intersection_container_type instanceof TIterable) {
                $intersection_container_type_lower = 'iterable';
            } elseif ($intersection_container_type instanceof TObjectWithProperties) {
                $intersection_container_type_lower = 'object';
            } elseif ($intersection_container_type instanceof TTemplateParam) {
                if (!$allow_interface_equality) {
                    if (isset($intersection_input_types[$container_type_key])) {
                        continue;
                    }

                    if (\substr($intersection_container_type->defining_class, 0, 3) === 'fn-') {
                        foreach ($intersection_input_types as $intersection_input_type) {
                            if ($intersection_input_type instanceof TTemplateParam
                                && \substr($intersection_input_type->defining_class, 0, 3) === 'fn-'
                                && $intersection_input_type->defining_class
                                    !== $intersection_container_type->defining_class
                            ) {
                                continue 2;
                            }
                        }
                    }

                    return false;
                }

                if ($intersection_container_type->as->isMixed()) {
                    continue;
                }

                $intersection_container_type_lower = null;

                foreach ($intersection_container_type->as->getAtomicTypes() as $g) {
                    if ($g instanceof TNull) {
                        continue;
                    }

                    if ($g instanceof TObject) {
                        continue 2;
                    }

                    if (!$g instanceof TNamedObject) {
                        continue 2;
                    }

                    $intersection_container_type_lower = strtolower($g->value);
                }

                if ($intersection_container_type_lower === null) {
                    return false;
                }
            } else {
                $container_was_static = $intersection_container_type->was_static;

                $intersection_container_type_lower = strtolower(
                    $codebase->classlikes->getUnAliasedName(
                        $intersection_container_type->value
                    )
                );
            }

            foreach ($intersection_input_types as $intersection_input_type) {
                $input_was_static = false;

                if ($intersection_input_type instanceof TIterable) {
                    $intersection_input_type_lower = 'iterable';
                } elseif ($intersection_input_type instanceof TObjectWithProperties) {
                    $intersection_input_type_lower = 'object';
                } elseif ($intersection_input_type instanceof TTemplateParam) {
                    if ($intersection_input_type->as->isMixed()) {
                        continue;
                    }

                    $intersection_input_type_lower = null;

                    foreach ($intersection_input_type->as->getAtomicTypes() as $g) {
                        if ($g instanceof TNull) {
                            continue;
                        }

                        if (!$g instanceof TNamedObject) {
                            continue 2;
                        }

                        $intersection_input_type_lower = strtolower($g->value);
                    }

                    if ($intersection_input_type_lower === null) {
                        return false;
                    }
                } else {
                    $input_was_static = $intersection_input_type->was_static;

                    $intersection_input_type_lower = strtolower(
                        $codebase->classlikes->getUnAliasedName(
                            $intersection_input_type->value
                        )
                    );
                }

                if ($intersection_container_type instanceof TTemplateParam
                    && $intersection_input_type instanceof TTemplateParam
                ) {
                    if ($intersection_container_type->param_name !== $intersection_input_type->param_name
                        || ((string)$intersection_container_type->defining_class
                            !== (string)$intersection_input_type->defining_class
                            && \substr($intersection_input_type->defining_class, 0, 3) !== 'fn-'
                            && \substr($intersection_container_type->defining_class, 0, 3) !== 'fn-')
                    ) {
                        if (\substr($intersection_input_type->defining_class, 0, 3) !== 'fn-') {
                            $input_class_storage = $codebase->classlike_storage_provider->get(
                                $intersection_input_type->defining_class
                            );

                            if (isset($input_class_storage->template_type_extends
                                    [$intersection_container_type->defining_class]
                                    [$intersection_container_type->param_name])
                            ) {
                                continue;
                            }
                        }

                        return false;
                    }
                }

                if (!$intersection_container_type instanceof TTemplateParam
                    || $intersection_input_type instanceof TTemplateParam
                ) {
                    if ($intersection_container_type_lower === $intersection_input_type_lower) {
                        if ($container_was_static
                            && !$input_was_static
                            && !$intersection_input_type instanceof TTemplateParam
                        ) {
                            if ($atomic_comparison_result) {
                                $atomic_comparison_result->type_coerced = true;
                            }

                            continue;
                        }

                        continue 2;
                    }

                    if ($intersection_input_type_lower === 'generator'
                        && in_array($intersection_container_type_lower, ['iterator', 'traversable', 'iterable'], true)
                    ) {
                        continue 2;
                    }

                    if ($intersection_container_type_lower === 'iterable') {
                        if ($intersection_input_type_lower === 'traversable'
                            || ($codebase->classlikes->classExists($intersection_input_type_lower)
                                && $codebase->classlikes->classImplements(
                                    $intersection_input_type_lower,
                                    'Traversable'
                                ))
                            || ($codebase->classlikes->interfaceExists($intersection_input_type_lower)
                                && $codebase->classlikes->interfaceExtends(
                                    $intersection_input_type_lower,
                                    'Traversable'
                                ))
                        ) {
                            continue 2;
                        }
                    }

                    if ($intersection_input_type_lower === 'traversable'
                        && $intersection_container_type_lower === 'iterable'
                    ) {
                        continue 2;
                    }

                    $input_type_is_interface = $codebase->interfaceExists($intersection_input_type_lower);
                    $container_type_is_interface = $codebase->interfaceExists($intersection_container_type_lower);

                    if ($allow_interface_equality && $input_type_is_interface && $container_type_is_interface) {
                        continue 2;
                    }

                    if ($codebase->classExists($intersection_input_type_lower)
                        && $codebase->classOrInterfaceExists($intersection_container_type_lower)
                        && $codebase->classExtendsOrImplements(
                            $intersection_input_type_lower,
                            $intersection_container_type_lower
                        )
                    ) {
                        if ($container_was_static && !$input_was_static) {
                            if ($atomic_comparison_result) {
                                $atomic_comparison_result->type_coerced = true;
                            }

                            continue;
                        }

                        continue 2;
                    }

                    if ($input_type_is_interface
                        && $codebase->interfaceExtends(
                            $intersection_input_type_lower,
                            $intersection_container_type_lower
                        )
                    ) {
                        continue 2;
                    }
                }

                if (ExpressionAnalyzer::isMock($intersection_input_type_lower)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Does the input param atomic type match the given param atomic type
     */
    public static function isAtomicContainedBy(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        bool $allow_interface_equality = false,
        bool $allow_float_int_equality = true,
        ?TypeComparisonResult $atomic_comparison_result = null
    ) : bool {
        if (($container_type_part instanceof TTemplateParam
                || ($container_type_part instanceof TNamedObject
                    && isset($container_type_part->extra_types)))
            && ($input_type_part instanceof TTemplateParam
                || ($input_type_part instanceof TNamedObject
                    && isset($input_type_part->extra_types)))
        ) {
            return self::isObjectContainedByObject(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result
            );
        }

        if ($container_type_part instanceof TMixed
            || ($container_type_part instanceof TTemplateParam
                && $container_type_part->as->isMixed()
                && !$container_type_part->extra_types
                && $input_type_part instanceof TMixed)
        ) {
            if (get_class($container_type_part) === TEmptyMixed::class
                && get_class($input_type_part) === TMixed::class
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                    $atomic_comparison_result->type_coerced_from_mixed = true;
                }

                return false;
            }

            return true;
        }

        if ($input_type_part instanceof TNever || $input_type_part instanceof Type\Atomic\TEmpty) {
            return true;
        }

        if ($input_type_part instanceof TMixed
            || ($input_type_part instanceof TTemplateParam
                && $input_type_part->as->isMixed()
                && !$input_type_part->extra_types)
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_mixed = true;
            }

            return false;
        }

        if ($input_type_part instanceof TNull) {
            if ($container_type_part instanceof TNull) {
                return true;
            }

            if ($container_type_part instanceof TTemplateParam
                && ($container_type_part->as->isNullable() || $container_type_part->as->isMixed())
            ) {
                return true;
            }

            return false;
        }

        if ($container_type_part instanceof TNull) {
            return false;
        }

        if ($container_type_part instanceof ObjectLike
            && $input_type_part instanceof TArray
        ) {
            $all_string_int_literals = true;

            $properties = [];

            foreach ($input_type_part->type_params[0]->getAtomicTypes() as $atomic_key_type) {
                if ($atomic_key_type instanceof TLiteralString || $atomic_key_type instanceof TLiteralInt) {
                    $properties[$atomic_key_type->value] = $input_type_part->type_params[1];
                } else {
                    $all_string_int_literals = false;
                }
            }

            if ($all_string_int_literals && $properties) {
                $input_type_part = new ObjectLike($properties);
            }
        }

        if ($container_type_part instanceof TNonEmptyString
            && get_class($input_type_part) === TString::class
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if (($input_type_part instanceof Type\Atomic\TLowercaseString
                || $input_type_part instanceof Type\Atomic\TNonEmptyLowercaseString)
            && get_class($container_type_part) === TString::class
        ) {
            return true;
        }

        if (($container_type_part instanceof Type\Atomic\TLowercaseString
                || $container_type_part instanceof Type\Atomic\TNonEmptyLowercaseString)
            && $input_type_part instanceof TString
        ) {
            if (($input_type_part instanceof Type\Atomic\TLowercaseString
                    && $container_type_part instanceof Type\Atomic\TLowercaseString)
                || ($input_type_part instanceof Type\Atomic\TNonEmptyLowercaseString
                    && $container_type_part instanceof Type\Atomic\TNonEmptyLowercaseString)
            ) {
                return true;
            }

            if ($input_type_part instanceof Type\Atomic\TNonEmptyLowercaseString
                && $container_type_part instanceof Type\Atomic\TLowercaseString
            ) {
                return true;
            }

            if ($input_type_part instanceof Type\Atomic\TLowercaseString
                && $container_type_part instanceof Type\Atomic\TNonEmptyLowercaseString
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            if ($input_type_part instanceof TLiteralString) {
                if (strtolower($input_type_part->value) === $input_type_part->value) {
                    return $input_type_part->value || $container_type_part instanceof Type\Atomic\TLowercaseString;
                }

                return false;
            }

            if ($input_type_part instanceof TClassString) {
                return false;
            }

            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($input_type_part->shallowEquals($container_type_part)
            || ($input_type_part instanceof Type\Atomic\TCallableObjectLikeArray
                && $container_type_part instanceof TArray)
            || ($input_type_part instanceof TCallable
                && $container_type_part instanceof TCallable)
            || ($input_type_part instanceof Type\Atomic\TFn
                && $container_type_part instanceof Type\Atomic\TFn)
            || (($input_type_part instanceof TNamedObject
                    || ($input_type_part instanceof TTemplateParam
                        && $input_type_part->as->hasObjectType())
                    || $input_type_part instanceof TIterable)
                && ($container_type_part instanceof TNamedObject
                    || ($container_type_part instanceof TTemplateParam
                        && $container_type_part->isObjectType())
                    || $container_type_part instanceof TIterable)
                && self::isObjectContainedByObject(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $atomic_comparison_result
                )
            )
        ) {
            return self::isMatchingTypeContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result ?: new TypeComparisonResult(),
                $allow_interface_equality
            );
        }

        if ($container_type_part instanceof TTemplateParam && $input_type_part instanceof TTemplateParam) {
            return self::isContainedBy(
                $codebase,
                $input_type_part->as,
                $container_type_part->as,
                false,
                false,
                $atomic_comparison_result,
                $allow_interface_equality
            );
        }

        if ($container_type_part instanceof TTemplateParam) {
            foreach ($container_type_part->as->getAtomicTypes() as $container_as_type_part) {
                if (self::isAtomicContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_as_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result
                )) {
                    if ($allow_interface_equality
                        || ($input_type_part instanceof TArray
                            && !$input_type_part->type_params[1]->isEmpty())
                        || $input_type_part instanceof ObjectLike
                    ) {
                        return true;
                    }
                }
            }

            return false;
        }

        if ($container_type_part instanceof TConditional) {
            $atomic_types = array_merge(
                array_values($container_type_part->if_type->getAtomicTypes()),
                array_values($container_type_part->else_type->getAtomicTypes())
            );

            foreach ($atomic_types as $container_as_type_part) {
                if (self::isAtomicContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_as_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result
                )) {
                    return true;
                }
            }

            return false;
        }

        if ($input_type_part instanceof TTemplateParam) {
            if ($input_type_part->extra_types) {
                foreach ($input_type_part->extra_types as $extra_type) {
                    if (self::isAtomicContainedBy(
                        $codebase,
                        $extra_type,
                        $container_type_part,
                        $allow_interface_equality,
                        $allow_float_int_equality,
                        $atomic_comparison_result
                    )) {
                        return true;
                    }
                }
            }

            foreach ($input_type_part->as->getAtomicTypes() as $input_as_type_part) {
                if ($input_as_type_part instanceof TNull && $container_type_part instanceof TNull) {
                    continue;
                }

                if (self::isAtomicContainedBy(
                    $codebase,
                    $input_as_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result
                )) {
                    return true;
                }
            }

            return false;
        }

        if ($input_type_part instanceof TConditional) {
            $input_atomic_types = array_merge(
                array_values($input_type_part->if_type->getAtomicTypes()),
                array_values($input_type_part->else_type->getAtomicTypes())
            );

            foreach ($input_atomic_types as $input_as_type_part) {
                if (self::isAtomicContainedBy(
                    $codebase,
                    $input_as_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result
                )) {
                    return true;
                }
            }

            return false;
        }

        if ($container_type_part instanceof GetClassT) {
            $first_type = array_values($container_type_part->as_type->getAtomicTypes())[0];

            $container_type_part = new TClassString(
                'object',
                $first_type instanceof TNamedObject ? $first_type : null
            );
        }

        if ($input_type_part instanceof GetClassT) {
            $first_type = array_values($input_type_part->as_type->getAtomicTypes())[0];

            if ($first_type instanceof TTemplateParam) {
                $object_type = array_values($first_type->as->getAtomicTypes())[0];

                $input_type_part = new TTemplateParamClass(
                    $first_type->param_name,
                    $first_type->as->getId(),
                    $object_type instanceof TNamedObject ? $object_type : null,
                    $first_type->defining_class
                );
            } else {
                $input_type_part = new TClassString(
                    'object',
                    $first_type instanceof TNamedObject ? $first_type : null
                );
            }
        }

        if ($input_type_part instanceof GetTypeT) {
            $input_type_part = new TString();

            if ($container_type_part instanceof TLiteralString) {
                return isset(ClassLikeAnalyzer::GETTYPE_TYPES[$container_type_part->value]);
            }
        }

        if ($container_type_part instanceof GetTypeT) {
            $container_type_part = new TString();

            if ($input_type_part instanceof TLiteralString) {
                return isset(ClassLikeAnalyzer::GETTYPE_TYPES[$input_type_part->value]);
            }
        }

        if ($input_type_part->shallowEquals($container_type_part)) {
            return self::isMatchingTypeContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result ?: new TypeComparisonResult(),
                $allow_interface_equality
            );
        }

        if ($input_type_part instanceof TFalse
            && $container_type_part instanceof TBool
            && !($container_type_part instanceof TTrue)
        ) {
            return true;
        }

        if ($input_type_part instanceof TTrue
            && $container_type_part instanceof TBool
            && !($container_type_part instanceof TFalse)
        ) {
            return true;
        }

        // from https://wiki.php.net/rfc/scalar_type_hints_v5:
        //
        // > int types can resolve a parameter type of float
        if ($input_type_part instanceof TInt
            && $container_type_part instanceof TFloat
            && !$container_type_part instanceof TLiteralFloat
            && $allow_float_int_equality
        ) {
            return true;
        }

        if ($input_type_part instanceof TNamedObject
            && $input_type_part->value === 'static'
            && $container_type_part instanceof TNamedObject
            && strtolower($container_type_part->value) === 'self'
        ) {
            return true;
        }

        if ($container_type_part instanceof TCallable && $input_type_part instanceof Type\Atomic\TFn) {
            $all_types_contain = true;

            if (self::compareCallable(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result ?: new TypeComparisonResult(),
                $all_types_contain
            ) === false
            ) {
                return false;
            }

            if (!$all_types_contain) {
                return false;
            }
        }

        if ($input_type_part instanceof TNamedObject &&
            $input_type_part->value === 'Closure' &&
            $container_type_part instanceof TCallable
        ) {
            return true;
        }

        if ($input_type_part instanceof TObject &&
            $container_type_part instanceof TCallable
        ) {
            return true;
        }

        if ($input_type_part instanceof Type\Atomic\TCallableObject &&
            $container_type_part instanceof TObject
        ) {
            return true;
        }

        if ($container_type_part instanceof TNumeric &&
            $input_type_part->isNumericType()
        ) {
            return true;
        }

        if ($container_type_part instanceof TArrayKey
            && $input_type_part instanceof TNumeric
        ) {
            return true;
        }

        if ($container_type_part instanceof TArrayKey
            && ($input_type_part instanceof TInt
                || $input_type_part instanceof TString
                || $input_type_part instanceof Type\Atomic\TTemplateKeyOf)
        ) {
            return true;
        }

        if ($input_type_part instanceof Type\Atomic\TTemplateKeyOf) {
            foreach ($input_type_part->as->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TArray) {
                    foreach ($atomic_type->type_params[0]->getAtomicTypes() as $array_key_atomic) {
                        if (!self::isAtomicContainedBy(
                            $codebase,
                            $array_key_atomic,
                            $container_type_part,
                            $allow_interface_equality,
                            $allow_float_int_equality,
                            $atomic_comparison_result
                        )) {
                            return false;
                        }
                    }
                }
            }

            return true;
        }

        if ($input_type_part instanceof TArrayKey &&
            ($container_type_part instanceof TInt || $container_type_part instanceof TString)
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_mixed = true;
                $atomic_comparison_result->scalar_type_match_found = true;
            }

            return false;
        }

        if (($container_type_part instanceof ObjectLike
                && $input_type_part instanceof ObjectLike)
            || ($container_type_part instanceof TObjectWithProperties
                && $input_type_part instanceof TObjectWithProperties)
        ) {
            $all_types_contain = true;

            foreach ($container_type_part->properties as $key => $container_property_type) {
                if (!isset($input_type_part->properties[$key])) {
                    if (!$container_property_type->possibly_undefined) {
                        $all_types_contain = false;
                    }

                    continue;
                }

                $input_property_type = $input_type_part->properties[$key];

                $property_type_comparison = new TypeComparisonResult();

                if (!$input_property_type->isEmpty()
                    && !self::isContainedBy(
                        $codebase,
                        $input_property_type,
                        $container_property_type,
                        $input_property_type->ignore_nullable_issues,
                        $input_property_type->ignore_falsable_issues,
                        $property_type_comparison,
                        $allow_interface_equality
                    )
                    && !$property_type_comparison->type_coerced_from_scalar
                ) {
                    $inverse_property_type_comparison = new TypeComparisonResult();

                    if ($atomic_comparison_result) {
                        if (self::isContainedBy(
                            $codebase,
                            $container_property_type,
                            $input_property_type,
                            false,
                            false,
                            $inverse_property_type_comparison,
                            $allow_interface_equality
                        )
                        || $inverse_property_type_comparison->type_coerced_from_scalar
                        ) {
                            $atomic_comparison_result->type_coerced = true;
                        }
                    }

                    $all_types_contain = false;
                }
            }

            if ($all_types_contain) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->to_string_cast = false;
                }

                return true;
            }

            return false;
        }

        if ($container_type_part instanceof TIterable) {
            if ($input_type_part instanceof TArray
                || $input_type_part instanceof ObjectLike
                || $input_type_part instanceof TList
            ) {
                if ($input_type_part instanceof ObjectLike) {
                    $input_type_part = $input_type_part->getGenericArrayType();
                } elseif ($input_type_part instanceof TList) {
                    $input_type_part = new TArray([Type::getInt(), $input_type_part->type_param]);
                }

                $all_types_contain = true;

                foreach ($input_type_part->type_params as $i => $input_param) {
                    $container_param_offset = $i - (2 - count($container_type_part->type_params));

                    if ($container_param_offset === -1) {
                        continue;
                    }

                    $container_param = $container_type_part->type_params[$container_param_offset];

                    if ($i === 0
                        && $input_param->hasMixed()
                        && $container_param->hasString()
                        && $container_param->hasInt()
                    ) {
                        continue;
                    }

                    $array_comparison_result = new TypeComparisonResult();

                    if (!$input_param->isEmpty()
                        && !self::isContainedBy(
                            $codebase,
                            $input_param,
                            $container_param,
                            $input_param->ignore_nullable_issues,
                            $input_param->ignore_falsable_issues,
                            $array_comparison_result,
                            $allow_interface_equality
                        )
                        && !$array_comparison_result->type_coerced_from_scalar
                    ) {
                        if ($atomic_comparison_result && $array_comparison_result->type_coerced_from_mixed) {
                            $atomic_comparison_result->type_coerced_from_mixed = true;
                        }
                        $all_types_contain = false;
                    }
                }

                if ($all_types_contain) {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->to_string_cast = false;
                    }

                    return true;
                }

                return false;
            }

            if ($input_type_part->hasTraversableInterface($codebase)) {
                return true;
            }
        }

        if ($container_type_part instanceof TScalar && $input_type_part instanceof Scalar) {
            return true;
        }

        if (get_class($container_type_part) === TInt::class && $input_type_part instanceof TLiteralInt) {
            return true;
        }

        if (get_class($container_type_part) === TFloat::class && $input_type_part instanceof TLiteralFloat) {
            return true;
        }

        if ($container_type_part instanceof TNonEmptyString
            && $input_type_part instanceof TLiteralString
            && $input_type_part->value === ''
        ) {
            return false;
        }

        if ((get_class($container_type_part) === TString::class
                || get_class($container_type_part) === TNonEmptyString::class
                || get_class($container_type_part) === TSingleLetter::class)
            && $input_type_part instanceof TLiteralString
        ) {
            return true;
        }

        if (get_class($input_type_part) === TInt::class && $container_type_part instanceof TLiteralInt) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        if (get_class($input_type_part) === TFloat::class && $container_type_part instanceof TLiteralFloat) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        if ((get_class($input_type_part) === TString::class
                || get_class($input_type_part) === TSingleLetter::class
                || get_class($input_type_part) === TNonEmptyString::class)
            && $container_type_part instanceof TLiteralString
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        if (($input_type_part instanceof Type\Atomic\TLowercaseString
                || $input_type_part instanceof Type\Atomic\TNonEmptyLowercaseString)
            && $container_type_part instanceof TLiteralString
            && strtolower($container_type_part->value) === $container_type_part->value
        ) {
            if ($atomic_comparison_result
                && ($container_type_part->value || $input_type_part instanceof Type\Atomic\TLowercaseString)
            ) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        if (($container_type_part instanceof TClassString || $container_type_part instanceof TLiteralClassString)
            && ($input_type_part instanceof TClassString || $input_type_part instanceof TLiteralClassString)
        ) {
            if ($container_type_part instanceof TLiteralClassString
                && $input_type_part instanceof TLiteralClassString
            ) {
                return $container_type_part->value === $input_type_part->value;
            }

            if ($container_type_part instanceof TTemplateParamClass
                && get_class($input_type_part) === TClassString::class
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            if ($container_type_part instanceof TClassString
                && $container_type_part->as === 'object'
                && !$container_type_part->as_type
            ) {
                return true;
            }

            if ($input_type_part instanceof TClassString
                && $input_type_part->as === 'object'
                && !$input_type_part->as_type
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                    $atomic_comparison_result->type_coerced_from_scalar = true;
                }

                return false;
            }

            $fake_container_object = $container_type_part instanceof TClassString
                && $container_type_part->as_type
                ? $container_type_part->as_type
                : new TNamedObject(
                    $container_type_part instanceof TClassString
                        ? $container_type_part->as
                        : $container_type_part->value
                );

            $fake_input_object = $input_type_part instanceof TClassString
                && $input_type_part->as_type
                ? $input_type_part->as_type
                : new TNamedObject(
                    $input_type_part instanceof TClassString
                        ? $input_type_part->as
                        : $input_type_part->value
                );

            return self::isAtomicContainedBy(
                $codebase,
                $fake_input_object,
                $fake_container_object,
                $allow_interface_equality,
                $allow_float_int_equality,
                $atomic_comparison_result
            );
        }

        if ($container_type_part instanceof TString && $input_type_part instanceof TTraitString) {
            return true;
        }

        if ($container_type_part instanceof TTraitString
            && (get_class($input_type_part) === TString::class
                || get_class($input_type_part) === TNonEmptyString::class)
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if (($input_type_part instanceof TClassString
            || $input_type_part instanceof TLiteralClassString)
            && (get_class($container_type_part) === TString::class
                || get_class($container_type_part) === TSingleLetter::class
                || get_class($container_type_part) === TNonEmptyString::class)
        ) {
            return true;
        }

        if ($input_type_part instanceof TCallableString
            && (get_class($container_type_part) === TString::class
                || get_class($container_type_part) === TSingleLetter::class
                || get_class($container_type_part) === TNonEmptyString::class)
        ) {
            return true;
        }

        if ($container_type_part instanceof TString
            && ($input_type_part instanceof TNumericString
                || $input_type_part instanceof THtmlEscapedString)
        ) {
            if ($container_type_part instanceof TLiteralString) {
                if (\is_numeric($container_type_part->value) && $atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            return true;
        }

        if ($input_type_part instanceof TString
            && ($container_type_part instanceof TNumericString
                || $container_type_part instanceof THtmlEscapedString)
        ) {
            if ($input_type_part instanceof TLiteralString) {
                return \is_numeric($input_type_part->value);
            }
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TCallableString
            && $input_type_part instanceof TLiteralString
        ) {
            $input_callable = self::getCallableFromAtomic($codebase, $input_type_part);
            $container_callable = self::getCallableFromAtomic($codebase, $container_type_part);

            if ($input_callable && $container_callable) {
                $all_types_contain = true;

                if (self::compareCallable(
                    $codebase,
                    $input_callable,
                    $container_callable,
                    $atomic_comparison_result ?: new TypeComparisonResult(),
                    $all_types_contain
                ) === false
                ) {
                    return false;
                }

                if (!$all_types_contain) {
                    return false;
                }
            }

            return true;
        }

        if (($container_type_part instanceof TClassString
                || $container_type_part instanceof TLiteralClassString
                || $container_type_part instanceof TCallableString)
            && $input_type_part instanceof TString
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if (($container_type_part instanceof TString || $container_type_part instanceof TScalar)
            && $input_type_part instanceof TNamedObject
        ) {
            // check whether the object has a __toString method
            if ($codebase->classOrInterfaceExists($input_type_part->value)
                && $codebase->methods->methodExists(
                    new \Psalm\Internal\MethodIdentifier(
                        $input_type_part->value,
                        '__tostring'
                    )
                )
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->to_string_cast = true;
                }

                return true;
            }

            // PHP 5.6 doesn't support this natively, so this introduces a bug *just* when checking PHP 5.6 code
            if ($input_type_part->value === 'ReflectionType') {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->to_string_cast = true;
                }

                return true;
            }
        }

        if (($container_type_part instanceof TString || $container_type_part instanceof TScalar)
            && $input_type_part instanceof TObjectWithProperties
            && isset($input_type_part->methods['__toString'])
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->to_string_cast = true;
            }

            return true;
        }

        if ($container_type_part instanceof Type\Atomic\TFn && $input_type_part instanceof TCallable) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TCallable &&
            (
                $input_type_part instanceof TLiteralString
                || $input_type_part instanceof TCallableString
                || $input_type_part instanceof TArray
                || $input_type_part instanceof ObjectLike
                || $input_type_part instanceof TList
                || (
                    $input_type_part instanceof TNamedObject &&
                    $codebase->classOrInterfaceExists($input_type_part->value) &&
                    $codebase->methodExists($input_type_part->value . '::__invoke')
                )
            )
        ) {
            if ($input_type_part instanceof TList) {
                if ($input_type_part->type_param->isMixed()
                    || $input_type_part->type_param->hasScalar()
                ) {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced_from_mixed = true;
                        $atomic_comparison_result->type_coerced = true;
                    }

                    return false;
                }

                if (!$input_type_part->type_param->hasString()) {
                    return false;
                }

                if (!$input_type_part instanceof Type\Atomic\TCallableList) {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced_from_mixed = true;
                        $atomic_comparison_result->type_coerced = true;
                    }

                    return false;
                }
            }

            if ($input_type_part instanceof TArray) {
                if ($input_type_part->type_params[1]->isMixed()
                    || $input_type_part->type_params[1]->hasScalar()
                ) {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced_from_mixed = true;
                        $atomic_comparison_result->type_coerced = true;
                    }

                    return false;
                }

                if (!$input_type_part->type_params[1]->hasString()) {
                    return false;
                }

                if (!$input_type_part instanceof Type\Atomic\TCallableArray) {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced_from_mixed = true;
                        $atomic_comparison_result->type_coerced = true;
                    }

                    return false;
                }
            } elseif ($input_type_part instanceof ObjectLike) {
                $method_id = self::getCallableMethodIdFromObjectLike($input_type_part);

                if ($method_id === 'not-callable') {
                    return false;
                }

                if (!$method_id) {
                    return true;
                }

                try {
                    $method_id = $codebase->methods->getDeclaringMethodId($method_id);

                    if (!$method_id) {
                        return false;
                    }

                    $codebase->methods->getStorage($method_id);
                } catch (\Exception $e) {
                    return false;
                }
            }

            $input_callable = self::getCallableFromAtomic($codebase, $input_type_part, $container_type_part);

            if ($input_callable) {
                $all_types_contain = true;

                if (self::compareCallable(
                    $codebase,
                    $input_callable,
                    $container_type_part,
                    $atomic_comparison_result ?: new TypeComparisonResult(),
                    $all_types_contain
                ) === false
                ) {
                    return false;
                }

                if (!$all_types_contain) {
                    return false;
                }
            }

            return true;
        }

        if ($input_type_part instanceof TNumeric) {
            if ($container_type_part->isNumericType()) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->scalar_type_match_found = true;
                }
            }
        }

        if ($input_type_part instanceof Scalar) {
            if ($container_type_part instanceof Scalar
                && !$container_type_part instanceof TLiteralInt
                && !$container_type_part instanceof TLiteralString
                && !$container_type_part instanceof TLiteralFloat
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->scalar_type_match_found = true;
                }
            }
        } elseif ($container_type_part instanceof TObject
            && $input_type_part instanceof TNamedObject
        ) {
            if ($container_type_part instanceof TObjectWithProperties
                && $input_type_part->value !== 'stdClass'
            ) {
                $all_types_contain = true;

                foreach ($container_type_part->properties as $property_name => $container_property_type) {
                    if (!is_string($property_name)) {
                        continue;
                    }

                    if (!$codebase->properties->propertyExists(
                        $input_type_part . '::$' . $property_name,
                        true
                    )) {
                        $all_types_contain = false;

                        continue;
                    }

                    $property_declaring_class = (string) $codebase->properties->getDeclaringClassForProperty(
                        $input_type_part . '::$' . $property_name,
                        true
                    );

                    $class_storage = $codebase->classlike_storage_provider->get($property_declaring_class);

                    $input_property_storage = $class_storage->properties[$property_name];

                    $input_property_type = $input_property_storage->type ?: Type::getMixed();

                    $property_type_comparison = new TypeComparisonResult();

                    if (!$input_property_type->isEmpty()
                        && !self::isContainedBy(
                            $codebase,
                            $input_property_type,
                            $container_property_type,
                            false,
                            false,
                            $property_type_comparison,
                            $allow_interface_equality
                        )
                        && !$property_type_comparison->type_coerced_from_scalar
                    ) {
                        $inverse_property_type_comparison = new TypeComparisonResult();

                        if (self::isContainedBy(
                            $codebase,
                            $container_property_type,
                            $input_property_type,
                            false,
                            false,
                            $inverse_property_type_comparison,
                            $allow_interface_equality
                        )
                        || $inverse_property_type_comparison->type_coerced_from_scalar
                        ) {
                            if ($atomic_comparison_result) {
                                $atomic_comparison_result->type_coerced = true;
                            }
                        }

                        $all_types_contain = false;
                    }
                }

                if ($all_types_contain === true) {
                    return true;
                }

                return false;
            }

            return true;
        } elseif ($atomic_comparison_result) {
            if ($input_type_part instanceof TObject && $container_type_part instanceof TNamedObject) {
                $atomic_comparison_result->type_coerced = true;
            } elseif ($container_type_part instanceof TNamedObject
                && $input_type_part instanceof TNamedObject
            ) {
                if ($container_type_part->was_static
                    && !$input_type_part->was_static
                ) {
                    $atomic_comparison_result->type_coerced = true;
                } elseif ($codebase->classOrInterfaceExists($input_type_part->value)
                    && (
                        (
                            $codebase->classExists($container_type_part->value)
                            && $codebase->classExtendsOrImplements(
                                $container_type_part->value,
                                $input_type_part->value
                            )
                        )
                        ||
                        (
                            $codebase->interfaceExists($container_type_part->value)
                            && $codebase->interfaceExtends(
                                $container_type_part->value,
                                $input_type_part->value
                            )
                        )
                    )
                ) {
                    $atomic_comparison_result->type_coerced = true;
                }
            }
        }

        return false;
    }

    /**
     * @return ?TCallable
     */
    public static function getCallableFromAtomic(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        ?TCallable $container_type_part = null,
        ?StatementsAnalyzer $statements_analyzer = null
    ) : ?TCallable {
        if ($input_type_part instanceof TLiteralString && $input_type_part->value) {
            try {
                $function_storage = $codebase->functions->getStorage(
                    $statements_analyzer,
                    strtolower($input_type_part->value)
                );

                return new TCallable(
                    'callable',
                    $function_storage->params,
                    $function_storage->return_type,
                    $function_storage->pure
                );
            } catch (\UnexpectedValueException $e) {
                if (InternalCallMapHandler::inCallMap($input_type_part->value)) {
                    $args = [];

                    $nodes = new \Psalm\Internal\Provider\NodeDataProvider();

                    if ($container_type_part && $container_type_part->params) {
                        foreach ($container_type_part->params as $i => $param) {
                            $arg = new \PhpParser\Node\Arg(
                                new \PhpParser\Node\Expr\Variable('_' . $i)
                            );

                            if ($param->type) {
                                $nodes->setType($arg->value, $param->type);
                            }

                            $args[] = $arg;
                        }
                    }

                    $matching_callable = \Psalm\Internal\Codebase\InternalCallMapHandler::getCallableFromCallMapById(
                        $codebase,
                        $input_type_part->value,
                        $args,
                        $nodes
                    );

                    $must_use = false;

                    $matching_callable->is_pure = $codebase->functions->isCallMapFunctionPure(
                        $codebase,
                        $statements_analyzer ? $statements_analyzer->node_data : null,
                        $input_type_part->value,
                        null,
                        $must_use
                    );

                    return $matching_callable;
                }
            }
        } elseif ($input_type_part instanceof ObjectLike) {
            $method_id = self::getCallableMethodIdFromObjectLike($input_type_part);
            if ($method_id && $method_id !== 'not-callable') {
                try {
                    $method_storage = $codebase->methods->getStorage($method_id);
                    $method_fqcln = $method_id->fq_class_name;

                    $converted_return_type = null;

                    if ($method_storage->return_type) {
                        $converted_return_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                            $codebase,
                            $method_storage->return_type,
                            $method_fqcln,
                            $method_fqcln,
                            null
                        );
                    }

                    return new TCallable(
                        'callable',
                        $method_storage->params,
                        $converted_return_type
                    );
                } catch (\UnexpectedValueException $e) {
                    // do nothing
                }
            }
        } elseif ($input_type_part instanceof TNamedObject
            && $codebase->classExists($input_type_part->value)
        ) {
            $invoke_id = new \Psalm\Internal\MethodIdentifier(
                $input_type_part->value,
                '__invoke'
            );

            if ($codebase->methods->methodExists($invoke_id)) {
                return new TCallable(
                    'callable',
                    $codebase->methods->getMethodParams($invoke_id),
                    $codebase->methods->getMethodReturnType(
                        $invoke_id,
                        $input_type_part->value
                    )
                );
            }
        }

        return null;
    }

    /** @return null|'not-callable'|\Psalm\Internal\MethodIdentifier */
    public static function getCallableMethodIdFromObjectLike(
        ObjectLike $input_type_part,
        Codebase $codebase = null,
        string $calling_method_id = null,
        string $file_name = null
    ) {
        if (!isset($input_type_part->properties[0])
            || !isset($input_type_part->properties[1])
        ) {
            return 'not-callable';
        }

        $lhs = $input_type_part->properties[0];
        $rhs = $input_type_part->properties[1];

        $rhs_low_info = $rhs->hasMixed() || $rhs->hasScalar();

        if ($rhs_low_info || !$rhs->isSingleStringLiteral()) {
            if (!$rhs_low_info && !$rhs->hasString()) {
                return 'not-callable';
            }

            if ($codebase && ($calling_method_id || $file_name)) {
                foreach ($lhs->getAtomicTypes() as $lhs_atomic_type) {
                    if ($lhs_atomic_type instanceof TNamedObject) {
                        $codebase->analyzer->addMixedMemberName(
                            strtolower($lhs_atomic_type->value) . '::',
                            $calling_method_id ?: $file_name
                        );
                    }
                }
            }

            return null;
        }

        $method_name = $rhs->getSingleStringLiteral()->value;

        $class_name = null;

        if ($lhs->isSingleStringLiteral()) {
            $class_name = $lhs->getSingleStringLiteral()->value;
            if ($class_name[0] === '\\') {
                $class_name = \substr($class_name, 1);
            }
        } elseif ($lhs->isSingle()) {
            foreach ($lhs->getAtomicTypes() as $lhs_atomic_type) {
                if ($lhs_atomic_type instanceof TNamedObject) {
                    $class_name = $lhs_atomic_type->value;
                }
            }
        }

        if ($class_name === 'self'
            || $class_name === 'static'
            || $class_name === 'parent'
        ) {
            return null;
        }

        if (!$class_name) {
            if ($codebase && ($calling_method_id || $file_name)) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($method_name),
                    $calling_method_id ?: $file_name
                );
            }

            return null;
        }

        return new \Psalm\Internal\MethodIdentifier(
            $class_name,
            strtolower($method_name)
        );
    }

    private static function isMatchingTypeContainedBy(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        TypeComparisonResult $atomic_comparison_result,
        bool $allow_interface_equality
    ) : bool {
        $all_types_contain = true;

        if ($container_type_part instanceof TIterable
            && !$container_type_part->extra_types
            && !$input_type_part instanceof TIterable
        ) {
            $container_type_part = new TGenericObject(
                'Traversable',
                $container_type_part->type_params
            );
        }

        if ($container_type_part instanceof TGenericObject || $container_type_part instanceof TIterable) {
            if (!$input_type_part instanceof TGenericObject && !$input_type_part instanceof TIterable) {
                if ($input_type_part instanceof TNamedObject
                    && $codebase->classExists($input_type_part->value)
                ) {
                    $class_storage = $codebase->classlike_storage_provider->get($input_type_part->value);

                    $container_class = $container_type_part->value;

                    // attempt to transform it
                    if (isset($class_storage->template_type_extends[$container_class])) {
                        $extends_list = $class_storage->template_type_extends[$container_class];

                        $generic_params = [];

                        foreach ($extends_list as $key => $value) {
                            if (is_string($key)) {
                                $generic_params[] = $value;
                            }
                        }

                        if (!$generic_params) {
                            return false;
                        }

                        $input_type_part = new TGenericObject(
                            $input_type_part->value,
                            $generic_params
                        );
                    }
                }

                if (!$input_type_part instanceof TGenericObject) {
                    if ($input_type_part instanceof TNamedObject) {
                        $input_type_part = new TGenericObject(
                            $input_type_part->value,
                            array_fill(0, count($container_type_part->type_params), Type::getMixed())
                        );
                    } else {
                        $atomic_comparison_result->type_coerced = true;
                        $atomic_comparison_result->type_coerced_from_mixed = true;

                        return false;
                    }
                }
            }

            $container_type_params_covariant = [];

            $input_type_params = \Psalm\Internal\Type\UnionTemplateHandler::getMappedGenericTypeParams(
                $codebase,
                $input_type_part,
                $container_type_part,
                $container_type_params_covariant
            );

            foreach ($input_type_params as $i => $input_param) {
                if (!isset($container_type_part->type_params[$i])) {
                    break;
                }

                $container_param = $container_type_part->type_params[$i];

                if ($input_param->isEmpty()) {
                    if (!$atomic_comparison_result->replacement_atomic_type) {
                        $atomic_comparison_result->replacement_atomic_type = clone $input_type_part;
                    }

                    if ($atomic_comparison_result->replacement_atomic_type instanceof TGenericObject) {
                        /** @psalm-suppress PropertyTypeCoercion */
                        $atomic_comparison_result->replacement_atomic_type->type_params[$i]
                            = clone $container_param;
                    }

                    continue;
                }

                $param_comparison_result = new TypeComparisonResult();

                if (!self::isContainedBy(
                    $codebase,
                    $input_param,
                    $container_param,
                    $input_param->ignore_nullable_issues,
                    $input_param->ignore_falsable_issues,
                    $param_comparison_result,
                    $allow_interface_equality
                )) {
                    if ($input_type_part->value === 'Generator'
                        && $i === 2
                        && $param_comparison_result->type_coerced_from_mixed
                    ) {
                        continue;
                    }

                    $atomic_comparison_result->type_coerced
                        = $param_comparison_result->type_coerced === true
                            && $atomic_comparison_result->type_coerced !== false;

                    $atomic_comparison_result->type_coerced_from_mixed
                        = $param_comparison_result->type_coerced_from_mixed === true
                            && $atomic_comparison_result->type_coerced_from_mixed !== false;

                    $atomic_comparison_result->type_coerced_from_as_mixed
                        = $param_comparison_result->type_coerced_from_as_mixed === true
                            && $atomic_comparison_result->type_coerced_from_as_mixed !== false;

                    $atomic_comparison_result->to_string_cast
                        = $param_comparison_result->to_string_cast === true
                            && $atomic_comparison_result->to_string_cast !== false;

                    $atomic_comparison_result->type_coerced_from_scalar
                        = $param_comparison_result->type_coerced_from_scalar === true
                            && $atomic_comparison_result->type_coerced_from_scalar !== false;

                    $atomic_comparison_result->scalar_type_match_found
                        = $param_comparison_result->scalar_type_match_found === true
                            && $atomic_comparison_result->scalar_type_match_found !== false;

                    if (!$param_comparison_result->type_coerced_from_as_mixed) {
                        $all_types_contain = false;
                    }
                } elseif (!$input_type_part instanceof TIterable
                    && !$container_type_part instanceof TIterable
                    && !$container_param->hasTemplate()
                    && !$input_param->hasTemplate()
                ) {
                    if ($input_param->hasEmptyArray()
                        || $input_param->hasLiteralValue()
                    ) {
                        if (!$atomic_comparison_result->replacement_atomic_type) {
                            $atomic_comparison_result->replacement_atomic_type = clone $input_type_part;
                        }

                        if ($atomic_comparison_result->replacement_atomic_type instanceof TGenericObject) {
                            /** @psalm-suppress PropertyTypeCoercion */
                            $atomic_comparison_result->replacement_atomic_type->type_params[$i]
                                = clone $container_param;
                        }
                    } else {
                        if (!($container_type_params_covariant[$i] ?? false)
                            && !$container_param->had_template
                        ) {
                            // Make sure types are basically the same
                            if (!self::isContainedBy(
                                $codebase,
                                $container_param,
                                $input_param,
                                $container_param->ignore_nullable_issues,
                                $container_param->ignore_falsable_issues,
                                $param_comparison_result,
                                $allow_interface_equality
                            ) || $param_comparison_result->type_coerced
                            ) {
                                if ($container_param->hasFormerStaticObject()
                                    && $input_param->isFormerStaticObject()
                                    && self::isContainedBy(
                                        $codebase,
                                        $input_param,
                                        $container_param,
                                        $container_param->ignore_nullable_issues,
                                        $container_param->ignore_falsable_issues,
                                        $param_comparison_result,
                                        $allow_interface_equality
                                    )
                                ) {
                                    // do nothing
                                } else {
                                    if ($container_param->hasMixed() || $container_param->isArrayKey()) {
                                        $atomic_comparison_result->type_coerced_from_mixed = true;
                                    } else {
                                        $all_types_contain = false;
                                    }

                                    $atomic_comparison_result->type_coerced = false;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($container_type_part instanceof Type\Atomic\TFn) {
            if (!$input_type_part instanceof Type\Atomic\TFn) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_mixed = true;

                return false;
            }

            if (self::compareCallable(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result,
                $all_types_contain
            ) === false
            ) {
                return false;
            }
        }

        if ($container_type_part instanceof Type\Atomic\TCallable
            && $input_type_part instanceof Type\Atomic\TCallable
        ) {
            if (self::compareCallable(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result,
                $all_types_contain
            ) === false
            ) {
                return false;
            }
        }

        if ($container_type_part instanceof TList
            && $input_type_part instanceof ObjectLike
        ) {
            if ($input_type_part->is_list) {
                $input_type_part = $input_type_part->getList();
            } else {
                return false;
            }
        }

        if ($container_type_part instanceof TList
            && $input_type_part instanceof TClassStringMap
        ) {
            return false;
        }

        if ($container_type_part instanceof TList
            && $input_type_part instanceof TArray
            && $input_type_part->type_params[1]->isEmpty()
        ) {
            return !$container_type_part instanceof TNonEmptyList;
        }

        if ($input_type_part instanceof TList
            && $container_type_part instanceof TList
        ) {
            if (!self::isContainedBy(
                $codebase,
                $input_type_part->type_param,
                $container_type_part->type_param,
                $input_type_part->type_param->ignore_nullable_issues,
                $input_type_part->type_param->ignore_falsable_issues,
                $atomic_comparison_result,
                $allow_interface_equality
            )) {
                return false;
            }

            return $input_type_part instanceof TNonEmptyList
                || !$container_type_part instanceof TNonEmptyList;
        }

        $prior_input_type_part = $input_type_part;

        if (($input_type_part instanceof TArray
                || $input_type_part instanceof ObjectLike
                || $input_type_part instanceof TList
                || $input_type_part instanceof TClassStringMap)
            && ($container_type_part instanceof TArray
                || $container_type_part instanceof ObjectLike
                || $container_type_part instanceof TList
                || $container_type_part instanceof TClassStringMap)
        ) {
            if ($container_type_part instanceof ObjectLike) {
                $generic_container_type_part = $container_type_part->getGenericArrayType();

                $container_params_can_be_undefined = (bool) array_reduce(
                    $container_type_part->properties,
                    /**
                     * @param bool $carry
                     *
                     * @return bool
                     */
                    function ($carry, Type\Union $item) {
                        return $carry || $item->possibly_undefined;
                    },
                    false
                );

                if ($input_type_part instanceof TArray
                    && !$input_type_part->type_params[0]->hasMixed()
                    && !($input_type_part->type_params[1]->isEmpty()
                        && $container_params_can_be_undefined)
                ) {
                    $all_types_contain = false;
                    $atomic_comparison_result->type_coerced = true;
                }

                $container_type_part = $generic_container_type_part;
            }

            if ($input_type_part instanceof ObjectLike) {
                $input_type_part = $input_type_part->getGenericArrayType();
            }

            if ($input_type_part instanceof TClassStringMap) {
                $input_type_part = new TArray([
                    $input_type_part->getStandinKeyParam(),
                    clone $input_type_part->value_param
                ]);
            }

            if ($container_type_part instanceof TClassStringMap) {
                $container_type_part = new TArray([
                    $container_type_part->getStandinKeyParam(),
                    clone $container_type_part->value_param
                ]);
            }

            if ($container_type_part instanceof TList) {
                $all_types_contain = false;
                $atomic_comparison_result->type_coerced = true;

                $container_type_part = new TArray([Type::getInt(), clone $container_type_part->type_param]);
            }

            if ($input_type_part instanceof TList) {
                if ($input_type_part instanceof TNonEmptyList) {
                    $input_type_part = new TNonEmptyArray([Type::getInt(), clone $input_type_part->type_param]);
                } else {
                    $input_type_part = new TArray([Type::getInt(), clone $input_type_part->type_param]);
                }
            }

            foreach ($input_type_part->type_params as $i => $input_param) {
                if ($i > 1) {
                    break;
                }

                $container_param = $container_type_part->type_params[$i];

                if ($i === 0
                    && $input_param->hasMixed()
                    && $container_param->hasString()
                    && $container_param->hasInt()
                ) {
                    continue;
                }

                if ($input_param->isEmpty()
                    && $container_type_part instanceof Type\Atomic\TNonEmptyArray
                ) {
                    return false;
                }

                $param_comparison_result = new TypeComparisonResult();

                if (!$input_param->isEmpty() &&
                    !self::isContainedBy(
                        $codebase,
                        $input_param,
                        $container_param,
                        $input_param->ignore_nullable_issues,
                        $input_param->ignore_falsable_issues,
                        $param_comparison_result,
                        $allow_interface_equality
                    )
                ) {
                    $atomic_comparison_result->type_coerced
                        = $param_comparison_result->type_coerced === true
                            && $atomic_comparison_result->type_coerced !== false;

                    $atomic_comparison_result->type_coerced_from_mixed
                        = $param_comparison_result->type_coerced_from_mixed === true
                            && $atomic_comparison_result->type_coerced_from_mixed !== false;

                    $atomic_comparison_result->type_coerced_from_as_mixed
                        = $param_comparison_result->type_coerced_from_as_mixed === true
                            && $atomic_comparison_result->type_coerced_from_as_mixed !== false;

                    $atomic_comparison_result->to_string_cast
                        = $param_comparison_result->to_string_cast === true
                            && $atomic_comparison_result->to_string_cast !== false;

                    $atomic_comparison_result->type_coerced_from_scalar
                        = $param_comparison_result->type_coerced_from_scalar === true
                            && $atomic_comparison_result->type_coerced_from_scalar !== false;

                    $atomic_comparison_result->scalar_type_match_found
                        = $param_comparison_result->scalar_type_match_found === true
                            && $atomic_comparison_result->scalar_type_match_found !== false;

                    if (!$param_comparison_result->type_coerced_from_as_mixed) {
                        $all_types_contain = false;
                    }
                }
            }
        }

        if ($container_type_part instanceof TNamedObject
            && $input_type_part instanceof TNamedObject
            && $container_type_part->was_static
            && !$input_type_part->was_static
        ) {
            $all_types_contain = false;
            $atomic_comparison_result->type_coerced = true;
        }

        if ($container_type_part instanceof Type\Atomic\TNonEmptyArray
            && !$input_type_part instanceof Type\Atomic\TNonEmptyArray
            && !($prior_input_type_part instanceof ObjectLike
                && ($prior_input_type_part->sealed
                    || $prior_input_type_part->previous_value_type
                    || \array_filter(
                        $prior_input_type_part->properties,
                        function ($prop_type) {
                            return !$prop_type->possibly_undefined;
                        }
                    )
                )
            )
        ) {
            if ($all_types_contain) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($all_types_contain) {
            $atomic_comparison_result->to_string_cast = false;

            return true;
        }

        return false;
    }

    /**
     * @param  TCallable|Type\Atomic\TFn   $input_type_part
     * @param  TCallable|Type\Atomic\TFn   $container_type_part
     * @param  bool   &$all_types_contain
     *
     * @return null|false
     */
    private static function compareCallable(
        Codebase $codebase,
        $input_type_part,
        $container_type_part,
        TypeComparisonResult $atomic_comparison_result,
        bool &$all_types_contain
    ) {
        if ($container_type_part->params !== null && $input_type_part->params === null) {
            $atomic_comparison_result->type_coerced = true;
            $atomic_comparison_result->type_coerced_from_mixed = true;

            return false;
        }

        if ($input_type_part->params !== null && $container_type_part->params !== null) {
            foreach ($input_type_part->params as $i => $input_param) {
                $container_param = null;

                if (isset($container_type_part->params[$i])) {
                    $container_param = $container_type_part->params[$i];
                } elseif ($container_type_part->params) {
                    $last_param = end($container_type_part->params);

                    if ($last_param->is_variadic) {
                        $container_param = $last_param;
                    }
                }

                if (!$container_param) {
                    if ($input_param->is_optional) {
                        break;
                    }

                    return false;
                }

                if ($container_param->type
                    && !$container_param->type->hasMixed()
                    && !self::isContainedBy(
                        $codebase,
                        $container_param->type,
                        $input_param->type ?: Type::getMixed(),
                        false,
                        false,
                        $atomic_comparison_result
                    )
                ) {
                    $all_types_contain = false;
                }
            }
        }

        if (isset($container_type_part->return_type)) {
            if (!isset($input_type_part->return_type)) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_mixed = true;

                $all_types_contain = false;
            } else {
                $input_return = $input_type_part->return_type;

                if ($input_return->isVoid() && $container_type_part->return_type->isNullable()) {
                    return;
                }

                if (!$container_type_part->return_type->isVoid()
                    && !self::isContainedBy(
                        $codebase,
                        $input_return,
                        $container_type_part->return_type,
                        false,
                        false,
                        $atomic_comparison_result
                    )
                ) {
                    $all_types_contain = false;
                }
            }
        }
    }

    /**
     * Takes two arrays of types and merges them
     *
     * @param  array<string, Type\Union>  $new_types
     * @param  array<string, Type\Union>  $existing_types
     *
     * @return array<string, Type\Union>
     */
    public static function combineKeyedTypes(array $new_types, array $existing_types)
    {
        $keys = array_merge(array_keys($new_types), array_keys($existing_types));
        $keys = array_unique($keys);

        $result_types = [];

        if (empty($new_types)) {
            return $existing_types;
        }

        if (empty($existing_types)) {
            return $new_types;
        }

        foreach ($keys as $key) {
            if (!isset($existing_types[$key])) {
                $result_types[$key] = $new_types[$key];
                continue;
            }

            if (!isset($new_types[$key])) {
                $result_types[$key] = $existing_types[$key];
                continue;
            }

            $existing_var_types = $existing_types[$key];
            $new_var_types = $new_types[$key];

            if ($new_var_types->getId() === $existing_var_types->getId()) {
                $result_types[$key] = $new_var_types;
            } else {
                $result_types[$key] = Type::combineUnionTypes($new_var_types, $existing_var_types);
            }
        }

        return $result_types;
    }

    /**
     * @return Type\Union
     */
    public static function simplifyUnionType(Codebase $codebase, Type\Union $union)
    {
        $union_type_count = count($union->getAtomicTypes());

        if ($union_type_count === 1 || ($union_type_count === 2 && $union->isNullable())) {
            return $union;
        }

        $from_docblock = $union->from_docblock;
        $ignore_nullable_issues = $union->ignore_nullable_issues;
        $ignore_falsable_issues = $union->ignore_falsable_issues;
        $possibly_undefined = $union->possibly_undefined;

        $unique_types = [];

        $inverse_contains = [];

        foreach ($union->getAtomicTypes() as $type_part) {
            $is_contained_by_other = false;

            // don't try to simplify intersection types
            if (($type_part instanceof TNamedObject
                    || $type_part instanceof TTemplateParam
                    || $type_part instanceof TIterable)
                && $type_part->extra_types
            ) {
                return $union;
            }

            foreach ($union->getAtomicTypes() as $container_type_part) {
                $string_container_part = $container_type_part->getId();
                $string_input_part = $type_part->getId();

                $atomic_comparison_result = new TypeComparisonResult();

                if ($type_part !== $container_type_part &&
                    !(
                        $container_type_part instanceof TInt
                        || $container_type_part instanceof TFloat
                        || $container_type_part instanceof TCallable
                        || ($container_type_part instanceof TString && $type_part instanceof TCallable)
                        || ($container_type_part instanceof TArray && $type_part instanceof TCallable)
                    ) &&
                    !isset($inverse_contains[$string_input_part][$string_container_part]) &&
                    TypeAnalyzer::isAtomicContainedBy(
                        $codebase,
                        $type_part,
                        $container_type_part,
                        false,
                        false,
                        $atomic_comparison_result
                    ) &&
                    !$atomic_comparison_result->to_string_cast
                ) {
                    $inverse_contains[$string_container_part][$string_input_part] = true;

                    $is_contained_by_other = true;
                    break;
                }
            }

            if (!$is_contained_by_other) {
                $unique_types[] = $type_part;
            }
        }

        if (!$unique_types) {
            throw new \UnexpectedValueException('There must be more than one unique type');
        }

        $unique_type = new Type\Union($unique_types);

        $unique_type->from_docblock = $from_docblock;
        $unique_type->ignore_nullable_issues = $ignore_nullable_issues;
        $unique_type->ignore_falsable_issues = $ignore_falsable_issues;
        $unique_type->possibly_undefined = $possibly_undefined;

        return $unique_type;
    }
}
