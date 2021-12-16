<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult2;
use Psalm\Type\Atomic;

use function get_class;

/**
 * Denotes a floating point value where the exact numeric value is known.
 */
class TLiteralFloat extends TFloat
{
    /** @var float */
    public $value;

    public function __construct(float $value)
    {
        $this->value = $value;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'float(' . $this->value . ')';
    }

    public function getId(bool $nested = false): string
    {
        return 'float(' . $this->value . ')';
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
        return 'float';
    }

    /**
     * @psalm-mutation-free
     */
    protected function containedByAtomic(
        Atomic $other,
        ?Codebase $codebase
        // bool $allow_interface_equality = false,
    ): TypeComparisonResult2 {
        $result = parent::containedByAtomic($other, $codebase);

        switch (get_class($other)) {
            case self::class:
                $result->result = $result->result_with_coercion = $this->value === $other->value;
                break;
            case TLiteralInt::class:
            case TLiteralString::class:
                $result->result_with_coercion = $this->value == $other->value;
                break;
            case TPositiveInt::class:
                $result->result_with_coercion = $this->value > 0 && ((int) $this->value) == $this->value;
                break;
            case TDependentListKey::class:
                $result->result_with_coercion = $this->value >= 0 && ((int) $this->value) == $this->value;
                break;
            case TInt::class:
                $result->result_with_coercion = ((int) $this->value) == $this->value;
                break;
        }

        return $result;
    }
}
