<?php
namespace Psalm;

use LanguageServerProtocol\{Position, Range};
use PhpParser;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionLikeStorage;

class Codebase
{
    /**
     * @var Config
     */
    public $config;

    /**
     * A map of fully-qualified use declarations to the files
     * that reference them (keyed by filename)
     *
     * @var array<string, array<int, \Psalm\CodeLocation>>
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
     * @var bool
     */
    public $collect_locations = false;

    /**
     * @var null|'always'|'auto'
     */
    public $find_unused_code = null;

    /**
     * @var FileProvider
     */
    public $file_provider;

    /**
     * @var FileReferenceProvider
     */
    public $file_reference_provider;

    /**
     * @var StatementsProvider
     */
    public $statements_provider;

    /**
     * @var Progress
     */
    private $progress;

    /**
     * @var array<string, Type\Union>
     */
    private static $stubbed_constants = [];

    /**
     * Whether to register autoloaded information
     *
     * @var bool
     */
    public $register_autoload_files = false;

    /**
     * Whether to log functions just at the file level or globally (for stubs)
     *
     * @var bool
     */
    public $register_stub_files = false;

    /**
     * @var bool
     */
    public $find_unused_variables = false;

    /**
     * @var Internal\Codebase\Reflection
     */
    private $reflection;

    /**
     * @var Internal\Codebase\Scanner
     */
    public $scanner;

    /**
     * @var Internal\Codebase\Analyzer
     */
    public $analyzer;

    /**
     * @var Internal\Codebase\Functions
     */
    public $functions;

    /**
     * @var Internal\Codebase\ClassLikes
     */
    public $classlikes;

    /**
     * @var Internal\Codebase\Methods
     */
    public $methods;

    /**
     * @var Internal\Codebase\Properties
     */
    public $properties;

    /**
     * @var Internal\Codebase\Populator
     */
    public $populator;

    /**
     * @var bool
     */
    public $server_mode = false;

    /**
     * @var bool
     */
    public $store_node_types = false;

    /**
     * Whether or not to infer types from usage. Computationally expensive, so turned off by default
     *
     * @var bool
     */
    public $infer_types_from_usage = false;

    /**
     * @var bool
     */
    public $alter_code = false;

    /**
     * @var bool
     */
    public $diff_methods = false;

    /**
     * @var array<string, string>
     */
    public $methods_to_move = [];

    /**
     * @var array<string, string>
     */
    public $methods_to_rename = [];

    /**
     * @var array<string, string>
     */
    public $properties_to_move = [];

    /**
     * @var array<string, string>
     */
    public $properties_to_rename = [];

    /**
     * @var array<string, string>
     */
    public $class_constants_to_move = [];

    /**
     * @var array<string, string>
     */
    public $class_constants_to_rename = [];

    /**
     * @var array<string, string>
     */
    public $classes_to_move = [];

    /**
     * @var array<string, string>
     */
    public $call_transforms = [];

    /**
     * @var array<string, string>
     */
    public $property_transforms = [];

    /**
     * @var array<string, string>
     */
    public $class_constant_transforms = [];

    /**
     * @var array<string, string>
     */
    public $class_transforms = [];

    /**
     * @var bool
     */
    public $allow_backwards_incompatible_changes = true;

    /**
     * @var int
     */
    public $php_major_version = PHP_MAJOR_VERSION;

    /**
     * @var int
     */
    public $php_minor_version = PHP_MINOR_VERSION;

