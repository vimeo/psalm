<?php
namespace Psalm\Type\Atomic;

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
}
