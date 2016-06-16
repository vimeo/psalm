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

        if ($config->stop_on_error) {
            throw new CodeException($e->getMessage());
        }

        echo get_class($e) . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;

        return true;
    }
}
