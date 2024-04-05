<?php

namespace Psalm\Internal\Type;

use AssertionError;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Codebase\ClassConstantByWildcardResolver;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\Any;
use Psalm\Storage\Assertion\ArrayKeyExists;
use Psalm\Storage\Assertion\HasArrayKey;
use Psalm\Storage\Assertion\HasAtLeastCount;
use Psalm\Storage\Assertion\HasExactCount;
use Psalm\Storage\Assertion\HasIntOrStringArrayAccess;
use Psalm\Storage\Assertion\HasMethod;
use Psalm\Storage\Assertion\HasStringArrayAccess;
use Psalm\Storage\Assertion\InArray;
use Psalm\Storage\Assertion\IsCountable;
use Psalm\Storage\Assertion\IsEqualIsset;
use Psalm\Storage\Assertion\IsGreaterThan;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Storage\Assertion\IsLessThan;
use Psalm\Storage\Assertion\IsLooselyEqual;
use Psalm\Storage\Assertion\IsType;
use Psalm\Storage\Assertion\NonEmpty;
use Psalm\Storage\Assertion\NonEmptyCountable;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableArray;
use Psalm\Type\Atomic\TCallableKeyedArray;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyScalar;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TValueOf;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_map;
use function array_merge;
use function array_values;
use function assert;
use function count;
use function explode;
use function get_class;
use function in_array;
use function is_int;
use function min;
use function strlen;
use function strpos;
use function strtolower;

/**
 * This class receives a known type and an assertion (probably coming from AssertionFinder). The goal is to refine
 * the known type using the assertion. For example: old type is `int` assertion is `>5` result is `int<6, max>`.
 * Complex reconciliation takes part in AssertionReconciler if this class couldn't handle the reconciliation
 *
 * @internal
 */
