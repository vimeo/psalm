<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Issue\CodeIssue;

final class BeforeAddIssueEvent
{
    private CodeIssue $issue;

    private bool $fixable;

    /** @internal */
    public function __construct(CodeIssue $issue, bool $fixable)
    {
        $this->issue = $issue;
        $this->fixable = $fixable;
    }

    public function getIssue(): CodeIssue
    {
        return $this->issue;
    }

    public function isFixable(): bool
    {
        return $this->fixable;
    }
}
