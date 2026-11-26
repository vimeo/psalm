<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Crash\Plugin;

use LogicException;

/**
 * Code of the plugin package that runs outside a hook
 *
 * @internal
 * @psalm-pure
 */
final class PluginCode
{
    /**
     * @psalm-pure
     */
    public static function fail(): never
    {
        throw new LogicException('the plugin failed');
    }
}
