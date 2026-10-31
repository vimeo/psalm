<?php

declare(strict_types=1);

namespace Psalm;

use Psalm\Internal\Analyzer\IssueData;
use Psalm\Report\ReportOptions;

use function array_filter;
use function assert;
use function htmlspecialchars;
use function mb_ord;
use function ord;
use function preg_replace_callback;
use function sprintf;
use function strlen;
use function strtoupper;

use const ENT_QUOTES;
use const ENT_XML1;

/**
 * @psalm-external-mutation-free
 * @api
 */
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
    final public const TYPE_TABLE = 'table';
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
     * @psalm-mutation-free
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

    /**
     * The severity shown at the start of an issue in human-readable reports:
     * SECURITY for security issues, ERROR for other errors, and INFO otherwise.
     *
     * @psalm-mutation-free
     */
    protected function getSeverityLabel(IssueData $issue_data): string
    {
        if ($issue_data->severity !== IssueData::SEVERITY_ERROR) {
            return strtoupper($issue_data->severity);
        }

        $label = $issue_data->is_security ? 'SECURITY' : 'ERROR';

        return $this->use_color ? "\e[0;31m" . $label . "\e[0m" : $label;
    }

    /**
     * Makes text taken from the analyzed code (or from a plugin) safe to print on a terminal: C0 controls (but LF,
     * TAB and a CR followed by LF), DEL, C1 controls and bidi formatting characters become a visible `\u{NNNN}`, so
     * what a person sees is what a program reads. Use it on what a human-readable report prints, before Psalm adds
     * its own escape sequences.
     *
     * @psalm-pure
     */
    protected function escapeControlCharacters(string $text): string
    {
        return (string) preg_replace_callback(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|\r(?!\n)|\xC2[\x80-\x9F]|\xD8\x9C'
                . '|\xE2\x80[\x8E\x8F\xAA-\xAE]|\xE2\x81[\xA6-\xA9]/',
            static function (array $match): string {
                $matched = $match[0] ?? '';

                $code_point = strlen($matched) === 1 ? ord($matched) : mb_ord($matched, 'UTF-8');

                // the pattern only matches complete UTF-8 sequences
                assert($code_point !== false);

                return sprintf('\u{%04X}', $code_point);
            },
            $text,
        );
    }

    /**
     * @psalm-pure
     */
    protected function xmlEncode(string $data): string
    {
        return htmlspecialchars($data, ENT_XML1 | ENT_QUOTES);
    }

    abstract public function create(): string;

    /**
     * @return array<string, self::TYPE_*>
     * @psalm-pure
     */
    public static function getMapping(): array
    {
        return [
            'checkstyle.xml' => self::TYPE_CHECKSTYLE,
            'sonarqube.json' => self::TYPE_SONARQUBE,
            'codeclimate.json' => self::TYPE_CODECLIMATE,
            'summary.json' => self::TYPE_JSON_SUMMARY,
            'junit.xml' => self::TYPE_JUNIT,
            '.xml' => self::TYPE_XML,
            '.sarif.json' => self::TYPE_SARIF,
            '.json' => self::TYPE_JSON,
            '.txt' => self::TYPE_TEXT,
            '.emacs' => self::TYPE_EMACS,
            '.pylint' => self::TYPE_PYLINT,
            '.console' => self::TYPE_CONSOLE,
            '.sarif' => self::TYPE_SARIF,
            'count.txt' => self::TYPE_COUNT,
        ];
    }
}
