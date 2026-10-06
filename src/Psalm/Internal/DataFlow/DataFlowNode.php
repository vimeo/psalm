<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

use InvalidArgumentException;
use Override;
use Psalm\CodeLocation;
use Psalm\Internal\Codebase\Methods;
use Psalm\Internal\MethodIdentifier;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Stringable;

use function count;
use function ltrim;
use function str_starts_with;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

/**
 * A node in the data-flow / taint graph.
 *
 * INVARIANT: a node's {@see self::$code_location} MUST be a pure function of its {@see self::$id}
 * -- every node created with a given id anywhere, in any (forked) analysis process, must carry the
 * same location. The forked-worker graphs are merged in a non-deterministic order, so if two
 * workers gave the same id two different locations the surviving one -- and therefore the location
 * a taint issue is reported at -- would depend on scheduling, and findings would differ between
 * otherwise identical runs.
 *
 * This is enforced structurally: the constructor is private, so a location can only reach a node
 * through one of the factories below, and each derives it deterministically from the node's
 * identity -- from the entity's {@see FunctionLikeStorage} (methods/functions), from a location
 * that is itself encoded into the id (assignments, taint sinks, specialized callables), or not at
 * all (null). Nodes produced while resolving the graph ({@see self::withSpecialization()},
 * {@see self::withFlow()}) copy the location from an existing node and can never introduce a new
 * one. There is therefore no code path -- internal or in a plugin -- that can attach a location a
 * caller chose independently of the id. Keep it that way: never add a factory that accepts a raw
 * CodeLocation which is not also folded into the id.
 *
 * @psalm-consistent-constructor
 * @internal
 * @psalm-external-mutation-free
 * @psalm-type CallableKind = 'builtin'|'inherited-method'|'magic-method'|'dynamic-function-call'|'dynamic-instantiation'|'callable-object'
 */
final class DataFlowNode implements Stringable
{
    /**
     * The prefix of the ids of the nodes of getForNarrowingToScalar()
     */
    private const NARROWED_TO_SCALAR = 'narrowed to a scalar: ';

    /**
     * @psalm-mutation-free
     */
    private function __construct(
        public readonly string $id,
        public readonly ?string $unspecialized_id,
        public readonly ?string $specialization_key,
        public readonly string $label,
        public readonly ?CodeLocation $code_location = null,
        public readonly int $taints = 0,
        public readonly ?self $taintSource = null,
        /** @var list<string> */
        public readonly array $path_types = [],
        /**
         * Taint resolution only: the specialized call entry (see TaintFlowGraph) whose
         * body the flow is currently in, or null outside of any specialized call.
         */
        public readonly ?int $context = null,
    ) {
    }

    /**
     * @psalm-mutation-free
     */
    private function __clone()
    {
    }

    /**
     * @psalm-pure
     */
    private static function make(
        string $id,
        string $label,
        ?CodeLocation $code_location,
        ?string $specialization_key = null,
        int $taints = 0,
    ): self {
        if ($specialization_key === null) {
            $unspecialized_id = null;
        } else {
            $unspecialized_id = $id;
            $id .= ' specialized in ' . $specialization_key;
        }
        return new self(
            $id,
            $unspecialized_id,
            $specialization_key,
            $label,
            $code_location,
            $taints,
        );
    }

    /**
     * The key identifying a call site among the specializations of a callee's taint nodes.
     *
     * @psalm-pure
     */
    public static function getSpecializationKey(CodeLocation $specialization_location): string
    {
        return strtolower($specialization_location->file_name) . ':' . $specialization_location->raw_file_start;
    }

    /**
     * @psalm-pure
     */
    public static function getForPropertyFetch(
        string $property_id,
        ?CodeLocation $specialization_location = null,
    ): self {
        $specialization_key = $specialization_location
            ? self::getSpecializationKey($specialization_location)
            : null;

        return self::make($property_id, $property_id, null, $specialization_key);
    }

    /**
     * The values a property gets through its class (as opposed to the node of the property, which also gets those
     * set through the subclasses), which the objects of its subclasses may have too.
     *
     * @psalm-pure
     */
    public static function getForInheritedProperty(string $property_id): self
    {
        return self::make($property_id . ' inherited', $property_id, null);
    }

    /**
     * Builds a node carrying a taint bitmask at a location. Whether it behaves as a
     * source or a sink depends on whether the caller passes it to
     * {@see TaintFlowGraph::addSource()} or {@see TaintFlowGraph::addSink()}.
     *
     * @psalm-pure
     */
    public static function getForTaint(
        string $taint_id,
        CodeLocation $code_location,
        int $taints,
    ): self {
        // A taint source/sink is identified by *where* it occurs, so its location doubles as the
        // specialization key and is thereby folded into the id: id -> location is a pure function
        // (see the class invariant). There is deliberately no independent location parameter -- a
        // caller cannot give the same id two different locations.
        $specialization_key = self::getSpecializationKey($code_location);

        return self::make($taint_id, $taint_id, $code_location, $specialization_key, $taints);
    }

