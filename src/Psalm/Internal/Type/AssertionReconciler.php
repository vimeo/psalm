<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\CoalesceAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\TypeDoesNotContainNull;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\ArrayKeyExists;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\HasAtLeastCount;
use Psalm\Storage\Assertion\HasExactCount;
use Psalm\Storage\Assertion\IsAClass;
use Psalm\Storage\Assertion\IsClassEqual;
use Psalm\Storage\Assertion\IsEqualIsset;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Storage\Assertion\IsLooselyEqual;
use Psalm\Storage\Assertion\IsNotIdentical;
use Psalm\Storage\Assertion\IsNotLooselyEqual;
use Psalm\Storage\Assertion\NestedAssertions;
use Psalm\Storage\Assertion\NonEmpty;
use Psalm\Storage\Assertion\NonEmptyCountable;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Storage\Capabilities;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TDecimalIntString;
use Psalm\Type\Atomic\TEmptyNumeric;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonDecimalIntString;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\Atomic\TTraitString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Atomic\TValueOf;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_diff_key;
use function array_filter;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_search;
use function array_values;
use function count;
use function is_nan;
use function is_numeric;
use function is_string;
use function reset;
use function rtrim;
use function strtolower;

/**
 * @internal
 */
final class AssertionReconciler extends Reconciler
{
    /**
     * Reconciles types
     *
     * think of this as a set of functions e.g. empty(T), notEmpty(T), null(T), notNull(T) etc. where
     *  - empty(Object) => null,
     *  - empty(bool) => false,
     *  - notEmpty(Object|null) => Object,
     *  - notEmpty(Object|false) => Object
     *
     * @param   string[]            $suppressed_issues
     * @param   array<string, array<string, Union>> $template_type_map
     * @param-out Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    public static function reconcile(
        Assertion $assertion,
        ?Union $existing_var_type,
        ?string $key,
        StatementsAnalyzer $statements_analyzer,
        bool $inside_loop,
        array $template_type_map,
        ?CodeLocation $code_location = null,
        array $suppressed_issues = [],
        ?int &$failed_reconciliation = Reconciler::RECONCILIATION_OK,
        bool $negated = false,
    ): Union {
        $codebase = $statements_analyzer->getCodebase();

        $failed_reconciliation = Reconciler::RECONCILIATION_OK;

        $is_negation = $assertion->isNegation();

        if ($assertion instanceof NestedAssertions) {
            $assertion = new Falsy();
            $is_negation = true;
        }

        if ($existing_var_type === null
            && is_string($key)
            && VariableFetchAnalyzer::isSuperGlobal($key)
        ) {
            $existing_var_type = VariableFetchAnalyzer::getGlobalType($key, $codebase->analysis_php_version_id);
        }

        if ($existing_var_type === null) {
            return self::getMissingType(
                $assertion,
                $inside_loop,
            );
        }

        // `$a !== $b` narrows only where it is false: where it is true, the operands may hold what they share
        if ($assertion instanceof IsNotIdentical && $assertion->of_operands) {
            return $existing_var_type;
        }

        $old_var_type_string = $existing_var_type->getId();

        $type_variables = [];

        foreach ($existing_var_type->getAtomicTypes() as $existing_atomic) {
            if ($existing_atomic instanceof TTypeVariable) {
                $type_variables[] = $existing_atomic;
            }
        }

        if ($type_variables) {
            // A type variable's constraints reconcile at the end of the
            // surrounding function-like: asserting a simple type on it
            // records that type as a lower bound and keeps the variable
            // alive, rather than narrowing it away (which would report
            // spurious contradictions). Other assertions (like instanceof
            // checks, which can simply be false at runtime) leave its bounds
            // alone.
            if (!$is_negation
                && ($assertion_atomic_type = $assertion->getAtomicType())
                && $assertion_atomic_type instanceof Scalar
            ) {
                foreach ($type_variables as $type_variable) {
                    $statements_analyzer->type_variable_tracker->addBounds(
                        [[$type_variable->name, new TemplateBound(new Union([$assertion_atomic_type]))]],
                        [],
                        $code_location,
                    );
                }
            }

            return new Union($type_variables);
        }

        $template_key_or_value_of_bounds = [];
        $expanded_var_type = $existing_var_type->getBuilder();

        foreach ($existing_var_type->getAtomicTypes() as $atomic_key => $existing_atomic) {
            if (!$existing_atomic instanceof TTemplateKeyOf && !$existing_atomic instanceof TTemplateValueOf) {
                continue;
            }

            $bound = $existing_atomic instanceof TTemplateKeyOf
                ? TKeyOf::getArrayKeyType($existing_atomic->as)
                : TValueOf::getValueType($existing_atomic->as, $codebase);

            if ($bound === null) {
                continue;
            }

            $template_key_or_value_of_bounds[$atomic_key] = [$existing_atomic, $bound];
            $expanded_var_type->removeType($atomic_key);

            foreach ($bound->getAtomicTypes() as $bound_atomic) {
                $expanded_var_type->addType($bound_atomic);
            }
        }

        if ($template_key_or_value_of_bounds) {
            $expanded_var_type = $expanded_var_type->freeze();

            $reconciled_type = self::reconcile(
                $assertion,
                $expanded_var_type,
                $key,
                $statements_analyzer,
                $inside_loop,
                $template_type_map,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $negated,
            );

            return self::restoreTemplateKeyOrValueOf(
                $codebase,
                $existing_var_type,
                $expanded_var_type,
                $reconciled_type,
                $template_key_or_value_of_bounds,
            );
        }

        if (($assertion instanceof IsLooselyEqual || $assertion instanceof IsNotLooselyEqual)
            && $assertion->type instanceof TNull
        ) {
            return self::reconcileLooseNullComparison(
                $assertion,
                $existing_var_type,
                $key,
                $statements_analyzer,
                $inside_loop,
                $template_type_map,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $negated,
            );
        }

        if ($is_negation) {
            return NegatedAssertionReconciler::reconcile(
                $statements_analyzer,
                $assertion,
                $existing_var_type,
                $old_var_type_string,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $inside_loop,
            );
        }

        $assertion_type = $assertion->getAtomicType();

        if ($assertion_type instanceof TLiteralInt
            || $assertion_type instanceof TLiteralString
            || $assertion_type instanceof TLiteralFloat
            || $assertion_type instanceof TEnumCase
        ) {
            return self::handleLiteralEquality(
                $statements_analyzer,
                $assertion,
                $assertion_type,
                $existing_var_type,
                $old_var_type_string,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
            );
        }

        if ($assertion instanceof IsAClass) {
            $should_return = false;

            $new_type_parts = self::handleIsA(
                $assertion,
                $codebase,
                $existing_var_type,
                $code_location,
                $key,
                $suppressed_issues,
                $should_return,
            );

            if ($should_return) {
                return new Union($new_type_parts);
            }

            $new_type_part = $new_type_parts[0];
        } else {
            $simply_reconciled_type = SimpleAssertionReconciler::reconcile(
                $assertion,
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $inside_loop,
            );

            if ($simply_reconciled_type) {
                return $simply_reconciled_type;
            }

            if ($assertion instanceof IsClassEqual) {
                $new_type_part = Atomic::create($assertion->type, null, $template_type_map);
            } elseif ($assertion_type = $assertion->getAtomicType()) {
                $new_type_part = $assertion_type;
            } else {
                $new_type_part = new TMixed();
            }
        }

        if ($existing_var_type->hasMixed()) {
            if ($assertion instanceof IsLooselyEqual
                && $new_type_part instanceof Scalar
            ) {
                return $existing_var_type;
            }

            return new Union([$new_type_part]);
        }

        $refined_type = self::refine(
            $statements_analyzer,
            $assertion,
            $new_type_part,
            $existing_var_type,
            $key,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
        );

        return TypeExpander::expandUnion(
            $codebase,
            $refined_type,
            null,
            null,
            null,
            true,
            false,
            false,
            true,
        );
    }

    /**
     * The types a part of the bound of a key-of<T> or value-of<T> stands for: the key-of<T> or value-of<T> itself when
     * the part is the whole bound, otherwise T with its bound narrowed to the part
     *
     * @param non-empty-list<Atomic> $narrowed_atomics the atomics of the bound the type is narrowed to
     * @return non-empty-list<Atomic>
     */
    private static function getTemplateKeyOrValueOfAtomics(
        Codebase $codebase,
        TTemplateKeyOf|TTemplateValueOf $template_of,
        Union $bound,
        array $narrowed_atomics,
    ): array {
        $narrowed_bound = new Union($narrowed_atomics);

        if ($narrowed_bound->getId() === $bound->getId()) {
            return [$template_of];
        }

        $template = $template_of->as->isSingle() ? $template_of->as->getSingleAtomic() : null;
        $key_type = TKeyOf::getArrayKeyType($template_of->as);
        $value_type = TValueOf::getValueType($template_of->as, $codebase);

        if (!$template instanceof TTemplateParam || $key_type === null || $value_type === null) {
            return $narrowed_atomics;
        }

        $narrowed_template = new Union([$template->replaceAs(new Union([new TArray(
            $template_of instanceof TTemplateKeyOf
                ? [$narrowed_bound, $value_type]
                : [$key_type, $narrowed_bound],
        )]))]);

        return [
            $template_of instanceof TTemplateKeyOf
                ? new TTemplateKeyOf(
                    $template_of->param_name,
                    $template_of->defining_class,
                    $narrowed_template,
                    $template_of->from_docblock,
                )
                : new TTemplateValueOf(
                    $template_of->param_name,
                    $template_of->defining_class,
                    $narrowed_template,
                    $template_of->from_docblock,
                ),
        ];
    }

