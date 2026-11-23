<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Perf;

/**
 * @internal
 * @psalm-pure
 */
class HookHandlerBase
{
    /**
     * @psalm-pure
     */
    public static function handle(object $event): int
    {
        return 1;
    }
}
