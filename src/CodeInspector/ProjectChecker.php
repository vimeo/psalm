<?php

namespace CodeInspector;

class ProjectChecker
{
    public static function check($debug = false)
    {
        $config = Config::getInstance();

        $files = $config->getFilesToCheck();

        foreach ($files as $file_name) {
            if ($debug) {
                echo 'Checking ' . $file_name . PHP_EOL;
            }

            $file_checker = new FileChecker($file_name);
            $file_checker->check(true);
        }

    }
}
