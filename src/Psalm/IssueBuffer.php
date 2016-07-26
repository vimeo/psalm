<?php

namespace Psalm;

class IssueBuffer
{
    protected static $errors = [];

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

        self::add($e);

        return true;
    }

    public static function add(Issue\CodeIssue $e)
    {
        $config = Config::getInstance();

        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);

        $error_message = $issue_type . ' - ' . $e->getMessage();

        $reporting_level = $config->getReportingLevel($issue_type);

        switch ($reporting_level) {
            case Config::REPORT_INFO:
                echo 'INFO: ' . $error_message . PHP_EOL;
                return false;

            case Config::REPORT_SUPPRESS:
                return false;
        }

        if ($config->throw_exception) {
            throw new Exception\CodeException($error_message);
        }

        echo (ProjectChecker::$use_color ? "\033[0;31m" : '') . 'ERROR: ' . (ProjectChecker::$use_color ? "\033[0m" : '') . $error_message . PHP_EOL;

        if ($config->stop_on_first_error) {
            exit(1);
        }

        self::$errors[] = $error_message;
    }

    public static function finish()
    {
        if (count(self::$errors)) {
            exit(1);
        }
    }
}
