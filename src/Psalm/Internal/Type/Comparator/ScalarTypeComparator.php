<?php

namespace Psalm\Internal\Type\Comparator;

use Exception;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TDependentGetClass;
use Psalm\Type\Atomic\TDependentGetDebugType;
use Psalm\Type\Atomic\TDependentGetType;
use Psalm\Type\Atomic\TDependentListKey;
use Psalm\Type\Atomic\TEmptyNumeric;
use Psalm\Type\Atomic\TEmptyScalar;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntMask;
use Psalm\Type\Atomic\TIntMaskOf;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyScalar;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTraitString;
use Psalm\Type\Atomic\TTrue;

use function get_class;
use function is_numeric;
use function sprintf;
use function strtolower;

/**
 * @internal
 */
class ScalarTypeComparator
{
    public static function isContainedBy(
        Codebase $codebase,
        Scalar $input_type_part,
        Scalar $container_type_part,
        bool $allow_interface_equality = false,
        bool $allow_float_int_equality = true,
        ?TypeComparisonResult $atomic_comparison_result = null
    ): bool {
        // TODO: Remove this if statement once all container types have been converted to use this pattern.
        if (!$container_type_part instanceof TString) {
            if ($container_type_part instanceof TArrayKey) {
                $result = self::isContainedByArrayKey($input_type_part, $container_type_part, $codebase);
            } elseif ($container_type_part instanceof TNumeric) {
                $result = self::isContainedByNumeric($input_type_part, $container_type_part);
            } elseif ($container_type_part instanceof TScalar) {
                $result = self::isContainedByScalar($input_type_part, $container_type_part);
            } elseif ($container_type_part instanceof TBool) {
                $result = self::isContainedByBool($input_type_part, $container_type_part);
            } elseif ($container_type_part instanceof TFloat) {
                $result = self::isContainedByFloat($input_type_part, $container_type_part, $allow_float_int_equality);
            } elseif ($container_type_part instanceof TInt) {
                $result = self::isContainedByInt($input_type_part, $container_type_part);
            } else {
                throw new Exception(
                    sprintf('"%s" is not supported by "%s".', get_class($container_type_part), __METHOD__),
                );
            }
            if ($result) {
                return true;
            } else {
                if ($atomic_comparison_result) {
                    // The type is coerced if the container type is contained by the input type (the opposite).
                    $atomic_comparison_result->type_coerced = self::isContainedBy(
                        $codebase,
                        $container_type_part,
                        $input_type_part,
                        $allow_interface_equality,
                        $allow_float_int_equality,
                    );
                    $atomic_comparison_result->type_coerced_from_scalar = $atomic_comparison_result->type_coerced
                        && $input_type_part instanceof TScalar;
                    $atomic_comparison_result->scalar_type_match_found = !$container_type_part->from_docblock;
                }
                return false;
            }
        }

        if (get_class($container_type_part) === TString::class
            && $input_type_part instanceof TString
        ) {
            return true;
        }

        if (get_class($container_type_part) === TInt::class
            && $input_type_part instanceof TInt
        ) {
            return true;
        }

        if ($container_type_part instanceof TNonEmptyString
            && get_class($input_type_part) === TString::class
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TNonspecificLiteralString
            && ($input_type_part instanceof TLiteralString || $input_type_part instanceof TNonspecificLiteralString)
        ) {
            return true;
        }

        if ($container_type_part instanceof TNonspecificLiteralString) {
            if ($input_type_part instanceof TString) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }
            }

