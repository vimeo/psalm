<?php

namespace Psalm;

use Psalm\Config\FileFilter;
use Psalm\Checker\FileChecker;
use SimpleXMLElement;

class Config
{
    const DEFAULT_FILE_NAME = 'psalm.xml';
    const REPORT_INFO = 'info';
    const REPORT_ERROR = 'error';
    const REPORT_SUPPRESS = 'suppress';

    public static $ERROR_LEVELS = [
        self::REPORT_INFO,
        self::REPORT_ERROR,
        self::REPORT_SUPPRESS
    ];

    protected static $_config;

    /**
     * Whether or not to stop when the first error is seen
     * @var boolean
     */
    public $stop_on_first_error = true;

    /**
     * Whether or not to use types as defined in docblocks
     * @var boolean
     */
    public $use_docblock_types = true;

    /**
     * Whether or not to throw an exception on first error
     * @var boolean
     */
    public $throw_exception = false;

    /**
     * Path to the autoader
     * @var string|null
     */
    public $autoloader;

    protected $inspect_files;

    /**
     * The base directory of this config file
     * @var string
     */
    protected $base_dir;

    protected $file_extensions = ['php'];

    protected $filetype_handlers = [];

    /**
     * @var array<string,FileFilter>
     */
    protected $issue_handlers = [];

    protected $custom_error_levels = [
    ];

    protected $mock_classes = [];

    public $hide_external_errors = true;

    /**
     * Psalm plugins
     * @var array<Plugin>
     */
    protected $plugins = [];

    private function __construct()
    {
        self::$_config = $this;
    }

