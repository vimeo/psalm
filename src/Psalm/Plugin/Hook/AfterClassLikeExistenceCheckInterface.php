<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\AfterClassLikeExistenceCheckEvent;

interface AfterClassLikeExistenceCheckInterface
{
    public static function afterClassLikeExistenceCheck(AfterClassLikeExistenceCheckEvent $event): void;
}
