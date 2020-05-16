<?php
namespace Psalm;

use function array_filter;
use Psalm\Internal\Analyzer\IssueData;

abstract class Report
{
    const TYPE_COMPACT = 'compact';
    const TYPE_CONSOLE = 'console';
    const TYPE_PYLINT = 'pylint';
    const TYPE_JSON = 'json';
    const TYPE_JSON_SUMMARY = 'json-summary';
    const TYPE_SONARQUBE = 'sonarqube';
    const TYPE_EMACS = 'emacs';
    const TYPE_XML = 'xml';
    const TYPE_JUNIT = 'junit';
    const TYPE_CHECKSTYLE = 'checkstyle';
    const TYPE_TEXT = 'text';
    const TYPE_GITHUB_ACTIONS = 'github';

    const SUPPORTED_OUTPUT_TYPES = [
        self::TYPE_COMPACT,
        self::TYPE_CONSOLE,
        self::TYPE_PYLINT,
        self::TYPE_JSON,
        self::TYPE_JSON_SUMMARY,
        self::TYPE_SONARQUBE,
        self::TYPE_EMACS,
        self::TYPE_XML,
        self::TYPE_JUNIT,
        self::TYPE_CHECKSTYLE,
        self::TYPE_TEXT,
        self::TYPE_GITHUB_ACTIONS,
    ];

    /**
     * @var array<int, IssueData>
     */
    protected $issues_data;

    /** @var array<string, int> */
    protected $fixable_issue_counts;

    /** @var bool */
    protected $use_color;

    /** @var bool */
    protected $show_snippet;

    /** @var bool */
    protected $show_info;

    /** @var bool */
    protected $pretty;

    /** @var int */
    protected $mixed_expression_count;

    /** @var int */
    protected $total_expression_count;

    /**
     * @param array<int, IssueData> $issues_data
     * @param array<string, int> $fixable_issue_counts
     * @param bool $use_color
     * @param bool $show_snippet
     * @param bool $show_info
     */
    public function __construct(
        array $issues_data,
        array $fixable_issue_counts,
        Report\ReportOptions $report_options,
        int $mixed_expression_count = 1,
        int $total_expression_count = 1
    ) {
        if (!$report_options->show_info) {
            $this->issues_data = array_filter(
                $issues_data,
                function ($issue_data) : bool {
                    return $issue_data->severity !== Config::REPORT_INFO;
                }
            );
        } else {
            $this->issues_data = $issues_data;
        }
        $this->fixable_issue_counts = $fixable_issue_counts;

        $this->use_color = $report_options->use_color;
        $this->show_snippet = $report_options->show_snippet;
        $this->show_info = $report_options->show_info;
        $this->pretty = $report_options->pretty;

        $this->mixed_expression_count = $mixed_expression_count;
        $this->total_expression_count = $total_expression_count;
    }

    /**
     * @return string
     */
    abstract public function create(): string;
}
