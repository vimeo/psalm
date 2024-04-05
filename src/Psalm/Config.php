<?php

namespace Psalm;

use Composer\Autoload\ClassLoader;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use DOMAttr;
use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use JsonException;
use LogicException;
use OutOfBoundsException;
use Psalm\CodeLocation\Raw;
use Psalm\Config\IssueHandler;
use Psalm\Config\ProjectFileFilter;
use Psalm\Config\TaintAnalysisFileFilter;
use Psalm\Exception\ConfigException;
use Psalm\Exception\ConfigNotFoundException;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\Composer;
use Psalm\Internal\EventDispatcher;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\AddRemoveTaints\HtmlFunctionTainter;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Issue\ArgumentIssue;
use Psalm\Issue\ClassConstantIssue;
use Psalm\Issue\ClassIssue;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\ConfigIssue;
use Psalm\Issue\FunctionIssue;
use Psalm\Issue\MethodIssue;
use Psalm\Issue\PropertyIssue;
use Psalm\Issue\VariableIssue;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\PluginFileExtensionsInterface;
use Psalm\Plugin\PluginInterface;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\Filesystem\Path;
use Throwable;
use UnexpectedValueException;
use XdgBaseDir\Xdg;
use stdClass;

use function array_key_exists;
use function array_merge;
use function array_pad;
use function array_pop;
use function array_shift;
use function assert;
use function basename;
use function chdir;
use function class_exists;
use function clearstatcache;
use function count;
use function dirname;
use function explode;
use function extension_loaded;
use function fclose;
use function file_exists;
use function file_get_contents;
use function flock;
use function fopen;
use function function_exists;
use function get_class;
use function get_defined_constants;
use function get_defined_functions;
use function getcwd;
use function glob;
use function implode;
use function in_array;
use function is_a;
use function is_array;
use function is_dir;
use function is_file;
use function is_resource;
use function is_string;
use function json_decode;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function mkdir;
use function phpversion;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function realpath;
use function reset;
use function rmdir;
use function rtrim;
use function scandir;
use function sha1;
use function simplexml_import_dom;
use function str_replace;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;
use function substr_count;
use function sys_get_temp_dir;
use function unlink;
use function usleep;
use function version_compare;

use const DIRECTORY_SEPARATOR;
use const GLOB_NOSORT;
use const JSON_THROW_ON_ERROR;
use const LIBXML_ERR_ERROR;
use const LIBXML_ERR_FATAL;
use const LIBXML_NONET;
use const LIBXML_NOWARNING;
use const LOCK_EX;
use const PHP_EOL;
use const PHP_VERSION_ID;
use const PSALM_VERSION;
use const SCANDIR_SORT_NONE;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-consistent-constructor
 */
class Config
{
    private const DEFAULT_FILE_NAME = 'psalm.xml';
    public const CONFIG_NAMESPACE = 'https://getpsalm.org/schema/config';
    public const REPORT_INFO = 'info';
    public const REPORT_ERROR = 'error';
    public const REPORT_SUPPRESS = 'suppress';

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
    private const MIXED_ISSUES = [
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
        'MixedArgumentTypeCoercion',
        'MixedPropertyTypeCoercion',
        'MixedReturnTypeCoercion',
    ];

    /**
     * These are special object classes that allow any and all properties to be get/set on them
     *
     * @var array<int, lowercase-string>
     */
    protected $universal_object_crates;

    /**
     * @var static|null
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
    public $use_docblock_property_types = false;

    /**
     * Whether using property annotations in docblocks should implicitly seal properties
     *
     * @var bool
     */
    public $docblock_property_types_seal_properties = true;

    /**
     * Whether or not to throw an exception on first error
     *
     * @var bool
     */
    public $throw_exception = false;

    /**
     * The directory to store PHP Parser (and other) caches
     *
     * @internal
     * @var string|null
     */
    public $cache_directory;

    private bool $cache_directory_initialized = false;

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
     * @var ProjectFileFilter|null
     */
    protected $extra_files;

    /**
     * The base directory of this config file without trailing slash
     *
     * @var string
     */
    public $base_dir;

    /**
     * The PHP version to assume as declared in the config file
     */
    private ?string $configured_php_version = null;

    /**
     * @var array<int, string>
     */
    private array $file_extensions = ['php'];

    /**
     * @var array<string, class-string<FileScanner>>
     */
    private array $filetype_scanners = [];

    /**
     * @var array<string, class-string<FileAnalyzer>>
     */
    private array $filetype_analyzers = [];

    /**
     * @var array<string, string>
     */
    private array $filetype_scanner_paths = [];

    /**
     * @var array<string, string>
     */
    private array $filetype_analyzer_paths = [];

    /**
     * @var array<string, IssueHandler>
     */
    private array $issue_handlers = [];

    /**
     * @var array<int, string>
     */
    private array $mock_classes = [];

    /**
     * @var array<string, string>
     */
    private array $preloaded_stub_files = [];

    /**
     * @var array<string, string>
     */
    private array $stub_files = [];

    /**
     * @var bool
     */
    public $hide_external_errors = false;

    /**
     * @var bool
     */
    public $hide_all_errors_except_passed_files = false;

    /** @var bool */
    public $allow_includes = true;

    /** @var 1|2|3|4|5|6|7|8 */
    public $level = 1;

    /**
     * @var ?bool
     */
    public $show_mixed_issues;

    /** @var bool */
    public $strict_binary_operands = false;

    /**
     * @var bool
     */
    public $remember_property_assignments_after_call = true;

    /** @var bool */
    public $use_igbinary = false;

    /** @var 'lz4'|'deflate'|'off' */
    public $compressor = 'off';

    /**
     * @var bool
     */
    public $allow_string_standin_for_class = false;

    /**
     * @var bool
     */
    public $disable_suppress_all = false;

    /**
     * @var bool
     */
    public $use_phpdoc_method_without_magic_or_parent = false;

    /**
     * @var bool
     */
    public $use_phpdoc_property_without_magic_or_parent = false;

    /**
     * @var bool
     */
    public $skip_checks_on_unresolvable_includes = false;

    /**
     * @var bool
     */
    public $seal_all_methods = false;

    /**
     * @var bool
     */
    public $seal_all_properties = false;

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
    public $disable_var_parsing = false;

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
     * @var bool
     */
    public $ensure_array_string_offsets_exist = false;

    /**
     * @var bool
     */
    public $ensure_array_int_offsets_exist = false;

    /**
     * @var bool
     */
    public $ensure_override_attribute = false;

    /**
     * @var array<lowercase-string, bool>
     */
    public $forbidden_functions = [];

    /**
     * TODO: Psalm 6: Update default to be true and remove warning.
     *
     * @var bool
     */
    public $find_unused_code = false;

    /**
     * @var bool
     */
    public $find_unused_variables = false;

    /**
     * @var bool
     */
    public $find_unused_psalm_suppress = false;

    /**
     * TODO: Psalm 6: Update default to be true and remove warning.
     */
    public bool $find_unused_baseline_entry = false;

    /**
     * @var bool
     */
    public $run_taint_analysis = false;

    /** @var bool */
    public $use_phpstorm_meta_path = true;

    /**
     * @var bool
     */
    public $resolve_from_config_file = true;

    /**
     * @var bool
     */
    public $restrict_return_types = false;

    /**
     * @var bool
     */
    public $limit_method_complexity = false;

    /**
     * @var int
     */
    public $max_graph_size = 200;

    /**
     * @var int
     */
    public $max_avg_path_length = 70;

    /**
     * @var int
     */
    public $max_shaped_array_size = 100;

    /**
     * @var string[]
     */
    public $plugin_paths = [];

    /**
     * @var array<array{class:string,config:?SimpleXMLElement}>
     */
    private array $plugin_classes = [];

    /**
     * @var bool
     */
    public $allow_internal_named_arg_calls = true;

    /**
     * @var bool
     */
    public $allow_named_arg_calls = true;

    /** @var array<string, mixed> */
    private array $predefined_constants = [];

    /** @var array<callable-string, bool> */
    private array $predefined_functions = [];

    private ?ClassLoader $composer_class_loader = null;

    /**
     * @var string
     */
    public $hash = '';

    /** @var string|null */
    public $error_baseline;

    /**
     * @var bool
     */
    public $include_php_versions_in_error_baseline = false;

    /**
     * @var string
     * @deprecated Please use {@see self::$shepherd_endpoint} instead. Property will be removed in Psalm 6.
     */
    public $shepherd_host = 'shepherd.dev';

    /**
     * @var string
     * @internal
     */
    public $shepherd_endpoint = 'https://shepherd.dev/hooks/psalm/';

    /**
     * @var array<string, string>
     */
    public $globals = [];

    /**
     * @var int
     */
    public $max_string_length = 1_000;

    private ?IncludeCollector $include_collector = null;

    /**
     * @var TaintAnalysisFileFilter|null
     */
    protected $taint_analysis_ignored_files;

    /**
     * @var bool whether to emit a backtrace of emitted issues to stderr
     */
    public $debug_emitted_issues = false;

    private bool $report_info = true;

    /**
     * @var EventDispatcher
     */
    public $eventDispatcher;

    /** @var list<ConfigIssue> */
    public $config_issues = [];

    /**
     * @var 'default'|'never'|'always'
     */
    public $trigger_error_exits = 'default';

