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
use Psalm\Internal\DataFlow\TaintFlowState;
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
use function ksort;
use function max;
use function min;
use function strpos;
use function substr;

use const PHP_INT_MAX;

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
     * How many states of the flows reaching a node it takes before widening them (see admit()).
     */
    private const MAX_NODE_STATES = 16;

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

    /*
     * Taint resolution state, see connectSinksAndSources(). Empty outside of it.
     */

    /**
     * Unspecialized node entered by specialized calls => what the flow entering it reaches, keyed
     * apart (see summarize())
     *
     * @var array<string, array<string, array{DataFlowNode, bool}>>
     */
    private array $summaries = [];

    /**
     * The summaries being made, as in summaries, and how deep they are in their making
     *
     * @var array<string, int>
     */
    private array $summary_depths = [];

    /**
     * What the summaries being made reach so far
     *
     * @var array<string, array<string, array{DataFlowNode, bool}>>
     */
    private array $partial_summaries = [];

    /**
     * The summaries made from one still being made, with the least and greatest depths of those
     * they used (see summarize()): they hold until one of those is made again
     *
     * @var array<string, array{array<string, array{DataFlowNode, bool}>, int, int}>
     */
    private array $provisional_summaries = [];

    /**
     * What the provisional summaries dropped reached, to make them again from (see summarize())
     *
     * @var array<string, array<string, array{DataFlowNode, bool}>>
     */
    private array $summary_seeds = [];

    /**
     * The least depth of the summaries being made that the summary being made used (see summarize())
     */
    private int $least_used_depth = PHP_INT_MAX;

    /**
     * The greatest depth of the summaries being made that the summary being made used
     */
    private int $greatest_used_depth = -1;

    /**
     * Node id => true, for the nodes from which a sink is reachable (see getSinkReachableNodes())
     *
     * @var array<string, true>
     */
    private array $sink_reachable = [];

    /**
     * Sink id => predecessor id => origin id => taints already reported for that flow
     *
     * @var array<string, array<string, array<string, int>>>
     */
    private array $reported_flows = [];

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

        return $node->withSpecialization($node->unspecialized_id, null, null);
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

        $codebase = ProjectAnalyzer::getInstance()->getCodebase();

        $this->despecializeImpureCalls($codebase);

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
        $this->sink_reachable = $this->getSinkReachableNodes($sources, $sinks);
        $this->sinks = $sinks;

        $roots = [];

        foreach ($sources as $id => $source) {
            if (isset($this->sink_reachable[$id])) {
                $roots[] = $source->withFlowState(TaintFlowState::fromSource($source->taints));
            }
        }

        // The number of rounds is not known ahead of time, so the progress bar
        // renders this phase as indeterminate (a tick per round, no percentage).
        $this->walk($roots, false, $codebase, $progress);

        $this->sinks = [];
        $this->sink_reachable = [];
        $this->summaries = [];
        $this->provisional_summaries = [];
        $this->summary_seeds = [];
        $this->reported_flows = [];

        $progress->taskDone(0);
    }

    /**
     * Propagates the flows $roots to a fixed point: each node is reached once per state of the
     * flows reaching it (see TaintFlowState), of which there are finitely many (see admit()).
     *
     * The flows from the taint sources report the sinks they reach. A specialized function-like
     * has only its entry nodes (e.g. parameters) and exit nodes (e.g. return) specialized to each
     * call: its body is shared by every call. A flow entering it through a call doesn't walk the
     * body: the body is walked once from each entry node, for all calls, $summarizing it -- the
     * walk returns the exits and sinks it reaches (see summarize()). A call applies those to the
     * flow entering it, and goes on from the exits at its own call site.
     *
     * @param list<DataFlowNode> $roots
     * @return array<string, array{DataFlowNode, bool}>
     */
    private function walk(array $roots, bool $summarizing, Codebase $codebase, ?Progress $progress = null): array
    {
        $config = $codebase->config;
        $project_analyzer = ProjectAnalyzer::getInstance();

        // Node id => state key => true
        $visited = [];
        // Node id => state key => flow
        $flows = [];
        $outcomes = [];

        foreach ($roots as $root) {
            $flows[$root->id][$root->getFlowState()->key] = $root;
        }

        while ($flows) {
            ksort($flows);

            foreach ($flows as $id => $states) {
                foreach ($states as $key => $_) {
                    $visited[$id][$key] = true;
                }
            }

            $next_flows = [];

            foreach ($flows as $states) {
                foreach ($states as $flow) {
                    if ($flow->code_location
                        && $project_analyzer->canReportIssues($flow->code_location->file_path)
                        && !$config->reportIssueInFile('TaintedInput', $flow->code_location->file_path)
                    ) {
                        continue;
                    }

                    $this->propagate($flow, $summarizing, $outcomes, $visited, $next_flows, $codebase);
                }
            }

            $flows = $next_flows;

            $progress?->taskDone(0);
        }

        return $outcomes;
    }

    /**
     * Adds the flows $flow goes on as from its node to $next_flows, unless $visited, and reports (or,
     * $summarizing, adds to $outcomes) the sinks it reaches.
     *
     * @param array<string, array{DataFlowNode, bool}> $outcomes
     * @param-out array<string, array{DataFlowNode, bool}> $outcomes
     * @param array<string, array<string, true>> $visited
     * @param array<string, array<string, DataFlowNode>> $next_flows
     * @param-out array<string, array<string, DataFlowNode>> $next_flows
     */
    private function propagate(
        DataFlowNode $flow,
        bool $summarizing,
        array &$outcomes,
        array $visited,
        array &$next_flows,
        Codebase $codebase,
    ): void {
        $state = $flow->getFlowState();

        if (isset($this->forward_edges[$flow->id])) {
            foreach ($this->forward_edges[$flow->id] as $to_id => $path) {
                // Skip nodes from which no sink is reachable: they cannot contribute
                // to any issue, so there is no point propagating taint through them.
                if (!isset($this->nodes[$to_id]) || !isset($this->sink_reachable[$to_id])) {
                    continue;
                }

                $next_state = $state->withPath($path);

                if ($next_state === null) {
                    continue;
                }

                if (isset($this->sinks[$to_id])) {
                    $this->reachSink(
                        $this->nodes[$to_id]->withFlow($flow, $path->type, $next_state),
                        $summarizing,
                        $outcomes,
                        $codebase,
                    );
                }

                $next_state = self::admit($to_id, $next_state, $visited, $next_flows);

                if ($next_state !== null) {
                    $next_flows[$to_id][$next_state->key] = $this->nodes[$to_id]->withFlow(
                        $flow,
                        $path->type,
                        $next_state,
                    );
                }
            }

            return;
        }

        // a call to a specialized function-like entering its body
        if ($flow->specialization_key !== null
            && $flow->unspecialized_id !== null
            && isset($this->specialized_calls[$flow->specialization_key])
        ) {
            if (!isset($this->forward_edges[$flow->unspecialized_id])) {
                return;
            }

            // a call speculatively specialized to a function-like that turned out impure is walked
            // through like an unspecialized one
            if (isset($this->despecialized_calls[$flow->specialization_key])) {
                $next_state = self::admit($flow->unspecialized_id, $state, $visited, $next_flows);

                if ($next_state !== null) {
                    $next_flows[$flow->unspecialized_id][$next_state->key] = $flow->withSpecialization(
                        $flow->unspecialized_id,
                        null,
                        null,
                        $next_state,
                    );
                }

                return;
            }

            foreach ($this->summarize($flow, $flow->unspecialized_id, $codebase) as [$reached, $is_sink]) {
                $next_state = $state->then($reached->getFlowState());

                if ($next_state === null) {
                    continue;
                }

                if ($is_sink) {
                    $this->reachSink(
                        $this->replay($flow, $reached, $next_state, $summarizing),
                        $summarizing,
                        $outcomes,
                        $codebase,
                    );
                    continue;
                }

                $specialized_id = $this->specializations[$reached->id][$flow->specialization_key] ?? null;

                if ($specialized_id !== null) {
                    $next_state = self::admit($specialized_id, $next_state, $visited, $next_flows);

                    if ($next_state !== null) {
                        $next_flows[$specialized_id][$next_state->key]
                            = $this->replay($flow, $reached, $next_state, $summarizing)
                                ->withSpecialization($specialized_id, $reached->id, $flow->specialization_key);
                    }
                } elseif ($summarizing) {
                    // the flow left the body through another node specialized to calls, e.g. after
                    // passing through a static property: maybe one of the calls the body summarized
                    // by $outcomes is made from
                    $outcomes['exit ' . $reached->id . ' ' . $next_state->key]
                        ??= [$this->replay($flow, $reached, $next_state, true), false];
                }
            }

            return;
        }

        // a node specialized to calls, e.g. the return of a specialized function-like
        if (isset($this->specializations[$flow->id])) {
            $exits_through_call = false;

            foreach ($this->specializations[$flow->id] as $specialization_key => $specialized_id) {
                // when making a summary, the flow leaves through the call sites of the calls entering
                // the summarized body (see propagate()), else through all of them
                if ($summarizing && !isset($this->despecialized_calls[$specialization_key])) {
                    $exits_through_call = true;
                    continue;
                }

                $next_state = self::admit($specialized_id, $state, $visited, $next_flows);

                if ($next_state !== null) {
                    $next_flows[$specialized_id][$next_state->key] = $flow->withSpecialization(
                        $specialized_id,
                        $flow->id,
                        $specialization_key,
                        $next_state,
                    );
                }
            }

            if ($exits_through_call) {
                $outcomes['exit ' . $flow->id . ' ' . $state->key] ??= [$flow, false];
            }
        }
    }

    /**
     * The state $state takes at node $id, unless the walk is already there in it. Past
     * MAX_NODE_STATES states at a node, it is widened (see TaintFlowState::widened()), so that a
     * node takes finitely few even when the flows reaching it wrap their values under many keys.
     *
     * @param array<string, array<string, true>> $visited
     * @param array<string, array<string, DataFlowNode>> $next_flows
     * @psalm-pure
     */
    private static function admit(
        string $id,
        TaintFlowState $state,
        array $visited,
        array $next_flows,
    ): ?TaintFlowState {
        if (isset($visited[$id][$state->key]) || isset($next_flows[$id][$state->key])) {
            return null;
        }

        if (count($visited[$id] ?? []) + count($next_flows[$id] ?? []) < self::MAX_NODE_STATES) {
            return $state;
        }

        $state = $state->widened();

        return isset($visited[$id][$state->key]) || isset($next_flows[$id][$state->key]) ? null : $state;
    }

    /**
     * What the flow entering $unspecialized_id, the shared entry node of the calls to a
     * specialized function-like, reaches: the exits through calls and the sinks, with the state
     * relative to it of the flow reaching them (see TaintFlowState) and its trace from
     * $unspecialized_id.
     *
     * A recursion summarizes itself: the summaries made from one another are made again from what
     * each other reaches so far, until that stops growing. A summary made from one still being
     * made is provisional: it holds until a summary it was made from is made again, which starts
     * from it.
     *
     * @return array<string, array{DataFlowNode, bool}>
     */
    private function summarize(DataFlowNode $call, string $unspecialized_id, Codebase $codebase): array
    {
        if (isset($this->summaries[$unspecialized_id])) {
            return $this->summaries[$unspecialized_id];
        }

        if (isset($this->summary_depths[$unspecialized_id])) {
            $depth = $this->summary_depths[$unspecialized_id];
            $this->least_used_depth = min($this->least_used_depth, $depth);
            $this->greatest_used_depth = max($this->greatest_used_depth, $depth);

            return $this->partial_summaries[$unspecialized_id];
        }

        if (isset($this->provisional_summaries[$unspecialized_id])) {
            [$outcomes, $least_used_depth, $greatest_used_depth] = $this->provisional_summaries[$unspecialized_id];
            $this->least_used_depth = min($this->least_used_depth, $least_used_depth);
            $this->greatest_used_depth = max($this->greatest_used_depth, $greatest_used_depth);

            return $outcomes;
        }

        $depth = count($this->summary_depths);
        $this->summary_depths[$unspecialized_id] = $depth;
        $outer_least_used_depth = $this->least_used_depth;
        $outer_greatest_used_depth = $this->greatest_used_depth;

        $root = ($this->nodes[$unspecialized_id] ?? $call->withSpecialization($unspecialized_id, null, null))
            ->withFlowState(TaintFlowState::fromEntry());

        $outcomes = $this->summary_seeds[$unspecialized_id] ?? [];
        unset($this->summary_seeds[$unspecialized_id]);

        do {
            // the summaries made from this one are made again
            $this->dropProvisionalSummaries($depth);

            $this->least_used_depth = PHP_INT_MAX;
            $this->greatest_used_depth = -1;
            $previous_outcomes = $outcomes;
            $this->partial_summaries[$unspecialized_id] = $outcomes;

            $outcomes = $this->walk([$root], true, $codebase) + $previous_outcomes;
        } while ($this->least_used_depth <= $depth && count($outcomes) > count($previous_outcomes));

        unset($this->summary_depths[$unspecialized_id], $this->partial_summaries[$unspecialized_id]);

        if ($this->least_used_depth < $depth) {
            // the summaries made from it hold as long as it does
            foreach ($this->provisional_summaries as $id => [, $least_used_depth]) {
                if ($least_used_depth >= $depth) {
                    $this->provisional_summaries[$id][1] = $this->least_used_depth;
                }
            }

            $this->provisional_summaries[$unspecialized_id] = [
                $outcomes,
                $this->least_used_depth,
                $this->greatest_used_depth,
            ];
            $this->least_used_depth = min($outer_least_used_depth, $this->least_used_depth);
            $this->greatest_used_depth = max($outer_greatest_used_depth, $this->greatest_used_depth);

            return $outcomes;
        }

        // so are the summaries made from it alone
        foreach ($this->provisional_summaries as $id => [$provisional_outcomes, $least_used_depth]) {
            if ($least_used_depth >= $depth) {
                $this->summaries[$id] = $provisional_outcomes;
                unset($this->provisional_summaries[$id]);
            }
        }

        $this->summaries[$unspecialized_id] = $outcomes;
        $this->least_used_depth = $outer_least_used_depth;
        $this->greatest_used_depth = $outer_greatest_used_depth;

        return $outcomes;
    }

    /**
     * Drops the provisional summaries made from a summary at least $depth deep: they are made again,
     * starting from what they reached.
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function dropProvisionalSummaries(int $depth): void
    {
        foreach ($this->provisional_summaries as $id => [$outcomes, , $greatest_used_depth]) {
            if ($greatest_used_depth >= $depth) {
                $this->summary_seeds[$id] = $outcomes + ($this->summary_seeds[$id] ?? []);
                unset($this->provisional_summaries[$id]);
            }
        }
    }

    /**
     * $reached, reached by the flow summarized from an entry node (see summarize()), as reached by
     * $caller entering it, in $state: its trace from the entry node is replayed on top of the
     * caller's -- unless $summarizing, where the trace only keeps the step into $reached: a summary
     * replaying the traces of the summaries it applies would make traces grow exponentially with
     * the nesting of calls. A sink is still reported from the node the flow reached it from.
     *
     * @psalm-mutation-free
     */
    private function replay(
        DataFlowNode $caller,
        DataFlowNode $reached,
        TaintFlowState $state,
        bool $summarizing,
    ): DataFlowNode {
        $predecessor = $reached->taintSource;

        if ($summarizing && $predecessor !== null) {
            if ($predecessor->taintSource !== null) {
                $caller = $predecessor->withFlow(
                    $caller,
                    $predecessor->path_types ? $predecessor->path_types[0] : '',
                    $predecessor->getFlowState(),
                );
            }

            return $reached->withFlow($caller, $reached->path_types ? $reached->path_types[0] : '', $state);
        }

        $walk = [];

        for ($node = $reached; $node->taintSource !== null; $node = $node->taintSource) {
            $walk[] = $node;
        }

        $trace = $caller;

        for ($i = count($walk) - 1; $i >= 0; $i--) {
            $node = $walk[$i];
            $trace = $node->withFlow(
                $trace,
                $node->path_types ? $node->path_types[0] : '',
                $i === 0 ? $state : $node->getFlowState(),
            );
        }

        return $trace;
    }

    /**
     * $flow reached a sink: reports it, or, $summarizing, adds it to $outcomes.
     *
     * @param array<string, array{DataFlowNode, bool}> $outcomes
     * @param-out array<string, array{DataFlowNode, bool}> $outcomes
     */
    private function reachSink(DataFlowNode $flow, bool $summarizing, array &$outcomes, Codebase $codebase): void
    {
        $state = $flow->getFlowState();
        $sink = $this->sinks[$flow->id];
        $predecessor = $flow->taintSource;

        if ($predecessor === null) {
            return;
        }

        if ($summarizing) {
            if (($state->kept_taints | $state->taints) & $sink->taints) {
                $outcomes['sink ' . $flow->id . ' ' . $predecessor->id . ' ' . $state->key] ??= [$flow, true];
            }

            return;
        }

        $matching_taints = $sink->taints & $state->taints;

        if ($matching_taints && $predecessor->code_location) {
            $this->reportTaintedFlowOnce($predecessor, $sink, $matching_taints, $codebase->config, $codebase);
        }
    }

    /**
     * Computes the set of node ids from which at least one sink is reachable.
     *
     * The search runs backwards from the sinks over the forward edges, treating
     * specialization links as bidirectional so that a node whose specialized or
     * de-specialized form can reach a sink is itself kept (the resolution walk
     * maps freely between the two).
     *
     * @param array<string, DataFlowNode> $sources
     * @param array<string, DataFlowNode> $sinks
     * @return array<string, true>
     * @psalm-capabilities read-props
     */
    private function getSinkReachableNodes(array $sources, array $sinks): array
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

        return $reachable;
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
        if ($predecessor->code_location === null) {
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

        if ($sink->code_location
            && $config->reportIssueInFile('TaintedInput', $sink->code_location->file_path)
        ) {
            $issue_location = $sink->code_location;
        } else {
            $issue_location = $predecessor->code_location;
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
