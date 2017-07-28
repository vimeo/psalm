<?php
namespace Psalm;

use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Config\IssueHandler;
use Psalm\Config\ProjectFileFilter;
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
        self::REPORT_SUPPRESS,
    ];

    /**
     * @var array
     */
    protected static $MIXED_ISSUES = [
        'MixedArgument',
        'MixedArrayAccess',
        'MixedArrayOffset',
        'MixedAssignment',
        'MixedInferredReturnType',
        'MixedMethodCall',
        'MixedOperand',
        'MixedPropertyFetch',
        'MixedPropertyAssignment',
        'MixedStringOffsetAssignment',
    ];

    /**
     * @var self|null
     */
    protected static $config;

    /**
     * Whether or not to stop when the first error is seen
     *
     * @var bool
     */
    public $stop_on_first_error = true;

    /**
     * Whether or not to use types as defined in docblocks
     *
     * @var bool
     */
    public $use_docblock_types = true;

    /**
     * Whether or not to throw an exception on first error
     *
     * @var bool
     */
    public $throw_exception = false;

    /**
     * The directory to store PHP Parser (and other) caches
     *
     * @var string
     */
    public $cache_directory;

    /**
     * Whether or not to use property defaults to inform type when none is listed
     *
     * @var bool
     */
    public $use_property_default_for_type = false;

    /**
     * Whether or not to care about casing of file names
     *
     * @var bool
     */
    public $use_case_sensitive_file_names = false;

    /**
     * Path to the autoader
     *
     * @var string|null
     */
    public $autoloader;

    /**
     * @var ProjectFileFilter|null
     */
    protected $project_files;

    /**
     * The base directory of this config file
     *
     * @var string
     */
    protected $base_dir;

    /**
     * The path to this config file
     *
     * @var string
     */
    public $file_path;

    /**
     * @var array<int, string>
     */
    private $file_extensions = ['php'];

    /**
     * @var array<string, string>
     */
    private $filetype_handlers = [];

    /**
     * @var array<string, IssueHandler>
     */
    private $issue_handlers = [];

    /**
     * @var array<int, string>
     */
    private $mock_classes = [];

    /**
     * @var array<int, string>
     */
    private $stub_files = [];

    /**
     * @var bool
     */
    public $cache_file_hashes_during_run = true;

    /**
     * @var bool
     */
    public $hide_external_errors = true;

    /** @var bool */
    public $allow_includes = true;

    /** @var bool */
    public $totally_typed = false;

    /** @var bool */
    public $strict_binary_operands = false;

    /** @var bool */
    public $add_void_docblocks = true;

    /**
     * If true, assert() calls can be used to check types of variables
     *
     * @var bool
     */
    public $use_assert_for_type = false;

    /**
     * @var bool
     */
    public $remember_property_assignments_after_call = true;

    /**
     * Psalm plugins
     *
     * @var array<Plugin>
     */
    private $plugins = [];

    /** @var array<string, bool> */
    private $predefined_classlikes = [];

    /** @var array<string, mixed> */
    private $predefined_constants;

    /** @var array<string, bool> */
    private $predefined_functions = [];

    protected function __construct()
    {
        self::$config = $this;
    }

    /**
     * Creates a new config object from the file
     *
     * @param  string           $file_path
     * @param  string           $base_dir
     *
     * @return self
     */
    public static function loadFromXMLFile($file_path, $base_dir)
    {
        $file_contents = file_get_contents($file_path);

        if ($file_contents === false) {
            throw new \InvalidArgumentException('Cannot open ' . $file_path);
        }

        return self::loadFromXML($file_path, $base_dir, $file_contents);
    }

    /**
     * Creates a new config object from an XML string
     *
     * @param  string           $file_path
     * @param  string           $base_dir
     * @param  string           $file_contents
     *
     * @return self
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedPropertyFetch
     * @psalm-suppress MixedMethodCall
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     * @psalm-suppress MixedPropertyAssignment
     */
    public static function loadFromXML($file_path, $base_dir, $file_contents)
    {
        $config = new static();

        $config->file_path = $file_path;

        $config->base_dir = $base_dir;

        $schema_path = dirname(dirname(__DIR__)) . '/config.xsd';

        if (!file_exists($schema_path)) {
            throw new ConfigException('Cannot locate config schema');
        }

        $dom_document = new \DOMDocument();
        $dom_document->loadXML($file_contents);

        // Enable user error handling
        libxml_use_internal_errors(true);

        if (!$dom_document->schemaValidate($schema_path)) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                if ($error->level === LIBXML_ERR_FATAL || $error->level === LIBXML_ERR_ERROR) {
                    throw new ConfigException(
                        'Error parsing file ' . $error->file . ' on line ' . $error->line . ': ' . $error->message
                    );
                }
            }
            libxml_clear_errors();
        }

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
            $config->cache_directory = (string)$config_xml['cacheDirectory'];
        } else {
            $config->cache_directory = sys_get_temp_dir() . '/psalm';
        }

        if (@mkdir($config->cache_directory, 0777, true) === false && is_dir($config->cache_directory) === false) {
            trigger_error('Could not create cache directory: ' . $config->cache_directory, E_USER_ERROR);
            exit(255);
        }

        if (isset($config_xml['usePropertyDefaultForType'])) {
            $attribute_text = (string) $config_xml['usePropertyDefaultForType'];
            $config->use_property_default_for_type = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['allowFileIncludes'])) {
            $attribute_text = (string) $config_xml['allowFileIncludes'];
            $config->allow_includes = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['totallyTyped'])) {
            $attribute_text = (string) $config_xml['totallyTyped'];
            $config->totally_typed = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['strictBinaryOperands'])) {
            $attribute_text = (string) $config_xml['strictBinaryOperands'];
            $config->strict_binary_operands = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['requireVoidReturnType'])) {
            $attribute_text = (string) $config_xml['requireVoidReturnType'];
            $config->add_void_docblocks = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['useAssertForType'])) {
            $attribute_text = (string) $config_xml['useAssertForType'];
            $config->use_assert_for_type = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['cacheFileContentHashes'])) {
            $attribute_text = (string) $config_xml['cacheFileContentHashes'];
            $config->cache_file_hashes_during_run = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['rememberPropertyAssignmentsAfterCall'])) {
            $attribute_text = (string) $config_xml['rememberPropertyAssignmentsAfterCall'];
            $config->remember_property_assignments_after_call = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml->projectFiles)) {
            $config->project_files = ProjectFileFilter::loadFromXMLElement($config_xml->projectFiles, $base_dir, true);
        }

        if (isset($config_xml->fileExtensions)) {
            $config->file_extensions = [];

            $config->loadFileExtensions($config_xml->fileExtensions->extension);
        }

        if (isset($config_xml->mockClasses) && isset($config_xml->mockClasses->class)) {
            /** @var \SimpleXMLElement $mock_class */
            foreach ($config_xml->mockClasses->class as $mock_class) {
                $config->mock_classes[] = (string)$mock_class['name'];
            }
        }

        if (isset($config_xml->stubs) && isset($config_xml->stubs->file)) {
            /** @var \SimpleXMLElement $stub_file */
            foreach ($config_xml->stubs->file as $stub_file) {
                $file_path = realpath($stub_file['name']);

                if (!$file_path) {
                    throw new Exception\ConfigException(
                        'Cannot resolve stubfile path ' . getcwd() . '/' . $stub_file['name']
                    );
                }

                $config->stub_files[] = $file_path;
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

        if (isset($config_xml->issueHandlers)) {
            /** @var \SimpleXMLElement $issue_handler */
            foreach ($config_xml->issueHandlers->children() as $key => $issue_handler) {
                /** @var string $key */
                $config->issue_handlers[$key] = IssueHandler::loadFromXMLElement($issue_handler, $base_dir);
            }
        }

        if ($config->autoloader) {
            require_once($base_dir . DIRECTORY_SEPARATOR . $config->autoloader);
        }

        $config->collectPredefinedConstants();
        $config->collectPredefinedFunctions();

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
     * @param string $issue_key
     * @param string $error_level
     *
     * @return void
     */
    public function setCustomErrorLevel($issue_key, $error_level)
    {
        $this->issue_handlers[$issue_key] = new IssueHandler();
        $this->issue_handlers[$issue_key]->setErrorLevel($error_level);
    }

    /**
     * @param  array<\SimpleXMLElement> $extensions
     *
     * @throws ConfigException if a Config file could not be found
     *
     * @return void
     */
    private function loadFileExtensions($extensions)
    {
        foreach ($extensions as $extension) {
            $extension_name = preg_replace('/^\.?/', '', (string)$extension['name']);
            $this->file_extensions[] = $extension_name;

            if (isset($extension['filetypeHandler'])) {
                $path = $this->base_dir . (string)$extension['filetypeHandler'];

                if (!file_exists($path)) {
                    throw new Exception\ConfigException('Error parsing config: cannot find file ' . $path);
                }

                $this->filetype_handlers[$extension_name] = $path;
            }
        }
    }

    /**
     * Initialises all the plugins (done once the config is fully loaded)
     *
     * @return void
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     */
    public function initializePlugins(ProjectChecker $project_checker)
    {
        foreach ($this->filetype_handlers as &$path) {
            $plugin_file_checker = new FileChecker($path, $project_checker);
            $plugin_file_checker->scan();

            $declared_classes = ClassLikeChecker::getClassesForFile($project_checker, $path);

            if (count($declared_classes) !== 1) {
                throw new \InvalidArgumentException(
                    'Filetype handlers must have exactly one class in the file - ' . $path . ' has ' .
                        count($declared_classes)
                );
            }

            require_once($path);

            if (!\Psalm\Checker\ClassChecker::classExtends($declared_classes[0], 'Psalm\\Checker\\FileChecker')) {
                throw new \InvalidArgumentException(
                    'Filetype handlers must extend \Psalm\Checker\FileChecker - ' . $path . ' does not'
                );
            }

            $path = $declared_classes[0];
        }
    }

    /**
     * @param  string $file_name
     *
     * @return string
     */
    public function shortenFileName($file_name)
    {
        return preg_replace('/^' . preg_quote($this->base_dir, DIRECTORY_SEPARATOR) . '/', '', $file_name);
    }

    /**
     * @param   string $issue_type
     * @param   string $file_path
     *
     * @return  bool
     */
    public function reportIssueInFile($issue_type, $file_path)
    {
        if (!$this->totally_typed && in_array($issue_type, self::$MIXED_ISSUES, true)) {
            return false;
        }

        if ($this->hide_external_errors) {
            $project_checker = ProjectChecker::getInstance();

            if (!$project_checker->canReportIssues($file_path)) {
                return false;
            }
        }

        if ($this->getReportingLevelForFile($issue_type, $file_path) === self::REPORT_SUPPRESS) {
            return false;
        }

        return true;
    }

    /**
     * @param   string $file_path
     *
     * @return  bool
     */
    public function isInProjectDirs($file_path)
    {
        return $this->project_files && $this->project_files->allows($file_path);
    }

    /**
     * @param   string $issue_type
     * @param   string $file_path
     *
     * @return  string
     */
    public function getReportingLevelForFile($issue_type, $file_path)
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForFile($file_path);
        }

        return self::REPORT_ERROR;
    }

    /**
     * @return array<string>
     */
    public function getProjectDirectories()
    {
        if (!$this->project_files) {
            return [];
        }

        return $this->project_files->getDirectories();
    }

    /**
     * @return array<string>
     */
    public function getFileExtensions()
    {
        return $this->file_extensions;
    }

    /**
     * @return array<string, string>
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
     * @param  ProjectChecker $project_checker
     *
     * @return void
     */
    public function visitStubFiles(ProjectChecker $project_checker)
    {
        $project_checker->register_global_functions = true;

        $generic_stubs = realpath(__DIR__ . '/Stubs/CoreGenericFunctions.php');

        if ($generic_stubs) {
            $generic_stub_checker = new FileChecker(
                $generic_stubs,
                $project_checker
            );
            $generic_stub_checker->scan();
        } else {
            throw new \UnexpectedValueException('Cannot locate core generic stubs');
        }

        foreach ($this->stub_files as $stub_file) {
            $stub_checker = new FileChecker($stub_file, $project_checker);
            $stub_checker->scan();
        }

        $project_checker->register_global_functions = false;
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
     * @return array<string, mixed>
     */
    public function getPredefinedConstants()
    {
        return $this->predefined_constants;
    }

    /**
     * @return void
     * @psalm-suppress InvalidPropertyAssignment
     */
    public function collectPredefinedConstants()
    {
        $this->predefined_constants = get_defined_constants();
    }

    /**
     * @return array<string, bool>
     */
    public function getPredefinedFunctions()
    {
        return $this->predefined_functions;
    }

    /**
     * @return void
     * @psalm-suppress InvalidPropertyAssignment
     * @psalm-suppress MixedAssignment
     */
    public function collectPredefinedFunctions()
    {
        $defined_functions = get_defined_functions();

        if (isset($defined_functions['user'])) {
            foreach ($defined_functions['user'] as $function_name) {
                $this->predefined_functions[$function_name] = true;
            }
        }

        if (isset($defined_functions['internal'])) {
            foreach ($defined_functions['internal'] as $function_name) {
                $this->predefined_functions[$function_name] = true;
            }
        }
    }
}
