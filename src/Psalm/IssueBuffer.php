<?php
namespace Psalm;

use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Issue\CodeIssue;
use Psalm\Output\Checkstyle;
use Psalm\Output\Compact;
use Psalm\Output\Console;
use Psalm\Output\Emacs;
use Psalm\Output\Json;
use Psalm\Output\JsonSummary;
use Psalm\Output\Pylint;
use Psalm\Output\Text;
use Psalm\Output\Xml;

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
     * @param   CodeIssue $e
     * @param   array     $suppressed_issues
     *
     * @return  bool
     */
    public static function accepts(CodeIssue $e, array $suppressed_issues = [])
    {
        if (self::isSuppressed($e, $suppressed_issues)) {
            return false;
        }

        return self::add($e);
    }

    /**
     * @param   CodeIssue $e
     * @param   array     $suppressed_issues
     *
     * @return  bool
     */
    public static function isSuppressed(CodeIssue $e, array $suppressed_issues = []) : bool
    {
        $config = Config::getInstance();

        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);

        if (!$config->reportIssueInFile($issue_type, $e->getFilePath())) {
            return true;
        }

        $reporting_level = $config->getReportingLevelForIssue($e, $suppressed_issues);

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
    public static function add(CodeIssue $e)
    {
        $config = Config::getInstance();

        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);

        $project_analyzer = ProjectAnalyzer::getInstance();

        if (!$project_analyzer->show_issues) {
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
                    . ' - ' . $e->getShortLocation()
                    . ':' . $e->getLocation()->getColumn()
                    . ' - ' . $e->getMessage()
            );
        }

        if (!self::alreadyEmitted($emitted_key)) {
            ++self::$error_count;
            self::$issues_data[] = $e->toArray(Config::REPORT_ERROR);
        }

        return true;
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
     * @return int
     */
    public static function getErrorCount()
    {
        return self::$error_count;
    }

    /**
     * @param array<int, array{severity: string, line_from: int, line_to: int, type: string, message: string,
     *  file_name: string, file_path: string, snippet: string, from: int, to: int, snippet_from: int,
     *  snippet_to: int, column_from: int, column_to: int}> $issues_data
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
        if ($project_analyzer->output_format === ProjectAnalyzer::TYPE_CONSOLE) {
            echo "\n";
        }

        $codebase = $project_analyzer->getCodebase();

        $error_count = 0;
        $info_count = 0;

        if (self::$issues_data) {
            usort(
                self::$issues_data,
                /** @return int */
                function (array $d1, array $d2) {
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
                            $position = array_search($issue_data['selected_text'], $issue_baseline[$file][$type]['s']);

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
                $project_analyzer->output_format,
                $project_analyzer->use_color,
                $project_analyzer->show_snippet,
                $project_analyzer->show_info,
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

        foreach ($project_analyzer->reports as $format => $path) {
            file_put_contents(
                $path,
                self::getOutput(
                    $format,
                    $project_analyzer->use_color,
                    true,
                    true,
                    $codebase->analyzer->getTotalTypeCoverage($codebase)
                )
            );
        }

        if ($project_analyzer->output_format === ProjectAnalyzer::TYPE_CONSOLE) {
            echo str_repeat('-', 30) . "\n";

            if ($error_count) {
                echo ($project_analyzer->use_color
                    ? "\e[0;31m" . $error_count . " errors\e[0m"
                    : $error_count . ' errors'
                ) . ' found' . "\n";
            } else {
                echo 'No errors found!' . "\n";
            }

            if ($info_count && $project_analyzer->show_info) {
                echo str_repeat('-', 30) . "\n";

                echo $info_count . ' other issues found.' . "\n"
                    . 'You can hide them with ' .
                    ($project_analyzer->use_color
                        ? "\e[30;48;5;195m--show-info=false\e[0m"
                        : '--show-info=false') . "\n";
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
     * @param string $format
     * @param bool   $use_color
     * @param bool   $show_snippet
     * @param bool   $show_info
     * @param array{int, int} $mixed_counts
     *
     * @return string
     */
    public static function getOutput(
        string $format,
        bool $use_color,
        bool $show_snippet = true,
        bool $show_info = true,
        array $mixed_counts = [0, 0]
    ) {
        $total_expression_count = $mixed_counts[0] + $mixed_counts[1];
        $mixed_expression_count = $mixed_counts[0];

        switch ($format) {
            case ProjectAnalyzer::TYPE_COMPACT:
                $output = new Compact(self::$issues_data, $use_color, $show_snippet, $show_info);
                break;

            case ProjectAnalyzer::TYPE_EMACS:
                $output = new Emacs(self::$issues_data, $use_color, $show_snippet, $show_info);
                break;

            case ProjectAnalyzer::TYPE_TEXT:
                $output = new Text(self::$issues_data, $use_color, $show_snippet, $show_info);
                break;

            case ProjectAnalyzer::TYPE_JSON:
                $output = new Json(self::$issues_data, $use_color, $show_snippet, $show_info);
                break;

            case ProjectAnalyzer::TYPE_JSON_SUMMARY:
                $output = new JsonSummary(
                    self::$issues_data,
                    $use_color,
                    $show_snippet,
                    $show_info,
                    $mixed_expression_count,
                    $total_expression_count
                );
                break;

            case ProjectAnalyzer::TYPE_PYLINT:
                $output = new Pylint(self::$issues_data, $use_color, $show_snippet, $show_info);
                break;

            case ProjectAnalyzer::TYPE_CHECKSTYLE:
                $output = new Checkstyle(self::$issues_data, $use_color, $show_snippet, $show_info);
                break;

            case ProjectAnalyzer::TYPE_XML:
                $output = new Xml(self::$issues_data, $use_color, $show_snippet, $show_info);
                break;

            case ProjectAnalyzer::TYPE_CONSOLE:
            default:
                $output = new Console(self::$issues_data, $use_color, $show_snippet, $show_info);
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
