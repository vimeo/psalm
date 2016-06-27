<?php

namespace CodeInspector;

class IssueBuffer
{
    protected static $errors = [];

    public static function accepts(Issue\CodeIssue $e)
    {
        $config = Config::getInstance();

        if ($config->excludeIssueInFile(get_class($e), $e->getFileName())) {
            return false;
        }

        $error_class_name = array_pop(explode('\\', get_class($e)));

        $error_message = $error_class_name . ' - ' . $e->getMessage();

        $reporting_level = $config->getReportingLevel(get_class($e));

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

        echo "\033[0;31m" . 'ERROR: ' . "\033[0m" . $error_message . PHP_EOL;

        if ($config->stop_on_first_error) {
            exit(1);
        }

        self::$errors[] = $error_message;

        return true;
    }

    public static function finish()
    {
        if (count(self::$errors)) {
            exit(1);
        }
    }
}
