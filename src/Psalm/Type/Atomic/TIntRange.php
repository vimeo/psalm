<?php
namespace Psalm\Type\Atomic;

use function max;
use function min;

/**
 * Denotes an interval of integers between two bounds
 */
class TIntRange extends TInt
{
    const BOUND_MIN = 'min';
    const BOUND_MAX = 'max';

    /**
     * @var int|null
     */
    public $min_bound;
    /**
     * @var int|null
     */
    public $max_bound;

    public function __construct(?int $min_bound, ?int $max_bound)
    {
        $this->min_bound = $min_bound;
        $this->max_bound = $max_bound;
    }

    public function __toString(): string
    {
        return $this->getKey();
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'int<' . ($this->min_bound ?? 'min') . ', ' . ($this->max_bound ?? 'max') . '>';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
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
     * @param array<lowercase-string, string> $aliased_classes
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        return $use_phpdoc_format ?
            'int' :
            'int<' . ($this->min_bound ?? 'min') . ', ' . ($this->max_bound ?? 'max') . '>';
    }

    public function isPositive(): bool
    {
        return $this->min_bound !== null && $this->min_bound > 0;
    }

    public function contains(int $i): bool
    {
        return
            ($this->min_bound === null && $this->max_bound === null) ||
            ($this->min_bound === null && $this->max_bound >= $i) ||
            ($this->max_bound === null && $this->min_bound <= $i) ||
            ($this->min_bound <= $i && $this->max_bound >= $i);
    }

    public static function getNewLowestBound(?int $bound1, ?int $bound2): ?int
    {
        if ($bound1 === null || $bound2 === null) {
            return null;
        }
        return min($bound1, $bound2);
    }

    public static function getNewHighestBound(?int $bound1, ?int $bound2): ?int
    {
        if ($bound1 === null || $bound2 === null) {
            return null;
        }
        return max($bound1, $bound2);
    }

    /**
     * convert any int to its equivalent in int range
     */
    public static function convertToIntRange(TInt $int_atomic): TIntRange
    {
        if ($int_atomic instanceof TPositiveInt) {
            return new TIntRange(1, null);
        }

        if ($int_atomic instanceof TLiteralInt) {
            return new TIntRange($int_atomic->value, $int_atomic->value);
        }

        return new TIntRange(null, null);
    }
}
