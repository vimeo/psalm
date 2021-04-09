<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Type\Atomic;
use Psalm\Type\Union;
use function get_class;
use function preg_quote;
use function preg_replace;
use function stripos;
use function strpos;
use function strtolower;

/**
 * Denotes a specific class string, generated by expressions like `A::class`.
 */
class TLiteralClassString extends TLiteralString
{
    protected const SUPERTYPES = parent::SUPERTYPES + [
        self::class => true,
        TNonEmptyString::class => true,
        TNonFalsyString::class => true,
    ];

    /**
     * @param string $value string
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return 'class-string';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'class-string(' . $this->value . ')';
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
    ): string {
        return 'string';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }

    public function getId(bool $nested = false): string
    {
        return $this->value . '::class';
    }

    public function getAssertionString(bool $exact = false): string
    {
        return $this->getKey();
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
        if ($use_phpdoc_format) {
            return 'string';
        }

        if ($this->value === 'static') {
            return 'static::class';
        }

        if ($this->value === $this_class) {
            return 'self::class';
        }

        if ($namespace && stripos($this->value, $namespace . '\\') === 0) {
            return preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $this->value
            ) . '::class';
        }

        if (!$namespace && strpos($this->value, '\\') === false) {
            return $this->value . '::class';
        }

        if (isset($aliased_classes[strtolower($this->value)])) {
            return $aliased_classes[strtolower($this->value)] . '::class';
        }

        return '\\' . $this->value . '::class';
    }

    public function getConstraintType(): TNamedObject
    {
        return new TNamedObject($this->value);
    }

    protected function isSubtypeOf(
        Atomic $other,
        Codebase $codebase,
        bool $allow_interface_equality = false,
        bool $allow_int_to_float_coercion = true,
        ?TypeComparisonResult $type_comparison_result = null
    ): bool {
        if ((get_class($other) === TClassString::class || get_class($other) === TLiteralClassString::class)
            && $this->getConstraintType()->isSubtypeOf(
                $other->getConstraintType(),
                $codebase,
                $allow_interface_equality,
                $allow_int_to_float_coercion
            )
        ) {
            return true;
        }

        if ($other instanceof TDependentGetClass) {
            return UnionTypeComparator::isContainedBy(
                $codebase,
                new Union([$this->getConstraintType()]),
                $other->as_type
            );
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
