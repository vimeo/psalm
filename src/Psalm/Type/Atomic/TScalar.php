<?php
namespace Psalm\Type\Atomic;

/**
 * Denotes the `scalar` super type (which can also result from an `is_scalar` check).
 * This type encompasses `float`, `int`, `bool` and `string`.
 */
class TScalar extends Scalar
{
    public function __toString(): string
    {
        return 'scalar';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'scalar';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string {
        return null;
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }

    // TODO this was removed on the original branch, why?
    public function getAssertionString(bool $exact = false): string
    {
        return 'scalar';
    }

    // protected function containedByAtomic(
    //     Atomic $other,
    //     Codebase $codebase,
    //     bool $allow_interface_equality = false,
    //     bool $allow_int_to_float_coercion = true,
    //     ?TypeComparisonResult $type_comparison_result = null
    // ): bool {
    //     if (parent::containedByAtomic(
    //         $other,
    //         $codebase,
    //         $allow_interface_equality,
    //         $allow_int_to_float_coercion,
    //         $type_comparison_result
    //     )) {
    //         return true;
    //     }

    //     if ($type_comparison_result !== null
    //         && $other instanceof TScalar
    //     ) {
    //         if (!$other instanceof TILiteral) {
    //             $type_comparison_result->scalar_type_match_found = true;
    //         }
    //         if ($type_comparison_result->type_coerced) {
    //             $type_comparison_result->type_coerced_from_scalar = true;
    //         }
    //     }

    //     return false;
    // }
}
