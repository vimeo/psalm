<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\IncompatibleTypeParameters;
use Psalm\IssueBuffer;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Union;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function usort;

/**
 * Tracks the type variables minted in a top-level function-like (or in the
 * statements of a file's global scope), accumulating constraints on each
 * until the function-like has been analyzed, at which point the lower and
 * upper bounds of every variable are reconciled with each other.
 *
 * One tracker is shared between a function-like and any closures nested
 * within it, so a constraint recorded inside a closure still reconciles when
 * the outermost function-like completes.
 *
 * @internal
 */
final class TypeVariableTracker
{
    /** @var array<string, TypeVariableBounds> */
    private array $bounds = [];

    /**
     * Mints a fresh type variable name, registering its bound storage.
     */
    public function addVariable(TypeVariableBounds $bounds): string
    {
        $name = '`_' . count($this->bounds);
        $this->bounds[$name] = $bounds;
        return $name;
    }

    /**
     * Transfers bounds recorded by a type comparison, stamping each with the
     * position of the expression that produced them. Bounds for unknown
     * variables are silently dropped.
     *
     * @param list<array{string, TemplateBound}> $lower
     * @param list<array{string, TemplateBound}> $upper
     */
    public function addBounds(array $lower, array $upper, ?CodeLocation $pos): void
    {
        foreach ($lower as [$name, $bound]) {
            if (isset($this->bounds[$name])) {
                $bound->pos = $pos;
                $this->bounds[$name]->lower_bounds[] = $bound;
            }
        }

        foreach ($upper as [$name, $bound]) {
            if (isset($this->bounds[$name])) {
                $bound->pos = $pos;
                $this->bounds[$name]->upper_bounds[] = $bound;
            }
        }
    }

