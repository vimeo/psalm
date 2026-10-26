<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Internal\Type\CallSiteVarianceProjector;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Variance;

use function array_merge;
use function array_values;
use function count;
use function strtolower;

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
            $input_variances,
        );

        $container_variances = $container_type_part instanceof TGenericObject
            ? CallSiteVarianceProjector::getCallSiteVariances($codebase, $container_type_part)
            : [];

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

            $container_variance = $container_variances[$i] ?? null;

            if ($container_variance === Variance::Bivariant) {
                continue;
            }

            $input_variance = $input_variances[$i] ?? null;

            // an input argument with a call-site variance only fits a container argument with the same one. This
            // comes before the shortcut below, which only concerns what the argument holds
            if ($input_variance !== null && $input_variance !== $container_variance) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = false;
                }

                $all_types_contain = false;

                continue;
            }

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

            $param_comparison_result = new TypeComparisonResult();

            if ($container_variance === Variance::Contravariant) {
                // a template of the container that no argument has bound yet is bound by this one, not compared
                if ($container_param->hasTemplate()
                    || ($container_param->had_template && $container_param->isMixed())
                ) {
                    continue;
                }

                // a bare `Box` is not known to fit, while a `Box<mixed>` or a class extending it is explicit
                if ($input_param->isMixed()
                    && !$container_param->isMixed()
                    && self::leavesTemplateUnbound($codebase, $input_type_part, $container_type_part, $i)
                ) {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced = true;
                        $atomic_comparison_result->type_coerced_from_mixed = true;
                    }

                    $all_types_contain = false;

                    continue;
                }

                $lower_bounds_before = count($param_comparison_result->type_variable_lower_bounds);

                $reverse_contained = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $container_param,
                    $input_param,
                    $container_param->ignore_nullable_issues,
                    $container_param->ignore_falsable_issues,
                    $param_comparison_result,
                    $allow_interface_equality,
                );

                self::markMirroredLowerBounds($param_comparison_result, $lower_bounds_before);

                if (!$reverse_contained || $param_comparison_result->type_coerced) {
                    $all_types_contain = false;

                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced = false;
                    }
                }
            } elseif (!UnionTypeComparator::isContainedBy(
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

                        self::markMirroredLowerBounds($param_comparison_result, $lower_bounds_before);

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
     * Lower bounds a reverse ("basically the same") check recorded are mirrors of the container param, not values
     * the variable holds
     */
    private static function markMirroredLowerBounds(TypeComparisonResult $result, int $first_mirror): void
    {
        $lower_bounds_count = count($result->type_variable_lower_bounds);

        for ($i = $first_mirror; $i < $lower_bounds_count; $i++) {
            $result->type_variable_lower_bounds[$i][1]->from_invariant_argument_mirror = true;
        }
    }

    /**
     * Whether an argument of the container is left to a template of an input without type params, or to a parent
     * the input extends without naming its arguments
     *
     * @psalm-capabilities read-props
     */
    private static function leavesTemplateUnbound(
        Codebase $codebase,
        TNamedObject|TIterable $input,
        TGenericObject|TIterable $container,
        int $offset,
    ): bool {
        if (!$input instanceof TNamedObject) {
            return true;
        }

        if (strtolower($input->value) === strtolower($container->value)
            || !$codebase->classlike_storage_provider->has($input->value)
        ) {
            return !$input instanceof TGenericObject;
        }

        $extended = $codebase->classlike_storage_provider->get($input->value)
            ->template_extended_params[$container->value] ?? [];
        $extended_param = array_values($extended)[$offset] ?? null;

        if ($extended_param === null || !$extended_param->from_docblock) {
            return true;
        }

        return !$input instanceof TGenericObject && $extended_param->hasTemplate();
    }
}
