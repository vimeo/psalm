<?php

declare(strict_types=1);

namespace Psalm;

use Exception;
use InvalidArgumentException;
use LanguageServerProtocol\Command;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use LanguageServerProtocol\InsertTextFormat;
use LanguageServerProtocol\ParameterInformation;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\SignatureInformation;
use LanguageServerProtocol\TextEdit;
use PhpParser;
use PhpParser\Node\Arg;
use Psalm\CodeLocation\Raw;
use Psalm\Exception\UnanalyzedFileException;
use Psalm\Exception\UnpopulatedClasslikeException;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\Analyzer;
use Psalm\Internal\Codebase\ClassLikes;
use Psalm\Internal\Codebase\Functions;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\Methods;
use Psalm\Internal\Codebase\Populator;
use Psalm\Internal\Codebase\Properties;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Internal\Codebase\Scanner;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Internal\DataFlow\TaintSource;
use Psalm\Internal\LanguageServer\PHPMarkdownContent;
use Psalm\Internal\LanguageServer\Reference;
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
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TaintKindGroup;
use Psalm\Type\Union;
use ReflectionProperty;
use ReflectionType;
use UnexpectedValueException;

use function array_combine;
use function array_key_exists;
use function array_pop;
use function array_reverse;
use function array_values;
use function count;
use function dirname;
use function error_log;
use function explode;
use function implode;
use function in_array;
use function intdiv;
use function is_numeric;
use function is_string;
use function krsort;
use function ksort;
use function preg_match;
use function preg_replace;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;
use function substr_count;

use const PHP_VERSION_ID;

/**
 * @api
 */
final class Codebase
{
    /**
     * A map of fully-qualified use declarations to the files
     * that reference them (keyed by filename)
     *
     * @var array<lowercase-string, array<int, CodeLocation>>
     */
    public array $use_referencing_locations = [];

    public FileStorageProvider $file_storage_provider;

    public ClassLikeStorageProvider $classlike_storage_provider;

    public bool $collect_references = false;

    public bool $collect_locations = false;

    /**
     * @var null|'always'|'auto'
     */
    public ?string $find_unused_code = null;

    public FileProvider $file_provider;

    public FileReferenceProvider $file_reference_provider;

    public StatementsProvider $statements_provider;

    public readonly Progress $progress;

    /**
     * @var array<string, Union>
     */
    private static array $stubbed_constants = [];

    /**
     * Whether to register autoloaded information
     */
    public bool $register_autoload_files = false;

    /**
     * Whether to log functions just at the file level or globally (for stubs)
     */
    public bool $register_stub_files = false;

    public bool $all_functions_global = false;

    public bool $all_constants_global = false;

    public bool $find_unused_variables = false;

    public Scanner $scanner;

    public Analyzer $analyzer;

    public Functions $functions;

    public ClassLikes $classlikes;

    public Methods $methods;

    public Properties $properties;

    public Populator $populator;

    public ?TaintFlowGraph $taint_flow_graph = null;

    public bool $server_mode = false;

    public bool $store_node_types = false;

    /**
     * Whether or not to infer types from usage. Computationally expensive, so turned off by default
     */
    public bool $infer_types_from_usage = false;

    public bool $alter_code = false;

    public bool $diff_methods = false;

    /** whether or not we only checked a part of the codebase */
    public bool $diff_run = false;

    public bool $language_server = false;

    /**
     * @var array<lowercase-string, string>
     */
    public array $methods_to_move = [];

    /**
     * @var array<lowercase-string, string>
     */
    public array $methods_to_rename = [];

    /**
     * @var array<string, string>
     */
    public array $properties_to_move = [];

    /**
     * @var array<string, string>
     */
    public array $properties_to_rename = [];

    /**
     * @var array<string, string>
     */
    public array $class_constants_to_move = [];

    /**
     * @var array<string, string>
     */
    public array $class_constants_to_rename = [];

    /**
     * @var array<lowercase-string, string>
     */
    public array $classes_to_move = [];

    /**
     * @var array<lowercase-string, string>
     */
    public array $call_transforms = [];

    /**
     * @var array<string, string>
     */
    public array $property_transforms = [];

    /**
     * @var array<string, string>
     */
    public array $class_constant_transforms = [];

    /**
     * @var array<lowercase-string, string>
     */
    public array $class_transforms = [];

    public bool $allow_backwards_incompatible_changes = true;

    public int $analysis_php_version_id = PHP_VERSION_ID;

    /** @var 'cli'|'config'|'composer'|'tests'|'runtime' */
    public string $php_version_source = 'runtime';

    public bool $track_unused_suppressions = false;

    public bool $literal_array_key_check = false;

    /** @internal */
    public function __construct(
        public Config $config,
        Providers $providers,
        ?Progress $progress = null,
    ) {
        if ($progress === null) {
            $progress = new VoidProgress();
        }
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
            $progress,
        );

        $this->loadAnalyzer();

        $this->functions = new Functions($providers->file_storage_provider, $reflection);

        $this->classlikes = new ClassLikes(
            $this->config,
            $providers->classlike_storage_provider,
            $providers->file_reference_provider,
            $this->scanner,
        );

        $this->properties = new Properties(
            $providers->classlike_storage_provider,
            $providers->file_reference_provider,
            $this->classlikes,
        );

        $this->methods = new Methods(
            $providers->classlike_storage_provider,
            $providers->file_reference_provider,
            $this->classlikes,
        );

        $this->populator = new Populator(
            $providers->classlike_storage_provider,
            $providers->file_storage_provider,
            $this->classlikes,
            $providers->file_reference_provider,
            $progress,
        );

