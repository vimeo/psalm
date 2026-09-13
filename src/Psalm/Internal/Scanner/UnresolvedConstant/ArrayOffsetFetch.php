<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @internal
 * @psalm-immutable
 */
final class ArrayOffsetFetch extends UnresolvedConstantComponent
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly UnresolvedConstantComponent $array,
        public readonly UnresolvedConstantComponent $offset,
    ) {
    }
}
