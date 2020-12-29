<?php

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\StringInterpreterEvent;
use Psalm\Type;

interface StringInterpreterInterface
{
    /**
     * Called after a statement has been checked
     */
    public static function getTypeFromValue(StringInterpreterEvent $event): ?Type\Atomic\TLiteralString;
}
