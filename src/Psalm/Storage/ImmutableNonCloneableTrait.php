<?php

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
