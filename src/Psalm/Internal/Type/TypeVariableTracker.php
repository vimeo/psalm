<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\TypeVisitor\TypeVariableResolver;
use Psalm\Issue\ArgumentTypeCoercion;
use Psalm\Issue\IncompatibleTypeParameters;
use Psalm\Issue\MixedArgumentTypeCoercion;
use Psalm\Issue\MixedPropertyTypeCoercion;
use Psalm\Issue\MixedReturnTypeCoercion;
use Psalm\Issue\PropertyTypeCoercion;
use Psalm\IssueBuffer;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Union;

use function array_filter;
use function array_map;
use function array_reverse;
use function array_splice;
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
     * The recording order of each position that recorded bounds, kept from its first time so a loop body analyzed
     * again doesn't move its bounds after the ones that follow it
     *
     * @var array<string, int>
     */
    private array $position_orders = [];

    /**
     * Mints a fresh type variable name, registering its bound storage.
     *
     * @psalm-external-mutation-free
     */
    public function addVariable(TypeVariableBounds $bounds): string
    {
        $name = '`_' . count($this->bounds);
        $this->bounds[$name] = $bounds;
        return $name;
    }

    /**
     * Whether any type variable has been minted in the tracked function-like
     * so far; when none has, no type can mention one.
     *
     * @psalm-mutation-free
     */
    public function hasVariables(): bool
    {
        return $this->bounds !== [];
    }

    /**
     * Transfers bounds recorded by a type comparison, stamping each with the
     * position of the expression that produced them. Bounds for unknown
     * variables are silently dropped.
     *
     * The suppressions in effect at that position are kept with the bounds, so
     * an issue raised against them at reconciliation honours a statement-level
     * `@psalm-suppress` the way the eagerly reported issue would have.
     *
     * @param list<array{string, TemplateBound}> $lower
     * @param list<array{string, TemplateBound}> $upper
     * @param array<string> $suppressed_issues
     */
    public function addBounds(
        array $lower,
        array $upper,
        ?CodeLocation $pos,
        array $suppressed_issues = [],
    ): void {
        $recorded_order = 0;

        if ($pos !== null) {
            $position_key = $pos->file_path . ':' . $pos->raw_file_start . ':' . $pos->raw_file_end;
            $recorded_order = $this->position_orders[$position_key] ??= count($this->position_orders) + 1;
        }

        foreach ($lower as [$name, $bound]) {
            if (isset($this->bounds[$name])) {
                $bound->pos = $pos;
                $bound->suppressed_issues = $suppressed_issues;
                $bound->recorded_order = $recorded_order;
                $this->bounds[$name]->lower_bounds[] = $bound;
            }
        }

        foreach ($upper as [$name, $bound]) {
            if (isset($this->bounds[$name])) {
                $bound->pos = $pos;
                $bound->suppressed_issues = $suppressed_issues;
                $bound->recorded_order = $recorded_order;
                $this->bounds[$name]->upper_bounds[] = $bound;
            }
        }
    }

    /**
     * Transfers the bounds recorded while checking a value assigned to a property against its declared type; a value
     * that only reaches an upper bound through mixed is reported as MixedPropertyTypeCoercion at reconciliation.
     *
     * @param array<string> $suppressed_issues
     */
    public function addPropertyAssignmentBounds(
        TypeComparisonResult $comparison_result,
        string $property_id,
        CodeLocation $pos,
        array $suppressed_issues,
    ): void {
        foreach ($comparison_result->type_variable_upper_bounds as [$_, $upper_bound]) {
            $upper_bound->property_requirement_id = $property_id;
        }

        foreach ($comparison_result->type_variable_lower_bounds as [$_, $lower_bound]) {
            $lower_bound->property_value_id = $property_id;
        }

        $this->addBounds(
            $comparison_result->type_variable_lower_bounds,
            $comparison_result->type_variable_upper_bounds,
            $pos,
            $suppressed_issues,
        );
    }

    /**
     * How many lower and upper bounds each variable has so far.
     *
     * @return array<string, array{int, int}>
     * @psalm-mutation-free
     */
    public function snapshotBounds(): array
    {
        return array_map(
            static fn(TypeVariableBounds $bounds): array => [
                count($bounds->lower_bounds),
                count($bounds->upper_bounds),
            ],
            $this->bounds,
        );
    }

    /**
     * Drops the bounds recorded between two calls to snapshotBounds() (or since
     * the first one, without the second) by analyses whose results are
     * discarded (a loop pass followed by another one, a pre-analysis) on the
     * variables minted before them. A variable minted by such an analysis
     * keeps its bounds: the types it is in can outlive it, and only the
     * analysis that minted it constrains them.
     *
     * @param array<string, array{int, int}> $from
     * @param array<string, array{int, int}>|null $to
     */
    public function discardBoundsRecordedBetween(array $from, ?array $to = null): void
    {
        foreach ($from as $name => [$lower_from, $upper_from]) {
            if (!isset($this->bounds[$name])) {
                continue;
            }

            $bounds = $this->bounds[$name];

            [$lower_to, $upper_to] = $to[$name] ?? [count($bounds->lower_bounds), count($bounds->upper_bounds)];

            array_splice($bounds->lower_bounds, $lower_from, $lower_to - $lower_from);
            array_splice($bounds->upper_bounds, $upper_from, $upper_to - $upper_from);
        }
    }

    /**
     * Resolves any top-level type variables in a union through the bounds
     * attached to them: the union of the lower bounds recorded so far, or the
     * constraint the variable was minted with when nothing has bound it from
     * below. Used where a concrete shape is required (property reads, method
     * call returns); the variable itself stays in the object's type params,
     * so later uses still constrain it.
     *
     * @psalm-external-mutation-free
     * @psalm-suppress ImpureMethodCall the resolver only mutates itself and the
     *      union it is handed
     */
    public static function resolveTypeVariables(Union $type, ?Codebase $codebase): Union
    {
        $resolved = self::doResolveTypeVariables($type, $codebase, []);

        // A variable minted for an empty construction stands for `never`
        // wherever it is nested as well: a `list<T>` read off an empty
        // collection is an empty list, and the surrounding analysis can
        // only see that once the variable has become `never`. Variables
        // bound to something stay live in there for later constraints.
        $nested_resolver = new TypeVariableResolver($codebase, true);
        $nested_resolver->traverse($resolved);

        return $resolved;
    }

    /**
     * @param array<string, true> $seen names currently being resolved, so a
     *      cyclic bound (`_a` bounded by `_b` bounded by `_a`) stops instead of
     *      recursing forever.
     * @psalm-external-mutation-free
     */
    private static function doResolveTypeVariables(Union $type, ?Codebase $codebase, array $seen): Union
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

            if ($atomic_type instanceof TTypeVariable
                && $atomic_type->bounds
                && !isset($seen[$atomic_type->name])
            ) {
                if ($atomic_type->bounds->lower_bounds) {
                    $resolved = TTypeVariable::widenMixedToConstraint(
                        TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                            $atomic_type->bounds->lower_bounds,
                            $codebase,
                        ),
                        $atomic_type->bounds,
                    );
                } elseif ($atomic_type->bounds->upper_bounds) {
                    $resolved = $atomic_type->bounds->upper_bounds[0]->type;
                }

                // A bound may itself be, or contain, another type variable
                // (`_b` whose bound is `_a` whose bound is a concrete type).
                // Resolve through to the concrete bound so a single call reaches
                // a fixpoint, guarding against cyclic bounds.
                if ($resolved) {
                    $resolved = self::doResolveTypeVariables(
                        $resolved,
                        $codebase,
                        $seen + [$atomic_type->name => true],
                    );
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
        $this->position_orders = [];

        // a bound may name another variable (the `list<TItem>` bounding `TList` in a `new Lists()`), which was
        // minted before it: going from the latest variable back, what the bounds of the others put on a variable
        // is known before it is reconciled
        $nested_lower_bounds = [];
        $nested_upper_bounds = [];

        foreach (array_reverse($all_bounds, true) as $name => $bounds) {
            $nested_bounds = [];

            self::reconcileLowerBoundsWithUpperBounds(
                $codebase,
                [...$bounds->lower_bounds, ...$nested_lower_bounds[$name] ?? []],
                [...$bounds->upper_bounds, ...$nested_upper_bounds[$name] ?? []],
                $fallback_location,
                $suppressed_issues,
                $nested_bounds,
            );

            unset($all_bounds[$name], $nested_lower_bounds[$name], $nested_upper_bounds[$name]);

            foreach ($nested_bounds as [$nested_name, $is_lower, $nested_bound]) {
                if (!isset($all_bounds[$nested_name])) {
                    continue;
                }

                if ($is_lower) {
                    $nested_lower_bounds[$nested_name][] = $nested_bound;
                } else {
                    $nested_upper_bounds[$nested_name][] = $nested_bound;
                }
            }
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
     * @param list<array{string, bool, TemplateBound}> $nested_bounds receives what a lower bound held by an upper
     *     bound puts on the variables they name (`list{'a'}` held by `list<T>` puts `'a'` below `T`): the name of
     *     the variable, whether the bound is a lower one, and the bound
     */
    private static function reconcileLowerBoundsWithUpperBounds(
        Codebase $codebase,
        array $lower_bounds,
        array $upper_bounds,
        CodeLocation $fallback_location,
        array $suppressed_issues,
        array &$nested_bounds,
    ): void {
        $relevant_lower_bounds = self::getRelevantBounds($lower_bounds);

        $content_lower_bounds = [];
        foreach ($relevant_lower_bounds as $bound) {
            if (!$bound->from_invariant_argument_mirror) {
                $content_lower_bounds[] = $bound;
            }
        }

        $lower_bounds_to_check = $content_lower_bounds ?: $relevant_lower_bounds;

        $has_issue = false;

        foreach ($lower_bounds_to_check as $relevant_lower_bound) {
            if ($relevant_lower_bound->from_constraint_fallback) {
                // the constraint standing in for an argument that was
                // rejected as invalid says nothing about the content, and
                // that argument has already been reported
                continue;
            }

            foreach ($upper_bounds as $upper_bound) {
                $union_comparison_result = new TypeComparisonResult();

                if (UnionTypeComparator::isContainedBy(
                    $codebase,
                    $relevant_lower_bound->type,
                    $upper_bound->type,
                    false,
                    false,
                    $union_comparison_result,
                )) {
                    foreach ([
                        [true, $union_comparison_result->type_variable_lower_bounds],
                        [false, $union_comparison_result->type_variable_upper_bounds],
                    ] as [$is_lower, $recorded_bounds]) {
                        foreach ($recorded_bounds as [$nested_name, $nested_bound]) {
                            // a violation is reported where the value that causes it was given
                            $nested_bound->pos = $relevant_lower_bound->pos;
                            $nested_bound->suppressed_issues = $relevant_lower_bound->suppressed_issues;
                            $nested_bound->recorded_order = $relevant_lower_bound->recorded_order;

                            $nested_bounds[] = [$nested_name, $is_lower, $nested_bound];
                        }
                    }
                } else {
                    // suppressions in effect where either bound was recorded
                    // apply to the issue, as the statement-level suppression
                    // of an eagerly reported coercion would have (the keys are
                    // the suppressions' source offsets, which mark them as used)
                    $bound_suppressed_issues = $suppressed_issues
                        + $relevant_lower_bound->suppressed_issues
                        + $upper_bound->suppressed_issues;

                    // a value passed or assigned after the requirement was set
                    // (`$this->p = new Coll(); $this->p->add($v)`) is reported
                    // there, as the issue the eager template model reported there
                    $lower_bound_is_later = $relevant_lower_bound->recorded_order > $upper_bound->recorded_order
                        && ($relevant_lower_bound->from_argument_value
                            || $relevant_lower_bound->property_value_id !== null);

                    if ($union_comparison_result->type_coerced_from_mixed) {
                        // a value that entered the variable as mixed (e.g. `new Box($mixed)`)
                        // and is then required to be something concrete by an argument,
                        // property or return position gets the same Mixed*TypeCoercion the
                        // eager template model reported for `Box<mixed>` in that position
                        // (a return position, as before, tolerates a template resolved
                        // through its `as mixed` constraint); any other bound inferred
                        // through mixed gets the same loose gate Psalm applies when binding
                        // templates from mixed arguments
                        $reports_mixed_coercion = $upper_bound->from_argument_requirement
                            || $upper_bound->property_requirement_id !== null
                            || ($upper_bound->from_return_requirement
                                && !$union_comparison_result->type_coerced_from_as_mixed);

                        if (!$reports_mixed_coercion) {
                            continue;
                        }

                        $has_issue = true;

                        self::reportMixedCoercion(
                            $relevant_lower_bound,
                            $upper_bound,
                            $lower_bound_is_later,
                            $fallback_location,
                            $bound_suppressed_issues,
                        );

                        continue;
                    }

                    $has_issue = true;

                    // argument requirements point at the call site, unless the
                    // value was passed after them; return types and constraints
                    // point at where the value entered
                    $pos = $upper_bound->from_argument_requirement && !$lower_bound_is_later
                        ? ($upper_bound->pos ?? $relevant_lower_bound->pos ?? $fallback_location)
                        : ($relevant_lower_bound->pos ?? $upper_bound->pos ?? $fallback_location);

                    [$from_argument, $property_id] = $lower_bound_is_later
                        ? [$relevant_lower_bound->from_argument_value, $relevant_lower_bound->property_value_id]
                        : [$upper_bound->from_argument_requirement, $upper_bound->property_requirement_id];

                    $message = 'Type ' . $relevant_lower_bound->type->getId()
                        . ' should be a subtype of ' . $upper_bound->type->getId();

                    // a value that is only a parent type of what an argument or
                    // property position requires (`Box<Base>` where `Box<Child>`
                    // is expected) is reported as the coercion the eager template
                    // model reported in that position, so the same suppression
                    // covers it
                    if ($union_comparison_result->type_coerced && $from_argument) {
                        $issue = new ArgumentTypeCoercion($message, $pos);
                    } elseif ($union_comparison_result->type_coerced && $property_id !== null) {
                        $issue = new PropertyTypeCoercion($message, $pos, $property_id);
                    } else {
                        $issue = new IncompatibleTypeParameters($message, $pos);
                    }

                    IssueBuffer::maybeAdd($issue, $bound_suppressed_issues);
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
     * Reports a lower bound that only reached a requirement through mixed, as
     * the coercion issue matching the later of the two positions.
     *
     * @param array<string> $suppressed_issues
     */
    private static function reportMixedCoercion(
        TemplateBound $lower_bound,
        TemplateBound $upper_bound,
        bool $lower_bound_is_later,
        CodeLocation $fallback_location,
        array $suppressed_issues,
    ): void {
        $pos = $lower_bound_is_later
            ? ($lower_bound->pos ?? $fallback_location)
            : ($upper_bound->pos ?? $lower_bound->pos ?? $fallback_location);

        $message = 'Type ' . $lower_bound->type->getId()
            . ' should be a subtype of ' . $upper_bound->type->getId();

        if ($lower_bound_is_later) {
            $issue = $lower_bound->from_argument_value
                ? new MixedArgumentTypeCoercion($message, $pos)
                : new MixedPropertyTypeCoercion($message, $pos, (string) $lower_bound->property_value_id);
        } elseif ($upper_bound->from_argument_requirement) {
            $issue = new MixedArgumentTypeCoercion($message, $pos);
        } elseif ($upper_bound->from_return_requirement) {
            $issue = new MixedReturnTypeCoercion($message, $pos);
        } else {
            $issue = new MixedPropertyTypeCoercion(
                $message,
                $pos,
                (string) $upper_bound->property_requirement_id,
            );
        }

        IssueBuffer::maybeAdd($issue, $suppressed_issues);
    }

    /**
     * Sorts the bounds by appearance depth and keeps the shallowest run,
     * escaping when the depth changes unless an invariant (equality) bound
     * matched at a different argument offset.
     *
     * @param list<TemplateBound> $lower_bounds
     * @return list<TemplateBound>
     * @psalm-mutation-free
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
