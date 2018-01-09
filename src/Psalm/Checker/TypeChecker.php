<?php
namespace Psalm\Checker;

use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;

class TypeChecker
{
    /**
     * Does the input param type match the given param type
     *
     * @param  Type\Union   $input_type
     * @param  Type\Union   $container_type
     * @param  ProjectChecker  $project_checker
     * @param  bool         $ignore_null
     * @param  bool         $ignore_false
     * @param  bool         &$has_scalar_match
     * @param  bool         &$type_coerced    whether or not there was type coercion involved
     * @param  bool         &$type_coerced_from_mixed
     * @param  bool         &$to_string_cast
     *
     * @return bool
     */
    public static function isContainedBy(
        ProjectChecker $project_checker,
        Type\Union $input_type,
        Type\Union $container_type,
        $ignore_null = false,
        $ignore_false = false,
        &$has_scalar_match = null,
        &$type_coerced = null,
        &$type_coerced_from_mixed = null,
        &$to_string_cast = null
    ) {
        $has_scalar_match = true;

        if ($container_type->isMixed()) {
            return true;
        }

        $type_match_found = false;

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
            $atomic_to_string_cast = false;

            foreach ($container_type->getTypes() as $container_type_part) {
                $is_atomic_contained_by = self::isAtomicContainedBy(
                    $project_checker,
                    $input_type_part,
                    $container_type_part,
                    $scalar_type_match_found,
                    $type_coerced,
                    $type_coerced_from_mixed,
                    $atomic_to_string_cast
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
     * Can any part of the $type1 be equal to any part of $type2
     *
     * @param  Type\Union   $type1
     * @param  Type\Union   $type2
     * @param  ProjectChecker  $project_checker
     *
     * @return bool
     */
    public static function canBeIdenticalTo(
        ProjectChecker $project_checker,
        Type\Union $type1,
        Type\Union $type2
    ) {
        if ($type1->isMixed() || $type2->isMixed()) {
            return true;
        }

        if ($type1->isNullable() && $type2->isNullable()) {
            return true;
        }

        foreach ($type1->getTypes() as $type1_part) {
            if ($type1_part instanceof TNull) {
                continue;
            }

            foreach ($type2->getTypes() as $type2_part) {
                if ($type2_part instanceof TNull) {
                    continue;
                }

                $either_contains = self::isAtomicContainedBy(
                    $project_checker,
                    $type1_part,
                    $type2_part
                ) || self::isAtomicContainedBy(
                    $project_checker,
                    $type2_part,
                    $type1_part
                );

                if ($either_contains) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  ProjectChecker $project_checker
     * @param  TNamedObject   $input_type_part
     * @param  TNamedObject   $container_type_part
     *
     * @return bool
     */
    private static function isObjectContainedByObject(
        ProjectChecker $project_checker,
        TNamedObject $input_type_part,
        TNamedObject $container_type_part
    ) {
        $intersection_input_types = $input_type_part->extra_types ?: [];
        $intersection_input_types[] = $input_type_part;
        $container_type_lower = strtolower($container_type_part->value);

        foreach ($intersection_input_types as $intersection_input_type) {
            if ($intersection_input_type->value === $container_type_part->value) {
                return true;
            }

            $intersection_input_type_lower = strtolower($intersection_input_type->value);

            if ($intersection_input_type_lower === 'generator'
                && in_array($container_type_lower, ['iterator', 'traversable', 'iterable'], true)
            ) {
                return true;
            }

            if (ClassChecker::classExists($project_checker, $intersection_input_type->value)
                && ClassChecker::classExtendsOrImplements(
                    $project_checker,
                    $intersection_input_type->value,
                    $container_type_part->value
                )
            ) {
                return true;
            }

            if (InterfaceChecker::interfaceExists($project_checker, $intersection_input_type->value)
                && InterfaceChecker::interfaceExtends(
                    $project_checker,
                    $intersection_input_type->value,
                    $container_type_part->value
                )
            ) {
                return true;
            }

            if (ExpressionChecker::isMock($intersection_input_type->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the input param atomic type match the given param atomic type
     *
     * @param  Type\Atomic  $input_type_part
     * @param  Type\Atomic  $container_type_part
     * @param  ProjectChecker  $project_checker
     * @param  bool         &$has_scalar_match
     * @param  bool         &$type_coerced    whether or not there was type coercion involved
     * @param  bool         &$type_coerced_from_mixed
     * @param  bool         &$to_string_cast
     *
     * @return bool
     */
    public static function isAtomicContainedBy(
        ProjectChecker $project_checker,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        &$has_scalar_match = null,
        &$type_coerced = null,
        &$type_coerced_from_mixed = null,
        &$to_string_cast = null
    ) {
        if ($container_type_part instanceof TMixed) {
            return true;
        }

        if ($input_type_part instanceof TMixed) {
            $type_coerced = true;
            $type_coerced_from_mixed = true;

            return true;
        }

        $input_is_object = $input_type_part->isObjectType();
        $container_is_object = $container_type_part->isObjectType();

        if ($input_type_part->shallowEquals($container_type_part) ||
            (
                $input_is_object &&
                $container_is_object &&
                $input_type_part instanceof TNamedObject &&
                $container_type_part instanceof TNamedObject &&
                self::isObjectContainedByObject($project_checker, $input_type_part, $container_type_part)
            )
        ) {
            $all_types_contain = true;

            if (($input_type_part instanceof TArray || $input_type_part instanceof ObjectLike)
                && ($container_type_part instanceof TArray || $container_type_part instanceof ObjectLike)
            ) {
                if ($container_type_part instanceof ObjectLike) {
                    if (!$input_type_part instanceof ObjectLike
                        && !$input_type_part->type_params[0]->isMixed()
                    ) {
                        $all_types_contain = false;
                        $type_coerced = true;
                    }

                    $container_type_part = $container_type_part->getGenericArrayType();
                }

                if ($input_type_part instanceof ObjectLike) {
                    $input_type_part = $input_type_part->getGenericArrayType();
                }

                foreach ($input_type_part->type_params as $i => $input_param) {
                    $container_param = $container_type_part->type_params[$i];

                    if (!$input_param->isEmpty() &&
                        !self::isContainedBy(
                            $project_checker,
                            $input_param,
                            $container_param,
                            $input_param->ignore_nullable_issues,
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

            if ($all_types_contain) {
                $to_string_cast = false;

                return true;
            }

            return false;
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
        if ($input_type_part instanceof TInt && $container_type_part instanceof TFloat) {
            return true;
        }

        if ($input_type_part instanceof TNamedObject
            && $input_type_part->value === 'static'
            && $container_type_part instanceof TNamedObject
            && strtolower($container_type_part->value) === 'self'
        ) {
            return true;
        }

        if ($input_type_part instanceof TNamedObject &&
            $input_type_part->value === 'Closure' &&
            $container_type_part instanceof TCallable
        ) {
            return true;
        }

        if ($container_type_part instanceof TNumeric &&
            ($input_type_part->isNumericType() || $input_type_part instanceof TString)
        ) {
            return true;
        }

        if ($container_type_part instanceof ObjectLike && $input_type_part instanceof ObjectLike) {
            $all_types_contain = true;

            foreach ($input_type_part->properties as $key => $input_property_type) {
                if (!isset($container_type_part->properties[$key])) {
                    return false;
                }

                $container_property_type = $container_type_part->properties[$key];

                if (!$input_property_type->isEmpty() &&
                    !self::isContainedBy(
                        $project_checker,
                        $input_property_type,
                        $container_property_type,
                        $input_property_type->ignore_nullable_issues,
                        false,
                        $has_scalar_match,
                        $type_coerced,
                        $type_coerced_from_mixed
                    )
                ) {
                    if (self::isContainedBy($project_checker, $container_property_type, $input_property_type)) {
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

        if ($container_type_part instanceof TNamedObject &&
            strtolower($container_type_part->value) === 'iterable' &&
            (
                $input_type_part instanceof TArray ||
                $input_type_part instanceof ObjectLike ||
                (
                    $input_type_part instanceof TNamedObject &&
                    (
                        strtolower($input_type_part->value) === 'traversable' ||
                        ClassChecker::classExtendsOrImplements(
                            $project_checker,
                            $input_type_part->value,
                            'Traversable'
                        )
                    )
                )
            )
        ) {
            return true;
        }

        if ($container_type_part instanceof TScalar && $input_type_part instanceof Scalar) {
            return true;
        }

        if ($container_type_part instanceof TString &&
            $input_type_part instanceof TNamedObject
        ) {
            // check whether the object has a __toString method
            if (ClassChecker::classOrInterfaceExists($project_checker, $input_type_part->value) &&
                MethodChecker::methodExists($project_checker, $input_type_part->value . '::__toString')
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

        if ($container_type_part instanceof TCallable &&
            (
                $input_type_part instanceof TString ||
                $input_type_part instanceof TArray ||
                $input_type_part instanceof ObjectLike ||
                (
                    $input_type_part instanceof TNamedObject &&
                    ClassChecker::classExists($project_checker, $input_type_part->value) &&
                    MethodChecker::methodExists($project_checker, $input_type_part->value . '::__invoke')
                )
            )
        ) {
            // @todo add value checks if possible here
            return true;
        }

        if ($input_type_part instanceof TCallable &&
            (
                $container_type_part instanceof TString ||
                $container_type_part instanceof TArray ||
                $container_type_part instanceof ObjectLike ||
                (
                    $container_type_part instanceof TNamedObject &&
                    ClassChecker::classExists($project_checker, $container_type_part->value) &&
                    MethodChecker::methodExists($project_checker, $container_type_part->value . '::__invoke')
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
            if ($container_type_part instanceof Scalar) {
                $has_scalar_match = true;
            }
        } elseif ($container_type_part instanceof TObject &&
            !$input_type_part instanceof TArray &&
            !$input_type_part instanceof TResource
        ) {
            return true;
        } elseif ($input_type_part instanceof TObject && $container_type_part instanceof TNamedObject) {
            $type_coerced = true;
        } elseif ($container_type_part instanceof TNamedObject &&
            $input_type_part instanceof TNamedObject &&
            ClassChecker::classOrInterfaceExists($project_checker, $input_type_part->value) &&
            (
                (
                ClassChecker::classExists($project_checker, $container_type_part->value) &&
                    ClassChecker::classExtendsOrImplements(
                        $project_checker,
                        $container_type_part->value,
                        $input_type_part->value
                    )
                ) ||
                (
                    InterfaceChecker::interfaceExists($project_checker, $container_type_part->value) &&
                    InterfaceChecker::interfaceExtends(
                        $project_checker,
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
     * @param  array<string, string>  $types
     *
     * @return array<string, string>
     */
    public static function negateTypes(array $types)
    {
        return array_map(
            /**
             * @param  string $type
             *
             * @return  string
             */
            function ($type) {
                return self::negateType($type);
            },
            $types
        );
    }

    /**
     * @param  string $type
     *
     * @return  string
     */
    public static function negateType($type)
    {
        if ($type === 'mixed') {
            return $type;
        }

        $type_parts = explode('&', (string)$type);

        foreach ($type_parts as &$type_part) {
            $type_part = $type_part[0] === '!' ? substr($type_part, 1) : '!' . $type_part;
        }

        return implode('&', $type_parts);
    }

    /**
     * @param  Type\Union   $declared_type
     * @param  Type\Union   $inferred_type
     * @param  ProjectChecker  $project_checker
     *
     * @return bool
     */
    public static function hasIdenticalTypes(
        ProjectChecker $project_checker,
        Type\Union $declared_type,
        Type\Union $inferred_type
    ) {
        if ($declared_type->isMixed() || $inferred_type->isEmpty()) {
            return true;
        }

        if ($declared_type->isNullable() !== $inferred_type->isNullable()) {
            return false;
        }

        $simple_declared_types = array_filter(
            array_keys($declared_type->getTypes()),
            /**
             * @param  string $type_value
             *
             * @return  bool
             */
            function ($type_value) {
                return $type_value !== 'null';
            }
        );

        $simple_inferred_types = array_filter(
            array_keys($inferred_type->getTypes()),
            /**
             * @param  string $type_value
             *
             * @return  bool
             */
            function ($type_value) {
                return $type_value !== 'null';
            }
        );

        // gets elements A△B
        $differing_types = array_diff($simple_inferred_types, $simple_declared_types);

        if (count($differing_types)) {
            // check whether the differing types are subclasses of declared return types
            foreach ($differing_types as $differing_type) {
                $is_match = false;

                if ($differing_type === 'mixed') {
                    continue;
                }

                foreach ($simple_declared_types as $simple_declared_type) {
                    if ($simple_declared_type === 'mixed') {
                        $is_match = true;
                        break;
                    }

                    if (strtolower($simple_declared_type) === 'callable' && strtolower($differing_type) === 'closure') {
                        $is_match = true;
                        break;
                    }

                    if (isset(ClassLikeChecker::$SPECIAL_TYPES[strtolower($simple_declared_type)]) ||
                        isset(ClassLikeChecker::$SPECIAL_TYPES[strtolower($differing_type)])
                    ) {
                        if (in_array($differing_type, ['float', 'int'], true) &&
                            in_array($simple_declared_type, ['float', 'int'], true)
                        ) {
                            $is_match = true;
                            break;
                        }

                        continue;
                    }

                    if (!ClassLikeChecker::classOrInterfaceExists($project_checker, $differing_type)) {
                        break;
                    }

                    if ($simple_declared_type === 'object') {
                        $is_match = true;
                        break;
                    }

                    if (!ClassLikeChecker::classOrInterfaceExists($project_checker, $simple_declared_type)) {
                        break;
                    }

                    if (ClassChecker::classExtendsOrImplements(
                        $project_checker,
                        $differing_type,
                        $simple_declared_type
                    )
                    ) {
                        $is_match = true;
                        break;
                    }

                    if (InterfaceChecker::interfaceExists($project_checker, $differing_type) &&
                        InterfaceChecker::interfaceExtends($project_checker, $differing_type, $simple_declared_type)
                    ) {
                        $is_match = true;
                        break;
                    }
                }

                if (!$is_match) {
                    return false;
                }
            }
        }

        foreach ($declared_type->getTypes() as $key => $declared_atomic_type) {
            if (!isset($inferred_type->getTypes()[$key])) {
                continue;
            }

            $inferred_atomic_type = $inferred_type->getTypes()[$key];

            if (!$declared_atomic_type instanceof Type\Atomic\TArray &&
                !$declared_atomic_type instanceof Type\Atomic\TGenericObject
            ) {
                continue;
            }

            if (!$inferred_atomic_type instanceof Type\Atomic\TArray &&
                !$inferred_atomic_type instanceof Type\Atomic\TGenericObject
            ) {
                // @todo handle this better
                continue;
            }

            foreach ($declared_atomic_type->type_params as $offset => $type_param) {
                if (!self::hasIdenticalTypes(
                    $project_checker,
                    $declared_atomic_type->type_params[$offset],
                    $inferred_atomic_type->type_params[$offset]
                )) {
                    return false;
                }
            }
        }

        foreach ($declared_type->getTypes() as $key => $declared_atomic_type) {
            if (!isset($inferred_type->getTypes()[$key])) {
                continue;
            }

            $inferred_atomic_type = $inferred_type->getTypes()[$key];

            if (!($declared_atomic_type instanceof Type\Atomic\ObjectLike)) {
                continue;
            }

            if (!($inferred_atomic_type instanceof Type\Atomic\ObjectLike)) {
                // @todo handle this better
                continue;
            }

            foreach ($declared_atomic_type->properties as $property_name => $type_param) {
                if (!isset($inferred_atomic_type->properties[$property_name])) {
                    return false;
                }

                if (!self::hasIdenticalTypes(
                    $project_checker,
                    $type_param,
                    $inferred_atomic_type->properties[$property_name]
                )) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  Type\Union  $union
     * @param  ProjectChecker $project_checker
     *
     * @return Type\Union
     */
    public static function simplifyUnionType(ProjectChecker $project_checker, Type\Union $union)
    {
        $union_type_count = count($union->getTypes());

        if ($union_type_count === 1 || ($union_type_count === 2 && $union->isNullable())) {
            return $union;
        }

        $from_docblock = $union->from_docblock;
        $ignore_nullable_issues = $union->ignore_nullable_issues;

        $unique_types = [];

        $inverse_contains = [];

        foreach ($union->getTypes() as $type_part) {
            $is_contained_by_other = false;

            // don't try to simplify intersection types
            if ($type_part instanceof TNamedObject && $type_part->extra_types) {
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
                    TypeChecker::isAtomicContainedBy(
                        $project_checker,
                        $type_part,
                        $container_type_part,
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

        return $unique_type;
    }
}
