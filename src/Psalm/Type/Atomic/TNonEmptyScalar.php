<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

class TNonEmptyScalar extends TScalar
{
    public function getId(bool $nested = false): string
    {
        return 'non-empty-scalar';
    }
}
