<?php

declare(strict_types=1);

namespace Psalm;

use Psalm\Internal\Analyzer\IssueData;
use Psalm\Report\ReportOptions;

use function array_filter;
use function htmlspecialchars;

use const ENT_QUOTES;
use const ENT_XML1;

abstract class Report
{
    final public const TYPE_COMPACT = 'compact';
    final public const TYPE_CONSOLE = 'console';
    final public const TYPE_PYLINT = 'pylint';
    final public const TYPE_JSON = 'json';
    final public const TYPE_JSON_SUMMARY = 'json-summary';
    final public const TYPE_SONARQUBE = 'sonarqube';
    final public const TYPE_EMACS = 'emacs';
    final public const TYPE_XML = 'xml';
    final public const TYPE_JUNIT = 'junit';
    final public const TYPE_CHECKSTYLE = 'checkstyle';
    final public const TYPE_TEXT = 'text';
    final public const TYPE_GITHUB_ACTIONS = 'github';
    final public const TYPE_PHP_STORM = 'phpstorm';
    final public const TYPE_SARIF = 'sarif';
    final public const TYPE_CODECLIMATE = 'codeclimate';
    final public const TYPE_COUNT = 'count';
    final public const TYPE_BY_ISSUE_LEVEL = 'by-issue-level';

    /**
     * @var array<int, IssueData>
     */
    protected array $issues_data;

    protected bool $use_color;

    protected bool $show_snippet;

    protected bool $show_info;

    protected bool $pretty;

    protected bool $in_ci;

    /**
     * @param array<int, IssueData> $issues_data
     * @param array<string, int> $fixable_issue_counts
     */
    public function __construct(
        array $issues_data,
        protected array $fixable_issue_counts,
        ReportOptions $report_options,
        protected int $mixed_expression_count = 1,
        protected int $total_expression_count = 1,
    ) {
        if (!$report_options->show_info) {
            $this->issues_data = array_filter(
                $issues_data,
                static fn(IssueData $issue_data): bool => $issue_data->severity !== IssueData::SEVERITY_INFO,
            );
        } else {
            $this->issues_data = $issues_data;
        }

        $this->use_color = $report_options->use_color;
        $this->show_snippet = $report_options->show_snippet;
        $this->show_info = $report_options->show_info;
        $this->pretty = $report_options->pretty;
        $this->in_ci = $report_options->in_ci;
    }

    protected function xmlEncode(string $data): string
    {
        return htmlspecialchars($data, ENT_XML1 | ENT_QUOTES);
    }

    abstract public function create(): string;
}
