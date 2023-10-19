<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
final class KeyValuePair extends UnresolvedConstantComponent
{
    public function __construct(public ?UnresolvedConstantComponent $key, public UnresolvedConstantComponent $value)
    {
    }
}
