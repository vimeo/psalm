<?php
namespace Psalm\Internal\Analyzer;

use JsonRPC\Server;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\LanguageServer\{LanguageServer, ProtocolStreamReader, ProtocolStreamWriter};
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Type;
use Sabre\Event\Loop;

class ProjectAnalyzer
{
    /**
     * Cached config
     *
     * @var Config
     */
    private $config;

    /**
     * @var self
     */
    public static $instance;

    /**
     * An object representing everything we know about the code
     *
     * @var Codebase
     */
    private $codebase;

    /** @var FileProvider */
    private $file_provider;

    /** @var ClassLikeStorageProvider */
    private $classlike_storage_provider;

    /** @var ?ParserCacheProvider */
    private $parser_cache_provider;

    /** @var FileReferenceProvider */
    private $file_reference_provider;

    /**
     * Whether or not to use colors in error output
     *
     * @var bool
     */
    public $use_color;

    /**
     * Whether or not to show snippets in error output
     *
     * @var bool
     */
    public $show_snippet;

    /**
     * Whether or not to show informational messages
     *
     * @var bool
     */
    public $show_info;

    /**
     * @var string
     */
    public $output_format;

    /**
     * @var bool
     */
    public $debug_output = false;

    /**
     * @var bool
     */
    public $debug_lines = false;

    /**
     * @var bool
     */
    public $show_issues = true;

    /** @var int */
    public $threads;

    /**
     * @var array<string,string>
     */
    public $reports = [];

    /**
     * @var array<string, bool>
     */
    private $issues_to_fix = [];

    /**
     * @var int
     */
    public $php_major_version = PHP_MAJOR_VERSION;

    /**
     * @var int
     */
    public $php_minor_version = PHP_MINOR_VERSION;

    /**
     * @var bool
     */
    public $dry_run = false;

    /**
     * @var bool
     */
    public $only_replace_php_types_with_non_docblock_types = false;

    /**
     * @var ?int
     */
    public $onchange_line_limit;

    const TYPE_CONSOLE = 'console';
    const TYPE_PYLINT = 'pylint';
    const TYPE_JSON = 'json';
    const TYPE_EMACS = 'emacs';
    const TYPE_XML = 'xml';

    const SUPPORTED_OUTPUT_TYPES = [
        self::TYPE_CONSOLE,
        self::TYPE_PYLINT,
        self::TYPE_JSON,
        self::TYPE_EMACS,
        self::TYPE_XML,
    ];

    /**
     * @param FileProvider  $file_provider
     * @param Providers     $cache_provider
     * @param bool          $use_color
     * @param bool          $show_info
     * @param string        $output_format
     * @param int           $threads
     * @param bool          $debug_output
     * @param string        $reports
     * @param bool          $show_snippet
     */
    public function __construct(
        Config $config,
        Providers $providers,
        $use_color = true,
        $show_info = true,
        $output_format = self::TYPE_CONSOLE,
        $threads = 1,
        $debug_output = false,
        $reports = null,
        $show_snippet = true
    ) {
        $this->parser_cache_provider = $providers->parser_cache_provider;
        $this->file_provider = $providers->file_provider;
        $this->classlike_storage_provider = $providers->classlike_storage_provider;
        $this->file_reference_provider = $providers->file_reference_provider;

        $this->use_color = $use_color;
        $this->show_info = $show_info;
        $this->debug_output = $debug_output;
        $this->threads = $threads;
        $this->config = $config;
        $this->show_snippet = $show_snippet;

        $this->codebase = new Codebase(
            $config,
            $providers,
            $debug_output
        );

        if (!in_array($output_format, self::SUPPORTED_OUTPUT_TYPES, true)) {
            throw new \UnexpectedValueException('Unrecognised output format ' . $output_format);
        }

        if ($reports) {
            /**
             * @var array<string,string>
             */
            $mapping = [
                '.xml' => self::TYPE_XML,
                '.json' => self::TYPE_JSON,
                '.txt' => self::TYPE_EMACS,
                '.emacs' => self::TYPE_EMACS,
                '.pylint' => self::TYPE_PYLINT,
            ];
            foreach ($mapping as $extension => $type) {
                if (substr($reports, -strlen($extension)) === $extension) {
                    $this->reports[$type] = $reports;
                    break;
                }
            }
            if (empty($this->reports)) {
                throw new \UnexpectedValueException('Unrecognised report format ' . $reports);
            }
        }

        $this->output_format = $output_format;
        self::$instance = $this;
    }

