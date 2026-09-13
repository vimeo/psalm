<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Closure;
use LogicException;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;

use function array_intersect_key;
use function array_pop;
use function md5;
use function str_contains;
use function strpos;
use function strtolower;
use function substr;

/**
 * A directed graph of "uses" between code elements (classes, function-likes,
 * properties, class constants), used for dead code detection, for reference
 * lookups (language server, `--find-references-to`) and for invalidating
 * cached analysis results in `--diff` mode.
 *
 * Every node is identified by a string of the form `<kind> <member>`.
 * An edge `A -> B` means "if A is alive, B is used by A".
 *
 * Usage is resolved by a reachability search from a set of root nodes
 * (the public API, top-level file code, free functions, which psalm never
 * reports as unused, and code outside of the project), so code that is only
 * referenced from other unused code (including cycles of otherwise
 * unreferenced code) is correctly reported as unused.
 *
 * @psalm-import-type MutationInfo from MutationLevelResolver
 * @internal
 */
final class CodeUseGraph
{
    public const PUBLIC_API = 'public-api';

    /** A regular use of the target: the target is used if the source is alive. */
    public const EDGE_USE = 'use';

    /**
     * A write to a property: the property is not considered used (only
     * reads make a property used), but the edge is tracked for reference
     * lookups and cache invalidation.
     */
    public const EDGE_WRITE = 'write';

    /** From the "return value" node of a function-like to the function-like itself. */
    public const EDGE_RETURN = 'return';

    /** From a method to the class declaring it. */
    public const EDGE_METHOD = 'method';

    /** From the public API root node to a node. */
    public const EDGE_PUBLIC_API = 'public-api';

    /**
     * From an overridden parent or interface method (or its return value) to
     * the overriding method (or its return value): a call to the parent method
     * may end up in the overriding one, but only if the overriding class is
     * itself used, so these edges are only followed once the class of the
     * target is used.
     */
    public const EDGE_OVERRIDE = 'override';

    /**
     * Edge types derived from the class storages rather than from the analysed
     * code: recomputed on every run instead of being cached.
     */
    public const STRUCTURAL_EDGES = [
        self::EDGE_PUBLIC_API => true,
        self::EDGE_OVERRIDE => true,
        self::EDGE_METHOD => true,
    ];

    private const KIND_CLASS = 'class';
    private const KIND_FUNCTION_LIKE = 'func';
    private const KIND_RETURN = 'return';
    private const KIND_PROPERTY = 'property';
    private const KIND_CONSTANT = 'const';
    private const KIND_MISSING_METHOD = 'missing-method';
    private const KIND_MISSING_PROPERTY = 'missing-property';
    private const KIND_USE_ALIAS = 'use-alias';
    private const KIND_FILE = 'file';

    /**
     * Node kinds whose member id (the part after the kind) is a class member id
     * in the same format used by the statement differ (`class::method`,
     * `class::$property`, `class::CONSTANT`, `use:Alias:filehash`).
     */
    private const MEMBER_KINDS = [
        self::KIND_FUNCTION_LIKE => true,
        self::KIND_RETURN => true,
        self::KIND_PROPERTY => true,
        self::KIND_CONSTANT => true,
        self::KIND_MISSING_METHOD => true,
        self::KIND_MISSING_PROPERTY => true,
        self::KIND_USE_ALIAS => true,
    ];

    /**
     * Forward edges: source node id => target node id => edge type
     *
     * @var array<string, array<string, string>>
     */
    private array $forward_edges = [];

    /**
     * Backward edges: target node id => source node id => edge type
     *
     * @var array<string, array<string, string>>
     */
    private array $backward_edges = [];

    /**
     * The file each source node was seen in, used to compute file-level
     * references in `--diff` mode and to prune stale edges.
     *
     * @var array<string, string>
     */
    private array $node_files = [];

    /**
     * Node id => location hash => location of the references to the node.
     * Only populated when $collect_locations is true.
     *
     * @var array<string, array<string, CodeLocation>>
     */
    private array $locations = [];

    /**
     * Source node => target node => location hash => true. This lets references
     * and their locations be removed together when a source is invalidated.
     *
     * @var array<string, array<string, array<string, true>>>
     */
    private array $source_locations = [];

    /**
     * Target node => location hash => source node => true. A source location
     * can be shared by trait analyses, so it is retained until every source
     * that recorded it has been invalidated.
     *
     * @var array<string, array<string, array<string, true>>>
     */
    private array $location_sources = [];