    /**
     * @psalm-pure
     * @param CallableKind $kind
     *
     * Unlike {@see self::getForMethodArgument()}, a callable node has no {@see FunctionLikeStorage}
     * to derive a canonical location from (it stands for a builtin/magic/callable-object/dynamic
     * call). Its only well-defined location is therefore its specialization (the callsite), which is
     * already baked into the node id via $specialization_location. Passing an independent
     * $code_location here used to allow the *same* (unspecialized) node id to be created with a
     * different callsite location in each analysis process; whichever forked worker registered the
     * id first then won the merge non-deterministically, so taint findings shifted between runs. The
     * location is now always derived from $specialization_location, keeping id -> location a pure
     * function. If you have a real storage and want a definition location, use
     * {@see self::getForMethodArgument()} / {@see self::getForMethodReturn()} instead.
     */
    public static function getForCallableArg(
        string $kind,
        string $cased_function_id,
        int $argument_offset,
        ?CodeLocation $specialization_location = null,
        int $taints = 0,
    ): self {
        $arg_id = strtolower($cased_function_id) . '#' . ($argument_offset + 1);

        $label = $kind . ' ' . $cased_function_id . '#' . ($argument_offset + 1);

        $specialization_key = $specialization_location
            ? self::getSpecializationKey($specialization_location)
            : null;

        return self::make($arg_id, $label, $specialization_location, $specialization_key, $taints);
    }

    /**
     * @psalm-pure
     * @param CallableKind $kind
     *
     * See {@see self::getForCallableArg()} for why the node location is derived from
     * $specialization_location rather than accepted as an independent argument.
     */
    public static function getForCallableReturn(
        string $kind,
        string $cased_function_id,
        ?CodeLocation $specialization_location = null,
        int $taints = 0,
        ?string $specialization_key = null,
    ): self {
        if ($specialization_key === null && $specialization_location) {
            $specialization_key = self::getSpecializationKey($specialization_location);
        }

        return self::make(
            strtolower($cased_function_id),
            $kind . ' ' . $cased_function_id,
            $specialization_location,
            $specialization_key,
            $taints,
        );
    }

    /**
     * @psalm-mutation-free
     *
     * The argument node's sink taints are derived from the parameter's storage rather than passed by
     * the caller: the node id is shared across every call site, so a caller-supplied value made the
     * same id carry the parameter's sinks at one site and none at another, and which survived the
     * multi-process graph merge was non-deterministic. Deriving from storage keeps id -> taints a
     * pure function.
     */
    public static function getForMethodArgument(
        string $cased_method_id,
        int $argument_offset,
        FunctionLikeStorage $storage,
        ?CodeLocation $specialization_location = null,
    ): self {
        $arg_id = strtolower($cased_method_id) . '#' . ($argument_offset + 1);

        $label = $cased_method_id . '#' . ($argument_offset + 1);

        $specialization_key = $specialization_location
            ? self::getSpecializationKey($specialization_location)
            : null;

        $param = self::getParameter($storage, $argument_offset);

        return self::make(
            $arg_id,
            $label,
            $param?->signature_type_location ?: $param?->type_location ?: $param?->location,
            $specialization_key,
            $param?->sinks ?? 0,
        );
    }

    /**
     * The value a by-reference parameter is left with when the function-like returns: what the
     * variable passed to it holds after the call.
     *
     * @psalm-mutation-free
     */
    public static function getForMethodArgumentOut(
        string $cased_method_id,
        int $argument_offset,
        FunctionLikeStorage $storage,
        ?CodeLocation $specialization_location = null,
    ): self {
        $specialization_key = $specialization_location
            ? self::getSpecializationKey($specialization_location)
            : null;

        $param = self::getParameter($storage, $argument_offset);

        return self::make(
            strtolower($cased_method_id) . '#' . ($argument_offset + 1) . ' out',
            $cased_method_id . '#' . ($argument_offset + 1) . ' out',
            $param?->location,
            $specialization_key,
        );
    }

