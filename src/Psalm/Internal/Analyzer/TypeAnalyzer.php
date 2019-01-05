<?php
namespace Psalm\Internal\Analyzer;

use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericIterable;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TGenericParam;
use Psalm\Type\Atomic\GetClassT;
use Psalm\Type\Atomic\THtmlEscapedString;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;

/**
 * @internal
 */
class TypeAnalyzer
{
    /**
     * Does the input param type match the given param type
     *
     * @param  Type\Union   $input_type
     * @param  Type\Union   $container_type
     * @param  bool         $ignore_null
     * @param  bool         $ignore_false
     * @param  bool         &$has_scalar_match
     * @param  bool         &$type_coerced    whether or not there was type coercion involved
     * @param  bool         &$type_coerced_from_mixed
     * @param  bool         &$to_string_cast
     * @param  bool         &$type_coerced_from_scalar
     * @param  bool        $allow_interface_equality
     *
     * @return bool
     */
    public static function isContainedBy(
        Codebase $codebase,
        Type\Union $input_type,
        Type\Union $container_type,
        $ignore_null = false,
        $ignore_false = false,
        &$has_scalar_match = null,
        &$type_coerced = null,
        &$type_coerced_from_mixed = null,
        &$to_string_cast = null,
        &$type_coerced_from_scalar = null,
        $allow_interface_equality = false
    ) {
        $has_scalar_match = true;

        if ($container_type->hasMixed() && !$container_type->isEmptyMixed()) {
            return true;
        }

        if ($input_type->possibly_undefined && !$container_type->possibly_undefined) {
            return false;
        }

        foreach ($input_type->getTypes() as $input_type_part) {
            if ($input_type_part instanceof TNull && $ignore_null) {
                continue;
            }

            if ($input_type_part instanceof TFalse && $ignore_false) {
                continue;
            }

            $type_match_found = false;
            $scalar_type_match_found = false;
            $all_to_string_cast = true;

            if ($input_type_part instanceof TArrayKey
                && ($container_type->hasInt() && $container_type->hasString())
            ) {
                continue;
            }

            foreach ($container_type->getTypes() as $container_type_part) {
                $atomic_to_string_cast = false;

                $is_atomic_contained_by = self::isAtomicContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    true,
                    $scalar_type_match_found,
                    $type_coerced,
                    $type_coerced_from_mixed,
                    $atomic_to_string_cast,
                    $type_coerced_from_scalar
                );

                if ($is_atomic_contained_by) {
                    $type_match_found = true;
                }

                if ($atomic_to_string_cast !== true && $type_match_found) {
                    $all_to_string_cast = false;
                }
            }

            // only set this flag if we're definite that the only
            // reason the type match has been found is because there
            // was a __toString cast
            if ($all_to_string_cast && $type_match_found) {
                $to_string_cast = true;
            }

            if (!$type_match_found) {
                if (!$scalar_type_match_found) {
                    $has_scalar_match = false;
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Used for comparing signature typehints, uses PHP's light contravariance rules
     *
     * @param  Type\Union   $input_type
     * @param  Type\Union   $container_type
     *
     * @return bool
     */
    public static function isContainedByInPhp(
        Type\Union $input_type = null,
        Type\Union $container_type
    ) {
        if (!$input_type) {
            return false;
        }

        if ($input_type->getId() === $container_type->getId()) {
            return true;
        }

        if ($input_type->isNullable() && !$container_type->isNullable()) {
            return false;
        }

        $input_type_not_null = clone $input_type;
        $input_type_not_null->removeType('null');

        $container_type_not_null = clone $container_type;
        $container_type_not_null->removeType('null');

        if ($input_type_not_null->getId() === $container_type_not_null->getId()) {
            return true;
        }

        if ($input_type_not_null->hasArray() && $container_type_not_null->hasType('iterable')) {
            return true;
        }

        return false;
    }

    /**
     * Does the input param type match the given param type
     *
     * @param  Type\Union   $input_type
     * @param  Type\Union   $container_type
     * @param  bool         $ignore_null
     * @param  bool         $ignore_false
     *
     * @return bool
     */
    public static function canBeContainedBy(
        Codebase $codebase,
        Type\Union $input_type,
        Type\Union $container_type,
        $ignore_null = false,
        $ignore_false = false
    ) {
        if ($container_type->hasMixed()) {
            return true;
        }

        if ($input_type->possibly_undefined && !$container_type->possibly_undefined) {
            return false;
        }

        foreach ($container_type->getTypes() as $container_type_part) {
            if ($container_type_part instanceof TNull && $ignore_null) {
                continue;
            }

            if ($container_type_part instanceof TFalse && $ignore_false) {
                continue;
            }

            $scalar_type_match_found = false;
            $atomic_to_string_cast = false;

            foreach ($input_type->getTypes() as $input_type_part) {
                $is_atomic_contained_by = self::isAtomicContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    false,
                    false,
                    $scalar_type_match_found,
                    $type_coerced,
                    $type_coerced_from_mixed,
                    $atomic_to_string_cast
                );

                if ($is_atomic_contained_by && !$atomic_to_string_cast) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Can any part of the $type1 be equal to any part of $type2
     *
     * @return bool
     */
    public static function canExpressionTypesBeIdentical(
        Codebase $codebase,
        Type\Union $type1,
        Type\Union $type2
    ) {
        if ($type1->hasMixed() || $type2->hasMixed()) {
            return true;
        }

        if ($type1->isNullable() && $type2->isNullable()) {
            return true;
        }

        foreach ($type1->getTypes() as $type1_part) {
            foreach ($type2->getTypes() as $type2_part) {
                $either_contains = self::isAtomicContainedBy(
                    $codebase,
                    $type1_part,
                    $type2_part,
                    true,
                    false
                ) || self::isAtomicContainedBy(
                    $codebase,
                    $type2_part,
                    $type1_part,
                    true,
                    false
                );

                if ($either_contains) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  Codebase       $codebase
     * @param  TNamedObject|TGenericParam|TIterable  $input_type_part
     * @param  TNamedObject|TGenericParam|TIterable  $container_type_part
     * @param  bool           $allow_interface_equality
     *
     * @return bool
     */
    private static function isObjectContainedByObject(
        Codebase $codebase,
        $input_type_part,
        $container_type_part,
        $allow_interface_equality
    ) {
        $intersection_input_types = $input_type_part->extra_types ?: [];
        $intersection_input_types[] = $input_type_part;

        $intersection_container_types = $container_type_part->extra_types ?: [];
        $intersection_container_types[] = $container_type_part;

        foreach ($intersection_container_types as $intersection_container_type) {
            if ($intersection_container_type instanceof TIterable) {
                $intersection_container_type_lower = 'iterable';
            } elseif ($intersection_container_type instanceof TGenericParam) {
                if ($intersection_container_type->as->isMixed()) {
                    continue;
                }

                $intersection_container_type_lower = null;

                foreach ($intersection_container_type->as->getTypes() as $g) {
                    if ($g instanceof TNull) {
                        continue;
                    }

                    if (!$g instanceof TNamedObject) {
                        continue 2;
                    }

                    $intersection_container_type_lower = strtolower($g->value);
                }

                if ($intersection_container_type_lower === null) {
                    return false;
                }
            } else {
                $intersection_container_type_lower = strtolower(
                    $codebase->classlikes->getUnAliasedName(
                        strtolower($intersection_container_type->value)
                    )
                );
            }

            foreach ($intersection_input_types as $intersection_input_type) {
                if ($intersection_input_type instanceof TIterable) {
                    $intersection_input_type_lower = 'iterable';
                } elseif ($intersection_input_type instanceof TGenericParam) {
                    if ($intersection_input_type->as->isMixed()) {
                        continue;
                    }

                    $intersection_input_type_lower = null;

                    foreach ($intersection_input_type->as->getTypes() as $g) {
                        if ($g instanceof TNull) {
                            continue;
                        }

                        if (!$g instanceof TNamedObject) {
                            continue 2;
                        }

                        $intersection_input_type_lower = strtolower($g->value);
                    }

                    if ($intersection_input_type_lower === null) {
                        return false;
                    }
                } else {
                    $intersection_input_type_lower = strtolower(
                        $codebase->classlikes->getUnAliasedName(
                            strtolower($intersection_input_type->value)
                        )
                    );
                }

                if ($intersection_container_type_lower === $intersection_input_type_lower) {
                    continue 2;
                }

                if ($intersection_input_type_lower === 'generator'
                    && in_array($intersection_container_type_lower, ['iterator', 'traversable', 'iterable'], true)
                ) {
                    continue 2;
                }

                if ($intersection_input_type_lower === 'traversable'
                    && $intersection_container_type_lower === 'iterable'
                ) {
                    continue 2;
                }

                $input_type_is_interface = $codebase->interfaceExists($intersection_input_type_lower);
                $container_type_is_interface = $codebase->interfaceExists($intersection_container_type_lower);

                if ($allow_interface_equality && $input_type_is_interface && $container_type_is_interface) {
                    continue 2;
                }

                if ($codebase->classExists($intersection_input_type_lower)
                    && $codebase->classExtendsOrImplements(
                        $intersection_input_type_lower,
                        $intersection_container_type_lower
                    )
                ) {
                    continue 2;
                }

                if ($input_type_is_interface
                    && $codebase->interfaceExtends(
                        $intersection_input_type_lower,
                        $intersection_container_type_lower
                    )
                ) {
                    continue 2;
                }

                if (ExpressionAnalyzer::isMock($intersection_input_type_lower)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Does the input param atomic type match the given param atomic type
     *
     * @param  Codebase     $codebase
     * @param  Type\Atomic  $input_type_part
     * @param  Type\Atomic  $container_type_part
     * @param  bool         &$has_scalar_match
     * @param  bool         &$type_coerced    whether or not there was type coercion involved
     * @param  bool         &$type_coerced_from_mixed
     * @param  bool         &$to_string_cast
     * @param  bool         &$type_coerced_from_scalar
     * @param  bool         $allow_interface_equality
     * @param  bool         $allow_float_int_equality  whether or not floats and its can be equal
     *
     * @return bool
     */
    public static function isAtomicContainedBy(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        $allow_interface_equality = false,
        $allow_float_int_equality = true,
        &$has_scalar_match = null,
        &$type_coerced = null,
        &$type_coerced_from_mixed = null,
        &$to_string_cast = null,
        &$type_coerced_from_scalar = null
    ) {
        if ($container_type_part instanceof TMixed
            || ($container_type_part instanceof TGenericParam
                && $container_type_part->as->isMixed()
                && !$container_type_part->extra_types)
        ) {
            if (get_class($container_type_part) === TEmptyMixed::class
                && get_class($input_type_part) === TMixed::class
            ) {
                $type_coerced = true;
                $type_coerced_from_mixed = true;

                return false;
            }

            return true;
        }

        if ($input_type_part instanceof TNever) {
            return true;
        }

        if ($input_type_part instanceof TMixed
            || ($input_type_part instanceof TGenericParam
                && $input_type_part->as->isMixed()
                && !$input_type_part->extra_types)
        ) {
            $type_coerced = true;
            $type_coerced_from_mixed = true;

            return false;
        }

        if ($input_type_part instanceof TNull && $container_type_part instanceof TNull) {
            return true;
        }

        if ($input_type_part instanceof TNull || $container_type_part instanceof TNull) {
            return false;
        }

        if ($input_type_part->shallowEquals($container_type_part)
            || (($input_type_part instanceof TNamedObject
                    || $input_type_part instanceof TGenericParam
                    || $input_type_part instanceof TIterable)
                && ($container_type_part instanceof TNamedObject
                    || $container_type_part instanceof TGenericParam
                    || $container_type_part instanceof TIterable)
                && self::isObjectContainedByObject(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality
                )
            )
        ) {
            return self::isMatchingTypeContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $has_scalar_match,
                $type_coerced,
                $type_coerced_from_mixed,
                $to_string_cast,
                $allow_interface_equality
            );
        }

        if ($container_type_part instanceof TGenericParam) {
            $container_type_part = array_values($container_type_part->as->getTypes())[0];
        }

        if ($input_type_part instanceof TGenericParam) {
            $input_type_part = array_values($input_type_part->as->getTypes())[0];
        }

        if ($input_type_part->shallowEquals($container_type_part)) {
            return self::isMatchingTypeContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $has_scalar_match,
                $type_coerced,
                $type_coerced_from_mixed,
                $to_string_cast,
                $allow_interface_equality
            );
        }

        if ($input_type_part instanceof TFalse
            && $container_type_part instanceof TBool
            && !($container_type_part instanceof TTrue)
        ) {
            return true;
        }

        if ($input_type_part instanceof TTrue
            && $container_type_part instanceof TBool
            && !($container_type_part instanceof TFalse)
        ) {
            return true;
        }

        // from https://wiki.php.net/rfc/scalar_type_hints_v5:
        //
        // > int types can resolve a parameter type of float
        if ($input_type_part instanceof TInt
            && $container_type_part instanceof TFloat
            && $allow_float_int_equality
        ) {
            return true;
        }

        if ($input_type_part instanceof TNamedObject
            && $input_type_part->value === 'static'
            && $container_type_part instanceof TNamedObject
            && strtolower($container_type_part->value) === 'self'
        ) {
            return true;
        }

        if ($container_type_part instanceof TCallable && $input_type_part instanceof Type\Atomic\Fn) {
            $all_types_contain = true;

            if (self::compareCallable(
                $codebase,
                $input_type_part,
                $container_type_part,
                $type_coerced,
                $type_coerced_from_mixed,
                $has_scalar_match,
                $all_types_contain
            ) === false
            ) {
                return false;
            }

            if (!$all_types_contain) {
                return false;
            }
        }

        if ($input_type_part instanceof TNamedObject &&
            $input_type_part->value === 'Closure' &&
            $container_type_part instanceof TCallable
        ) {
            return true;
        }

        if ($input_type_part instanceof TObject &&
            $container_type_part instanceof TCallable
        ) {
            return true;
        }

        if ($container_type_part instanceof TNumeric &&
            ($input_type_part->isNumericType() || $input_type_part instanceof TString)
        ) {
            return true;
        }

        if ($container_type_part instanceof TArrayKey &&
            ($input_type_part instanceof TInt || $input_type_part instanceof TString)
        ) {
            return true;
        }

        if ($input_type_part instanceof TArrayKey &&
            ($container_type_part instanceof TInt || $container_type_part instanceof TString)
        ) {
            $type_coerced = true;
            $type_coerced_from_mixed = true;
            $has_scalar_match = true;

            return false;
        }

        if ($container_type_part instanceof ObjectLike && $input_type_part instanceof ObjectLike) {
            $all_types_contain = true;

            foreach ($container_type_part->properties as $key => $container_property_type) {
                if (!isset($input_type_part->properties[$key])) {
                    if (!$container_property_type->possibly_undefined) {
                        $all_types_contain = false;
                    }

                    continue;
                }

                $input_property_type = $input_type_part->properties[$key];

                if (!$input_property_type->isEmpty()
                    && !self::isContainedBy(
                        $codebase,
                        $input_property_type,
                        $container_property_type,
                        $input_property_type->ignore_nullable_issues,
                        $input_property_type->ignore_falsable_issues,
                        $property_has_scalar_match,
                        $property_type_coerced,
                        $property_type_coerced_from_mixed,
                        $property_type_to_string_cast,
                        $property_type_coerced_from_scalar,
                        $allow_interface_equality
                    )
                    && !$property_type_coerced_from_scalar
                ) {
                    if (self::isContainedBy(
                        $codebase,
                        $container_property_type,
                        $input_property_type,
                        false,
                        false,
                        $inverse_property_has_scalar_match,
                        $inverse_property_type_coerced,
                        $inverse_property_type_coerced_from_mixed,
                        $inverse_property_type_to_string_cast,
                        $inverse_property_type_coerced_from_scalar,
                        $allow_interface_equality
                    )
                    || $inverse_property_type_coerced_from_scalar
                    ) {
                        $type_coerced = true;
                    }

                    $all_types_contain = false;
                }
            }

            if ($all_types_contain) {
                $to_string_cast = false;

                return true;
            }

            return false;
        }

        if ($container_type_part instanceof TIterable) {
            if ($input_type_part instanceof TArray || $input_type_part instanceof ObjectLike) {
                if (!$container_type_part instanceof TGenericIterable) {
                    return true;
                }

                if ($input_type_part instanceof ObjectLike) {
                    $input_type_part = $input_type_part->getGenericArrayType();
                }

                $all_types_contain = true;

                foreach ($input_type_part->type_params as $i => $input_param) {
                    $container_param_offset = $i - (2 - count($container_type_part->type_params));

                    if ($container_param_offset === -1) {
                        continue;
                    }

                    $container_param = $container_type_part->type_params[$container_param_offset];

                    if ($i === 0
                        && $input_param->hasMixed()
                        && $container_param->hasString()
                        && $container_param->hasInt()
                    ) {
                        continue;
                    }

                    if (!$input_param->isEmpty()
                        && !self::isContainedBy(
                            $codebase,
                            $input_param,
                            $container_param,
                            $input_param->ignore_nullable_issues,
                            $input_param->ignore_falsable_issues,
                            $array_has_scalar_match,
                            $array_type_coerced,
                            $type_coerced_from_mixed,
                            $array_to_string_cast,
                            $array_type_coerced_from_scalar,
                            $allow_interface_equality
                        )
                        && !$array_type_coerced_from_scalar
                    ) {
                        $all_types_contain = false;
                    }
                }

                if ($all_types_contain) {
                    $to_string_cast = false;

                    return true;
                }

                return false;
            }

            if ($input_type_part->isTraversable($codebase)) {
                return true;
            }
        }

        if ($container_type_part instanceof TScalar && $input_type_part instanceof Scalar) {
            return true;
        }

        if (get_class($container_type_part) === TInt::class && $input_type_part instanceof TLiteralInt) {
            return true;
        }

        if (get_class($container_type_part) === TFloat::class && $input_type_part instanceof TLiteralFloat) {
            return true;
        }

        if ((get_class($container_type_part) === TString::class
                || get_class($container_type_part) === TSingleLetter::class)
            && $input_type_part instanceof TLiteralString
        ) {
            return true;
        }

        if (get_class($input_type_part) === TInt::class && $container_type_part instanceof TLiteralInt) {
            $type_coerced = true;
            $type_coerced_from_scalar = true;

            return false;
        }

        if (get_class($input_type_part) === TFloat::class && $container_type_part instanceof TLiteralFloat) {
            $type_coerced = true;
            $type_coerced_from_scalar = true;

            return false;
        }

        if ((get_class($input_type_part) === TString::class || get_class($container_type_part) === TSingleLetter::class)
            && $container_type_part instanceof TLiteralString
        ) {
            $type_coerced = true;
            $type_coerced_from_scalar = true;

            return false;
        }

        if (($container_type_part instanceof TClassString || $container_type_part instanceof TLiteralClassString)
            && ($input_type_part instanceof TClassString || $input_type_part instanceof TLiteralClassString)
        ) {
            if ($container_type_part instanceof TLiteralClassString
                && $input_type_part instanceof TLiteralClassString
            ) {
                return $container_type_part->value === $input_type_part->value;
            }

            if ($container_type_part instanceof TClassString
                && $container_type_part->as === 'object'
            ) {
                return true;
            }

            if ($input_type_part instanceof TClassString
                && $input_type_part->as === 'object'
            ) {
                $type_coerced = true;
                $type_coerced_from_scalar = true;

                return false;
            }

            $fake_container_object = new TNamedObject(
                $container_type_part instanceof TClassString
                    ? $container_type_part->as
                    : $container_type_part->value
            );

            $fake_input_object = new TNamedObject(
                $input_type_part instanceof TClassString
                    ? $input_type_part->as
                    : $input_type_part->value
            );

            return self::isObjectContainedByObject(
                $codebase,
                $fake_input_object,
                $fake_container_object,
                $allow_interface_equality
            );
        }

        if (($input_type_part instanceof TClassString
            || $input_type_part instanceof TLiteralClassString)
            && (get_class($container_type_part) === TString::class
                || get_class($container_type_part) === TSingleLetter::class
                || get_class($container_type_part) === GetClassT::class)
        ) {
            return true;
        }

        if ($container_type_part instanceof TClassString && $input_type_part instanceof GetClassT) {
            return true;
        }

        if ($container_type_part instanceof TString
            && ($input_type_part instanceof TNumericString
                || $input_type_part instanceof THtmlEscapedString)
        ) {
            return true;
        }

        if ($input_type_part instanceof TString
            && ($container_type_part instanceof TNumericString
                || $container_type_part instanceof THtmlEscapedString)
        ) {
            $type_coerced = true;

            return false;
        }

        if (($container_type_part instanceof TClassString || $container_type_part instanceof TLiteralClassString)
            && $input_type_part instanceof TString
        ) {
            $type_coerced = true;

            return false;
        }

        if ($container_type_part instanceof TString
            && $input_type_part instanceof TNamedObject
        ) {
            // check whether the object has a __toString method
            if ($codebase->classOrInterfaceExists($input_type_part->value)
                && $codebase->methodExists($input_type_part->value . '::__toString')
            ) {
                $to_string_cast = true;

                return true;
            }

            // PHP 5.6 doesn't support this natively, so this introduces a bug *just* when checking PHP 5.6 code
            if ($input_type_part->value === 'ReflectionType') {
                $to_string_cast = true;

                return true;
            }
        }

        if ($container_type_part instanceof Type\Atomic\Fn && $input_type_part instanceof TCallable) {
            $type_coerced = true;

            return false;
        }

        if ($container_type_part instanceof TCallable &&
            (
                $input_type_part instanceof TString ||
                $input_type_part instanceof TArray ||
                $input_type_part instanceof ObjectLike ||
                (
                    $input_type_part instanceof TNamedObject &&
                    $codebase->classExists($input_type_part->value) &&
                    $codebase->methodExists($input_type_part->value . '::__invoke')
                )
            )
        ) {
            // @todo add value checks if possible here
            return true;
        }

        if ($input_type_part instanceof TNumeric) {
            if ($container_type_part->isNumericType()) {
                $has_scalar_match = true;
            }
        }

        if ($input_type_part instanceof Scalar) {
            if ($container_type_part instanceof Scalar
                && !$container_type_part instanceof TLiteralInt
                && !$container_type_part instanceof TLiteralString
                && !$container_type_part instanceof TLiteralFloat
            ) {
                $has_scalar_match = true;
            }
        } elseif ($container_type_part instanceof TObject &&
            !$input_type_part instanceof TArray &&
            !$input_type_part instanceof TResource
        ) {
            return true;
        } elseif ($input_type_part instanceof TObject && $container_type_part instanceof TNamedObject) {
            $type_coerced = true;
        } elseif ($container_type_part instanceof TNamedObject
            && $input_type_part instanceof TNamedObject
            && $codebase->classOrInterfaceExists($input_type_part->value)
            && (
                (
                    $codebase->classExists($container_type_part->value)
                    && $codebase->classExtendsOrImplements(
                        $container_type_part->value,
                        $input_type_part->value
                    )
                )
                ||
                (
                    $codebase->interfaceExists($container_type_part->value)
                    && $codebase->interfaceExtends(
                        $container_type_part->value,
                        $input_type_part->value
                    )
                )
            )
        ) {
            $type_coerced = true;
        }

        return false;
    }

    /**
     * @param  Codebase    $codebase
     * @param  Type\Atomic $input_type_part
     * @param  Type\Atomic $container_type_part
     * @param  ?bool       &$has_scalar_match
     * @param  ?bool       &$type_coerced
     * @param  ?bool       &$type_coerced_from_mixed
     * @param  ?bool       &$to_string_cast
     * @param  bool        $allow_interface_equality
     *
     * @return bool
     */
    private static function isMatchingTypeContainedBy(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        &$has_scalar_match,
        &$type_coerced,
        &$type_coerced_from_mixed,
        &$to_string_cast,
        $allow_interface_equality
    ) {
        $all_types_contain = true;

        if ($container_type_part instanceof TGenericObject) {
            if (!$input_type_part instanceof TGenericObject) {
                $type_coerced = true;
                $type_coerced_from_mixed = true;

                return false;
            }

            foreach ($input_type_part->type_params as $i => $input_param) {
                if (!isset($container_type_part->type_params[$i])) {
                    break;
                }

                $container_param = $container_type_part->type_params[$i];

                if (!$input_param->isEmpty() &&
                    !self::isContainedBy(
                        $codebase,
                        $input_param,
                        $container_param,
                        $input_param->ignore_nullable_issues,
                        $input_param->ignore_falsable_issues,
                        $has_scalar_match,
                        $type_coerced,
                        $type_coerced_from_mixed,
                        $to_string_cast,
                        $type_coerced_from_scalar,
                        $allow_interface_equality
                    )
                ) {
                    $all_types_contain = false;
                }
            }
        }

        if ($container_type_part instanceof Type\Atomic\Fn) {
            if (!$input_type_part instanceof Type\Atomic\Fn) {
                $type_coerced = true;
                $type_coerced_from_mixed = true;

                return false;
            }

            if (self::compareCallable(
                $codebase,
                $input_type_part,
                $container_type_part,
                $type_coerced,
                $type_coerced_from_mixed,
                $has_scalar_match,
                $all_types_contain
            ) === false
            ) {
                return false;
            }
        }

        if (($input_type_part instanceof TArray || $input_type_part instanceof ObjectLike)
            && ($container_type_part instanceof TArray || $container_type_part instanceof ObjectLike)
        ) {
            if ($container_type_part instanceof ObjectLike) {
                $generic_container_type_part = $container_type_part->getGenericArrayType();

                $container_params_can_be_undefined = (bool) array_reduce(
                    $container_type_part->properties,
                    /**
                     * @param bool $carry
                     *
                     * @return bool
                     */
                    function ($carry, Type\Union $item) {
                        return $carry || $item->possibly_undefined;
                    },
                    false
                );

                if (!$input_type_part instanceof ObjectLike
                    && !$input_type_part->type_params[0]->hasMixed()
                    && !($input_type_part->type_params[1]->isEmpty()
                        && $container_params_can_be_undefined)
                ) {
                    $all_types_contain = false;
                    $type_coerced = true;
                }

                $container_type_part = $generic_container_type_part;
            }

            if ($input_type_part instanceof ObjectLike) {
                $input_type_part = $input_type_part->getGenericArrayType();
            }

            foreach ($input_type_part->type_params as $i => $input_param) {
                $container_param = $container_type_part->type_params[$i];

                if ($i === 0
                    && $input_param->hasMixed()
                    && $container_param->hasString()
                    && $container_param->hasInt()
                ) {
                    continue;
                }

                if (!$input_param->isEmpty() &&
                    !self::isContainedBy(
                        $codebase,
                        $input_param,
                        $container_param,
                        $input_param->ignore_nullable_issues,
                        $input_param->ignore_falsable_issues,
                        $has_scalar_match,
                        $type_coerced,
                        $type_coerced_from_mixed,
                        $to_string_cast,
                        $type_coerced_from_scalar,
                        $allow_interface_equality
                    )
                ) {
                    $all_types_contain = false;
                }
            }
        }

        if ($all_types_contain) {
            $to_string_cast = false;

            return true;
        }

        return false;
    }

    /**
     * @param  TCallable|Type\Atomic\Fn   $input_type_part
     * @param  TCallable|Type\Atomic\Fn   $container_type_part
     * @param  bool   &$type_coerced
     * @param  bool   &$type_coerced_from_mixed
     * @param  bool   $has_scalar_match
     * @param  bool   &$all_types_contain
     *
     * @return null|false
     *
     * @psalm-suppress ConflictingReferenceConstraint
     */
    private static function compareCallable(
        Codebase $codebase,
        $input_type_part,
        $container_type_part,
        &$type_coerced = null,
        &$type_coerced_from_mixed = null,
        &$has_scalar_match = null,
        &$all_types_contain = null
    ) {
        if ($container_type_part->params !== null && $input_type_part->params === null) {
            $type_coerced = true;
            $type_coerced_from_mixed = true;

            return false;
        }

        if ($container_type_part->params !== null) {
            foreach ($container_type_part->params as $i => $container_param) {
                if (!isset($input_type_part->params[$i])) {
                    if ($container_param->is_optional) {
                        break;
                    }

                    $type_coerced = true;
                    $type_coerced_from_mixed = true;

                    $all_types_contain = false;
                    break;
                }

                $input_param = $input_type_part->params[$i];

                if (!self::isContainedBy(
                    $codebase,
                    $input_param->type ?: Type::getMixed(),
                    $container_param->type ?: Type::getMixed(),
                    false,
                    false,
                    $has_scalar_match,
                    $type_coerced,
                    $type_coerced_from_mixed
                )
                ) {
                    $all_types_contain = false;
                }
            }

            if (isset($container_type_part->return_type)) {
                if (!isset($input_type_part->return_type)) {
                    $type_coerced = true;
                    $type_coerced_from_mixed = true;

                    $all_types_contain = false;
                } else {
                    if (!$container_type_part->return_type->isVoid()
                        && !self::isContainedBy(
                            $codebase,
                            $input_type_part->return_type,
                            $container_type_part->return_type,
                            false,
                            false,
                            $has_scalar_match,
                            $type_coerced,
                            $type_coerced_from_mixed
                        )
                    ) {
                        $all_types_contain = false;
                    }
                }
            }
        }
    }

    /**
     * Takes two arrays of types and merges them
     *
     * @param  array<string, Type\Union>  $new_types
     * @param  array<string, Type\Union>  $existing_types
     *
     * @return array<string, Type\Union>
     */
    public static function combineKeyedTypes(array $new_types, array $existing_types)
    {
        $keys = array_merge(array_keys($new_types), array_keys($existing_types));
        $keys = array_unique($keys);

        $result_types = [];

        if (empty($new_types)) {
            return $existing_types;
        }

        if (empty($existing_types)) {
            return $new_types;
        }

        foreach ($keys as $key) {
            if (!isset($existing_types[$key])) {
                $result_types[$key] = $new_types[$key];
                continue;
            }

            if (!isset($new_types[$key])) {
                $result_types[$key] = $existing_types[$key];
                continue;
            }

            $existing_var_types = $existing_types[$key];
            $new_var_types = $new_types[$key];

            if ($new_var_types->getId() === $existing_var_types->getId()) {
                $result_types[$key] = $new_var_types;
            } else {
                $result_types[$key] = Type::combineUnionTypes($new_var_types, $existing_var_types);
            }
        }

        return $result_types;
    }

    /**
     * @return Type\Union
     */
    public static function simplifyUnionType(Codebase $codebase, Type\Union $union)
    {
        $union_type_count = count($union->getTypes());

        if ($union_type_count === 1 || ($union_type_count === 2 && $union->isNullable())) {
            return $union;
        }

        $from_docblock = $union->from_docblock;
        $ignore_nullable_issues = $union->ignore_nullable_issues;
        $ignore_falsable_issues = $union->ignore_falsable_issues;
        $possibly_undefined = $union->possibly_undefined;

        $unique_types = [];

        $inverse_contains = [];

        foreach ($union->getTypes() as $type_part) {
            $is_contained_by_other = false;

            // don't try to simplify intersection types
            if (($type_part instanceof TNamedObject
                    || $type_part instanceof TGenericParam
                    || $type_part instanceof TIterable)
                && $type_part->extra_types
            ) {
                return $union;
            }

            foreach ($union->getTypes() as $container_type_part) {
                $string_container_part = $container_type_part->getId();
                $string_input_part = $type_part->getId();

                if ($type_part !== $container_type_part &&
                    !(
                        $container_type_part instanceof TInt
                        || $container_type_part instanceof TFloat
                        || $container_type_part instanceof TCallable
                        || ($container_type_part instanceof TString && $type_part instanceof TCallable)
                        || ($container_type_part instanceof TArray && $type_part instanceof TCallable)
                    ) &&
                    !isset($inverse_contains[$string_input_part][$string_container_part]) &&
                    TypeAnalyzer::isAtomicContainedBy(
                        $codebase,
                        $type_part,
                        $container_type_part,
                        false,
                        false,
                        $has_scalar_match,
                        $type_coerced,
                        $type_coerced_from_mixed,
                        $to_string_cast
                    ) &&
                    !$to_string_cast
                ) {
                    $inverse_contains[$string_container_part][$string_input_part] = true;

                    $is_contained_by_other = true;
                    break;
                }
            }

            if (!$is_contained_by_other) {
                $unique_types[] = $type_part;
            }
        }

        if (count($unique_types) === 0) {
            throw new \UnexpectedValueException('There must be more than one unique type');
        }

        $unique_type = new Type\Union($unique_types);

        $unique_type->from_docblock = $from_docblock;
        $unique_type->ignore_nullable_issues = $ignore_nullable_issues;
        $unique_type->ignore_falsable_issues = $ignore_falsable_issues;
        $unique_type->possibly_undefined = $possibly_undefined;

        return $unique_type;
    }
}
