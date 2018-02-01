<?php
namespace Psalm;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\FileChecker;
use Psalm\Checker\FunctionChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\FileManipulation\FileManipulation;
use Psalm\FileManipulation\FileManipulationBuffer;
use Psalm\FileManipulation\FunctionDocblockManipulator;
use Psalm\Issue\CircularReference;
use Psalm\Issue\PossiblyUnusedMethod;
use Psalm\Issue\PossiblyUnusedParam;
use Psalm\Issue\PossiblyUnusedProperty;
use Psalm\Issue\UnusedClass;
use Psalm\Issue\UnusedMethod;
use Psalm\Issue\UnusedProperty;
use Psalm\Provider\ClassLikeStorageProvider;
use Psalm\Provider\FileProvider;
use Psalm\Provider\FileReferenceProvider;
use Psalm\Provider\FileStorageProvider;
use Psalm\Provider\StatementsProvider;
use Psalm\Scanner\FileScanner;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;

class Codebase
{
    /**
     * @var Config;
     */
    public $config;

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
    private $existing_classes = [];

    /**
     * @var array<string, bool>
     */
    private $existing_interfaces_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_interfaces = [];

    /**
     * @var array<string, bool>
     */
    private $existing_traits_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_traits = [];

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
     * @var array<string, FileChecker>
     */
    private $file_checkers = [];

    /**
     * @var array<string, MethodChecker>
     */
    private $method_checkers = [];

    /**
     * @var array<string, int>
     */
    private $classlike_references = [];

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
     * @var FileStorageProvider
     */
    public $file_storage_provider;

    /**
     * @var ClassLikeStorageProvider
     */
    public $classlike_storage_provider;

    /**
     * @var bool
     */
    public $collect_references = false;

    /**
     * @var null|array<string, string>
     */
    private $composer_classmap;

    /**
     * @var FileProvider
     */
    private $file_provider;

    /**
     * @var StatementsProvider
     */
    private $statements_provider;

    /**
     * @var array<string, FunctionLikeStorage>
     */
    private static $stubbed_functions;

    /**
     * @var array<string, PhpParser\Node\Stmt\Trait_>
     */
    private $trait_nodes = [];

    /**
     * @var array<string, Aliases>
     */
    private $trait_aliases = [];

    /**
     * @var bool
     */
    private $cache = false;

    /**
     * @var bool
     */
    private $debug_output = false;

    /**
     * @var array<string, Type\Union>
     */
    private static $stubbed_constants = [];

    /**
     * Whether to log functions just at the file level or globally (for stubs)
     *
     * @var bool
     */
    public $register_global_functions = false;

    /**
     * @var Codebase\Reflection
     */
    private $reflection;

    /**
     * Used to store counts of mixed vs non-mixed variables
     *
     * @var array<string, array{0: int, 1: int}
     */
    private $mixed_counts = [];

    /**
     * @var bool
     */
    private $count_mixed = true;

    /**
     * @param bool $collect_references
     * @param bool $debug_output
     */
    public function __construct(
        Config $config,
        FileStorageProvider $file_storage_provider,
        ClassLikeStorageProvider $classlike_storage_provider,
        FileProvider $file_provider,
        StatementsProvider $statements_provider,
        $collect_references = false,
        $debug_output = false
    ) {
        $this->config = $config;
        $this->file_storage_provider = $file_storage_provider;
        $this->classlike_storage_provider = $classlike_storage_provider;
        $this->debug_output = $debug_output;
        $this->file_provider = $file_provider;
        $this->statements_provider = $statements_provider;
        $this->debug_output = $debug_output;
        $this->collect_references = $collect_references;

        self::$stubbed_functions = [];
        self::$stubbed_constants = [];

        $this->reflection = new Codebase\Reflection($classlike_storage_provider, $this);

        $this->collectPredefinedClassLikes();
    }