    /**
     * Like {@see self::getForMethodArgument()} but resolves the (declaring) method storage from the
     * cased method id itself, via $methods, instead of requiring the caller to hold it. Returns null
     * when the id does not resolve to a stored method (a callable object, or a magic method with no
     * backing storage), so the caller can fall back to {@see self::getForCallableArg()}.
     *
     * Centralises the id -> declaring-storage lookup so every site that mints a `Class::method#offset`
     * node derives the same location and sink taints for the same id -- see the class invariant. When
     * the matched $param is given, the node is keyed by its declared index in the resolved storage
     * (see {@see self::getParameterOffset()}), so a named argument keys the same way here as it does
     * on the storage-carrying path -- otherwise a reordered named argument would attach to the node
     * for whichever parameter happens to sit at the call offset.
     *
     * @psalm-mutation-free
     */
    public static function getForMethodArgumentById(
        Methods $methods,
        string $cased_method_id,
        int $argument_offset,
        ?CodeLocation $specialization_location = null,
        ?FunctionLikeParameter $param = null,
    ): ?self {
        $separator_pos = strpos($cased_method_id, '::');

        if ($separator_pos === false) {
            return null;
        }

        $method_id = new MethodIdentifier(
            strtolower(ltrim(substr($cased_method_id, 0, $separator_pos), '\\')),
            strtolower(substr($cased_method_id, $separator_pos + 2)),
        );

        try {
            $declaring_id = $methods->getDeclaringMethodId($method_id);
        } catch (InvalidArgumentException) {
            // not a class, e.g. `object::__invoke` for a callable object
            return null;
        }

        if ($declaring_id === null || !$methods->hasStorage($declaring_id)) {
            return null;
        }

        $storage = $methods->getStorage($declaring_id);

        return self::getForMethodArgument(
            $cased_method_id,
            $param === null ? $argument_offset : self::getParameterOffset($storage, $param, $argument_offset),
            $storage,
            $specialization_location,
        );
    }

    /**
     * The declared parameter index that identifies $param within $storage. Argument nodes are keyed
     * by this rather than by the parameter's position in a given call, so a named argument resolves
     * to the same node as the equivalent positional one -- and as the method body's own parameter
     * node (see {@see FunctionLikeAnalyzer}). For a positional call this is already the call offset,
     * so nothing changes. Falls back to $fallback (the call offset) for a variadic parameter --
     * matching the per-call-position nodes the flow path creates -- and when $param cannot be
     * located, keeping id -> parameter deterministic (see the class invariant).
     *
     * @psalm-mutation-free
     */
    public static function getParameterOffset(
        FunctionLikeStorage $storage,
        FunctionLikeParameter $param,
        int $fallback,
    ): int {
        if ($param->is_variadic) {
            return $fallback;
        }

        foreach ($storage->params as $i => $candidate) {
            if ($candidate->name === $param->name) {
                return $i;
            }
        }

        return $fallback;
    }

    /**
     * The value of $node narrowed to a type that cannot carry taints, e.g. a literal string.
     *
     * Data flows from $node to it, but no taint does, so that the narrowed value takes none: see
     * Reconciler. It stands for $node when types compare their parent nodes (see
     * getNarrowedNodeId()).
     *
     * @psalm-pure
     */
    public static function getForNarrowingToScalar(self $node): self
    {
        // $node's location is a function of its id, so of this id too: see the class invariant
        return self::make(self::NARROWED_TO_SCALAR . $node->id, $node->label, $node->code_location);
    }

    /**
     * The id of the node this one narrows if it is one of getForNarrowingToScalar(), else null.
     *
     * @psalm-mutation-free
     */
    public function getNarrowedNodeId(): ?string
    {
        return str_starts_with($this->id, self::NARROWED_TO_SCALAR)
            ? substr($this->id, strlen(self::NARROWED_TO_SCALAR))
            : null;
    }

    /**
     * The union of $parent_nodes and $other_parent_nodes, without the nodes narrowing others in
     * it (see getForNarrowingToScalar()): those only stand for the nodes they narrow.
     *
     * @param array<string, self> $parent_nodes
     * @param array<string, self> $other_parent_nodes
     * @return array<string, self>
     * @psalm-pure
     */
    public static function combineParentNodes(array $parent_nodes, array $other_parent_nodes): array
    {
        if (!$parent_nodes || !$other_parent_nodes) {
            return $parent_nodes + $other_parent_nodes;
        }

        $parent_nodes += $other_parent_nodes;

        foreach ($parent_nodes as $parent_node_id => $parent_node) {
            $narrowed_node_id = $parent_node->getNarrowedNodeId();

            if ($narrowed_node_id !== null && isset($parent_nodes[$narrowed_node_id])) {
                unset($parent_nodes[$parent_node_id]);
            }
        }

        return $parent_nodes;
    }

