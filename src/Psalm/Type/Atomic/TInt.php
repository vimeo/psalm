<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

class TInt extends Scalar
{
    public function __toString(): string
    {
        return 'int';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'int';
    }
}
