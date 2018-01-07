<?php
namespace Psalm\Checker;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception;
use Psalm\FileManipulation\FileManipulationBuffer;
use Psalm\FileManipulation\FunctionDocblockManipulator;
use Psalm\Issue\CircularReference;
use Psalm\Issue\PossiblyUnusedMethod;
use Psalm\Issue\PossiblyUnusedParam;
use Psalm\Issue\UnusedClass;
use Psalm\Issue\UnusedMethod;
use Psalm\IssueBuffer;
use Psalm\Provider\ClassLikeStorageProvider;
use Psalm\Provider\FileProvider;
use Psalm\Provider\FileReferenceProvider;
use Psalm\Provider\FileStorageProvider;
use Psalm\Provider\ParserCacheProvider;
use Psalm\Provider\StatementsProvider;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ProjectChecker
{
    /**
     * Cached config
     *
     * @var Config|null
     */
    private $config;

    /**
     * @var self
     */
    public static $instance;

    /** @var FileProvider */
    private $file_provider;

    /** @var FileStorageProvider */
    public $file_storage_provider;

    /** @var ClassLikeStorageProvider */
    public $classlike_storage_provider;

    /** @var ParserCacheProvider */
    public $cache_provider;

    /**
     * Whether or not to use colors in error output
     *
     * @var bool
     */
    public $use_color;

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
    public $collect_references = false;

    /**
     * @var string|null
     */
    public $find_references_to;

    /**
     * @var bool
     */
    public $debug_output = false;

    /**
     * @var bool
     */
    public $alter_code = false;

    /**
     * @var bool
     */
    public $cache = false;

    /**
     * @var array<string, bool>
     */
    private $existing_classlikes_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_classlikes = [];

    /**
     * @var array<string, bool>
     */
    private $existing_classes_lc = [];

    /**
     * @var array<string, bool>
     */
    private $reflected_classeslikes_lc = [];

    /**
     * @var array<string, bool>
     */
    public $existing_classes = [];

    /**
     * @var array<string, bool>
     */
    private $existing_interfaces_lc = [];

    /**
     * @var array<string, bool>
     */
    public $existing_interfaces = [];

    /**
     * @var array<string, bool>
     */
    private $existing_traits_lc = [];

    /**
     * @var array<string, bool>
     */
    public $existing_traits = [];

    /**
     * @var array<string, string>
     */
    private $classlike_files = [];

    /**
     * @var array<string, string>
     */
    private $files_to_scan = [];

    /**
     * @var array<string, string>
     */
    private $classes_to_scan = [];

    /**
     * @var array<string, bool>
     */
    private $classes_to_deep_scan = [];

    /**
     * @var array<string, bool>
     */
    private $store_scan_failure = [];

    /**
     * @var array<string, string>
     */
    private $files_to_deep_scan = [];

    /**
     * We analyze more files than we necessarily report errors in
     *
     * @var array<string, string>
     */
    private $files_to_report = [];

    /**
     * @var array<string, bool>
     */
    private $scanned_files = [];

    /**
     * @var array<string, bool>
     */
    private $visited_classes = [];

    /**
     * @var array<string, FileChecker>
     */
    private $file_checkers = [];

    /**
     * @var array<string, MethodChecker>
     */
    public $method_checkers = [];

    /**
     * @var array<string, int>
     */
    public $classlike_references = [];

    /**
     * A map of fully-qualified use declarations to the files
     * that reference them (keyed by filename)
     *
     * @var array<string, array<string, array<int, \Psalm\CodeLocation>>>
     */
    public $use_referencing_locations = [];

    /**
     * A map of file names to the classes that they contain explicit references to
     * used in collaboration with use_referencing_locations
     *
     * @var array<string, array<string, bool>>
     */
    public $use_referencing_files = [];

    /**
     * @var bool
     */
    public $server_mode = false;

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
     * Whether to log functions just at the file level or globally (for stubs)
     *
     * @var bool
     */
    public $register_global_functions = false;

    /**
     * @var ?array<string, string>
     */
    private $composer_classmap;

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

    const TYPE_CONSOLE = 'console';
    const TYPE_JSON = 'json';
    const TYPE_EMACS = 'emacs';
    const TYPE_XML = 'xml';

    /**
     * @param FileProvider  $file_provider
     * @param ParserCacheProvider $cache_provider
     * @param bool          $use_color
     * @param bool          $show_info
     * @param string        $output_format
     * @param int           $threads
     * @param bool          $debug_output
     * @param bool          $collect_references
     * @param string        $find_references_to
     * @param string        $reports
     */
    public function __construct(
        FileProvider $file_provider,
        ParserCacheProvider $cache_provider,
        $use_color = true,
        $show_info = true,
        $output_format = self::TYPE_CONSOLE,
        $threads = 1,
        $debug_output = false,
        $collect_references = false,
        $find_references_to = null,
        string $reports = null
    ) {
        $this->file_provider = $file_provider;
        $this->cache_provider = $cache_provider;
        $this->use_color = $use_color;
        $this->show_info = $show_info;
        $this->debug_output = $debug_output;
        $this->threads = $threads;
        $this->collect_references = $collect_references;
        $this->find_references_to = $find_references_to;

        if (!in_array($output_format, [self::TYPE_CONSOLE, self::TYPE_JSON, self::TYPE_EMACS, self::TYPE_XML], true)) {
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

        $this->collectPredefinedClassLikes();

        $this->file_storage_provider = new FileStorageProvider();
        $this->classlike_storage_provider = new ClassLikeStorageProvider();
    }

    /**
     * @return ProjectChecker
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

        if (!$this->config) {
            throw new \InvalidArgumentException('Config should not be null here');
        }

        $diff_files = null;
        $deleted_files = null;

        if ($is_diff && FileReferenceProvider::loadReferenceCache() && $this->cache_provider->canDiffFiles()) {
            $deleted_files = FileReferenceProvider::getDeletedReferencedFiles();
            $diff_files = $deleted_files;

            foreach ($this->config->getProjectDirectories() as $dir_name) {
                $diff_files = array_merge($diff_files, $this->getDiffFilesInDir($dir_name, $this->config));
            }
        }

        if ($diff_files === null || $deleted_files === null || count($diff_files) > 200) {
            foreach ($this->config->getProjectDirectories() as $dir_name) {
                $this->checkDirWithConfig($dir_name, $this->config);
            }

            $this->scanFiles();

            if (!$this->server_mode) {
                $this->analyzeFiles();
            }
        } else {
            if ($this->debug_output) {
                echo count($diff_files) . ' changed files' . PHP_EOL;
            }

            $file_list = self::getReferencedFilesFromDiff($diff_files);

            // strip out deleted files
            $file_list = array_diff($file_list, $deleted_files);

            $this->checkDiffFilesWithConfig($this->config, $file_list);

            $this->scanFiles();

            if (!$this->server_mode) {
                $this->analyzeFiles();
            }
        }

        $removed_parser_files = $this->cache_provider->deleteOldParserCaches(
            $is_diff ? $this->cache_provider->getLastGoodRun() : $start_checks
        );

        if ($this->debug_output && $removed_parser_files) {
            echo 'Removed ' . $removed_parser_files . ' old parser caches' . PHP_EOL;
        }

        if ($is_diff) {
            $this->cache_provider->touchParserCaches($this->getAllFiles($this->config), $start_checks);
        }

        if ($this->collect_references) {
            if ($this->find_references_to) {
                if (strpos($this->find_references_to, '::') !== false) {
                    $locations_by_files = $this->findReferencesToMethod($this->find_references_to);
                } else {
                    $locations_by_files = $this->findReferencesToClassLike($this->find_references_to);
                }

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

                        echo $location->file_name . ':' . $location->getLineNumber() . PHP_EOL .
                            (
                                $this->use_color
                                ? substr($snippet, 0, $selection_start) .
                                "\e[97;42m" . substr($snippet, $selection_start, $selection_length) .
                                "\e[0m" . substr($snippet, $selection_length + $selection_start)
                                : $snippet
                            ) . PHP_EOL . PHP_EOL;
                    }
                }
            } else {
                $this->checkClassReferences();
            }
        }

        IssueBuffer::finish($this, true, (int)$start_checks, $this->scanned_files);
    }

    /**
     * @return void
     */
    public function scanFiles()
    {
        if (!$this->config) {
            throw new \UnexpectedValueException('$this->config cannot be null');
        }

        $filetype_handlers = $this->config->getFiletypeHandlers();

        $has_changes = false;

        while ($this->files_to_scan || $this->classes_to_scan) {
            if ($this->files_to_scan) {
                $file_path = array_shift($this->files_to_scan);

                if (!isset($this->scanned_files[$file_path])) {
                    $this->scanFile($file_path, $filetype_handlers, isset($this->files_to_deep_scan[$file_path]));
                    $has_changes = true;
                }
            } else {
                /** @var string */
                $fq_classlike_name = array_shift($this->classes_to_scan);
                $fq_classlike_name_lc = strtolower($fq_classlike_name);

                if (isset($this->reflected_classeslikes_lc[$fq_classlike_name_lc])) {
                    continue;
                }

                if (isset($this->existing_classlikes_lc[$fq_classlike_name_lc])
                        && $this->existing_classlikes_lc[$fq_classlike_name_lc] === false
                ) {
                    continue;
                }

                if (!isset($this->classlike_files[$fq_classlike_name_lc])) {
                    if (isset($this->existing_classlikes_lc[$fq_classlike_name_lc])
                        && $this->existing_classlikes_lc[$fq_classlike_name_lc]
                    ) {
                        if ($this->debug_output) {
                            echo 'Using reflection to get metadata for ' . $fq_classlike_name . PHP_EOL;
                        }

                        $reflected_class = new \ReflectionClass($fq_classlike_name);
                        ClassLikeChecker::registerReflectedClass($reflected_class->name, $reflected_class, $this);
                        $this->reflected_classeslikes_lc[$fq_classlike_name_lc] = true;
                    } elseif ($this->fileExistsForClassLike($fq_classlike_name)) {
                        if (isset($this->classlike_files[$fq_classlike_name_lc])) {
                            $file_path = $this->classlike_files[$fq_classlike_name_lc];
                            $this->files_to_scan[$file_path] = $file_path;
                            if (isset($this->classes_to_deep_scan[$fq_classlike_name_lc])) {
                                unset($this->classes_to_deep_scan[$fq_classlike_name_lc]);
                                $this->files_to_deep_scan[$file_path] = $file_path;
                            }
                        }
                    } elseif ($this->store_scan_failure[$fq_classlike_name]) {
                        $this->existing_classlikes_lc[$fq_classlike_name_lc] = false;
                    }
                }
            }
        }

        if (!$has_changes) {
            return;
        }

        if ($this->debug_output) {
            echo 'ClassLikeStorage is populating' . PHP_EOL;
        }

        foreach ($this->classlike_storage_provider->getAll() as $storage) {
            if (!$storage->user_defined) {
                continue;
            }

            $this->populateClassLikeStorage($storage);
        }

        if ($this->debug_output) {
            echo 'ClassLikeStorage is populated' . PHP_EOL;
        }

        if ($this->debug_output) {
            echo 'FileStorage is populating' . PHP_EOL;
        }

        $all_file_storage = $this->file_storage_provider->getAll();

        foreach ($all_file_storage as $file_storage) {
            $this->populateFileStorage($file_storage);
        }

        if ($this->debug_output) {
            echo 'FileStorage is populated' . PHP_EOL;
        }
    }

    /**
     * @param  ClassLikeStorage $storage
     * @param  array            $dependent_classlikes
     *
     * @return void
     */
    private function populateClassLikeStorage(ClassLikeStorage $storage, $dependent_classlikes = [])
    {
        if ($storage->populated) {
            return;
        }

        if (isset($dependent_classlikes[strtolower($storage->name)])) {
            if ($storage->location && IssueBuffer::accepts(
                new CircularReference(
                    'Circular reference discovered when loading ' . $storage->name,
                    $storage->location
                )
            )) {
                // fall through
            }

            return;
        }

        $storage_provider = $this->classlike_storage_provider;

        foreach ($storage->used_traits as $used_trait_lc => $used_trait) {
            try {
                $trait_storage = $storage_provider->get($used_trait_lc);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $this->populateClassLikeStorage($trait_storage, $dependent_classlikes);

            $this->inheritMethodsFromParent($storage, $trait_storage);
            $this->inheritPropertiesFromParent($storage, $trait_storage);
        }

        $dependent_classlikes[strtolower($storage->name)] = true;

        if (isset($storage->parent_classes[0])) {
            try {
                $parent_storage = $storage_provider->get($storage->parent_classes[0]);
            } catch (\InvalidArgumentException $e) {
                $parent_storage = null;
            }

            if ($parent_storage) {
                $this->populateClassLikeStorage($parent_storage, $dependent_classlikes);

                $storage->parent_classes = array_merge($storage->parent_classes, $parent_storage->parent_classes);

                $this->inheritMethodsFromParent($storage, $parent_storage);
                $this->inheritPropertiesFromParent($storage, $parent_storage);

                $storage->class_implements += $parent_storage->class_implements;

                $storage->public_class_constants += $parent_storage->public_class_constants;
                $storage->protected_class_constants += $parent_storage->protected_class_constants;
            }
        }

        $parent_interfaces = [];

        foreach ($storage->parent_interfaces as $parent_interface_lc => $_) {
            try {
                $parent_interface_storage = $storage_provider->get($parent_interface_lc);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $this->populateClassLikeStorage($parent_interface_storage, $dependent_classlikes);

            // copy over any constants
            $storage->public_class_constants = array_merge(
                $storage->public_class_constants,
                $parent_interface_storage->public_class_constants
            );

            $parent_interfaces = array_merge($parent_interfaces, $parent_interface_storage->parent_interfaces);

            $this->inheritMethodsFromParent($storage, $parent_interface_storage);
        }

        $storage->parent_interfaces = array_merge($parent_interfaces, $storage->parent_interfaces);

        $extra_interfaces = [];

        foreach ($storage->class_implements as $implemented_interface_lc => $_) {
            try {
                $implemented_interface_storage = $storage_provider->get($implemented_interface_lc);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $this->populateClassLikeStorage($implemented_interface_storage, $dependent_classlikes);

            // copy over any constants
            $storage->public_class_constants = array_merge(
                $storage->public_class_constants,
                $implemented_interface_storage->public_class_constants
            );

            $extra_interfaces = array_merge($extra_interfaces, $implemented_interface_storage->parent_interfaces);

            $storage->public_class_constants += $implemented_interface_storage->public_class_constants;
        }

        $storage->class_implements = array_merge($extra_interfaces, $storage->class_implements);

        foreach ($storage->class_implements as $implemented_interface) {
            try {
                $implemented_interface_storage = $storage_provider->get($implemented_interface);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            foreach ($implemented_interface_storage->methods as $method_name => $method) {
                if ($method->visibility === ClassLikeChecker::VISIBILITY_PUBLIC) {
                    $mentioned_method_id = $implemented_interface . '::' . $method_name;
                    $implemented_method_id = $storage->name . '::' . $method_name;

                    if ($storage->abstract) {
                        MethodChecker::setOverriddenMethodId($this, $implemented_method_id, $mentioned_method_id);
                    }
                }
            }
        }

        if ($storage->location) {
            $file_path = $storage->location->file_path;

            foreach ($storage->parent_interfaces as $parent_interface_lc) {
                FileReferenceProvider::addFileInheritanceToClass($file_path, $parent_interface_lc);
            }

            foreach ($storage->parent_classes as $parent_class_lc) {
                FileReferenceProvider::addFileInheritanceToClass($file_path, $parent_class_lc);
            }

            foreach ($storage->class_implements as $implemented_interface) {
                FileReferenceProvider::addFileInheritanceToClass($file_path, strtolower($implemented_interface));
            }

            foreach ($storage->used_traits as $used_trait_lc) {
                FileReferenceProvider::addFileInheritanceToClass($file_path, $used_trait_lc);
            }
        }

        $storage->populated = true;
    }

    /**
     * @param  FileStorage $storage
     * @param  array<string, bool> $dependent_file_paths
     *
     * @return void
     */
    private function populateFileStorage(FileStorage $storage, array $dependent_file_paths = [])
    {
        if ($storage->populated) {
            return;
        }

        if (isset($dependent_file_paths[strtolower($storage->file_path)])) {
            return;
        }

        $dependent_file_paths[strtolower($storage->file_path)] = true;

        foreach ($storage->included_file_paths as $included_file_path => $_) {
            try {
                $included_file_storage = $this->file_storage_provider->get($included_file_path);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $this->populateFileStorage($included_file_storage, $dependent_file_paths);

            $storage->declaring_function_ids = array_merge(
                $included_file_storage->declaring_function_ids,
                $storage->declaring_function_ids
            );

            $storage->declaring_constants = array_merge(
                $included_file_storage->declaring_constants,
                $storage->declaring_constants
            );
        }

        $storage->populated = true;
    }

    /**
     * @param ClassLikeStorage $storage
     * @param ClassLikeStorage $parent_storage
     *
     * @return void
     */
    protected function inheritMethodsFromParent(ClassLikeStorage $storage, ClassLikeStorage $parent_storage)
    {
        $fq_class_name = $storage->name;

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            if ($parent_storage->is_trait
                && $storage->trait_alias_map
                && isset($storage->trait_alias_map[$method_name])
            ) {
                $aliased_method_name = $storage->trait_alias_map[$method_name];
            } else {
                $aliased_method_name = $method_name;
            }

            if (isset($storage->appearing_method_ids[$aliased_method_name])) {
                continue;
            }

            $implemented_method_id = $fq_class_name . '::' . $aliased_method_name;

            $storage->appearing_method_ids[$aliased_method_name] =
                $parent_storage->is_trait ? $implemented_method_id : $appearing_method_id;
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_method_ids as $method_name => $declaring_method_id) {
            if (!$parent_storage->is_trait) {
                $implemented_method_id = $fq_class_name . '::' . $method_name;

                MethodChecker::setOverriddenMethodId($this, $implemented_method_id, $declaring_method_id);
            }

            if ($parent_storage->is_trait
                && $storage->trait_alias_map
                && isset($storage->trait_alias_map[$method_name])
            ) {
                $aliased_method_name = $storage->trait_alias_map[$method_name];
            } else {
                $aliased_method_name = $method_name;
            }

            if (isset($storage->declaring_method_ids[$aliased_method_name])) {
                list($implementing_fq_class_name, $implementing_method_name) = explode(
                    '::',
                    $storage->declaring_method_ids[$aliased_method_name]
                );

                $implementing_class_storage = $this->classlike_storage_provider->get($implementing_fq_class_name);

                if (!$implementing_class_storage->methods[$implementing_method_name]->abstract) {
                    continue;
                }
            }

            $storage->declaring_method_ids[$aliased_method_name] = $declaring_method_id;
            $storage->inheritable_method_ids[$aliased_method_name] = $declaring_method_id;
        }
    }

    /**
     * @param ClassLikeStorage $storage
     * @param ClassLikeStorage $parent_storage
     *
     * @return void
     */
    protected function inheritPropertiesFromParent(ClassLikeStorage $storage, ClassLikeStorage $parent_storage)
    {
        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_property_ids as $property_name => $appearing_property_id) {
            if (isset($storage->appearing_property_ids[$property_name])) {
                continue;
            }

            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeChecker::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $implemented_property_id = $storage->name . '::$' . $property_name;

            $storage->appearing_property_ids[$property_name] =
                $parent_storage->is_trait ? $implemented_property_id : $appearing_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_property_ids as $property_name => $declaring_property_id) {
            if (isset($storage->declaring_property_ids[$property_name])) {
                continue;
            }

            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeChecker::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->declaring_property_ids[$property_name] = $declaring_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_property_ids as $property_name => $inheritable_property_id) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeChecker::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->inheritable_property_ids[$property_name] = $inheritable_property_id;
        }
    }

    /**
     * @param  string  $fq_classlike_name
     * @param  string|null  $referencing_file_path
     * @param  bool $analyze_too
     * @param  bool $store_failure
     *
     * @return void
     */
    public function queueClassLikeForScanning(
        $fq_classlike_name,
        $referencing_file_path = null,
        $analyze_too = false,
        $store_failure = true
    ) {
        if (!$this->config) {
            throw new \UnexpectedValueException('Config should not be null here');
        }

        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        // avoid checking classes that we know will just end in failure
        if ($fq_classlike_name_lc === 'null' || substr($fq_classlike_name_lc, -5) === '\null') {
            return;
        }

        if (!isset($this->classlike_files[$fq_classlike_name_lc])) {
            if (!isset($this->classes_to_scan[$fq_classlike_name_lc]) || $store_failure) {
                $this->classes_to_scan[$fq_classlike_name_lc] = $fq_classlike_name;
            }

            if ($analyze_too) {
                $this->classes_to_deep_scan[$fq_classlike_name_lc] = true;
            }

            $this->store_scan_failure[$fq_classlike_name] = $store_failure;
        }

        if ($referencing_file_path) {
            FileReferenceProvider::addFileReferenceToClass($referencing_file_path, $fq_classlike_name_lc);
        }
    }

    /**
     * @param  string $file_path
     *
     * @return void
     */
    public function queueFileForScanning($file_path)
    {
        $this->files_to_scan[$file_path] = $file_path;
    }

    /**
     * @return void
     */
    private function analyzeFiles()
    {
        if (!$this->config) {
            throw new \UnexpectedValueException('$this->config cannot be null');
        }

        $filetype_handlers = $this->config->getFiletypeHandlers();

        $analysis_worker =
            /**
             * @param int $i
             * @param string $file_path
             *
             * @return void
             *
             * @psalm-suppress UnusedParam
             */
            function ($i, $file_path) use ($filetype_handlers) {
                $file_checker = $this->getFile($file_path, $filetype_handlers, true);

                if ($this->debug_output) {
                    echo 'Analyzing ' . $file_checker->getFilePath() . PHP_EOL;
                }

                $file_checker->analyze(null);
            };

        $pool_size = $this->threads;

        if ($pool_size > 1 && count($this->files_to_report) > $pool_size) {
            $process_file_paths = [];

            $i = 0;

            foreach ($this->files_to_report as $file_path) {
                $process_file_paths[$i % $pool_size][] = $file_path;
                ++$i;
            }

            // Run analysis one file at a time, splitting the set of
            // files up among a given number of child processes.
            $pool = new \Psalm\Fork\Pool(
                $process_file_paths,
                /** @return void */
                function () {
                },
                $analysis_worker,
                /** @return array */
                function () {
                    return [
                        'issues' => IssueBuffer::getIssuesData(),
                        'file_references' => FileReferenceProvider::getAllFileReferences(),
                    ];
                }
            );

            // Wait for all tasks to complete and collect the results.
            /**
             * @var array<array{issues: array<int, array{severity: string, line_number: string, type: string,
             *  message: string, file_name: string, file_path: string, snippet: string, from: int, to: int,
             *  snippet_from: int, snippet_to: int, column: int}>, file_references: array<string, array<string,bool>>}>
             */
            $forked_pool_data = $pool->wait();

            foreach ($forked_pool_data as $pool_data) {
                IssueBuffer::addIssues($pool_data['issues']);
                FileReferenceProvider::addFileReferences($pool_data['file_references']);
            }

            // TODO: Tell the caller that the fork pool encountered an error in another PR?
            // $did_fork_pool_have_error = $pool->didHaveError();
        } else {
            $i = 0;

            foreach ($this->files_to_report as $file_path => $_) {
                $analysis_worker($i, $file_path);
                ++$i;
            }
        }

        if ($this->alter_code) {
            foreach ($this->files_to_report as $file_path) {
                $this->updateFile($file_path, $this->dry_run, true);
            }
        }
    }

    /**
     * @param  string $file_path
     * @param  bool $dry_run
     * @param  bool   $output_changes to console
     *
     * @return void
     */
    public function updateFile($file_path, $dry_run, $output_changes = false)
    {
        if ($this->alter_code) {
            $new_return_type_manipulations = FunctionDocblockManipulator::getManipulationsForFile($file_path);
        } else {
            $new_return_type_manipulations = [];
        }

        $other_manipulations = FileManipulationBuffer::getForFile($file_path);

        $file_manipulations = $new_return_type_manipulations + $other_manipulations;

        krsort($file_manipulations);

        $docblock_update_count = count($file_manipulations);

        $existing_contents = $this->getFileContents($file_path);

        foreach ($file_manipulations as $manipulation) {
            $existing_contents
                = substr($existing_contents, 0, $manipulation->start)
                    . $manipulation->insertion_text
                    . substr($existing_contents, $manipulation->end);
        }

        if ($docblock_update_count) {
            if ($dry_run) {
                echo $file_path . ':' . PHP_EOL;

                $differ = new \PhpCsFixer\Diff\v2_0\Differ(
                    new \PhpCsFixer\Diff\GeckoPackages\DiffOutputBuilder\UnifiedDiffOutputBuilder([
                        'fromFile' => 'Original',
                        'toFile' => 'New',
                    ])
                );

                echo (string) $differ->diff($this->getFileContents($file_path), $existing_contents);

                return;
            }

            if ($output_changes) {
                echo 'Altering ' . $file_path . PHP_EOL;
            }

            $this->file_provider->setContents($file_path, $existing_contents);
        }
    }

    /**
     * @param  string $method_id
     *
     * @return array<string, \Psalm\CodeLocation[]>
     */
    public function findReferencesToMethod($method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        try {
            $class_storage = $this->classlike_storage_provider->get($fq_class_name);
        } catch (\InvalidArgumentException $e) {
            die('Class ' . $fq_class_name . ' cannot be found' . PHP_EOL);
        }

        if (!isset($class_storage->methods[strtolower($method_name)])) {
            die('Method ' . $method_id . ' cannot be found' . PHP_EOL);
        }

        $method_storage = $class_storage->methods[strtolower($method_name)];

        if ($method_storage->referencing_locations === null) {
            die('No references found for ' . $method_id . PHP_EOL);
        }

        return $method_storage->referencing_locations;
    }

    /**
     * @param  string $fq_class_name
     *
     * @return array<string, \Psalm\CodeLocation[]>
     */
    public function findReferencesToClassLike($fq_class_name)
    {
        try {
            $class_storage = $this->classlike_storage_provider->get($fq_class_name);
        } catch (\InvalidArgumentException $e) {
            die('Class ' . $fq_class_name . ' cannot be found' . PHP_EOL);
        }

        if ($class_storage->referencing_locations === null) {
            die('No references found for ' . $fq_class_name . PHP_EOL);
        }

        $classlike_references_by_file = $class_storage->referencing_locations;

        if (isset($this->use_referencing_locations[strtolower($fq_class_name)])) {
            foreach ($this->use_referencing_locations[strtolower($fq_class_name)] as $file_path => $locations) {
                if (!isset($classlike_references_by_file[$file_path])) {
                    $classlike_references_by_file[$file_path] = $locations;
                } else {
                    $classlike_references_by_file[$file_path] = array_merge(
                        $locations,
                        $classlike_references_by_file[$file_path]
                    );
                }
            }
        }

        return $classlike_references_by_file;
    }

    /**
     * @return void
     */
    public function checkClassReferences()
    {
        foreach ($this->existing_classlikes_lc as $fq_class_name_lc => $_) {
            try {
                $classlike_storage = $this->classlike_storage_provider->get($fq_class_name_lc);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            if ($classlike_storage->location &&
                $this->config &&
                $this->config->isInProjectDirs($classlike_storage->location->file_path)
            ) {
                if (!isset($this->classlike_references[$fq_class_name_lc])) {
                    if (IssueBuffer::accepts(
                        new UnusedClass(
                            'Class ' . $classlike_storage->name . ' is never used',
                            $classlike_storage->location
                        )
                    )) {
                        // fall through
                    }
                } else {
                    $this->checkMethodReferences($classlike_storage);
                }
            }
        }
    }

    /**
     * @return void
     */
    protected function checkMethodReferences(ClassLikeStorage $classlike_storage)
    {
        foreach ($classlike_storage->methods as $method_name => $method_storage) {
            if (($method_storage->referencing_locations === null
                    || count($method_storage->referencing_locations) === 0)
                && (substr($method_name, 0, 2) !== '__' || $method_name === '__construct')
                && $method_storage->location
            ) {
                $method_id = $classlike_storage->name . '::' . $method_storage->cased_name;

                if ($method_storage->visibility === ClassLikeChecker::VISIBILITY_PUBLIC) {
                    $method_name_lc = strtolower($method_name);

                    $has_parent_references = false;

                    foreach ($classlike_storage->overridden_method_ids[$method_name_lc] as $parent_method_id) {
                        $parent_method_storage = MethodChecker::getStorage($this, $parent_method_id);

                        if (!$parent_method_storage->abstract || $parent_method_storage->referencing_locations) {
                            $has_parent_references = true;
                            break;
                        }
                    }

                    foreach ($classlike_storage->class_implements as $fq_interface_name) {
                        $interface_storage = $this->classlike_storage_provider->get($fq_interface_name);
                        if (isset($interface_storage->methods[$method_name])) {
                            $interface_method_storage = $interface_storage->methods[$method_name];

                            if ($interface_method_storage->referencing_locations) {
                                $has_parent_references = true;
                                break;
                            }
                        }
                    }

                    if (!$has_parent_references) {
                        if (IssueBuffer::accepts(
                            new PossiblyUnusedMethod(
                                'Cannot find public calls to method ' . $method_id,
                                $method_storage->location
                            ),
                            $method_storage->suppressed_issues
                        )) {
                            // fall through
                        }
                    }
                } elseif (!isset($classlike_storage->declaring_method_ids['__call'])) {
                    if (IssueBuffer::accepts(
                        new UnusedMethod(
                            'Method ' . $method_id . ' is never used',
                            $method_storage->location
                        )
                    )) {
                        // fall through
                    }
                }
            } else {
                foreach ($method_storage->unused_params as $offset => $code_location) {
                    $has_parent_references = false;

                    $method_name_lc = strtolower($method_name);

                    foreach ($classlike_storage->overridden_method_ids[$method_name_lc] as $parent_method_id) {
                        $parent_method_storage = MethodChecker::getStorage($this, $parent_method_id);

                        if (!$parent_method_storage->abstract
                            && isset($parent_method_storage->used_params[$offset])
                        ) {
                            $has_parent_references = true;
                            break;
                        }
                    }

                    if (!$has_parent_references && !isset($method_storage->used_params[$offset])) {
                        if (IssueBuffer::accepts(
                            new PossiblyUnusedParam(
                                'Param #' . $offset . ' is never referenced in this method',
                                $code_location
                            ),
                            $method_storage->suppressed_issues
                        )) {
                            // fall through
                        }
                    }
                }
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
        if (!$this->config) {
            throw new \UnexpectedValueException('Config should be set here');
        }

        FileReferenceProvider::loadReferenceCache();

        $start_checks = (int)microtime(true);

        $this->checkDirWithConfig($dir_name, $this->config, true);

        $this->scanFiles();
        $this->analyzeFiles();

        IssueBuffer::finish($this, false, $start_checks, $this->scanned_files);
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

        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                $extension = $iterator->getExtension();
                if (in_array($extension, $file_extensions, true)) {
                    $file_path = (string)$iterator->getRealPath();

                    if ($allow_non_project_files || $config->isInProjectDirs($file_path)) {
                        $this->files_to_report[$file_path] = $file_path;
                        $this->files_to_deep_scan[$file_path] = $file_path;
                        $this->files_to_scan[$file_path] = $file_path;
                    }
                }
            }

            $iterator->next();
        }
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

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_name));
        $iterator->rewind();

        $diff_files = [];

        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                $extension = $iterator->getExtension();
                if (in_array($extension, $file_extensions, true)) {
                    $file_path = (string)$iterator->getRealPath();

                    if ($config->isInProjectDirs($file_path)) {
                        if ($this->file_provider->getModifiedTime($file_path) > $this->cache_provider->getLastGoodRun()
                        ) {
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
        foreach ($file_list as $file_path) {
            if (!file_exists($file_path)) {
                continue;
            }

            if (!$config->isInProjectDirs($file_path)) {
                if ($this->debug_output) {
                    echo 'skipping ' . $file_path . PHP_EOL;
                }

                continue;
            }

            $this->files_to_report[$file_path] = $file_path;
            $this->files_to_deep_scan[$file_path] = $file_path;
            $this->files_to_scan[$file_path] = $file_path;
        }
    }

    /**
     * @param  string  $file_path
     *
     * @return void
     */
    public function checkFile($file_path)
    {
        if ($this->debug_output) {
            echo 'Checking ' . $file_path . PHP_EOL;
        }

        if (!$this->config) {
            throw new \UnexpectedValueException('Config should be set here');
        }

        $start_checks = (int)microtime(true);

        $this->config->hide_external_errors = $this->config->isInProjectDirs($file_path);

        $this->files_to_deep_scan[$file_path] = $file_path;
        $this->files_to_scan[$file_path] = $file_path;
        $this->files_to_report[$file_path] = $file_path;

        FileReferenceProvider::loadReferenceCache();

        $this->scanFiles();

        $this->analyzeFiles();

        IssueBuffer::finish($this, false, $start_checks, $this->scanned_files);
    }

    /**
     * @param  string $file_path
     * @param  array  $filetype_handlers
     * @param  bool   $will_analyze
     *
     * @return FileChecker
     */
    private function getFile($file_path, array $filetype_handlers, $will_analyze = false)
    {
        $extension = (string)pathinfo($file_path)['extension'];

        if (isset($filetype_handlers[$extension])) {
            /** @var FileChecker */
            $file_checker = new $filetype_handlers[$extension]($file_path, $this);
        } else {
            $file_checker = new FileChecker($file_path, $this, $will_analyze);
        }

        if ($this->debug_output) {
            echo 'Getting ' . $file_path . PHP_EOL;
        }

        return $file_checker;
    }

    /**
     * @param  string $file_path
     * @param  array  $filetype_handlers
     * @param  bool   $will_analyze
     *
     * @return FileChecker
     */
    private function scanFile($file_path, array $filetype_handlers, $will_analyze = false)
    {
        $path_parts = explode(DIRECTORY_SEPARATOR, $file_path);
        $file_name_parts = explode('.', array_pop($path_parts));
        $extension = count($file_name_parts) > 1 ? array_pop($file_name_parts) : null;

        if (isset($filetype_handlers[$extension])) {
            /** @var FileChecker */
            $file_checker = new $filetype_handlers[$extension]($file_path, $this);
        } else {
            $file_checker = new FileChecker($file_path, $this, $will_analyze);
        }

        if (isset($this->scanned_files[$file_path])) {
            throw new \UnexpectedValueException('Should not be rescanning ' . $file_path);
        }

        $this->file_storage_provider->create($file_path);

        if ($this->debug_output) {
            if (isset($this->files_to_deep_scan[$file_path])) {
                echo 'Deep scanning ' . $file_path . PHP_EOL;
            } else {
                echo 'Scanning ' . $file_path . PHP_EOL;
            }
        }

        $this->scanned_files[$file_path] = true;

        $file_checker->scan();

        return $file_checker;
    }

    /**
     * Checks whether a class exists, and if it does then records what file it's in
     * for later checking
     *
     * @param  string $fq_class_name
     *
     * @return bool
     */
    public function fileExistsForClassLike($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (isset($this->classlike_files[$fq_class_name_lc])) {
            return true;
        }

        if (isset($this->existing_classlikes_lc[$fq_class_name_lc])) {
            throw new \InvalidArgumentException('Why are you asking about a builtin class?');
        }

        if (!$this->config) {
            throw new \UnexpectedValueException('Config should be set here');
        }

        if ($this->composer_classmap === null) {
            $this->composer_classmap = $this->config->getComposerClassMap();
        }

        if (isset($this->composer_classmap[$fq_class_name_lc])) {
            if (file_exists($this->composer_classmap[$fq_class_name_lc])) {
                if ($this->debug_output) {
                    echo 'Using generated composer classmap to locate file for ' . $fq_class_name . PHP_EOL;
                }

                $this->existing_classlikes_lc[$fq_class_name_lc] = true;
                $this->existing_classlikes[$fq_class_name] = true;
                $this->classlike_files[$fq_class_name_lc] = $this->composer_classmap[$fq_class_name_lc];

                return true;
            }
        }

        $old_level = error_reporting();

        if (!$this->debug_output) {
            error_reporting(E_ERROR);
        }

        try {
            if ($this->debug_output) {
                echo 'Using reflection to locate file for ' . $fq_class_name . PHP_EOL;
            }

            $reflected_class = new \ReflectionClass($fq_class_name);
        } catch (\ReflectionException $e) {
            error_reporting($old_level);

            // do not cache any results here (as case-sensitive filenames can screw things up)

            return false;
        }

        error_reporting($old_level);

        /** @psalm-suppress MixedMethodCall due to Reflection class weirdness */
        $file_path = (string)$reflected_class->getFileName();

        // if the file was autoloaded but exists in evaled code only, return false
        if (!file_exists($file_path)) {
            return false;
        }

        $fq_class_name = $reflected_class->getName();
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_classlikes[$fq_class_name] = true;

        if ($reflected_class->isInterface()) {
            $this->addFullyQualifiedInterfaceName($fq_class_name, $file_path);
        } elseif ($reflected_class->isTrait()) {
            $this->addFullyQualifiedTraitName($fq_class_name, $file_path);
        } else {
            $this->addFullyQualifiedClassName($fq_class_name, $file_path);
        }

        return true;
    }

    /**
     * @return void
     */
    public function enableCache()
    {
        $this->cache = true;
    }

    /**
     * @return void
     */
    public function disableCache()
    {
        $this->cache = false;
    }

    /**
     * @return bool
     */
    public function canCache()
    {
        return $this->cache;
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

        $appearing_method_id = MethodChecker::getAppearingMethodId($this, $original_method_id);

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

        $stmts = $file_checker->getStatements();

        $file_checker->populateCheckers($stmts);

        if (!$this_context->self) {
            $this_context->self = $fq_class_name;
            $this_context->vars_in_scope['$this'] = Type::parseString($fq_class_name);
        }

        $file_checker->getMethodMutations($appearing_method_id, $this_context);
    }

    /**
     * @param  string $fq_class_name
     *
     * @return FileChecker
     */
    private function getFileCheckerForClassLike($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        // this registers the class if it's not user defined
        if (!$this->fileExistsForClassLike($fq_class_name)) {
            throw new \UnexpectedValueException('File does not exist for ' . $fq_class_name);
        }

        if (!isset($this->classlike_files[$fq_class_name_lc])) {
            throw new \UnexpectedValueException('Class ' . $fq_class_name . ' is not user-defined');
        }

        $file_path = $this->classlike_files[$fq_class_name_lc];

        if ($this->cache && isset($this->file_checkers[$file_path])) {
            return $this->file_checkers[$file_path];
        }

        $file_checker = new FileChecker($file_path, $this, true);

        if ($this->cache) {
            $this->file_checkers[$file_path] = $file_checker;
        }

        return $file_checker;
    }

    /**
     * Gets a Config object from an XML file.
     *
     * Searches up a folder hierarchy for the most immediate config.
     *
     * @param  string $path
     * @param  string $base_dir
     *
     * @throws Exception\ConfigException if a config path is not found
     *
     * @return Config
     */
    public function getConfigForPath($path, $base_dir)
    {
        $dir_path = realpath($path);

        if ($dir_path === false) {
            throw new Exception\ConfigException('Config not found for path ' . $path);
        }

        if (!is_dir($dir_path)) {
            $dir_path = dirname($dir_path);
        }

        $config = null;

        do {
            $maybe_path = $dir_path . DIRECTORY_SEPARATOR . Config::DEFAULT_FILE_NAME;

            if (file_exists($maybe_path)) {
                $config = Config::loadFromXMLFile($maybe_path, $base_dir);

                break;
            }

            $dir_path = dirname($dir_path);
        } while (dirname($dir_path) !== $dir_path);

        if (!$config) {
            if ($this->output_format === self::TYPE_CONSOLE) {
                exit(
                    'Could not locate a config XML file in path ' . $path . '. Have you run \'psalm --init\' ?' .
                    PHP_EOL
                );
            }

            throw new Exception\ConfigException('Config not found for path ' . $path);
        }

        $this->config = $config;

        $this->cache_provider->use_igbinary = $config->use_igbinary;

        $config->visitStubFiles($this);
        $config->visitComposerAutoloadFiles($this);
        $config->initializePlugins($this);

        return $config;
    }

    /**
     * @return ?Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param  Config $config
     *
     * @return void
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        $this->cache_provider->use_igbinary = $config->use_igbinary;

        $config->visitStubFiles($this);
        $config->visitComposerAutoloadFiles($this);
        $config->initializePlugins($this);
    }

    /**
     * @param string $path_to_config
     * @param string $base_dir
     *
     * @throws Exception\ConfigException if a config file is not found in the given location
     *
     * @return void
     */
    public function setConfigXML($path_to_config, $base_dir)
    {
        if (!file_exists($path_to_config)) {
            throw new Exception\ConfigException('Config not found at location ' . $path_to_config);
        }

        $this->config = Config::loadFromXMLFile($path_to_config, $base_dir);

        $this->config->visitStubFiles($this);
        $this->config->visitComposerAutoloadFiles($this);
        $this->config->initializePlugins($this);
    }

    /**
     * @param  array<string>  $diff_files
     *
     * @return array<string>
     */
    public static function getReferencedFilesFromDiff(array $diff_files)
    {
        $all_inherited_files_to_check = $diff_files;

        while ($diff_files) {
            $diff_file = array_shift($diff_files);

            $dependent_files = FileReferenceProvider::getFilesInheritingFromFile($diff_file);
            $new_dependent_files = array_diff($dependent_files, $all_inherited_files_to_check);

            $all_inherited_files_to_check += $new_dependent_files;
            $diff_files += $new_dependent_files;
        }

        $all_files_to_check = $all_inherited_files_to_check;

        foreach ($all_inherited_files_to_check as $file_name) {
            $dependent_files = FileReferenceProvider::getFilesReferencingFile($file_name);
            $all_files_to_check = array_merge($dependent_files, $all_files_to_check);
        }

        return array_unique($all_files_to_check);
    }

    /**
     * @param  string $file_path
     *
     * @return void
     */
    public function registerAnalyzableFile($file_path)
    {
        $this->files_to_deep_scan[$file_path] = $file_path;
        $this->files_to_report[$file_path] = $file_path;
    }

    /**
     * @param  string $file_path
     *
     * @return array<int, \PhpParser\Node\Stmt>
     */
    public function getStatementsForFile($file_path)
    {
        return StatementsProvider::getStatementsForFile(
            $file_path,
            $this->file_provider,
            $this->cache_provider,
            $this->debug_output
        );
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
     * @param  string $file_path
     *
     * @return string
     */
    public function getFileContents($file_path)
    {
        return $this->file_provider->getContents($file_path);
    }

    /**
     * @param string        $fq_class_name
     * @param string|null   $file_path
     *
     * @return void
     */
    public function addFullyQualifiedClassName($fq_class_name, $file_path = null)
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_classes_lc[$fq_class_name_lc] = true;
        $this->existing_traits_lc[$fq_class_name_lc] = false;
        $this->existing_interfaces_lc[$fq_class_name_lc] = false;
        $this->existing_classes[$fq_class_name] = true;

        if ($file_path) {
            $this->classlike_files[$fq_class_name_lc] = $file_path;
        }
    }

    /**
     * @param string        $fq_class_name
     * @param string|null   $file_path
     *
     * @return void
     */
    public function addFullyQualifiedInterfaceName($fq_class_name, $file_path = null)
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_interfaces_lc[$fq_class_name_lc] = true;
        $this->existing_classes_lc[$fq_class_name_lc] = false;
        $this->existing_traits_lc[$fq_class_name_lc] = false;
        $this->existing_interfaces[$fq_class_name] = true;

        if ($file_path) {
            $this->classlike_files[$fq_class_name_lc] = $file_path;
        }
    }

    /**
     * @param string        $fq_class_name
     * @param string|null   $file_path
     *
     * @return void
     */
    public function addFullyQualifiedTraitName($fq_class_name, $file_path = null)
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_traits_lc[$fq_class_name_lc] = true;
        $this->existing_classes_lc[$fq_class_name_lc] = false;
        $this->existing_interfaces_lc[$fq_class_name_lc] = false;
        $this->existing_traits[$fq_class_name] = true;

        if ($file_path) {
            $this->classlike_files[$fq_class_name_lc] = $file_path;
        }
    }

    /**
     * @param string $fq_class_name
     *
     * @return bool
     */
    public function hasFullyQualifiedClassName($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (!isset($this->existing_classes_lc[$fq_class_name_lc])
            || !$this->existing_classes_lc[$fq_class_name_lc]
            || !$this->classlike_storage_provider->has($fq_class_name_lc)
        ) {
            if ((
                !isset($this->existing_classes_lc[$fq_class_name_lc])
                    || $this->existing_classes_lc[$fq_class_name_lc] === true
                )
                && !$this->classlike_storage_provider->has($fq_class_name_lc)
            ) {
                if ($this->debug_output) {
                    echo 'Last-chance attempt to hydrate ' . $fq_class_name . PHP_EOL;
                }
                // attempt to load in the class
                $this->queueClassLikeForScanning($fq_class_name);
                $this->scanFiles();

                if (!isset($this->existing_classes_lc[$fq_class_name_lc])) {
                    $this->existing_classes_lc[$fq_class_name_lc] = false;

                    return false;
                }

                return $this->existing_classes_lc[$fq_class_name_lc];
            }

            return false;
        }

        if ($this->collect_references) {
            if (!isset($this->classlike_references[$fq_class_name_lc])) {
                $this->classlike_references[$fq_class_name_lc] = 0;
            }

            ++$this->classlike_references[$fq_class_name_lc];
        }

        return true;
    }

    /**
     * @param string $fq_class_name
     *
     * @return bool
     */
    public function hasFullyQualifiedInterfaceName($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (!isset($this->existing_interfaces_lc[$fq_class_name_lc])
            || !$this->existing_interfaces_lc[$fq_class_name_lc]
            || !$this->classlike_storage_provider->has($fq_class_name_lc)
        ) {
            if ((
                !isset($this->existing_classes_lc[$fq_class_name_lc])
                    || $this->existing_classes_lc[$fq_class_name_lc] === true
                )
                && !$this->classlike_storage_provider->has($fq_class_name_lc)
            ) {
                if ($this->debug_output) {
                    echo 'Last-chance attempt to hydrate ' . $fq_class_name . PHP_EOL;
                }

                // attempt to load in the class
                $this->queueClassLikeForScanning($fq_class_name);
                $this->scanFiles();

                if (!isset($this->existing_interfaces_lc[$fq_class_name_lc])) {
                    $this->existing_interfaces_lc[$fq_class_name_lc] = false;

                    return false;
                }

                return $this->existing_interfaces_lc[$fq_class_name_lc];
            }

            return false;
        }

        if ($this->collect_references) {
            if (!isset($this->classlike_references[$fq_class_name_lc])) {
                $this->classlike_references[$fq_class_name_lc] = 0;
            }

            ++$this->classlike_references[$fq_class_name_lc];
        }

        return true;
    }

    /**
     * @param string $fq_class_name
     *
     * @return bool
     */
    public function hasFullyQualifiedTraitName($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (!isset($this->existing_traits_lc[$fq_class_name_lc]) ||
            !$this->existing_traits_lc[$fq_class_name_lc]
        ) {
            return false;
        }

        if ($this->collect_references) {
            if (!isset($this->classlike_references[$fq_class_name_lc])) {
                $this->classlike_references[$fq_class_name_lc] = 0;
            }

            ++$this->classlike_references[$fq_class_name_lc];
        }

        return true;
    }

    /**
     * @param  string $file_path
     *
     * @return bool
     */
    public function canReportIssues($file_path)
    {
        return isset($this->files_to_report[$file_path]);
    }

    /**
     * @return void
     */
    public function collectPredefinedClassLikes()
    {
        /** @var array<int, string> */
        $predefined_classes = get_declared_classes();

        foreach ($predefined_classes as $predefined_class) {
            $reflection_class = new \ReflectionClass($predefined_class);

            if (!$reflection_class->isUserDefined()) {
                $predefined_class_lc = strtolower($predefined_class);
                $this->existing_classlikes_lc[$predefined_class_lc] = true;
                $this->existing_classes_lc[$predefined_class_lc] = true;
            }
        }

        /** @var array<int, string> */
        $predefined_interfaces = get_declared_interfaces();

        foreach ($predefined_interfaces as $predefined_interface) {
            $reflection_class = new \ReflectionClass($predefined_interface);

            if (!$reflection_class->isUserDefined()) {
                $predefined_interface_lc = strtolower($predefined_interface);
                $this->existing_classlikes_lc[$predefined_interface_lc] = true;
                $this->existing_interfaces_lc[$predefined_interface_lc] = true;
            }
        }
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
}
