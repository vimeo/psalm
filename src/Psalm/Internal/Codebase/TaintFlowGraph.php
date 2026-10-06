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
use Webmozart\Assert\Assert;

use function array_pop;
use function array_slice;
use function array_splice;
use function array_unshift;
use function count;
use function end;
use function implode;
use function ksort;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strpos;
use function substr;

use const SORT_STRING;

/**
 * @internal
 */
final class TaintFlowGraph extends DataFlowGraph
{
    /**
     * The separator DataFlowNode uses to build a specialized node id from its
     * unspecialized base id and specialization key (see DataFlowNode::make()).
     */
    private const SPECIALIZATION_SEPARATOR = ' specialized in ';

    /**
     * The expression types whose assignments and fetches shouldIgnoreFetch() matches.
     */
    private const STRUCTURAL_PATH_TYPE_FAMILIES = ['arraykey', 'arrayvalue', 'property'];

    /**
     * The suffix of the type of an edge converting to a scalar a value that may be an array (see
     * CastAnalyzer::getArrayConversionSuffix() and convertsTheArrayHoldingTheTaint()).
     */
    public const ARRAY_CONVERSION_SUFFIX = '-of-array';

    /**
     * How many of the innermost open assignments (see appendPathType()) of a flow
     * entering a specialized call recursively its entry is keyed on (see
     * enterSpecializedCall()).
     */
    private const RECURSIVE_ENTRY_OPEN_ASSIGNMENT_DEPTH = 4;

    /**
     * How many flows with the same taints and context, but different open assignments
     * (see appendPathType()), the resolution walks from a node before it forgets all but
     * the innermost open assignment of the next ones (see getChildNodes()). Each one
     * more costs a large codebase more than it gains in precision.
     */
    private const MAX_OPEN_ASSIGNMENT_STATES = 1;

    /**
     * How many taint masks and specialized call entries a node is visited with before the flows reaching it are
     * widened (see getChildNodes()).
     */
    private const MAX_NODE_CONTEXTS = 16;

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
     * Unspecialized node entered by a specialized call => exit node id => whether
     * the exit is one of the same function-like (see isExitOfEntered())
     *
     * @var array<string, array<string, bool>>
     */
    private array $exits_of_entered = [];

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

    /*
     * Taint resolution state, see connectSinksAndSources() and enterSpecializedCall().
     * Empty outside of connectSinksAndSources().
     */

    /**
     * Entry key (see enterSpecializedCall()) => entry
     *
     * @var array<string, int>
     */
    private array $entry_ids = [];

    /**
     * Entry => the node its body walk starts from
     *
     * @var list<DataFlowNode>
     */
    private array $entry_roots = [];

    /**
     * Entry => the calls entering it: the entered node, carrying the caller's trace
     * and context, and the specialization key of the call
     *
     * @var array<int, non-empty-list<array{DataFlowNode, string}>>
     */
    private array $entry_callers = [];

    /**
     * Entry => exit node id . ' ' . taints => the node its body walk leaves through
     *
     * @var array<int, array<string, DataFlowNode>>
     */
    private array $entry_exits = [];

    /**
     * Entry => flow key => [sink, predecessor reached by its body walk, matching taints]
     *
     * @var array<int, array<string, array{DataFlowNode, DataFlowNode, int}>>
     */
    private array $entry_sinks = [];

    /**
     * Sink id => predecessor id => origin id => taints already reported for that flow
     *
     * @var array<string, array<string, array<string, int>>>
     */
    private array $reported_flows = [];

    /**
     * The ids of the nodes from which the resolution can reach a sink through an edge
     * that may drop a flow by its open assignments (see mayDropByOpenAssignments()):
     * only there do the open assignments of a flow decide where it goes (see
     * getOpenAssignmentsKey())
     *
     * @var array<string, true>
     */
    private array $fetch_reachable = [];

    /**
     * Call sites specialized speculatively, before knowing whether the callee is pure:
     * specialization key => (function-like node of the callee => true)
     *
     * @var array<string, array<string, true>>
     */
    private array $speculative_calls = [];

