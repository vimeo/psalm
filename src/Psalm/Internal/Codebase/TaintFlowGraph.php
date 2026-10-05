<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Override;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\Path;
use Psalm\Issue\TaintedCallable;
use Psalm\Issue\TaintedCookie;
use Psalm\Issue\TaintedCustom;
use Psalm\Issue\TaintedEval;
use Psalm\Issue\TaintedExtract;
use Psalm\Issue\TaintedFile;
use Psalm\Issue\TaintedHeader;
use Psalm\Issue\TaintedHtml;
use Psalm\Issue\TaintedInclude;
use Psalm\Issue\TaintedLdap;
use Psalm\Issue\TaintedLlmPrompt;
use Psalm\Issue\TaintedNosql;
use Psalm\Issue\TaintedSSRF;
use Psalm\Issue\TaintedShell;
use Psalm\Issue\TaintedSleep;
use Psalm\Issue\TaintedSql;
use Psalm\Issue\TaintedSystemSecret;
use Psalm\Issue\TaintedTextWithQuotes;
use Psalm\Issue\TaintedUnserialize;
use Psalm\Issue\TaintedUrlComponent;
use Psalm\Issue\TaintedUrlPath;
use Psalm\Issue\TaintedUserSecret;
use Psalm\Issue\TaintedXpath;
use Psalm\IssueBuffer;
use Psalm\Progress\Phase;
use Psalm\Progress\Progress;
use Psalm\Storage\Capabilities;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;

use function array_pop;
use function array_unshift;
use function count;
use function end;
use function implode;
use function ksort;
use function min;
use function strcmp;
use function strlen;
use function strpos;
use function substr;

/**
 * @internal
 */
final class TaintFlowGraph extends DataFlowGraph
{
    /**
     * The suffix of the type of an edge converting to a scalar a value that may be an array (see
     * CastAnalyzer::getArrayConversionSuffix()).
     */
    public const ARRAY_CONVERSION_SUFFIX = '-of-array';

    /**
     * The separator DataFlowNode uses to build a specialized node id from its
     * unspecialized base id and specialization key (see DataFlowNode::make()).
     */
    public const SPECIALIZATION_SEPARATOR = ' specialized in ';

    /**
     * How many nested calls the search for the generators what is sent can be sent to matches (see
     * linkGeneratorSends()): recursive calls nest without end.
     */
    private const GENERATOR_SEND_CALL_DEPTH = 8;

    /** @var array<string, DataFlowNode> */
    private array $sources = [];

    /** @var array<string, DataFlowNode> */
    private array $nodes = [];

    /** @var array<string, DataFlowNode> */
    private array $sinks = [];

    /**
     * Unspecialized ID => (Specialization key => Specialized ID)
     *
     * @var array<string, array<string, string>>
     */
    private array $specializations = [];

    /**
     * Specialization key => true
     *
     * @var array<string, true>
     */
    private array $specialized_calls = [];

    /**
     * The return node id of each generator function-like => the node of what is sent to it (see
     * linkGeneratorSends())
     *
     * @var array<string, DataFlowNode>
     */
    private array $generator_sent_nodes = [];

    /**
     * The node of what is sent to a generator => the parent nodes of that generator (see linkGeneratorSends())
     *
     * @var array<string, array<string, true>>
     */
    private array $generator_sends = [];

    /**
     * The ids of the nodes holding a stream that writes to the response (see OutputStreamTaintAnalyzer). Only used
     * while analysing the function-like they are in, so never merged by addGraph().
     *
     * @var array<string, true>
     */
    private array $output_streams = [];

    /**
     * Call sites specialized speculatively, before knowing whether the callee is pure:
     * specialization key => (function-like node of the callee => true)
     *
     * @var array<string, array<string, true>>
     */
    private array $speculative_calls = [];

    /**
     * The array keys passed to parameters at call sites: unspecialized argument node id => (specialization key of
     * the call site, whether the call is specialized or not => key). A literal key is as the keys of array
     * fetches and assignments are in path types, and a parameter of the function-like making the call, as passed,
     * is its unspecialized argument node id prefixed with '@'. The body of a function-like whose array key is one of its parameters, as passed,
     * fetches or assigns the key of each call (see ArrayFetchAnalyzer::getParamKey() and
     * TaintFlowResolution::resolveParamKey()).
     *
     * @var array<string, array<string, string>>
     */
    private array $param_keys = [];