        $this->loadAnalyzer();
    }

    private function loadAnalyzer(): void
    {
        $this->analyzer = new Analyzer(
            $this->config,
            $this->file_provider,
            $this->file_storage_provider,
            $this->progress,
        );
    }

    /**
     * @param array<string> $candidate_files
     */
    public function reloadFiles(ProjectAnalyzer $project_analyzer, array $candidate_files, bool $force = false): void
    {
        $this->loadAnalyzer();

        if ($force) {
            FileReferenceProvider::clearCache();
        }

        $this->file_reference_provider->loadReferenceCache(false);

        FunctionLikeAnalyzer::clearCache();

        if ($force || !$this->statements_provider->parser_cache_provider) {
            $diff_files = $candidate_files;
        } else {
            $diff_files = [];

            $parser_cache_provider = $this->statements_provider->parser_cache_provider;

            foreach ($candidate_files as $candidate_file_path) {
                $hash = $parser_cache_provider->getHash($candidate_file_path);
                if ($hash !== null &&
                    $hash !== $this->file_provider->getContents($candidate_file_path)
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
     */
    public function addFilesToAnalyze(array $files_to_analyze): void
    {
        $this->scanner->addFilesToDeepScan($files_to_analyze);
        $this->analyzer->addFilesToAnalyze($files_to_analyze);
    }

    /**
     * Scans all files their related files
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
    public function getStatementsForFile(string $file_path, ?Progress $progress = null): array
    {
        return $this->statements_provider->getStatementsForFile(
            $file_path,
            $this->analysis_php_version_id,
            $this->diff_methods
                || $this->diff_run
                || $this->language_server
                || $this->file_reference_provider->cache?->persistent,
            $progress ?? $this->progress,
        );
    }

    public function createClassLikeStorage(string $fq_classlike_name): ClassLikeStorage
    {
        return $this->classlike_storage_provider->create($fq_classlike_name);
    }

    public function cacheClassLikeStorage(ClassLikeStorage $classlike_storage, string $file_path): void
    {
        if (!$this->classlike_storage_provider->cache) {
            return;
        }

        $file_contents = $this->file_provider->getContents($file_path);
        $this->classlike_storage_provider->cache->writeToCache($classlike_storage, $file_path, $file_contents);
    }

    public function exhumeClassLikeStorage(string $fq_classlike_name, string $file_path): void
    {
        $file_contents = $this->file_provider->getContents($file_path);
        $storage = $this->classlike_storage_provider->exhume(
            $fq_classlike_name,
            $file_path,
            $file_contents,
        );

        if ($storage->is_trait) {
            $this->classlikes->addFullyQualifiedTraitName($storage->name, $file_path);
        } elseif ($storage->is_interface) {
            $this->classlikes->addFullyQualifiedInterfaceName($storage->name, $file_path);
        } elseif ($storage->is_enum) {
            $this->classlikes->addFullyQualifiedEnumName($storage->name, $file_path);
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

        if (str_contains($symbol, '::$')) {
            return $this->findReferencesToProperty($symbol);
        }

        if (str_contains($symbol, '::')) {
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
        /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
        [$fq_class_name, $property_name] = explode('::', $property_id);

        return $this->file_reference_provider->getClassPropertyLocations(
            strtolower($fq_class_name) . '::' . $property_name,
        );
    }

    /**
     * @return CodeLocation[]
     * @psalm-return array<int, CodeLocation>
     */
    public function findReferencesToClassLike(string $fq_class_name): array
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $locations = $this->file_reference_provider->getClassLocations($fq_class_name_lc);

        if (isset($this->use_referencing_locations[$fq_class_name_lc])) {
            $locations = [...$locations, ...$this->use_referencing_locations[$fq_class_name_lc]];
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
            'Expecting ' . $closure_id . ' to have storage in ' . $file_path,
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
     * @param array<string, Union> $stubs
     */
    public function addGlobalConstantTypes(array $stubs): void
    {
        self::$stubbed_constants += $stubs;
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
        ?string $calling_method_id = null,
    ): bool {
        return $this->classlikes->classOrInterfaceExists(
            $fq_class_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id,
        );
    }

    /**
     * Check whether a class/interface exists
     *
     * @psalm-assert-if-true class-string|interface-string|enum-string $fq_class_name
     */
    public function classOrInterfaceOrEnumExists(
        string $fq_class_name,
        ?CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null,
    ): bool {
        return $this->classlikes->classOrInterfaceOrEnumExists(
            $fq_class_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id,
        );
    }

    /** @psalm-mutation-free */
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
        ?string $calling_method_id = null,
    ): bool {
        return $this->classlikes->classExists(
            $fq_class_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id,
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
        ?string $calling_method_id = null,
    ): bool {
        return $this->classlikes->interfaceExists(
            $fq_interface_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id,
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
            $this->classlikes->getUnAliasedName($fq_interface_name),
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

    public function traitHasCorrectCasing(string $fq_trait_name): bool
    {
        return $this->classlikes->traitHasCorrectCasing($fq_trait_name);
    }

    /**
     * Given a function id, return the function like storage for
     * a method, closure, or function.
     *
     * @param non-empty-string $function_id
     * @return FunctionStorage|MethodStorage
     */
    public function getFunctionLikeStorage(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
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
     */
    public function methodExists(
        string|MethodIdentifier $method_id,
        ?CodeLocation $code_location = null,
        string|MethodIdentifier|null $calling_method_id = null,
        ?string $file_path = null,
        bool $is_used = true,
    ): bool {
        return $this->methods->methodExists(
            MethodIdentifier::wrap($method_id),
            is_string($calling_method_id) ? strtolower($calling_method_id) : strtolower((string) $calling_method_id),
            $code_location,
            null,
            $file_path,
            true,
            $is_used,
        );
    }

    /**
     * @return array<int, FunctionLikeParameter>
     */
    public function getMethodParams(string|MethodIdentifier $method_id): array
    {
        return $this->methods->getMethodParams(MethodIdentifier::wrap($method_id));
    }

    public function isVariadic(string|MethodIdentifier $method_id): bool
    {
        return $this->methods->isVariadic(MethodIdentifier::wrap($method_id));
    }

    /**
     * @param  list<Arg> $call_args
     */
    public function getMethodReturnType(
        string|MethodIdentifier $method_id,
        ?string &$self_class,
        array $call_args = [],
    ): ?Union {
        return $this->methods->getMethodReturnType(
            MethodIdentifier::wrap($method_id),
            $self_class,
            null,
            $call_args,
        );
    }

    public function getMethodReturnsByRef(string|MethodIdentifier $method_id): bool
    {
        return $this->methods->getMethodReturnsByRef(MethodIdentifier::wrap($method_id));
    }

    public function getMethodReturnTypeLocation(
        string|MethodIdentifier $method_id,
        ?CodeLocation &$defined_location = null,
    ): ?CodeLocation {
        return $this->methods->getMethodReturnTypeLocation(
            MethodIdentifier::wrap($method_id),
            $defined_location,
        );
    }

    public function getDeclaringMethodId(string|MethodIdentifier $method_id): ?string
    {
        $new_method_id = $this->methods->getDeclaringMethodId(MethodIdentifier::wrap($method_id));

        return $new_method_id ? (string) $new_method_id : null;
    }

    /**
     * Get the class this method appears in (vs is declared in, which could give a trait)
     */
    public function getAppearingMethodId(string|MethodIdentifier $method_id): ?string
    {
        $new_method_id = $this->methods->getAppearingMethodId(MethodIdentifier::wrap($method_id));

        return $new_method_id ? (string) $new_method_id : null;
    }

    /**
     * @return array<string, MethodIdentifier>
     */
    public function getOverriddenMethodIds(string|MethodIdentifier $method_id): array
    {
        return $this->methods->getOverriddenMethodIds(MethodIdentifier::wrap($method_id));
    }

    public function getCasedMethodId(string|MethodIdentifier $method_id): string
    {
        return $this->methods->getCasedMethodId(MethodIdentifier::wrap($method_id));
    }

    public function invalidateInformationForFile(string $file_path): void
    {
        $this->scanner->removeFile($file_path);

        try {
            $file_storage = $this->file_storage_provider->get($file_path);
        } catch (InvalidArgumentException) {
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
     * Get Markup content from Reference
     */
    public function getMarkupContentForSymbolByReference(
        Reference $reference,
    ): ?PHPMarkdownContent {
        //Direct Assignment
        if (is_numeric($reference->symbol[0])) {
            return new PHPMarkdownContent(
                (string) preg_replace(
                    '/^[^:]*:/',
                    '',
                    $reference->symbol,
                ),
            );
        }

        //Class
        if (strpos($reference->symbol, '::')) {
            //Class Method
            if (strpos($reference->symbol, '()')) {
                $symbol = substr($reference->symbol, 0, -2);

                /** @psalm-suppress ArgumentTypeCoercion */
                $method_id = new MethodIdentifier(...explode('::', $symbol));

                $declaring_method_id = $this->methods->getDeclaringMethodId(
                    $method_id,
                );

                if (!$declaring_method_id) {
                    return null;
                }

                $storage = $this->methods->getStorage($declaring_method_id);

                return new PHPMarkdownContent(
                    $storage->getHoverMarkdown(),
                    "{$storage->defining_fqcln}::{$storage->cased_name}",
                    $storage->description,
                );
            }

            /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
            [, $symbol_name] = explode('::', $reference->symbol);

            //Class Property
            if (str_contains($reference->symbol, '$')) {
                $property_id = (string) preg_replace('/^\\\\/', '', $reference->symbol);
                /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
                [$fq_class_name, $property_name] = explode('::$', $property_id);
                $class_storage = $this->classlikes->getStorageFor($fq_class_name);

                //Get Real Properties
                if (isset($class_storage->declaring_property_ids[$property_name])) {
                    $declaring_property_class = $class_storage->declaring_property_ids[$property_name];
                    $declaring_class_storage = $this->classlike_storage_provider->get($declaring_property_class);

                    if (isset($declaring_class_storage->properties[$property_name])) {
                        $storage = $declaring_class_storage->properties[$property_name];
                        return new PHPMarkdownContent(
                            "{$storage->getInfo()} {$symbol_name}",
                            $reference->symbol,
                            $storage->description,
                        );
                    }
                }

                //Get Docblock properties
                if (isset($class_storage->pseudo_property_set_types['$'.$property_name])) {
                    return new PHPMarkdownContent(
                        'public '.
                        (string) $class_storage->pseudo_property_set_types['$'.$property_name].' $'.$property_name,
                        $reference->symbol,
                    );
                }

                //Get Docblock properties
                if (isset($class_storage->pseudo_property_get_types['$'.$property_name])) {
                    return new PHPMarkdownContent(
                        'public '.
                        (string) $class_storage->pseudo_property_get_types['$'.$property_name].' $'.$property_name,
                        $reference->symbol,
                    );
                }

                return null;
            }

            /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
            [$fq_classlike_name, $const_name] = explode(
                '::',
                $reference->symbol,
            );

            $class_constants = $this->classlikes->getConstantsForClass(
                $fq_classlike_name,
                ReflectionProperty::IS_PRIVATE,
            );

            if (!isset($class_constants[$const_name])) {
                return null;
            }

            //Class Constant
            return new PHPMarkdownContent(
                $class_constants[$const_name]->getHoverMarkdown($const_name),
                $fq_classlike_name . '::' . $const_name,
                $class_constants[$const_name]->description,
            );
        }

        //Procedural Function
        if (strpos($reference->symbol, '()')) {
            $function_id = strtolower(substr($reference->symbol, 0, -2));
            $file_storage = $this->file_storage_provider->get(
                $reference->file_path,
            );

            if (isset($file_storage->functions[$function_id])) {
                $function_storage = $file_storage->functions[$function_id];

                return new PHPMarkdownContent(
                    $function_storage->getHoverMarkdown(),
                    $function_id,
                    $function_storage->description,
                );
            }

            if (!$function_id) {
                return null;
            }

            $function = $this->functions->getStorage(null, $function_id);

            return new PHPMarkdownContent(
                $function->getHoverMarkdown(),
                $function_id,
                $function->description,
            );
        }

        //Procedural Variable
        if (str_starts_with($reference->symbol, '$')) {
            $type = VariableFetchAnalyzer::getGlobalType($reference->symbol, $this->analysis_php_version_id);
            if (!$type->isMixed()) {
                return new PHPMarkdownContent(
                    (string) $type,
                    $reference->symbol,
                );
            }
        }

        try {
            $storage = $this->classlike_storage_provider->get(
                $reference->symbol,
            );
            return new PHPMarkdownContent(
                ($storage->abstract ? 'abstract ' : '') .
                    'class ' .
                    $storage->name,
                $storage->name,
                $storage->description,
            );
        } catch (InvalidArgumentException) {
            //continue on as normal
        }

        if (strpos($reference->symbol, '\\')) {
            $const_name_parts = explode('\\', $reference->symbol);
            $const_name = array_pop($const_name_parts);
            $namespace_name = implode('\\', $const_name_parts);

            $namespace_constants = NamespaceAnalyzer::getConstantsForNamespace(
                $namespace_name,
                ReflectionProperty::IS_PUBLIC,
            );
            //Namespace Constant
            if (isset($namespace_constants[$const_name])) {
                $type = $namespace_constants[$const_name];
                return new PHPMarkdownContent(
                    $reference->symbol . ' ' . $type,
                    $reference->symbol,
                );
            }
        } else {
            $file_storage = $this->file_storage_provider->get(
                $reference->file_path,
            );
            // ?
            if (isset($file_storage->constants[$reference->symbol])) {
                return new PHPMarkdownContent(
                    'const ' .
                        $reference->symbol .
                        ' ' .
                        $file_storage->constants[$reference->symbol],
                    $reference->symbol,
                );
            }
            $type = ConstFetchAnalyzer::getGlobalConstType(
                $this,
                $reference->symbol,
                $reference->symbol,
            );

            //Global Constant
            if ($type) {
                return new PHPMarkdownContent(
                    'const ' . $reference->symbol . ' ' . $type,
                    $reference->symbol,
                );
            }
        }

        return new PHPMarkdownContent($reference->symbol);
    }

    public function getSymbolLocationByReference(Reference $reference): ?CodeLocation
    {
        if (is_numeric($reference->symbol[0])) {
            $symbol = (string) preg_replace('/:.*/', '', $reference->symbol);
            $symbol_parts = explode('-', $symbol);

            if (!isset($symbol_parts[0]) || !isset($symbol_parts[1])) {
                return null;
            }

            $file_contents = $this->getFileContents($reference->file_path);

            return new Raw(
                $file_contents,
                $reference->file_path,
                $this->config->shortenFileName($reference->file_path),
                (int) $symbol_parts[0],
                (int) $symbol_parts[1],
            );
        }

        try {
            if (strpos($reference->symbol, '::')) {
                if (strpos($reference->symbol, '()')) {
                    $symbol = substr($reference->symbol, 0, -2);

                    /** @psalm-suppress ArgumentTypeCoercion */
                    $method_id = new MethodIdentifier(
                        ...explode('::', $symbol),
                    );

                    $declaring_method_id = $this->methods->getDeclaringMethodId(
                        $method_id,
                    );

                    if (!$declaring_method_id) {
                        return null;
                    }

                    $storage = $this->methods->getStorage($declaring_method_id);

                    return $storage->location;
                }

                if (str_contains($reference->symbol, '$')) {
                    $storage = $this->properties->getStorage(
                        $reference->symbol,
                    );

                    return $storage->location;
                }

                /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
                [$fq_classlike_name, $const_name] = explode(
                    '::',
                    $reference->symbol,
                );

                $class_constants = $this->classlikes->getConstantsForClass(
                    $fq_classlike_name,
                    ReflectionProperty::IS_PRIVATE,
                );

                if (!isset($class_constants[$const_name])) {
                    return null;
                }

                return $class_constants[$const_name]->location;
            }

            if (strpos($reference->symbol, '()')) {
                $file_storage = $this->file_storage_provider->get(
                    $reference->file_path,
                );

                $function_id = strtolower(substr($reference->symbol, 0, -2));

                if (isset($file_storage->functions[$function_id])) {
                    return $file_storage->functions[$function_id]->location;
                }

                if (!$function_id) {
                    return null;
                }

                return $this->functions->getStorage(null, $function_id)
                    ->location;
            }

            return $this->classlike_storage_provider->get(
                $reference->symbol,
            )->location;
        } catch (UnexpectedValueException $e) {
            error_log($e->getMessage());

            return null;
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return array{0: string, 1: Range}|null
     */
    public function getReferenceAtPosition(string $file_path, Position $position): ?array
    {
        $ref = $this->getReferenceAtPositionAsReference($file_path, $position);
        if ($ref === null) {
            return null;
        }
        return [$ref->symbol, $ref->range];
    }

    /**
     * Get Reference from Position
     */
    public function getReferenceAtPositionAsReference(
        string $file_path,
        Position $position,
    ): ?Reference {
        $is_open = $this->file_provider->isOpen($file_path);

        if (!$is_open) {
            throw new UnanalyzedFileException($file_path . ' is not open');
        }

        $file_contents = $this->getFileContents($file_path);

        $offset = $position->toOffset($file_contents);

        $reference_maps = $this->analyzer->getMapsForFile($file_path);

        $reference_start_pos = null;
        $reference_end_pos = null;
        $symbol = null;

        foreach ($reference_maps as $reference_map) {
            ksort($reference_map);

            foreach ($reference_map as $start_pos => [$end_pos, $possible_reference]) {
                if ($offset < $start_pos) {
                    break;
                }

                if ($offset > $end_pos) {
                    continue;
                }
                $reference_start_pos = $start_pos;
                $reference_end_pos = $end_pos;
                $symbol = $possible_reference;
            }

            if ($symbol !== null &&
                $reference_start_pos !== null &&
                $reference_end_pos !== null
            ) {
                break;
            }
        }

        if ($symbol === null || $reference_start_pos === null || $reference_end_pos === null) {
            return null;
        }

        $range = new Range(
            self::getPositionFromOffset($reference_start_pos, $file_contents),
            self::getPositionFromOffset($reference_end_pos, $file_contents),
        );

        return new Reference($file_path, $symbol, $range);
    }

    /**
     * @return array{0: non-empty-string, 1: int, 2: Range}|null
     */
    public function getFunctionArgumentAtPosition(string $file_path, Position $position): ?array
    {
        $is_open = $this->file_provider->isOpen($file_path);

        if (!$is_open) {
            throw new UnanalyzedFileException($file_path . ' is not open');
        }

        $file_contents = $this->getFileContents($file_path);

        $offset = $position->toOffset($file_contents);

        [, , $argument_map] = $this->analyzer->getMapsForFile($file_path);

        $reference = null;
        $argument_number = null;

        if (!$argument_map) {
            return null;
        }

        $start_pos = null;
        $end_pos = null;

        ksort($argument_map);

        foreach ($argument_map as $start_pos => [$end_pos, $possible_reference, $possible_argument_number]) {
            if ($offset < $start_pos) {
                break;
            }

            if ($offset > $end_pos) {
                continue;
            }

            $reference = $possible_reference;
            $argument_number = $possible_argument_number;
        }

        if ($reference === null || $start_pos === null || $end_pos === null || $argument_number === null) {
            return null;
        }

        $range = new Range(
            self::getPositionFromOffset($start_pos, $file_contents),
            self::getPositionFromOffset($end_pos, $file_contents),
        );

        return [$reference, $argument_number, $range];
    }

    /**
     * @param  non-empty-string $function_symbol
     */
    public function getSignatureInformation(
        string $function_symbol,
        ?string $file_path = null,
    ): ?SignatureInformation {
        $signature_label = '';
        $signature_documentation = null;
        if (str_contains($function_symbol, '::')) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $method_id = new MethodIdentifier(...explode('::', $function_symbol));

            $declaring_method_id = $this->methods->getDeclaringMethodId($method_id);

            if ($declaring_method_id === null) {
                return null;
            }

            $method_storage = $this->methods->getStorage($declaring_method_id);
            $params = $method_storage->params;
            $signature_label = $method_storage->cased_name;
            $signature_documentation = $method_storage->description;
        } else {
            try {
                if ($file_path) {
                    $function_storage = $this->functions->getStorage(
                        null,
                        strtolower($function_symbol),
                        dirname($file_path),
                        $file_path,
                    );
                } else {
                    $function_storage = $this->functions->getStorage(null, strtolower($function_symbol));
                }
                $params = $function_storage->params;
                $signature_label = $function_storage->cased_name;
                $signature_documentation = $function_storage->description;
            } catch (Exception) {
                if (InternalCallMapHandler::inCallMap($function_symbol)) {
                    $callables = InternalCallMapHandler::getCallablesFromCallMap($function_symbol);

                    if (!$callables || !isset($callables[0]->params)) {
                        return null;
                    }

                    $params = $callables[0]->params;
                } else {
                    return null;
                }
            }
        }

        $signature_label .= '(';
        $parameters = [];

        foreach ($params as $i => $param) {
            $parameter_label = ($param->type ?: 'mixed') . ' $' . $param->name;
            $parameters[] = new ParameterInformation(
                [
                    strlen($signature_label),
                    strlen($signature_label) + strlen($parameter_label),
                ],
                $param->description ?? null,
            );

            $signature_label .= $parameter_label;

            if ($i < (count($params) - 1)) {
                $signature_label .= ', ';
            }
        }

        $signature_label .= ')';

        return new SignatureInformation(
            $signature_label,
            $parameters,
            $signature_documentation,
        );
    }

    /**
     * @return array{0: string, 1: '->'|'::'|'['|'symbol', 2: int}|null
     */
    public function getCompletionDataAtPosition(string $file_path, Position $position): ?array
    {
        $is_open = $this->file_provider->isOpen($file_path);

        if (!$is_open) {
            throw new UnanalyzedFileException($file_path . ' is not open');
        }

        $file_contents = $this->getFileContents($file_path);

        $offset = $position->toOffset($file_contents);

        $literal_part = $this->getBeginedLiteralPart($file_path, $position);
        $begin_literal_offset = $offset - strlen($literal_part);

        [$reference_map, $type_map] = $this->analyzer->getMapsForFile($file_path);

        if (!$reference_map && !$type_map) {
            return null;
        }

        krsort($type_map);

        foreach ($type_map as $start_pos => [$end_pos_excluding_whitespace, $possible_type]) {
            if ($offset < $start_pos) {
                continue;
            }

            /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
            $num_whitespace_bytes = preg_match('/\G\s+/', $file_contents, $matches, 0, $end_pos_excluding_whitespace)
                ? strlen($matches[0])
                : 0;
            $end_pos = $end_pos_excluding_whitespace + $num_whitespace_bytes;

            if ($offset - $end_pos === 1) {
                $candidate_gap = substr($file_contents, $end_pos, 1);

                if ($candidate_gap === '[') {
                    $gap = $candidate_gap;
                    $recent_type = $possible_type;

                    if ($recent_type === 'mixed') {
                        return null;
                    }

                    return [$recent_type, $gap, $offset];
                }
            }

            if ($begin_literal_offset - $end_pos === 2) {
                $candidate_gap = substr($file_contents, $end_pos, 2);

                if ($candidate_gap === '->' || $candidate_gap === '::') {
                    $gap = $candidate_gap;
                    $recent_type = $possible_type;

                    if ($recent_type === 'mixed') {
                        return null;
                    }

                    return [$recent_type, $gap, $offset];
                }
            }
        }

        foreach ($reference_map as $start_pos => [$end_pos, $possible_reference]) {
            if ($offset < $start_pos) {
                continue;
            }
            // If the reference precedes a "::" then treat it as a class reference.
            if ($offset - $end_pos === 2 && substr($file_contents, $end_pos, 2) === '::') {
                return [$possible_reference, '::', $offset];
            }

            if ($offset <= $end_pos && substr($file_contents, $begin_literal_offset - 2, 2) === '::') {
                $class_name = explode('::', $possible_reference)[0];
                return [$class_name, '::', $offset];
            }

            // Only continue for references that are partial / don't exist.
            if ($possible_reference[0] !== '*') {
                continue;
            }

            if ($offset - $end_pos === 0) {
                $recent_type = $possible_reference;

                return [$recent_type, 'symbol', $offset];
            }
        }

        return null;
    }

    public function getBeginedLiteralPart(string $file_path, Position $position): string
    {
        $is_open = $this->file_provider->isOpen($file_path);

        if (!$is_open) {
            throw new UnanalyzedFileException($file_path . ' is not open');
        }

        $file_contents = $this->getFileContents($file_path);

        $offset = $position->toOffset($file_contents);

        preg_match('/\$?\w+$/', substr($file_contents, 0, $offset), $matches);

        return $matches[0] ?? '';
    }

    public function getTypeContextAtPosition(string $file_path, Position $position): ?Union
    {
        $file_contents = $this->getFileContents($file_path);
        $offset = $position->toOffset($file_contents);

        [$reference_map, $type_map, $argument_map] = $this->analyzer->getMapsForFile($file_path);
        if (!$reference_map && !$type_map && !$argument_map) {
            return null;
        }
        foreach ($argument_map as $start_pos => [$end_pos, $function, $argument_num]) {
            if ($offset < $start_pos || $offset > $end_pos) {
                continue;
            }
            // First parameter to a function-like
            $function_storage = $this->getFunctionStorageForSymbol($file_path, $function . '()');
            if (!$function_storage || !$function_storage->params || !isset($function_storage->params[$argument_num])) {
                return null;
            }

            return $function_storage->params[$argument_num]->type;
        }

        return null;
    }

    /**
     * @param list<int> $allow_visibilities
     * @param list<string> $ignore_fq_class_names
     * @return list<CompletionItem>
     */
    public function getCompletionItemsForClassishThing(
        string $type_string,
        string $gap,
        bool $snippets_supported = false,
        ?array $allow_visibilities = null,
        array $ignore_fq_class_names = [],
    ): array {
        if ($allow_visibilities === null) {
            $allow_visibilities = [
                ClassLikeAnalyzer::VISIBILITY_PUBLIC,
                ClassLikeAnalyzer::VISIBILITY_PROTECTED,
                ClassLikeAnalyzer::VISIBILITY_PRIVATE,
            ];
        }
        $allow_visibilities[] = null;

        $completion_items = [];

        $type = Type::parseString($type_string);

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TNamedObject) {
                try {
                    $class_storage = $this->classlike_storage_provider->get($atomic_type->value);

                    $method_storages = [];
                    foreach ($class_storage->declaring_method_ids as $declaring_method_id) {
                        try {
                            $method_storages[] = $this->methods->getStorage($declaring_method_id);
                        } catch (UnexpectedValueException $e) {
                            error_log($e->getMessage());
                        }
                    }
                    if ($gap === '->') {
                        $method_storages += $class_storage->pseudo_methods;
                    }
                    if ($gap === '::') {
                        $method_storages += $class_storage->pseudo_static_methods;
                    }

                    $had = [];
                    foreach ($method_storages as $method_storage) {
                        if (!in_array($method_storage->visibility, $allow_visibilities)) {
                            continue;
                        }
                        if ($method_storage->cased_name !== null) {
                            if (array_key_exists($method_storage->cased_name, $had)) {
                                continue;
                            }
                            $had[$method_storage->cased_name] = true;
                        }
                        if ($method_storage->is_static || $gap === '->') {
                            $completion_item = new CompletionItem(
                                $method_storage->cased_name,
                                CompletionItemKind::METHOD,
                                $method_storage->getCompletionSignature(),
                                $method_storage->description,
                                (string)$method_storage->visibility,
                                $method_storage->cased_name,
                                $method_storage->cased_name,
                                null,
                                null,
                                new Command('Trigger parameter hints', 'editor.action.triggerParameterHints'),
                                null,
                                2,
                            );

                            if ($snippets_supported && count($method_storage->params) > 0) {
                                $completion_item->insertText .= '($0)';
                                $completion_item->insertTextFormat =
                                    InsertTextFormat::SNIPPET;
                            } else {
                                $completion_item->insertText .= '()';
                            }

                            $completion_items[] = $completion_item;
                        }
                    }

                    if ($gap === '->') {
                        $pseudo_property_types = [];
                        foreach ($class_storage->pseudo_property_get_types as $property_name => $type) {
                            $pseudo_property_types[$property_name] = new CompletionItem(
                                str_replace('$', '', $property_name),
                                CompletionItemKind::PROPERTY,
                                $type->__toString(),
                                null,
                                '1', //sort text
                                str_replace('$', '', $property_name),
                                str_replace('$', '', $property_name),
                            );
                        }

                        foreach ($class_storage->pseudo_property_set_types as $property_name => $type) {
                            $pseudo_property_types[$property_name] = new CompletionItem(
                                str_replace('$', '', $property_name),
                                CompletionItemKind::PROPERTY,
                                $type->__toString(),
                                null,
                                '1',
                                str_replace('$', '', $property_name),
                                str_replace('$', '', $property_name),
                            );
                        }

                        $completion_items = [...$completion_items, ...array_values($pseudo_property_types)];
                    }

                    foreach ($class_storage->declaring_property_ids as $property_name => $declaring_class) {
                        try {
                            $property_storage = $this->properties->getStorage(
                                $declaring_class . '::$' . $property_name,
                            );
                        } catch (UnexpectedValueException $e) {
                            error_log($e->getMessage());
                            continue;
                        }

                        if (!in_array($property_storage->visibility, $allow_visibilities)) {
                            continue;
                        }
                        if ($property_storage->is_static === ($gap === '::')) {
                            $completion_items[] = new CompletionItem(
                                $property_name,
                                CompletionItemKind::PROPERTY,
                                $property_storage->getInfo(),
                                $property_storage->description,
                                (string)$property_storage->visibility,
                                $property_name,
                                ($gap === '::' ? '$' : '') . $property_name,
                            );
                        }
                    }

                    foreach ($class_storage->constants as $const_name => $const) {
                        $completion_items[] = new CompletionItem(
                            $const_name,
                            CompletionItemKind::VARIABLE,
                            'const ' . $const_name,
                            $const->description,
                            null,
                            $const_name,
                            $const_name,
                        );
                    }

                    if ($gap === '->') {
                        foreach ($class_storage->namedMixins as $mixin) {
                            if (in_array($mixin->value, $ignore_fq_class_names)) {
                                continue;
                            }
                            $mixin_completion_items = $this->getCompletionItemsForClassishThing(
                                $mixin->value,
                                $gap,
                                $snippets_supported,
                                [ClassLikeAnalyzer::VISIBILITY_PUBLIC],
                                [$type_string, ...$ignore_fq_class_names],
                            );
                            $completion_items = [...$completion_items, ...$mixin_completion_items];
                        }
                    }
                } catch (Exception $e) {
                    error_log($e->getMessage());
                    continue;
                }
            }
        }

        return $completion_items;
    }

    /**
     * @param list<CompletionItem> $items
     * @return list<CompletionItem>
     * @deprecated to be removed in Psalm 6
     * @api fix deprecation problem "PossiblyUnusedMethod: Cannot find any calls to method"
     */
    public function filterCompletionItemsByBeginLiteralPart(array $items, string $literal_part): array
    {
        if (!$literal_part) {
            return $items;
        }

        $res = [];
        foreach ($items as $item) {
            if ($item->insertText && str_starts_with($item->insertText, $literal_part)) {
                $res[] = $item;
            }
        }

        return $res;
    }

    /**
     * @return list<CompletionItem>
     */
    public function getCompletionItemsForPartialSymbol(
        string $type_string,
        int $offset,
        string $file_path,
    ): array {
        $fq_suggestion = false;

        if (($type_string[1] ?? '') === '\\') {
            $fq_suggestion = true;
        }

        $matching_classlike_names = $this->classlikes->getMatchingClassLikeNames($type_string);

        $completion_items = [];

        $file_storage = $this->file_storage_provider->get($file_path);

        $aliases = null;

        foreach ($file_storage->classlikes_in_file as $fq_class_name => $_) {
            try {
                $class_storage = $this->classlike_storage_provider->get($fq_class_name);
            } catch (Exception) {
                continue;
            }

            if (!$class_storage->stmt_location) {
                continue;
            }

            if ($offset > $class_storage->stmt_location->raw_file_start
                && $offset < $class_storage->stmt_location->raw_file_end
            ) {
                $aliases = $class_storage->aliases;
                break;
            }
        }

        if (!$aliases) {
            foreach ($file_storage->namespace_aliases as $namespace_start => $namespace_aliases) {
                if ($namespace_start < $offset) {
                    $aliases = $namespace_aliases;
                    break;
                }
            }

            if (!$aliases) {
                $aliases = $file_storage->aliases;
            }
        }

        foreach ($matching_classlike_names as $fq_class_name) {
            $extra_edits = [];

            $insertion_text = Type::getStringFromFQCLN(
                $fq_class_name,
                $aliases && $aliases->namespace ? $aliases->namespace : null,
                $aliases->uses_flipped ?? [],
                null,
            );

            if ($aliases
                && !$fq_suggestion
                && $aliases->namespace
                && $insertion_text === '\\' . $fq_class_name
                && $aliases->namespace_first_stmt_start
            ) {
                $file_contents = $this->getFileContents($file_path);

                $class_name = (string) preg_replace('/^.*\\\/', '', $fq_class_name, 1);

                if ($aliases->uses_end) {
                    $position = self::getPositionFromOffset($aliases->uses_end, $file_contents);
                    $extra_edits[] = new TextEdit(
                        new Range(
                            $position,
                            $position,
                        ),
                        "\n" . 'use ' . $fq_class_name . ';',
                    );
                } else {
                    $position = self::getPositionFromOffset($aliases->namespace_first_stmt_start, $file_contents);
                    $extra_edits[] = new TextEdit(
                        new Range(
                            $position,
                            $position,
                        ),
                        'use ' . $fq_class_name . ';' . "\n" . "\n",
                    );
                }

                $insertion_text = $class_name;
            }

            try {
                $class_storage = $this->classlike_storage_provider->get($fq_class_name);
                $description = $class_storage->description;
            } catch (Exception) {
                $description = null;
            }

            $completion_items[] = new CompletionItem(
                $fq_class_name,
                CompletionItemKind::CLASS_,
                null,
                $description,
                null,
                $fq_class_name,
                $insertion_text,
                null,
                $extra_edits,
            );
        }

        $functions = $this->functions->getMatchingFunctionNames($type_string, $offset, $file_path, $this);

        $namespace_map = [];
        if ($aliases) {
            $namespace_map += $aliases->uses_flipped;
            if ($aliases->namespace) {
                $namespace_map[$aliases->namespace] = '';
            }
        }

        // Sort the map by longest first, so we replace most specific
        // used namespaces first.
        ksort($namespace_map);
        $namespace_map = array_reverse($namespace_map);

        foreach ($functions as $function_lowercase => $function) {
            // Transform FQFN relative to all uses namespaces
            $function_name = $function->cased_name;
            if (!$function_name) {
                continue;
            }
            $in_namespace_map = false;
            foreach ($namespace_map as $namespace_name => $namespace_alias) {
                if (str_starts_with($function_lowercase, $namespace_name . '\\')) {
                    $function_name = $namespace_alias . '\\' . substr($function_name, strlen($namespace_name) + 1);
                    $in_namespace_map = true;
                }
            }
            // If the function is not use'd, and it's not a global function
            // prepend it with a backslash.
            if (!$in_namespace_map && str_contains($function_name, '\\')) {
                $function_name = '\\' . $function_name;
            }
            $completion_items[] = new CompletionItem(
                $function_name,
                CompletionItemKind::FUNCTION,
                $function->getCompletionSignature(),
                $function->description,
                null,
                $function_name,
                $function_name . (count($function->params) !== 0 ? '($0)' : '()'),
                null,
                null,
                new Command('Trigger parameter hints', 'editor.action.triggerParameterHints'),
                null,
                2,
            );
        }

        return $completion_items;
    }

    /**
     * @return list<CompletionItem>
     */
    public function getCompletionItemsForType(Union $type): array
    {
        $completion_items = [];
        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TBool) {
                $bools = (string) $atomic_type === 'bool' ? ['true', 'false'] : [(string) $atomic_type];
                foreach ($bools as $property_name) {
                    $completion_items[] = new CompletionItem(
                        $property_name,
                        CompletionItemKind::VALUE,
                        'bool',
                        null,
                        null,
                        null,
                        $property_name,
                    );
                }
            } elseif ($atomic_type instanceof TLiteralString) {
                $completion_items[] = new CompletionItem(
                    $atomic_type->value,
                    CompletionItemKind::VALUE,
                    $atomic_type->getId(),
                    null,
                    null,
                    null,
                    "'$atomic_type->value'",
                );
            } elseif ($atomic_type instanceof TLiteralInt) {
                $completion_items[] = new CompletionItem(
                    (string) $atomic_type->value,
                    CompletionItemKind::VALUE,
                    $atomic_type->getId(),
                    null,
                    null,
                    null,
                    (string) $atomic_type->value,
                );
            } elseif ($atomic_type instanceof TClassConstant) {
                $const = $atomic_type->fq_classlike_name . '::' . $atomic_type->const_name;
                $completion_items[] = new CompletionItem(
                    $const,
                    CompletionItemKind::VALUE,
                    $atomic_type->getId(),
                    null,
                    null,
                    null,
                    $const,
                );
            }
        }
        return $completion_items;
    }

    /**
     * @return list<CompletionItem>
     */
    public function getCompletionItemsForArrayKeys(
        string $type_string,
    ): array {
        $completion_items = [];
        $type = Type::parseString($type_string);
        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TKeyedArray) {
                foreach ($atomic_type->properties as $property_name => $property) {
                    $completion_items[] = new CompletionItem(
                        (string) $property_name,
                        CompletionItemKind::PROPERTY,
                        (string) $property,
                        null,
                        null,
                        null,
                        "'$property_name'",
                    );
                }
            }
        }
        return $completion_items;
    }

    private static function getPositionFromOffset(int $offset, string $file_contents): Position
    {
        $file_contents = substr($file_contents, 0, $offset);

        $offsetLength = $offset - strlen($file_contents);

        //PHP 8.0: Argument #3 ($offset) must be contained in argument #1 ($haystack)
        if (($textlen = strlen($file_contents)) < $offsetLength) {
            $offsetLength = $textlen;
        }

        $before_newline_count = strrpos($file_contents, "\n", $offsetLength);

        return new Position(
            substr_count($file_contents, "\n"),
            $offset - (int)$before_newline_count - 1,
        );
    }

    public function addTemporaryFileChanges(string $file_path, string $new_content, ?int $version = null): void
    {
        $this->file_provider->addTemporaryFileChanges($file_path, $new_content, $version);
    }

    public function removeTemporaryFileChanges(string $file_path): void
    {
        $this->file_provider->removeTemporaryFileChanges($file_path);
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
        Union $container_type,
        bool $ignore_null = false,
        bool $ignore_false = false,
        bool $allow_interface_equality = false,
        bool $allow_float_int_equality = true,
    ): bool {
        return UnionTypeComparator::isContainedBy(
            $this,
            $input_type,
            $container_type,
            $ignore_null,
            $ignore_false,
            null,
            $allow_interface_equality,
            $allow_float_int_equality,
        );
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
        Union $container_type,
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
     */
    public function queueClassLikeForScanning(
        string $fq_classlike_name,
        bool $analyze_too = false,
        bool $store_failure = true,
        array $phantom_classes = [],
    ): void {
        $this->scanner->queueClassLikeForScanning($fq_classlike_name, $analyze_too, $store_failure, $phantom_classes);
    }

    /**
     * @param array<string> $taints
     */
    public function addTaintSource(
        Union $expr_type,
        string $taint_id,
        array $taints = TaintKindGroup::ALL_INPUT,
        ?CodeLocation $code_location = null,
    ): Union {
        if (!$this->taint_flow_graph) {
            return $expr_type;
        }

        $source = new TaintSource(
            $taint_id,
            $taint_id,
            $code_location,
            null,
            $taints,
        );

        $this->taint_flow_graph->addSource($source);

        return $expr_type->addParentNodes([$source->id => $source]);
    }

    /**
     * @param array<string> $taints
     */
    public function addTaintSink(
        string $taint_id,
        array $taints = TaintKindGroup::ALL_INPUT,
        ?CodeLocation $code_location = null,
    ): void {
        if (!$this->taint_flow_graph) {
            return;
        }

        $sink = new TaintSink(
            $taint_id,
            $taint_id,
            $code_location,
            null,
            $taints,
        );

        $this->taint_flow_graph->addSink($sink);
    }

    public function getMinorAnalysisPhpVersion(): int
    {
        return self::transformPhpVersionId($this->analysis_php_version_id % 10_000, 100);
    }

    public function getMajorAnalysisPhpVersion(): int
    {
        return self::transformPhpVersionId($this->analysis_php_version_id, 10_000);
    }

    public static function transformPhpVersionId(int $php_version_id, int $div): int
    {
        return intdiv($php_version_id, $div);
    }
}
