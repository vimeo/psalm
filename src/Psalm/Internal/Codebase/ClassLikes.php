<?php
namespace Psalm\Internal\Codebase;

use function array_merge;
use function array_pop;
use function count;
use function end;
use function explode;
use function get_declared_classes;
use function get_declared_interfaces;
use function implode;
use const PHP_EOL;
use PhpParser;
use function preg_match;
use function preg_replace;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Exception\UnpopulatedClasslikeException;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Internal\Scanner\UnresolvedConstant;
use Psalm\Issue\PossiblyUnusedMethod;
use Psalm\Issue\PossiblyUnusedParam;
use Psalm\Issue\PossiblyUnusedProperty;
use Psalm\Issue\UnusedClass;
use Psalm\Issue\UnusedMethod;
use Psalm\Issue\UnusedProperty;
use Psalm\IssueBuffer;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use ReflectionProperty;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;

/**
 * @internal
 *
 * Handles information about classes, interfaces and traits
 */
class ClassLikes
{
    /**
     * @var ClassLikeStorageProvider
     */
    private $classlike_storage_provider;

    /**
     * @var FileReferenceProvider
     */
    public $file_reference_provider;

    /**
     * @var array<lowercase-string, bool>
     */
    private $existing_classlikes_lc = [];

    /**
     * @var array<lowercase-string, bool>
     */
    private $existing_classes_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_classes = [];

    /**
     * @var array<lowercase-string, bool>
     */
    private $existing_interfaces_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_interfaces = [];

    /**
     * @var array<lowercase-string, bool>
     */
    private $existing_traits_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_traits = [];

    /**
     * @var array<string, string>
     */
    private $classlike_aliases = [];

    /**
     * @var array<string, PhpParser\Node\Stmt\Trait_>
     */
    private $trait_nodes = [];

    /**
     * @var bool
     */
    public $collect_references = false;

    /**
     * @var bool
     */
    public $collect_locations = false;

    /**
     * @var StatementsProvider
     */
    private $statements_provider;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Scanner
     */
    private $scanner;

    public function __construct(
        Config $config,
        ClassLikeStorageProvider $storage_provider,
        FileReferenceProvider $file_reference_provider,
        StatementsProvider $statements_provider,
        Scanner $scanner
    ) {
        $this->config = $config;
        $this->classlike_storage_provider = $storage_provider;
        $this->file_reference_provider = $file_reference_provider;
        $this->statements_provider = $statements_provider;
        $this->scanner = $scanner;

        $this->collectPredefinedClassLikes();
    }

