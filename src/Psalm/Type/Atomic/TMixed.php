<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult2;
use Psalm\Type\Atomic;

/**
 * Denotes the `mixed` type, used when you don’t know the type of an expression.
 */
class TMixed extends Atomic
{
    /**
     * Mixed coercion is handled specially.
     *
     * @var array<class-string<Scalar>, true>
     */
    protected const COERCIBLE_TO = [];

    /** @var bool */
    public $from_loop_isset = false;

    public function __construct(bool $from_loop_isset = false)
    {
        $this->from_loop_isset = $from_loop_isset;
    }

    public function __toString(): string
    {
        return 'mixed';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'mixed';
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
        return $php_major_version >= 8 ? 'mixed' : null;
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return $php_major_version >= 8;
    }

    public function getAssertionString(bool $exact = false): string
    {
        return 'mixed';
    }

    /**
     * @psalm-mutation-free
     */
    public function containedByAtomic(
        Atomic $other,
        ?Codebase $codebase
        // bool $allow_interface_equality = false,
    ): TypeComparisonResult2 {
        return $other instanceof TMixed ? TypeComparisonResult2::true() : TypeComparisonResult2::coercedFromMixed();
    }
}
