<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Internal\MethodIdentifier;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableInterface;
use Psalm\Type\Atomic\TCallableKeyedArray;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\Atomic\TValueOf;
use Psalm\Type\Union;

use function array_merge;
use function array_values;
use function assert;
use function count;
use function strtolower;

/**
 * @internal
 */
final class AtomicTypeComparator
{
    /**
     * Does the input param atomic type match the given param atomic type
     */
    public static function isContainedBy(
        Codebase $codebase,
        Atomic $input_type_part,
        Atomic $container_type_part,
        bool $allow_interface_equality = false,
        bool $allow_float_int_equality = true,
        ?TypeComparisonResult $atomic_comparison_result = null,
    ): bool {


        if (($container_type_part instanceof TTemplateParam
                || ($container_type_part instanceof TNamedObject
                    && $container_type_part->extra_types))
            && ($input_type_part instanceof TTemplateParam
                || ($input_type_part instanceof TNamedObject
                    && $input_type_part->extra_types))
        ) {
            return ObjectComparator::isShallowlyContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result,
            );
        }

        if ($input_type_part instanceof TValueOf) {
            if ($container_type_part instanceof TValueOf) {
                return UnionTypeComparator::isContainedBy(
                    $codebase,
                    $input_type_part->type,
                    $container_type_part->type,
                    false,
                    false,
                    null,
                    false,
                    false,
                );
            } elseif ($container_type_part instanceof Scalar) {
                return UnionTypeComparator::isContainedBy(
                    $codebase,
                    TValueOf::getValueType($input_type_part->type, $codebase) ?? $input_type_part->type,
                    new Union([$container_type_part]),
                    false,
                    false,
                    null,
                    false,
                    false,
                );
            }
        }

        if ($container_type_part instanceof TMixed
            || ($container_type_part instanceof TTemplateParam
                && $container_type_part->as->isMixed()
                && !$container_type_part->extra_types
                && $input_type_part instanceof TMixed)
        ) {
            if ($input_type_part::class === TMixed::class
                && (
                    $container_type_part::class === TEmptyMixed::class
                    || $container_type_part::class === TNonEmptyMixed::class
                )
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                    $atomic_comparison_result->type_coerced_from_mixed = true;
                }

                return false;
            }

