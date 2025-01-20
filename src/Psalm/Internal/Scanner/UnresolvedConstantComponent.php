<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
abstract class UnresolvedConstantComponent
{
    use ImmutableNonCloneableTrait;
}
