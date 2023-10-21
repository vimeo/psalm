<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
final class ClassConstant extends UnresolvedConstantComponent
{
    public function __construct(public readonly string $fqcln, public readonly string $name)
    {
    }
}