    /**
     * @var string[]
     */
    public $internal_stubs = [];

    /** @var ?int */
    public $threads;

    /**
     * A list of php extensions supported by Psalm.
     * Where key - extension name (without ext- prefix), value - whether to load extension’s stub.
     * Values:
     *  - true: ext enabled explicitly or bundled with PHP (should load stubs)
     *  - false: ext disabled explicitly (should not load stubs)
     *  - null: state is unknown (e.g. config not processed yet) or ext neither explicitly enabled or disabled.
     *
     * @psalm-readonly-allow-private-mutation
     * @var array<string, bool|null>
     */
    public $php_extensions = [
        "apcu" => null,
        "decimal" => null,
        "dom" => null,
        "ds" => null,
        "ffi" => null,
        "geos" => null,
        "gmp" => null,
        "ibm_db2" => null,
        "mongodb" => null,
        "mysqli" => null,
        "pdo" => null,
        "random" => null,
        "rdkafka" => null,
        "redis" => null,
        "simplexml" => null,
        "soap" => null,
        "xdebug" => null,
    ];

    /**
     * A list of php extensions described in CallMap Psalm files
     * as opposite to stub files loaded by condition (see stubs/extensions dir).
     *
     * @see https://www.php.net/manual/en/extensions.membership.php
     * @var list<non-empty-string>
     * @readonly
     */
    public $php_extensions_supported_by_psalm_callmaps = [
        'apache',
        'bcmath',
        'bzip2',
        'calendar',
        'ctype',
        'curl',
        'dom',
        'enchant',
        'exif',
        'filter',
        'gd',
        'gettext',
        'gmp',
        'hash',
        'ibm_db2',
        'iconv',
        'imap',
        'intl',
        'json',
        'ldap',
        'libxml',
        'mbstring',
        'mysqli',
        'mysqlnd',
        'mhash',
        'oci8',
        'opcache',
        'openssl',
        'pcntl',
        'PDO',
        'pdo_mysql',
        'pdo-sqlite',
        'pdo-pgsql',
        'pgsql',
        'pspell',
        'phar',
        'phpdbg',
        'posix',
        'redis',
        'readline',
        'session',
        'sockets',
        'sqlite3',
        'snmp',
        'soap',
        'sodium',
        'shmop',
        'sysvsem',
        'tidy',
        'tokenizer',
        'uodbc',
        'xml',
        'xmlreader',
        'xmlwriter',
        'xsl',
        'zip',
        'zlib',
    ];

    /**
     * A list of php extensions required by the project that aren't fully supported by Psalm.
     *
     * @var array<string, true>
     */
    public $php_extensions_not_supported = [];

    /**
     * @var array<class-string, PluginInterface>
     */
    private array $plugins = [];

    /** @var list<string> */
    public array $config_warnings = [];

    /** @internal */
    protected function __construct()
    {
        self::$instance = $this;
        $this->eventDispatcher = new EventDispatcher();
        $this->universal_object_crates = [
            strtolower(stdClass::class),
        ];
    }

    /**
     * Gets a Config object from an XML file.
     *
     * Searches up a folder hierarchy for the most immediate config.
     *
     * @throws ConfigException if a config path is not found
     */
    public static function getConfigForPath(string $path, string $current_dir): Config
    {
        $config_path = self::locateConfigFile($path);

        if (!$config_path) {
            throw new ConfigNotFoundException('Config not found for path ' . $path);
        }

        return self::loadFromXMLFile($config_path, $current_dir);
    }

    /**
     * Searches up a folder hierarchy for the most immediate config.
     *
     * @throws ConfigException
     */
    public static function locateConfigFile(string $path): ?string
    {
        $dir_path = realpath($path);

        if ($dir_path === false) {
            throw new ConfigNotFoundException('Config not found for path ' . $path);
        }

        if (!is_dir($dir_path)) {
            $dir_path = dirname($dir_path);
        }

        do {
            $maybe_path = $dir_path . DIRECTORY_SEPARATOR . self::DEFAULT_FILE_NAME;

            if (file_exists($maybe_path) || file_exists($maybe_path .= '.dist')) {
                return $maybe_path;
            }

            $dir_path = dirname($dir_path);
        } while (dirname($dir_path) !== $dir_path);

        return null;
    }

    /**
     * Creates a new config object from the file
     */
    public static function loadFromXMLFile(string $file_path, string $current_dir): Config
    {
        $file_contents = file_get_contents($file_path);

        $base_dir = dirname($file_path);

        if ($file_contents === false) {
            throw new InvalidArgumentException('Cannot open ' . $file_path);
        }

        if ($file_contents === '') {
            throw new InvalidArgumentException('Invalid empty file ' . $file_path);
        }

        try {
            $config = self::loadFromXML($base_dir, $file_contents, $current_dir, $file_path);
            $config->hash = sha1($file_contents . PSALM_VERSION);
        } catch (ConfigException $e) {
            throw new ConfigException(
                'Problem parsing ' . $file_path . ":\n" . '  ' . $e->getMessage(),
            );
        }

        return $config;
    }

    /**
     * Computes the hash to use for a cache folder from CLI flags and from the config file's xml contents
     */
    public function computeHash(): string
    {
        return sha1($this->hash . ':' . $this->level);
    }

    /**
     * Creates a new config object from an XML string
     *
     * @param  string|null      $current_dir Current working directory, if different to $base_dir
     * @param  non-empty-string $file_contents
     * @throws ConfigException
     */
    public static function loadFromXML(
        string $base_dir,
        string $file_contents,
        ?string $current_dir = null,
        ?string $file_path = null
    ): Config {
        if ($current_dir === null) {
            $current_dir = $base_dir;
        }

        self::validateXmlConfig($base_dir, $file_contents);

        return self::fromXmlAndPaths($base_dir, $file_contents, $current_dir, $file_path);
    }

    /**
     * @param non-empty-string $file_contents
     */
    private static function loadDomDocument(string $base_dir, string $file_contents): DOMDocument
    {
        $dom_document = new DOMDocument();

        // there's no obvious way to set xml:base for a document when loading it from string
        // so instead we're changing the current directory instead to be able to process XIncludes
        $oldpwd = getcwd();
        chdir($base_dir);

        $dom_document->loadXML($file_contents, LIBXML_NONET);
        $dom_document->xinclude(LIBXML_NOWARNING | LIBXML_NONET);

        chdir($oldpwd);
        return $dom_document;
    }

