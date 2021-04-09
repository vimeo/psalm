<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Type\Atomic;
use function get_class;

/**
 * Denotes an integer value where the exact numeric value is known.
 */
class TLiteralInt extends TInt implements TILiteral
{
    /** @var int */
    public $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'int(' . $this->value . ')';
    }

    public function getId(bool $nested = false): string
    {
        return (string) $this->value;
    }

    public function getAssertionString(bool $exact = false): string
    {
        return 'int(' . $this->value . ')';
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

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        return $use_phpdoc_format ? 'int' : (string) $this->value;
    }

    protected function isSubtypeOf(
        Atomic $other,
        Codebase $codebase,
        bool $allow_interface_equality = false,
        bool $allow_int_to_float_coercion = true,
        ?TypeComparisonResult $type_comparison_result = null
    ): bool {
        if (get_class($other) === TLiteralInt::class) {
            return $this->value === $other->value;
        }

        if (get_class($other) === TPositiveInt::class) {
            return $this->value > 0;
        }

        if (get_class($other) === TDependentListKey::class) {
            return $this->value >= 0;
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