    /**
     * The arguments of unspecialized calls: argument node id of the call site => [the specialization key of the
     * call site, the file, start and end of the declaration of the function-like called]. A flow entering it
     * through them knows the array keys the call passes (see TaintFlowResolution::bindCall()).
     *
     * @var array<string, array{string, string, int, int}>
     */
    private array $call_arguments = [];

    /**
     * Speculatively specialized call sites of a callee that turned out not to be pure,
     * which are resolved as if they were not specialized: specialization key => true
     *
     * @var array<string, true>
     */
    private array $despecialized_calls = [];

    /**
     * The despecialized calls whose callees only read, and so can't keep what a call gets for another one to
     * return: specialization key => true
     *
     * @var array<string, true>
     */
    private array $read_only_calls = [];

    /**
     * Whether the taint nodes of a call to $storage at $call_location are specialized to the call site,
     * so that taint flowing into one call does not flow out of the other calls.
     *
     * This is sound only for callees that are pure: a function-like with a side effect could store the
     * taint somewhere it is read back by another call. Function-likes marked pure (or with
     * `@psalm-taint-specialize`) are always specialized. The purity of an unannotated project
     * function-like is only known once the whole codebase has been analysed (see
     * {@see MutationLevelResolver}), so its calls are specialized speculatively when it cannot be
     * overridden, and resolved as unspecialized calls if it turns out not to be pure
     * (see {@see self::despecializeImpureCalls()}).
     */
    public static function isCallSpecialized(
        ?self $graph,
        Codebase $codebase,
        FunctionLikeStorage $storage,
        CodeLocation $call_location,
    ): bool {
        if ($storage->specialize_call) {
            return true;
        }

        // a builtin has no body: the taints flow through each of its calls as its declaration says, whether it is
        // pure or not (`reset()` moves the pointer of the array it returns an element of), unless its calls return
        // what was given to the others
        if ($storage->builtin) {
            return $storage instanceof MethodStorage
                || $storage->cased_name === null
                || !InternalCallMapHandler::keepsStateBetweenCalls($storage->cased_name);
        }

        if ($graph === null
            || $storage->has_mutations_annotation
            || $storage->location === null
            || !$codebase->config->isInProjectDirs($storage->location->file_path)
        ) {
            return false;
        }

        if ($storage instanceof MethodStorage) {
            // `@method` pseudo-methods have no defining class, nor a body whose purity could be inferred
            if ($storage->cased_name === '__construct'
                || $storage->defining_fqcln === null
                || $storage->defining_fqcln === ''
            ) {
                return false;
            }

            // an override could have side effects even if this implementation doesn't
            if ($storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PRIVATE
                && !$storage->final
                && !$codebase->classlike_storage_provider->get($storage->defining_fqcln)->final
            ) {
                return false;
            }
        }

        $function_node_id = CodeUseGraph::functionLikeNodeForStorage($storage);

        if ($function_node_id === null) {
            return false;
        }

        $graph->speculative_calls[DataFlowNode::getSpecializationKey($call_location)][$function_node_id] = true;

        return true;
    }

    /**
     * The node as it would be without a speculative specialization: speculative specializations
     * only concern taints, so the variable use graph (see {@see VariableUseGraph}) keeps the nodes
     * it had before.
     *
     * @psalm-mutation-free
     */
    public function withoutSpeculativeSpecialization(DataFlowNode $node): DataFlowNode
    {
        if ($node->unspecialized_id === null
            || $node->specialization_key === null
            || !isset($this->speculative_calls[$node->specialization_key])
        ) {
            return $node;
        }

        return $node->withSpecialization($node->unspecialized_id, null, null, $node->context);
    }

