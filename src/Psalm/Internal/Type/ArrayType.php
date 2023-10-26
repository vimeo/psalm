<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Union;

use function count;

/**
 * @internal
 */
final class ArrayType
{
    public Union $key;

    public Union $value;

    public bool $is_list;

    public ?int $count = null;

    public function __construct(Union $key, Union $value, bool $is_list, ?int $count)
    {
        $this->key = $key;
        $this->value = $value;
        $this->is_list = $is_list;
        $this->count = $count;
    }

    /**
     * @return (
     *     $type is TArrayKey ? self : (
     *         $type is TArray ? self : null
     *     )
     * )
     */
    public static function infer(Atomic $type): ?self
    {
        if ($type instanceof TKeyedArray) {
            $count = null;
            if ($type->isSealed()) {
                $count = count($type->properties);
            }

            return new self(
                $type->getGenericKeyType(),
                $type->getGenericValueType(),
                $type->is_list,
                $count,
            );
        }

        if ($type instanceof TNonEmptyArray) {
            return new self(
                $type->type_params[0],
                $type->type_params[1],
                false,
                $type->count,
            );
        }

        if ($type instanceof TArray) {
            $empty = $type->isEmptyArray();
            return new self(
                $type->type_params[0],
                $type->type_params[1],
                false,
                $empty?0:null,
            );
        }

        return null;
    }
}
