<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Internal\MethodIdentifier;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TDependentGetClass;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TUnknownClassString;

use function ltrim;

/**
 * @internal
 */
final class ClassLikeStringComparator
{
    /**
     * A literal string is a valid stand-in only when the class exists and satisfies the bound.
     * This must not make arbitrary text into a class-string in other contexts.
     */
    public static function canLiteralNameBeClassString(
        Codebase $codebase,
        TLiteralString $literal,
        TClassString|TDependentGetClass $class_string,
        bool $allow_interface_equality,
    ): bool {
        $class_name = ltrim($literal->value, '\\');

        if (!$codebase->classlike_storage_provider->has($class_name)
            || $codebase->classlike_storage_provider->get($class_name)->name !== $class_name
        ) {
            return false;
        }

        if ($class_string instanceof TDependentGetClass) {
            // get_class() returns the canonical class name, without a leading backslash.
            if ($literal->value !== $class_name) {
                return false;
            }

            foreach ($class_string->as_type->getAtomicTypes() as $bound) {
                if (self::classSatisfiesBound($codebase, $class_name, $bound, $allow_interface_equality)) {
                    return true;
                }
            }

            return false;
        }

        if ($class_string instanceof TUnknownClassString && $class_string->as_unknown_type !== null) {
            return self::classSatisfiesBound(
                $codebase,
                $class_name,
                $class_string->as_unknown_type,
                $allow_interface_equality,
            );
        }

        return self::isContainedBy(
            $codebase,
            new TLiteralClassString($class_name),
            $class_string,
            $allow_interface_equality,
        );
    }

    private static function classSatisfiesBound(
        Codebase $codebase,
        string $class_name,
        Atomic $bound,
        bool $allow_interface_equality,
    ): bool {
        if ($bound instanceof TCallableObject) {
            return $codebase->methodExists(new MethodIdentifier($class_name, '__invoke'));
        }

        if ($bound instanceof TObject) {
            return true;
        }

        if ($bound instanceof TNamedObject) {
            return AtomicTypeComparator::isContainedBy(
                $codebase,
                new TNamedObject($class_name),
                $bound,
                $allow_interface_equality,
            );
        }

        // A template or an anonymous structural bound cannot be established from a name alone.
        return false;
    }

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
            // a class name is not the name of an interface or of an enum
            return !$input_type_part instanceof TLiteralClassString
                || ((!$container_type_part->is_interface
                        || $codebase->classlikes->interfaceExists($input_type_part->value))
                    && (!$container_type_part->is_enum
                        || $codebase->classlikes->enumExists($input_type_part->value)));
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
