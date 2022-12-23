<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
class UnresolvedMultiplicationOp extends UnresolvedBinaryOp
{
    use ImmutableNonCloneableTrait;
}
