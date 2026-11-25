<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\EventHandler\TaintedCall;

use Override;
use Psalm\Plugin\EventHandler\AddCallTaintsInterface;
use Psalm\Plugin\EventHandler\Event\AddRemoveCallTaintsEvent;

use function strtolower;

/**
 * Records the call taint events dispatched, as `function_id` (what the call returns) or `function_id#offset`
 */
final class CallTaintsRecorder implements AddCallTaintsInterface
{
    /** @var list<string> */
    private static array $events = [];

    /**
     * @return list<string> the events recorded since the last call
     */
    public static function flush(): array
    {
        $events = self::$events;
        self::$events = [];

        return $events;
    }

    #[Override]
    public static function addCallTaints(AddRemoveCallTaintsEvent $event): int
    {
        $offset = $event->getArgumentOffset();
        self::$events[] = strtolower($event->getFunctionId()) . ($offset === null ? '' : '#' . $offset);

        return 0;
    }
}
