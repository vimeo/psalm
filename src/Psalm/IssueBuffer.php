<?php

declare(strict_types=1);

namespace Psalm;

use Psalm\CodeLocation\Raw;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\ExecutionEnvironment\BuildInfoCollector;
use Psalm\Internal\ExecutionEnvironment\GitInfoCollector;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\ConfigIssue;
use Psalm\Issue\MixedIssue;
use Psalm\Issue\TaintedInput;
use Psalm\Issue\UnusedBaselineEntry;
use Psalm\Issue\UnusedIssueHandlerSuppression;
use Psalm\Issue\UnusedPsalmSuppress;
use Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\BeforeAddIssueEvent;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\Report\ByIssueLevelAndTypeReport;
use Psalm\Report\CheckstyleReport;
use Psalm\Report\CodeClimateReport;
use Psalm\Report\CompactReport;
use Psalm\Report\ConsoleReport;
use Psalm\Report\CountReport;
use Psalm\Report\EmacsReport;
use Psalm\Report\GithubActionsReport;
use Psalm\Report\JsonReport;
use Psalm\Report\JsonSummaryReport;
use Psalm\Report\JunitReport;
use Psalm\Report\PhpStormReport;
use Psalm\Report\PylintReport;
use Psalm\Report\ReportOptions;
use Psalm\Report\SarifReport;
use Psalm\Report\SonarqubeReport;
use Psalm\Report\TableReport;
use Psalm\Report\TextReport;
use Psalm\Report\XmlReport;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use UnexpectedValueException;

use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_search;
use function array_slice;
use function array_splice;
use function array_sum;
use function array_values;
use function arsort;
use function count;
use function debug_print_backtrace;
use function dirname;
use function escapeshellarg;
use function explode;
use function file_put_contents;
use function fstat;
use function fwrite;
use function getenv;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function is_int;
use function is_string;
use function ksort;
use function max;
use function memory_get_peak_usage;
use function microtime;
use function min;
use function mkdir;
use function number_format;
use function ob_get_clean;
use function ob_start;
use function preg_match;
use function rtrim;
use function sha1;
use function sprintf;
use function str_pad;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function stream_isatty;
use function strlen;
use function trim;
use function uksort;
use function usort;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const PSALM_VERSION;
use const STDERR;
use const STDOUT;
use const STR_PAD_LEFT;

/**
 * @api
 */
final class IssueBuffer
{
    /** The error types are broken down in the summary above this many errors */
    private const ERROR_BREAKDOWN_THRESHOLD = 10;

    /** How many error types the breakdown shows */
    private const ERROR_BREAKDOWN_TYPES = 5;

    /** How many altered files the summary lists */
    private const ALTERED_FILES_SHOWN = 10;

    /**
     * @var array<string, list<IssueData>>
     */
    private static array $issues_data = [];

    /**
     * @var array<string, int>
     */
    private static array $fixable_issue_counts = [];

    private static int $error_count = 0;

    /**
     * @var array<string, bool>
     */
    private static array $emitted = [];

    private static int $recording_level = 0;

    /** @var array<int, array<int, CodeIssue>> */
    private static array $recorded_issues = [];

    /**
     * @var array<string, array<int, int>>
     */
    private static array $unused_suppressions = [];

    /**
     * @var array<string, array<int, bool>>
     */
    private static array $used_suppressions = [];

    /** @var array<array-key,mixed> */
    private static array $server = [];

    /**
     * This will add an issue to be emitted if it's not suppressed and return if it has been added
     *
     * @param string[]  $suppressed_issues
     */
    public static function accepts(CodeIssue $e, array $suppressed_issues = [], bool $is_fixable = false): bool
    {
        $config = Config::getInstance();
        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();
        $event = new BeforeAddIssueEvent($e, $is_fixable, $codebase);
        if ($config->eventDispatcher->dispatchBeforeAddIssue($event) === false) {
            return false;
        }

        if (self::isSuppressed($e, $suppressed_issues)) {
            return false;
        }

        return self::add($e, $is_fixable);
    }

    /**
     * This will add an issue to be emitted if it's not suppressed
     *
     * @param string[]  $suppressed_issues
     */
    public static function maybeAdd(CodeIssue $e, array $suppressed_issues = [], bool $is_fixable = false): void
    {
        self::accepts($e, $suppressed_issues, $is_fixable);
    }

    /**
     * This is part of the findUnusedPsalmSuppress feature
     *
     * @psalm-external-mutation-free
     */
    public static function addUnusedSuppression(
        string $file_path,
        int $offset,
        string $issue_type,
        bool $taint_analysis,
    ): void {
        // Taint issues are only computed when running taint analysis, so outside
        // of it their suppressions can never be observed as used - don't report them.
        if (!$taint_analysis && str_starts_with($issue_type, 'Tainted')) {
            return;
        }

        if (isset(self::$used_suppressions[$file_path][$offset])) {
            return;
        }

        if (!isset(self::$unused_suppressions[$file_path])) {
            self::$unused_suppressions[$file_path] = [];
        }

        self::$unused_suppressions[$file_path][$offset] = $offset + strlen($issue_type) - 1;
    }

