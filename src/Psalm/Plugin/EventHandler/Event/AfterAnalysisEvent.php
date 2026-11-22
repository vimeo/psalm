<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\SourceControl\SourceControlInfo;

/**
 * @psalm-immutable
 * @api
 */
final class AfterAnalysisEvent
{
    /**
     * Called after analysis is complete
     *
     * @param array<string, list<IssueData>> $issues where string key is a filepath
     * @param array<string, int<1, max>> $baselined_issue_counts
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Codebase $codebase,
        private readonly array $issues,
        private readonly array $build_info,
        private readonly ?SourceControlInfo $source_control_info = null,
        private readonly array $baselined_issue_counts = [],
    ) {
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }

    /**
     * @return array<string, list<IssueData>> where string key is a filepath
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    public function getBuildInfo(): array
    {
        return $this->build_info;
    }

    public function getSourceControlInfo(): ?SourceControlInfo
    {
        return $this->source_control_info;
    }

    /**
     * How many issues the baseline covers, by issue type, sorted by type. They are among the issues of
     * getIssues(), reported as info.
     *
     * @return array<string, int<1, max>>
     */
    public function getBaselinedIssueCounts(): array
    {
        return $this->baselined_issue_counts;
    }
}
