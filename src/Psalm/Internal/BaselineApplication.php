<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Psalm\Internal\Analyzer\IssueData;

/**
 * The issues as reported with a baseline, see {@see BaselineMatcher::apply()}
 *
 * @internal
 * @psalm-immutable
 */
final class BaselineApplication
{
    /**
     * @param array<string, list<IssueData>> $issues_data the issues, a copy of severity info for each the baseline
     *  covers
     * @param array<string, int<1, max>> $baselined_issue_counts how many issues the baseline covered, by type, sorted
     * @param array<string, array<string, array{o: int, s: array<int, string>}>> $remaining_baseline the entries no
     *  issue took
     */
    public function __construct(
        public readonly array $issues_data,
        public readonly array $baselined_issue_counts,
        public readonly array $remaining_baseline,
    ) {
    }
}
