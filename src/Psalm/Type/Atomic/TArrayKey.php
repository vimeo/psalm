<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Type\Atomic;

/**
 * Denotes the `array-key` type, used for something that could be the offset of an `array`.
 */
class TArrayKey extends TScalar
{
    protected const SUPERTYPES = parent::SUPERTYPES + [
        self::class => true,
    ];

    public function __toString(): string
    {
        return 'array-key';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'array-key';
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

    /**
     * @param array<lowercase-string, string> $aliased_classes
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        return $use_phpdoc_format ? '(int|string)' : 'array-key';
    }

    protected function isSubtypeOf(
        Atomic $other,
        Codebase $codebase,
        bool $allow_interface_equality = false,
        bool $allow_int_to_float_coercion = true,
        ?TypeComparisonResult $type_comparison_result = null
    ): bool {
        if (parent::isSubtypeOf(
            $other,
            $codebase,
            $allow_interface_equality,
            $allow_int_to_float_coercion,
            $type_comparison_result
        )) {
            return true;
        }

        if ($type_comparison_result !== null && ($other instanceof TInt || $other instanceof TString)) {
            $type_comparison_result->type_coerced_from_mixed = true;
        }

        return false;
    }
}