    /**
     * @param non-empty-string $file_contents
     * @throws ConfigException
     */
    private static function validateXmlConfig(string $base_dir, string $file_contents): void
    {
        $schema_path = dirname(__DIR__, 2). '/config.xsd';

        if (!file_exists($schema_path)) {
            throw new ConfigException('Cannot locate config schema');
        }

        // Enable user error handling
        $prev_xml_internal_errors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom_document = self::loadDomDocument($base_dir, $file_contents);

        $psalm_nodes = $dom_document->getElementsByTagName('psalm');

        $psalm_node = $psalm_nodes->item(0);

        if (!$psalm_node) {
            throw new ConfigException(
                'Missing psalm node',
            );
        }

        if (!$psalm_node->hasAttribute('xmlns')) {
            $psalm_node->setAttribute('xmlns', self::CONFIG_NAMESPACE);

            $old_dom_document = $dom_document;
            $old_file_contents = $old_dom_document->saveXML();
            assert($old_file_contents !== false && $old_file_contents !== '');
            $dom_document = self::loadDomDocument($base_dir, $old_file_contents);
        }

        $dom_document->schemaValidate($schema_path); // If it returns false it will generate errors handled below

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev_xml_internal_errors);
        foreach ($errors as $error) {
            if ($error->level === LIBXML_ERR_FATAL || $error->level === LIBXML_ERR_ERROR) {
                throw new ConfigException(
                    'Error on line ' . $error->line . ":\n" . '    ' . $error->message,
                );
            }
        }
    }

    /**
     * @param positive-int $line_number 1-based line number
     * @return int 0-based byte offset
     * @throws OutOfBoundsException
     */
    private static function lineNumberToByteOffset(string $string, int $line_number): int
    {
        if ($line_number === 1) {
            return 0;
        }

        $offset = 0;

        for ($i = 0; $i < $line_number - 1; $i++) {
            $newline_offset = strpos($string, "\n", $offset);
            if (false === $newline_offset) {
                throw new OutOfBoundsException(
                    'Line ' . $line_number . ' is not found in a string with ' . ($i + 1) . ' lines',
                );
            }
            $offset = $newline_offset + 1;
        }

        if ($offset > strlen($string)) {
            throw new OutOfBoundsException('Line ' . $line_number . ' is not found');
        }

        return $offset;
    }

    private static function processDeprecatedAttribute(
        DOMAttr $attribute,
        string $file_contents,
        self $config,
        string $config_path
    ): void {
        $line = $attribute->getLineNo();
        assert($line > 0); // getLineNo() always returns non-zero for nodes loaded from file

        $offset = self::lineNumberToByteOffset($file_contents, $line);
        $attribute_start = strrpos($file_contents, $attribute->name, $offset - strlen($file_contents)) ?: 0;
        $attribute_end = $attribute_start + strlen($attribute->name) - 1;

        $config->config_issues[] = new ConfigIssue(
            'Attribute "' . $attribute->name . '" is deprecated '
            . 'and is going to be removed in the next major version',
            new Raw(
                $file_contents,
                $config_path,
                basename($config_path),
                $attribute_start,
                $attribute_end,
            ),
        );
    }

    private static function processDeprecatedElement(
        DOMElement $deprecated_element_xml,
        string $file_contents,
        self $config,
        string $config_path
    ): void {
        $line = $deprecated_element_xml->getLineNo();
        assert($line > 0);

        $offset = self::lineNumberToByteOffset($file_contents, $line);
        $element_start = strpos($file_contents, $deprecated_element_xml->localName, $offset) ?: 0;
        $element_end = $element_start + strlen($deprecated_element_xml->localName) - 1;

        $config->config_issues[] = new ConfigIssue(
            'Element "' . $deprecated_element_xml->localName . '" is deprecated '
            . 'and is going to be removed in the next major version',
            new Raw(
                $file_contents,
                $config_path,
                basename($config_path),
                $element_start,
                $element_end,
            ),
        );
    }

    private static function processConfigDeprecations(
        self $config,
        DOMDocument $dom_document,
        string $file_contents,
        string $config_path
    ): void {
        $config->config_issues = [];

        // Attributes to be removed in Psalm 6
        $deprecated_attributes = [];

        /** @var list<string> */
        $deprecated_elements = [];

        $psalm_element_item = $dom_document->getElementsByTagName('psalm')->item(0);
        assert($psalm_element_item !== null);
        $attributes = $psalm_element_item->attributes;

        foreach ($attributes as $attribute) {
            if (in_array($attribute->name, $deprecated_attributes, true)) {
                self::processDeprecatedAttribute($attribute, $file_contents, $config, $config_path);
            }
        }

        foreach ($deprecated_elements as $deprecated_element) {
            $deprecated_elements_xml = $dom_document->getElementsByTagNameNS(
                self::CONFIG_NAMESPACE,
                $deprecated_element,
            );
            if ($deprecated_elements_xml->length) {
                $deprecated_element_xml = $deprecated_elements_xml->item(0);
                self::processDeprecatedElement($deprecated_element_xml, $file_contents, $config, $config_path);
            }
        }
    }

    /**
     * @param non-empty-string $file_contents
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedPropertyFetch
     * @throws ConfigException
     */
    private static function fromXmlAndPaths(
        string $base_dir,
        string $file_contents,
        string $current_dir,
        ?string $config_path
    ): self {
        $config = new static();

        $dom_document = self::loadDomDocument($base_dir, $file_contents);

        if (null !== $config_path) {
            self::processConfigDeprecations(
                $config,
                $dom_document,
                $file_contents,
                $config_path,
            );
        }

        $config_xml = simplexml_import_dom($dom_document);

        $booleanAttributes = [
            'useDocblockTypes' => 'use_docblock_types',
            'useDocblockPropertyTypes' => 'use_docblock_property_types',
            'docblockPropertyTypesSealProperties' => 'docblock_property_types_seal_properties',
            'throwExceptionOnError' => 'throw_exception',
            'hideExternalErrors' => 'hide_external_errors',
            'hideAllErrorsExceptPassedFiles' => 'hide_all_errors_except_passed_files',
            'resolveFromConfigFile' => 'resolve_from_config_file',
            'allowFileIncludes' => 'allow_includes',
            'strictBinaryOperands' => 'strict_binary_operands',
            'rememberPropertyAssignmentsAfterCall' => 'remember_property_assignments_after_call',
            'disableVarParsing' => 'disable_var_parsing',
            'allowStringToStandInForClass' => 'allow_string_standin_for_class',
            'disableSuppressAll' => 'disable_suppress_all',
            'usePhpDocMethodsWithoutMagicCall' => 'use_phpdoc_method_without_magic_or_parent',
            'usePhpDocPropertiesWithoutMagicCall' => 'use_phpdoc_property_without_magic_or_parent',
            'memoizeMethodCallResults' => 'memoize_method_calls',
            'hoistConstants' => 'hoist_constants',
            'addParamDefaultToDocblockType' => 'add_param_default_to_docblock_type',
            'checkForThrowsDocblock' => 'check_for_throws_docblock',
            'checkForThrowsInGlobalScope' => 'check_for_throws_in_global_scope',
            'ignoreInternalFunctionFalseReturn' => 'ignore_internal_falsable_issues',
            'ignoreInternalFunctionNullReturn' => 'ignore_internal_nullable_issues',
            'includePhpVersionsInErrorBaseline' => 'include_php_versions_in_error_baseline',
            'ensureArrayStringOffsetsExist' => 'ensure_array_string_offsets_exist',
            'ensureArrayIntOffsetsExist' => 'ensure_array_int_offsets_exist',
            'ensureOverrideAttribute' => 'ensure_override_attribute',
            'reportMixedIssues' => 'show_mixed_issues',
            'skipChecksOnUnresolvableIncludes' => 'skip_checks_on_unresolvable_includes',
            'sealAllMethods' => 'seal_all_methods',
            'sealAllProperties' => 'seal_all_properties',
            'runTaintAnalysis' => 'run_taint_analysis',
            'usePhpStormMetaPath' => 'use_phpstorm_meta_path',
            'allowInternalNamedArgumentsCalls' => 'allow_internal_named_arg_calls',
            'allowNamedArgumentCalls' => 'allow_named_arg_calls',
            'findUnusedPsalmSuppress' => 'find_unused_psalm_suppress',
            'findUnusedBaselineEntry' => 'find_unused_baseline_entry',
            'reportInfo' => 'report_info',
            'restrictReturnTypes' => 'restrict_return_types',
            'limitMethodComplexity' => 'limit_method_complexity',
        ];

        foreach ($booleanAttributes as $xmlName => $internalName) {
            if (isset($config_xml[$xmlName])) {
                $attribute_text = (string) $config_xml[$xmlName];
                $config->setBooleanAttribute(
                    $internalName,
                    $attribute_text === 'true' || $attribute_text === '1',
                );
            }
        }

        if ($config->resolve_from_config_file) {
            $config->base_dir = $base_dir;
        } else {
            $config->base_dir = $current_dir;
            $base_dir = $current_dir;
        }

        $composer_json_path = Composer::getJsonFilePath($config->base_dir);

        $composer_json = null;
        if (file_exists($composer_json_path)) {
            $composer_json = json_decode(file_get_contents($composer_json_path), true);
            if (!is_array($composer_json)) {
                throw new UnexpectedValueException('Invalid composer.json at ' . $composer_json_path);
            }
        }
        $required_extensions = [];
        foreach (($composer_json["require"] ?? []) as $required => $_) {
            if (strpos($required, "ext-") === 0) {
                $required_extensions[strtolower(substr($required, 4))] = true;
            }
        }
        foreach ($required_extensions as $required_ext => $_) {
            if (array_key_exists($required_ext, $config->php_extensions)) {
                $config->php_extensions[$required_ext] = true;
            } else {
                $config->php_extensions_not_supported[$required_ext] = true;
            }
        }

        if (isset($config_xml->enableExtensions) && isset($config_xml->enableExtensions->extension)) {
            foreach ($config_xml->enableExtensions->extension as $extension) {
                assert(isset($extension["name"]));
                $extensionName = (string) $extension["name"];
                assert(array_key_exists($extensionName, $config->php_extensions));
                $config->php_extensions[$extensionName] = true;
            }
        }

        if (isset($config_xml->disableExtensions) && isset($config_xml->disableExtensions->extension)) {
            foreach ($config_xml->disableExtensions->extension as $extension) {
                assert(isset($extension["name"]));
                $extensionName = (string) $extension["name"];
                assert(array_key_exists($extensionName, $config->php_extensions));
                $config->php_extensions[$extensionName] = false;
            }
        }

        if (isset($config_xml['phpVersion'])) {
            $config->configured_php_version = (string) $config_xml['phpVersion'];
        }

        if (isset($config_xml['autoloader'])) {
            $autoloader = (string) $config_xml['autoloader'];
            $autoloader_path = $config->base_dir . DIRECTORY_SEPARATOR . $autoloader;

            if (!file_exists($autoloader_path)) {
                // in here for legacy reasons where people put absolute paths but psalm resolved it relative
                if ($autoloader[0] === '/') {
                    $autoloader_path = $autoloader;
                }

                if (!file_exists($autoloader_path)) {
                    throw new ConfigException('Cannot locate autoloader');
                }
            }

            $config->autoloader = realpath($autoloader_path);
        }

        if (isset($config_xml['cacheDirectory'])) {
            $config->cache_directory = (string)$config_xml['cacheDirectory'];
        } elseif ($user_cache_dir = (new Xdg())->getHomeCacheDir()) {
            $config->cache_directory = $user_cache_dir . '/psalm';
        } else {
            $config->cache_directory = sys_get_temp_dir() . '/psalm';
        }

        $config->global_cache_directory = $config->cache_directory;

        $config->cache_directory .= DIRECTORY_SEPARATOR . sha1($base_dir);

        if (isset($config_xml['serializer'])) {
            $attribute_text = (string) $config_xml['serializer'];
            $config->use_igbinary = $attribute_text === 'igbinary';
            if ($config->use_igbinary
                && (
                    !function_exists('igbinary_serialize')
                    || !function_exists('igbinary_unserialize')
                )
            ) {
                $config->use_igbinary = false;
                $config->config_warnings[] = '"serializer" set to "igbinary" but ext-igbinary seems to be missing on ' .
                    'the system. Using php\'s build-in serializer.';
            }
        } elseif ($igbinary_version = phpversion('igbinary')) {
            $config->use_igbinary = version_compare($igbinary_version, '2.0.5') >= 0;
        }

        if (isset($config_xml['compressor'])) {
            $compressor = (string) $config_xml['compressor'];
            if ($compressor === 'lz4') {
                if (function_exists('lz4_compress') && function_exists('lz4_uncompress')) {
                    $config->compressor = 'lz4';
                } else {
                    $config->config_warnings[] = '"compressor" set to "lz4" but ext-lz4 seems to be missing on the ' .
                        'system. Disabling cache compressor.';
                }
            } elseif ($compressor === 'deflate') {
                if (function_exists('gzinflate') && function_exists('gzdeflate')) {
                    $config->compressor = 'deflate';
                } else {
                    $config->config_warnings[] = '"compressor" set to "deflate" but zlib seems to be missing on the ' .
                        'system. Disabling cache compressor.';
                }
            }
        } elseif (function_exists('gzinflate') && function_exists('gzdeflate')) {
            $config->compressor = 'deflate';
        }

        if (!isset($config_xml['findUnusedBaselineEntry'])) {
            $config->config_warnings[] = '"findUnusedBaselineEntry" will default to "true" in Psalm 6.'
                . ' You should explicitly enable or disable this setting.';
        }

        if (isset($config_xml['findUnusedCode'])) {
            $attribute_text = (string) $config_xml['findUnusedCode'];
            $config->find_unused_code = $attribute_text === 'true' || $attribute_text === '1';
            $config->find_unused_variables = $config->find_unused_code;
        } else {
            $config->config_warnings[] = '"findUnusedCode" will default to "true" in Psalm 6.'
                . ' You should explicitly enable or disable this setting.';
        }

        if (isset($config_xml['findUnusedVariablesAndParams'])) {
            $attribute_text = (string) $config_xml['findUnusedVariablesAndParams'];
            $config->find_unused_variables = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['errorLevel'])) {
            $attribute_text = (int) $config_xml['errorLevel'];

            if (!in_array($attribute_text, [1, 2, 3, 4, 5, 6, 7, 8], true)) {
                throw new ConfigException(
                    'Invalid error level ' . $config_xml['errorLevel'],
                );
            }

            $config->level = $attribute_text;
        } else {
            $config->level = 2;
        }

        // turn on unused variable detection in level 1
        if (!isset($config_xml['findUnusedCode'])
            && !isset($config_xml['findUnusedVariablesAndParams'])
            && $config->level === 1
            && $config->show_mixed_issues !== false
        ) {
            $config->find_unused_variables = true;
        }

        if (isset($config_xml['errorBaseline'])) {
            $attribute_text = (string) $config_xml['errorBaseline'];
            $config->error_baseline = $attribute_text;
        }

        if (isset($config_xml['maxStringLength'])) {
            $attribute_text = (int)$config_xml['maxStringLength'];
            $config->max_string_length = $attribute_text;
        }

        if (isset($config_xml['maxShapedArraySize'])) {
            $attribute_text = (int)$config_xml['maxShapedArraySize'];
            $config->max_shaped_array_size = $attribute_text;
        }

        if (isset($config_xml['inferPropertyTypesFromConstructor'])) {
            $attribute_text = (string) $config_xml['inferPropertyTypesFromConstructor'];
            $config->infer_property_types_from_constructor = $attribute_text === 'true' || $attribute_text === '1';
        }

        if (isset($config_xml['triggerErrorExits'])) {
            $attribute_text = (string) $config_xml['triggerErrorExits'];
            if ($attribute_text === 'always' || $attribute_text === 'never') {
                $config->trigger_error_exits = $attribute_text;
            }
        }

        if (isset($config_xml->projectFiles)) {
            $config->project_files = ProjectFileFilter::loadFromXMLElement($config_xml->projectFiles, $base_dir, true);
        }

        // any paths passed via CLI should be added to the projectFiles
        // as they're getting analyzed like if they are part of the project
        // ProjectAnalyzer::getInstance()->check_paths_files is not populated at this point in time

        $paths_to_check = null;

        global $argv;

        // Hack for Symfonys own argv resolution.
        // @see https://github.com/vimeo/psalm/issues/10465
        if (!isset($argv[0]) || basename($argv[0]) !== 'psalm-plugin') {
            $paths_to_check = CliUtils::getPathsToCheck(null);
        }

        if ($paths_to_check !== null) {
            $paths_to_add_to_project_files = array();
            foreach ($paths_to_check as $path) {
                // if we have an .xml arg here, the files passed are invalid
                // valid cases (in which we don't want to add CLI passed files to projectFiles though)
                // are e.g. if running phpunit tests for psalm itself
                if (substr($path, -4) === '.xml') {
                    $paths_to_add_to_project_files = array();
                    break;
                }

                // we need an absolute path for checks
                if (Path::isRelative($path)) {
                    $prospective_path = $base_dir . DIRECTORY_SEPARATOR . $path;
                } else {
                    $prospective_path = $path;
                }

                // will report an error when config is loaded anyway
                if (!file_exists($prospective_path)) {
                    continue;
                }

                if ($config->isInProjectDirs($prospective_path)) {
                    continue;
                }

                $paths_to_add_to_project_files[] = $prospective_path;
            }

            if ($paths_to_add_to_project_files !== array() && !isset($config_xml->projectFiles)) {
                if ($config_xml === null) {
                    $config_xml = new SimpleXMLElement('<psalm/>');
                }
                $config_xml->addChild('projectFiles');
            }

            if ($paths_to_add_to_project_files !== array() && isset($config_xml->projectFiles)) {
                foreach ($paths_to_add_to_project_files as $path) {
                    if (is_dir($path)) {
                        $child = $config_xml->projectFiles->addChild('directory');
                    } else {
                        $child = $config_xml->projectFiles->addChild('file');
                    }

                    $child->addAttribute('name', $path);
                }

                $config->project_files = ProjectFileFilter::loadFromXMLElement(
                    $config_xml->projectFiles,
                    $base_dir,
                    true,
                );
            }
        }

        if (isset($config_xml->extraFiles)) {
            $config->extra_files = ProjectFileFilter::loadFromXMLElement($config_xml->extraFiles, $base_dir, true);
        }

        if (isset($config_xml->taintAnalysis->ignoreFiles)) {
            $config->taint_analysis_ignored_files = TaintAnalysisFileFilter::loadFromXMLElement(
                $config_xml->taintAnalysis->ignoreFiles,
                $base_dir,
                false,
            );
        }

        if (isset($config_xml->fileExtensions->extension)) {
            $config->file_extensions = [];

            $config->loadFileExtensions($config_xml->fileExtensions->extension);
        }

        if (isset($config_xml->mockClasses) && isset($config_xml->mockClasses->class)) {
            /** @var SimpleXMLElement $mock_class */
            foreach ($config_xml->mockClasses->class as $mock_class) {
                $config->mock_classes[] = strtolower((string)$mock_class['name']);
            }
        }

        if (isset($config_xml->universalObjectCrates) && isset($config_xml->universalObjectCrates->class)) {
            /** @var SimpleXMLElement $universal_object_crate */
            foreach ($config_xml->universalObjectCrates->class as $universal_object_crate) {
                /** @var string $classString */
                $classString = $universal_object_crate['name'];
                $config->addUniversalObjectCrate($classString);
            }
        }

        if (isset($config_xml->ignoreExceptions)) {
            if (isset($config_xml->ignoreExceptions->class)) {
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
            /** @var SimpleXMLElement $forbidden_function */
            foreach ($config_xml->forbiddenFunctions->function as $forbidden_function) {
                $config->forbidden_functions[strtolower((string) $forbidden_function['name'])] = true;
            }
        }

        if (isset($config_xml->stubs) && isset($config_xml->stubs->file)) {
            /** @var SimpleXMLElement $stub_file */
            foreach ($config_xml->stubs->file as $stub_file) {
                $stub_file_name = (string)$stub_file['name'];
                if (!Path::isAbsolute($stub_file_name)) {
                    $stub_file_name = $config->base_dir . DIRECTORY_SEPARATOR . $stub_file_name;
                }
                $file_path = realpath($stub_file_name);

                if (!$file_path) {
                    throw new ConfigException(
                        'Cannot resolve stubfile path '
                            . $config->base_dir
                            . DIRECTORY_SEPARATOR
                            . $stub_file['name'],
                    );
                }

                if (isset($stub_file['preloadClasses'])) {
                    $preload_classes = (string)$stub_file['preloadClasses'];

                    if ($preload_classes === 'true' || $preload_classes === '1') {
                        $config->addPreloadedStubFile($file_path);
                    } else {
                        $config->addStubFile($file_path);
                    }
                } else {
                    $config->addStubFile($file_path);
                }
            }
        }

        // this plugin loading system borrows heavily from etsy/phan
        if (isset($config_xml->plugins)) {
            if (isset($config_xml->plugins->plugin)) {
                foreach ($config_xml->plugins->plugin as $plugin) {
                    $plugin_file_name = (string) $plugin['filename'];

                    $path = Path::isAbsolute($plugin_file_name)
                        ? $plugin_file_name
                        : $config->base_dir . DIRECTORY_SEPARATOR . $plugin_file_name;

                    $config->addPluginPath($path);
                }
            }
            if (isset($config_xml->plugins->pluginClass)) {
                foreach ($config_xml->plugins->pluginClass as $plugin) {
                    $plugin_class_name = $plugin['class'];
                    // any child elements are used as plugin configuration
                    $plugin_config = null;
                    if ($plugin->count()) {
                        $plugin_config = $plugin->children();
                    }

                    $config->addPluginClass((string) $plugin_class_name, $plugin_config);
                }
            }
        }

        if (isset($config_xml->issueHandlers)) {
            foreach ($config_xml->issueHandlers as $issue_handlers) {
                $issue_handler_children = $issue_handlers->children();
                if ($issue_handler_children) {
                    foreach ($issue_handler_children as $key => $issue_handler) {
                        if ($key === 'PluginIssue') {
                            $custom_class_name = (string)$issue_handler['name'];
                            /** @var string $key */
                            $config->issue_handlers[$custom_class_name] = IssueHandler::loadFromXMLElement(
                                $issue_handler,
                                $base_dir,
                            );
                        } else {
                            /** @var string $key */
                            $config->issue_handlers[$key] = IssueHandler::loadFromXMLElement(
                                $issue_handler,
                                $base_dir,
                            );
                        }
                    }
                }
            }
        }

        if (isset($config_xml->globals) && isset($config_xml->globals->var)) {
            /** @var SimpleXMLElement $var */
            foreach ($config_xml->globals->var as $var) {
                $config->globals['$' . (string) $var['name']] = (string) $var['type'];
            }
        }

        if (isset($config_xml['threads'])) {
            $config->threads = (int)$config_xml['threads'];
        }

        return $config;
    }

    public static function getInstance(): Config
    {
        if (self::$instance) {
            return self::$instance;
        }

        throw new UnexpectedValueException('No config initialized');
    }

    public function setComposerClassLoader(?ClassLoader $loader = null): void
    {
        $this->composer_class_loader = $loader;
    }

    public function setAdvancedErrorLevel(string $issue_key, array $config, ?string $default_error_level = null): void
    {
        $this->issue_handlers[$issue_key] = new IssueHandler();
        if ($default_error_level !== null) {
            $this->issue_handlers[$issue_key]->setErrorLevel($default_error_level);
        }
        $this->issue_handlers[$issue_key]->setCustomLevels($config, $this->base_dir);
    }

    public function safeSetAdvancedErrorLevel(
        string $issue_key,
        array $config,
        ?string $default_error_level = null
    ): void {
        if (!isset($this->issue_handlers[$issue_key])) {
            $this->setAdvancedErrorLevel($issue_key, $config, $default_error_level);
        }
    }

    public function setCustomErrorLevel(string $issue_key, string $error_level): void
    {
        $this->issue_handlers[$issue_key] = new IssueHandler();
        $this->issue_handlers[$issue_key]->setErrorLevel($error_level);
    }

    public function safeSetCustomErrorLevel(string $issue_key, string $error_level): void
    {
        if (!isset($this->issue_handlers[$issue_key])) {
            $this->setCustomErrorLevel($issue_key, $error_level);
        }
    }

    /**
     * @throws ConfigException if a Config file could not be found
     */
    private function loadFileExtensions(SimpleXMLElement $extensions): void
    {
        foreach ($extensions as $extension) {
            $extension_name = preg_replace('/^\.?/', '', (string) $extension['name'], 1);
            $this->file_extensions[] = $extension_name;

            if (isset($extension['scanner'])) {
                $path = $this->base_dir . DIRECTORY_SEPARATOR . (string) $extension['scanner'];

                if (!file_exists($path)) {
                    throw new ConfigException('Error parsing config: cannot find file ' . $path);
                }

                $this->filetype_scanner_paths[$extension_name] = $path;
            }

            if (isset($extension['checker'])) {
                $path = $this->base_dir . DIRECTORY_SEPARATOR . (string) $extension['checker'];

                if (!file_exists($path)) {
                    throw new ConfigException('Error parsing config: cannot find file ' . $path);
                }

                $this->filetype_analyzer_paths[$extension_name] = $path;
            }
        }
    }

    public function addPluginPath(string $path): void
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException('Cannot find plugin file ' . $path);
        }

        $this->plugin_paths[] = $path;
    }

    public function addPluginClass(string $class_name, ?SimpleXMLElement $plugin_config = null): void
    {
        $this->plugin_classes[] = ['class' => $class_name, 'config' => $plugin_config];
    }

    /** @return array<array{class:string, config:?SimpleXMLElement}> */
    public function getPluginClasses(): array
    {
        return $this->plugin_classes;
    }

    public function processPluginFileExtensions(ProjectAnalyzer $projectAnalyzer): void
    {
        $projectAnalyzer->progress->debug('Process plugin adjustments...' . PHP_EOL);
        $socket = new PluginFileExtensionsSocket($this);
        foreach ($this->plugin_classes as $pluginClassEntry) {
            $pluginClassName = $pluginClassEntry['class'];
            $pluginConfig = $pluginClassEntry['config'];
            $plugin = $this->loadPlugin($projectAnalyzer, $pluginClassName);
            if (!$plugin instanceof PluginFileExtensionsInterface) {
                continue;
            }
            try {
                $plugin->processFileExtensions($socket, $pluginConfig);
            } catch (Throwable $t) {
                throw new ConfigException(
                    'Failed to process plugin file extensions ' . $pluginClassName,
                    1_635_800_581,
                    $t,
                );
            }
            $projectAnalyzer->progress->debug('Initialized plugin ' . $pluginClassName . ' successfully' . PHP_EOL);
        }
        // populate additional aspects after plugins have been initialized
        foreach ($socket->getAdditionalFileExtensions() as $fileExtension) {
            $this->file_extensions[] = $fileExtension;
        }
        foreach ($socket->getAdditionalFileTypeScanners() as $extension => $className) {
            $this->filetype_scanners[$extension] = $className;
        }
        foreach ($socket->getAdditionalFileTypeAnalyzers() as $extension => $className) {
            $this->filetype_analyzers[$extension] = $className;
        }
    }

    /**
     * Initialises all the plugins (done once the config is fully loaded)
     */
    public function initializePlugins(ProjectAnalyzer $project_analyzer): void
    {
        $codebase = $project_analyzer->getCodebase();

        $project_analyzer->progress->debug('Initializing plugins...' . PHP_EOL);

        $socket = new PluginRegistrationSocket($this, $codebase);
        // initialize plugin classes earlier to let them hook into subsequent load process
        foreach ($this->plugin_classes as $plugin_class_entry) {
            $plugin_class_name = $plugin_class_entry['class'];
            $plugin_config = $plugin_class_entry['config'];

            $plugin = $this->loadPlugin($project_analyzer, $plugin_class_name);
            if (!$plugin instanceof PluginEntryPointInterface) {
                continue;
            }

            try {
                $plugin($socket, $plugin_config);
            } catch (Throwable $t) {
                throw new ConfigException(
                    'Failed to invoke plugin ' . $plugin_class_name,
                    1_635_800_582,
                    $t,
                );
            }

            $project_analyzer->progress->debug('Initialized plugin ' . $plugin_class_name . ' successfully' . PHP_EOL);
        }

        foreach ($this->filetype_scanner_paths as $extension => $path) {
            $fq_class_name = $this->getPluginClassForPath(
                $codebase,
                $path,
                FileScanner::class,
            );

            self::requirePath($path);

            $this->filetype_scanners[$extension] = $fq_class_name;
        }

        foreach ($this->filetype_analyzer_paths as $extension => $path) {
            $fq_class_name = $this->getPluginClassForPath(
                $codebase,
                $path,
                FileAnalyzer::class,
            );

            self::requirePath($path);

            $this->filetype_analyzers[$extension] = $fq_class_name;
        }

        foreach ($this->plugin_paths as $path) {
            try {
                $plugin = new FileBasedPluginAdapter($path, $this, $codebase);
                $plugin($socket);
            } catch (Throwable $e) {
                throw new ConfigException('Failed to load plugin ' . $path, 0, $e);
            }
        }

        new HtmlFunctionTainter();

        $socket->registerHooksFromClass(HtmlFunctionTainter::class);
    }

    private function loadPlugin(ProjectAnalyzer $projectAnalyzer, string $pluginClassName): PluginInterface
    {
        if (isset($this->plugins[$pluginClassName])) {
            return $this->plugins[$pluginClassName];
        }
        try {
            // Below will attempt to load plugins from the project directory first.
            // Failing that, it will use registered autoload chain, which will load
            // plugins from Psalm directory or phar file. If that fails as well, it
            // will fall back to project autoloader. It may seem that the last step
            // will always fail, but it's only true if project uses Composer autoloader
            if ($this->composer_class_loader
                && ($pluginclas_class_path = $this->composer_class_loader->findFile($pluginClassName))
            ) {
                $projectAnalyzer->progress->debug(
                    'Loading plugin ' . $pluginClassName . ' via require' . PHP_EOL,
                );

                self::requirePath($pluginclas_class_path);
            } else {
                if (!class_exists($pluginClassName)) {
                    throw new UnexpectedValueException($pluginClassName . ' is not a known class');
                }
            }
            if (!is_a($pluginClassName, PluginInterface::class, true)) {
                throw new UnexpectedValueException($pluginClassName . ' is not a PluginInterface implementation');
            }
            $this->plugins[$pluginClassName] = new $pluginClassName;
            $projectAnalyzer->progress->debug('Loaded plugin ' . $pluginClassName . PHP_EOL);
            return $this->plugins[$pluginClassName];
        } catch (Throwable $e) {
            throw new ConfigException('Failed to load plugin ' . $pluginClassName, 0, $e);
        }
    }

    private static function requirePath(string $path): void
    {
        /** @psalm-suppress UnresolvableInclude */
        require_once($path);
    }

    /**
     * @template T
     * @param  T::class $must_extend
     * @return class-string<T>
     */
    private function getPluginClassForPath(Codebase $codebase, string $path, string $must_extend): string
    {
        $file_storage = $codebase->createFileStorageForPath($path);
        $file_to_scan = new FileScanner($path, $this->shortenFileName($path), true);
        $file_to_scan->scan(
            $codebase,
            $file_storage,
        );

        $declared_classes = ClassLikeAnalyzer::getClassesForFile($codebase, $path);

        if (!count($declared_classes)) {
            throw new InvalidArgumentException(
                'Plugins must have at least one class in the file - ' . $path . ' has ' .
                    count($declared_classes),
            );
        }

        $fq_class_name = reset($declared_classes);

        if (!$codebase->classlikes->classExtends(
            $fq_class_name,
            $must_extend,
        )
        ) {
            throw new InvalidArgumentException(
                'This plugin must extend ' . $must_extend . ' - ' . $path . ' does not',
            );
        }

        /**
         * @var class-string<T>
         */
        return $fq_class_name;
    }

    public function shortenFileName(string $to): string
    {
        if (!is_file($to)) {
            // if cwd is the root directory it will be just the directory separator - trim it off first
            return preg_replace(
                '/^' . preg_quote(rtrim($this->base_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, '/') . '/',
                '',
                $to,
                1,
            );
        }

        $from = $this->base_dir;

        // some compatibility fixes for Windows paths
        $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
        $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
        $from = str_replace('\\', '/', $from);
        $to   = str_replace('\\', '/', $to);

        $from     = explode('/', $from);
        $to       = explode('/', $to);
        $relPath  = $to;

        foreach ($from as $depth => $dir) {
            // find first non-matching dir
            if ($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                }
            }
        }

        return implode('/', $relPath);
    }

    public function reportIssueInFile(string $issue_type, string $file_path): bool
    {
        if ((($this->level < 3 && $this->show_mixed_issues === false)
            || ($this->level > 2 && $this->show_mixed_issues !== true))
            && in_array($issue_type, self::MIXED_ISSUES, true)
        ) {
            return false;
        }

        if ($this->mustBeIgnored($file_path)) {
            return false;
        }

        $dependent_files = [strtolower($file_path) => $file_path];

        $project_analyzer = ProjectAnalyzer::getInstance();

        // if the option is set and at least one file is passed via CLI
        if ($this->hide_all_errors_except_passed_files
            && $project_analyzer->check_paths_files
            && !in_array($file_path, $project_analyzer->check_paths_files, true)) {
            return false;
        }

        $codebase = $project_analyzer->getCodebase();

        if (!$this->hide_external_errors) {
            try {
                $file_storage = $codebase->file_storage_provider->get($file_path);
                $dependent_files += $file_storage->required_by_file_paths;
            } catch (InvalidArgumentException $e) {
                // do nothing
            }
        }

        $any_file_path_matched = false;

        foreach ($dependent_files as $dependent_file_path) {
            if (((!$project_analyzer->full_run && $codebase->analyzer->canReportIssues($dependent_file_path))
                    || $project_analyzer->canReportIssues($dependent_file_path))
                && ($file_path === $dependent_file_path || !$this->mustBeIgnored($dependent_file_path))
            ) {
                $any_file_path_matched = true;
                break;
            }
        }

        if (!$any_file_path_matched) {
            return false;
        }

        if ($this->getReportingLevelForFile($issue_type, $file_path) === self::REPORT_SUPPRESS) {
            return false;
        }

        return true;
    }

    public function isInProjectDirs(string $file_path): bool
    {
        return $this->project_files && $this->project_files->allows($file_path);
    }

    public function isInExtraDirs(string $file_path): bool
    {
        return $this->extra_files && $this->extra_files->allows($file_path);
    }

    public function mustBeIgnored(string $file_path): bool
    {
        return $this->project_files && $this->project_files->forbids($file_path);
    }

    public function trackTaintsInPath(string $file_path): bool
    {
        return !$this->taint_analysis_ignored_files
            || $this->taint_analysis_ignored_files->allows($file_path);
    }

    public function getReportingLevelForIssue(CodeIssue $e): string
    {
        $fqcn_parts = explode('\\', get_class($e));
        $issue_type = array_pop($fqcn_parts);

        $reporting_level = null;

        if ($e instanceof ClassIssue) {
            $reporting_level = $this->getReportingLevelForClass($issue_type, $e->fq_classlike_name);
        } elseif ($e instanceof MethodIssue) {
            $reporting_level = $this->getReportingLevelForMethod($issue_type, $e->method_id);
        } elseif ($e instanceof FunctionIssue) {
            $reporting_level = $this->getReportingLevelForFunction($issue_type, $e->function_id);
        } elseif ($e instanceof PropertyIssue) {
            $reporting_level = $this->getReportingLevelForProperty($issue_type, $e->property_id);
        } elseif ($e instanceof ClassConstantIssue) {
            $reporting_level = $this->getReportingLevelForClassConstant($issue_type, $e->const_id);
        } elseif ($e instanceof ArgumentIssue && $e->function_id) {
            $reporting_level = $this->getReportingLevelForArgument($issue_type, $e->function_id);
        } elseif ($e instanceof VariableIssue) {
            $reporting_level = $this->getReportingLevelForVariable($issue_type, $e->var_name);
        }

        if ($reporting_level === null) {
            $reporting_level = $this->getReportingLevelForFile($issue_type, $e->getFilePath());
        }

        if (!$this->report_info && $reporting_level === self::REPORT_INFO) {
            $reporting_level = self::REPORT_SUPPRESS;
        }

        $parent_issue_type = self::getParentIssueType($issue_type);

        if ($parent_issue_type && $reporting_level === self::REPORT_ERROR) {
            $parent_reporting_level = $this->getReportingLevelForFile($parent_issue_type, $e->getFilePath());

            if ($parent_reporting_level !== $reporting_level) {
                return $parent_reporting_level;
            }
        }

        return $reporting_level;
    }

    /**
     * @psalm-pure
     */
    public static function getParentIssueType(string $issue_type): ?string
    {
        if ($issue_type === 'PossiblyUndefinedIntArrayOffset'
            || $issue_type === 'PossiblyUndefinedStringArrayOffset'
        ) {
            return 'PossiblyUndefinedArrayOffset';
        }

        if ($issue_type === 'PossiblyNullReference') {
            return 'NullReference';
        }

        if ($issue_type === 'PossiblyFalseReference') {
            return null;
        }

        if ($issue_type === 'PossiblyUndefinedArrayOffset') {
            return null;
        }

        if (strpos($issue_type, 'Possibly') === 0) {
            $stripped_issue_type = preg_replace('/^Possibly(False|Null)?/', '', $issue_type, 1);

            if (strpos($stripped_issue_type, 'Invalid') === false && strpos($stripped_issue_type, 'Un') !== 0) {
                $stripped_issue_type = 'Invalid' . $stripped_issue_type;
            }

            return $stripped_issue_type;
        }

        if (strpos($issue_type, 'Tainted') === 0) {
            return 'TaintedInput';
        }

        if (preg_match('/^(False|Null)[A-Z]/', $issue_type) && !strpos($issue_type, 'Reference')) {
            return preg_replace('/^(False|Null)/', 'Invalid', $issue_type, 1);
        }

        if ($issue_type === 'UndefinedInterfaceMethod') {
            return 'UndefinedMethod';
        }

        if ($issue_type === 'UndefinedMagicPropertyFetch') {
            return 'UndefinedPropertyFetch';
        }

        if ($issue_type === 'UndefinedMagicPropertyAssignment') {
            return 'UndefinedPropertyAssignment';
        }

        if ($issue_type === 'UndefinedMagicMethod') {
            return 'UndefinedMethod';
        }

        if ($issue_type === 'PossibleRawObjectIteration') {
            return 'RawObjectIteration';
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

        if ($issue_type === 'UnusedConstructor') {
            return 'UnusedMethod';
        }

        if ($issue_type === 'StringIncrement') {
            return 'InvalidOperand';
        }

        if ($issue_type === 'InvalidLiteralArgument') {
            return 'InvalidArgument';
        }

        if ($issue_type === 'RedundantConditionGivenDocblockType') {
            return 'RedundantCondition';
        }

        if ($issue_type === 'RedundantFunctionCallGivenDocblockType') {
            return 'RedundantFunctionCall';
        }

        if ($issue_type === 'RedundantCastGivenDocblockType') {
            return 'RedundantCast';
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

        if ($issue_type === 'UnusedForeachValue') {
            return 'UnusedVariable';
        }

        return null;
    }

    public function getReportingLevelForFile(string $issue_type, string $file_path): string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForFile($file_path);
        }

        // this string is replaced by scoper for Phars, so be careful
        $issue_class = 'Psalm\\Issue\\' . $issue_type;

        if (!class_exists($issue_class) || !is_a($issue_class, CodeIssue::class, true)) {
            return self::REPORT_ERROR;
        }

        /** @var int */
        $issue_level = $issue_class::ERROR_LEVEL;

        if ($issue_level > 0 && $issue_level < $this->level) {
            return self::REPORT_INFO;
        }

        return self::REPORT_ERROR;
    }

    public function getReportingLevelForClass(string $issue_type, string $fq_classlike_name): ?string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForClass($fq_classlike_name);
        }

        return null;
    }

    public function getReportingLevelForMethod(string $issue_type, string $method_id): ?string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForMethod($method_id);
        }

        return null;
    }

    public function getReportingLevelForFunction(string $issue_type, string $function_id): ?string
    {
        $level = null;
        if (isset($this->issue_handlers[$issue_type])) {
            $level = $this->issue_handlers[$issue_type]->getReportingLevelForFunction($function_id);

            if ($level === null && $issue_type === 'UndefinedFunction') {
                // undefined functions trigger global namespace fallback
                // so we should also check reporting levels for the symbol in global scope
                $root_function_id = preg_replace('/.*\\\/', '', $function_id);
                if ($root_function_id !== $function_id) {
                    /** @psalm-suppress PossiblyUndefinedStringArrayOffset https://github.com/vimeo/psalm/issues/7656 */
                    $level = $this->issue_handlers[$issue_type]->getReportingLevelForFunction($root_function_id);
                }
            }
        }

        return $level;
    }

    public function getReportingLevelForArgument(string $issue_type, string $function_id): ?string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForArgument($function_id);
        }

        return null;
    }

    public function getReportingLevelForProperty(string $issue_type, string $property_id): ?string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForProperty($property_id);
        }

        return null;
    }

    public function getReportingLevelForClassConstant(string $issue_type, string $constant_id): ?string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForClassConstant($constant_id);
        }

        return null;
    }

    public function getReportingLevelForVariable(string $issue_type, string $var_name): ?string
    {
        if (isset($this->issue_handlers[$issue_type])) {
            return $this->issue_handlers[$issue_type]->getReportingLevelForVariable($var_name);
        }

        return null;
    }

    /**
     * @return array<string>
     */
    public function getProjectDirectories(): array
    {
        if (!$this->project_files) {
            return [];
        }

        return $this->project_files->getDirectories();
    }

    /**
     * @return array<string>
     */
    public function getProjectFiles(): array
    {
        if (!$this->project_files) {
            return [];
        }

        return $this->project_files->getFiles();
    }

    /**
     * @return array<string>
     */
    public function getExtraDirectories(): array
    {
        if (!$this->extra_files) {
            return [];
        }

        return $this->extra_files->getDirectories();
    }

    public function reportTypeStatsForFile(string $file_path): bool
    {
        return $this->project_files
            && $this->project_files->allows($file_path)
            && $this->project_files->reportTypeStats($file_path);
    }

    public function useStrictTypesForFile(string $file_path): bool
    {
        return $this->project_files && $this->project_files->useStrictTypes($file_path);
    }

    /**
     * @return array<int, string>
     */
    public function getFileExtensions(): array
    {
        return $this->file_extensions;
    }

    /**
     * @return array<string, class-string<FileScanner>>
     */
    public function getFiletypeScanners(): array
    {
        return $this->filetype_scanners;
    }

    /**
     * @return array<string, class-string<FileAnalyzer>>
     */
    public function getFiletypeAnalyzers(): array
    {
        return $this->filetype_analyzers;
    }

    /**
     * @return array<int, string>
     */
    public function getMockClasses(): array
    {
        return $this->mock_classes;
    }

    public function visitPreloadedStubFiles(Codebase $codebase, ?Progress $progress = null): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $core_generic_files = [];

        if (PHP_VERSION_ID < 8_00_00 && $codebase->analysis_php_version_id >= 8_00_00) {
            $stringable_path = dirname(__DIR__, 2) . '/stubs/Php80.phpstub';

            if (!file_exists($stringable_path)) {
                throw new UnexpectedValueException('Cannot locate PHP 8.0 classes');
            }

            $core_generic_files[] = $stringable_path;
        }

        if (PHP_VERSION_ID < 8_01_00 && $codebase->analysis_php_version_id >= 8_01_00) {
            $stringable_path = dirname(__DIR__, 2) . '/stubs/Php81.phpstub';

            if (!file_exists($stringable_path)) {
                throw new UnexpectedValueException('Cannot locate PHP 8.1 classes');
            }

            $core_generic_files[] = $stringable_path;
        }

        if (PHP_VERSION_ID < 8_02_00 && $codebase->analysis_php_version_id >= 8_02_00) {
            $stringable_path = dirname(__DIR__, 2) . '/stubs/Php82.phpstub';

            if (!file_exists($stringable_path)) {
                throw new UnexpectedValueException('Cannot locate PHP 8.2 classes');
            }

            $core_generic_files[] = $stringable_path;
        }

        $stub_files = array_merge($core_generic_files, $this->preloaded_stub_files);

        if (!$stub_files) {
            return;
        }

        foreach ($stub_files as $file_path) {
            $file_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file_path);
            // fix mangled phar paths on Windows
            if (strpos($file_path, 'phar:\\\\') === 0) {
                $file_path = 'phar://'. substr($file_path, 7);
            }
            $codebase->scanner->addFileToDeepScan($file_path);
        }

        $progress->debug('Registering preloaded stub files' . "\n");

        $codebase->register_stub_files = true;

        $codebase->scanFiles();

        $codebase->register_stub_files = false;

        $progress->debug('Finished registering preloaded stub files' . "\n");
    }

    public function visitStubFiles(Codebase $codebase, ?Progress $progress = null): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $codebase->register_stub_files = true;

        $dir_lvl_2 = dirname(__DIR__, 2);
        $stubsDir = $dir_lvl_2 . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR;
        $this->internal_stubs = [
            $stubsDir . 'CoreGenericFunctions.phpstub',
            $stubsDir . 'CoreGenericClasses.phpstub',
            $stubsDir . 'CoreGenericIterators.phpstub',
            $stubsDir . 'CoreImmutableClasses.phpstub',
            $stubsDir . 'Reflection.phpstub',
            $stubsDir . 'SPL.phpstub',
        ];

        if ($codebase->analysis_php_version_id >= 7_04_00) {
            $this->internal_stubs[] = $stubsDir . 'Php74.phpstub';
        }

        if ($codebase->analysis_php_version_id >= 8_00_00) {
            $this->internal_stubs[] = $stubsDir . 'CoreGenericAttributes.phpstub';
            $this->internal_stubs[] = $stubsDir . 'Php80.phpstub';
        }

        if ($codebase->analysis_php_version_id >= 8_01_00) {
            $this->internal_stubs[] = $stubsDir . 'Php81.phpstub';
        }

        if ($codebase->analysis_php_version_id >= 8_02_00) {
            $this->internal_stubs[] = $stubsDir . 'Php82.phpstub';
            $this->php_extensions['random'] = true; // random is a part of the PHP core starting from PHP 8.2
        }

        $ext_stubs_dir = $dir_lvl_2 . DIRECTORY_SEPARATOR . "stubs" . DIRECTORY_SEPARATOR . "extensions";
        foreach ($this->php_extensions as $ext => $enabled) {
            if ($enabled) {
                $this->internal_stubs[] = $ext_stubs_dir . DIRECTORY_SEPARATOR . "$ext.phpstub";
            }
        }

        /** @deprecated Will be removed in Psalm 6 */
        $extensions_to_load_stubs_using_deprecated_way = ['apcu', 'random', 'redis'];
        foreach ($extensions_to_load_stubs_using_deprecated_way as $ext_name) {
            $ext_stub_path = $ext_stubs_dir . DIRECTORY_SEPARATOR . "$ext_name.phpstub";
            $is_stub_already_loaded = in_array($ext_stub_path, $this->internal_stubs, true);
            $is_ext_explicitly_disabled = ($this->php_extensions[$ext_name] ?? null) === false;
            if (! $is_stub_already_loaded && ! $is_ext_explicitly_disabled && extension_loaded($ext_name)) {
                $this->internal_stubs[] = $ext_stub_path;
                $this->config_warnings[] = "Psalm 6 will not automatically load stubs for ext-$ext_name."
                    . " You should explicitly enable or disable this ext in composer.json or Psalm config.";
            }
        }

        foreach ($this->internal_stubs as $stub_path) {
            if (!file_exists($stub_path)) {
                throw new UnexpectedValueException('Cannot locate ' . $stub_path);
            }
        }

        $stub_files = array_merge($this->internal_stubs, $this->stub_files);

        $phpstorm_meta_path = $this->base_dir . DIRECTORY_SEPARATOR . '.phpstorm.meta.php';

        if ($this->use_phpstorm_meta_path) {
            if (is_file($phpstorm_meta_path)) {
                $stub_files[] = $phpstorm_meta_path;
            } elseif (is_dir($phpstorm_meta_path)) {
                $phpstorm_meta_path = realpath($phpstorm_meta_path);

                foreach (glob($phpstorm_meta_path . '/*.meta.php', GLOB_NOSORT) as $glob) {
                    if (is_file($glob) && realpath(dirname($glob)) === $phpstorm_meta_path) {
                        $stub_files[] = $glob;
                    }
                }
            }
        }

        foreach ($stub_files as $file_path) {
            $file_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file_path);
            // fix mangled phar paths on Windows
            if (strpos($file_path, 'phar:\\\\') === 0) {
                $file_path = 'phar://' . substr($file_path, 7);
            }
            $codebase->scanner->addFileToDeepScan($file_path);
        }

        $progress->debug('Registering stub files' . "\n");

        $codebase->scanFiles();

        $progress->debug('Finished registering stub files' . "\n");

        $codebase->register_stub_files = false;
    }

    public function getCacheDirectory(): ?string
    {
        if ($this->cache_directory === null) {
            return null;
        }

        if ($this->cache_directory_initialized) {
            return $this->cache_directory;
        }

        $cwd = null;

        if ($this->resolve_from_config_file) {
            $cwd = getcwd();
            chdir($this->base_dir);
        }

        try {
            if (!is_dir($this->cache_directory)) {
                try {
                    if (mkdir($this->cache_directory, 0777, true) === false) {
                        // any other error than directory already exists/permissions issue
                        throw new RuntimeException('Failed to create Psalm cache directory for unknown reasons');
                    }
                } catch (RuntimeException $e) {
                    if (!is_dir($this->cache_directory)) {
                        // rethrow the error with default message
                        // it contains the reason why creation failed
                        throw $e;
                    }
                }
            }
        } finally {
            if ($cwd) {
                chdir($cwd);
            }
        }

        $this->cache_directory_initialized = true;

        return $this->cache_directory;
    }

    public function getGlobalCacheDirectory(): ?string
    {
        return $this->global_cache_directory;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPredefinedConstants(): array
    {
        return $this->predefined_constants;
    }

    public function collectPredefinedConstants(): void
    {
        $this->predefined_constants = get_defined_constants();
    }

    /**
     * @return array<callable-string, bool>
     */
    public function getPredefinedFunctions(): array
    {
        return $this->predefined_functions;
    }

    public function collectPredefinedFunctions(): void
    {
        $defined_functions = get_defined_functions();
        foreach ($defined_functions['user'] as $function_name) {
            $this->predefined_functions[$function_name] = true;
        }
        foreach ($defined_functions['internal'] as $function_name) {
            $this->predefined_functions[$function_name] = true;
        }
    }

    public function setIncludeCollector(IncludeCollector $include_collector): void
    {
        $this->include_collector = $include_collector;
    }

    public function visitComposerAutoloadFiles(ProjectAnalyzer $project_analyzer, ?Progress $progress = null): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        if (!$this->include_collector) {
            throw new LogicException("IncludeCollector should be set at this point");
        }

        $vendor_autoload_files_path
            = $this->base_dir . DIRECTORY_SEPARATOR . 'vendor'
                . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_files.php';

        if (file_exists($vendor_autoload_files_path)) {
            $this->include_collector->runAndCollect(
                static fn(): array =>
                    /**
                     * @psalm-suppress UnresolvableInclude
                     * @var string[]
                     */
                    require $vendor_autoload_files_path,
            );
        }

        $codebase = $project_analyzer->getCodebase();

        $this->collectPredefinedFunctions();

        if ($this->autoloader) {
            // somee classes that we think are missing may not actually be missing
            // as they might be autoloadable once we require the autoloader below
            $codebase->classlikes->forgetMissingClassLikes();

            $this->include_collector->runAndCollect(
                [$this, 'requireAutoloader'],
            );
        }

        $this->collectPredefinedConstants();

        $autoload_included_files = $this->include_collector->getFilteredIncludedFiles();

        if ($autoload_included_files) {
            $codebase->register_autoload_files = true;

            $progress->debug('Registering autoloaded files' . "\n");
            foreach ($autoload_included_files as $file_path) {
                $file_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file_path);
                $progress->debug('   ' . $file_path . "\n");
                $codebase->scanner->addFileToDeepScan($file_path);
            }

            $codebase->scanner->scanFiles($codebase->classlikes);

            $progress->debug('Finished registering autoloaded files' . "\n");

            $codebase->register_autoload_files = false;
        }
    }

    /**
     * @return string|false
     */
    public function getComposerFilePathForClassLike(string $fq_classlike_name)
    {
        if (!$this->composer_class_loader) {
            return false;
        }

        return $this->composer_class_loader->findFile($fq_classlike_name);
    }

    public function getPotentialComposerFilePathForClassLike(string $class): ?string
    {
        if (!$this->composer_class_loader) {
            return null;
        }

        $psr4_prefixes = $this->composer_class_loader->getPrefixesPsr4();

        // PSR-4 lookup
        $logicalPathPsr4 = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

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

    public static function removeCacheDirectory(string $dir): void
    {
        clearstatcache(true, $dir);
        if (is_dir($dir)) {
            $objects = scandir($dir, SCANDIR_SORT_NONE);

            if ($objects === false) {
                throw new UnexpectedValueException('Not expecting false here');
            }

            foreach ($objects as $object) {
                if ($object === '.' || $object === '..') {
                    continue;
                }

                $full_path = $dir . '/' . $object;

                // if it was deleted in the meantime/race condition with other psalm process
                clearstatcache(true, $full_path);
                if (!file_exists($full_path)) {
                    continue;
                }

                if (is_dir($full_path)) {
                    self::removeCacheDirectory($full_path);
                } else {
                    $fp = fopen($full_path, 'c');
                    if ($fp === false) {
                        continue;
                    }

                    $max_wait_cycles = 5;
                    $has_lock = false;
                    while ($max_wait_cycles > 0) {
                        if (flock($fp, LOCK_EX)) {
                            $has_lock = true;
                            break;
                        }
                        $max_wait_cycles--;
                        usleep(50_000);
                    }

                    try {
                        if (!$has_lock) {
                            throw new RuntimeException('Could not acquire lock for deletion of ' . $full_path);
                        }

                        unlink($full_path);
                        fclose($fp);
                    } catch (RuntimeException $e) {
                        if (is_resource($fp)) {
                            fclose($fp);
                        }
                        clearstatcache(true, $full_path);
                        if (file_exists($full_path)) {
                            // rethrow the error with default message
                            // it contains the reason why deletion failed
                            throw $e;
                        }
                    }
                }
            }

            // may have been removed in the meantime
            clearstatcache(true, $dir);
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

    public function setServerMode(): void
    {
        if ($this->cache_directory !== null) {
            $this->cache_directory .= '-s';
        }
    }

    public function addStubFile(string $stub_file): void
    {
        $this->stub_files[$stub_file] = $stub_file;
    }

    public function hasStubFile(string $stub_file): bool
    {
        return isset($this->stub_files[$stub_file]);
    }

    /**
     * @return array<string, string>
     */
    public function getStubFiles(): array
    {
        return $this->stub_files;
    }

    public function addPreloadedStubFile(string $stub_file): void
    {
        $this->preloaded_stub_files[$stub_file] = $stub_file;
    }

    public function getPhpVersion(): ?string
    {
        return $this->getPhpVersionFromConfig() ?? $this->getPHPVersionFromComposerJson();
    }

    public function getPhpVersionFromConfig(): ?string
    {
        return $this->configured_php_version;
    }

    private function setBooleanAttribute(string $name, bool $value): void
    {
        $this->$name = $value;
    }

    /**
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAccess
     */
    public function getPHPVersionFromComposerJson(): ?string
    {
        $composer_json_path = Composer::getJsonFilePath($this->base_dir);

        if (file_exists($composer_json_path)) {
            try {
                $composer_json = json_decode(file_get_contents($composer_json_path), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $composer_json = null;
            }

            if (!$composer_json) {
                throw new UnexpectedValueException('Invalid composer.json at ' . $composer_json_path);
            }
            $php_version = $composer_json['require']['php'] ?? null;

            if (is_string($php_version)) {
                $version_parser = new VersionParser();

                $constraint = $version_parser->parseConstraints($php_version);
                $php_versions = [
                    '5.4',
                    '5.5',
                    '5.6',
                    '7.0',
                    '7.1',
                    '7.2',
                    '7.3',
                    '7.4',
                    '8.0',
                    '8.1',
                    '8.2',
                    '8.3',
                ];

                foreach ($php_versions as $candidate) {
                    if ($constraint->matches(new Constraint('<=', "$candidate.0.0-dev"))
                        || $constraint->matches(new Constraint('<=', "$candidate.999"))
                    ) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }

    public function addUniversalObjectCrate(string $class): void
    {
        if (!class_exists($class)) {
            throw new UnexpectedValueException($class . ' is not a known class');
        }
        $this->universal_object_crates[] = strtolower($class);
    }

    /**
     * @return array<int, lowercase-string>
     */
    public function getUniversalObjectCrates(): array
    {
        return $this->universal_object_crates;
    }

    /** @internal */
    public function requireAutoloader(): void
    {
        /** @psalm-suppress UnresolvableInclude */
        require $this->autoloader;
    }
}
