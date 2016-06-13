<?php

namespace CodeInspector;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ProjectChecker
{
    public static function check($debug = false)
    {
        foreach (Config::getInstance()->getIncludeDirs() as $dir_name) {
            self::checkDir($dir_name, $debug);
        }
    }

    public static function checkDir($dir_name, $debug = false)
    {
        $config = Config::getInstance();

        $file_extensions = $config->getFileExtensions();
        $base_dir = $config->getBaseDir();

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir . '/' . $dir_name));
        $iterator->rewind();

        $files = [];

        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                if (in_array($iterator->getExtension(), $file_extensions)) {
                    $files[] = $iterator->getRealPath();
                }
            }

            $iterator->next();
        }

        foreach ($files as $file_name) {
            if ($debug) {
                echo 'Checking ' . $file_name . PHP_EOL;
            }

            $file_checker = new FileChecker($file_name);
            $file_checker->check(true);
        }
    }
}
