<?php

declare(strict_types=1);

namespace Psalm\Storage;

/**
 * @psalm-immutable
 * @api
 */
trait ImmutableNonCloneableTrait
{
    /**
     * @psalm-mutation-free
     */
    private function __clone()
    {
    }
}
