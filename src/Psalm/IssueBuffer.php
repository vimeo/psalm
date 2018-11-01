<?php
namespace Psalm;

use LSS\Array2XML;
use Psalm\Checker\ProjectChecker;
use Psalm\Issue\ClassIssue;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\MethodIssue;
use Psalm\Issue\PropertyIssue;

class IssueBuffer
{
    /**
     * @var array<int, array{severity: string, line_from: int, line_to: int, type: string, message: string,
     * file_name: string, file_path: string, snippet: string, from: int, to: int,
     * snippet_from: int, snippet_to: int, column_from: int, column_to: int}>
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
        $config = Config::getInstance();

        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);

        if (in_array($issue_type, $suppressed_issues, true)) {
            return false;
        }

        if (!$config->reportIssueInFile($issue_type, $e->getFilePath())) {
            return false;
        }

        if ($e instanceof ClassIssue
            && $config->getReportingLevelForClass($issue_type, $e->fq_classlike_name) === Config::REPORT_SUPPRESS
        ) {
            return false;
        }

        if ($e instanceof MethodIssue
            && $config->getReportingLevelForMethod($issue_type, $e->method_id) === Config::REPORT_SUPPRESS
        ) {
            return false;
        }

        if ($e instanceof PropertyIssue
            && $config->getReportingLevelForProperty($issue_type, $e->property_id) === Config::REPORT_SUPPRESS
        ) {
            return false;
        }

        $parent_issue_type = self::getParentIssueType($issue_type);

        if ($parent_issue_type) {
            if (in_array($parent_issue_type, $suppressed_issues, true)) {
                return false;
            }

            if (!$config->reportIssueInFile($parent_issue_type, $e->getFilePath())) {
                return false;
            }
        }

        if (self::$recording_level > 0) {
            self::$recorded_issues[self::$recording_level][] = $e;

            return false;
        }

        return self::add($e);
    }

    /**
     * @param  string $issue_type
     * @return string|null
     */
    private static function getParentIssueType($issue_type)
    {
        if (strpos($issue_type, 'Possibly') === 0) {
            $stripped_issue_type = preg_replace('/^Possibly(False|Null)?/', '', $issue_type);

            if (strpos($stripped_issue_type, 'Invalid') === false && strpos($stripped_issue_type, 'Un') !== 0) {
                $stripped_issue_type = 'Invalid' . $stripped_issue_type;
            }

            return $stripped_issue_type;
        }

        if (preg_match('/^(False|Null)[A-Z]/', $issue_type)) {
            return preg_replace('/^(False|Null)/', 'Invalid', $issue_type);
        }

        return null;
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

        $project_checker = ProjectChecker::getInstance();

        if (!$project_checker->show_issues) {
            return false;
        }

        $error_message = $issue_type . ' - ' . $e->getShortLocation() . ' - ' . $e->getMessage();

        $reporting_level = $config->getReportingLevelForFile($issue_type, $e->getFilePath());

        $parent_issue_type = self::getParentIssueType($issue_type);

        if ($parent_issue_type && $reporting_level === Config::REPORT_ERROR) {
            $parent_reporting_level = $config->getReportingLevelForFile($parent_issue_type, $e->getFilePath());

            if ($parent_reporting_level !== $reporting_level) {
                $reporting_level = $parent_reporting_level;
            }
        }

        if ($reporting_level === Config::REPORT_SUPPRESS) {
            return false;
        }

        if ($reporting_level === Config::REPORT_INFO) {
            if (!self::alreadyEmitted($error_message)) {
                self::$issues_data[] = $e->toArray(Config::REPORT_INFO);
            }

            return false;
        }

        if ($config->throw_exception) {
            throw new Exception\CodeException($error_message);
        }

        if (!self::alreadyEmitted($error_message)) {
            ++self::$error_count;
            self::$issues_data[] = $e->toArray(Config::REPORT_ERROR);
        }

        return true;
    }

