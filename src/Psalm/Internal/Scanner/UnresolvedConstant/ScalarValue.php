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
    public string|int|float|bool|null $value = null;

    public function __construct(string|int|float|bool|null $value)
    {
        $this->value = $value;
    }
}
