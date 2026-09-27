<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use InvalidArgumentException;
use PhpParser;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeTraverser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\UnpopulatedClasslikeException;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\FileManipulation\ClassDocblockManipulator;
use Psalm\Internal\FileManipulation\CodeMigration;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\PhpVisitor\TraitFinder;
use Psalm\Internal\PropertyIdentifier;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Interner;
use Psalm\Issue\ClassMustBeFinal;
use Psalm\Issue\MissingImmutableAnnotation;
use Psalm\Issue\MissingInterfaceImmutableAnnotation;
use Psalm\Issue\PossiblyUnusedMethod;
use Psalm\Issue\PossiblyUnusedParam;
use Psalm\Issue\PossiblyUnusedProperty;
use Psalm\Issue\PossiblyUnusedReturnValue;
use Psalm\Issue\UnusedClass;
use Psalm\Issue\UnusedConstructor;
use Psalm\Issue\UnusedMethod;
use Psalm\Issue\UnusedParam;
use Psalm\Issue\UnusedProperty;
use Psalm\Issue\UnusedReturnValue;
use Psalm\IssueBuffer;
use Psalm\Node\VirtualNode;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\StatementsSource;
use Psalm\Storage\ClassConstantStorage;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\Mutations;
use Psalm\StrId;
use Psalm\Type;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Union;
use ReflectionClass;
use ReflectionProperty;
use UnexpectedValueException;

use function array_filter;
use function array_merge;
use function array_pop;
use function count;
use function end;
use function explode;
use function get_declared_classes;
use function get_declared_interfaces;
use function implode;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;

use const PHP_EOL;

/**
 * @internal
 *
 * Handles information about classes, interfaces and traits
 */
final class ClassLikes
{
    private const SPECIAL_TYPES_LC = [
        StrId::int => true,
        StrId::string => true,
        StrId::float => true,
        StrId::bool => true,
        StrId::false => true,
        StrId::object => true,
        StrId::never => true,
        StrId::callable => true,
        StrId::array => true,
        StrId::iterable => true,
        StrId::null => true,
        StrId::mixed => true,
    ];

    /**
     * @var array<int, bool> lowercase class name id => exists
     */
    private array $existing_classlikes_lc = [];

    /**
     * @var array<int, bool> lowercase class name id => exists
     */
    private array $existing_classes_lc = [];

    /**
     * @var array<int, bool> class name id => exists
     */
    private array $existing_classes = [];

    /**
     * @var array<int, bool> lowercase interface name id => exists
     */
    private array $existing_interfaces_lc = [];

    /**
     * @var array<int, bool> interface name id => exists
     */
    private array $existing_interfaces = [];

    /**
     * @var array<int, bool> lowercase trait name id => exists
     */
    private array $existing_traits_lc = [];

    /**
     * @var array<int, bool> trait name id => exists
     */
    private array $existing_traits = [];

    /**
     * @var array<int, bool> lowercase enum name id => exists
     */
    private array $existing_enums_lc = [];

    /**
     * @var array<int, bool> enum name id => exists
     */
    private array $existing_enums = [];

    /**
     * @var array<int, int> lowercase alias name id => class name id
     */
    private array $classlike_aliases_map = [];

    /**
     * @var array<int, bool> alias name id => true
     */
    private array $existing_classlike_aliases = [];

    /**
     * @var array<int, PhpParser\Node\Stmt\Trait_> lowercase trait name id => node
     */
    private array $trait_nodes = [];

    public function __construct(
        private readonly Config $config,
        private readonly ClassLikeStorageProvider $classlike_storage_provider,
        public FileReferenceProvider $file_reference_provider,
        private readonly Scanner $scanner,
    ) {
        $this->collectPredefinedClassLikes();
    }