    /**
     * Adds paths into $node from the parent nodes found in the array keys and values of $type,
     * at any depth, as the array assignments that put them there. Returns whether it added any.
     *
     * A value without parent nodes of its own carries its taint in those of its array keys and
     * values: an array fetch from it takes the parent nodes of the fetched value. Once $node is
     * made a parent node of such a value, the fetch goes through $node instead, which these
     * paths keep leading to the same taint.
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public function addPathsFromNestedParentNodes(DataFlowNode $node, Union $type, CodeLocation $location): bool
    {
        $added = false;

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TKeyedArray) {
                foreach ($atomic_type->properties as $key => $property_type) {
                    $added = $this->addPathsFromParentNodes(
                        $node,
                        $property_type,
                        'arrayvalue-assignment-\'' . $key . '\'',
                        $location,
                    ) || $added;
                }

                $type_params = $atomic_type->fallback_params;
            } elseif ($atomic_type instanceof TArray) {
                $type_params = $atomic_type->type_params;
            } else {
                continue;
            }

            if ($type_params !== null) {
                $added = $this->addPathsFromParentNodes($node, $type_params[0], 'arraykey-assignment', $location)
                    || $added;
                $added = $this->addPathsFromParentNodes($node, $type_params[1], 'arrayvalue-assignment', $location)
                    || $added;
            }
        }

        return $added;
    }

    /**
     * Adds paths of type $path_type into $node from the parent nodes of $type, or else from those
     * nested in it (see addPathsFromNestedParentNodes()). Returns whether it added any.
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    private function addPathsFromParentNodes(
        DataFlowNode $node,
        Union $type,
        string $path_type,
        CodeLocation $location,
    ): bool {
        if ($type->parent_nodes) {
            foreach ($type->parent_nodes as $parent_node) {
                $this->addPath($parent_node, $node, $path_type);
            }

            return true;
        }

        $nested_node = DataFlowNode::getForAssignment($node->label . ' ' . $path_type, $location);

        if (!$this->addPathsFromNestedParentNodes($nested_node, $type, $location)) {
            return false;
        }

        $this->addNode($nested_node);
        $this->addPath($nested_node, $node, $path_type);

        return true;
    }

    /**
     * Resolves the speculatively specialized call sites of callees that turned out not to be pure
     * (or whose purity is unknown) as unspecialized calls.
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    private function despecializeImpureCalls(Codebase $codebase): void
    {
        if (!$this->speculative_calls) {
            return;
        }

        $mutation_levels = $codebase->code_use_graph->getMutationLevels();

        foreach ($this->speculative_calls as $specialization_key => $callees) {
            $is_read_only = true;

            foreach ($callees as $function_node_id => $_) {
                $mutation_level = $mutation_levels[$function_node_id] ?? Capabilities::ALL;

                if ($mutation_level !== Capabilities::NONE) {
                    $this->despecialized_calls[$specialization_key] = true;
                }

                $is_read_only = $is_read_only
                    && ($mutation_level & ~(Capabilities::READ_PROPS | Capabilities::READ_GLOBALS)) === 0;
            }

            if ($is_read_only && isset($this->despecialized_calls[$specialization_key])) {
                $this->read_only_calls[$specialization_key] = true;
            }
        }
    }

    /**
     * Records that the call at $call_location passes the array key $key (see $param_keys) to the parameter of
     * $argument_node
     *
     * @psalm-external-mutation-free
     */
    public function addParamKey(DataFlowNode $argument_node, string $key, CodeLocation $call_location): void
    {
        $this->param_keys[$argument_node->unspecialized_id ?? $argument_node->id]
            [DataFlowNode::getSpecializationKey($call_location)] = $key;
    }

