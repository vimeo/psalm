<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\CodeLocation;
use Psalm\Storage\Assertion\IsGreaterThan;
use Psalm\Storage\Assertion\IsGreaterThanOrEqualTo;
use Psalm\Storage\Assertion\IsLessThan;
use Psalm\Storage\Assertion\IsLessThanOrEqualTo;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\MutableUnion;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function max;
use function min;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Narrows a type with an integer comparison: `$a > 5` and `!($a < 6)` both keep the values of at least 6
 *
 * @internal
 */
final class IntBoundReconciler extends Reconciler
{
    /**
     * @param string[] $suppressed_issues
     */
    public static function reconcileMinBound(
        IsGreaterThan|IsGreaterThanOrEqualTo $assertion,
        Union $existing_var_type,
        bool $inside_loop,
        string $old_var_type_string,
        ?string $var_id,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
    ): Union {
        $existing_var_type = $existing_var_type->getBuilder();
        // no integer is greater than PHP_INT_MAX, and adding 1 to it would give a float
        $is_unreachable = $assertion instanceof IsGreaterThan && $assertion->value === PHP_INT_MAX;
        $min_bound = $assertion instanceof IsGreaterThan && !$is_unreachable
            ? $assertion->value + 1
            : $assertion->value;

        $redundant = true;

        if ($assertion->doesFilterNullOrFalse() &&
            ($existing_var_type->hasType('null') || $existing_var_type->hasType('false'))
        ) {
            $redundant = false;
            $existing_var_type->removeType('null');
            $existing_var_type->removeType('false');
        }

        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($is_unreachable && $atomic_type instanceof TInt) {
                $redundant = false;
                $existing_var_type->removeType($atomic_type->getKey());
            } elseif ($atomic_type instanceof TIntRange) {
                if ($atomic_type->min_bound !== null && $atomic_type->min_bound >= $min_bound) {
                    // if the whole range is within the bound, the check is redundant
                } elseif ($atomic_type->contains($min_bound)) {
                    // if the range contains the bound, the range must be adapted
                    $redundant = false;
                    $existing_var_type->removeType($atomic_type->getKey());
                    $existing_var_type->addType(self::getNarrowedIntRange(
                        $atomic_type->min_bound === null ? $min_bound : max($atomic_type->min_bound, $min_bound),
                        $atomic_type->max_bound,
                    ));
                } elseif ($atomic_type->isLesserThan($min_bound)) {
                    // if the range is below the bound, the type must be removed
                    $redundant = false;
                    $existing_var_type->removeType($atomic_type->getKey());
                }
            } elseif ($atomic_type instanceof TLiteralInt) {
                if ($atomic_type->value < $min_bound) {
                    $redundant = false;
                    $existing_var_type->removeType($atomic_type->getKey());
                }
            } elseif ($atomic_type instanceof TInt) {
                $redundant = false;
                $existing_var_type->removeType($atomic_type->getKey());
                $existing_var_type->addType(new TIntRange($min_bound, null));
            } elseif ($atomic_type instanceof TTemplateParam) {
                $redundant = false;
                self::narrowTemplateBound(
                    $existing_var_type,
                    $atomic_type,
                    static fn(Union $bound): Union => self::reconcileMinBound(
                        $assertion,
                        $bound,
                        $inside_loop,
                        $old_var_type_string,
                        null,
                        $negated,
                        null,
                        $suppressed_issues,
                    ),
                );
            } else {
                // we assume that other types may have been removed (empty strings? numeric strings?)
                //It may be worth refining to improve reconciliation while keeping in mind we're on loose comparison
                $redundant = false;
            }
        }

