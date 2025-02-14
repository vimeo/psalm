<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Override;

/**
 * @psalm-immutable
 */
trait ImmutableNonCloneableTrait
{
    #[Override]
    private function __clone()
    {
    }
}
