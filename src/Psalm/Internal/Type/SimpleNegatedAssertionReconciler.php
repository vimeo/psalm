<?php

namespace Psalm\Internal\Type;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\RedundantPropertyInitializationCheck;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\ArrayKeyDoesNotExist;
use Psalm\Storage\Assertion\DoesNotHaveAtLeastCount;
use Psalm\Storage\Assertion\DoesNotHaveExactCount;
use Psalm\Storage\Assertion\Empty_;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\IsGreaterThanOrEqualTo;
use Psalm\Storage\Assertion\IsLessThanOrEqualTo;
use Psalm\Storage\Assertion\IsNotIsset;
use Psalm\Storage\Assertion\NotInArray;
use Psalm\Storage\Assertion\NotNonEmptyCountable;
use Psalm\Type;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableArray;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TEmptyNumeric;
use Psalm\Type\Atomic\TEmptyScalar;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function assert;
use function get_class;
use function max;
use function strpos;

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
        bool $inside_loop = false
    ): ?Union {
        $old_var_type_string = $existing_var_type->getId();

        if ($assertion instanceof IsNotIsset) {
            if ($existing_var_type->possibly_undefined) {
                return Type::getNever();
            }

            if (!$existing_var_type->isNullable()
                && $key
                && strpos($key, '[') === false
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
                                'Cannot resolve types for ' . $key . ' with docblock-defined type '
                                    . $existing_var_type . ' and !isset assertion',
                                $code_location,
                                'cannot resolve !isset '.$existing_var_type. ' ' . $key,
                            ),
                            $suppressed_issues,
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new TypeDoesNotContainType(
                                'Cannot resolve types for ' . $key . ' with type '
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

            return Type::getNull();
        }

        if ($assertion instanceof ArrayKeyDoesNotExist) {
            return Type::getNever();
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
            return self::reconcileIsLessThanOrEqualTo(
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
            return self::reconcileIsGreaterThanOrEqualTo(
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

        if ($assertion_type && get_class($assertion_type) === TBool::class && !$existing_var_type->hasMixed()) {
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

        if ($assertion_type && get_class($assertion_type) === TInt::class && !$existing_var_type->hasMixed()) {
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

        if ($assertion_type && get_class($assertion_type) === TString::class && !$existing_var_type->hasMixed()) {
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

        if ($assertion_type instanceof TFalse && !$existing_var_type->hasMixed()) {
            return self::reconcileFalse(
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

        if ($assertion_type instanceof TTrue && !$existing_var_type->hasMixed()) {
            return self::reconcileTrue(
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

        if ($assertion_type instanceof TCallable) {
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
        TCallable $assertion_type
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
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_bool_types = [];
        $redundant = true;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = $type->replaceAs(self::reconcileBool(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality,
                    ));

                    $redundant = false;

                    if (!$template_did_fail) {
                        $non_bool_types[] = $type;
                    }
                } else {
                    $redundant = false;
                    $non_bool_types[] = $type;
                }
            } elseif (!$type instanceof TBool
                || ($is_equality && get_class($type) === TBool::class)
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

        if ($redundant || !$non_bool_types) {
            if ($key && $code_location && !$is_equality) {
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

        if ($non_bool_types) {
            return new Union($non_bool_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
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
        ?int $count
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
                                $existing_var_type->addType(new TKeyedArray(
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
                        $existing_var_type->addType(new TArray(
                            [
                                new Union([new TNever()]),
                                new Union([new TNever()]),
                            ],
                        ));
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
        bool $is_equality
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

        if ($redundant || !$types) {
            if ($key && $code_location && !$is_equality) {
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

        if ($types) {
            return $existing_var_type->setTypes($types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileFalse(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $types = $existing_var_type->getAtomicTypes();
        $old_var_type_string = $existing_var_type->getId();
        $redundant = true;

        if (isset($types['scalar'])) {
            $redundant = false;
        }
        if (isset($types['bool'])) {
            $redundant = false;
            $types[] = new TTrue();
            unset($types['bool']);
        }
        if (isset($types['false'])) {
            $redundant = false;
            unset($types['false']);
        }

        foreach ($types as &$type) {
            if ($type instanceof TTemplateParam) {
                $new = $type->replaceAs(self::reconcileFalse(
                    $assertion,
                    $type->as,
                    null,
                    false,
                    null,
                    $suppressed_issues,
                    $failed_reconciliation,
                    $is_equality,
                ));

                if ($new !== $type) {
                    $redundant = false;
                }
                $type = $new;
            }
        }
        unset($type);

        if ($redundant || !$types) {
            if ($key && $code_location && !$is_equality) {
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

        if ($types) {
            return $existing_var_type->setTypes($types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
    }

    /**
     * @param string[] $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileTrue(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $types = $existing_var_type->getAtomicTypes();
        $old_var_type_string = $existing_var_type->getId();
        $redundant = true;

        if (isset($types['scalar'])) {
            $redundant = false;
        }
        if (isset($types['bool'])) {
            $redundant = false;
            $types[] = new TFalse();
            unset($types['bool']);
        }
        if (isset($types['true'])) {
            $redundant = false;
            unset($types['true']);
        }

        foreach ($types as &$type) {
            if ($type instanceof TTemplateParam) {
                $new = $type->replaceAs(self::reconcileTrue(
                    $assertion,
                    $type->as,
                    null,
                    false,
                    null,
                    $suppressed_issues,
                    $failed_reconciliation,
                    $is_equality,
                ));

                if ($new !== $type) {
                    $redundant = false;
                }
                $type = $new;
            }
        }
        unset($type);

        if ($redundant || !$types) {
            if ($key && $code_location && !$is_equality) {
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

        if ($types) {
            return $existing_var_type->setTypes($types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
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
        bool $recursive_check
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

            if (get_class($mixed_atomic_type) === TMixed::class) {
                $existing_var_type->removeType('mixed');
                $existing_var_type->addType(new TEmptyMixed());
            }
        }

        if ($existing_var_type->hasScalar()) {
            $scalar_atomic_type = $existing_var_type->getAtomicTypes()['scalar'];

            if (get_class($scalar_atomic_type) === TScalar::class) {
                $existing_var_type->removeType('scalar');
                $existing_var_type->addType(new TEmptyScalar());
            }
        }

        if ($existing_var_type->hasType('string')) {
            $string_atomic_type = $existing_var_type->getAtomicTypes()['string'];

            if (get_class($string_atomic_type) === TString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(Type::getAtomicStringFromLiteral(''));
                $existing_var_type->addType(Type::getAtomicStringFromLiteral('0'));
            } elseif (get_class($string_atomic_type) === TNonEmptyString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(Type::getAtomicStringFromLiteral('0'));
            } elseif (get_class($string_atomic_type) === TNonEmptyLowercaseString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(Type::getAtomicStringFromLiteral('0'));
            } elseif (get_class($string_atomic_type) === TNonEmptyNonspecificLiteralString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(Type::getAtomicStringFromLiteral('0'));
            }
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

                    if (!$template_did_fail) {
                        $existing_var_type->removeType($type_key);
                        $existing_var_type->addType($existing_var_atomic_type);
                    }
                }
            }
        }

        /** @psalm-suppress RedundantCondition Psalm bug */
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
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_scalar_types = [];
        $redundant = true;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = $type->replaceAs(self::reconcileScalar(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality,
                    ));

                    $redundant = false;

                    if (!$template_did_fail) {
                        $non_scalar_types[] = $type;
                    }
                } else {
                    $redundant = false;
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

        if ($redundant || !$non_scalar_types) {
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

            if ($redundant) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($non_scalar_types) {
            return new Union($non_scalar_types, [
                'ignore_falsable_issues' => $existing_var_type->ignore_falsable_issues,
                'ignore_nullable_issues' => $existing_var_type->ignore_nullable_issues,
                'from_docblock' => $existing_var_type->from_docblock,
            ]);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
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
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_object_types = [];
        $redundant = true;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = $type->replaceAs(self::reconcileObject(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality,
                    ));

                    $redundant = false;

                    if (!$template_did_fail) {
                        $non_object_types[] = $type;
                    }
                } else {
                    $redundant = false;
                    $non_object_types[] = $type;
                }
            } elseif ($type instanceof TCallable) {
                $non_object_types[] = new TCallableArray([
                    Type::getArrayKey(),
                    Type::getMixed(),
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

        if (!$non_object_types || $redundant) {
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

            if ($redundant) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($non_object_types) {
            return new Union($non_object_types, [
                'ignore_falsable_issues' => $existing_var_type->ignore_falsable_issues,
                'ignore_nullable_issues' => $existing_var_type->ignore_nullable_issues,
                'from_docblock' => $existing_var_type->from_docblock,
            ]);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
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
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_numeric_types = [];
        $redundant = !($existing_var_type->hasString()
            || $existing_var_type->hasScalar());

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = $type->replaceAs(self::reconcileNumeric(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality,
                    ));

                    $redundant = false;

                    if (!$template_did_fail) {
                        $non_numeric_types[] = $type;
                    }
                } else {
                    $redundant = false;
                    $non_numeric_types[] = $type;
                }
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

        if (!$non_numeric_types || $redundant) {
            if ($key && $code_location && !$is_equality) {
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

        if ($non_numeric_types) {
            return new Union($non_numeric_types, [
                'ignore_falsable_issues' => $existing_var_type->ignore_falsable_issues,
                'ignore_nullable_issues' => $existing_var_type->ignore_nullable_issues,
                'from_docblock' => $existing_var_type->from_docblock,
            ]);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
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
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_int_types = [];
        $redundant = true;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = $type->replaceAs(self::reconcileInt(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality,
                    ));

                    $redundant = false;

                    if (!$template_did_fail) {
                        $non_int_types[] = $type;
                    }
                } else {
                    $redundant = false;
                    $non_int_types[] = $type;
                }
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

        if (!$non_int_types || $redundant) {
            if ($key && $code_location && !$is_equality) {
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

        if ($non_int_types) {
            return new Union($non_int_types, [
                'ignore_falsable_issues' => $existing_var_type->ignore_falsable_issues,
                'ignore_nullable_issues' => $existing_var_type->ignore_nullable_issues,
                'from_docblock' => $existing_var_type->from_docblock,
            ]);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
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
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_float_types = [];
        $redundant = true;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = $type->replaceAs(self::reconcileFloat(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality,
                    ));

                    $redundant = false;

                    if (!$template_did_fail) {
                        $non_float_types[] = $type;
                    }
                } else {
                    $redundant = false;
                    $non_float_types[] = $type;
                }
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

        if (!$non_float_types || $redundant) {
            if ($key && $code_location && !$is_equality) {
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

        if ($non_float_types) {
            return new Union($non_float_types, [
                'ignore_falsable_issues' => $existing_var_type->ignore_falsable_issues,
                'ignore_nullable_issues' => $existing_var_type->ignore_nullable_issues,
                'from_docblock' => $existing_var_type->from_docblock,
            ]);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
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
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_string_types = [];
        $redundant = !$existing_var_type->hasScalar();

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = $type->replaceAs(self::reconcileString(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality,
                    ));

                    $redundant = false;

                    if (!$template_did_fail) {
                        $non_string_types[] = $type;
                    }
                } else {
                    $redundant = false;
                    $non_string_types[] = $type;
                }
            } elseif ($type instanceof TArrayKey) {
                $non_string_types[] = new TInt();
                $redundant = false;
            } elseif ($type instanceof TCallable) {
                $non_string_types[] = new TCallableArray([
                    Type::getArrayKey(),
                    Type::getMixed(),
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

        if (!$non_string_types || $redundant) {
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

            if ($redundant) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($non_string_types) {
            return new Union($non_string_types, [
                'ignore_falsable_issues' => $existing_var_type->ignore_falsable_issues,
                'ignore_nullable_issues' => $existing_var_type->ignore_nullable_issues,
                'from_docblock' => $existing_var_type->from_docblock,
            ]);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
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
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_array_types = [];
        $redundant = !$existing_var_type->hasScalar();

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TList) {
                $type = $type->getKeyedArray();
            }
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = $type->replaceAs(self::reconcileArray(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality,
                    ));

                    $redundant = false;

                    if (!$template_did_fail) {
                        $non_array_types[] = $type;
                    }
                } else {
                    $redundant = false;
                    $non_array_types[] = $type;
                }
            } elseif ($type instanceof TCallable) {
                $non_array_types[] = new TCallableString();
                $non_array_types[] = new TCallableObject();
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

        if ((!$non_array_types || $redundant)) {
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

            if ($redundant) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($non_array_types) {
            return new Union($non_array_types, [
                'ignore_falsable_issues' => $existing_var_type->ignore_falsable_issues,
                'ignore_nullable_issues' => $existing_var_type->ignore_nullable_issues,
                'from_docblock' => $existing_var_type->from_docblock,
            ]);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
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
        bool $is_equality
    ): Union {
        $types = $existing_var_type->getAtomicTypes();
        $old_var_type_string = $existing_var_type->getId();
        $redundant = true;

        if (isset($types['resource'])) {
            $redundant = false;
            unset($types['resource']);
        }

        foreach ($types as &$type) {
            if ($type instanceof TTemplateParam) {
                $new = $type->replaceAs(self::reconcileResource(
                    $assertion,
                    $type->as,
                    null,
                    false,
                    null,
                    $suppressed_issues,
                    $failed_reconciliation,
                    $is_equality,
                ));

                $redundant = $new === $type;
                $type = $new;
            }
        }
        unset($type);

        if ($redundant || !$types) {
            if ($key && $code_location && !$is_equality) {
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

        if ($types) {
            return $existing_var_type->setTypes($types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
    }

    /**
     * @param string[] $suppressed_issues
     */
    private static function reconcileIsLessThanOrEqualTo(
        IsLessThanOrEqualTo $assertion,
        Union               $existing_var_type,
        bool                $inside_loop,
        string              $old_var_type_string,
        ?string             $var_id,
        bool                $negated,
        ?CodeLocation       $code_location,
        array               $suppressed_issues
    ): Union {
        $existing_var_type = $existing_var_type->getBuilder();
        $assertion_value = $assertion->value;

        $redundant = true;

        if ($assertion->doesFilterNullOrFalse() &&
            ($existing_var_type->hasType('null') || $existing_var_type->hasType('false'))
        ) {
            $redundant = false;
            $existing_var_type->removeType('null');
            $existing_var_type->removeType('false');
        }

        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TIntRange) {
                if ($atomic_type->contains($assertion_value)) {
                    // if the range contains the assertion, the range must be adapted
                    $redundant = false;
                    $existing_var_type->removeType($atomic_type->getKey());
                    if ($atomic_type->max_bound === null) {
                        $max_bound = $assertion_value;
                    } else {
                        $max_bound = TIntRange::getNewLowestBound(
                            $assertion_value,
                            $atomic_type->max_bound,
                        );
                    }
                    $existing_var_type->addType(new TIntRange(
                        $atomic_type->min_bound,
                        $max_bound,
                    ));
                } elseif ($atomic_type->isLesserThan($assertion_value)) {
                    // if the range is lesser than the assertion, the check is redundant
                } elseif ($atomic_type->isGreaterThan($assertion_value)) {
                    // if the range is greater than the assertion, the type must be removed
                    $redundant = false;
                    $existing_var_type->removeType($atomic_type->getKey());
                }
            } elseif ($atomic_type instanceof TLiteralInt) {
                if ($atomic_type->value > $assertion_value) {
                    $redundant = false;
                    $existing_var_type->removeType($atomic_type->getKey());
                } /*elseif ($inside_loop) {
                    //when inside a loop, allow the range to extends the type
                    $existing_var_type->removeType($atomic_type->getKey());
                    if ($atomic_type->value < $assertion_value) {
                        $existing_var_type->addType(new TIntRange($atomic_type->value, $assertion_value));
                    } else {
                        $existing_var_type->addType(new TIntRange($assertion_value, $atomic_type->value));
                    }
                }*/
            } elseif ($atomic_type instanceof TInt) {
                $redundant = false;
                $existing_var_type->removeType($atomic_type->getKey());
                $existing_var_type->addType(new TIntRange(null, $assertion_value));
            } else {
                // we assume that other types may have been removed (empty strings? numeric strings?)
                //It may be worth refining to improve reconciliation while keeping in mind we're on loose comparison
                $redundant = false;
            }
        }

        if (!$inside_loop && $redundant && $var_id && $code_location) {
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
            $existing_var_type->addType(new TNever());
        }

        return $existing_var_type->freeze();
    }

    /**
     * @param string[] $suppressed_issues
     */
    private static function reconcileIsGreaterThanOrEqualTo(
        IsGreaterThanOrEqualTo $assertion,
        Union                  $existing_var_type,
        bool                   $inside_loop,
        string                 $old_var_type_string,
        ?string                $var_id,
        bool                   $negated,
        ?CodeLocation          $code_location,
        array                  $suppressed_issues
    ): Union {
        $existing_var_type = $existing_var_type->getBuilder();
        $assertion_value = $assertion->value;

        $redundant = true;

        if ($assertion->doesFilterNullOrFalse() &&
            ($existing_var_type->hasType('null') || $existing_var_type->hasType('false'))
        ) {
            $redundant = false;
            $existing_var_type->removeType('null');
            $existing_var_type->removeType('false');
        }

        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TIntRange) {
                if ($atomic_type->contains($assertion_value)) {
                    // if the range contains the assertion, the range must be adapted
                    $redundant = false;
                    $existing_var_type->removeType($atomic_type->getKey());
                    $min_bound = $atomic_type->min_bound;
                    if ($min_bound === null) {
                        $min_bound = $assertion_value;
                    } else {
                        $min_bound = max($min_bound, $assertion_value);
                    }
                    $existing_var_type->addType(new TIntRange(
                        $min_bound,
                        $atomic_type->max_bound,
                    ));
                } elseif ($atomic_type->isLesserThan($assertion_value)) {
                    // if the range is lesser than the assertion, the type must be removed
                    $redundant = false;
                    $existing_var_type->removeType($atomic_type->getKey());
                } elseif ($atomic_type->isGreaterThan($assertion_value)) {
                    // if the range is greater than the assertion, the check is redundant
                }
            } elseif ($atomic_type instanceof TLiteralInt) {
                if ($atomic_type->value < $assertion_value) {
                    $redundant = false;
                    $existing_var_type->removeType($atomic_type->getKey());
                } /* elseif ($inside_loop) {
                    //when inside a loop, allow the range to extends the type
                    $existing_var_type->removeType($atomic_type->getKey());
                    if ($atomic_type->value < $assertion_value) {
                        $existing_var_type->addType(new TIntRange($atomic_type->value, $assertion_value));
                    } else {
                        $existing_var_type->addType(new TIntRange($assertion_value, $atomic_type->value));
                    }
                }*/
            } elseif ($atomic_type instanceof TInt) {
                $redundant = false;
                $existing_var_type->removeType($atomic_type->getKey());
                $existing_var_type->addType(new TIntRange($assertion_value, null));
            } else {
                // we assume that other types may have been removed (empty strings? numeric strings?)
                //It may be worth refining to improve reconciliation while keeping in mind we're on loose comparison
                $redundant = false;
            }
        }

        if (!$inside_loop && $redundant && $var_id && $code_location) {
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
            $existing_var_type->addType(new TNever());
        }

        return $existing_var_type->freeze();
    }
}
