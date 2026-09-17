<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTypeAlias;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Union;

use function array_map;
use function array_merge;
use function array_pop;
use function array_push;
use function array_reverse;
use function array_unique;
use function assert;
use function count;
use function implode;
use function is_array;
use function sort;

use const PHP_INT_MAX;

/**
 * @internal
 */
final class UnionTypeComparator
{
    /**
     * Does the input param type match the given param type
     */
    public static function isContainedBy(
        Codebase $codebase,
        Union $input_type,
        Union $container_type,
        bool $ignore_null = false,
        bool $ignore_false = false,
        ?TypeComparisonResult $union_comparison_result = null,
        bool $allow_interface_equality = false,
        bool $allow_float_int_equality = true,
    ): bool {
        if ($container_type->isVanillaMixed()) {
            return true;
        }

        if ($input_type->isNever()) {
            return true;
        }

        if ($union_comparison_result) {
            $union_comparison_result->scalar_type_match_found = true;
        }

        if ($input_type->possibly_undefined
            && !$input_type->possibly_undefined_from_try
            && !$container_type->possibly_undefined
        ) {
            return false;
        }


        $container_has_template = $container_type->hasTemplateOrStatic();

        $input_atomic_types = array_reverse(self::getTypeParts($codebase, $input_type));

        while ($input_type_part = array_pop($input_atomic_types)) {
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

            if ($input_type_part instanceof TTypeVariable) {
                // A type variable in input position is constrained from above
                // by the container: record `name <: container` and treat it
                // as contained.
                //
                // A container that already holds the variable itself (`T <: T|X`)
                // constrains nothing, and recording it would make the variable's
                // bounds refer back to the variable, which can never be rendered
                // or resolved to a finite type.
                if (isset($container_type->getAtomicTypes()[$input_type_part->name])) {
                    continue;
                }

                if ($union_comparison_result) {
                    $union_comparison_result->type_variable_upper_bounds[] = [
                        $input_type_part->name,
                        new TemplateBound($container_type),
                    ];
                }

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

            $some_missing_shape_fields = null;

            if ($input_type_part instanceof TArrayKey
                && ($container_type->hasInt() && $container_type->hasString())
            ) {
                continue;
            }

            if ($input_type_part instanceof TArrayKey && $container_type->hasTemplate()) {
                foreach ($container_type->getTemplateTypes() as $template_type) {
                    if ($template_type->as->isArrayKey()) {
                        continue 2;
                    }
                }
            }

            if ($input_type_part instanceof TIntRange && $container_type->hasInt()) {
                if (IntegerRangeComparator::isContainedByUnion(
                    $input_type_part,
                    $container_type,
                )) {
                    continue;
                }
            }

            // Type variables among the container parts are only fallen back
            // to when no concrete container part accepts the input part: a
            // value that a concrete alternative already accounts for should
            // not constrain a variable it never has to flow into.
            $type_variable_container_parts = [];

            // The bounds recorded by each concrete container part that
            // accepted the input part. Only one alternative of a union needs
            // to hold, so they are reconciled with each other below rather
            // than all being imposed at once.
            $alternative_bounds = [];
            $unconditional_match = false;

            foreach (self::getTypeParts($codebase, $container_type) as $container_type_part) {
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

                if ($container_type_part instanceof TTypeVariable) {
                    $type_variable_container_parts[] = $container_type_part;
                    continue;
                }

                // if params are specified
                if ($container_type_part instanceof TCallable
                    && is_array($container_type_part->params)
                    && $input_type_part instanceof TCallable
                ) {
                    $container_all_param_count = count($container_type_part->params);
                    $container_required_param_count = 0;
                    foreach ($container_type_part->params as $index => $container_param) {
                        if (!$container_param->is_optional) {
                            $container_required_param_count = $index + 1;
                        }

                        if ($container_param->is_variadic === true) {
                            $container_all_param_count = PHP_INT_MAX;
                        }
                    }

                    $input_required_param_count = 0;
                    if (!is_array($input_type_part->params)) {
                        // it's not declared, there can be an arbitrary number of params
                        $input_all_param_count = PHP_INT_MAX;
                    } else {
                        $input_all_param_count = count($input_type_part->params);
                        foreach ($input_type_part->params as $index => $input_param) {
                            // can be false or not set at all
                            if (!$input_param->is_optional) {
                                $input_required_param_count = $index + 1;
                            }

                            if ($input_param->is_variadic === true) {
                                $input_all_param_count = PHP_INT_MAX;
                            }
                        }
                    }

                    // too few or too many non-optional params provided in callback
                    if ($container_all_param_count > $input_all_param_count
                        || $container_required_param_count > $input_all_param_count
                        || $input_required_param_count > $container_all_param_count
                        || $input_required_param_count > $container_required_param_count
                    ) {
                        continue;
                    }
                }

                if ($union_comparison_result) {
                    $atomic_comparison_result = new TypeComparisonResult();
                } else {
                    $atomic_comparison_result = null;
                }

                $is_atomic_contained_by = AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result,
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
                            $union_comparison_result->replacement_union_type = $input_type;
                        }

                        $replacement = $union_comparison_result->replacement_union_type->getBuilder();
                        $replacement->removeType($input_type->getKey());
                        $replacement->addType(
                            $atomic_comparison_result->replacement_atomic_type,
                        );
                        $union_comparison_result->replacement_union_type = $replacement->freeze();
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

                if ($input_type_part instanceof Atomic\TIterable
                    && ($container_type->hasArray() || $container_type->containsClassLike('traversable'))
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

                    if ($atomic_comparison_result->missing_shape_fields) {
                        $some_missing_shape_fields = $atomic_comparison_result->missing_shape_fields;
                    }
                }

                if ($is_atomic_contained_by) {
                    $type_match_found = true;

                    if ($atomic_comparison_result) {
                        if ($atomic_comparison_result->to_string_cast !== true) {
                            $all_to_string_cast = false;
                        }

                        if ($union_comparison_result) {
                            if ($atomic_comparison_result->type_variable_lower_bounds
                                || $atomic_comparison_result->type_variable_upper_bounds
                            ) {
                                $alternative_bounds[] = [
                                    $atomic_comparison_result->type_variable_lower_bounds,
                                    $atomic_comparison_result->type_variable_upper_bounds,
                                ];
                            } else {
                                $unconditional_match = true;
                            }
                        }
                    }

                    $all_type_coerced_from_mixed = false;
                    $all_type_coerced_from_as_mixed = false;
                    $all_type_coerced = false;
                }
            }

            if (!$type_match_found && $type_variable_container_parts) {
                // A type variable in container position is constrained from
                // below by an input part no concrete container part accepted:
                // record `name >: part` and treat it as a match. Where several
                // variables could take the part, only one needs to, and with
                // nothing to tell them apart the first is constrained.
                //
                // The input part is never the variable itself (that case is
                // handled above), so the bound cannot refer back to it.
                if ($union_comparison_result) {
                    $union_comparison_result->type_variable_lower_bounds[] = [
                        $type_variable_container_parts[0]->name,
                        new TemplateBound(new Union([$input_type_part])),
                    ];
                }

                $type_match_found = true;
                $all_to_string_cast = false;
                $all_type_coerced = false;
                $all_type_coerced_from_mixed = false;
                $all_type_coerced_from_as_mixed = false;
            } elseif ($union_comparison_result && $alternative_bounds && !$unconditional_match) {
                // every accepting alternative constrained a type variable, so
                // whichever holds, the variables are bound by at least their
                // reconciliation; an alternative that accepted the part
                // outright leaves them unconstrained instead
                [$lower_bounds, $upper_bounds] = self::mergeAlternativeBounds($alternative_bounds, $codebase);

                $union_comparison_result->type_variable_lower_bounds = array_merge(
                    $union_comparison_result->type_variable_lower_bounds,
                    $lower_bounds,
                );

                $union_comparison_result->type_variable_upper_bounds = array_merge(
                    $union_comparison_result->type_variable_upper_bounds,
                    $upper_bounds,
                );
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

                    if ($some_missing_shape_fields && !$some_type_coerced && !$scalar_type_match_found) {
                        $union_comparison_result->missing_shape_fields = $some_missing_shape_fields;
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
     * @psalm-external-mutation-free
     */
    public static function isContainedByInPhp(
        ?Union $input_type,
        Union $container_type,
    ): bool {
        if ($container_type->isMixed()) {
            return true;
        }

        if (!$input_type) {
            return false;
        }

        if ($input_type->isNever()) {
            return true;
        }

        if ($input_type->getId() === $container_type->getId()) {
            return true;
        }

        if ($input_type->isNullable() && !$container_type->isNullable()) {
            return false;
        }

        $input_type_not_null = $input_type->getBuilder();
        $input_type_not_null->removeType('null');

        $container_type_not_null = $container_type->getBuilder();
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
     * Does the input param type match the given param type
     */
    public static function canBeContainedBy(
        Codebase $codebase,
        Union $input_type,
        Union $container_type,
        bool $ignore_null = false,
        bool $ignore_false = false,
        array &$matching_input_keys = [],
    ): bool {
        if ($container_type->hasMixed()) {
            return true;
        }

        if ($input_type->isNever()) {
            return true;
        }

        if ($input_type->possibly_undefined && !$container_type->possibly_undefined) {
            return false;
        }

        foreach (self::getTypeParts($codebase, $container_type) as $container_type_part) {
            if ($container_type_part instanceof TNull && $ignore_null) {
                continue;
            }

            if ($container_type_part instanceof TFalse && $ignore_false) {
                continue;
            }

            foreach (self::getTypeParts($codebase, $input_type) as $input_type_part) {
                if ($input_type_part instanceof TTypeVariable) {
                    // a type variable can stand for anything the container
                    // accepts; it is bound as itself (read sites unroll a
                    // variable nested in another variable's bound), and
                    // reconciles with the constraints it is later held to
                    $matching_input_keys[$input_type_part->getKey()] = true;
                    continue;
                }

                $atomic_comparison_result = new TypeComparisonResult();
                $is_atomic_contained_by = AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    false,
                    false,
                    $atomic_comparison_result,
                );

                if (($is_atomic_contained_by && !$atomic_comparison_result->to_string_cast)
                    || $atomic_comparison_result->type_coerced_from_mixed
                ) {
                    $matching_input_keys[$input_type_part->getKey()] = true;
                }
            }
        }

        return (bool)$matching_input_keys;
    }

    /**
     * Merges the type-variable bounds recorded by the alternatives of a union
     * container that each accepted the same input part. Only one alternative
     * needs to hold, so a bound recorded by some of them constrains nothing on
     * its own; what remains is what every alternative agrees on:
     *
     * - a variable every alternative bounded from above is bounded by the
     *   union of the recorded types (a single alternative's several upper
     *   bounds are folded the same way, which can only loosen them);
     * - a variable every alternative bounded from below identically keeps that
     *   bound; differing lower bounds are dropped.
     *
     * @param non-empty-list<array{
     *     list<array{string, TemplateBound}>,
     *     list<array{string, TemplateBound}>
     * }> $alternatives
     * @return array{list<array{string, TemplateBound}>, list<array{string, TemplateBound}>}
     * @psalm-external-mutation-free
     */
    private static function mergeAlternativeBounds(array $alternatives, Codebase $codebase): array
    {
        $alternative_count = count($alternatives);

        if ($alternative_count === 1) {
            return $alternatives[0];
        }

        /** @var array<string, array<int, list<TemplateBound>>> $lower_by_name */
        $lower_by_name = [];
        /** @var array<string, array<int, list<TemplateBound>>> $upper_by_name */
        $upper_by_name = [];

        foreach ($alternatives as $i => [$lower_bounds, $upper_bounds]) {
            foreach ($lower_bounds as [$name, $bound]) {
                $lower_by_name[$name][$i][] = $bound;
            }

            foreach ($upper_bounds as [$name, $bound]) {
                $upper_by_name[$name][$i][] = $bound;
            }
        }

        $merged_lower_bounds = [];

        foreach ($lower_by_name as $name => $bounds_by_alternative) {
            if (count($bounds_by_alternative) !== $alternative_count) {
                continue;
            }

            if (count(array_unique(array_map(self::getBoundsId(...), $bounds_by_alternative))) === 1) {
                // every alternative recorded the same bounds: the first stand
                // for all of them
                foreach ($bounds_by_alternative as $bounds) {
                    foreach ($bounds as $bound) {
                        $merged_lower_bounds[] = [$name, $bound];
                    }

                    break;
                }
            }
        }

        $merged_upper_bounds = [];

        foreach ($upper_by_name as $name => $bounds_by_alternative) {
            if (count($bounds_by_alternative) !== $alternative_count) {
                continue;
            }

            if (count(array_unique(array_map(self::getBoundsId(...), $bounds_by_alternative))) === 1) {
                foreach ($bounds_by_alternative as $bounds) {
                    foreach ($bounds as $bound) {
                        $merged_upper_bounds[] = [$name, $bound];
                    }

                    break;
                }

                continue;
            }

            $merged_type = null;

            foreach ($bounds_by_alternative as $bounds) {
                foreach ($bounds as $bound) {
                    $merged_type = Type::combineUnionTypes($merged_type, $bound->type, $codebase);
                }
            }

            assert($merged_type !== null);

            $merged_upper_bounds[] = [$name, new TemplateBound($merged_type)];
        }

        return [$merged_lower_bounds, $merged_upper_bounds];
    }

    /**
     * @param list<TemplateBound> $bounds
     * @psalm-external-mutation-free
     */
    private static function getBoundsId(array $bounds): string
    {
        $ids = array_map(static fn(TemplateBound $bound): string => $bound->type->getId(), $bounds);
        sort($ids);

        return implode('|', $ids);
    }

    /**
     * Can any part of the $type1 be equal to any part of $type2
     */
    public static function canExpressionTypesBeIdentical(
        Codebase $codebase,
        Union $type1,
        Union $type2,
        bool $allow_interface_equality = true,
    ): bool {
        if ($type1->hasMixed() || $type2->hasMixed()) {
            return true;
        }

        if ($type1->isNullable() && $type2->isNullable()) {
            return true;
        }

        foreach (self::getTypeParts($codebase, $type1) as $type1_part) {
            foreach (self::getTypeParts($codebase, $type2) as $type2_part) {
                //special case for TIntRange because it can contain a part of another TIntRange.
                //For example int<0,10> and int<5, 15> can be identical but none contain the other
                if ($type1_part instanceof TIntRange && $type2_part instanceof TIntRange) {
                    $intersection_range = TIntRange::intersectIntRanges(
                        $type1_part,
                        $type2_part,
                    );
                    return $intersection_range !== null;
                }

                $either_contains = AtomicTypeComparator::canBeIdentical(
                    $codebase,
                    $type1_part,
                    $type2_part,
                    $allow_interface_equality,
                );

                if ($either_contains) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<Atomic>
     */
    private static function getTypeParts(
        Codebase $codebase,
        Union $union_type,
    ): array {
        $atomic_types = [];
        foreach ($union_type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof TTypeAlias && !$atomic_type instanceof TClassConstant) {
                $atomic_types[] = $atomic_type;
                continue;
            }

            if ($atomic_type instanceof TTypeAlias) {
                $fq_classlike_name = $atomic_type->declaring_fq_classlike_name;
            } else {
                $fq_classlike_name = $atomic_type->fq_classlike_name;
            }

            $expanded = TypeExpander::expandAtomic(
                $codebase,
                $atomic_type,
                $fq_classlike_name,
                $fq_classlike_name,
                null,
                true,
                true,
            );

            array_push($atomic_types, ...$expanded);
        }

        return $atomic_types;
    }
}
