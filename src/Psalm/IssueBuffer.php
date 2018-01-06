<?php
namespace Psalm;

use LSS\Array2XML;
use Psalm\Checker\ProjectChecker;
use Psalm\FileManipulation\FileManipulation;
use Psalm\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\CodeIssue;

class IssueBuffer
{
    /**
     * @var array<int, array{severity: string, line_number: string, type: string, message: string, file_name: string,
     *  file_path: string, snippet: string, from: int, to: int, snippet_from: int, snippet_to: int, column: int}>
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
     * @var float
     */
    protected static $start_time = 0;

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

        if (self::$recording_level > 0) {
            self::$recorded_issues[self::$recording_level][] = $e;

            return false;
        }

        return self::add($e);
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
        $project_checker = ProjectChecker::getInstance();

        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);

        $issues_to_fix = \Psalm\Checker\ProjectChecker::getInstance()->getIssuesToFix();

        if (isset($issues_to_fix[$issue_type])
            && $e instanceof \Psalm\Issue\FixableCodeIssue
            && $replacement_text = $e->getReplacementText()
        ) {
            $code_location = $e->getLocation();
            $bounds = $code_location->getSelectionBounds();
            FileManipulationBuffer::add(
                $e->getFilePath(),
                [new FileManipulation($bounds[0], $bounds[1], $replacement_text)]
            );

            return false;
        }

        $error_message = $issue_type . ' - ' . $e->getShortLocation() . ' - ' . $e->getMessage();

        $reporting_level = $config->getReportingLevelForFile($issue_type, $e->getFilePath());

        if ($reporting_level === Config::REPORT_SUPPRESS) {
            return false;
        }

        if ($reporting_level === Config::REPORT_INFO) {
            if ($project_checker->show_info && !self::alreadyEmitted($error_message)) {
                self::$issues_data[] = $e->toArray(Config::REPORT_INFO);
            }

            return false;
        }

        if ($config->throw_exception) {
            throw new Exception\CodeException($error_message);
        }

        if (!self::alreadyEmitted($error_message)) {
            self::$issues_data[] = $e->toArray(Config::REPORT_ERROR);
        }

        if ($config->stop_on_first_error) {
            exit(1);
        }

        return true;
    }

    /**
     * @param  array{severity: string, line_number: string, type: string, message: string, file_name: string,
     *  file_path: string, snippet: string, from: int, to: int, snippet_from: int, snippet_to: int,
     *  column: int} $issue_data
     *
     * @return string
     */
    protected static function getEmacsOutput(array $issue_data)
    {
        return $issue_data['file_path'] . ':' . $issue_data['line_number'] . ':' . $issue_data['column'] . ':' .
            ($issue_data['severity'] === Config::REPORT_ERROR ? 'error' : 'warning') . ' - ' . $issue_data['message'];
    }

    /**
     * @param  array{severity: string, line_number: string, type: string, message: string, file_name: string,
     *  file_path: string, snippet: string, from: int, to: int, snippet_from: int, snippet_to: int,
     *  column: int} $issue_data
     * @param  bool  $use_color
     *
     * @return string
     */
    protected static function getConsoleOutput(array $issue_data, $use_color)
    {
        $issue_string = '';

        if ($issue_data['severity'] === Config::REPORT_ERROR) {
            $issue_string .= ($use_color ? "\e[0;31mERROR\e[0m" : 'ERROR');
        } else {
            $issue_string .= 'INFO';
        }

        $issue_string .= ': ' . $issue_data['type'] . ' - ' . $issue_data['file_name'] . ':' .
            $issue_data['line_number'] . ':' . $issue_data['column'] . ' - ' . $issue_data['message'] . PHP_EOL;

        $snippet = $issue_data['snippet'];

        if (!$use_color) {
            $issue_string .= $snippet;
        } else {
            $selection_start = $issue_data['from'] - $issue_data['snippet_from'];
            $selection_length = $issue_data['to'] - $issue_data['from'];

            $issue_string .= substr($snippet, 0, $selection_start) .
                "\e[97;41m" . substr($snippet, $selection_start, $selection_length) .
                "\e[0m" . substr($snippet, $selection_length + $selection_start) . PHP_EOL;
        }

        return $issue_string;
    }

    /**
     * @return array<int, array{severity: string, line_number: string, type: string, message: string, file_name: string,
     *  file_path: string, snippet: string, from: int, to: int, snippet_from: int, snippet_to: int, column: int}>
     */
    public static function getIssuesData()
    {
        return self::$issues_data;
    }

    /**
     * @param array<int, array{severity: string, line_number: string, type: string, message: string,
     *  file_name: string, file_path: string, snippet: string, from: int, to: int, snippet_from: int,
     *  snippet_to: int, column: int}> $issues_data
     *
     * @return void
     */
    public static function addIssues(array $issues_data)
    {
        self::$issues_data = array_merge($issues_data, self::$issues_data);
    }

    /**
     * @param  bool                 $is_full
     * @param  int|null             $start_time
     * @param  array<string, bool>  $visited_files
     *
     * @return void
     */
    public static function finish(ProjectChecker $project_checker, $is_full, $start_time, array $visited_files)
    {
        Provider\FileReferenceProvider::updateReferenceCache($project_checker, $visited_files);

        $has_error = false;

        $project_checker = ProjectChecker::getInstance();

        if (self::$issues_data) {
            usort(
                self::$issues_data,
                /** @return int */
                function (array $d1, array $d2) {
                    if ($d1['file_path'] === $d2['file_path']) {
                        if ($d1['line_number'] === $d2['line_number']) {
                            if ($d1['column'] === $d2['column']) {
                                return 0;
                            }

                            return $d1['column'] > $d2['column'] ? 1 : -1;
                        }

                        return $d1['line_number'] > $d2['line_number'] ? 1 : -1;
                    }

                    return $d1['file_path'] > $d2['file_path'] ? 1 : -1;
                }
            );

            foreach (self::$issues_data as $issue_data) {
                if ($issue_data['severity'] === Config::REPORT_ERROR) {
                    $has_error = true;
                }
            }

            echo self::getOutput($project_checker->output_format, $project_checker->use_color);
        }
        foreach ($project_checker->reports as $format => $path) {
            file_put_contents(
                $path,
                self::getOutput($format, $project_checker->use_color)
            );
        }

        if ($start_time) {
            echo 'Checks took ' . ((float)microtime(true) - self::$start_time);
            echo ' and used ' . number_format(memory_get_peak_usage() / (1024 * 1024), 3) . 'MB' . PHP_EOL;
        }

        if ($has_error) {
            exit(1);
        }

        if ($is_full && $start_time) {
            $project_checker->cache_provider->processSuccessfulRun($start_time);
        }
    }

    /**
     * @param string $format
     * @param bool   $useColor
     *
     * @return string
     */
    public static function getOutput($format, $useColor)
    {
        if ($format === ProjectChecker::TYPE_JSON) {
            return json_encode(self::$issues_data) . PHP_EOL;
        } elseif ($format === ProjectChecker::TYPE_XML) {
            $xml = Array2XML::createXML('report', ['item' => self::$issues_data]);

            return $xml->saveXML();
        } elseif ($format === ProjectChecker::TYPE_EMACS) {
            $output = '';
            foreach (self::$issues_data as $issue_data) {
                $output .= self::getEmacsOutput($issue_data) . PHP_EOL;
            }

            return $output;
        }

        $output = '';
        foreach (self::$issues_data as $issue_data) {
            $output .= self::getConsoleOutput($issue_data, $useColor) . PHP_EOL . PHP_EOL;
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
     * @param float $time
     *
     * @return void
     */
    public static function setStartTime($time)
    {
        self::$start_time = $time;
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
