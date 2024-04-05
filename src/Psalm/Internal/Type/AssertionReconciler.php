<?php

namespace Psalm\Internal\Type;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
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
use Psalm\Storage\Assertion\NestedAssertions;
use Psalm\Storage\Assertion\NonEmpty;
use Psalm\Storage\Assertion\NonEmptyCountable;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_intersect_key;
use function array_merge;
use function count;
use function get_class;
use function is_string;

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
        bool $negated = false
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

        $old_var_type_string = $existing_var_type->getId();

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

    private static function getMissingType(
        Assertion $assertion,
        bool $inside_loop
    ): Union {
        if (($assertion instanceof IsIsset || $assertion instanceof IsEqualIsset)
            || $assertion instanceof NonEmpty
        ) {
            return Type::getMixed($inside_loop);
        }

        if ($assertion instanceof ArrayKeyExists
            || $assertion instanceof NonEmptyCountable
            || $assertion instanceof HasExactCount
            || $assertion instanceof HasAtLeastCount
        ) {
            return Type::getMixed();
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
        int &$failed_reconciliation
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

            foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                if ($existing_var_type_part instanceof TNamedObject
                    || $existing_var_type_part instanceof TTemplateParam
                ) {
                    $acceptable_atomic_types[] = $existing_var_type_part;
                } else {
                    if (AtomicTypeComparator::isContainedBy(
                        $codebase,
                        $existing_var_type_part,
                        $new_as_atomic,
                    )) {
                        $acceptable_atomic_types[] = $existing_var_type_part;
                    }
                }
            }

            if ($acceptable_atomic_types) {
                $acceptable_atomic_types =
                    count($acceptable_atomic_types) === count($existing_var_type->getAtomicTypes())
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
                ) {
                    $existing_var_type_part = $existing_var_type_part->addIntersectionType($new_type_part);
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

            if ($code_location
                && $key
                && !$assertion->hasEquality()
                && $new_type_part instanceof TNamedObject
                && !$new_type_has_interface
                && (!($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
                    || $key !== '$this')
                && UnionTypeComparator::isContainedBy(
                    $codebase,
                    $existing_var_type,
                    new Union([$new_type_part]),
                    false,
                    false,
                    null,
                    false,
                    false,
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

            $intersection_type = self::filterTypeWithAnother(
                $codebase,
                $existing_var_type,
                new Union([$new_type_part]),
                $any_scalar_type_match_found,
            );

            if ($code_location
                && !$intersection_type
                && (!$assertion instanceof IsLooselyEqual || !$any_scalar_type_match_found)
            ) {
                if ($new_type_part instanceof TNull) {
                    if ($existing_var_type->from_docblock) {
                        IssueBuffer::maybeAdd(
                            new DocblockTypeContradiction(
                                'Cannot resolve types for ' . $key . ' - docblock-defined type '
                                    . $existing_var_type . ' does not contain null',
                                $code_location,
                                $existing_var_type->getId() . ' null',
                            ),
                            $suppressed_issues,
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new TypeDoesNotContainNull(
                                'Cannot resolve types for ' . $key . ' - ' . $existing_var_type
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
                                'Cannot resolve types for ' . $key . ' - docblock-defined type '
                                    . $existing_var_type->getId() . ' does not contain ' . $new_type_part->getId(),
                                $code_location,
                                $existing_var_type->getId() . ' ' . $new_type_part->getId(),
                            ),
                            $suppressed_issues,
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new TypeDoesNotContainType(
                                'Cannot resolve types for ' . $key . ' - ' . $existing_var_type->getId()
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
            }
        }

        return $new_type ?: new Union([$new_type_part]);
    }

    /**
     * This method receives two types. The goal is to use datas in the new type to reduce the existing_type to a more
     * precise version. For example: new is `array<int>` old is `list<mixed>` so the result is `list<int>`
     */
    private static function filterTypeWithAnother(
        Codebase $codebase,
        Union &$existing_type,
        Union $new_type,
        bool &$any_scalar_type_match_found = false
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

    private static function filterAtomicWithAnother(
        Atomic &$type_1_atomic,
        Atomic $type_2_atomic,
        Codebase $codebase,
        bool &$any_scalar_type_match_found
    ): ?Atomic {
        if ($type_1_atomic instanceof TFloat
            && $type_2_atomic instanceof TInt
        ) {
            $any_scalar_type_match_found = true;
            return $type_2_atomic;
        }

        if ($type_1_atomic instanceof TNamedObject) {
            $type_1_atomic = $type_1_atomic->setIsStatic(false);
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

        if ($atomic_contained_by) {
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

        $matching_atomic_type = null;

        if ($type_1_atomic instanceof TNamedObject
            && $type_2_atomic instanceof TNamedObject
            && ($codebase->interfaceExists($type_1_atomic->value)
                || $codebase->interfaceExists($type_2_atomic->value))
        ) {
            return $type_2_atomic->addIntersectionType($type_1_atomic);
        }

        /*if ($type_2_atomic instanceof TKeyedArray
            && $type_1_atomic instanceof \Psalm\Type\Atomic\TList
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

                return new TKeyedArray(
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

                return new TKeyedArray(
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

            $matching_atomic_type = new TKeyedArray(
                $type_1_properties,
                $type_1_atomic->class_strings,
                $fallback_types,
                $type_1_atomic->is_list,
                $type_1_atomic->from_docblock,
            );
            $atomic_comparison_results->type_coerced = true;
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

        // Lowercase-string and non-empty-string are compatible but none is contained into the other completely
        if (($type_2_atomic instanceof TLowercaseString && $type_1_atomic instanceof TNonEmptyString) ||
            ($type_2_atomic instanceof TNonEmptyString && $type_1_atomic instanceof TLowercaseString)
        ) {
            $matching_atomic_type = new TNonEmptyLowercaseString();
        }

        if (!$atomic_comparison_results->type_coerced && $atomic_comparison_results->scalar_type_match_found) {
            $any_scalar_type_match_found = true;
        }

        return $matching_atomic_type;
    }

    private static function refineContainedAtomicWithAnother(
        Atomic $type_1_atomic,
        Atomic $type_2_atomic,
        Codebase $codebase,
        bool $type_coerced
    ): ?Atomic {
        if ($type_coerced
            && get_class($type_2_atomic) === TNamedObject::class
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
        array              $suppressed_issues
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
                if (get_class($atomic_type) === TNamedObject::class
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
        array              $suppressed_issues
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

            if ($existing_var_atomic_type instanceof TTemplateParam) {
                $compatible_int_type = self::getCompatibleIntType(
                    $existing_var_type,
                    $existing_var_atomic_type->as->getAtomicTypes(),
                    $assertion_type,
                    $assertion instanceof IsLooselyEqual,
                );
                if ($compatible_int_type !== null) {
                    return $compatible_int_type;
                }

                $existing_var_atomic_type = $existing_var_atomic_type->replaceAs(
                    self::handleLiteralEquality(
                        $statements_analyzer,
                        $assertion,
                        $assertion_type,
                        $existing_var_atomic_type->as,
                        $old_var_type_string,
                        $var_id,
                        $negated,
                        $code_location,
                        $suppressed_issues,
                    ),
                );

                return new Union([$existing_var_atomic_type]);
            }

            if ($assertion instanceof IsLooselyEqual
                && $existing_var_atomic_type instanceof TLiteralFloat
                && (int)$existing_var_atomic_type->value === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }

            if ($assertion instanceof IsLooselyEqual
                && $existing_var_atomic_type instanceof TLiteralString
                && (int)$existing_var_atomic_type->value === $value
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
        array              $suppressed_issues
    ): Union {
        $value = $assertion_type->value;

        // we create the literal that is being asserted. We'll return this when we're sure this is the resulting type
        $literal_asserted_type_string = new Union([$assertion_type], [
            'from_docblock' => $existing_var_type->from_docblock,
        ]);

        $compatible_string_type = self::getCompatibleStringType(
            $existing_var_type,
            $existing_var_atomic_types,
            $assertion_type,
            $assertion instanceof IsLooselyEqual,
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

                return $literal_asserted_type_string;
            }

            if ($existing_var_atomic_type instanceof TString && !$existing_var_atomic_type instanceof TLiteralString) {
                return $literal_asserted_type_string;
            }

            if ($existing_var_atomic_type instanceof TTemplateParam) {
                $compatible_string_type = self::getCompatibleStringType(
                    $existing_var_type,
                    $existing_var_atomic_type->as->getAtomicTypes(),
                    $assertion_type,
                    $assertion instanceof IsLooselyEqual,
                );
                if ($compatible_string_type !== null) {
                    return $compatible_string_type;
                }

                if ($existing_var_atomic_type->as->hasString()) {
                    return $literal_asserted_type_string;
                }

                $existing_var_atomic_type = $existing_var_atomic_type->replaceAs(
                    self::handleLiteralEquality(
                        $statements_analyzer,
                        $assertion,
                        $assertion_type,
                        $existing_var_atomic_type->as,
                        $old_var_type_string,
                        $var_id,
                        $negated,
                        $code_location,
                        $suppressed_issues,
                    ),
                );

                return new Union([$existing_var_atomic_type]);
            }

            if ($assertion instanceof IsLooselyEqual
                && $existing_var_atomic_type instanceof TLiteralInt
                && (string)$existing_var_atomic_type->value === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }

            if ($assertion instanceof IsLooselyEqual
                && $existing_var_atomic_type instanceof TLiteralFloat
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
        array              $suppressed_issues
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

            if ($existing_var_atomic_type instanceof TTemplateParam) {
                $compatible_float_type = self::getCompatibleFloatType(
                    $existing_var_type,
                    $existing_var_atomic_type->as->getAtomicTypes(),
                    $assertion_type,
                    $assertion instanceof IsLooselyEqual,
                );
                if ($compatible_float_type !== null) {
                    return $compatible_float_type;
                }

                if ($existing_var_atomic_type->as->hasFloat()) {
                    return $literal_asserted_type;
                }

                $existing_var_atomic_type = $existing_var_atomic_type->replaceAs(
                    self::handleLiteralEquality(
                        $statements_analyzer,
                        $assertion,
                        $assertion_type,
                        $existing_var_atomic_type->as,
                        $old_var_type_string,
                        $var_id,
                        $negated,
                        $code_location,
                        $suppressed_issues,
                    ),
                );

                return new Union([$existing_var_atomic_type]);
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
     */
    private static function getCompatibleIntType(
        Union $existing_var_type,
        array $existing_var_atomic_types,
        TLiteralInt $assertion_type,
        bool $is_loose_equality
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
        Union $existing_var_type,
        array $existing_var_atomic_types,
        TLiteralString $assertion_type,
        bool $is_loose_equality
    ): ?Union {
        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TMixed
                || $existing_var_atomic_type instanceof TScalar
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
    private static function getCompatibleFloatType(
        Union $existing_var_type,
        array $existing_var_atomic_types,
        TLiteralFloat $assertion_type,
        bool $is_loose_equality
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
        bool &$should_return
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
                    new Union([$assertion_type->as_type ? $assertion_type->as_type : new TObject()]),
                    $assertion_type->defining_class,
                )];
            }
            return [$assertion_type];
        }

        if ($existing_has_string && !$existing_has_object) {
            if (!$allow_string_comparison && $code_location) {
                IssueBuffer::maybeAdd(
                    new TypeDoesNotContainType(
                        'Cannot allow string comparison to object for ' . $key,
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
}
