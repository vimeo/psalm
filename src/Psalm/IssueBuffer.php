<?php
namespace Psalm;

use Psalm\Checker\ProjectChecker;

class IssueBuffer
{
    /**
     * @var array<int, array>
     */
    protected static $issue_data = [];

    /**
     * @var array<int, string>
     */
    protected static $errors = [];

    /**
     * @var array<string, bool>
     */
    protected static $emitted = [];

    /**
     * @var int
     */
    protected static $start_time = 0;

    /**
     * @param   Issue\CodeIssue $e
     * @param   array           $suppressed_issues
     * @return  bool
     */
    public static function accepts(Issue\CodeIssue $e, array $suppressed_issues = [])
    {
        $config = Config::getInstance();

        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);

        if (in_array($issue_type, $suppressed_issues)) {
            return false;
        }

        if ($config->excludeIssueInFile($issue_type, $e->getFileName())) {
            return false;
        }

        return self::add($e);
    }

    /**
     * @param   Issue\CodeIssue $e
     * @return  bool
     * @throws  Exception\CodeException
     */
    public static function add(Issue\CodeIssue $e)
    {
        $config = Config::getInstance();
        $project_checker = ProjectChecker::getInstance();

        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);

        $error_message = $issue_type . ' - ' . $e->getShortLocation() . ' - ' . $e->getMessage();

        $reporting_level = $config->getReportingLevelForFile($issue_type, $e->getFileName());

        if ($reporting_level === Config::REPORT_SUPPRESS) {
            return false;
        }

        if ($reporting_level === Config::REPORT_INFO) {
            if ($project_checker->show_info && !self::alreadyEmitted($error_message)) {
                switch ($project_checker->output_format) {
                    case ProjectChecker::TYPE_CONSOLE:
                        echo 'INFO: ' . $error_message . PHP_EOL;
                        break;

                    case ProjectChecker::TYPE_JSON:
                        self::$issue_data[] = self::getIssueArray($e, Config::REPORT_INFO);
                        break;
                }
            }
            return false;
        }

        if ($config->throw_exception) {
            throw new Exception\CodeException($error_message);
        }

        if (!self::alreadyEmitted($error_message)) {
            switch ($project_checker->output_format) {
                case ProjectChecker::TYPE_CONSOLE:
                    echo ($project_checker->use_color ? "\e[0;31mERROR\e[0m" : 'ERROR') .
                        ': ' . $error_message . PHP_EOL;

                    echo self::getSnippet($e, $project_checker->use_color) . PHP_EOL . PHP_EOL;

                    break;

                case ProjectChecker::TYPE_JSON:
                    self::$issue_data[] = self::getIssueArray($e);
                    break;
            }
        }

        if ($config->stop_on_first_error) {
            exit(1);
        }

        return true;
    }

    /**
     * @param  Issue\CodeIssue $e
     * @param  string          $severity
     * @return array
     */
    protected static function getIssueArray(Issue\CodeIssue $e, $severity = Config::REPORT_ERROR)
    {
        $location = $e->getLocation();
        $selection_bounds = $location->getSelectionBounds();

        return [
            'type' => $severity,
            'line_number' => $location->getLineNumber(),
            'message' => $e->getMessage(),
            'file_name' => $location->file_name,
            'file_path' => $location->file_path,
            'snippet' => $location->getSnippet(),
            'from' => $selection_bounds[0],
            'to' => $selection_bounds[1],
        ];
    }

    /**
     * @return array<int, array>
     */
    public static function getIssueData()
    {
        return self::$issue_data;
    }

    /**
     * @param  Issue\CodeIssue $e
     * @param  boolean         $use_color
     * @return string
     */
    protected static function getSnippet(Issue\CodeIssue $e, $use_color = true)
    {
        $location = $e->getLocation();

        $snippet = $location->getSnippet();

        if (!$use_color) {
            return $snippet;
        }

        $snippet_bounds = $location->getSnippetBounds();
        $selection_bounds = $location->getSelectionBounds();

        $selection_start = $selection_bounds[0] - $snippet_bounds[0];
        $selection_length = $selection_bounds[1] - $selection_bounds[0];

        return substr($snippet, 0, $selection_start) .
            "\e[97;41m" . substr($snippet, $selection_start, $selection_length) .
            "\e[0m" . substr($snippet, $selection_length + $selection_start) . PHP_EOL;
    }

    /**
     * @param  bool     $is_full
     * @param  int|null $start_time
     * @param  bool     $debug
     * @return void
     */
    public static function finish($is_full = false, $start_time = null, $debug = false)
    {
        Checker\FileChecker::updateReferenceCache();

        if ($start_time) {
            echo('Checks took ' . ((float)microtime(true) - self::$start_time));
            echo(' and used ' . memory_get_peak_usage() . PHP_EOL);
        }

        if (count(self::$emitted)) {
            $project_checker = ProjectChecker::getInstance();
            if ($project_checker->output_format === ProjectChecker::TYPE_JSON) {
                echo json_encode(self::$issue_data) . PHP_EOL;
            }

            exit(1);
        }

        if ($is_full && $start_time) {
            Checker\FileChecker::goodRun($start_time);
        }
    }

    /**
     * @param  string $message
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
     * @param int $time
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
        self::$issue_data = [];
        self::$emitted = [];
    }
}
