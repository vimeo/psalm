<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\DoesNotHaveAtLeastCount;
use Psalm\Storage\Assertion\Empty_;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\HasAtLeastCount;
use Psalm\Storage\Assertion\InArray;
use Psalm\Storage\Assertion\IsEqualIsset;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Storage\Assertion\IsNotIsset;
use Psalm\Storage\Assertion\NonEmpty;
use Psalm\Storage\Assertion\NonEmptyCountable;
use Psalm\Storage\Assertion\NotInArray;
use Psalm\Storage\Assertion\NotNonEmptyCountable;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TString;
use Psalm\Type\MutableUnion;
use Psalm\Type\Union;

/**
 * A docblock type narrowing a native type of the same kind (`list<int>` for `array`, `non-empty-string` for
 * `string`) is trusted for the kind, which PHP checks, but not for the refinement: a condition that is only redundant
 * or impossible because of the refinement is reported with the docblock issue kinds.
 *
 * @internal
 */
final class DocblockRefinement
{
    private static ?Union $string_kind = null;

    private static ?Union $int_kind = null;

    private static ?Union $iterable_kind = null;

    private static ?Union $array_kind = null;

    /**
     * Called by the scanners on a docblock atomic type they just created, whose key a signature atomic type shares
     *
     * @psalm-suppress InaccessibleProperty The scanners pass types they just created
     */
    public static function markGuaranteedBy(Atomic $type, Atomic $signature_type): void
    {
        $type->from_docblock = false;

        if (($signature_type instanceof TString
                || $signature_type instanceof TInt
                || $signature_type instanceof TArray
                || $signature_type instanceof TIterable)
            && $type->getId() !== $signature_type->getId()
        ) {
            $type->refined_by_docblock = true;
        }
    }

    /**
     * @psalm-pure
     */
    public static function hasRefinement(Union|MutableUnion $type): bool
    {
        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type->refined_by_docblock) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether $type is contained by $other (or disjoint from it, when $contained is false) only because of its
     * docblock refinements
     */
    public static function isNeededFor(
        Codebase $codebase,
        Union|MutableUnion $type,
        Union $other,
        bool $contained,
    ): bool {
        foreach ($type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type->refined_by_docblock) {
                continue;
            }

            $kind = self::getKind($atomic_type);

            if ($contained
                ? !UnionTypeComparator::isContainedBy($codebase, $kind, $other)
                : UnionTypeComparator::canExpressionTypesBeIdentical($codebase, $kind, $other)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether $assertion is redundant (or impossible) for $type only because of its docblock refinements
     */
    public static function isNeededForAssertion(Union|MutableUnion $type, Assertion $assertion, bool $redundant): bool
    {
        if (!self::hasRefinement($type)) {
            return false;
        }

        // these are never decided by the array, string, int or iterable kind alone
        if ($assertion instanceof Truthy
            || $assertion instanceof Falsy
            || $assertion instanceof Empty_
            || $assertion instanceof NonEmpty
            || $assertion instanceof NonEmptyCountable
            || $assertion instanceof NotNonEmptyCountable
            || $assertion instanceof HasAtLeastCount
            || $assertion instanceof DoesNotHaveAtLeastCount
        ) {
            return true;
        }

        if ($assertion instanceof InArray || $assertion instanceof NotInArray) {
            // both are reported when the value can't be in the array, whichever way they flag redundancy
            $other = $assertion->type;
            $contained = false;
        } elseif ($atomic_assertion_type = $assertion->getAtomicType()) {
            $other = new Union([$atomic_assertion_type]);
            $contained = $redundant !== $assertion->isNegation();
        } else {
            return false;
        }

        // Reconciler::triggerIssueForImpossible(), which calls this, is protected API: its signature can't take a
        // codebase
        return self::isNeededFor(ProjectAnalyzer::getInstance()->getCodebase(), $type, $other, $contained);
    }

    /**
     * Drops the refinements reconciliation may have established in code: those of every atomic type when an
     * assertion is not decided by the kinds alone, otherwise those of the atomic types it changed
     *
     * @param array<array<Assertion>> $assertions
     */
    public static function keepAfterReconciliation(
        Codebase $codebase,
        Union $before,
        Union $after,
        array $assertions,
    ): Union {
        if (!self::hasRefinement($after)) {
            return $after;
        }

        $decided_by_kinds = self::areDecidedByKinds($codebase, $after, $assertions);

        if ($decided_by_kinds && $before === $after) {
            return $after;
        }

        $before_types = $before->getAtomicTypes();
        $types = $after->getAtomicTypes();
        $changed = false;

        foreach ($types as $key => $type) {
            if ($type->refined_by_docblock
                && (!$decided_by_kinds || ($before_types[$key] ?? null) !== $type)
            ) {
                $types[$key] = $type->setRefinedByDocblock(false);
                $changed = true;
            }
        }

        return $changed ? $after->setTypes($types) : $after;
    }

    /**
     * @psalm-pure
     */
    public static function withoutRefinements(Union $type): Union
    {
        if (!self::hasRefinement($type)) {
            return $type;
        }

        $types = [];

        foreach ($type->getAtomicTypes() as $key => $atomic_type) {
            $types[$key] = $atomic_type->setRefinedByDocblock(false);
        }

        return $type->setTypes($types);
    }

    /**
     * @param array<array<Assertion>> $assertions
     */
    private static function areDecidedByKinds(Codebase $codebase, Union $type, array $assertions): bool
    {
        foreach ($assertions as $assertion_clause) {
            foreach ($assertion_clause as $assertion) {
                if ($assertion instanceof IsIsset
                    || $assertion instanceof IsNotIsset
                    || $assertion instanceof IsEqualIsset
                ) {
                    continue;
                }

                $atomic_assertion_type = $assertion->getAtomicType();

                if (!$atomic_assertion_type) {
                    return false;
                }

                $asserted_type = new Union([$atomic_assertion_type]);

                foreach ($type->getAtomicTypes() as $atomic_type) {
                    if (!$atomic_type->refined_by_docblock) {
                        continue;
                    }

                    $kind = self::getKind($atomic_type);

                    if (!UnionTypeComparator::isContainedBy($codebase, $kind, $asserted_type)
                        && UnionTypeComparator::canExpressionTypesBeIdentical($codebase, $kind, $asserted_type)
                    ) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * The native type of the refined atomic type's key: markGuaranteedBy() only marks refinements of `string`,
     * `int`, `array` and `iterable`
     */
    private static function getKind(Atomic $type): Union
    {
        return match (true) {
            $type instanceof TString => self::$string_kind ??= Type::getString(),
            $type instanceof TInt => self::$int_kind ??= Type::getInt(),
            $type instanceof TIterable => self::$iterable_kind ??= new Union([new TIterable()]),
            default => self::$array_kind ??= Type::getArray(),
        };
    }
}
