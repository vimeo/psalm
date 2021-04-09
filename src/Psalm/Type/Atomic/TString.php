<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Type\Atomic;

/**
 * Denotes the `string` type, where the exact value is unknown.
 */
class TString extends TScalar
{
    protected const SUPERTYPES = parent::SUPERTYPES + [
        self::class => true,
        TArrayKey::class => true,
    ];

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
        return $php_major_version >= 7 ? 'string' : null;
    }

    public function __toString(): string
    {
        return 'string';
    }

    public function getKey(bool $include_extra = true) : string
    {
        return 'string';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return true;
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

        if ($type_comparison_result !== null
            && $type_comparison_result->type_coerced
            && $other instanceof TString
        ) {
            // Type coercion between string types is allowed when doing strict comparisons.
            $type_comparison_result->type_coerced_strict = true;
        }

        return false;
    }
}