    /**
     * This will return false if an issue is ready to be added for emission. Reasons for not returning false include:
     * - The issue is suppressed in config
     * - We're in a recording state
     * - The issue is included in the list of issues to be suppressed in param
     *
     * @param string[] $suppressed_issues
     */
    public static function isSuppressed(CodeIssue $e, array $suppressed_issues = []): bool
    {
        $config = Config::getInstance();

        $fqcn_parts = explode('\\', $e::class);
        $issue_type = array_pop($fqcn_parts);
        $file_path = $e->getFilePath();

        if (!$e instanceof ConfigIssue && !$config->reportIssueInFile($issue_type, $file_path)) {
            return true;
        }

        $suppressed_issue_position = array_search($issue_type, $suppressed_issues, true);

        if ($suppressed_issue_position !== false) {
            if (is_int($suppressed_issue_position)) {
                self::$used_suppressions[$file_path][$suppressed_issue_position] = true;
            }

            return true;
        }

        $parent_issue_type = Config::getParentIssueType($issue_type);

        if ($parent_issue_type) {
            $suppressed_issue_position = array_search($parent_issue_type, $suppressed_issues, true);

            if ($suppressed_issue_position !== false) {
                if (is_int($suppressed_issue_position)) {
                    self::$used_suppressions[$file_path][$suppressed_issue_position] = true;
                }

                return true;
            }
        }

        $suppress_all_position = $config->disable_suppress_all
            ? false
            : array_search('all', $suppressed_issues, true);

        if ($suppress_all_position !== false) {
            if (is_int($suppress_all_position)) {
                self::$used_suppressions[$file_path][$suppress_all_position] = true;
            }

            return true;
        }

        $reporting_level = $config->getReportingLevelForIssue($e);

        if ($reporting_level === Config::REPORT_SUPPRESS) {
            return true;
        }

        if ($e->code_location->getLineNumber() === -1) {
            return true;
        }

        if (self::$recording_level > 0) {
            self::$recorded_issues[self::$recording_level][] = $e;

            return true;
        }

        return false;
    }

    /**
     * Add an issue to be emitted. This method should normally not be used! Use IssueBuffer::maybeAdd instead.
     *
     * @psalm-internal Psalm\IssueBuffer
     * @psalm-internal Psalm\Type\Reconciler::getValueForKey
     * @throws  CodeException
     */
    public static function add(CodeIssue $e, bool $is_fixable = false): bool
    {
        $config = Config::getInstance();
        $project_analyzer = ProjectAnalyzer::getInstance();

        $fqcn_parts = explode('\\', $e::class);
        $issue_type = array_pop($fqcn_parts);

        if (!$project_analyzer->show_issues) {
            return false;
        }

        $is_tainted = str_starts_with($issue_type, 'Tainted');

        $reporting_level = $config->getReportingLevelForIssue($e);

        if ($reporting_level === Config::REPORT_SUPPRESS) {
            return false;
        }

        if ($config->debug_emitted_issues) {
            ob_start();
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $trace = ob_get_clean();
            $project_analyzer->progress->write("Emitting {$e->getShortLocation()} $issue_type {$e->message}\n$trace\n");
        }

        // Make issue type for trace variable specific ("Trace" => "Trace~$var").
        $trace_var = $issue_type === 'Trace' && preg_match('/^(\$.+?):/', $e->message, $m) === 1 && isset($m[1])
            ? '~' . $m[1]
            : '';

        $emitted_key = $issue_type
            . $trace_var
            . '-' . $e->getShortLocation()
            . ':' . $e->code_location->getColumn()
            . ' ' . ($e->dupe_key ?? $e->message);

        if ($reporting_level === Config::REPORT_INFO) {
            if ($is_tainted || !self::alreadyEmitted($emitted_key)) {
                self::$issues_data[$e->getFilePath()][] = $e->toIssueData(IssueData::SEVERITY_INFO);

                if ($is_fixable) {
                    self::addFixableIssue($issue_type);
                }
            }

            return false;
        }

        if ($config->throw_exception) {
            FileAnalyzer::clearCache();

            $message = $e instanceof TaintedInput
                ? $e->getJourneyMessage()
                : ($e instanceof MixedIssue
                    ? $e->getMixedOriginMessage()
                    : $e->message);

            throw new CodeException(
                $issue_type
                    . ' - ' . $e->getShortLocationWithPrevious()
                    . ':' . $e->code_location->getColumn()
                    . ' - ' . $message,
            );
        }

        if ($is_tainted || !self::alreadyEmitted($emitted_key)) {
            ++self::$error_count;
            self::$issues_data[$e->getFilePath()][] = $e->toIssueData(IssueData::SEVERITY_ERROR);

            if ($is_fixable) {
                self::addFixableIssue($issue_type);
            }
        }

        return true;
    }