    /**
     * @return void
     */
    private function collectPredefinedClassLikes()
    {
        /** @var array<int, string> */
        $predefined_classes = get_declared_classes();

        foreach ($predefined_classes as $predefined_class) {
            $predefined_class = preg_replace('/^\\\/', '', $predefined_class);
            /** @psalm-suppress TypeCoercion */
            $reflection_class = new \ReflectionClass($predefined_class);

            if (!$reflection_class->isUserDefined()) {
                $predefined_class_lc = strtolower($predefined_class);
                $this->existing_classlikes_lc[$predefined_class_lc] = true;
                $this->existing_classes_lc[$predefined_class_lc] = true;
                $this->existing_classes[$predefined_class] = true;
            }
        }

        /** @var array<int, string> */
        $predefined_interfaces = get_declared_interfaces();

        foreach ($predefined_interfaces as $predefined_interface) {
            $predefined_interface = preg_replace('/^\\\/', '', $predefined_interface);
            /** @psalm-suppress TypeCoercion */
            $reflection_class = new \ReflectionClass($predefined_interface);

            if (!$reflection_class->isUserDefined()) {
                $predefined_interface_lc = strtolower($predefined_interface);
                $this->existing_classlikes_lc[$predefined_interface_lc] = true;
                $this->existing_interfaces_lc[$predefined_interface_lc] = true;
                $this->existing_interfaces[$predefined_interface] = true;
            }
        }
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
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
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
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
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
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    /**
     * @param string        $fq_class_name_lc
     * @param string|null   $file_path
     *
     * @return void
     */
    public function addFullyQualifiedClassLikeName($fq_class_name_lc, $file_path = null)
    {
        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    /**
     * @return string[]
     */
    public function getMatchingClassLikeNames(string $stub) : array
    {
        $matching_classes = [];

        if ($stub[0] === '*') {
            $stub = substr($stub, 1);
        }

        $stub = strtolower($stub);

        foreach ($this->existing_classes as $fq_classlike_name => $found) {
            if (!$found) {
                continue;
            }

            if (preg_match('@(^|\\\)' . $stub . '.*@i', $fq_classlike_name)) {
                $matching_classes[] = $fq_classlike_name;
            }
        }

        foreach ($this->existing_interfaces as $fq_classlike_name => $found) {
            if (!$found) {
                continue;
            }

            if (preg_match('@(^|\\\)' . $stub . '.*@i', $fq_classlike_name)) {
                $matching_classes[] = $fq_classlike_name;
            }
        }

        return $matching_classes;
    }

    /**
     * @param string $fq_class_name
     *
     * @return bool
     */
    public function hasFullyQualifiedClassName(
        $fq_class_name,
        CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ) {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (isset($this->classlike_aliases[$fq_class_name_lc])) {
            $fq_class_name_lc = strtolower($this->classlike_aliases[$fq_class_name_lc]);
        }

        if ($code_location) {
            if ($calling_method_id) {
                $this->file_reference_provider->addMethodReferenceToClass(
                    $calling_method_id,
                    $fq_class_name_lc
                );
            } elseif (!$calling_fq_class_name || strtolower($calling_fq_class_name) !== $fq_class_name_lc) {
                $this->file_reference_provider->addNonMethodReferenceToClass(
                    $code_location->file_path,
                    $fq_class_name_lc
                );

                if ($calling_fq_class_name) {
                    $class_storage = $this->classlike_storage_provider->get($calling_fq_class_name);

                    if ($class_storage->location
                        && $class_storage->location->file_path !== $code_location->file_path
                    ) {
                        $this->file_reference_provider->addNonMethodReferenceToClass(
                            $class_storage->location->file_path,
                            $fq_class_name_lc
                        );
                    }
                }
            }
        }

        if (!isset($this->existing_classes_lc[$fq_class_name_lc])
            || !$this->existing_classes_lc[$fq_class_name_lc]
            || !$this->classlike_storage_provider->has($fq_class_name_lc)
        ) {
            if ((
                !isset($this->existing_classes_lc[$fq_class_name_lc])
                    || $this->existing_classes_lc[$fq_class_name_lc]
                )
                && !$this->classlike_storage_provider->has($fq_class_name_lc)
            ) {
                if (!isset($this->existing_classes_lc[$fq_class_name_lc])) {
                    $this->existing_classes_lc[$fq_class_name_lc] = false;

                    return false;
                }

                return $this->existing_classes_lc[$fq_class_name_lc];
            }

            return false;
        }

        if ($this->collect_locations && $code_location) {
            $this->file_reference_provider->addCallingLocationForClass(
                $code_location,
                strtolower($fq_class_name)
            );
        }

        return true;
    }

    /**
     * @param string $fq_class_name
     *
     * @return bool
     */
    public function hasFullyQualifiedInterfaceName(
        $fq_class_name,
        CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ) {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (isset($this->classlike_aliases[$fq_class_name_lc])) {
            $fq_class_name_lc = strtolower($this->classlike_aliases[$fq_class_name_lc]);
        }

        if (!isset($this->existing_interfaces_lc[$fq_class_name_lc])
            || !$this->existing_interfaces_lc[$fq_class_name_lc]
            || !$this->classlike_storage_provider->has($fq_class_name_lc)
        ) {
            if ((
                !isset($this->existing_classes_lc[$fq_class_name_lc])
                    || $this->existing_classes_lc[$fq_class_name_lc]
                )
                && !$this->classlike_storage_provider->has($fq_class_name_lc)
            ) {
                if (!isset($this->existing_interfaces_lc[$fq_class_name_lc])) {
                    $this->existing_interfaces_lc[$fq_class_name_lc] = false;

                    return false;
                }

                return $this->existing_interfaces_lc[$fq_class_name_lc];
            }

            return false;
        }

        if ($this->collect_references && $code_location) {
            if ($calling_method_id) {
                $this->file_reference_provider->addMethodReferenceToClass(
                    $calling_method_id,
                    $fq_class_name_lc
                );
            } else {
                $this->file_reference_provider->addNonMethodReferenceToClass(
                    $code_location->file_path,
                    $fq_class_name_lc
                );

                if ($calling_fq_class_name) {
                    $class_storage = $this->classlike_storage_provider->get($calling_fq_class_name);

                    if ($class_storage->location
                        && $class_storage->location->file_path !== $code_location->file_path
                    ) {
                        $this->file_reference_provider->addNonMethodReferenceToClass(
                            $class_storage->location->file_path,
                            $fq_class_name_lc
                        );
                    }
                }
            }
        }

        if ($this->collect_locations && $code_location) {
            $this->file_reference_provider->addCallingLocationForClass(
                $code_location,
                strtolower($fq_class_name)
            );
        }

        return true;
    }

    /**
     * @param string $fq_class_name
     *
     * @return bool
     */
    public function hasFullyQualifiedTraitName($fq_class_name, CodeLocation $code_location = null)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (isset($this->classlike_aliases[$fq_class_name_lc])) {
            $fq_class_name_lc = strtolower($this->classlike_aliases[$fq_class_name_lc]);
        }

        if (!isset($this->existing_traits_lc[$fq_class_name_lc]) ||
            !$this->existing_traits_lc[$fq_class_name_lc]
        ) {
            return false;
        }

        if ($this->collect_references && $code_location) {
            $this->file_reference_provider->addNonMethodReferenceToClass(
                $code_location->file_path,
                $fq_class_name_lc
            );
        }

        return true;
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
        CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ) {
        if (!$this->classExists($fq_class_name, $code_location, $calling_fq_class_name, $calling_method_id)
            && !$this->interfaceExists($fq_class_name, $code_location, $calling_fq_class_name, $calling_method_id)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether or not a given class exists
     *
     * @param  string       $fq_class_name
     *
     * @return bool
     */
    public function classExists(
        $fq_class_name,
        CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ) {
        if (isset(ClassLikeAnalyzer::SPECIAL_TYPES[$fq_class_name])) {
            return false;
        }

        if ($fq_class_name === 'Generator') {
            return true;
        }

        return $this->hasFullyQualifiedClassName(
            $fq_class_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id
        );
    }

    /**
     * Determine whether or not a class extends a parent
     *
     * @param  string       $fq_class_name
     * @param  string       $possible_parent
     *
     * @throws UnpopulatedClasslikeException when called on unpopulated class
     * @throws \InvalidArgumentException when class does not exist
     *
     * @return bool
     */
    public function classExtends($fq_class_name, $possible_parent, bool $from_api = false)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        if ($fq_class_name_lc === 'generator') {
            return false;
        }

        $fq_class_name = $this->classlike_aliases[$fq_class_name_lc] ?? $fq_class_name;

        $class_storage = $this->classlike_storage_provider->get($fq_class_name_lc);

        if ($from_api && !$class_storage->populated) {
            throw new UnpopulatedClasslikeException($fq_class_name);
        }

        return isset($class_storage->parent_classes[strtolower($possible_parent)]);
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

        if ($interface_id === 'traversable' && $fq_class_name === 'iterator') {
            return true;
        }

        if (isset(ClassLikeAnalyzer::SPECIAL_TYPES[$interface_id])
            || isset(ClassLikeAnalyzer::SPECIAL_TYPES[$fq_class_name])
        ) {
            return false;
        }

        if (isset($this->classlike_aliases[$fq_class_name])) {
            $fq_class_name = $this->classlike_aliases[$fq_class_name];
        }

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        return isset($class_storage->class_implements[$interface_id]);
    }

    /**
     * @param  string         $fq_interface_name
     *
     * @return bool
     */
    public function interfaceExists(
        $fq_interface_name,
        CodeLocation $code_location = null,
        ?string $calling_fq_class_name = null,
        ?string $calling_method_id = null
    ) {
        if (isset(ClassLikeAnalyzer::SPECIAL_TYPES[strtolower($fq_interface_name)])) {
            return false;
        }

        return $this->hasFullyQualifiedInterfaceName(
            $fq_interface_name,
            $code_location,
            $calling_fq_class_name,
            $calling_method_id
        );
    }

    /**
     * @param  string         $interface_name
     * @param  string         $possible_parent
     *
     * @return bool
     */
    public function interfaceExtends($interface_name, $possible_parent)
    {
        return isset($this->getParentInterfaces($interface_name)[strtolower($possible_parent)]);
    }

    /**
     * @param  string         $fq_interface_name
     *
     * @return array<string, string>   all interfaces extended by $interface_name
     */
    public function getParentInterfaces($fq_interface_name)
    {
        $fq_interface_name = strtolower($fq_interface_name);

        $storage = $this->classlike_storage_provider->get($fq_interface_name);

        return $storage->parent_interfaces;
    }

    /**
     * @param  string         $fq_trait_name
     *
     * @return bool
     */
    public function traitExists($fq_trait_name, CodeLocation $code_location = null)
    {
        return $this->hasFullyQualifiedTraitName($fq_trait_name, $code_location);
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

        if (isset($this->classlike_aliases[strtolower($fq_class_name)])) {
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
        if (isset($this->classlike_aliases[strtolower($fq_interface_name)])) {
            return true;
        }

        return isset($this->existing_interfaces[$fq_interface_name]);
    }

    /**
     * @param  string $fq_trait_name
     *
     * @return bool
     */
    public function traitHasCorrectCase($fq_trait_name)
    {
        if (isset($this->classlike_aliases[strtolower($fq_trait_name)])) {
            return true;
        }

        return isset($this->existing_traits[$fq_trait_name]);
    }

    /**
     * @param  lowercase-string  $fq_class_name
     *
     * @return bool
     */
    public function isUserDefined($fq_class_name)
    {
        return $this->classlike_storage_provider->get($fq_class_name)->user_defined;
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

        $storage = $this->classlike_storage_provider->get($fq_trait_name);

        if (!$storage->location) {
            throw new \UnexpectedValueException('Storage should exist for ' . $fq_trait_name);
        }

        $file_statements = $this->statements_provider->getStatementsForFile($storage->location->file_path);

        $trait_finder = new \Psalm\Internal\PhpVisitor\TraitFinder($fq_trait_name);

        $traverser = new \PhpParser\NodeTraverser();
        $traverser->addVisitor(
            $trait_finder
        );

        $traverser->traverse($file_statements);

        $trait_node = $trait_finder->getNode();

        if ($trait_node) {
            $this->trait_nodes[$fq_trait_name_lc] = $trait_node;

            return $trait_node;
        }

        throw new \UnexpectedValueException('Could not locate trait statement');
    }

    /**
     * @param lowercase-string $alias_name
     * @return void
     */
    public function addClassAlias(string $fq_class_name, string $alias_name)
    {
        $this->classlike_aliases[$alias_name] = $fq_class_name;
    }

    /**
     * @return string
     */
    public function getUnAliasedName(string $alias_name)
    {
        $alias_name_lc = strtolower($alias_name);
        if ($this->existing_classlikes_lc[$alias_name_lc] ?? false) {
            return $alias_name;
        }

        return $this->classlike_aliases[$alias_name_lc] ?? $alias_name;
    }

    /**
     * @return void
     */
    public function consolidateAnalyzedData(Methods $methods, ?Progress $progress, bool $find_unused_code)
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $progress->debug('Checking class references' . PHP_EOL);

        foreach ($this->existing_classlikes_lc as $fq_class_name_lc => $_) {
            try {
                $classlike_storage = $this->classlike_storage_provider->get($fq_class_name_lc);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            if ($classlike_storage->location
                && $this->config->isInProjectDirs($classlike_storage->location->file_path)
                && !$classlike_storage->is_trait
            ) {
                if ($find_unused_code) {
                    if (!$this->file_reference_provider->isClassReferenced($fq_class_name_lc)) {
                        if (IssueBuffer::accepts(
                            new UnusedClass(
                                'Class ' . $classlike_storage->name . ' is never used',
                                $classlike_storage->location,
                                $classlike_storage->name
                            ),
                            $classlike_storage->suppressed_issues
                        )) {
                            // fall through
                        }
                    } else {
                        $this->checkMethodReferences($classlike_storage, $methods);
                        $this->checkPropertyReferences($classlike_storage);
                    }
                }

                $this->findPossibleMethodParamTypes($classlike_storage);
            }
        }
    }

    /**
     * @return void
     */
    public function moveMethods(Methods $methods, Progress $progress = null)
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        if (!$codebase->methods_to_move) {
            return;
        }

        $progress->debug('Refactoring methods ' . PHP_EOL);

        $code_migrations = [];

        foreach ($codebase->methods_to_move as $source => $destination) {
            $source_parts = explode('::', $source);

            try {
                $source_method_storage = $methods->getStorage(
                    new \Psalm\Internal\MethodIdentifier(...$source_parts)
                );
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            list($destination_fq_class_name, $destination_name) = explode('::', $destination);

            try {
                $classlike_storage = $this->classlike_storage_provider->get($destination_fq_class_name);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            if ($classlike_storage->stmt_location
                && $this->config->isInProjectDirs($classlike_storage->stmt_location->file_path)
                && $source_method_storage->stmt_location
                && $source_method_storage->stmt_location->file_path
                && $source_method_storage->location
            ) {
                $new_class_bounds = $classlike_storage->stmt_location->getSnippetBounds();
                $old_method_bounds = $source_method_storage->stmt_location->getSnippetBounds();

                $old_method_name_bounds = $source_method_storage->location->getSelectionBounds();

                FileManipulationBuffer::add(
                    $source_method_storage->stmt_location->file_path,
                    [
                        new \Psalm\FileManipulation(
                            $old_method_name_bounds[0],
                            $old_method_name_bounds[1],
                            $destination_name
                        ),
                    ]
                );

                $selection = $classlike_storage->stmt_location->getSnippet();

                $insert_pos = strrpos($selection, "\n", -1);

                if (!$insert_pos) {
                    $insert_pos = strlen($selection) - 1;
                } else {
                    ++$insert_pos;
                }

                $code_migrations[] = new \Psalm\Internal\FileManipulation\CodeMigration(
                    $source_method_storage->stmt_location->file_path,
                    $old_method_bounds[0],
                    $old_method_bounds[1],
                    $classlike_storage->stmt_location->file_path,
                    $new_class_bounds[0] + $insert_pos
                );
            }
        }

        FileManipulationBuffer::addCodeMigrations($code_migrations);
    }

    /**
     * @return void
     */
    public function moveProperties(Properties $properties, Progress $progress = null)
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        if (!$codebase->properties_to_move) {
            return;
        }

        $progress->debug('Refacting properties ' . PHP_EOL);

        $code_migrations = [];

        foreach ($codebase->properties_to_move as $source => $destination) {
            try {
                $source_property_storage = $properties->getStorage($source);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            list($source_fq_class_name) = explode('::$', $source);
            list($destination_fq_class_name, $destination_name) = explode('::$', $destination);

            $source_classlike_storage = $this->classlike_storage_provider->get($source_fq_class_name);
            $destination_classlike_storage = $this->classlike_storage_provider->get($destination_fq_class_name);

            if ($destination_classlike_storage->stmt_location
                && $this->config->isInProjectDirs($destination_classlike_storage->stmt_location->file_path)
                && $source_property_storage->stmt_location
                && $source_property_storage->stmt_location->file_path
                && $source_property_storage->location
            ) {
                if ($source_property_storage->type
                    && $source_property_storage->type_location
                    && $source_property_storage->type_location !== $source_property_storage->signature_type_location
                ) {
                    $bounds = $source_property_storage->type_location->getSelectionBounds();

                    $replace_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                        $codebase,
                        $source_property_storage->type,
                        $source_classlike_storage->name,
                        $source_classlike_storage->name,
                        $source_classlike_storage->parent_class
                    );

                    $this->airliftClassDefinedDocblockType(
                        $replace_type,
                        $destination_fq_class_name,
                        $source_property_storage->stmt_location->file_path,
                        $bounds[0],
                        $bounds[1]
                    );
                }

                $new_class_bounds = $destination_classlike_storage->stmt_location->getSnippetBounds();
                $old_property_bounds = $source_property_storage->stmt_location->getSnippetBounds();

                $old_property_name_bounds = $source_property_storage->location->getSelectionBounds();

                FileManipulationBuffer::add(
                    $source_property_storage->stmt_location->file_path,
                    [
                        new \Psalm\FileManipulation(
                            $old_property_name_bounds[0],
                            $old_property_name_bounds[1],
                            '$' . $destination_name
                        ),
                    ]
                );

                $selection = $destination_classlike_storage->stmt_location->getSnippet();

                $insert_pos = strrpos($selection, "\n", -1);

                if (!$insert_pos) {
                    $insert_pos = strlen($selection) - 1;
                } else {
                    ++$insert_pos;
                }

                $code_migrations[] = new \Psalm\Internal\FileManipulation\CodeMigration(
                    $source_property_storage->stmt_location->file_path,
                    $old_property_bounds[0],
                    $old_property_bounds[1],
                    $destination_classlike_storage->stmt_location->file_path,
                    $new_class_bounds[0] + $insert_pos
                );
            }
        }

        FileManipulationBuffer::addCodeMigrations($code_migrations);
    }

    /**
     * @return void
     */
    public function moveClassConstants(Progress $progress = null)
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        if (!$codebase->class_constants_to_move) {
            return;
        }

        $progress->debug('Refacting constants ' . PHP_EOL);

        $code_migrations = [];

        foreach ($codebase->class_constants_to_move as $source => $destination) {
            list($source_fq_class_name, $source_const_name) = explode('::', $source);
            list($destination_fq_class_name, $destination_name) = explode('::', $destination);

            $source_classlike_storage = $this->classlike_storage_provider->get($source_fq_class_name);
            $destination_classlike_storage = $this->classlike_storage_provider->get($destination_fq_class_name);

            $source_const_stmt_location = $source_classlike_storage->class_constant_stmt_locations[$source_const_name];
            $source_const_location = $source_classlike_storage->class_constant_locations[$source_const_name];

            if ($destination_classlike_storage->stmt_location
                && $this->config->isInProjectDirs($destination_classlike_storage->stmt_location->file_path)
                && $source_const_stmt_location->file_path
            ) {
                $new_class_bounds = $destination_classlike_storage->stmt_location->getSnippetBounds();
                $old_const_bounds = $source_const_stmt_location->getSnippetBounds();

                $old_const_name_bounds = $source_const_location->getSelectionBounds();

                FileManipulationBuffer::add(
                    $source_const_stmt_location->file_path,
                    [
                        new \Psalm\FileManipulation(
                            $old_const_name_bounds[0],
                            $old_const_name_bounds[1],
                            $destination_name
                        ),
                    ]
                );

                $selection = $destination_classlike_storage->stmt_location->getSnippet();

                $insert_pos = strrpos($selection, "\n", -1);

                if (!$insert_pos) {
                    $insert_pos = strlen($selection) - 1;
                } else {
                    ++$insert_pos;
                }

                $code_migrations[] = new \Psalm\Internal\FileManipulation\CodeMigration(
                    $source_const_stmt_location->file_path,
                    $old_const_bounds[0],
                    $old_const_bounds[1],
                    $destination_classlike_storage->stmt_location->file_path,
                    $new_class_bounds[0] + $insert_pos
                );
            }
        }

        FileManipulationBuffer::addCodeMigrations($code_migrations);
    }

    /**
     * @param lowercase-string|null $calling_method_id
     */
    public function handleClassLikeReferenceInMigration(
        \Psalm\Codebase $codebase,
        \Psalm\StatementsSource $source,
        PhpParser\Node $class_name_node,
        string $fq_class_name,
        ?string $calling_method_id,
        bool $force_change = false,
        bool $was_self = false
    ) : bool {
        $calling_fq_class_name = $source->getFQCLN();

        // if we're inside a moved class static method
        if ($codebase->methods_to_move
            && $calling_fq_class_name
            && $calling_method_id
            && isset($codebase->methods_to_move[$calling_method_id])
        ) {
            $destination_class = explode('::', $codebase->methods_to_move[$calling_method_id])[0];

            $intended_fq_class_name = strtolower($calling_fq_class_name) === strtolower($fq_class_name)
                && isset($codebase->classes_to_move[strtolower($calling_fq_class_name)])
                ? $destination_class
                : $fq_class_name;

            $this->airliftClassLikeReference(
                $intended_fq_class_name,
                $destination_class,
                $source->getFilePath(),
                (int) $class_name_node->getAttribute('startFilePos'),
                (int) $class_name_node->getAttribute('endFilePos') + 1,
                $class_name_node instanceof PhpParser\Node\Scalar\MagicConst\Class_,
                $was_self
            );

            return true;
        }

        // if we're outside a moved class, but we're changing all references to a class
        if (isset($codebase->class_transforms[strtolower($fq_class_name)])) {
            $new_fq_class_name = $codebase->class_transforms[strtolower($fq_class_name)];
            $file_manipulations = [];

            if ($class_name_node instanceof PhpParser\Node\Identifier) {
                $destination_parts = explode('\\', $new_fq_class_name);

                $destination_class_name = array_pop($destination_parts);
                $file_manipulations = [];

                $file_manipulations[] = new \Psalm\FileManipulation(
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                    $destination_class_name
                );

                FileManipulationBuffer::add($source->getFilePath(), $file_manipulations);

                return true;
            }

            $uses_flipped = $source->getAliasedClassesFlipped();
            $uses_flipped_replaceable = $source->getAliasedClassesFlippedReplaceable();

            $old_fq_class_name = strtolower($fq_class_name);

            $migrated_source_fqcln = $calling_fq_class_name;

            if ($calling_fq_class_name
                && isset($codebase->class_transforms[strtolower($calling_fq_class_name)])
            ) {
                $migrated_source_fqcln = $codebase->class_transforms[strtolower($calling_fq_class_name)];
            }

            $source_namespace = $source->getNamespace();

            if ($migrated_source_fqcln && $calling_fq_class_name !== $migrated_source_fqcln) {
                $new_source_parts = explode('\\', $migrated_source_fqcln);
                array_pop($new_source_parts);
                $source_namespace = implode('\\', $new_source_parts);
            }

            if (isset($uses_flipped_replaceable[$old_fq_class_name])) {
                $alias = $uses_flipped_replaceable[$old_fq_class_name];
                unset($uses_flipped[$old_fq_class_name]);
                $old_class_name_parts = explode('\\', $old_fq_class_name);
                $old_class_name = end($old_class_name_parts);
                if (strtolower($old_class_name) === strtolower($alias)) {
                    $new_class_name_parts = explode('\\', $new_fq_class_name);
                    $new_class_name = end($new_class_name_parts);
                    $uses_flipped[strtolower($new_fq_class_name)] = $new_class_name;
                } else {
                    $uses_flipped[strtolower($new_fq_class_name)] = $alias;
                }
            }

            $file_manipulations[] = new \Psalm\FileManipulation(
                (int) $class_name_node->getAttribute('startFilePos'),
                (int) $class_name_node->getAttribute('endFilePos') + 1,
                Type::getStringFromFQCLN(
                    $new_fq_class_name,
                    $source_namespace,
                    $uses_flipped,
                    $migrated_source_fqcln,
                    $was_self
                )
                    . ($class_name_node instanceof PhpParser\Node\Scalar\MagicConst\Class_ ? '::class' : '')
            );

            FileManipulationBuffer::add($source->getFilePath(), $file_manipulations);

            return true;
        }

        // if we're inside a moved class (could be a method, could be a property/class const default)
        if ($codebase->classes_to_move
            && $calling_fq_class_name
            && isset($codebase->classes_to_move[strtolower($calling_fq_class_name)])
        ) {
            $destination_class = $codebase->classes_to_move[strtolower($calling_fq_class_name)];

            if ($class_name_node instanceof PhpParser\Node\Identifier) {
                $destination_parts = explode('\\', $destination_class);

                $destination_class_name = array_pop($destination_parts);
                $file_manipulations = [];

                $file_manipulations[] = new \Psalm\FileManipulation(
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                    $destination_class_name
                );

                FileManipulationBuffer::add($source->getFilePath(), $file_manipulations);
            } else {
                $this->airliftClassLikeReference(
                    strtolower($calling_fq_class_name) === strtolower($fq_class_name)
                        ? $destination_class
                        : $fq_class_name,
                    $destination_class,
                    $source->getFilePath(),
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                    $class_name_node instanceof PhpParser\Node\Scalar\MagicConst\Class_
                );
            }

            return true;
        }

        if ($force_change) {
            if ($calling_fq_class_name) {
                $this->airliftClassLikeReference(
                    $fq_class_name,
                    $calling_fq_class_name,
                    $source->getFilePath(),
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1
                );
            } else {
                $file_manipulations = [];

                $file_manipulations[] = new \Psalm\FileManipulation(
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                    Type::getStringFromFQCLN(
                        $fq_class_name,
                        $source->getNamespace(),
                        $source->getAliasedClassesFlipped(),
                        null
                    )
                );

                FileManipulationBuffer::add($source->getFilePath(), $file_manipulations);
            }

            return true;
        }

        return false;
    }

    /**
     * @param lowercase-string|null $calling_method_id
     */
    public function handleDocblockTypeInMigration(
        \Psalm\Codebase $codebase,
        \Psalm\StatementsSource $source,
        Type\Union $type,
        CodeLocation $type_location,
        ?string $calling_method_id
    ) : void {
        $calling_fq_class_name = $source->getFQCLN();

        $moved_type = false;

        // if we're inside a moved class static method
        if ($codebase->methods_to_move
            && $calling_fq_class_name
            && $calling_method_id
            && isset($codebase->methods_to_move[$calling_method_id])
        ) {
            $bounds = $type_location->getSelectionBounds();

            $destination_class = explode('::', $codebase->methods_to_move[$calling_method_id])[0];

            $this->airliftClassDefinedDocblockType(
                $type,
                $destination_class,
                $source->getFilePath(),
                $bounds[0],
                $bounds[1]
            );

            $moved_type = true;
        }

        // if we're outside a moved class, but we're changing all references to a class
        if (!$moved_type && $codebase->class_transforms) {
            $uses_flipped = $source->getAliasedClassesFlipped();
            $uses_flipped_replaceable = $source->getAliasedClassesFlippedReplaceable();

            $migrated_source_fqcln = $calling_fq_class_name;

            if ($calling_fq_class_name
                && isset($codebase->class_transforms[strtolower($calling_fq_class_name)])
            ) {
                $migrated_source_fqcln = $codebase->class_transforms[strtolower($calling_fq_class_name)];
            }

            $source_namespace = $source->getNamespace();

            if ($migrated_source_fqcln && $calling_fq_class_name !== $migrated_source_fqcln) {
                $new_source_parts = explode('\\', $migrated_source_fqcln);
                array_pop($new_source_parts);
                $source_namespace = implode('\\', $new_source_parts);
            }

            foreach ($codebase->class_transforms as $old_fq_class_name => $new_fq_class_name) {
                if (isset($uses_flipped_replaceable[$old_fq_class_name])) {
                    $alias = $uses_flipped_replaceable[$old_fq_class_name];
                    unset($uses_flipped[$old_fq_class_name]);
                    $old_class_name_parts = explode('\\', $old_fq_class_name);
                    $old_class_name = end($old_class_name_parts);
                    if (strtolower($old_class_name) === strtolower($alias)) {
                        $new_class_name_parts = explode('\\', $new_fq_class_name);
                        $new_class_name = end($new_class_name_parts);
                        $uses_flipped[strtolower($new_fq_class_name)] = $new_class_name;
                    } else {
                        $uses_flipped[strtolower($new_fq_class_name)] = $alias;
                    }
                }
            }

            foreach ($codebase->class_transforms as $old_fq_class_name => $new_fq_class_name) {
                if ($type->containsClassLike($old_fq_class_name)) {
                    $type = clone $type;

                    $type->replaceClassLike($old_fq_class_name, $new_fq_class_name);

                    $bounds = $type_location->getSelectionBounds();

                    $file_manipulations = [];

                    $file_manipulations[] = new \Psalm\FileManipulation(
                        $bounds[0],
                        $bounds[1],
                        $type->toNamespacedString(
                            $source_namespace,
                            $uses_flipped,
                            $migrated_source_fqcln,
                            false
                        )
                    );

                    FileManipulationBuffer::add(
                        $source->getFilePath(),
                        $file_manipulations
                    );

                    $moved_type = true;
                }
            }
        }

        // if we're inside a moved class (could be a method, could be a property/class const default)
        if (!$moved_type
            && $codebase->classes_to_move
            && $calling_fq_class_name
            && isset($codebase->classes_to_move[strtolower($calling_fq_class_name)])
        ) {
            $bounds = $type_location->getSelectionBounds();

            $destination_class = $codebase->classes_to_move[strtolower($calling_fq_class_name)];

            if ($type->containsClassLike(strtolower($calling_fq_class_name))) {
                $type = clone $type;

                $type->replaceClassLike(strtolower($calling_fq_class_name), $destination_class);
            }

            $this->airliftClassDefinedDocblockType(
                $type,
                $destination_class,
                $source->getFilePath(),
                $bounds[0],
                $bounds[1]
            );
        }
    }

    public function airliftClassLikeReference(
        string $fq_class_name,
        string $destination_fq_class_name,
        string $source_file_path,
        int $source_start,
        int $source_end,
        bool $add_class_constant = false,
        bool $allow_self = false
    ) : void {
        $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        $destination_class_storage = $codebase->classlike_storage_provider->get($destination_fq_class_name);

        if (!$destination_class_storage->aliases) {
            throw new \UnexpectedValueException('Aliases should not be null');
        }

        $file_manipulations = [];

        $file_manipulations[] = new \Psalm\FileManipulation(
            $source_start,
            $source_end,
            Type::getStringFromFQCLN(
                $fq_class_name,
                $destination_class_storage->aliases->namespace,
                $destination_class_storage->aliases->uses_flipped,
                $destination_class_storage->name,
                $allow_self
            ) . ($add_class_constant ? '::class' : '')
        );

        FileManipulationBuffer::add(
            $source_file_path,
            $file_manipulations
        );
    }

    public function airliftClassDefinedDocblockType(
        Type\Union $type,
        string $destination_fq_class_name,
        string $source_file_path,
        int $source_start,
        int $source_end
    ) : void {
        $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        $destination_class_storage = $codebase->classlike_storage_provider->get($destination_fq_class_name);

        if (!$destination_class_storage->aliases) {
            throw new \UnexpectedValueException('Aliases should not be null');
        }

        $file_manipulations = [];

        $file_manipulations[] = new \Psalm\FileManipulation(
            $source_start,
            $source_end,
            $type->toNamespacedString(
                $destination_class_storage->aliases->namespace,
                $destination_class_storage->aliases->uses_flipped,
                $destination_class_storage->name,
                false
            )
        );

        FileManipulationBuffer::add(
            $source_file_path,
            $file_manipulations
        );
    }

    /**
     * @param  string $class_name
     * @param  mixed  $visibility
     *
     * @return array<string,Type\Union>
     */
    public function getConstantsForClass($class_name, $visibility)
    {
        $class_name = strtolower($class_name);

        $storage = $this->classlike_storage_provider->get($class_name);

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return $storage->public_class_constants;
        }

        if ($visibility === ReflectionProperty::IS_PROTECTED) {
            return array_merge(
                $storage->public_class_constants,
                $storage->protected_class_constants
            );
        }

        if ($visibility === ReflectionProperty::IS_PRIVATE) {
            return array_merge(
                $storage->public_class_constants,
                $storage->protected_class_constants,
                $storage->private_class_constants
            );
        }

        throw new \InvalidArgumentException('Must specify $visibility');
    }

    public function getConstantForClass(
        string $class_name,
        string $constant_name,
        int $visibility,
        ?\Psalm\Internal\Analyzer\StatementsAnalyzer $statements_analyzer = null,
        array $visited_constant_ids = []
    ) : ?Type\Union {
        $class_name = strtolower($class_name);
        $storage = $this->classlike_storage_provider->get($class_name);

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            $type_candidates = $storage->public_class_constants;

            $fallbacks = $storage->public_class_constant_nodes;
        } elseif ($visibility === ReflectionProperty::IS_PROTECTED) {
            $type_candidates = array_merge(
                $storage->public_class_constants,
                $storage->protected_class_constants
            );

            $fallbacks = array_merge(
                $storage->public_class_constant_nodes,
                $storage->protected_class_constant_nodes
            );
        } elseif ($visibility === ReflectionProperty::IS_PRIVATE) {
            $type_candidates = array_merge(
                $storage->public_class_constants,
                $storage->protected_class_constants,
                $storage->private_class_constants
            );

            $fallbacks = array_merge(
                $storage->public_class_constant_nodes,
                $storage->protected_class_constant_nodes,
                $storage->private_class_constant_nodes
            );
        } else {
            throw new \InvalidArgumentException('Must specify $visibility');
        }

        if (isset($fallbacks[$constant_name])) {
            return new Type\Union([
                $this->resolveConstantType(
                    $fallbacks[$constant_name],
                    $statements_analyzer,
                    $visited_constant_ids
                )
            ]);
        }

        if (isset($type_candidates[$constant_name])) {
            return $type_candidates[$constant_name];
        }

        return null;
    }

