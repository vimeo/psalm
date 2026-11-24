<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Union;

use function array_keys;
use function is_string;

/**
 * @internal
 */
final class KeyedArrayComparator
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
        ?TypeComparisonResult $atomic_comparison_result,
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

                    // a sealed array never has a key it doesn't declare, so no value of the input fits
                    if ($atomic_comparison_result
                        && $input_type_part instanceof TKeyedArray
                        && $input_type_part->fallback_params === null
                    ) {
                        $atomic_comparison_result->mergeFailedPart(new TypeComparisonResult());
                    }
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

            if (!self::isPartContainedBy(
                $codebase,
                $input_property_type,
                $container_property_type,
                $allow_interface_equality,
                $atomic_comparison_result,
                true,
            )) {
                $all_types_contain = false;
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
                if (!self::isPartContainedBy(
                    $codebase,
                    is_string($key) ? Type::getString($key) : Type::getInt(false, $key),
                    $key_type,
                    $allow_interface_equality,
                    $atomic_comparison_result,
                )) {
                    $all_types_contain = false;
                }

                if (!self::isPartContainedBy(
                    $codebase,
                    $input_property_type,
                    $value_type,
                    $allow_interface_equality,
                    $atomic_comparison_result,
                )) {
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
                if (!self::isPartContainedBy(
                    $codebase,
                    $input_param,
                    $container_type_part->fallback_params[$i],
                    $allow_interface_equality,
                    $atomic_comparison_result,
                )) {
                    $all_types_contain = false;
                }
            }
        }

        return $all_types_contain;
    }

    /**
     * Compares a key or a value of the input array with the container's, and records the outcome in
     * $atomic_comparison_result.
     *
     * @param bool $check_parent_type whether a parent type given for the part is a coercion, as for a declared entry;
     *                                an extra entry or a fallback is compared as the value of an `array<K, V>`
     */
    private static function isPartContainedBy(
        Codebase $codebase,
        Union $input_part,
        Union $container_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result,
        bool $check_parent_type = false,
    ): bool {
        $part_comparison = new TypeComparisonResult();

        $is_contained = UnionTypeComparator::isContainedBy(
            $codebase,
            $input_part,
            $container_part,
            $input_part->ignore_nullable_issues,
            $input_part->ignore_falsable_issues,
            $part_comparison,
            $allow_interface_equality,
        );

        if (!$atomic_comparison_result) {
            return $is_contained;
        }

        if ($is_contained) {
            $atomic_comparison_result->to_string_cast = $atomic_comparison_result->to_string_cast === true
                || $part_comparison->to_string_cast === true;
        } else {
            if ($check_parent_type
                && !$part_comparison->type_coerced_from_scalar
                && !$part_comparison->type_coerced
            ) {
                // a parent type of the container's was given
                $inverse_part_comparison = new TypeComparisonResult();

                if (UnionTypeComparator::isContainedBy(
                    $codebase,
                    $container_part,
                    $input_part,
                    false,
                    false,
                    $inverse_part_comparison,
                    $allow_interface_equality,
                )
                    || $inverse_part_comparison->type_coerced_from_scalar
                ) {
                    $part_comparison->type_coerced = true;
                }
            }

            $atomic_comparison_result->mergeFailedPart($part_comparison);

            if ($part_comparison->missing_shape_fields) {
                $atomic_comparison_result->missing_shape_fields = $part_comparison->missing_shape_fields;
            }
        }

        $atomic_comparison_result->addTypeVariableBounds($part_comparison);

        return $is_contained;
    }

    public static function isContainedByObjectWithProperties(
        Codebase $codebase,
        TNamedObject $input_type_part,
        TObjectWithProperties $container_type_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result,
    ): bool {
        $all_types_contain = true;

        $input_object_with_keys = self::coerceToObjectWithProperties(
            $codebase,
            $input_type_part,
            $container_type_part,
        );

        foreach ($container_type_part->properties as $property_name => $container_property_type) {
            if (!$input_object_with_keys || !isset($input_object_with_keys->properties[$property_name])) {
                $all_types_contain = false;

                continue;
            }

            $input_property_type = $input_object_with_keys->properties[$property_name];

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

    /**
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function coerceToObjectWithProperties(
        Codebase $codebase,
        TNamedObject $input_type_part,
        TObjectWithProperties $container_type_part,
    ): ?TObjectWithProperties {
        $storage = $codebase->classlikes->getStorageFor($input_type_part->value);

        if (!$storage) {
            return null;
        }

        $inferred_lower_bounds = [];

        if ($input_type_part instanceof TGenericObject) {
            foreach ($storage->template_types ?? [] as $template_name => $templates) {
                foreach (array_keys($templates) as $offset => $defining_at) {
                    $inferred_lower_bounds[$template_name][$defining_at] =
                        $input_type_part->type_params[$offset];
                }
            }
        }

        foreach ($storage->template_extended_params ?? [] as $defining_at => $templates) {
            foreach ($templates as $template_name => $template_atomic) {
                $inferred_lower_bounds[$template_name][$defining_at] = $template_atomic;
            }
        }

        $properties = [];

        foreach ($storage->appearing_property_ids as $property_name => $property_id) {
            if (!isset($container_type_part->properties[$property_name])) {
                continue;
            }

            $property_type = $codebase->properties->hasStorage($property_id)
                ? $codebase->properties->getStorage($property_id)->type
                : null;

            $properties[$property_name] = $property_type ?? Type::getMixed();
        }

        // a `@property` is readable as the fetch of the property finds it: through `__get()`, or where the
        // configuration or `#[AllowDynamicProperties]` lets the docblock stand for the property
        $pseudo_properties_are_readable = $codebase->config->use_phpdoc_property_without_magic_or_parent
            || $storage->hasAttributeIncludingParents('AllowDynamicProperties', $codebase)
            || isset($storage->declaring_method_ids['__get']);

        foreach ($container_type_part->properties as $property_name => $_) {
            if ($pseudo_properties_are_readable
                && !isset($properties[$property_name])
                && isset($storage->pseudo_property_get_types['$' . $property_name])
            ) {
                $properties[$property_name] = $storage->pseudo_property_get_types['$' . $property_name];
            }
        }

        $replaced_object = TemplateInferredTypeReplacer::replace(
            new Union([
                new TObjectWithProperties($properties),
            ]),
            new TemplateResult(
                $storage->template_types ?? [],
                $inferred_lower_bounds,
            ),
            $codebase,
        );

        /** @var TObjectWithProperties */
        return $replaced_object->getSingleAtomic();
    }
}
