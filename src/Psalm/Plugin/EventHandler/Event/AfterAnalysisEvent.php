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
        public readonly Codebase $codebase,
        public readonly array $issues,
        public readonly array $build_info,
        public readonly ?SourceControlInfo $source_control_info = null,
    ) {
    }
}
