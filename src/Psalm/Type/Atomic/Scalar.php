<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
abstract class Scalar extends Atomic
{
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return true;
    }
}
