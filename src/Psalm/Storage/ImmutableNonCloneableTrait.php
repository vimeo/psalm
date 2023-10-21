<?php

declare(strict_types=1);

namespace Psalm\Storage;

/**
 * @psalm-immutable
 */
trait ImmutableNonCloneableTrait
{
    private function __clone()
    {
    }
}
