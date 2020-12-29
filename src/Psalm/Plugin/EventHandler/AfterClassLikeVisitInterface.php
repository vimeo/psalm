<?php
namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;

interface AfterClassLikeVisitInterface
{
    /**
     * @return void
     */
    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event);
}
