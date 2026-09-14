<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

use Override;
use Psalm\CodeLocation;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Stringable;

use function count;
use function strtolower;

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
         * @var array<string, array<string, string>>
         */
        public readonly array $specialized_calls = [],
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
     * Create a node whose location is folded into its id, so id -> location is a pure function --
     * the only sanctioned way to attach a location that is not derived from a FunctionLikeStorage.
     * Two nodes built from the same location get the same id and location; nodes at different
     * locations get different ids. This is why {@see self::getForAssignment()} and
     * {@see self::getForTaintSink()} may take a raw CodeLocation: it is consumed into the identity
     * here, never stored decoupled from it. See the class invariant.
     *
     * @psalm-pure
     */
    private static function makeLocatedById(
        string $id_prefix,
        string $id_separator,
        string $label,
        CodeLocation $location,
        ?string $specialization_key = null,
        int $taints = 0,
    ): self {
        $id = $id_prefix . $id_separator . strtolower($location->file_name)
            . ':' . $location->raw_file_start . '-' . $location->raw_file_end;

        return self::make($id, $label, $location, $specialization_key, $taints);
    }

    /**
     * @psalm-pure
     */
    public static function getForPropertyFetch(
        string $property_id,
        ?CodeLocation $specialization_location = null,
    ): self {
        $specialization_key = $specialization_location
            ? strtolower($specialization_location->file_name) . ':' . $specialization_location->raw_file_start
            : null;

        return self::make($property_id, $property_id, null, $specialization_key);
    }

    /**
     * @psalm-pure
     */
    public static function getForTaintSink(
        string $taint_id,
        CodeLocation $code_location,
        int $taints,
    ): self {
        // A taint source/sink is identified by *where* it occurs, so its location doubles as the
        // specialization key and is thereby folded into the id: id -> location is a pure function
        // (see the class invariant). There is deliberately no independent location parameter -- a
        // caller cannot give the same id two different locations.
        $specialization_key = strtolower($code_location->file_name) . ':' . $code_location->raw_file_start;

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

        $specialization_key = null;

        if ($specialization_location) {
            $specialization_key = strtolower($specialization_location->file_name)
                . ':' . $specialization_location->raw_file_start;
        }

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
            $specialization_key = strtolower($specialization_location->file_name)
                . ':' . $specialization_location->raw_file_start;
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

        $specialization_key = null;

        if ($specialization_location) {
            $specialization_key = strtolower($specialization_location->file_name)
                . ':' . $specialization_location->raw_file_start;
        }

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
     * @psalm-pure
     */
    public static function getForAssignment(
        string $var_id,
        CodeLocation $assignment_location,
        ?string $specialization_key = null,
    ): self {
        return self::makeLocatedById($var_id, ' from ', $var_id, $assignment_location, $specialization_key);
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
            $specialization_key = strtolower($specialization_location->file_name)
                . ':' . $specialization_location->raw_file_start;
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
            $this->specialized_calls,
        );
    }

    /**
     * Re-key this node under a different (un)specialization while carrying over its identity-derived
     * location, label and flow state unchanged. Used by the taint resolver when it de-specializes or
     * re-specializes a node it already holds. The location is copied from $this, so it can never
     * diverge from the id -- see the class invariant.
     *
     * @param array<string, array<string, string>> $specialized_calls
     * @psalm-mutation-free
     */
    public function withSpecialization(
        string $id,
        ?string $unspecialized_id,
        ?string $specialization_key,
        array $specialized_calls,
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
            $specialized_calls,
        );
    }

    /**
     * Produce the successor reached when taint flows out of this node along an edge: the same
     * identity, label and location, with updated flow state (taints, provenance and path types).
     * The location is copied from $this, so it can never diverge from the id -- see the class
     * invariant.
     *
     * @param list<string> $path_types
     * @param array<string, array<string, string>> $specialized_calls
     * @psalm-mutation-free
     */
    public function withFlow(
        int $taints,
        self $taintSource,
        array $path_types,
        array $specialized_calls,
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
            $specialized_calls,
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
