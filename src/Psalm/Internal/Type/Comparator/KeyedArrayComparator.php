<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObjectWithProperties;

use function array_keys;
use function is_string;

/**
 * @internal
 */
class KeyedArrayComparator
{
    /**
     * @param TKeyedArray|TObjectWithProperties $input_type_part
     * @param TKeyedArray|TObjectWithProperties $container_type_part
     */
    public static function isContainedBy(
        Codebase $codebase,
        Atomic $input_type_part,
        Atomic $container_type_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result
    ): bool {
        $container_sealed = $container_type_part instanceof TKeyedArray
            && $container_type_part->fallback_params === null;

        if ($container_sealed
            && $input_type_part instanceof TKeyedArray
            && $input_type_part->fallback_params !== null
        ) {
            return false;
        }

        if ($container_type_part instanceof TKeyedArray
            && $container_type_part->is_list
            && $input_type_part instanceof TKeyedArray
            && !$input_type_part->is_list
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }
            return false;
        }

        $all_types_contain = true;

        $input_properties = $input_type_part->properties;
        foreach ($container_type_part->properties as $key => $container_property_type) {
            if (!isset($input_properties[$key])) {
                if (!$container_property_type->possibly_undefined) {
                    $all_types_contain = false;
                }

                continue;
            }

            if ($input_properties[$key]->possibly_undefined
                && !$container_property_type->possibly_undefined
            ) {
                $all_types_contain = false;

                continue;
            }

            $input_property_type = $input_properties[$key];
            unset($input_properties[$key]);

            $property_type_comparison = new TypeComparisonResult();

            if (!$input_property_type->isNever()) {
                $is_input_containedby_container = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $input_property_type,
                    $container_property_type,
                    $input_property_type->ignore_nullable_issues,
                    $input_property_type->ignore_falsable_issues,
                    $property_type_comparison,
                    $allow_interface_equality,
                );
                if (!$is_input_containedby_container) {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced
                            = $property_type_comparison->type_coerced === true
                            && $atomic_comparison_result->type_coerced !== false;

                        if (!$property_type_comparison->type_coerced_from_scalar
                            && !$atomic_comparison_result->type_coerced) {
                            //if we didn't detect a coercion, we try to compare the other way around
                            $inverse_property_type_comparison = new TypeComparisonResult();
                            if (UnionTypeComparator::isContainedBy(
                                $codebase,
                                $container_property_type,
                                $input_property_type,
                                false,
                                false,
                                $inverse_property_type_comparison,
                                $allow_interface_equality,
                            )
                                || $inverse_property_type_comparison->type_coerced_from_scalar
                            ) {
                                $atomic_comparison_result->type_coerced = true;
                            }
                        }

                        $atomic_comparison_result->type_coerced_from_mixed
                            = $property_type_comparison->type_coerced_from_mixed === true
                            && $atomic_comparison_result->type_coerced_from_mixed !== false;

                        $atomic_comparison_result->type_coerced_from_as_mixed
                            = $property_type_comparison->type_coerced_from_as_mixed === true
                            && $atomic_comparison_result->type_coerced_from_as_mixed !== false;

                        $atomic_comparison_result->type_coerced_from_scalar
                            = $property_type_comparison->type_coerced_from_scalar === true
                            && $atomic_comparison_result->type_coerced_from_scalar !== false;

                        $atomic_comparison_result->scalar_type_match_found
                            = $property_type_comparison->scalar_type_match_found === true
                            && $atomic_comparison_result->scalar_type_match_found !== false;

                        if ($property_type_comparison->missing_shape_fields) {
                            $atomic_comparison_result->missing_shape_fields
                                = $property_type_comparison->missing_shape_fields;
                        }
                    }

                    $all_types_contain = false;
                } else {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->to_string_cast
                            = $atomic_comparison_result->to_string_cast === true
                            || $property_type_comparison->to_string_cast === true;
                    }
                }
            }
        }
        if ($container_sealed && $input_properties) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->missing_shape_fields = array_keys($input_properties);
            }
            return false;
        }

        // check remaining $input_properties against container's fallback_params
        if ($container_type_part instanceof TKeyedArray
            && $container_type_part->fallback_params !== null
        ) {
            [$key_type, $value_type] = $container_type_part->fallback_params;
            // treat fallback params as possibly undefined
            // otherwise comparison below would fail for list{0?:int} <=> list{...<int<0,max>, int>}
            // as the latter `int` is not marked as possibly_undefined
            $value_type = $value_type->setPossiblyUndefined(true);

            foreach ($input_properties as $key => $input_property_type) {
                $key_type_comparison = new TypeComparisonResult();
                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    is_string($key) ? Type::getString($key) : Type::getInt(false, $key),
                    $key_type,
                    false,
                    false,
                    $key_type_comparison,
                    $allow_interface_equality,
                )) {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced
                            = $key_type_comparison->type_coerced === true
                            && $atomic_comparison_result->type_coerced !== false;

                        $atomic_comparison_result->type_coerced_from_mixed
                            = $key_type_comparison->type_coerced_from_mixed === true
                            && $atomic_comparison_result->type_coerced_from_mixed !== false;

                        $atomic_comparison_result->type_coerced_from_as_mixed
                            = $key_type_comparison->type_coerced_from_as_mixed === true
                            && $atomic_comparison_result->type_coerced_from_as_mixed !== false;

                        $atomic_comparison_result->type_coerced_from_scalar
                            = $key_type_comparison->type_coerced_from_scalar === true
                            && $atomic_comparison_result->type_coerced_from_scalar !== false;

                        $atomic_comparison_result->scalar_type_match_found
                            = $key_type_comparison->scalar_type_match_found === true
                            && $atomic_comparison_result->scalar_type_match_found !== false;
                    }
                    $all_types_contain = false;
                }

                $property_type_comparison = new TypeComparisonResult();
                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $input_property_type,
                    $value_type,
                    false,
                    false,
                    $property_type_comparison,
                    $allow_interface_equality,
                )) {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced
                            = $property_type_comparison->type_coerced === true
                            && $atomic_comparison_result->type_coerced !== false;

                        $atomic_comparison_result->type_coerced_from_mixed
                            = $property_type_comparison->type_coerced_from_mixed === true
                            && $atomic_comparison_result->type_coerced_from_mixed !== false;

                        $atomic_comparison_result->type_coerced_from_as_mixed
                            = $property_type_comparison->type_coerced_from_as_mixed === true
                            && $atomic_comparison_result->type_coerced_from_as_mixed !== false;

                        $atomic_comparison_result->type_coerced_from_scalar
                            = $property_type_comparison->type_coerced_from_scalar === true
                            && $atomic_comparison_result->type_coerced_from_scalar !== false;

                        $atomic_comparison_result->scalar_type_match_found
                            = $property_type_comparison->scalar_type_match_found === true
                            && $atomic_comparison_result->scalar_type_match_found !== false;
                    }
                    $all_types_contain = false;
                }
            }
        }

        // finally, check input type fallback params against container type fallback params
        if ($input_type_part instanceof TKeyedArray
            && $container_type_part instanceof TKeyedArray
            && $input_type_part->fallback_params !== null
            && $container_type_part->fallback_params !== null
        ) {
            foreach ($input_type_part->fallback_params as $i => $input_param) {
                $container_param = $container_type_part->fallback_params[$i];
                $param_comparison = new TypeComparisonResult();
                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $input_param,
                    $container_param,
                    false,
                    false,
                    $param_comparison,
                    $allow_interface_equality,
                )) {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced
                            = $param_comparison->type_coerced === true
                            && $atomic_comparison_result->type_coerced !== false;

                        $atomic_comparison_result->type_coerced_from_mixed
                            = $param_comparison->type_coerced_from_mixed === true
                            && $atomic_comparison_result->type_coerced_from_mixed !== false;

                        $atomic_comparison_result->type_coerced_from_as_mixed
                            = $param_comparison->type_coerced_from_as_mixed === true
                            && $atomic_comparison_result->type_coerced_from_as_mixed !== false;

                        $atomic_comparison_result->type_coerced_from_scalar
                            = $param_comparison->type_coerced_from_scalar === true
                            && $atomic_comparison_result->type_coerced_from_scalar !== false;

                        $atomic_comparison_result->scalar_type_match_found
                            = $param_comparison->scalar_type_match_found === true
                            && $atomic_comparison_result->scalar_type_match_found !== false;
                    }
                    $all_types_contain = false;
                }
            }
        }

        return $all_types_contain;
    }

    public static function isContainedByObjectWithProperties(
        Codebase $codebase,
        TNamedObject $input_type_part,
        TObjectWithProperties $container_type_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result
    ): bool {
        $all_types_contain = true;

        foreach ($container_type_part->properties as $property_name => $container_property_type) {
            if (!is_string($property_name)) {
                continue;
            }

            if (!$codebase->classlikes->classOrInterfaceExists($input_type_part->value)) {
                $all_types_contain = false;

                continue;
            }

            if (!$codebase->properties->propertyExists(
                $input_type_part->value . '::$' . $property_name,
                true,
            )) {
                $all_types_contain = false;

                continue;
            }

            $property_declaring_class = (string) $codebase->properties->getDeclaringClassForProperty(
                $input_type_part . '::$' . $property_name,
                true,
            );

            $class_storage = $codebase->classlike_storage_provider->get($property_declaring_class);

            $input_property_storage = $class_storage->properties[$property_name];

            $input_property_type = $input_property_storage->type ?: Type::getMixed();

            $property_type_comparison = new TypeComparisonResult();

            if (!$input_property_type->isNever()
                && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $input_property_type,
                    $container_property_type,
                    false,
                    false,
                    $property_type_comparison,
                    $allow_interface_equality,
                )
                && !$property_type_comparison->type_coerced_from_scalar
            ) {
                $inverse_property_type_comparison = new TypeComparisonResult();

                if (UnionTypeComparator::isContainedBy(
                    $codebase,
                    $container_property_type,
                    $input_property_type,
                    false,
                    false,
                    $inverse_property_type_comparison,
                    $allow_interface_equality,
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

        return $all_types_contain;
    }
}
