<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * @psalm-immutable
 * @internal
 */
final class ClosureValue extends UnresolvedConstantComponent
{
    public function __construct(public readonly string $file_path, public readonly string $closure_id)
    {
    }
}