    /**
     * Creates a new config object from the file
     * @param  string $file_name
     * @return $this
     */
    public static function loadFromXML($file_name)
    {
        $file_contents = file_get_contents($file_name);

        $config = new self();

        $config->base_dir = dirname($file_name) . '/';

        $config_xml = new SimpleXMLElement($file_contents);

        if (isset($config_xml['stopOnFirstError'])) {
            $attribute_text = (string) $config_xml['stopOnError'];
            $config->stop_on_first_error = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['useDocblockTypes'])) {
            $attribute_text = (string) $config_xml['useDocblockTypes'];
            $config->use_docblock_types = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['throwExceptionOnError'])) {
            $attribute_text = (string) $config_xml['throwExceptionOnError'];
            $config->throw_exception = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['hideExternalErrors'])) {
            $attribute_text = (string) $config_xml['hideExternalErrors'];
            $config->hide_external_errors = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['autoloader'])) {
            $config->autoloader = (string) $config_xml['autoloader'];
        }

        if (isset($config_xml->inspectFiles)) {
            $config->inspect_files = FileFilter::loadFromXML($config_xml->inspectFiles, true);
        }

        if (isset($config_xml->fileExtensions)) {
            $config->file_extensions = [];

            $config->loadFileExtensions($config_xml->fileExtensions->extension);
        }

        if (isset($config_xml->mockClasses) && isset($config_xml->mockClasses->class)) {
            foreach ($config_xml->mockClasses->class as $mock_class) {
                $config->mock_classes[] = $mock_class['name'];
            }
        }

        // this plugin loading system borrows heavily from etsy/phan
        if (isset($config_xml->plugins) && isset($config_xml->plugins->plugin)) {
            foreach ($config_xml->plugins->plugin as $plugin) {
                $plugin_file_name = $plugin['filename'];

                $path = $config->base_dir . $plugin_file_name;

                if (!file_exists($path)) {
                    throw new \InvalidArgumentException('Cannot find file ' . $path);
                }

                $loaded_plugin = require($path);

                if (!$loaded_plugin) {
                    throw new \InvalidArgumentException('Plugins must return an instance of that plugin at the end of the file - ' . $plugin_file_name . ' does not');
                }

                if (!($loaded_plugin instanceof Plugin)) {
                    throw new \InvalidArgumentException('Plugins must extend \Psalm\Plugin - ' . $plugin_file_name . ' does not');
                }

                $config->plugins[] = $loaded_plugin;
            }
        }

        if (isset($config_xml->issueHandler)) {
            foreach ($config_xml->issueHandler->children() as $key => $issue_handler) {
                if (isset($issue_handler['errorLevel'])) {
                    $error_level = (string) $issue_handler['errorLevel'];

                    if (!in_array($error_level, self::$ERROR_LEVELS)) {
                        throw new \InvalidArgumentException('Error level ' . $error_level . ' could not be recognised');
                    }

                    $config->custom_error_levels[$key] = $error_level;
                }

                if (isset($issue_handler->excludeFiles)) {
                    $config->issue_handlers[$key] = FileFilter::loadFromXML($issue_handler->excludeFiles, false);
                }
            }
        }

        return $config;
    }

    /**
     * @return $this
     */
    public static function getInstance()
    {
        if (self::$_config) {
            return self::$_config;
        }

        return new self();
    }

    protected function loadFileExtensions($extensions)
    {
        foreach ($extensions as $extension) {
            $extension_name = preg_replace('/^\.?/', '', $extension['name']);
            $this->file_extensions[] = $extension_name;

            if (isset($extension['filetypeHandler'])) {
                $path = $this->base_dir . $extension['filetypeHandler'];

                if (!file_exists($path)) {
                    throw new Exception\ConfigException('Error parsing config: cannot find file ' . $path);
                }

                $declared_classes = FileChecker::getDeclaredClassesInFile($path);

                if (count($declared_classes) !== 1) {
                    throw new \InvalidArgumentException('Filetype handlers must have exactly one class in the file - ' . $path . ' has ' . count($declared_classes));
                }

                require_once($path);

                if (!is_subclass_of($declared_classes[0], 'Psalm\\Checker\\FileChecker')) {
                    throw new \InvalidArgumentException('Filetype handlers must extend \Psalm\Checker\FileChecker - ' . $path . ' does not');
                }

                $this->filetype_handlers[$extension_name] = $declared_classes[0];
            }
        }
    }

    /**
     * @param  string $file_name
     * @return string
     */
    public function shortenFileName($file_name)
    {
        return preg_replace('/^' . preg_quote($this->base_dir, '/') . '/', '', $file_name);
    }

    public function excludeIssueInFile($issue_type, $file_name)
    {
        if ($this->getReportingLevel($issue_type) === self::REPORT_SUPPRESS) {
            return true;
        }

        $file_name = $this->shortenFileName($file_name);

        if ($this->getIncludeDirs() && $this->hide_external_errors) {
            if (!$this->isInProjectDirs($file_name)) {
                return true;
            }
        }

        if (!isset($this->issue_handlers[$issue_type])) {
            return false;
        }

        return !$this->issue_handlers[$issue_type]->allows($file_name);
    }

    public function isInProjectDirs($file_name)
    {
        foreach ($this->getIncludeDirs() as $dir_name) {
            if (preg_match('/^' . preg_quote($dir_name, '/') . '/', $file_name)) {
                return true;
            }
        }

        return false;
    }

    public function getReportingLevel($issue_type)
    {
        if (isset($this->custom_error_levels[$issue_type])) {
            return $this->custom_error_levels[$issue_type];
        }

        return self::REPORT_ERROR;
    }

    /**
     * @return array<string>
     */
    public function getIncludeDirs()
    {
        if (!$this->inspect_files) {
            return [];
        }

        return $this->inspect_files->getIncludeDirs();
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return $this->base_dir;
    }

    /**
     * @return array<string>
     */
    public function getFileExtensions()
    {
        return $this->file_extensions;
    }

    public function getFiletypeHandlers()
    {
        return $this->filetype_handlers;
    }

    /**
     * @return array<string>
     */
    public function getMockClasses()
    {
        return $this->mock_classes;
    }

    /**
     * @return array<Plugin>
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    public function setIssueHandler($issue_name, FileFilter $filter = null)
    {
        $this->issue_handlers[$issue_name] = $filter;
    }
}
