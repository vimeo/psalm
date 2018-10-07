<?php
namespace Psalm\Checker;

use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Provider\ClassLikeStorageCacheProvider;
use Psalm\Provider\ClassLikeStorageProvider;
use Psalm\Provider\FileProvider;
use Psalm\Provider\FileReferenceProvider;
use Psalm\Provider\FileStorageCacheProvider;
use Psalm\Provider\FileStorageProvider;
use Psalm\Provider\ParserCacheProvider;
use Psalm\Provider\Providers;
use Psalm\Provider\StatementsProvider;
use Psalm\Type;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Sabre\Event\Loop;

class ProjectChecker
{
    /**
     * Cached config
     *
     * @var Config
     */
    public $config;

    /**
     * @var self
     */
    public static $instance;

    /**
     * An object representing everything we know about the code
     *
     * @var Codebase
     */
    public $codebase;

    /** @var FileProvider */
    private $file_provider;

    /** @var FileStorageProvider */
    public $file_storage_provider;

    /** @var ClassLikeStorageProvider */
    public $classlike_storage_provider;

    /** @var ?ParserCacheProvider */
    public $parser_cache_provider;

    /** @var FileReferenceProvider */
    public $file_reference_provider;

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
    public $alter_code = false;

    /**
     * @var bool
     */
    public $show_issues = true;

    /** @var int */
    public $threads;

    /**
     * Whether or not to infer types from usage. Computationally expensive, so turned off by default
     *
     * @var bool
     */
    public $infer_types_from_usage = false;

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
    public $diff_methods = false;

    /**
     * @var bool
     */
    public $only_replace_php_types_with_non_docblock_types = false;

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
        $this->file_storage_provider = $providers->file_storage_provider;
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

        if ($this->parser_cache_provider) {
            $this->parser_cache_provider->use_igbinary = $config->use_igbinary;
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

        $reference_cache = $this->file_reference_provider->loadReferenceCache();

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

            $this->codebase->scanFiles();
        } else {
            if ($this->debug_output) {
                echo count($diff_files) . ' changed files' . "\n";
            }

            if ($diff_files) {
                $file_list = $this->getReferencedFilesFromDiff($diff_files);

                // strip out deleted files
                $file_list = array_diff($file_list, $deleted_files);

                $this->checkDiffFilesWithConfig($this->config, $file_list);

                $this->config->initializePlugins($this);

                $this->codebase->scanFiles();
            }
        }

        if ($this->output_format === self::TYPE_CONSOLE) {
            echo 'Analyzing files...' . "\n";
        }

        $this->config->visitStubFiles($this->codebase, $this->debug_output);

        $this->codebase->analyzer->analyzeFiles($this, $this->threads, $this->alter_code);

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

        $this->codebase->scanFiles();

        $this->config->visitStubFiles($this->codebase, $this->debug_output);

        if ($this->output_format === self::TYPE_CONSOLE) {
            echo 'Analyzing files...' . "\n";
        }

        $this->codebase->analyzer->analyzeFiles($this, $this->threads, $this->alter_code);
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

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_name));
        $iterator->rewind();

        $files_to_scan = [];

        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                $extension = $iterator->getExtension();
                if (in_array($extension, $file_extensions, true)) {
                    $file_path = (string)$iterator->getRealPath();

                    if ($allow_non_project_files || $config->isInProjectDirs($file_path)) {
                        $files_to_scan[$file_path] = $file_path;
                    }
                }
            }

            $iterator->next();
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
        $file_names = [];

        foreach ($config->getProjectDirectories() as $dir_name) {
            /** @var RecursiveDirectoryIterator */
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_name));
            $iterator->rewind();

            while ($iterator->valid()) {
                if (!$iterator->isDot()) {
                    $extension = $iterator->getExtension();
                    if (in_array($extension, $file_extensions, true)) {
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
     *
     * @return array<string>
     */
    protected function getDiffFilesInDir($dir_name, Config $config)
    {
        $file_extensions = $config->getFileExtensions();

        if (!$this->parser_cache_provider) {
            throw new \UnexpectedValueException('Parser cache provider cannot be null here');
        }

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_name));
        $iterator->rewind();

        $diff_files = [];

        $last_good_run = $this->parser_cache_provider->getLastGoodRun();

        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                $extension = $iterator->getExtension();
                if (in_array($extension, $file_extensions, true)) {
                    $file_path = (string)$iterator->getRealPath();

                    if ($config->isInProjectDirs($file_path)) {
                        if ($this->file_provider->getModifiedTime($file_path) > $last_good_run) {
                            $diff_files[] = $file_path;
                        }
                    }
                }
            }

            $iterator->next();
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
            if (!file_exists($file_path)) {
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

        $this->codebase->scanFiles();

        $this->config->visitStubFiles($this->codebase, $this->debug_output);

        if ($this->output_format === self::TYPE_CONSOLE) {
            echo 'Analyzing files...' . "\n";
        }

        $this->codebase->analyzer->analyzeFiles($this, $this->threads, $this->alter_code);
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

        $this->codebase->scanFiles();

        $this->config->visitStubFiles($this->codebase, $this->debug_output);

        if ($this->output_format === self::TYPE_CONSOLE) {
            echo 'Analyzing files...' . "\n";
        }

        $this->codebase->analyzer->analyzeFiles($this, $this->threads, $this->alter_code);
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
     * @param  bool           $include_referencing_files
     *
     * @return array<string, string>
     */
    public function getReferencedFilesFromDiff(array $diff_files, $include_referencing_files = true)
    {
        $all_inherited_files_to_check = $diff_files;

        while ($diff_files) {
            $diff_file = array_shift($diff_files);

            $dependent_files = $this->file_reference_provider->getFilesInheritingFromFile($diff_file);
            $new_dependent_files = array_diff($dependent_files, $all_inherited_files_to_check);

            $all_inherited_files_to_check += $new_dependent_files;
            $diff_files += $new_dependent_files;
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
        $this->alter_code = true;
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
     * @return FileChecker
     */
    public function getFileCheckerForClassLike($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        $file_path = $this->codebase->scanner->getClassLikeFilePath($fq_class_name_lc);

        $file_checker = new FileChecker(
            $this,
            $file_path,
            $this->config->shortenFileName($file_path)
        );

        return $file_checker;
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

        $file_checker = $this->getFileCheckerForClassLike($fq_class_name);

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
            $file_checker = $this->getFileCheckerForClassLike($appearing_fq_class_name);
        }

        $stmts = $this->codebase->getStatementsForFile(
            $file_checker->getFilePath()
        );

        $file_checker->populateCheckers($stmts);

        if (!$this_context->self) {
            $this_context->self = $fq_class_name;
            $this_context->vars_in_scope['$this'] = Type::parseString($fq_class_name);
        }

        $file_checker->getMethodMutations($appearing_method_id, $this_context);
    }
}