            return false;
        }

        if ($container_type_part instanceof TNonspecificLiteralInt
            && ($input_type_part instanceof TLiteralInt
                || $input_type_part instanceof TNonspecificLiteralInt)
        ) {
            return true;
        }

        if ($container_type_part instanceof TNonspecificLiteralInt) {
            if ($input_type_part instanceof TInt) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }
            }

            return false;
        }

        if ($input_type_part instanceof TCallableString
            && (get_class($container_type_part) === TSingleLetter::class
                || get_class($container_type_part) === TNonEmptyString::class
                || get_class($container_type_part) === TNonFalsyString::class
                || get_class($container_type_part) === TLowercaseString::class)
        ) {
            return true;
        }

        if (($container_type_part instanceof TLowercaseString
                || $container_type_part instanceof TNonEmptyLowercaseString)
            && $input_type_part instanceof TString
        ) {
            if (($input_type_part instanceof TLowercaseString
                    && $container_type_part instanceof TLowercaseString)
                || ($input_type_part instanceof TNonEmptyLowercaseString
                    && $container_type_part instanceof TNonEmptyLowercaseString)
            ) {
                return true;
            }

            if ($input_type_part instanceof TNonEmptyLowercaseString
                && $container_type_part instanceof TLowercaseString
            ) {
                return true;
            }

            if ($input_type_part instanceof TLowercaseString
                && $container_type_part instanceof TNonEmptyLowercaseString
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            if ($input_type_part instanceof TLiteralString) {
                if (strtolower($input_type_part->value) === $input_type_part->value) {
                    return $input_type_part->value || $container_type_part instanceof TLowercaseString;
                }

                return false;
            }

            if ($input_type_part instanceof TClassString) {
                return false;
            }

            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TDependentGetClass) {
            $first_type = $container_type_part->as_type->getSingleAtomic();

            $container_type_part = new TClassString(
                'object',
                $first_type instanceof TNamedObject ? $first_type : null,
            );
        }

        if ($input_type_part instanceof TDependentGetClass) {
            $first_type = $input_type_part->as_type->getSingleAtomic();

            if ($first_type instanceof TTemplateParam) {
                $object_type = $first_type->as->getSingleAtomic();

                $input_type_part = new TTemplateParamClass(
                    $first_type->param_name,
                    $first_type->as->getId(),
                    $object_type instanceof TNamedObject ? $object_type : null,
                    $first_type->defining_class,
                );
            } else {
                $input_type_part = new TClassString(
                    'object',
                    $first_type instanceof TNamedObject ? $first_type : null,
                );
            }
        }

        if ($input_type_part instanceof TDependentGetType) {
            $input_type_part = new TString();

            if ($container_type_part instanceof TLiteralString) {
                return isset(ClassLikeAnalyzer::GETTYPE_TYPES[$container_type_part->value]);
            }
        }

        if ($container_type_part instanceof TDependentGetDebugType) {
            return $input_type_part instanceof TString;
        }

        if ($input_type_part instanceof TDependentGetDebugType) {
            $input_type_part = new TString();
        }

        if ($container_type_part instanceof TDependentGetType) {
            $container_type_part = new TString();

            if ($input_type_part instanceof TLiteralString) {
                return isset(ClassLikeAnalyzer::GETTYPE_TYPES[$input_type_part->value]);
            }
        }

        if ($input_type_part instanceof TArrayKey &&
            ($container_type_part instanceof TInt || $container_type_part instanceof TString)
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_mixed = true;
                $atomic_comparison_result->scalar_type_match_found = !$container_type_part->from_docblock;
            }

            return false;
        }

        if ((get_class($container_type_part) === TNonEmptyString::class
                || get_class($container_type_part) === TNonEmptyNonspecificLiteralString::class)
            && $input_type_part instanceof TNonFalsyString
        ) {
            return true;
        }

        if ($container_type_part instanceof TNonFalsyString
            && $input_type_part instanceof TNonFalsyString
        ) {
            return true;
        }

        if ($container_type_part instanceof TNonFalsyString
            && ($input_type_part instanceof TNonEmptyString
                || $input_type_part instanceof TNonEmptyNonspecificLiteralString)
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TNonEmptyString
            && $input_type_part instanceof TLiteralString
            && $input_type_part->value === ''
        ) {
            return false;
        }

        if ($container_type_part instanceof TNonFalsyString
            && $input_type_part instanceof TLiteralString
            && $input_type_part->value === '0'
        ) {
            return false;
        }

        if ((get_class($container_type_part) === TNonEmptyString::class
                || get_class($container_type_part) === TNonFalsyString::class
                || get_class($container_type_part) === TSingleLetter::class)
            && $input_type_part instanceof TLiteralString
        ) {
            return true;
        }

        if (get_class($input_type_part) === TInt::class && $container_type_part instanceof TLiteralInt) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        if ($input_type_part instanceof TIntRange && $container_type_part instanceof TIntRange) {
            return IntegerRangeComparator::isContainedBy(
                $input_type_part,
                $container_type_part,
            );
        }

        if ($input_type_part instanceof TInt && $container_type_part instanceof TIntRange) {
            if ($input_type_part instanceof TLiteralInt) {
                $min_bound = $container_type_part->min_bound;
                $max_bound = $container_type_part->max_bound;

                return
                    ($min_bound === null || $min_bound <= $input_type_part->value) &&
                    ($max_bound === null || $max_bound >= $input_type_part->value);
            }

            //any int can't be pushed inside a range without coercion (unless the range is from min to max)
            if ($container_type_part->min_bound !== null || $container_type_part->max_bound !== null) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                    $atomic_comparison_result->type_coerced_from_scalar = true;
                }
            }

            return false;
        }

        if ((get_class($input_type_part) === TString::class
                || get_class($input_type_part) === TSingleLetter::class
                || $input_type_part instanceof TNonEmptyString
                || $input_type_part instanceof TNonspecificLiteralString)
            && $container_type_part instanceof TLiteralString
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        if (($input_type_part instanceof TLowercaseString
                || $input_type_part instanceof TNonEmptyLowercaseString)
            && $container_type_part instanceof TLiteralString
            && strtolower($container_type_part->value) === $container_type_part->value
        ) {
            if ($atomic_comparison_result
                && ($container_type_part->value)
            ) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        if (($container_type_part instanceof TClassString || $container_type_part instanceof TLiteralClassString)
            && ($input_type_part instanceof TClassString || $input_type_part instanceof TLiteralClassString)
        ) {
            return ClassLikeStringComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result,
            );
        }

        if ($container_type_part instanceof TString && $input_type_part instanceof TTraitString) {
            return true;
        }

        if ($container_type_part instanceof TTraitString
            && (get_class($input_type_part) === TString::class
                || $input_type_part instanceof TNonEmptyString
                || $input_type_part instanceof TNonEmptyNonspecificLiteralString)
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if (($input_type_part instanceof TClassString
            || $input_type_part instanceof TLiteralClassString)
            && (get_class($container_type_part) === TSingleLetter::class
                || get_class($container_type_part) === TNonEmptyString::class
                || get_class($container_type_part) === TNonFalsyString::class)
        ) {
            return true;
        }

        if ($input_type_part instanceof TNumericString
            && get_class($container_type_part) === TNonEmptyString::class
        ) {
            return true;
        }

        if ($container_type_part instanceof TString
            && $input_type_part instanceof TNumericString
        ) {
            if ($container_type_part instanceof TLiteralString) {
                if (is_numeric($container_type_part->value) && $atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            return true;
        }

        if ($input_type_part instanceof TString
            && $container_type_part instanceof TNumericString
        ) {
            if ($input_type_part instanceof TLiteralString) {
                return is_numeric($input_type_part->value);
            }
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TCallableString
            && $input_type_part instanceof TLiteralString
        ) {
            $input_callable = CallableTypeComparator::getCallableFromAtomic($codebase, $input_type_part);
            $container_callable = CallableTypeComparator::getCallableFromAtomic($codebase, $container_type_part);

            if ($input_callable && $container_callable) {
                if (CallableTypeComparator::isContainedBy(
                    $codebase,
                    $input_callable,
                    $container_callable,
                    $atomic_comparison_result ?? new TypeComparisonResult(),
                ) === false
                ) {
                    return false;
                }
            }

            if (!$input_callable) {
                //we could not find a callable for the input type, so the input is not contained in the container
                return false;
            }

            return true;
        }

        if ($input_type_part instanceof TLowercaseString
            && get_class($container_type_part) === TNonEmptyString::class) {
            return false;
        }

        if ($input_type_part->getKey() === $container_type_part->getKey()) {
            return true;
        }

        if (($container_type_part instanceof TClassString
                || $container_type_part instanceof TLiteralClassString
                || $container_type_part instanceof TCallableString)
            && $input_type_part instanceof TString
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($input_type_part instanceof TNumeric) {
            if ($container_type_part->isNumericType()) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                    $atomic_comparison_result->scalar_type_match_found = !$container_type_part->from_docblock;
                }
            }
        }

        if (!$container_type_part instanceof TLiteralInt
            && !$container_type_part instanceof TLiteralString
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced
                    = $atomic_comparison_result->type_coerced_from_scalar
                    = $input_type_part instanceof TScalar;
                $atomic_comparison_result->scalar_type_match_found = !$container_type_part->from_docblock;
            }
        }

        return false;
    }

    private static function isContainedByArrayKey(
        Scalar $input_type,
        TArrayKey $container_type,
        Codebase $codebase
    ): bool {
        $container_type_class = get_class($container_type);
        if ($container_type_class === TArrayKey::class) {
            return $input_type instanceof TArrayKey
                || $input_type instanceof TString
                || $input_type instanceof TInt
                || $input_type instanceof TNumeric;
        }
        if ($container_type instanceof TKeyOf) {
            $key_types = TKeyOf::getArrayKeyType($container_type->type);
            if ($key_types) {
                foreach ($key_types->getAtomicTypes() as $key_type) {
                    if (AtomicTypeComparator::isContainedBy($codebase, $input_type, $key_type)) {
                        return true;
                    }
                }
            }
            return false;
        }
        throw new Exception(sprintf('"%s" is not supported by "%s".', $container_type_class, __METHOD__));
    }

    private static function isContainedByNumeric(
        Scalar $input_type,
        TNumeric $container_type
    ): bool {
        $container_type_class = get_class($container_type);
        if ($container_type_class === TNumeric::class) {
            return $input_type instanceof TNumeric
                || $input_type instanceof TInt
                || $input_type instanceof TFloat
                || $input_type instanceof TNumericString
                || ($input_type instanceof TLiteralString && is_numeric($input_type->value));
        }
        if ($container_type instanceof TEmptyNumeric) {
            return $input_type instanceof TEmptyNumeric
                || ($input_type instanceof TLiteralInt && empty($input_type->value))
                || ($input_type instanceof TLiteralFloat && empty($input_type->value))
                || ($input_type instanceof TLiteralString
                    && is_numeric($input_type->value)
                    && empty($input_type->value));
        }
        throw new Exception(sprintf('"%s" is not supported by "%s".', $container_type_class, __METHOD__));
    }

    private static function isContainedByScalar(
        Scalar $input_type,
        TScalar $container_type
    ): bool {
        $container_type_class = get_class($container_type);
        if ($container_type_class === TScalar::class) {
            return true;
        }
        if ($container_type instanceof TEmptyScalar) {
            return $input_type instanceof TEmptyScalar
                || $input_type instanceof TFalse
                || $input_type instanceof TEmptyNumeric
                || ($input_type instanceof TLiteralInt && empty($input_type->value))
                || ($input_type instanceof TLiteralFloat && empty($input_type->value))
                || ($input_type instanceof TLiteralString && empty($input_type->value));
        }
        if ($container_type instanceof TNonEmptyScalar) {
            return $input_type instanceof TNonEmptyScalar
                || $input_type instanceof TTrue
                || ($input_type instanceof TLiteralInt && !empty($input_type->value))
                || ($input_type instanceof TLiteralFloat && !empty($input_type->value))
                || ($input_type instanceof TLiteralString && !empty($input_type->value));
        }
        throw new Exception(sprintf('"%s" is not supported by "%s".', $container_type_class, __METHOD__));
    }

    private static function isContainedByBool(
        Scalar $input_type,
        TBool $container_type
    ): bool {
        $container_type_class = get_class($container_type);
        if ($container_type_class === TBool::class) {
            return $input_type instanceof TBool;
        }
        if ($container_type instanceof TFalse) {
            return $input_type instanceof TFalse;
        }
        if ($container_type instanceof TTrue) {
            return $input_type instanceof TTrue;
        }
        throw new Exception(sprintf('"%s" is not supported by "%s".', $container_type_class, __METHOD__));
    }

    private static function isContainedByFloat(
        Scalar $input_type,
        TFloat $container_type,
        bool $allow_float_int_equality
    ): bool {
        $container_type_class = get_class($container_type);
        if ($container_type_class === TFloat::class) {
            return $input_type instanceof TFloat
                || ($allow_float_int_equality && $input_type instanceof TInt);
        }
        if ($container_type instanceof TLiteralFloat) {
            return ($input_type instanceof TLiteralFloat && $container_type->value === $input_type->value)
                || ($allow_float_int_equality
                    && $input_type instanceof TLiteralInt
                    && $container_type->value === (float) $input_type->value);
        }
        throw new Exception(sprintf('"%s" is not supported by "%s".', $container_type_class, __METHOD__));
    }

    private static function isContainedByInt(
        Scalar $input_type,
        TInt $container_type
    ): bool {
        $container_type_class = get_class($container_type);
        if ($container_type_class === TInt::class) {
            return $input_type instanceof TInt;
        }
        if ($container_type instanceof TIntMask) {
            return false; // TODO
        }
        if ($container_type instanceof TIntMaskOf) {
            return false; // TODO
        }
        if ($container_type instanceof TDependentListKey) {
            return false; // TODO
        }
        if ($container_type instanceof TLiteralInt) {
            return $input_type instanceof TLiteralInt && $container_type->value === $input_type->value;
        }
        if ($container_type instanceof TNonspecificLiteralInt) {
            return $input_type instanceof TNonspecificLiteralInt;
        }
        if ($container_type instanceof TIntRange) {
            return ($input_type instanceof TIntRange
                    && ($container_type->min_bound === null
                        || ($input_type->min_bound !== null && $container_type->min_bound <= $input_type->min_bound))
                    && ($container_type->max_bound === null
                        || ($input_type->max_bound !== null && $container_type->max_bound >= $input_type->max_bound)))
                || ($input_type instanceof TLiteralInt && $container_type->contains($input_type->value));
        }
        throw new Exception(sprintf('"%s" is not supported by "%s".', $container_type_class, __METHOD__));
    }
}