            return true;
        }

        if ($input_type_part instanceof TNever) {
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

        if ($input_type_part instanceof Scalar && $container_type_part instanceof Scalar) {
            return ScalarTypeComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $allow_float_int_equality,
                $atomic_comparison_result,
            );
        }

        if ($input_type_part instanceof TCallableKeyedArray
            && $container_type_part instanceof TArray
        ) {
            return ArrayTypeComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result,
            );
        }

        if (($container_type_part instanceof TCallable
            && $input_type_part instanceof TCallableInterface
            )
            || ($container_type_part instanceof TClosure
                && $input_type_part instanceof TClosure)
        ) {
            return CallableTypeComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result,
            );
        }

        if ($container_type_part instanceof TClosure) {
            if ($input_type_part instanceof TCallable) {
                if (CallableTypeComparator::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $atomic_comparison_result,
                ) === false
                ) {
                    return false;
                }

                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }
            }

            return false;
        }

        if ($container_type_part instanceof TCallable && $input_type_part instanceof TClosure) {
            return CallableTypeComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result,
            );
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

        if ($input_type_part instanceof TCallableObject &&
            $container_type_part instanceof TObject
        ) {
            return true;
        }

        if ($container_type_part instanceof TObjectWithProperties
            && $container_type_part->is_stringable_object_only
        ) {
            if (($input_type_part instanceof TObjectWithProperties
                    && $input_type_part->is_stringable_object_only)
                || ($input_type_part instanceof TNamedObject
                    && $codebase->methodExists(new MethodIdentifier($input_type_part->value, '__tostring')))
            ) {
                return true;
            }
            return false;
        }

        if ($container_type_part instanceof TNamedObject
            && $container_type_part->value === 'Stringable'
            && $codebase->analysis_php_version_id >= 8_00_00
            && $input_type_part instanceof TObjectWithProperties
            && $input_type_part->is_stringable_object_only
        ) {
            return true;
        }

        if (($container_type_part instanceof TKeyedArray
                && $input_type_part instanceof TKeyedArray)
            || ($container_type_part instanceof TObjectWithProperties
                && $input_type_part instanceof TObjectWithProperties)
        ) {
            return KeyedArrayComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result,
            );
        }

        if ($container_type_part instanceof TObjectWithProperties
            && $input_type_part instanceof TObject
            && !$input_type_part instanceof TObjectWithProperties
            && !$input_type_part instanceof TCallableObject
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }
            return false;
        }

        if (($input_type_part instanceof TArray
                || $input_type_part instanceof TKeyedArray
                || $input_type_part instanceof TClassStringMap)
            && ($container_type_part instanceof TArray
                || $container_type_part instanceof TKeyedArray
                || $container_type_part instanceof TClassStringMap)
        ) {
            return ArrayTypeComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result,
            );
        }

        if ($container_type_part::class === TNamedObject::class
            && $input_type_part instanceof TEnumCase
            && $input_type_part->value === $container_type_part->value
        ) {
            return true;
        }

        if ($input_type_part::class === TNamedObject::class
            && $container_type_part instanceof TEnumCase
            && $input_type_part->value === $container_type_part->value
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return true;
        }

        if ($container_type_part instanceof TEnumCase
            && $input_type_part instanceof TEnumCase
        ) {
            return $container_type_part->value === $input_type_part->value
                && $container_type_part->case_name === $input_type_part->case_name;
        }

        if (($input_type_part instanceof TNamedObject
                || ($input_type_part instanceof TTemplateParam
                    && $input_type_part->as->hasObjectType())
                || $input_type_part instanceof TIterable)
            && ($container_type_part instanceof TNamedObject
                || ($container_type_part instanceof TTemplateParam
                    && $container_type_part->isObjectType())
                || $container_type_part instanceof TIterable)
            && ObjectComparator::isShallowlyContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result,
            )
        ) {
            if ($container_type_part instanceof TGenericObject || $container_type_part instanceof TIterable) {
                return GenericTypeComparator::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $atomic_comparison_result,
                );
            }

            if ($container_type_part instanceof TNamedObject
                && $input_type_part instanceof TNamedObject
                && $container_type_part->is_static
                && !$input_type_part->is_static
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            if ($atomic_comparison_result) {
                $atomic_comparison_result->to_string_cast = false;
            }

            return true;
        }

        if ($input_type_part::class === TObject::class
            && $container_type_part::class === TObject::class
        ) {
            return true;
        }

        if ($container_type_part instanceof TTemplateKeyOf) {
            if (!$input_type_part instanceof TTemplateKeyOf) {
                return false;
            }

            return UnionTypeComparator::isContainedBy(
                $codebase,
                $input_type_part->as,
                $container_type_part->as,
            );
        }

        if ($input_type_part instanceof TTemplateKeyOf) {
            $array_key_type = TKeyOf::getArrayKeyType($input_type_part->as);
            if ($array_key_type === null) {
                return false;
            }

            foreach ($array_key_type->getAtomicTypes() as $array_key_atomic) {
                if (!self::isContainedBy(
                    $codebase,
                    $array_key_atomic,
                    $container_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result,
                )) {
                    return false;
                }
            }

            return true;
        }

        if ($container_type_part instanceof TTemplateValueOf) {
            if (!$input_type_part instanceof TTemplateValueOf) {
                return false;
            }

            return UnionTypeComparator::isContainedBy(
                $codebase,
                $input_type_part->as,
                $container_type_part->as,
            );
        }

        if ($input_type_part instanceof TTemplateValueOf) {
            $array_value_type = TValueOf::getValueType($input_type_part->as, $codebase);
            if ($array_value_type === null) {
                return false;
            }

            foreach ($array_value_type->getAtomicTypes() as $array_value_atomic) {
                if (!self::isContainedBy(
                    $codebase,
                    $array_value_atomic,
                    $container_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result,
                )) {
                    return false;
                }
            }

            return true;
        }

        if ($container_type_part instanceof TTemplateParam && $input_type_part instanceof TTemplateParam) {
            return UnionTypeComparator::isContainedBy(
                $codebase,
                $input_type_part->as,
                $container_type_part->as,
                false,
                false,
                $atomic_comparison_result,
                $allow_interface_equality,
            );
        }

        if ($container_type_part instanceof TTemplateParam) {
            foreach ($container_type_part->as->getAtomicTypes() as $container_as_type_part) {
                if (self::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_as_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result,
                )) {
                    if ($allow_interface_equality) {
                        return true;
                    }
                }
            }

            return false;
        }

        if ($container_type_part instanceof TConditional) {
            $atomic_types = array_merge(
                array_values($container_type_part->if_type->getAtomicTypes()),
                array_values($container_type_part->else_type->getAtomicTypes()),
            );

            foreach ($atomic_types as $container_as_type_part) {
                if (self::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_as_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result,
                )) {
                    return true;
                }
            }

            return false;
        }

        if ($input_type_part instanceof TTemplateParam) {
            if ($input_type_part->extra_types) {
                foreach ($input_type_part->extra_types as $extra_type) {
                    if (self::isContainedBy(
                        $codebase,
                        $extra_type,
                        $container_type_part,
                        $allow_interface_equality,
                        $allow_float_int_equality,
                        $atomic_comparison_result,
                    )) {
                        return true;
                    }
                }
            }

            foreach ($input_type_part->as->getAtomicTypes() as $input_as_type_part) {
                if (self::isContainedBy(
                    $codebase,
                    $input_as_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result,
                )) {
                    return true;
                }
            }

            return false;
        }

        if ($input_type_part instanceof TConditional) {
            $input_atomic_types = array_merge(
                array_values($input_type_part->if_type->getAtomicTypes()),
                array_values($input_type_part->else_type->getAtomicTypes()),
            );

            foreach ($input_atomic_types as $input_as_type_part) {
                if (self::isContainedBy(
                    $codebase,
                    $input_as_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result,
                )) {
                    return true;
                }
            }

            return false;
        }

        if ($input_type_part instanceof TNamedObject
            && $input_type_part->value === 'static'
            && $container_type_part instanceof TNamedObject
            && strtolower($container_type_part->value) === 'self'
        ) {
            return true;
        }

        if ($container_type_part instanceof TIterable) {
            if ($input_type_part instanceof TArray
                || $input_type_part instanceof TKeyedArray
            ) {
                if ($input_type_part instanceof TKeyedArray) {
                    $input_type_part = $input_type_part->getGenericArrayType();
                }

                $all_types_contain = true;

                foreach ($input_type_part->type_params as $i => $input_param) {
                    $container_param_offset = $i - (2 - count($container_type_part->type_params));

                    $container_param = $container_type_part->type_params[$container_param_offset];

                    if ($i === 0
                        && $input_param->hasMixed()
                        && $container_param->hasString()
                        && $container_param->hasInt()
                    ) {
                        continue;
                    }

                    $array_comparison_result = new TypeComparisonResult();

                    if (!$input_param->isNever()) {
                        if (!UnionTypeComparator::isContainedBy(
                            $codebase,
                            $input_param,
                            $container_param,
                            $input_param->ignore_nullable_issues,
                            $input_param->ignore_falsable_issues,
                            $array_comparison_result,
                            $allow_interface_equality,
                        )
                            && !$array_comparison_result->type_coerced_from_scalar
                        ) {
                            if ($atomic_comparison_result && $array_comparison_result->type_coerced_from_mixed) {
                                $atomic_comparison_result->type_coerced_from_mixed = true;
                            }
                            $all_types_contain = false;
                        } else {
                            if ($atomic_comparison_result) {
                                $atomic_comparison_result->to_string_cast
                                    = $atomic_comparison_result->to_string_cast === true
                                        || $array_comparison_result->to_string_cast === true;
                            }
                        }
                    }
                }
                return $all_types_contain;
            }

            if ($input_type_part->hasTraversableInterface($codebase)) {
                return true;
            }
        }

        if ($container_type_part instanceof TString || $container_type_part instanceof TScalar) {
            if ($input_type_part instanceof TNamedObject) {
                // check whether the object has a __toString method
                if ($codebase->classOrInterfaceExists($input_type_part->value)) {
                    if ($codebase->analysis_php_version_id >= 8_00_00
                        && ($input_type_part->value === 'Stringable'
                            || ($codebase->classlikes->classExists($input_type_part->value)
                                && $codebase->classlikes->classImplements($input_type_part->value, 'Stringable'))
                            || $codebase->classlikes->interfaceExtends($input_type_part->value, 'Stringable'))
                    ) {
                        if ($atomic_comparison_result) {
                            $atomic_comparison_result->to_string_cast = true;
                        }

                        return true;
                    }

                    if ($codebase->methods->methodExists(
                        new MethodIdentifier(
                            $input_type_part->value,
                            '__tostring',
                        ),
                    )) {
                        if ($atomic_comparison_result) {
                            $atomic_comparison_result->to_string_cast = true;
                        }

                        return true;
                    }
                }

                // PHP 5.6 doesn't support this natively, so this introduces a bug *just* when checking PHP 5.6 code
                if ($input_type_part->value === 'ReflectionType') {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->to_string_cast = true;
                    }

                    return true;
                }
            } elseif ($input_type_part instanceof TObjectWithProperties
                && isset($input_type_part->methods['__tostring'])
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->to_string_cast = true;
                }

                return true;
            }
        }

        if ($container_type_part instanceof TCallable &&
            (
                $input_type_part instanceof TLiteralString
                || $input_type_part instanceof TCallableString
                || $input_type_part instanceof TArray
                || $input_type_part instanceof TKeyedArray
                || (
                    $input_type_part instanceof TNamedObject &&
                    $codebase->classOrInterfaceExists($input_type_part->value) &&
                    $codebase->methodExists($input_type_part->value . '::__invoke')
                )
            )
        ) {
            return CallableTypeComparator::isNotExplicitlyCallableTypeCallable(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result,
            );
        }

        if ($container_type_part instanceof TObject
            && $input_type_part instanceof TNamedObject
        ) {
            if ($container_type_part instanceof TObjectWithProperties
                && $input_type_part->value !== 'stdClass'
            ) {
                return KeyedArrayComparator::isContainedByObjectWithProperties(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $atomic_comparison_result,
                );
            }

            return true;
        }

        if ($container_type_part instanceof TNamedObject
            && $input_type_part instanceof TNamedObject
            && $container_type_part->is_static
            && !$input_type_part->is_static
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($input_type_part instanceof TObject && $container_type_part instanceof TNamedObject) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TNamedObject
            && $input_type_part instanceof TNamedObject
            && $codebase->classOrInterfaceOrEnumExists($input_type_part->value)
            && (
                (
                    $codebase->classExists($container_type_part->value)
                    && $codebase->classExtendsOrImplements(
                        $container_type_part->value,
                        $input_type_part->value,
                    )
                )
                ||
                (
                    $codebase->interfaceExists($container_type_part->value)
                    && $codebase->interfaceExtends(
                        $container_type_part->value,
                        $input_type_part->value,
                    )
                )
            )
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        return $input_type_part->getKey() === $container_type_part->getKey();
    }

    /**
     * @psalm-assert-if-true TKeyedArray $array
     */
    public static function isLegacyTListLike(Atomic $array): bool
    {
        return $array instanceof TKeyedArray
            && $array->is_list
            && $array->fallback_params
            && count($array->properties) === 1
            && $array->properties[0]->possibly_undefined
            && $array->properties[0]->equals($array->fallback_params[1], true, true, false)
        ;
    }
    /**
     * @psalm-assert-if-true TKeyedArray $array
     */
    public static function isLegacyTNonEmptyListLike(Atomic $array): bool
    {
        return $array instanceof TKeyedArray
            && $array->is_list
            && $array->fallback_params
            && count($array->properties) === 1
            && !$array->properties[0]->possibly_undefined
            && $array->properties[0]->equals($array->fallback_params[1])
        ;
    }
    /**
     * Does the input param atomic type match the given param atomic type
     */
    public static function canBeIdentical(
        Codebase $codebase,
        Atomic $type1_part,
        Atomic $type2_part,
        bool $allow_interface_equality = true,
    ): bool {


        if ((self::isLegacyTListLike($type1_part)
                && self::isLegacyTNonEmptyListLike($type2_part))
            || (self::isLegacyTListLike($type2_part)
                && self::isLegacyTNonEmptyListLike($type1_part))
        ) {
            assert($type1_part->fallback_params !== null);
            assert($type2_part->fallback_params !== null);
            return UnionTypeComparator::canExpressionTypesBeIdentical(
                $codebase,
                $type1_part->fallback_params[1],
                $type2_part->fallback_params[1],
            );
        }

        if (($type1_part::class === TArray::class
                && $type2_part instanceof TNonEmptyArray)
            || ($type2_part::class === TArray::class
                && $type1_part instanceof TNonEmptyArray)
        ) {
            return UnionTypeComparator::canExpressionTypesBeIdentical(
                $codebase,
                $type1_part->type_params[0],
                $type2_part->type_params[0],
            )
            && UnionTypeComparator::canExpressionTypesBeIdentical(
                $codebase,
                $type1_part->type_params[1],
                $type2_part->type_params[1],
            );
        }

        $first_comparison_result = new TypeComparisonResult();
        $second_comparison_result = new TypeComparisonResult();

        return (self::isContainedBy(
            $codebase,
            $type1_part,
            $type2_part,
            $allow_interface_equality,
            false,
            $first_comparison_result,
        )
            && !$first_comparison_result->to_string_cast
        ) || (self::isContainedBy(
            $codebase,
            $type2_part,
            $type1_part,
            $allow_interface_equality,
            false,
            $second_comparison_result,
        )
            && !$second_comparison_result->to_string_cast
        ) || ($first_comparison_result->type_coerced
            && $second_comparison_result->type_coerced
        );
    }
}
