<?php
namespace Psalm;

use PhpParser;
use Psalm\Checker\ProjectChecker;
use Psalm\Provider\ClassLikeStorageProvider;
use Psalm\Provider\FileProvider;
use Psalm\Provider\FileReferenceProvider;
use Psalm\Provider\FileStorageProvider;
use Psalm\Provider\Providers;
use Psalm\Provider\StatementsProvider;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Checker\ClassLikeChecker;

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
     * @var FileProvider
     */
    private $file_provider;

    /**
     * @var FileReferenceProvider
     */
    public $file_reference_provider;

    /**
     * @var StatementsProvider
     */
    public $statements_provider;

    /**
     * @var bool
     */
    private $debug_output = false;

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
    public $find_unused_code = false;

    /**
     * @var Codebase\Reflection
     */
    private $reflection;

    /**
     * @var Codebase\Scanner
     */
    public $scanner;

    /**
     * @var Codebase\Analyzer
     */
    public $analyzer;

    /**
     * @var Codebase\Functions
     */
    public $functions;

    /**
     * @var Codebase\ClassLikes
     */
    public $classlikes;

    /**
     * @var Codebase\Methods
     */
    public $methods;

    /**
     * @var Codebase\Properties
     */
    public $properties;

    /**
     * @var Codebase\Populator
     */
    public $populator;

    /**
     * @param bool $collect_references
     * @param bool $debug_output
     */
    public function __construct(
        Config $config,
        Providers $providers,
        $debug_output = false
    ) {
        $this->config = $config;
        $this->file_storage_provider = $providers->file_storage_provider;
        $this->classlike_storage_provider = $providers->classlike_storage_provider;
        $this->debug_output = $debug_output;
        $this->file_provider = $providers->file_provider;
        $this->file_reference_provider = $providers->file_reference_provider;
        $this->statements_provider = $providers->statements_provider;
        $this->debug_output = $debug_output;

        self::$stubbed_constants = [];

        $this->reflection = new Codebase\Reflection($providers->classlike_storage_provider, $this);

        $this->scanner = new Codebase\Scanner(
            $this,
            $config,
            $providers->file_storage_provider,
            $providers->file_provider,
            $this->reflection,
            $providers->file_reference_provider,
            $debug_output
        );

        $this->loadAnalyzer();

        $this->functions = new Codebase\Functions($providers->file_storage_provider, $this->reflection);
        $this->methods = new Codebase\Methods(
            $config,
            $providers->classlike_storage_provider,
            $providers->file_reference_provider
        );
        $this->properties = new Codebase\Properties(
            $providers->classlike_storage_provider,
            $providers->file_reference_provider
        );

        $this->classlikes = new Codebase\ClassLikes(
            $this->config,
            $providers->classlike_storage_provider,
            $this->scanner,
            $this->methods
        );
        $this->populator = new Codebase\Populator(
            $config,
            $providers->classlike_storage_provider,
            $providers->file_storage_provider,
            $this->classlikes,
            $providers->file_reference_provider,
            $debug_output
        );

        $this->loadAnalyzer();
    }

    /**
     * @return void
     */
    private function loadAnalyzer()
    {
        $this->analyzer = new Codebase\Analyzer(
            $this->config,
            $this->file_provider,
            $this->file_storage_provider,
            $this->classlike_storage_provider,
            $this->debug_output
        );
    }

    /**
     * @param array<string> $candidate_files
     *
     * @return void
     */
    public function reloadFiles(ProjectChecker $project_checker, array $candidate_files)
    {
        $this->loadAnalyzer();

        $project_checker->file_reference_provider->loadReferenceCache();

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

        $referenced_files = $project_checker->getReferencedFilesFromDiff($diff_files);

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
        $this->scanner->scanFiles($this->classlikes);

        $project_checker->file_reference_provider->updateReferenceCache($this, $referenced_files);

        $this->populator->populateCodebase($this);
    }

    /**
     * @return void
     */
    public function collectReferences()
    {
        $this->collect_references = true;
        $this->classlikes->collect_references = true;
        $this->methods->collect_references = true;
        $this->properties->collect_references = true;
    }

    /**
     * @return void
     */
    public function reportUnusedCode()
    {
        $this->collectReferences();
        $this->find_unused_code = true;
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
            $this->debug_output
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
     */
    public function classExtends($fq_class_name, $possible_parent)
    {
        return $this->classlikes->classExtends($fq_class_name, $possible_parent);
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
    public function methodExists($method_id, CodeLocation $code_location = null, $calling_method_id = null)
    {
        return $this->methods->methodExists($method_id, $calling_method_id, $code_location);
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
     *
     * @return Type\Union|null
     */
    public function getMethodReturnType($method_id, &$self_class)
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
    private function invalidateInformationForFile($file_path)
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
}
