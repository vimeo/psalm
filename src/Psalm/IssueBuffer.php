<?php
namespace Psalm;

use function array_pop;
use function array_search;
use function array_splice;
use function count;
use function explode;
use function file_put_contents;
use function get_class;
use function memory_get_peak_usage;
use function microtime;
use function number_format;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\UnusedPsalmSuppress;
use Psalm\Report\CheckstyleReport;
use Psalm\Report\CompactReport;
use Psalm\Report\ConsoleReport;
use Psalm\Report\EmacsReport;
use Psalm\Report\JsonReport;
use Psalm\Report\JsonSummaryReport;
use Psalm\Report\JunitReport;
use Psalm\Report\PylintReport;
use Psalm\Report\SonarqubeReport;
use Psalm\Report\TextReport;
use Psalm\Report\XmlReport;
use function sha1;
use function str_repeat;
use function str_replace;
use function usort;

class IssueBuffer
{
    /**
     * @var array<int, array{severity: string, line_from: int, line_to: int, type: string, message: string,
     * file_name: string, file_path: string, snippet: string, from: int, to: int,
     * snippet_from: int, snippet_to: int, column_from: int, column_to: int, selected_text: string}>
     */
    protected static $issues_data = [];

    /**
     * @var array<int, array>
     */
    protected static $console_issues = [];

    /**
     * @var array<string, int>
     */
    protected static $fixable_issue_counts = [];

    /**
     * @var int
     */
    protected static $error_count = 0;

    /**
     * @var array<string, bool>
     */
    protected static $emitted = [];

    /** @var int */
    protected static $recording_level = 0;

    /** @var array<int, array<int, CodeIssue>> */
    protected static $recorded_issues = [];

    /**
     * @var array<string, array<int, int>>
     */
    protected static $unused_suppressions = [];

    /**
     * @var array<string, array<int, bool>>
     */
    protected static $used_suppressions = [];

    /**
     * @param   CodeIssue $e
     * @param   string[]  $suppressed_issues
     *
     * @return  bool
     */
    public static function accepts(CodeIssue $e, array $suppressed_issues = [], bool $is_fixable = false)
    {
        if (self::isSuppressed($e, $suppressed_issues)) {
            return false;
        }

        return self::add($e, $is_fixable);
    }

    public static function addUnusedSuppression(string $file_path, int $offset, string $issue_type) : void
    {
        if (isset(self::$used_suppressions[$file_path][$offset])) {
            return;
        }

        if (!isset(self::$unused_suppressions[$file_path])) {
            self::$unused_suppressions[$file_path] = [];
        }

        self::$unused_suppressions[$file_path][$offset] = $offset + \strlen($issue_type) - 1;
    }

    /**
     * @param   CodeIssue $e
     * @param   string[]  $suppressed_issues
     *
     * @return  bool
     */
    public static function isSuppressed(CodeIssue $e, array $suppressed_issues = []) : bool
    {
        $config = Config::getInstance();

        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);
        $file_path = $e->getFilePath();

        if (!$config->reportIssueInFile($issue_type, $file_path)) {
            return true;
        }

        $suppressed_issue_position = array_search($issue_type, $suppressed_issues);

        if ($suppressed_issue_position !== false) {
            if (\is_int($suppressed_issue_position)) {
                self::$used_suppressions[$file_path][$suppressed_issue_position] = true;
            }

            return true;
        }

        $parent_issue_type = Config::getParentIssueType($issue_type);

        if ($parent_issue_type) {
            $suppressed_issue_position = array_search($parent_issue_type, $suppressed_issues);

            if ($suppressed_issue_position !== false) {
                if (\is_int($suppressed_issue_position)) {
                    self::$used_suppressions[$file_path][$suppressed_issue_position] = true;
                }

                return true;
            }
        }

        $suppress_all_position = array_search('all', $suppressed_issues);

        if ($suppress_all_position !== false) {
            if (\is_int($suppress_all_position)) {
                self::$used_suppressions[$file_path][$suppress_all_position] = true;
            }

            return true;
        }

