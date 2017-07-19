<?php
namespace Psalm\Checker;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception;
use Psalm\Exception\CircularReferenceException;
use Psalm\Issue\PossiblyUnusedMethod;
use Psalm\Issue\UnusedClass;
use Psalm\Issue\UnusedMethod;
use Psalm\IssueBuffer;
use Psalm\Provider\CacheProvider;
use Psalm\Provider\FileProvider;
use Psalm\Provider\FileReferenceProvider;
use Psalm\Provider\StatementsProvider;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\PropertyStorage;
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
    public $update_docblocks = false;

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

    /**
     * Whether to log functions just at the file level or globally (for stubs)
     *
     * @var bool
     */
    public $register_global_functions = false;

    const TYPE_CONSOLE = 'console';
    const TYPE_JSON = 'json';
    const TYPE_EMACS = 'emacs';

    /**
     * @param bool $use_color
     * @param bool $show_info
     * @param bool $debug_output
     * @param string  $output_format
     * @param bool    $update_docblocks
     * @param bool    $collect_references
     * @param string  $find_references_to
     */
    public function __construct(
        FileProvider $file_provider,
        $use_color = true,
        $show_info = true,
        $output_format = self::TYPE_CONSOLE,
        $debug_output = false,
        $update_docblocks = false,
        $collect_references = false,
        $find_references_to = null
    ) {
        $this->file_provider = $file_provider;
        $this->use_color = $use_color;
        $this->show_info = $show_info;
        $this->debug_output = $debug_output;
        $this->update_docblocks = $update_docblocks;
        $this->collect_references = $collect_references;
        $this->find_references_to = $find_references_to;

        if (!in_array($output_format, [self::TYPE_CONSOLE, self::TYPE_JSON, self::TYPE_EMACS], true)) {
            throw new \UnexpectedValueException('Unrecognised output format ' . $output_format);
        }

        $this->output_format = $output_format;
        self::$instance = $this;

        $this->collectPredefinedClassLikes();
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

        if (!$this->config) {
            $this->config = $this->getConfigForPath($base_dir, $base_dir);
        }

        $diff_files = null;
        $deleted_files = null;

        if ($is_diff && FileReferenceProvider::loadReferenceCache() && CacheProvider::canDiffFiles()) {
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

        $removed_parser_files = CacheProvider::deleteOldParserCaches(
            $is_diff ? CacheProvider::getLastGoodRun() : $start_checks
        );

        if ($this->debug_output && $removed_parser_files) {
            echo 'Removed ' . $removed_parser_files . ' old parser caches' . PHP_EOL;
        }

        if ($is_diff) {
            CacheProvider::touchParserCaches($this->getAllFiles($this->config), $start_checks);
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
                            ($this->use_color
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

        IssueBuffer::finish(true, (int)$start_checks, $this->scanned_files);
    }

    /**
     * @return void
     * @psalm-suppress ParadoxicalCondition
     */
    public function scanFiles()
    {
        if (!$this->config) {
            throw new \UnexpectedValueException('$this->config cannot be null');
        }

        $filetype_handlers = $this->config->getFiletypeHandlers();

        while ($this->files_to_scan || $this->classes_to_scan) {
            if ($this->files_to_scan) {
                $file_path = array_shift($this->files_to_scan);

                if (!isset($this->scanned_files[$file_path])) {
                    $this->scanFile($file_path, $filetype_handlers, isset($this->files_to_deep_scan[$file_path]));
                }
            } else {
                $fq_classlike_name = array_shift($this->classes_to_scan);
                $fq_classlike_name_lc = strtolower($fq_classlike_name);

                if (isset($this->reflected_classeslikes_lc[$fq_classlike_name_lc])) {
                    continue;
                }

                if (!isset($this->classlike_files[$fq_classlike_name_lc])) {
                    if (isset($this->existing_classlikes_lc[$fq_classlike_name_lc])) {
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
                        } else {

                        }
                    }
                }
            }
        }

        if ($this->debug_output) {
            echo 'ClassLikeStorage is populating' . PHP_EOL;
        }

        foreach (ClassLikeChecker::$storage as $storage) {
            if (!$storage->user_defined) {
                continue;
            }

            $this->populateClassLikeStorage($storage);
        }

        if ($this->debug_output) {
            echo 'ClassLikeStorage is populated' . PHP_EOL;
        }
    }

    /**
     * @param  ClassLikeStorage $storage
     * @param  array            $dependent_classlikes
     * @return void
     */
    private function populateClassLikeStorage(ClassLikeStorage $storage, $dependent_classlikes = [])
    {
        if ($storage->populated) {
            return;
        }

        if (isset($dependent_classlikes[strtolower($storage->name)])) {
            throw new CircularReferenceException(
                'Circular reference discovered when loading ' . $storage->name
            );
        }

        $dependent_classlikes[strtolower($storage->name)] = true;

        if (isset($storage->parent_classes[0]) && isset(ClassLikeChecker::$storage[$storage->parent_classes[0]])) {
            $parent_storage = ClassLikeChecker::$storage[$storage->parent_classes[0]];

            $this->populateClassLikeStorage($parent_storage, $dependent_classlikes);

            $storage->parent_classes = array_merge($storage->parent_classes, $parent_storage->parent_classes);

            $this->inheritMethodsFromParent($storage, $parent_storage);
            $this->inheritPropertiesFromParent($storage, $parent_storage);

            $storage->class_implements += $parent_storage->class_implements;

            $storage->public_class_constants += $parent_storage->public_class_constants;
            $storage->protected_class_constants += $parent_storage->protected_class_constants;
        }

        $parent_interfaces = [];

        foreach ($storage->parent_interfaces as $parent_interface_lc => $_) {
            $parent_interface_storage = ClassLikeChecker::$storage[$parent_interface_lc];

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
            if (!isset(ClassLikeChecker::$storage[$implemented_interface_lc])) {
                continue;
            }
            $implemented_interface_storage = ClassLikeChecker::$storage[$implemented_interface_lc];

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
            if (!isset(ClassLikeChecker::$storage[strtolower($implemented_interface)])) {
                continue;
            }

            $implemented_interface_storage = ClassLikeChecker::$storage[strtolower($implemented_interface)];

            foreach ($implemented_interface_storage->methods as $method_name => $method) {
                if ($method->visibility === ClassLikeChecker::VISIBILITY_PUBLIC) {
                    $mentioned_method_id = $implemented_interface . '::' . $method_name;
                    $implemented_method_id = $storage->name . '::' . $method_name;

                    MethodChecker::setOverriddenMethodId($implemented_method_id, $mentioned_method_id);
                }
            }
        }

        foreach ($storage->used_traits as $used_trait_lc => $used_trait) {
            if (!isset(ClassLikeChecker::$storage[$used_trait_lc])) {
                continue;
            }

            $trait_storage = ClassLikeChecker::$storage[$used_trait_lc];

            $this->populateClassLikeStorage($trait_storage, $dependent_classlikes);

            $this->inheritMethodsFromParent($storage, $trait_storage);
            $this->inheritPropertiesFromParent($storage, $trait_storage);
        }

        $storage->populated = true;
    }

    /**
     * @param string $fq_class_name
     * @param string $parent_class
     *
     * @return void
     */
    protected static function inheritMethodsFromParent(ClassLikeStorage $storage, ClassLikeStorage $parent_storage)
    {
        $fq_class_name = $storage->name;
        $parent_class = $parent_storage->name;

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            if (isset($storage->appearing_method_ids[$method_name])) {
                continue;
            }

            $parent_method_id = $parent_class . '::' . $method_name;

            $implemented_method_id = $fq_class_name . '::' . $method_name;

            $storage->appearing_method_ids[$method_name] =
                $parent_storage->is_trait ? $implemented_method_id : $appearing_method_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_method_ids as $method_name => $declaring_method_id) {
            if (!$parent_storage->is_trait) {
                $implemented_method_id = $fq_class_name . '::' . $method_name;

                MethodChecker::setOverriddenMethodId($implemented_method_id, $declaring_method_id);
            }

            if (isset($storage->declaring_method_ids[$method_name])) {
                continue;
            }

            $parent_method_id = $parent_class . '::' . $method_name;

            $storage->declaring_method_ids[$method_name] = $declaring_method_id;
        }
    }

    /**
     * @param string $fq_class_name
     * @param string $parent_class
     *
     * @return void
     */
    protected static function inheritPropertiesFromParent(ClassLikeStorage $storage, ClassLikeStorage $parent_storage)
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

            $storage->appearing_property_ids[$property_name] = $appearing_property_id;
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
     * @param  boolean $analyze_too
     * @return void
     */
    public function queueClassLikeForScanning($fq_classlike_name, $analyze_too = false)
    {
        if (!$this->config) {
            throw new \UnexpectedValueException('Config should not be null here');
        }

        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        if (!isset($this->classlike_files[$fq_classlike_name_lc])) {
            $this->classes_to_scan[$fq_classlike_name_lc] = $fq_classlike_name;

            if ($analyze_too) {
                $this->classes_to_deep_scan[$fq_classlike_name_lc] = true;
            }
        }
    }

    /**
     * @param  string $file_path
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
             */
            function ($i, $file_path) use ($filetype_handlers) {
                $file_checker = $this->getFile($file_path, $filetype_handlers, true);

                if ($this->debug_output) {
                    echo 'Analyzing ' . $file_checker->getFilePath() . PHP_EOL;
                }

                $file_checker->analyze(null, $this->update_docblocks);
            };

        $pool_size = 4;

        if (true && count($this->files_to_report) > $pool_size) {
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
                    return IssueBuffer::getIssuesData();
                }
            );

            // Wait for all tasks to complete and collect the results.
            /**
             * @var array<array<int, array{severity: string, line_number: string, type: string, message: string,
             *  file_name: string, file_path: string, snippet: string, from: int, to: int, snippet_from: int,
             *  snippet_to: int, column: int}>>
             */
            $forked_issues_data = $pool->wait();

            foreach ($forked_issues_data as $issues_data) {
                IssueBuffer::addIssues($issues_data);
            }

            $did_fork_pool_have_error = $pool->didHaveError();
        } else {
            $i = 0;

            foreach ($this->files_to_report as $file_path => $_) {
                $analysis_worker($i, $file_path);
                ++$i;
            }
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

        if (!isset(ClassLikeChecker::$storage[strtolower($fq_class_name)])) {
            die('Class ' . $fq_class_name . ' cannot be found' . PHP_EOL);
        }

        $class_storage = ClassLikeChecker::$storage[strtolower($fq_class_name)];

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
        if (!isset(ClassLikeChecker::$storage[strtolower($fq_class_name)])) {
            die('Class ' . $fq_class_name . ' cannot be found' . PHP_EOL);
        }

        $class_storage = ClassLikeChecker::$storage[strtolower($fq_class_name)];

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
            if (isset(ClassLikeChecker::$storage[$fq_class_name_lc])) {
                $classlike_storage = ClassLikeChecker::$storage[$fq_class_name_lc];

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
                        self::checkMethodReferences($classlike_storage);
                    }
                }
            }
        }
    }

    /**
     * @param  \Psalm\Storage\ClassLikeStorage  $classlike_storage
     *
     * @return void
     */
    protected static function checkMethodReferences($classlike_storage)
    {
        foreach ($classlike_storage->methods as $method_name => $method_storage) {
            if (count($method_storage->referencing_locations) === 0 &&
                !$classlike_storage->overridden_method_ids[$method_name] &&
                (substr($method_name, 0, 2) !== '__' || $method_name === '__construct') &&
                $method_storage->location
            ) {
                $method_id = $classlike_storage->name . '::' . $method_storage->cased_name;

                if ($method_storage->visibility === ClassLikeChecker::VISIBILITY_PUBLIC) {
                    if (IssueBuffer::accepts(
                        new PossiblyUnusedMethod(
                            'Cannot find public calls to method ' . $method_id,
                            $method_storage->location
                        )
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UnusedMethod(
                            'Method ' . $method_id . ' is never used',
                            $method_storage->location
                        )
                    )) {
                        // fall through
                    }
                }
            }
        }
    }

    /**
     * @param  string  $dir_name
     * @param  string  $base_dir
     *
     * @return void
     */
    public function checkDir($dir_name, $base_dir)
    {
        if (!$this->config) {
            $this->config = $this->getConfigForPath($dir_name, $base_dir);
            $this->config->hide_external_errors = $this->config->isInProjectDirs($dir_name . DIRECTORY_SEPARATOR);
        }

        FileReferenceProvider::loadReferenceCache();

        $start_checks = (int)microtime(true);

        $this->checkDirWithConfig($dir_name, $this->config, true);

        $this->scanFiles();
        $this->analyzeFiles();

        IssueBuffer::finish(false, $start_checks, $this->scanned_files);
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
        $filetype_handlers = $config->getFiletypeHandlers();

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_name));
        $iterator->rewind();

        $diff_files = [];

        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                $extension = $iterator->getExtension();
                if (in_array($extension, $file_extensions, true)) {
                    $file_name = (string)$iterator->getRealPath();

                    if ($config->isInProjectDirs($file_name)) {
                        if ($this->file_provider->hasFileChanged($file_name)) {
                            $diff_files[] = $file_name;
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

            $this->files_to_deep_scan[$file_path] = $file_path;
            $this->files_to_report[$file_path] = $file_path;
        }
    }

    /**
     * @param  string  $file_path
     * @param  string  $base_dir
     *
     * @return void
     */
    public function checkFile($file_path, $base_dir)
    {
        if ($this->debug_output) {
            echo 'Checking ' . $file_path . PHP_EOL;
        }

        if (!$this->config) {
            $this->config = $this->getConfigForPath($file_path, $base_dir);
        }

        $start_checks = (int)microtime(true);

        $this->config->hide_external_errors = $this->config->isInProjectDirs($file_path);

        $this->files_to_deep_scan[$file_path] = $file_path;
        $this->files_to_scan[$file_path] = $file_path;
        $this->files_to_report[$file_path] = $file_path;

        $filetype_handlers = $this->config->getFiletypeHandlers();

        FileReferenceProvider::loadReferenceCache();

        $this->scanFiles();

        $this->analyzeFiles();

        IssueBuffer::finish(false, $start_checks, $this->scanned_files);
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
        $extension = count($file_name_parts > 1) ? array_pop($file_name_parts) : null;

        if (isset($filetype_handlers[$extension])) {
            /** @var FileChecker */
            $file_checker = new $filetype_handlers[$extension]($file_path, $this);
        } else {
            $file_checker = new FileChecker($file_path, $this, $will_analyze);
        }

        if (isset($this->scanned_files[$file_path])) {
            throw new \UnexpectedValueException('Should not be rescanning ' . $file_path);
        }

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
     * @psalm-suppress MixedMethodCall due to Reflection class weirdness
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

        $old_level = error_reporting();

        if (!$this->debug_output) {
            error_reporting(E_ERROR);
        }

        try {
            $reflected_class = new \ReflectionClass($fq_class_name);
        } catch (\ReflectionException $e) {
            error_reporting($old_level);

            // do not cache any results here (as case-sensitive filenames can screw things up)

            return false;
        }

        error_reporting($old_level);

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

        $appearing_method_id = (string)MethodChecker::getAppearingMethodId($original_method_id);
        list($appearing_fq_class_name) = explode('::', $appearing_method_id);

        $appearing_class_storage = ClassLikeChecker::$storage[strtolower($appearing_fq_class_name)];

        if (!$appearing_class_storage->user_defined) {
            return;
        }

        if (strtolower($appearing_fq_class_name) !== strtolower($fq_class_name)) {
            $file_checker = $this->getFileCheckerForClassLike($appearing_fq_class_name);
        }

        $file_checker->analyze(null, false, true);

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
    private function getConfigForPath($path, $base_dir)
    {
        $dir_path = realpath($path);

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

        $config->visitStubFiles($this);
        $config->initializePlugins($this);

        return $config;
    }

    /**
     * @param  Config $config
     *
     * @return void
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        $config->visitStubFiles($this);
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
     * @return array<int, \PhpParser\Node\Stmt>
     */
    public function getStatementsForFile($file_path)
    {
        return StatementsProvider::getStatementsForFile(
            $file_path,
            $this->file_provider->getContents($file_path),
            $this->file_provider->getModifiedTime($file_path),
            $this->debug_output
        );
    }

    /**
     * @param  string $file_path
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

        if (!isset($this->existing_classes_lc[$fq_class_name_lc]) ||
            !$this->existing_classes_lc[$fq_class_name_lc]
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
     * @param string $fq_class_name
     *
     * @return bool
     */
    public function hasFullyQualifiedInterfaceName($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (!isset($this->existing_interfaces_lc[$fq_class_name_lc]) ||
            !$this->existing_interfaces_lc[$fq_class_name_lc]
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
}
