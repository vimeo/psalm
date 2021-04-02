<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Exception\InvalidConstraintException;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Type\Atomic;
use function implode;
use function is_int;

/**
 * Denotes the `int` type, where the exact value is unknown.
 */
class TInt extends Scalar implements IConstrainableType
{
    /**
     * Minimum allowed value
     *
     * @var int|null
     */
    public $min;

    /**
     * Maximum allowed value
     *
     * @var int|null
     */
    public $max;

    public function __toString(): string
    {
        return 'int';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'int';
    }

    public function getId(bool $nested = false): string
    {
        if ($this->min === null && $this->max === null) {
            return 'int';
        }

        if ($this->min === $this->max) {
            // Literal int
            return (string) $this->min;
        }

        $constraints = [];
        if ($this->min !== null) {
            $constraints[] = 'min=' . $this->min;
        }
        if ($this->max !== null) {
            $constraints[] = 'max=' . $this->max;
        }
        return 'int(' . implode(', ', $constraints) . ')';
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
     * @param mixed $value
     */
    public function setConstraint(?string $name, $value): void
    {
        if (!is_int($value)) {
            throw new InvalidConstraintException("int $name constraint must be an integer");
        }
        if ($name === null) {
            $this->min = $this->max = $value;
        } elseif ($name === 'min') {
            $this->min = $value;
        } elseif ($name === 'max') {
            $this->max = $value;
        } else {
            throw new InvalidConstraintException("int doesn't have constraint $name");
        }
    }

    public function isSupertypeOf(
        Atomic $other,
        Codebase $_codebase,
        bool $_allow_interface_equality = false,
        bool $_allow_float_int_equality = true,
        ?TypeComparisonResult $type_comparison_result = null
    ): bool {
        if (!$other instanceof TInt) {
            return false;
        }
        if ($other instanceof TLiteralInt) {
            $other->min = $other->max = $other->value;
        }
        if ($this instanceof TLiteralInt) {
            $this->min = $this->max = $this->value;
        }
        if ($other instanceof TPositiveInt) {
            $other->min = 1;
        }
        if ($this instanceof TPositiveInt) {
            $this->min = 1;
        }

        if (($this->min === null || $other->min !== null && $this->min <= $other->min)
            && ($this->max === null || $other->max !== null && $this->max >= $other->max)
        ) {
            return true;
        }

        if ($type_comparison_result
            // A literal can't be coerced to another literal
            && !($this instanceof TLiteralInt && $other instanceof TLiteralInt)
        ) {
            $type_comparison_result->type_coerced = true;
            $type_comparison_result->type_coerced_from_scalar = true;
        }

        return false;
    }
}
