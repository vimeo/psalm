<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\AfterClassLikeVisitEvent;

interface AfterClassLikeVisitInterface
{
    /**
     * @return void
     */
    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event);
}