        $reporting_level = $config->getReportingLevelForIssue($e);

        if ($reporting_level === Config::REPORT_SUPPRESS) {
            return true;
        }

        if ($e->getLocation()->getLineNumber() === -1) {
            return true;
        }

        if (self::$recording_level > 0) {
            self::$recorded_issues[self::$recording_level][] = $e;

            return true;
        }

        return false;
    }

    /**
     * @param   CodeIssue $e
     *
     * @throws  Exception\CodeException
     *
     * @return  bool
     */
    public static function add(CodeIssue $e, bool $is_fixable = false)
    {
        $config = Config::getInstance();

        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);

        $project_analyzer = ProjectAnalyzer::getInstance();

        if (!$project_analyzer->show_issues) {
            return false;
        }

        if ($project_analyzer->getCodebase()->taint && $issue_type !== 'TaintedInput') {
            return false;
        }

        $reporting_level = $config->getReportingLevelForIssue($e);

        if ($reporting_level === Config::REPORT_SUPPRESS) {
            return false;
        }

        $emitted_key = $issue_type . '-' . $e->getShortLocation() . ':' . $e->getLocation()->getColumn();

        if ($reporting_level === Config::REPORT_INFO) {
            if (!self::alreadyEmitted($emitted_key)) {
                self::$issues_data[] = $e->toArray(Config::REPORT_INFO);
            }

            return false;
        }

        if ($config->throw_exception) {
            \Psalm\Internal\Analyzer\FileAnalyzer::clearCache();

            throw new Exception\CodeException(
                $issue_type
                    . ' - ' . $e->getShortLocationWithPrevious()
                    . ':' . $e->getLocation()->getColumn()
                    . ' - ' . $e->getMessage()
            );
        }

        if (!self::alreadyEmitted($emitted_key)) {
            ++self::$error_count;
            self::$issues_data[] = $e->toArray(Config::REPORT_ERROR);
        }

        if ($is_fixable) {
            self::addFixableIssue($issue_type);
        }

        return true;
    }

    public static function addFixableIssue(string $issue_type) : void
    {
        if (isset(self::$fixable_issue_counts[$issue_type])) {
            self::$fixable_issue_counts[$issue_type]++;
        } else {
            self::$fixable_issue_counts[$issue_type] = 1;
        }
    }

    /**
     * @return array<int, array{severity: string, line_from: int, line_to: int, type: string, message: string,
     *  file_name: string, file_path: string, snippet: string, from: int, to: int, snippet_from: int, snippet_to: int,
     *  column_from: int, column_to: int, selected_text: string}>
     */
    public static function getIssuesData()
    {
        return self::$issues_data;
    }

    /**
     * @return array<string, int>
     */
    public static function getFixableIssues()
    {
        return self::$fixable_issue_counts;
    }

    /**
     * @param array<string, int> $fixable_issue_counts
     */
    public static function addFixableIssues(array $fixable_issue_counts) : void
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
     */
    public static function getUnusedSuppressions() : array
    {
        return self::$unused_suppressions;
    }

    /**
     * @return array<string, array<int, bool>>
     */
    public static function getUsedSuppressions() : array
    {
        return self::$used_suppressions;
    }

    /**
     * @param array<string, array<int, int>> $unused_suppressions
     */
    public static function addUnusedSuppressions(array $unused_suppressions) : void
    {
        self::$unused_suppressions += $unused_suppressions;
    }

    /**
     * @param array<string, array<int, bool>> $used_suppressions
     */
    public static function addUsedSuppressions(array $used_suppressions) : void
    {
        foreach ($used_suppressions as $file => $offsets) {
            if (!isset(self::$used_suppressions[$file])) {
                self::$used_suppressions[$file] = $offsets;
            } else {
                self::$used_suppressions[$file] += $offsets;
            }
        }
    }

    public static function processUnusedSuppressions(\Psalm\Internal\Provider\FileProvider $file_provider) : void
    {
        $config = Config::getInstance();

        foreach (self::$unused_suppressions as $file_path => $offsets) {
            if (!$offsets) {
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
                        new CodeLocation\Raw(
                            $file_contents,
                            $file_path,
                            $config->shortenFileName($file_path),
                            $start,
                            $end
                        )
                    )
                );
            }
        }
    }

    /**
     * @return int
     */
    public static function getErrorCount()
    {
        return self::$error_count;
    }

    /**
     * @param array<int, array{severity: string, line_from: int, line_to: int, type: string, message: string,
     *  file_name: string, file_path: string, snippet: string, from: int, to: int, snippet_from: int,
     *  snippet_to: int, column_from: int, column_to: int, selected_text: string}> $issues_data
     *
     * @return void
     */
    public static function addIssues(array $issues_data)
    {
        foreach ($issues_data as $issue) {
            $emitted_key = $issue['type']
                . '-' . $issue['file_name']
                . ':' . $issue['line_from']
                . ':' . $issue['column_from'];

            if (!self::alreadyEmitted($emitted_key)) {
                self::$issues_data[] = $issue;
            }
        }
    }

    /**
     * @param  ProjectAnalyzer                   $project_analyzer
     * @param  bool                             $is_full
     * @param  float                            $start_time
     * @param  bool                             $add_stats
     * @param  array<string,array<string,array{o:int, s:array<int, string>}>>  $issue_baseline
     *
     * @return void
     */
    public static function finish(
        ProjectAnalyzer $project_analyzer,
        bool $is_full,
        float $start_time,
        bool $add_stats = false,
        array $issue_baseline = []
    ) {
        if (!$project_analyzer->stdout_report_options) {
            throw new \UnexpectedValueException('Cannot finish without stdout report options');
        }

        if ($project_analyzer->stdout_report_options->format === Report::TYPE_CONSOLE) {
            echo "\n";
        }

        $codebase = $project_analyzer->getCodebase();

        $error_count = 0;
        $info_count = 0;

        if (self::$issues_data) {
            usort(
                self::$issues_data,
                /**
                 * @param array{file_path: string, line_from: int, column_from: int} $d1
                 * @param array{file_path: string, line_from: int, column_from: int} $d2
                 */
                function (array $d1, array $d2) : int {
                    if ($d1['file_path'] === $d2['file_path']) {
                        if ($d1['line_from'] === $d2['line_from']) {
                            if ($d1['column_from'] === $d2['column_from']) {
                                return 0;
                            }

                            return $d1['column_from'] > $d2['column_from'] ? 1 : -1;
                        }

                        return $d1['line_from'] > $d2['line_from'] ? 1 : -1;
                    }

                    return $d1['file_path'] > $d2['file_path'] ? 1 : -1;
                }
            );

            if (!empty($issue_baseline)) {
                // Set severity for issues in baseline to INFO
                foreach (self::$issues_data as $key => $issue_data) {
                    $file = $issue_data['file_name'];
                    $file = str_replace('\\', '/', $file);
                    $type = $issue_data['type'];

                    if (isset($issue_baseline[$file][$type]) && $issue_baseline[$file][$type]['o'] > 0) {
                        if ($issue_baseline[$file][$type]['o'] === count($issue_baseline[$file][$type]['s'])) {
                            $position = array_search(
                                $issue_data['selected_text'],
                                $issue_baseline[$file][$type]['s'],
                                true
                            );

                            if ($position !== false) {
                                $issue_data['severity'] = Config::REPORT_INFO;
                                array_splice($issue_baseline[$file][$type]['s'], $position, 1);
                                $issue_baseline[$file][$type]['o'] = $issue_baseline[$file][$type]['o'] - 1;
                            }
                        } else {
                            $issue_baseline[$file][$type]['s'] = [];
                            $issue_data['severity'] = Config::REPORT_INFO;
                            $issue_baseline[$file][$type]['o'] = $issue_baseline[$file][$type]['o'] - 1;
                        }
                    }

                    self::$issues_data[$key] = $issue_data;
                }
            }

            foreach (self::$issues_data as $issue_data) {
                if ($issue_data['severity'] === Config::REPORT_ERROR) {
                    ++$error_count;
                } else {
                    ++$info_count;
                }
            }

            echo self::getOutput(
                $project_analyzer->stdout_report_options,
                $codebase->analyzer->getTotalTypeCoverage($codebase)
            );
        }

        $after_analysis_hooks = $codebase->config->after_analysis;

        if ($after_analysis_hooks) {
            $source_control_info = null;
            $build_info = (new \Psalm\Internal\ExecutionEnvironment\BuildInfoCollector($_SERVER))->collect();

            try {
                $source_control_info = (new \Psalm\Internal\ExecutionEnvironment\GitInfoCollector())->collect();
            } catch (\RuntimeException $e) {
                // do nothing
            }

            foreach ($after_analysis_hooks as $after_analysis_hook) {
                $after_analysis_hook::afterAnalysis(
                    $codebase,
                    self::$issues_data,
                    $build_info,
                    $source_control_info
                );
            }
        }

        foreach ($project_analyzer->generated_report_options as $report_options) {
            if (!$report_options->output_path) {
                throw new \UnexpectedValueException('Output path should not be null here');
            }

            file_put_contents(
                $report_options->output_path,
                self::getOutput(
                    $report_options,
                    $codebase->analyzer->getTotalTypeCoverage($codebase)
                )
            );
        }

        if ($project_analyzer->stdout_report_options->format === Report::TYPE_CONSOLE) {
            echo str_repeat('-', 30) . "\n";

            if ($error_count) {
                echo($project_analyzer->stdout_report_options->use_color
                    ? "\e[0;31m" . $error_count . " errors\e[0m"
                    : $error_count . ' errors'
                ) . ' found' . "\n";
            } else {
                echo 'No errors found!' . "\n";
            }

            $show_info = $project_analyzer->stdout_report_options->show_info;
            $show_suggestions = $project_analyzer->stdout_report_options->show_suggestions;

            if ($info_count && ($show_info || $show_suggestions)) {
                echo str_repeat('-', 30) . "\n";

                echo $info_count . ' other issues found.' . "\n";

                if ($show_info) {
                    echo 'You can hide them with ' .
                        ($project_analyzer->stdout_report_options->use_color
                            ? "\e[30;48;5;195m--show-info=false\e[0m"
                            : '--show-info=false') . "\n";
                }
            }

            if (self::$fixable_issue_counts && $show_suggestions) {
                echo str_repeat('-', 30) . "\n";

                $total_count = \array_sum(self::$fixable_issue_counts);
                $command = '--alter --issues=' . \implode(',', \array_keys(self::$fixable_issue_counts));
                $command .= ' --dry-run';

                echo 'Psalm can automatically fix ' . $total_count
                    . ($show_info ? ' issues' : ' of them') . ".\n"
                    . 'Run Psalm again with ' . "\n"
                    . ($project_analyzer->stdout_report_options->use_color
                        ? "\e[30;48;5;195m" . $command . "\e[0m"
                        : $command) . "\n"
                    . 'to see what it can fix.' . "\n";
            }

            echo str_repeat('-', 30) . "\n" . "\n";

            if ($start_time) {
                echo 'Checks took ' . number_format(microtime(true) - $start_time, 2) . ' seconds';
                echo ' and used ' . number_format(memory_get_peak_usage() / (1024 * 1024), 3) . 'MB of memory' . "\n";

                $analysis_summary = $codebase->analyzer->getTypeInferenceSummary($codebase);
                echo $analysis_summary . "\n";

                if ($add_stats) {
                    echo '-----------------' . "\n";
                    echo $codebase->analyzer->getNonMixedStats();
                    echo "\n";
                }
            }
        }

        if ($error_count) {
            exit(1);
        }

        if ($is_full && $start_time) {
            $codebase->file_reference_provider->removeDeletedFilesFromReferences();

            if ($codebase->statements_provider->parser_cache_provider) {
                $codebase->statements_provider->parser_cache_provider->processSuccessfulRun($start_time);
            }
        }
    }

    /**
     * @param array{int, int} $mixed_counts
     *
     * @return string
     */
    public static function getOutput(
        \Psalm\Report\ReportOptions $report_options,
        array $mixed_counts = [0, 0]
    ) {
        $total_expression_count = $mixed_counts[0] + $mixed_counts[1];
        $mixed_expression_count = $mixed_counts[0];

        switch ($report_options->format) {
            case Report::TYPE_COMPACT:
                $output = new CompactReport(self::$issues_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_EMACS:
                $output = new EmacsReport(self::$issues_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_TEXT:
                $output = new TextReport(self::$issues_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_JSON:
                $output = new JsonReport(self::$issues_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_JSON_SUMMARY:
                $output = new JsonSummaryReport(
                    self::$issues_data,
                    self::$fixable_issue_counts,
                    $report_options,
                    $mixed_expression_count,
                    $total_expression_count
                );
                break;

            case Report::TYPE_SONARQUBE:
                $output = new SonarqubeReport(self::$issues_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_PYLINT:
                $output = new PylintReport(self::$issues_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_CHECKSTYLE:
                $output = new CheckstyleReport(self::$issues_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_XML:
                $output = new XmlReport(self::$issues_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_JUNIT:
                $output = new JUnitReport(self::$issues_data, self::$fixable_issue_counts, $report_options);
                break;

            case Report::TYPE_CONSOLE:
                $output = new ConsoleReport(self::$issues_data, self::$fixable_issue_counts, $report_options);
                break;
        }

        return $output->create();
    }

    /**
     * @param  string $message
     *
     * @return bool
     */
    protected static function alreadyEmitted($message)
    {
        $sham = sha1($message);

        if (isset(self::$emitted[$sham])) {
            return true;
        }

        self::$emitted[$sham] = true;

        return false;
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$issues_data = [];
        self::$emitted = [];
        self::$error_count = 0;
        self::$recording_level = 0;
        self::$recorded_issues = [];
        self::$console_issues = [];
        self::$unused_suppressions = [];
        self::$used_suppressions = [];
    }

    /**
     * @return array<int, array{severity: string, line_from: int, line_to: int, type: string, message: string,
     *  file_name: string, file_path: string, snippet: string, from: int, to: int, snippet_from: int, snippet_to: int,
     *  column_from: int, column_to: int}>
     */
    public static function clear()
    {
        $current_data = self::$issues_data;
        self::$issues_data = [];
        self::$emitted = [];

        return $current_data;
    }

    /**
     * @return bool
     */
    public static function isRecording()
    {
        return self::$recording_level > 0;
    }

    /**
     * @return void
     */
    public static function startRecording()
    {
        ++self::$recording_level;
        self::$recorded_issues[self::$recording_level] = [];
    }

    /**
     * @return void
     */
    public static function stopRecording()
    {
        if (self::$recording_level === 0) {
            throw new \UnexpectedValueException('Cannot stop recording - already at base level');
        }

        --self::$recording_level;
    }

    /**
     * @return array<int, CodeIssue>
     */
    public static function clearRecordingLevel()
    {
        if (self::$recording_level === 0) {
            throw new \UnexpectedValueException('Not currently recording');
        }

        $recorded_issues = self::$recorded_issues[self::$recording_level];

        self::$recorded_issues[self::$recording_level] = [];

        return $recorded_issues;
    }

    /**
     * @return void
     */
    public static function bubbleUp(CodeIssue $e)
    {
        if (self::$recording_level === 0) {
            self::add($e);

            return;
        }

        self::$recorded_issues[self::$recording_level][] = $e;
    }
}
