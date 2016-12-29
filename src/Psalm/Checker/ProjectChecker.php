<?php
namespace Psalm\Checker;

use Psalm\Config;
use Psalm\Exception;
use Psalm\IssueBuffer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ProjectChecker
{
    /**
     * Cached config
     *
     * @var Config|null
     */
    protected $config;

    /**
     * @var self
     */
    public static $instance;

    /**
     * Whether or not to use colors in error output
     *
     * @var boolean
     */
    public $use_color;

    /**
     * Whether or not to show informational messages
     *
     * @var boolean
     */
    public $show_info;

    /**
     * @var string
     */
    public $output_format;

    /**
     * @var array<string, string>
     */
    public $fake_files = [];

    const TYPE_CONSOLE = 'console';
    const TYPE_JSON = 'json';

    /**
     * @param boolean $use_color
     * @param boolean $show_info
     * @param string  $output_format
     */
    public function __construct($use_color = true, $show_info = true, $output_format = self::TYPE_CONSOLE)
    {
        $this->use_color = $use_color;
        $this->show_info = $show_info;

        if (!in_array($output_format, [self::TYPE_CONSOLE, self::TYPE_JSON])) {
            throw new \UnexpectedValueException('Unrecognised output format ' . $output_format);
        }

        $this->output_format = $output_format;
        self::$instance = $this;
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * @param  boolean $debug
     * @param  boolean $is_diff
     * @param  boolean $update_docblocks
     * @return void
     */
    public function check($debug = false, $is_diff = false, $update_docblocks = false)
    {
        $cwd = getcwd();

        $start_checks = (int)microtime(true);

        if (!$cwd) {
            throw new \InvalidArgumentException('Cannot work with empty cwd');
        }

        if (!$this->config) {
            $this->config = self::getConfigForPath($cwd);
        }

        $diff_files = null;
        $deleted_files = null;

        if ($is_diff && FileChecker::loadReferenceCache() && FileChecker::canDiffFiles()) {
            $deleted_files = FileChecker::getDeletedReferencedFiles();
            $diff_files = $deleted_files;

            foreach ($this->config->getProjectDirectories() as $dir_name) {
                $diff_files = array_merge($diff_files, self::getDiffFilesInDir($dir_name, $this->config));
            }
        }

        $files_checked = [];

        if ($diff_files === null || $deleted_files === null || count($diff_files) > 200) {
            foreach ($this->config->getProjectDirectories() as $dir_name) {
                $this->checkDirWithConfig($dir_name, $this->config, $debug, $update_docblocks);
            }
        } else {
            if ($debug) {
                echo count($diff_files) . ' changed files' . PHP_EOL;
            }

            $file_list = self::getReferencedFilesFromDiff($diff_files);

            // strip out deleted files
            $file_list = array_diff($file_list, $deleted_files);
            $this->checkDiffFilesWithConfig($this->config, $debug, $file_list);
        }

        $removed_parser_files = FileChecker::deleteOldParserCaches(
            $is_diff ? FileChecker::getLastGoodRun() : $start_checks
        );

        if ($debug && $removed_parser_files) {
            echo 'Removed ' . $removed_parser_files . ' old parser caches' . PHP_EOL;
        }

        if ($is_diff) {
            FileChecker::touchParserCaches($this->getAllFiles($this->config), $start_checks);
        }

        IssueBuffer::finish(true, (int)$start_checks, $debug);
    }

    /**
     * @param  string  $dir_name
     * @param  boolean $debug
     * @param  boolean $update_docblocks
     * @return void
     */
    public function checkDir($dir_name, $debug = false, $update_docblocks = false)
    {
        if (!$this->config) {
            $this->config = self::getConfigForPath($dir_name);
            $this->config->hide_external_errors = $this->config->isInProjectDirs(
                $this->config->shortenFileName($dir_name)
            );
        }

        FileChecker::loadReferenceCache();

        $this->checkDirWithConfig($dir_name, $this->config, $debug, $update_docblocks);

        IssueBuffer::finish(false, null, $debug);
    }

    /**
     * @param  string $dir_name
     * @param  Config $config
     * @param  bool   $debug
     * @param  bool   $update_docblocks
     * @return void
     */
    protected function checkDirWithConfig($dir_name, Config $config, $debug, $update_docblocks)
    {
        $file_extensions = $config->getFileExtensions();
        $filetype_handlers = $config->getFiletypeHandlers();

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_name));
        $iterator->rewind();

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
                    } else {
                        $file_checker = new FileChecker($file_name);
                    }

                    $file_checker->check(true, true, null, true, $update_docblocks);
                }
            }

            $iterator->next();
        }
    }

    /**
     * @param  Config $config
     * @return array<int, string>
     */
    protected function getAllFiles(Config $config)
    {
        $file_extensions = $config->getFileExtensions();
        $file_names = [];

        foreach ($config->getProjectDirectories() as $dir_name) {
            /** @var RecursiveDirectoryIterator */
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_name));
            $iterator->rewind();

            while ($iterator->valid()) {
                if (!$iterator->isDot()) {
                    $extension = $iterator->getExtension();
                    if (in_array($extension, $file_extensions)) {
                        $file_names[] = (string)$iterator->getRealPath();
                    }
                }

                $iterator->next();
            }
        }

        return $file_names;
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
     * @param  Config           $config
     * @param  bool             $debug
     * @param  array<string>    $file_list
     * @return void
     */
    protected function checkDiffFilesWithConfig(Config $config, $debug, array $file_list = [])
    {
        $file_extensions = $config->getFileExtensions();
        $filetype_handlers = $config->getFiletypeHandlers();

        foreach ($file_list as $file_name) {
            if (!file_exists($file_name)) {
                continue;
            }

            if (!$config->isInProjectDirs(
                preg_replace('/^' . preg_quote($config->getBaseDir(), '/') . '/', '', $file_name)
            )) {
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
            } else {
                $file_checker = new FileChecker($file_name);
            }

            $file_checker->check(true);
        }
    }

    /**
     * @param  string  $file_name
     * @param  bool    $debug
     * @param  bool    $update_docblocks
     * @return void
     */
    public function checkFile($file_name, $debug = false, $update_docblocks = false)
    {
        if ($debug) {
            echo 'Checking ' . $file_name . PHP_EOL;
        }

        if (!$this->config) {
            $this->config = self::getConfigForPath($file_name);
        }

        $this->config->hide_external_errors = $this->config->isInProjectDirs(
            $this->config->shortenFileName($file_name)
        );

        $file_name_parts = explode('.', $file_name);

        $extension = array_pop($file_name_parts);

        $filetype_handlers = $this->config->getFiletypeHandlers();

        FileChecker::loadReferenceCache();

        if (isset($filetype_handlers[$extension])) {
            /** @var FileChecker */
            $file_checker = new $filetype_handlers[$extension]($file_name);
        } else {
            $file_checker = new FileChecker($file_name);
        }

        $file_checker->check(true, true, null, true, $update_docblocks);

        IssueBuffer::finish();
    }

    /**
     * Gets a Config object from an XML file.
     *
     * Searches up a folder hierarchy for the most immediate config.
     *
     * @param  string $path
     * @return Config
     * @throws Exception\ConfigException If a config path is not found.
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
                $config = Config::loadFromXMLFile($maybe_path);

                if ($config->autoloader) {
                    require_once($dir_path . $config->autoloader);
                }

                $config->collectPredefinedConstants();

                break;
            }

            $dir_path = preg_replace('/[^\/]+\/$/', '', $dir_path);
        } while ($dir_path !== '/');

        if (!$config) {
            throw new Exception\ConfigException('Config not found for path ' . $path);
        }

        return $config;
    }

    /**
     * @param string $path_to_config
     * @return void
     * @throws Exception\ConfigException If a config file is not found in the given location.
     */
    public function setConfigXML($path_to_config)
    {
        if (!file_exists($path_to_config)) {
            throw new Exception\ConfigException('Config not found at location ' . $path_to_config);
        }

        $dir_path = dirname($path_to_config) . '/';

        $this->config = Config::loadFromXMLFile($path_to_config);

        if ($this->config->autoloader) {
            require_once($dir_path . $this->config->autoloader);
        }

        $this->config->collectPredefinedConstants();
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

    /**
     * @param  string $file_path
     * @param  string $file_contents
     * @return void
     */
    public function registerFile($file_path, $file_contents)
    {
        $this->fake_files[$file_path] = $file_contents;
    }

    /**
     * @param  string $file_path
     * @return string
     */
    public function getFileContents($file_path)
    {
        if (isset($this->fake_files[$file_path])) {
            return $this->fake_files[$file_path];
        }

        return (string)file_get_contents($file_path);
    }
}
