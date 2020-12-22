<?php

namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\StringInterpreterEvent;
use Psalm\Type;

interface StringInterpreterInterface
{
    /**
     * Called after a statement has been checked
     */
    public static function getTypeFromValue(StringInterpreterEvent $event): ?Type\Atomic\TLiteralString;
}