    /**
     * @psalm-external-mutation-free
     */
    private static function removeRecordedIssue(string $issue_type, int $file_offset): void
    {
        $recorded_issues = self::$recorded_issues[self::$recording_level];
        $filtered_issues = [];

        foreach ($recorded_issues as $issue) {
            [$from] = $issue->code_location->getSelectionBounds();

            if ($issue::getIssueType() !== $issue_type || $from !== $file_offset) {
                $filtered_issues[] = $issue;
            }
        }

        self::$recorded_issues[self::$recording_level] = $filtered_issues;
    }

    /**
     * This will try to remove an issue that has been added for emission
     *
     * @psalm-external-mutation-free
     */
    public static function remove(string $file_path, string $issue_type, int $file_offset): void
    {
        if (self::$recording_level > 0) {
            self::removeRecordedIssue($issue_type, $file_offset);
        }

        if (!isset(self::$issues_data[$file_path])) {
            return;
        }

        $filtered_issues = [];

        foreach (self::$issues_data[$file_path] as $issue) {
            if ($issue->type !== $issue_type || $issue->from !== $file_offset) {
                $filtered_issues[] = $issue;
            }
        }

        if (empty($filtered_issues)) {
            unset(self::$issues_data[$file_path]);
        } else {
            self::$issues_data[$file_path] = $filtered_issues;
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function addFixableIssue(string $issue_type): void
    {
        if (isset(self::$fixable_issue_counts[$issue_type])) {
            self::$fixable_issue_counts[$issue_type]++;
        } else {
            self::$fixable_issue_counts[$issue_type] = 1;
        }
    }

    /**
     * @return array<string, list<IssueData>>
     * @psalm-external-mutation-free
     */
    public static function getIssuesData(): array
    {
        return self::$issues_data;
    }

    /**
     * @return list<IssueData>
     * @psalm-external-mutation-free
     */
    public static function getIssuesDataForFile(string $file_path): array
    {
        return self::$issues_data[$file_path] ?? [];
    }

    /**
     * @return array<string, int>
     * @psalm-external-mutation-free
     */
    public static function getFixableIssues(): array
    {
        return self::$fixable_issue_counts;
    }

    /**
     * @param array<string, int> $fixable_issue_counts
     * @psalm-external-mutation-free
     */
    public static function addFixableIssues(array $fixable_issue_counts): void
    {
        foreach ($fixable_issue_counts as $issue_type => $count) {
            if (isset(self::$fixable_issue_counts[$issue_type])) {
                self::$fixable_issue_counts[$issue_type] += $count;
            } else {
                self::$fixable_issue_counts[$issue_type] = $count;
            }
        }
    }

    /**
     * @return array<string, array<int, int>>
     * @psalm-external-mutation-free
     */
    public static function getUnusedSuppressions(): array
    {
        return self::$unused_suppressions;
    }

    /**
     * @return array<string, array<int, bool>>
     * @psalm-external-mutation-free
     */
    public static function getUsedSuppressions(): array
    {
        return self::$used_suppressions;
    }

    /**
     * @param array<string, array<int, int>> $unused_suppressions
     * @psalm-external-mutation-free
     */
    public static function addUnusedSuppressions(array $unused_suppressions): void
    {
        self::$unused_suppressions += $unused_suppressions;
    }

    /**
     * @param array<string, array<int, bool>> $used_suppressions
     * @psalm-external-mutation-free
     */
    public static function addUsedSuppressions(array $used_suppressions): void
    {
        foreach ($used_suppressions as $file => $offsets) {
            if (!isset(self::$used_suppressions[$file])) {
                self::$used_suppressions[$file] = $offsets;
            } else {
                self::$used_suppressions[$file] += $offsets;
            }
        }
    }

    public static function processUnusedSuppressions(FileProvider $file_provider): void
    {
        $config = Config::getInstance();

        foreach (self::$unused_suppressions as $file_path => $offsets) {
            if (!$offsets) {
                continue;
            }

            if (!$config->isInProjectDirs($file_path)) {
                continue;
            }

            $file_contents = $file_provider->getContents($file_path);

            foreach ($offsets as $start => $end) {
                if (isset(self::$used_suppressions[$file_path][$start])) {
                    continue;
                }

                self::add(
                    new UnusedPsalmSuppress(
                        'This suppression is never used',
                        new Raw(
                            $file_contents,
                            $file_path,
                            $config->shortenFileName($file_path),
                            $start,
                            $end,
                        ),
                    ),
                );
            }
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function getErrorCount(): int
    {
        return self::$error_count;
    }

    /**
     * @param array<string, list<IssueData>> $issues_data
     * @psalm-external-mutation-free
     */
    public static function addIssues(array $issues_data): void
    {
        foreach ($issues_data as $file_path => $file_issues) {
            foreach ($file_issues as $issue) {
                $emitted_key = $issue->type
                    . '-' . $issue->file_name
                    . ':' . $issue->line_from
                    . ':' . $issue->column_from
                    . ' ' . ($issue->dupe_key ?? $issue->message);

                if (!self::alreadyEmitted($emitted_key)) {
                    self::$issues_data[$file_path][] = $issue;
                }
            }
        }
    }

    /**
     * @param  array<string,array<string,array{o:int, s:array<int, string>}>>  $issue_baseline
     */
    public static function finish(
        ProjectAnalyzer $project_analyzer,
        bool $is_full,
        float $start_time,
        bool $add_stats = false,
        array $issue_baseline = [],
    ): void {
        if (!$project_analyzer->stdout_report_options) {
            throw new UnexpectedValueException('Cannot finish without stdout report options');
        }

        $codebase = $project_analyzer->getCodebase();

        foreach ($codebase->config->config_issues as $issue) {
            self::maybeAdd($issue);
        }

        $error_count = 0;
        $info_count = 0;
        $baselined_count = 0;

        $issues_data = [];

        if (self::$issues_data) {
            ksort(self::$issues_data);

            foreach (self::$issues_data as $file_path => $file_issues) {
                usort(
                    $file_issues,
                    static fn(IssueData $d1, IssueData $d2): int => [$d1->file_path, $d1->line_from, $d1->column_from]
                        <=>
                        [$d2->file_path, $d2->line_from, $d2->column_from],
                );
                self::$issues_data[$file_path] = $file_issues;
            }

            // make a copy so what gets saved in cache is unaffected by baseline
            $issues_data = self::$issues_data;
        }

        if (!empty($issue_baseline)) {
            // Set severity for issues in baseline to INFO
            foreach ($issues_data as $file_path => $file_issues) {
                foreach ($file_issues as $key => $issue_data) {
                    $file = $issue_data->file_name;
                    $file = str_replace('\\', '/', $file);
                    $type = $issue_data->type;

                    if (isset($issue_baseline[$file][$type]) && $issue_baseline[$file][$type]['o'] > 0) {
                        if ($issue_baseline[$file][$type]['o'] === count($issue_baseline[$file][$type]['s'])) {
                            $position = array_search(
                                str_replace("\r\n", "\n", trim($issue_data->selected_text)),
                                $issue_baseline[$file][$type]['s'],
                                true,
                            );

                            if ($position !== false) {
                                $issue_data->severity = IssueData::SEVERITY_INFO;
                                ++$baselined_count;
                                array_splice($issue_baseline[$file][$type]['s'], $position, 1);
                                $issue_baseline[$file][$type]['o']--;
                            }
                        } else {
                            $issue_baseline[$file][$type]['s'] = [];
                            $issue_data->severity = IssueData::SEVERITY_INFO;
                            ++$baselined_count;
                            $issue_baseline[$file][$type]['o']--;
                        }
                    }

                    $issues_data[$file_path][$key] = $issue_data;
                }
            }

            if ($codebase->config->find_unused_baseline_entry) {
                foreach ($issue_baseline as $file_path => $issues) {
                    foreach ($issues as $issue_name => $issue) {
                        if ($issue['o'] !== 0) {
                            $issues_data[$file_path][] = new IssueData(
                                IssueData::SEVERITY_ERROR,
                                0,
                                0,
                                UnusedBaselineEntry::getIssueType(),
                                sprintf(
                                    'Baseline for issue "%s" has %d extra %s.',
                                    $issue_name,
                                    $issue['o'],
                                    $issue['o'] === 1 ? 'entry' : 'entries',
                                ),
                                $file_path,
                                Path::join($codebase->config->base_dir, $file_path),
                                '',
                                '',
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                UnusedBaselineEntry::SHORTCODE,
                                UnusedBaselineEntry::ERROR_LEVEL,
                            );
                        }
                    }
                }
            }
        }

        $issue_handler_suppressions_skipped = false;
        if ($codebase->config->find_unused_issue_handler_suppression) {
            if ($is_full && !$codebase->diff_run) {
                $config_path = $codebase->config->source_filename ?? '';
                $config_name = $config_path === '' ? '' : $codebase->config->shortenFileName($config_path);

                foreach ($codebase->config->getIssueHandlers() as $type => $handler) {
                    foreach ($handler->getFilters() as $filter) {
                        if ($filter->suppressions > 0 || $filter->getErrorLevel() != Config::REPORT_SUPPRESS) {
                            continue;
                        }
                        $issues_data['config'][] = new IssueData(
                            IssueData::SEVERITY_ERROR,
                            $filter->line,
                            $filter->line,
                            UnusedIssueHandlerSuppression::getIssueType(),
                            sprintf(
                                'Suppressed issue type "%s" for %s was not thrown.',
                                $type,
                                implode(', ', array_map(
                                    $codebase->config->shortenFileName(...),
                                    [...$filter->getFiles(), ...$filter->getDirectories()],
                                )),
                            ),
                            $config_name,
                            $config_path,
                            '',
                            '',
                            0,
                            0,
                            0,
                            0,
                            $filter->line > 0 ? 1 : 0,
                            $filter->line > 0 ? 1 : 0,
                            UnusedIssueHandlerSuppression::SHORTCODE,
                            UnusedIssueHandlerSuppression::ERROR_LEVEL,
                        );
                    }
                }
            } else {
                // a note about it is only worth printing when the config does suppress some issues by path
                foreach ($codebase->config->getIssueHandlers() as $handler) {
                    foreach ($handler->getFilters() as $filter) {
                        if ($filter->getErrorLevel() === Config::REPORT_SUPPRESS) {
                            $issue_handler_suppressions_skipped = true;
                            break 2;
                        }
                    }
                }
            }
        }

        // The report is written to the terminal, not into a web page, so it goes to STDOUT rather than through echo.
        $report = self::getOutput(
            $issues_data,
            $project_analyzer->stdout_report_options,
            $codebase->analyzer->getTotalTypeCoverage($codebase),
        );
        // reports read by people end with their last issue: the blank line before the summary comes from below
        if ($report !== '' && in_array(
            $project_analyzer->stdout_report_options->format,
            [Report::TYPE_CONSOLE, Report::TYPE_PHP_STORM, Report::TYPE_BY_ISSUE_LEVEL],
            true,
        )) {
            $report = rtrim($report, "\n") . "\n";
        }
        fwrite(STDOUT, $report);

        /** @var array<string, int> $error_counts_by_type */
        $error_counts_by_type = [];
        $files_with_errors = [];
        foreach ($issues_data as $file_path => $file_issues) {
            foreach ($file_issues as $issue_data) {
                if ($issue_data->severity === Config::REPORT_ERROR) {
                    ++$error_count;
                    $error_counts_by_type[$issue_data->type] = ($error_counts_by_type[$issue_data->type] ?? 0) + 1;
                    $files_with_errors[$file_path] = true;
                } else {
                    ++$info_count;
                }
            }
        }


        if ($codebase->config->eventDispatcher->after_analysis) {
            $source_control_info = null;
            $build_info = (new BuildInfoCollector(self::$server))->collect();

            try {
                $source_control_info = (new GitInfoCollector())->collect();
            } catch (RuntimeException) {
                // do nothing
            }

            /** @psalm-suppress ArgumentTypeCoercion due to Psalm bug */
            $event = new AfterAnalysisEvent(
                $codebase,
                $issues_data,
                $build_info,
                $source_control_info,
            );

            $codebase->config->eventDispatcher->dispatchAfterAnalysis($event);
        }

        foreach ($project_analyzer->generated_report_options as $report_options) {
            if (!$report_options->output_path) {
                throw new UnexpectedValueException('Output path should not be null here');
            }

            $folder = dirname($report_options->output_path);
            if (!is_dir($folder) && !mkdir($folder, 0777, true) && !is_dir($folder)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $folder));
            }
            file_put_contents(
                $report_options->output_path,
                self::getOutput(
                    $issues_data,
                    $report_options,
                    $codebase->analyzer->getTotalTypeCoverage($codebase),
                ),
            );
        }

        // The summary follows the console report on STDOUT, as it always did. A report in another format is read
        // by tools, and the --alter --dry-run diff may be applied: the summary goes to STDERR then, so that STDOUT
        // stays unchanged.
        $summary_on_stdout = !$codebase->alter_code && in_array(
            $project_analyzer->stdout_report_options->format,
            [Report::TYPE_CONSOLE, Report::TYPE_PHP_STORM, Report::TYPE_GITHUB_ACTIONS],
            true,
        );

        // one blank line after the report, which may not even end its last line (e.g. JSON), when both share a
        // terminal or a log: with STDOUT redirected elsewhere, the summary already follows the blank line after
        // the progress
        [$stdout, $stderr] = [fstat(STDOUT), fstat(STDERR)];
        $shares_stream = $summary_on_stdout || ($stdout && $stderr && $stdout['ino'] === $stderr['ino']);
        $output = $report !== '' && $shares_stream
            ? str_repeat("\n", max(0, 2 - (strlen($report) - strlen(rtrim($report, "\n")))))
            : '';

        // on STDERR, colors only on a terminal (not in a log file, nor with TERM=dumb)
        $use_color = $project_analyzer->stdout_report_options->use_color
            && ($summary_on_stdout || (stream_isatty(STDERR) && getenv('TERM') !== 'dumb'));

        $highlight = static fn(string $text): string => $use_color ? "\e[30;48;5;195m{$text}\e[0m" : $text;
        $separator = Progress::separator();

        $show_info = $project_analyzer->stdout_report_options->show_info;
        $show_suggestions = $project_analyzer->stdout_report_options->show_suggestions;

        if ($codebase->alter_code) {
            // issues aren't reported while altering code: the verdict is what was altered
            $altered_count = $codebase->analyzer->getAlteredFileCount();
            $altered_files = number_format($altered_count) . ($altered_count === 1 ? ' file' : ' files');
            $summary = match (true) {
                $altered_count === 0 => 'Nothing to alter',
                $project_analyzer->dry_run => "Would alter $altered_files (dry run)."
                    . ' Run without --dry-run to apply',
                default => "Altered $altered_files",
            };

            // which files: with --dry-run the diff above shows them
            if ($altered_count > 0 && !$project_analyzer->dry_run) {
                foreach (array_slice($codebase->analyzer->getAlteredFiles(), 0, self::ALTERED_FILES_SHOWN) as $path) {
                    $summary .= "\n  " . $codebase->config->shortenFileName($path);
                }

                if ($altered_count > self::ALTERED_FILES_SHOWN) {
                    $summary .= "\n  +" . number_format($altered_count - self::ALTERED_FILES_SHOWN) . ' more';
                }
            }
        } elseif ($error_count) {
            // e.g. "396 errors in 112 files · 121 baselined · 27 info hidden"
            $file_count = count($files_with_errors);
            $summary = number_format($error_count) . ($error_count === 1 ? ' error' : ' errors')
                . ' in ' . number_format($file_count) . ($file_count === 1 ? ' file' : ' files');
            $summary = $use_color ? "\e[0;31m{$summary}\e[0m" : $summary;
        } else {
            $summary = self::formatSuccessMessage($use_color);
        }

        if (!$codebase->alter_code) {
            // the baseline only holds errors: they come right after the reported ones
            if ($baselined_count) {
                $summary .= $separator . number_format($baselined_count) . ' baselined';
            }

            $other_count = $info_count - $baselined_count;
            // a count like the baselined one, not a suggestion: shown with --no-suggestions too
            if ($other_count > 0) {
                $summary .= $separator . number_format($other_count) . ' info' . ($show_info ? '' : ' hidden');
            }
        }

        $output .= $summary . "\n";

        $show_breakdown = $error_count > self::ERROR_BREAKDOWN_THRESHOLD;
        if ($show_breakdown) {
            // "fixable" is a suggestion too
            $output .= self::getErrorBreakdown(
                $error_counts_by_type,
                $show_suggestions ? self::$fixable_issue_counts : [],
            );
        }

        // Fixability is only counted by type: only suggest fixing the types of the errors reported,
        // not those of info issues or baselined ones
        $fixable_error_counts = [];
        foreach (self::$fixable_issue_counts as $type => $count) {
            if (isset($error_counts_by_type[$type])) {
                $fixable_error_counts[$type] = min($count, $error_counts_by_type[$type]);
            }
        }

        if ($fixable_error_counts && $show_suggestions) {
            $command = self::getInvokedCommand(
                '--alter',
                '--issues=' . implode(',', array_keys($fixable_error_counts)),
                '--dry-run',
            );
            $fixable_count = array_sum($fixable_error_counts);

            // a block of its own after the breakdown
            $output .= ($show_breakdown ? "\n" : '') . 'Preview the fix for ' . number_format($fixable_count)
                . ($fixable_count === 1 ? ' issue: ' : ' issues: ') . $highlight($command) . "\n";
        }

        if ($start_time) {
            // e.g. "72.8s · 11.9 GB peak · type coverage 99.87%"
            $stats = number_format(microtime(true) - $start_time, 1) . 's'
                . $separator . self::formatMemory(memory_get_peak_usage()) . ' peak';

            $type_inference_summary = $codebase->analyzer->getTypeInferenceSummary($codebase);
            // type coverage was measured before --alter changed anything
            if ($type_inference_summary !== '' && !$codebase->alter_code) {
                $stats .= $separator . $type_inference_summary;
            }

            $output .= "\n" . $stats . "\n";

            $non_mixed_stats = $add_stats ? $codebase->analyzer->getNonMixedStats() : '';
            if ($non_mixed_stats !== '') {
                $output .= "\nType coverage by file:\n" . $non_mixed_stats;
            }

            $function_timings = $project_analyzer->debug_performance
                ? $codebase->analyzer->getFunctionTimings()
                : [];

            if ($function_timings) {
                $output .= "\nSlowest functions to analyze:\n";

                arsort($function_timings);

                // e.g. "   1.23 ms/node  Foo::bar"
                foreach (array_slice($function_timings, 0, 10, true) as $function_id => $time) {
                    $output .= '  ' . str_pad(number_format(1_000 * $time, 2), 6, ' ', STR_PAD_LEFT) . ' ms/node  '
                        . $function_id . "\n";
                }
            }
        }

        $skipped_checks = [];
        if ($project_analyzer->unused_code_skipped) {
            $skipped_checks[] = 'unused code';
        }

        if ($issue_handler_suppressions_skipped) {
            $skipped_checks[] = 'unused <issueHandlers> suppressions';
        }

        // --alter reports no issues at all: a full run wouldn't report these either
        if ($skipped_checks && !$codebase->alter_code) {
            $output .= "\nNote: " . implode(' and ', $skipped_checks) . ' are only reported on a full run.' . "\n";
        }

        if ($summary_on_stdout) {
            echo $output;
        } elseif ($project_analyzer->progress instanceof VoidProgress) {
            // the summary (and --stats) isn't progress: --no-progress and agents still get it
            fwrite(STDERR, $output);
        } else {
            $project_analyzer->progress->write($output);
        }

        if ($is_full && $start_time) {
            $project_analyzer->finish($start_time, PSALM_VERSION);
        }

        // Persist the custom taint name->bit mapping on every run that populated the cache (not just full
        // runs), so cached taint sinks/sources keep matching after the analysis is reused from cache.
        $project_analyzer->persistCustomTaints();

        if ($error_count
            && !($codebase->taint_flow_graph
                && $project_analyzer->generated_report_options
                && isset($_SERVER['GITHUB_WORKFLOW']))
        ) {
            exit(2);
        }
    }

    public static function printSuccessMessage(ProjectAnalyzer $project_analyzer): void
    {
        if (!$project_analyzer->stdout_report_options) {
            throw new UnexpectedValueException('Cannot print success message without stdout report options');
        }

        echo self::formatSuccessMessage($project_analyzer->stdout_report_options->use_color) . "\n";
    }

    /**
     * @psalm-pure
     */
    private static function formatSuccessMessage(bool $use_color): string
    {
        return $use_color ? "\e[0;32mNo errors found!\e[0m" : 'No errors found!';
    }

    /**
     * A command running Psalm again with the given options, so that it can be copied as is: the binary Psalm was
     * started with (e.g. vendor/bin/psalm, or a wrapper of it), and what decides which code is analysed and how: the
     * config, root and PHP version, and the paths (e.g. "vendor/bin/psalm -c psalm.xml --alter … src/Foo.php")
     *
     * Options and paths are told apart as CliUtils::getPathsToCheck() does. It is only printed to the terminal, not
     * into a web page.
     *
     * @psalm-taint-escape html
     * @psalm-taint-escape has_quotes
     */
    private static function getInvokedCommand(string ...$options): string
    {
        $argv = isset(self::$server['argv']) && is_array(self::$server['argv']) ? self::$server['argv'] : [];
        $binary = isset($argv[0]) && is_string($argv[0]) ? $argv[0] : 'psalm';

        $kept_options = [];
        // after the options: PHP's getopt() stops at the first path
        $paths = [];
        for ($i = 1, $count = count($argv); $i < $count; ++$i) {
            $arg = $argv[$i] ?? null;
            if (!is_string($arg) || $arg === '' || $arg === '-') {
                continue;
            }

            if ($arg[0] !== '-') {
                $paths[] = $arg;
            } elseif (in_array($arg, ['-c', '-f', '-r', '--config', '--root'], true)) {
                // the value is the next argument
                ++$i;
                $kept_options[] = $arg;
                $kept_options[] = isset($argv[$i]) && is_string($argv[$i]) ? $argv[$i] : '';
            } elseif (preg_match('/^(-[cfr].|--(config|root|php-version)=)/', $arg) === 1) {
                $kept_options[] = $arg;
            } elseif ($arg === '--printer') {
                ++$i;
            }
        }

        // quoted only when the shell needs it, to keep the command readable
        return implode(' ', array_map(
            static fn(string $word): string => preg_match('#^[\w./:=@%+,-]+$#', $word) === 1
                ? $word
                : escapeshellarg($word),
            [$binary, ...$kept_options, ...$options, ...$paths],
        ));
    }

    /**
     * @psalm-pure
     */
    private static function formatMemory(int $bytes): string
    {
        if ($bytes >= 1_024 ** 3) {
            return number_format($bytes / 1_024 ** 3, 1) . ' GB';
        }

        return number_format($bytes / 1_024 ** 2) . ' MB';
    }

    /**
     * The most frequent error types, e.g. "  312  MissingOverrideAttribute   fixable"
     *
     * @param array<string, int> $error_counts_by_type
     * @param array<string, int> $fixable_issue_counts
     * @psalm-pure
     */
    private static function getErrorBreakdown(array $error_counts_by_type, array $fixable_issue_counts): string
    {
        uksort(
            $error_counts_by_type,
            static fn(string $a, string $b): int
                => [$error_counts_by_type[$b], $a] <=> [$error_counts_by_type[$a], $b],
        );

        $shown = array_slice($error_counts_by_type, 0, self::ERROR_BREAKDOWN_TYPES, true);
        if ($shown === []) {
            return '';
        }

        $count_width = strlen(number_format(max($shown)));
        $type_width = max(array_map(strlen(...), array_keys($shown)));

        $breakdown = '';
        foreach ($shown as $type => $count) {
            $line = '  ' . str_pad(number_format($count), $count_width, ' ', STR_PAD_LEFT) . '  ' . $type;
            if (isset($fixable_issue_counts[$type])) {
                $line = str_pad($line, 4 + $count_width + $type_width) . '   fixable';
            }

            $breakdown .= $line . "\n";
        }

        $hidden_types = count($error_counts_by_type) - count($shown);
        if ($hidden_types > 0) {
            $breakdown .= str_repeat(' ', 4 + $count_width) . '+' . $hidden_types
                . ($hidden_types === 1 ? ' more type' : ' more types') . ' (all of them: --output-format=count)' . "\n";
        }

        return $breakdown;
    }

    /**
     * @param array<string, array<int, IssueData>> $issues_data
     * @param array{int, int} $mixed_counts
     */
    public static function getOutput(
        array $issues_data,
        ReportOptions $report_options,
        array $mixed_counts = [0, 0],
    ): string {
        $total_expression_count = $mixed_counts[0] + $mixed_counts[1];
        $mixed_expression_count = $mixed_counts[0];

        $normalized_data = $issues_data === [] ? [] : array_merge(...array_values($issues_data));

        $format = $report_options->format;

        $output = match ($format) {
            Report::TYPE_COMPACT => new CompactReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_EMACS => new EmacsReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_TABLE => new TableReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_TEXT => new TextReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_JSON => new JsonReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_BY_ISSUE_LEVEL => new ByIssueLevelAndTypeReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_JSON_SUMMARY => new JsonSummaryReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
                $mixed_expression_count,
                $total_expression_count,
            ),
            Report::TYPE_SONARQUBE => new SonarqubeReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_PYLINT => new PylintReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_CHECKSTYLE => new CheckstyleReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_XML => new XmlReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_JUNIT => new JunitReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_CONSOLE => new ConsoleReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_GITHUB_ACTIONS => new GithubActionsReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_PHP_STORM => new PhpStormReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_SARIF => new SarifReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_CODECLIMATE => new CodeClimateReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
            Report::TYPE_COUNT => new CountReport(
                $normalized_data,
                self::$fixable_issue_counts,
                $report_options,
            ),
        };

        return $output->create();
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function alreadyEmitted(string $message): bool
    {
        $sham = sha1($message);

        if (isset(self::$emitted[$sham])) {
            return true;
        }

        self::$emitted[$sham] = true;

        return false;
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function clearCache(): void
    {
        self::$issues_data = [];
        self::$emitted = [];
        self::$error_count = 0;
        self::$recording_level = 0;
        self::$recorded_issues = [];
        self::$unused_suppressions = [];
        self::$used_suppressions = [];
    }

    /**
     * @return array<string, list<IssueData>>
     * @psalm-external-mutation-free
     */
    public static function clear(): array
    {
        $current_data = self::$issues_data;
        self::$issues_data = [];
        self::$emitted = [];

        return $current_data;
    }

    /**
     * Return whether or not we're in a recording state regarding startRecording/stopRecording status
     *
     * @psalm-external-mutation-free
     */
    public static function isRecording(): bool
    {
        return self::$recording_level > 0;
    }

    /**
     * Increase the recording level in order to start recording issues instead of adding them while in a loop
     *
     * @psalm-external-mutation-free
     */
    public static function startRecording(): void
    {
        ++self::$recording_level;
        self::$recorded_issues[self::$recording_level] = [];
    }

    /**
     * Decrease the recording level after leaving a loop
     *
     * @see startRecording
     * @psalm-external-mutation-free
     */
    public static function stopRecording(): void
    {
        if (self::$recording_level === 0) {
            throw new UnexpectedValueException('Cannot stop recording - already at base level');
        }

        --self::$recording_level;
    }

    /**
     * This will return the recorded issues for the current recording level
     *
     * @return array<int, CodeIssue>
     * @psalm-external-mutation-free
     */
    public static function clearRecordingLevel(): array
    {
        if (self::$recording_level === 0) {
            throw new UnexpectedValueException('Not currently recording');
        }

        $recorded_issues = self::$recorded_issues[self::$recording_level];

        self::$recorded_issues[self::$recording_level] = [];

        return $recorded_issues;
    }

    /**
     * This will try to add issues that has been retrieved through clearRecordingLevel or record them at a lower level
     */
    public static function bubbleUp(CodeIssue $e): void
    {
        if (self::$recording_level === 0) {
            self::add($e);

            return;
        }

        self::$recorded_issues[self::$recording_level][] = $e;
    }

    /**
     * @internal
     * @param array<array-key,mixed> $server
     * @psalm-external-mutation-free
     */
    final public static function captureServer(array $server): void
    {
        self::$server = $server;
    }
    /**
     * @internal
     * @return array<array-key,mixed>
     * @psalm-external-mutation-free
     */
    final public static function getServer(): array
    {
        return self::$server;
    }
}