    /**
     * Resolves any top-level type variables in a union through the bounds
     * attached to them: the union of the lower bounds recorded so far, or the
     * constraint the variable was minted with when nothing has bound it from
     * below. Used where a concrete shape is required (property reads, method
     * call returns); the variable itself stays in the object's type params,
     * so later uses still constrain it.
     */
    public static function resolveTypeVariables(Union $type, ?Codebase $codebase): Union
    {
        $has_type_variable = false;

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TTypeVariable) {
                $has_type_variable = true;
                break;
            }
        }

        if (!$has_type_variable) {
            return $type;
        }

        $resolved_types = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            $resolved = null;

            if ($atomic_type instanceof TTypeVariable && $atomic_type->bounds) {
                if ($atomic_type->bounds->lower_bounds) {
                    $resolved = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                        $atomic_type->bounds->lower_bounds,
                        $codebase,
                    );
                } elseif ($atomic_type->bounds->upper_bounds) {
                    $resolved = $atomic_type->bounds->upper_bounds[0]->type;
                }
            }

            if ($resolved) {
                foreach ($resolved->getAtomicTypes() as $resolved_atomic_type) {
                    $resolved_types[] = $resolved_atomic_type;
                }
            } else {
                $resolved_types[] = $atomic_type;
            }
        }

        return TypeCombiner::combine($resolved_types, $codebase);
    }

    /**
     * Reconciles every accumulated bound set, then clears the map so a
     * re-analysis of the same function-like starts fresh.
     *
     * @param array<string> $suppressed_issues
     */
    public function reconcile(
        Codebase $codebase,
        CodeLocation $fallback_location,
        array $suppressed_issues,
    ): void {
        $all_bounds = $this->bounds;
        $this->bounds = [];

        foreach ($all_bounds as $bounds) {
            self::reconcileLowerBoundsWithUpperBounds(
                $codebase,
                $bounds->lower_bounds,
                $bounds->upper_bounds,
                $fallback_location,
                $suppressed_issues,
            );
        }
    }

    /**
     * Reconciles the lower/upper/equality bounds accumulated for a type
     * variable, raising IncompatibleTypeParameters when they cannot hold
     * simultaneously.
     *
     * Valid constraints:
     *
     *   T <: int|float, T >: int --- implies T is an int
     *   T = int --- implies T is an int
     *
     * Invalid constraints:
     *
     *   T <: int|string, T >: string|float --- implies T <: int and T >: float,
     *   which is impossible
     *   T = int, T = string --- implies T is a string _and_ an int, which is
     *   impossible
     *
     * @param list<TemplateBound> $lower_bounds
     * @param list<TemplateBound> $upper_bounds
     * @param array<string> $suppressed_issues
     */
    private static function reconcileLowerBoundsWithUpperBounds(
        Codebase $codebase,
        array $lower_bounds,
        array $upper_bounds,
        CodeLocation $fallback_location,
        array $suppressed_issues,
    ): void {
        $relevant_lower_bounds = self::getRelevantBounds($lower_bounds);

        $has_issue = false;

        foreach ($relevant_lower_bounds as $relevant_lower_bound) {
            foreach ($upper_bounds as $upper_bound) {
                $union_comparison_result = new TypeComparisonResult();

                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $relevant_lower_bound->type,
                    $upper_bound->type,
                    false,
                    false,
                    $union_comparison_result,
                )) {
                    if ($union_comparison_result->type_coerced_from_mixed) {
                        // a bound inferred through mixed gets the same loose
                        // gate Psalm applies when binding templates from
                        // mixed arguments
                        continue;
                    }

                    $has_issue = true;
                    IssueBuffer::maybeAdd(
                        new IncompatibleTypeParameters(
                            'Type ' . $relevant_lower_bound->type->getId()
                                . ' should be a subtype of ' . $upper_bound->type->getId(),
                            $relevant_lower_bound->pos ?? $upper_bound->pos ?? $fallback_location,
                        ),
                        $suppressed_issues,
                    );
                }
            }
        }

        if (!$has_issue && count($relevant_lower_bounds) > 1) {
            $bounds_with_equality = array_values(array_filter(
                $lower_bounds,
                static fn(TemplateBound $bound): bool => $bound->equality_bound_classlike !== null,
            ));

            if (!$bounds_with_equality) {
                return;
            }

            $equality_strings = array_values(array_unique(
                array_map(
                    static fn(TemplateBound $bound): string => $bound->type->getId(),
                    $bounds_with_equality,
                ),
            ));

            if (count($equality_strings) > 1) {
                IssueBuffer::maybeAdd(
                    new IncompatibleTypeParameters(
                        'Incompatible types found for type variable (must have only one of '
                            . implode(', ', $equality_strings) . ')',
                        $bounds_with_equality[0]->pos ?? $fallback_location,
                    ),
                    $suppressed_issues,
                );
                return;
            }

            foreach ($lower_bounds as $lower_bound) {
                if ($lower_bound->equality_bound_classlike !== null) {
                    continue;
                }

                $matches_equality_bound = false;

                foreach ($bounds_with_equality as $bound_with_equality) {
                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $lower_bound->type,
                        $bound_with_equality->type,
                        false,
                        false,
                        new TypeComparisonResult(),
                    )) {
                        $matches_equality_bound = true;
                        break;
                    }
                }

                if (!$matches_equality_bound) {
                    $has_issue = true;
                    IssueBuffer::maybeAdd(
                        new IncompatibleTypeParameters(
                            'Incompatible types found for type variable (' . $lower_bound->type->getId()
                                . ' is not in ' . implode(', ', $equality_strings) . ')',
                            $lower_bound->pos ?? $fallback_location,
                        ),
                        $suppressed_issues,
                    );
                }
            }
        }

        if (!$has_issue && count($upper_bounds) > 1) {
            $has_upper_equality = false;

            foreach ($upper_bounds as $upper_bound) {
                if ($upper_bound->equality_bound_classlike !== null) {
                    $has_upper_equality = true;
                    break;
                }
            }

            if (!$has_upper_equality) {
                return;
            }

            foreach ($upper_bounds as $i => $upper_bound_with_equality) {
                if ($upper_bound_with_equality->equality_bound_classlike === null) {
                    continue;
                }

                foreach ($upper_bounds as $j => $upper_bound) {
                    if ($i === $j) {
                        continue;
                    }

                    if (!UnionTypeComparator::canExpressionTypesBeIdentical(
                        $codebase,
                        $upper_bound_with_equality->type,
                        $upper_bound->type,
                    )) {
                        IssueBuffer::maybeAdd(
                            new IncompatibleTypeParameters(
                                'Incompatible types found for type variable (' . $upper_bound->type->getId()
                                    . ' is not in ' . $upper_bound_with_equality->type->getId() . ')',
                                $upper_bound->pos ?? $fallback_location,
                            ),
                            $suppressed_issues,
                        );
                    }
                }
            }
        }
    }

    /**
     * Sorts the bounds by appearance depth and keeps the shallowest run,
     * escaping when the depth changes unless an invariant (equality) bound
     * matched at a different argument offset.
     *
     * @param list<TemplateBound> $lower_bounds
     * @return list<TemplateBound>
     */
    private static function getRelevantBounds(array $lower_bounds): array
    {
        if (count($lower_bounds) < 2) {
            return $lower_bounds;
        }

        usort(
            $lower_bounds,
            static fn(TemplateBound $bound_a, TemplateBound $bound_b): int
                => $bound_a->appearance_depth <=> $bound_b->appearance_depth,
        );

        $current_depth = null;
        $had_invariant = false;
        $last_arg_offset = -1;

        $applicable_bounds = [];

        foreach ($lower_bounds as $template_bound) {
            if ($current_depth === null) {
                $current_depth = $template_bound->appearance_depth;
            } elseif ($current_depth !== $template_bound->appearance_depth && $applicable_bounds) {
                if (!$had_invariant || $last_arg_offset === $template_bound->arg_offset) {
                    // escape switches when matching on invariant generic
                    // params and when matching
                    break;
                }

                $current_depth = $template_bound->appearance_depth;
            }

            $had_invariant = $had_invariant ?: $template_bound->equality_bound_classlike !== null;

            $applicable_bounds[] = $template_bound;

            $last_arg_offset = $template_bound->arg_offset;
        }

        return $applicable_bounds;
    }
}
