<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;
use Psalm\Issue\CodeIssue;

final class BeforeAddIssueEvent
{
    private CodeIssue $issue;
    private bool $fixable;
    private Codebase $codebase;

    /** @internal */
    public function __construct(CodeIssue $issue, bool $fixable, Codebase $codebase)
    {
        $this->issue = $issue;
        $this->fixable = $fixable;
        $this->codebase = $codebase;
    }

    public function getIssue(): CodeIssue
    {
        return $this->issue;
    }

    public function isFixable(): bool
    {
        return $this->fixable;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }
}
