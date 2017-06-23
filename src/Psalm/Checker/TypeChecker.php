<?php
namespace Psalm\Checker;

use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\CodeLocation;
use Psalm\Issue\FailedTypeResolution;
use Psalm\Issue\TypeDoesNotContainNull;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
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
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;

class TypeChecker
{
    /**
     * Takes two arrays and consolidates them, removing null values from existing types where applicable
     *
     * @param  array<string, string>     $new_types
     * @param  array<string, Type\Union> $existing_types
     * @param  array<string>             $changed_types
     * @param  StatementsChecker         $statements_checker
     * @param  CodeLocation              $code_location
     * @param  array<string>             $suppressed_issues
     *
     * @return array<string, Type\Union>|false
     */
    public static function reconcileKeyedTypes(
        array $new_types,
        array $existing_types,
        array &$changed_types,
        StatementsChecker $statements_checker,
        CodeLocation $code_location,
        array $suppressed_issues = []
    ) {
        $keys = [];

        foreach ($existing_types as $ek => $_) {
            if (!in_array($ek, $keys, true)) {
                $keys[] = $ek;
            }
        }

        foreach ($new_types as $nk => $_) {
            if (!in_array($nk, $keys, true)) {
                $keys[] = $nk;
            }
        }

        if (empty($new_types)) {
            return $existing_types;
        }

        foreach ($keys as $key) {
            if (!isset($new_types[$key])) {
                continue;
            }

            $new_type_parts = explode('&', $new_types[$key]);

            $result_type = isset($existing_types[$key])
                ? clone $existing_types[$key]
                : self::getValueForKey($key, $existing_types, $statements_checker->getFileChecker());

            if ($result_type && empty($result_type->types)) {
                throw new \InvalidArgumentException('Union::$types cannot be empty after get value for ' . $key);
            }

            $before_adjustment = (string)$result_type;

            $failed_reconciliation = false;

            foreach ($new_type_parts as $new_type_part) {
                $result_type = self::reconcileTypes(
                    (string) $new_type_part,
                    $result_type,
                    $key,
                    $statements_checker,
                    $code_location,
                    $suppressed_issues,
                    $failed_reconciliation
                );

                // special case if result is just a simple array
                if ((string) $result_type === 'array') {
                    $result_type = Type::getArray();
                }

                if ($result_type === false) {
                    return false;
                }
            }

            if ($result_type === null) {
                continue;
            }

            if ((string)$result_type !== $before_adjustment) {
                $changed_types[] = $key;
            }

            if ($failed_reconciliation) {
                $result_type->failed_reconciliation = true;
            }

            $existing_types[$key] = $result_type;
        }

        return $existing_types;
    }

