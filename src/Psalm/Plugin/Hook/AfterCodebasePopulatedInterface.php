<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\AfterCodebasePopulatedEvent;

interface AfterCodebasePopulatedInterface
{
    /**
     * Called after codebase has been populated
     *
     * @return void
     */
    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event);
}
