<?php

namespace Psalm\Type\Atomic;

use Psalm\Type;

use function array_fill;

/**
 * Denotes a list that is _also_ `callable`.
 *
 * @deprecated Will be removed in Psalm v6, please use TCallableKeyedArrays with is_list=true instead.
 * @psalm-immutable
 */
final class TCallableList extends TNonEmptyList
{
    public const KEY = 'callable-list';
    public function getKeyedArray(): TKeyedArray
    {
        if (!$this->count && !$this->min_count) {
            return new TKeyedArray(
                [$this->type_param],
                null,
                [Type::getListKey(), $this->type_param],
                true,
                $this->from_docblock,
            );
        }
        if ($this->count) {
            return new TCallableKeyedArray(
                array_fill(0, $this->count, $this->type_param),
                null,
                null,
                true,
                $this->from_docblock,
            );
        }
        return new TCallableKeyedArray(
            array_fill(0, $this->min_count, $this->type_param),
            null,
            [Type::getListKey(), $this->type_param],
            true,
            $this->from_docblock,
        );
    }
}
