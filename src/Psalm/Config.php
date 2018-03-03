<?php
namespace Psalm;

use Composer\Autoload\ClassLoader;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Config\IssueHandler;
use Psalm\Config\ProjectFileFilter;
use Psalm\Exception\ConfigException;
use Psalm\Scanner\FileScanner;
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
        'MixedArrayAssignment',
        'MixedArrayOffset',
        'MixedAssignment',
        'MixedInferredReturnType',
        'MixedMethodCall',
        'MixedOperand',
        'MixedPropertyFetch',
        'MixedPropertyAssignment',
        'MixedReturnStatement',
        'MixedStringOffsetAssignment',
        'MixedTypeCoercion',
    ];

    /**
     * @var self|null
     */
    private static $instance;

    /**
     * Whether or not to use types as defined in docblocks
     *
     * @var bool
     */
    public $use_docblock_types = true;

    /**
     * Whether or not to use types as defined in property docblocks.
     * This is distinct from the above because you may want to use
     * property docblocks, but not function docblocks.
     *
     * @var bool
     */
    public $use_docblock_property_types = true;

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
     * @var array<int, string>
     */
    private $file_extensions = ['php'];

    /**
     * @var array<string, string>
     */
    private $filetype_scanners = [];

    /**
     * @var array<string, string>
     */
    private $filetype_checkers = [];

    /**
     * @var array<string, string>
     */
    private $filetype_scanner_paths = [];

    /**
     * @var array<string, string>
     */
    private $filetype_checker_paths = [];

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

    /** @var bool */
    public $use_igbinary = false;

    /**
     * @var bool
     */
    public $allow_phpstorm_generics = false;

    /**
     * @var string[]
     */
    private $plugin_paths = [];

    /**
     * Static methods to be called after method checks have completed
     *
     * @var string[]
     */
    public $after_method_checks = [];

    /**
     * Static methods to be called after expression checks have completed
     *
     * @var string[]
     */
    public $after_expression_checks = [];

    /**
     * Static methods to be called after statement checks have completed
     *
     * @var string[]
     */
    public $after_statement_checks = [];

    /**
     * Static methods to be called after classlike exists checks have completed
     *
     * @var string[]
     */
    public $after_classlike_exists_checks = [];

    /**
     * Static methods to be called after classlikes have been scanned
     *
     * @var string[]
     */
    public $after_visit_classlikes = [];

    /** @var array<string, mixed> */
    private $predefined_constants;

    /** @var array<string, bool> */
    private $predefined_functions = [];

    /** @var ClassLoader|null */
    private $composer_class_loader;

    protected function __construct()
    {
        self::$instance = $this;
    }

    /**
     * Gets a Config object from an XML file.
     *
     * Searches up a folder hierarchy for the most immediate config.
     *
     * @param  string $path
     * @param  string $base_dir
     * @param  string $output_format
     *
     * @throws ConfigException if a config path is not found
     *
     * @return Config
     */
    public static function getConfigForPath($path, $base_dir, $output_format)
    {
        $dir_path = realpath($path);

        if ($dir_path === false) {
            throw new ConfigException('Config not found for path ' . $path);
        }

        if (!is_dir($dir_path)) {
            $dir_path = dirname($dir_path);
        }

        $config = null;

        do {
            $maybe_path = $dir_path . DIRECTORY_SEPARATOR . Config::DEFAULT_FILE_NAME;

            if (file_exists($maybe_path)) {
                $config = self::loadFromXMLFile($maybe_path, $base_dir);

                break;
            }

            $dir_path = dirname($dir_path);
        } while (dirname($dir_path) !== $dir_path);

        if (!$config) {
            if ($output_format === ProjectChecker::TYPE_CONSOLE) {
                exit(
                    'Could not locate a config XML file in path ' . $path . '. Have you run \'psalm --init\' ?' .
                    PHP_EOL
                );
            }

            throw new ConfigException('Config not found for path ' . $path);
        }

        return $config;
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

        return self::loadFromXML($base_dir, $file_contents);
    }

    /**
     * Creates a new config object from an XML string
     *
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
    public static function loadFromXML($base_dir, $file_contents)
    {
        $config = new static();

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

        if (isset($config_xml['useDocblockTypes'])) {
            $attribute_text = (string) $config_xml['useDocblockTypes'];
            $config->use_docblock_types = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['useDocblockPropertyTypes'])) {
            $attribute_text = (string) $config_xml['useDocblockPropertyTypes'];
            $config->use_docblock_property_types = $attribute_text === 'true' || $attribute_text === '1';
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

        if (isset($config_xml['serializer'])) {
            $attribute_text = (string) $config_xml['serializer'];
            $config->use_igbinary = $attribute_text === 'igbinary';
        } elseif ($igbinary_version = phpversion('igbinary')) {
            $config->use_igbinary = version_compare($igbinary_version, '2.0.5') >= 0;
        }

        if (isset($config_xml['allowPhpStormGenerics'])) {
            $attribute_text = (string) $config_xml['allowPhpStormGenerics'];
            $config->allow_phpstorm_generics = $attribute_text === 'true' || $attribute_text === '1';
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

                $config->addPluginPath($path);
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
            /** @psalm-suppress UnresolvableInclude */
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
        if (self::$instance) {
            return self::$instance;
        }

        throw new \UnexpectedValueException('No config initialized');
    }

    /**
     * @return void
     */
    public function setComposerClassLoader(ClassLoader $loader)
    {
        $this->composer_class_loader = $loader;
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
     * @param  array<SimpleXMLElement> $extensions
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

            if (isset($extension['scanner'])) {
                $path = $this->base_dir . (string)$extension['scanner'];

                if (!file_exists($path)) {
                    throw new Exception\ConfigException('Error parsing config: cannot find file ' . $path);
                }

                $this->filetype_scanner_paths[$extension_name] = $path;
            }

            if (isset($extension['checker'])) {
                $path = $this->base_dir . (string)$extension['checker'];

                if (!file_exists($path)) {
                    throw new Exception\ConfigException('Error parsing config: cannot find file ' . $path);
                }

                $this->filetype_checker_paths[$extension_name] = $path;
            }
        }
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function addPluginPath($path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException('Cannot find plugin file ' . $path);
        }

        $this->plugin_paths[] = $path;
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
        $codebase = $project_checker->codebase;

        foreach ($this->filetype_scanner_paths as $extension => $path) {
            $fq_class_name = $this->getPluginClassForPath($project_checker, $path, 'Psalm\\Scanner\\FileScanner');

            $this->filetype_scanners[$extension] = $fq_class_name;

            /** @psalm-suppress UnresolvableInclude */
            require_once($path);
        }

        foreach ($this->filetype_checker_paths as $extension => $path) {
            $fq_class_name = $this->getPluginClassForPath($project_checker, $path, 'Psalm\\Checker\\FileChecker');

            $this->filetype_checkers[$extension] = $fq_class_name;

            /** @psalm-suppress UnresolvableInclude */
            require_once($path);
        }

        foreach ($this->plugin_paths as $path) {
            $fq_class_name = $this->getPluginClassForPath($project_checker, $path, 'Psalm\\Plugin');

            /** @psalm-suppress UnresolvableInclude */
            require_once($path);

            if ($codebase->methods->methodExists($fq_class_name . '::afterMethodCallCheck')) {
                $this->after_method_checks[$fq_class_name] = $fq_class_name;
            }

            if ($codebase->methods->methodExists($fq_class_name . '::afterExpressionCheck')) {
                $this->after_expression_checks[$fq_class_name] = $fq_class_name;
            }

            if ($codebase->methods->methodExists($fq_class_name . '::afterStatementCheck')) {
                $this->after_statement_checks[$fq_class_name] = $fq_class_name;
            }

            if ($codebase->methods->methodExists($fq_class_name . '::afterClassLikeExistsCheck')) {
                $this->after_classlike_exists_checks[$fq_class_name] = $fq_class_name;
            }

            if ($codebase->methods->methodExists($fq_class_name . '::afterVisitClassLike')) {
                $this->after_visit_classlikes[$fq_class_name] = $fq_class_name;
            }
        }
    }

    /**
     * @param  string $path
     * @param  string $must_extend
     *
     * @return string
     */
    private function getPluginClassForPath(ProjectChecker $project_checker, $path, $must_extend)
    {
        $codebase = $project_checker->codebase;

        $file_storage = $codebase->createFileStorageForPath($path);
        $file_to_scan = new FileScanner($path, $this->shortenFileName($path), true);
        $file_to_scan->scan(
            $codebase,
            $file_storage
        );

        $declared_classes = ClassLikeChecker::getClassesForFile($project_checker, $path);

        if (count($declared_classes) !== 1) {
            throw new \InvalidArgumentException(
                'Plugins must have exactly one class in the file - ' . $path . ' has ' .
                    count($declared_classes)
            );
        }

        $fq_class_name = reset($declared_classes);

        if (!$codebase->classExtends(
            $fq_class_name,
            $must_extend
        )
        ) {
            throw new \InvalidArgumentException(
                'This plugin must extend ' . $must_extend . ' - ' . $path . ' does not'
            );
        }

        return $fq_class_name;
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
            $codebase = ProjectChecker::getInstance()->codebase;

            if (!$codebase->analyzer->canReportIssues($file_path)) {
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
    public function getFiletypeScanners()
    {
        return $this->filetype_scanners;
    }

    /**
     * @return array<string, string>
     */
    public function getFiletypeCheckers()
    {
        return $this->filetype_checkers;
    }

    /**
     * @return array<int, string>
     */
    public function getMockClasses()
    {
        return $this->mock_classes;
    }

    /**
     * @return void
     */
    public function visitStubFiles(Codebase $codebase)
    {
        $codebase->register_global_functions = true;

        $generic_stubs_path = realpath(__DIR__ . '/Stubs/CoreGenericFunctions.php');

        if (!$generic_stubs_path) {
            throw new \UnexpectedValueException('Cannot locate core generic stubs');
        }

        $generic_classes_path = realpath(__DIR__ . '/Stubs/CoreGenericClasses.php');

        if (!$generic_classes_path) {
            throw new \UnexpectedValueException('Cannot locate core generic classes');
        }

        $stub_files = array_merge([$generic_stubs_path, $generic_classes_path], $this->stub_files);

        foreach ($stub_files as $stub_file_path) {
            $file_storage = $codebase->createFileStorageForPath($stub_file_path);
            $file_to_scan = new FileScanner($stub_file_path, $this->shortenFileName($stub_file_path), false);
            $file_to_scan->scan(
                $codebase,
                $file_storage
            );
        }

        $codebase->register_global_functions = false;
    }

    /**
     * @return string
     */
    public function getCacheDirectory()
    {
        return $this->cache_directory;
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
     * @psalm-suppress MixedTypeCoercion
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
     * @psalm-suppress MixedArrayOffset
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

    /**
     * @return void
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAccess
     */
    public function visitComposerAutoloadFiles(ProjectChecker $project_checker)
    {
        $composer_json_path = $this->base_dir . 'composer.json'; // this should ideally not be hardcoded

        if (!file_exists($composer_json_path)) {
            return;
        }

        /** @psalm-suppress PossiblyFalseArgument */
        if (!$composer_json = json_decode(file_get_contents($composer_json_path), true)) {
            throw new \UnexpectedValueException('Invalid composer.json at ' . $composer_json_path);
        }

        if (isset($composer_json['autoload']['files'])) {
            $codebase = $project_checker->codebase;
            $codebase->register_global_functions = true;

            /** @var string[] */
            $files = $composer_json['autoload']['files'];

            foreach ($files as $file) {
                $file_path = realpath($this->base_dir . $file);

                if (!$file_path) {
                    continue;
                }

                $file_storage = $codebase->createFileStorageForPath($file_path);
                $file_to_scan = new \Psalm\Scanner\FileScanner($file_path, $this->shortenFileName($file_path), false);
                $file_to_scan->scan(
                    $codebase,
                    $file_storage
                );
            }

            $project_checker->codebase->register_global_functions = false;
        }
    }

    /**
     * @param  string $fq_classlike_name
     *
     * @return string|false
     */
    public function getComposerFilePathForClassLike($fq_classlike_name)
    {
        if (!$this->composer_class_loader) {
            throw new \LogicException('Composer class loader should exist here');
        }

        return $this->composer_class_loader->findFile($fq_classlike_name);
    }

    /**
     * @param string $dir
     *
     * @return void
     */
    public static function removeCacheDirectory($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);

            if ($objects === false) {
                throw new \UnexpectedValueException('Not expecting false here');
            }

            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir . '/' . $object) == 'dir') {
                        self::removeCacheDirectory($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }

            reset($objects);
            rmdir($dir);
        }
    }
}
