<?php

namespace CodeInspector;

class IssueHandler
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

        if ($config->getReportingLevel(get_class($e)) !== Config::REPORT_ERROR) {
            echo $error_message . PHP_EOL;
            return false;
        }

        echo $error_message . PHP_EOL;

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