    /**
     * Reconciles types
     *
     * think of this as a set of functions e.g. empty(T), notEmpty(T), null(T), notNull(T) etc. where
     *  - empty(Object) => null,
     *  - empty(bool) => false,
     *  - notEmpty(Object|null) => Object,
     *  - notEmpty(Object|false) => Object
     *
     * @param   string              $new_var_type
     * @param   Type\Union|null     $existing_var_type
     * @param   string|null         $key
     * @param   StatementsChecker   $statements_checker
     * @param   CodeLocation        $code_location
     * @param   array               $suppressed_issues
     * @param   bool                $failed_reconciliation if the types cannot be reconciled, we need to know
     *
     * @return  Type\Union|null|false
     */
    public static function reconcileTypes(
        $new_var_type,
        $existing_var_type,
        $key,
        StatementsChecker $statements_checker,
        CodeLocation $code_location = null,
        array $suppressed_issues = [],
        &$failed_reconciliation = false
    ) {
        $file_checker = $statements_checker->getFileChecker();

        if ($existing_var_type === null) {
            if ($new_var_type === '^isset') {
                return null;
            }

            if ($new_var_type === 'isset') {
                return Type::getMixed();
            }

            if ($new_var_type[0] !== '!' && $new_var_type !== 'empty') {
                if ($new_var_type[0] === '^') {
                    $new_var_type = substr($new_var_type, 1);
                }

                return Type::parseString($new_var_type);
            }

            return $new_var_type === '!empty' ? Type::getMixed() : null;
        }

        if ($new_var_type === 'mixed' && $existing_var_type->isMixed()) {
            return $existing_var_type;
        }

        if ($new_var_type[0] === '!') {
            // this is a specific value comparison type that cannot be negated
            if ($new_var_type[1] === '^') {
                return $existing_var_type;
            }

            if ($new_var_type === '!isset') {
                return Type::getNull();
            }

            if ($new_var_type === '!object' && !$existing_var_type->isMixed()) {
                $non_object_types = [];

                foreach ($existing_var_type->types as $type) {
                    if (!$type->isObjectType()) {
                        $non_object_types[] = $type;
                    }
                }

                if ($non_object_types) {
                    return new Type\Union($non_object_types);
                } elseif (!$existing_var_type->from_docblock) {
                    if ($key && $code_location) {
                        if (IssueBuffer::accepts(
                            new FailedTypeResolution('Cannot resolve types for ' . $key, $code_location),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    }

                    $failed_reconciliation = true;
                }

                return Type::getMixed();
            }

            if (in_array($new_var_type, ['!empty', '!null'], true)) {
                $existing_var_type->removeType('null');

                if ($new_var_type === '!empty') {
                    $existing_var_type->removeType('false');

                    if ($existing_var_type->hasType('array') &&
                        (string)$existing_var_type->types['array'] === 'array<empty, empty>'
                    ) {
                        $existing_var_type->removeType('array');
                    }
                }

                if (empty($existing_var_type->types)) {
                    if ($key && $code_location) {
                        if (IssueBuffer::accepts(
                            new FailedTypeResolution('Cannot resolve types for ' . $key, $code_location),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    }

                    $failed_reconciliation = true;

                    return Type::getMixed();
                }

                return $existing_var_type;
            }

            $negated_type = substr($new_var_type, 1);

            $existing_var_type->removeType($negated_type);

            if (empty($existing_var_type->types)) {
                if (!$existing_var_type->from_docblock
                    && ($key !== '$this' || !($statements_checker->getSource()->getSource() instanceof TraitChecker))
                ) {
                    if ($key && $code_location) {
                        if (IssueBuffer::accepts(
                            new FailedTypeResolution('Cannot resolve types for ' . $key, $code_location),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    }
                }

                $failed_reconciliation = true;

                return Type::getMixed();
            }

            return $existing_var_type;
        }

        if ($new_var_type === '^isset' || $new_var_type === 'isset') {
            $existing_var_type->removeType('null');

            if (empty($existing_var_type->types)) {
                $failed_reconciliation = true;

                // @todo - I think there's a better way to handle this, but for the moment
                // mixed will have to do.
                return Type::getMixed();
            }

            return $existing_var_type;
        }

        if ($new_var_type[0] === '^') {
            $new_var_type = substr($new_var_type, 1);
        }

        if ($new_var_type === 'empty') {
            if ($existing_var_type->hasType('bool')) {
                $existing_var_type->removeType('bool');
                $existing_var_type->types['false'] = new TFalse;
            }

            $existing_var_type->removeObjects();

            if (empty($existing_var_type->types)) {
                return Type::getNull();
            }

            return $existing_var_type;
        }

        if ($new_var_type === 'object' && !$existing_var_type->isMixed()) {
            $object_types = [];

            foreach ($existing_var_type->types as $type) {
                if ($type->isObjectType()) {
                    $object_types[] = $type;
                }
            }

            if ($object_types) {
                return new Type\Union($object_types);
            }
        }

        if ($new_var_type === 'numeric') {
            if ($existing_var_type->hasString()) {
                $existing_var_type->removeType('string');
                $existing_var_type->types['numeric-string'] = new TNumericString;
            }

            $numeric_types = [];

            foreach ($existing_var_type->types as $type) {
                if ($type->isNumericType()) {
                    $numeric_types[] = $type;
                }
            }

            if ($numeric_types) {
                return new Type\Union($numeric_types);
            }
        }

        if ($new_var_type === 'scalar') {
            $scalar_types = [];

            foreach ($existing_var_type->types as $type) {
                if ($type instanceof Scalar) {
                    $scalar_types[] = $type;
                }
            }

            if ($scalar_types) {
                return new Type\Union($scalar_types);
            }
        }

        $new_type = Type::parseString($new_var_type);

        if ($existing_var_type->isMixed()) {
            return $new_type;
        }

        $has_interface = false;

        if ($new_type->hasObjectType()) {
            foreach ($new_type->types as $new_type_part) {
                if ($new_type_part instanceof TNamedObject &&
                    InterfaceChecker::interfaceExists($new_type_part->value, $file_checker)
                ) {
                    $has_interface = true;
                    break;
                }
            }
        }

        if ($has_interface) {
            $new_type_part = new TNamedObject($new_var_type);

            $acceptable_atomic_types = [];

            foreach ($existing_var_type->types as $existing_var_type_part) {
                if (TypeChecker::isAtomicContainedBy(
                    $existing_var_type_part,
                    $new_type_part,
                    $file_checker,
                    $scalar_type_match_found,
                    $type_coerced,
                    $atomic_to_string_cast
                )) {
                    $acceptable_atomic_types[] = $existing_var_type_part;
                    continue;
                }

                if ($existing_var_type_part instanceof TNamedObject &&
                    ClassChecker::classExists($existing_var_type_part->value, $file_checker)
                ) {
                    $existing_var_type_part->addIntersectionType($new_type_part);
                    $acceptable_atomic_types[] = $existing_var_type_part;
                }
            }

            if ($acceptable_atomic_types) {
                return new Type\Union($acceptable_atomic_types);
            }
        } elseif ($code_location &&
            !$new_type->isMixed() &&
            !$existing_var_type->from_docblock
        ) {
            $has_match = true;

            foreach ($new_type->types as $new_type_part) {
                $has_local_match = false;

                foreach ($existing_var_type->types as $existing_var_type_part) {
                    if (TypeChecker::isAtomicContainedBy(
                        $new_type_part,
                        $existing_var_type_part,
                        $file_checker,
                        $scalar_type_match_found,
                        $type_coerced,
                        $atomic_to_string_cast
                    ) || $type_coerced
                    ) {
                        $has_local_match = true;
                        break;
                    }
                }

                if (!$has_local_match) {
                    $has_match = false;
                    break;
                }
            }

            if (!$has_match) {
                if ($new_var_type === 'null') {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainNull(
                            'Cannot resolve types for ' . $key . ' - ' . $existing_var_type .
                            ' does not contain null',
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainType(
                            'Cannot resolve types for ' . $key . ' - ' . $existing_var_type .
                            ' does not contain ' . $new_type,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        // fall through
                    }
                }

                $failed_reconciliation = true;
            }
        }

        if ($existing_var_type->hasType($new_var_type)) {
            return new Type\Union([$existing_var_type->types[$new_var_type]]);
        }

        return $new_type;
    }

    /**
     * Does the input param type match the given param type
     *
     * @param  Type\Union   $input_type
     * @param  Type\Union   $container_type
     * @param  FileChecker  $file_checker
     * @param  bool         $ignore_null
     * @param  bool         &$has_scalar_match
     * @param  bool         &$type_coerced    whether or not there was type coercion involved
     * @param  bool         &$to_string_cast
     *
     * @return bool
     */
    public static function isContainedBy(
        Type\Union $input_type,
        Type\Union $container_type,
        FileChecker $file_checker,
        $ignore_null = false,
        &$has_scalar_match = null,
        &$type_coerced = null,
        &$to_string_cast = null
    ) {
        $has_scalar_match = true;

        if ($container_type->isMixed()) {
            return true;
        }

        $type_match_found = false;

        foreach ($input_type->types as $input_type_part) {
            if ($input_type_part instanceof TNull && $ignore_null) {
                continue;
            }

            $type_match_found = false;
            $scalar_type_match_found = false;
            $all_to_string_cast = true;
            $atomic_to_string_cast = false;

            foreach ($container_type->types as $container_type_part) {
                $is_atomic_contained_by = self::isAtomicContainedBy(
                    $input_type_part,
                    $container_type_part,
                    $file_checker,
                    $scalar_type_match_found,
                    $type_coerced,
                    $atomic_to_string_cast
                );

                if ($is_atomic_contained_by) {
                    $type_match_found = true;
                } elseif (!$type_coerced &&
                    $input_type_part instanceof TNamedObject &&
                    $intersection_types = $input_type_part->getIntersectionTypes()
                ) {
                    foreach ($intersection_types as $intersection_type) {
                        $is_atomic_contained_by = self::isAtomicContainedBy(
                            $intersection_type,
                            $container_type_part,
                            $file_checker,
                            $scalar_type_match_found,
                            $type_coerced,
                            $atomic_to_string_cast
                        );

                        if ($is_atomic_contained_by) {
                            $type_match_found = true;
                            break;
                        }
                    }
                }

                if ($atomic_to_string_cast !== true) {
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
     * @param  FileChecker  $file_checker
     *
     * @return bool
     */
    public static function canBeIdenticalTo(
        Type\Union $type1,
        Type\Union $type2,
        FileChecker $file_checker
    ) {
        if ($type1->isMixed() || $type2->isMixed()) {
            return true;
        }

        if ($type1->isNullable() && $type2->isNullable()) {
            return true;
        }

        $type_match_found = false;

        foreach ($type1->types as $type1_part) {
            if ($type1_part instanceof TNull) {
                continue;
            }

            foreach ($type2->types as $type2_part) {
                if ($type2_part instanceof TNull) {
                    continue;
                }

                $either_contains = self::isAtomicContainedBy(
                    $type1_part,
                    $type2_part,
                    $file_checker
                ) || self::isAtomicContainedBy(
                    $type2_part,
                    $type1_part,
                    $file_checker
                );

                if ($either_contains) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Does the input param atomic type match the given param atomic type
     *
     * @param  Type\Atomic  $input_type_part
     * @param  Type\Atomic  $container_type_part
     * @param  FileChecker  $file_checker
     * @param  bool         &$has_scalar_match
     * @param  bool         &$type_coerced    whether or not there was type coercion involved
     * @param  bool         &$to_string_cast
     *
     * @return bool
     */
    private static function isAtomicContainedBy(
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        FileChecker $file_checker,
        &$has_scalar_match = null,
        &$type_coerced = null,
        &$to_string_cast = null
    ) {
        if ($container_type_part instanceof TMixed) {
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
                (
                    (ClassChecker::classExists($input_type_part->value, $file_checker) &&
                        ClassChecker::classExtendsOrImplements(
                            $input_type_part->value,
                            $container_type_part->value
                        )
                    ) ||
                    (InterfaceChecker::interfaceExists($input_type_part->value, $file_checker) &&
                        InterfaceChecker::interfaceExtends(
                            $input_type_part->value,
                            $container_type_part->value,
                            $file_checker
                        )
                    ) ||
                    ExpressionChecker::isMock($input_type_part->value)
                )
            )
        ) {
            $all_types_contain = true;

            if ($input_type_part instanceof TArray && $container_type_part instanceof TArray) {
                foreach ($input_type_part->type_params as $i => $input_param) {
                    $container_param = $container_type_part->type_params[$i];

                    if (!$input_param->isEmpty() &&
                        !self::isContainedBy(
                            $input_param,
                            $container_param,
                            $file_checker,
                            false,
                            $has_scalar_match,
                            $type_coerced
                        )
                    ) {
                        if (self::isContainedBy($container_param, $input_param, $file_checker)) {
                            $type_coerced = true;
                        }

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

        if ($input_type_part instanceof TFalse && $container_type_part instanceof TBool) {
            return true;
        }

        // from https://wiki.php.net/rfc/scalar_type_hints_v5:
        //
        // > int types can resolve a parameter type of float
        if ($input_type_part instanceof TInt && $container_type_part instanceof TFloat) {
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

        if ($container_type_part instanceof TArray && $input_type_part instanceof ObjectLike) {
            return true;
        }

        if ($container_type_part instanceof TNamedObject &&
            strtolower($container_type_part->value) === 'iterable' &&
            (
                $input_type_part instanceof TArray ||
                ($input_type_part instanceof TNamedObject &&
                    (strtolower($input_type_part->value) === 'traversable' ||
                        ClassChecker::classExtendsOrImplements(
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
            if (ClassChecker::classOrInterfaceExists($input_type_part->value, $file_checker) &&
                MethodChecker::methodExists($input_type_part->value . '::__toString', $file_checker)
            ) {
                $to_string_cast = true;

                return true;
            }
        }

        if ($container_type_part instanceof TCallable &&
            ($input_type_part instanceof TString ||
                $input_type_part instanceof TArray ||
                ($input_type_part instanceof TNamedObject &&
                    ClassChecker::classExists($input_type_part->value, $file_checker) &&
                    MethodChecker::methodExists($input_type_part->value . '::__invoke', $file_checker)
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
            ClassChecker::classOrInterfaceExists($input_type_part->value, $file_checker) &&
            ((
                ClassChecker::classExists($container_type_part->value, $file_checker) &&
                    ClassChecker::classExtendsOrImplements(
                        $container_type_part->value,
                        $input_type_part->value
                    )
                ) ||
                (InterfaceChecker::interfaceExists($container_type_part->value, $file_checker) &&
                    InterfaceChecker::interfaceExtends(
                        $container_type_part->value,
                        $input_type_part->value,
                        $file_checker
                    )
                )
            )
        ) {
            $type_coerced = true;
        }

        return false;
    }

    /**
     * Gets the type for a given (non-existent key) based on the passed keys
     *
     * @param  string                    $key
     * @param  array<string,Type\Union>  $existing_keys
     * @param  FileChecker               $file_checker
     *
     * @return Type\Union|null
     */
    protected static function getValueForKey($key, array &$existing_keys, FileChecker $file_checker)
    {
        if (strpos($key, '[') !== false) {
            return self::getArrayValueForKey($key, $existing_keys);
        }

        $key_parts = explode('->', $key);

        $base_type = self::getArrayValueForKey($key_parts[0], $existing_keys);

        if (!$base_type) {
            return null;
        }

        $base_key = $key_parts[0];

        // for an expression like $obj->key1->key2
        for ($i = 1; $i < count($key_parts); ++$i) {
            $new_base_key = $base_key . '->' . $key_parts[$i];

            if (!isset($existing_keys[$new_base_key])) {
                $new_base_type = null;

                foreach ($existing_keys[$base_key]->types as $existing_key_type_part) {
                    if ($existing_key_type_part instanceof TNull) {
                        $class_property_type = Type::getNull();
                    } elseif ($existing_key_type_part instanceof TMixed ||
                        $existing_key_type_part instanceof TObject ||
                        ($existing_key_type_part instanceof TNamedObject &&
                            strtolower($existing_key_type_part->value) === 'stdclass')
                    ) {
                        $class_property_type = Type::getMixed();
                    } elseif ($existing_key_type_part instanceof TNamedObject) {
                        if (!ClassLikeChecker::classOrInterfaceExists(
                            $existing_key_type_part->value,
                            $file_checker
                        )) {
                            continue;
                        }

                        $property_id = $existing_key_type_part->value . '::$' . $key_parts[$i];

                        if (!ClassLikeChecker::propertyExists($property_id)) {
                            return null;
                        }

                        $declaring_property_class = ClassLikeChecker::getDeclaringClassForProperty($property_id);

                        $class_storage = ClassLikeChecker::$storage[strtolower((string)$declaring_property_class)];

                        $class_property_type = $class_storage->properties[$key_parts[$i]]->type;

                        $class_property_type = $class_property_type ? clone $class_property_type : Type::getMixed();
                    } else {
                        // @todo handle this
                        continue;
                    }

                    if ($new_base_type instanceof Type\Union) {
                        $new_base_type = Type::combineUnionTypes($new_base_type, $class_property_type);
                    } else {
                        $new_base_type = $class_property_type;
                    }

                    $existing_keys[$new_base_key] = $new_base_type;
                }
            }

            $base_type = $existing_keys[$new_base_key];
            $base_key = $new_base_key;
        }

        return $existing_keys[$base_key];
    }

    /**
     * Gets the type for a given (non-existent key) based on the passed keys
     *
     * @param  string                    $key
     * @param  array<string,Type\Union>  $existing_keys
     *
     * @return Type\Union|null
     */
    protected static function getArrayValueForKey($key, array &$existing_keys)
    {
        $key_parts = preg_split('/(\]|\[)/', $key, -1, PREG_SPLIT_NO_EMPTY);

        if (count($key_parts) === 1) {
            return isset($existing_keys[$key_parts[0]]) ? clone $existing_keys[$key_parts[0]] : null;
        }

        if (!isset($existing_keys[$key_parts[0]])) {
            return null;
        }

        $base_key = $key_parts[0];

        // for an expression like $obj->key1->key2
        for ($i = 1; $i < count($key_parts); ++$i) {
            $new_base_key = $base_key . '[' . $key_parts[$i] . ']';

            if (!isset($existing_keys[$new_base_key])) {
                $new_base_type = null;

                foreach ($existing_keys[$base_key]->types as $existing_key_type_part) {
                    if ($existing_key_type_part instanceof Type\Atomic\TArray) {
                        $new_base_type_candidate = clone $existing_key_type_part->type_params[1];
                    } elseif (!$existing_key_type_part instanceof Type\Atomic\ObjectLike) {
                        return null;
                    } else {
                        $array_properties = $existing_key_type_part->properties;

                        $key_parts_key = str_replace('\'', '', $key_parts[$i]);

                        if (!isset($array_properties[$key_parts_key])) {
                            return null;
                        }

                        $new_base_type_candidate = clone $array_properties[$key_parts_key];
                    }

                    if (!$new_base_type) {
                        $new_base_type = $new_base_type_candidate;
                    } else {
                        $new_base_type = Type::combineUnionTypes(
                            $new_base_type,
                            $new_base_type_candidate
                        );
                    }

                    $existing_keys[$new_base_key] = $new_base_type;
                }
            }

            $base_key = $new_base_key;
        }

        return $existing_keys[$base_key];
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

            if ((string) $new_var_types === (string) $existing_var_types) {
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
     * @param  FileChecker  $file_checker
     *
     * @return bool
     */
    public static function hasIdenticalTypes(
        Type\Union $declared_type,
        Type\Union $inferred_type,
        FileChecker $file_checker
    ) {
        if ($declared_type->isMixed() || $inferred_type->isEmpty()) {
            return true;
        }

        if ($declared_type->isNullable() !== $inferred_type->isNullable()) {
            return false;
        }

        $simple_declared_types = array_filter(
            array_keys($declared_type->types),
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
            array_keys($inferred_type->types),
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

                    if (!ClassLikeChecker::classOrInterfaceExists($differing_type, $file_checker)) {
                        break;
                    }

                    if ($simple_declared_type === 'object') {
                        $is_match = true;
                        break;
                    }

                    if (!ClassLikeChecker::classOrInterfaceExists($simple_declared_type, $file_checker)) {
                        break;
                    }

                    if (ClassChecker::classExtendsOrImplements($differing_type, $simple_declared_type)) {
                        $is_match = true;
                        break;
                    }

                    if (InterfaceChecker::interfaceExists($differing_type, $file_checker) &&
                        InterfaceChecker::interfaceExtends($differing_type, $simple_declared_type, $file_checker)
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

        foreach ($declared_type->types as $key => $declared_atomic_type) {
            if (!isset($inferred_type->types[$key])) {
                continue;
            }

            $inferred_atomic_type = $inferred_type->types[$key];

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
                    $declared_atomic_type->type_params[$offset],
                    $inferred_atomic_type->type_params[$offset],
                    $file_checker
                )) {
                    return false;
                }
            }
        }

        foreach ($declared_type->types as $key => $declared_atomic_type) {
            if (!isset($inferred_type->types[$key])) {
                continue;
            }

            $inferred_atomic_type = $inferred_type->types[$key];

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
                    $type_param,
                    $inferred_atomic_type->properties[$property_name],
                    $file_checker
                )) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  Type\Union  $union
     * @param  FileChecker $file_checker
     *
     * @return Type\Union
     */
    public static function simplifyUnionType(Type\Union $union, FileChecker $file_checker)
    {
        $union_type_count = count($union->types);

        if ($union_type_count === 1 || ($union_type_count === 2 && $union->isNullable())) {
            return $union;
        }

        $from_docblock = $union->from_docblock;
        $ignore_nullable_issues = $union->ignore_nullable_issues;

        $unique_types = [];

        foreach ($union->types as $type_part) {
            $is_contained_by_other = false;

            foreach ($union->types as $container_type_part) {
                if ($type_part !== $container_type_part &&
                    !($type_part instanceof TInt && $container_type_part instanceof TFloat) &&
                    TypeChecker::isAtomicContainedBy(
                        $type_part,
                        $container_type_part,
                        $file_checker,
                        $has_scalar_match,
                        $type_coerced,
                        $to_string_cast
                    ) &&
                    !$to_string_cast
                ) {
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
