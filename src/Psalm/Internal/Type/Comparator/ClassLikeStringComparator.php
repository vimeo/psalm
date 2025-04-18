<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParamClass;

/**
 * @internal
 */
final class ClassLikeStringComparator
{
    /**
     * @param TClassString|TLiteralClassString $input_type_part
     * @param TClassString|TLiteralClassString $container_type_part
     */
    public static function isContainedBy(
        Codebase $codebase,
        Scalar $input_type_part,
        Scalar $container_type_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result = null,
    ): bool {
        if ($container_type_part instanceof TLiteralClassString
            && $input_type_part instanceof TLiteralClassString
        ) {
            return $container_type_part->value === $input_type_part->value;
        }

        if ($container_type_part instanceof TTemplateParamClass
            && $input_type_part::class === TClassString::class
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
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
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        $fake_container_object = $container_type_part instanceof TClassString
            && $container_type_part->as_type
            ? $container_type_part->as_type
            : new TNamedObject(
                $container_type_part instanceof TClassString
                    ? $container_type_part->as
                    : $container_type_part->value,
            );

        $fake_input_object = $input_type_part instanceof TClassString
            && $input_type_part->as_type
            ? $input_type_part->as_type
            : new TNamedObject(
                $input_type_part instanceof TClassString
                    ? $input_type_part->as
                    : $input_type_part->value,
            );

        $isContainedBy = AtomicTypeComparator::isContainedBy(
            $codebase,
            $fake_input_object,
            $fake_container_object,
            $allow_interface_equality,
            false,
            $atomic_comparison_result,
        );

        if ($atomic_comparison_result
            && $atomic_comparison_result->replacement_atomic_type instanceof TNamedObject
        ) {
            $atomic_comparison_result->replacement_atomic_type = new TClassString(
                'object',
                $atomic_comparison_result->replacement_atomic_type,
            );
        }

        return $isContainedBy;
    }
}