    /**
     * key-of<T> and value-of<T> are reconciled as their bound, then the part of the result within that bound
     * becomes key-of<T> or value-of<T> again, with T narrowed to it
     *
     * @param non-empty-array<string, array{TTemplateKeyOf|TTemplateValueOf, Union}> $template_key_or_value_of_bounds
     */
    private static function restoreTemplateKeyOrValueOf(
        Codebase $codebase,
        Union $existing_var_type,
        Union $expanded_var_type,
        Union $reconciled_type,
        array $template_key_or_value_of_bounds,
    ): Union {
        if ($reconciled_type->isNever() || $reconciled_type->failed_reconciliation) {
            return $reconciled_type;
        }

        if ($reconciled_type->getId() === $expanded_var_type->getId()) {
            return $existing_var_type;
        }

        $new_type = $reconciled_type->getBuilder();
        $other_atomics = array_diff_key(
            $existing_var_type->getAtomicTypes(),
            $template_key_or_value_of_bounds,
        );

        foreach ($reconciled_type->getAtomicTypes() as $reconciled_key => $reconciled_atomic) {
            $in_bound = false;

            foreach ($template_key_or_value_of_bounds as [, $bound]) {
                if (UnionTypeComparator::isContainedBy($codebase, new Union([$reconciled_atomic]), $bound)) {
                    $in_bound = true;
                    break;
                }
            }

            if (!$in_bound) {
                continue;
            }

            $in_other_type = false;

            foreach ($other_atomics as $other_atomic) {
                if (AtomicTypeComparator::isContainedBy($codebase, $reconciled_atomic, $other_atomic)) {
                    $in_other_type = true;
                    break;
                }
            }

            if (!$in_other_type) {
                $new_type->removeType($reconciled_key);
            }
        }

        foreach ($template_key_or_value_of_bounds as [$template_of, $bound]) {
            $narrowed_atomics = [];

            foreach ($reconciled_type->getAtomicTypes() as $reconciled_atomic) {
                if (UnionTypeComparator::isContainedBy($codebase, new Union([$reconciled_atomic]), $bound)) {
                    $narrowed_atomics[] = $reconciled_atomic;
                }
            }

            if (!$narrowed_atomics) {
                continue;
            }

            $template_atomics = self::getTemplateKeyOrValueOfAtomics(
                $codebase,
                $template_of,
                $bound,
                $narrowed_atomics,
            );

            foreach ($template_atomics as $atomic) {
                $new_type->addType($atomic);
            }
        }

        return $new_type->freeze();
    }

