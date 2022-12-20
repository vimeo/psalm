<?php

namespace Psalm\Internal\Type;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\IsClassNotEqual;
use Psalm\Storage\Assertion\IsNotCountable;
use Psalm\Storage\Assertion\IsNotIdentical;
use Psalm\Storage\Assertion\IsNotType;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_merge;
use function array_values;
use function count;
use function get_class;
use function strtolower;

/**
 * @internal
 */
class NegatedAssertionReconciler extends Reconciler
{
    /**
     * @param  string[]   $suppressed_issues
     * @param  Reconciler::RECONCILIATION_*      $failed_reconciliation
     */
    public static function reconcile(
        StatementsAnalyzer $statements_analyzer,
        Assertion $assertion,
        Union $existing_var_type,
        string $old_var_type_string,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $inside_loop
    ): Union {
        $is_equality = $assertion->hasEquality();

        $assertion_type = $assertion->getAtomicType();

        // this is a specific value comparison type that cannot be negated
        if ($is_equality
            && ($assertion_type instanceof TLiteralFloat
                || $assertion_type instanceof TLiteralInt
                || $assertion_type instanceof TLiteralString
                || $assertion_type instanceof TEnumCase)
        ) {
            if ($existing_var_type->hasMixed()) {
                return $existing_var_type;
            }

            return self::handleLiteralNegatedEquality(
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

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();
        $existing_var_type = $existing_var_type->getBuilder();

        $simple_negated_type = SimpleNegatedAssertionReconciler::reconcile(
            $statements_analyzer->getCodebase(),
            $assertion,
            $existing_var_type->freeze(),
            $key,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
            $is_equality,
            $inside_loop,
        );

        if ($simple_negated_type) {
            return $simple_negated_type;
        }

        $assertion_type = $assertion->getAtomicType();

        if (($assertion instanceof IsNotType
                && $assertion_type instanceof TIterable
                && $assertion_type->type_params[1]->isMixed())
            || $assertion instanceof IsNotCountable
        ) {
            $existing_var_type->removeType('array');
        }

        if (!$is_equality
            && isset($existing_var_atomic_types['int'])
            && $existing_var_type->from_calculation
            && ($assertion_type instanceof TInt || $assertion_type instanceof TFloat)
        ) {
            $existing_var_type->removeType($assertion_type->getKey());

            if ($assertion_type instanceof TInt) {
                $existing_var_type->addType(new TFloat);
            } else {
                $existing_var_type->addType(new TInt);
            }

            $existing_var_type->from_calculation = false;

            return $existing_var_type->freeze();
        }

        if (!$is_equality
            && $assertion_type instanceof TNamedObject
            && ($assertion_type->value === 'DateTime' || $assertion_type->value === 'DateTimeImmutable')
            && isset($existing_var_atomic_types['DateTimeInterface'])
        ) {
            $existing_var_type->removeType('DateTimeInterface');

            if ($assertion_type->value === 'DateTime') {
                $existing_var_type->addType(new TNamedObject('DateTimeImmutable'));
            } else {
                $existing_var_type->addType(new TNamedObject('DateTime'));
            }

            return $existing_var_type->freeze();
        }

        if (!$is_equality && $assertion_type instanceof TNamedObject) {
            foreach ($existing_var_type->getAtomicTypes() as $key => $type) {
                if ($type instanceof TEnumCase && $type->value === $assertion_type->value) {
                    $existing_var_type->removeType($key);
                }
            }
        }

        $codebase = $statements_analyzer->getCodebase();

        if ($assertion_type instanceof TNamedObject
            && strtolower($assertion_type->value) === 'traversable'
            && isset($existing_var_atomic_types['iterable'])
        ) {
            /** @var TIterable */
            $iterable = $existing_var_atomic_types['iterable'];
            $existing_var_type->removeType('iterable');
            $existing_var_type->addType(new TArray(
                [
                    $iterable->type_params[0]->hasMixed()
                        ? Type::getArrayKey()
                        : $iterable->type_params[0],
                    $iterable->type_params[1],
                ],
            ));
        } elseif ($assertion_type !== null && get_class($assertion_type) === TInt::class
            && isset($existing_var_type->getAtomicTypes()['array-key'])
            && !$is_equality
        ) {
            $existing_var_type->removeType('array-key');
            $existing_var_type->addType(new TString);
        } elseif ($assertion instanceof IsClassNotEqual) {
            // do nothing
        } elseif ($assertion_type instanceof TClassString && $assertion_type->is_loaded) {
            // do nothing
        } elseif ($existing_var_type->isSingle()
            && $existing_var_type->hasNamedObjectType()
            && $assertion_type instanceof TNamedObject
            && isset($existing_var_type->getAtomicTypes()[$assertion_type->getKey()])
        ) {
            // checking if two types share a common parent is not enough to guarantee childs are instanceof each other
            // fall through
        } elseif ($existing_var_type->isArray()
            && ($assertion->getAtomicType() instanceof TArray
                || $assertion->getAtomicType() instanceof TKeyedArray
                || $assertion->getAtomicType() instanceof TList)
        ) {
            //if both types are arrays, try to combine them
            $combined_type = TypeCombiner::combine(
                array_merge(array_values($existing_var_type->getAtomicTypes()), [$assertion->getAtomicType()]),
                $codebase,
            );
            $existing_var_type->removeType('array');
            if ($combined_type->isSingle()) {
                $existing_var_type->addType($combined_type->getSingleAtomic());
            }
        } elseif (!$is_equality) {
            $assertion_type = $assertion->getAtomicType();

            // if there wasn't a direct hit, go deeper, eliminating subtypes
            if ($assertion_type && !$existing_var_type->removeType($assertion_type->getKey())) {
                if ($assertion_type instanceof TNamedObject) {
                    foreach ($existing_var_type->getAtomicTypes() as $part_name => $existing_var_type_part) {
                        if (!$existing_var_type_part->isObjectType()) {
                            continue;
                        }

                        if (!$existing_var_type_part instanceof TTemplateParam
                            && AtomicTypeComparator::isContainedBy(
                                $codebase,
                                $existing_var_type_part,
                                $assertion_type,
                                false,
                                false,
                            )) {
                            $existing_var_type->removeType($part_name);
                        } elseif (AtomicTypeComparator::isContainedBy(
                            $codebase,
                            $assertion_type,
                            $existing_var_type_part,
                            false,
                            false,
                        )) {
                            $existing_var_type->different = true;
                        }
                    }
                }
            }
        }

        $existing_var_type = $existing_var_type->freeze();

        if ($assertion instanceof IsNotIdentical
            && ($key !== '$this'
                || !($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer))
        ) {
            $assertion_type = new Union([$assertion->type]);

            if ($key
                && $code_location
                && !UnionTypeComparator::canExpressionTypesBeIdentical(
                    $statements_analyzer->getCodebase(),
                    $existing_var_type,
                    $assertion_type,
                )
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
        }

        if ($existing_var_type->isUnionEmpty()) {
            if ($key !== '$this'
                || !($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
            ) {
                if ($key && $code_location && !$is_equality) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $assertion,
                        false,
                        $negated,
                        $code_location,
                        $suppressed_issues,
                    );
                }
            }

            $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

            return $existing_var_type->from_docblock
                ? Type::getMixed()
                : Type::getNever();
        }

        return $existing_var_type;
    }

    /**
     * @param  TLiteralInt|TLiteralString|TLiteralFloat|TEnumCase $assertion_type
     * @param  string[]   $suppressed_issues
     */
    private static function handleLiteralNegatedEquality(
        StatementsAnalyzer $statements_analyzer,
        Assertion $assertion,
        Atomic $assertion_type,
        Union $existing_var_type,
        string $old_var_type_string,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues
    ): Union {
        $existing_var_type = $existing_var_type->getBuilder();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $did_remove_type = false;
        $did_match_literal_type = false;

        $scalar_var_type = null;

        if ($assertion_type instanceof TLiteralInt) {
            if ($existing_var_type->hasInt()) {
                if ($existing_var_type->getLiteralInts()) {
                    $did_match_literal_type = true;

                    if ($existing_var_type->removeType($assertion_type->getKey())) {
                        $did_remove_type = true;
                    }
                }

                $existing_range_types = $existing_var_type->getRangeInts();

                if ($existing_range_types) {
                    foreach ($existing_range_types as $int_key => $literal_type) {
                        if ($literal_type->contains($assertion_type->value)) {
                            $did_remove_type = true;
                            $existing_var_type->removeType($int_key);
                            if ($literal_type->min_bound === null
                                || $literal_type->min_bound <= $assertion_type->value - 1
                            ) {
                                $existing_var_type->addType(new Type\Atomic\TIntRange(
                                    $literal_type->min_bound,
                                    $assertion_type->value - 1,
                                ));
                            }
                            if ($literal_type->max_bound === null
                                || $literal_type->max_bound >= $assertion_type->value + 1
                            ) {
                                $existing_var_type->addType(new Type\Atomic\TIntRange(
                                    $assertion_type->value + 1,
                                    $literal_type->max_bound,
                                ));
                            }
                        }
                    }
                }

                if (isset($existing_var_type->getAtomicTypes()['int'])
                    && get_class($existing_var_type->getAtomicTypes()['int']) === Type\Atomic\TInt::class
                ) {
                    $did_remove_type = true;
                    //this may be used to generate a range containing any int except the one that was asserted against
                    //but this is failing some tests
                    /*$existing_var_type->removeType('int');
                    $existing_var_type->addType(new Type\Atomic\TIntRange(null, $assertion_type->value - 1));
                    $existing_var_type->addType(new Type\Atomic\TIntRange($assertion_type->value + 1, null));*/
                }
            } else {
                $scalar_var_type = $assertion_type;
            }
        } elseif ($assertion_type instanceof TLiteralString) {
            if ($existing_var_type->hasString()) {
                if ($existing_var_type->getLiteralStrings()) {
                    $did_match_literal_type = true;

                    if ($existing_var_type->removeType($assertion_type->getKey())) {
                        $did_remove_type = true;
                    }
                } elseif ($assertion_type->value === "") {
                    $existing_var_type->addType(new TNonEmptyString());
                }
            } elseif (get_class($assertion_type) === TLiteralString::class) {
                $scalar_var_type = $assertion_type;
            }
        } elseif ($assertion_type instanceof TLiteralFloat) {
            if ($existing_var_type->hasFloat()) {
                if ($existing_var_type->getLiteralFloats()) {
                    $did_match_literal_type = true;

                    if ($existing_var_type->removeType($assertion_type->getKey())) {
                        $did_remove_type = true;
                    }
                }
            } else {
                $scalar_var_type = $assertion_type;
            }
        } else {
            $fq_enum_name = $assertion_type->value;
            $case_name = $assertion_type->case_name;

            foreach ($existing_var_type->getAtomicTypes() as $atomic_key => $atomic_type) {
                if (get_class($atomic_type) === TNamedObject::class
                    && $atomic_type->value === $fq_enum_name
                ) {
                    $codebase = $statements_analyzer->getCodebase();

                    $enum_storage = $codebase->classlike_storage_provider->get($fq_enum_name);

                    if (!$enum_storage->is_enum || !$enum_storage->enum_cases) {
                        $scalar_var_type = $assertion_type;
                    } else {
                        $existing_var_type->removeType($atomic_type->getKey());
                        $did_remove_type = true;

                        foreach ($enum_storage->enum_cases as $alt_case_name => $_) {
                            if ($alt_case_name === $case_name) {
                                continue;
                            }

                            $existing_var_type->addType(new TEnumCase($fq_enum_name, $alt_case_name));
                        }
                    }
                } elseif ($atomic_type instanceof TEnumCase
                    && $atomic_type->value === $fq_enum_name
                    && $atomic_type->case_name !== $case_name
                ) {
                    $did_match_literal_type = true;
                } elseif ($atomic_key === $assertion_type->getKey()) {
                    $existing_var_type->removeType($assertion_type->getKey());
                    $did_remove_type = true;
                }
            }
        }

        $existing_var_type = $existing_var_type->freeze();

        if ($key && $code_location) {
            if ($did_match_literal_type
                && (!$did_remove_type || count($existing_var_atomic_types) === 1)
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues,
                );
            } elseif ($scalar_var_type
                && $assertion instanceof IsNotIdentical
                && ($key !== '$this'
                    || !($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer))
            ) {
                if (!UnionTypeComparator::canExpressionTypesBeIdentical(
                    $statements_analyzer->getCodebase(),
                    $existing_var_type,
                    new Union([$scalar_var_type]),
                )) {
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
            }
        }

        return $existing_var_type;
    }
}
