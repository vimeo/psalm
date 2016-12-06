<?php
namespace Psalm;

use Psalm\Checker\FileChecker;
use Psalm\Config\FileFilter;
use Psalm\Exception\ConfigException;
use SimpleXMLElement;

class Config
{
    const DEFAULT_FILE_NAME = 'psalm.xml';
    const REPORT_INFO = 'info';
    const REPORT_ERROR = 'error';
    const REPORT_SUPPRESS = 'suppress';

    /**
     * @var array<string>
     */
    public static $ERROR_LEVELS = [
        self::REPORT_INFO,
        self::REPORT_ERROR,
        self::REPORT_SUPPRESS
    ];

    /**
     * @var self|null
     */
    protected static $config;

    /**
     * Whether or not to stop when the first error is seen
     *
     * @var boolean
     */
    public $stop_on_first_error = true;

    /**
     * Whether or not to use types as defined in docblocks
     *
     * @var boolean
     */
    public $use_docblock_types = true;

    /**
     * Whether or not to throw an exception on first error
     *
     * @var boolean
     */
    public $throw_exception = false;

    /**
     * The directory to store PHP Parser (and other) caches
     *
     * @var string
     */
    public $cache_directory = '/var/tmp/psalm';

    /**
     * Whether or not to use property defaults to inform type when none is listed
     *
     * @var boolean
     */
    public $use_property_default_for_type = true;

    /**
     * Path to the autoader
     *
     * @var string|null
     */
    public $autoloader;

    /**
     * @var FileFilter|null
     */
    protected $inspect_files;

    /**
     * The base directory of this config file
     *
     * @var string
     */
    protected $base_dir;

    /**
     * @var array<int, string>
     */
    protected $file_extensions = ['php'];

    /**
     * @var array<int, string>
     */
    protected $filetype_handlers = [];

    /**
     * @var array<string, FileFilter>
     */
    protected $issue_handlers = [];

    /**
     * @var array<string, string>
     */
    protected $custom_error_levels = [];

    /**
     * @var array<int, string>
     */
    protected $mock_classes = [];

    /**
     * @var boolean
     */
    public $hide_external_errors = true;

    /** @var bool */
    public $allow_includes = true;

    /**
     * Psalm plugins
     *
     * @var array<Plugin>
     */
    protected $plugins = [];

    /** @var array<string, mixed> */
    protected $predefined_constants;

    private function __construct()
    {
        self::$config = $this;
    }

    /**
     * Creates a new config object from the file
     *
     * @param  string $file_name
     * @return self
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedPropertyFetch
     * @psalm-suppress MixedMethodCall
     */
    public static function loadFromXML($file_name)
    {
        $file_contents = file_get_contents($file_name);

        $config = new self();

        $config->base_dir = dirname($file_name) . '/';

        $config_xml = new SimpleXMLElement($file_contents);

        if (isset($config_xml['stopOnFirstError'])) {
            $attribute_text = (string) $config_xml['stopOnFirstError'];
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

        if (isset($config_xml['cacheDirectory'])) {
            $config->cache_directory = (string) $config_xml['cacheDirectory'];
        }

        if (isset($config_xml['cacheDirectory'])) {
            $config->cache_directory = (string) $config_xml['cacheDirectory'];
        }

        if (isset($config_xml['usePropertyDefaultForType'])) {
            $attribute_text = (string) $config_xml['usePropertyDefaultForType'];
            $config->use_property_default_for_type = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['allowFileIncludes'])) {
            $attribute_text = (string) $config_xml['allowFileIncludes'];
            $config->allow_includes = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml->inspectFiles)) {
            $config->inspect_files = FileFilter::loadFromXML($config_xml->inspectFiles, true);
        }

        if (isset($config_xml->fileExtensions)) {
            $config->file_extensions = [];

            $config->loadFileExtensions($config_xml->fileExtensions->extension);
        }

