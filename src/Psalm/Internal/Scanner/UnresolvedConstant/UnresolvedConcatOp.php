<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
class UnresolvedConcatOp extends UnresolvedBinaryOp
{
    use ImmutableNonCloneableTrait;
}
