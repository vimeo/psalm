<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult2;
use Psalm\Type\Atomic;

use function get_class;

/**
 * Denotes array known to be non-empty of the form `non-empty-array<TKey, TValue>`.
 * It expects an array with two elements, both union types.
 */
class TNonEmptyArray extends TArray
{
    /**
     * @var int|null
     */
    public $count;

    /**
     * @var string
     */
    public $value = 'non-empty-array';

    /**
     * @psalm-mutation-free
     */
    protected function containedByAtomic(
        Atomic $other,
        ?Codebase $codebase
        // bool $allow_interface_equality = false,
    ): TypeComparisonResult2 {
        switch (get_class($other)) {
            case TList::class:
            case TNonEmptyList::class:
                return (TypeComparisonResult2::notTrue())->and(
                    $this->type_params[1]->containedBy($other->type_param, $codebase)
                );
            case TArray::class:
            case self::class:
            case TCallableArray::class:
            case TIterable::class:
                return $this->type_params[0]->containedBy($other->type_params[0], $codebase)->and(
                    $this->type_params[1]->containedBy($other->type_params[1], $codebase)
                );
        }

        return parent::containedByAtomic($other, $codebase);
    }
}
