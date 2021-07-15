<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TInterfaceString;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTemplateParamInterface;

use function get_class;

/**
 * @internal
 */
class ClassLikeStringComparator
{
    /**
     * @param TInterfaceString|TClassString|TLiteralClassString $input_type_part
     * @param TInterfaceString|TClassString|TLiteralClassString $container_type_part
     */
    public static function isContainedBy(
        Codebase $codebase,
        Scalar $input_type_part,
        Scalar $container_type_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result = null
    ) : bool {
        if ($container_type_part instanceof TLiteralClassString
            && $input_type_part instanceof TLiteralClassString
        ) {
            return $container_type_part->value === $input_type_part->value;
        }

        if (($container_type_part instanceof TTemplateParamClass
                || $container_type_part instanceof TTemplateParamInterface)
            && get_class($input_type_part) === TClassString::class
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
            return !$input_type_part instanceof TInterfaceString
                && (!$input_type_part instanceof TLiteralClassString
                    || !$codebase->classOrInterfaceExists($input_type_part->value)
                    || $codebase->classExists($input_type_part->value));
        }

        if ($container_type_part instanceof TInterfaceString
            && $container_type_part->as === 'object'
            && !$container_type_part->as_type
        ) {
            return !$input_type_part instanceof TClassString
                && (!$input_type_part instanceof TLiteralClassString
                    || !$codebase->classOrInterfaceExists($input_type_part->value)
                    || $codebase->interfaceExists($input_type_part->value));
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

        if ($input_type_part instanceof TLiteralClassString
            && $codebase->classOrInterfaceExists($input_type_part->value)
        ) {
            if (($container_type_part instanceof TClassString
                    && !$codebase->classExists($input_type_part->value))
                || ($container_type_part instanceof TInterfaceString
                    && !$codebase->interfaceExists($input_type_part->value))
            ) {
                return false;
            }
        }

        if ($container_type_part instanceof TClassString || $container_type_part instanceof TInterfaceString) {
            $fake_container_object = $container_type_part->as_type ?: new TNamedObject($container_type_part->as);
        } else {
            $fake_container_object = new TNamedObject($container_type_part->value);
        }

        if ($input_type_part instanceof TClassString || $input_type_part instanceof TInterfaceString) {
            $fake_input_object = $input_type_part->as_type ?: new TNamedObject($input_type_part->as);
        } else {
            $fake_input_object = new TNamedObject($input_type_part->value);
        }

        return AtomicTypeComparator::isContainedBy(
            $codebase,
            $fake_input_object,
            $fake_container_object,
            $allow_interface_equality,
            false,
            $atomic_comparison_result
        );
    }
}
