<?php

namespace Psalm\Checker;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Psalm\Config;
use Psalm\IssueBuffer;
use Psalm\Exception;

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

    /**
     * Whether or not to show informational messages
     * @var boolean
     */
    public static $show_info = true;

    /**
     * @param  boolean $debug
     * @param  boolean $is_diff
     * @return void
     */
    public static function check($debug = false, $is_diff = false)
    {
        $cwd = getcwd();

        if (!$cwd) {
            throw new \InvalidArgumentException('Cannot work with empty cwd');
        }

        if (!self::$config) {
            self::$config = self::getConfigForPath($cwd);
        }

        $diff_files = null;
        $deleted_files = null;

        if ($is_diff && FileChecker::loadReferenceCache() && FileChecker::canDiffFiles()) {
            $deleted_files = FileChecker::getDeletedReferencedFiles();
            $diff_files = $deleted_files;

            foreach (self::$config->getIncludeDirs() as $dir_name) {
                $diff_files = array_merge($diff_files, self::getDiffFilesInDir($dir_name, self::$config));
            }
        }

        if ($diff_files === null || $deleted_files === null || count($diff_files) > 200) {
            foreach (self::$config->getIncludeDirs() as $dir_name) {
                self::checkDirWithConfig($dir_name, self::$config, $debug);
            }
        }
        else {
            if ($debug) {
                echo count($diff_files) . ' changed files' . PHP_EOL;
            }

            $file_list = self::getReferencedFilesFromDiff($diff_files);
            // strip out deleted files
            $file_list = array_diff($file_list, $deleted_files);
            self::checkDiffFilesWithConfig($file_list, self::$config, $debug);
        }

        IssueBuffer::finish(true);
    }

    /**
     * @param  string  $dir_name
     * @param  boolean $debug
     * @return void
     */
    public static function checkDir($dir_name, $debug = false)
    {
        if (!self::$config) {
            self::$config = self::getConfigForPath($dir_name);
            self::$config->hide_external_errors = self::$config->isInProjectDirs(self::$config->shortenFileName($dir_name));
        }

        FileChecker::loadReferenceCache();

        self::checkDirWithConfig($dir_name, self::$config, $debug);

        IssueBuffer::finish();
    }

    /**
     * @param  string $dir_name
     * @param  Config $config
     * @param  bool   $debug
     * @return void
     */
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
                    $file_name = (string)$iterator->getRealPath();

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

    /**
     * @param  string $dir_name
     * @param  Config $config
     * @return array<string>
     */
    protected static function getDiffFilesInDir($dir_name, Config $config)
    {
        $file_extensions = $config->getFileExtensions();
        $filetype_handlers = $config->getFiletypeHandlers();

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_name));
        $iterator->rewind();

        $diff_files = [];

        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                $extension = $iterator->getExtension();
                if (in_array($extension, $file_extensions)) {
                    $file_name = (string)$iterator->getRealPath();

                    if (FileChecker::hasFileChanged($file_name)) {
                        $diff_files[] = $file_name;
                    }
                }
            }

            $iterator->next();
        }

        return $diff_files;
    }

    /**
     * @param  array<string>    $file_list
     * @param  Config           $config
     * @param  bool             $debug
     * @return void
     */
    protected static function checkDiffFilesWithConfig(array $file_list = [], Config $config, $debug)
    {
        $file_extensions = $config->getFileExtensions();
        $filetype_handlers = $config->getFiletypeHandlers();



        foreach ($file_list as $file_name) {
            if (!file_exists($file_name)) {
                continue;
            }

            if (!$config->isInProjectDirs(preg_replace('/^' . preg_quote($config->getBaseDir(), '/') . '/', '', $file_name))) {
                if ($debug) {
                    echo('skipping ' . $file_name . PHP_EOL);
                }

                continue;
            }

            $extension = pathinfo($file_name, PATHINFO_EXTENSION);

            if ($debug) {
                echo 'Checking affected file ' . $file_name . PHP_EOL;
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

    /**
     * @param  string  $file_name
     * @param  boolean $debug
     * @return void
     */
    public static function checkFile($file_name, $debug = false)
    {
        if ($debug) {
            echo 'Checking ' . $file_name . PHP_EOL;
        }

        if (!self::$config) {
            self::$config = self::getConfigForPath($file_name);
        }

        self::$config->hide_external_errors = self::$config->isInProjectDirs(self::$config->shortenFileName($file_name));

        $file_name_parts = explode('.', $file_name);

        $extension = array_pop($file_name_parts);

        $filetype_handlers = self::$config->getFiletypeHandlers();

        FileChecker::loadReferenceCache();

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

                if ($config->autoloader) {
                    require_once($dir_path . $config->autoloader);
                }

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

    /**
     * @param string $path_to_config
     */
    public static function setConfigXML($path_to_config)
    {
        if (!file_exists($path_to_config)) {
            throw new Exception\ConfigException('Config not found at location ' . $path_to_config);
        }

        $dir_path = dirname($path_to_config) . '/';

        self::$config = \Psalm\Config::loadFromXML($path_to_config);

        if (self::$config->autoloader) {
            require_once($dir_path . self::$config->autoloader);
        }
    }

    /**
     * @param  array<string>  $diff_files
     * @return array<string>
     */
    public static function getReferencedFilesFromDiff(array $diff_files)
    {
        $all_inherited_files_to_check = $diff_files;

        while ($diff_files) {
            $diff_file = array_shift($diff_files);

            $dependent_files = FileChecker::getFilesInheritingFromFile($diff_file);
            $new_dependent_files = array_diff($dependent_files, $all_inherited_files_to_check);

            $all_inherited_files_to_check += $new_dependent_files;
            $diff_files += $new_dependent_files;
        }

        $all_files_to_check = $all_inherited_files_to_check;

        foreach ($all_inherited_files_to_check as $file_name) {
            $dependent_files = FileChecker::getFilesReferencingFile($file_name);
            $all_files_to_check = array_merge($dependent_files, $all_files_to_check);
        }

        return array_unique($all_files_to_check);
    }
}
