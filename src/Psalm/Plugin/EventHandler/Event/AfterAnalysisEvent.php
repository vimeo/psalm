<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\SourceControl\SourceControlInfo;

final class AfterAnalysisEvent
{
    /**
     * Called after analysis is complete
     *
     * @param array<string, list<IssueData>> $issues where string key is a filepath
     * @internal
     */
    public function __construct(
        private readonly Codebase $codebase,
        private readonly array $issues,
        private readonly array $build_info,
        private readonly ?SourceControlInfo $source_control_info = null,
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
}
