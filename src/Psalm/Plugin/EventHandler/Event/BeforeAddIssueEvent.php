<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;
use Psalm\Issue\CodeIssue;

final class BeforeAddIssueEvent
{
    /** @internal */
    public function __construct(
        public readonly CodeIssue $issue,
        public readonly bool $fixable,
        public readonly Codebase $codebase,
    ) {
    }
}