        return self::finish(
            $existing_var_type,
            $redundant,
            $assertion,
            $inside_loop,
            $old_var_type_string,
            $var_id,
            $negated,
            $code_location,
            $suppressed_issues,
        );
    }

    /**
     * @param string[] $suppressed_issues
     */
    public static function reconcileMaxBound(
        IsLessThan|IsLessThanOrEqualTo $assertion,
        Union $existing_var_type,
        bool $inside_loop,
        string $old_var_type_string,
        ?string $var_id,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
    ): Union {
        $existing_var_type = $existing_var_type->getBuilder();
        // no integer is less than PHP_INT_MIN, and removing 1 from it would give a float
        $is_unreachable = $assertion instanceof IsLessThan && $assertion->value === PHP_INT_MIN;
        $max_bound = $assertion instanceof IsLessThan && !$is_unreachable
            ? $assertion->value - 1
            : $assertion->value;

        $redundant = true;

        if ($assertion->doesFilterNullOrFalse() &&
            ($existing_var_type->hasType('null') || $existing_var_type->hasType('false'))
        ) {
            $redundant = false;
            $existing_var_type->removeType('null');
            $existing_var_type->removeType('false');
        }

        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($is_unreachable && $atomic_type instanceof TInt) {
                $redundant = false;
                $existing_var_type->removeType($atomic_type->getKey());
            } elseif ($atomic_type instanceof TIntRange) {
                if ($atomic_type->max_bound !== null && $atomic_type->max_bound <= $max_bound) {
                    // if the whole range is within the bound, the check is redundant
                } elseif ($atomic_type->contains($max_bound)) {
                    // if the range contains the bound, the range must be adapted
                    $redundant = false;
                    $existing_var_type->removeType($atomic_type->getKey());
                    $existing_var_type->addType(self::getNarrowedIntRange(
                        $atomic_type->min_bound,
                        $atomic_type->max_bound === null ? $max_bound : min($atomic_type->max_bound, $max_bound),
                    ));
                } elseif ($atomic_type->isGreaterThan($max_bound)) {
                    // if the range is above the bound, the type must be removed
                    $redundant = false;
                    $existing_var_type->removeType($atomic_type->getKey());
                }
            } elseif ($atomic_type instanceof TLiteralInt) {
                if ($atomic_type->value > $max_bound) {
                    $redundant = false;
                    $existing_var_type->removeType($atomic_type->getKey());
                }
            } elseif ($atomic_type instanceof TInt) {
                $redundant = false;
                $existing_var_type->removeType($atomic_type->getKey());
                $existing_var_type->addType(new TIntRange(null, $max_bound));
            } elseif ($atomic_type instanceof TTemplateParam) {
                $redundant = false;
                self::narrowTemplateBound(
                    $existing_var_type,
                    $atomic_type,
                    static fn(Union $bound): Union => self::reconcileMaxBound(
                        $assertion,
                        $bound,
                        $inside_loop,
                        $old_var_type_string,
                        null,
                        $negated,
                        null,
                        $suppressed_issues,
                    ),
                );
            } else {
                // we assume that other types may have been removed (empty strings? numeric strings?)
                //It may be worth refining to improve reconciliation while keeping in mind we're on loose comparison
                $redundant = false;
            }
        }

        return self::finish(
            $existing_var_type,
            $redundant,
            $assertion,
            $inside_loop,
            $old_var_type_string,
            $var_id,
            $negated,
            $code_location,
            $suppressed_issues,
        );
    }

    /**
     * The range a comparison narrowed an integer range to: the value itself when a single one is left
     *
     * @psalm-pure
     */
    private static function getNarrowedIntRange(?int $min_bound, ?int $max_bound): TIntRange|TLiteralInt
    {
        return $min_bound !== null && $min_bound === $max_bound
            ? new TLiteralInt($min_bound)
            : new TIntRange($min_bound, $max_bound);
    }

    /**
     * Reports a redundant comparison, or one that leaves no type, and freezes the narrowed type
     *
     * @param string[] $suppressed_issues
     */
    private static function finish(
        MutableUnion $existing_var_type,
        bool $redundant,
        IsGreaterThan|IsGreaterThanOrEqualTo|IsLessThan|IsLessThanOrEqualTo $assertion,
        bool $inside_loop,
        string $old_var_type_string,
        ?string $var_id,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
    ): Union {
        if (!$inside_loop && $redundant && $var_id !== null && $code_location) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $old_var_type_string,
                $var_id,
                $assertion,
                true,
                $negated,
                $code_location,
                $suppressed_issues,
            );
        }

        if ($existing_var_type->isUnionEmpty()) {
            if ($var_id !== null && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $var_id,
                    $assertion,
                    false,
                    $negated,
                    $code_location,
                    $suppressed_issues,
                );
            }
            $existing_var_type->addType(new TNever());
        }

        return $existing_var_type->freeze();
    }
}
