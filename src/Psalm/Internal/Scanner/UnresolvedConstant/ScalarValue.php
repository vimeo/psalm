<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
final class ScalarValue extends UnresolvedConstantComponent
{
    public function __construct(public readonly string|int|float|bool|null $value)
    {
    }
}
