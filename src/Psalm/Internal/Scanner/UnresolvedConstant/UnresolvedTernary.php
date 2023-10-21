<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
final class UnresolvedTernary extends UnresolvedConstantComponent
{
    use ImmutableNonCloneableTrait;

    public function __construct(
        public readonly UnresolvedConstantComponent $cond,
        public readonly ?UnresolvedConstantComponent $if,
        public readonly UnresolvedConstantComponent $else,
    ) {
    }
}
