<?php

namespace CodeInspector;

class ExceptionHandler
{
    public static function accepts(Issue\CodeIssue $e)
    {
        $config = Config::getInstance();

        if ($config->excludeIssueInFile(get_class($e), $e->getFileName())) {
            return false;
        }

        $error_class_name = array_pop(explode('\\', get_class($e)));

        $error_message = $error_class_name . ' - ' . $e->getMessage();

        if ($config->stop_on_error) {
            throw new CodeException($error_message);
        }

        echo $error_message . PHP_EOL;

        return true;
    }
}
