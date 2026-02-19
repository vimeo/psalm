<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;
use Psalm\Issue\CodeIssue;

/**
 * @psalm-immutable
 */
final class BeforeAddIssueEvent
{
    /**
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly CodeIssue $issue,
        private readonly bool $fixable,
        private readonly Codebase $codebase,
    ) {
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