    /**
     * @param  array{severity: string, line_from: int, line_to: int, type: string, message: string,
     *  file_name: string, file_path: string, snippet: string, from: int, to: int,
     *  snippet_from: int, snippet_to: int, column_from: int, column_to: int} $issue_data
     *
     * @return string
     */
    protected static function getEmacsOutput(array $issue_data)
    {
        return $issue_data['file_path'] . ':' . $issue_data['line_from'] . ':' . $issue_data['column_from'] . ':' .
            ($issue_data['severity'] === Config::REPORT_ERROR ? 'error' : 'warning') . ' - ' . $issue_data['message'];
    }

    /**
     * @param  array{severity: string, line_from: int, line_to: int, type: string, message: string,
     *  file_name: string, file_path: string, snippet: string, from: int, to: int,
     *  snippet_from: int, snippet_to: int, column_from: int, column_to: int} $issue_data
     *
     * @return string
     */
    protected static function getPylintOutput(array $issue_data)
    {
        $message = sprintf(
            '%s: %s',
            $issue_data['type'],
            $issue_data['message']
        );
        if ($issue_data['severity'] === Config::REPORT_ERROR) {
            $code = 'E0001';
        } else {
            $code = 'W0001';
        }

        // https://docs.pylint.org/en/1.6.0/output.html doesn't mention what to do about 'column',
        // but it's still useful for users.
        // E.g. jenkins can't parse %s:%d:%d.
        $message = sprintf('%s (column %d)', $message, $issue_data['column_from']);
        $issue_string = sprintf(
            '%s:%d: [%s] %s',
            $issue_data['file_name'],
            $issue_data['line_from'],
            $code,
            $message
        );

        return $issue_string;
    }

    /**
     * @param  array{severity: string, line_from: int, line_to: int, type: string, message: string,
     *  file_name: string, file_path: string, snippet: string, from: int, to: int,
     *  snippet_from: int, snippet_to: int, column_from: int, column_to: int} $issue_data
     * @param  bool  $include_snippet
     * @param  bool  $use_color
     *
     * @return string
     */
    protected static function getConsoleOutput(array $issue_data, $use_color, $include_snippet = true)
    {
        $issue_string = '';

        $is_error = $issue_data['severity'] === Config::REPORT_ERROR;

        if ($is_error) {
            $issue_string .= ($use_color ? "\e[0;31mERROR\e[0m" : 'ERROR');
        } else {
            $issue_string .= 'INFO';
        }

        $issue_string .= ': ' . $issue_data['type'] . ' - ' . $issue_data['file_name'] . ':' .
            $issue_data['line_from'] . ':' . $issue_data['column_from'] . ' - ' . $issue_data['message'] . "\n";

        if ($include_snippet) {
            $snippet = $issue_data['snippet'];

            if (!$use_color) {
                $issue_string .= $snippet;
            } else {
                $selection_start = $issue_data['from'] - $issue_data['snippet_from'];
                $selection_length = $issue_data['to'] - $issue_data['from'];

                $issue_string .= substr($snippet, 0, $selection_start)
                    . ($is_error ? "\e[97;41m" : "\e[30;47m") . substr($snippet, $selection_start, $selection_length)
                    . "\e[0m" . substr($snippet, $selection_length + $selection_start) . "\n";
            }
        }

        return $issue_string;
    }

    /**
     * @return array<int, array{severity: string, line_from: int, line_to: int, type: string, message: string,
     *  file_name: string, file_path: string, snippet: string, from: int, to: int, snippet_from: int, snippet_to: int,
     *  column_from: int, column_to: int}>
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
            $error_message = $issue['type']
                . ' - ' . $issue['file_name']
                . ':' . $issue['line_from']
                . ' - ' . $issue['message'];

            if (!self::alreadyEmitted($error_message)) {
                self::$issues_data[] = $issue;
            }
        }
    }

    /**
     * @param  ProjectChecker                   $project_checker
     * @param  bool                             $is_full
     * @param  float                            $start_time
     * @param  bool                             $add_stats
     * @param  array<string,array<string,int>>  $issue_baseline
     *
     * @return void
     */
    public static function finish(
        ProjectChecker $project_checker,
        bool $is_full,
        float $start_time,
        bool $add_stats = false,
        array $issue_baseline = []
    ) {
        if ($project_checker->output_format === ProjectChecker::TYPE_CONSOLE) {
            echo "\n";
        }

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
                    $type = $issue_data['type'];

                    if (isset($issue_baseline[$file][$type]) && $issue_baseline[$file][$type] > 0) {
                        $issue_data['severity'] = Config::REPORT_INFO;
                        $issue_baseline[$file][$type] = (int)$issue_baseline[$file][$type] - 1;
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
                $project_checker->output_format,
                $project_checker->use_color,
                $project_checker->show_snippet,
                $project_checker->show_info
            );
        }