final class SimpleAssertionReconciler extends Reconciler
{
    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    public static function reconcile(
        Assertion $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key = null,
        bool $negated = false,
        ?CodeLocation $code_location = null,
        array $suppressed_issues = [],
        int &$failed_reconciliation = Reconciler::RECONCILIATION_OK,
        bool $inside_loop = false
    ): ?Union {
        if ($assertion instanceof Any) {
            return $existing_var_type;
        }

        $old_var_type_string = $existing_var_type->getId();

        $is_equality = $assertion->hasEquality();

        if ($assertion instanceof IsIsset || $assertion instanceof IsEqualIsset) {
            return self::reconcileIsset(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $assertion instanceof IsEqualIsset,
                $inside_loop,
            );
        }

        if ($assertion instanceof ArrayKeyExists) {
            return $existing_var_type->setPossiblyUndefined(false);
        }

        if ($assertion instanceof InArray) {
            return self::reconcileInArray(
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

        if ($assertion instanceof HasArrayKey) {
            return self::reconcileHasArrayKey(
                $existing_var_type,
                $assertion,
            );
        }

        if ($assertion instanceof IsGreaterThan) {
            return self::reconcileIsGreaterThan(
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

        if ($assertion instanceof IsLessThan) {
            return self::reconcileIsLessThan(
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

        if ($assertion instanceof Truthy || $assertion instanceof NonEmpty) {
            return self::reconcileTruthyOrNonEmpty(
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

        if ($assertion instanceof IsCountable) {
            return self::reconcileCountable(
                $assertion,
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion instanceof HasStringArrayAccess) {
            return self::reconcileStringArrayAccess(
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
        }

        if ($assertion instanceof HasIntOrStringArrayAccess) {
            return self::reconcileIntArrayAccess(
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
        }

        if ($assertion instanceof NonEmptyCountable) {
            return self::reconcileNonEmptyCountable(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $is_equality,
            );
        }

        if ($assertion instanceof HasAtLeastCount) {
            return self::reconcileNonEmptyCountable(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $is_equality,
            );
        }

        if ($assertion instanceof HasExactCount) {
            return self::reconcileExactlyCountable(
                $existing_var_type,
                $assertion,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $is_equality,
            );
        }

        if ($assertion instanceof HasMethod) {
            return self::reconcileHasMethod(
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

        $assertion_type = $assertion->getAtomicType();

        if ($assertion_type instanceof TObject) {
            return self::reconcileObject(
                $codebase,
                $assertion,
                $assertion_type,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type instanceof TResource) {
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

        if ($assertion_type instanceof TCallable) {
            return self::reconcileCallable(
                $assertion,
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type instanceof TIterable
            && $assertion_type->type_params[0]->isMixed()
            && $assertion_type->type_params[1]->isMixed()
        ) {
            return self::reconcileIterable(
                $assertion,
                $codebase,
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

        if ($assertion_type instanceof TList) {
            $assertion_type = $assertion_type->getKeyedArray();
        }

        if ($assertion_type instanceof TKeyedArray
            && $assertion_type->is_list
            && $assertion_type->getGenericValueType()->isMixed()
        ) {
            return self::reconcileList(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                $assertion_type->isNonEmpty(),
            );
        }

        if ($assertion_type instanceof TNamedObject
            && $assertion_type->value === 'Traversable'
        ) {
            return self::reconcileTraversable(
                $assertion,
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
            );
        }

        if ($assertion_type instanceof TNumeric) {
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

        if ($assertion_type instanceof TScalar) {
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

        if ($assertion_type && get_class($assertion_type) === TBool::class) {
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

        if ($assertion_type && $assertion_type instanceof TTrue) {
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

        if ($assertion_type && $assertion_type instanceof TFalse) {
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

        if ($assertion_type && get_class($assertion_type) === TString::class) {
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

        if ($assertion_type && get_class($assertion_type) === TInt::class) {
            return self::reconcileInt(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
            );
        }

        if ($assertion_type instanceof TFloat) {
            if ($existing_var_type->from_calculation
                && $existing_var_type->hasInt()
            ) {
                return Type::getFloat();
            }

            if ($assertion instanceof IsLooselyEqual && $existing_var_type->isString()) {
                return Type::getNumericString();
            }
        }

        if ($assertion_type instanceof TClassConstant) {
            return self::reconcileClassConstant(
                $codebase,
                $assertion_type,
                $existing_var_type,
                $failed_reconciliation,
            );
        }

        if ($existing_var_type->isSingle()
            && $existing_var_type->hasTemplate()
        ) {
            $types = $existing_var_type->getAtomicTypes();
            foreach ($types as $k => $atomic_type) {
                if ($atomic_type instanceof TTemplateParam && $assertion_type) {
                    if ($atomic_type->as->hasMixed()
                        || $atomic_type->as->hasObject()
                    ) {
                        unset($types[$k]);
                        $atomic_type = $atomic_type->replaceAs(new Union([$assertion_type]));
                        $types[$atomic_type->getKey()] = $atomic_type;
                        return new Union($types);
                    }
                }
            }
        }

        if ($assertion_type instanceof TValueOf) {
            return self::reconcileValueOf(
                $codebase,
                $assertion_type,
                $failed_reconciliation,
            );
        }

        return null;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileIsset(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        bool $inside_loop
    ): Union {
        $existing_var_type = $existing_var_type->getBuilder();
        $old_var_type_string = $existing_var_type->getId();

        // if key references an array offset
        $redundant = !(($key && strpos($key, '['))
            || !$existing_var_type->initialized
            || $existing_var_type->possibly_undefined
            || $existing_var_type->ignore_isset);

        if ($existing_var_type->isNullable()) {
            $existing_var_type->removeType('null');

            $redundant = false;
        }

        if (!$existing_var_type->hasMixed()
            && !$is_equality
            && ($redundant || $existing_var_type->isUnionEmpty())
            && $key
            && $code_location
        ) {
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

            if ($existing_var_type->isUnionEmpty()) {
                $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;
                return Type::getNever();
            }
        }

        if ($inside_loop) {
            if ($existing_var_type->hasType('never')) {
                $existing_var_type->removeType('never');
                $existing_var_type->addType(new TMixed(true));
            }
        }

        $existing_var_type->from_property = false;
        $existing_var_type->from_static_property = false;
        $existing_var_type->possibly_undefined = false;
        $existing_var_type->possibly_undefined_from_try = false;
        $existing_var_type->ignore_isset = false;

        return $existing_var_type->freeze();
    }

    /**
     * @param NonEmptyCountable|HasAtLeastCount $assertion
     * @param   string[]  $suppressed_issues
     */
    private static function reconcileNonEmptyCountable(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $existing_var_type = $existing_var_type->getBuilder();

        if ($existing_var_type->hasType('array')) {
            $array_atomic_type = $existing_var_type->getArray();
            $redundant = true;

            if ($array_atomic_type instanceof TArray) {
                if (!$array_atomic_type instanceof TNonEmptyArray
                    || ($assertion instanceof HasAtLeastCount
                        && $array_atomic_type->min_count < $assertion->count)
                ) {
                    if ($array_atomic_type->isEmptyArray()) {
                        $existing_var_type->removeType('array');
                    } else {
                        $non_empty_array = new TNonEmptyArray(
                            $array_atomic_type->type_params,
                            null,
                            $assertion instanceof HasAtLeastCount ? $assertion->count : null,
                        );

                        $existing_var_type->addType($non_empty_array);
                    }

                    $redundant = false;
                }
            } elseif ($array_atomic_type instanceof TKeyedArray) {
                $prop_max_count = count($array_atomic_type->properties);
                $prop_min_count = $array_atomic_type->getMinCount();

                if ($assertion instanceof HasAtLeastCount) {
                    // count($a) > 3
                    // count($a) >= 4

                    // 4
                    $count = $assertion->count;
                } else {
                    // count($a) >= 1
                    $count = 1;
                }
                if ($array_atomic_type->fallback_params === null) {
                    // We're asserting that count($a) >= $count
                    // If it's impossible, remove the type
                    // If it's possible but redundant, mark as redundant
                    // If it's possible, mark as not redundant

                    // Impossible because count($a) < $count always
                    if ($prop_max_count < $count) {
                        $redundant = false;
                        $existing_var_type->removeType('array');

                        // Redundant because count($a) >= $count always
                    } elseif ($prop_min_count >= $count) {
                        $redundant = true;

                        // If count($a) === $count and there are possibly undefined properties
                    } elseif ($prop_max_count === $count && $prop_min_count !== $prop_max_count) {
                        $existing_var_type->removeType('array');
                        $existing_var_type->addType($array_atomic_type->setProperties(
                            array_map(
                                static fn(Union $union) => $union->setPossiblyUndefined(false),
                                $array_atomic_type->properties,
                            ),
                        ));
                        $redundant = false;

                        // Possible, alter type if we're a list
                    } elseif ($array_atomic_type->is_list) {
                        // Possible

                        $redundant = false;
                        $properties = $array_atomic_type->properties;
                        for ($i = $prop_min_count; $i < $count; $i++) {
                            $properties[$i] = $properties[$i]->setPossiblyUndefined(false);
                        }
                        $array_atomic_type = $array_atomic_type->setProperties($properties);
                        $existing_var_type->removeType('array');
                        $existing_var_type->addType($array_atomic_type);
                    } else {
                        $redundant = false;
                    }
                } elseif ($array_atomic_type->is_list) {
                    if ($count <= $prop_min_count) {
                        $redundant = true;
                    } else {
                        $redundant = false;
                        $properties = $array_atomic_type->properties;
                        for ($i = $prop_min_count; $i < $count; $i++) {
                            $properties[$i] = isset($properties[$i])
                                ? $properties[$i]->setPossiblyUndefined(false)
                                : $array_atomic_type->fallback_params[1];
                        }
                        $array_atomic_type = $array_atomic_type->setProperties($properties);
                        $existing_var_type->removeType('array');
                        $existing_var_type->addType($array_atomic_type);
                    }
                } else {
                    $redundant = false;
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
     * @param array<string> $suppressed_issues
     */
    private static function reconcileExactlyCountable(
        Union $existing_var_type,
        HasExactCount $assertion,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        bool $is_equality
    ): Union {
        $existing_var_type = $existing_var_type->getBuilder();
        if ($existing_var_type->hasType('array')) {
            $old_var_type_string = $existing_var_type->getId();
            $array_atomic_type = $existing_var_type->getArray();
            $redundant = true;

            if ($array_atomic_type instanceof TArray) {
                if (!$array_atomic_type instanceof TNonEmptyArray
                    || $array_atomic_type->count !== $assertion->count
                ) {
                    $non_empty_array = new TNonEmptyArray(
                        $array_atomic_type->type_params,
                        $assertion->count,
                    );

                    $existing_var_type->removeType('array');
                    $existing_var_type->addType(
                        $non_empty_array,
                    );

                    $redundant = false;
                } else {
                    $redundant = true;
                }
            } elseif ($array_atomic_type instanceof TKeyedArray) {
                $prop_max_count = count($array_atomic_type->properties);
                $prop_min_count = $array_atomic_type->getMinCount();

                if ($assertion->count < $prop_min_count) {
                    // Impossible
                    $existing_var_type->removeType('array');
                    $redundant = false;
                } elseif ($array_atomic_type->fallback_params === null) {
                    if ($assertion->count === $prop_min_count) {
                        // Redundant
                        $redundant = true;
                    } elseif ($assertion->count > $prop_max_count) {
                        // Impossible
                        $existing_var_type->removeType('array');
                        $redundant = false;
                    } elseif ($assertion->count === $prop_max_count) {
                        $redundant = false;
                        $existing_var_type->removeType('array');
                        $existing_var_type->addType($array_atomic_type->setProperties(
                            array_map(
                                static fn(Union $union) => $union->setPossiblyUndefined(false),
                                $array_atomic_type->properties,
                            ),
                        ));
                    } elseif ($array_atomic_type->is_list) {
                        $redundant = false;
                        $properties = $array_atomic_type->properties;
                        for ($x = $prop_min_count; $x < $assertion->count; $x++) {
                            $properties[$x] = $properties[$x]->setPossiblyUndefined(false);
                        }
                        $array_atomic_type = $array_atomic_type->setProperties($properties);
                        $existing_var_type->removeType('array');
                        $existing_var_type->addType($array_atomic_type);
                    } else {
                        $redundant = false;
                    }
                } else {
                    if ($array_atomic_type->is_list) {
                        $redundant = false;
                        $properties = $array_atomic_type->properties;
                        for ($x = $prop_min_count; $x < $assertion->count; $x++) {
                            $properties[$x] = isset($properties[$x])
                                ? $properties[$x]->setPossiblyUndefined(false)
                                : $array_atomic_type->fallback_params[1];
                        }
                        $array_atomic_type = new TKeyedArray(
                            $properties,
                            null,
                            null,
                            true,
                        );
                        $existing_var_type->removeType('array');
                        $existing_var_type->addType($array_atomic_type);
                    } elseif ($prop_max_count === $prop_min_count && $prop_max_count === $assertion->count) {
                        $existing_var_type->removeType('array');
                        $existing_var_type->addType($array_atomic_type->makeSealed());
                    }
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
    private static function reconcileHasMethod(
        HasMethod $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation
    ): Union {
        $method_name = $assertion->method;
        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $object_types = [];
        $redundant = true;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TNamedObject
                && $codebase->classOrInterfaceExists($type->value)
            ) {
                if (!$codebase->methodExists($type->value . '::' . $method_name)) {
                    $match_found = false;

                    $extra_types = $type->extra_types;
                    foreach ($type->extra_types as $k => $extra_type) {
                        if ($extra_type instanceof TNamedObject
                            && $codebase->classOrInterfaceExists($extra_type->value)
                            && $codebase->methodExists($extra_type->value . '::' . $method_name)
                        ) {
                            $match_found = true;
                        } elseif ($extra_type instanceof TObjectWithProperties) {
                            $match_found = true;

                            if (!isset($extra_type->methods[strtolower($method_name)])) {
                                unset($extra_types[$k]);
                                $extra_type = $extra_type->setMethods(array_merge($extra_type->methods, [
                                    strtolower($method_name) => 'object::' . $method_name,
                                ]));
                                $extra_types[$extra_type->getKey()] = $extra_type;
                                $redundant = false;
                            }
                        }
                    }

                    if (!$match_found) {
                        $extra_type = new TObjectWithProperties(
                            [],
                            [strtolower($method_name) => $type->value . '::' . $method_name],
                        );
                        $extra_types[$extra_type->getKey()] = $extra_type;
                        $redundant = false;
                    }

                    $type = $type->setIntersectionTypes($extra_types);
                }
                $object_types[] = $type;
            } elseif ($type instanceof TObjectWithProperties) {
                if (!isset($type->methods[strtolower($method_name)])) {
                    $type = $type->setMethods(array_merge($type->methods, [
                        strtolower($method_name) => 'object::' . $method_name,
                    ]));
                    $redundant = false;
                }
                $object_types[] = $type;
            } elseif ($type instanceof TObject || $type instanceof TMixed) {
                $object_types[] = new TObjectWithProperties(
                    [],
                    [strtolower($method_name) =>  'object::' . $method_name],
                );
                $redundant = false;
            } elseif ($type instanceof TString) {
                // we don’t know
                $object_types[] = $type;
                $redundant = false;
            } elseif ($type instanceof TTemplateParam) {
                $object_types[] = $type;
                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if (!$object_types || $redundant) {
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

        if ($object_types) {
            return new Union($object_types);
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
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed()) {
            if ($assertion instanceof IsLooselyEqual) {
                return $existing_var_type;
            }

            return Type::getString();
        }

        $string_types = [];
        $redundant = true;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TString) {
                if (get_class($type) === TString::class) {
                    $type = $type->setFromDocblock(false);
                }
                $string_types[] = $type;
            } elseif ($type instanceof TCallable) {
                $string_types[] = new TCallableString;
                $redundant = false;
            } elseif ($type instanceof TNumeric) {
                $string_types[] = new TNumericString;
                $redundant = false;
            } elseif ($type instanceof TScalar || $type instanceof TArrayKey) {
                $string_types[] = new TString;
                $redundant = false;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasString() || $type->as->hasMixed() || $type->as->hasScalar()) {
                    $type = $type->replaceAs(self::reconcileString(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                    ));

                    $string_types[] = $type;
                }

                $redundant = false;
            } elseif ($type instanceof TInt && $assertion instanceof IsLooselyEqual) {
                // don't change the type of an int for non-strict comparisons
                $string_types[] = $type;
                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if (($redundant || !$string_types) && !$is_equality) {
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

        if ($string_types) {
            return new Union($string_types);
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
        int &$failed_reconciliation
    ): Union {
        if ($existing_var_type->hasMixed()) {
            if ($assertion instanceof IsLooselyEqual) {
                return $existing_var_type;
            }

            return Type::getInt();
        }

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $int_types = [];
        $redundant = true;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TInt) {
                if (get_class($type) === TInt::class) {
                    $type = $type->setFromDocblock(false);
                }

                $int_types[] = $type;

                if ($existing_var_type->from_calculation) {
                    $redundant = false;
                }
            } elseif ($type instanceof TNumeric) {
                $int_types[] = new TInt;
                $redundant = false;
            } elseif ($type instanceof TScalar || $type instanceof TArrayKey) {
                $int_types[] = new TInt;
                $redundant = false;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasInt() || $type->as->hasMixed()) {
                    $type = $type->replaceAs(self::reconcileInt(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                    ));

                    $int_types[] = $type;
                }

                $redundant = false;
            } elseif ($type instanceof TString && $assertion instanceof IsLooselyEqual) {
                $int_types[] = new TNumericString();
                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if (($redundant || !$int_types) && $assertion instanceof IsType) {
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

        if ($int_types) {
            return new Union($int_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
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
        if ($existing_var_type->hasMixed()) {
            return Type::getBool();
        }

        $bool_types = [];
        $redundant = true;

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TBool) {
                $type = $type->setFromDocblock(false);
                $bool_types[] = $type;
            } elseif ($type instanceof TScalar) {
                $bool_types[] = new TBool;
                $redundant = false;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasBool() || $type->as->hasMixed()) {
                    $type = $type->replaceAs(self::reconcileBool(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                    ));

                    $bool_types[] = $type;
                }

                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if (($redundant || !$bool_types) && !$is_equality) {
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

        if ($bool_types) {
            return new Union($bool_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
    }

    /**
     * @param string[] $suppressed_issues
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
        if ($existing_var_type->hasMixed()) {
            return Type::getFalse();
        }
        if ($existing_var_type->hasScalar()) {
            return Type::getFalse();
        }

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $false_types = [];
        $redundant = true;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TFalse) {
                $false_types[] = $type;
            } elseif ($type instanceof TBool) {
                $false_types[] = new TFalse();
                $redundant = false;
            } elseif ($type instanceof TTemplateParam && $type->as->isMixed()) {
                $type = $type->replaceAs(Type::getFalse());
                $false_types[] = $type;
                $redundant = false;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasScalar() || $type->as->hasMixed() || $type->as->hasBool()) {
                    $type = $type->replaceAs(self::reconcileFalse(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                    ));

                    $false_types[] = $type;
                }

                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if ((!$false_types || $redundant) && !$is_equality) {
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

        if ($false_types) {
            return new Union($false_types);
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
        if ($existing_var_type->hasMixed()) {
            return Type::getTrue();
        }
        if ($existing_var_type->hasScalar()) {
            return Type::getTrue();
        }

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $true_types = [];
        $redundant = true;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TTrue) {
                $true_types[] = $type;
            } elseif ($type instanceof TBool) {
                $true_types[] = new TTrue();
                $redundant = false;
            } elseif ($type instanceof TTemplateParam && $type->as->isMixed()) {
                $type = $type->replaceAs(Type::getTrue());
                $true_types[] = $type;
                $redundant = false;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasScalar() || $type->as->hasMixed() || $type->as->hasBool()) {
                    $type = $type->replaceAs(self::reconcileTrue(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                    ));

                    $true_types[] = $type;
                }

                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if ((!$true_types || $redundant) && !$is_equality) {
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

        if ($true_types) {
            return new Union($true_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
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
        if ($existing_var_type->hasMixed()) {
            return Type::getScalar();
        }

        $scalar_types = [];
        $redundant = true;

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof Scalar) {
                $scalar_types[] = $type;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasScalarType() || $type->as->hasMixed()) {
                    $type = $type->replaceAs(self::reconcileScalar(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                    ));

                    $scalar_types[] = $type;
                }

                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if (($redundant || !$scalar_types) && !$is_equality) {
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

        if ($scalar_types) {
            return new Union($scalar_types);
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
        if ($existing_var_type->hasMixed()) {
            return Type::getNumeric();
        }
        $existing_var_type = $existing_var_type->getBuilder();

        $old_var_type_string = $existing_var_type->getId();

        $numeric_types = [];
        $redundant = true;

        if ($existing_var_type->hasString()) {
            $redundant = false;
            $existing_var_type->removeType('string');
            $existing_var_type->addType(new TNumericString);
        }

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TNumeric || $type instanceof TNumericString) {
                // this is a workaround for a possible issue running
                // is_numeric($a) && is_string($a)
                $redundant = false;
                $numeric_types[] = $type;
            } elseif ($type->isNumericType()) {
                $numeric_types[] = $type;
            } elseif ($type instanceof TScalar) {
                $redundant = false;
                $numeric_types[] = new TNumeric();
            } elseif ($type instanceof TArrayKey) {
                $redundant = false;
                $numeric_types[] = new TInt();
                $numeric_types[] = new TNumericString();
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasScalarType() || $type->as->hasMixed()) {
                    $type = $type->replaceAs(self::reconcileNumeric(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                    ));

                    $numeric_types[] = $type;
                }

                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if (($redundant || !$numeric_types) && !$is_equality) {
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

        if ($numeric_types) {
            return new Union($numeric_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileObject(
        Codebase $codebase,
        Assertion $assertion,
        TObject $assertion_type,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        if ($existing_var_type->hasMixed()) {
            return new Union([$assertion_type]);
        }

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $object_types = [];
        $redundant = true;

        $assertion_type_is_intersectable_type = Type::isIntersectionType($assertion_type);
        foreach ($existing_var_atomic_types as $type) {
            if ($assertion_type_is_intersectable_type
                && self::areIntersectionTypesAllowed($codebase, $type)
            ) {
                /** @var TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject $assertion_type */
                $object_types[] = $type->addIntersectionType($assertion_type);
                $redundant = false;
            } elseif ($type instanceof TCallable) {
                $callable_object = new TCallableObject($type->from_docblock, $type);
                $object_types[] = $callable_object;
                $redundant = false;
            } elseif ($type instanceof TTemplateParam
                && $type->as->isMixed()
            ) {
                $type = $type->replaceAs(Type::getObject());
                $object_types[] = $type;
                $redundant = false;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasObjectType() || $type->as->hasMixed()) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument This looks wrong, psalm assumes that $assertion_type
                     *                                         can contain TNamedObject due to the reconciliation above
                     *                                         regarding {@see Type::isIntersectionType}. Due to the
                     *                                         native argument type `TObject`, the variable object will
                     *                                         never be `TNamedObject`.
                     */
                    $reconciled_type = self::reconcileObject(
                        $codebase,
                        $assertion,
                        $assertion_type,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                    );
                    $type = $type->replaceAs($reconciled_type);

                    $object_types[] = $type;
                }

                $redundant = false;
            } elseif ($type->isObjectType()) {
                if ($assertion_type_is_intersectable_type
                    && !self::areIntersectionTypesAllowed($codebase, $type)
                ) {
                    $redundant = false;
                } else {
                    $object_types[] = $type;
                }
            } elseif ($type instanceof TIterable) {
                $params = $type->type_params;
                $params[0] = self::refineArrayKey($params[0]);

                $object_types[] = new TGenericObject(
                    'Traversable',
                    $params,
                );

                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if ((!$object_types || $redundant) && !$is_equality) {
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

        if ($object_types) {
            return new Union($object_types);
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
        if ($existing_var_type->hasMixed()) {
            return Type::getResource();
        }

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $resource_types = [];
        $redundant = true;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TResource) {
                $resource_types[] = $type;
            } else {
                $redundant = false;
            }
        }

        if ((!$resource_types || $redundant) && !$is_equality) {
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

        if ($resource_types) {
            return new Union($resource_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileCountable(
        Assertion $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();


        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return new Union([
                new TArray([Type::getArrayKey(), Type::getMixed()]),
                new TNamedObject('Countable'),
            ]);
        }

        $iterable_types = [];
        $redundant = true;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isCountable($codebase)) {
                $iterable_types[] = $type;
            } elseif ($type instanceof TObject) {
                $iterable_types[] = new TNamedObject('Countable');
                $redundant = false;
            } elseif ($type instanceof TNamedObject || $type instanceof TIterable) {
                $countable = new TNamedObject('Countable');
                $type = $type->addIntersectionType($countable);
                $iterable_types[] = $type;
                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if ((!$iterable_types || $redundant) && !$is_equality) {
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

        if ($iterable_types) {
            return new Union($iterable_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileIterable(
        Assertion $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return new Union([new TIterable]);
        }

        $iterable_types = [];
        $redundant = true;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isIterable($codebase)) {
                $iterable_types[] = $type;
            } elseif ($type instanceof TObject) {
                $iterable_types[] = new TNamedObject('Traversable');
                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if ((!$iterable_types || $redundant) && !$is_equality) {
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

        if ($iterable_types) {
            return new Union($iterable_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileInArray(
        InArray $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation
    ): Union {
        $new_var_type = $assertion->type;

        if ($new_var_type->isSingle() && $new_var_type->getSingleAtomic() instanceof TClassConstant) {
            // Can't do assertion on const with non-literal type
            return $existing_var_type;
        }

        $intersection = Type::intersectUnionTypes($new_var_type, $existing_var_type, $codebase);

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

            return Type::getNever();
        }

        return $intersection;
    }

    private static function reconcileHasArrayKey(
        Union $existing_var_type,
        HasArrayKey $assertion
    ): Union {
        $assertion = $assertion->key;
        $types = $existing_var_type->getAtomicTypes();
        foreach ($types as &$atomic_type) {
            if ($atomic_type instanceof TList) {
                $atomic_type = $atomic_type->getKeyedArray();
            }
            if ($atomic_type instanceof TKeyedArray) {
                assert(strpos($assertion, '::class') === (strlen($assertion)-7));
                [$assertion] = explode('::', $assertion);

                $atomic_type = new TKeyedArray(
                    array_merge(
                        $atomic_type->properties,
                        [$assertion => Type::getMixed()],
                    ),
                    array_merge(
                        $atomic_type->class_strings ?? [],
                        [$assertion => true],
                    ),
                    $atomic_type->fallback_params,
                    $atomic_type->is_list,
                );
            }
        }
        unset($atomic_type);
        return $existing_var_type->setTypes($types);
    }

    /**
     * @param string[] $suppressed_issues
     */
    private static function reconcileIsGreaterThan(
        IsGreaterThan $assertion,
        Union         $existing_var_type,
        bool          $inside_loop,
        string        $old_var_type_string,
        ?string       $var_id,
        bool          $negated,
        ?CodeLocation $code_location,
        array         $suppressed_issues
    ): Union {
        $existing_var_type = $existing_var_type->getBuilder();
        //we add 1 from the assertion value because we're on a strict operator
        $assertion_value = $assertion->value + 1;

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
                        $min_bound = TIntRange::getNewHighestBound(
                            $assertion_value,
                            $min_bound,
                        );
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
                } /*elseif ($inside_loop) {
                    //when inside a loop, allow the range to extends the type
                    $existing_var_type->removeType($atomic_type->getKey());
                    if ($atomic_type->value < $assertion_value) {
                        $existing_var_type->addType(new TIntRange($atomic_type->value, $assertion_value));
                    } else {
                        $existing_var_type->addType(new TIntRange($assertion_value, $atomic_type->value));
                    }
                }*/
            } elseif ($atomic_type instanceof TInt && is_int($assertion_value)) {
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

    /**
     * @param string[] $suppressed_issues
     */
    private static function reconcileIsLessThan(
        IsLessThan    $assertion,
        Union         $existing_var_type,
        bool          $inside_loop,
        string        $old_var_type_string,
        ?string       $var_id,
        bool          $negated,
        ?CodeLocation $code_location,
        array         $suppressed_issues
    ): Union {
        //we remove 1 from the assertion value because we're on a strict operator
        $assertion_value = $assertion->value - 1;
        $existing_var_type = $existing_var_type->getBuilder();

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
                    $max_bound = $atomic_type->max_bound;
                    if ($max_bound === null) {
                        $max_bound = $assertion_value;
                    } else {
                        $max_bound = min($max_bound, $assertion_value);
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
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileTraversable(
        Assertion $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return new Union([new TNamedObject('Traversable')]);
        }

        $traversable_types = [];
        $redundant = true;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->hasTraversableInterface($codebase)) {
                $traversable_types[] = $type;
            } elseif ($type instanceof TIterable) {
                $traversable_types[] = new TGenericObject('Traversable', $type->type_params);
                $redundant = false;
            } elseif ($type instanceof TObject) {
                $traversable_types[] = new TNamedObject('Traversable');
                $redundant = false;
            } elseif ($type instanceof TNamedObject) {
                $traversable = new TNamedObject('Traversable');
                $type = $type->addIntersectionType($traversable);
                $traversable_types[] = $type;
                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if ((!$traversable_types || $redundant) && !$is_equality) {
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

        if ($traversable_types) {
            return new Union($traversable_types);
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

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed()) {
            if ($assertion->getAtomicType()) {
                return new Union([$assertion->getAtomicType()]);
            }
            return Type::getArray();
        }

        $atomic_assertion_type = $assertion->getAtomicType();

        $array_types = [];
        $redundant = true;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TList) {
                $type = $type->getKeyedArray();
            }
            if ($type instanceof TArray) {
                if ($atomic_assertion_type instanceof TNonEmptyArray) {
                    $array_types[] = new TNonEmptyArray(
                        $type->type_params,
                        $atomic_assertion_type->count,
                        $atomic_assertion_type->min_count,
                        'non-empty-array',
                        $type->from_docblock,
                    );
                } else {
                    $array_types[] = $type;
                }
            } elseif ($type instanceof TKeyedArray) {
                //we don't currently have "definitely defined" shapes so we keep the one we have even if we have
                //a non-empty-array assertion
                $array_types[] = $type;
            } elseif ($type instanceof TCallable) {
                $array_types[] = new TCallableKeyedArray([
                    new Union([new TClassString, new TObject]),
                    Type::getString(),
                ]);

                $redundant = false;
            } elseif ($type instanceof TIterable) {
                $params = $type->type_params;
                $params[0] = self::refineArrayKey($params[0]);
                $array_types[] = new TArray($params);

                $redundant = false;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasArray() || $type->as->hasIterable() || $type->as->hasMixed()) {
                    $type = $type->replaceAs(self::reconcileArray(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                    ));

                    $array_types[] = $type;
                }

                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if ((!$array_types || $redundant) && !$is_equality) {
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

                if ($redundant) {
                    $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
                }
            }
        }

        if ($array_types) {
            return TypeCombiner::combine($array_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileList(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        bool $is_non_empty
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return $is_non_empty ? Type::getNonEmptyList() : Type::getList();
        }

        $array_types = [];
        $redundant = true;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TList) {
                $type = $type->getKeyedArray();
            }
            if ($type instanceof TKeyedArray && $type->is_list) {
                if ($is_non_empty && !$type->isNonEmpty()) {
                    $properties = $type->properties;
                    $properties[0] = $properties[0]->setPossiblyUndefined(false);
                    $array_types[] = $type->setProperties($properties);
                    $redundant = false;
                } else {
                    $array_types[] = $type;
                }
            } elseif ($type instanceof TArray
                || ($type instanceof TKeyedArray && $type->fallback_params !== null)
            ) {
                if ($type instanceof TKeyedArray) {
                    $type = $type->getGenericArrayType();
                }

                if ($type->type_params[0]->hasArrayKey()
                    || $type->type_params[0]->hasInt()
                ) {
                    if ($type instanceof TNonEmptyArray || $is_non_empty) {
                        $array_types[] = Type::getNonEmptyListAtomic($type->type_params[1]);
                    } else {
                        $array_types[] = Type::getListAtomic($type->type_params[1]);
                    }
                }

                if ($type->isEmptyArray()) {
                    //we allow an empty array to pass as a list. We keep the type as empty array though (more precise)
                    $array_types[] = $type;
                }

                $redundant = false;
            } elseif ($type instanceof TCallable) {
                $array_types[] = new TCallableKeyedArray([
                    new Union([new TClassString, new TObject]),
                    Type::getString(),
                ]);

                $redundant = false;
            } elseif ($type instanceof TIterable) {
                $array_types[] = $is_non_empty
                    ? Type::getNonEmptyListAtomic($type->type_params[1])
                    : Type::getListAtomic($type->type_params[1]);

                $redundant = false;
            } else {
                $redundant = false;
            }
        }

        if ((!$array_types || $redundant) && !$is_equality) {
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

                if ($redundant) {
                    $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
                }
            }
        }

        if ($array_types) {
            return TypeCombiner::combine($array_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileStringArrayAccess(
        Assertion $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $inside_loop
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return new Union([
                new TNonEmptyArray([Type::getArrayKey(), Type::getMixed()]),
                new TNamedObject('ArrayAccess'),
            ]);
        }

        $array_types = [];

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TList) {
                $type = $type->getKeyedArray();
            }
            if ($type->isArrayAccessibleWithStringKey($codebase)) {
                if (get_class($type) === TArray::class) {
                    $array_types[] = new TNonEmptyArray($type->type_params);
                } elseif ($type instanceof TKeyedArray && $type->is_list) {
                    $properties = $type->properties;
                    $properties[0] = $properties[0]->setPossiblyUndefined(false);
                    $array_types[] = $type->setProperties($properties);
                } else {
                    $array_types[] = $type;
                }
            } elseif ($type instanceof TTemplateParam) {
                $array_types[] = $type;
            }
        }

        if (!$array_types) {
            if ($key && $code_location) {
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

        if ($array_types) {
            return new Union($array_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed($inside_loop);
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileIntArrayAccess(
        Assertion $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $inside_loop
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed()) {
            return Type::getMixed();
        }

        $array_types = [];

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isArrayAccessibleWithIntOrStringKey($codebase)) {
                if (get_class($type) === TArray::class) {
                    $array_types[] = new TNonEmptyArray($type->type_params);
                } else {
                    $array_types[] = $type;
                }
            } elseif ($type instanceof TTemplateParam) {
                $array_types[] = $type;
            }
        }

        if (!$array_types) {
            if ($key && $code_location) {
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

        if ($array_types) {
            return TypeCombiner::combine($array_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed($inside_loop);
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileCallable(
        Assertion $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        if ($existing_var_type->hasMixed()) {
            return Type::parseString('callable');
        }

        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $callable_types = [];
        $redundant = true;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TList) {
                $type = $type->getKeyedArray();
            }
            if ($type->isCallableType()) {
                $callable_types[] = $type;
            } elseif ($type instanceof TObject) {
                $callable_types[] = new TCallableObject();
                $redundant = false;
            } elseif ($type instanceof TNamedObject
                && $codebase->classExists($type->value)
                && $codebase->methodExists($type->value . '::__invoke')
            ) {
                $callable_types[] = $type;
            } elseif (get_class($type) === TString::class
                || get_class($type) === TNonEmptyString::class
                || get_class($type) === TNonFalsyString::class
            ) {
                $callable_types[] = new TCallableString();
                $redundant = false;
            } elseif (get_class($type) === TLiteralString::class
                && InternalCallMapHandler::inCallMap($type->value)
            ) {
                $callable_types[] = $type;
                $redundant = false;
            } elseif ($type instanceof TArray) {
                $type = new TCallableArray($type->type_params);
                $callable_types[] = $type;
                $redundant = false;
            } elseif ($type instanceof TKeyedArray && count($type->properties) === 2) {
                $type = new TCallableKeyedArray($type->properties);
                $callable_types[] = $type;
                $redundant = false;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasCallableType() || $type->as->hasMixed()) {
                    $type = $type->replaceAs(self::reconcileCallable(
                        $assertion,
                        $codebase,
                        $type->as,
                        null,
                        $negated,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                    ));
                }

                $redundant = false;

                $callable_types[] = $type;
            } elseif ($candidate_callable = CallableTypeComparator::getCallableFromAtomic(
                $codebase,
                $type,
            )) {
                $redundant = false;

                $callable_types[] = $candidate_callable;
            } else {
                $redundant = false;
            }
        }

        if ((!$callable_types || $redundant) && !$is_equality) {
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

        if ($callable_types) {
            return TypeCombiner::combine($callable_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getNever();
    }

    /**
     * @param   Truthy|NonEmpty $assertion
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileTruthyOrNonEmpty(
        Assertion $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $recursive_check
    ): Union {
        $types = $existing_var_type->getAtomicTypes();
        $old_var_type_string = $existing_var_type->getId();

        //empty is used a lot to check for array offset existence, so we have to silent errors a lot
        $is_empty_assertion = $assertion instanceof NonEmpty;

        $redundant = !($existing_var_type->possibly_undefined
            || $existing_var_type->possibly_undefined_from_try);

        foreach ($types as $existing_var_type_key => $existing_var_type_part) {
            //if any atomic in the union is either always falsy, we remove it. If not always truthy, we mark the check
            //as not redundant.
            if ($existing_var_type_part->isFalsy()) {
                $redundant = false;
                unset($types[$existing_var_type_key]);
            } elseif ($existing_var_type->possibly_undefined
                || $existing_var_type->possibly_undefined_from_try
                || !$existing_var_type_part->isTruthy()
            ) {
                $redundant = false;
            }
        }

        if (!$redundant && !$types) {
            //every type was removed, this is an impossible assertion
            if ($code_location && $key && !$is_empty_assertion && !$recursive_check) {
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
            if ($code_location && $key && !$is_empty_assertion && !$recursive_check) {
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

            if (!$types) {
                throw new AssertionError("We must have some types here!");
            }
            return $existing_var_type->setTypes($types);
        }

        if (isset($types['bool'])) {
            unset($types['bool']);
            $types []= new TTrue;
        }

        if (isset($types['array'])) {
            $array_atomic_type = $types['array'];
            if ($array_atomic_type instanceof TList) {
                $array_atomic_type = $array_atomic_type->getKeyedArray();
            }

            if ($array_atomic_type instanceof TArray
                && !$array_atomic_type instanceof TNonEmptyArray
            ) {
                unset($types['array']);
                $types [] = new TNonEmptyArray($array_atomic_type->type_params);
            } elseif ($array_atomic_type instanceof TKeyedArray
                && $array_atomic_type->is_list
                && $array_atomic_type->properties[0]->possibly_undefined
            ) {
                unset($types['array']);
                $properties = $array_atomic_type->properties;
                $properties[0] = $properties[0]->setPossiblyUndefined(false);
                $types [] = $array_atomic_type->setProperties($properties);
            }
        }

        if (isset($types['mixed'])) {
            $mixed_atomic_type = $types['mixed'];

            if (get_class($mixed_atomic_type) === TMixed::class) {
                unset($types['mixed']);
                $types []= new TNonEmptyMixed();
            }
        }

        if (isset($types['scalar'])) {
            $scalar_atomic_type = $types['scalar'];

            if (get_class($scalar_atomic_type) === TScalar::class) {
                unset($types['scalar']);
                $types []= new TNonEmptyScalar();
            }
        }

        if (isset($types['string'])) {
            $string_atomic_type = $types['string'];

            if (get_class($string_atomic_type) === TString::class) {
                unset($types['string']);
                $types []= new TNonFalsyString();
            } elseif (get_class($string_atomic_type) === TLowercaseString::class) {
                unset($types['string']);
                $types []= new TNonEmptyLowercaseString();
            } elseif (get_class($string_atomic_type) === TNonspecificLiteralString::class) {
                unset($types['string']);
                $types []= new TNonEmptyNonspecificLiteralString();
            } elseif (get_class($string_atomic_type) === TNonEmptyString::class) {
                unset($types['string']);
                $types []= new TNonFalsyString();
            }
        }

        if ($existing_var_type->hasInt()) {
            $existing_range_types = $existing_var_type->getRangeInts();

            if ($existing_range_types) {
                foreach ($existing_range_types as $int_key => $literal_type) {
                    if ($literal_type->contains(0)) {
                        unset($types[$int_key]);
                        if ($literal_type->min_bound === null || $literal_type->min_bound <= -1) {
                            $types []= new TIntRange($literal_type->min_bound, -1);
                        }
                        if ($literal_type->max_bound === null || $literal_type->max_bound >= 1) {
                            $types []= new TIntRange(1, $literal_type->max_bound);
                        }
                    }
                }
            }
        }

        foreach ($types as $type_key => $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TTemplateParam) {
                if (!$existing_var_atomic_type->as->isMixed()) {
                    $template_did_fail = 0;

                    $existing_var_atomic_type = $existing_var_atomic_type->replaceAs(self::reconcileTruthyOrNonEmpty(
                        $assertion,
                        $existing_var_atomic_type->as,
                        $key,
                        $negated,
                        $code_location,
                        $suppressed_issues,
                        $template_did_fail,
                        true,
                    ));

                    if (!$template_did_fail) {
                        unset($types[$type_key]);
                        $types []= $existing_var_atomic_type;
                    }
                }
            }
        }

        if (!$types) {
            throw new AssertionError("We must have some types here!");
        }
        $new = $existing_var_type->setTypes($types);
        if ($new === $existing_var_type && ($new->possibly_undefined || $new->possibly_undefined_from_try)) {
            $new = $existing_var_type->setPossiblyUndefined(false, false);
        } else {
            /** @psalm-suppress InaccessibleProperty We just created this type */
            $new->possibly_undefined = false;
            /** @psalm-suppress InaccessibleProperty We just created this type */
            $new->possibly_undefined_from_try = false;
        }
        return $new;
    }

    /**
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileClassConstant(
        Codebase $codebase,
        TClassConstant $class_constant_expression,
        Union $existing_type,
        int &$failed_reconciliation
    ): Union {
        $class_name = $class_constant_expression->fq_classlike_name;
        if (!$codebase->classlike_storage_provider->has($class_name)) {
            return $existing_type;
        }

        $constant_pattern = $class_constant_expression->const_name;

        $resolver = new ClassConstantByWildcardResolver($codebase);
        $matched_class_constant_types = $resolver->resolve(
            $class_name,
            $constant_pattern,
        );

        if ($matched_class_constant_types === null) {
            $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;
            return Type::getNever();
        }

        return TypeCombiner::combine(array_values($matched_class_constant_types), $codebase);
    }

    /**
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileValueOf(
        Codebase $codebase,
        TValueOf $assertion_type,
        int &$failed_reconciliation
    ): ?Union {
        $reconciled_types = [];

        // For now, only enums are supported here
        foreach ($assertion_type->type->getAtomicTypes() as $atomic_type) {
            $enum_case_to_assert = null;
            if ($atomic_type instanceof TClassConstant) {
                $class_name = $atomic_type->fq_classlike_name;
                $enum_case_to_assert = $atomic_type->const_name;
            } elseif ($atomic_type instanceof TNamedObject) {
                $class_name = $atomic_type->value;
            } else {
                return null;
            }

            if (!$codebase->classOrInterfaceOrEnumExists($class_name)) {
                return null;
            }

            $class_storage = $codebase->classlike_storage_provider->get($class_name);
            if (!$class_storage->is_enum) {
                return null;
            }

            if (!in_array($class_storage->enum_type, ['string', 'int'], true)) {
                return null;
            }

            // For value-of<MyBackedEnum>, the assertion is meant to return *ANY* value of *ANY* enum case
            if ($enum_case_to_assert === null) {
                foreach ($class_storage->enum_cases as $enum_case) {
                    $enum_value = $enum_case->getValue($codebase->classlikes);
                    assert(
                        $enum_value !== null,
                        'Verified enum type above, value can not contain `null` anymore.',
                    );
                    $reconciled_types[] = Type::getLiteral($enum_value);
                }

                continue;
            }

            $enum_case = $class_storage->enum_cases[$enum_case_to_assert] ?? null;
            if ($enum_case === null) {
                return null;
            }

            $enum_value = $enum_case->getValue($codebase->classlikes);

            assert($enum_value !== null, 'Verified enum type above, value can not contain `null` anymore.');
            $reconciled_types[] = Type::getLiteral($enum_value);
        }

        if ($reconciled_types === []) {
            $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;
            return Type::getNever();
        }

        return TypeCombiner::combine($reconciled_types, $codebase, false, false);
    }

    /**
     * @psalm-assert-if-true TCallableObject|TObjectWithProperties|TNamedObject $type
     */
    private static function areIntersectionTypesAllowed(Codebase $codebase, Atomic $type): bool
    {
        if ($type instanceof TObjectWithProperties || $type instanceof TCallableObject) {
            return true;
        }

        if (!$type instanceof TNamedObject || !$codebase->classlike_storage_provider->has($type->value)) {
            return false;
        }

        $class_storage = $codebase->classlike_storage_provider->get($type->value);

        return !$class_storage->final;
    }
}