        if (isset($config_xml->mockClasses) && isset($config_xml->mockClasses->class)) {
            /** @var \SimpleXMLElement $mock_class */
            foreach ($config_xml->mockClasses->class as $mock_class) {
                $config->mock_classes[] = $mock_class['name'];
            }
        }

        // this plugin loading system borrows heavily from etsy/phan
        if (isset($config_xml->plugins) && isset($config_xml->plugins->plugin)) {
            /** @var \SimpleXMLElement $plugin */
            foreach ($config_xml->plugins->plugin as $plugin) {
                $plugin_file_name = $plugin['filename'];

                $path = $config->base_dir . $plugin_file_name;

                if (!file_exists($path)) {
                    throw new \InvalidArgumentException('Cannot find file ' . $path);
                }

                $loaded_plugin = require($path);

                if (!$loaded_plugin) {
                    throw new \InvalidArgumentException(
                        'Plugins must return an instance of that plugin at the end of the file - ' .
                            $plugin_file_name . ' does not'
                    );
                }

                if (!($loaded_plugin instanceof Plugin)) {
                    throw new \InvalidArgumentException(
                        'Plugins must extend \Psalm\Plugin - ' . $plugin_file_name . ' does not'
                    );
                }

                $config->plugins[] = $loaded_plugin;
            }
        }

        if (isset($config_xml->issueHandler)) {
            /** @var \SimpleXMLElement $issue_handler */
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
        if (self::$config) {
            return self::$config;
        }

        return new self();
    }

    /**
     * @param  array<\SimpleXMLElement> $extensions
     * @return void
     * @throws ConfigException If a Config file could not be found.
     * @psalm-suppress MixedArrayAccess
     */
    protected function loadFileExtensions($extensions)
    {
        foreach ($extensions as $extension) {
            $extension_name = preg_replace('/^\.?/', '', (string)$extension['name']);
            $this->file_extensions[] = $extension_name;

            if (isset($extension['filetypeHandler'])) {
                $path = $this->base_dir . $extension['filetypeHandler'];

                if (!file_exists($path)) {
                    throw new Exception\ConfigException('Error parsing config: cannot find file ' . $path);
                }

                $declared_classes = array_keys(FileChecker::getDeclaredClassesInFile($path));

                if (count($declared_classes) !== 1) {
                    throw new \InvalidArgumentException(
                        'Filetype handlers must have exactly one class in the file - ' . $path . ' has ' .
                            count($declared_classes)
                    );
                }

                require_once($path);

                if (!is_subclass_of($declared_classes[0], 'Psalm\\Checker\\FileChecker')) {
                    throw new \InvalidArgumentException(
                        'Filetype handlers must extend \Psalm\Checker\FileChecker - ' . $path . ' does not'
                    );
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

    /**
     * @param   string $issue_type
     * @param   string $file_name
     * @return  bool
     */
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

    /**
     * @param   string $file_name
     * @return  bool
     */
    public function isInProjectDirs($file_name)
    {
        foreach ($this->getIncludeDirs() as $dir_name) {
            if (preg_match('/^' . preg_quote($dir_name, '/') . '/', $file_name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param   string $issue_type
     * @return  string
     */
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

    /**
     * @return array
     */
    public function getFiletypeHandlers()
    {
        return $this->filetype_handlers;
    }

    /**
     * @return array<int, string>
     */
    public function getMockClasses()
    {
        return $this->mock_classes;
    }

    /**
     * @return string
     */
    public function getCacheDirectory()
    {
        return $this->cache_directory;
    }

    /**
     * @return array<Plugin>
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * @param string            $issue_name
     * @param FileFilter|null   $filter
     * @return void
     */
    public function setIssueHandler($issue_name, FileFilter $filter = null)
    {
        $this->issue_handlers[$issue_name] = $filter;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPredefinedConstants()
    {
        return $this->predefined_constants;
    }

    /**
     * @return void
     */
    public function collectPredefinedConstants()
    {
        $this->predefined_constants = get_defined_constants();
    }
}