    /**
     * @param  string $base_dir
     * @param  string|null $address
     * @param  bool $server_mode
     * @return void
     */
    public function server($address = '127.0.0.1:12345', $server_mode = true)
    {
        $this->codebase->diff_methods = true;
        $this->file_reference_provider->loadReferenceCache();
        $this->codebase->enterServerMode();

        $cwd = getcwd();

        if (!$cwd) {
            throw new \InvalidArgumentException('Cannot work with empty cwd');
        }

        $cpu_count = self::getCpuCount();

        // let's not go crazy
        $usable_cpus = $cpu_count - 2;

        if ($usable_cpus > 1) {
            $this->threads = $usable_cpus;
        }

        $this->config->initializePlugins($this);

        foreach ($this->config->getProjectDirectories() as $dir_name) {
            $this->checkDirWithConfig($dir_name, $this->config);
        }

        $this->output_format = self::TYPE_JSON;

        @cli_set_process_title('Psalm PHP Language Server');

        if (!$server_mode && $address) {
            // Connect to a TCP server
            $socket = stream_socket_client('tcp://' . $address, $errno, $errstr);
            if ($socket === false) {
                fwrite(STDERR, "Could not connect to language client. Error $errno\n$errstr");
                exit(1);
            }
            stream_set_blocking($socket, false);
            new LanguageServer(
                new ProtocolStreamReader($socket),
                new ProtocolStreamWriter($socket),
                $this
            );
            Loop\run();
        } elseif ($server_mode && $address) {
            // Run a TCP Server
            $tcpServer = stream_socket_server('tcp://' . $address, $errno, $errstr);
            if ($tcpServer === false) {
                fwrite(STDERR, "Could not listen on $address. Error $errno\n$errstr");
                exit(1);
            }
            fwrite(STDOUT, "Server listening on $address\n");
            if (!extension_loaded('pcntl')) {
                fwrite(STDERR, "PCNTL is not available. Only a single connection will be accepted\n");
            }
            while ($socket = stream_socket_accept($tcpServer, -1)) {
                fwrite(STDOUT, "Connection accepted\n");
                stream_set_blocking($socket, false);
                if (extension_loaded('pcntl')) {
                    // If PCNTL is available, fork a child process for the connection
                    // An exit notification will only terminate the child process
                    $pid = pcntl_fork();
                    if ($pid === -1) {
                        fwrite(STDERR, "Could not fork\n");
                        exit(1);
                    }

                    if ($pid === 0) {
                        // Child process
                        $reader = new ProtocolStreamReader($socket);
                        $reader->on(
                            'close',
                            /** @return void */
                            function () {
                                fwrite(STDOUT, "Connection closed\n");
                            }
                        );
                        new LanguageServer(
                            $reader,
                            new ProtocolStreamWriter($socket),
                            $this
                        );
                        Loop\run();
                        // Just for safety
                        exit(0);
                    }
                } else {
                    // If PCNTL is not available, we only accept one connection.
                    // An exit notification will terminate the server
                    new LanguageServer(
                        new ProtocolStreamReader($socket),
                        new ProtocolStreamWriter($socket),
                        $this
                    );
                    Loop\run();
                }
            }
        } else {
            // Use STDIO
            stream_set_blocking(STDIN, false);
            new LanguageServer(
                new ProtocolStreamReader(STDIN),
                new ProtocolStreamWriter(STDOUT),
                $this
            );
            Loop\run();
        }
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * @param  string  $base_dir
     * @param  bool $is_diff
     *
     * @return void
     */
    public function check($base_dir, $is_diff = false)
    {
        $start_checks = (int)microtime(true);

        if (!$base_dir) {
            throw new \InvalidArgumentException('Cannot work with empty base_dir');
        }

        $diff_files = null;
        $deleted_files = null;

        $reference_cache = $this->file_reference_provider->loadReferenceCache(true);

        if ($is_diff
            && $reference_cache
            && $this->parser_cache_provider
            && $this->parser_cache_provider->canDiffFiles()
        ) {
            $deleted_files = $this->file_reference_provider->getDeletedReferencedFiles();
            $diff_files = $deleted_files;

            foreach ($this->config->getProjectDirectories() as $dir_name) {
                $diff_files = array_merge($diff_files, $this->getDiffFilesInDir($dir_name, $this->config));
            }
        }

        if ($this->output_format === self::TYPE_CONSOLE) {
            echo 'Scanning files...' . "\n";
        }

        if ($diff_files === null || $deleted_files === null || count($diff_files) > 200) {
            foreach ($this->config->getProjectDirectories() as $dir_name) {
                $this->checkDirWithConfig($dir_name, $this->config);
            }

            foreach ($this->config->getProjectFiles() as $file_path) {
                $this->codebase->addFilesToAnalyze([$file_path => $file_path]);
            }

            $this->config->initializePlugins($this);

            $this->codebase->scanFiles($this->threads);
        } else {
            if ($this->debug_output) {
                echo count($diff_files) . ' changed files: ' . "\n";
                echo '    ' . implode("\n    ", $diff_files) . "\n";
            }

            if ($diff_files) {
                $file_list = $this->getReferencedFilesFromDiff($diff_files);

                // strip out deleted files
                $file_list = array_diff($file_list, $deleted_files);

                $this->checkDiffFilesWithConfig($this->config, $file_list);

                $this->config->initializePlugins($this);

                $this->codebase->scanFiles($this->threads);
            }
        }

        if ($this->output_format === self::TYPE_CONSOLE) {
            echo 'Analyzing files...' . "\n";
        }

        $this->config->visitStubFiles($this->codebase, $this->debug_output);

        $this->codebase->analyzer->analyzeFiles($this, $this->threads, $this->codebase->alter_code);

        if ($this->parser_cache_provider) {
            $removed_parser_files = $this->parser_cache_provider->deleteOldParserCaches(
                $is_diff ? $this->parser_cache_provider->getLastGoodRun() : $start_checks
            );

            if ($this->debug_output && $removed_parser_files) {
                echo 'Removed ' . $removed_parser_files . ' old parser caches' . "\n";
            }

            if ($is_diff) {
                $this->parser_cache_provider->touchParserCaches($this->getAllFiles($this->config), $start_checks);
            }
        }
    }

    /**
     * @return void
     */
    public function checkClassReferences()
    {
        if (!$this->codebase->collect_references) {
            throw new \UnexpectedValueException('Should not be checking references');
        }

        $this->codebase->classlikes->checkClassReferences();
    }

    /**
     * @param  string $symbol
     *
     * @return void
     */
    public function findReferencesTo($symbol)
    {
        $locations_by_files = $this->codebase->findReferencesToSymbol($symbol);

        foreach ($locations_by_files as $locations) {
            $bounds_starts = [];

            foreach ($locations as $location) {
                $snippet = $location->getSnippet();

                $snippet_bounds = $location->getSnippetBounds();
                $selection_bounds = $location->getSelectionBounds();

                if (isset($bounds_starts[$selection_bounds[0]])) {
                    continue;
                }

                $bounds_starts[$selection_bounds[0]] = true;

                $selection_start = $selection_bounds[0] - $snippet_bounds[0];
                $selection_length = $selection_bounds[1] - $selection_bounds[0];

                echo $location->file_name . ':' . $location->getLineNumber() . "\n" .
                    (
                        $this->use_color
                        ? substr($snippet, 0, $selection_start) .
                        "\e[97;42m" . substr($snippet, $selection_start, $selection_length) .
                        "\e[0m" . substr($snippet, $selection_length + $selection_start)
                        : $snippet
                    ) . "\n" . "\n";
            }
        }
    }

    /**
     * @param  string  $dir_name
     *
     * @return void
     */
    public function checkDir($dir_name)
    {
        $this->file_reference_provider->loadReferenceCache();

        $this->checkDirWithConfig($dir_name, $this->config, true);

        if ($this->output_format === self::TYPE_CONSOLE) {
            echo 'Scanning files...' . "\n";
        }

        $this->config->initializePlugins($this);

        $this->codebase->scanFiles($this->threads);

        $this->config->visitStubFiles($this->codebase, $this->debug_output);

        if ($this->output_format === self::TYPE_CONSOLE) {
            echo 'Analyzing files...' . "\n";
        }

        $this->codebase->analyzer->analyzeFiles($this, $this->threads, $this->codebase->alter_code);
    }

    /**
     * @param  string $dir_name
     * @param  Config $config
     * @param  bool   $allow_non_project_files
     *
     * @return void
     */
    private function checkDirWithConfig($dir_name, Config $config, $allow_non_project_files = false)
    {
        $file_extensions = $config->getFileExtensions();

        $file_paths = $this->file_provider->getFilesInDir($dir_name, $file_extensions);

        $files_to_scan = [];

        foreach ($file_paths as $file_path) {
            if ($allow_non_project_files || $config->isInProjectDirs($file_path)) {
                $files_to_scan[$file_path] = $file_path;
            }
        }

        $this->codebase->addFilesToAnalyze($files_to_scan);
    }

    /**
     * @param  Config $config
     *
     * @return array<int, string>
     */
    private function getAllFiles(Config $config)
    {
        $file_extensions = $config->getFileExtensions();
        $file_paths = [];

        foreach ($config->getProjectDirectories() as $dir_name) {
            $file_paths = array_merge(
                $file_paths,
                $this->file_provider->getFilesInDir($dir_name, $file_extensions)
            );
        }

        return $file_paths;
    }

    /**
     * @param  string $dir_name
     * @param  Config $config
     *
     * @return array<string>
     */
    protected function getDiffFilesInDir($dir_name, Config $config)
    {
        $file_extensions = $config->getFileExtensions();

        if (!$this->parser_cache_provider) {
            throw new \UnexpectedValueException('Parser cache provider cannot be null here');
        }

        $diff_files = [];

        $last_good_run = $this->parser_cache_provider->getLastGoodRun();

        $file_paths = $this->file_provider->getFilesInDir($dir_name, $file_extensions);

        foreach ($file_paths as $file_path) {
            if ($config->isInProjectDirs($file_path)) {
                if ($this->file_provider->getModifiedTime($file_path) > $last_good_run) {
                    $diff_files[] = $file_path;
                }
            }
        }

        return $diff_files;
    }

    /**
     * @param  Config           $config
     * @param  array<string>    $file_list
     *
     * @return void
     */
    private function checkDiffFilesWithConfig(Config $config, array $file_list = [])
    {
        $files_to_scan = [];

        foreach ($file_list as $file_path) {
            if (!$this->file_provider->fileExists($file_path)) {
                continue;
            }

            if (!$config->isInProjectDirs($file_path)) {
                if ($this->debug_output) {
                    echo 'skipping ' . $file_path . "\n";
                }

                continue;
            }

            $files_to_scan[$file_path] = $file_path;
        }

        $this->codebase->addFilesToAnalyze($files_to_scan);
    }

    /**
     * @param  string  $file_path
     *
     * @return void
     */
    public function checkFile($file_path)
    {
        if ($this->debug_output) {
            echo 'Checking ' . $file_path . "\n";
        }

        $this->config->hide_external_errors = $this->config->isInProjectDirs($file_path);

        $this->codebase->addFilesToAnalyze([$file_path => $file_path]);

        $this->file_reference_provider->loadReferenceCache();

        if ($this->output_format === self::TYPE_CONSOLE) {
            echo 'Scanning files...' . "\n";
        }

        $this->config->initializePlugins($this);

        $this->codebase->scanFiles($this->threads);

        $this->config->visitStubFiles($this->codebase, $this->debug_output);

        if ($this->output_format === self::TYPE_CONSOLE) {
            echo 'Analyzing files...' . "\n";
        }

        $this->codebase->analyzer->analyzeFiles($this, $this->threads, $this->codebase->alter_code);
    }

    /**
     * @param string[] $paths_to_check
     * @return void
     */
    public function checkPaths(array $paths_to_check)
    {
        foreach ($paths_to_check as $path) {
            if ($this->debug_output) {
                echo 'Checking ' . $path . "\n";
            }

            if (is_dir($path)) {
                $this->checkDirWithConfig($path, $this->config, true);
            } elseif (is_file($path)) {
                $this->codebase->addFilesToAnalyze([$path => $path]);
                $this->config->hide_external_errors = $this->config->isInProjectDirs($path);
            }
        }

        $this->file_reference_provider->loadReferenceCache();

        if ($this->output_format === self::TYPE_CONSOLE) {
            echo 'Scanning files...' . "\n";
        }

        $this->config->initializePlugins($this);

        $this->codebase->scanFiles($this->threads);

        $this->config->visitStubFiles($this->codebase, $this->debug_output);

        if ($this->output_format === self::TYPE_CONSOLE) {
            echo 'Analyzing files...' . "\n";
        }

        $this->codebase->analyzer->analyzeFiles($this, $this->threads, $this->codebase->alter_code);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param  array<string>  $diff_files
     *
     * @return array<string, string>
     */
    public function getReferencedFilesFromDiff(array $diff_files, bool $include_referencing_files = true)
    {
        $all_inherited_files_to_check = $diff_files;

        while ($diff_files) {
            $diff_file = array_shift($diff_files);

            $dependent_files = $this->file_reference_provider->getFilesInheritingFromFile($diff_file);

            $new_dependent_files = array_diff($dependent_files, $all_inherited_files_to_check);

            $all_inherited_files_to_check = array_merge($all_inherited_files_to_check, $new_dependent_files);
            $diff_files = array_merge($diff_files, $new_dependent_files);
        }

        $all_files_to_check = $all_inherited_files_to_check;

        if ($include_referencing_files) {
            foreach ($all_inherited_files_to_check as $file_name) {
                $dependent_files = $this->file_reference_provider->getFilesReferencingFile($file_name);
                $all_files_to_check = array_merge($dependent_files, $all_files_to_check);
            }
        }

        return array_combine($all_files_to_check, $all_files_to_check);
    }

    /**
     * @param  string $file_path
     *
     * @return bool
     */
    public function fileExists($file_path)
    {
        return $this->file_provider->fileExists($file_path);
    }

    /**
     * @param int $php_major_version
     * @param int $php_minor_version
     * @param bool $dry_run
     * @param bool $safe_types
     *
     * @return void
     */
    public function alterCodeAfterCompletion(
        $php_major_version,
        $php_minor_version,
        $dry_run = false,
        $safe_types = false
    ) {
        $this->codebase->alter_code = true;
        $this->show_issues = false;
        $this->php_major_version = $php_major_version;
        $this->php_minor_version = $php_minor_version;
        $this->dry_run = $dry_run;
        $this->only_replace_php_types_with_non_docblock_types = $safe_types;
    }

    /**
     * @param array<string, bool> $issues
     *
     * @return void
     */
    public function setIssuesToFix(array $issues)
    {
        $this->issues_to_fix = $issues;
    }

    /**
     * @return array<string, bool>
     *
     * @psalm-suppress PossiblyUnusedMethod - need to fix #422
     */
    public function getIssuesToFix()
    {
        return $this->issues_to_fix;
    }

    /**
     * @return Codebase
     */
    public function getCodebase()
    {
        return $this->codebase;
    }

    /**
     * @param  string $fq_class_name
     *
     * @return FileAnalyzer
     */
    public function getFileAnalyzerForClassLike($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        $file_path = $this->codebase->scanner->getClassLikeFilePath($fq_class_name_lc);

        $file_analyzer = new FileAnalyzer(
            $this,
            $file_path,
            $this->config->shortenFileName($file_path)
        );

        return $file_analyzer;
    }

    /**
     * @param  string   $original_method_id
     * @param  Context  $this_context
     *
     * @return void
     */
    public function getMethodMutations($original_method_id, Context $this_context)
    {
        list($fq_class_name) = explode('::', $original_method_id);

        $file_analyzer = $this->getFileAnalyzerForClassLike($fq_class_name);

        $appearing_method_id = $this->codebase->methods->getAppearingMethodId($original_method_id);

        if (!$appearing_method_id) {
            // this can happen for some abstract classes implementing (but not fully) interfaces
            return;
        }

        list($appearing_fq_class_name) = explode('::', $appearing_method_id);

        $appearing_class_storage = $this->classlike_storage_provider->get($appearing_fq_class_name);

        if (!$appearing_class_storage->user_defined) {
            return;
        }

        if (strtolower($appearing_fq_class_name) !== strtolower($fq_class_name)) {
            $file_analyzer = $this->getFileAnalyzerForClassLike($appearing_fq_class_name);
        }

        $stmts = $this->codebase->getStatementsForFile(
            $file_analyzer->getFilePath()
        );

        $file_analyzer->populateCheckers($stmts);

        if (!$this_context->self) {
            $this_context->self = $fq_class_name;
            $this_context->vars_in_scope['$this'] = Type::parseString($fq_class_name);
        }

        $file_analyzer->getMethodMutations($appearing_method_id, $this_context);

        $file_analyzer->class_analyzers_to_analyze = [];
        $file_analyzer->interface_analyzers_to_analyze = [];
    }

    /**
     * Adapted from https://gist.github.com/divinity76/01ef9ca99c111565a72d3a8a6e42f7fb
     * returns number of cpu cores
     * Copyleft 2018, license: WTFPL
     * @throws \RuntimeException
     * @throws \LogicException
     * @return int
     * @psalm-suppress ForbiddenCode
     */
    private function getCpuCount(): int
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            /*
            $str = trim((string) shell_exec('wmic cpu get NumberOfCores 2>&1'));
            if (!preg_match('/(\d+)/', $str, $matches)) {
                throw new \RuntimeException('wmic failed to get number of cpu cores on windows!');
            }
            return ((int) $matches [1]);
            */
            return 1;
        }

        $has_nproc = trim((string) @shell_exec('command -v nproc'));
        if ($has_nproc) {
            $ret = @shell_exec('nproc');
            if (is_string($ret)) {
                $ret = trim($ret);
                /** @var int|false */
                $tmp = filter_var($ret, FILTER_VALIDATE_INT);
                if (is_int($tmp)) {
                    return $tmp;
                }
            }
        }

        $ret = @shell_exec('sysctl -n hw.ncpu');
        if (is_string($ret)) {
            $ret = trim($ret);
            /** @var int|false */
            $tmp = filter_var($ret, FILTER_VALIDATE_INT);
            if (is_int($tmp)) {
                return $tmp;
            }
        }

        if (is_readable('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            $count = substr_count($cpuinfo, 'processor');
            if ($count > 0) {
                return $count;
            }
        }

        throw new \LogicException('failed to detect number of CPUs!');
    }
}