        foreach ($project_checker->reports as $format => $path) {
            file_put_contents(
                $path,
                self::getOutput($format, $project_checker->use_color)
            );
        }

        if ($project_checker->output_format === ProjectChecker::TYPE_CONSOLE) {
            echo str_repeat('-', 30) . "\n";

            if ($error_count) {
                echo ($project_checker->use_color
                    ? "\e[0;31m" . $error_count . " errors\e[0m"
                    : $error_count . ' errors'
                ) . ' found' . "\n";
            } else {
                echo 'No errors found!' . "\n";
            }

            if ($info_count && $project_checker->show_info) {
                echo str_repeat('-', 30) . "\n";

                echo $info_count . ' other issues found.' . "\n"
                    . 'You can hide them with ' .
                    ($project_checker->use_color
                        ? "\e[30;48;5;195m--show-info=false\e[0m"
                        : '--show-info=false') . "\n";
            }

            echo str_repeat('-', 30) . "\n" . "\n";

            if ($start_time) {
                echo 'Checks took ' . number_format(microtime(true) - $start_time, 2) . ' seconds';
                echo ' and used ' . number_format(memory_get_peak_usage() / (1024 * 1024), 3) . 'MB of memory' . "\n";

                if ($is_full) {
                    $analysis_summary = $project_checker->codebase->analyzer->getTypeInferenceSummary();
                    echo $analysis_summary . "\n";
                }

                if ($add_stats) {
                    echo '-----------------' . "\n";
                    echo $project_checker->codebase->analyzer->getNonMixedStats();
                    echo "\n";
                }
            }
        }

        if ($error_count) {
            exit(1);
        }

        if ($is_full && $start_time) {
            $project_checker->file_reference_provider->removeDeletedFilesFromReferences();

            if ($project_checker->parser_cache_provider) {
                $project_checker->parser_cache_provider->processSuccessfulRun($start_time);
            }
        }
    }

    /**
     * @param string $format
     * @param bool   $use_color
     * @param bool   $show_snippet
     * @param bool   $show_info
     *
     * @return string
     */
    public static function getOutput($format, $use_color, $show_snippet = true, $show_info = true)
    {
        if ($format === ProjectChecker::TYPE_JSON) {
            return json_encode(self::$issues_data) . "\n";
        } elseif ($format === ProjectChecker::TYPE_XML) {
            $xml = Array2XML::createXML('report', ['item' => self::$issues_data]);

            return $xml->saveXML();
        } elseif ($format === ProjectChecker::TYPE_EMACS) {
            $output = '';
            foreach (self::$issues_data as $issue_data) {
                $output .= self::getEmacsOutput($issue_data) . "\n";
            }

            return $output;
        } elseif ($format === ProjectChecker::TYPE_PYLINT) {
            $output = '';
            foreach (self::$issues_data as $issue_data) {
                $output .= self::getPylintOutput($issue_data) . "\n";
            }

            return $output;
        }

        $output = '';
        foreach (self::$issues_data as $issue_data) {
            if (!$show_info && $issue_data['severity'] === Config::REPORT_INFO) {
                continue;
            }

            $output .= self::getConsoleOutput($issue_data, $use_color, $show_snippet) . "\n" . "\n";
        }

        return $output;
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
