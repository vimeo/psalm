<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @internal
 * @psalm-immutable
 */
final class UnresolvedMultiplicationOp extends UnresolvedBinaryOp
{
    use ImmutableNonCloneableTrait;
}
