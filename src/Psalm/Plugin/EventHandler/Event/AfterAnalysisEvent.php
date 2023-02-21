<?php

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\SourceControl\SourceControlInfo;

final class AfterAnalysisEvent
{
    private Codebase $codebase;
    /**
     * @var IssueData[][]
     */
    private array $issues;
    private array $build_info;
    private ?SourceControlInfo $source_control_info;

    /**
     * Called after analysis is complete
     *
     * @param array<string, list<IssueData>> $issues
     * @internal
     */
    public function __construct(
        Codebase $codebase,
        array $issues,
        array $build_info,
        ?SourceControlInfo $source_control_info = null
    ) {
        $this->codebase = $codebase;
        $this->issues = $issues;
        $this->build_info = $build_info;
        $this->source_control_info = $source_control_info;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }

    /**
     * @return IssueData[][]
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