    /**
     * @param array<string, string> $files_to_scan
     *
     * @return void
     */
    public function addFilesToScan(array $files_to_scan)
    {
        $this->files_to_scan += $files_to_scan;
        $this->files_to_deep_scan += $files_to_scan;
        $this->files_to_report += $files_to_scan;
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
    public function scanFiles()
    {
        $filetype_scanners = $this->config->getFiletypeScanners();

        $has_changes = false;

        while ($this->files_to_scan || $this->classes_to_scan) {
            if ($this->files_to_scan) {
                $file_path = array_shift($this->files_to_scan);

                if (!isset($this->scanned_files[$file_path])) {
                    $this->scanFile(
                        $file_path,
                        $filetype_scanners,
                        isset($this->files_to_deep_scan[$file_path])
                    );
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
                        $this->reflection->registerClass($reflected_class);
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

        foreach ($this->classlike_storage_provider->getAll() as $class_storage) {
            if (!$class_storage->user_defined && !$class_storage->stubbed) {
                continue;
            }

            $this->populateClassLikeStorage($class_storage);
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

        if ($this->config->allow_phpstorm_generics) {
            foreach ($this->classlike_storage_provider->getAll() as $class_storage) {
                foreach ($class_storage->properties as $property_storage) {
                    if ($property_storage->type) {
                        $this->convertPhpStormGenericToPsalmGeneric($property_storage->type, true);
                    }
                }

                foreach ($class_storage->methods as $method_storage) {
                    if ($method_storage->return_type) {
                        $this->convertPhpStormGenericToPsalmGeneric($method_storage->return_type);
                    }

                    foreach ($method_storage->params as $param_storage) {
                        if ($param_storage->type) {
                            $this->convertPhpStormGenericToPsalmGeneric($param_storage->type);
                        }
                    }
                }
            }

            foreach ($all_file_storage as $file_storage) {
                foreach ($file_storage->functions as $function_storage) {
                    if ($function_storage->return_type) {
                        $this->convertPhpStormGenericToPsalmGeneric($function_storage->return_type);
                    }

                    foreach ($function_storage->params as $param_storage) {
                        if ($param_storage->type) {
                            $this->convertPhpStormGenericToPsalmGeneric($param_storage->type);
                        }
                    }
                }
            }
        }

        if ($this->debug_output) {
            echo 'FileStorage is populated' . PHP_EOL;
        }
    }

    /**
     * @return array<string, bool>
     */
    public function getScannedFiles()
    {
        return $this->scanned_files;
    }

    /**
     * @return void
     */
    public function enableCheckerCache()
    {
        $this->cache = true;
    }

    /**
     * @return void
     */
    public function disableCheckerCache()
    {
        $this->cache = false;
    }

    /**
     * @return bool
     */
    public function canCacheCheckers()
    {
        return $this->cache;
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

        $fq_classlike_name_lc = strtolower($storage->name);

        if (isset($dependent_classlikes[$fq_classlike_name_lc])) {
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

        foreach ($storage->used_traits as $used_trait_lc => $_) {
            try {
                $trait_storage = $storage_provider->get($used_trait_lc);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $this->populateClassLikeStorage($trait_storage, $dependent_classlikes);

            $this->inheritMethodsFromParent($storage, $trait_storage);
            $this->inheritPropertiesFromParent($storage, $trait_storage);
        }

        $dependent_classlikes[$fq_classlike_name_lc] = true;

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

        $interface_method_implementers = [];

        foreach ($storage->class_implements as $implemented_interface) {
            try {
                $implemented_interface_storage = $storage_provider->get($implemented_interface);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            foreach ($implemented_interface_storage->methods as $method_name => $method) {
                if ($method->visibility === ClassLikeChecker::VISIBILITY_PUBLIC) {
                    $mentioned_method_id = $implemented_interface . '::' . $method_name;
                    $interface_method_implementers[$method_name][] = $mentioned_method_id;
                }
            }
        }

        foreach ($interface_method_implementers as $method_name => $interface_method_ids) {
            if (count($interface_method_ids) === 1) {
                MethodChecker::setOverriddenMethodId(
                    $this->classlike_storage_provider,
                    $storage->name . '::' . $method_name,
                    $interface_method_ids[0]
                );
            } else {
                $storage->interface_method_ids[$method_name] = $interface_method_ids;
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

        $file_path_lc = strtolower($storage->file_path);

        if (isset($dependent_file_paths[$file_path_lc])) {
            return;
        }

        $dependent_file_paths[$file_path_lc] = true;

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
     * @param  Type\Union $candidate
     * @param  bool       $is_property
     *
     * @return void
     */
    private function convertPhpStormGenericToPsalmGeneric(Type\Union $candidate, $is_property = false)
    {
        $atomic_types = $candidate->getTypes();

        if (isset($atomic_types['array']) && count($atomic_types) > 1) {
            $iterator_name = null;
            $generic_params = null;

            foreach ($atomic_types as $type) {
                if ($type instanceof Type\Atomic\TNamedObject
                    && (!$type->from_docblock || $is_property)
                    && (
                        strtolower($type->value) === 'traversable'
                        || $this->interfaceExtends(
                            $type->value,
                            'Traversable'
                        )
                        || $this->classImplements(
                            $type->value,
                            'Traversable'
                        )
                    )
                ) {
                    $iterator_name = $type->value;
                } elseif ($type instanceof Type\Atomic\TArray) {
                    $generic_params = $type->type_params;
                }
            }

            if ($iterator_name && $generic_params) {
                $generic_iterator = new Type\Atomic\TGenericObject($iterator_name, $generic_params);
                $candidate->removeType('array');
                $candidate->addType($generic_iterator);
            }
        }
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

                MethodChecker::setOverriddenMethodId(
                    $this->classlike_storage_provider,
                    $implemented_method_id,
                    $declaring_method_id
                );
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
    private function inheritPropertiesFromParent(ClassLikeStorage $storage, ClassLikeStorage $parent_storage)
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
     * @param  ProjectChecker $project_checker
     * @param  int            $pool_size
     * @param  bool           $alter_code
     *
     * @return void
     */
    public function analyzeFiles(ProjectChecker $project_checker, $pool_size, $alter_code)
    {
        $filetype_checkers = $this->config->getFiletypeCheckers();

        $analysis_worker =
            /**
             * @param int $i
             * @param string $file_path
             *
             * @return void
             *
             * @psalm-suppress UnusedParam
             */
            function ($i, $file_path) use ($project_checker, $filetype_checkers) {
                $file_checker = $this->getFile($project_checker, $file_path, $filetype_checkers);

                if ($this->debug_output) {
                    echo 'Analyzing ' . $file_checker->getFilePath() . PHP_EOL;
                }

                $file_checker->analyze(null);
            };

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
                        'mixed_counts' => ProjectChecker::getInstance()->codebase->getMixedCounts(),
                    ];
                }
            );

            // Wait for all tasks to complete and collect the results.
            /**
             * @var array<array{issues: array<int, array{severity: string, line_number: string, type: string,
             *  message: string, file_name: string, file_path: string, snippet: string, from: int, to: int,
             *  snippet_from: int, snippet_to: int, column: int}>, file_references: array<string, array<string,bool>>,
             *  mixed_counts: array<string, array{0: int, 1: int}>}>
             */
            $forked_pool_data = $pool->wait();

            foreach ($forked_pool_data as $pool_data) {
                IssueBuffer::addIssues($pool_data['issues']);
                FileReferenceProvider::addFileReferences($pool_data['file_references']);

                foreach ($pool_data['mixed_counts'] as $file_path => list($mixed_count, $nonmixed_count)) {
                    if (!isset($this->mixed_counts[$file_path])) {
                        $this->mixed_counts[$file_path] = [$mixed_count, $nonmixed_count];
                    } else {
                        $this->mixed_counts[$file_path][0] += $mixed_count;
                        $this->mixed_counts[$file_path][1] += $nonmixed_count;
                    }
                }
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

        if ($alter_code) {
            foreach ($this->files_to_report as $file_path) {
                $this->updateFile($file_path, $project_checker->dry_run, true);
            }
        }
    }

    /**
     * @param  string $file_path
     * @param  array<string, string> $filetype_checkers
     * @param  bool   $will_analyze
     *
     * @return FileChecker
     */
    private function getFile(ProjectChecker $project_checker, $file_path, array $filetype_checkers)
    {
        $extension = (string)pathinfo($file_path)['extension'];

        $file_name = $this->config->shortenFileName($file_path);

        if (isset($filetype_checkers[$extension])) {
            /** @var FileChecker */
            $file_checker = new $filetype_checkers[$extension]($project_checker, $file_path, $file_name);
        } else {
            $file_checker = new FileChecker($project_checker, $file_path, $file_name);
        }

        if ($this->debug_output) {
            echo 'Getting ' . $file_path . PHP_EOL;
        }

        return $file_checker;
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
        $new_return_type_manipulations = FunctionDocblockManipulator::getManipulationsForFile($file_path);

        $other_manipulations = FileManipulationBuffer::getForFile($file_path);

        $file_manipulations = array_merge($new_return_type_manipulations, $other_manipulations);

        usort(
            $file_manipulations,
            /**
             * @return int
             */
            function (FileManipulation $a, FileManipulation $b) {
                if ($a->start === $b->start) {
                    if ($b->end === $a->end) {
                        return $b->insertion_text > $a->insertion_text ? 1 : -1;
                    }

                    return $b->end > $a->end ? 1 : -1;
                }

                return $b->start > $a->start ? 1 : -1;
            }
        );

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
     * @param  string $file_path
     *
     * @return string
     */
    public function getFileContents($file_path)
    {
        return $this->file_provider->getContents($file_path);
    }

    /**
     * @param  string $file_path
     * @param  array<string, string>  $filetype_scanners
     * @param  bool   $will_analyze
     *
     * @return FileScanner
     */
    private function scanFile(
        $file_path,
        array $filetype_scanners,
        $will_analyze = false
    ) {
        $path_parts = explode(DIRECTORY_SEPARATOR, $file_path);
        $file_name_parts = explode('.', array_pop($path_parts));
        $extension = count($file_name_parts) > 1 ? array_pop($file_name_parts) : null;

        $file_name = $this->config->shortenFileName($file_path);

        if (isset($filetype_scanners[$extension])) {
            /** @var FileScanner */
            $file_checker = new $filetype_scanners[$extension]($file_path, $file_name, $will_analyze);
        } else {
            $file_checker = new FileScanner($file_path, $file_name, $will_analyze);
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

        $file_checker->scan(
            $this,
            $this->statements_provider->getStatementsForFile(
                $file_path,
                $this->debug_output
            ),
            $this->getFileStorageForPath($file_path)
        );

        return $file_checker;
    }

    /**
     * @param  string $file_path
     *
     * @return array<int, PhpParser\Node\Stmt>
     */
    public function getStatementsForFile($file_path)
    {
        return $this->statements_provider->getStatementsForFile(
            $file_path,
            $this->debug_output
        );
    }

    /**
     * @param  string $file_path
     *
     * @return FileStorage
     */
    public function getFileStorageForPath($file_path)
    {
        return $this->file_storage_provider->get($file_path);
    }

    /**
     * @param  string $fq_classlike_name
     *
     * @return ClassLikeStorage
     */
    public function createClassLikeStorage($fq_classlike_name)
    {
        return $this->classlike_storage_provider->create($fq_classlike_name);
    }

    /**
     * @param  string $file_path
     *
     * @return FileStorage
     */
    public function createFileStorageForPath($file_path)
    {
        return $this->file_storage_provider->create($file_path);
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
     * Checks whether a class exists, and if it does then records what file it's in
     * for later checking
     *
     * @param  string $fq_class_name
     *
     * @return bool
     */
    private function fileExistsForClassLike($fq_class_name)
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
    private function collectPredefinedClassLikes()
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
     * @param  string $symbol
     *
     * @return array<string, \Psalm\CodeLocation[]>
     */
    public function findReferencesToSymbol($symbol)
    {
        if (!$this->collect_references) {
            throw new \UnexpectedValueException('Should not be checking references');
        }

        if (strpos($symbol, '::') !== false) {
            return $this->findReferencesToMethod($symbol);
        }

        return $this->findReferencesToClassLike($symbol);
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

        $method_name_lc = strtolower($method_name);

        if (!isset($class_storage->methods[$method_name_lc])) {
            die('Method ' . $method_id . ' cannot be found' . PHP_EOL);
        }

        $method_storage = $class_storage->methods[$method_name_lc];

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

        $fq_class_name_lc = strtolower($fq_class_name);

        if (isset($this->use_referencing_locations[$fq_class_name_lc])) {
            foreach ($this->use_referencing_locations[$fq_class_name_lc] as $file_path => $locations) {
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
                        $parent_method_storage = $this->getMethodStorage($parent_method_id);

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
                        $parent_method_storage = $this->getMethodStorage($parent_method_id);

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

        foreach ($classlike_storage->properties as $property_name => $property_storage) {
            if (($property_storage->referencing_locations === null
                    || count($property_storage->referencing_locations) === 0)
                && (substr($property_name, 0, 2) !== '__' || $property_name === '__construct')
                && $property_storage->location
            ) {
                $property_id = $classlike_storage->name . '::$' . $property_name;

                if ($property_storage->visibility === ClassLikeChecker::VISIBILITY_PUBLIC) {
                    if (IssueBuffer::accepts(
                        new PossiblyUnusedProperty(
                            'Cannot find public calls to property ' . $property_id,
                            $property_storage->location
                        ),
                        $classlike_storage->suppressed_issues
                    )) {
                        // fall through
                    }
                } elseif (!isset($classlike_storage->declaring_method_ids['__get'])) {
                    if (IssueBuffer::accepts(
                        new UnusedProperty(
                            'Property ' . $property_id . ' is never used',
                            $property_storage->location
                        )
                    )) {
                        // fall through
                    }
                }
            }
        }
    }

    /**
     * @param  string $fq_class_name
     *
     * @return FileChecker
     */
    public function getFileCheckerForClassLike(ProjectChecker $project_checker, $fq_class_name)
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

        $file_checker = new FileChecker(
            $project_checker,
            $file_path,
            $this->config->shortenFileName($file_path)
        );

        if ($this->cache) {
            $this->file_checkers[$file_path] = $file_checker;
        }

        return $file_checker;
    }

    /**
     * Check whether a class/interface exists
     *
     * @param  string          $fq_class_name
     * @param  CodeLocation $code_location
     *
     * @return bool
     */
    public function classOrInterfaceExists(
        $fq_class_name,
        CodeLocation $code_location = null
    ) {
        if (!$this->classExists($fq_class_name) && !$this->interfaceExists($fq_class_name)) {
            return false;
        }

        if ($this->collect_references && $code_location) {
            $class_storage = $this->classlike_storage_provider->get($fq_class_name);
            if ($class_storage->referencing_locations === null) {
                $class_storage->referencing_locations = [];
            }
            $class_storage->referencing_locations[$code_location->file_path][] = $code_location;
        }

        return true;
    }

    /**
     * @param  string       $fq_class_name
     * @param  string       $possible_parent
     *
     * @return bool
     */
    public function classExtendsOrImplements($fq_class_name, $possible_parent)
    {
        return $this->classExtends($fq_class_name, $possible_parent)
            || $this->classImplements($fq_class_name, $possible_parent);
    }

    /**
     * Determine whether or not a given class exists
     *
     * @param  string       $fq_class_name
     *
     * @return bool
     */
    public function classExists($fq_class_name)
    {
        if (isset(ClassLikeChecker::$SPECIAL_TYPES[$fq_class_name])) {
            return false;
        }

        if ($fq_class_name === 'Generator') {
            return true;
        }

        return $this->hasFullyQualifiedClassName($fq_class_name);
    }

    /**
     * Determine whether or not a class extends a parent
     *
     * @param  string       $fq_class_name
     * @param  string       $possible_parent
     *
     * @return bool
     */
    public function classExtends($fq_class_name, $possible_parent)
    {
        $fq_class_name = strtolower($fq_class_name);

        if ($fq_class_name === 'generator') {
            return false;
        }

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        return in_array(strtolower($possible_parent), $class_storage->parent_classes, true);
    }

    /**
     * Check whether a class implements an interface
     *
     * @param  string       $fq_class_name
     * @param  string       $interface
     *
     * @return bool
     */
    public function classImplements($fq_class_name, $interface)
    {
        $interface_id = strtolower($interface);

        $fq_class_name = strtolower($fq_class_name);

        if ($interface_id === 'callable' && $fq_class_name === 'closure') {
            return true;
        }

        if ($interface_id === 'traversable' && $fq_class_name === 'generator') {
            return true;
        }

        if (isset(ClassLikeChecker::$SPECIAL_TYPES[$interface_id])
            || isset(ClassLikeChecker::$SPECIAL_TYPES[$fq_class_name])
        ) {
            return false;
        }

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        return isset($class_storage->class_implements[$interface_id]);
    }

    /**
     * @param  string         $fq_interface_name
     *
     * @return bool
     */
    public function interfaceExists($fq_interface_name)
    {
        if (isset(ClassLikeChecker::$SPECIAL_TYPES[strtolower($fq_interface_name)])) {
            return false;
        }

        return $this->hasFullyQualifiedInterfaceName($fq_interface_name);
    }

    /**
     * @param  string         $interface_name
     * @param  string         $possible_parent
     *
     * @return bool
     */
    public function interfaceExtends($interface_name, $possible_parent)
    {
        return in_array($possible_parent, $this->getParentInterfaces($interface_name), true);
    }

    /**
     * @param  string         $fq_interface_name
     *
     * @return array<string>   all interfaces extended by $interface_name
     */
    public function getParentInterfaces($fq_interface_name)
    {
        $fq_interface_name = strtolower($fq_interface_name);

        $storage = $this->classlike_storage_provider->get($fq_interface_name);

        return $storage->parent_interfaces;
    }

    /**
     * Whether or not a given method exists
     *
     * @param  string       $method_id
     * @param  CodeLocation|null $code_location
     *
     * @return bool
     */
    public function methodExists(
        $method_id,
        CodeLocation $code_location = null
    ) {
        // remove trailing backslash if it exists
        $method_id = preg_replace('/^\\\\/', '', $method_id);
        list($fq_class_name, $method_name) = explode('::', $method_id);
        $method_name = strtolower($method_name);
        $method_id = $fq_class_name . '::' . $method_name;

        $old_method_id = null;

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->declaring_method_ids[$method_name])) {
            if ($this->collect_references && $code_location) {
                $declaring_method_id = $class_storage->declaring_method_ids[$method_name];
                list($declaring_method_class, $declaring_method_name) = explode('::', $declaring_method_id);

                $declaring_class_storage = $this->classlike_storage_provider->get($declaring_method_class);
                $declaring_method_storage = $declaring_class_storage->methods[strtolower($declaring_method_name)];
                if ($declaring_method_storage->referencing_locations === null) {
                    $declaring_method_storage->referencing_locations = [];
                }
                $declaring_method_storage->referencing_locations[$code_location->file_path][] = $code_location;

                foreach ($class_storage->class_implements as $fq_interface_name) {
                    $interface_storage = $this->classlike_storage_provider->get($fq_interface_name);
                    if (isset($interface_storage->methods[$method_name])) {
                        $interface_method_storage = $interface_storage->methods[$method_name];
                        if (!isset($interface_method_storage->referencing_locations)) {
                            $interface_method_storage->referencing_locations = [];
                        }
                        $interface_method_storage->referencing_locations[$code_location->file_path][] = $code_location;
                    }
                }

                if (isset($declaring_class_storage->overridden_method_ids[$declaring_method_name])) {
                    $overridden_method_ids = $declaring_class_storage->overridden_method_ids[$declaring_method_name];

                    foreach ($overridden_method_ids as $overridden_method_id) {
                        list($overridden_method_class, $overridden_method_name) = explode('::', $overridden_method_id);

                        $class_storage = $this->classlike_storage_provider->get($overridden_method_class);
                        $method_storage = $class_storage->methods[strtolower($overridden_method_name)];
                        if ($method_storage->referencing_locations === null) {
                            $method_storage->referencing_locations = [];
                        }
                        $method_storage->referencing_locations[$code_location->file_path][] = $code_location;
                    }
                }
            }

            return true;
        }

        if ($class_storage->abstract && isset($class_storage->overridden_method_ids[$method_name])) {
            return true;
        }

        // support checking oldstyle constructors
        if ($method_name === '__construct') {
            $method_name_parts = explode('\\', $fq_class_name);
            $old_constructor_name = array_pop($method_name_parts);
            $old_method_id = $fq_class_name . '::' . $old_constructor_name;
        }

        if (FunctionChecker::inCallMap($method_id) || ($old_method_id && FunctionChecker::inCallMap($method_id))) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether or not a class has the correct casing
     *
     * @param  string $fq_class_name
     *
     * @return bool
     */
    public function classHasCorrectCasing($fq_class_name)
    {
        if ($fq_class_name === 'Generator') {
            return true;
        }

        return isset($this->existing_classes[$fq_class_name]);
    }

    /**
     * @param  string $fq_interface_name
     *
     * @return bool
     */
    public function interfaceHasCorrectCasing($fq_interface_name)
    {
        return isset($this->existing_interfaces[$fq_interface_name]);
    }

    /**
     * @param  string $fq_trait_name
     *
     * @return bool
     */
    public function traitHasCorrectCase($fq_trait_name)
    {
        return isset($this->existing_traits[$fq_trait_name]);
    }

    /**
     * @param string $function_id
     * @param FunctionLikeStorage $storage
     *
     * @return void
     */
    public function addStubbedFunction($function_id, FunctionLikeStorage $storage)
    {
        self::$stubbed_functions[$function_id] = $storage;
    }

    /**
     * @param  string  $function_id
     *
     * @return bool
     */
    public function hasStubbedFunction($function_id)
    {
        return isset(self::$stubbed_functions[$function_id]);
    }

    /**
     * @param  string $function_id
     *
     * @return bool
     */
    public function functionExists(StatementsChecker $statements_checker, $function_id)
    {
        $file_storage = $this->file_storage_provider->get($statements_checker->getFilePath());

        if (isset($file_storage->declaring_function_ids[$function_id])) {
            return true;
        }

        if ($this->reflection->hasFunction($function_id)) {
            return true;
        }

        if (isset(self::$stubbed_functions[$function_id])) {
            return true;
        }

        if (isset($statements_checker->getFunctionCheckers()[$function_id])) {
            return true;
        }

        if ($this->reflection->registerFunction($function_id) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param  string $function_id
     *
     * @return FunctionLikeStorage
     */
    public function getFunctionStorage(StatementsChecker $statements_checker, $function_id)
    {
        if (isset(self::$stubbed_functions[$function_id])) {
            return self::$stubbed_functions[$function_id];
        }

        if ($this->reflection->hasFunction($function_id)) {
            return $this->reflection->getFunctionStorage($function_id);
        }

        $file_path = $statements_checker->getFilePath();
        $file_storage = $this->file_storage_provider->get($file_path);

        $function_checkers = $statements_checker->getFunctionCheckers();

        if (isset($function_checkers[$function_id])) {
            $function_id = $function_checkers[$function_id]->getMethodId();

            if (!isset($file_storage->functions[$function_id])) {
                throw new \UnexpectedValueException(
                    'Expecting ' . $function_id . ' to have storage in ' . $file_path
                );
            }

            return $file_storage->functions[$function_id];
        }

        // closures can be returned here
        if (isset($file_storage->functions[$function_id])) {
            return $file_storage->functions[$function_id];
        }

        if (!isset($file_storage->declaring_function_ids[$function_id])) {
            throw new \UnexpectedValueException(
                'Expecting ' . $function_id . ' to have storage in ' . $file_path
            );
        }

        $declaring_file_path = $file_storage->declaring_function_ids[$function_id];

        $declaring_file_storage = $this->file_storage_provider->get($declaring_file_path);

        if (!isset($declaring_file_storage->functions[$function_id])) {
            throw new \UnexpectedValueException(
                'Not expecting ' . $function_id . ' to not have storage in ' . $declaring_file_path
            );
        }

        return $declaring_file_storage->functions[$function_id];
    }

    /**
     * @param  string $file_path
     * @param  string $closure_id
     *
     * @return FunctionLikeStorage
     */
    public function getClosureStorage($file_path, $closure_id)
    {
        $file_storage = $this->file_storage_provider->get($file_path);

        // closures can be returned here
        if (isset($file_storage->functions[$closure_id])) {
            return $file_storage->functions[$closure_id];
        }

        throw new \UnexpectedValueException(
            'Expecting ' . $closure_id . ' to have storage in ' . $file_path
        );
    }

    /**
     * @param  string $method_id
     *
     * @return MethodStorage
     */
    public function getMethodStorage($method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        $method_name_lc = strtolower($method_name);

        if (!isset($class_storage->methods[$method_name_lc])) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $method_id);
        }

        return $class_storage->methods[$method_name_lc];
    }

    /**
     * @param  string $const_id
     * @param  Type\Union $type
     *
     * @return  void
     */
    public function addStubbedConstantType($const_id, $type)
    {
        self::$stubbed_constants[$const_id] = $type;
    }

    /**
     * @param  string $const_id
     *
     * @return Type\Union|null
     */
    public function getStubbedConstantType($const_id)
    {
        return isset(self::$stubbed_constants[$const_id]) ? self::$stubbed_constants[$const_id] : null;
    }

    /**
     * @param  string $fq_trait_name
     *
     * @return void
     */
    public function addTraitNode($fq_trait_name, PhpParser\Node\Stmt\Trait_ $node, Aliases $aliases)
    {
        $fq_trait_name_lc = strtolower($fq_trait_name);
        $this->trait_nodes[$fq_trait_name_lc] = $node;
        $this->trait_aliases[$fq_trait_name_lc] = $aliases;
    }

    /**
     * @param  string $fq_trait_name
     *
     * @return PhpParser\Node\Stmt\Trait_
     */
    public function getTraitNode($fq_trait_name)
    {
        $fq_trait_name_lc = strtolower($fq_trait_name);

        if (isset($this->trait_nodes[$fq_trait_name_lc])) {
            return $this->trait_nodes[$fq_trait_name_lc];
        }

        throw new \UnexpectedValueException(
            'Expecting trait statements to exist for ' . $fq_trait_name
        );
    }

    /**
     * @param  string $fq_trait_name
     *
     * @return Aliases
     */
    public function getTraitAliases($fq_trait_name)
    {
        $fq_trait_name_lc = strtolower($fq_trait_name);

        if (isset($this->trait_aliases[$fq_trait_name_lc])) {
            return $this->trait_aliases[$fq_trait_name_lc];
        }

        throw new \UnexpectedValueException(
            'Expecting trait aliases to exist for ' . $fq_trait_name
        );
    }

    /**
     * @param  string $method_id
     *
     * @return MethodChecker|null
     */
    public function getCachedMethodChecker($method_id)
    {
        if (isset($this->method_checkers[$method_id])) {
            return $this->method_checkers[$method_id];
        }

        return null;
    }

    /**
     * @param  string        $method_id
     * @param  MethodChecker $checker
     *
     * @return void
     */
    public function cacheMethodChecker($method_id, MethodChecker $checker)
    {
        $this->method_checkers[$method_id] = $checker;
    }

    /**
     * @return ClassLikeStorageProvider
     */
    public function getClassLikeStorageProvider()
    {
        return $this->classlike_storage_provider;
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
     * @return void
     */
    public function incrementMixedCount($file_path)
    {
        if (!$this->count_mixed) {
            return;
        }

        if (!isset($this->mixed_counts[$file_path])) {
            $this->mixed_counts[$file_path] = [0, 0];
        }

        ++$this->mixed_counts[$file_path][0];
    }

    /**
     * @param  string $file_path
     *
     * @return void
     */
    public function incrementNonMixedCount($file_path)
    {
        if (!$this->count_mixed) {
            return;
        }

        if (!isset($this->mixed_counts[$file_path])) {
            $this->mixed_counts[$file_path] = [0, 0];
        }

        ++$this->mixed_counts[$file_path][1];
    }

    /**
     * @return array<string, array{0: int, 1: int}>
     */
    public function getMixedCounts()
    {
        return $this->mixed_counts;
    }

    /**
     * @return float
     */
    public function getNonMixedPercentage()
    {
        $mixed_count = 0;
        $nonmixed_count = 0;

        foreach ($this->files_to_report as $file_path => $_) {
            if (isset($this->mixed_counts[$file_path])) {
                list($path_mixed_count, $path_nonmixed_count) = $this->mixed_counts[$file_path];
                $mixed_count += $path_mixed_count;
                $nonmixed_count += $path_nonmixed_count;
            }
        }

        $total = $mixed_count + $nonmixed_count;

        if (!$total) {
            return 0;
        }

        return 100 * $nonmixed_count / $total;
    }

    /**
     * @return string
     */
    public function getNonMixedStats()
    {
        $stats = '';

        foreach ($this->files_to_report as $file_path => $_) {
            if (isset($this->mixed_counts[$file_path])) {
                list($path_mixed_count, $path_nonmixed_count) = $this->mixed_counts[$file_path];
                $stats .= number_format(100 * $path_nonmixed_count / ($path_mixed_count + $path_nonmixed_count), 0)
                    . '% ' . $this->config->shortenFileName($file_path)
                    . ' (' . $path_mixed_count . ' mixed)' . PHP_EOL;
            }
        }

        return $stats;
    }

    /**
     * @return void
     */
    public function disableMixedCounts()
    {
        $this->count_mixed = false;
    }

    /**
     * @return void
     */
    public function enableMixedCounts()
    {
        $this->count_mixed = true;
    }
}
