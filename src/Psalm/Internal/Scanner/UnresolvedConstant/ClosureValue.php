<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * A `static function () {}` in a constant expression, by the id it has in the storage of its file
 *
 * @internal
 * @psalm-immutable
 */
final class ClosureValue extends UnresolvedConstantComponent
{
    /**
     * @param non-empty-lowercase-string $closure_id
     * @psalm-mutation-free
     */
    public function __construct(public readonly string $file_path, public readonly string $closure_id)
    {
    }
}
