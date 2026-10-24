<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Variance;

use function array_keys;
use function array_merge;
use function array_search;
use function array_values;
use function count;

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
            // the purity of the iterable is the last template of Traversable
            $container_type_part = new TGenericObject(
                'Traversable',
                [...$container_type_part->type_params, $container_type_part->purity],
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

            // a param standing for `never` (an empty construction's type
            // variable included) is widened to the container's param: the
            // value is being handed to code that sees it as the wider type
            if ($input_param->isNever()
                || ($input_param->isSingle()
                    && ($input_variable = $input_param->getSingleAtomic()) instanceof TTypeVariable
                    && $input_variable->isNeverBound())
            ) {
                if ($atomic_comparison_result_type_params !== null) {
                    $atomic_comparison_result_type_params[$i] = $container_param;
                }

                continue;
            }

            $container_variance = $container_type_part instanceof TGenericObject
                ? $container_type_part->type_param_variances[$i] ?? null
                : null;

            if ($container_variance === Variance::Bivariant) {
                continue;
            }

            $input_variance = $input_type_part instanceof TGenericObject
                ? self::getInputVariance($codebase, $input_type_part, $container_type_part, $i)
                : null;

            // an input argument with a call-site variance only fits a container argument with the same one
            if ($input_variance !== null && $input_variance !== $container_variance) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = false;
                }

                $all_types_contain = false;

                continue;
            }

            $param_comparison_result = new TypeComparisonResult();

            [$contained_param, $containing_param] = $container_variance === Variance::Contravariant
                ? [$container_param, $input_param]
                : [$input_param, $container_param];

            if (!UnionTypeComparator::isContainedBy(
                $codebase,
                $contained_param,
                $containing_param,
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
                        && $container_variance !== Variance::Contravariant
                        && !$container_param->had_template
                    ) {
                        $lower_bounds_before = count($param_comparison_result->type_variable_lower_bounds);

                        // Make sure types are basically the same
                        $reverse_contained = UnionTypeComparator::isContainedBy(
                            $codebase,
                            $container_param,
                            $input_param,
                            $container_param->ignore_nullable_issues,
                            $container_param->ignore_falsable_issues,
                            $param_comparison_result,
                            $allow_interface_equality,
                        );

                        // lower bounds this reverse check recorded are mirrors
                        // of the container param, not values the variable holds
                        $lower_bounds_count = count($param_comparison_result->type_variable_lower_bounds);
                        for ($mirror_i = $lower_bounds_before; $mirror_i < $lower_bounds_count; $mirror_i++) {
                            $param_comparison_result
                                ->type_variable_lower_bounds[$mirror_i][1]
                                ->from_invariant_argument_mirror = true;
                        }

                        if (!$reverse_contained || $param_comparison_result->type_coerced) {
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

            if ($atomic_comparison_result
                && ($param_comparison_result->type_variable_lower_bounds
                    || $param_comparison_result->type_variable_upper_bounds)
            ) {
                // generic params constrain a type variable appearing in them:
                // an invariant param's reverse ("basically the same") check
                // has already recorded the container param as a lower bound,
                // so the bounds can be passed up as they were recorded
                $atomic_comparison_result->type_variable_lower_bounds = array_merge(
                    $atomic_comparison_result->type_variable_lower_bounds,
                    $param_comparison_result->type_variable_lower_bounds,
                );

                $atomic_comparison_result->type_variable_upper_bounds = array_merge(
                    $atomic_comparison_result->type_variable_upper_bounds,
                    $param_comparison_result->type_variable_upper_bounds,
                );
            }
        }

        // what iterating over the input may do must be allowed by the container
        if ($input_type_part instanceof TIterable
            && $container_type_part instanceof TIterable
            && !UnionTypeComparator::isContainedBy(
                $codebase,
                $input_type_part->purity,
                $container_type_part->purity,
            )
        ) {
            $all_types_contain = false;
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

    /**
     * The call-site variance of the input's argument that ends up as the container's argument at $offset.
     * One equal to the variance the container class declares for the template is no call-site variance.
     */
    private static function getInputVariance(
        Codebase $codebase,
        TGenericObject $input_type_part,
        Atomic $container_type_part,
        int $offset,
    ): ?Variance {
        if ($input_type_part->type_param_variances === []) {
            return null;
        }

        $input_offset = $offset;

        if ($input_type_part->value !== $container_type_part->value) {
            $input_storage = $codebase->classlike_storage_provider->has($input_type_part->value)
                ? $codebase->classlike_storage_provider->get($input_type_part->value)
                : null;

            $extended_params = $input_storage?->template_extended_params[$container_type_part->value] ?? null;
            $extended_param = $extended_params !== null ? array_values($extended_params)[$offset] ?? null : null;
            $extended_atomic = $extended_param?->isSingle() ? $extended_param->getSingleAtomic() : null;

            if (!$input_storage
                || !$extended_atomic instanceof TTemplateParam
                || $extended_atomic->defining_class !== $input_storage->name
            ) {
                return null;
            }

            $input_offset = array_search(
                $extended_atomic->param_name,
                array_keys($input_storage->template_types ?? []),
                true,
            );

            if ($input_offset === false) {
                return null;
            }
        }

        $variance = $input_type_part->type_param_variances[$input_offset] ?? null;

        if ($variance === Variance::Covariant
            && $codebase->classlike_storage_provider->has($container_type_part->value)
        ) {
            $container_storage = $codebase->classlike_storage_provider->get($container_type_part->value);

            if ($container_storage->template_covariants[$offset] ?? false) {
                return null;
            }
        }

        return $variance;
    }
}
