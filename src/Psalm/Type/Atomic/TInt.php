<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes the `int` type, where the exact value is unknown.
 */
class TInt extends Scalar
{
    /** @var array<class-string<Atomic>, true> */
    protected const CONTAINED_BY = parent::CONTAINED_BY + [
        self::class => true,
        TNumeric::class => true,
        TArrayKey::class => true,
    ];

    protected const COERCIBLE_TO = parent::COERCIBLE_TO + [
        TFloat::class => true,
        TString::class => true,
        TNumericString::class => true,
        TNonEmptyString::class => true,
        TLowercaseString::class => true,
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

    // protected function containedByAtomic(
    //     Atomic $other,
    //     Codebase $codebase,
    //     bool $allow_interface_equality = false,
    //     bool $allow_int_to_float_coercion = true,
    //     ?TypeComparisonResult $type_comparison_result = null
    // ): bool {
    //     if (get_class($other) === TFloat::class && $allow_int_to_float_coercion) {
    //         return true;
    //     }

    //     return parent::containedByAtomic(
    //         $other,
    //         $codebase,
    //         $allow_interface_equality,
    //         $allow_int_to_float_coercion,
    //         $type_comparison_result
    //     );
    // }
}
