<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult2;
use Psalm\Type\Atomic;

use function get_class;

/**
 * Denotes an integer value where the exact numeric value is known.
 */
class TLiteralInt extends TInt
{
    /** @var array<class-string<Scalar>, true> */
    protected const CONTAINED_BY = parent::CONTAINED_BY + [
        TNonspecificLiteralInt::class => true,
    ];

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
            case TLiteralFloat::class:
            case TLiteralString::class:
                $result->result_with_coercion = $this->value == $other->value;
                break;
            case TPositiveInt::class:
                $result->result = $result->result_with_coercion = $this->value > 0;
                break;
            case TDependentListKey::class:
                $result->result = $result->result_with_coercion = $this->value >= 0;
                break;
        }

        return $result;
    }
}
