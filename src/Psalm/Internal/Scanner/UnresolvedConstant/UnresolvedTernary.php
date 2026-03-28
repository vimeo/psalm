<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @internal
 * @psalm-immutable
 */
final class UnresolvedTernary extends UnresolvedConstantComponent
{
    use ImmutableNonCloneableTrait;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly UnresolvedConstantComponent $cond,
        public readonly ?UnresolvedConstantComponent $if,
        public readonly UnresolvedConstantComponent $else,
    ) {
    }
}
