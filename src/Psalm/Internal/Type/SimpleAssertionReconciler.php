<?php

namespace Psalm\Internal\Type;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Codebase\ClassConstantByWildcardResolver;
use Psalm\Internal\Codebase\InternalCallMapHandler;
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
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableArray;
use Psalm\Type\Atomic\TCallableKeyedArray;
use Psalm\Type\Atomic\TCallableList;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmptyMixed;
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
use Psalm\Type\Atomic\TNonEmptyList;
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
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function assert;
use function count;
use function explode;
use function get_class;
use function min;
use function strpos;

/**
 * This class receives a known type and an assertion (probably coming from AssertionFinder). The goal is to refine
 * the known type using the assertion. For example: old type is `int` assertion is `>5` result is `int<6, max>`.
 * Complex reconciliation takes part in AssertionReconciler if this class couldn't handle the reconciliation
 *
 * @internal
 */
class SimpleAssertionReconciler extends Reconciler
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
        if ($assertion instanceof Any && $existing_var_type->hasMixed()) {
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
                $inside_loop
            );
        }

        if ($assertion instanceof ArrayKeyExists) {
            $existing_var_type->possibly_undefined = false;

            return $existing_var_type;
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
                $failed_reconciliation
            );
        }

        if ($assertion instanceof HasArrayKey) {
            return self::reconcileHasArrayKey(
                $existing_var_type,
                $assertion
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
                $suppressed_issues
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
                $suppressed_issues
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
                false
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
                $is_equality
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
                $inside_loop
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
                $inside_loop
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
                $assertion->count
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
                $failed_reconciliation
            );
        }

        $assertion_type = $assertion->getAtomicType();

        if ($assertion_type instanceof TObject) {
            return self::reconcileObject(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
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
                $is_equality
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
                $is_equality
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
                $is_equality
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
                $is_equality
            );
        }

        if ($assertion_type instanceof TList
            && $assertion_type->type_param->isMixed()
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
                $assertion_type instanceof TNonEmptyList
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
                $is_equality
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
                $is_equality
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
                $is_equality
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
                $is_equality
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
                $failed_reconciliation
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
                $failed_reconciliation
            );
        }

        if ($existing_var_type->isSingle()
            && $existing_var_type->hasTemplate()
        ) {
            foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TTemplateParam && $assertion_type) {
                    if ($atomic_type->as->hasMixed()
                        || $atomic_type->as->hasObject()
                    ) {
                        $atomic_type->as = new Union([clone $assertion_type]);

                        return $existing_var_type;
                    }
                }
            }
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
        $did_remove_type = ($key && strpos($key, '['))
            || !$existing_var_type->initialized
            || $existing_var_type->possibly_undefined
            || $existing_var_type->ignore_isset;

        if ($existing_var_type->isNullable()) {
            $existing_var_type->removeType('null');

            $did_remove_type = true;
        }

        if (!$existing_var_type->hasMixed()
            && !$is_equality
            && (!$did_remove_type || $existing_var_type->isUnionEmpty())
            && $key
            && $code_location
        ) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $old_var_type_string,
                $key,
                $assertion,
                !$did_remove_type,
                $negated,
                $code_location,
                $suppressed_issues
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
            $array_atomic_type = $existing_var_type->getAtomicTypes()['array'];
            $did_remove_type = false;

            if ($array_atomic_type instanceof TArray) {
                if (!$array_atomic_type instanceof TNonEmptyArray
                    || ($assertion instanceof HasAtLeastCount
                        && $array_atomic_type->min_count < $assertion->count)
                ) {
                    if ($array_atomic_type->getId() === 'array<empty, empty>') {
                        $existing_var_type->removeType('array');
                    } else {
                        $non_empty_array = new TNonEmptyArray(
                            $array_atomic_type->type_params
                        );

                        if ($assertion instanceof HasAtLeastCount) {
                            $non_empty_array->min_count = $assertion->count;
                        }

                        $existing_var_type->addType($non_empty_array);
                    }

                    $did_remove_type = true;
                }
            } elseif ($array_atomic_type instanceof TList) {
                if (!$array_atomic_type instanceof TNonEmptyList
                    || ($assertion instanceof HasAtLeastCount
                        && $array_atomic_type->count < $assertion->count)
                ) {
                    $non_empty_list = new TNonEmptyList(
                        $array_atomic_type->type_param
                    );

                    if ($assertion instanceof HasAtLeastCount) {
                        $non_empty_list->min_count = $assertion->count;
                    }

                    $did_remove_type = true;
                    $existing_var_type->addType($non_empty_list);
                }
            } elseif ($array_atomic_type instanceof TKeyedArray) {
                $prop_count = count($array_atomic_type->properties);
                $min_count = 0;
                foreach ($array_atomic_type->properties as $property_type) {
                    if (!$property_type->possibly_undefined) {
                        $min_count++;
                    }
                }

                if ($assertion instanceof HasAtLeastCount) {
                    if ($array_atomic_type->sealed && $assertion->count > $min_count) {
                        $existing_var_type->removeType('array');
                        $did_remove_type = true;
                    } elseif (!$array_atomic_type->sealed
                        && $array_atomic_type->is_list
                        && $min_count === $prop_count
                    ) {
                        if ($assertion->count <= $min_count) {
                            // this means a redundant condition
                        } else {
                            $did_remove_type = true;
                            for ($i = $prop_count; $i < $assertion->count; $i++) {
                                $array_atomic_type->properties[$i]
                                    = clone ($array_atomic_type->previous_value_type ?: Type::getMixed());
                            }
                        }
                    } else {
                        $did_remove_type = true;
                    }
                } elseif ($min_count !== $prop_count) {
                    $did_remove_type = true;
                }
            }

            if (!$is_equality
                && !$existing_var_type->hasMixed()
                && (!$did_remove_type || $existing_var_type->isUnionEmpty())
            ) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $assertion,
                        !$did_remove_type,
                        $negated,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }
        }

        return $existing_var_type->freeze();
    }

    /**
     * @param   positive-int $count
     */
    private static function reconcileExactlyCountable(
        Union $existing_var_type,
        int $count
    ): Union {
        $existing_var_type = $existing_var_type->getBuilder();
        if ($existing_var_type->hasType('array')) {
            $array_atomic_type = $existing_var_type->getAtomicTypes()['array'];

            if ($array_atomic_type instanceof TArray) {
                $non_empty_array = new TNonEmptyArray(
                    $array_atomic_type->type_params
                );

                $non_empty_array->count = $count;

                $existing_var_type->addType(
                    $non_empty_array
                );
            } elseif ($array_atomic_type instanceof TList) {
                $non_empty_list = new TNonEmptyList(
                    $array_atomic_type->type_param,
                    $count
                );

                $existing_var_type->addType(
                    $non_empty_list
                );
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
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TNamedObject
                && $codebase->classOrInterfaceExists($type->value)
            ) {
                if (!$codebase->methodExists($type->value . '::' . $method_name)) {
                    $match_found = false;

                    if ($type->extra_types) {
                        foreach ($type->extra_types as $extra_type) {
                            if ($extra_type instanceof TNamedObject
                                && $codebase->classOrInterfaceExists($extra_type->value)
                                && $codebase->methodExists($extra_type->value . '::' . $method_name)
                            ) {
                                $match_found = true;
                            } elseif ($extra_type instanceof TObjectWithProperties) {
                                $match_found = true;

                                if (!isset($extra_type->methods[$method_name])) {
                                    $extra_type->methods[$method_name] = 'object::' . $method_name;
                                    $did_remove_type = true;
                                }
                            }
                        }
                    }

                    if (!$match_found) {
                        $type = $type->addIntersectionType(new TObjectWithProperties(
                            [],
                            [$method_name => $type->value . '::' . $method_name]
                        ));
                        $did_remove_type = true;
                    }
                }
                $object_types[] = $type;
            } elseif ($type instanceof TObjectWithProperties) {
                if (!isset($type->methods[$method_name])) {
                    $type->methods[$method_name] = 'object::' . $method_name;
                    $did_remove_type = true;
                }
                $object_types[] = $type;
            } elseif ($type instanceof TObject || $type instanceof TMixed) {
                $object_types[] = new TObjectWithProperties(
                    [],
                    [$method_name =>  'object::' . $method_name]
                );
                $did_remove_type = true;
            } elseif ($type instanceof TString) {
                // we don’t know
                $object_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                $object_types[] = $type;
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if (!$object_types || !$did_remove_type) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($object_types) {
            return new Union($object_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? new Union([new TEmptyMixed()])
            : Type::getNever();
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
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TString) {
                $string_types[] = $type;

                if (get_class($type) === TString::class) {
                    $type->from_docblock = false;
                }
            } elseif ($type instanceof TCallable) {
                $string_types[] = new TCallableString;
                $did_remove_type = true;
            } elseif ($type instanceof TNumeric) {
                $string_types[] = new TNumericString;
                $did_remove_type = true;
            } elseif ($type instanceof TScalar || $type instanceof TArrayKey) {
                $string_types[] = new TString;
                $did_remove_type = true;
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
                        $is_equality
                    ));

                    $string_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$string_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($string_types) {
            return new Union($string_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? new Union([new TEmptyMixed()])
            : Type::getNever();
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
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TInt) {
                $int_types[] = $type;

                if (get_class($type) === TInt::class) {
                    $type->from_docblock = false;
                }

                if ($existing_var_type->from_calculation) {
                    $did_remove_type = true;
                }
            } elseif ($type instanceof TNumeric) {
                $int_types[] = new TInt;
                $did_remove_type = true;
            } elseif ($type instanceof TScalar || $type instanceof TArrayKey) {
                $int_types[] = new TInt;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasInt() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileInt(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation
                    );

                    $int_types[] = $type;
                }

                $did_remove_type = true;
            } elseif ($type instanceof TString && $assertion instanceof IsLooselyEqual) {
                $int_types[] = new TNumericString();
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$int_types) && $assertion instanceof IsType) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($int_types) {
            return new Union($int_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? new Union([new TEmptyMixed()])
            : Type::getNever();
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
        $did_remove_type = false;

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TBool) {
                $bool_types[] = $type;
                $type->from_docblock = false;
            } elseif ($type instanceof TScalar) {
                $bool_types[] = new TBool;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasBool() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileBool(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $bool_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$bool_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($bool_types) {
            return new Union($bool_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getNever();
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
        $did_remove_type = false;

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof Scalar) {
                $scalar_types[] = $type;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasScalar() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileScalar(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $scalar_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$scalar_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($scalar_types) {
            return new Union($scalar_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getNever();
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
        $did_remove_type = false;

        if ($existing_var_type->hasString()) {
            $did_remove_type = true;
            $existing_var_type->removeType('string');
            $existing_var_type->addType(new TNumericString);
        }

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TNumeric || $type instanceof TNumericString) {
                // this is a workaround for a possible issue running
                // is_numeric($a) && is_string($a)
                $did_remove_type = true;
                $numeric_types[] = $type;
            } elseif ($type->isNumericType()) {
                $numeric_types[] = $type;
            } elseif ($type instanceof TScalar) {
                $did_remove_type = true;
                $numeric_types[] = new TNumeric();
            } elseif ($type instanceof TArrayKey) {
                $did_remove_type = true;
                $numeric_types[] = new TInt();
                $numeric_types[] = new TNumericString();
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasNumeric() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileNumeric(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $numeric_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$numeric_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($numeric_types) {
            return new Union($numeric_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getNever();
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
        if ($existing_var_type->hasMixed()) {
            return Type::getObject();
        }

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $object_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isObjectType()) {
                $object_types[] = $type;
            } elseif ($type instanceof TCallable) {
                $object_types[] = new TCallableObject();
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam
                && $type->as->isMixed()
            ) {
                $type = clone $type;
                $type->as = Type::getObject();
                $object_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasObject() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileObject(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $object_types[] = $type;
                }

                $did_remove_type = true;
            } elseif ($type instanceof TIterable) {
                $params = $type->type_params;
                $params[0] = self::refineArrayKey($params[0]);

                $object_types[] = new TGenericObject(
                    'Traversable',
                    $params
                );

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$object_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($object_types) {
            return new Union($object_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getNever();
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
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TResource) {
                $resource_types[] = $type;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$resource_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($resource_types) {
            return new Union($resource_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getNever();
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
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isCountable($codebase)) {
                $iterable_types[] = $type;
            } elseif ($type instanceof TObject) {
                $iterable_types[] = new TNamedObject('Countable');
                $did_remove_type = true;
            } elseif ($type instanceof TNamedObject || $type instanceof TIterable) {
                $countable = new TNamedObject('Countable');
                $type = $type->addIntersectionType($countable);
                $iterable_types[] = $type;
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$iterable_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($iterable_types) {
            return new Union($iterable_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getNever();
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
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isIterable($codebase)) {
                $iterable_types[] = $type;
            } elseif ($type instanceof TObject) {
                $iterable_types[] = new TNamedObject('Traversable');
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$iterable_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($iterable_types) {
            return new Union($iterable_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getNever();
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
        $new_var_type = clone $assertion->type;

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
                    $suppressed_issues
                );
            }

            $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

            return $existing_var_type->from_docblock
                ? Type::getMixed()
                : Type::getNever();
        }

        return $intersection;
    }

    private static function reconcileHasArrayKey(
        Union $existing_var_type,
        HasArrayKey $assertion
    ): Union {
        $assertion = $assertion->key;
        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TKeyedArray) {
                $is_class_string = false;

                if (strpos($assertion, '::class')) {
                    [$assertion] = explode('::', $assertion);
                    $is_class_string = true;
                }

                if (isset($atomic_type->properties[$assertion])) {
                    $atomic_type->properties[$assertion]->possibly_undefined = false;
                } else {
                    $atomic_type->properties[$assertion] = Type::getMixed();

                    if ($is_class_string) {
                        $atomic_type->class_strings[$assertion] = true;
                    }
                }
            }
        }

        return $existing_var_type;
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

        $did_remove_type = false;

        if ($existing_var_type->hasType('null') && $assertion->doesFilterNull()) {
            $did_remove_type = true;
            $existing_var_type->removeType('null');
        }

        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($inside_loop) {
                continue;
            }

            if ($atomic_type instanceof TIntRange) {
                if ($atomic_type->contains($assertion_value)) {
                    // if the range contains the assertion, the range must be adapted
                    $did_remove_type = true;
                    $existing_var_type->removeType($atomic_type->getKey());
                    if ($atomic_type->min_bound === null) {
                        $atomic_type->min_bound = $assertion_value;
                    } else {
                        $atomic_type->min_bound = TIntRange::getNewHighestBound(
                            $assertion_value,
                            $atomic_type->min_bound
                        );
                    }
                    $existing_var_type->addType($atomic_type);
                } elseif ($atomic_type->isLesserThan($assertion_value)) {
                    // if the range is lesser than the assertion, the type must be removed
                    $did_remove_type = true;
                    $existing_var_type->removeType($atomic_type->getKey());
                } elseif ($atomic_type->isGreaterThan($assertion_value)) {
                    // if the range is greater than the assertion, the check is redundant
                }
            } elseif ($atomic_type instanceof TLiteralInt) {
                if ($atomic_type->value < $assertion_value) {
                    $did_remove_type = true;
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
                $did_remove_type = true;
                $existing_var_type->removeType($atomic_type->getKey());
                $existing_var_type->addType(new TIntRange($assertion_value, null));
            } else {
                // we assume that other types may have been removed (empty strings? numeric strings?)
                //It may be worth refining to improve reconciliation while keeping in mind we're on loose comparison
                $did_remove_type = true;
            }
        }

        if (!$inside_loop && !$did_remove_type && $var_id && $code_location) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $old_var_type_string,
                $var_id,
                $assertion,
                true,
                $negated,
                $code_location,
                $suppressed_issues
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
                    $suppressed_issues
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

        $did_remove_type = false;

        if ($existing_var_type->hasType('null') && $assertion->doesFilterNull()) {
            $did_remove_type = true;
            $existing_var_type->removeType('null');
        }

        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($inside_loop) {
                continue;
            }

            if ($atomic_type instanceof TIntRange) {
                if ($atomic_type->contains($assertion_value)) {
                    // if the range contains the assertion, the range must be adapted
                    $did_remove_type = true;
                    $existing_var_type->removeType($atomic_type->getKey());
                    if ($atomic_type->max_bound === null) {
                        $atomic_type->max_bound = $assertion_value;
                    } else {
                        $atomic_type->max_bound = min($atomic_type->max_bound, $assertion_value);
                    }
                    $existing_var_type->addType($atomic_type);
                } elseif ($atomic_type->isLesserThan($assertion_value)) {
                    // if the range is lesser than the assertion, the check is redundant
                } elseif ($atomic_type->isGreaterThan($assertion_value)) {
                    // if the range is greater than the assertion, the type must be removed
                    $did_remove_type = true;
                    $existing_var_type->removeType($atomic_type->getKey());
                }
            } elseif ($atomic_type instanceof TLiteralInt) {
                if ($atomic_type->value > $assertion_value) {
                    $did_remove_type = true;
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
                $did_remove_type = true;
                $existing_var_type->removeType($atomic_type->getKey());
                $existing_var_type->addType(new TIntRange(null, $assertion_value));
            } else {
                // we assume that other types may have been removed (empty strings? numeric strings?)
                //It may be worth refining to improve reconciliation while keeping in mind we're on loose comparison
                $did_remove_type = true;
            }
        }

        if (!$inside_loop && !$did_remove_type && $var_id && $code_location) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $old_var_type_string,
                $var_id,
                $assertion,
                true,
                $negated,
                $code_location,
                $suppressed_issues
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
                    $suppressed_issues
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
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->hasTraversableInterface($codebase)) {
                $traversable_types[] = $type;
            } elseif ($type instanceof TIterable) {
                $clone_type = clone $type;
                $traversable_types[] = new TGenericObject('Traversable', $clone_type->type_params);
                $did_remove_type = true;
            } elseif ($type instanceof TObject) {
                $traversable_types[] = new TNamedObject('Traversable');
                $did_remove_type = true;
            } elseif ($type instanceof TNamedObject) {
                $traversable = new TNamedObject('Traversable');
                $type = $type->addIntersectionType($traversable);
                $traversable_types[] = $type;
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$traversable_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($traversable_types) {
            return new Union($traversable_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getNever();
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
            return Type::getArray();
        }

        $array_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TArray || $type instanceof TKeyedArray || $type instanceof TList) {
                $array_types[] = $type;
            } elseif ($type instanceof TCallable) {
                $array_types[] = new TCallableKeyedArray([
                    new Union([new TClassString, new TObject]),
                    Type::getString()
                ]);

                $did_remove_type = true;
            } elseif ($type instanceof TIterable) {
                $params = $type->type_params;
                $params[0] = self::refineArrayKey($params[0]);
                $array_types[] = new TArray($params);

                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasArray() || $type->as->hasIterable() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileArray(
                        $assertion,
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $array_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$array_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );

                if (!$did_remove_type) {
                    $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
                }
            }
        }

        if ($array_types) {
            return TypeCombiner::combine($array_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getNever();
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
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TList
                || ($type instanceof TKeyedArray && $type->is_list)
            ) {
                if ($is_non_empty && $type instanceof TList && !$type instanceof TNonEmptyList) {
                    $array_types[] = new TNonEmptyList($type->type_param);
                    $did_remove_type = true;
                } else {
                    $array_types[] = $type;
                }
            } elseif ($type instanceof TArray || $type instanceof TKeyedArray) {
                if ($type instanceof TKeyedArray) {
                    $type = $type->getGenericArrayType();
                }

                if ($type->type_params[0]->hasArrayKey()
                    || $type->type_params[0]->hasInt()
                ) {
                    if ($type instanceof TNonEmptyArray) {
                        $array_types[] = new TNonEmptyList($type->type_params[1]);
                    } else {
                        $array_types[] = new TList($type->type_params[1]);
                    }
                }

                if ($type->isEmptyArray()) {
                    //we allow an empty array to pass as a list. We keep the type as empty array though (more precise)
                    $array_types[] = $type;
                }

                $did_remove_type = true;
            } elseif ($type instanceof TCallable) {
                $array_types[] = new TCallableKeyedArray([
                    new Union([new TClassString, new TObject]),
                    Type::getString()
                ]);

                $did_remove_type = true;
            } elseif ($type instanceof TIterable) {
                $clone_type = clone $type;
                $array_types[] = new TList($clone_type->type_params[1]);

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$array_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );

                if (!$did_remove_type) {
                    $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
                }
            }
        }

        if ($array_types) {
            return TypeCombiner::combine($array_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getNever();
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
            if ($type->isArrayAccessibleWithStringKey($codebase)) {
                if (get_class($type) === TArray::class) {
                    $array_types[] = new TNonEmptyArray($type->type_params);
                } elseif (get_class($type) === TList::class) {
                    $array_types[] = new TNonEmptyList($type->type_param);
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
                    $suppressed_issues
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
                    $suppressed_issues
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
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isCallableType()) {
                $callable_types[] = $type;
            } elseif ($type instanceof TObject) {
                $callable_types[] = new TCallableObject();
                $did_remove_type = true;
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
                $did_remove_type = true;
            } elseif (get_class($type) === TLiteralString::class
                && InternalCallMapHandler::inCallMap($type->value)
            ) {
                $callable_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TArray) {
                $type = clone $type;
                $type = new TCallableArray($type->type_params);
                $callable_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TList) {
                $type = clone $type;
                $type = new TCallableList($type->type_param);
                $callable_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TKeyedArray && count($type->properties) === 2) {
                $type = clone $type;
                $type = new TCallableKeyedArray($type->properties);
                $callable_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasCallableType() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileCallable(
                        $assertion,
                        $codebase,
                        $type->as,
                        null,
                        $negated,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );
                }

                $did_remove_type = true;

                $callable_types[] = $type;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$callable_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($callable_types) {
            return TypeCombiner::combine($callable_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getNever();
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
        $existing_var_type = $existing_var_type->getBuilder();
        $old_var_type_string = $existing_var_type->getId();

        //empty is used a lot to check for array offset existence, so we have to silent errors a lot
        $is_empty_assertion = $assertion instanceof NonEmpty;

        $did_remove_type = $existing_var_type->possibly_undefined
            || $existing_var_type->possibly_undefined_from_try;

        foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_key => $existing_var_type_part) {
            //if any atomic in the union is either always falsy, we remove it. If not always truthy, we mark the check
            //as not redundant.
            if ($existing_var_type_part->isFalsy()) {
                $did_remove_type = true;
                $existing_var_type->removeType($existing_var_type_key);
            } elseif ($existing_var_type->possibly_undefined
                || $existing_var_type->possibly_undefined_from_try
                || !$existing_var_type_part->isTruthy()
            ) {
                $did_remove_type = true;
            }
        }

        if ($did_remove_type && $existing_var_type->isUnionEmpty()) {
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
                    $suppressed_issues
                );
            }

            $failed_reconciliation = 2;

            return Type::getNever();
        }

        if (!$did_remove_type) {
            if ($code_location && $key && !$is_empty_assertion && !$recursive_check) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            $failed_reconciliation = 1;

            return $existing_var_type->freeze();
        }

        $existing_var_type->possibly_undefined = false;
        $existing_var_type->possibly_undefined_from_try = false;

        if ($existing_var_type->hasType('bool')) {
            $existing_var_type->removeType('bool');
            $existing_var_type->addType(new TTrue());
        }

        if ($existing_var_type->hasArray()) {
            $array_atomic_type = $existing_var_type->getAtomicTypes()['array'];

            if ($array_atomic_type instanceof TArray
                && !$array_atomic_type instanceof TNonEmptyArray
            ) {
                $existing_var_type->removeType('array');
                $existing_var_type->addType(
                    new TNonEmptyArray(
                        $array_atomic_type->type_params
                    )
                );
            } elseif ($array_atomic_type instanceof TList
                && !$array_atomic_type instanceof TNonEmptyList
            ) {
                $existing_var_type->removeType('array');
                $existing_var_type->addType(
                    new TNonEmptyList(
                        $array_atomic_type->type_param
                    )
                );
            }
        }

        if ($existing_var_type->hasMixed()) {
            $mixed_atomic_type = $existing_var_type->getAtomicTypes()['mixed'];

            if (get_class($mixed_atomic_type) === TMixed::class) {
                $existing_var_type->removeType('mixed');
                $existing_var_type->addType(new TNonEmptyMixed());
            }
        }

        if ($existing_var_type->hasScalar()) {
            $scalar_atomic_type = $existing_var_type->getAtomicTypes()['scalar'];

            if (get_class($scalar_atomic_type) === TScalar::class) {
                $existing_var_type->removeType('scalar');
                $existing_var_type->addType(new TNonEmptyScalar());
            }
        }

        if ($existing_var_type->hasType('string')) {
            $string_atomic_type = $existing_var_type->getAtomicTypes()['string'];

            if (get_class($string_atomic_type) === TString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TNonFalsyString());
            } elseif (get_class($string_atomic_type) === TLowercaseString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TNonEmptyLowercaseString());
            } elseif (get_class($string_atomic_type) === TNonspecificLiteralString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TNonEmptyNonspecificLiteralString());
            } elseif (get_class($string_atomic_type) === TNonEmptyString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TNonFalsyString());
            }
        }

        if ($existing_var_type->hasInt()) {
            $existing_range_types = $existing_var_type->getRangeInts();

            if ($existing_range_types) {
                foreach ($existing_range_types as $int_key => $literal_type) {
                    if ($literal_type->contains(0)) {
                        $existing_var_type->removeType($int_key);
                        if ($literal_type->min_bound === null || $literal_type->min_bound <= -1) {
                            $existing_var_type->addType(new TIntRange($literal_type->min_bound, -1));
                        }
                        if ($literal_type->max_bound === null || $literal_type->max_bound >= 1) {
                            $existing_var_type->addType(new TIntRange(1, $literal_type->max_bound));
                        }
                    }
                }
            }

            if ($existing_var_type->isSingle()) {
                return $existing_var_type->freeze();
            }
        }

        foreach ($existing_var_type->getAtomicTypes() as $type_key => $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TTemplateParam) {
                if (!$existing_var_atomic_type->as->isMixed()) {
                    $template_did_fail = 0;

                    $existing_var_atomic_type = clone $existing_var_atomic_type;

                    $existing_var_atomic_type->as = self::reconcileTruthyOrNonEmpty(
                        $assertion,
                        $existing_var_atomic_type->as,
                        $key,
                        $negated,
                        $code_location,
                        $suppressed_issues,
                        $template_did_fail,
                        true
                    );

                    if (!$template_did_fail) {
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
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileClassConstant(
        Codebase $codebase,
        TClassConstant $class_constant_expression,
        Union $existing_type,
        int &$failed_reconciliation
    ): Union {
        $class_name = $class_constant_expression->fq_classlike_name;
        $constant_pattern = $class_constant_expression->const_name;

        $resolver = new ClassConstantByWildcardResolver($codebase);
        $matched_class_constant_types = $resolver->resolve($class_name, $constant_pattern);
        if ($matched_class_constant_types === null) {
            return $existing_type;
        }

        if ($matched_class_constant_types === []) {
            $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;
            return Type::getNever();
        }

        return TypeCombiner::combine($matched_class_constant_types, $codebase);
    }
}
