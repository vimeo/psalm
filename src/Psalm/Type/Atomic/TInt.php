<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Type\Atomic;
use function get_class;

/**
 * Denotes the `int` type, where the exact value is unknown.
 */
class TInt extends TScalar
{
    protected const SUPERTYPES = parent::SUPERTYPES + [
        self::class => true,
        TNumeric::class => true,
        TArrayKey::class => true,
    ];

    public function __toString(): string
    {
        return 'int';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'int';
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
        return $php_major_version >= 7 ? 'int' : null;
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
        if (get_class($other) === TFloat::class && $allow_int_to_float_coercion) {
            return true;
        }

        return parent::isSubtypeOf(
            $other,
            $codebase,
            $allow_interface_equality,
            $allow_int_to_float_coercion,
            $type_comparison_result
        );
    }
}
