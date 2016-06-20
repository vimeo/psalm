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
        $filetype_handlers = $config->getFiletypeHandlers();
        $base_dir = $config->getBaseDir();

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir . $dir_name));
        $iterator->rewind();

        $files = [];

        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                $extension = $iterator->getExtension();
                if (in_array($extension, $file_extensions)) {
                    $file_name = $iterator->getRealPath();

                    if ($debug) {
                        echo 'Checking ' . $file_name . PHP_EOL;
                    }

                    if (isset($filetype_handlers[$extension])) {
                        /** @var FileChecker */
                        $file_checker = new $filetype_handlers[$extension]($file_name);
                    }
                    else {
                        $file_checker = new FileChecker($file_name);
                    }

                    $file_checker->check(true);
                }
            }

            $iterator->next();
        }
    }

    public static function checkFile($file_name, $debug = false)
    {
        if ($debug) {
            echo 'Checking ' . $file_name . PHP_EOL;
        }

        $config = Config::getInstance();

        $base_dir = $config->getBaseDir();

        $extension = array_pop(explode('.', $file_name));

        $filetype_handlers = $config->getFiletypeHandlers();

        if (isset($filetype_handlers[$extension])) {
            /** @var FileChecker */
            $file_checker = new $filetype_handlers[$extension]($base_dir . $file_name);
        }
        else {
            $file_checker = new FileChecker($base_dir . $file_name);
        }

        $file_checker->check(true);
    }
}