    /**
     * `$x == null` is `!$x`, except that null is compared to a string as '': the falsy '0' is still != null
     *
     * @param   string[]            $suppressed_issues
     * @param   array<string, array<string, Union>> $template_type_map
     * @param-out Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileLooseNullComparison(
        IsLooselyEqual|IsNotLooselyEqual $assertion,
        Union $existing_var_type,
        ?string $key,
        StatementsAnalyzer $statements_analyzer,
        bool $inside_loop,
        array $template_type_map,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        ?int &$failed_reconciliation,
        bool $negated,
    ): Union {
        $codebase = $statements_analyzer->getCodebase();
        $zero = TLiteralString::make('0');
        $zero_types = self::getZeroStringTypes($codebase, $existing_var_type, $zero);

        if (!$zero_types) {
            return self::reconcile(
                $assertion instanceof IsLooselyEqual ? new Falsy() : new Truthy(),
                $existing_var_type,
                $key,
                $statements_analyzer,
                $inside_loop,
                $template_type_map,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $negated,
            );
        }

        // the truthiness check is done without issues, they would be wrong about '0'
        if ($assertion instanceof IsLooselyEqual) {
            $result = self::reconcile(
                new IsNotIdentical($zero),
                self::reconcile(
                    new Falsy(),
                    $existing_var_type,
                    $key,
                    $statements_analyzer,
                    $inside_loop,
                    $template_type_map,
                ),
                $key,
                $statements_analyzer,
                $inside_loop,
                $template_type_map,
            );
        } else {
            $result = Type::combineUnionTypes(
                self::reconcile(
                    new Truthy(),
                    $existing_var_type,
                    $key,
                    $statements_analyzer,
                    $inside_loop,
                    $template_type_map,
                ),
                new Union($zero_types),
                $codebase,
            );
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_OK;

        $impossible = $result->isNever() || $result->isUnionEmpty();

        if ($impossible) {
            $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;
        }

        if ($key !== null
            && $code_location
            && ($impossible || $result->getId() === $existing_var_type->getId())
        ) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $existing_var_type->getId(),
                $key,
                $assertion,
                !$impossible,
                $negated,
                $code_location,
                $suppressed_issues,
            );
        }

        return $result;
    }

    /**
     * The parts of $type that can be '0'. A mixed is left out: it stays non-empty-mixed rather than becoming
     * '0'|non-empty-mixed
     *
     * @return list<Atomic>
     */
    private static function getZeroStringTypes(Codebase $codebase, Union $type, TLiteralString $zero): array
    {
        $zero_types = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TTemplateParam) {
                $as_zero_types = self::getZeroStringTypes($codebase, $atomic_type->as, $zero);

                if ($as_zero_types) {
                    $zero_types[] = $atomic_type->replaceAs(new Union($as_zero_types));
                }
            } elseif (!$atomic_type instanceof TMixed
                && AtomicTypeComparator::isContainedBy($codebase, $zero, $atomic_type)
            ) {
                $zero_types[] = $zero;
            }
        }

        return $zero_types;
    }

    /**
     * @psalm-pure
     */
    private static function getMissingType(
        Assertion $assertion,
        bool $inside_loop,
    ): Union {
        if ($assertion instanceof NonEmpty) {
            return new Union([new TNonEmptyMixed($inside_loop)]);
        }

        if ($assertion instanceof IsIsset || $assertion instanceof IsEqualIsset) {
            return Type::getMixed($inside_loop);
        }

        if ($assertion instanceof ArrayKeyExists
            || $assertion instanceof NonEmptyCountable
            || $assertion instanceof HasExactCount
            || $assertion instanceof HasAtLeastCount
        ) {
            return Type::getMixed();
        }

        // false, 0, '' and [] are loosely null too
        if ($assertion instanceof IsLooselyEqual && $assertion->type instanceof TNull) {
            return Type::getMixed($inside_loop);
        }

        if (!$assertion->isNegation()) {
            $assertion_type = $assertion->getAtomicType();

            if ($assertion_type) {
                return new Union([$assertion_type]);
            }
        }

        return Type::getMixed();
    }

    /**
     * This method is called when SimpleAssertionReconciler was not enough. It receives the existing type, the assertion
     * and also a new type created from the assertion string.
     *
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     * @param   string[]    $suppressed_issues
     * @param-out Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function refine(
        StatementsAnalyzer $statements_analyzer,
        Assertion $assertion,
        Atomic $new_type_part,
        Union &$existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
    ): Union {
        $codebase = $statements_analyzer->getCodebase();

        $old_var_type_string = $existing_var_type->getId();

        if ($new_type_part instanceof TMixed) {
            return $existing_var_type;
        }

        $new_type_has_interface = false;

        if ($new_type_part->isObjectType()) {
            if ($new_type_part instanceof TNamedObject &&
                $codebase->interfaceExists($new_type_part->value)
            ) {
                $new_type_has_interface = true;
            }
        }

        $old_type_has_interface = false;

        if ($existing_var_type->hasObjectType()) {
            foreach ($existing_var_type->getAtomicTypes() as $existing_type_part) {
                if ($existing_type_part instanceof TNamedObject &&
                    $codebase->interfaceExists($existing_type_part->value)
                ) {
                    $old_type_has_interface = true;
                    break;
                }
            }
        }

        if ($new_type_part instanceof TTemplateParam
            && $new_type_part->as->isSingle()
        ) {
            $new_as_atomic = $new_type_part->as->getSingleAtomic();

            $acceptable_atomic_types = [];
            $narrowed = false;

            foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                if ($existing_var_type_part instanceof TNamedObject
                    || $existing_var_type_part instanceof TTemplateParam
                ) {
                    $acceptable_atomic_types[] = $existing_var_type_part;
                } elseif (AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $existing_var_type_part,
                    $new_as_atomic,
                )) {
                    $acceptable_atomic_types[] = $existing_var_type_part;
                } elseif (($overlap = Type::intersectUnionTypes(
                    new Union([$existing_var_type_part]),
                    new Union([$new_as_atomic]),
                    $codebase,
                )) !== null
                    && !$overlap->isNever()
                ) {
                    // a string can be the value of a template of `numeric`: a numeric string
                    $narrowed = true;

                    foreach ($overlap->getAtomicTypes() as $overlap_atomic_type) {
                        $acceptable_atomic_types[] = $overlap_atomic_type;
                    }
                }
            }

            if ($acceptable_atomic_types) {
                $acceptable_atomic_types =
                    !$narrowed && count($acceptable_atomic_types) === count($existing_var_type->getAtomicTypes())
                        ? $existing_var_type
                        : new Union($acceptable_atomic_types);
                return new Union([$new_type_part->replaceAs($acceptable_atomic_types)]);
            }
        }

        if ($new_type_part instanceof TKeyedArray) {
            $acceptable_atomic_types = [];

            foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                if ($existing_var_type_part instanceof TKeyedArray) {
                    if (!array_intersect_key(
                        $existing_var_type_part->properties,
                        $new_type_part->properties,
                    )) {
                        $acceptable_atomic_types[] = $existing_var_type_part->setProperties(array_merge(
                            $existing_var_type_part->properties,
                            $new_type_part->properties,
                        ));
                    }
                }
            }

            if ($acceptable_atomic_types) {
                return new Union($acceptable_atomic_types);
            }
        }

        $new_type = null;

        if ($new_type_part instanceof TNamedObject
            && ($new_type_has_interface || $old_type_has_interface)
            && !UnionTypeComparator::canExpressionTypesBeIdentical(
                $codebase,
                new Union([$new_type_part]),
                $existing_var_type,
                false,
            )
        ) {
            $acceptable_atomic_types = [];

            foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                if (AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $existing_var_type_part,
                    $new_type_part,
                )) {
                    $acceptable_atomic_types[] = $existing_var_type_part;
                    continue;
                }

                if ($existing_var_type_part instanceof TNamedObject
                    && ($codebase->classExists($existing_var_type_part->value)
                        || $codebase->interfaceExists($existing_var_type_part->value))
                    && self::canBeIntersected($codebase, $existing_var_type_part, $new_type_part)
                ) {
                    $intersected_type_part = $new_type_part;

                    if ($existing_var_type_part instanceof TGenericObject
                        && !$new_type_part instanceof TGenericObject
                    ) {
                        $intersected_type_part = self::inferTemplateParamsFromParent(
                            $codebase,
                            $new_type_part,
                            $existing_var_type_part,
                        );
                    }

                    $existing_var_type_part = $existing_var_type_part->addIntersectionType($intersected_type_part);
                    $acceptable_atomic_types[] = $existing_var_type_part;
                }

                if ($existing_var_type_part instanceof TTemplateParam) {
                    $existing_var_type_part = $existing_var_type_part->addIntersectionType($new_type_part);
                    $acceptable_atomic_types[] = $existing_var_type_part;
                }
            }

            if ($acceptable_atomic_types) {
                return new Union($acceptable_atomic_types);
            }
        } elseif (!$new_type_part instanceof TMixed) {
            $any_scalar_type_match_found = false;

            $redundant_comparison_result = new TypeComparisonResult();

            if ($code_location
                && $key
                && !$assertion->hasEquality()
                && $new_type_part instanceof TNamedObject
                && (!($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
                    || $key !== '$this')
                && UnionTypeComparator::isContainedBy(
                    $codebase,
                    $existing_var_type,
                    new Union([$new_type_part]),
                    false,
                    false,
                    $redundant_comparison_result,
                    false,
                    false,
                )
                // a containment that recorded type-variable bounds is
                // provisional (the variable's constraints reconcile at the
                // end of the function-like), so it cannot prove the
                // assertion redundant
                && !$redundant_comparison_result->type_variable_lower_bounds
                && !$redundant_comparison_result->type_variable_upper_bounds
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues,
                );
            }

            $intersection_type = self::filterTypeWithAnother(
                $codebase,
                $existing_var_type,
                new Union([$new_type_part]),
                $any_scalar_type_match_found,
            );

            // a key or a value of the bound of T can be one of key-of<T> or value-of<T>
            if (!$intersection_type
                && ($new_type_part instanceof TTemplateKeyOf || $new_type_part instanceof TTemplateValueOf)
                && ($bound = $new_type_part instanceof TTemplateKeyOf
                    ? TKeyOf::getArrayKeyType($new_type_part->as)
                    : TValueOf::getValueType($new_type_part->as, $codebase)) !== null
                && ($overlap = Type::intersectUnionTypes($existing_var_type, $bound, $codebase)) !== null
                && !$overlap->isNever()
            ) {
                // the narrowed variable keeps its link to T
                $intersection_type = new Union(self::getTemplateKeyOrValueOfAtomics(
                    $codebase,
                    $new_type_part,
                    $bound,
                    array_values($overlap->getAtomicTypes()),
                ));
            }

            if ($code_location
                && !$intersection_type
                && (!$assertion instanceof IsLooselyEqual || !$any_scalar_type_match_found)
            ) {
                if ($new_type_part instanceof TNull) {
                    if ($existing_var_type->from_docblock) {
                        IssueBuffer::maybeAdd(
                            new DocblockTypeContradiction(
                                'Cannot resolve types for '
                                    . CoalesceAnalyzer::getDisplayName($key, $code_location)
                                    . ' - docblock-defined type '
                                    . $existing_var_type . ' does not contain null',
                                $code_location,
                                $existing_var_type->getId() . ' null',
                            ),
                            $suppressed_issues,
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new TypeDoesNotContainNull(
                                'Cannot resolve types for '
                                    . CoalesceAnalyzer::getDisplayName($key, $code_location)
                                    . ' - ' . $existing_var_type
                                    . ' does not contain null',
                                $code_location,
                                $existing_var_type->getId(),
                            ),
                            $suppressed_issues,
                        );
                    }
                } elseif (!($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
                    || ($key !== '$this'
                        && !($existing_var_type->hasLiteralClassString()
                            && $assertion instanceof IsAClass))
                ) {
                    if ($existing_var_type->from_docblock) {
                        IssueBuffer::maybeAdd(
                            new DocblockTypeContradiction(
                                'Cannot resolve types for '
                                    . CoalesceAnalyzer::getDisplayName($key, $code_location)
                                    . ' - docblock-defined type '
                                    . $existing_var_type->getId() . ' does not contain ' . $new_type_part->getId(),
                                $code_location,
                                $existing_var_type->getId() . ' ' . $new_type_part->getId(),
                            ),
                            $suppressed_issues,
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new TypeDoesNotContainType(
                                'Cannot resolve types for '
                                    . CoalesceAnalyzer::getDisplayName($key, $code_location)
                                    . ' - ' . $existing_var_type->getId()
                                    . ' does not contain ' . $new_type_part->getId(),
                                $code_location,
                                $existing_var_type->getId() . ' ' . $new_type_part->getId(),
                            ),
                            $suppressed_issues,
                        );
                    }
                }

                $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;
            }

            if ($intersection_type) {
                $new_type = $intersection_type;
            } elseif ($new_type_part instanceof TNull) {
                $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

                return Type::getNever();
            }
        }

        return $new_type ?: new Union([$new_type_part]);
    }

    /**
     * This method receives two types. The goal is to use data in the new type to reduce the existing_type to a more
     * precise version. For example: new is `array<int>` old is `list<mixed>` so the result is `list<int>`
     */
    private static function filterTypeWithAnother(
        Codebase $codebase,
        Union &$existing_type,
        Union $new_type,
        bool &$any_scalar_type_match_found = false,
    ): ?Union {
        $matching_atomic_types = [];

        $existing_types = $existing_type->getAtomicTypes();
        foreach ($new_type->getAtomicTypes() as $new_type_part) {
            foreach ($existing_types as &$existing_type_part) {
                $matching_atomic_type = self::filterAtomicWithAnother(
                    $existing_type_part,
                    $new_type_part,
                    $codebase,
                    $any_scalar_type_match_found,
                );

                if ($matching_atomic_type) {
                    $matching_atomic_types[] = $matching_atomic_type;
                } else {
                    $matching_atomic_types = [
                        ...$matching_atomic_types,
                        ...self::filterAtomicPartsWithAnother(
                            $existing_type_part,
                            $new_type_part,
                            $codebase,
                            $any_scalar_type_match_found,
                        ),
                    ];
                }
            }
            unset($existing_type_part);
        }
        $existing_type = $existing_type->setTypes($existing_types);

        if ($matching_atomic_types) {
            return new Union($matching_atomic_types);
        }

        return null;
    }

    /**
     * The matching types of the parts of two types neither of which contains the other: e.g. callable filtered with
     * string is a callable-string, A&I filtered with a subclass B of A is B&I
     *
     * @return list<Atomic>
     */
    private static function filterAtomicPartsWithAnother(
        Atomic $type_1_atomic,
        Atomic $type_2_atomic,
        Codebase $codebase,
        bool &$any_scalar_type_match_found,
    ): array {
        $type_1_parts = AtomicParts::split($type_1_atomic, $type_2_atomic, $codebase);
        $type_2_parts = $type_1_parts === null ? AtomicParts::split($type_2_atomic, $type_1_atomic, $codebase) : null;

        if ($type_1_parts === null && $type_2_parts === null) {
            $refined_type = self::refineWithIntersectionParts(
                $type_1_atomic,
                $type_2_atomic,
                $codebase,
                $any_scalar_type_match_found,
            );

            return $refined_type ? [$refined_type] : [];
        }

        $matching_atomic_types = [];

        foreach ($type_1_parts ?? [$type_1_atomic] as $type_1_part) {
            foreach ($type_2_parts ?? [$type_2_atomic] as $type_2_part) {
                $matching_atomic_type = self::filterAtomicWithAnother(
                    $type_1_part,
                    $type_2_part,
                    $codebase,
                    $any_scalar_type_match_found,
                );

                $matching_atomic_types = [
                    ...$matching_atomic_types,
                    ...($matching_atomic_type !== null
                        ? [$matching_atomic_type]
                        : self::filterAtomicPartsWithAnother(
                            $type_1_part,
                            $type_2_part,
                            $codebase,
                            $any_scalar_type_match_found,
                        )),
                ];
            }
        }

        return $matching_atomic_types;
    }

    /**
     * Narrowing a `Parent<A, B>` to a child class whose templates are passed straight to Parent's
     * (`@extends Parent<T, U>`, `@implements Iterator[P]<K, V>`) gives the child those arguments:
     * `Iterator[pure]<int, string>` narrowed to `Child` is a `Child[pure]<int, string>`. With
     * $unknown_as_star, the other type templates are `*` rather than leaving the child without arguments.
     */
    private static function inferTemplateParamsFromParent(
        Codebase $codebase,
        TNamedObject $child,
        TGenericObject $parent,
        bool $unknown_as_star = false,
    ): TNamedObject {
        if (!$codebase->classlike_storage_provider->has($child->value)
            || !$codebase->classlike_storage_provider->has($parent->value)
        ) {
            return $child;
        }

        $child_storage = $codebase->classlike_storage_provider->get($child->value);
        $parent_storage = $codebase->classlike_storage_provider->get($parent->value);

        $extended_params = $child_storage->template_extended_params[$parent_storage->name] ?? null;

        if ($child_storage->template_types === null
            || $child_storage->template_types === []
            || $parent_storage->template_types === null
            || $parent_storage->template_types === []
            || $extended_params === null
        ) {
            return $child;
        }

        $parent_template_names = array_keys($parent_storage->template_types);
        $type_params = [];
        $projections = [];

        foreach ($child_storage->template_types as $template_name => $type_map) {
            $inferred = null;

            foreach ($extended_params as $parent_template_name => $extended_type) {
                $offset = array_search($parent_template_name, $parent_template_names, true);

                if ($offset === false || !isset($parent->type_params[$offset]) || !$extended_type->isSingle()) {
                    continue;
                }

                $extended_atomic = $extended_type->getSingleAtomic();

                if ($extended_atomic instanceof TTemplateParam
                    && $extended_atomic->param_name === $template_name
                    && $extended_atomic->defining_class === $child_storage->name
                ) {
                    $inferred = $parent->type_params[$offset];

                    if ($parent->getProjection($offset) === TypeArgumentProjection::Star) {
                        $projections[count($type_params)] = TypeArgumentProjection::Star;
                    }

                    break;
                }
            }

            if ($inferred === null) {
                if (!$unknown_as_star) {
                    // a template not passed straight to the parent: nothing is known about it
                    return $child;
                }

                // a purity template is left to its bound, which a `*` cannot stand for
                $bound = reset($type_map);

                if (Capabilities::isPurityType($bound)) {
                    $inferred = PurityArguments::getOmittedArgument($bound);
                } else {
                    $inferred = Type::getMixed();
                    $projections[count($type_params)] = TypeArgumentProjection::Star;
                }
            }

            $type_params[] = $inferred;
        }

        $narrowed = (new TGenericObject(
            $child->value,
            $type_params,
            false,
            $child->is_static,
            $child->extra_types,
            $child->from_docblock,
        ))->setProjections($projections);

        // a `*` passed down holds the bound of the child's template, with the child's other arguments applied
        return StarProjection::resolve($narrowed, $codebase, array_keys($projections));
    }

    /**
     * Two arrays of the same keys overlap when every pair of their items does, e.g. list{int, true} and list{0, bool}
     */
    private static function intersectKeyedArrays(
        Codebase $codebase,
        TKeyedArray $array_1,
        TKeyedArray $array_2,
        bool &$any_scalar_type_match_found,
    ): ?Atomic {
        if ($array_1->fallback_params !== null
            || $array_2->fallback_params !== null
            || count($array_1->properties) !== count($array_2->properties)
            || array_diff_key($array_1->properties, $array_2->properties) !== []
        ) {
            return null;
        }

        $properties = [];

        foreach ($array_1->properties as $key => $property_1) {
            $property_2 = $array_2->properties[$key];

            if ($property_1->possibly_undefined || $property_2->possibly_undefined) {
                return null;
            }

            $property = self::filterTypeWithAnother($codebase, $property_1, $property_2, $any_scalar_type_match_found);

            if ($property === null) {
                return null;
            }

            $properties[$key] = $property;
        }

        return TKeyedArray::make(
            $properties,
            $array_1->class_strings,
            null,
            $array_1->is_list && $array_2->is_list,
            $array_1->from_docblock && $array_2->from_docblock,
        );
    }

    /**
     * The object half of an iterable is a Traversable, which can be narrowed to any of its subclasses
     *
     * @return array{Atomic, Atomic}
     */
    private static function narrowIterableToItsTraversableHalf(
        Codebase $codebase,
        Atomic $type_1_atomic,
        Atomic $type_2_atomic,
    ): array {
        if ($type_1_atomic instanceof TIterable && $type_2_atomic instanceof TNamedObject) {
            $type_1_atomic = AtomicTypeComparator::getTraversableHalf($codebase, $type_1_atomic, $type_2_atomic)
                ?? $type_1_atomic;
        } elseif ($type_2_atomic instanceof TIterable && $type_1_atomic instanceof TNamedObject) {
            $type_2_atomic = AtomicTypeComparator::getTraversableHalf($codebase, $type_2_atomic, $type_1_atomic)
                ?? $type_2_atomic;
        }

        return [$type_1_atomic, $type_2_atomic];
    }

    /**
     * The intersection of objects neither of which contains the other: an interface and a class that can implement
     * it, or a subclass, which narrows the class part of an intersection and keeps the interfaces already known
     */
    private static function intersectNamedObjects(
        Atomic $type_1_atomic,
        Atomic $type_2_atomic,
        Codebase $codebase,
    ): ?TNamedObject {
        if (!$type_1_atomic instanceof TNamedObject || !$type_2_atomic instanceof TNamedObject) {
            return null;
        }

        if (($codebase->interfaceExists($type_1_atomic->value)
                || $codebase->interfaceExists($type_2_atomic->value))
            && self::canBeIntersected($codebase, $type_1_atomic, $type_2_atomic)
        ) {
            if ($type_1_atomic instanceof TGenericObject && !$type_2_atomic instanceof TGenericObject) {
                $type_2_atomic = self::inferTemplateParamsFromParent($codebase, $type_2_atomic, $type_1_atomic);
            }

            return $type_2_atomic->addIntersectionType($type_1_atomic);
        }

        if ($type_1_atomic::class !== TNamedObject::class
            || $type_2_atomic::class !== TNamedObject::class
            || !$type_1_atomic->extra_types
            || $type_2_atomic->extra_types
            || !$codebase->classExists($type_1_atomic->value)
            || !$codebase->classExists($type_2_atomic->value)
            || $codebase->classlike_storage_provider->get($type_2_atomic->value)->final
            || !$codebase->classlikes->classExtends($type_2_atomic->value, $type_1_atomic->value)
        ) {
            return null;
        }

        foreach ($type_1_atomic->extra_types as $constraint) {
            if ($constraint::class !== TNamedObject::class || !$codebase->interfaceExists($constraint->value)) {
                return null;
            }
        }

        return $type_2_atomic->setIntersectionTypes($type_1_atomic->extra_types);
    }

    private static function filterAtomicWithAnother(
        Atomic &$type_1_atomic,
        Atomic $type_2_atomic,
        Codebase $codebase,
        bool &$any_scalar_type_match_found,
    ): ?Atomic {
        if ($type_1_atomic instanceof TFloat
            && $type_2_atomic instanceof TInt
        ) {
            $any_scalar_type_match_found = true;
            return $type_2_atomic;
        }

        [$type_1_atomic, $type_2_atomic] = self::narrowIterableToItsTraversableHalf(
            $codebase,
            $type_1_atomic,
            $type_2_atomic,
        );

        if ($type_1_atomic instanceof TNamedObject) {
            $type_1_atomic = $type_1_atomic->setIsStatic(false);
        }

        if ($type_1_atomic instanceof TNamedObject
            && $type_2_atomic instanceof TString
            && $codebase->classOrInterfaceExists($type_1_atomic->value)
            && $codebase->methodExists(new MethodIdentifier($type_1_atomic->value, '__tostring'))
        ) {
            $any_scalar_type_match_found = true;
            return $type_1_atomic;
        }

        $atomic_comparison_results = new TypeComparisonResult();

        $atomic_contained_by = AtomicTypeComparator::isContainedBy(
            $codebase,
            $type_2_atomic,
            $type_1_atomic,
            !($type_1_atomic instanceof TNamedObject && $type_2_atomic instanceof TNamedObject),
            false,
            $atomic_comparison_results,
        );

        // an object with __toString() is accepted where a string is expected, but no string is such an object;
        // it can only be loosely equal to one
        if ($atomic_contained_by && $atomic_comparison_results->to_string_cast === true) {
            $any_scalar_type_match_found = true;
        } elseif ($atomic_contained_by) {
            return self::refineContainedAtomicWithAnother(
                $type_1_atomic,
                $type_2_atomic,
                $codebase,
                $atomic_comparison_results->type_coerced ?? false,
            );
        }

        $atomic_comparison_results = new TypeComparisonResult();

        $atomic_contained_by = AtomicTypeComparator::isContainedBy(
            $codebase,
            $type_1_atomic,
            $type_2_atomic,
            $type_1_atomic instanceof TClassString && $type_2_atomic instanceof TClassString,
            false,
            $atomic_comparison_results,
        );

        if ($atomic_contained_by) {
            return self::refineContainedAtomicWithAnother(
                $type_2_atomic,
                $type_1_atomic,
                $codebase,
                $atomic_comparison_results->type_coerced ?? false,
            );
        }

        $object_intersection = self::intersectNamedObjects($type_1_atomic, $type_2_atomic, $codebase);
        if ($object_intersection !== null) {
            return $object_intersection;
        }

        $matching_atomic_type = null;

        /*if ($type_2_atomic instanceof TKeyedArray
        ) {
            $type_2_key = $type_2_atomic->getGenericKeyType();
            $type_2_value = $type_2_atomic->getGenericValueType();

            if (!$type_2_key->hasString()) {
                $type_1_type_param = $type_1_atomic->type_param;
                $type_2_value = self::filterTypeWithAnother(
                    $codebase,
                    $type_1_type_param,
                    $type_2_value,
                    $any_scalar_type_match_found
                );
                $type_1_atomic = $type_1_atomic->setTypeParam($type_1_type_param);

                if ($type_2_value === null) {
                    return null;
                }

                return TKeyedArray::make(
                    $type_2_atomic->properties,
                    null,
                    [Type::getInt(), $type_2_value],
                    true
                );
            }
        } elseif ($type_1_atomic instanceof TKeyedArray
            && $type_2_atomic instanceof \Psalm\Type\Atomic\TList
        ) {
            $type_1_key = $type_1_atomic->getGenericKeyType();
            $type_1_value = $type_1_atomic->getGenericValueType();

            if (!$type_1_key->hasString()) {
                $type_2_type_param = $type_2_atomic->type_param;
                $type_1_value = self::filterTypeWithAnother(
                    $codebase,
                    $type_2_type_param,
                    $type_1_value,
                    $any_scalar_type_match_found
                );

                if ($type_1_value === null) {
                    return null;
                }

                return TKeyedArray::make(
                    $type_1_atomic->properties,
                    null,
                    [Type::getInt(), $type_1_value],
                    true
                );
            }
        }*/

        if ($type_2_atomic instanceof TTemplateParam
            && $type_1_atomic instanceof TTemplateParam
            && $type_2_atomic->param_name !== $type_1_atomic->param_name
            && $type_2_atomic->as->hasObject()
            && $type_1_atomic->as->hasObject()
        ) {
            return $type_2_atomic->addIntersectionType($type_1_atomic);
        }

        //we filter both types of standard iterables
        if (($type_2_atomic instanceof TGenericObject
                || $type_2_atomic instanceof TArray
                || $type_2_atomic instanceof TIterable)
            && ($type_1_atomic instanceof TGenericObject
                || $type_1_atomic instanceof TArray
                || $type_1_atomic instanceof TIterable)
            && count($type_2_atomic->type_params) === count($type_1_atomic->type_params)
        ) {
            $type_1_params = $type_1_atomic->type_params;
            foreach ($type_2_atomic->type_params as $i => $type_2_param) {
                $type_1_param = $type_1_params[$i];

                $type_2_param_id = $type_2_param->getId();

                $type_2_param = self::filterTypeWithAnother(
                    $codebase,
                    $type_1_param,
                    $type_2_param,
                    $any_scalar_type_match_found,
                );

                if ($type_2_param === null) {
                    return null;
                }

                if ($type_1_params[$i]->getId() !== $type_2_param_id) {
                    $type_1_params[$i] = $type_2_param;
                }
            }

            /** @psalm-suppress InvalidArgument */
            $type_1_atomic = $type_1_atomic->setTypeParams(
                $type_1_params,
            );

            $matching_atomic_type = $type_1_atomic;
            $atomic_comparison_results->type_coerced = true;
        }

        //we filter the second part of a list with the second part of standard iterables
        /*if (($type_2_atomic instanceof TArray
                || $type_2_atomic instanceof TIterable)
            && $type_1_atomic instanceof \Psalm\Type\Atomic\TList
        ) {
            $type_2_param = $type_2_atomic->type_params[1];
            $type_1_param = $type_1_atomic->type_param;

            $type_2_param = self::filterTypeWithAnother(
                $codebase,
                $type_1_param,
                $type_2_param,
                $any_scalar_type_match_found
            );

            if ($type_2_param === null) {
                return null;
            }

            if ($type_1_param->getId() !== $type_2_param->getId()) {
                $type_1_atomic = $type_1_atomic->setTypeParam($type_2_param);
            } elseif ($type_1_param !== $type_1_atomic->type_param) {
                $type_1_atomic = $type_1_atomic->setTypeParam($type_1_param);
            }

            $matching_atomic_type = $type_1_atomic;
            $atomic_comparison_results->type_coerced = true;
        }*/

        //we filter each property of a Keyed Array with the second part of standard iterables
        if (($type_2_atomic instanceof TArray
                || $type_2_atomic instanceof TIterable)
            && $type_1_atomic instanceof TKeyedArray
        ) {
            $type_2_param = $type_2_atomic->type_params[1];
            $type_1_properties = $type_1_atomic->properties;
            foreach ($type_1_properties as &$type_1_param) {
                $type_2_param = self::filterTypeWithAnother(
                    $codebase,
                    $type_1_param,
                    $type_2_param,
                    $any_scalar_type_match_found,
                );

                if ($type_2_param === null) {
                    return null;
                }

                if ($type_1_param->getId() !== $type_2_param->getId()) {
                    $type_1_param = $type_2_param->setPossiblyUndefined($type_1_param->possibly_undefined);
                }
            }
            unset($type_1_param);

            if ($type_1_atomic->fallback_params === null) {
                $fallback_types = null;
            } else {
                //any fallback type is now the value of iterable
                $fallback_types = [$type_1_atomic->fallback_params[0], $type_2_param];
            }

            $filtered_atomic_type = TKeyedArray::make(
                $type_1_properties,
                $type_1_atomic->class_strings,
                $fallback_types,
                $type_1_atomic->is_list,
                $type_1_atomic->from_docblock,
            );
            $matching_atomic_type = $filtered_atomic_type instanceof TKeyedArray
                ? $filtered_atomic_type->setIsCallable($type_1_atomic->is_callable)
                : $filtered_atomic_type;
            $atomic_comparison_results->type_coerced = true;
        }

        if ($matching_atomic_type === null
            && $type_1_atomic instanceof TKeyedArray
            && $type_2_atomic instanceof TKeyedArray
        ) {
            $matching_atomic_type = self::intersectKeyedArrays(
                $codebase,
                $type_1_atomic,
                $type_2_atomic,
                $any_scalar_type_match_found,
            );
        }

        //These partial match wouldn't have been handled by AtomicTypeComparator
        $new_range = null;
        if ($type_2_atomic instanceof TIntRange
            && $type_1_atomic instanceof TIntRange
        ) {
            $new_range = TIntRange::intersectIntRanges(
                $type_1_atomic,
                $type_2_atomic,
            );
        }

        if ($new_range !== null) {
            $matching_atomic_type = $new_range;
        }

        $refined_string = StringRefinement::intersectAsserted($type_1_atomic, $type_2_atomic);

        if ($refined_string !== null) {
            // e.g. lowercase-string and non-falsy-string, none of which is contained into the other completely
            $matching_atomic_type = $refined_string;
        } elseif (($type_2_atomic instanceof TLowercaseString && $type_1_atomic instanceof TNonEmptyString) ||
            ($type_2_atomic instanceof TNonEmptyString && $type_1_atomic instanceof TLowercaseString)
        ) {
            // Lowercase-string and non-empty-string are compatible but none is contained into the other completely
            $matching_atomic_type = new TNonEmptyLowercaseString();
        }

        // Lowercase-string and non-empty-string are compatible but none is contained into the other completely
        if (($type_2_atomic instanceof TLowercaseString
            && $type_1_atomic instanceof TNonEmptyNonspecificLiteralString) ||
            ($type_2_atomic instanceof TNonEmptyNonspecificLiteralString && $type_1_atomic instanceof TLowercaseString)
        ) {
            $matching_atomic_type = new TNonEmptyLowercaseString();
        }

        if ($matching_atomic_type === null) {
            $matching_atomic_type = self::intersectDecimalIntString($type_1_atomic, $type_2_atomic);
        }

        if (!$atomic_comparison_results->type_coerced && $atomic_comparison_results->scalar_type_match_found) {
            $any_scalar_type_match_found = true;
        }

        return $matching_atomic_type;
    }

    /**
     * A type containing the values that a decimal-int-string or a non-decimal-int-string shares with another string
     * type when neither contains the other: the decimal-int-string itself (e.g. for a non-falsy-string, still with
     * "0"), or the other type for a non-decimal-int-string (e.g. a numeric-string, still with "1")
     *
     * @psalm-pure
     */
    private static function intersectDecimalIntString(Atomic $type_1_atomic, Atomic $type_2_atomic): ?Atomic
    {
        if (!$type_1_atomic instanceof TString
            || !$type_2_atomic instanceof TString
            || $type_1_atomic instanceof TLiteralString
            || $type_2_atomic instanceof TLiteralString
        ) {
            return null;
        }

        if ($type_2_atomic instanceof TDecimalIntString || $type_2_atomic instanceof TNonDecimalIntString) {
            [$type_1_atomic, $type_2_atomic] = [$type_2_atomic, $type_1_atomic];
        }

        if ($type_2_atomic instanceof TDecimalIntString || $type_2_atomic instanceof TNonDecimalIntString) {
            // a decimal-int-string and a non-decimal-int-string share no value
            return null;
        }

        if ($type_1_atomic instanceof TNonDecimalIntString) {
            return $type_2_atomic;
        }

        if ($type_1_atomic instanceof TDecimalIntString
            // no class, function or trait name is a decimal int
            && !$type_2_atomic instanceof TClassString
            && !$type_2_atomic instanceof TCallableString
            && !$type_2_atomic instanceof TTraitString
        ) {
            return $type_1_atomic;
        }

        return null;
    }

    private static function refineContainedAtomicWithAnother(
        Atomic $type_1_atomic,
        Atomic $type_2_atomic,
        Codebase $codebase,
        bool $type_coerced,
    ): ?Atomic {
        if ($type_2_atomic::class === TNamedObject::class
            && $type_1_atomic instanceof TGenericObject
            && $type_1_atomic->hasStarProjection()
        ) {
            // what a `*` leaves unknown stays unknown: `Pair<int, *>` narrowed to `Pair` is still a `Pair<int, *>`
            // (with the intersection asserted next to it), and narrowed to a `Child` that `@extends Pair<int, T>` is a
            // `Child<*>`, not one of the template's bound
            return strtolower($type_2_atomic->value) === strtolower($type_1_atomic->value)
                ? $type_1_atomic->setIntersectionTypes([...$type_1_atomic->extra_types, ...$type_2_atomic->extra_types])
                : self::inferTemplateParamsFromParent($codebase, $type_2_atomic, $type_1_atomic, true);
        }

        if ($type_coerced
            && $type_2_atomic::class === TNamedObject::class
            && $type_1_atomic instanceof TGenericObject
        ) {
            // this is a hack - it's not actually rigorous, as the params may be different
            return new TGenericObject(
                $type_2_atomic->value,
                $type_1_atomic->type_params,
            );
        } elseif ($type_2_atomic instanceof TNamedObject
            && $type_1_atomic instanceof TTemplateParam
            && $type_1_atomic->as->hasObjectType()
        ) {
            $type_1_as_init = $type_1_atomic->as;
            $type_1_as = self::filterTypeWithAnother(
                $codebase,
                $type_1_as_init,
                new Union([$type_2_atomic]),
            );

            if ($type_1_as === null) {
                return null;
            }

            return $type_1_atomic->replaceAs($type_1_as);
        } else {
            return $type_2_atomic;
        }
    }

    /**
     * @param  TLiteralInt|TLiteralFloat|TLiteralString|TEnumCase $assertion_type
     * @param  string[]          $suppressed_issues
     */
    private static function handleLiteralEquality(
        StatementsAnalyzer $statements_analyzer,
        Assertion          $assertion,
        Atomic             $assertion_type,
        Union              $existing_var_type,
        string             $old_var_type_string,
        ?string            $var_id,
        bool               $negated,
        ?CodeLocation      $code_location,
        array              $suppressed_issues,
    ): Union {
        $existing_var_atomic_types = [];

        foreach ($existing_var_type->getAtomicTypes() as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TClassConstant) {
                $expanded = TypeExpander::expandAtomic(
                    $statements_analyzer->getCodebase(),
                    $existing_var_atomic_type,
                    $existing_var_atomic_type->fq_classlike_name,
                    $existing_var_atomic_type->fq_classlike_name,
                    null,
                    true,
                    true,
                );

                foreach ($expanded as $atomic_type) {
                    $existing_var_atomic_types[$atomic_type->getKey()] = $atomic_type;
                }
            } else {
                $existing_var_atomic_types[$existing_var_atomic_type->getKey()] = $existing_var_atomic_type;
            }
        }

        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TTemplateParam) {
                return self::handleLiteralEqualityWithTemplates(
                    $statements_analyzer,
                    $assertion,
                    $assertion_type,
                    $existing_var_type,
                    $existing_var_atomic_types,
                    $old_var_type_string,
                    $var_id,
                    $negated,
                    $code_location,
                    $suppressed_issues,
                );
            }
        }

        if ($assertion instanceof IsLooselyEqual && !$assertion_type instanceof TEnumCase) {
            $bools_and_null = array_filter(
                $existing_var_atomic_types,
                static fn(Atomic $atomic_type): bool => $atomic_type instanceof TBool
                    || $atomic_type instanceof TNull,
            );
            $loosely_equal_bools_and_null = self::getLooselyEqualBoolsAndNull($assertion_type, $bools_and_null);

            if ($loosely_equal_bools_and_null !== []) {
                $other_atomic_types = array_diff_key($existing_var_atomic_types, $bools_and_null);
                $other_types = $other_atomic_types === [] ? null : new Union(array_values($other_atomic_types), [
                    'from_docblock' => $existing_var_type->from_docblock,
                ]);

                // the other types are reconciled without reporting: the comparison may be true for a bool or null
                $other_type = $other_types === null
                    ? Type::getNever()
                    : self::handleLiteralEquality(
                        $statements_analyzer,
                        $assertion,
                        $assertion_type,
                        $other_types,
                        $old_var_type_string,
                        null,
                        $negated,
                        null,
                        $suppressed_issues,
                    );

                // nothing narrowed: the same type tells the caller that the comparison didn't reconcile it
                if (($other_types === null || $other_type === $other_types)
                    && $loosely_equal_bools_and_null === array_values($bools_and_null)
                ) {
                    return $existing_var_type;
                }

                return new Union([
                    ...$other_type->isNever() ? [] : array_values($other_type->getAtomicTypes()),
                    ...$loosely_equal_bools_and_null,
                ], ['from_docblock' => $existing_var_type->from_docblock]);
            }
        }

        if ($assertion_type instanceof TLiteralInt) {
            return self::handleLiteralEqualityWithInt(
                $statements_analyzer,
                $assertion,
                $assertion_type,
                $existing_var_type,
                $existing_var_atomic_types,
                $old_var_type_string,
                $var_id,
                $negated,
                $code_location,
                $suppressed_issues,
            );
        } elseif ($assertion_type instanceof TLiteralString) {
            return self::handleLiteralEqualityWithString(
                $statements_analyzer,
                $assertion,
                $assertion_type,
                $existing_var_type,
                $existing_var_atomic_types,
                $old_var_type_string,
                $var_id,
                $negated,
                $code_location,
                $suppressed_issues,
            );
        } elseif ($assertion_type instanceof TLiteralFloat) {
            return self::handleLiteralEqualityWithFloat(
                $statements_analyzer,
                $assertion,
                $assertion_type,
                $existing_var_type,
                $existing_var_atomic_types,
                $old_var_type_string,
                $var_id,
                $negated,
                $code_location,
                $suppressed_issues,
            );
        } else {
            $fq_enum_name = $assertion_type->value;
            $case_name = $assertion_type->case_name;

            if ($existing_var_type->hasMixed()) {
                if ($assertion instanceof IsLooselyEqual) {
                    return $existing_var_type;
                }

                return new Union([new TEnumCase($fq_enum_name, $case_name)]);
            }

            $can_be_equal = false;
            $redundant = true;

            $existing_var_type = $existing_var_type->getBuilder();
            foreach ($existing_var_atomic_types as $atomic_key => $atomic_type) {
                if ($atomic_type::class === TNamedObject::class
                    && $atomic_type->value === $fq_enum_name
                ) {
                    $can_be_equal = true;
                    $redundant = false;
                    $existing_var_type->removeType($atomic_key);
                    $existing_var_type->addType(new TEnumCase($fq_enum_name, $case_name));
                } elseif (AtomicTypeComparator::canBeIdentical(
                    $statements_analyzer->getCodebase(),
                    $atomic_type,
                    $assertion_type,
                )) {
                    $can_be_equal = true;
                    $redundant = $atomic_key === $assertion_type->getKey();
                    $existing_var_type->removeType($atomic_key);
                    $existing_var_type->addType(new TEnumCase($fq_enum_name, $case_name));
                } elseif ($atomic_key !== $assertion_type->getKey()) {
                    $existing_var_type->removeType($atomic_key);
                    $redundant = false;
                } else {
                    $can_be_equal = true;
                }
            }
            $existing_var_type = $existing_var_type->freeze();

            if ($var_id
                && $code_location
                && (!$can_be_equal || ($redundant && count($existing_var_atomic_types) === 1))
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $var_id,
                    $assertion,
                    $can_be_equal,
                    $negated,
                    $code_location,
                    $suppressed_issues,
                );
            }
        }

        return $existing_var_type;
    }

    /**
     * The bools and null loosely equal to the scalar: a bool is compared to the scalar cast to bool (`false == 0`),
     * null to a string as "" (`null == ""`) and to anything else as false (`null == 0`)
     *
     * @param array<string, TBool|TNull> $bools_and_null
     * @return list<TBool|TNull>
     * @psalm-pure
     */
    private static function getLooselyEqualBoolsAndNull(
        TLiteralInt|TLiteralString|TLiteralFloat $scalar,
        array $bools_and_null,
    ): array {
        $scalar_is_truthy = (bool) $scalar->value;
        $loosely_equal = [];

        foreach ($bools_and_null as $atomic_type) {
            if ($atomic_type instanceof TNull) {
                if ($scalar instanceof TLiteralString ? $scalar->value === '' : !$scalar_is_truthy) {
                    $loosely_equal[] = $atomic_type;
                }
            } elseif ($atomic_type instanceof TTrue || $atomic_type instanceof TFalse) {
                if ($atomic_type instanceof TTrue === $scalar_is_truthy) {
                    $loosely_equal[] = $atomic_type;
                }
            } else {
                $loosely_equal[] = $scalar_is_truthy ? new TTrue() : new TFalse();
            }
        }

        return $loosely_equal;
    }

    /**
     * A value of a template type equal to the literal is still of that type: narrow its bound, and the other
     * members of the union separately
     *
     * @param non-empty-array<string, Atomic> $existing_var_atomic_types
     * @param string[] $suppressed_issues
     */
    private static function handleLiteralEqualityWithTemplates(
        StatementsAnalyzer $statements_analyzer,
        Assertion          $assertion,
        TLiteralInt|TLiteralFloat|TLiteralString|TEnumCase $assertion_type,
        Union              $existing_var_type,
        array              $existing_var_atomic_types,
        string             $old_var_type_string,
        ?string            $var_id,
        bool               $negated,
        ?CodeLocation      $code_location,
        array              $suppressed_issues,
    ): Union {
        // a lone template reports impossible and redundant conditions through its bound
        $is_single = count($existing_var_atomic_types) === 1;

        $narrowed_types = [];
        $other_types = [];

        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if (!$existing_var_atomic_type instanceof TTemplateParam) {
                $other_types[] = $existing_var_atomic_type;
                continue;
            }

            $as = $existing_var_atomic_type->as->setProperties([
                'from_docblock' => $existing_var_type->from_docblock,
            ]);

            $narrowed_as = self::handleLiteralEquality(
                $statements_analyzer,
                $assertion,
                $assertion_type,
                $as,
                $old_var_type_string,
                $is_single ? $var_id : null,
                $negated,
                $is_single ? $code_location : null,
                $suppressed_issues,
            );

            if ($narrowed_as === $as) {
                // e.g. a loose equality with a `mixed` bound: any member of the union can still be equal
                if ($assertion instanceof IsLooselyEqual) {
                    return $existing_var_type;
                }

                $narrowed_types[] = $existing_var_atomic_type;
            } elseif (!$narrowed_as->isNever()) {
                $narrowed_types[] = $existing_var_atomic_type->replaceAs($narrowed_as);
            }
        }

        if ($other_types) {
            $narrowed_others = self::handleLiteralEquality(
                $statements_analyzer,
                $assertion,
                $assertion_type,
                new Union($other_types, ['from_docblock' => $existing_var_type->from_docblock]),
                $old_var_type_string,
                null,
                $negated,
                null,
                $suppressed_issues,
            );

            if (!$narrowed_others->isNever()) {
                $narrowed_types = [...$narrowed_types, ...$narrowed_others->getAtomicTypes()];
            }
        }

        if (!$narrowed_types) {
            if (!$is_single && $var_id !== null && $code_location) {
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

            return Type::getNever();
        }

        return new Union($narrowed_types, ['from_docblock' => $existing_var_type->from_docblock]);
    }

    /**
     * @param array<string, Atomic> $existing_var_atomic_types
     * @param string[]     $suppressed_issues
     */
    private static function handleLiteralEqualityWithInt(
        StatementsAnalyzer $statements_analyzer,
        Assertion      $assertion,
        TLiteralInt        $assertion_type,
        Union              $existing_var_type,
        array              $existing_var_atomic_types,
        string             $old_var_type_string,
        ?string            $var_id,
        bool               $negated,
        ?CodeLocation      $code_location,
        array              $suppressed_issues,
    ): Union {
        $value = $assertion_type->value;

        // we create the literal that is being asserted. We'll return this when we're sure this is the resulting type
        $literal_asserted_type = new Union([new TLiteralInt($value)], [
            'from_docblock' => $existing_var_type->from_docblock,
        ]);
        $compatible_int_type = self::getCompatibleIntType(
            $existing_var_type,
            $existing_var_atomic_types,
            $assertion_type,
            $assertion instanceof IsLooselyEqual,
        );

        if ($compatible_int_type !== null) {
            return $compatible_int_type;
        }

        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TIntRange && $existing_var_atomic_type->contains($value)) {
                return $literal_asserted_type;
            }

            if ($existing_var_atomic_type instanceof TLiteralInt && $existing_var_atomic_type->value === $value) {
                //if we're here, we check that we had at least another type in the union, otherwise it's redundant

                if ($existing_var_type->isSingleIntLiteral()) {
                    if ($var_id && $code_location) {
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
                    return $existing_var_type;
                }
                return $literal_asserted_type;
            }

            if ($existing_var_atomic_type instanceof TInt && !$existing_var_atomic_type instanceof TLiteralInt) {
                return $literal_asserted_type;
            }

            if ($assertion instanceof IsLooselyEqual
                && $existing_var_atomic_type instanceof TLiteralFloat
                && $existing_var_atomic_type->value == $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }

            if ($assertion instanceof IsLooselyEqual
                && $existing_var_atomic_type instanceof TLiteralString
                && LiteralCast::toInt($existing_var_atomic_type->value) === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }
        }

        //here we'll accept non-literal type that *could* match on loose equality and return the original type
        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            //here we'll accept non-literal type that *could* match on loose equality and return the original type
            if ($assertion instanceof IsLooselyEqual) {
                if ($existing_var_atomic_type instanceof TString
                    && !$existing_var_atomic_type instanceof TLiteralString
                ) {
                    return $existing_var_type;
                }

                if ($existing_var_atomic_type instanceof TFloat
                    && !$existing_var_atomic_type instanceof TLiteralFloat
                ) {
                    return $existing_var_type;
                }
            }
        }

        //if we're here, no type was eligible for the given literal. We'll emit an impossible error for this assertion
        if ($var_id && $code_location) {
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

        return Type::getNever();
    }

    /**
     * What `is_numeric()` tells for the PHP version analysed: a trailing whitespace is allowed as of PHP 8
     *
     * @psalm-pure
     */
    public static function isNumericString(string $value, int $analysis_php_version_id): bool
    {
        return is_numeric($value)
            && ($analysis_php_version_id >= 8_00_00 || rtrim($value, " \t\n\r\v\f") === $value);
    }

    /**
     * An object with a `__toString()` can be loosely equal to a string: it stays in the type next to the literal
     *
     * @param array<string, Atomic> $existing_var_atomic_types
     */
    private static function addStringableObjects(
        StatementsAnalyzer $statements_analyzer,
        Assertion $assertion,
        Union $literal_type,
        array $existing_var_atomic_types,
    ): Union {
        if (!$assertion instanceof IsLooselyEqual) {
            return $literal_type;
        }

        $codebase = $statements_analyzer->getCodebase();
        $atomic_types = $literal_type->getAtomicTypes();

        foreach ($existing_var_atomic_types as $atomic_type) {
            // an `object` may be one with a __toString too
            if ($atomic_type::class === TObject::class
                || ($atomic_type instanceof TNamedObject
                    && $codebase->methodExists(new MethodIdentifier($atomic_type->value, '__tostring')))
            ) {
                $atomic_types[$atomic_type->getKey()] = $atomic_type;
            }
        }

        return new Union(array_values($atomic_types), ['from_docblock' => $literal_type->from_docblock]);
    }

    /**
     * @param array<string, Atomic> $existing_var_atomic_types
     * @param string[]     $suppressed_issues
     */
    private static function handleLiteralEqualityWithString(
        StatementsAnalyzer $statements_analyzer,
        Assertion      $assertion,
        TLiteralString     $assertion_type,
        Union              $existing_var_type,
        array              $existing_var_atomic_types,
        string             $old_var_type_string,
        ?string            $var_id,
        bool               $negated,
        ?CodeLocation      $code_location,
        array              $suppressed_issues,
    ): Union {
        $value = $assertion_type->value;

        // PHP compares two numeric strings by their numbers: `"1" == "01"`
        $loosely_equal_literals = [];

        if ($assertion instanceof IsLooselyEqual
            && self::isNumericString($value, $statements_analyzer->getCodebase()->analysis_php_version_id)
        ) {
            foreach ($existing_var_atomic_types as $key => $existing_var_atomic_type) {
                if ($existing_var_atomic_type instanceof TLiteralString
                    && $existing_var_atomic_type->value !== $value
                    && self::isNumericString(
                        $existing_var_atomic_type->value,
                        $statements_analyzer->getCodebase()->analysis_php_version_id,
                    )
                    && $existing_var_atomic_type->value == $value
                ) {
                    $loosely_equal_literals[$key] = $existing_var_atomic_type;
                }
            }
        }

        // we create the literal that is being asserted. We'll return this when we're sure this is the resulting type
        $literal_asserted_type_string = new Union([$assertion_type, ...array_values($loosely_equal_literals)], [
            'from_docblock' => $existing_var_type->from_docblock,
        ]);

        $compatible_string_type = self::getCompatibleStringType(
            $statements_analyzer->getCodebase(),
            $existing_var_type,
            $existing_var_atomic_types,
            $assertion_type,
            $assertion instanceof IsLooselyEqual,
            $statements_analyzer->getCodebase()->analysis_php_version_id >= 8_00_00,
        );

        if ($compatible_string_type !== null) {
            return $compatible_string_type;
        }

        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TLiteralString && $existing_var_atomic_type->value === $value) {
                //if we're here, we check that we had at least another type in the union, otherwise it's redundant

                if ($existing_var_type->isSingleStringLiteral()) {
                    if ($var_id && $code_location) {
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
                    return $existing_var_type;
                }

                return self::addStringableObjects(
                    $statements_analyzer,
                    $assertion,
                    $literal_asserted_type_string,
                    $existing_var_atomic_types,
                );
            }

            if ($existing_var_atomic_type instanceof TString && !$existing_var_atomic_type instanceof TLiteralString) {
                return self::addStringableObjects(
                    $statements_analyzer,
                    $assertion,
                    $literal_asserted_type_string,
                    $existing_var_atomic_types,
                );
            }

            if ($assertion instanceof IsLooselyEqual
                && $existing_var_atomic_type instanceof TLiteralInt
                && (string)$existing_var_atomic_type->value === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }

            if ($assertion instanceof IsLooselyEqual
                && $existing_var_atomic_type instanceof TLiteralFloat
                && !is_nan($existing_var_atomic_type->value) // NAN is not equal to anything
                && (string)$existing_var_atomic_type->value === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }
        }

        //here we'll accept non-literal type that *could* match on loose equality and return the original type
        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            //here we'll accept non-literal type that *could* match on loose equality and return the original type
            if ($assertion instanceof IsLooselyEqual) {
                if ($existing_var_atomic_type instanceof TInt
                    && !$existing_var_atomic_type instanceof TLiteralInt
                ) {
                    return $existing_var_type;
                }

                if ($existing_var_atomic_type instanceof TFloat
                    && !$existing_var_atomic_type instanceof TLiteralFloat
                ) {
                    return $existing_var_type;
                }
            }
        }

        if ($loosely_equal_literals !== []) {
            return new Union(array_values($loosely_equal_literals), [
                'from_docblock' => $existing_var_type->from_docblock,
            ]);
        }

        //if we're here, no type was eligible for the given literal. We'll emit an impossible error for this assertion
        if ($var_id && $code_location) {
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

        return Type::getNever();
    }

    /**
     * @param array<string, Atomic> $existing_var_atomic_types
     * @param string[]     $suppressed_issues
     */
    private static function handleLiteralEqualityWithFloat(
        StatementsAnalyzer $statements_analyzer,
        Assertion      $assertion,
        TLiteralFloat      $assertion_type,
        Union              $existing_var_type,
        array              $existing_var_atomic_types,
        string             $old_var_type_string,
        ?string            $var_id,
        bool               $negated,
        ?CodeLocation      $code_location,
        array              $suppressed_issues,
    ): Union {
        $value = $assertion_type->value;

        // we create the literal that is being asserted. We'll return this when we're sure this is the resulting type
        $literal_asserted_type = new Union([new TLiteralFloat($value)], [
            'from_docblock' => $existing_var_type->from_docblock,
        ]);

        $compatible_float_type = self::getCompatibleFloatType(
            $existing_var_type,
            $existing_var_atomic_types,
            $assertion_type,
            $assertion instanceof IsLooselyEqual,
        );

        if ($compatible_float_type !== null) {
            return $compatible_float_type;
        }

        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TLiteralFloat && $existing_var_atomic_type->value === $value) {
                //if we're here, we check that we had at least another type in the union, otherwise it's redundant

                if ($existing_var_type->isSingleFloatLiteral()) {
                    if ($var_id && $code_location) {
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
                    return $existing_var_type;
                }

                return $literal_asserted_type;
            }

            if ($existing_var_atomic_type instanceof TFloat && !$existing_var_atomic_type instanceof TLiteralFloat) {
                return $literal_asserted_type;
            }

            if ($assertion instanceof IsLooselyEqual
                && $existing_var_atomic_type instanceof TLiteralInt
                && (float)$existing_var_atomic_type->value === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }

            if ($assertion instanceof IsLooselyEqual
                && $existing_var_atomic_type instanceof TLiteralString
                && (float)$existing_var_atomic_type->value === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }
        }

        //here we'll accept non-literal type that *could* match on loose equality and return the original type
        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($assertion instanceof IsLooselyEqual) {
                if ($existing_var_atomic_type instanceof TInt
                    && !$existing_var_atomic_type instanceof TLiteralInt
                ) {
                    return $existing_var_type;
                }

                if ($existing_var_atomic_type instanceof TString
                    && !$existing_var_atomic_type instanceof TLiteralString
                ) {
                    return $existing_var_type;
                }
            }
        }

        //if we're here, no type was eligible for the given literal. We'll emit an impossible error for this assertion
        if ($var_id && $code_location) {
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

        return Type::getNever();
    }

    /**
     * @param array<string, Atomic> $existing_var_atomic_types
     * @psalm-pure
     */
    private static function getCompatibleIntType(
        Union $existing_var_type,
        array $existing_var_atomic_types,
        TLiteralInt $assertion_type,
        bool $is_loose_equality,
    ): ?Union {
        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TMixed
                || $existing_var_atomic_type instanceof TScalar
                || $existing_var_atomic_type instanceof TNumeric
                || $existing_var_atomic_type instanceof TArrayKey
            ) {
                if ($is_loose_equality) {
                    return $existing_var_type;
                }

                return new Union([$assertion_type], [
                    'from_docblock' => $existing_var_type->from_docblock,
                ]);
            }
        }

        return null;
    }

    /**
     * @param array<string, Atomic> $existing_var_atomic_types
     */
    private static function getCompatibleStringType(
        Codebase $codebase,
        Union $existing_var_type,
        array $existing_var_atomic_types,
        TLiteralString $assertion_type,
        bool $is_loose_equality,
        bool $is_php8,
    ): ?Union {
        $value = $assertion_type->value;

        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TMixed
                || $existing_var_atomic_type instanceof TScalar
                || $existing_var_atomic_type instanceof TArrayKey
                // e.g. '1'
                || ($existing_var_atomic_type instanceof TNumeric
                    && is_numeric($value)
                    && (!$existing_var_atomic_type instanceof TEmptyNumeric || $value === '0'))
                // the name of a function is never numeric, e.g. 'strlen'
                || ($existing_var_atomic_type::class === TCallable::class
                    && $value !== ''
                    && !is_numeric($value)
                    && (($existing_var_atomic_type->params === null && $existing_var_atomic_type->return_type === null)
                        || AtomicTypeComparator::isContainedBy($codebase, $assertion_type, $existing_var_atomic_type)))
            ) {
                // Since PHP 8, an int is only loosely equal to a numeric string (`switch` compares loosely), so an
                // array key loosely equal to a string that isn't numeric is that string
                if ($is_loose_equality
                    && !($is_php8
                        && $existing_var_atomic_type instanceof TArrayKey
                        && !is_numeric($assertion_type->value))
                ) {
                    return $existing_var_type;
                }

                return new Union([$assertion_type], [
                    'from_docblock' => $existing_var_type->from_docblock,
                ]);
            }
        }

        return null;
    }

    /**
     * @param array<string, Atomic> $existing_var_atomic_types
     * @psalm-pure
     */
    private static function getCompatibleFloatType(
        Union $existing_var_type,
        array $existing_var_atomic_types,
        TLiteralFloat $assertion_type,
        bool $is_loose_equality,
    ): ?Union {
        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TMixed
                || $existing_var_atomic_type instanceof TScalar
                || $existing_var_atomic_type instanceof TNumeric
            ) {
                if ($is_loose_equality) {
                    return $existing_var_type;
                }

                return new Union([$assertion_type], [
                    'from_docblock' => $existing_var_type->from_docblock,
                ]);
            }
        }

        return null;
    }

    /**
     * @param array<string>           $suppressed_issues
     * @return non-empty-list<Atomic>
     */
    private static function handleIsA(
        IsAClass $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?CodeLocation $code_location,
        ?string $key,
        array $suppressed_issues,
        bool &$should_return,
    ): array {
        $allow_string_comparison = $assertion->allow_string;

        $assertion_type = $assertion->type;

        if ($existing_var_type->hasMixed()) {
            if (!$assertion_type instanceof TNamedObject) {
                return [$assertion_type];
            }

            $types = [$assertion_type];

            if ($allow_string_comparison) {
                $types[] = new TClassString(
                    $assertion_type->value,
                    $assertion_type,
                );
            }

            $should_return = true;
            return $types;
        }

        $existing_has_object = $existing_var_type->hasObjectType();
        $existing_has_string = $existing_var_type->hasString();

        if ($existing_has_object && !$existing_has_string) {
            if ($assertion_type instanceof TTemplateParamClass) {
                return [new TTemplateParam(
                    $assertion_type->param_name,
                    new Union([$assertion_type->as_type ?: new TObject()]),
                    $assertion_type->defining_class,
                )];
            }
            return [$assertion_type];
        }

        if ($existing_has_string && !$existing_has_object) {
            if (!$allow_string_comparison && $code_location) {
                IssueBuffer::maybeAdd(
                    new TypeDoesNotContainType(
                        'Cannot allow string comparison to object for '
                            . CoalesceAnalyzer::getDisplayName($key, $code_location),
                        $code_location,
                        "no string comparison to $key",
                    ),
                    $suppressed_issues,
                );

                return [new TMixed()];
            } else {
                if (!$assertion_type instanceof TNamedObject) {
                    return [$assertion_type];
                }

                // a `class-string<T>` stays one of `T`, restricted to the asserted class
                $template_class_strings = [];

                foreach ($existing_var_type->getAtomicTypes() as $existing_type_part) {
                    if (!$existing_type_part instanceof TTemplateParamClass) {
                        $template_class_strings = [];
                        break;
                    }

                    $bound = $existing_type_part->as_type;

                    if ($bound === null
                        || AtomicTypeComparator::isContainedBy($codebase, $assertion_type, $bound)
                    ) {
                        $refined_bound = $assertion_type;
                    } elseif (AtomicTypeComparator::isContainedBy($codebase, $bound, $assertion_type)) {
                        $refined_bound = $bound;
                    } else {
                        $refined_bound = $bound->addIntersectionType($assertion_type);
                    }

                    $template_class_strings[] = new TTemplateParamClass(
                        $existing_type_part->param_name,
                        $refined_bound->value,
                        $refined_bound,
                        $existing_type_part->defining_class,
                    );
                }

                if ($template_class_strings) {
                    $should_return = true;

                    return $template_class_strings;
                }

                $new_type_has_interface_string = $codebase->interfaceExists($assertion_type->value);

                $old_type_has_interface_string = false;

                foreach ($existing_var_type->getAtomicTypes() as $existing_type_part) {
                    if ($existing_type_part instanceof TClassString
                        && $existing_type_part->as_type
                        && $codebase->interfaceExists($existing_type_part->as_type->value)
                    ) {
                        $old_type_has_interface_string = true;
                        break;
                    }
                }

                $new_type = Type::getClassString($assertion_type->value);

                if ((
                        $new_type_has_interface_string
                        && !UnionTypeComparator::isContainedBy(
                            $codebase,
                            $existing_var_type,
                            $new_type,
                        )
                    )
                    || (
                        $old_type_has_interface_string
                        && !UnionTypeComparator::isContainedBy(
                            $codebase,
                            $new_type,
                            $existing_var_type,
                        )
                    )
                ) {
                    $new_type_part = $assertion_type;

                    $acceptable_atomic_types = [];

                    foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                        if (!$existing_var_type_part instanceof TClassString) {
                            $acceptable_atomic_types = [];

                            break;
                        }

                        if (!$existing_var_type_part->as_type instanceof TNamedObject) {
                            $acceptable_atomic_types = [];

                            break;
                        }

                        $existing_var_type_part = $existing_var_type_part->as_type;

                        if (AtomicTypeComparator::isContainedBy(
                            $codebase,
                            $existing_var_type_part,
                            $new_type_part,
                        )) {
                            $acceptable_atomic_types[] = $existing_var_type_part;
                            continue;
                        }

                        if ($codebase->classExists($existing_var_type_part->value)
                            || $codebase->interfaceExists($existing_var_type_part->value)
                        ) {
                            $existing_var_type_part = $existing_var_type_part->addIntersectionType($new_type_part);
                            $acceptable_atomic_types[] = $existing_var_type_part;
                        }
                    }

                    if (count($acceptable_atomic_types) === 1) {
                        $should_return = true;

                        return [new TClassString('object', $acceptable_atomic_types[0])];
                    }
                }
            }

            return [$new_type->getSingleAtomic()];
        } else {
            return [new TMixed()];
        }
    }

    /**
     * The second type refined with each part of the first, an intersection of named objects
     */
    private static function refineWithIntersectionParts(
        Atomic $type_1_atomic,
        Atomic $type_2_atomic,
        Codebase $codebase,
        bool &$any_scalar_type_match_found,
    ): ?TNamedObject {
        if (!$type_1_atomic instanceof TNamedObject
            || !$type_1_atomic->extra_types
            || !$type_2_atomic instanceof TNamedObject
        ) {
            return null;
        }

        $type_1_parts = [$type_1_atomic->setIntersectionTypes([])];

        foreach ($type_1_atomic->extra_types as $extra_type) {
            if (!$extra_type instanceof TNamedObject || $extra_type->extra_types) {
                return null;
            }

            $type_1_parts[] = $extra_type;
        }

        foreach ($type_1_parts as $type_1_part) {
            $type_2_atomic = self::filterAtomicWithAnother(
                $type_1_part,
                $type_2_atomic,
                $codebase,
                $any_scalar_type_match_found,
            );

            if (!$type_2_atomic instanceof TNamedObject) {
                return null;
            }
        }

        return $type_2_atomic;
    }

    /**
     * @psalm-capabilities read-props|write-props
     */
    private static function isOnlyImplementedByEnums(Codebase $codebase, string $interface_name): bool
    {
        return strtolower($interface_name) === 'unitenum'
            || ($codebase->interfaceExists($interface_name)
                && $codebase->interfaceExtends($interface_name, 'UnitEnum'));
    }

    /**
     * No object is an instance of two classes that don't extend one another, or of a final class and an interface
     * it doesn't implement
     *
     * @psalm-capabilities read-props|write-props
     */
    private static function canBeIntersected(
        Codebase $codebase,
        TNamedObject $existing_type_part,
        TNamedObject $new_type_part,
    ): bool {
        $existing_names = [$existing_type_part->value];

        foreach ($existing_type_part->extra_types as $extra_type) {
            if ($extra_type instanceof TNamedObject) {
                $existing_names[] = $extra_type->value;
            }
        }

        foreach ($existing_names as $existing_name) {
            if (!$codebase->classOrInterfaceExists($existing_name)
                || !$codebase->classOrInterfaceExists($new_type_part->value)
            ) {
                continue;
            }

            $existing_storage = $codebase->classlike_storage_provider->get($existing_name);
            $new_storage = $codebase->classlike_storage_provider->get($new_type_part->value);

            $existing_is_class = !$existing_storage->is_interface && !$existing_storage->is_trait;
            $new_is_class = !$new_storage->is_interface && !$new_storage->is_trait;

            // only an enum implements UnitEnum, and an enum extends no class
            if (($existing_is_class
                    && !$existing_storage->is_enum
                    && self::isOnlyImplementedByEnums($codebase, $new_type_part->value))
                || ($new_is_class
                    && !$new_storage->is_enum
                    && self::isOnlyImplementedByEnums($codebase, $existing_name))
            ) {
                return false;
            }

            if ($existing_is_class && $new_is_class) {
                if (!$codebase->classExtends($existing_name, $new_type_part->value)
                    && !$codebase->classExtends($new_type_part->value, $existing_name)
                    && strtolower($existing_name) !== strtolower($new_type_part->value)
                ) {
                    return false;
                }
            } elseif ($existing_is_class && $existing_storage->final) {
                if (!$codebase->classImplements($existing_name, $new_type_part->value)) {
                    return false;
                }
            } elseif ($new_is_class && $new_storage->final) {
                if (!$codebase->classImplements($new_type_part->value, $existing_name)) {
                    return false;
                }
            }
        }

        return true;
    }
}
