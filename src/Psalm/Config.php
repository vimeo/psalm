<?php
namespace Psalm;

use Webmozart\PathUtil\Path;
use function array_merge;
use function array_pop;
use function array_unique;
use function class_exists;
use Composer\Autoload\ClassLoader;
use function count;
use const DIRECTORY_SEPARATOR;
use function dirname;
use const E_USER_ERROR;
use function explode;
use function file_exists;
use function file_get_contents;
use function filetype;
use function get_class;
use function get_defined_constants;
use function get_defined_functions;
use function in_array;
use function intval;
use function is_dir;
use function json_decode;
use function libxml_clear_errors;
use const LIBXML_ERR_ERROR;
use const LIBXML_ERR_FATAL;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function mkdir;
use const PHP_EOL;
use function phpversion;
use function preg_match;
use function preg_quote;
use function preg_replace;
use Psalm\Config\IssueHandler;
use Psalm\Config\ProjectFileFilter;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Issue\ArgumentIssue;
use Psalm\Issue\ClassIssue;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\FunctionIssue;
use Psalm\Issue\MethodIssue;
use Psalm\Issue\PropertyIssue;
use Psalm\Plugin\Hook;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use function realpath;
use function reset;
use function rmdir;
use function scandir;
use function sha1;
use SimpleXMLElement;
use function strpos;
use function strrpos;
use function strtolower;
use function strtr;
use function substr;
use function substr_count;
use function sys_get_temp_dir;
use function trigger_error;
use function unlink;
use function version_compare;

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
    const MIXED_ISSUES = [
        'MixedArgument',
        'MixedArrayAccess',
        'MixedArrayAssignment',
        'MixedArrayOffset',
        'MixedArrayTypeCoercion',
        'MixedAssignment',
        'MixedFunctionCall',
        'MixedInferredReturnType',
        'MixedMethodCall',
        'MixedOperand',
        'MixedPropertyFetch',
        'MixedPropertyAssignment',
        'MixedReturnStatement',
        'MixedStringOffsetAssignment',
        'MixedTypeCoercion',
        'MixedArgumentTypeCoercion',
        'MixedPropertyTypeCoercion',
        'MixedReturnTypeCoercion',
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
     * The directory to store all Psalm project caches
     *
     * @var string|null
     */
    public $global_cache_directory;

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
    public $base_dir;

    /**
     * @var array<int, string>
     */
    private $file_extensions = ['php'];

    /**
     * @var array<string, class-string<FileScanner>>
     */
    private $filetype_scanners = [];

    /**
     * @var array<string, class-string<FileAnalyzer>>
     */
    private $filetype_analyzers = [];

    /**
     * @var array<string, string>
     */
    private $filetype_scanner_paths = [];

    /**
     * @var array<string, string>
     */
    private $filetype_analyzer_paths = [];

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
    public $use_assert_for_type = true;

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
     * @var bool
     */
    public $allow_string_standin_for_class = false;

    /**
     * @var bool
     */
    public $use_phpdoc_method_without_magic_or_parent = false;

    /**
     * @var bool
     */
    public $memoize_method_calls = false;

    /**
     * @var bool
     */
    public $hoist_constants = false;

    /**
     * @var bool
     */
    public $add_param_default_to_docblock_type = false;

    /**
     * @var bool
     */
    public $check_for_throws_docblock = false;

    /**
     * @var bool
     */
    public $check_for_throws_in_global_scope = false;

    /**
     * @var bool
     */
    public $ignore_internal_falsable_issues = true;

    /**
     * @var bool
     */
    public $ignore_internal_nullable_issues = true;

    /**
     * @var array<string, bool>
     */
    public $ignored_exceptions = [];

    /**
     * @var array<string, bool>
     */
    public $ignored_exceptions_in_global_scope = [];

    /**
     * @var array<string, bool>
     */
    public $ignored_exceptions_and_descendants = [];

    /**
     * @var array<string, bool>
     */
    public $ignored_exceptions_and_descendants_in_global_scope = [];

    /**
     * @var bool
     */
    public $infer_property_types_from_constructor = true;

    /**
     * @var array<string, bool>
     */
    public $forbidden_functions = [];

    /**
     * @var bool
     */
    public $forbid_echo = false;

    /**
     * @var bool
     */
    public $find_unused_code = false;

    /**
     * @var bool
     */
    public $find_unused_variables = false;

    /**
     * Whether to resolve file and directory paths from the location of the config file,
     * instead of the current working directory.
     *
     * @var bool
     */
    public $resolve_from_config_file = false;

    /**
     * @var string[]
     */
    public $plugin_paths = [];

    /**
     * @var array<array{class:string,config:?SimpleXMLElement}>
     */
    private $plugin_classes = [];

    /**
     * Static methods to be called after method checks have completed
     *
     * @var class-string<Hook\AfterMethodCallAnalysisInterface>[]
     */
    public $after_method_checks = [];

    /**
     * Static methods to be called after function checks have completed
     *
     * @var class-string<Hook\AfterFunctionCallAnalysisInterface>[]
     */
    public $after_function_checks = [];

    /**
     * Static methods to be called after expression checks have completed
     *
     * @var class-string<Hook\AfterExpressionAnalysisInterface>[]
     */
    public $after_expression_checks = [];

    /**
     * Static methods to be called after statement checks have completed
     *
     * @var class-string<Hook\AfterStatementAnalysisInterface>[]
     */
    public $after_statement_checks = [];

    /**
     * Static methods to be called after method checks have completed
     *
     * @var class-string<Hook\StringInterpreterInterface>[]
     */
    public $string_interpreters = [];

    /**
     * Static methods to be called after classlike exists checks have completed
     *
     * @var class-string<Hook\AfterClassLikeExistenceCheckInterface>[]
     */
    public $after_classlike_exists_checks = [];

    /**
     * Static methods to be called after classlike checks have completed
     *
     * @var class-string<Hook\AfterClassLikeAnalysisInterface>[]
     */
    public $after_classlike_checks = [];

    /**
     * Static methods to be called after classlikes have been scanned
     *
     * @var class-string<Hook\AfterClassLikeVisitInterface>[]
     */
    public $after_visit_classlikes = [];

    /**
     * Static methods to be called after codebase has been populated
     *
     * @var class-string<Hook\AfterCodebasePopulatedInterface>[]
     */
    public $after_codebase_populated = [];

    /**
     * Static methods to be called after codebase has been populated
     *
     * @var class-string<Hook\AfterAnalysisInterface>[]
     */
    public $after_analysis = [];

    /**
     * Static methods to be called after codebase has been populated
     * @var class-string<Hook\BeforeAnalyzeFileInterface>[]
     */
    public $before_analyze_file = [];

    /** @var array<string, mixed> */
    private $predefined_constants;

    /** @var array<callable-string, bool> */
    private $predefined_functions = [];

    /** @var ClassLoader|null */
    private $composer_class_loader;

    /**
     * Custom functions that always exit
     *
     * @var array<string, bool>
     */
    public $exit_functions = [];

    /**
     * @var string
     */
    public $hash = '';

    /** @var string|null */
    public $error_baseline = null;

    /**
     * @var bool
     */
    public $include_php_versions_in_error_baseline = false;

    /** @var string */
    public $shepherd_host = 'shepherd.dev';

    /**
     * @var array<string, string>
     */
    public $globals = [];

    /**
     * @var bool
     */
    public $parse_sql = false;

    /**
     * @var int
     */
    public $max_string_length = 1000;

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
     * @param  string $current_dir
     * @param  string $output_format
     *
     * @return Config
     * @psalm-suppress MixedArgument
     *@throws ConfigException if a config path is not found
     *
     */
    public static function getConfigForPath($path, $current_dir, $output_format)
    {
        $config_path = self::locateConfigFile($path);

        if (!$config_path) {
            if ($output_format === \Psalm\Report::TYPE_CONSOLE) {
                echo 'Could not locate a config XML file in path ' . $path
                    . '. Have you run \'psalm --init\' ?' . PHP_EOL;
                exit(1);
            }
            throw new ConfigException('Config not found for path ' . $path);
        }

        return self::loadFromXMLFile($config_path, $current_dir);
    }

    /**
     * Searches up a folder hierarchy for the most immediate config.
     *
     * @throws ConfigException
     *
     * @return ?string
     */
    public static function locateConfigFile(string $path)
    {
        $dir_path = realpath($path);

        if ($dir_path === false) {
            throw new ConfigException('Config not found for path ' . $path);
        }

        if (!is_dir($dir_path)) {
            $dir_path = dirname($dir_path);
        }

        do {
            $maybe_path = $dir_path . DIRECTORY_SEPARATOR . Config::DEFAULT_FILE_NAME;

            if (file_exists($maybe_path) || file_exists($maybe_path .= '.dist')) {
                return $maybe_path;
            }

            $dir_path = dirname($dir_path);
        } while (dirname($dir_path) !== $dir_path);

        return null;
    }

    /**
     * Creates a new config object from the file
     *
     * @param  string           $file_path
     * @param  string           $current_dir
     *
     * @return self
     */
    public static function loadFromXMLFile($file_path, $current_dir)
    {
        $file_contents = file_get_contents($file_path);

        $base_dir = dirname($file_path) . DIRECTORY_SEPARATOR;

        if ($file_contents === false) {
            throw new \InvalidArgumentException('Cannot open ' . $file_path);
        }

        try {
            $config = self::loadFromXML($base_dir, $file_contents, $current_dir);
            $config->hash = sha1($file_contents . \PSALM_VERSION);
        } catch (ConfigException $e) {
            throw new ConfigException(
                'Problem parsing ' . $file_path . ":\n" . '  ' . $e->getMessage()
            );
        }

        return $config;
    }

    /**
     * Creates a new config object from an XML string
     *
     * @throws ConfigException
     *
     * @param  string           $base_dir
     * @param  string           $file_contents
     * @param  string|null      $current_dir Current working directory, if different to $base_dir
     *
     * @return self
     */
    public static function loadFromXML($base_dir, $file_contents, $current_dir = null)
    {
        if ($current_dir === null) {
            $current_dir = $base_dir;
        }

        self::validateXmlConfig($file_contents);

        return self::fromXmlAndPaths($base_dir, $file_contents, $current_dir);
    }


    /**
     * @throws ConfigException
     */
    private static function validateXmlConfig(string $file_contents): void
    {
        $schema_path = dirname(dirname(__DIR__)) . '/config.xsd';

        if (!file_exists($schema_path)) {
            throw new ConfigException('Cannot locate config schema');
        }

        $dom_document = new \DOMDocument();
        $dom_document->loadXML($file_contents);

        $psalm_nodes = $dom_document->getElementsByTagName('psalm');

        /** @var \DomElement|null */
        $psalm_node = $psalm_nodes->item(0);

        if (!$psalm_node) {
            throw new ConfigException(
                'Missing psalm node'
            );
        }

        if (!$psalm_node->hasAttribute('xmlns')) {
            $psalm_node->setAttribute('xmlns', 'https://getpsalm.org/schema/config');

            $old_dom_document = $dom_document;
            $dom_document = new \DOMDocument();
            $dom_document->loadXML($old_dom_document->saveXML());
        }

        // Enable user error handling
        libxml_use_internal_errors(true);

        if (!$dom_document->schemaValidate($schema_path)) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                if ($error->level === LIBXML_ERR_FATAL || $error->level === LIBXML_ERR_ERROR) {
                    throw new ConfigException(
                        'Error on line ' . $error->line . ":\n" . '    ' . $error->message
                    );
                }
            }
            libxml_clear_errors();
        }
    }


    /**
     * @psalm-suppress MixedMethodCall
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     * @psalm-suppress MixedPropertyAssignment
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedPropertyFetch
     *
     * @throws ConfigException
     */
    private static function fromXmlAndPaths(string $base_dir, string $file_contents, string $current_dir): self
    {
        $config = new static();

        $config_xml = new SimpleXMLElement($file_contents);

        $booleanAttributes = [
            'useDocblockTypes' => 'use_docblock_types',
            'useDocblockPropertyTypes' => 'use_docblock_property_types',
            'throwExceptionOnError' => 'throw_exception',
            'hideExternalErrors' => 'hide_external_errors',
            'resolveFromConfigFile' => 'resolve_from_config_file',
            'allowFileIncludes' => 'allow_includes',
            'totallyTyped' => 'totally_typed',
            'strictBinaryOperands' => 'strict_binary_operands',
            'requireVoidReturnType' => 'add_void_docblocks',
            'useAssertForType' => 'use_assert_for_type',
            'rememberPropertyAssignmentsAfterCall' => 'remember_property_assignments_after_call',
            'allowPhpStormGenerics' => 'allow_phpstorm_generics',
            'allowStringToStandInForClass' => 'allow_string_standin_for_class',
            'usePhpDocMethodsWithoutMagicCall' => 'use_phpdoc_method_without_magic_or_parent',
            'memoizeMethodCallResults' => 'memoize_method_calls',
            'hoistConstants' => 'hoist_constants',
            'addParamDefaultToDocblockType' => 'add_param_default_to_docblock_type',
            'checkForThrowsDocblock' => 'check_for_throws_docblock',
            'checkForThrowsInGlobalScope' => 'check_for_throws_in_global_scope',
            'forbidEcho' => 'forbid_echo',
            'ignoreInternalFunctionFalseReturn' => 'ignore_internal_falsable_issues',
            'ignoreInternalFunctionNullReturn' => 'ignore_internal_nullable_issues',
            'includePhpVersionsInErrorBaseline' => 'include_php_versions_in_error_baseline',
        ];

        foreach ($booleanAttributes as $xmlName => $internalName) {
            if (isset($config_xml[$xmlName])) {
                $attribute_text = (string) $config_xml[$xmlName];
                $config->setBooleanAttribute(
                    $internalName,
                    $attribute_text === 'true' || $attribute_text === '1'
                );
            }
        }

        if ($config->resolve_from_config_file) {
            $config->base_dir = $base_dir;
        } else {
            $config->base_dir = $current_dir;
            $base_dir = $current_dir;
        }

        if (isset($config_xml['autoloader'])) {
            $autoloader_path = $config->base_dir . DIRECTORY_SEPARATOR . $config_xml['autoloader'];

            if (!file_exists($autoloader_path)) {
                throw new ConfigException('Cannot locate autoloader');
            }

            $config->autoloader = realpath($autoloader_path);
        }

        if (isset($config_xml['cacheDirectory'])) {
            $config->cache_directory = (string)$config_xml['cacheDirectory'];
        } else {
            $config->cache_directory = sys_get_temp_dir() . '/psalm';
        }

        $config->global_cache_directory = $config->cache_directory;

        $config->cache_directory .= DIRECTORY_SEPARATOR . sha1($base_dir);

        if (is_dir($config->cache_directory) === false && @mkdir($config->cache_directory, 0777, true) === false) {
            trigger_error('Could not create cache directory: ' . $config->cache_directory, E_USER_ERROR);
        }

        if (isset($config_xml['serializer'])) {
            $attribute_text = (string) $config_xml['serializer'];
            $config->use_igbinary = $attribute_text === 'igbinary';
        } elseif ($igbinary_version = phpversion('igbinary')) {
            $config->use_igbinary = version_compare($igbinary_version, '2.0.5') >= 0;
        }


        if (isset($config_xml['findUnusedCode'])) {
            $attribute_text = (string) $config_xml['findUnusedCode'];
            $config->find_unused_code = $attribute_text === 'true' || $attribute_text === '1';
            $config->find_unused_variables = $config->find_unused_code;
        }

        if (isset($config_xml['findUnusedVariablesAndParams'])) {
            $attribute_text = (string) $config_xml['findUnusedVariablesAndParams'];
            $config->find_unused_variables = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['errorBaseline'])) {
            $attribute_text = (string) $config_xml['errorBaseline'];
            $config->error_baseline = $attribute_text;
        }

        if (isset($config_xml['maxStringLength'])) {
            $attribute_text = intval($config_xml['maxStringLength']);
            $config->max_string_length = $attribute_text;
        }

        if (isset($config_xml['parseSql'])) {
            $attribute_text = (string) $config_xml['parseSql'];
            $config->parse_sql = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['inferPropertyTypesFromConstructor'])) {
            $attribute_text = (string) $config_xml['inferPropertyTypesFromConstructor'];
            $config->infer_property_types_from_constructor = $attribute_text === 'true' || $attribute_text === '1';
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
                $config->mock_classes[] = strtolower((string)$mock_class['name']);
            }
        }

        if (isset($config_xml->ignoreExceptions)) {
            if (isset($config_xml->ignoreExceptions->class)) {
                /** @var \SimpleXMLElement $exception_class */
                foreach ($config_xml->ignoreExceptions->class as $exception_class) {
                    $exception_name = (string) $exception_class['name'];
                    $global_attribute_text = (string) $exception_class['onlyGlobalScope'];
                    if ($global_attribute_text !== 'true' && $global_attribute_text !== '1') {
                        $config->ignored_exceptions[$exception_name] = true;
                    }
                    $config->ignored_exceptions_in_global_scope[$exception_name] = true;
                }
            }
            if (isset($config_xml->ignoreExceptions->classAndDescendants)) {
                /** @var \SimpleXMLElement $exception_class */
                foreach ($config_xml->ignoreExceptions->classAndDescendants as $exception_class) {
                    $exception_name = (string) $exception_class['name'];
                    $global_attribute_text = (string) $exception_class['onlyGlobalScope'];
                    if ($global_attribute_text !== 'true' && $global_attribute_text !== '1') {
                        $config->ignored_exceptions_and_descendants[$exception_name] = true;
                    }
                    $config->ignored_exceptions_and_descendants_in_global_scope[$exception_name] = true;
                }
            }
        }

        if (isset($config_xml->forbiddenFunctions) && isset($config_xml->forbiddenFunctions->function)) {
            /** @var \SimpleXMLElement $forbidden_function */
            foreach ($config_xml->forbiddenFunctions->function as $forbidden_function) {
                $config->forbidden_functions[strtolower((string) $forbidden_function['name'])] = true;
            }
        }

        if (isset($config_xml->exitFunctions) && isset($config_xml->exitFunctions->function)) {
            /** @var \SimpleXMLElement $exit_function */
            foreach ($config_xml->exitFunctions->function as $exit_function) {
                $config->exit_functions[strtolower((string) $exit_function['name'])] = true;
            }
        }

        if (isset($config_xml->stubs) && isset($config_xml->stubs->file)) {
            /** @var \SimpleXMLElement $stub_file */
            foreach ($config_xml->stubs->file as $stub_file) {
                $file_path = realpath($config->base_dir . DIRECTORY_SEPARATOR . $stub_file['name']);

                if (!$file_path) {
                    throw new Exception\ConfigException(
                        'Cannot resolve stubfile path ' . $config->base_dir . DIRECTORY_SEPARATOR . $stub_file['name']
                    );
                }

                $config->addStubFile($file_path);
            }
        }

        // this plugin loading system borrows heavily from etsy/phan
        if (isset($config_xml->plugins)) {
            if (isset($config_xml->plugins->plugin)) {
                /** @var \SimpleXMLElement $plugin */
                foreach ($config_xml->plugins->plugin as $plugin) {
                    $plugin_file_name = (string) $plugin['filename'];

                    $path = Path::isAbsolute($plugin_file_name)
                        ? $plugin_file_name
                        : $config->base_dir . $plugin_file_name;

                    $config->addPluginPath($path);
                }
            }
            if (isset($config_xml->plugins->pluginClass)) {
                /** @var \SimpleXMLElement $plugin */
                foreach ($config_xml->plugins->pluginClass as $plugin) {
                    $plugin_class_name = $plugin['class'];
                    // any child elements are used as plugin configuration
                    $plugin_config = null;
                    if ($plugin->count()) {
                        $plugin_config = $plugin->children();
                    }

                    $config->addPluginClass($plugin_class_name, $plugin_config);
                }
            }
        }

        if (isset($config_xml->issueHandlers)) {
            /** @var \SimpleXMLElement $issue_handler */
            foreach ($config_xml->issueHandlers->children() as $key => $issue_handler) {
                if ($key === 'PluginIssue') {
                    $custom_class_name = (string) $issue_handler['name'];
                    /** @var string $key */
                    $config->issue_handlers[$custom_class_name] = IssueHandler::loadFromXMLElement(
                        $issue_handler,
                        $base_dir
                    );
                } else {
                    /** @var string $key */
                    $config->issue_handlers[$key] = IssueHandler::loadFromXMLElement(
                        $issue_handler,
                        $base_dir
                    );
                }
            }
        }

        if (isset($config_xml->globals) && isset($config_xml->globals->var)) {
            /** @var \SimpleXMLElement $var */
            foreach ($config_xml->globals->var as $var) {
                $config->globals['$' . (string) $var['name']] = (string) $var['type'];
            }
        }

        return $config;
    }

    /**
     * @param  string $autoloader_path
     *
     * @return void
     *
     * @psalm-suppress UnresolvableInclude
     */
    private function requireAutoloader($autoloader_path)
    {
        require_once($autoloader_path);
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
    public function setComposerClassLoader(?ClassLoader $loader = null)
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

                $this->filetype_analyzer_paths[$extension_name] = $path;
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

    /** @return void */
    public function addPluginClass(string $class_name, SimpleXMLElement $plugin_config = null)
    {
        $this->plugin_classes[] = ['class' => $class_name, 'config' => $plugin_config];
    }

    /** @return array<array{class:string, config:?SimpleXmlElement}> */
    public function getPluginClasses(): array
    {
        return $this->plugin_classes;
    }

    /**
     * Initialises all the plugins (done once the config is fully loaded)
     *
     * @return void
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public function initializePlugins(ProjectAnalyzer $project_analyzer)
    {
        $codebase = $project_analyzer->getCodebase();

        $socket = new PluginRegistrationSocket($this, $codebase);
        // initialize plugin classes earlier to let them hook into subsequent load process
        foreach ($this->plugin_classes as $plugin_class_entry) {
            $plugin_class_name = $plugin_class_entry['class'];
            $plugin_config = $plugin_class_entry['config'];
            try {
                // Below will attempt to load plugins from the project directory first.
                // Failing that, it will use registered autoload chain, which will load
                // plugins from Psalm directory or phar file. If that fails as well, it
                // will fall back to project autoloader. It may seem that the last step
                // will always fail, but it's only true if project uses Composer autoloader
                if ($this->composer_class_loader
                    && ($plugin_class_path = $this->composer_class_loader->findFile($plugin_class_name))
                ) {
                    /** @psalm-suppress UnresolvableInclude */
                    require_once($plugin_class_path);
                } else {
                    if (!class_exists($plugin_class_name, true)) {
                        throw new \UnexpectedValueException($plugin_class_name . ' is not a known class');
                    }
                }

                /**
                 * @psalm-suppress InvalidStringClass
                 *
                 * @var Plugin\PluginEntryPointInterface
                 */
                $plugin_object = new $plugin_class_name;
                $plugin_object($socket, $plugin_config);
            } catch (\Throwable $e) {
                throw new ConfigException('Failed to load plugin ' . $plugin_class_name, 0, $e);
            }
        }

        foreach ($this->filetype_scanner_paths as $extension => $path) {
            $fq_class_name = $this->getPluginClassForPath(
                $codebase,
                $path,
                FileScanner::class
            );

            /** @psalm-suppress UnresolvableInclude */
            require_once($path);

            $this->filetype_scanners[$extension] = $fq_class_name;
        }

        foreach ($this->filetype_analyzer_paths as $extension => $path) {
            $fq_class_name = $this->getPluginClassForPath(
                $codebase,
                $path,
                FileAnalyzer::class
            );

            /** @psalm-suppress UnresolvableInclude */
            require_once($path);

            $this->filetype_analyzers[$extension] = $fq_class_name;
        }

        foreach ($this->plugin_paths as $path) {
            try {
                $plugin_object = new FileBasedPluginAdapter($path, $this, $codebase);
                $plugin_object($socket);
            } catch (\Throwable $e) {
                throw new ConfigException('Failed to load plugin ' . $path, 0, $e);
            }
        }
    }

    /**
     * @template T
     *
     * @param  string $path
     * @param  T::class $must_extend
     *
     * @return class-string<T>
     */
    private function getPluginClassForPath(Codebase $codebase, $path, $must_extend)
    {
        $file_storage = $codebase->createFileStorageForPath($path);
        $file_to_scan = new FileScanner($path, $this->shortenFileName($path), true);
        $file_to_scan->scan(
            $codebase,
            $file_storage
        );

        $declared_classes = ClassLikeAnalyzer::getClassesForFile($codebase, $path);

        if (!count($declared_classes)) {
            throw new \InvalidArgumentException(
                'Plugins must have at least one class in the file - ' . $path . ' has ' .
                    count($declared_classes)
            );
        }

        $fq_class_name = reset($declared_classes);

        if (!$codebase->classlikes->classExtends(
            $fq_class_name,
            $must_extend
        )
        ) {
            throw new \InvalidArgumentException(
                'This plugin must extend ' . $must_extend . ' - ' . $path . ' does not'
            );
        }

        /**
         * @var class-string<T>
         */
        return $fq_class_name;
    }

    /**
     * @param  string $file_name
     *
     * @return string
     */
    public function shortenFileName($file_name)
    {
        return preg_replace('/^' . preg_quote($this->base_dir, '/') . '/', '', $file_name);
    }

    /**
     * @param   string $issue_type
     * @param   string $file_path
     *
     * @return  bool
     */
    public function reportIssueInFile($issue_type, $file_path)
    {
        if (!$this->totally_typed && in_array($issue_type, self::MIXED_ISSUES, true)) {
            return false;
        }

        if ($this->hide_external_errors) {
            if ($this->mustBeIgnored($file_path)) {
                return false;
            }

            $project_analyzer = ProjectAnalyzer::getInstance();

            $codebase = $project_analyzer->getCodebase();

            $dependent_files = [strtolower($file_path) => $file_path];

            try {
                $file_storage = $codebase->file_storage_provider->get($file_path);
                $dependent_files += $file_storage->required_by_file_paths;
            } catch (\InvalidArgumentException $e) {
                // do nothing
            }

            $any_file_path_matched = false;

            foreach ($dependent_files as $dependent_file_path) {
                if (($codebase->analyzer->canReportIssues($dependent_file_path)
                        || $project_analyzer->canReportIssues($dependent_file_path))
                    && !$this->mustBeIgnored($dependent_file_path)
                ) {
                    $any_file_path_matched = true;
                    break;
                }
            }

            if (!$any_file_path_matched) {
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
     * @param   string $file_path
     *
     * @return  bool
     */
    public function mustBeIgnored($file_path)
    {
        return $this->project_files && $this->project_files->forbids($file_path);
    }

    /**
     * @param string[] $suppressed_issues
     */
    public function getReportingLevelForIssue(CodeIssue $e, array $suppressed_issues = []) : string
    {
        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);

        if (in_array($issue_type, $suppressed_issues, true)) {
            return self::REPORT_SUPPRESS;
        }

        $reporting_level = null;

        if ($e instanceof ClassIssue) {
            $reporting_level = $this->getReportingLevelForClass($issue_type, $e->fq_classlike_name);
        } elseif ($e instanceof MethodIssue) {
            $reporting_level = $this->getReportingLevelForMethod($issue_type, $e->method_id);
        } elseif ($e instanceof FunctionIssue) {
            $reporting_level = $this->getReportingLevelForFunction($issue_type, $e->function_id);
        } elseif ($e instanceof PropertyIssue) {
            $reporting_level = $this->getReportingLevelForProperty($issue_type, $e->property_id);
        } elseif ($e instanceof ArgumentIssue && $e->function_id) {
            $reporting_level = $this->getReportingLevelForArgument($issue_type, $e->function_id);
        }

        if ($reporting_level === null) {
            $reporting_level = $this->getReportingLevelForFile($issue_type, $e->getFilePath());
        }

        $parent_issue_type = self::getParentIssueType($issue_type);

        if ($parent_issue_type && in_array($parent_issue_type, $suppressed_issues, true)) {
            return self::REPORT_SUPPRESS;
        }

        if ($parent_issue_type && $reporting_level === Config::REPORT_ERROR) {
            $parent_reporting_level = $this->getReportingLevelForFile($parent_issue_type, $e->getFilePath());

            if ($parent_reporting_level !== $reporting_level) {
                return $parent_reporting_level;
            }
        }

        return $reporting_level;
    }

    /**
     * @param  string $issue_type
     *
     * @return string|null
     */
    private static function getParentIssueType($issue_type)
    {
        if (strpos($issue_type, 'Possibly') === 0) {
            $stripped_issue_type = preg_replace('/^Possibly(False|Null)?/', '', $issue_type);

            if (strpos($stripped_issue_type, 'Invalid') === false && strpos($stripped_issue_type, 'Un') !== 0) {
                $stripped_issue_type = 'Invalid' . $stripped_issue_type;
            }

            return $stripped_issue_type;
        }

        if (preg_match('/^(False|Null)[A-Z]/', $issue_type)) {
            return preg_replace('/^(False|Null)/', 'Invalid', $issue_type);
        }

        if ($issue_type === 'UndefinedInterfaceMethod') {
            return 'UndefinedMethod';
        }

        if ($issue_type === 'UninitializedProperty') {
            return 'PropertyNotSetInConstructor';
        }

        if ($issue_type === 'InvalidDocblockParamName') {
            return 'InvalidDocblock';
        }

        if ($issue_type === 'UnusedClosureParam') {
            return 'UnusedParam';
        }

        if ($issue_type === 'TraitMethodSignatureMismatch') {
            return 'MethodSignatureMismatch';
        }

        if ($issue_type === 'ImplementedParamTypeMismatch') {
            return 'MoreSpecificImplementedParamType';
        }

        if ($issue_type === 'UndefinedDocblockClass') {
            return 'UndefinedClass';
        }

        if ($issue_type === 'MixedArgumentTypeCoercion'
            || $issue_type === 'MixedPropertyTypeCoercion'
            || $issue_type === 'MixedReturnTypeCoercion'
            || $issue_type === 'MixedArrayTypeCoercion'
        ) {
            return 'MixedTypeCoercion';
        }

        if ($issue_type === 'ArgumentTypeCoercion'
            || $issue_type === 'PropertyTypeCoercion'
            || $issue_type === 'ReturnTypeCoercion'
        ) {
            return 'TypeCoercion';
        }

        return null;
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
     * @param   string $issue_type
     * @param   string $fq_classlike_name
     *
     * @return  string|null
     */
    public function getReportingLevelForClass($issue_type, $fq_classlike_name)
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForClass($fq_classlike_name);
        }
    }

    /**
     * @param   string $issue_type
     * @param   string $method_id
     *
     * @return  string|null
     */
    public function getReportingLevelForMethod($issue_type, $method_id)
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForMethod($method_id);
        }
    }

    /**
     * @return  string|null
     */
    public function getReportingLevelForFunction(string $issue_type, string $function_id)
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForFunction($function_id);
        }
    }

    /**
     * @return  string|null
     */
    public function getReportingLevelForArgument(string $issue_type, string $function_id)
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForArgument($function_id);
        }
    }

    /**
     * @param   string $issue_type
     * @param   string $property_id
     *
     * @return  string|null
     */
    public function getReportingLevelForProperty($issue_type, $property_id)
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForProperty($property_id);
        }
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
    public function getProjectFiles()
    {
        if (!$this->project_files) {
            return [];
        }

        return $this->project_files->getFiles();
    }

    /**
     * @param   string $file_path
     *
     * @return  bool
     */
    public function reportTypeStatsForFile($file_path)
    {
        return $this->project_files
            && $this->project_files->allows($file_path)
            && $this->project_files->reportTypeStats($file_path);
    }

    /**
     * @param   string $file_path
     *
     * @return  bool
     */
    public function useStrictTypesForFile($file_path)
    {
        return $this->project_files && $this->project_files->useStrictTypes($file_path);
    }

    /**
     * @return array<string>
     */
    public function getFileExtensions()
    {
        return $this->file_extensions;
    }

    /**
     * @return array<string, class-string<FileScanner>>
     */
    public function getFiletypeScanners()
    {
        return $this->filetype_scanners;
    }

    /**
     * @return array<string, class-string<FileAnalyzer>>
     */
    public function getFiletypeAnalyzers()
    {
        return $this->filetype_analyzers;
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
    public function visitStubFiles(Codebase $codebase, Progress $progress = null)
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $codebase->register_stub_files = true;

        // note: don't realpath $generic_stubs_path, or phar version will fail
        $generic_stubs_path = __DIR__ . '/Internal/Stubs/CoreGenericFunctions.php';

        if (!file_exists($generic_stubs_path)) {
            throw new \UnexpectedValueException('Cannot locate core generic stubs');
        }

        // note: don't realpath $generic_classes_path, or phar version will fail
        $generic_classes_path = __DIR__ . '/Internal/Stubs/CoreGenericClasses.php';

        if (!file_exists($generic_classes_path)) {
            throw new \UnexpectedValueException('Cannot locate core generic classes');
        }

        $stub_files = array_merge([$generic_stubs_path, $generic_classes_path], $this->stub_files);

        $phpstorm_meta_path = $this->base_dir . DIRECTORY_SEPARATOR . '.phpstorm.meta.php';

        if (file_exists($phpstorm_meta_path)) {
            $stub_files[] = $phpstorm_meta_path;
        }

        foreach ($stub_files as $file_path) {
            $file_path = \str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file_path);
            $codebase->scanner->addFileToShallowScan($file_path);
        }

        $progress->debug('Registering stub files' . "\n");

        $codebase->scanFiles();

        $progress->debug('Finished registering stub files' . "\n");

        $codebase->register_stub_files = false;
    }

    /**
     * @return string
     */
    public function getCacheDirectory()
    {
        return $this->cache_directory;
    }

    /**
     * @return ?string
     */
    public function getGlobalCacheDirectory()
    {
        return $this->global_cache_directory;
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
     * @return array<callable-string, bool>
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
    public function visitComposerAutoloadFiles(ProjectAnalyzer $project_analyzer, Progress $progress = null)
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $this->collectPredefinedConstants();
        $this->collectPredefinedFunctions();

        $composer_json_path = $this->base_dir . 'composer.json'; // this should ideally not be hardcoded

        $autoload_files_files = [];

        if ($this->autoloader) {
            $autoload_files_files[] = $this->autoloader;
        }

        if (file_exists($composer_json_path)) {
            /** @psalm-suppress PossiblyFalseArgument */
            if (!$composer_json = json_decode(file_get_contents($composer_json_path), true)) {
                throw new \UnexpectedValueException('Invalid composer.json at ' . $composer_json_path);
            }

            if (isset($composer_json['autoload']['files'])) {
                /** @var string[] */
                $composer_autoload_files = $composer_json['autoload']['files'];

                foreach ($composer_autoload_files as $file) {
                    $file_path = realpath($this->base_dir . $file);

                    if ($file_path && file_exists($file_path)) {
                        $autoload_files_files[] = $file_path;
                    }
                }
            }

            $vendor_autoload_files_path
                = $this->base_dir . DIRECTORY_SEPARATOR . 'vendor'
                    . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_files.php';

            if (file_exists($vendor_autoload_files_path)) {
                /**
                 * @var string[]
                 * @psalm-suppress UnresolvableInclude
                 */
                $vendor_autoload_files = require $vendor_autoload_files_path;

                $autoload_files_files = array_merge($autoload_files_files, $vendor_autoload_files);
            }
        }

        $autoload_files_files = array_unique($autoload_files_files);

        if ($autoload_files_files) {
            $codebase = $project_analyzer->getCodebase();
            $codebase->register_autoload_files = true;

            foreach ($autoload_files_files as $file_path) {
                $file_path = \str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file_path);
                $codebase->scanner->addFileToDeepScan($file_path);
            }

            $progress->debug('Registering autoloaded files' . "\n");

            $codebase->scanner->scanFiles($codebase->classlikes);

            $progress->debug('Finished registering autoloaded files' . "\n");

            $codebase->register_autoload_files = false;
        }

        if ($this->autoloader) {
            // do this in a separate method so scope does not leak
            $this->requireAutoloader($this->autoloader);

            $this->collectPredefinedConstants();
            $this->collectPredefinedFunctions();
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
            return false;
        }

        return $this->composer_class_loader->findFile($fq_classlike_name);
    }

    public function getPotentialComposerFilePathForClassLike(string $class) : ?string
    {
        if (!$this->composer_class_loader) {
            return null;
        }

        /** @var array<string, array<int, string>> */
        $psr4_prefixes = $this->composer_class_loader->getPrefixesPsr4();

        // PSR-4 lookup
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';

        $candidate_path = null;

        $maxDepth = 0;

        $subPath = $class;
        while (false !== $lastPos = strrpos($subPath, '\\')) {
            $subPath = substr($subPath, 0, $lastPos);
            $search = $subPath . '\\';
            if (isset($psr4_prefixes[$search])) {
                $depth = substr_count($search, '\\');
                $pathEnd = DIRECTORY_SEPARATOR . substr($logicalPathPsr4, $lastPos + 1);

                foreach ($psr4_prefixes[$search] as $dir) {
                    $dir = realpath($dir);

                    if ($dir
                        && $depth > $maxDepth
                        && $this->isInProjectDirs($dir . DIRECTORY_SEPARATOR . 'testdummy.php')
                    ) {
                        $maxDepth = $depth;
                        $candidate_path = realpath($dir) . $pathEnd;
                    }
                }
            }
        }

        return $candidate_path;
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

    /**
     * @return void
     */
    public function setServerMode()
    {
        $this->cache_directory .= '-s';
    }

    /** @return void */
    public function addStubFile(string $stub_file)
    {
        $this->stub_files[] = $stub_file;
    }

    private function setBooleanAttribute(string $name, bool $value): void
    {
        $this->$name = $value;
    }
}