    /**
     * Set of used node ids, null until resolved.
     *
     * @var array<string, true>|null
     */
    private ?array $used = null;

    /**
     * Cached reverse index of $node_files (file => node ids), rebuilt on demand.
     *
     * @var array<string, array<string, true>>|null
     */
    private ?array $file_nodes = null;

    /**
     * Cached index of the nodes referencing any member of a class
     * (lowercase class name => node ids), rebuilt on demand.
     *
     * @var array<lowercase-string, array<string, true>>|null
     */
    private ?array $class_referencing_nodes = null;

    /**
     * The mutations performed by each analysed function-like and the
     * unannotated function-likes it calls, resolved by MutationLevelResolver.
     *
     * @var array<string, MutationInfo>
     */
    private array $mutation_info = [];

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public bool $collect_locations = false,
    ) {
    }

    // Node ids

    /**
     * @param lowercase-string $fq_class_name_lc
     * @psalm-pure
     */
    public static function classNode(string $fq_class_name_lc): string
    {
        return self::KIND_CLASS . ' ' . $fq_class_name_lc;
    }

    /**
     * @param lowercase-string $function_id_lc a method id (`class::method`) or a function id
     * @psalm-pure
     */
    public static function functionLikeNode(string $function_id_lc): string
    {
        return self::KIND_FUNCTION_LIKE . ' ' . $function_id_lc;
    }

    /**
     * @param lowercase-string $function_id_lc
     * @psalm-pure
     */
    public static function functionLikeReturnNode(string $function_id_lc): string
    {
        return self::KIND_RETURN . ' ' . $function_id_lc;
    }

    /**
     * @param lowercase-string $fq_class_name_lc
     * @param string $property_name the property name, without the leading `$`
     * @psalm-pure
     */
    public static function propertyNode(string $fq_class_name_lc, string $property_name): string
    {
        return self::KIND_PROPERTY . ' ' . $fq_class_name_lc . '::$' . $property_name;
    }

    /**
     * @param lowercase-string $fq_class_name_lc
     * @psalm-pure
     */
    public static function classConstantNode(string $fq_class_name_lc, string $const_name): string
    {
        return self::KIND_CONSTANT . ' ' . $fq_class_name_lc . '::' . $const_name;
    }

    /**
     * @param lowercase-string $method_id_lc
     * @psalm-pure
     */
    public static function missingMethodNode(string $method_id_lc): string
    {
        return self::KIND_MISSING_METHOD . ' ' . $method_id_lc;
    }

    /**
     * @param lowercase-string $fq_class_name_lc
     * @param string $property_name the property name, without the leading `$`
     * @psalm-pure
     */
    public static function missingPropertyNode(string $fq_class_name_lc, string $property_name): string
    {
        return self::KIND_MISSING_PROPERTY . ' ' . $fq_class_name_lc . '::$' . $property_name;
    }

    /**
     * The function-like node of a method or named function, from its storage.
     * Closures have no name: their node is derived from their closure id.
     *
     * @psalm-mutation-free
     */
    public static function functionLikeNodeForStorage(FunctionLikeStorage $storage): ?string
    {
        if ($storage instanceof MethodStorage) {
            if ($storage->defining_fqcln === null || $storage->cased_name === null) {
                return null;
            }

            return self::functionLikeNode(strtolower($storage->defining_fqcln . '::' . $storage->cased_name));
        }

        if ($storage->cased_name === null) {
            return null;
        }

        return self::functionLikeNode(strtolower($storage->cased_name));
    }

    /**
     * A node representing a `use` import alias in a given file: methods
     * referencing the alias get invalidated when the import changes.
     *
     * @psalm-pure
     */
    public static function useAliasNode(string $alias, string $file_path): string
    {
        // do NOT change this to hash, it will fail on Windows for whatever reason
        return self::KIND_USE_ALIAS . ' use:' . $alias . ':' . md5($file_path);
    }

    /**
     * A node representing the top-level code of a file.
     *
     * @psalm-pure
     */
    public static function fileNode(string $file_path): string
    {
        return self::KIND_FILE . ' ' . $file_path;
    }

    /**
     * @psalm-pure
     */
    private static function getKind(string $node_id): string
    {
        $pos = strpos($node_id, ' ');

        return $pos === false ? $node_id : substr($node_id, 0, $pos);
    }

    /**
     * Returns the class member id (`class::member`) for nodes representing class
     * members, in the same format used by the statement differ, or null.
     *
     * @psalm-pure
     */
    public static function getMemberId(string $node_id): ?string
    {
        $pos = strpos($node_id, ' ');

        if ($pos === false) {
            return null;
        }

        if (!isset(self::MEMBER_KINDS[substr($node_id, 0, $pos)])) {
            return null;
        }

        return substr($node_id, $pos + 1);
    }

    /**
     * Returns the lowercase name of the class a node belongs to, or null for
     * nodes that don't belong to a class (files, free functions, roots).
     *
     * @return lowercase-string|null
     * @psalm-pure
     */
    public static function getOwnerClass(string $node_id): ?string
    {
        $pos = strpos($node_id, ' ');

        if ($pos === false) {
            return null;
        }

        $kind = substr($node_id, 0, $pos);
        $member = substr($node_id, $pos + 1);

        if ($kind === self::KIND_CLASS) {
            /** @var lowercase-string */
            return $member;
        }

        if ($kind === self::KIND_FILE || $kind === self::KIND_USE_ALIAS || !isset(self::MEMBER_KINDS[$kind])) {
            return null;
        }

        $separator = strpos($member, '::');

        if ($separator === false) {
            return null;
        }

        /** @var lowercase-string */
        return substr($member, 0, $separator);
    }

    /**
     * Whether a node is a root of the usage search: a root is always alive.
     *
     * @param Closure(string): bool $is_external whether a node belongs to code outside of the project
     */
    private static function isRoot(string $node_id, Closure $is_external): bool
    {
        if ($node_id === self::PUBLIC_API) {
            return true;
        }

        $kind = self::getKind($node_id);

        if ($kind === self::KIND_FILE) {
            return true;
        }

        // Psalm never reports unused free functions, so they're entry points
        if ($kind === self::KIND_FUNCTION_LIKE && !str_contains($node_id, '::')) {
            return true;
        }

        return $is_external($node_id);
    }

    // Building

    /**
     * Records that $source_node uses $target_node.
     *
     * @psalm-external-mutation-free
     */
    public function addEdge(string $source_node, string $target_node, string $type = self::EDGE_USE): void
    {
        if ($source_node === $target_node) {
            return;
        }

        if (isset($this->forward_edges[$source_node][$target_node])) {
            $existing_type = $this->forward_edges[$source_node][$target_node];

            // a write never downgrades a read
            if ($existing_type === $type || $type === self::EDGE_WRITE) {
                return;
            }
        }

        $this->forward_edges[$source_node][$target_node] = $type;
        $this->backward_edges[$target_node][$source_node] = $type;
        $this->used = null;
        $this->class_referencing_nodes = null;
    }

    /**
     * Records a reference to $target_node from the code element described by
     * $context (the calling method or function, or the class for class-level
     * code), falling back to the top-level code of the file of $location
     * (or $file_path).
     *
     * Nothing is recorded if neither a usable context nor a file is known.
     *
     * @psalm-external-mutation-free
     */
    public function addReference(
        string $target_node,
        ?Context $context,
        ?CodeLocation $location = null,
        string $type = self::EDGE_USE,
        ?string $file_path = null,
    ): void {
        $file_path = $location?->file_path ?? $file_path;

        $calling_method_id = $context?->calling_method_id;
        $calling_function_id = $context?->calling_function_id;
        $self = $context?->self;

        if ($calling_method_id !== null) {
            $source_node = self::functionLikeNode($calling_method_id);
        } elseif ($calling_function_id !== null) {
            $source_node = self::functionLikeNode($calling_function_id);
        } elseif ($self !== null) {
            $source_node = self::classNode(strtolower($self));
        } elseif ($file_path !== null) {
            $source_node = self::fileNode($file_path);
        } else {
            return;
        }

        $this->addEdge($source_node, $target_node, $type);

        if ($file_path !== null && !isset($this->node_files[$source_node])) {
            $this->node_files[$source_node] = $file_path;
            $this->file_nodes = null;
        }

        if ($location !== null && $this->collect_locations) {
            $location_hash = $location->getHash();
            $this->locations[$target_node][$location_hash] = $location;
            $this->source_locations[$source_node][$target_node][$location_hash] = true;
            $this->location_sources[$target_node][$location_hash][$source_node] = true;
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function markAsPublicApi(string $node_id): void
    {
        $this->addEdge(self::PUBLIC_API, $node_id, self::EDGE_PUBLIC_API);
    }

    /**
     * Merges another graph (e.g. from a worker process) into this one.
     *
     * @psalm-external-mutation-free
     */
    public function addGraph(self $other): void
    {
        foreach ($other->forward_edges as $source_node => $targets) {
            foreach ($targets as $target_node => $type) {
                $this->addEdge($source_node, $target_node, $type);
            }
        }

        foreach ($other->node_files as $node_id => $file_path) {
            if (!isset($this->node_files[$node_id])) {
                $this->node_files[$node_id] = $file_path;
                $this->file_nodes = null;
            }
        }

        foreach ($other->source_locations as $source_node => $targets) {
            foreach ($targets as $target_node => $location_hashes) {
                foreach ($location_hashes as $location_hash => $_) {
                    $location = $other->locations[$target_node][$location_hash] ?? null;

                    if ($location === null) {
                        continue;
                    }

                    $this->locations[$target_node][$location_hash] = $location;
                    $this->source_locations[$source_node][$target_node][$location_hash] = true;
                    $this->location_sources[$target_node][$location_hash][$source_node] = true;
                }
            }
        }

        $this->mutation_info = $other->mutation_info + $this->mutation_info;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function clear(): void
    {
        $this->forward_edges = [];
        $this->backward_edges = [];
        $this->node_files = [];
        $this->locations = [];
        $this->source_locations = [];
        $this->location_sources = [];
        $this->mutation_info = [];
        $this->used = null;
        $this->file_nodes = null;
        $this->class_referencing_nodes = null;
    }

    // Mutation levels

    /**
     * Records the mutations performed by an analysed function-like.
     *
     * @param MutationInfo $info
     * @psalm-external-mutation-free
     */
    public function addMutationInfo(string $node_id, array $info): void
    {
        $this->mutation_info[$node_id] = $info;
    }

    /**
     * @return array<string, MutationInfo>
     * @psalm-mutation-free
     */
    public function getMutationInfo(): array
    {
        return $this->mutation_info;
    }

    /**
     * Marks the mutation info of a function-like as coming from a previous
     * run, so that its issues are not reported again.
     *
     * @psalm-external-mutation-free
     */
    public function markMutationInfoStale(string $node_id): void
    {
        if (isset($this->mutation_info[$node_id])) {
            $this->mutation_info[$node_id]['fresh'] = false;
        }
    }

    // Resolution

    /**
     * Computes the set of used nodes: everything reachable from a root node
     * through non-write edges. Iterative, so cycles and deep graphs are fine.
     *
     * Must be called before isUsed(), and again after the graph changes.
     *
     * @param Closure(string): bool $is_external whether a node (with outgoing
     *        edges) belongs to code outside of the project, e.g. a vendor class
     *        or a caller made up by a plugin: such code is never reported as
     *        unused, so what it references is used.
     */
    public function resolve(Closure $is_external): void
    {
        $used = [self::PUBLIC_API => true];
        $queue = [self::PUBLIC_API];

        foreach ($this->forward_edges as $node_id => $_) {
            if (!isset($used[$node_id]) && self::isRoot($node_id, $is_external)) {
                $used[$node_id] = true;
                $queue[] = $node_id;
            }
        }

        /**
         * Override edges whose target's class is not used yet, by class node
         *
         * @var array<string, list<string>>
         */
        $deferred = [];

        while ($queue) {
            $node_id = array_pop($queue);

            foreach ($this->forward_edges[$node_id] ?? [] as $target_node => $type) {
                if ($type === self::EDGE_WRITE || isset($used[$target_node])) {
                    continue;
                }

                if ($type === self::EDGE_OVERRIDE) {
                    $owner_class = self::getOwnerClass($target_node);

                    if ($owner_class !== null) {
                        $owner_node = self::classNode($owner_class);

                        if (!isset($used[$owner_node])) {
                            $deferred[$owner_node][] = $target_node;
                            continue;
                        }
                    }
                }

                $used[$target_node] = true;
                $queue[] = $target_node;

                if (isset($deferred[$target_node])) {
                    foreach ($deferred[$target_node] as $deferred_node) {
                        if (!isset($used[$deferred_node])) {
                            $used[$deferred_node] = true;
                            $queue[] = $deferred_node;
                        }
                    }

                    unset($deferred[$target_node]);
                }
            }
        }

        $this->used = $used;
    }

    /**
     * @psalm-mutation-free
     */
    public function isUsed(string $node_id): bool
    {
        if ($this->used === null) {
            throw new LogicException('The graph must be resolved before checking usage');
        }

        return isset($this->used[$node_id]);
    }

    /**
     * Whether a node is referenced by other used code, as opposed to being
     * merely alive as an entry point or through its own members.
     *
     * @psalm-mutation-free
     */
    public function isReferenced(string $node_id): bool
    {
        if ($this->used === null) {
            throw new LogicException('The graph must be resolved before checking usage');
        }

        foreach ($this->backward_edges[$node_id] ?? [] as $source_node => $type) {
            if (($type === self::EDGE_USE || $type === self::EDGE_WRITE)
                && isset($this->used[$source_node])
                && self::getOwnerClass($source_node) !== self::getOwnerClass($node_id)
            ) {
                return true;
            }
        }

        return false;
    }

    // Queries

    /**
     * Returns the nodes referencing $node_id, optionally only through edges
     * of the given type.
     *
     * @return array<string, true>
     * @psalm-mutation-free
     */
    public function getReferencingNodes(string $node_id, ?string $type = null): array
    {
        $references = [];

        foreach ($this->backward_edges[$node_id] ?? [] as $source_node => $edge_type) {
            if ($type === null || $edge_type === $type) {
                $references[$source_node] = true;
            }
        }

        return $references;
    }

    /**
     * Returns only references made by code that is reachable from an entry point.
     *
     * @return array<string, true>
     * @psalm-mutation-free
     */
    public function getUsedReferencingNodes(string $node_id, ?string $type = null): array
    {
        if ($this->used === null) {
            throw new LogicException('The graph must be resolved before checking usage');
        }

        return array_intersect_key($this->getReferencingNodes($node_id, $type), $this->used);
    }

    /**
     * @return array<string, CodeLocation>
     * @psalm-mutation-free
     */
    public function getReferenceLocations(string $node_id): array
    {
        return $this->locations[$node_id] ?? [];
    }

    /**
     * Target node id => referencing node id => true
     *
     * @return array<string, array<string, true>>
     * @psalm-mutation-free
     */
    public function getAllReferences(): array
    {
        $result = [];

        foreach ($this->backward_edges as $target_node => $sources) {
            foreach ($sources as $source_node => $_) {
                $result[$target_node][$source_node] = true;
            }
        }

        return $result;
    }

    /**
     * Returns, for each referenced class member id (in the statement differ
     * format), the ids of the function-likes referencing it. Used to find the
     * methods to re-analyse when a member changes.
     *
     * @return array<string, array<string, true>>
     * @psalm-mutation-free
     */
    public function getFunctionLikeReferencesToMembers(): array
    {
        $result = [];

        foreach ($this->backward_edges as $target_node => $sources) {
            $member_id = self::getMemberId($target_node);

            if ($member_id === null) {
                continue;
            }

            foreach ($sources as $source_node => $type) {
                if (($type === self::EDGE_USE || $type === self::EDGE_WRITE)
                    && self::getKind($source_node) === self::KIND_FUNCTION_LIKE
                ) {
                    $result[$member_id][substr($source_node, 5)] = true;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the nodes referencing the given class or any of its members.
     * The file of a node is given by getNodeFile(); when unknown, it is the
     * file of the node's owner class, if any (see getOwnerClass()).
     *
     * @param lowercase-string $fq_class_name_lc
     * @return array<string, true>
     * @psalm-external-mutation-free
     */
    public function getNodesReferencingClass(string $fq_class_name_lc): array
    {
        if ($this->class_referencing_nodes === null) {
            $this->class_referencing_nodes = [];

            foreach ($this->backward_edges as $target_node => $sources) {
                $owner = self::getOwnerClass($target_node);

                if ($owner === null) {
                    continue;
                }

                foreach ($sources as $source_node => $_) {
                    $this->class_referencing_nodes[$owner][$source_node] = true;
                }
            }
        }

        return $this->class_referencing_nodes[$fq_class_name_lc] ?? [];
    }

    /**
     * Returns the file a node was seen in, if known.
     *
     * @psalm-mutation-free
     */
    public function getNodeFile(string $node_id): ?string
    {
        if (self::getKind($node_id) === self::KIND_FILE) {
            return substr($node_id, 5);
        }

        return $this->node_files[$node_id] ?? null;
    }

    // Invalidation (--diff mode)

    /**
     * Removes all the references made by a node, e.g. because the code it
     * represents is about to be re-analysed or was deleted.
     *
     * @psalm-external-mutation-free
     */
    public function removeReferencesFrom(string $node_id): void
    {
        foreach ($this->source_locations[$node_id] ?? [] as $target_node => $location_hashes) {
            foreach ($location_hashes as $location_hash => $_) {
                unset($this->location_sources[$target_node][$location_hash][$node_id]);

                if (!$this->location_sources[$target_node][$location_hash]) {
                    unset(
                        $this->location_sources[$target_node][$location_hash],
                        $this->locations[$target_node][$location_hash],
                    );
                }
            }

            if (!$this->location_sources[$target_node]) {
                unset($this->location_sources[$target_node], $this->locations[$target_node]);
            }
        }

        unset($this->source_locations[$node_id]);

        foreach ($this->forward_edges[$node_id] ?? [] as $target_node => $_) {
            unset($this->backward_edges[$target_node][$node_id]);

            if (!$this->backward_edges[$target_node]) {
                unset($this->backward_edges[$target_node]);
            }
        }

        unset(
            $this->forward_edges[$node_id],
            $this->node_files[$node_id],
            $this->mutation_info[$node_id],
        );
        $this->used = null;
        $this->file_nodes = null;
        $this->class_referencing_nodes = null;
    }

    /**
     * Removes all the edges of the given types.
     *
     * @param array<string, true> $types
     * @psalm-external-mutation-free
     */
    public function removeEdgesOfTypes(array $types): void
    {
        foreach ($this->forward_edges as $source_node => $targets) {
            foreach ($targets as $target_node => $type) {
                if (!isset($types[$type])) {
                    continue;
                }

                unset(
                    $this->forward_edges[$source_node][$target_node],
                    $this->backward_edges[$target_node][$source_node],
                );

                if (!$this->backward_edges[$target_node]) {
                    unset($this->backward_edges[$target_node]);
                }
            }

            if (!$this->forward_edges[$source_node]) {
                unset($this->forward_edges[$source_node]);
            }
        }

        $this->used = null;
        $this->class_referencing_nodes = null;
    }

    /**
     * Removes all the references made by the nodes seen in a file, except the
     * nodes in $keep_nodes.
     *
     * @param array<string, true> $keep_nodes
     * @psalm-external-mutation-free
     */
    public function removeReferencesFromFile(string $file_path, array $keep_nodes = []): void
    {
        $this->removeReferencesFrom(self::fileNode($file_path));

        if ($this->file_nodes === null) {
            $this->file_nodes = [];

            foreach ($this->node_files as $node_id => $node_file) {
                $this->file_nodes[$node_file][$node_id] = true;
            }
        }

        foreach ($this->file_nodes[$file_path] ?? [] as $node_id => $_) {
            if (!isset($keep_nodes[$node_id])) {
                $this->removeReferencesFrom($node_id);
            }
        }
    }

    // Caching

    /**
     * @return array{
     *     edges: array<string, array<string, string>>,
     *     node_files: array<string, string>,
     *     mutation_info: array<string, MutationInfo>
     * }
     * @psalm-mutation-free
     */
    public function getCacheData(): array
    {
        $edges = [];

        foreach ($this->forward_edges as $source_node => $targets) {
            foreach ($targets as $target_node => $type) {
                if (!isset(self::STRUCTURAL_EDGES[$type])) {
                    $edges[$source_node][$target_node] = $type;
                }
            }
        }

        $mutation_info = $this->mutation_info;

        foreach ($mutation_info as $node_id => $_) {
            $mutation_info[$node_id]['fresh'] = false;
        }

        return [
            'edges' => $edges,
            'node_files' => $this->node_files,
            'mutation_info' => $mutation_info,
        ];
    }

    /**
     * Merges cached data produced by getCacheData() into the graph.
     *
     * @param array{
     *     edges: array<string, array<string, string>>,
     *     node_files: array<string, string>,
     *     mutation_info?: array<string, MutationInfo>
     * } $data
     * @psalm-external-mutation-free
     */
    public function loadCacheData(array $data): void
    {
        foreach ($data['edges'] as $source_node => $targets) {
            foreach ($targets as $target_node => $type) {
                $this->addEdge($source_node, $target_node, $type);
            }
        }

        foreach ($data['node_files'] as $node_id => $file_path) {
            if (!isset($this->node_files[$node_id])) {
                $this->node_files[$node_id] = $file_path;
            }
        }

        $this->mutation_info += $data['mutation_info'] ?? [];

        $this->file_nodes = null;
    }
}
