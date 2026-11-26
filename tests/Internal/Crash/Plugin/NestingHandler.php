<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Crash\Plugin;

use Override;
use Psalm\Internal\EventDispatcher;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Plugin\EventHandler\Event\StringInterpreterEvent;

/**
 * A hook that dispatches the hooks of another dispatcher
 *
 * @internal
 */
final class NestingHandler implements AfterCodebasePopulatedInterface
{
    public static ?EventDispatcher $dispatcher = null;

    #[Override]
    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
    {
        self::$dispatcher?->dispatchStringInterpreter(new StringInterpreterEvent('x', $event->getCodebase()));
    }
}
