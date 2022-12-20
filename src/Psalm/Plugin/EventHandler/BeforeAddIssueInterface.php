<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\BeforeAddIssueEvent;

interface BeforeAddIssueInterface
{
    /**
     * Called before adding a code issue.
     *
     * Note that event handlers are called in the order they were registered, and thus
     * the handler registered earlier may prevent subsequent handlers from running by
     * returning a boolean value.
     *
     * @return null|bool $event How and whether to continue:
     *  + `null` continues with next event handler
     *  + `true` stops event handling & keeps issue
     *  + `false` stops event handling & ignores issue
     */
    public static function beforeAddIssue(BeforeAddIssueEvent $event): ?bool;
}
