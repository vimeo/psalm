<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Storage\Capabilities;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIntersection;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
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
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Atomic\TValueOf;
use Psalm\Type\Union;

use function array_all;
use function array_any;
use function array_diff_key;
use function array_merge;
use function array_values;
use function assert;
use function count;
use function is_int;
use function min;
use function reset;
use function strtolower;

/**
 * @internal
 * @psalm-import-type CapabilitySet from Capabilities
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

        if ($container_type_part instanceof TIntersection) {
            // the input has all the types of the intersection
            $input_type = new Union([$input_type_part]);

            return array_all(
                $container_type_part->types,
                static fn(Union $type): bool => UnionTypeComparator::isContainedBy(
                    $codebase,
                    $input_type,
                    $type,
                    false,
                    false,
                    null,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                ),
            );
        }

        if ($input_type_part instanceof TIntersection) {
            // any type of the intersection contains all of its values
            $container_type = new Union([$container_type_part]);

            return array_any(
                $input_type_part->types,
                static fn(Union $type): bool => UnionTypeComparator::isContainedBy(
                    $codebase,
                    $type,
                    $container_type,
                    false,
                    false,
                    null,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                ),
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

        if ($input_type_part instanceof TCapabilities || $container_type_part instanceof TCapabilities) {
            // a purity template fits wherever its bound does
            if ($input_type_part instanceof TTemplateParam) {
                foreach ($input_type_part->as->getAtomicTypes() as $as_type_part) {
                    if (!self::isContainedBy($codebase, $as_type_part, $container_type_part)) {
                        return false;
                    }
                }

                return true;
            }

            // a conditional purity argument (`[$param is array ? pure : P]`) fits if both branches do
            if ($input_type_part instanceof TConditional) {
                $branch_type_parts = array_merge(
                    array_values($input_type_part->if_type->getAtomicTypes()),
                    array_values($input_type_part->else_type->getAtomicTypes()),
                );

                foreach ($branch_type_parts as $branch_type_part) {
                    if (!self::isContainedBy($codebase, $branch_type_part, $container_type_part)) {
                        return false;
                    }
                }

                return true;
            }

            // a capability set fits a purity template if every value of the template allows it,
            // i.e. its lower bound does: `pure` fits `Closure[P]` for any P
            if ($input_type_part instanceof TCapabilities
                && $container_type_part instanceof TTemplateParam
                && Capabilities::isPurityType($container_type_part->as)
            ) {
                return Capabilities::allowsAnyOf(
                    self::getPurityTemplateLowerBound($codebase, $container_type_part),
                    $input_type_part->capabilities,
                );
            }

            // a capability set is a subtype of every superset: pure fits anywhere, impure only in impure
            return $input_type_part instanceof TCapabilities
                && $container_type_part instanceof TCapabilities
                && Capabilities::allowsAnyOf($container_type_part->capabilities, $input_type_part->capabilities);
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

        if ($input_type_part instanceof TKeyedArray && $input_type_part->is_callable
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
            && $input_type_part->isCallableType()
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
                    $to_string_method_id = new MethodIdentifier(
                        $input_type_part->value,
                        '__tostring',
                    );

                    if ($container_type_part instanceof TString
                        && $container_type_part::class !== TString::class
                        && $codebase->methodExists($to_string_method_id)
                    ) {
                        $self_class = $input_type_part->value;
                        $return_type = $codebase->getMethodReturnType($to_string_method_id, $self_class);

                        if ($return_type !== null && $input_type_part instanceof TGenericObject) {
                            $class_template_params = ClassTemplateParamCollector::collect(
                                $codebase,
                                $codebase->methods->getClassLikeStorageForMethod($to_string_method_id),
                                $codebase->classlike_storage_provider->get($input_type_part->value),
                                $to_string_method_id->method_name,
                                $input_type_part,
                            );

                            if ($class_template_params !== null) {
                                $return_type = TemplateInferredTypeReplacer::replace(
                                    $return_type,
                                    new TemplateResult([], $class_template_params),
                                    $codebase,
                                );
                            }
                        }

                        $return_type_comparison = new TypeComparisonResult();
                        if ($return_type === null
                            || !UnionTypeComparator::isContainedBy(
                                $codebase,
                                $return_type,
                                new Union([$container_type_part]),
                                false,
                                false,
                                $return_type_comparison,
                            )
                        ) {
                            if ($atomic_comparison_result) {
                                $atomic_comparison_result->type_coerced = $return_type === null
                                    || $return_type_comparison->type_coerced;
                                $atomic_comparison_result->to_string_cast = true;
                            }

                            return false;
                        }
                    }

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

                    if ($codebase->methodExists($to_string_method_id)) {
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
     * @psalm-pure
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
     * @psalm-pure
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
     * The object half of an iterable is a Traversable, unless the other type is a class that cannot be one
     * or has template params of its own, which the generic comparison handles. Whether an iterable may be
     * that object is not a question of its purity.
     *
     * @psalm-capabilities read-props|write-props
     * @internal
     */
    public static function getTraversableHalf(
        Codebase $codebase,
        TIterable $iterable,
        TNamedObject $other,
        bool $keep_purity = true,
    ): ?TGenericObject {
        if ($other instanceof TGenericObject
            || $iterable->extra_types
            || (($codebase->classExists($other->value) || $codebase->classlikes->enumExists($other->value))
                && !$codebase->classImplements($other->value, 'Traversable')
                && ($codebase->classlike_storage_provider->get($other->value)->final
                    || $codebase->classlike_storage_provider->get($other->value)->is_enum))
        ) {
            return null;
        }

        // a generic class takes the key and the value of the iterable for the templates that stand for its own
        if ($codebase->classExists($other->value)) {
            $other_storage = $codebase->classlike_storage_provider->get($other->value);
            $extended_params = $other_storage->template_extended_params['Traversable'] ?? null;

            if ($other_storage->template_types !== null
                && $extended_params !== null
                && isset($extended_params['TKey'], $extended_params['TValue'])
                && isset($iterable->type_params[1])
            ) {
                $iterable_params = [
                    'TKey' => $iterable->type_params[0],
                    'TValue' => $iterable->type_params[1],
                ];
                $class_params = [];
                $found = [];

                foreach ($other_storage->template_types as $template_name => $bounds) {
                    $class_param = reset($bounds);

                    foreach ($iterable_params as $traversable_template => $iterable_param) {
                        $standing_for = $extended_params[$traversable_template];

                        if ($standing_for->isSingle()
                            && $standing_for->getSingleAtomic() instanceof TTemplateParam
                            && $standing_for->getSingleAtomic()->param_name === $template_name
                        ) {
                            $class_param = $iterable_param;
                            $found[$traversable_template] = true;
                        }
                    }

                    // only the purity template that stands for the one of Traversable is the iterable's
                    $purity_standing_for = $extended_params['TPurity'] ?? null;

                    if ($purity_standing_for !== null
                        && $purity_standing_for->isSingle()
                        && $purity_standing_for->getSingleAtomic() instanceof TTemplateParam
                        && $purity_standing_for->getSingleAtomic()->param_name === $template_name
                    ) {
                        $class_param = $iterable->purity;
                    }

                    $class_params[] = $class_param;
                }

                if ($class_params !== [] && count($found) === 2) {
                    return new TGenericObject($other->value, $class_params);
                }
            }
        }

        // the purity of the iterable is the last template of Traversable
        return new TGenericObject(
            'Traversable',
            $keep_purity ? [...$iterable->type_params, $iterable->purity] : $iterable->type_params,
        );
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
        if ($type1_part instanceof TTypeVariable || $type2_part instanceof TTypeVariable) {
            // A type variable can be identical to anything; its constraints
            // are reconciled at the end of the surrounding function-like.
            return true;
        }

        if ($type1_part instanceof TIterable && $type2_part instanceof TNamedObject) {
            $type1_part = self::getTraversableHalf($codebase, $type1_part, $type2_part, false) ?? $type1_part;
        } elseif ($type2_part instanceof TIterable && $type1_part instanceof TNamedObject) {
            $type2_part = self::getTraversableHalf($codebase, $type2_part, $type1_part, false) ?? $type2_part;
        }

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

        // arrays of the same keys can be identical when every pair of their items can, as list{int|string} and
        // list{string|bool}: neither contains the other
        if ($type1_part instanceof TKeyedArray
            && $type2_part instanceof TKeyedArray
            && $type1_part->fallback_params === null
            && $type2_part->fallback_params === null
            && count($type1_part->properties) === count($type2_part->properties)
            && array_diff_key($type1_part->properties, $type2_part->properties) === []
        ) {
            $only_defined_items = true;

            foreach ($type1_part->properties as $key => $property_1) {
                $property_2 = $type2_part->properties[$key];

                if ($property_1->possibly_undefined || $property_2->possibly_undefined) {
                    $only_defined_items = false;
                    break;
                }

                if (!UnionTypeComparator::canExpressionTypesBeIdentical($codebase, $property_1, $property_2)) {
                    return false;
                }
            }

            if ($only_defined_items) {
                return true;
            }
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
        ) || ($type1_part instanceof TKeyedArray
            && $type2_part instanceof TKeyedArray
            && self::canKeyedArraysBeIdentical($codebase, $type1_part, $type2_part)
        );
    }

    /**
     * Whether some array could have the shape of both: each key that one of them requires is allowed by the other
     * (as a key it declares, or by its fallback), and the values of such a key can be identical.
     */
    private static function canKeyedArraysBeIdentical(Codebase $codebase, TKeyedArray $first, TKeyedArray $second): bool
    {
        return self::keyedArrayRequirementsFit($codebase, $first, $second)
            && self::keyedArrayRequirementsFit($codebase, $second, $first)
            && self::listCanHaveTheRequiredKeysOf($codebase, $first, $second)
            && self::listCanHaveTheRequiredKeysOf($codebase, $second, $first);
    }

    /**
     * A list has every key below the highest it has, so it can only be identical to a shape that allows all the
     * keys below the highest int key the shape requires (`array{0: int, 2: int}` is not a list).
     */
    private static function listCanHaveTheRequiredKeysOf(
        Codebase $codebase,
        TKeyedArray $list,
        TKeyedArray $other,
    ): bool {
        if (!$list->is_list) {
            return true;
        }

        $highest_required_key = null;

        foreach ($other->properties as $key => $property) {
            if (!$property->possibly_undefined && is_int($key) && ($highest_required_key ?? $key) <= $key) {
                $highest_required_key = $key;
            }
        }

        // far-fetched keys are not worth checking one by one
        for ($key = 0; $key < min($highest_required_key ?? 0, 256); $key++) {
            // the list has the item, and so must the other one: of a type both can have
            $list_item = $list->properties[$key] ?? $list->fallback_params[1] ?? null;

            if (isset($other->properties[$key])) {
                $other_item = $other->properties[$key];
            } elseif ($other->fallback_params !== null
                && UnionTypeComparator::canExpressionTypesBeIdentical(
                    $codebase,
                    new Union([new TLiteralInt($key)]),
                    $other->fallback_params[0],
                )
            ) {
                $other_item = $other->fallback_params[1];
            } else {
                return false;
            }

            if ($list_item !== null
                && !UnionTypeComparator::canExpressionTypesBeIdentical($codebase, $list_item, $other_item)
            ) {
                return false;
            }
        }

        return true;
    }

    private static function keyedArrayRequirementsFit(
        Codebase $codebase,
        TKeyedArray $required,
        TKeyedArray $other,
    ): bool {
        foreach ($required->properties as $key => $property) {
            if ($property->possibly_undefined) {
                continue;
            }

            if (isset($other->properties[$key])) {
                if (!UnionTypeComparator::canExpressionTypesBeIdentical(
                    $codebase,
                    $property,
                    $other->properties[$key],
                )) {
                    return false;
                }

                continue;
            }

            if ($other->fallback_params === null) {
                return false;
            }

            $key_type = new Union([is_int($key) ? new TLiteralInt($key) : Type::getAtomicStringFromLiteral($key)]);

            [$fallback_key_type, $fallback_value_type] = $other->fallback_params;

            if (!UnionTypeComparator::canExpressionTypesBeIdentical($codebase, $key_type, $fallback_key_type)
                || !UnionTypeComparator::canExpressionTypesBeIdentical($codebase, $property, $fallback_value_type)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * The capabilities every value of a purity template has: its lower bound
     * (`@psalm-purity-template write-props <= C`), which only a class purity template can declare.
     *
     * @return CapabilitySet
     * @psalm-capabilities read-props
     */
    private static function getPurityTemplateLowerBound(Codebase $codebase, TTemplateParam $template): int
    {
        if (!$codebase->classlike_storage_provider->has($template->defining_class)) {
            return Capabilities::NONE;
        }

        return $codebase->classlike_storage_provider->get($template->defining_class)
            ->template_lower_bounds[$template->param_name] ?? Capabilities::NONE;
    }
}