    /**
     * @param  string|int|float|bool|null $value
     */
    private static function getLiteralTypeFromScalarValue($value) : Type\Atomic
    {
        if (\is_string($value)) {
            return new Type\Atomic\TLiteralString($value);
        } elseif (\is_int($value)) {
            return new Type\Atomic\TLiteralInt($value);
        } elseif (\is_float($value)) {
            return new Type\Atomic\TLiteralFloat($value);
        } elseif ($value === false) {
            return new Type\Atomic\TFalse;
        } elseif ($value === true) {
            return new Type\Atomic\TTrue;
        } else {
            return new Type\Atomic\TNull;
        }
    }

    private function resolveConstantType(
        \Psalm\Internal\Scanner\UnresolvedConstantComponent $c,
        \Psalm\Internal\Analyzer\StatementsAnalyzer $statements_analyzer = null,
        array $visited_constant_ids = []
    ) : Type\Atomic {
        $c_id = \spl_object_id($c);

        if (isset($visited_constant_ids[$c_id])) {
            throw new \Psalm\Exception\CircularReferenceException('Found a circular reference');
        }

        if ($c instanceof UnresolvedConstant\ScalarValue) {
            return self::getLiteralTypeFromScalarValue($c->value);
        }

        if ($c instanceof UnresolvedConstant\UnresolvedBinaryOp) {
            $left = $this->resolveConstantType(
                $c->left,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );
            $right = $this->resolveConstantType(
                $c->right,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            if ($left instanceof Type\Atomic\TMixed || $right instanceof Type\Atomic\TMixed) {
                return new Type\Atomic\TMixed;
            }

            if ($c instanceof UnresolvedConstant\UnresolvedConcatOp) {
                if ($left instanceof Type\Atomic\TLiteralString && $right instanceof Type\Atomic\TLiteralString) {
                    return new Type\Atomic\TLiteralString($left->value . $right->value);
                }

                return new Type\Atomic\TMixed;
            }

            if ($c instanceof UnresolvedConstant\UnresolvedAdditionOp
                || $c instanceof UnresolvedConstant\UnresolvedSubtractionOp
                || $c instanceof UnresolvedConstant\UnresolvedDivisionOp
                || $c instanceof UnresolvedConstant\UnresolvedMultiplicationOp
                || $c instanceof UnresolvedConstant\UnresolvedBitwiseOr
            ) {
                if (($left instanceof Type\Atomic\TLiteralFloat || $left instanceof Type\Atomic\TLiteralInt)
                    && ($right instanceof Type\Atomic\TLiteralFloat || $right instanceof Type\Atomic\TLiteralInt)
                ) {
                    if ($c instanceof UnresolvedConstant\UnresolvedAdditionOp) {
                        return self::getLiteralTypeFromScalarValue($left->value + $right->value);
                    }

                    if ($c instanceof UnresolvedConstant\UnresolvedSubtractionOp) {
                        return self::getLiteralTypeFromScalarValue($left->value - $right->value);
                    }

                    if ($c instanceof UnresolvedConstant\UnresolvedDivisionOp) {
                        return self::getLiteralTypeFromScalarValue($left->value / $right->value);
                    }

                    if ($c instanceof UnresolvedConstant\UnresolvedBitwiseOr) {
                        return self::getLiteralTypeFromScalarValue($left->value | $right->value);
                    }

                    return self::getLiteralTypeFromScalarValue($left->value * $right->value);
                }

                return new Type\Atomic\TMixed;
            }

            return new Type\Atomic\TMixed;
        }

        if ($c instanceof UnresolvedConstant\UnresolvedTernary) {
            $cond = $this->resolveConstantType(
                $c->cond,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );
            $if = $c->if ? $this->resolveConstantType(
                $c->if,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            ) : null;
            $else = $this->resolveConstantType(
                $c->else,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            if ($cond instanceof Type\Atomic\TLiteralFloat
                || $cond instanceof Type\Atomic\TLiteralInt
                || $cond instanceof Type\Atomic\TLiteralString
            ) {
                if ($cond->value) {
                    return $if ? $if : $cond;
                }
            } elseif ($cond instanceof Type\Atomic\TFalse || $cond instanceof Type\Atomic\TNull) {
                return $else;
            } elseif ($cond instanceof Type\Atomic\TTrue) {
                return $if ? $if : $cond;
            }
        }

        if ($c instanceof UnresolvedConstant\ArrayValue) {
            $properties = [];

            if (!$c->entries) {
                return new Type\Atomic\TArray([Type::getEmpty(), Type::getEmpty()]);
            }

            $is_list = true;

            foreach ($c->entries as $i => $entry) {
                if ($entry->key) {
                    $key_type = $this->resolveConstantType(
                        $entry->key,
                        $statements_analyzer,
                        $visited_constant_ids + [$c_id => true]
                    );

                    if (!$key_type instanceof Type\Atomic\TLiteralInt
                        || $key_type->value !== $i
                    ) {
                        $is_list = false;
                    }
                } else {
                    $key_type = new Type\Atomic\TLiteralInt($i);
                }

                if ($key_type instanceof Type\Atomic\TLiteralInt
                    || $key_type instanceof Type\Atomic\TLiteralString
                ) {
                    $key_value = $key_type->value;
                } else {
                    return new Type\Atomic\TArray([Type::getArrayKey(), Type::getMixed()]);
                }

                $value_type = new Type\Union([$this->resolveConstantType(
                    $entry->value,
                    $statements_analyzer,
                    $visited_constant_ids + [$c_id => true]
                )]);

                $properties[$key_value] = $value_type;
            }

            $objectlike = new Type\Atomic\ObjectLike($properties);

            $objectlike->is_list = $is_list;
            $objectlike->sealed = true;

            return $objectlike;
        }

        if ($c instanceof UnresolvedConstant\ClassConstant) {
            if ($c->name === 'class') {
                return new Type\Atomic\TLiteralClassString($c->fqcln);
            }

            $found_type = $this->getConstantForClass(
                $c->fqcln,
                $c->name,
                ReflectionProperty::IS_PRIVATE,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            if ($found_type) {
                return \array_values($found_type->getAtomicTypes())[0];
            }
        }

        if ($c instanceof UnresolvedConstant\ArrayOffsetFetch) {
            $var_type = $this->resolveConstantType(
                $c->array,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            $offset_type = $this->resolveConstantType(
                $c->offset,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            if ($var_type instanceof Type\Atomic\ObjectLike
                && ($offset_type instanceof Type\Atomic\TLiteralInt
                    || $offset_type instanceof Type\Atomic\TLiteralString)
            ) {
                $union = $var_type->properties[$offset_type->value] ?? null;

                if ($union && $union->isSingle()) {
                    return \array_values($union->getAtomicTypes())[0];
                }
            }
        }

        if ($c instanceof UnresolvedConstant\Constant) {
            if ($statements_analyzer) {
                $found_type = ConstFetchAnalyzer::getConstType(
                    $statements_analyzer,
                    $c->name,
                    $c->is_fully_qualified,
                    null
                );

                if ($found_type) {
                    return \array_values($found_type->getAtomicTypes())[0];
                }
            }
        }

        return new Type\Atomic\TMixed;
    }

    /**
     * @param   string      $class_name
     * @param   string      $const_name
     * @param   Type\Union  $type
     * @param   int         $visibility
     *
     * @return  void
     */
    public function setConstantType(
        $class_name,
        $const_name,
        Type\Union $type,
        $visibility
    ) {
        $storage = $this->classlike_storage_provider->get($class_name);

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            $storage->public_class_constants[$const_name] = $type;
        } elseif ($visibility === ReflectionProperty::IS_PROTECTED) {
            $storage->protected_class_constants[$const_name] = $type;
        } elseif ($visibility === ReflectionProperty::IS_PRIVATE) {
            $storage->private_class_constants[$const_name] = $type;
        }
    }

    /**
     * @return void
     */
    private function checkMethodReferences(ClassLikeStorage $classlike_storage, Methods $methods)
    {
        $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        foreach ($classlike_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            $appearing_fq_classlike_name = $appearing_method_id->fq_class_name;

            if ($appearing_fq_classlike_name !== $classlike_storage->name) {
                continue;
            }

            $method_id = $appearing_method_id;

            $declaring_classlike_storage = $classlike_storage;

            if (isset($classlike_storage->methods[$method_name])) {
                $method_storage = $classlike_storage->methods[$method_name];
            } else {
                $declaring_method_id = $classlike_storage->declaring_method_ids[$method_name];

                $declaring_fq_classlike_name = $declaring_method_id->fq_class_name;
                $declaring_method_name = $declaring_method_id->method_name;

                try {
                    $declaring_classlike_storage = $this->classlike_storage_provider->get($declaring_fq_classlike_name);
                } catch (\InvalidArgumentException $e) {
                    continue;
                }

                $method_storage = $declaring_classlike_storage->methods[$declaring_method_name];
                $method_id = $declaring_method_id;
            }

            if ($method_storage->location
                && !$project_analyzer->canReportIssues($method_storage->location->file_path)
                && !$codebase->analyzer->canReportIssues($method_storage->location->file_path)
            ) {
                continue;
            }

            $method_referenced = $this->file_reference_provider->isClassMethodReferenced(
                strtolower((string) $method_id)
            );

            if (!$method_referenced
                && $method_name !== '__destruct'
                && $method_name !== '__clone'
                && $method_name !== '__invoke'
                && $method_name !== '__unset'
                && $method_name !== '__isset'
                && $method_name !== '__sleep'
                && $method_name !== '__wakeup'
                && $method_name !== '__serialize'
                && $method_name !== '__unserialize'
                && $method_name !== '__set_state'
                && $method_name !== '__debuginfo'
                && $method_name !== '__tostring' // can be called in array_unique
                && $method_storage->location
            ) {
                $method_location = $method_storage->location;

                $method_id = $classlike_storage->name . '::' . $method_storage->cased_name;

                if ($method_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
                    $has_parent_references = false;

                    if ($codebase->classImplements($classlike_storage->name, 'Serializable')
                        && ($method_name === 'serialize' || $method_name === 'unserialize')
                    ) {
                        continue;
                    }

                    $has_variable_calls = $codebase->analyzer->hasMixedMemberName($method_name)
                        || $codebase->analyzer->hasMixedMemberName(strtolower($classlike_storage->name . '::'));

                    if (isset($classlike_storage->overridden_method_ids[$method_name])) {
                        foreach ($classlike_storage->overridden_method_ids[$method_name] as $parent_method_id) {
                            $parent_method_storage = $methods->getStorage($parent_method_id);

                            if ($parent_method_storage->location
                                && !$project_analyzer->canReportIssues($parent_method_storage->location->file_path)
                            ) {
                                // here we just don’t know
                                $has_parent_references = true;
                                break;
                            }

                            $parent_method_referenced = $this->file_reference_provider->isClassMethodReferenced(
                                strtolower((string) $parent_method_id)
                            );

                            if (!$parent_method_storage->abstract || $parent_method_referenced) {
                                $has_parent_references = true;
                                break;
                            }
                        }
                    }

                    foreach ($classlike_storage->parent_classes as $parent_method_fqcln) {
                        if ($codebase->analyzer->hasMixedMemberName(
                            strtolower($parent_method_fqcln) . '::'
                        )) {
                            $has_variable_calls = true;
                        }
                    }

                    foreach ($classlike_storage->class_implements as $fq_interface_name_lc => $_) {
                        try {
                            $interface_storage = $this->classlike_storage_provider->get($fq_interface_name_lc);
                        } catch (\InvalidArgumentException $e) {
                            continue;
                        }

                        if ($codebase->analyzer->hasMixedMemberName(
                            $fq_interface_name_lc . '::'
                        )) {
                            $has_variable_calls = true;
                        }

                        if (isset($interface_storage->methods[$method_name])) {
                            $interface_method_referenced = $this->file_reference_provider->isClassMethodReferenced(
                                $fq_interface_name_lc . '::' . $method_name
                            );

                            if ($interface_method_referenced) {
                                $has_parent_references = true;
                            }
                        }
                    }

                    if (!$has_parent_references) {
                        $issue = new PossiblyUnusedMethod(
                            'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                                . ' calls to method ' . $method_id
                                . ($has_variable_calls ? ' (but did find some potential callers)' : ''),
                            $method_storage->location,
                            $method_id
                        );

                        if ($codebase->alter_code) {
                            if ($method_storage->stmt_location
                                && !$declaring_classlike_storage->is_trait
                                && isset($project_analyzer->getIssuesToFix()['PossiblyUnusedMethod'])
                                && !$has_variable_calls
                                && !IssueBuffer::isSuppressed($issue, $method_storage->suppressed_issues)
                            ) {
                                FileManipulationBuffer::addForCodeLocation(
                                    $method_storage->stmt_location,
                                    '',
                                    true
                                );
                            }
                        } elseif (IssueBuffer::accepts(
                            $issue,
                            $method_storage->suppressed_issues,
                            $method_storage->stmt_location
                                && !$declaring_classlike_storage->is_trait
                                && !$has_variable_calls
                        )) {
                            // fall through
                        }
                    }
                } elseif (!isset($classlike_storage->declaring_method_ids['__call'])) {
                    $has_variable_calls = $codebase->analyzer->hasMixedMemberName(
                        strtolower($classlike_storage->name . '::')
                    ) || $codebase->analyzer->hasMixedMemberName($method_name);

                    $issue = new UnusedMethod(
                        'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                            . ' calls to private method ' . $method_id
                            . ($has_variable_calls ? ' (but did find some potential callers)' : ''),
                        $method_location,
                        $method_id
                    );

                    if ($codebase->alter_code) {
                        if ($method_storage->stmt_location
                            && !$declaring_classlike_storage->is_trait
                            && isset($project_analyzer->getIssuesToFix()['UnusedMethod'])
                            && !$has_variable_calls
                            && !IssueBuffer::isSuppressed($issue, $method_storage->suppressed_issues)
                        ) {
                            FileManipulationBuffer::addForCodeLocation(
                                $method_storage->stmt_location,
                                '',
                                true
                            );
                        }
                    } elseif (IssueBuffer::accepts(
                        $issue,
                        $method_storage->suppressed_issues,
                        $method_storage->stmt_location
                            && !$declaring_classlike_storage->is_trait
                            && !$has_variable_calls
                    )) {
                        // fall through
                    }
                }
            } else {
                if ($method_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PRIVATE
                    && !$classlike_storage->is_interface
                ) {
                    foreach ($method_storage->params as $offset => $param_storage) {
                        if (!$this->file_reference_provider->isMethodParamUsed(
                            strtolower((string) $method_id),
                            $offset
                        )
                            && $param_storage->location
                        ) {
                            if ($method_storage->final) {
                                if (IssueBuffer::accepts(
                                    new \Psalm\Issue\UnusedParam(
                                        'Param #' . ($offset + 1) . ' is never referenced in this method',
                                        $param_storage->location
                                    ),
                                    $method_storage->suppressed_issues
                                )) {
                                    // fall through
                                }
                            } else {
                                if (IssueBuffer::accepts(
                                    new PossiblyUnusedParam(
                                        'Param #' . ($offset + 1) . ' is never referenced in this method',
                                        $param_storage->location
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
        }
    }

    /**
     * @return void
     */
    private function findPossibleMethodParamTypes(ClassLikeStorage $classlike_storage)
    {
        $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        foreach ($classlike_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            $appearing_fq_classlike_name = $appearing_method_id->fq_class_name;

            if ($appearing_fq_classlike_name !== $classlike_storage->name) {
                continue;
            }

            $method_id = $appearing_method_id;

            $declaring_classlike_storage = $classlike_storage;

            if (isset($classlike_storage->methods[$method_name])) {
                $method_storage = $classlike_storage->methods[$method_name];
            } else {
                $declaring_method_id = $classlike_storage->declaring_method_ids[$method_name];

                $declaring_fq_classlike_name = $declaring_method_id->fq_class_name;
                $declaring_method_name = $declaring_method_id->method_name;

                try {
                    $declaring_classlike_storage = $this->classlike_storage_provider->get($declaring_fq_classlike_name);
                } catch (\InvalidArgumentException $e) {
                    continue;
                }

                $method_storage = $declaring_classlike_storage->methods[$declaring_method_name];
                $method_id = $declaring_method_id;
            }

            if ($method_storage->location
                && !$project_analyzer->canReportIssues($method_storage->location->file_path)
                && !$codebase->analyzer->canReportIssues($method_storage->location->file_path)
            ) {
                continue;
            }

            if ($declaring_classlike_storage->is_trait) {
                continue;
            }

            $method_id_lc = strtolower((string) $method_id);

            if (isset($codebase->analyzer->possible_method_param_types[$method_id_lc])) {
                if ($method_storage->location) {
                    $possible_param_types
                        = $codebase->analyzer->possible_method_param_types[$method_id_lc];

                    if ($possible_param_types) {
                        foreach ($possible_param_types as $offset => $possible_type) {
                            if (!isset($method_storage->params[$offset])) {
                                continue;
                            }

                            $param_name = $method_storage->params[$offset]->name;

                            if ($possible_type->hasMixed() || $possible_type->isNull()) {
                                continue;
                            }

                            if ($method_storage->params[$offset]->default_type) {
                                $possible_type = \Psalm\Type::combineUnionTypes(
                                    $possible_type,
                                    $method_storage->params[$offset]->default_type
                                );
                            }

                            if ($codebase->alter_code
                                && isset($project_analyzer->getIssuesToFix()['MissingParamType'])
                            ) {
                                $function_analyzer = $project_analyzer->getFunctionLikeAnalyzer(
                                    $method_id,
                                    $method_storage->location->file_path
                                );

                                $has_variable_calls = $codebase->analyzer->hasMixedMemberName(
                                    $method_name
                                )
                                    || $codebase->analyzer->hasMixedMemberName(
                                        strtolower($classlike_storage->name . '::')
                                    );

                                if ($has_variable_calls) {
                                    $possible_type->from_docblock = true;
                                }

                                if ($function_analyzer) {
                                    $function_analyzer->addOrUpdateParamType(
                                        $project_analyzer,
                                        $param_name,
                                        $possible_type,
                                        $possible_type->from_docblock
                                            && $project_analyzer->only_replace_php_types_with_non_docblock_types
                                    );
                                }
                            } else {
                                IssueBuffer::addFixableIssue('MissingParamType');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    private function checkPropertyReferences(ClassLikeStorage $classlike_storage)
    {
        $project_analyzer = \Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        foreach ($classlike_storage->properties as $property_name => $property_storage) {
            $referenced_property_name = strtolower($classlike_storage->name) . '::$' . $property_name;
            $property_referenced = $this->file_reference_provider->isClassPropertyReferenced(
                $referenced_property_name
            );

            $property_constructor_referenced = false;
            if ($property_referenced && $property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
                $all_method_references = $this->file_reference_provider->getAllMethodReferencesToClassMembers();

                if (isset($all_method_references[$referenced_property_name])
                    && count($all_method_references[$referenced_property_name]) === 1) {
                    $constructor_name = strtolower($classlike_storage->name) . '::__construct';
                    $property_references = $all_method_references[$referenced_property_name];

                    $property_constructor_referenced = isset($property_references[$constructor_name])
                        && !$property_storage->is_static;
                }
            }

            if ((!$property_referenced || $property_constructor_referenced)
                && $property_storage->location
            ) {
                $property_id = $classlike_storage->name . '::$' . $property_name;

                if ($property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                    || $property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PROTECTED
                ) {
                    $has_parent_references = isset($classlike_storage->overridden_property_ids[$property_name]);

                    $has_variable_calls = $codebase->analyzer->hasMixedMemberName('$' . $property_name)
                        || $codebase->analyzer->hasMixedMemberName(strtolower($classlike_storage->name) . '::$');

                    foreach ($classlike_storage->parent_classes as $parent_method_fqcln) {
                        if ($codebase->analyzer->hasMixedMemberName(
                            strtolower($parent_method_fqcln) . '::$'
                        )) {
                            $has_variable_calls = true;
                            break;
                        }
                    }

                    foreach ($classlike_storage->class_implements as $fq_interface_name) {
                        if ($codebase->analyzer->hasMixedMemberName(
                            strtolower($fq_interface_name) . '::$'
                        )) {
                            $has_variable_calls = true;
                            break;
                        }
                    }

                    if (!$has_parent_references
                        && ($property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                            || !isset($classlike_storage->declaring_method_ids['__get']))
                    ) {
                        $issue = new PossiblyUnusedProperty(
                            'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                                . ' references to property ' . $property_id
                                . ($has_variable_calls ? ' (but did find some potential references)' : ''),
                            $property_storage->location
                        );

                        if ($codebase->alter_code) {
                            if ($property_storage->stmt_location
                                && isset($project_analyzer->getIssuesToFix()['PossiblyUnusedProperty'])
                                && !$has_variable_calls
                                && !IssueBuffer::isSuppressed($issue, $classlike_storage->suppressed_issues)
                            ) {
                                FileManipulationBuffer::addForCodeLocation(
                                    $property_storage->stmt_location,
                                    '',
                                    true
                                );
                            }
                        } elseif (IssueBuffer::accepts(
                            $issue,
                            $classlike_storage->suppressed_issues
                        )) {
                            // fall through
                        }
                    }
                } elseif (!isset($classlike_storage->declaring_method_ids['__get'])) {
                    $has_variable_calls = $codebase->analyzer->hasMixedMemberName('$' . $property_name);

                    $issue = new UnusedProperty(
                        'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                            . ' references to private property ' . $property_id
                            . ($has_variable_calls ? ' (but did find some potential references)' : ''),
                        $property_storage->location
                    );

                    if ($codebase->alter_code) {
                        if (!$property_constructor_referenced
                            && $property_storage->stmt_location
                            && isset($project_analyzer->getIssuesToFix()['UnusedProperty'])
                            && !$has_variable_calls
                            && !IssueBuffer::isSuppressed($issue, $classlike_storage->suppressed_issues)
                        ) {
                            FileManipulationBuffer::addForCodeLocation(
                                $property_storage->stmt_location,
                                '',
                                true
                            );
                        }
                    } elseif (IssueBuffer::accepts(
                        $issue,
                        $classlike_storage->suppressed_issues
                    )) {
                        // fall through
                    }
                }
            }
        }
    }

    /**
     * @param  lowercase-string $fq_classlike_name_lc
     *
     * @return void
     */
    public function registerMissingClassLike($fq_classlike_name_lc)
    {
        $this->existing_classlikes_lc[$fq_classlike_name_lc] = false;
    }

    /**
     * @param  lowercase-string $fq_classlike_name_lc
     *
     * @return bool
     */
    public function isMissingClassLike($fq_classlike_name_lc)
    {
        return isset($this->existing_classlikes_lc[$fq_classlike_name_lc])
            && $this->existing_classlikes_lc[$fq_classlike_name_lc] === false;
    }

    /**
     * @param  lowercase-string $fq_classlike_name_lc
     *
     * @return bool
     */
    public function doesClassLikeExist($fq_classlike_name_lc)
    {
        return isset($this->existing_classlikes_lc[$fq_classlike_name_lc])
            && $this->existing_classlikes_lc[$fq_classlike_name_lc];
    }

    public function forgetMissingClassLikes() : void
    {
        $this->existing_classlikes_lc = \array_filter($this->existing_classlikes_lc);
    }

    /**
     * @param string $fq_class_name
     *
     * @return void
     */
    public function removeClassLike($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        unset(
            $this->existing_classlikes_lc[$fq_class_name_lc],
            $this->existing_classes_lc[$fq_class_name_lc],
            $this->existing_traits_lc[$fq_class_name_lc],
            $this->existing_traits[$fq_class_name],
            $this->existing_interfaces_lc[$fq_class_name_lc],
            $this->existing_interfaces[$fq_class_name],
            $this->existing_classes[$fq_class_name],
            $this->trait_nodes[$fq_class_name_lc]
        );

        $this->scanner->removeClassLike($fq_class_name_lc);
    }

    /**
     * @return array{
     *     0: array<lowercase-string, bool>,
     *     1: array<lowercase-string, bool>,
     *     2: array<lowercase-string, bool>,
     *     3: array<string, bool>,
     *     4: array<lowercase-string, bool>,
     *     5: array<string, bool>,
     *     6: array<string, bool>,
     * }
     */
    public function getThreadData()
    {
        return [
            $this->existing_classlikes_lc,
            $this->existing_classes_lc,
            $this->existing_traits_lc,
            $this->existing_traits,
            $this->existing_interfaces_lc,
            $this->existing_interfaces,
            $this->existing_classes,
        ];
    }

    /**
     * @param array{
     *     0: array<lowercase-string, bool>,
     *     1: array<lowercase-string, bool>,
     *     2: array<lowercase-string, bool>,
     *     3: array<string, bool>,
     *     4: array<lowercase-string, bool>,
     *     5: array<string, bool>,
     *     6: array<string, bool>,
     * } $thread_data
     *
     * @return void
     */
    public function addThreadData(array $thread_data)
    {
        list(
            $existing_classlikes_lc,
            $existing_classes_lc,
            $existing_traits_lc,
            $existing_traits,
            $existing_interfaces_lc,
            $existing_interfaces,
            $existing_classes) = $thread_data;

        $this->existing_classlikes_lc = array_merge($existing_classlikes_lc, $this->existing_classlikes_lc);
        $this->existing_classes_lc = array_merge($existing_classes_lc, $this->existing_classes_lc);
        $this->existing_traits_lc = array_merge($existing_traits_lc, $this->existing_traits_lc);
        $this->existing_traits = array_merge($existing_traits, $this->existing_traits);
        $this->existing_interfaces_lc = array_merge($existing_interfaces_lc, $this->existing_interfaces_lc);
        $this->existing_interfaces = array_merge($existing_interfaces, $this->existing_interfaces);
        $this->existing_classes = array_merge($existing_classes, $this->existing_classes);
    }
}
