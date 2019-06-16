<?php
namespace Psalm\Internal\Analyzer;

use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Codebase;
use Psalm\Internal\Codebase\CallMap;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\GetClassT;
use Psalm\Type\Atomic\GetTypeT;
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
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTraitString;
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

            $all_type_coerced = null;
            $all_type_coerced_from_mixed = null;

            $some_type_coerced = false;
            $some_type_coerced_from_mixed = false;

            if ($input_type_part instanceof TArrayKey
                && ($container_type->hasInt() && $container_type->hasString())
            ) {
                continue;
            }

            foreach ($container_type->getTypes() as $container_type_part) {
                if ($ignore_null
                    && $container_type_part instanceof TNull
                    && !$input_type_part instanceof TNull
                ) {
                    continue;
                }

                if ($ignore_false
                    && $container_type_part instanceof TFalse
                    && !$input_type_part instanceof TFalse
                ) {
                    continue;
                }

                $atomic_to_string_cast = false;
                $atomic_type_coerced = false;
                $atomic_type_coerced_from_mixed = false;

                $is_atomic_contained_by = self::isAtomicContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    true,
                    $scalar_type_match_found,
                    $atomic_type_coerced,
                    $atomic_type_coerced_from_mixed,
                    $atomic_to_string_cast,
                    $type_coerced_from_scalar
                );

                if ($input_type_part instanceof TNumeric
                    && $container_type->hasString()
                    && $container_type->hasInt()
                    && $container_type->hasFloat()
                ) {
                    $scalar_type_match_found = false;
                    $is_atomic_contained_by = true;
                }

                if ($atomic_type_coerced) {
                    $some_type_coerced = true;
                }

                if ($atomic_type_coerced_from_mixed) {
                    $some_type_coerced_from_mixed = true;
                }

                if ($atomic_type_coerced !== true || $all_type_coerced === false) {
                    $all_type_coerced = false;
                } else {
                    $all_type_coerced = true;
                }

                if ($atomic_type_coerced_from_mixed !== true || $all_type_coerced_from_mixed === false) {
                    $all_type_coerced_from_mixed = false;
                } else {
                    $all_type_coerced_from_mixed = true;
                }

                if ($is_atomic_contained_by) {
                    $type_match_found = true;

                    if ($atomic_to_string_cast !== true) {
                        $all_to_string_cast = false;
                    }

                    $all_type_coerced_from_mixed = false;
                    $all_type_coerced = false;
                }
            }

            // only set this flag if we're definite that the only
            // reason the type match has been found is because there
            // was a __toString cast
            if ($all_to_string_cast && $type_match_found) {
                $to_string_cast = true;
            }

            if ($all_type_coerced) {
                $type_coerced = true;
            }

            if ($all_type_coerced_from_mixed) {
                $type_coerced_from_mixed = true;
            }

            if (!$type_match_found) {
                if ($some_type_coerced) {
                    $type_coerced = true;
                }

                if ($some_type_coerced_from_mixed) {
                    $type_coerced_from_mixed = true;
                }

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
     * Used for comparing docblock types to signature types before we know about all types
     *
     * @param  Type\Union   $input_type
     * @param  Type\Union   $container_type
     */
    public static function isSimplyContainedBy(
        Type\Union $input_type,
        Type\Union $container_type
    ) : bool {
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

        foreach ($input_type->getTypes() as $input_key => $input_type_part) {
            foreach ($container_type->getTypes() as $container_key => $container_type_part) {
                if (get_class($container_type_part) === TNamedObject::class
                    && $input_type_part instanceof TNamedObject
                    && $input_type_part->value === $container_type_part->value
                ) {
                    continue 2;
                }

                if ($input_key === $container_key) {
                    continue 2;
                }
            }

            return false;
        }



        return true;
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
     * @param  TNamedObject|TTemplateParam|TIterable  $input_type_part
     * @param  TNamedObject|TTemplateParam|TIterable  $container_type_part
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

        if ($input_type_part instanceof TTemplateParam) {
            foreach ($input_type_part->as->getTypes() as $g) {
                if ($g instanceof TNamedObject && $g->extra_types) {
                    $intersection_input_types = array_merge(
                        $intersection_input_types,
                        $g->extra_types
                    );
                }
            }
        }

        $intersection_container_types = $container_type_part->extra_types ?: [];
        $intersection_container_types[] = $container_type_part;

        if ($container_type_part instanceof TTemplateParam) {
            foreach ($container_type_part->as->getTypes() as $g) {
                if ($g instanceof TNamedObject && $g->extra_types) {
                    $intersection_container_types = array_merge(
                        $intersection_container_types,
                        $g->extra_types
                    );
                }
            }
        }

        foreach ($intersection_container_types as $intersection_container_type) {
            if ($intersection_container_type instanceof TIterable) {
                $intersection_container_type_lower = 'iterable';
            } elseif ($intersection_container_type instanceof TTemplateParam) {
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
                } elseif ($intersection_input_type instanceof TTemplateParam) {
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
                    && $codebase->classOrInterfaceExists($intersection_container_type_lower)
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
            || ($container_type_part instanceof TTemplateParam
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
            || ($input_type_part instanceof TTemplateParam
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

        if ($container_type_part instanceof ObjectLike
            && $input_type_part instanceof TArray
        ) {
            $all_string_literals = true;

            $properties = [];

            foreach ($input_type_part->type_params[0]->getTypes() as $atomic_key_type) {
                if ($atomic_key_type instanceof TLiteralString) {
                    $properties[$atomic_key_type->value] = $input_type_part->type_params[1];
                } else {
                    $all_string_literals = false;
                    break;
                }
            }

            if ($all_string_literals) {
                $input_type_part = new ObjectLike($properties);
            }
        }

        if ($input_type_part->shallowEquals($container_type_part)
            || (($input_type_part instanceof TNamedObject
                    || $input_type_part instanceof TTemplateParam
                    || $input_type_part instanceof TIterable)
                && ($container_type_part instanceof TNamedObject
                    || ($container_type_part instanceof TTemplateParam
                        && $container_type_part->as->hasObjectType())
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

        if ($container_type_part instanceof TTemplateParam && $input_type_part instanceof TTemplateParam) {
            return self::isContainedBy(
                $codebase,
                $input_type_part->as,
                $container_type_part->as,
                false,
                false,
                $has_scalar_match,
                $type_coerced,
                $type_coerced_from_mixed,
                $to_string_cast,
                $type_coerced_from_scalar,
                $allow_interface_equality
            );
        }

        if ($container_type_part instanceof TTemplateParam) {
            foreach ($container_type_part->as->getTypes() as $container_as_type_part) {
                if (self::isAtomicContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_as_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $has_scalar_match,
                    $type_coerced,
                    $type_coerced_from_mixed,
                    $to_string_cast,
                    $type_coerced_from_scalar
                )) {
                    return true;
                }
            }

            return false;
        }

        if ($input_type_part instanceof TTemplateParam) {
            foreach ($input_type_part->as->getTypes() as $input_as_type_part) {
                if ($input_as_type_part instanceof TNull && $container_type_part instanceof TNull) {
                    continue;
                }

                if (self::isAtomicContainedBy(
                    $codebase,
                    $input_as_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $has_scalar_match,
                    $type_coerced,
                    $type_coerced_from_mixed,
                    $to_string_cast,
                    $type_coerced_from_scalar
                )) {
                    return true;
                }
            }

            return false;
        }

        if ($container_type_part instanceof GetClassT) {
            $first_type = array_values($container_type_part->as_type->getTypes())[0];

            $container_type_part = new TClassString(
                'object',
                $first_type instanceof TNamedObject ? $first_type : null
            );
        }

        if ($input_type_part instanceof GetClassT) {
            $first_type = array_values($input_type_part->as_type->getTypes())[0];

            $input_type_part = new TClassString(
                'object',
                $first_type instanceof TNamedObject ? $first_type : null
            );
        }

        if ($input_type_part instanceof GetTypeT) {
            $input_type_part = new TString();

            if ($container_type_part instanceof TLiteralString) {
                return isset(ClassLikeAnalyzer::GETTYPE_TYPES[$container_type_part->value]);
            }
        }

        if ($container_type_part instanceof GetTypeT) {
            $container_type_part = new TString();

            if ($input_type_part instanceof TLiteralString) {
                return isset(ClassLikeAnalyzer::GETTYPE_TYPES[$input_type_part->value]);
            }
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
            && !$container_type_part instanceof TLiteralFloat
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

        if ($container_type_part instanceof TCallable && $input_type_part instanceof Type\Atomic\TFn) {
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

        if ($container_type_part instanceof TArrayKey
            && ($input_type_part instanceof TInt
                || $input_type_part instanceof TString
                || $input_type_part instanceof Type\Atomic\TTemplateKeyOf)
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

        if (($container_type_part instanceof ObjectLike
                && $input_type_part instanceof ObjectLike)
            || ($container_type_part instanceof TObjectWithProperties
                && $input_type_part instanceof TObjectWithProperties)
        ) {
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

            if ($input_type_part->hasTraversableInterface($codebase)) {
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

        if ((get_class($input_type_part) === TString::class || get_class($input_type_part) === TSingleLetter::class)
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
                && !$container_type_part->as_type
            ) {
                return true;
            }

            if ($input_type_part instanceof TClassString
                && $input_type_part->as === 'object'
                && !$input_type_part->as_type
            ) {
                $type_coerced = true;
                $type_coerced_from_scalar = true;

                return false;
            }

            $fake_container_object = $container_type_part instanceof TClassString
                && $container_type_part->as_type
                ? $container_type_part->as_type
                : new TNamedObject(
                    $container_type_part instanceof TClassString
                        ? $container_type_part->as
                        : $container_type_part->value
                );

            $fake_input_object = $input_type_part instanceof TClassString
                && $input_type_part->as_type
                ? $input_type_part->as_type
                : new TNamedObject(
                    $input_type_part instanceof TClassString
                        ? $input_type_part->as
                        : $input_type_part->value
                );

            return self::isAtomicContainedBy(
                $codebase,
                $fake_input_object,
                $fake_container_object,
                $allow_interface_equality,
                $allow_float_int_equality,
                $has_scalar_match,
                $type_coerced,
                $type_coerced_from_mixed
            );
        }

        if ($container_type_part instanceof TString && $input_type_part instanceof TTraitString) {
            return true;
        }

        if ($container_type_part instanceof TTraitString && get_class($input_type_part) === TString::class) {
            $type_coerced = true;

            return false;
        }

        if (($input_type_part instanceof TClassString
            || $input_type_part instanceof TLiteralClassString)
            && (get_class($container_type_part) === TString::class
                || get_class($container_type_part) === TSingleLetter::class)
        ) {
            return true;
        }

        if ($input_type_part instanceof TCallableString
            && (get_class($container_type_part) === TString::class
                || get_class($container_type_part) === TSingleLetter::class)
        ) {
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

        if ($container_type_part instanceof TCallableString
            && $input_type_part instanceof TLiteralString
        ) {
            $input_callable = self::getCallableFromAtomic($codebase, $input_type_part);
            $container_callable = self::getCallableFromAtomic($codebase, $container_type_part);

            if ($input_callable && $container_callable) {
                $all_types_contain = true;

                if (self::compareCallable(
                    $codebase,
                    $input_callable,
                    $container_callable,
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

            return true;
        }

        if (($container_type_part instanceof TClassString
                || $container_type_part instanceof TLiteralClassString
                || $container_type_part instanceof TCallableString)
            && $input_type_part instanceof TString
        ) {
            $type_coerced = true;

            return false;
        }

        if (($container_type_part instanceof TString || $container_type_part instanceof TScalar)
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

        if ($container_type_part instanceof Type\Atomic\TFn && $input_type_part instanceof TCallable) {
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
            if ($input_type_part instanceof TArray) {
                if ($input_type_part->type_params[1]->isMixed()
                    || $input_type_part->type_params[1]->hasScalar()
                ) {
                    $type_coerced_from_mixed = true;
                    $type_coerced = true;

                    return false;
                }

                if (!$input_type_part->type_params[1]->hasString()) {
                    return false;
                }

                if (!$input_type_part instanceof Type\Atomic\TCallableArray) {
                    $type_coerced_from_mixed = true;
                    $type_coerced = true;

                    return false;
                }
            } elseif ($input_type_part instanceof ObjectLike) {
                $method_id = self::getCallableMethodIdFromObjectLike($input_type_part);

                if ($method_id === 'not-callable') {
                    return false;
                }

                if (!$method_id) {
                    return true;
                }

                try {
                    $method_id = $codebase->methods->getDeclaringMethodId($method_id);

                    if (!$method_id) {
                        return false;
                    }

                    $codebase->methods->getStorage($method_id);
                } catch (\Exception $e) {
                    return false;
                }
            }

            $input_callable = self::getCallableFromAtomic($codebase, $input_type_part, $container_type_part);

            if ($input_callable) {
                $all_types_contain = true;

                if (self::compareCallable(
                    $codebase,
                    $input_callable,
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
        } elseif ($container_type_part instanceof TObject
            && $input_type_part instanceof TNamedObject
        ) {
            if ($container_type_part instanceof TObjectWithProperties
                && $input_type_part->value !== 'stdClass'
            ) {
                $all_types_contain = true;

                foreach ($container_type_part->properties as $property_name => $container_property_type) {
                    if (!is_string($property_name)) {
                        continue;
                    }

                    if (!$codebase->properties->propertyExists(
                        $input_type_part . '::$' . $property_name,
                        true
                    )) {
                        $all_types_contain = false;

                        continue;
                    }

                    $property_declaring_class = (string) $codebase->properties->getDeclaringClassForProperty(
                        $input_type_part . '::$' . $property_name,
                        true
                    );

                    $class_storage = $codebase->classlike_storage_provider->get($property_declaring_class);

                    $input_property_storage = $class_storage->properties[$property_name];

                    $input_property_type = $input_property_storage->type ?: Type::getMixed();

                    if (!$input_property_type->isEmpty()
                        && !self::isContainedBy(
                            $codebase,
                            $input_property_type,
                            $container_property_type,
                            false,
                            false,
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

                if ($all_types_contain === true) {
                    return true;
                }

                return false;
            }

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
     * @return ?TCallable
     */
    public static function getCallableFromAtomic(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        ?TCallable $container_type_part = null
    ) : ?TCallable {
        if ($input_type_part instanceof TLiteralString) {
            try {
                $function_storage = $codebase->functions->getStorage(null, $input_type_part->value);

                return new TCallable(
                    'callable',
                    $function_storage->params,
                    $function_storage->return_type
                );
            } catch (\Exception $e) {
                if (CallMap::inCallMap($input_type_part->value)) {
                    $args = [];

                    if ($container_type_part && $container_type_part->params) {
                        foreach ($container_type_part->params as $i => $param) {
                            $arg = new \PhpParser\Node\Arg(
                                new \PhpParser\Node\Expr\Variable('_' . $i)
                            );

                            $arg->value->inferredType = $param->type;

                            $args[] = $arg;
                        }
                    }

                    return \Psalm\Internal\Codebase\CallMap::getCallableFromCallMapById(
                        $codebase,
                        $input_type_part->value,
                        $args
                    );
                }
            }
        } elseif ($input_type_part instanceof ObjectLike) {
            if ($method_id = self::getCallableMethodIdFromObjectLike($input_type_part)) {
                try {
                    $method_storage = $codebase->methods->getStorage($method_id);

                    return new TCallable(
                        'callable',
                        $method_storage->params,
                        $method_storage->return_type
                    );
                } catch (\Exception $e) {
                    // do nothing
                }
            }
        }

        return null;
    }

    /** @return ?string */
    public static function getCallableMethodIdFromObjectLike(
        ObjectLike $input_type_part,
        Codebase $codebase = null,
        string $calling_method_id = null,
        string $file_name = null
    ) {
        if (!isset($input_type_part->properties[0])
            || !isset($input_type_part->properties[1])
        ) {
            return 'not-callable';
        }

        $lhs = $input_type_part->properties[0];
        $rhs = $input_type_part->properties[1];

        $rhs_low_info = $rhs->hasMixed() || $rhs->hasScalar();

        if ($rhs_low_info || !$rhs->isSingleStringLiteral()) {
            if (!$rhs_low_info && !$rhs->hasString()) {
                return 'not-callable';
            }

            if ($codebase && ($calling_method_id || $file_name)) {
                foreach ($lhs->getTypes() as $lhs_atomic_type) {
                    if ($lhs_atomic_type instanceof TNamedObject) {
                        $codebase->analyzer->addMixedMemberName(
                            strtolower($lhs_atomic_type->value) . '::',
                            $calling_method_id ?: $file_name
                        );
                    }
                }
            }

            return null;
        }

        $lhs = $input_type_part->properties[0];
        $method_name = $rhs->getSingleStringLiteral()->value;

        $class_name = null;

        if ($lhs->isSingleStringLiteral()) {
            $class_name = $lhs->getSingleStringLiteral()->value;
        } elseif ($lhs->isSingle()) {
            foreach ($lhs->getTypes() as $lhs_atomic_type) {
                if ($lhs_atomic_type instanceof TNamedObject) {
                    $class_name = $lhs_atomic_type->value;
                }
            }
        }

        if (!$class_name) {
            if ($codebase && ($calling_method_id || $file_name)) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($method_name),
                    $calling_method_id ?: $file_name
                );
            }

            return null;
        }

        return $class_name . '::' . $method_name;
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

        if ($container_type_part instanceof TGenericObject || $container_type_part instanceof TIterable) {
            if (!$input_type_part instanceof TGenericObject && !$input_type_part instanceof TIterable) {
                if ($input_type_part instanceof TNamedObject
                    && $codebase->classExists($input_type_part->value)
                ) {
                    $class_storage = $codebase->classlike_storage_provider->get($input_type_part->value);

                    $container_class_lc = strtolower($container_type_part->value);

                    // attempt to transform it
                    if (isset($class_storage->template_type_extends[$container_class_lc])) {
                        $extends_list = $class_storage->template_type_extends[$container_class_lc];

                        $generic_params = [];

                        foreach ($extends_list as $key => $value) {
                            if (is_string($key)) {
                                $generic_params[] = $value;
                            }
                        }

                        $input_type_part = new TGenericObject(
                            $input_type_part->value,
                            $generic_params
                        );
                    }
                }

                if (!$input_type_part instanceof TGenericObject) {
                    if ($input_type_part instanceof TNamedObject) {
                        $input_type_part = new TGenericObject(
                            $input_type_part->value,
                            array_fill(0, count($container_type_part->type_params), Type::getMixed())
                        );
                    } else {
                        $type_coerced = true;
                        $type_coerced_from_mixed = true;
                        return false;
                    }
                }
            }

            $input_type_params = $input_type_part->type_params;

            if ($input_type_part->value !== $container_type_part->value) {
                try {
                    $input_class_storage = $codebase->classlike_storage_provider->get($input_type_part->value);
                    $template_extends = $input_class_storage->template_type_extends;

                    if (isset($template_extends[strtolower($container_type_part->value)])) {
                        $params = $template_extends[strtolower($container_type_part->value)];

                        $new_input_params = [];

                        foreach ($params as $key => $extended_input_param_type) {
                            if (is_string($key)) {
                                $new_input_param = null;

                                foreach ($extended_input_param_type->getTypes() as $et) {
                                    if ($et instanceof TTemplateParam
                                        && $et->param_name
                                        && isset($input_class_storage->template_types[$et->param_name])
                                    ) {
                                        $old_params_offset = (int) array_search(
                                            $et->param_name,
                                            array_keys($input_class_storage->template_types)
                                        );

                                        if (!isset($input_type_params[$old_params_offset])) {
                                            return false;
                                        }

                                        $candidate_param_type = $input_type_params[$old_params_offset];
                                    } else {
                                        $candidate_param_type = new Type\Union([$et]);
                                    }

                                    if (!$new_input_param) {
                                        $new_input_param = $candidate_param_type;
                                    } else {
                                        $new_input_param = Type::combineUnionTypes(
                                            $new_input_param,
                                            $candidate_param_type
                                        );
                                    }
                                }

                                $new_input_params[] = $new_input_param ?: Type::getMixed();
                            }
                        }

                        $input_type_params = $new_input_params;
                    }
                } catch (\Throwable $t) {
                    // do nothing
                }
            }

            foreach ($input_type_params as $i => $input_param) {
                if (!isset($container_type_part->type_params[$i])) {
                    break;
                }

                $container_param = $container_type_part->type_params[$i];

                if ($input_type_part->value === 'Generator' && $i === 2) {
                    continue;
                }

                if ($input_param->isEmpty()) {
                    continue;
                }

                if (!self::isContainedBy(
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
                )) {
                    $all_types_contain = false;
                } elseif (!$input_type_part instanceof TIterable
                    && !$container_param->had_template
                    && !$input_param->had_template
                    && !$container_param->hasTemplate()
                    && !$input_param->hasTemplate()
                    && !$input_param->hasLiteralValue()
                ) {
                    $input_storage = $codebase->classlike_storage_provider->get($input_type_part->value);

                    if (!($input_storage->template_covariants[$i] ?? false)) {
                        // Make sure types are basically the same
                        if (!self::isContainedBy(
                            $codebase,
                            $container_param,
                            $input_param,
                            $container_param->ignore_nullable_issues,
                            $container_param->ignore_falsable_issues,
                            $has_scalar_match,
                            $type_coerced,
                            $type_coerced_from_mixed,
                            $to_string_cast,
                            $type_coerced_from_scalar,
                            $allow_interface_equality
                        ) || $type_coerced
                        ) {
                            if ($container_param->hasMixed() || $container_param->isArrayKey()) {
                                $type_coerced_from_mixed = true;
                            } else {
                                $all_types_contain = false;
                            }

                            $type_coerced = false;
                        }
                    }
                }
            }
        }

        if ($container_type_part instanceof Type\Atomic\TFn) {
            if (!$input_type_part instanceof Type\Atomic\TFn) {
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

            $any_scalar_param_match = false;

            foreach ($input_type_part->type_params as $i => $input_param) {
                if ($i > 1) {
                    break;
                }

                $container_param = $container_type_part->type_params[$i];

                if ($i === 0
                    && $input_param->hasMixed()
                    && $container_param->hasString()
                    && $container_param->hasInt()
                ) {
                    continue;
                }

                if ($input_param->isEmpty()
                    && $container_type_part instanceof Type\Atomic\TNonEmptyArray
                ) {
                    return false;
                }

                $scalar_param_match = false;

                if (!$input_param->isEmpty() &&
                    !self::isContainedBy(
                        $codebase,
                        $input_param,
                        $container_param,
                        $input_param->ignore_nullable_issues,
                        $input_param->ignore_falsable_issues,
                        $scalar_param_match,
                        $type_coerced,
                        $type_coerced_from_mixed,
                        $to_string_cast,
                        $type_coerced_from_scalar,
                        $allow_interface_equality
                    )
                ) {
                    $all_types_contain = false;

                    if ($scalar_param_match) {
                        $any_scalar_param_match = true;
                    }
                }
            }

            if ($any_scalar_param_match) {
                $has_scalar_match = true;
            }
        }

        if ($container_type_part instanceof Type\Atomic\TNonEmptyArray
            && !$input_type_part instanceof Type\Atomic\TNonEmptyArray
            && !($input_type_part instanceof ObjectLike && $input_type_part->sealed)
        ) {
            if ($all_types_contain) {
                $type_coerced = true;
            }

            return false;
        }

        if ($all_types_contain) {
            $to_string_cast = false;

            return true;
        }

        return false;
    }

    /**
     * @param  TCallable|Type\Atomic\TFn   $input_type_part
     * @param  TCallable|Type\Atomic\TFn   $container_type_part
     * @param  bool   &$type_coerced
     * @param  bool   &$type_coerced_from_mixed
     * @param  bool   $has_scalar_match
     * @param  bool   &$all_types_contain
     *
     * @return null|false
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

        if ($input_type_part->params !== null && $container_type_part->params !== null) {
            foreach ($input_type_part->params as $i => $input_param) {
                $container_param = null;

                if (isset($container_type_part->params[$i])) {
                    $container_param = $container_type_part->params[$i];
                } elseif ($container_type_part->params) {
                    $last_param = end($container_type_part->params);

                    if ($last_param->is_variadic) {
                        $container_param = $last_param;
                    }
                }

                if (!$container_param) {
                    if ($input_param->is_optional) {
                        break;
                    }

                    return false;
                }

                if ($container_param->type
                    && !$container_param->type->hasMixed()
                    && !self::isContainedBy(
                        $codebase,
                        $container_param->type,
                        $input_param->type ?: Type::getMixed(),
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
                    || $type_part instanceof TTemplateParam
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
