<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Closure;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\CoalesceAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\RedundantPropertyInitializationCheck;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\ArrayKeyDoesNotExist;
use Psalm\Storage\Assertion\DoesNotHaveAtLeastCount;
use Psalm\Storage\Assertion\DoesNotHaveExactCount;
use Psalm\Storage\Assertion\DoesNotHaveMethod;
use Psalm\Storage\Assertion\Empty_;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\IsGreaterThanOrEqualTo;
use Psalm\Storage\Assertion\IsLessThanOrEqualTo;
use Psalm\Storage\Assertion\IsNotIdentical;
use Psalm\Storage\Assertion\IsNotIsset;
use Psalm\Storage\Assertion\IsNotLooselyEqual;
use Psalm\Storage\Assertion\NotInArray;
use Psalm\Storage\Assertion\NotNonEmptyCountable;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TEmptyNumeric;
use Psalm\Type\Atomic\TEmptyScalar;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseNumericString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\MutableUnion;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function assert;
use function str_contains;
use function strpbrk;
use function strtolower;

/**
 * @internal
 */
final class SimpleNegatedAssertionReconciler extends Reconciler
{
    /**
     * @param  string[]   $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    public static function reconcile(
        Codebase $codebase,
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key = null,
        bool $negated = false,
        ?CodeLocation $code_location = null,
        array $suppressed_issues = [],
        int &$failed_reconciliation = Reconciler::RECONCILIATION_EMPTY,
        bool $is_equality = false,
        bool $inside_loop = false,
    ): ?Union {
        $old_var_type_string = $existing_var_type->getId();

        if ($assertion instanceof IsNotIsset) {
            if ($existing_var_type->possibly_undefined) {
                // a variable that is not set reads as null (and may still be undefined), an offset or a property
                // that is not set has no value
                return $key !== null && $key[0] === '$' && strpbrk($key, '[-:') === false
                    ? Type::getNull()->setPossiblyUndefined(true)
                    : Type::getNever();
            }

            if (!$existing_var_type->isNullable()
                && $existing_var_type->initialized
                && !$existing_var_type->ignore_isset
                && $key
                && !str_contains($key, '[')
                && (!$existing_var_type->hasMixed() || $existing_var_type->isAlwaysTruthy())
            ) {
                if ($code_location) {
                    if ($existing_var_type->from_static_property) {
                        IssueBuffer::maybeAdd(
                            new RedundantPropertyInitializationCheck(
                                'Static property ' . $key . ' with type '
                                    . $existing_var_type
                                    . ' has unexpected isset check — should it be nullable?',
                                $code_location,
                            ),
                            $suppressed_issues,
                        );
                    } elseif ($existing_var_type->from_property) {
                        IssueBuffer::maybeAdd(
                            new RedundantPropertyInitializationCheck(
                                'Property ' . $key . ' with type '
                                    . $existing_var_type . ' should already be set in the constructor',
                                $code_location,
                            ),
                            $suppressed_issues,
                        );
                    } elseif ($existing_var_type->from_docblock) {
                        IssueBuffer::maybeAdd(
                            new DocblockTypeContradiction(
                                'Cannot resolve types for '
                                    . CoalesceAnalyzer::getDisplayName($key, $code_location)
                                    . ' with docblock-defined type '
                                    . $existing_var_type . ' and !isset assertion',
                                $code_location,
                                'cannot resolve !isset '.$existing_var_type. ' ' . $key,
                            ),
                            $suppressed_issues,
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new TypeDoesNotContainType(
                                'Cannot resolve types for '
                                    . CoalesceAnalyzer::getDisplayName($key, $code_location) . ' with type '
                                    . $existing_var_type . ' and !isset assertion',
                                $code_location,
                                'cannot resolve !isset '.$existing_var_type. ' ' . $key,
                            ),
                            $suppressed_issues,
                        );
                    }
                }

                return Type::getNever();
            }

            // a typed property that nothing initializes (see InstancePropertyFetchAnalyzer) has no value when it is
            // not set: reading it throws
            if ($existing_var_type->ignore_isset
                && !$existing_var_type->initialized
                && !$existing_var_type->isNullable()
            ) {
                return Type::getNever();
            }

            return Type::getNull();
        }

        if ($assertion instanceof ArrayKeyDoesNotExist) {
            return Type::getNever();
        }

        if ($assertion instanceof DoesNotHaveMethod) {
            return self::reconcileDoesNotHaveMethod(
                $assertion,
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
            );
        }

        if ($assertion instanceof NotInArray) {
            $new_var_type = $assertion->type;

            $intersection = Type::intersectUnionTypes(
                $new_var_type,
                $existing_var_type,
                $codebase,
            );

            if ($intersection === null) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $existing_var_type->getId(),
                        $key,
                        $assertion,
                        true,
                        $negated,
                        $code_location,
                        $suppressed_issues,
                    );
                }

                $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;
            }

            return $existing_var_type;
        }

        if ($assertion instanceof Falsy || $assertion instanceof Empty_) {
            return self::reconcileFalsyOrEmpty(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                false,
            );
        }

        if ($assertion instanceof NotNonEmptyCountable) {
            return self::reconcileNotNonEmptyCountable(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $is_equality,
                null,
            );
        }

        if ($assertion instanceof DoesNotHaveAtLeastCount) {
            return self::reconcileNotNonEmptyCountable(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $is_equality,
                $assertion->count,
            );
        }

        if ($assertion instanceof DoesNotHaveExactCount) {
            return $existing_var_type;
        }

        if ($assertion instanceof IsLessThanOrEqualTo) {
            return IntBoundReconciler::reconcileMaxBound(
                $assertion,
                $existing_var_type,
                $inside_loop,
                $old_var_type_string,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
            );
        }

        if ($assertion instanceof IsGreaterThanOrEqualTo) {
            return IntBoundReconciler::reconcileMinBound(
                $assertion,
                $existing_var_type,
                $inside_loop,
                $old_var_type_string,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
            );
        }

        $assertion_type = $assertion->getAtomicType();

        if ($assertion_type instanceof TObject && !$existing_var_type->hasMixed()) {
            return self::reconcileObject(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type instanceof TScalar && !$existing_var_type->hasMixed()) {
            return self::reconcileScalar(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type instanceof TResource && !$existing_var_type->hasMixed()) {
            return self::reconcileResource(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type && $assertion_type::class === TBool::class && !$existing_var_type->hasMixed()) {
            return self::reconcileBool(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type instanceof TNumeric && !$existing_var_type->hasMixed()) {
            return self::reconcileNumeric(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type instanceof TFloat && !$existing_var_type->hasMixed()) {
            return self::reconcileFloat(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type && $assertion_type::class === TInt::class && !$existing_var_type->hasMixed()) {
            return self::reconcileInt(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type && $assertion_type::class === TString::class && !$existing_var_type->hasMixed()) {
            return self::reconcileString(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type instanceof TArray
            && !$existing_var_type->hasMixed()
            && $assertion_type->type_params[0]->isArrayKey()
            && $assertion_type->type_params[1]->isMixed()
        ) {
            return self::reconcileArray(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type instanceof TNull && !$existing_var_type->hasMixed()) {
            return self::reconcileNull(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if (($assertion_type instanceof TTrue || $assertion_type instanceof TFalse)
            && !$existing_var_type->hasMixed()
        ) {
            return self::reconcileBoolLiteral(
                $assertion_type instanceof TTrue,
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type instanceof TCallable && !self::isComparedWithOneValue($assertion)) {
            return self::reconcileCallable(
                $existing_var_type,
                $codebase,
                $assertion_type,
            );
        }

        return null;
    }

    private static function reconcileCallable(
        Union $existing_var_type,
        Codebase $codebase,
        TCallable $assertion_type,
    ): Union {
        $existing_var_type = $existing_var_type->getBuilder();
        foreach ($existing_var_type->getAtomicTypes() as $atomic_key => $type) {
            if ($type instanceof TLiteralString
                && InternalCallMapHandler::inCallMap($type->value)
            ) {
                $existing_var_type->removeType($atomic_key);
                continue;
            }

            if ($type->isCallableType()) {
                $existing_var_type->removeType($atomic_key);
                continue;
            }

            $candidate_callable = CallableTypeComparator::getCallableFromAtomic(
                $codebase,
                $type,
                $assertion_type,
            );

            if ($candidate_callable) {
                $existing_var_type->removeType($atomic_key);
            }
        }

        return $existing_var_type->freeze();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileBool(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $compared_with_one_value = self::isComparedWithOneValue($assertion);
        $non_bool_types = [];
        $redundant = true;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type = self::reconcileTemplateBound(
                    $type,
                    self::reconcileBool(...),
                    $assertion,
                    $suppressed_issues,
                    $is_equality,
                    $redundant,
                );

                if ($type !== null) {
                    $non_bool_types[] = $type;
                }
            } elseif ($compared_with_one_value && ($type instanceof TBool || $type instanceof TScalar)) {
                $redundant = false;
                $non_bool_types[] = $type;
            } elseif (!$type instanceof TBool
                || ($is_equality && $type::class === TBool::class)
            ) {
                if ($type instanceof TScalar) {
                    $redundant = false;
                    $non_bool_types[] = new TString();
                    $non_bool_types[] = new TInt();
                    $non_bool_types[] = new TFloat();
                } else {
                    $non_bool_types[] = $type;
                }
            } else {
                $redundant = false;
            }
        }

        return self::finishReconciliation(
            self::combineRemainingTypes($non_bool_types, $existing_var_type),
            $redundant,
            !$is_equality,
            $existing_var_type,
            $old_var_type_string,
            $key,
            $assertion,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
        );
    }

    /**
     * @param   string[]  $suppressed_issues
     */
    private static function reconcileNotNonEmptyCountable(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        bool $is_equality,
        ?int $count,
    ): Union {
        $existing_var_type = $existing_var_type->getBuilder();
        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if (isset($existing_var_atomic_types['array'])) {
            $array_atomic_type = $existing_var_type->getArray();
            $redundant = true;

            if ($array_atomic_type instanceof TKeyedArray) {
                if ($count !== null) {
                    $prop_max_count = $array_atomic_type->getMaxCount();
                    $prop_min_count = $array_atomic_type->getMinCount();

                    // !(count($a) >= 3)
                    // count($a) < 3

                    // We're asserting that count($a) < $count
                    // If it's impossible, remove the type
                    // If it's possible but redundant, mark as redundant
                    // If it's possible, mark as not redundant

                    // Impossible because count($a) >= $count always
                    if ($prop_min_count >= $count) {
                        $redundant = false;

                        $existing_var_type->removeType('array');

                        // Redundant because count($a) < $count always
                    } elseif ($prop_max_count && $prop_max_count < $count) {
                        $redundant = true;

                        // Possible
                    } else {
                        if ($array_atomic_type->is_list && $array_atomic_type->fallback_params) {
                            $properties = [];
                            for ($x = 0; $x < ($count-1); $x++) {
                                $properties []= $array_atomic_type->properties[$x]
                                    ?? $array_atomic_type->fallback_params[1]->setPossiblyUndefined(true);
                            }
                            $existing_var_type->removeType('array');
                            if (!$properties) {
                                $existing_var_type->addType(Type::getEmptyArrayAtomic());
                            } else {
                                $existing_var_type->addType(TKeyedArray::make(
                                    $properties,
                                    null,
                                    null,
                                    true,
                                    $array_atomic_type->from_docblock,
                                ));
                            }
                        }
                        $redundant = false;
                    }
                } else {
                    if ($array_atomic_type->isNonEmpty()) {
                        // Impossible, never empty
                        $redundant = false;
                        $existing_var_type->removeType('array');
                    } else {
                        // Possible, can be empty
                        $redundant = false;
                        $existing_var_type->removeType('array');
                        $existing_var_type->addType(Type::getEmptyArrayAtomic());
                    }
                }
            } elseif (!$array_atomic_type instanceof TArray || !$array_atomic_type->isEmptyArray()) {
                $redundant = false;

                if (!$count) {
                    $existing_var_type->addType(new TArray(
                        [
                            new Union([new TNever()]),
                            new Union([new TNever()]),
                        ],
                    ));
                }
            }

            if (!$is_equality
                && !$existing_var_type->hasMixed()
                && ($redundant || $existing_var_type->isUnionEmpty())
            ) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $assertion,
                        $redundant,
                        $negated,
                        $code_location,
                        $suppressed_issues,
                    );
                }
            }
        }

        return $existing_var_type->freeze();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileNull(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
    ): Union {
        $types = $existing_var_type->getAtomicTypes();
        $old_var_type_string = $existing_var_type->getId();
        $redundant = true;

        if (isset($types['null'])) {
            $redundant = false;
            unset($types['null']);
        }

        foreach ($types as &$type) {
            if ($type instanceof TTemplateParam) {
                $new = $type->replaceAs(self::reconcileNull(
                    $assertion,
                    $type->as,
                    null,
                    false,
                    null,
                    $suppressed_issues,
                    $failed_reconciliation,
                    $is_equality,
                ));

                //if ($new !== $type) {
                //    $redundant = false;
                //}
                // TODO: This is technically wrong, but for some reason we get a
                // duplicated assertion here when using template types.
                $redundant = false;
                $type = $new;
            }
        }
        unset($type);

        return self::finishReconciliation(
            $types ? $existing_var_type->setTypes($types) : null,
            $redundant,
            !$is_equality,
            $existing_var_type,
            $old_var_type_string,
            $key,
            $assertion,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
        );
    }

    /**
     * @param bool $value the value asserted to be excluded
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileBoolLiteral(
        bool $value,
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
    ): Union {
        $types = $existing_var_type->getAtomicTypes();
        $old_var_type_string = $existing_var_type->getId();
        $redundant = true;

        if (isset($types['scalar'])) {
            $redundant = false;
        }
        if (isset($types['bool'])) {
            $redundant = false;
            $types[] = $value ? new TFalse() : new TTrue();
            unset($types['bool']);
        }
        $literal_key = $value ? 'true' : 'false';
        if (isset($types[$literal_key])) {
            $redundant = false;
            unset($types[$literal_key]);
        }

        foreach ($types as $k => $type) {
            if ($type instanceof TTemplateParam) {
                $type = self::reconcileTemplateBound(
                    $type,
                    static fn(
                        Assertion $assertion,
                        Union $as,
                        ?string $key,
                        bool $negated,
                        ?CodeLocation $code_location,
                        array $suppressed_issues,
                        int &$failed_reconciliation,
                        bool $is_equality,
                    ): Union => self::reconcileBoolLiteral(
                        $value,
                        $assertion,
                        $as,
                        $key,
                        $negated,
                        $code_location,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                    ),
                    $assertion,
                    $suppressed_issues,
                    $is_equality,
                    $redundant,
                );

                if ($type === null) {
                    unset($types[$k]);
                } else {
                    $types[$k] = $type;
                }
            }
        }

        return self::finishReconciliation(
            $types ? $existing_var_type->setTypes($types) : null,
            $redundant,
            !$is_equality,
            $existing_var_type,
            $old_var_type_string,
            $key,
            $assertion,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
        );
    }

    /**
     * @param   Falsy|Empty_ $assertion
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileFalsyOrEmpty(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $recursive_check,
    ): Union {
        $existing_var_type = $existing_var_type->getBuilder();
        $old_var_type_string = $existing_var_type->getId();

        $redundant = !($existing_var_type->possibly_undefined
            || $existing_var_type->possibly_undefined_from_try);

        foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_key => $existing_var_type_part) {
            //if any atomic in the union is either always truthy, we remove it. If not always falsy, we mark the check
            //as not redundant.
            if (!$existing_var_type->possibly_undefined
                && !$existing_var_type->possibly_undefined_from_try
                && $existing_var_type_part->isTruthy()
            ) {
                $redundant = false;
                $existing_var_type->removeType($existing_var_type_key);
            } elseif (!$existing_var_type_part->isFalsy()) {
                $redundant = false;
            }
        }

        if (!$redundant && $existing_var_type->isUnionEmpty()) {
            //every type was removed, this is an impossible assertion
            if ($code_location && $key && !$recursive_check) {
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

            $failed_reconciliation = 2;

            return Type::getNever();
        }

        if ($redundant) {
            //nothing was removed, this is a redundant assertion
            if ($code_location && $key && !$recursive_check) {
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

            $failed_reconciliation = 1;

            return $existing_var_type->freeze();
        }

        if ($existing_var_type->hasType('bool')) {
            $existing_var_type->removeType('bool');
            $existing_var_type->addType(new TFalse());
        }

        if ($existing_var_type->hasArray()) {
            $existing_var_type->removeType('array');
            $existing_var_type->addType(new TArray(
                [
                    new Union([new TNever()]),
                    new Union([new TNever()]),
                ],
            ));
        }

        if ($existing_var_type->hasMixed()) {
            $mixed_atomic_type = $existing_var_type->getAtomicTypes()['mixed'];

            if ($mixed_atomic_type::class === TMixed::class) {
                $existing_var_type->removeType('mixed');
                $existing_var_type->addType(new TEmptyMixed());
            }
        }

        if ($existing_var_type->hasScalar()) {
            $scalar_atomic_type = $existing_var_type->getAtomicTypes()['scalar'];

            if ($scalar_atomic_type::class === TScalar::class) {
                $existing_var_type->removeType('scalar');
                $existing_var_type->addType(new TEmptyScalar());
            }
        }

        if ($existing_var_type->hasType('string')) {
            $string_atomic_type = $existing_var_type->getAtomicTypes()['string'];

            if ($string_atomic_type::class === TString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(Type::getAtomicStringFromLiteral(''));
                $existing_var_type->addType(Type::getAtomicStringFromLiteral('0'));
            } elseif ($string_atomic_type::class === TNonEmptyString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(Type::getAtomicStringFromLiteral('0'));
            } elseif ($string_atomic_type::class === TNonEmptyLowercaseString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(Type::getAtomicStringFromLiteral('0'));
            } elseif ($string_atomic_type::class === TNonEmptyNonspecificLiteralString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(Type::getAtomicStringFromLiteral('0'));
            }
        }

        if ($existing_var_type->hasType('numeric-string')) {
            $numeric_string_atomic_type = $existing_var_type->getAtomicTypes()['numeric-string'];

            if ($numeric_string_atomic_type::class === TNumericString::class
                || $numeric_string_atomic_type instanceof TLowercaseNumericString
            ) {
                $existing_var_type->removeType('numeric-string');
                $existing_var_type->addType(Type::getAtomicStringFromLiteral('0'));
            }
        }

        // "0" is the only falsy decimal int string, "" the only other falsy string
        if ($existing_var_type->hasType('decimal-int-string')) {
            $existing_var_type->removeType('decimal-int-string');
            $existing_var_type->addType(Type::getAtomicStringFromLiteral('0'));
        }

        if ($existing_var_type->hasType('non-decimal-int-string')) {
            $existing_var_type->removeType('non-decimal-int-string');
            $existing_var_type->addType(Type::getAtomicStringFromLiteral(''));
        }

        if ($existing_var_type->hasInt()) {
            $existing_range_types = $existing_var_type->getRangeInts();

            if ($existing_range_types) {
                foreach ($existing_range_types as $int_key => $literal_type) {
                    if ($literal_type->contains(0)) {
                        $existing_var_type->removeType($int_key);
                        $existing_var_type->addType(new TLiteralInt(0));
                    }
                }
            } else {
                $existing_var_type->removeType('int');
                $existing_var_type->addType(new TLiteralInt(0));
            }
        }

        if ($existing_var_type->hasFloat()) {
            $existing_var_type->removeType('float');
            $existing_var_type->addType(new TLiteralFloat(0.0));
        }

        if ($existing_var_type->hasNumeric()) {
            $existing_var_type->removeType('numeric');
            $existing_var_type->addType(new TEmptyNumeric());
        }

        foreach ($existing_var_type->getAtomicTypes() as $type_key => $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TTemplateParam) {
                if (!$existing_var_atomic_type->as->isMixed()) {
                    $template_did_fail = 0;

                    $existing_var_atomic_type = $existing_var_atomic_type->replaceAs(self::reconcileFalsyOrEmpty(
                        $assertion,
                        $existing_var_atomic_type->as,
                        $key,
                        $negated,
                        $code_location,
                        $suppressed_issues,
                        $template_did_fail,
                        $recursive_check,
                    ));

                    if ($template_did_fail !== Reconciler::RECONCILIATION_EMPTY) {
                        $existing_var_type->removeType($type_key);
                        $existing_var_type->addType($existing_var_atomic_type);
                    }
                }
            }
        }

        assert(!$existing_var_type->isUnionEmpty());
        return $existing_var_type->freeze();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileScalar(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_scalar_types = [];
        $redundant = true;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type = self::reconcileTemplateBound(
                    $type,
                    self::reconcileScalar(...),
                    $assertion,
                    $suppressed_issues,
                    $is_equality,
                    $redundant,
                );

                if ($type !== null) {
                    $non_scalar_types[] = $type;
                }
            } elseif (!($type instanceof Scalar)) {
                $non_scalar_types[] = $type;
            } else {
                $redundant = false;

                if ($is_equality) {
                    $non_scalar_types[] = $type;
                }
            }
        }

        return self::finishReconciliation(
            self::combineRemainingTypes($non_scalar_types, $existing_var_type),
            $redundant,
            true,
            $existing_var_type,
            $old_var_type_string,
            $key,
            $assertion,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
        );
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileObject(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $compared_with_one_value = self::isComparedWithOneValue($assertion);
        $non_object_types = [];
        // `true == $object`
        $redundant = !($assertion instanceof IsNotLooselyEqual && $existing_var_type->hasBool());

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type = self::reconcileTemplateBound(
                    $type,
                    self::reconcileObject(...),
                    $assertion,
                    $suppressed_issues,
                    $is_equality,
                    $redundant,
                );

                if ($type !== null) {
                    $non_object_types[] = $type;
                }
            } elseif ($compared_with_one_value && ($type instanceof TCallable || $type instanceof TIterable)) {
                $redundant = false;
                $non_object_types[] = $type;
            } elseif ($type instanceof TCallable) {
                $non_object_types[] = TKeyedArray::makeCallable([
                    new Union([new TClassString, new TObject]),
                    Type::getNonEmptyString(),
                ]);
                $non_object_types[] = new TCallableString();
                $redundant = false;
            } elseif ($type instanceof TIterable) {
                $params = $type->type_params;
                $params[0] = self::refineArrayKey($params[0]);
                $non_object_types[] = new TArray($params);

                $redundant = false;
            } elseif (!$type->isObjectType()) {
                $non_object_types[] = $type;
            } else {
                $redundant = false;

                if ($is_equality) {
                    $non_object_types[] = $type;
                }
            }
        }

        return self::finishReconciliation(
            self::combineRemainingTypes($non_object_types, $existing_var_type),
            $redundant,
            true,
            $existing_var_type,
            $old_var_type_string,
            $key,
            $assertion,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
        );
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileNumeric(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $compared_with_one_value = self::isComparedWithOneValue($assertion);
        $non_numeric_types = [];
        $redundant = !($existing_var_type->hasString()
            || $existing_var_type->hasScalar());

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type = self::reconcileTemplateBound(
                    $type,
                    self::reconcileNumeric(...),
                    $assertion,
                    $suppressed_issues,
                    $is_equality,
                    $redundant,
                );

                if ($type !== null) {
                    $non_numeric_types[] = $type;
                }
            } elseif ($compared_with_one_value && ($type instanceof TArrayKey || $type instanceof TScalar)) {
                $redundant = false;
                $non_numeric_types[] = $type;
            } elseif ($type instanceof TArrayKey) {
                $redundant = false;
                $non_numeric_types[] = new TString();
            } elseif ($type instanceof TScalar) {
                $redundant = false;
                $non_numeric_types[] = new TString();
                $non_numeric_types[] = new TBool();
            } elseif (!$type->isNumericType()) {
                $non_numeric_types[] = $type;
            } else {
                $redundant = false;

                if ($is_equality) {
                    $non_numeric_types[] = $type;
                }
            }
        }

        return self::finishReconciliation(
            self::combineRemainingTypes($non_numeric_types, $existing_var_type),
            $redundant,
            !$is_equality,
            $existing_var_type,
            $old_var_type_string,
            $key,
            $assertion,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
        );
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileInt(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $compared_with_one_value = self::isComparedWithOneValue($assertion);
        $non_int_types = [];
        $redundant = true;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type = self::reconcileTemplateBound(
                    $type,
                    self::reconcileInt(...),
                    $assertion,
                    $suppressed_issues,
                    $is_equality,
                    $redundant,
                );

                if ($type !== null) {
                    $non_int_types[] = $type;
                }
            } elseif ($compared_with_one_value
                && ($type instanceof TArrayKey || $type instanceof TScalar || $type instanceof TNumeric)
            ) {
                $redundant = false;
                $non_int_types[] = $type;
            } elseif ($type instanceof TArrayKey) {
                $redundant = false;
                $non_int_types[] = new TString();
            } elseif ($type instanceof TScalar) {
                $redundant = false;
                $non_int_types[] = new TString();
                $non_int_types[] = new TFloat();
                $non_int_types[] = new TBool();
            } elseif ($type instanceof TInt) {
                $redundant = false;

                if ($is_equality) {
                    $non_int_types[] = $type;
                } elseif ($existing_var_type->from_calculation) {
                    $non_int_types[] = new TFloat();
                }
            } elseif ($type instanceof TNumeric) {
                $redundant = false;
                $non_int_types[] = new TString();
                $non_int_types[] = new TFloat();
            } else {
                $non_int_types[] = $type;
            }
        }

        return self::finishReconciliation(
            self::combineRemainingTypes($non_int_types, $existing_var_type),
            $redundant,
            !$is_equality,
            $existing_var_type,
            $old_var_type_string,
            $key,
            $assertion,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
        );
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileFloat(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $compared_with_one_value = self::isComparedWithOneValue($assertion);
        $non_float_types = [];
        $redundant = true;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type = self::reconcileTemplateBound(
                    $type,
                    self::reconcileFloat(...),
                    $assertion,
                    $suppressed_issues,
                    $is_equality,
                    $redundant,
                );

                if ($type !== null) {
                    $non_float_types[] = $type;
                }
            } elseif ($compared_with_one_value && ($type instanceof TScalar || $type instanceof TNumeric)) {
                $redundant = false;
                $non_float_types[] = $type;
            } elseif ($type instanceof TScalar) {
                $redundant = false;
                $non_float_types[] = new TString();
                $non_float_types[] = new TInt();
                $non_float_types[] = new TBool();
            } elseif ($type instanceof TFloat) {
                $redundant = false;

                if ($is_equality) {
                    $non_float_types[] = $type;
                }
            } elseif ($type instanceof TNumeric) {
                $redundant = false;
                $non_float_types[] = new TString();
                $non_float_types[] = new TInt();
            } else {
                $non_float_types[] = $type;
            }
        }

        return self::finishReconciliation(
            self::combineRemainingTypes($non_float_types, $existing_var_type),
            $redundant,
            !$is_equality,
            $existing_var_type,
            $old_var_type_string,
            $key,
            $assertion,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
        );
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileString(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $compared_with_one_value = self::isComparedWithOneValue($assertion);
        $non_string_types = [];
        $redundant = !$existing_var_type->hasScalar();

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type = self::reconcileTemplateBound(
                    $type,
                    self::reconcileString(...),
                    $assertion,
                    $suppressed_issues,
                    $is_equality,
                    $redundant,
                );

                if ($type !== null) {
                    $non_string_types[] = $type;
                }
            } elseif ($compared_with_one_value
                && ($type instanceof TArrayKey || $type instanceof TCallable || $type instanceof TScalar)
            ) {
                $redundant = false;
                $non_string_types[] = $type;
            } elseif ($type instanceof TArrayKey) {
                $non_string_types[] = new TInt();
                $redundant = false;
            } elseif ($type instanceof TCallable) {
                $non_string_types[] = TKeyedArray::makeCallable([
                    new Union([new TClassString, new TObject]),
                    Type::getNonEmptyString(),
                ]);
                $non_string_types[] = new TCallableObject();
                $redundant = false;
            } elseif ($type instanceof TNumeric) {
                $non_string_types[] = $type;
                $redundant = false;
            } elseif ($type instanceof TScalar) {
                $redundant = false;
                $non_string_types[] = new TFloat();
                $non_string_types[] = new TInt();
                $non_string_types[] = new TBool();
            } elseif (!$type instanceof TString) {
                $non_string_types[] = $type;
            } else {
                $redundant = false;

                if ($is_equality) {
                    $non_string_types[] = $type;
                }
            }
        }

        return self::finishReconciliation(
            self::combineRemainingTypes($non_string_types, $existing_var_type),
            $redundant,
            true,
            $existing_var_type,
            $old_var_type_string,
            $key,
            $assertion,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
        );
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileArray(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $compared_with_one_value = self::isComparedWithOneValue($assertion);
        $non_array_types = [];
        // `false == []` and `null == []`
        $redundant = !$existing_var_type->hasScalar()
            && !($assertion instanceof IsNotLooselyEqual
                && ($existing_var_type->hasBool() || $existing_var_type->isNullable()));

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type = self::reconcileTemplateBound(
                    $type,
                    self::reconcileArray(...),
                    $assertion,
                    $suppressed_issues,
                    $is_equality,
                    $redundant,
                );

                if ($type !== null) {
                    $non_array_types[] = $type;
                }
            } elseif ($compared_with_one_value && ($type instanceof TCallable || $type instanceof TIterable)) {
                $redundant = false;
                $non_array_types[] = $type;
            } elseif ($type instanceof TCallable) {
                $non_array_types[] = new TCallableString();
                $non_array_types[] = new TCallableObject($type->from_docblock, $type);
                $redundant = false;
            } elseif ($type instanceof TIterable) {
                if (!$type->type_params[0]->isMixed() || !$type->type_params[1]->isMixed()) {
                    $non_array_types[] = new TGenericObject('Traversable', $type->type_params);
                } else {
                    $non_array_types[] = new TNamedObject('Traversable');
                }

                $redundant = false;
            } elseif (!$type instanceof TArray
                && !$type instanceof TKeyedArray
            ) {
                $non_array_types[] = $type;
            } else {
                $redundant = false;

                if ($is_equality) {
                    $non_array_types[] = $type;
                }
            }
        }

        return self::finishReconciliation(
            self::combineRemainingTypes($non_array_types, $existing_var_type),
            $redundant,
            true,
            $existing_var_type,
            $old_var_type_string,
            $key,
            $assertion,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
        );
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileResource(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $compared_with_one_value = self::isComparedWithOneValue($assertion);
        $non_resource_types = [];
        $redundant = true;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type = self::reconcileTemplateBound(
                    $type,
                    self::reconcileResource(...),
                    $assertion,
                    $suppressed_issues,
                    $is_equality,
                    $redundant,
                );

                if ($type !== null) {
                    $non_resource_types[] = $type;
                }
            } elseif ($type instanceof TResource) {
                $redundant = false;

                if ($compared_with_one_value) {
                    $non_resource_types[] = $type;
                }
            } else {
                $non_resource_types[] = $type;
            }
        }

        return self::finishReconciliation(
            $non_resource_types ? $existing_var_type->setTypes($non_resource_types) : null,
            $redundant,
            !$is_equality,
            $existing_var_type,
            $old_var_type_string,
            $key,
            $assertion,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
        );
    }

    /**
     * Applies the assertion to the template's bound, or returns null when no type of the bound passes it
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     * @param Closure[_](
     *     Assertion,
     *     Union,
     *     ?string,
     *     bool,
     *     ?CodeLocation,
     *     string[],
     *     Reconciler::RECONCILIATION_* &$failed_reconciliation,
     *     bool
     * ):Union $reconcile
     * @param string[] $suppressed_issues
     */
    private static function reconcileTemplateBound(
        TTemplateParam $type,
        Closure $reconcile,
        Assertion $assertion,
        array $suppressed_issues,
        bool $is_equality,
        bool &$redundant,
    ): ?TTemplateParam {
        if ($is_equality || $type->as->isMixed()) {
            $redundant = false;

            return $type;
        }

        $bound_failed_reconciliation = Reconciler::RECONCILIATION_OK;

        $as = $reconcile(
            $assertion,
            $type->as,
            null,
            false,
            null,
            $suppressed_issues,
            $bound_failed_reconciliation,
            $is_equality,
        );

        $redundant = $redundant && $bound_failed_reconciliation === Reconciler::RECONCILIATION_REDUNDANT;

        return $bound_failed_reconciliation === Reconciler::RECONCILIATION_EMPTY ? null : $type->replaceAs($as);
    }

    /**
     * @param list<Atomic> $types
     */
    private static function combineRemainingTypes(array $types, Union $existing_var_type): ?Union
    {
        if (!$types) {
            return null;
        }

        return TypeCombiner::combine($types)->setProperties([
            'ignore_falsable_issues' => $existing_var_type->ignore_falsable_issues,
            'ignore_nullable_issues' => $existing_var_type->ignore_nullable_issues,
            'from_docblock' => $existing_var_type->from_docblock,
        ]);
    }

    /**
     * Only code that is analysed guarantees a method: the methods of builtin classes can depend on how the extension
     * was compiled, and a method existence provider can report methods that don't exist at runtime
     */
    private static function isMethodAlwaysDeclared(Codebase $codebase, string $fq_class_name, string $method_name): bool
    {
        $method_id = new MethodIdentifier($fq_class_name, strtolower($method_name));

        if (!$codebase->methodExists($method_id, null, null, null, null, false)) {
            return false;
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        return $declaring_method_id !== null
            && $codebase->classlike_storage_provider->get($declaring_method_id->fq_class_name)->user_defined;
    }

    /**
     * An object whose class has the method can't be one for which method_exists() is false
     *
     * @param string[] $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileDoesNotHaveMethod(
        DoesNotHaveMethod $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
    ): ?Union {
        $remaining_types = [];
        $found_class_with_method = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TNamedObject
                && $codebase->classOrInterfaceExists($type->value)
                && self::isMethodAlwaysDeclared($codebase, $type->value, $assertion->method)
            ) {
                $found_class_with_method = true;

                continue;
            }

            $remaining_types[] = $type;
        }

        if (!$found_class_with_method) {
            return null;
        }

        if ($remaining_types) {
            return new Union($remaining_types);
        }

        if ($key !== null && $code_location) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $existing_var_type->getId(),
                $key,
                $assertion,
                false,
                $negated,
                $code_location,
                $suppressed_issues,
            );
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type;
    }

    /**
     * Reports a redundant assertion, or one that leaves no type, and returns $result, or never when there is none
     *
     * @param string[] $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function finishReconciliation(
        ?Union $result,
        bool $redundant,
        bool $report,
        Union|MutableUnion $existing_var_type,
        string $old_var_type_string,
        ?string $key,
        Assertion $assertion,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
    ): Union {
        if ($redundant || $result === null) {
            if ($report && $key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    $redundant,
                    $negated,
                    $code_location,
                    $suppressed_issues,
                );
            }

            if ($redundant) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($result !== null) {
            return $result;
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
    }

    /**
     * Whether the value is compared with one value of a type that has more than one: not being equal to it, the
     * value can still be of that type
     *
     * @psalm-pure
     */
    private static function isComparedWithOneValue(Assertion $assertion): bool
    {
        return ($assertion instanceof IsNotIdentical || $assertion instanceof IsNotLooselyEqual)
            && $assertion->comparand !== null;
    }
}