    /**
     * @psalm-pure
     */
    public static function getForAssignment(
        string $var_id,
        CodeLocation $assignment_location,
        ?string $specialization_key = null,
    ): self {
        // The assignment location is folded into the id, so id -> location is a pure function (see
        // the class invariant): two assignments at the same location get the same id, and nodes at
        // different locations get different ids. This is the only sanctioned way to attach a
        // location that is not derived from a FunctionLikeStorage.
        $id = $var_id . ' from ' . strtolower($assignment_location->file_name)
            . ':' . $assignment_location->raw_file_start . '-' . $assignment_location->raw_file_end;

        return self::make($id, $var_id, $assignment_location, $specialization_key);
    }

    /**
     * @psalm-mutation-free
     */
    public static function getForMethodReturn(
        string $cased_method_id,
        FunctionLikeStorage $storage,
        ?CodeLocation $specialization_location = null,
        int $taints = 0,
        ?string $specialization_key = null,
    ): self {
        if ($specialization_key === null && $specialization_location) {
            $specialization_key = self::getSpecializationKey($specialization_location);
        }

        return self::make(
            strtolower($cased_method_id),
            $cased_method_id,
            self::getReturnLocation($storage),
            $specialization_key,
            $taints,
        );
    }

    /**
     * @psalm-mutation-free
     */
    private static function getReturnLocation(FunctionLikeStorage $storage): ?CodeLocation
    {
        $loc = $storage->return_type_location
            ?: $storage->signature_return_type_location
            ?: $storage->location;

        return $loc;
    }

    /**
     * @psalm-mutation-free
     */
    private static function getParameter(FunctionLikeStorage $storage, int $argument_offset): ?FunctionLikeParameter
    {
        $param = $storage->params[$argument_offset] ?? null;

        if (!$param && $storage->params) {
            $last_param = $storage->params[count($storage->params) - 1];
            $param = $last_param->is_variadic ? $last_param : null;
        }

        return $param;
    }


    private static self $forVariableUse;
    /**
     * @psalm-external-mutation-free
     */
    public static function getForVariableUse(): self
    {
        return self::$forVariableUse ??= new self('variable-use', null, null, 'variable use');
    }


    private static self $forUnknownOrigin;
    /**
     * @psalm-external-mutation-free
     */
    public static function getForUnknownOrigin(): self
    {
        return self::$forUnknownOrigin ??= new self('unknown-origin', null, null, 'unknown origin');
    }

    private static self $forClosureUse;
    /**
     * @psalm-external-mutation-free
     */
    public static function getForClosureUse(): self
    {
        return self::$forClosureUse ??= new self('closure-use', null, null, 'closure use');
    }

    /**
     * @psalm-mutation-free
     */
    public function setTaints(int $taints): self
    {
        if ($this->taints === $taints) {
            return $this;
        }
        return new self(
            $this->id,
            $this->unspecialized_id,
            $this->specialization_key,
            $this->label,
            $this->code_location,
            $taints,
            $this->taintSource,
            $this->path_types,
            $this->context,
        );
    }

    /**
     * Re-key this node under a different (un)specialization while carrying over its identity-derived
     * location, label and flow state unchanged. Used by the taint resolver when it de-specializes or
     * re-specializes a node it already holds. The location is copied from $this, so it can never
     * diverge from the id -- see the class invariant.
     *
     * @psalm-mutation-free
     */
    public function withSpecialization(
        string $id,
        ?string $unspecialized_id,
        ?string $specialization_key,
        ?int $context,
    ): self {
        return new self(
            $id,
            $unspecialized_id,
            $specialization_key,
            $this->label,
            $this->code_location,
            $this->taints,
            $this->taintSource,
            $this->path_types,
            $context,
        );
    }

    /**
     * Produce the successor reached when taint flows out of this node along an edge: the same
     * identity, label and location, with updated flow state (taints, provenance and path types).
     * The location is copied from $this, so it can never diverge from the id -- see the class
     * invariant.
     *
     * @param list<string> $path_types
     * @psalm-mutation-free
     */
    public function withFlow(
        int $taints,
        self $taintSource,
        array $path_types,
        ?int $context,
    ): self {
        return new self(
            $this->id,
            $this->unspecialized_id,
            $this->specialization_key,
            $this->label,
            $this->code_location,
            $taints,
            $taintSource,
            $path_types,
            $context,
        );
    }

    /**
     * A node identified only by its id, with no location and no taint state. Used by the
     * variable-use graph, whose nodes are never taint-reporting sites; a null location trivially
     * satisfies the id -> location invariant.
     *
     * @param list<string> $path_types
     * @psalm-pure
     */
    public static function getForVariableUseDestination(string $id, array $path_types = []): self
    {
        return new self($id, null, null, $id, null, 0, null, $path_types);
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function __toString(): string
    {
        return $this->id;
    }
}