    /**
     * Records that $argument_node is an argument of the unspecialized call at $call_location, of the function-like
     * declared at $callee_location (see $call_arguments)
     *
     * @psalm-external-mutation-free
     */
    public function addCallArgument(
        DataFlowNode $argument_node,
        CodeLocation $call_location,
        CodeLocation $callee_location,
    ): void {
        $this->call_arguments[$argument_node->id] = [
            DataFlowNode::getSpecializationKey($call_location),
            $callee_location->file_path,
            $callee_location->raw_file_start,
            $callee_location->raw_file_end,
        ];
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function addNode(DataFlowNode $node): void
    {
        $this->nodes[$node->id] = $node;

        if ($node->unspecialized_id !== null) {
            /** @var string $node->specialization_key */
            $this->specialized_calls[$node->specialization_key] = true;
            $this->specializations[$node->unspecialized_id][$node->specialization_key] = $node->id;
        }
    }

    /**
     * Leaves out the paths no taint goes through, and those to the uses only the variable use
     * graph tracks: the analysis adds every path to the data flow graph, whichever graphs it
     * builds, so that types get the same parent nodes either way.
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    #[Override]
    public function addPath(
        DataFlowNode $from,
        DataFlowNode $to,
        string $path_type,
        int $added_taints = 0,
        int $removed_taints = 0,
    ): void {
        if ($removed_taints === TaintKind::ALL
            || $to->id === DataFlowNode::getForVariableUse()->id
            || $to->id === DataFlowNode::getForClosureUse()->id
        ) {
            return;
        }

        parent::addPath($from, $to, $path_type, $added_taints, $removed_taints);
    }

    /**
     * Adds a path between two nodes from the graph of another worker (see addGraph()), which may have added
     * one already, from another file: then a flow can take either. Whichever came first, so that the
     * resolution doesn't depend on the order the workers' graphs are merged in. (The analysis of a file adds
     * a path again to replace it, e.g. with the taints a call escapes: see addPath().)
     *
     * Two paths of the same type are merged into one: a flow keeps the taints either keeps, and gets those
     * either adds. Two paths of different types aren't: they handle open assignments differently (see
     * shouldIgnoreFetch()). The one of the first type stays, and the other goes through a node of its own.
     *
     * @psalm-external-mutation-free
     */
    private function mergePath(string $from_id, ?DataFlowNode $from, string $to_id, Path $path): void
    {
        $existing = $this->forward_edges[$from_id][$to_id] ?? null;

        if ($existing === null) {
            $this->forward_edges[$from_id][$to_id] = $path;

            return;
        }

        if ($existing->type === $path->type) {
            $this->forward_edges[$from_id][$to_id] = new Path(
                $path->type,
                min($existing->length, $path->length),
                ($existing->added_taints & ~$existing->removed_taints) | ($path->added_taints & ~$path->removed_taints),
                $existing->removed_taints & $path->removed_taints,
            );

            return;
        }

        [$kept, $moved] = strcmp($existing->type, $path->type) < 0 ? [$existing, $path] : [$path, $existing];

        $this->forward_edges[$from_id][$to_id] = $kept;

        $variant = DataFlowNode::getForPathVariant($from_id, $from, $moved->type);
        $this->nodes[$variant->id] = $variant;
        $this->mergePath($from_id, $from, $variant->id, $moved);
        $this->forward_edges[$variant->id][$to_id] = new Path('=', 0);
    }

    /**
     * Records that the generators of the function-like with the return node $return_node get what is sent to
     * them through $sent_node.
     *
     * @psalm-external-mutation-free
     */
    public function addGenerator(DataFlowNode $return_node, DataFlowNode $sent_node): void
    {
        $this->generator_sent_nodes[$return_node->id] = $sent_node;
    }

    /**
     * Records that what flows into $sent_node is sent to the generator with the parent nodes $generator_nodes.
     *
     * @param array<string, DataFlowNode> $generator_nodes
     * @psalm-external-mutation-free
     */
    public function addGeneratorSend(DataFlowNode $sent_node, array $generator_nodes): void
    {
        $this->nodes[$sent_node->id] = $sent_node;

        foreach ($generator_nodes as $generator_node) {
            $this->generator_sends[$sent_node->id][$generator_node->id] = true;
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addSource(DataFlowNode $node): void
    {
        $this->sources[$node->id] = $node;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addSink(DataFlowNode $node): void
    {
        $this->sinks[$node->id] = $node;
        // in the rare case the sink is the _next_ node, this is necessary
        $this->nodes[$node->id] = $node;
    }

    /**
     * Records that $node holds a stream writing to the response
     *
     * @psalm-external-mutation-free
     */
    public function addOutputStream(DataFlowNode $node): void
    {
        $this->output_streams[$node->id] = true;
    }

    /**
     * Whether a value of $type may be a stream writing to the response
     *
     * @psalm-mutation-free
     */
    public function isOutputStream(Union $type): bool
    {
        foreach ($type->parent_nodes as $parent_node_id => $_) {
            if (isset($this->output_streams[$parent_node_id])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addGraph(self $other): void
    {
        $this->sources += $other->sources;
        $this->sinks += $other->sinks;
        $this->nodes += $other->nodes;
        $this->specialized_calls += $other->specialized_calls;
        $this->generator_sent_nodes += $other->generator_sent_nodes;

        foreach ($other->generator_sends as $key => $map) {
            $this->generator_sends[$key] = ($this->generator_sends[$key] ?? []) + $map;
        }

        foreach ($other->param_keys as $key => $map) {
            $this->param_keys[$key] = ($this->param_keys[$key] ?? []) + $map;
        }

        $this->call_arguments += $other->call_arguments;

        foreach ($other->speculative_calls as $key => $map) {
            $this->speculative_calls[$key] = ($this->speculative_calls[$key] ?? []) + $map;
        }

        foreach ($other->forward_edges as $key => $map) {
            if (!isset($this->forward_edges[$key])) {
                $this->forward_edges[$key] = $map;

                continue;
            }

            $from = $this->nodes[$key] ?? $this->sources[$key] ?? null;

            foreach ($map as $to_id => $path) {
                $this->mergePath($key, $from, $to_id, $path);
            }
        }

        foreach ($other->specializations as $key => $map) {
            if (!isset($this->specializations[$key])) {
                $this->specializations[$key] = $map;
            } else {
                $this->specializations[$key] += $map;
            }
        }
    }

    /**
     * @psalm-mutation-free
     */
    public function getPredecessorPath(DataFlowNode $source): string
    {
        $location_summary = '';

        if ($source->code_location) {
            $location_summary = $source->code_location->getShortSummary();
        }

        $source_descriptor = $source->label . ($location_summary ? ' (' . $location_summary . ')' : '');

        $previous_source = $source->taintSource;

        if ($previous_source) {
            if ($previous_source === $source) {
                return '';
            }

            if ($source->code_location
                && $previous_source->code_location
                && $previous_source->code_location->getHash() === $source->code_location->getHash()
                && $previous_source->taintSource
            ) {
                return $this->getPredecessorPath($previous_source->taintSource) . ' -> ' . $source_descriptor;
            }

            return $this->getPredecessorPath($previous_source) . ' -> ' . $source_descriptor;
        }

        return $source_descriptor;
    }

    /**
     * @psalm-mutation-free
     */
    public function getSuccessorPath(DataFlowNode $sink): string
    {
        $location_summary = '';

        if ($sink->code_location) {
            $location_summary = $sink->code_location->getShortSummary();
        }

        $sink_descriptor = $sink->label . ($location_summary ? ' (' . $location_summary . ')' : '');

        $next_sink = $sink->taintSource;

        if ($next_sink) {
            if ($next_sink === $sink) {
                return '';
            }

            if ($sink->code_location
                && $next_sink->code_location
                && $next_sink->code_location->getHash() === $sink->code_location->getHash()
                && $next_sink->taintSource
            ) {
                return $sink_descriptor . ' -> ' . $this->getSuccessorPath($next_sink->taintSource);
            }

            return $sink_descriptor . ' -> ' . $this->getSuccessorPath($next_sink);
        }

        return $sink_descriptor;
    }

    /**
     * @return list<array{location: ?CodeLocation, label: string, entry_path_type: string}>
     * @psalm-pure
     */
    public function getIssueTrace(DataFlowNode $source): array
    {
        $out = [];
        do {
            /** @var DataFlowNode $source */
            $previous_source = $source->taintSource;
            if ($previous_source === $source) {
                break;
            }
            $path_types = $source->path_types;
            array_unshift($out, [
                'location' => $source->code_location,
                'label' => $source->label,
                'entry_path_type' => end($path_types) ?: '',
            ]);
            $source = $previous_source;
        } while ($previous_source);

        return $out;
    }

    public function connectSinksAndSources(Progress $progress): void
    {
        $progress->startPhase(Phase::TAINT_GRAPH_RESOLUTION);

        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        $this->despecializeImpureCalls($codebase);

        if ($this->generator_sends) {
            $reverse = $this->getReverseEdges();
            $this->linkGeneratorSends($reverse);
            unset($reverse);
        }

        // Remove all specializations without an outgoing edge
        foreach ($this->specializations as $k => &$map) {
            foreach ($map as $kk => $specialized_id) {
                if (!isset($this->forward_edges[$specialized_id])) {
                    unset($map[$kk]);
                }
            }
            if (!$map) {
                unset($this->specializations[$k]);
            }
        } unset($map);

        $resolution = new TaintFlowResolution(
            $this,
            $this->forward_edges,
            $this->nodes,
            $this->sources,
            $this->sinks,
            $this->specializations,
            $this->specialized_calls,
            $this->despecialized_calls,
            $this->read_only_calls,
            $this->param_keys,
            $this->call_arguments,
            Config::getInstance(),
            $project_analyzer,
            $codebase,
        );

        $this->sinks = [];
        $this->sources = [];

        $resolution->resolve($progress);

        $progress->taskDone(0);
    }

    /**
     * Links what is sent to a generator (see Generator::send()) to the yield expressions of the
     * generator function-likes it can come from, and what is sent to a generator delegating to another
     * (`yield from`) to the yield expressions of the latter.
     *
     * Which function-likes a generator can come from is only known once the whole graph is built: they
     * are the generator function-likes whose return nodes reach the parent nodes of the generator. The
     * search for them goes backwards from those parent nodes, matching the calls it leaves through
     * their arguments with the ones it entered through their returns, as the resolution walk does. It
     * stops at the first such return node of each path: a generator a generator yields is not linked,
     * nor one from a function-like without a body (unknown origin).
     *
     * What is sent to the generator of a specialized call goes into its specialization, so that it only
     * reaches that call's body walk.
     *
     * @param array<string, array<string, true>> $reverse see getReverseEdges()
     * @param-out array<string, array<string, true>> $reverse
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    private function linkGeneratorSends(array &$reverse): void
    {
        // the graphs of the forked workers are merged in any order: keep the edges added the same
        ksort($this->generator_sends);

        foreach ($this->generator_sends as $send_id => $generator_ids) {
            ksort($generator_ids);

            $send_node = $this->nodes[$send_id];

            // [node id, specialization keys of the calls entered through their returns, innermost last]
            $queue = [];
            $visited = [];

            foreach ($generator_ids as $generator_id => $_) {
                $queue[] = [$generator_id, []];
                $visited[$generator_id . "\0"] = true;
            }

            while ($queue) {
                [$id, $calls] = array_pop($queue);

                [$unspecialized_id, $specialization_key] = self::splitSpecializedId($id);

                if (isset($this->generator_sent_nodes[$unspecialized_id])) {
                    $this->linkGeneratorSend($reverse, $send_node, $unspecialized_id, $specialization_key);

                    continue;
                }

                foreach ($reverse[$id] ?? [] as $from_id => $_) {
                    $from_calls = $calls;

                    if ($specialization_key !== null && $from_id === $unspecialized_id) {
                        // into the body of a call through its return
                        $from_calls[] = $specialization_key;

                        if (count($from_calls) > self::GENERATOR_SEND_CALL_DEPTH) {
                            // too deep (recursion): any call matches
                            $from_calls = [];
                        }
                    } else {
                        [$from_unspecialized_id, $from_specialization_key] = self::splitSpecializedId($from_id);

                        if ($from_unspecialized_id === $id) {
                            // out of a body through the arguments of a call: the one entered, if any
                            if ($from_calls && end($from_calls) !== $from_specialization_key) {
                                continue;
                            }

                            array_pop($from_calls);
                        }
                    }

                    $state_key = $from_id . "\0" . implode("\0", $from_calls);

                    if (!isset($visited[$state_key])) {
                        $visited[$state_key] = true;
                        $queue[] = [$from_id, $from_calls];
                    }
                }
            }
        }
    }

    /**
     * Links $send_node to what is sent to the generators of the function-like with the return node
     * $return_node_id, in the specialization $specialization_key if given.
     *
     * @param array<string, array<string, true>> $reverse see getReverseEdges()
     * @param-out array<string, array<string, true>> $reverse
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    private function linkGeneratorSend(
        array &$reverse,
        DataFlowNode $send_node,
        string $return_node_id,
        ?string $specialization_key,
    ): void {
        $sent_node = $this->generator_sent_nodes[$return_node_id];

        if ($specialization_key !== null) {
            $sent_node = $sent_node->withSpecialization(
                $sent_node->id . self::SPECIALIZATION_SEPARATOR . $specialization_key,
                $sent_node->id,
                $specialization_key,
                null,
            );

            $this->addNode($sent_node);
            self::linkSpecialization($reverse, $sent_node->id);
        }

        $this->addPath($send_node, $sent_node, 'generator-send');
        $reverse[$sent_node->id][$send_node->id] = true;
    }

    /**
     * The unspecialized id and the specialization key of the node $id, or $id and null if it is not specialized
     *
     * @return array{string, ?string}
     * @psalm-pure
     */
    private static function splitSpecializedId(string $id): array
    {
        $separator_pos = strpos($id, self::SPECIALIZATION_SEPARATOR);

        if ($separator_pos === false) {
            return [$id, null];
        }

        return [
            substr($id, 0, $separator_pos),
            substr($id, $separator_pos + strlen(self::SPECIALIZATION_SEPARATOR)),
        ];
    }

    /**
     * The edges of the graph from their destinations to their origins, with the specialization links (see
     * linkSpecialization()) in both directions, for linkGeneratorSends() (TaintFlowResolution computes its own).
     *
     * @return array<string, array<string, true>>
     * @psalm-capabilities read-props
     */
    private function getReverseEdges(): array
    {
        $reverse = [];

        foreach ($this->forward_edges as $from_id => $destinations) {
            self::linkSpecialization($reverse, $from_id);

            foreach ($destinations as $to_id => $_) {
                $reverse[$to_id][$from_id] = true;
                self::linkSpecialization($reverse, $to_id);
            }
        }

        foreach ($this->nodes as $node) {
            if ($node->unspecialized_id !== null) {
                $reverse[$node->id][$node->unspecialized_id] = true;
                $reverse[$node->unspecialized_id][$node->id] = true;
            }
        }

        return $reverse;
    }

    /**
     * If $id is a specialized node id, records a link between it and its unspecialized base in both directions
     *
     * @param array<string, array<string, true>> $reverse
     * @param-out array<string, array<string, true>> $reverse
     * @psalm-pure
     */
    private static function linkSpecialization(array &$reverse, string $id): void
    {
        $pos = strpos($id, self::SPECIALIZATION_SEPARATOR);

        if ($pos === false) {
            return;
        }

        $unspecialized_id = substr($id, 0, $pos);

        $reverse[$id][$unspecialized_id] = true;
        $reverse[$unspecialized_id][$id] = true;
    }

    /**
     * Reports the flow of taints $taints from $predecessor into $sink.
     */
    public function reportTaintedFlow(
        DataFlowNode $predecessor,
        DataFlowNode $sink,
        int $taints,
        Config $config,
        Codebase $codebase,
    ): void {
        if ($sink->code_location
            && $config->reportIssueInFile('TaintedInput', $sink->code_location->file_path)
        ) {
            $issue_location = $sink->code_location;
        } elseif ($predecessor->code_location !== null) {
            $issue_location = $predecessor->code_location;
        } else {
            return;
        }

        $issue_trace = $this->getIssueTrace($predecessor);
        $path = $this->getPredecessorPath($predecessor)
            . ' -> ' . $this->getSuccessorPath($sink);

        $max = $codebase->taint_count;
        for ($x = 0; $x < $max; $x++) {
            $t = 1 << $x;
            if (!($taints & $t)) {
                continue;
            }
            $issue = match ($t) {
                TaintKind::INPUT_CALLABLE => new TaintedCallable(
                    'Detected tainted text',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_UNSERIALIZE => new TaintedUnserialize(
                    'Detected tainted code passed to unserialize or similar',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_INCLUDE => new TaintedInclude(
                    'Detected tainted code passed to include or similar',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_EVAL => new TaintedEval(
                    'Detected tainted code passed to eval or similar',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_SQL => new TaintedSql(
                    'Detected tainted SQL',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_NOSQL => new TaintedNosql(
                    'Detected tainted NoSQL query',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_HTML => new TaintedHtml(
                    'Detected tainted HTML',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_HAS_QUOTES => new TaintedTextWithQuotes(
                    'Detected tainted text with possible quotes',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_SHELL => new TaintedShell(
                    'Detected tainted shell code',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::USER_SECRET => new TaintedUserSecret(
                    'Detected tainted user secret leaking',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::SYSTEM_SECRET => new TaintedSystemSecret(
                    'Detected tainted system secret leaking',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_SSRF => new TaintedSSRF(
                    'Detected tainted network request',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_URL_COMPONENT => new TaintedUrlComponent(
                    'Detected tainted URL component',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_URL_PATH => new TaintedUrlPath(
                    'Detected tainted URL path segment',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_LDAP => new TaintedLdap(
                    'Detected tainted LDAP request',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_COOKIE => new TaintedCookie(
                    'Detected tainted cookie',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_FILE => new TaintedFile(
                    'Detected tainted file handling',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_HEADER => new TaintedHeader(
                    'Detected tainted header',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_XPATH => new TaintedXpath(
                    'Detected tainted xpath query',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_SLEEP => new TaintedSleep(
                    'Detected tainted sleep',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_EXTRACT => new TaintedExtract(
                    'Detected tainted extract',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                TaintKind::INPUT_LLM_PROMPT => new TaintedLlmPrompt(
                    'Detected tainted LLM prompt',
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
                default => new TaintedCustom(
                    'Detected tainted ' . $codebase->custom_taints[$t],
                    $issue_location,
                    $issue_trace,
                    $path,
                ),
            };

            IssueBuffer::maybeAdd($issue);
        }
    }
}
