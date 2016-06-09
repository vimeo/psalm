<?php

namespace CodeInspector;

class ExceptionHandler
{
    public static function accepts(Issue\CodeIssue $e)
    {
        $config = Config::getInstance();

        if ($config->stopOnError) {
            die($e->getMessage());
        }

        if ($config->excludeIssueInFile(get_class($e), $e->getFileName())) {
            return false;
        }


    }
}
