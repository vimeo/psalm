<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @internal
 * @psalm-immutable
 */
final class Constant extends UnresolvedConstantComponent
{
    /**
     * @param int $name interned constant name
     * @psalm-mutation-free
     */
    public function __construct(public readonly int $name, public readonly bool $is_fully_qualified)
    {
    }
}
