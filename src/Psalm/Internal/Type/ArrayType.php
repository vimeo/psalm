<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

/**
 * @internal
 */
class ArrayType
{
    public Union $key;

    public Union $value;

    public bool $is_list;

    public function __construct(Union $key, Union $value, bool $is_list)
    {
        $this->key = $key;
        $this->value = $value;
        $this->is_list = $is_list;
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
            return new self(
                $type->getGenericKeyType(),
                $type->getGenericValueType(),
                $type->is_list,
            );
        }

        if ($type instanceof TArray) {
            return new self(
                $type->type_params[0],
                $type->type_params[1],
                false,
            );
        }

        return null;
    }
}
