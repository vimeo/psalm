<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TNamedObject;

/**
 * @internal
 */
final class GenericTypeComparator
{
    /**
     * @param TGenericObject|TIterable $container_type_part
     */
    public static function isContainedBy(
        Codebase $codebase,
        Atomic $input_type_part,
        Atomic $container_type_part,
        bool $allow_interface_equality = false,
        ?TypeComparisonResult $atomic_comparison_result = null,
    ): bool {
        $all_types_contain = true;
        $container_was_iterable = false;

        if ($container_type_part instanceof TIterable
            && !$container_type_part->extra_types
            && !$input_type_part instanceof TIterable
        ) {
            $container_type_part = new TGenericObject(
                'Traversable',
                $container_type_part->type_params,
            );

            $container_was_iterable = true;
        }

        if (!$input_type_part instanceof TNamedObject && !$input_type_part instanceof TIterable) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_mixed = true;
            }

            return false;
        }

        $container_type_params_covariant = [];

        $input_type_params = TemplateStandinTypeReplacer::getMappedGenericTypeParams(
            $codebase,
            $input_type_part,
            $container_type_part,
            $container_type_params_covariant,
        );

        $atomic_comparison_result_type_params = null;
        if ($atomic_comparison_result) {
            if (!$atomic_comparison_result->replacement_atomic_type) {
                $atomic_comparison_result->replacement_atomic_type = $input_type_part;
            }

            if ($atomic_comparison_result->replacement_atomic_type instanceof TGenericObject) {
                $atomic_comparison_result_type_params = $atomic_comparison_result->replacement_atomic_type->type_params;
            }
        }
        foreach ($input_type_params as $i => $input_param) {
            if (!isset($container_type_part->type_params[$i])) {
                break;
            }

            $container_param = $container_type_part->type_params[$i];

            if ($input_param->isNever()) {
                if ($atomic_comparison_result_type_params !== null) {
                    $atomic_comparison_result_type_params[$i] = $container_param;
                }

                continue;
            }

            $param_comparison_result = new TypeComparisonResult();

            if (!UnionTypeComparator::isContainedBy(
                $codebase,
                $input_param,
                $container_param,
                $input_param->ignore_nullable_issues,
                $input_param->ignore_falsable_issues,
                $param_comparison_result,
                $allow_interface_equality,
            )) {
                if ($input_type_part->value === 'Generator'
                    && $i === 2
                    && $param_comparison_result->type_coerced_from_mixed
                ) {
                    continue;
                }

                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced
                        = $param_comparison_result->type_coerced === true
                            && $atomic_comparison_result->type_coerced !== false;

                    $atomic_comparison_result->type_coerced_from_mixed
                        = $param_comparison_result->type_coerced_from_mixed === true
                            && $atomic_comparison_result->type_coerced_from_mixed !== false;

                    $atomic_comparison_result->type_coerced_from_as_mixed
                        = !$container_was_iterable
                            && $param_comparison_result->type_coerced_from_as_mixed === true
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
                }

                // if the container was an iterable then there was no mapping
                // from a template type
                if ($container_was_iterable || !$param_comparison_result->type_coerced_from_as_mixed) {
                    $all_types_contain = false;
                }
            } elseif (!$input_type_part instanceof TIterable
                && !$container_type_part instanceof TIterable
                && !$container_param->hasTemplate()
                && !$input_param->hasTemplate()
            ) {
                if ($input_param->containsAnyLiteral()) {
                    if ($atomic_comparison_result_type_params !== null) {
                        $atomic_comparison_result_type_params[$i] = $container_param;
                    }
                } else {
                    if (!($container_type_params_covariant[$i] ?? false)
                        && !$container_param->had_template
                    ) {
                        // Make sure types are basically the same
                        if (!UnionTypeComparator::isContainedBy(
                            $codebase,
                            $container_param,
                            $input_param,
                            $container_param->ignore_nullable_issues,
                            $container_param->ignore_falsable_issues,
                            $param_comparison_result,
                            $allow_interface_equality,
                        ) || $param_comparison_result->type_coerced
                        ) {
                            if ($container_param->hasStaticObject()
                                && $input_param->isStaticObject()
                            ) {
                                // do nothing
                            } else {
                                $all_types_contain = false;

                                if ($atomic_comparison_result) {
                                    $atomic_comparison_result->type_coerced = false;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($atomic_comparison_result
            && $atomic_comparison_result->replacement_atomic_type instanceof TGenericObject
            && $atomic_comparison_result_type_params
        ) {
            /** @psalm-suppress ArgumentTypeCoercion Psalm bug */
            $atomic_comparison_result->replacement_atomic_type =
                $atomic_comparison_result->replacement_atomic_type
                    ->setTypeParams($atomic_comparison_result_type_params);
        }

        if ($all_types_contain) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->to_string_cast = false;
            }

            return true;
        }

        return false;
    }
}
