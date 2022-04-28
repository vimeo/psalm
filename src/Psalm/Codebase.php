<?php

namespace Psalm;

use InvalidArgumentException;
use PhpParser;
use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Exception\UnpopulatedClasslikeException;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\Analyzer;
use Psalm\Internal\Codebase\ClassLikes;
use Psalm\Internal\Codebase\Functions;
use Psalm\Internal\Codebase\Methods;
use Psalm\Internal\Codebase\Populator;
use Psalm\Internal\Codebase\Properties;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Internal\Codebase\Scanner;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Internal\DataFlow\TaintSource;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\FunctionStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type\Atomic;
use Psalm\Type\TaintKindGroup;
use Psalm\Type\Union;
use ReflectionType;
use UnexpectedValueException;

use function array_combine;
use function array_merge;
use function explode;
use function in_array;
use function is_string;
use function strpos;
use function strtolower;
use function substr;

use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_VERSION_ID;

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
     * @var array<lowercase-string, array<int, CodeLocation>>
     */
    public $use_referencing_locations = [];

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
    public $find_unused_code;

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
     * @var array<string, Union>
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
     * @var Scanner
     */
    public $scanner;

    /**
     * @var Analyzer
     * @psalm-suppress PropertyNotSetInConstructor
     */
    public $analyzer;

    /**
     * @var Functions
     */
    public $functions;

    /**
     * @var ClassLikes
     */
    public $classlikes;

    /**
     * @var Methods
     */
    public $methods;

    /**
     * @var Properties
     */
    public $properties;

    /**
     * @var Populator
     */
    public $populator;

    /**
     * @var ?TaintFlowGraph
     */
    public $taint_flow_graph;

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
     * @var array<lowercase-string, string>
     */
    public $methods_to_move = [];

    /**
     * @var array<lowercase-string, string>
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
     * @var array<lowercase-string, string>
     */
    public $classes_to_move = [];

    /**
     * @var array<lowercase-string, string>
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
     * @var array<lowercase-string, string>
     */
    public $class_transforms = [];

    /**
     * @var bool
     */
    public $allow_backwards_incompatible_changes = true;

    /**
     * @var int
     * @deprecated Removed in Psalm 5, use Codebase::$analysis_php_version_id
     */
    public $php_major_version = PHP_MAJOR_VERSION;

    /**
     * @var int
     * @deprecated Removed in Psalm 5, use Codebase::$analysis_php_version_id
     */
    public $php_minor_version = PHP_MINOR_VERSION;

    /** @var int */
    public $analysis_php_version_id = PHP_VERSION_ID;

    /** @var 'cli'|'config'|'composer'|'tests'|'runtime' */
    public $php_version_source = 'runtime';

    /**
     * @var bool
     */
    public $track_unused_suppressions = false;

    public function __construct(
        Config $config,
        Providers $providers,
        ?Progress $progress = null
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

        $reflection = new Reflection($providers->classlike_storage_provider, $this);

        $this->scanner = new Scanner(
            $this,
            $config,
            $providers->file_storage_provider,
            $providers->file_provider,
            $reflection,
            $providers->file_reference_provider,
            $progress
        );

        $this->loadAnalyzer();

        $this->functions = new Functions($providers->file_storage_provider, $reflection);

        $this->classlikes = new ClassLikes(
            $this->config,
            $providers->classlike_storage_provider,
            $providers->file_reference_provider,
            $providers->statements_provider,
            $this->scanner
        );

        $this->properties = new Properties(
            $providers->classlike_storage_provider,
            $providers->file_reference_provider,
            $this->classlikes
        );

        $this->methods = new Methods(
            $providers->classlike_storage_provider,
            $providers->file_reference_provider,
            $this->classlikes
        );

        $this->populator = new Populator(
            $config,
            $providers->classlike_storage_provider,
            $providers->file_storage_provider,
            $this->classlikes,
            $providers->file_reference_provider,
            $progress
        );

        $this->loadAnalyzer();
    }

    protected function loadAnalyzer(): void
    {
        $this->analyzer = new Analyzer(
            $this->config,
            $this->file_provider,
            $this->file_storage_provider,
            $this->progress
        );
    }

    public function enterServerMode(): void
    {
        $this->server_mode = true;
        $this->store_node_types = true;
    }

    public function collectLocations(): void
    {
        $this->collect_locations = true;
        $this->classlikes->collect_locations = true;
        $this->methods->collect_locations = true;
        $this->properties->collect_locations = true;
    }

    /**
     * @param 'always'|'auto' $find_unused_code
     *
     */
    public function reportUnusedCode(string $find_unused_code = 'auto'): void
    {
        $this->collect_references = true;
        $this->classlikes->collect_references = true;
        $this->find_unused_code = $find_unused_code;
        $this->find_unused_variables = true;
    }

    public function reportUnusedVariables(): void
    {
        $this->collect_references = true;
        $this->find_unused_variables = true;
    }

    /**
     * @param array<string, string> $files_to_analyze
     *
     */
    public function addFilesToAnalyze(array $files_to_analyze): void
    {
        $this->scanner->addFilesToDeepScan($files_to_analyze);
        $this->analyzer->addFilesToAnalyze($files_to_analyze);
    }

    /**
     * Scans all files their related files
     *
     */
    public function scanFiles(int $threads = 1): void
    {
        $has_changes = $this->scanner->scanFiles($this->classlikes, $threads);

        if ($has_changes) {
            $this->populator->populateCodebase();
        }
    }

    public function getFileContents(string $file_path): string
    {
        return $this->file_provider->getContents($file_path);
    }

    /**
     * @return list<PhpParser\Node\Stmt>
     */
    public function getStatementsForFile(string $file_path): array
    {
        return $this->statements_provider->getStatementsForFile(
            $file_path,
            $this->php_major_version . '.' . $this->php_minor_version,
            $this->progress
        );
    }

    public function createClassLikeStorage(string $fq_classlike_name): ClassLikeStorage
    {
        return $this->classlike_storage_provider->create($fq_classlike_name);
    }

    public function cacheClassLikeStorage(ClassLikeStorage $classlike_storage, string $file_path): void
    {
        $file_contents = $this->file_provider->getContents($file_path);

        if ($this->classlike_storage_provider->cache) {
            $this->classlike_storage_provider->cache->writeToCache($classlike_storage, $file_path, $file_contents);
        }
    }

    public function exhumeClassLikeStorage(string $fq_classlike_name, string $file_path): void
    {
        $file_contents = $this->file_provider->getContents($file_path);
        $storage = $this->classlike_storage_provider->exhume(
            $fq_classlike_name,
            $file_path,
            $file_contents
        );

        if ($storage->is_trait) {
            $this->classlikes->addFullyQualifiedTraitName($storage->name, $file_path);
        } elseif ($storage->is_interface) {
            $this->classlikes->addFullyQualifiedInterfaceName($storage->name, $file_path);
        } else {
            $this->classlikes->addFullyQualifiedClassName($storage->name, $file_path);
        }
    }

    public static function getPsalmTypeFromReflection(?ReflectionType $type): Union
    {
        return Reflection::getPsalmTypeFromReflectionType($type);
    }

    public function createFileStorageForPath(string $file_path): FileStorage
    {
        return $this->file_storage_provider->create($file_path);
    }

    /**
     * @return array<int, CodeLocation>
     */
    public function findReferencesToSymbol(string $symbol): array
    {
        if (!$this->collect_locations) {
            throw new UnexpectedValueException('Should not be checking references');
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
     * @return array<int, CodeLocation>
     */
    public function findReferencesToMethod(string $method_id): array
    {
        return $this->file_reference_provider->getClassMethodLocations(strtolower($method_id));
    }

    /**
     * @return array<int, CodeLocation>
     */
    public function findReferencesToProperty(string $property_id): array
    {
        [$fq_class_name, $property_name] = explode('::', $property_id);

        return $this->file_reference_provider->getClassPropertyLocations(
            strtolower($fq_class_name) . '::' . $property_name
        );
    }

    /**
     * @return CodeLocation[]
     *
     * @psalm-return array<int, CodeLocation>
     */
    public function findReferencesToClassLike(string $fq_class_name): array
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $locations = $this->file_reference_provider->getClassLocations($fq_class_name_lc);

        if (isset($this->use_referencing_locations[$fq_class_name_lc])) {
            $locations = array_merge($locations, $this->use_referencing_locations[$fq_class_name_lc]);
        }

        return $locations;
    }

    public function getClosureStorage(string $file_path, string $closure_id): FunctionStorage
    {
        $file_storage = $this->file_storage_provider->get($file_path);

        // closures can be returned here
        if (isset($file_storage->functions[$closure_id])) {
            return $file_storage->functions[$closure_id];
        }

        throw new UnexpectedValueException(
            'Expecting ' . $closure_id . ' to have storage in ' . $file_path
        );
    }

    public function addGlobalConstantType(string $const_id, Union $type): void
    {
        self::$stubbed_constants[$const_id] = $type;
    }

    public function getStubbedConstantType(string $const_id): ?Union
    {
        return self::$stubbed_constants[$const_id] ?? null;
    }

    /**
     * @return array<string, Union>
     */
    public function getAllStubbedConstants(): array
    {
        return self::$stubbed_constants;
    }

    public function fileExists(string $file_path): bool
    {
        return $this->file_provider->fileExists($file_path);
    }

    /**
     * Check whether a class/interface exists
     */
    public function classOrInterfaceExists(
        string $fq_class_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        return $this->classlikes->classOrInterfaceExists(
            $fq_class_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id
        );
    }

    /**
     * Check whether a class/interface exists
     */
    public function classOrInterfaceOrEnumExists(
        string $fq_class_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        return $this->classlikes->classOrInterfaceOrEnumExists(
            $fq_class_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id
        );
    }

    public function classExtendsOrImplements(string $fq_class_name, string $possible_parent): bool
    {
        return $this->classlikes->classExtends($fq_class_name, $possible_parent)
            || $this->classlikes->classImplements($fq_class_name, $possible_parent);
    }

    /**
     * Determine whether or not a given class exists
     */
    public function classExists(
        string $fq_class_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        return $this->classlikes->classExists(
            $fq_class_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id
        );
    }

    /**
     * Determine whether or not a class extends a parent
     *
     * @throws UnpopulatedClasslikeException when called on unpopulated class
     * @throws InvalidArgumentException when class does not exist
     */
    public function classExtends(string $fq_class_name, string $possible_parent): bool
    {
        return $this->classlikes->classExtends($fq_class_name, $possible_parent, true);
    }

    /**
     * Check whether a class implements an interface
     */
    public function classImplements(string $fq_class_name, string $interface): bool
    {
        return $this->classlikes->classImplements($fq_class_name, $interface);
    }

    public function interfaceExists(
        string $fq_interface_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ): bool {
        return $this->classlikes->interfaceExists(
            $fq_interface_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id
        );
    }

    public function interfaceExtends(string $interface_name, string $possible_parent): bool
    {
        return $this->classlikes->interfaceExtends($interface_name, $possible_parent);
    }

    /**
     * @return array<string, string> all interfaces extended by $interface_name
     */
    public function getParentInterfaces(string $fq_interface_name): array
    {
        return $this->classlikes->getParentInterfaces(
            $this->classlikes->getUnAliasedName($fq_interface_name)
        );
    }

    /**
     * Determine whether or not a class has the correct casing
     */
    public function classHasCorrectCasing(string $fq_class_name): bool
    {
        return $this->classlikes->classHasCorrectCasing($fq_class_name);
    }

    public function interfaceHasCorrectCasing(string $fq_interface_name): bool
    {
        return $this->classlikes->interfaceHasCorrectCasing($fq_interface_name);
    }

    public function traitHasCorrectCase(string $fq_trait_name): bool
    {
        return $this->classlikes->traitHasCorrectCase($fq_trait_name);
    }

    /**
     * Given a function id, return the function like storage for
     * a method, closure, or function.
     *
     * @param non-empty-string $function_id
     *
     * @return FunctionStorage|MethodStorage
     */
    public function getFunctionLikeStorage(
        StatementsAnalyzer $statements_analyzer,
        string $function_id
    ): FunctionLikeStorage {
        $doesMethodExist =
            MethodIdentifier::isValidMethodIdReference($function_id)
            && $this->methodExists($function_id);

        if ($doesMethodExist) {
            $method_id = MethodIdentifier::wrap($function_id);

            $declaring_method_id = $this->methods->getDeclaringMethodId($method_id);

            if (!$declaring_method_id) {
                throw new UnexpectedValueException('Declaring method for ' . $method_id . ' cannot be found');
            }

            return $this->methods->getStorage($declaring_method_id);
        }

        return $this->functions->getStorage($statements_analyzer, strtolower($function_id));
    }

    /**
     * Whether or not a given method exists
     *
     * @param  string|MethodIdentifier      $method_id
     * @param  string|MethodIdentifier|null $calling_method_id
     */
    public function methodExists(
        $method_id,
        ?CodeLocation $code_location = null,
        $calling_method_id = null,
        ?string $file_path = null,
        bool $is_used = true
    ): bool {
        return $this->methods->methodExists(
            MethodIdentifier::wrap($method_id),
            is_string($calling_method_id) ? strtolower($calling_method_id) : strtolower((string) $calling_method_id),
            $code_location,
            null,
            $file_path,
            true,
            $is_used
        );
    }

    /**
     * @param  string|MethodIdentifier $method_id
     *
     * @return array<int, FunctionLikeParameter>
     */
    public function getMethodParams($method_id): array
    {
        return $this->methods->getMethodParams(MethodIdentifier::wrap($method_id));
    }

    /**
     * @param  string|MethodIdentifier $method_id
     *
     */
    public function isVariadic($method_id): bool
    {
        return $this->methods->isVariadic(MethodIdentifier::wrap($method_id));
    }

    /**
     * @param  string|MethodIdentifier $method_id
     * @param  list<Arg> $call_args
     *
     */
    public function getMethodReturnType($method_id, ?string &$self_class, array $call_args = []): ?Union
    {
        return $this->methods->getMethodReturnType(
            MethodIdentifier::wrap($method_id),
            $self_class,
            null,
            $call_args
        );
    }

    /**
     * @param  string|MethodIdentifier $method_id
     *
     */
    public function getMethodReturnsByRef($method_id): bool
    {
        return $this->methods->getMethodReturnsByRef(MethodIdentifier::wrap($method_id));
    }

    /**
     * @param  string|MethodIdentifier $method_id
     * @param  CodeLocation|null       $defined_location
     *
     */
    public function getMethodReturnTypeLocation(
        $method_id,
        CodeLocation &$defined_location = null
    ): ?CodeLocation {
        return $this->methods->getMethodReturnTypeLocation(
            MethodIdentifier::wrap($method_id),
            $defined_location
        );
    }

    /**
     * @param  string|MethodIdentifier $method_id
     *
     */
    public function getDeclaringMethodId($method_id): ?string
    {
        $new_method_id = $this->methods->getDeclaringMethodId(MethodIdentifier::wrap($method_id));

        return $new_method_id ? (string) $new_method_id : null;
    }

    /**
     * Get the class this method appears in (vs is declared in, which could give a trait)
     *
     * @param  string|MethodIdentifier $method_id
     *
     */
    public function getAppearingMethodId($method_id): ?string
    {
        $new_method_id = $this->methods->getAppearingMethodId(MethodIdentifier::wrap($method_id));

        return $new_method_id ? (string) $new_method_id : null;
    }

    /**
     * @param  string|MethodIdentifier $method_id
     *
     * @return array<string, MethodIdentifier>
     */
    public function getOverriddenMethodIds($method_id): array
    {
        return $this->methods->getOverriddenMethodIds(MethodIdentifier::wrap($method_id));
    }

    /**
     * @param  string|MethodIdentifier $method_id
     *
     */
    public function getCasedMethodId($method_id): string
    {
        return $this->methods->getCasedMethodId(MethodIdentifier::wrap($method_id));
    }

    public function invalidateInformationForFile(string $file_path): void
    {
        $this->scanner->removeFile($file_path);

        try {
            $file_storage = $this->file_storage_provider->get($file_path);
        } catch (InvalidArgumentException $e) {
            return;
        }

        foreach ($file_storage->classlikes_in_file as $fq_classlike_name) {
            $this->classlike_storage_provider->remove($fq_classlike_name);
            $this->classlikes->removeClassLike($fq_classlike_name);
        }

        $this->file_storage_provider->remove($file_path);
    }

    public function getFunctionStorageForSymbol(string $file_path, string $symbol): ?FunctionLikeStorage
    {
        if (strpos($symbol, '::')) {
            $symbol = substr($symbol, 0, -2);
            /** @psalm-suppress ArgumentTypeCoercion */
            $method_id = new MethodIdentifier(...explode('::', $symbol));

            $declaring_method_id = $this->methods->getDeclaringMethodId($method_id);

            if (!$declaring_method_id) {
                return null;
            }

            return $this->methods->getStorage($declaring_method_id);
        }

        $function_id = strtolower(substr($symbol, 0, -2));
        $file_storage = $this->file_storage_provider->get($file_path);

        if (isset($file_storage->functions[$function_id])) {
            return $file_storage->functions[$function_id];
        }

        if (!$function_id) {
            return null;
        }

        return $this->functions->getStorage(null, $function_id);
    }

    /**
     * Checks if type is a subtype of other
     *
     * Given two types, checks if `$input_type` is a subtype of `$container_type`.
     * If you consider `Union` as a set of types, this will tell you
     * if `$input_type` is fully contained in `$container_type`,
     *
     * $input_type ⊆ $container_type
     *
     * Useful for emitting issues like InvalidArgument, where argument at the call site
     * should be a subset of the function parameter type.
     */
    public function isTypeContainedByType(
        Union $input_type,
        Union $container_type
    ): bool {
        return UnionTypeComparator::isContainedBy($this, $input_type, $container_type);
    }

    /**
     * Checks if type has any part that is a subtype of other
     *
     * Given two types, checks if *any part* of `$input_type` is a subtype of `$container_type`.
     * If you consider `Union` as a set of types, this will tell you if intersection
     * of `$input_type` with `$container_type` is not empty.
     *
     * $input_type ∩ $container_type ≠ ∅ , e.g. they are not disjoint.
     *
     * Useful for emitting issues like PossiblyInvalidArgument, where argument at the call
     * site should be a subtype of the function parameter type, but it's has some types that are
     * not a subtype of the required type.
     */
    public function canTypeBeContainedByType(
        Union $input_type,
        Union $container_type
    ): bool {
        return UnionTypeComparator::canBeContainedBy($this, $input_type, $container_type);
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
     * @return array{Union, Union}
     */
    public function getKeyValueParamsForTraversableObject(Atomic $type): array
    {
        $key_type = null;
        $value_type = null;

        ForeachAnalyzer::getKeyValueParamsForTraversableObject($type, $this, $key_type, $value_type);

        return [
            $key_type ?? Type::getMixed(),
            $value_type ?? Type::getMixed(),
        ];
    }

    /**
     * @param array<string, mixed> $phantom_classes
     * @psalm-suppress PossiblyUnusedMethod part of the public API
     */
    public function queueClassLikeForScanning(
        string $fq_classlike_name,
        bool $analyze_too = false,
        bool $store_failure = true,
        array $phantom_classes = []
    ): void {
        $this->scanner->queueClassLikeForScanning($fq_classlike_name, $analyze_too, $store_failure, $phantom_classes);
    }

    /**
     * @param array<string> $taints
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function addTaintSource(
        Union $expr_type,
        string $taint_id,
        array $taints = TaintKindGroup::ALL_INPUT,
        ?CodeLocation $code_location = null
    ): void {
        if (!$this->taint_flow_graph) {
            return;
        }

        $source = new TaintSource(
            $taint_id,
            $taint_id,
            $code_location,
            null,
            $taints
        );

        $this->taint_flow_graph->addSource($source);

        $expr_type->parent_nodes = [
            $source->id => $source,
        ];
    }

    /**
     * @param array<string> $taints
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function addTaintSink(
        string $taint_id,
        array $taints = TaintKindGroup::ALL_INPUT,
        ?CodeLocation $code_location = null
    ): void {
        if (!$this->taint_flow_graph) {
            return;
        }

        $sink = new TaintSink(
            $taint_id,
            $taint_id,
            $code_location,
            null,
            $taints
        );

        $this->taint_flow_graph->addSink($sink);
    }

        /**
     * @param array<string> $candidate_files
     *
     */
    public function reloadFiles(ProjectAnalyzer $project_analyzer, array $candidate_files, bool $force = false): void
    {
        $this->loadAnalyzer();

        if ($force) {
            FileReferenceProvider::clearCache();
        }

        $this->file_reference_provider->loadReferenceCache($force);

        FunctionLikeAnalyzer::clearCache();

        if ($force || !$this->statements_provider->parser_cache_provider) {
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
            if (in_array($referenced_file_path, $diff_files, true)) {
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

        $this->populator->populateCodebase();
    }
}
