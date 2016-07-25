<?php

namespace Psalm;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ProjectChecker
{
    /**
     * Cached config
     * @var Config|null
     */
    protected static $config;

    /**
     * Whether or not to use colors in error output
     * @var boolean
     */
    public static $use_color = true;

    public static function check($debug = false)
    {
        if (!self::$config) {
            self::$config = self::getConfigForPath(getcwd());
        }

        foreach (self::$config->getIncludeDirs() as $dir_name) {
            self::checkDirWithConfig($dir_name, self::$config, $debug);
        }

        IssueBuffer::finish();
    }

    public static function checkDir($dir_name, $debug = false)
    {
        if (!self::$config) {
            self::$config = self::getConfigForPath($dir_name);
        }

        self::checkDirWithConfig($dir_name, self::$config, $debug);

        IssueBuffer::finish();
    }

    protected static function checkDirWithConfig($dir_name, Config $config, $debug)
    {
        $file_extensions = $config->getFileExtensions();
        $filetype_handlers = $config->getFiletypeHandlers();

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_name));
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

        if (!self::$config) {
            self::$config = self::getConfigForPath($file_name);
        }

        $extension = array_pop(explode('.', $file_name));

        $filetype_handlers = self::$config->getFiletypeHandlers();

        if (isset($filetype_handlers[$extension])) {
            /** @var FileChecker */
            $file_checker = new $filetype_handlers[$extension]($file_name);
        }
        else {
            $file_checker = new FileChecker($file_name);
        }

        $file_checker->check(true);

        IssueBuffer::finish();
    }

    /**
     * Gets a Config object from an XML file.
     * Searches up a folder hierachy for the most immediate config.
     *
     * @param  string $path
     * @return Config
     */
    protected static function getConfigForPath($path)
    {
        $dir_path = realpath($path) . '/';

        if (!is_dir($dir_path)) {
            $dir_path = dirname($dir_path) . '/';
        }

        $config = null;

        do {
            $maybe_path = $dir_path . Config::DEFAULT_FILE_NAME;

            if (file_exists($maybe_path)) {
                $config = \Psalm\Config::loadFromXML($maybe_path);
                break;
            }

            $dir_path = preg_replace('/[^\/]+\/$/', '', $dir_path);
        }
        while ($dir_path !== '/');

        if (!$config) {
            throw new Exception\ConfigException('Config not found for path ' . $path);
        }

        return $config;
    }

    public static function setConfigXML($path_to_config)
    {
        if (!file_exists($path_to_config)) {
            throw new Exception\ConfigException('Config not found at location ' . $path_to_config);
        }

        self::$config = \Psalm\Config::loadFromXML($path_to_config);
    }
}