    private function collectPredefinedClassLikes(): void
    {
        /** @var array<int, string> */
        $predefined_classes = get_declared_classes();

        foreach ($predefined_classes as $predefined_class) {
            $predefined_class = (string) preg_replace('/^\\\/', '', $predefined_class, 1);
            /** @psalm-suppress ArgumentTypeCoercion */
            $reflection_class = new ReflectionClass($predefined_class);

            if (!$reflection_class->isUserDefined() && $reflection_class->name === $predefined_class) {
                $predefined_class_id = Interner::intern($predefined_class);
                $predefined_class_lc = Interner::lower($predefined_class_id);
                $this->existing_classlikes_lc[$predefined_class_lc] = true;
                $this->existing_classes_lc[$predefined_class_lc] = true;
                $this->existing_classes[$predefined_class_id] = true;
            }
        }

        /** @var array<int, string> */
        $predefined_interfaces = get_declared_interfaces();

        foreach ($predefined_interfaces as $predefined_interface) {
            $predefined_interface = (string) preg_replace('/^\\\/', '', $predefined_interface, 1);
            /** @psalm-suppress ArgumentTypeCoercion */
            $reflection_class = new ReflectionClass($predefined_interface);

            if (!$reflection_class->isUserDefined() && $reflection_class->name === $predefined_interface) {
                $predefined_interface_id = Interner::intern($predefined_interface);
                $predefined_interface_lc = Interner::lower($predefined_interface_id);
                $this->existing_classlikes_lc[$predefined_interface_lc] = true;
                $this->existing_interfaces_lc[$predefined_interface_lc] = true;
                $this->existing_interfaces[$predefined_interface_id] = true;
            }
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addFullyQualifiedClassName(int $fq_class_name, ?string $file_path = null): void
    {
        $fq_class_name_lc = Interner::lower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_classes_lc[$fq_class_name_lc] = true;
        $this->existing_classes[$fq_class_name] = true;

        $this->existing_traits_lc[$fq_class_name_lc] = false;
        $this->existing_interfaces_lc[$fq_class_name_lc] = false;
        $this->existing_enums_lc[$fq_class_name_lc] = false;

        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addFullyQualifiedInterfaceName(int $fq_class_name, ?string $file_path = null): void
    {
        $fq_class_name_lc = Interner::lower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_interfaces_lc[$fq_class_name_lc] = true;
        $this->existing_interfaces[$fq_class_name] = true;

        $this->existing_classes_lc[$fq_class_name_lc] = false;
        $this->existing_traits_lc[$fq_class_name_lc] = false;
        $this->existing_enums_lc[$fq_class_name_lc] = false;

        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addFullyQualifiedTraitName(int $fq_class_name, ?string $file_path = null): void
    {
        $fq_class_name_lc = Interner::lower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_traits_lc[$fq_class_name_lc] = true;
        $this->existing_traits[$fq_class_name] = true;

        $this->existing_classes_lc[$fq_class_name_lc] = false;
        $this->existing_interfaces_lc[$fq_class_name_lc] = false;
        $this->existing_enums[$fq_class_name] = false;

        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addFullyQualifiedEnumName(int $fq_class_name, ?string $file_path = null): void
    {
        $fq_class_name_lc = Interner::lower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_enums_lc[$fq_class_name_lc] = true;
        $this->existing_enums[$fq_class_name] = true;

        $this->existing_traits_lc[$fq_class_name_lc] = false;
        $this->existing_classes_lc[$fq_class_name_lc] = false;
        $this->existing_interfaces_lc[$fq_class_name_lc] = false;

        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    /**
     * @param int $fq_class_name_lc lowercase class name id
     * @psalm-external-mutation-free
     */
    public function addFullyQualifiedClassLikeName(int $fq_class_name_lc, ?string $file_path = null): void
    {
        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    /**
     * @return list<int>
     * @psalm-mutation-free
     */
    public function getMatchingClassLikeNames(string $stub): array
    {
        $matching_classes = [];

        if ($stub[0] === '*') {
            $stub = substr($stub, 1);
        }

        $fully_qualified = false;

        if ($stub[0] === '\\') {
            $fully_qualified = true;
            $stub = substr($stub, 1);
        } else {
            // for any not-fully-qualified class name the bit we care about comes after a dash
            [, $stub] = explode('-', $stub);
        }

        $stub = preg_quote(strtolower($stub));

        if ($fully_qualified) {
            $stub = '^' . $stub;
        } else {
            $stub = '(^|\\\)' . $stub;
        }

        foreach ($this->existing_classes as $fq_classlike_name => $found) {
            if (!$found) {
                continue;
            }

            if (preg_match('@' . $stub . '.*@i', Interner::str($fq_classlike_name))) {
                $matching_classes[] = $fq_classlike_name;
            }
        }

        foreach ($this->existing_interfaces as $fq_classlike_name => $found) {
            if (!$found) {
                continue;
            }

            if (preg_match('@' . $stub . '.*@i', Interner::str($fq_classlike_name))) {
                $matching_classes[] = $fq_classlike_name;
            }
        }

        return $matching_classes;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function hasFullyQualifiedClassName(
        int $fq_class_name,
        ?CodeLocation $location = null,
        ?Context $context = null,
    ): bool {
        $fq_class_name_lc = Interner::lower($this->getUnAliasedName($fq_class_name));

        // fixme: this looks like a crazy caching hack
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

        $this->file_reference_provider->code_use_graph->addReference(
            CodeUseGraph::classNode($fq_class_name_lc),
            $context,
            $location,
        );

        return true;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function hasFullyQualifiedInterfaceName(
        int $fq_class_name,
        ?CodeLocation $location = null,
        ?Context $context = null,
    ): bool {
        $fq_class_name_lc = Interner::lower($this->getUnAliasedName($fq_class_name));

        // fixme: this looks like a crazy caching hack
        if (!isset($this->existing_interfaces_lc[$fq_class_name_lc])
            || !$this->existing_interfaces_lc[$fq_class_name_lc]
            || !$this->classlike_storage_provider->has($fq_class_name_lc)
        ) {
            if ((
                !isset($this->existing_interfaces_lc[$fq_class_name_lc])
                    || $this->existing_interfaces_lc[$fq_class_name_lc]
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

        $this->file_reference_provider->code_use_graph->addReference(
            CodeUseGraph::classNode($fq_class_name_lc),
            $context,
            $location,
        );

        return true;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function hasFullyQualifiedEnumName(
        int $fq_class_name,
        ?CodeLocation $location = null,
        ?Context $context = null,
    ): bool {
        $fq_class_name_lc = Interner::lower($this->getUnAliasedName($fq_class_name));

        // fixme: this looks like a crazy caching hack
        if (!isset($this->existing_enums_lc[$fq_class_name_lc])
            || !$this->existing_enums_lc[$fq_class_name_lc]
            || !$this->classlike_storage_provider->has($fq_class_name_lc)
        ) {
            if ((
                !isset($this->existing_enums_lc[$fq_class_name_lc])
                    || $this->existing_enums_lc[$fq_class_name_lc]
                )
                && !$this->classlike_storage_provider->has($fq_class_name_lc)
            ) {
                if (!isset($this->existing_enums_lc[$fq_class_name_lc])) {
                    $this->existing_enums_lc[$fq_class_name_lc] = false;

                    return false;
                }

                return $this->existing_enums_lc[$fq_class_name_lc];
            }

            return false;
        }

        $this->file_reference_provider->code_use_graph->addReference(
            CodeUseGraph::classNode($fq_class_name_lc),
            $context,
            $location,
        );

        return true;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function hasFullyQualifiedTraitName(
        int $fq_class_name,
        ?CodeLocation $location = null,
        ?Context $context = null,
    ): bool {
        $fq_class_name_lc = Interner::lower($this->getUnAliasedName($fq_class_name));

        if (!isset($this->existing_traits_lc[$fq_class_name_lc]) ||
            !$this->existing_traits_lc[$fq_class_name_lc]
        ) {
            return false;
        }

        $this->file_reference_provider->code_use_graph->addReference(
            CodeUseGraph::classNode($fq_class_name_lc),
            $context,
            $location,
        );

        return true;
    }

    /**
     * Check whether a class/interface exists
     *
     * @psalm-external-mutation-free
     */
    public function classOrInterfaceExists(
        int $fq_class_name,
        ?CodeLocation $location = null,
        ?Context $context = null,
    ): bool {
        return $this->classExists($fq_class_name, $location, $context)
            || $this->interfaceExists($fq_class_name, $location, $context);
    }

    /**
     * Check whether a class/interface exists
     *
     * @psalm-external-mutation-free
     */
    public function classOrInterfaceOrEnumExists(
        int $fq_class_name,
        ?CodeLocation $location = null,
        ?Context $context = null,
    ): bool {
        return $this->classExists($fq_class_name, $location, $context)
            || $this->interfaceExists($fq_class_name, $location, $context)
            || $this->enumExists($fq_class_name, $location, $context);
    }

    /**
     * Determine whether or not a given class exists
     *
     * @psalm-external-mutation-free
     */
    public function classExists(
        int $fq_class_name,
        ?CodeLocation $location = null,
        ?Context $context = null,
    ): bool {
        if (isset(self::SPECIAL_TYPES_LC[$fq_class_name])) {
            return false;
        }

        if ($fq_class_name === StrId::Generator) {
            return true;
        }

        return $this->hasFullyQualifiedClassName(
            $fq_class_name,
            $location,
            $context,
        );
    }

    /**
     * Determine whether or not a class extends a parent
     *
     * @psalm-mutation-free
     * @throws UnpopulatedClasslikeException when called on unpopulated class
     * @throws InvalidArgumentException when class does not exist
     */
    public function classExtends(int $fq_class_name, int $possible_parent, bool $from_api = false): bool
    {
        $unaliased_fq_class_name = $this->getUnAliasedName($fq_class_name);

        if (Interner::lower($unaliased_fq_class_name) === StrId::generator) {
            return false;
        }

        $class_storage = $this->classlike_storage_provider->get($unaliased_fq_class_name);

        if ($from_api && !$class_storage->populated) {
            throw new UnpopulatedClasslikeException(Interner::str($fq_class_name));
        }

        return isset($class_storage->parent_classes[Interner::lower($possible_parent)]);
    }

    /**
     * Check whether a class implements an interface
     *
     * @psalm-mutation-free
     */
    public function classImplements(int $fq_class_name, int $interface): bool
    {
        $interface_id = Interner::lower($interface);

        $fq_class_name = Interner::lower($fq_class_name);

        if ($interface_id === StrId::callable && $fq_class_name === StrId::closure) {
            return true;
        }

        if ($interface_id === StrId::traversable && $fq_class_name === StrId::generator) {
            return true;
        }

        if ($interface_id === StrId::traversable && $fq_class_name === StrId::iterator) {
            return true;
        }

        if (isset(self::SPECIAL_TYPES_LC[$interface_id])
            || isset(self::SPECIAL_TYPES_LC[$fq_class_name])
        ) {
            return false;
        }

        $fq_class_name = $this->getUnAliasedName($fq_class_name);

        if (!$this->classlike_storage_provider->has($fq_class_name)) {
            return false;
        }
        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->class_implements[$interface_id])) {
            return true;
        }

        foreach ($class_storage->class_implements as $implementing_interface_lc => $_) {
            $aliased_interface_lc = Interner::lower(
                $this->getUnAliasedName($implementing_interface_lc),
            );

            if ($aliased_interface_lc === $interface_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function interfaceExists(
        int $fq_interface_name,
        ?CodeLocation $location = null,
        ?Context $context = null,
    ): bool {
        if (isset(self::SPECIAL_TYPES_LC[Interner::lower($fq_interface_name)])) {
            return false;
        }

        return $this->hasFullyQualifiedInterfaceName(
            $fq_interface_name,
            $location,
            $context,
        );
    }

    /**
     * @psalm-external-mutation-free
     */
    public function enumExists(
        int $fq_enum_name,
        ?CodeLocation $location = null,
        ?Context $context = null,
    ): bool {
        if (isset(self::SPECIAL_TYPES_LC[Interner::lower($fq_enum_name)])) {
            return false;
        }

        return $this->hasFullyQualifiedEnumName(
            $fq_enum_name,
            $location,
            $context,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function interfaceExtends(int $interface_name, int $possible_parent): bool
    {
        return isset($this->getParentInterfaces($interface_name)[Interner::lower($possible_parent)]);
    }

    /**
     * @return array<int, int> all interfaces extended by $interface_name (lowercase name id => name id)
     * @psalm-mutation-free
     */
    public function getParentInterfaces(int $fq_interface_name): array
    {
        return $this->classlike_storage_provider->get($fq_interface_name)->parent_interfaces;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function traitExists(int $fq_trait_name, ?CodeLocation $location = null, ?Context $context = null): bool
    {
        return $this->hasFullyQualifiedTraitName($fq_trait_name, $location, $context);
    }

    /**
     * Determine whether or not a class has the correct casing
     *
     * @psalm-mutation-free
     */
    public function classHasCorrectCasing(int $fq_class_name): bool
    {
        if ($fq_class_name === StrId::Generator) {
            return true;
        }

        if (isset($this->existing_classlike_aliases[$fq_class_name])) {
            return true;
        }

        return isset($this->existing_classes[$fq_class_name]);
    }

    /**
     * @psalm-mutation-free
     */
    public function interfaceHasCorrectCasing(int $fq_interface_name): bool
    {
        if (isset($this->existing_classlike_aliases[$fq_interface_name])) {
            return true;
        }

        return isset($this->existing_interfaces[$fq_interface_name]);
    }

    /**
     * @psalm-mutation-free
     */
    public function enumHasCorrectCasing(int $fq_enum_name): bool
    {
        if (isset($this->existing_classlike_aliases[$fq_enum_name])) {
            return true;
        }

        return isset($this->existing_enums[$fq_enum_name]);
    }

    /**
     * @psalm-mutation-free
     */
    public function traitHasCorrectCasing(int $fq_trait_name): bool
    {
        if (isset($this->existing_classlike_aliases[$fq_trait_name])) {
            return true;
        }

        return isset($this->existing_traits[$fq_trait_name]);
    }

    public function getTraitNode(int $fq_trait_name): PhpParser\Node\Stmt\Trait_
    {
        $fq_trait_name_lc = Interner::lower($fq_trait_name);

        if (isset($this->trait_nodes[$fq_trait_name_lc])) {
            return $this->trait_nodes[$fq_trait_name_lc];
        }

        $storage = $this->classlike_storage_provider->get($fq_trait_name);

        if (!$storage->location) {
            throw new UnexpectedValueException('Storage should exist for ' . Interner::str($fq_trait_name));
        }

        $codebase = ProjectAnalyzer::getInstance()->getCodebase();
        $file_statements = $codebase->getStatementsForFile(
            $storage->location->file_path,
        );

        $trait_finder = new TraitFinder($fq_trait_name);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            $trait_finder,
        );

        $traverser->traverse($file_statements);

        $trait_node = $trait_finder->getNode();

        if ($trait_node) {
            $this->trait_nodes[$fq_trait_name_lc] = $trait_node;

            return $trait_node;
        }

        throw new UnexpectedValueException(
            'Could not locate trait statement for ' . Interner::str($fq_trait_name),
        );
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addClassAlias(int $fq_class_name, int $alias_name): void
    {
        $this->classlike_aliases_map[Interner::lower($alias_name)] = $fq_class_name;
        $this->existing_classlike_aliases[$alias_name] = true;
    }

    /** @psalm-mutation-free */
    public function getUnAliasedName(int $alias_name): int
    {
        $alias_name_lc = Interner::lower($alias_name);
        if ($this->existing_classlikes_lc[$alias_name_lc] ?? false) {
            return $alias_name;
        }

        $result = $this->classlike_aliases_map[$alias_name_lc] ?? $alias_name;
        if ($result === $alias_name) {
            return $result;
        }

        return $this->getUnAliasedName($result);
    }

    public function consolidateAnalyzedData(Methods $methods, ?Progress $progress, bool $find_unused_code): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $progress->debug('Checking class references' . PHP_EOL);

        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        $code_use_graph = $codebase->code_use_graph;

        // the class hierarchy and the public API may have changed since the graph was cached
        $code_use_graph->removeEdgesOfTypes(CodeUseGraph::STRUCTURAL_EDGES);

        foreach ($this->existing_classlikes_lc as $fq_class_name_lc => $_) {
            try {
                $classlike_storage = $this->classlike_storage_provider->get($fq_class_name_lc);
            } catch (InvalidArgumentException) {
                continue;
            }

            if (!$classlike_storage->location
                || !$this->config->isInProjectDirs($classlike_storage->location->file_path)
            ) {
                continue;
            }

            $class_node = CodeUseGraph::classNode($fq_class_name_lc);

            if ($classlike_storage->public_api) {
                $code_use_graph->markAsPublicApi($class_node);
            }

            foreach ($classlike_storage->methods as $method_name => $method_storage) {
                $method_node = CodeUseGraph::functionLikeNode(new MethodIdentifier($fq_class_name_lc, $method_name));

                // a used method means its class is used
                $code_use_graph->addEdge($method_node, $class_node, CodeUseGraph::EDGE_METHOD);

                if ($method_storage->public_api
                    || ($classlike_storage->public_api
                        && ($method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                            || ($method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PROTECTED
                                && !$classlike_storage->final)))
                ) {
                    $code_use_graph->markAsPublicApi($method_node);
                }
            }
        }

        foreach ($this->existing_classlikes_lc as $fq_class_name_lc => $_) {
            try {
                $classlike_storage = $this->classlike_storage_provider->get($fq_class_name_lc);
            } catch (InvalidArgumentException) {
                continue;
            }

            if (!$classlike_storage->location
                || !$this->config->isInProjectDirs($classlike_storage->location->file_path)
            ) {
                continue;
            }

            $class_node = CodeUseGraph::classNode($fq_class_name_lc);

            // calls to an overridden parent or interface method may end up in the overriding method
            foreach ($classlike_storage->declaring_method_ids as $method_name => $declaring_method_id) {
                $method_node = CodeUseGraph::functionLikeNode($declaring_method_id);
                $return_node = CodeUseGraph::functionLikeReturnNode($declaring_method_id);

                $code_use_graph->addEdge($return_node, $method_node, CodeUseGraph::EDGE_RETURN);

                $appearing_method_id = $classlike_storage->appearing_method_ids[$method_name] ?? null;

                if ($appearing_method_id !== null
                    && Interner::lower($appearing_method_id->fq_class_name) === $fq_class_name_lc
                    && Interner::lower($declaring_method_id->fq_class_name) !== $fq_class_name_lc
                ) {
                    // a trait method is analysed (and records its references) once per using class,
                    // as `UsingClass::method`, while calls resolve to the declaring `Trait::method`
                    $appearing_method_id_lc = new MethodIdentifier($fq_class_name_lc, $method_name);
                    $appearing_method_node = CodeUseGraph::functionLikeNode($appearing_method_id_lc);
                    $appearing_return_node = CodeUseGraph::functionLikeReturnNode($appearing_method_id_lc);

                    $code_use_graph->addEdge($appearing_return_node, $appearing_method_node, CodeUseGraph::EDGE_RETURN);
                    $code_use_graph->addEdge($appearing_method_node, $class_node, CodeUseGraph::EDGE_METHOD);
                    $code_use_graph->addEdge($method_node, $appearing_method_node, CodeUseGraph::EDGE_OVERRIDE);
                    $code_use_graph->addEdge($return_node, $appearing_return_node, CodeUseGraph::EDGE_OVERRIDE);
                }

                $parent_method_ids = $classlike_storage->overridden_method_ids[$method_name] ?? [];

                foreach ($classlike_storage->class_implements as $fq_interface_name_lc => $_) {
                    if (!isset($parent_method_ids[$fq_interface_name_lc])) {
                        try {
                            $interface_storage = $this->classlike_storage_provider->get($fq_interface_name_lc);
                        } catch (InvalidArgumentException) {
                            continue;
                        }

                        if (isset($interface_storage->methods[$method_name])) {
                            $parent_method_ids[$fq_interface_name_lc] = new MethodIdentifier(
                                $interface_storage->name,
                                $method_name,
                            );
                        }
                    }
                }

                foreach ($parent_method_ids as $parent_method_id) {
                    $code_use_graph->addEdge(
                        CodeUseGraph::functionLikeNode($parent_method_id),
                        $method_node,
                        CodeUseGraph::EDGE_OVERRIDE,
                    );
                    $code_use_graph->addEdge(
                        CodeUseGraph::functionLikeReturnNode($parent_method_id),
                        $return_node,
                        CodeUseGraph::EDGE_OVERRIDE,
                    );
                }
            }
        }

        $code_use_graph->resolve(function (string $node_id): bool {
            $owner_class = CodeUseGraph::getOwnerClass($node_id);

            if ($owner_class === null) {
                return false;
            }

            try {
                $owner_storage = $this->classlike_storage_provider->get($owner_class);
            } catch (InvalidArgumentException) {
                // unknown class, e.g. a caller made up by a plugin
                return true;
            }

            return !$owner_storage->location
                || !$this->config->isInProjectDirs($owner_storage->location->file_path);
        });

        foreach ($this->existing_classlikes_lc as $fq_class_name_lc => $_) {
            try {
                $classlike_storage = $this->classlike_storage_provider->get($fq_class_name_lc);
            } catch (InvalidArgumentException) {
                continue;
            }
            if ($classlike_storage->location
                && $this->config->isInProjectDirs($classlike_storage->location->file_path)
            ) {
                if (!$classlike_storage->is_trait) {
                    if ($find_unused_code) {
                        $class_node = CodeUseGraph::classNode($fq_class_name_lc);

                        // a class that is only alive because of its own methods
                        // still has its members unchecked
                        if ($code_use_graph->isUsed($class_node)
                            && ($classlike_storage->public_api || $code_use_graph->isReferenced($class_node))
                        ) {
                            $this->checkMethodReferences($classlike_storage, $methods);
                            $this->checkPropertyReferences($classlike_storage);
                        } else {
                            IssueBuffer::maybeAdd(
                                new UnusedClass(
                                    'Class ' . Interner::str($classlike_storage->name) . ' is never used',
                                    $classlike_storage->location,
                                    $classlike_storage->name,
                                ),
                                $classlike_storage->suppressed_issues,
                            );
                        }
                        $this->checkMethodParamReferences($classlike_storage);
                    }
                    if (!$classlike_storage->public_api
                        && !$classlike_storage->has_children
                        && !$classlike_storage->abstract
                        && !$classlike_storage->final
                        && !$classlike_storage->is_enum
                        && !$classlike_storage->is_interface
                    ) {
                        IssueBuffer::maybeAdd(
                            new ClassMustBeFinal(
                                'Class ' . Interner::str($classlike_storage->name)
                                    . ' is never extended and is not part of the public API'
                                    .', and thus must be made final.',
                                $classlike_storage->location,
                                $classlike_storage->name,
                            ),
                            $classlike_storage->suppressed_issues,
                            true,
                        );
                        
                        if ($codebase->alter_code
                            && $classlike_storage->stmt_location !== null
                            && isset($project_analyzer->getIssuesToFix()['ClassMustBeFinal'])
                        ) {
                            $selection = $classlike_storage->stmt_location->getSnippet();
                            $insert_pos = strpos($selection, "class");
            
                            if ($insert_pos === false) {
                                $insert_pos = $classlike_storage->stmt_location->getSelectionBounds()[0];
                            }

                            FileManipulationBuffer::add($classlike_storage->stmt_location->file_path, [
                                new FileManipulation($insert_pos, $insert_pos, 'final ', true),
                            ]);
                        }
                    }

                    $this->findPossibleMethodParamTypes($classlike_storage);
                } elseif (!$classlike_storage->trait_used) {
                    continue;
                }

                $mut = $codebase->analyzer->mutable_classes[$fq_class_name_lc]
                    ?? Mutations::LEVEL_NONE;
                if ($mut !== Mutations::LEVEL_ALL
                    && !$classlike_storage->has_mutations_annotation
                ) {
                    $change = $codebase->alter_code
                        && isset($project_analyzer->getIssuesToFix()['MissingImmutableAnnotation']);

                    $stmts = $codebase->getStatementsForFile(
                        $classlike_storage->location->file_path,
                    );

                    foreach ($stmts as $stmt) {
                        if ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                            foreach ($stmt->stmts as $namespace_stmt) {
                                if ($namespace_stmt instanceof PhpParser\Node\Stmt\ClassLike
                                    && Interner::internLower(
                                        (string) $stmt->name . '\\' . (string) $namespace_stmt->name,
                                    )
                                        === $fq_class_name_lc
                                ) {
                                    self::makeImmutable(
                                        $mut,
                                        $change,
                                        $classlike_storage,
                                        $namespace_stmt,
                                        $project_analyzer,
                                    );
                                }
                            }
                        } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassLike
                            && Interner::internLower((string) $stmt->name) === $fq_class_name_lc
                        ) {
                            self::makeImmutable(
                                $mut,
                                $change,
                                $classlike_storage,
                                $stmt,
                                $project_analyzer,
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Mutations::LEVEL_* $allowed_mutations
     */
    private static function makeImmutable(
        int $allowed_mutations,
        bool $change,
        ClassLikeStorage $storage,
        ClassLike $class_stmt,
        ProjectAnalyzer $project_analyzer,
        ?string $msg = null,
    ): void {
        if ($class_stmt instanceof VirtualNode || $storage->location === null) {
            return;
        }
        if ($storage->is_interface) {
            if ($storage->has_mutations_annotation) {
                return;
            }
            IssueBuffer::maybeAdd(
                new MissingInterfaceImmutableAnnotation(
                    Interner::str($storage->name)
                    . ' must be marked with either @psalm-pure, @psalm-immutable, @psalm-mutation-free,'
                    . ' @psalm-external-mutation-free or @psalm-mutable to aid security analysis',
                    $storage->location,
                ),
                $storage->suppressed_issues,
            );

            return;
        }

        if ($change) {
            $manipulator = ClassDocblockManipulator::getForClass(
                $project_analyzer,
                $storage->location->file_path,
                $class_stmt,
            );

            $manipulator->setAllowedMutations($allowed_mutations);
        }

        IssueBuffer::maybeAdd(
            new MissingImmutableAnnotation(
                $msg ?? (Interner::str($storage->name) . ' must be marked '.Mutations::TO_ATTRIBUTE_CLASSLIKE[
                    $allowed_mutations
                ].' to aid security analysis,'
                    .' run with --alter --issues=MissingImmutableAnnotation to fix this'),
                $storage->location,
            ),
            $storage->suppressed_issues,
        );
    }

    public function moveMethods(Methods $methods, ?Progress $progress = null): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        if (!$codebase->methods_to_move) {
            return;
        }

        $progress->debug('Refactoring methods ' . PHP_EOL);

        $code_migrations = [];

        foreach ($codebase->methods_to_move as $source_fq_class_name_lc => $source_methods) {
            foreach ($source_methods as $source_method_name_lc => [$destination_fq_class_name, $destination_name]) {
                try {
                    $source_method_storage = $methods->getStorage(
                        new MethodIdentifier($source_fq_class_name_lc, $source_method_name_lc),
                    );
                } catch (InvalidArgumentException) {
                    continue;
                }

                try {
                    $classlike_storage = $this->classlike_storage_provider->get($destination_fq_class_name);
                } catch (InvalidArgumentException) {
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
                            new FileManipulation(
                                $old_method_name_bounds[0],
                                $old_method_name_bounds[1],
                                Interner::str($destination_name),
                            ),
                        ],
                    );

                    $selection = $classlike_storage->stmt_location->getSnippet();

                    $insert_pos = strrpos($selection, "\n", -1);

                    if (!$insert_pos) {
                        $insert_pos = strlen($selection) - 1;
                    } else {
                        ++$insert_pos;
                    }

                    $code_migrations[] = new CodeMigration(
                        $source_method_storage->stmt_location->file_path,
                        $old_method_bounds[0],
                        $old_method_bounds[1],
                        $classlike_storage->stmt_location->file_path,
                        $new_class_bounds[0] + $insert_pos,
                    );
                }
            }
        }

        FileManipulationBuffer::addCodeMigrations($code_migrations);
    }

    public function moveProperties(Properties $properties, ?Progress $progress = null): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        if (!$codebase->properties_to_move) {
            return;
        }

        $progress->debug('Refacting properties ' . PHP_EOL);

        $code_migrations = [];

        foreach ($codebase->properties_to_move as $source_fq_class_name => $source_properties) {
            foreach ($source_properties as $source_property_name => [$destination_fq_class_name, $destination_name]) {
                try {
                    $source_property_storage = $properties->getStorage(
                        new PropertyIdentifier($source_fq_class_name, $source_property_name),
                    );
                } catch (InvalidArgumentException) {
                    continue;
                }

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

                        $replace_type = TypeExpander::expandUnion(
                            $codebase,
                            $source_property_storage->type,
                            $source_classlike_storage->name,
                            $source_classlike_storage->name,
                            $source_classlike_storage->parent_class,
                        );

                        $this->airliftClassDefinedDocblockType(
                            $replace_type,
                            $destination_fq_class_name,
                            $source_property_storage->stmt_location->file_path,
                            $bounds[0],
                            $bounds[1],
                        );
                    }

                    $new_class_bounds = $destination_classlike_storage->stmt_location->getSnippetBounds();
                    $old_property_bounds = $source_property_storage->stmt_location->getSnippetBounds();

                    $old_property_name_bounds = $source_property_storage->location->getSelectionBounds();

                    FileManipulationBuffer::add(
                        $source_property_storage->stmt_location->file_path,
                        [
                            new FileManipulation(
                                $old_property_name_bounds[0],
                                $old_property_name_bounds[1],
                                '$' . Interner::str($destination_name),
                            ),
                        ],
                    );

                    $selection = $destination_classlike_storage->stmt_location->getSnippet();

                    $insert_pos = strrpos($selection, "\n", -1);

                    if (!$insert_pos) {
                        $insert_pos = strlen($selection) - 1;
                    } else {
                        ++$insert_pos;
                    }

                    $code_migrations[] = new CodeMigration(
                        $source_property_storage->stmt_location->file_path,
                        $old_property_bounds[0],
                        $old_property_bounds[1],
                        $destination_classlike_storage->stmt_location->file_path,
                        $new_class_bounds[0] + $insert_pos,
                    );
                }
            }
        }

        FileManipulationBuffer::addCodeMigrations($code_migrations);
    }

    public function moveClassConstants(?Progress $progress = null): void
    {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        if (!$codebase->class_constants_to_move) {
            return;
        }

        $progress->debug('Refacting constants ' . PHP_EOL);

        $code_migrations = [];

        foreach ($codebase->class_constants_to_move as $source_fq_class_name => $source_constants) {
            foreach ($source_constants as $source_const_name => [$destination_fq_class_name, $destination_name]) {
                $source_classlike_storage = $this->classlike_storage_provider->get($source_fq_class_name);
                $destination_classlike_storage = $this->classlike_storage_provider->get($destination_fq_class_name);

                if (!isset($source_classlike_storage->constants[$source_const_name])) {
                    continue;
                }

                $constant_storage = $source_classlike_storage->constants[$source_const_name];

                $source_const_stmt_location = $constant_storage->stmt_location;
                $source_const_location = $constant_storage->location;

                if (!$source_const_location || !$source_const_stmt_location) {
                    continue;
                }

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
                            new FileManipulation(
                                $old_const_name_bounds[0],
                                $old_const_name_bounds[1],
                                Interner::str($destination_name),
                            ),
                        ],
                    );

                    $selection = $destination_classlike_storage->stmt_location->getSnippet();

                    $insert_pos = strrpos($selection, "\n", -1);

                    if (!$insert_pos) {
                        $insert_pos = strlen($selection) - 1;
                    } else {
                        ++$insert_pos;
                    }

                    $code_migrations[] = new CodeMigration(
                        $source_const_stmt_location->file_path,
                        $old_const_bounds[0],
                        $old_const_bounds[1],
                        $destination_classlike_storage->stmt_location->file_path,
                        $new_class_bounds[0] + $insert_pos,
                    );
                }
            }
        }

        FileManipulationBuffer::addCodeMigrations($code_migrations);
    }

    /**
     * Returns the class a method is being moved to, if any
     *
     * @psalm-mutation-free
     */
    private static function getMethodDestinationClass(Codebase $codebase, ?MethodIdentifier $method_id): ?int
    {
        if ($method_id === null) {
            return null;
        }

        $destination = $codebase->methods_to_move[Interner::lower($method_id->fq_class_name)]
            [Interner::lower($method_id->method_name)] ?? null;

        return $destination === null ? null : $destination[0];
    }

    public function handleClassLikeReferenceInMigration(
        Codebase $codebase,
        StatementsSource $source,
        PhpParser\Node $class_name_node,
        int $fq_class_name,
        ?Context $context,
        bool $force_change = false,
        bool $was_self = false,
    ): bool {
        if ($class_name_node instanceof VirtualNode) {
            return false;
        }
        $calling_fq_class_name = $source->getFQCLN();
        $calling_method_id = $context?->calling_method_id;

        $destination_class = $codebase->methods_to_move && $calling_fq_class_name
            ? self::getMethodDestinationClass($codebase, $calling_method_id)
            : null;

        // if we're inside a moved class static method
        if ($destination_class !== null && $calling_fq_class_name !== null) {
            $intended_fq_class_name = Interner::equalsLower($calling_fq_class_name, $fq_class_name)
                && isset($codebase->classes_to_move[Interner::lower($calling_fq_class_name)])
                ? $destination_class
                : $fq_class_name;

            $this->airliftClassLikeReference(
                $intended_fq_class_name,
                $destination_class,
                $source->getFilePath(),
                (int) $class_name_node->getAttribute('startFilePos'),
                (int) $class_name_node->getAttribute('endFilePos') + 1,
                $class_name_node instanceof PhpParser\Node\Scalar\MagicConst\Class_,
                $was_self,
            );

            return true;
        }

        // if we're outside a moved class, but we're changing all references to a class
        if (isset($codebase->class_transforms[Interner::lower($fq_class_name)])) {
            $new_fq_class_name = $codebase->class_transforms[Interner::lower($fq_class_name)];
            $file_manipulations = [];

            if ($class_name_node instanceof PhpParser\Node\Identifier) {
                $destination_parts = explode('\\', Interner::str($new_fq_class_name));

                $destination_class_name = array_pop($destination_parts);

                $file_manipulations[] = new FileManipulation(
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                    $destination_class_name,
                );

                FileManipulationBuffer::add($source->getFilePath(), $file_manipulations);

                return true;
            }

            $uses_flipped = $source->getAliasedClassesFlipped();
            $uses_flipped_replaceable = $source->getAliasedClassesFlippedReplaceable();

            $old_fq_class_name = Interner::lower($fq_class_name);

            $migrated_source_fqcln = $calling_fq_class_name;

            if ($calling_fq_class_name
                && isset($codebase->class_transforms[Interner::lower($calling_fq_class_name)])
            ) {
                $migrated_source_fqcln = $codebase->class_transforms[Interner::lower($calling_fq_class_name)];
            }

            $source_namespace = $source->getNamespace();

            if ($migrated_source_fqcln && $calling_fq_class_name !== $migrated_source_fqcln) {
                $source_namespace = self::getNamespaceOf($migrated_source_fqcln);
            }

            if (isset($uses_flipped_replaceable[$old_fq_class_name])) {
                $alias = $uses_flipped_replaceable[$old_fq_class_name];
                unset($uses_flipped[$old_fq_class_name]);
                if (self::getShortNameLc($old_fq_class_name) === Interner::lower($alias)) {
                    $uses_flipped[Interner::lower($new_fq_class_name)] = self::getShortName($new_fq_class_name);
                } else {
                    $uses_flipped[Interner::lower($new_fq_class_name)] = $alias;
                }
            }

            $file_manipulations[] = new FileManipulation(
                (int) $class_name_node->getAttribute('startFilePos'),
                (int) $class_name_node->getAttribute('endFilePos') + 1,
                Type::getStringFromFQCLN(
                    $new_fq_class_name,
                    $source_namespace,
                    $uses_flipped,
                    $migrated_source_fqcln,
                    $was_self,
                )
                    . ($class_name_node instanceof PhpParser\Node\Scalar\MagicConst\Class_ ? '::class' : ''),
            );

            FileManipulationBuffer::add($source->getFilePath(), $file_manipulations);

            return true;
        }

        // if we're inside a moved class (could be a method, could be a property/class const default)
        if ($codebase->classes_to_move
            && $calling_fq_class_name
            && isset($codebase->classes_to_move[Interner::lower($calling_fq_class_name)])
        ) {
            $destination_class = $codebase->classes_to_move[Interner::lower($calling_fq_class_name)];

            if ($class_name_node instanceof PhpParser\Node\Identifier) {
                $destination_parts = explode('\\', Interner::str($destination_class));

                $destination_class_name = array_pop($destination_parts);
                $file_manipulations = [];

                $file_manipulations[] = new FileManipulation(
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                    $destination_class_name,
                );

                FileManipulationBuffer::add($source->getFilePath(), $file_manipulations);
            } else {
                $this->airliftClassLikeReference(
                    Interner::equalsLower($calling_fq_class_name, $fq_class_name)
                        ? $destination_class
                        : $fq_class_name,
                    $destination_class,
                    $source->getFilePath(),
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                    $class_name_node instanceof PhpParser\Node\Scalar\MagicConst\Class_,
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
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                );
            } else {
                $file_manipulations = [];

                $file_manipulations[] = new FileManipulation(
                    (int) $class_name_node->getAttribute('startFilePos'),
                    (int) $class_name_node->getAttribute('endFilePos') + 1,
                    Type::getStringFromFQCLN(
                        $fq_class_name,
                        $source->getNamespace(),
                        $source->getAliasedClassesFlipped(),
                        null,
                    ),
                );

                FileManipulationBuffer::add($source->getFilePath(), $file_manipulations);
            }

            return true;
        }

        return false;
    }

    /**
     * @psalm-pure
     */
    private static function getNamespaceOf(int $fq_class_name): int
    {
        $parts = explode('\\', Interner::str($fq_class_name), -1);

        return Interner::intern(implode('\\', $parts));
    }

    /**
     * @psalm-pure
     */
    private static function getShortName(int $fq_class_name): int
    {
        $parts = explode('\\', Interner::str($fq_class_name));

        return Interner::intern(end($parts));
    }

    /**
     * @psalm-pure
     */
    private static function getShortNameLc(int $fq_class_name): int
    {
        return Interner::lower(self::getShortName($fq_class_name));
    }

    public function handleDocblockTypeInMigration(
        Codebase $codebase,
        StatementsSource $source,
        Union $type,
        CodeLocation $type_location,
        ?MethodIdentifier $calling_method_id,
    ): void {
        $calling_fq_class_name = $source->getFQCLN();
        $fq_class_name_lc = $calling_fq_class_name !== null ? Interner::lower($calling_fq_class_name) : null;

        $moved_type = false;

        $destination_class = $codebase->methods_to_move && $calling_fq_class_name
            ? self::getMethodDestinationClass($codebase, $calling_method_id)
            : null;

        // if we're inside a moved class static method
        if ($destination_class !== null) {
            $bounds = $type_location->getSelectionBounds();

            $this->airliftClassDefinedDocblockType(
                $type,
                $destination_class,
                $source->getFilePath(),
                $bounds[0],
                $bounds[1],
            );

            $moved_type = true;
        }

        // if we're outside a moved class, but we're changing all references to a class
        if (!$moved_type && $codebase->class_transforms) {
            $uses_flipped = $source->getAliasedClassesFlipped();
            $uses_flipped_replaceable = $source->getAliasedClassesFlippedReplaceable();

            $migrated_source_fqcln = $calling_fq_class_name;

            if ($fq_class_name_lc !== null
                && isset($codebase->class_transforms[$fq_class_name_lc])
            ) {
                $migrated_source_fqcln = $codebase->class_transforms[$fq_class_name_lc];
            }

            $source_namespace = $source->getNamespace();

            if ($migrated_source_fqcln && $calling_fq_class_name !== $migrated_source_fqcln) {
                $source_namespace = self::getNamespaceOf($migrated_source_fqcln);
            }

            foreach ($codebase->class_transforms as $old_fq_class_name => $new_fq_class_name) {
                if (isset($uses_flipped_replaceable[$old_fq_class_name])) {
                    $alias = $uses_flipped_replaceable[$old_fq_class_name];
                    unset($uses_flipped[$old_fq_class_name]);
                    if (self::getShortNameLc($old_fq_class_name) === Interner::lower($alias)) {
                        $uses_flipped[Interner::lower($new_fq_class_name)] = self::getShortName($new_fq_class_name);
                    } else {
                        $uses_flipped[Interner::lower($new_fq_class_name)] = $alias;
                    }
                }
            }

            foreach ($codebase->class_transforms as $old_fq_class_name => $new_fq_class_name) {
                if ($type->containsClassLike($old_fq_class_name)) {
                    $type = $type->replaceClassLike(
                        $old_fq_class_name,
                        $new_fq_class_name,
                    );

                    $bounds = $type_location->getSelectionBounds();

                    $file_manipulations = [];

                    $file_manipulations[] = new FileManipulation(
                        $bounds[0],
                        $bounds[1],
                        $type->toNamespacedString(
                            $source_namespace,
                            $uses_flipped,
                            $migrated_source_fqcln,
                            false,
                        ),
                    );

                    FileManipulationBuffer::add(
                        $source->getFilePath(),
                        $file_manipulations,
                    );

                    $moved_type = true;
                }
            }
        }

        // if we're inside a moved class (could be a method, could be a property/class const default)
        if (!$moved_type
            && $codebase->classes_to_move
            && $fq_class_name_lc !== null
            && isset($codebase->classes_to_move[$fq_class_name_lc])
        ) {
            $bounds = $type_location->getSelectionBounds();

            $destination_class = $codebase->classes_to_move[$fq_class_name_lc];

            if ($type->containsClassLike($fq_class_name_lc)) {
                $type = $type->replaceClassLike(
                    $fq_class_name_lc,
                    $destination_class,
                );
            }

            $this->airliftClassDefinedDocblockType(
                $type,
                $destination_class,
                $source->getFilePath(),
                $bounds[0],
                $bounds[1],
            );
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function airliftClassLikeReference(
        int $fq_class_name,
        int $destination_fq_class_name,
        string $source_file_path,
        int $source_start,
        int $source_end,
        bool $add_class_constant = false,
        bool $allow_self = false,
    ): void {
        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        $destination_class_storage = $codebase->classlike_storage_provider->get($destination_fq_class_name);

        if (!$destination_class_storage->aliases) {
            throw new UnexpectedValueException('Aliases should not be null');
        }

        $file_manipulations = [];

        $file_manipulations[] = new FileManipulation(
            $source_start,
            $source_end,
            Type::getStringFromFQCLN(
                $fq_class_name,
                $destination_class_storage->aliases->namespace,
                $destination_class_storage->aliases->uses_flipped,
                $destination_class_storage->name,
                $allow_self,
            ) . ($add_class_constant ? '::class' : ''),
        );

        FileManipulationBuffer::add(
            $source_file_path,
            $file_manipulations,
        );
    }

    /**
     * @psalm-external-mutation-free
     */
    public function airliftClassDefinedDocblockType(
        Union $type,
        int $destination_fq_class_name,
        string $source_file_path,
        int $source_start,
        int $source_end,
    ): void {
        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        $destination_class_storage = $codebase->classlike_storage_provider->get($destination_fq_class_name);

        if (!$destination_class_storage->aliases) {
            throw new UnexpectedValueException('Aliases should not be null');
        }

        $file_manipulations = [];

        $file_manipulations[] = new FileManipulation(
            $source_start,
            $source_end,
            $type->toNamespacedString(
                $destination_class_storage->aliases->namespace,
                $destination_class_storage->aliases->uses_flipped,
                $destination_class_storage->name,
                false,
            ),
        );

        FileManipulationBuffer::add(
            $source_file_path,
            $file_manipulations,
        );
    }

    /**
     * @param ReflectionProperty::IS_PUBLIC|ReflectionProperty::IS_PROTECTED|ReflectionProperty::IS_PRIVATE
     *  $visibility
     * @return array<int, ClassConstantStorage> constant name id => storage
     * @psalm-mutation-free
     */
    public function getConstantsForClass(int $class_name, int $visibility): array
    {
        $storage = $this->classlike_storage_provider->get($class_name);

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return array_filter(
                $storage->constants,
                static fn(ClassConstantStorage $constant): bool => $constant->type
                    && $constant->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC,
            );
        }

        if ($visibility === ReflectionProperty::IS_PROTECTED) {
            return array_filter(
                $storage->constants,
                static fn(ClassConstantStorage $constant): bool => $constant->type
                    && ($constant->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                        || $constant->visibility === ClassLikeAnalyzer::VISIBILITY_PROTECTED),
            );
        }

        return array_filter(
            $storage->constants,
            static fn(ClassConstantStorage $constant): bool => $constant->type !== null,
        );
    }

    /**
     * @param int $constant_name interned constant name, or a `FOO_*` constant name pattern
     * @param ReflectionProperty::IS_PUBLIC|ReflectionProperty::IS_PROTECTED|ReflectionProperty::IS_PRIVATE $visibility
     */
    public function getClassConstantType(
        int $class_name,
        int $constant_name,
        int $visibility,
        ?StatementsAnalyzer $statements_analyzer = null,
        array $visited_constant_ids = [],
        bool $late_static_binding = false,
        bool $in_value_of_context = false,
    ): ?Union {
        if (!$this->classlike_storage_provider->has($class_name)) {
            return null;
        }

        $storage = $this->classlike_storage_provider->get($class_name);

        $enum_types = null;

        if ($storage->is_enum) {
            $enum_types = $this->getEnumType(
                $storage,
                $constant_name,
            );

            if ($in_value_of_context) {
                return $enum_types;
            }
        }

        $constant_types = $this->getConstantType(
            $storage,
            $constant_name,
            $visibility,
            $statements_analyzer,
            $visited_constant_ids,
            $late_static_binding,
        );

        $types = [];
        if ($enum_types !== null) {
            $types = array_merge($types, $enum_types->getAtomicTypes());
        }

        if ($constant_types !== null) {
            $types = array_merge($types, $constant_types->getAtomicTypes());
        }

        if ($types === []) {
            return null;
        }

        return new Union($types);
    }

    private function checkMethodReferences(ClassLikeStorage $classlike_storage, Methods $methods): void
    {
        $project_analyzer = ProjectAnalyzer::getInstance();
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
                } catch (InvalidArgumentException) {
                    continue;
                }

                $method_storage = $declaring_classlike_storage->methods[$declaring_method_name];
                $method_id = $declaring_method_id;
            }

            if ($classlike_storage->public_api
                && ($method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                    || ($method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PROTECTED
                        && !$classlike_storage->final
                    )
                )
            ) {
                continue;
            }

            if ($method_storage->public_api) {
                continue;
            }

            if ($method_storage->location
                && !$project_analyzer->canReportIssues($method_storage->location->file_path)
                && !$codebase->analyzer->canReportIssues($method_storage->location->file_path)
            ) {
                continue;
            }

            $method_referenced = $codebase->code_use_graph->isUsed(
                CodeUseGraph::functionLikeNode($method_id),
            );

            if (!$method_referenced
                && $method_storage->location
            ) {
                if ($method_name !== StrId::__destruct
                    && $method_name !== StrId::__clone
                    && $method_name !== StrId::__invoke
                    && $method_name !== StrId::__unset
                    && $method_name !== StrId::__isset
                    && $method_name !== StrId::__sleep
                    && $method_name !== StrId::__wakeup
                    && $method_name !== StrId::__serialize
                    && $method_name !== StrId::__unserialize
                    && $method_name !== StrId::__set_state
                    && $method_name !== StrId::__debuginfo
                    && $method_name !== StrId::__tostring // can be called in array_unique
                ) {
                    $method_location = $method_storage->location;

                    $issue_method_id = new MethodIdentifier(
                        $classlike_storage->name,
                        $method_name,
                    );
                    $cased_method_id = Interner::str($classlike_storage->name) . '::'
                        . Interner::str($method_storage->cased_name ?? $method_name);

                    $method_name_str = Interner::str($method_name);
                    $class_name_lc_str = Interner::str(Interner::lower($classlike_storage->name));

                    if ($method_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
                        $has_parent_references = false;

                        if ($codebase->classImplements($classlike_storage->name, StrId::Serializable)
                            && ($method_name === StrId::serialize || $method_name === StrId::unserialize)
                        ) {
                            continue;
                        }

                        if ($codebase->classImplements($classlike_storage->name, StrId::JsonSerializable)
                            && ($method_name === StrId::jsonserialize)
                        ) {
                            continue;
                        }

                        $has_variable_calls = $codebase->analyzer->hasMixedMemberName($method_name_str)
                            || $codebase->analyzer->hasMixedMemberName($class_name_lc_str . '::');

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

                                $parent_method_referenced = $codebase->code_use_graph->isUsed(
                                    CodeUseGraph::functionLikeNode($parent_method_id),
                                );

                                if (!$parent_method_storage->abstract || $parent_method_referenced) {
                                    $has_parent_references = true;
                                    break;
                                }
                            }
                        }

                        foreach ($classlike_storage->parent_classes as $parent_method_fqcln_lc => $_) {
                            if ($codebase->analyzer->hasMixedMemberName(
                                Interner::str($parent_method_fqcln_lc) . '::',
                            )) {
                                $has_variable_calls = true;
                                break;
                            }
                        }

                        foreach ($classlike_storage->class_implements as $fq_interface_name_lc => $_) {
                            try {
                                $interface_storage = $this->classlike_storage_provider->get($fq_interface_name_lc);
                            } catch (InvalidArgumentException) {
                                continue;
                            }

                            if ($codebase->analyzer->hasMixedMemberName(
                                Interner::str($fq_interface_name_lc) . '::',
                            )) {
                                $has_variable_calls = true;
                            }

                            if (isset($interface_storage->methods[$method_name])) {
                                $interface_method_referenced = $codebase->code_use_graph->isUsed(
                                    CodeUseGraph::functionLikeNode(
                                        new MethodIdentifier($fq_interface_name_lc, $method_name),
                                    ),
                                );

                                if ($interface_method_referenced) {
                                    $has_parent_references = true;
                                }
                            }
                        }

                        if (!$has_parent_references) {
                            $issue = new PossiblyUnusedMethod(
                                'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                                    . ' calls to method ' . $cased_method_id
                                    . ($has_variable_calls ? ' (but did find some potential callers)' : ''),
                                $method_storage->location,
                                $issue_method_id,
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
                                        true,
                                    );
                                }
                            } else {
                                IssueBuffer::maybeAdd(
                                    $issue,
                                    $method_storage->suppressed_issues,
                                    $method_storage->stmt_location
                                        && !$declaring_classlike_storage->is_trait
                                        && !$has_variable_calls,
                                );
                            }
                        }
                    } elseif (!isset($classlike_storage->declaring_method_ids[StrId::__call])) {
                        $has_variable_calls = $codebase->analyzer->hasMixedMemberName(
                            $class_name_lc_str . '::',
                        ) || $codebase->analyzer->hasMixedMemberName($method_name_str);

                        if ($method_name === StrId::__construct) {
                            $issue = new UnusedConstructor(
                                'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                                    . ' calls to private constructor ' . $cased_method_id
                                    . ($has_variable_calls ? ' (but did find some potential callers)' : ''),
                                $method_location,
                                $issue_method_id,
                            );
                        } else {
                            $issue = new UnusedMethod(
                                'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                                    . ' calls to private method ' . $cased_method_id
                                    . ($has_variable_calls ? ' (but did find some potential callers)' : ''),
                                $method_location,
                                $issue_method_id,
                            );
                        }

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
                                    true,
                                );
                            }
                        } else {
                            IssueBuffer::maybeAdd(
                                $issue,
                                $method_storage->suppressed_issues,
                                $method_storage->stmt_location
                                && !$declaring_classlike_storage->is_trait
                                && !$has_variable_calls,
                            );
                        }
                    }
                }
            } else {
                // methods only reached through an overridden parent or interface method
                // are called by code that doesn't know about them, so their return value
                // is only checked when they're called directly
                $directly_referenced = $codebase->code_use_graph->getUsedReferencingNodes(
                    CodeUseGraph::functionLikeNode($method_id),
                    CodeUseGraph::EDGE_USE,
                ) !== [];

                if ($directly_referenced
                    && $method_storage->return_type
                    && $method_storage->return_type_location
                    && !$method_storage->return_type->isVoid()
                    && !$method_storage->return_type->isNever()
                    && $method_id->method_name !== StrId::__tostring
                    && ($method_storage->is_static || !$method_storage->probably_fluent)
                ) {
                    $method_return_referenced = $codebase->code_use_graph->isUsed(
                        CodeUseGraph::functionLikeReturnNode($method_id),
                    );

                    if (!$method_return_referenced) {
                        if ($method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
                            IssueBuffer::maybeAdd(
                                new UnusedReturnValue(
                                    'The return value for this private method is never used',
                                    $method_storage->return_type_location,
                                ),
                                $method_storage->suppressed_issues,
                            );
                        } else {
                            IssueBuffer::maybeAdd(
                                new PossiblyUnusedReturnValue(
                                    'The return value for this method is never used',
                                    $method_storage->return_type_location,
                                ),
                                $method_storage->suppressed_issues,
                            );
                        }
                    }
                }
            }
        }
    }


    private function checkMethodParamReferences(ClassLikeStorage $classlike_storage): void
    {
        foreach ($classlike_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            $appearing_fq_classlike_name = $appearing_method_id->fq_class_name;

            if ($appearing_fq_classlike_name !== $classlike_storage->name) {
                continue;
            }

            $method_id = $appearing_method_id;

            if (isset($classlike_storage->methods[$method_name])) {
                $method_storage = $classlike_storage->methods[$method_name];
            } else {
                $declaring_method_id = $classlike_storage->declaring_method_ids[$method_name];

                $declaring_fq_classlike_name = $declaring_method_id->fq_class_name;
                $declaring_method_name = $declaring_method_id->method_name;

                try {
                    $declaring_classlike_storage = $this->classlike_storage_provider->get($declaring_fq_classlike_name);
                } catch (InvalidArgumentException) {
                    continue;
                }

                $method_storage = $declaring_classlike_storage->methods[$declaring_method_name];
                $method_id = $declaring_method_id;
            }

            if ($method_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PRIVATE
                && !$classlike_storage->is_interface
            ) {
                foreach ($method_storage->params as $offset => $param_storage) {
                    if (empty($classlike_storage->overridden_method_ids[$method_name])
                        && $param_storage->location
                        && !$param_storage->promoted_property
                        && !$this->file_reference_provider->isMethodParamUsed(
                            $method_id,
                            $offset,
                        )
                    ) {
                        if ($method_storage->final) {
                            IssueBuffer::maybeAdd(
                                new UnusedParam(
                                    'Param #' . ($offset + 1) . ' is never referenced in this method',
                                    $param_storage->location,
                                ),
                                $method_storage->suppressed_issues,
                            );
                        } else {
                            IssueBuffer::maybeAdd(
                                new PossiblyUnusedParam(
                                    'Param #' . ($offset + 1) . ' is never referenced in this method',
                                    $param_storage->location,
                                ),
                                $method_storage->suppressed_issues,
                            );
                        }
                    }
                }
            }
        }
    }

    private function findPossibleMethodParamTypes(ClassLikeStorage $classlike_storage): void
    {
        $project_analyzer = ProjectAnalyzer::getInstance();
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
                } catch (InvalidArgumentException) {
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

            $method_class_lc = Interner::lower($method_id->fq_class_name);
            $method_name_lc = Interner::lower($method_id->method_name);

            if (isset($codebase->analyzer->possible_method_param_types[$method_class_lc][$method_name_lc])) {
                if ($method_storage->location) {
                    $possible_param_types
                        = $codebase->analyzer->possible_method_param_types[$method_class_lc][$method_name_lc];

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
                                if ($method_storage->params[$offset]->default_type instanceof Union) {
                                    $default_type = $method_storage->params[$offset]->default_type;
                                } else {
                                    $default_type_atomic = ConstantTypeResolver::resolve(
                                        $codebase->classlikes,
                                        $method_storage->params[$offset]->default_type,
                                        null,
                                    );

                                    $default_type = new Union([$default_type_atomic]);
                                }

                                $possible_type = Type::combineUnionTypes(
                                    $possible_type,
                                    $default_type,
                                );
                            }

                            if ($codebase->alter_code
                                && isset($project_analyzer->getIssuesToFix()['MissingParamType'])
                            ) {
                                $function_analyzer = $project_analyzer->getFunctionLikeAnalyzer(
                                    $method_id,
                                    $method_storage->location->file_path,
                                );

                                $has_variable_calls = $codebase->analyzer->hasMixedMemberName(
                                    Interner::str($method_name),
                                )
                                    || $codebase->analyzer->hasMixedMemberName(
                                        Interner::str(Interner::lower($classlike_storage->name)) . '::',
                                    );

                                if ($has_variable_calls) {
                                    $possible_type = $possible_type->setProperties(['from_docblock' => true]);
                                }

                                if ($function_analyzer) {
                                    $function_analyzer->addOrUpdateParamType(
                                        $project_analyzer,
                                        $param_name,
                                        $possible_type,
                                        $possible_type->from_docblock
                                            && $project_analyzer->only_replace_php_types_with_non_docblock_types,
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

    private function checkPropertyReferences(ClassLikeStorage $classlike_storage): void
    {
        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        foreach ($classlike_storage->properties as $property_name => $property_storage) {
            if ($classlike_storage->public_api
                && ($property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                    || ($property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PROTECTED
                        && !$classlike_storage->final
                    )
                )
            ) {
                continue;
            }

            $property_node = CodeUseGraph::propertyNode($classlike_storage->name, $property_name);
            $property_referenced = $codebase->code_use_graph->isUsed($property_node);

            $property_constructor_referenced = false;
            if ($property_referenced && $property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
                $property_references = $codebase->code_use_graph->getUsedReferencingNodes(
                    $property_node,
                    CodeUseGraph::EDGE_USE,
                );

                if (count($property_references) === 1) {
                    $constructor_node = CodeUseGraph::functionLikeNode(
                        new MethodIdentifier($classlike_storage->name, StrId::__construct),
                    );

                    $property_constructor_referenced = isset($property_references[$constructor_node])
                        && !$property_storage->is_static;
                }
            }

            if ((!$property_referenced || $property_constructor_referenced)
                && $property_storage->location
            ) {
                $property_id = new PropertyIdentifier($classlike_storage->name, $property_name);
                $property_name_str = Interner::str($property_name);

                if ($property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                    || $property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PROTECTED
                ) {
                    $has_parent_references = isset($classlike_storage->overridden_property_ids[$property_name]);

                    $has_variable_calls = $codebase->analyzer->hasMixedMemberName('$' . $property_name_str)
                        || $codebase->analyzer->hasMixedMemberName(
                            Interner::str(Interner::lower($classlike_storage->name)) . '::$',
                        );

                    foreach ($classlike_storage->parent_classes as $parent_method_fqcln_lc => $_) {
                        if ($codebase->analyzer->hasMixedMemberName(
                            Interner::str($parent_method_fqcln_lc) . '::$',
                        )) {
                            $has_variable_calls = true;
                            break;
                        }
                    }

                    foreach ($classlike_storage->class_implements as $fq_interface_name_lc => $_) {
                        if ($codebase->analyzer->hasMixedMemberName(
                            Interner::str($fq_interface_name_lc) . '::$',
                        )) {
                            $has_variable_calls = true;
                            break;
                        }
                    }

                    if (!$has_parent_references
                        && ($property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                            || !isset($classlike_storage->declaring_method_ids[StrId::__get]))
                    ) {
                        $issue = new PossiblyUnusedProperty(
                            'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                                . ' references to property ' . (string) $property_id
                                . ($has_variable_calls ? ' (but did find some potential references)' : ''),
                            $property_storage->location,
                            $property_id,
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
                                    true,
                                );
                            }
                        } else {
                            IssueBuffer::maybeAdd(
                                $issue,
                                $classlike_storage->suppressed_issues + $property_storage->suppressed_issues,
                            );
                        }
                    }
                } elseif (!isset($classlike_storage->declaring_method_ids[StrId::__get])) {
                    $has_variable_calls = $codebase->analyzer->hasMixedMemberName('$' . $property_name_str);

                    $issue = new UnusedProperty(
                        'Cannot find ' . ($has_variable_calls ? 'explicit' : 'any')
                            . ' references to private property ' . (string) $property_id
                            . ($has_variable_calls ? ' (but did find some potential references)' : ''),
                        $property_storage->location,
                        $property_id,
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
                                true,
                            );
                        }
                    } else {
                        IssueBuffer::maybeAdd(
                            $issue,
                            $classlike_storage->suppressed_issues + $property_storage->suppressed_issues,
                        );
                    }
                }
            }
        }
    }

    /**
     * @param int $fq_classlike_name_lc lowercase class name id
     * @psalm-external-mutation-free
     */
    public function registerMissingClassLike(int $fq_classlike_name_lc): void
    {
        $this->existing_classlikes_lc[$fq_classlike_name_lc] = false;
    }

    /**
     * @param int $fq_classlike_name_lc lowercase class name id
     * @psalm-mutation-free
     */
    public function isMissingClassLike(int $fq_classlike_name_lc): bool
    {
        return isset($this->existing_classlikes_lc[$fq_classlike_name_lc])
            && $this->existing_classlikes_lc[$fq_classlike_name_lc] === false;
    }

    /**
     * @param int $fq_classlike_name_lc lowercase class name id
     * @psalm-mutation-free
     */
    public function doesClassLikeExist(int $fq_classlike_name_lc): bool
    {
        return isset($this->existing_classlikes_lc[$fq_classlike_name_lc])
            && $this->existing_classlikes_lc[$fq_classlike_name_lc];
    }

    /**
     * @psalm-external-mutation-free
     */
    public function forgetMissingClassLikes(): void
    {
        $this->existing_classlikes_lc = array_filter($this->existing_classlikes_lc);
    }

    /**
     * @psalm-external-mutation-free
     */
    public function removeClassLike(int $fq_class_name): void
    {
        $fq_class_name_lc = Interner::lower($fq_class_name);

        unset(
            $this->existing_classlikes_lc[$fq_class_name_lc],
            $this->existing_traits_lc[$fq_class_name_lc],
            $this->existing_traits[$fq_class_name],
            $this->existing_enums_lc[$fq_class_name_lc],
            $this->existing_enums[$fq_class_name],
            $this->existing_interfaces_lc[$fq_class_name_lc],
            $this->existing_interfaces[$fq_class_name],
            $this->existing_classes_lc[$fq_class_name_lc],
            $this->existing_classes[$fq_class_name],
            $this->trait_nodes[$fq_class_name_lc],
        );

        $this->scanner->removeClassLike($fq_class_name_lc);
    }

    /**
     * @return array{
     *     array<int, bool>,
     *     array<int, bool>,
     *     array<int, bool>,
     *     array<int, bool>,
     *     array<int, bool>,
     *     array<int, bool>,
     *     array<int, bool>,
     *     array<int, bool>,
     *     array<int, bool>,
     * }
     * @psalm-mutation-free
     */
    public function getThreadData(): array
    {
        return [
            $this->existing_classlikes_lc,
            $this->existing_classes_lc,
            $this->existing_traits_lc,
            $this->existing_traits,
            $this->existing_enums_lc,
            $this->existing_enums,
            $this->existing_interfaces_lc,
            $this->existing_interfaces,
            $this->existing_classes,
        ];
    }

    /**
     * @param array{
     *     0: array<int, bool>,
     *     1: array<int, bool>,
     *     2: array<int, bool>,
     *     3: array<int, bool>,
     *     4: array<int, bool>,
     *     5: array<int, bool>,
     *     6: array<int, bool>,
     *     7: array<int, bool>,
     *     8: array<int, bool>,
     * } $thread_data
     * @psalm-external-mutation-free
     */
    public function addThreadData(array $thread_data): void
    {
        [
            $existing_classlikes_lc,
            $existing_classes_lc,
            $existing_traits_lc,
            $existing_traits,
            $existing_enums_lc,
            $existing_enums,
            $existing_interfaces_lc,
            $existing_interfaces,
            $existing_classes,
        ] = $thread_data;

        $this->existing_classlikes_lc = self::mergeThreadData($existing_classlikes_lc, $this->existing_classlikes_lc);
        $this->existing_classes_lc = self::mergeThreadData($existing_classes_lc, $this->existing_classes_lc);
        $this->existing_traits_lc = self::mergeThreadData($existing_traits_lc, $this->existing_traits_lc);
        $this->existing_traits = self::mergeThreadData($existing_traits, $this->existing_traits);
        $this->existing_enums_lc = self::mergeThreadData($existing_enums_lc, $this->existing_enums_lc);
        $this->existing_enums = self::mergeThreadData($existing_enums, $this->existing_enums);
        $this->existing_interfaces_lc = self::mergeThreadData($existing_interfaces_lc, $this->existing_interfaces_lc);
        $this->existing_interfaces = self::mergeThreadData($existing_interfaces, $this->existing_interfaces);
        $this->existing_classes = self::mergeThreadData($existing_classes, $this->existing_classes);
    }

    /**
     * @param array<int, bool> $old
     * @param array<int, bool> $new
     * @return array<int, bool>
     * @psalm-pure
     */
    private static function mergeThreadData(array $old, array $new): array
    {
        foreach ($new as $name => $value) {
            if (!isset($old[$name]) || (!$old[$name] && $value)) {
                $old[$name] = $value;
            }
        }
        return $old;
    }

    /**
     * @psalm-mutation-free
     */
    public function getStorageFor(int $fq_class_name): ?ClassLikeStorage
    {
        $fq_class_name = $this->getUnAliasedName($fq_class_name);

        try {
            return $this->classlike_storage_provider->get($fq_class_name);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function getConstantType(
        ClassLikeStorage $class_like_storage,
        int $constant_name,
        int $visibility,
        ?StatementsAnalyzer $statements_analyzer,
        array $visited_constant_ids,
        bool $late_static_binding,
    ): ?Union {
        $constant_resolver = new StorageByPatternResolver();
        $resolved_constants = $constant_resolver->resolveConstants(
            $class_like_storage,
            $constant_name,
        );

        $filtered_constants_by_visibility = array_filter(
            $resolved_constants,
            fn(ClassConstantStorage $resolved_constant) => $this->filterConstantNameByVisibility(
                $resolved_constant,
                $visibility,
            ),
        );

        if ($filtered_constants_by_visibility === []) {
            return null;
        }

        $new_atomic_types = [];

        foreach ($filtered_constants_by_visibility as $filtered_constant_name => $constant_storage) {
            if (!isset($class_like_storage->constants[$filtered_constant_name])) {
                continue;
            }

            if ($constant_storage->unresolved_node) {
                /** @psalm-suppress InaccessibleProperty Lazy resolution */
                $constant_storage->inferred_type = new Union([ConstantTypeResolver::resolve(
                    $this,
                    $constant_storage->unresolved_node,
                    $statements_analyzer,
                    $visited_constant_ids,
                )]);

                if ($constant_storage->type === null || !$constant_storage->type->from_docblock) {
                    /** @psalm-suppress InaccessibleProperty Lazy resolution */
                    $constant_storage->type = $constant_storage->inferred_type;
                }
            }

            $constant_type = $late_static_binding
                ? $constant_storage->type
                : ($constant_storage->inferred_type ?? null);

            if ($constant_type === null) {
                continue;
            }

            $new_atomic_types[] = $constant_type->getAtomicTypes();
        }

        if ($new_atomic_types === []) {
            return null;
        }

        return new Union(array_merge([], ...$new_atomic_types));
    }

    /**
     * @psalm-mutation-free
     */
    private function getEnumType(
        ClassLikeStorage $class_like_storage,
        int $constant_name,
    ): ?Union {
        $constant_resolver = new StorageByPatternResolver();
        $resolved_enums = $constant_resolver->resolveEnums(
            $class_like_storage,
            $constant_name,
        );

        if ($resolved_enums === []) {
            return null;
        }

        $types = [];
        foreach ($resolved_enums as $enum_case_name => $_) {
            $types[$enum_case_name] = new TEnumCase($class_like_storage->name, $enum_case_name);
        }

        return new Union($types);
    }

    /**
     * @psalm-pure
     */
    private function filterConstantNameByVisibility(
        ClassConstantStorage $constant_storage,
        int $visibility,
    ): bool {

        if ($visibility === ReflectionProperty::IS_PUBLIC
            && $constant_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PUBLIC
        ) {
            return false;
        }

        if ($visibility === ReflectionProperty::IS_PROTECTED
            && $constant_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PUBLIC
            && $constant_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PROTECTED
        ) {
            return false;
        }

        return true;
    }
}
