<?php

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
