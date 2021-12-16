<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult2;
use Psalm\Type\Atomic;

use function get_class;

/**
 * Represents a non-empty list
 */
class TNonEmptyList extends TList
{
    /**
     * @var int|null
     */
    public $count;

    public const KEY = 'non-empty-list';

    public function getAssertionString(bool $exact = false): string
    {
        return 'non-empty-list';
    }

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
            case self::class:
                return $this->type_param->containedBy($other->type_param, $codebase);
            case TArray::class:
            case TNonEmptyArray::class:
            case TIterable::class:
                // TODO use TDependentListKey?
                return (new TInt())->containedBy($other->type_params[0], $codebase)->and(
                    $this->type_param->containedBy($other->type_params[1], $codebase)
                );
            case TKeyedArray::class:
                if ($other->is_list
                    && $other->sealed
                    && $this->count >= count($other->properties)
                    && $other->getGenericValueType()->containedBy($this->type_param)->result
                ) {
                    return TypeComparisonResult2::true();
                }
        }

        return parent::containedByAtomic($other, $codebase);
    }
}
