<?php

declare(strict_types=1);

namespace Psalm\Internal;

/**
 * What IssueBuffer::finish() knows about the run it finishes, for the after-analysis handlers
 *
 * @internal
 * @psalm-immutable
 */
final class AnalysisRunSummary
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        /**
         * How many issues the baseline covered, by issue type, sorted by type
         *
         * @var array<string, int<1, max>>
         */
        public readonly array $baselined_issue_counts,
        /** How many entries the baseline held before it covered any issue */
        public readonly int $baseline_entry_count,
        /** Whether the run was for the whole project, not for given paths */
        public readonly bool $is_full,
    ) {
    }
}