    /**
     * Speculatively specialized call sites of a callee that turned out not to be pure,
     * which are resolved as if they were not specialized: specialization key => true
     *
     * @var array<string, true>
     */
    private array $despecialized_calls = [];

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
            foreach ($callees as $function_node_id => $_) {
                if (($mutation_levels[$function_node_id] ?? Capabilities::ALL) !== Capabilities::NONE) {
                    $this->despecialized_calls[$specialization_key] = true;
                    break;
                }
            }
        }
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

        foreach ($other->speculative_calls as $key => $map) {
            $this->speculative_calls[$key] = ($this->speculative_calls[$key] ?? []) + $map;
        }

        foreach ($other->forward_edges as $key => $map) {
            if (!isset($this->forward_edges[$key])) {
                $this->forward_edges[$key] = $map;
            } else {
                $this->forward_edges[$key] += $map;
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

        $sources = $this->sources;
        $sinks = $this->sinks;

        $this->sinks = [];
        $this->sources = [];

        ksort($this->forward_edges);
        ksort($this->specializations);

        $config = Config::getInstance();

        $project_analyzer = ProjectAnalyzer::getInstance();

        $codebase = $project_analyzer->getCodebase();

        $this->despecializeImpureCalls($codebase);

        $reverse = $this->getReverseEdges($sources);

        $this->linkGeneratorSends($reverse);

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

        // Restrict resolution to the sub-graph that can actually reach a sink.
        // A node from which no sink is reachable can never produce an issue, so
        // propagating taint into it is wasted work. On real codebases the full
        // taint graph is huge but this relevant sub-graph is tiny, which is what
        // makes resolution converge quickly.
        $sink_reachable = $this->getSinkReachableNodes($reverse, $sinks);

        foreach ($sources as $id => $_) {
            if (!isset($sink_reachable[$id])) {
                unset($sources[$id]);
            }
        }

        // Resolution runs to a fixed point (rather than for a fixed number of
        // rounds): the (id, taints, context, open assignments) visited guard in
        // getChildNodes() makes the state space finite -- a context is a specialized
        // call entry, of which there are finitely many (see enterSpecializedCall()),
        // and a node is walked from with finitely many open assignments (see
        // getChildNodes()) -- so the loop is guaranteed to terminate on its own.
        // Combined with the sink-reachability pruning above, this converges quickly
        // enough that no artificial nesting limit is needed.
        //
        // Node id => state key (see getStateKey()) => open assignments key (see
        // getOpenAssignmentsKey()) => true
        $visited_source_ids = [];

        // The number of rounds is not known ahead of time, so the progress bar
        // renders this phase as indeterminate (a tick per round, no percentage).
        while (count($sinks) && count($sources)) {
            $new_sources = [];

            ksort($sources);

            foreach ($sources as $source) {
                $visited_source_ids[$source->id][self::getStateKey($source->taints, $source->context)]
                    [$this->getOpenAssignmentsKey($source->id, $source->path_types)] = true;

                // If we have one or more edges starting at this node,
                // process destinations of those edges.
                if (isset($this->forward_edges[$source->id])) {
                    $this->getChildNodes(
                        $new_sources,
                        $source,
                        $visited_source_ids,
                        $sinks,
                        $sink_reachable,
                        $config,
                        $project_analyzer,
                        $codebase,
                    );
                } elseif ($source->specialization_key !== null
                    && isset($this->specialized_calls[$source->specialization_key])
                ) {
                    // If this is a specialized node, de-specialize: enter its shared body
                    // (see enterSpecializedCall()).
                    /** @var string $source->unspecialized_id */
                    if (!isset($this->forward_edges[$source->unspecialized_id])) {
                        continue;
                    }

                    if (isset($this->despecialized_calls[$source->specialization_key])) {
                        // A despecialized call is entered like an unspecialized one: the body is walked
                        // in the context of the flow, and it is exited through all of its call sites.
                        $this->getChildNodes(
                            $new_sources,
                            $source->withSpecialization($source->unspecialized_id, null, null, $source->context),
                            $visited_source_ids,
                            $sinks,
                            $sink_reachable,
                            $config,
                            $project_analyzer,
                            $codebase,
                        );
                    } else {
                        foreach ($this->enterSpecializedCall(
                            $source,
                            $source->unspecialized_id,
                            $source->specialization_key,
                            $config,
                            $codebase,
                        ) as $generated_source) {
                            $this->getChildNodes(
                                $new_sources,
                                $generated_source,
                                $visited_source_ids,
                                $sinks,
                                $sink_reachable,
                                $config,
                                $project_analyzer,
                                $codebase,
                            );
                        }
                    }
                } elseif (isset($this->specializations[$source->id])) {
                    // If this node has first level specializations (=> is first-level & unspecialized),
                    // process them: all of them outside of any specialized call, else only those of
                    // the calls the flow's body was entered through (see addEntryExit()).
                    Assert::null($source->specialization_key);

                    $has_specialized_calls = false;

                    foreach ($this->specializations[$source->id] as $specialization => $_) {
                        if (!isset($this->despecialized_calls[$specialization])) {
                            $has_specialized_calls = true;
                            break;
                        }
                    }

                    $exits = $has_specialized_calls && $source->context !== null
                        ? $this->addEntryExit($source->context, $source)
                        : [];

                    // The call sites of despecialized calls are all exited, keeping the context of the flow.
                    foreach ($this->specializations[$source->id] as $specialization => $specialized_id) {
                        if (isset($this->despecialized_calls[$specialization])) {
                            $this->getChildNodes(
                                $new_sources,
                                $source->withSpecialization(
                                    $specialized_id,
                                    $source->id,
                                    $specialization,
                                    $source->context,
                                ),
                                $visited_source_ids,
                                $sinks,
                                $sink_reachable,
                                $config,
                                $project_analyzer,
                                $codebase,
                            );
                        }
                    }

                    if ($has_specialized_calls && $source->context !== null) {
                        foreach ($exits as $generated_source) {
                            $this->getChildNodes(
                                $new_sources,
                                $generated_source,
                                $visited_source_ids,
                                $sinks,
                                $sink_reachable,
                                $config,
                                $project_analyzer,
                                $codebase,
                            );
                        }
                    } elseif ($has_specialized_calls) {
                        foreach ($this->specializations[$source->id] as $specialization => $specialized_id) {
                            if (!isset($this->despecialized_calls[$specialization])) {
                                $this->getChildNodes(
                                    $new_sources,
                                    $source->withSpecialization(
                                        $specialized_id,
                                        $source->id,
                                        $specialization,
                                        null,
                                    ),
                                    $visited_source_ids,
                                    $sinks,
                                    $sink_reachable,
                                    $config,
                                    $project_analyzer,
                                    $codebase,
                                );
                            }
                        }
                    }
                }
            }

            $sources = $new_sources;

            $progress->taskDone(0);
        }

        $this->entry_ids = [];
        $this->entry_roots = [];
        $this->entry_callers = [];
        $this->entry_exits = [];
        $this->entry_sinks = [];
        $this->reported_flows = [];
        $this->fetch_reachable = [];

        $progress->taskDone(0);
    }

    /**
     * @psalm-pure
     */
    private static function getStateKey(int $taints, ?int $context): string
    {
        return $context === null ? (string) $taints : $taints . '@' . $context;
    }

    /**
     * Besides its taints and context, what the rest of the walk of a flow depends on:
     * its open assignments (see appendPathType()), which decide the fetches it takes
     * (see shouldIgnoreFetch()), and the overwrites and conversions that drop it (see
     * isOverwritten() and convertsTheArrayHoldingTheTaint()). A flow that put a value in
     * an array key must still reach the fetches of that key after another one, which put
     * a value in another key, visited the same node. Unless no such edge is reachable
     * from the node (see $fetch_reachable): there, telling them apart would only walk the
     * same edges again.
     *
     * @param list<string> $path_types
     * @psalm-mutation-free
     */
    private function getOpenAssignmentsKey(string $id, array $path_types): string
    {
        return isset($this->fetch_reachable[$id]) ? implode(' ', self::getOpenAssignments($path_types)) : '';
    }

    /**
     * A flow enters the shared body of a specialized function-like through the
     * specialized node $source of the call identified by $specialization_key.
     *
     * Only the entry nodes of such a body are specialized: all of its inner nodes
     * are shared by every call. So the body is walked once per entry -- the
     * unspecialized node entered plus what the walk depends on of the entering
     * flow -- with the flows of that walk carrying the entry as their context.
     * Everything the walk reaches depends on the call: an exit back to the call
     * site (addEntryExit()) as well as a sink (addEntrySink()), inside the body or
     * past it. So it is recorded against the entry and applied to each call
     * entering it, including calls that arrive after the walk: those are not
     * walked again, the recorded outcomes are replayed for them instead.
     *
     * This keeps the resolution context-sensitive for specialized calls, however
     * many rounds apart their flows arrive, while the state space stays bounded by
     * the number of entries rather than by the number of calls or call chains.
     *
     * @return list<DataFlowNode>
     */
    private function enterSpecializedCall(
        DataFlowNode $source,
        string $unspecialized_id,
        string $specialization_key,
        Config $config,
        Codebase $codebase,
    ): array {
        // What the body walk does depends on the entering taints and, through
        // shouldIgnoreFetch(), on the flow's open assignments (see appendPathType()).
        $entry_key = $unspecialized_id . ' ' . $source->taints;
        $open_assignments = self::getOpenAssignments($source->path_types);

        // A recursive call can wrap its argument deeper on every call: entering a body
        // the flow is already in (through the first callers of its entries), only the
        // innermost few open assignments are kept, so that there are finitely many
        // entries. Every other entry is keyed on all of them: a chain of first callers
        // only has one such entry per entered node, and finitely many others.
        //
        // The flow forgets the others, rather than walking the body with those of the
        // first call to make the entry: every call sharing it can differ there. Without
        // them, a fetch reaching past the kept ones is not ignored (see shouldIgnoreFetch()),
        // in the walk as after it -- the walk may have fetched past them. So the walk
        // may take taints a call does not have there, but takes all those it has.
        if ($source->taintSource !== null
            && count($open_assignments) > self::RECURSIVE_ENTRY_OPEN_ASSIGNMENT_DEPTH
        ) {
            $context = $source->context;

            while ($context !== null) {
                if ($this->entry_roots[$context]->id === $unspecialized_id) {
                    // the kept open assignments, and the type of the edge taken last if not one of them
                    $source = $source->withFlow(
                        $source->taints,
                        $source->taintSource,
                        array_slice(
                            $source->path_types,
                            count($open_assignments) - count($source->path_types)
                                - self::RECURSIVE_ENTRY_OPEN_ASSIGNMENT_DEPTH,
                        ),
                        $source->context,
                    );
                    $open_assignments = array_slice($open_assignments, -self::RECURSIVE_ENTRY_OPEN_ASSIGNMENT_DEPTH);

                    break;
                }

                $context = $this->entry_callers[$context][0][0]->context;
            }
        }

        $caller = $source->withSpecialization($unspecialized_id, null, null, $source->context);

        foreach ($open_assignments as $path_type) {
            $entry_key .= ' ' . $path_type;
        }

        if (!isset($this->entry_ids[$entry_key])) {
            $entry = count($this->entry_roots);
            $root = $source->withSpecialization($unspecialized_id, null, null, $entry);

            $this->entry_ids[$entry_key] = $entry;
            $this->entry_roots[] = $root;
            $this->entry_callers[$entry] = [[$caller, $specialization_key]];
            $this->entry_exits[$entry] = [];
            $this->entry_sinks[$entry] = [];

            return [$root];
        }

        $entry = $this->entry_ids[$entry_key];
        $this->entry_callers[$entry][] = [$caller, $specialization_key];

        foreach ($this->entry_sinks[$entry] as [$sink, $predecessor, $matching_taints]) {
            $this->addSinkThroughCaller(
                $entry,
                $sink,
                $predecessor,
                $matching_taints,
                $caller,
                $config,
                $codebase,
            );
        }

        $nodes = [];

        foreach ($this->entry_exits[$entry] as $exit) {
            foreach ($this->exitThroughCaller($entry, $exit, $caller, $specialization_key) as $node) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * The body walk of $entry reached $exit, an unspecialized node whose
     * specializations lead back to call sites. Continues it at the call site of
     * each call entering $entry.
     *
     * @return list<DataFlowNode>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function addEntryExit(int $entry, DataFlowNode $exit): array
    {
        $exit_key = $exit->id . ' ' . $exit->taints;

        if (isset($this->entry_exits[$entry][$exit_key])) {
            return [];
        }

        $this->entry_exits[$entry][$exit_key] = $exit;

        $nodes = [];

        foreach ($this->entry_callers[$entry] as [$caller, $specialization_key]) {
            foreach ($this->exitThroughCaller($entry, $exit, $caller, $specialization_key) as $node) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * Continues $exit, reached by the body walk of $entry, in the context of one
     * call entering $entry: at that call's specialization of the exit node if it
     * has one. Else, if the exit is one of the function-like the call enters, the
     * call site doesn't use it, and the flow ends. If it is one of another
     * function-like, reached through something the calls share (a property, a
     * static property, ...), any call to it may return what the flow holds: as an
     * exit of the entry the call is made from, so that it leaves through an
     * enclosing call of that function-like if any, and outside of any specialized
     * call through all of its call sites, as a flow reaching it there would.
     *
     * The walk itself carries the trace of the first call entering $entry. For
     * any other call, the walk is summarized as a single step from the entered
     * node to the exit: replaying it in full would make traces through nested
     * specialized calls grow exponentially with the nesting depth.
     *
     * @return list<DataFlowNode>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function exitThroughCaller(
        int $entry,
        DataFlowNode $exit,
        DataFlowNode $caller,
        string $specialization_key,
    ): array {
        if ($caller !== $this->entry_callers[$entry][0][0]) {
            // the call and the start of the walk have the same open assignments: see enterSpecializedCall()
            $exit = $exit->withFlow($exit->taints, $caller, $exit->path_types, $caller->context);
        }

        if (isset($this->specializations[$exit->id][$specialization_key])) {
            return [$exit->withSpecialization(
                $this->specializations[$exit->id][$specialization_key],
                $exit->id,
                $specialization_key,
                $caller->context,
            )];
        }

        // an exit of the function-like entered: the call site doesn't use it
        if ($this->isExitOfEntered($exit->id, $caller->id)) {
            return [];
        }

        if ($caller->context !== null) {
            return $this->addEntryExit($caller->context, $exit);
        }

        $nodes = [];

        // the call sites of despecialized calls were all left from the exit already
        foreach ($this->specializations[$exit->id] as $exit_specialization_key => $specialized_id) {
            if (!isset($this->despecialized_calls[$exit_specialization_key])) {
                $nodes[] = $exit->withSpecialization($specialized_id, $exit->id, $exit_specialization_key, null);
            }
        }

        return $nodes;
    }

    /**
     * Whether exit $exit_id is one of the function-like of the unspecialized node
     * $entered_id a specialized call entered: that one is specialized for some of
     * the calls the exit is (whether or not a given call uses the exit, that is
     * whether it has a specialization of it: see connectSinksAndSources()).
     *
     * @psalm-external-mutation-free
     */
    private function isExitOfEntered(string $exit_id, string $entered_id): bool
    {
        if (!isset($this->exits_of_entered[$entered_id][$exit_id])) {
            $is_exit = false;

            foreach ($this->specializations[$exit_id] ?? [] as $specialization_key => $_) {
                if (isset($this->nodes[$entered_id . self::SPECIALIZATION_SEPARATOR . $specialization_key])) {
                    $is_exit = true;

                    break;
                }
            }

            $this->exits_of_entered[$entered_id][$exit_id] = $is_exit;
        }

        return $this->exits_of_entered[$entered_id][$exit_id];
    }

    /**
     * The body walk of $entry reached $sink from $predecessor. Like everything
     * the walk reaches, that flow depends on the call entering $entry, so it is
     * a finding for every such call (see addSinkThroughCaller()).
     */
    private function addEntrySink(
        int $entry,
        DataFlowNode $sink,
        DataFlowNode $predecessor,
        int $matching_taints,
        Config $config,
        Codebase $codebase,
    ): void {
        $sink_key = $sink->id . ' ' . $predecessor->id . ' ' . $matching_taints;

        if (isset($this->entry_sinks[$entry][$sink_key])) {
            return;
        }

        $this->entry_sinks[$entry][$sink_key] = [$sink, $predecessor, $matching_taints];

        foreach ($this->entry_callers[$entry] as [$caller]) {
            $this->addSinkThroughCaller(
                $entry,
                $sink,
                $predecessor,
                $matching_taints,
                $caller,
                $config,
                $codebase,
            );
        }
    }

    /**
     * Reports the flow into $sink from $predecessor, reached by the body walk of
     * $entry, as seen from one call entering $entry. If that call is itself made
     * inside the body walk of an enclosing entry, the flow is a finding for every
     * call entering that one instead.
     */
    private function addSinkThroughCaller(
        int $entry,
        DataFlowNode $sink,
        DataFlowNode $predecessor,
        int $matching_taints,
        DataFlowNode $caller,
        Config $config,
        Codebase $codebase,
    ): void {
        $predecessor = $this->getTraceThroughCaller($entry, $predecessor, $caller);

        if ($caller->context !== null) {
            $this->addEntrySink($caller->context, $sink, $predecessor, $matching_taints, $config, $codebase);
        } else {
            $this->reportTaintedFlowOnce($predecessor, $sink, $matching_taints, $config, $codebase);
        }
    }

    /**
     * Rebuilds $node, reached by the body walk of $entry, as reached through
     * $caller: the part of its trace inside the walk is replayed on top of the
     * caller's own trace, so the result carries the caller's origin. The walk
     * itself carries the trace of the first call entering $entry.
     *
     * The result only serves to report a flow, so its nodes keep just the path
     * type the trace displays for them.
     *
     * @psalm-mutation-free
     */
    private function getTraceThroughCaller(int $entry, DataFlowNode $node, DataFlowNode $caller): DataFlowNode
    {
        if ($caller === $this->entry_callers[$entry][0][0]) {
            return $node;
        }

        $root = $this->entry_roots[$entry];

        $walk = [];

        while ($node !== $root) {
            $walk[] = $node;
            $node = $node->taintSource;

            Assert::notNull($node);
        }

        $trace = $caller;

        for ($i = count($walk) - 1; $i >= 0; $i--) {
            $node = $walk[$i];
            $path_types = $node->path_types ? [$node->path_types[count($node->path_types) - 1]] : [];

            $trace = $node->withFlow($node->taints, $trace, $path_types, $caller->context);
        }

        return $trace;
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
            $this->linkSpecialization($reverse, $sent_node->id);
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
     * The edges of the graph from their destinations to their origins, with the specialization links
     * (see linkSpecialization()) in both directions.
     *
     * @param array<string, DataFlowNode> $sources
     * @return array<string, array<string, true>>
     * @psalm-capabilities read-props
     */
    private function getReverseEdges(array $sources): array
    {
        $reverse = [];

        foreach ($this->forward_edges as $from_id => $destinations) {
            $this->linkSpecialization($reverse, $from_id);

            foreach ($destinations as $to_id => $_) {
                $reverse[$to_id][$from_id] = true;
                $this->linkSpecialization($reverse, $to_id);
            }
        }

        // Complementary, authoritative specialization links taken from the node
        // objects' unspecialized_id field rather than from parsing the id string.
        // linkSpecialization() above only recognises a specialized node while its
        // id carries the ' specialized in ' separator (which DataFlowNode::make()
        // is the sole producer of); linking via the field as well keeps pruning
        // sound even if a future factory were to set unspecialized_id without
        // going through make(). Sinks are registered in $this->nodes too, but
        // sources are not, so they must be iterated separately.
        foreach ($this->nodes as $node) {
            $this->linkSpecializationByField($reverse, $node);
        }

        foreach ($sources as $node) {
            $this->linkSpecializationByField($reverse, $node);
        }

        return $reverse;
    }

    /**
     * Computes the set of node ids from which at least one sink is reachable, and
     * the subset of them from which one is reachable through an edge that may drop a
     * flow by its open assignments (see $fetch_reachable).
     *
     * The search runs backwards from the sinks over the forward edges, treating
     * specialization links as bidirectional so that a node whose specialized or
     * de-specialized form can reach a sink is itself kept (the resolution walk
     * maps freely between the two).
     *
     * @param array<string, array<string, true>> $reverse see getReverseEdges()
     * @param array<string, DataFlowNode> $sinks
     * @return array<string, true>
     * @psalm-capabilities read-props|write-this-props
     */
    private function getSinkReachableNodes(array $reverse, array $sinks): array
    {
        $reachable = [];
        $queue = [];

        foreach ($sinks as $id => $_) {
            $reachable[$id] = true;
            $queue[] = $id;
        }

        while ($queue) {
            $id = array_pop($queue);

            foreach ($reverse[$id] ?? [] as $from_id => $_) {
                if (!isset($reachable[$from_id])) {
                    $reachable[$from_id] = true;
                    $queue[] = $from_id;
                }
            }
        }

        $this->fetch_reachable = [];

        foreach ($this->forward_edges as $from_id => $destinations) {
            if (!isset($reachable[$from_id])) {
                continue;
            }

            // Which flow reaches a node first decides the open assignments it is walked
            // from with (see getChildNodes()): the edges of a node are walked in the same
            // order, whichever order the analysis of the files added them in.
            ksort($destinations, SORT_STRING);
            $this->forward_edges[$from_id] = $destinations;

            foreach ($destinations as $to_id => $path) {
                if (isset($reachable[$to_id]) && self::mayDropByOpenAssignments($path->type)) {
                    $this->fetch_reachable[$from_id] = true;
                    $queue[] = $from_id;

                    break;
                }
            }
        }

        while ($queue) {
            $id = array_pop($queue);

            foreach ($reverse[$id] ?? [] as $from_id => $_) {
                if (isset($reachable[$from_id]) && !isset($this->fetch_reachable[$from_id])) {
                    $this->fetch_reachable[$from_id] = true;
                    $queue[] = $from_id;
                }
            }
        }

        return $reachable;
    }

    /**
     * Whether an edge of type $path_type may drop a flow by its open assignments: a fetch
     * of a given array key or property, or of an array key, which shouldIgnoreFetch() may
     * ignore, an overwrite of an array key (see isOverwritten()), or a conversion of an
     * array (see convertsTheArrayHoldingTheTaint())
     *
     * @psalm-pure
     */
    private static function mayDropByOpenAssignments(string $path_type): bool
    {
        if ($path_type === 'arraykey-fetch'
            || str_starts_with($path_type, 'arrayvalue-overwrite-')
            || str_ends_with($path_type, self::ARRAY_CONVERSION_SUFFIX)
        ) {
            return true;
        }

        foreach (self::STRUCTURAL_PATH_TYPE_FAMILIES as $family) {
            if (str_starts_with($path_type, $family . '-fetch-')) {
                return true;
            }
        }

        return false;
    }

    /**
     * If $id is a specialized node id, records a bidirectional link between it
     * and its unspecialized base in the reverse adjacency map: during resolution
     * the walk maps freely between a node's specialized and de-specialized forms,
     * so both must share reachability. The link is derived from the id string
     * because specialized nodes are not always registered in $this->specializations
     * (e.g. specialized source and sink nodes).
     *
     * @param array<string, array<string, true>> $reverse
     * @param-out array<string, array<string, true>> $reverse
     * @psalm-capabilities write-refs|read-props
     */
    private function linkSpecialization(array &$reverse, string $id): void
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
     * Like linkSpecialization(), but derives the unspecialized form from the
     * node's authoritative unspecialized_id field instead of its id string, so
     * the link is recorded even for a specialized node whose id was not built
     * with the ' specialized in ' separator.
     *
     * @param array<string, array<string, true>> $reverse
     * @param-out array<string, array<string, true>> $reverse
     * @psalm-capabilities write-refs|read-props
     */
    private function linkSpecializationByField(array &$reverse, DataFlowNode $node): void
    {
        if ($node->unspecialized_id === null) {
            return;
        }

        $reverse[$node->id][$node->unspecialized_id] = true;
        $reverse[$node->unspecialized_id][$node->id] = true;
    }

    /**
     * Follows every outgoing edge of $generated_source: reports the flow into
     * each sink it reaches and enqueues the destinations not visited yet.
     *
     * @param array<string, DataFlowNode> $new_sources
     * @param-out array<string, DataFlowNode> $new_sources
     * @param array<string, array<string, array<string, true>>> $visited_source_ids
     * @param array<string, DataFlowNode> $sinks
     * @param array<string, true> $sink_reachable
     */
    private function getChildNodes(
        array &$new_sources,
        DataFlowNode $generated_source,
        array $visited_source_ids,
        array $sinks,
        array $sink_reachable,
        Config $config,
        ProjectAnalyzer $project_analyzer,
        Codebase $codebase,
    ): void {
        if ($generated_source->code_location
            && $project_analyzer->canReportIssues($generated_source->code_location->file_path)
            && !$config->reportIssueInFile('TaintedInput', $generated_source->code_location->file_path)
        ) {
            return;
        }

        $source_taints = $generated_source->taints;
        $context = $generated_source->context;
        $open_assignments = self::getOpenAssignments($generated_source->path_types);

        foreach ($this->forward_edges[$generated_source->id] as $to_id => $path) {
            if (!isset($this->nodes[$to_id])) {
                continue;
            }

            // Skip nodes from which no sink is reachable: they cannot contribute
            // to any issue, so there is no point propagating taint through them.
            if (!isset($sink_reachable[$to_id])) {
                continue;
            }

            $new_taints = ($source_taints | $path->added_taints) & ~$path->removed_taints;

            $path_type = $path->type;

            if (self::shouldIgnoreFetch($path_type, 'arraykey', $open_assignments)) {
                continue;
            }

            if (self::shouldIgnoreFetch($path_type, 'arrayvalue', $open_assignments)) {
                continue;
            }

            if (self::shouldIgnoreFetch($path_type, 'property', $open_assignments)) {
                continue;
            }

            if (self::isOverwritten($path_type, $open_assignments)) {
                continue;
            }

            if (self::convertsTheArrayHoldingTheTaint($path_type, $open_assignments)) {
                continue;
            }

            // past the edges that can drop a flow by its open assignments, they don't matter anymore
            $path_types = isset($this->fetch_reachable[$to_id])
                ? self::appendPathType($open_assignments, $path_type)
                : [$path_type];
            $to_context = $context;

            // A node reached in the context of many specialized call entries (a property they all write, and
            // everything reading it) would be walked once per entry: past a few, the flow forgets the entry and goes
            // on as if outside of any call, exiting through all of the call sites. It forgets its open assignments
            // too, so that any fetch takes it, and the flows widened at a node are walked once per taints rather
            // than once per open assignments. Both can only add flows
            $is_widened = $context !== null
                && count($visited_source_ids[$to_id] ?? []) >= self::MAX_NODE_CONTEXTS;

            if ($is_widened) {
                $to_context = null;
                $path_types = [$path_type];
            }

            // The visited guard keeps the fixed point finite, so a visited node is never
            // propagated from again. A visited sink still gets to report, though: the flow
            // arriving through this edge may be a different one from the flow that visited it
            // first (it can arrive rounds later when its path is longer).
            $state_key = self::getStateKey($new_taints, $to_context);
            $open_assignments_key = $this->getOpenAssignmentsKey($to_id, $path_types);
            $visited_states = $visited_source_ids[$to_id][$state_key] ?? [];

            // a flow without open assignments takes all the edges another one takes
            $already_visited = isset($visited_states[''])
                || isset($visited_states[$open_assignments_key]);

            // A loop can wrap a value deeper on every iteration, and a node can be reached
            // with more open assignments than can be walked from: past a few, a flow forgets
            // all but the innermost one. Then an edge the others would have dropped it at
            // doesn't (see mayDropByOpenAssignments()): the flow may take taints it doesn't
            // have there, but takes all those it has. A widened flow has already forgotten them.
            if (!$already_visited && !$is_widened && count($visited_states) >= self::MAX_OPEN_ASSIGNMENT_STATES) {
                $path_types = self::isStructuralAssignment($path_type)
                    ? [$path_type]
                    : self::appendPathType(array_slice($open_assignments, -1), $path_type);
                $open_assignments_key = $this->getOpenAssignmentsKey($to_id, $path_types);
                $already_visited = isset($visited_states[$open_assignments_key]);
            }

            $sink = $sinks[$to_id] ?? null;

            if ($already_visited && $sink === null) {
                continue;
            }

            // a flow is reported at its sink, or else at the node it reaches the sink from: a plugin can
            // connect a node without a location to a sink
            if ($sink !== null && ($generated_source->code_location || $sink->code_location)) {
                $matching_taints = $sink->taints & $new_taints;

                if ($matching_taints) {
                    if ($to_context !== null) {
                        $this->addEntrySink(
                            $to_context,
                            $sink,
                            $generated_source,
                            $matching_taints,
                            $config,
                            $codebase,
                        );
                    } else {
                        $this->reportTaintedFlowOnce($generated_source, $sink, $matching_taints, $config, $codebase);
                    }
                }
            }

            if ($already_visited) {
                continue;
            }

            $key = $to_id . ' ' . $state_key . ' ' . $open_assignments_key;

            if (isset($new_sources[$key])) {
                continue;
            }

            $new_sources[$key] = $this->nodes[$to_id]->withFlow(
                $new_taints,
                $generated_source,
                $path_types,
                $to_context,
            );
        }
    }

    /**
     * Returns the path types of a flow that took an edge of type $path_type from a
     * node whose open assignments (see getOpenAssignments()) are $open_assignments.
     *
     * Of the path types a flow went through, only what shouldIgnoreFetch() can still
     * observe is kept: the assignments to array keys, array values and properties
     * that no later fetch has matched yet -- a fetch matches the latest such
     * assignment of its expression type -- followed by the type of the edge the
     * flow took last, which the trace displays, unless that is such an assignment
     * itself. Keeping each node's full path would make the resolution use memory
     * quadratic in the length of the flows.
     *
     * @param list<string> $open_assignments
     * @return non-empty-list<string>
     * @psalm-pure
     */
    private static function appendPathType(array $open_assignments, string $path_type): array
    {
        foreach (self::STRUCTURAL_PATH_TYPE_FAMILIES as $family) {
            if (!str_starts_with($path_type, $family . '-fetch')) {
                continue;
            }

            for ($i = count($open_assignments) - 1; $i >= 0; $i--) {
                if (str_starts_with($open_assignments[$i], $family . '-assignment')) {
                    array_splice($open_assignments, $i, 1);

                    break;
                }
            }

            break;
        }

        $open_assignments[] = $path_type;

        return $open_assignments;
    }

    /**
     * Returns the open assignments of a flow from its path types (see
     * appendPathType()): the path types without the trailing one if that is not an
     * assignment. This is the history shouldIgnoreFetch() matches the flow's next
     * edge against; it gives the same result as on the full history.
     *
     * @param list<string> $path_types
     * @return list<string>
     * @psalm-pure
     */
    private static function getOpenAssignments(array $path_types): array
    {
        if ($path_types && !self::isStructuralAssignment($path_types[count($path_types) - 1])) {
            array_pop($path_types);
        }

        return $path_types;
    }

    /**
     * Whether the edge of type $path_type converts to a scalar (a cast, a concatenation) a value that may be the
     * array the innermost of the flow's open assignments $open_assignments put the taint in, under a key or as a
     * key: an array converts to "Array", or to 0/1, never to what it holds.
     *
     * @param list<string> $open_assignments
     * @psalm-pure
     */
    private static function convertsTheArrayHoldingTheTaint(string $path_type, array $open_assignments): bool
    {
        if (!str_ends_with($path_type, self::ARRAY_CONVERSION_SUFFIX)) {
            return false;
        }

        for ($i = count($open_assignments) - 1; $i >= 0; $i--) {
            if (self::isStructuralAssignment($open_assignments[$i])) {
                return str_starts_with($open_assignments[$i], 'arrayvalue-assignment')
                    || str_starts_with($open_assignments[$i], 'arraykey-assignment');
            }
        }

        return false;
    }

    /**
     * @psalm-pure
     */
    private static function isStructuralAssignment(string $path_type): bool
    {
        foreach (self::STRUCTURAL_PATH_TYPE_FAMILIES as $family) {
            if (str_starts_with($path_type, $family . '-assignment')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reports the flow from $predecessor into $sink, unless already reported.
     *
     * A flow is identified by its origin and by the edge it enters the sink through:
     * the origin keeps distinct sources apart where they share that edge (e.g. two
     * calls to a specialized function), the edge keeps distinct call sites of the
     * same source apart. Each taint kind is reported once per flow, however many
     * rounds, taint masks or contexts carry it.
     */
    private function reportTaintedFlowOnce(
        DataFlowNode $predecessor,
        DataFlowNode $sink,
        int $matching_taints,
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

        // the node the flow started at: the root of its taintSource chain
        $origin_node = $predecessor;
        while (($previous = $origin_node->taintSource) !== null && $previous !== $origin_node) {
            $origin_node = $previous;
        }
        $origin = $origin_node->id;
        $reported_taints = $this->reported_flows[$sink->id][$predecessor->id][$origin] ?? 0;
        $unreported_taints = $matching_taints & ~$reported_taints;

        if (!$unreported_taints) {
            return;
        }

        $this->reported_flows[$sink->id][$predecessor->id][$origin] = $reported_taints | $unreported_taints;

        // a value choosing the server of a URL can also inject any URL syntax in it, such as the `..` segments of
        // its path: each is reported as the most general issue alone
        if ($unreported_taints & TaintKind::INPUT_SSRF) {
            $unreported_taints &= ~(TaintKind::INPUT_URL_COMPONENT | TaintKind::INPUT_URL_PATH);
        } elseif ($unreported_taints & TaintKind::INPUT_URL_COMPONENT) {
            $unreported_taints &= ~TaintKind::INPUT_URL_PATH;
        }

        $issue_trace = $this->getIssueTrace($predecessor);
        $path = $this->getPredecessorPath($predecessor)
            . ' -> ' . $this->getSuccessorPath($sink);

        $max = $codebase->taint_count;
        for ($x = 0; $x < $max; $x++) {
            $t = 1 << $x;
            if (!($unreported_taints & $t)) {
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