    public function __construct(
        Config $config,
        Providers $providers,
        Progress $progress = null
    ) {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $this->config = $config;
        $this->file_storage_provider = $providers->file_storage_provider;
        $this->classlike_storage_provider = $providers->classlike_storage_provider;
        $this->progress = $progress;
        $this->file_provider = $providers->file_provider;
        $this->file_reference_provider = $providers->file_reference_provider;
        $this->statements_provider = $providers->statements_provider;

        self::$stubbed_constants = [];

        $this->reflection = new Internal\Codebase\Reflection($providers->classlike_storage_provider, $this);

        $this->scanner = new Internal\Codebase\Scanner(
            $this,
            $config,
            $providers->file_storage_provider,
            $providers->file_provider,
            $this->reflection,
            $providers->file_reference_provider,
            $progress
        );

        $this->loadAnalyzer();

        $this->functions = new Internal\Codebase\Functions($providers->file_storage_provider, $this->reflection);

        $this->properties = new Internal\Codebase\Properties(
            $providers->classlike_storage_provider,
            $providers->file_reference_provider
        );

        $this->classlikes = new Internal\Codebase\ClassLikes(
            $this->config,
            $providers->classlike_storage_provider,
            $providers->file_reference_provider,
            $this->scanner
        );

        $this->methods = new Internal\Codebase\Methods(
            $config,
            $providers->classlike_storage_provider,
            $providers->file_reference_provider,
            $this->classlikes
        );

        $this->populator = new Internal\Codebase\Populator(
            $config,
            $providers->classlike_storage_provider,
            $providers->file_storage_provider,
            $this->classlikes,
            $providers->file_reference_provider,
            $progress
        );

        $this->loadAnalyzer();
    }

    /**
     * @return void
     */
    private function loadAnalyzer()
    {
        $this->analyzer = new Internal\Codebase\Analyzer(
            $this->config,
            $this->file_provider,
            $this->file_storage_provider,
            $this->progress
        );
    }

    /**
     * @param array<string> $candidate_files
     *
     * @return void
     */
    public function reloadFiles(ProjectAnalyzer $project_analyzer, array $candidate_files)
    {
        $this->loadAnalyzer();

        $this->file_reference_provider->loadReferenceCache();

        if (!$this->statements_provider->parser_cache_provider) {
            $diff_files = $candidate_files;
        } else {
            $diff_files = [];

            $parser_cache_provider = $this->statements_provider->parser_cache_provider;

            foreach ($candidate_files as $candidate_file_path) {
                if ($parser_cache_provider->loadExistingFileContentsFromCache($candidate_file_path)
                    !== $this->file_provider->getContents($candidate_file_path)
                ) {
                    $diff_files[] = $candidate_file_path;
                }
            }
        }

        $referenced_files = $project_analyzer->getReferencedFilesFromDiff($diff_files, false);

        foreach ($diff_files as $diff_file_path) {
            $this->invalidateInformationForFile($diff_file_path);
        }

        foreach ($referenced_files as $referenced_file_path) {
            if (in_array($referenced_file_path, $diff_files)) {
                continue;
            }

            $file_storage = $this->file_storage_provider->get($referenced_file_path);

            foreach ($file_storage->classlikes_in_file as $fq_classlike_name) {
                $this->classlike_storage_provider->remove($fq_classlike_name);
                $this->classlikes->removeClassLike($fq_classlike_name);
            }

            $this->file_storage_provider->remove($referenced_file_path);
            $this->scanner->removeFile($referenced_file_path);
        }

        $referenced_files = array_combine($referenced_files, $referenced_files);

        $this->scanner->addFilesToDeepScan($referenced_files);
        $this->addFilesToAnalyze(array_combine($candidate_files, $candidate_files));
        $this->scanner->scanFiles($this->classlikes);

        $this->file_reference_provider->updateReferenceCache($this, $referenced_files);

        $this->populator->populateCodebase($this);
    }

    /** @return void */
    public function enterServerMode()
    {
        $this->server_mode = true;
        $this->store_node_types = true;
    }

    /**
     * @return void
     */
    public function collectLocations()
    {
        $this->collect_locations = true;
        $this->classlikes->collect_locations = true;
        $this->methods->collect_locations = true;
        $this->properties->collect_locations = true;
    }

    /**
     * @param 'always'|'auto' $find_unused_code
     *
     * @return void
     */
    public function reportUnusedCode(string $find_unused_code = 'auto')
    {
        $this->collect_references = true;
        $this->classlikes->collect_references = true;
        $this->find_unused_code = $find_unused_code;
        $this->find_unused_variables = true;
    }

    /**
     * @return void
     */
    public function reportUnusedVariables()
    {
        $this->collect_references = true;
        $this->find_unused_variables = true;
    }

    /**
     * @param array<string, string> $files_to_analyze
     *
     * @return void
     */
    public function addFilesToAnalyze(array $files_to_analyze)
    {
        $this->scanner->addFilesToDeepScan($files_to_analyze);
        $this->analyzer->addFiles($files_to_analyze);
    }

