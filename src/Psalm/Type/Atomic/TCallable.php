<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult2;
use Psalm\Type\Atomic;

/**
 * Denotes the `callable` type. Can result from an `is_callable` check.
 */
class TCallable extends Atomic
{
    use CallableTrait {
        containedByAtomic as callableContainedByAtomic;
    }

    /**
     * @var string
     */
    public $value;

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
        return 'callable';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return $this->params === null && $this->return_type === null;
    }

    /**
     * @psalm-mutation-free
     */
    protected function containedByAtomic(
        Atomic $other,
        ?Codebase $codebase
        // bool $allow_interface_equality = false,
    ): TypeComparisonResult2 {
        if (get_class($other) === TClosure::class) {
            return TypeComparisonResult2::false();
        }

        return $this->callableContainedByAtomic($other, $codebase);
    }
}
