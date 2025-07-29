<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
final class Constant extends UnresolvedConstantComponent
{
    public function __construct(public readonly string $name, public readonly bool $is_fully_qualified)
    {
    }
}
