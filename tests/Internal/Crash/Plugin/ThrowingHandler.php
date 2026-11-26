<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Crash\Plugin;

use LogicException;
use Override;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;

/**
 * A hook of the plugin package in this directory that throws
 *
 * @internal
 */
final class ThrowingHandler implements AfterCodebasePopulatedInterface
{
    public static ?LogicException $thrown = null;

    #[Override]
    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
    {
        throw self::$thrown = new LogicException('the handler failed');
    }
}
