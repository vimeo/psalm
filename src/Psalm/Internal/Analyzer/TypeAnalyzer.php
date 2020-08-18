<?php
namespace Psalm\Internal\Analyzer;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TString;
use function array_merge;
use function count;
use function array_keys;
use function array_unique;

/**
 * @internal
 */
class TypeAnalyzer
{
    /**
     * Does the input param type match the given param type
     *
     * @deprecated in favour of UnionTypeComparator
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function isContainedBy(
        Codebase $codebase,
        Type\Union $input_type,
        Type\Union $container_type,
        bool $ignore_null = false,
        bool $ignore_false = false,
        ?TypeComparisonResult $union_comparison_result = null,
        bool $allow_interface_equality = false
    ) : bool {
        return UnionTypeComparator::isContainedBy(
            $codebase,
            $input_type,
            $container_type,
            $ignore_null,
            $ignore_false,
            $union_comparison_result,
            $allow_interface_equality
        );
    }

    /**
     * Does the input param atomic type match the given param atomic type
     *
     * @deprecated in favour of AtomicTypeComparator
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function isAtomicContainedBy(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        bool $allow_interface_equality = false,
        bool $allow_float_int_equality = true,
        ?TypeComparisonResult $atomic_comparison_result = null
    ) : bool {
        return AtomicTypeComparator::isContainedBy(
            $codebase,
            $input_type_part,
            $container_type_part,
            $allow_interface_equality,
            $allow_float_int_equality,
            $atomic_comparison_result
        );
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
        $union_type_count = count($union->getAtomicTypes());

        if ($union_type_count === 1 || ($union_type_count === 2 && $union->isNullable())) {
            return $union;
        }

        $from_docblock = $union->from_docblock;
        $ignore_nullable_issues = $union->ignore_nullable_issues;
        $ignore_falsable_issues = $union->ignore_falsable_issues;
        $possibly_undefined = $union->possibly_undefined;

        $unique_types = [];

        $inverse_contains = [];

        foreach ($union->getAtomicTypes() as $type_part) {
            $is_contained_by_other = false;

            // don't try to simplify intersection types
            if (($type_part instanceof TNamedObject
                    || $type_part instanceof TTemplateParam
                    || $type_part instanceof TIterable)
                && $type_part->extra_types
            ) {
                return $union;
            }

            foreach ($union->getAtomicTypes() as $container_type_part) {
                $string_container_part = $container_type_part->getId();
                $string_input_part = $type_part->getId();

                $atomic_comparison_result = new TypeComparisonResult();

                if ($type_part !== $container_type_part &&
                    !(
                        $container_type_part instanceof TInt
                        || $container_type_part instanceof TFloat
                        || $container_type_part instanceof TCallable
                        || ($container_type_part instanceof TString && $type_part instanceof TCallable)
                        || ($container_type_part instanceof TArray && $type_part instanceof TCallable)
                    ) &&
                    !isset($inverse_contains[$string_input_part][$string_container_part]) &&
                    AtomicTypeComparator::isContainedBy(
                        $codebase,
                        $type_part,
                        $container_type_part,
                        false,
                        false,
                        $atomic_comparison_result
                    ) &&
                    !$atomic_comparison_result->to_string_cast
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

        if (!$unique_types) {
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