    /**
     * Scans all files their related files
     *
     * @return void
     */
    public function scanFiles(int $threads = 1)
    {
        $has_changes = $this->scanner->scanFiles($this->classlikes, $threads);

        if ($has_changes) {
            $this->populator->populateCodebase($this);
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
     *
     * @return array<int, PhpParser\Node\Stmt>
     */
    public function getStatementsForFile($file_path)
    {
        return $this->statements_provider->getStatementsForFile(
            $file_path,
            $this->progress
        );
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
     * @return void
     */
    public function cacheClassLikeStorage(ClassLikeStorage $classlike_storage, $file_path)
    {
        $file_contents = $this->file_provider->getContents($file_path);

        if ($this->classlike_storage_provider->cache) {
            $this->classlike_storage_provider->cache->writeToCache($classlike_storage, $file_path, $file_contents);
        }
    }

    /**
     * @param  string $fq_classlike_name
     * @param  string $file_path
     *
     * @return void
     */
    public function exhumeClassLikeStorage($fq_classlike_name, $file_path)
    {
        $file_contents = $this->file_provider->getContents($file_path);
        $storage = $this->classlike_storage_provider->exhume($fq_classlike_name, $file_path, $file_contents);

        if ($storage->is_trait) {
            $this->classlikes->addFullyQualifiedTraitName($fq_classlike_name, $file_path);
        } elseif ($storage->is_interface) {
            $this->classlikes->addFullyQualifiedInterfaceName($fq_classlike_name, $file_path);
        } else {
            $this->classlikes->addFullyQualifiedClassName($fq_classlike_name, $file_path);
        }
    }

    /**
     * @param  ?\ReflectionType $type
     */
    public static function getPsalmTypeFromReflection($type) : Type\Union
    {
        return \Psalm\Internal\Codebase\Reflection::getPsalmTypeFromReflectionType($type);
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
     * @param  string $symbol
     *
     * @return \Psalm\CodeLocation[]
     */
    public function findReferencesToSymbol($symbol)
    {
        if (!$this->collect_locations) {
            throw new \UnexpectedValueException('Should not be checking references');
        }

        if (strpos($symbol, '::$') !== false) {
            return $this->findReferencesToProperty($symbol);
        }

        if (strpos($symbol, '::') !== false) {
            return $this->findReferencesToMethod($symbol);
        }

        return $this->findReferencesToClassLike($symbol);
    }

    /**
     * @param  string $method_id
     *
     * @return \Psalm\CodeLocation[]
     */
    public function findReferencesToMethod($method_id)
    {
        return $this->file_reference_provider->getClassMethodLocations(strtolower($method_id));
    }

    /**
     * @return \Psalm\CodeLocation[]
     */
    public function findReferencesToProperty(string $property_id)
    {
        list($fq_class_name, $property_name) = explode('::', $property_id);
        return $this->file_reference_provider->getClassPropertyLocations(
            strtolower($fq_class_name) . '::' . $property_name
        );
    }

    /**
     * @param  string $fq_class_name
     *
     * @return \Psalm\CodeLocation[]
     */
    public function findReferencesToClassLike($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $locations = $this->file_reference_provider->getClassLocations($fq_class_name_lc);

        if (isset($this->use_referencing_locations[$fq_class_name_lc])) {
            $locations = array_merge($locations, $this->use_referencing_locations[$fq_class_name_lc]);
        }

        return $locations;
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
     * @param  string $const_id
     * @param  Type\Union $type
     *
     * @return  void
     */
    public function addGlobalConstantType($const_id, Type\Union $type)
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
     * @param  string $file_path
     *
     * @return bool
     */
    public function fileExists($file_path)
    {
        return $this->file_provider->fileExists($file_path);
    }

    /**
     * Check whether a class/interface exists
     *
     * @param  string          $fq_class_name
     * @param  CodeLocation $code_location
     *
     * @return bool
     */
    public function classOrInterfaceExists($fq_class_name, CodeLocation $code_location = null)
    {
        return $this->classlikes->classOrInterfaceExists($fq_class_name, $code_location);
    }

    /**
     * @param  string       $fq_class_name
     * @param  string       $possible_parent
     *
     * @return bool
     */
    public function classExtendsOrImplements($fq_class_name, $possible_parent)
    {
        return $this->classlikes->classExtends($fq_class_name, $possible_parent)
            || $this->classlikes->classImplements($fq_class_name, $possible_parent);
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
        return $this->classlikes->classExists($fq_class_name);
    }

    /**
     * Determine whether or not a class extends a parent
     *
     * @param  string       $fq_class_name
     * @param  string       $possible_parent
     *
     * @return bool
     * @throws \Psalm\Exception\UnpopulatedClasslikeException when called on unpopulated class
     * @throws \InvalidArgumentException when class does not exist
     */
    public function classExtends($fq_class_name, $possible_parent)
    {
        return $this->classlikes->classExtends($fq_class_name, $possible_parent, true);
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
        return $this->classlikes->classImplements($fq_class_name, $interface);
    }

    /**
     * @param  string         $fq_interface_name
     *
     * @return bool
     */
    public function interfaceExists($fq_interface_name)
    {
        return $this->classlikes->interfaceExists($fq_interface_name);
    }

    /**
     * @param  string         $interface_name
     * @param  string         $possible_parent
     *
     * @return bool
     */
    public function interfaceExtends($interface_name, $possible_parent)
    {
        return $this->classlikes->interfaceExtends($interface_name, $possible_parent);
    }

    /**
     * @param  string         $fq_interface_name
     *
     * @return array<string>   all interfaces extended by $interface_name
     */
    public function getParentInterfaces($fq_interface_name)
    {
        return $this->classlikes->getParentInterfaces($fq_interface_name);
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
        return $this->classlikes->classHasCorrectCasing($fq_class_name);
    }

    /**
     * @param  string $fq_interface_name
     *
     * @return bool
     */
    public function interfaceHasCorrectCasing($fq_interface_name)
    {
        return $this->classlikes->interfaceHasCorrectCasing($fq_interface_name);
    }

    /**
     * @param  string $fq_trait_name
     *
     * @return bool
     */
    public function traitHasCorrectCase($fq_trait_name)
    {
        return $this->classlikes->traitHasCorrectCase($fq_trait_name);
    }

    /**
     * Whether or not a given method exists
     *
     * @param  string       $method_id
     * @param  CodeLocation|null $code_location
     * @param  string       $calling_method_id
     *
     * @return bool
     */
    public function methodExists(
        string $method_id,
        CodeLocation $code_location = null,
        $calling_method_id = null,
        string $file_path = null
    ) {
        return $this->methods->methodExists(
            $method_id,
            $calling_method_id,
            $code_location,
            null,
            $file_path
        );
    }

    /**
     * @param  string $method_id
     *
     * @return array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public function getMethodParams($method_id)
    {
        return $this->methods->getMethodParams($method_id);
    }

    /**
     * @param  string $method_id
     *
     * @return bool
     */
    public function isVariadic($method_id)
    {
        return $this->methods->isVariadic($method_id);
    }

    /**
     * @param  string $method_id
     * @param  string $self_class
     * @param  array<int, PhpParser\Node\Arg> $call_args
     *
     * @return Type\Union|null
     */
    public function getMethodReturnType($method_id, &$self_class, array $call_args = [])
    {
        return $this->methods->getMethodReturnType($method_id, $self_class);
    }

    /**
     * @param  string $method_id
     *
     * @return bool
     */
    public function getMethodReturnsByRef($method_id)
    {
        return $this->methods->getMethodReturnsByRef($method_id);
    }

    /**
     * @param  string               $method_id
     * @param  CodeLocation|null    $defined_location
     *
     * @return CodeLocation|null
     */
    public function getMethodReturnTypeLocation(
        $method_id,
        CodeLocation &$defined_location = null
    ) {
        return $this->methods->getMethodReturnTypeLocation($method_id, $defined_location);
    }

    /**
     * @param  string $method_id
     *
     * @return string|null
     */
    public function getDeclaringMethodId($method_id)
    {
        return $this->methods->getDeclaringMethodId($method_id);
    }

    /**
     * Get the class this method appears in (vs is declared in, which could give a trait)
     *
     * @param  string $method_id
     *
     * @return string|null
     */
    public function getAppearingMethodId($method_id)
    {
        return $this->methods->getAppearingMethodId($method_id);
    }

    /**
     * @param  string $method_id
     *
     * @return array<string>
     */
    public function getOverriddenMethodIds($method_id)
    {
        return $this->methods->getOverriddenMethodIds($method_id);
    }

    /**
     * @param  string $method_id
     *
     * @return string
     */
    public function getCasedMethodId($method_id)
    {
        return $this->methods->getCasedMethodId($method_id);
    }

    /**
     * @param string $file_path
     *
     * @return void
     */
    public function invalidateInformationForFile(string $file_path)
    {
        $this->scanner->removeFile($file_path);

        try {
            $file_storage = $this->file_storage_provider->get($file_path);
        } catch (\InvalidArgumentException $e) {
            return;
        }

        foreach ($file_storage->classlikes_in_file as $fq_classlike_name) {
            $this->classlike_storage_provider->remove($fq_classlike_name);
            $this->classlikes->removeClassLike($fq_classlike_name);
        }

        $this->file_storage_provider->remove($file_path);
    }

    /**
     * @return ?string
     */
    public function getSymbolInformation(string $file_path, string $symbol)
    {
        if (substr($symbol, 0, 6) === 'type: ') {
            return substr($symbol, 6);
        }

        try {
            if (strpos($symbol, '::')) {
                if (strpos($symbol, '()')) {
                    $symbol = substr($symbol, 0, -2);

                    $declaring_method_id = $this->methods->getDeclaringMethodId($symbol);

                    if (!$declaring_method_id) {
                        return null;
                    }

                    $storage = $this->methods->getStorage($declaring_method_id);

                    return '<?php ' . $storage;
                }

                list(, $symbol_name) = explode('::', $symbol);

                if (strpos($symbol, '$') !== false) {
                    $storage = $this->properties->getStorage($symbol);

                    return '<?php ' . $storage->getInfo() . ' ' . $symbol_name;
                }

                list($fq_classlike_name, $const_name) = explode('::', $symbol);

                $class_constants = $this->classlikes->getConstantsForClass(
                    $fq_classlike_name,
                    \ReflectionProperty::IS_PRIVATE
                );

                if (!isset($class_constants[$const_name])) {
                    return null;
                }

                return '<?php ' . $const_name;
            }

            if (strpos($symbol, '()')) {
                $file_storage = $this->file_storage_provider->get($file_path);

                $function_id = strtolower(substr($symbol, 0, -2));

                if (isset($file_storage->functions[$function_id])) {
                    $function_storage = $file_storage->functions[$function_id];

                    return '<?php ' . $function_storage;
                }

                return null;
            }

            $storage = $this->classlike_storage_provider->get($symbol);

            return '<?php ' . ($storage->abstract ? 'abstract ' : '') . 'class ' . $storage->name;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * @return ?CodeLocation
     */
    public function getSymbolLocation(string $file_path, string $symbol)
    {
        try {
            if (strpos($symbol, '::')) {
                if (strpos($symbol, '()')) {
                    $symbol = substr($symbol, 0, -2);

                    $declaring_method_id = $this->methods->getDeclaringMethodId($symbol);

                    if (!$declaring_method_id) {
                        return null;
                    }

                    $storage = $this->methods->getStorage($declaring_method_id);

                    return $storage->location;
                }

                if (strpos($symbol, '$') !== false) {
                    $storage = $this->properties->getStorage($symbol);

                    return $storage->location;
                }

                list($fq_classlike_name, $const_name) = explode('::', $symbol);

                $class_constants = $this->classlikes->getConstantsForClass(
                    $fq_classlike_name,
                    \ReflectionProperty::IS_PRIVATE
                );

                if (!isset($class_constants[$const_name])) {
                    return null;
                }

                $class_const_storage = $this->classlike_storage_provider->get($fq_classlike_name);

                return $class_const_storage->class_constant_locations[$const_name];
            }

            if (strpos($symbol, '()')) {
                $file_storage = $this->file_storage_provider->get($file_path);

                $function_id = strtolower(substr($symbol, 0, -2));

                if (isset($file_storage->functions[$function_id])) {
                    return $file_storage->functions[$function_id]->location;
                }

                return null;
            }

            $storage = $this->classlike_storage_provider->get($symbol);

            return $storage->location;
        } catch (\UnexpectedValueException $e) {
            error_log($e->getMessage());
            return null;
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @return array{0: string, 1: Range}|null
     */
    public function getReferenceAtPosition(string $file_path, Position $position)
    {
        $is_open = $this->file_provider->isOpen($file_path);

        if (!$is_open) {
            throw new \Psalm\Exception\UnanalyzedFileException($file_path . ' is not open');
        }

        $file_contents = $this->getFileContents($file_path);

        $offset = $position->toOffset($file_contents);

        list($reference_map, $type_map) = $this->analyzer->getMapsForFile($file_path);

        $reference = null;

        if (!$reference_map && !$type_map) {
            return null;
        }

        $start_pos = null;
        $end_pos = null;

        ksort($reference_map);

        foreach ($reference_map as $start_pos => list($end_pos, $possible_reference)) {
            if ($offset < $start_pos) {
                break;
            }

            if ($offset > $end_pos) {
                continue;
            }

            $reference = $possible_reference;
        }

        if ($reference === null || $start_pos === null || $end_pos === null) {
            ksort($type_map);

            foreach ($type_map as $start_pos => list($end_pos, $possible_type)) {
                if ($offset < $start_pos) {
                    break;
                }

                if ($offset > $end_pos) {
                    continue;
                }

                $reference = 'type: ' . $possible_type;
            }

            if ($reference === null || $start_pos === null || $end_pos === null) {
                return null;
            }
        }

        $range = new Range(
            self::getPositionFromOffset($start_pos, $file_contents),
            self::getPositionFromOffset($end_pos, $file_contents)
        );

        return [$reference, $range];
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    public function getCompletionDataAtPosition(string $file_path, Position $position)
    {
        $is_open = $this->file_provider->isOpen($file_path);

        if (!$is_open) {
            throw new \Psalm\Exception\UnanalyzedFileException($file_path . ' is not open');
        }

        $file_contents = $this->getFileContents($file_path);

        $offset = $position->toOffset($file_contents);

        list(, $type_map) = $this->analyzer->getMapsForFile($file_path);

        if (!$type_map) {
            return null;
        }

        $recent_type = null;

        krsort($type_map);

        $gap = null;

        foreach ($type_map as $start_pos => list($end_pos_excluding_whitespace, $possible_type)) {
            if ($offset < $start_pos) {
                continue;
            }

            $num_whitespace_bytes = preg_match('/\G\s+/', $file_contents, $matches, 0, $end_pos_excluding_whitespace)
                ? strlen($matches[0])
                : 0;
            $end_pos = $end_pos_excluding_whitespace + $num_whitespace_bytes;

            if ($offset - $end_pos === 2 || $offset - $end_pos === 3) {
                $candidate_gap = substr($file_contents, $end_pos, 2);

                if ($candidate_gap === '->' || $candidate_gap === '::') {
                    $gap = $candidate_gap;
                    $recent_type = $possible_type;

                    break;
                }
            }
        }

        if (!$recent_type
            || $recent_type === 'mixed'
            || !$gap
        ) {
            return null;
        }

        return [$recent_type, $gap];
    }

    /**
     * @return array<int, \LanguageServerProtocol\CompletionItem>
     */
    public function getCompletionItemsForClassishThing(string $type_string, string $gap) : array
    {
        $instance_completion_items = [];
        $static_completion_items = [];

        $type = Type::parseString($type_string);

        foreach ($type->getTypes() as $atomic_type) {
            if ($atomic_type instanceof Type\Atomic\TNamedObject) {
                try {
                    $class_storage = $this->classlike_storage_provider->get($atomic_type->value);

                    foreach ($class_storage->appearing_method_ids as $declaring_method_id) {
                        $method_storage = $this->methods->getStorage($declaring_method_id);

                        $completion_item = new \LanguageServerProtocol\CompletionItem(
                            (string)$method_storage,
                            \LanguageServerProtocol\CompletionItemKind::METHOD,
                            null,
                            null,
                            (string)$method_storage->visibility,
                            $method_storage->cased_name,
                            $method_storage->cased_name . '()'
                        );

                        if ($method_storage->is_static) {
                            $static_completion_items[] = $completion_item;
                        } else {
                            $instance_completion_items[] = $completion_item;
                        }
                    }

                    foreach ($class_storage->declaring_property_ids as $property_name => $declaring_class) {
                        $property_storage = $this->properties->getStorage(
                            $declaring_class . '::$' . $property_name
                        );

                        $completion_item = new \LanguageServerProtocol\CompletionItem(
                            $property_storage->getInfo() . ' $' . $property_name,
                            \LanguageServerProtocol\CompletionItemKind::PROPERTY,
                            null,
                            null,
                            (string)$property_storage->visibility,
                            $property_name,
                            ($gap === '::' ? '$' : '') . $property_name
                        );

                        if ($property_storage->is_static) {
                            $static_completion_items[] = $completion_item;
                        } else {
                            $instance_completion_items[] = $completion_item;
                        }
                    }

                    foreach ($class_storage->class_constant_locations as $const_name => $_) {
                        $static_completion_items[] = new \LanguageServerProtocol\CompletionItem(
                            'const ' . $const_name,
                            \LanguageServerProtocol\CompletionItemKind::VARIABLE,
                            null,
                            null,
                            null,
                            $const_name,
                            $const_name
                        );
                    }
                } catch (\Exception $e) {
                    error_log($e->getMessage());
                    continue;
                }
            }
        }

        if ($gap === '->') {
            $completion_items = $instance_completion_items;
        } else {
            $completion_items = array_merge(
                $instance_completion_items,
                $static_completion_items
            );
        }

        return $completion_items;
    }

    private static function getPositionFromOffset(int $offset, string $file_contents) : Position
    {
        $file_contents = substr($file_contents, 0, $offset);
        return new Position(
            substr_count($file_contents, "\n"),
            $offset - (int)strrpos($file_contents, "\n", strlen($file_contents))
        );
    }

    /**
     * @return void
     */
    public function addTemporaryFileChanges(string $file_path, string $new_content)
    {
        $this->file_provider->addTemporaryFileChanges($file_path, $new_content);
    }

    /**
     * @return void
     */
    public function removeTemporaryFileChanges(string $file_path)
    {
        $this->file_provider->removeTemporaryFileChanges($file_path);
    }

    /**
     * Checks if type is a subtype of other
     *
     * Given two types, checks if `$input_type` is a subtype of `$container_type`.
     * If you consider `Type\Union` as a set of types, this will tell you
     * if `$input_type` is fully contained in `$container_type`,
     *
     * $input_type ⊆ $container_type
     *
     * Useful for emitting issues like InvalidArgument, where argument at the call site
     * should be a subset of the function parameter type.
     */
    public function isTypeContainedByType(
        Type\Union $input_type,
        Type\Union $container_type
    ): bool {
        return TypeAnalyzer::isContainedBy($this, $input_type, $container_type);
    }

    /**
     * Checks if type has any part that is a subtype of other
     *
     * Given two types, checks if *any part* of `$input_type` is a subtype of `$container_type`.
     * If you consider `Type\Union` as a set of types, this will tell you if intersection
     * of `$input_type` with `$container_type` is not empty.
     *
     * $input_type ∩ $container_type ≠ ∅ , e.g. they are not disjoint.
     *
     * Useful for emitting issues like PossiblyInvalidArgument, where argument at the call
     * site should be a subtype of the function parameter type, but it's has some types that are
     * not a subtype of the required type.
     */
    public function canTypeBeContainedByType(
        Type\Union $input_type,
        Type\Union $container_type
    ): bool {
        return TypeAnalyzer::canBeContainedBy($this, $input_type, $container_type);
    }

    /**
     * Extracts key and value types from a traversable object (or iterable)
     *
     * Given an iterable type (*but not TArray*) returns a tuple of it's key/value types.
     * First element of the tuple holds key type, second has the value type.
     *
     * Example:
     * ```php
     * $codebase->getKeyValueParamsForTraversableObject(Type::parseString('iterable<int,string>'))
     * //  returns [Union(TInt), Union(TString)]
     * ```
     *
     * @return array{Type\Union,Type\Union}
     */
    public function getKeyValueParamsForTraversableObject(Type\Atomic $type): array
    {
        $key_type = null;
        $value_type = null;

        ForeachAnalyzer::getKeyValueParamsForTraversableObject($type, $this, $key_type, $value_type);

        return [
            $key_type ?? Type::getMixed(),
            $value_type ?? Type::getMixed(),
        ];
    }
}
