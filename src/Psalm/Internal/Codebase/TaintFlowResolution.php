<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\Path;
use Psalm\Progress\Progress;
use Psalm\Type\TaintKind;

use function array_key_exists;
use function array_key_first;
use function array_merge;
use function array_pop;
use function array_slice;
use function count;
use function implode;
use function ksort;
use function max;
use function min;
use function str_starts_with;
use function strlen;
use function strpos;
use function substr;

use const PHP_INT_MAX;
use const SORT_STRING;

/**
 * One resolution of a taint flow graph: finds the flows from its sources into its sinks, and reports them.
 *
 * A flow is in a state: the node it reached, the specialized call entry (see enterSpecializedCall()) whose
 * body it is walking if any (its context), and its open assignments (see getNextOpenAssignments()). The
 * resolution walks each state once, so that it stays finite and cheap:
 *
 * - The taints of a state are not part of it: they are the union of the taints of all the flows reaching it.
 *   Which edges a flow takes doesn't depend on its taints, so a state is walked again only when it gets
 *   taints it didn't have (bit-vector propagation).
 * - Within the body walk of an entry, those taints are relative to the taints of the calls entering it: a
 *   state keeps the taints of the call it doesn't remove ($state_kept) and adds its own ($state_added). For
 *   any call, the taints of a state of the walk are `($call_taints & $kept) | $added`. So a body walk is
 *   shared by every call, whatever its taints.
 * - Within the body walk of an entry, the open assignments are relative to those of the calls entering it
 *   too: a state knows the assignments made in the walk, and how many of those of the call it closed. A
 *   fetch observing one of those of the call goes on only for the calls whose assignment it doesn't ignore,
 *   in a filter of the entry for them (see getFilter()). So a body walk is shared by every call, whatever
 *   its open assignments.
 * - Where flows converge in many states, at a node many contexts or open assignments reach (a property, a
 *   parameter of a function called with many different arrays, ...), what is reachable from it is walked
 *   once for each innermost open assignment of the flows, relative to them, like the body of a specialized
 *   call (see enterConvergence()).
 * - Nothing is kept of a flow but its state and, to rebuild the traces of the flows reported, the state it
 *   came from (see buildTrace()).
 *
 * @internal
 */
final class TaintFlowResolution
{
    /**
     * The expression types whose assignments and fetches shouldIgnoreFetch() matches, in the order
     * appendPathType() matches a fetch against them. The assignments and fetches of array keys are those of
     * array values: the key and the value of an item are at the same level of an array, so the open
     * assignments of both are one stack (see getPathTypeEffects()).
     */
    private const FAMILIES = ['arrayvalue', 'property'];
    private const ARRAY_FAMILY = 0;

    /**
     * The class (see getClass()) of an open assignment of an array key: a fetch of an array value ignores
     * it, a fetch of an array key doesn't.
     */
    private const KEY_CLASS = 'key';

    /**
     * How many of its innermost open assignments of each expression type a flow outside of any context keeps
     * at most: a loop can wrap a value further every time. Past those, a fetch of an open assignment the
     * flow forgot isn't ignored (see getNextOpenAssignments()): the flow may take taints it doesn't have
     * there, but takes all those it has.
     */
    private const MAX_OPEN_ASSIGNMENT_DEPTH = 8;

    /**
     * How many of them a flow in the walk of an entry keeps at most, counting those of the call entering it
     * it closed. Fewer: a walk is told apart in a filter for each open assignment of the calls entering it a
     * fetch observes (see getFilter()), and the deeper one the more.
     */
    private const MAX_CALL_OPEN_ASSIGNMENT_DEPTH = 4;

    /**
     * The number of open assignments of the call entering its context a flow closed (see
     * $open_assignments) when it doesn't know them anymore, and when it is in no context
     */
    private const FORGOTTEN = self::MAX_CALL_OPEN_ASSIGNMENT_DEPTH + 1;
    private const NO_CALL = -1;

    /**
     * The open assignments of a flow outside of any context without any, and of the flow starting the body
     * walk of an entry (see internOpenAssignments())
     */
    private const NO_OPEN_ASSIGNMENTS = 0;
    private const CALL_OPEN_ASSIGNMENTS = 1;

    /**
     * What getNextOpenAssignments() returns for an edge shouldIgnoreFetch() ignores, and for one whose
     * fetch observes an open assignment of the call entering the context
     */
    private const IGNORED = -1;
    private const OBSERVES_CALL = -2;

    /**
     * How many states a node must have for the flows reaching it in more states to be walked from it once,
     * relative to them (see enterConvergence()). Each convergence a flow goes through takes away one of the
     * CONVERGENCE_LEVELS it finds the open assignments it made before them through: fewer convergences, of
     * flows reaching a node in more states, keep more of those, at little cost.
     */
    private const CONVERGING_STATES = 12;

    /**
     * How many states a node must have for the flows reaching it in more to be widened (see
     * widenOpenAssignments()). The flows reaching a node in a body walk differ by their open assignments, whose
     * number can grow with the product of the keys and depths of the arrays they went through: recursive functions
     * putting what they return for an element back under its key, components passing arrays of options on through
     * many levels, ... Convergence (see enterConvergence()) doesn't help in a body walk the flows of a single call
     * reach, since it leaves through all the call sites. A node that holds that many states lets the flows reaching
     * it in more forget what they would only use to tell apart deeper fetches.
     */
    private const WIDENING_STATES = 1024;

    /**
     * The kinds of entries: specialized call entries, and convergences (see enterConvergence())
     */
    private const ENTRY_CALL = 0;
    private const ENTRY_CONVERGENCE = 1;

    /**
     * Through how many convergences a flow entering an entry finds the class of an open assignment of it made
     * before them, that a fetch in the walk of the entry observes (see getAssignmentClass()). Values go
     * through many convergences on large code bases (properties, parameters of functions called with many
     * different arrays, ...): past those, no fetch ignores the open assignment.
     */
    private const CONVERGENCE_LEVELS = 12;

    /**
     * How many convergences of a node know the innermost open assignments of the flows entering them at most
     * (see getConvergenceOpenAssignments())
     */
    private const MAX_CONVERGENCE_KEYS = 32;

    /**
     * The bits of a packed observable depth (see computeObservableDepths()) for each expression type
     */
    private const DEPTH_BITS = 8;
    private const DEPTH_MASK = (1 << self::DEPTH_BITS) - 1;

    /**
     * The taints a state of a body walk keeps of the call when it starts: all
     */
    private const ALL_TAINTS = -1;

    /**
     * Which taints of a state the trace of one of its taints follows (see buildTrace())
     */
    private const TRACE_KEPT = 1;
    private const TRACE_ADDED = 2;
    private const TRACE_ANY = 3;

    /** @var array<string, true> */
    private array $sink_reachable = [];

    /**
     * Node id => the nodes linked to it by a specialization, either way
     *
     * @var array<string, array<string, true>>
     */
    private array $specialization_links = [];

    /**
     * Node id => observable depths, packed (see computeObservableDepths()); 0 if absent
     *
     * @var array<string, int>
     */
    private array $observable_depths = [];

    /**
     * The ids of the nodes from which a flow can reach an edge adding taints: elsewhere, a flow without
     * taints can't report anything
     *
     * @var array<string, true>
     */
    private array $taint_adding_reachable = [];

    /**
     * File path => whether the flows from its nodes are not followed (see isWalkedFrom())
     *
     * @var array<string, bool>
     */
    private array $unwalked_files = [];

    /**
     * Node id => node, for the nodes not registered in the graph (see getNode())
     *
     * @var array<string, ?DataFlowNode>
     */
    private array $derived_nodes = [];

    /** @var array<string, int> */
    private array $path_type_ids = [];

    /** @var list<string> */
    private array $path_types = [];

    /**
     * Path type => [
     *     expression type whose innermost open assignment decides whether to ignore it, or -1,
     *     what the key of that assignment must be not to ignore it, or null if any key but '' ignores it,
     *     expression type whose innermost open assignment it closes, or -1,
     *     expression type whose open assignments it adds to, or -1,
     * ]
     *
     * @var list<array{int, ?string, int, int}>
     */
    private array $path_type_effects = [];

    /**
     * Open assignments => key (see internOpenAssignments())
     *
     * @var array<string, int>
     */
    private array $open_assignment_ids = [];

    /**
     * Open assignments key => [
     *     for each expression type, the open assignments the flow made from the outermost to the innermost,
     *     as path type ids,
     *     for each expression type, how many of those of the call entering its context it closed, FORGOTTEN
     *     if it doesn't know, or NO_CALL outside of any context,
     * ]
     *
     * @var list<array{array<int, list<int>>, array<int, int>}>
     */
    private array $open_assignments = [];

    /**
     * Open assignments key => path type => the open assignments after taking an edge of that type, IGNORED
     * or OBSERVES_CALL
     *
     * @var array<int, array<int, int>>
     */
    private array $open_assignment_transitions = [];

    /**
     * Open assignments key of a call => open assignments key of a flow of its body walk => the open
     * assignments of that flow in the context of the call
     *
     * @var array<int, array<int, int>>
     */
    private array $open_assignment_compositions = [];

    /**
     * Open assignments key => packed observable depths => the open assignments a fetch can observe
     *
     * @var array<int, array<int, int>>
     */
    private array $open_assignment_truncations = [];

    /**
     * Open assignments key => the open assignments widened (see widenOpenAssignments())
     *
     * @var array<int, int>
     */
    private array $open_assignment_widenings = [];

    /*
     * The states, as parallel lists indexed by state id
     */

    /**
     * Node id => (context + 1) << 32 | open assignments => state
     *
     * @var array<string, array<int, int>>
     */
    private array $state_ids = [];

    /** @var list<string> */
    private array $state_nodes = [];

    /**
     * The entry whose body the flow is in, or -1 outside of any
     *
     * @var list<int>
     */
    private array $state_contexts = [];

    /** @var list<int> */
    private array $state_open_assignments = [];

    /**
     * The taints of the call entering the context the flows reaching the state keep (0 outside of any)
     *
     * @var list<int>
     */
    private array $state_kept = [];

    /**
     * The taints the flows reaching the state add (outside of any context, all of their taints)
     *
     * @var list<int>
     */
    private array $state_added = [];

    /*
     * How each state was reached first (see buildTrace()): the state it came from (-1 for a taint source),
     * the calls of the state it came from it left (a caller link, see getLink()), the type of the edge it
     * took (-1 for none: the state takes the place of the one it came from in the trace), and when (a
     * sequence number shared with $later_reaches).
     */

    /** @var list<int> */
    private array $state_predecessors = [];

    /** @var array<int, int> */
    private array $state_links = [];

    /** @var list<int> */
    private array $state_path_types = [];

    /** @var list<int> */
    private array $state_sequence = [];

    /**
     * State => how the flows that gave it more taints reached it:
     * list of [predecessor, caller link, path type, sequence number, kept taints added, added taints added]
     *
     * @var array<int, list<array{int, int, int, int, int, int}>>
     */
    private array $later_reaches = [];

    private int $sequence = 0;

    /** @var list<int> */
    private array $queue = [];

    /** @var array<int, true> */
    private array $queued = [];

    /*
     * Specialized call entries, see enterSpecializedCall()
     */

    /**
     * Kind . ' ' . node id . ' ' . open assignments its walk starts with => entry
     *
     * @var array<string, int>
     */
    private array $entry_ids = [];

    /**
     * Entry => the node entered
     *
     * @var list<string>
     */
    private array $entry_nodes = [];

    /**
     * Entry => what it is: ENTRY_CALL or ENTRY_CONVERGENCE
     *
     * @var list<self::ENTRY_*>
     */
    private array $entry_kinds = [];

    /**
     * Entry => what it knows of the open assignments of the calls entering it, if it is a filter of another
     * (see getFilter() and dependOnClass()): position (see getPosition()) =>
     * [the class of the open assignment there (see getAssignmentClass()) if known, the keys whose fetches
     * don't ignore it => true]
     *
     * @var list<array<int, array{?string, array<string, true>}>>
     */
    private array $entry_facts = [];

    /**
     * Entry => expression type . ' ' . depth . ' ' . fetched key => the filter of the entry for the calls
     * whose open assignment there a fetch of that key doesn't ignore (see getFilter())
     *
     * @var array<int, array<string, int>>
     */
    private array $entry_filters = [];

    /**
     * Filter => [expression type, depth, fetched key] of the fetch the calls entering it pass
     *
     * @var array<int, array{int, int, string}>
     */
    private array $filter_fetches = [];

    /**
     * Entry => position (see getPosition()) => class => the filter of the entry for the calls whose open
     * assignment there is of that class (see dependOnClass())
     *
     * @var array<int, array<int, array<string, int>>>
     */
    private array $entry_class_filters = [];

    /**
     * Entry => position (see getPosition()) => the states of its walk that depend on the class of the open
     * assignment there of the calls entering it => true: they go on in each of those filters
     *
     * @var array<int, array<int, array<int, true>>>
     */
    private array $entry_class_dependents = [];

    /**
     * Entry => position (see getPosition()) => through how many convergences the calls entering it that don't
     * know their class of open assignment there find it (see dependOnClass())
     *
     * @var array<int, array<int, int>>
     */
    private array $entry_class_levels = [];

    /**
     * Node id => some of the specialized call entries whose walks reached it => true: a node the walks of
     * several reach is shared by them, like a property (see enterConvergence())
     *
     * @var array<string, array<int, true>>
     */
    private array $call_entries_reaching = [];

    /**
     * Node id => the open assignments the convergences of that node start with (see
     * getConvergenceOpenAssignments()) => true
     *
     * @var array<string, array<int, true>>
     */
    private array $convergence_open_assignments = [];

    /**
     * Root state => its entry
     *
     * @var array<int, int>
     */
    private array $root_entries = [];

    /**
     * Entry => the entry it is a filter of, or itself
     *
     * @var list<int>
     */
    private array $entry_bases = [];

    /**
     * Unspecialized node entered by a specialized call => exit node id => whether the exit is one of the same
     * function-like (see isExitOfEntered())
     *
     * @var array<string, array<string, bool>>
     */
    private array $exits_of_entered = [];

    /**
     * Entry => the taints of the flows entering it (see getUnionTaints())
     *
     * @var array<int, int>
     */
    private array $entry_taints = [];

    /**
     * Entry => taint => the flow entering it that gave it that taint first, which traces go back to (see
     * buildTrace())
     *
     * @var array<int, array<int, int>>
     */
    private array $entry_taint_callers = [];

    /**
     * Entry => the flows of its walk entering another entry: state => that entry => true
     *
     * @var array<int, array<int, array<int, true>>>
     */
    private array $entry_taint_dependents = [];

    /**
     * Entry => the calls entering it: the state of the specialized node entered => the specialization key of
     * the call (null for a convergence)
     *
     * @var array<int, array<int, ?string>>
     */
    private array $entry_callers = [];

    /**
     * Entry => exit node id and open assignments => [exit node id, open assignments, kept, added, how
     * each of them was reached: list of [state, caller link, kept, added]]
     *
     * @var array<int, array<string, array{string, int, int, int, list<array{int, int, int, int}>}>>
     */
    private array $entry_exits = [];

    /**
     * Entry => sink id and predecessor node id => [sink id, kept, added, how each of them was reached:
     * list of [state, caller link, kept, added]]
     *
     * @var array<int, array<string, array{string, int, int, list<array{int, int, int, int}>}>>
     */
    private array $entry_sinks = [];

    /**
     * Caller links: the state of the outermost call a trace returns to, and the link of the calls inside it
     * (0 for none): see buildTrace()
     *
     * @var array<int, array{int, int}>
     */
    private array $links = [0 => [-1, 0]];

    /** @var array<string, int> */
    private array $link_ids = [];

    /**
     * Sink id => predecessor state . ' ' . caller link => taints already looked at
     *
     * @var array<string, array<string, int>>
     */
    private array $reported_pointers = [];

    /**
     * Sink id => predecessor id => origin id => taints already reported for that flow
     *
     * @var array<string, array<string, array<string, int>>>
     */
    private array $reported_flows = [];

    /**
     * @param array<string, array<string, Path>> $forward_edges
     * @param array<string, DataFlowNode> $nodes
     * @param array<string, DataFlowNode> $sources
     * @param array<string, DataFlowNode> $sinks
     * @param array<string, array<string, string>> $specializations
     * @param array<string, true> $specialized_calls
     * @param array<string, true> $despecialized_calls
     * @psalm-capabilities read-props
     */
    public function __construct(
        private readonly TaintFlowGraph $graph,
        private array $forward_edges,
        private readonly array $nodes,
        private readonly array $sources,
        private readonly array $sinks,
        private array $specializations,
        private readonly array $specialized_calls,
        private readonly array $despecialized_calls,
        private readonly Config $config,
        private readonly ProjectAnalyzer $project_analyzer,
        private readonly Codebase $codebase,
    ) {
        // NO_OPEN_ASSIGNMENTS and CALL_OPEN_ASSIGNMENTS
        $this->internOpenAssignments([[], []], [self::NO_CALL, self::NO_CALL]);
        $this->internOpenAssignments([[], []], [0, 0]);
    }

    public function resolve(Progress $progress): void
    {
        $this->prepareGraph();

        $sources = $this->sources;
        ksort($sources);

        foreach ($sources as $id => $source) {
            if (!isset($this->sink_reachable[$id]) || $source->taints === 0) {
                continue;
            }

            $open_assignments = self::NO_OPEN_ASSIGNMENTS;

            foreach ($source->path_types as $path_type) {
                $next = $this->getNextOpenAssignments($open_assignments, $this->getPathTypeId($path_type));

                if ($next >= 0) {
                    $open_assignments = $next;
                }
            }

            $this->reach($id, -1, $open_assignments, 0, $source->taints, -1, 0, -1);
        }

        // Each round walks the states reached, or given more taints, in the previous one. The states are
        // finitely many: there is an entry per unspecialized node entered and per convergence, a filter per
        // open assignment and fetched key or class (see getFilter() and dependOnClass()), and finitely many
        // open assignments a flow knows (see MAX_OPEN_ASSIGNMENT_DEPTH). Taints only grow. So the resolution
        // reaches a fixed point.
        while ($this->queue) {
            $queue = $this->queue;
            $this->queue = [];

            foreach ($queue as $state) {
                unset($this->queued[$state]);
                $this->walk($state);
            }

            $progress->taskDone(0);
        }
    }

    /**
     * Restricts the resolution to the nodes from which a sink is reachable, and computes what it needs to
     * know of the graph beforehand.
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function prepareGraph(): void
    {
        $reverse = [];

        foreach ($this->forward_edges as $from_id => $destinations) {
            $this->linkSpecialization($from_id);

            foreach ($destinations as $to_id => $_) {
                $reverse[$to_id][$from_id] = true;
                $this->linkSpecialization($to_id);
            }
        }

        // Specialization links taken from the unspecialized_id of the nodes, rather than from their ids, as
        // well (see linkSpecialization()). Sinks are registered in $this->nodes too, but sources are not.
        foreach ($this->nodes as $node) {
            $this->linkSpecializationByField($node);
        }

        foreach ($this->sources as $node) {
            $this->linkSpecializationByField($node);
        }

        // The search runs backwards from the sinks over the forward edges, treating specialization links
        // as bidirectional so that a node whose specialized or de-specialized form can reach a sink is
        // itself kept (the resolution walk maps freely between the two).
        $queue = [];

        foreach ($this->sinks as $id => $_) {
            $this->sink_reachable[$id] = true;
            $queue[] = $id;
        }

        while ($queue) {
            $id = array_pop($queue);

            foreach ($reverse[$id] ?? [] as $from_id => $_) {
                if (!isset($this->sink_reachable[$from_id])) {
                    $this->sink_reachable[$from_id] = true;
                    $queue[] = $from_id;
                }
            }

            foreach ($this->specialization_links[$id] ?? [] as $linked_id => $_) {
                if (!isset($this->sink_reachable[$linked_id])) {
                    $this->sink_reachable[$linked_id] = true;
                    $queue[] = $linked_id;
                }
            }
        }

        foreach ($this->forward_edges as $from_id => $destinations) {
            if (!isset($this->sink_reachable[$from_id])) {
                unset($this->forward_edges[$from_id]);

                continue;
            }

            // Only the edges a flow can take, in the same order whichever order the analysis of the files
            // added them in: the traces of the flows reported depend on the order the states are walked in.
            foreach ($destinations as $to_id => $_) {
                if (!isset($this->sink_reachable[$to_id]) || !isset($this->nodes[$to_id])) {
                    unset($destinations[$to_id]);
                }
            }

            ksort($destinations, SORT_STRING);
            $this->forward_edges[$from_id] = $destinations;
        }

        // The states, and so where flows converge (see enterConvergence()) and the traces of the flows
        // reported, depend on the order the call sites of a node are left through (see walk()).
        foreach ($this->specializations as $id => $specializations) {
            ksort($specializations, SORT_STRING);
            $this->specializations[$id] = $specializations;
        }

        $this->computeObservableDepths($reverse);
        $this->computeTaintAddingReachable($reverse);
    }

    /**
     * If $id is a specialized node id, links it and its unspecialized base: during resolution the walk maps
     * freely between a node's specialized and de-specialized forms. The link is derived from the id string
     * because specialized nodes are not always registered in $this->specializations (e.g. specialized
     * source and sink nodes).
     *
     * @psalm-external-mutation-free
     */
    private function linkSpecialization(string $id): void
    {
        $pos = strpos($id, TaintFlowGraph::SPECIALIZATION_SEPARATOR);

        if ($pos === false) {
            return;
        }

        $unspecialized_id = substr($id, 0, $pos);

        $this->specialization_links[$id][$unspecialized_id] = true;
        $this->specialization_links[$unspecialized_id][$id] = true;
    }

    /**
     * Like linkSpecialization(), but derives the unspecialized form from the node's unspecialized_id, so
     * the link is recorded even for a specialized node whose id was not built with the separator.
     *
     * @psalm-external-mutation-free
     */
    private function linkSpecializationByField(DataFlowNode $node): void
    {
        if ($node->unspecialized_id === null) {
            return;
        }

        $this->specialization_links[$node->id][$node->unspecialized_id] = true;
        $this->specialization_links[$node->unspecialized_id][$node->id] = true;
    }

    /**
     * Computes, for each node, how many of the innermost open assignments of each expression type a fetch
     * reachable from it can observe: a flow forgets the others there (see truncateOpenAssignments()), as
     * they can't decide where it goes (see getNextOpenAssignments()).
     *
     * An assignment wraps the value one level deeper, and a fetch unwraps it one level: one of a given array
     * key or property (or an array key fetch) observes the innermost open assignment of its expression type,
     * and then any fetch closes it. So the depth observable from a node is the largest, over the paths from
     * it, of the depths below its innermost open assignment a fetch on the path observes. The paths follow
     * the specialization links both ways, which covers the calls entered and exited.
     *
     * The depths are capped at MAX_OPEN_ASSIGNMENT_DEPTH, which also bounds a cycle of fetches.
     *
     * @param array<string, array<string, true>> $reverse
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function computeObservableDepths(array $reverse): void
    {
        $queue = [];

        foreach ($this->forward_edges as $from_id => $destinations) {
            $depths = 0;

            foreach ($destinations as $path) {
                $depths = self::maxDepths($depths, $this->getObservedDepths(0, $this->getPathTypeId($path->type)));
            }

            if ($depths !== 0) {
                $this->observable_depths[$from_id] = $depths;
                $queue[] = $from_id;
            }
        }

        // whenever the depths of a node grow, so may those of the nodes with an edge to it
        while ($queue) {
            $id = array_pop($queue);
            $depths = $this->observable_depths[$id];

            foreach ($reverse[$id] ?? [] as $from_id => $_) {
                if (isset($this->forward_edges[$from_id][$id])) {
                    $this->raiseObservableDepths(
                        $queue,
                        $from_id,
                        $this->getObservedDepths(
                            $depths,
                            $this->getPathTypeId($this->forward_edges[$from_id][$id]->type),
                        ),
                    );
                }
            }

            foreach ($this->specialization_links[$id] ?? [] as $linked_id => $_) {
                $this->raiseObservableDepths($queue, $linked_id, $depths);
            }
        }
    }

    /**
     * @param list<string> $queue
     * @param-out list<string> $queue
     * @psalm-external-mutation-free
     */
    private function raiseObservableDepths(array &$queue, string $id, int $depths): void
    {
        $previous = $this->observable_depths[$id] ?? 0;
        $depths = self::maxDepths($previous, $depths);

        if ($depths !== $previous) {
            $this->observable_depths[$id] = $depths;
            $queue[] = $id;
        }
    }

    /**
     * The observable depths (see computeObservableDepths()) from a node with an edge of type $path_type to a
     * node from which they are $depths
     *
     * @psalm-mutation-free
     */
    private function getObservedDepths(int $depths, int $path_type): int
    {
        [$observed_family, , $closed_family, $added_family] = $this->path_type_effects[$path_type];

        if ($this->path_types[$path_type] === 'arrayvalue-fetch') {
            // it ignores an innermost array key (see getNextOpenAssignments())
            $observed_family = self::ARRAY_FAMILY;
        }

        if ($observed_family === -1 && $closed_family === -1 && $added_family === -1) {
            return $depths;
        }

        $result = 0;

        foreach (self::FAMILIES as $family => $_) {
            $shift = $family * self::DEPTH_BITS;
            $depth = ($depths >> $shift) & self::DEPTH_MASK;

            if ($added_family === $family) {
                $depth = max(0, $depth - 1);
            } elseif ($closed_family === $family) {
                // closing it shows the next one to the fetches past it, and this fetch may observe it
                $depth = $depth > 0 || $observed_family === $family ? $depth + 1 : 0;
            } elseif ($observed_family === $family) {
                $depth = max(1, $depth);
            }

            $result |= min($depth, self::MAX_OPEN_ASSIGNMENT_DEPTH) << $shift;
        }

        return $result;
    }

    /**
     * @psalm-pure
     */
    private static function maxDepths(int $a, int $b): int
    {
        $result = 0;

        foreach (self::FAMILIES as $family => $_) {
            $mask = self::DEPTH_MASK << ($family * self::DEPTH_BITS);
            $result |= max($a & $mask, $b & $mask);
        }

        return $result;
    }

    /**
     * @param array<string, array<string, true>> $reverse
     * @psalm-external-mutation-free
     */
    private function computeTaintAddingReachable(array $reverse): void
    {
        $queue = [];

        foreach ($this->forward_edges as $from_id => $destinations) {
            foreach ($destinations as $path) {
                if ($path->added_taints !== 0) {
                    $this->taint_adding_reachable[$from_id] = true;
                    $queue[] = $from_id;

                    break;
                }
            }
        }

        while ($queue) {
            $id = array_pop($queue);

            foreach ($reverse[$id] ?? [] as $from_id => $_) {
                if (!isset($this->taint_adding_reachable[$from_id])) {
                    $this->taint_adding_reachable[$from_id] = true;
                    $queue[] = $from_id;
                }
            }

            foreach ($this->specialization_links[$id] ?? [] as $linked_id => $_) {
                if (!isset($this->taint_adding_reachable[$linked_id])) {
                    $this->taint_adding_reachable[$linked_id] = true;
                    $queue[] = $linked_id;
                }
            }
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    private function getPathTypeId(string $path_type): int
    {
        if (isset($this->path_type_ids[$path_type])) {
            return $this->path_type_ids[$path_type];
        }

        $id = count($this->path_types);
        $this->path_type_ids[$path_type] = $id;
        $this->path_types[] = $path_type;
        $this->path_type_effects[] = self::getPathTypeEffects($path_type);

        return $id;
    }

    /**
     * What an edge of type $path_type does with the open assignments of a flow, as shouldIgnoreFetch() and
     * appendPathType() treat them, except that the array keys are at the level of the array values (see
     * FAMILIES). The edge to what an array becomes once its value under a key is replaced (see
     * DataFlowGraph::isOverwritten()) has that key prefixed with '!' as observed key, and observes, closes and
     * adds nothing (see getNextOpenAssignments()).
     *
     * @return array{int, ?string, int, int}
     * @psalm-pure
     */
    private static function getPathTypeEffects(string $path_type): array
    {
        if ($path_type === 'arraykey-assignment') {
            return [-1, null, -1, self::ARRAY_FAMILY];
        }

        if ($path_type === 'arraykey-fetch') {
            // it fetches the key '' (see classPassesFetch()): not the key of a value assigned under a known key
            return [self::ARRAY_FAMILY, '', self::ARRAY_FAMILY, -1];
        }

        if (str_starts_with($path_type, 'arrayvalue-overwrite-')) {
            return [-1, '!' . substr($path_type, 21), -1, -1];
        }

        $observed_family = -1;
        $observed_key = null;
        $closed_family = -1;
        $added_family = -1;

        foreach (self::FAMILIES as $family => $expression_type) {
            if (str_starts_with($path_type, $expression_type . '-fetch-')) {
                $observed_family = $family;
                $observed_key = substr($path_type, strlen($expression_type) + 7);
            }
        }

        foreach (self::FAMILIES as $family => $expression_type) {
            if (str_starts_with($path_type, $expression_type . '-fetch')) {
                $closed_family = $family;

                break;
            }
        }

        foreach (self::FAMILIES as $family => $expression_type) {
            if (str_starts_with($path_type, $expression_type . '-assignment')) {
                $added_family = $family;

                break;
            }
        }

        return [$observed_family, $observed_key, $closed_family, $added_family];
    }

    /**
     * @param array<int, list<int>> $made
     * @param array<int, int> $closed
     * @psalm-external-mutation-free
     */
    private function internOpenAssignments(array $made, array $closed): int
    {
        $key = '';

        foreach (self::FAMILIES as $family => $_) {
            $key .= implode(',', $made[$family] ?? []) . '|' . ($closed[$family] ?? self::NO_CALL) . '|';
        }

        if (isset($this->open_assignment_ids[$key])) {
            return $this->open_assignment_ids[$key];
        }

        $id = count($this->open_assignments);
        $this->open_assignment_ids[$key] = $id;
        $this->open_assignments[] = [$made, $closed];

        return $id;
    }

    /**
     * Keeps the innermost open assignments of an expression type a flow can keep (see
     * MAX_OPEN_ASSIGNMENT_DEPTH and MAX_CALL_OPEN_ASSIGNMENT_DEPTH)
     *
     * @param array<int, list<int>> $made
     * @param array<int, int> $closed
     * @param-out array<int, list<int>> $made
     * @param-out array<int, int> $closed
     * @psalm-capabilities write-refs
     */
    private static function capMadeOpenAssignments(array &$made, array &$closed, int $family): void
    {
        self::capOpenAssignments(
            $made,
            $closed,
            $family,
            $closed[$family] === self::NO_CALL
                ? self::MAX_OPEN_ASSIGNMENT_DEPTH
                : self::MAX_CALL_OPEN_ASSIGNMENT_DEPTH,
        );
    }

    /**
     * Keeps the innermost $depth open assignments of an expression type
     *
     * @param array<int, list<int>> $made
     * @param array<int, int> $closed
     * @param-out array<int, list<int>> $made
     * @param-out array<int, int> $closed
     * @psalm-capabilities write-refs
     */
    private static function capOpenAssignments(array &$made, array &$closed, int $family, int $depth): void
    {
        if (count($made[$family]) <= $depth) {
            return;
        }

        $made[$family] = $depth === 0 ? [] : array_slice($made[$family], -$depth);

        // what is below the innermost ones is not known anymore
        if ($closed[$family] !== self::NO_CALL) {
            $closed[$family] = self::FORGOTTEN;
        }
    }

    /**
     * The open assignments of a flow with open assignments $open_assignments after it takes an edge of type
     * $path_type, IGNORED if shouldIgnoreFetch() ignores that edge, or OBSERVES_CALL if that depends on an
     * open assignment of the call entering the flow's context, which the flow doesn't know.
     *
     * A fetch only observes and closes the innermost open assignment of its expression type, and an
     * assignment only adds one to its own: the open assignments of each expression type are a stack of their
     * own, and how they interleave doesn't matter. Array keys share the stack of array values (see FAMILIES).
     *
     * @psalm-external-mutation-free
     */
    private function getNextOpenAssignments(int $open_assignments, int $path_type): int
    {
        if (isset($this->open_assignment_transitions[$open_assignments][$path_type])) {
            return $this->open_assignment_transitions[$open_assignments][$path_type];
        }

        [$observed_family, $observed_key] = $this->path_type_effects[$path_type];
        [$made, $closed] = $this->open_assignments[$open_assignments];
        $array_assignments = $made[self::ARRAY_FAMILY] ?? [];

        if ($observed_family === -1 && $observed_key !== null) {
            // The replacement of the value under a key (see getPathTypeEffects()) stops a flow of what was
            // assigned under that key, where the flow knows that's its innermost open array assignment. Any other
            // goes on, also one that doesn't know it: deciding that for each call entering its context, in
            // filters, would lose the precision of the fetches in them (see getAssignmentClass()) for no flow
            // they would take otherwise. Nor is it an observation keeping the open assignments of the flows
            // reaching it (see computeObservableDepths()): where no fetch past it can observe the one it would
            // stop, the flow goes on as it did through the plain edge it replaces.
            $innermost = $array_assignments ? $array_assignments[count($array_assignments) - 1] : -1;
            $next = $innermost >= 0
                && !self::classPassesFetch($this->getClass($innermost, self::ARRAY_FAMILY), $observed_key)
                ? self::IGNORED
                : $open_assignments;
        } elseif ($array_assignments
            && $this->path_types[$array_assignments[count($array_assignments) - 1]] === 'arraykey-assignment'
            && $this->path_types[$path_type] === 'arrayvalue-fetch'
        ) {
            // The value of an item under an unknown key doesn't take what was assigned to its key either. Only
            // where the flow knows that's the innermost one: an unknown key is fetched too often for the walks
            // to be told apart by it in filters (see getFilter()).
            $next = self::IGNORED;
        } elseif ($observed_family === -1) {
            $next = $this->applyPathType($open_assignments, $path_type);
        } elseif ($made[$observed_family]) {
            $next = self::classPassesFetch(
                $this->getClass($made[$observed_family][count($made[$observed_family]) - 1], $observed_family),
                $observed_key ?? '',
            ) ? $this->applyPathType($open_assignments, $path_type) : self::IGNORED;
        } elseif ($closed[$observed_family] !== self::NO_CALL && $closed[$observed_family] !== self::FORGOTTEN) {
            $next = self::OBSERVES_CALL;
        } else {
            // there is none, or the flow doesn't know it
            $next = $this->applyPathType($open_assignments, $path_type);
        }

        $this->open_assignment_transitions[$open_assignments][$path_type] = $next;

        return $next;
    }

    /**
     * The open assignments of a flow with open assignments $open_assignments after it takes an edge of type
     * $path_type that it doesn't ignore: a fetch closes the innermost open assignment of its expression
     * type, and an assignment adds one to its own.
     *
     * @psalm-external-mutation-free
     */
    private function applyPathType(int $open_assignments, int $path_type): int
    {
        [, , $closed_family, $added_family] = $this->path_type_effects[$path_type];

        if ($closed_family === -1 && $added_family === -1) {
            return $open_assignments;
        }

        [$made, $closed] = $this->open_assignments[$open_assignments];

        if ($closed_family !== -1) {
            if ($made[$closed_family]) {
                array_pop($made[$closed_family]);
            } elseif ($closed[$closed_family] !== self::NO_CALL && $closed[$closed_family] !== self::FORGOTTEN) {
                // past MAX_CALL_OPEN_ASSIGNMENT_DEPTH, FORGOTTEN
                $closed[$closed_family]++;
            }
        }

        if ($added_family !== -1) {
            $made[$added_family][] = $path_type;
            self::capMadeOpenAssignments($made, $closed, $added_family);
        }

        return $this->internOpenAssignments($made, $closed);
    }

    /**
     * The open assignments of a flow of a body walk with open assignments $open_assignments, in the context of
     * a call entering it with open assignments $call_open_assignments: those of the call, without those the
     * flow closed, and with those it made.
     *
     * @psalm-external-mutation-free
     */
    private function composeOpenAssignments(int $call_open_assignments, int $open_assignments): int
    {
        if (isset($this->open_assignment_compositions[$call_open_assignments][$open_assignments])) {
            return $this->open_assignment_compositions[$call_open_assignments][$open_assignments];
        }

        [$call_made, $call_closed] = $this->open_assignments[$call_open_assignments];
        [$flow_made, $flow_closed] = $this->open_assignments[$open_assignments];
        $made = [[], []];
        $closed = [self::NO_CALL, self::NO_CALL];

        foreach (self::FAMILIES as $family => $_) {
            $count = count($call_made[$family]);
            $flow_closed_count = $flow_closed[$family];

            if ($flow_closed_count === self::FORGOTTEN) {
                $made[$family] = $flow_made[$family];
                $closed[$family] = $call_closed[$family] === self::NO_CALL ? self::NO_CALL : self::FORGOTTEN;
            } elseif ($flow_closed_count <= $count) {
                $made[$family] = array_merge(
                    array_slice($call_made[$family], 0, $count - $flow_closed_count),
                    $flow_made[$family],
                );
                $closed[$family] = $call_closed[$family];
            } else {
                $made[$family] = $flow_made[$family];
                $closed[$family] = $call_closed[$family] === self::NO_CALL || $call_closed[$family] === self::FORGOTTEN
                    ? $call_closed[$family]
                    : min(self::FORGOTTEN, $call_closed[$family] + $flow_closed_count - $count);
            }

            self::capMadeOpenAssignments($made, $closed, $family);
        }

        $result = $this->internOpenAssignments($made, $closed);
        $this->open_assignment_compositions[$call_open_assignments][$open_assignments] = $result;

        return $result;
    }

    /**
     * The open assignments $open_assignments of a flow reaching a node with WIDENING_STATES states: only their
     * innermost open assignment of each expression type, and none of those of the call entering their context
     * known. A fetch only ignores an open assignment the flow knows, so forgetting some only lets the flow go
     * on through more fetches.
     *
     * @psalm-external-mutation-free
     */
    private function widenOpenAssignments(int $open_assignments): int
    {
        if (isset($this->open_assignment_widenings[$open_assignments])) {
            return $this->open_assignment_widenings[$open_assignments];
        }

        [$made, $closed] = $this->open_assignments[$open_assignments];

        foreach (self::FAMILIES as $family => $_) {
            $made[$family] ??= [];
            $closed[$family] ??= self::NO_CALL;
            self::capOpenAssignments($made, $closed, $family, 1);

            if ($closed[$family] !== self::NO_CALL) {
                $closed[$family] = self::FORGOTTEN;
            }
        }

        return $this->open_assignment_widenings[$open_assignments] = $this->internOpenAssignments($made, $closed);
    }

    /**
     * The open assignments of $open_assignments a fetch reachable from $node_id can observe (see
     * computeObservableDepths())
     *
     * @psalm-external-mutation-free
     */
    private function truncateOpenAssignments(int $open_assignments, string $node_id): int
    {
        $depths = $this->observable_depths[$node_id] ?? 0;

        if (isset($this->open_assignment_truncations[$open_assignments][$depths])) {
            return $this->open_assignment_truncations[$open_assignments][$depths];
        }

        [$made, $closed] = $this->open_assignments[$open_assignments];

        foreach (self::FAMILIES as $family => $_) {
            $depth = ($depths >> ($family * self::DEPTH_BITS)) & self::DEPTH_MASK;

            self::capOpenAssignments($made, $closed, $family, $depth);

            // nor can it observe those of the call below
            if ($depth <= count($made[$family]) && $closed[$family] !== self::NO_CALL) {
                $closed[$family] = self::FORGOTTEN;
            }
        }

        $result = $this->internOpenAssignments($made, $closed);
        $this->open_assignment_truncations[$open_assignments][$depths] = $result;

        return $result;
    }

    /**
     * Records that flows reach node $node_id in context $context with open assignments $open_assignments,
     * keeping $kept and adding $added taints, from state $predecessor (see $state_predecessors), seen through
     * the call of state $caller if any and those of $link: makes that state, or gives it the taints it didn't
     * have, and queues it to be walked if so.
     *
     * @psalm-external-mutation-free
     */
    private function reach(
        string $node_id,
        int $context,
        int $open_assignments,
        int $kept,
        int $added,
        int $predecessor,
        int $link,
        int $path_type,
        int $caller = -1,
    ): void {
        if ($kept === 0 && $added === 0 && !isset($this->taint_adding_reachable[$node_id])) {
            return;
        }

        $open_assignments = $this->truncateOpenAssignments($open_assignments, $node_id);

        if (isset($this->state_ids[$node_id]) && count($this->state_ids[$node_id]) >= self::WIDENING_STATES) {
            $open_assignments = $this->widenOpenAssignments($open_assignments);
        }

        $key = (($context + 1) << 32) | $open_assignments;

        if (!isset($this->state_ids[$node_id][$key])) {
            if ($context !== -1
                && $this->entry_kinds[$context] === self::ENTRY_CALL
                && count($this->call_entries_reaching[$node_id] ?? []) < 2
            ) {
                $this->call_entries_reaching[$node_id][$this->entry_bases[$context]] = true;
            }

            $state = count($this->state_nodes);
            $this->state_ids[$node_id][$key] = $state;
            $this->state_nodes[] = $node_id;
            $this->state_contexts[] = $context;
            $this->state_open_assignments[] = $open_assignments;
            $this->state_kept[] = $kept;
            $this->state_added[] = $added;
            $this->state_predecessors[] = $predecessor;
            $this->state_path_types[] = $path_type;
            $this->state_sequence[] = $this->sequence++;

            if ($caller !== -1) {
                $link = $this->getLink($caller, $link);
            }

            if ($link !== 0) {
                $this->state_links[$state] = $link;
            }

            $this->queue[] = $state;
            $this->queued[$state] = true;

            return;
        }

        $state = $this->state_ids[$node_id][$key];
        $new_kept = $kept & ~$this->state_kept[$state];
        $new_added = $added & ~$this->state_added[$state];

        if ($new_kept === 0 && $new_added === 0) {
            return;
        }

        if ($caller !== -1) {
            $link = $this->getLink($caller, $link);
        }

        $this->state_kept[$state] |= $new_kept;
        $this->state_added[$state] |= $new_added;
        $this->later_reaches[$state][] = [$predecessor, $link, $path_type, $this->sequence++, $new_kept, $new_added];

        if (!isset($this->queued[$state])) {
            $this->queue[] = $state;
            $this->queued[$state] = true;
        }
    }

    /**
     * Follows the flows of a state: through the edges of its node, or else into the body of the call it
     * enters, or out of the body it leaves.
     */
    private function walk(int $state): void
    {
        $id = $this->state_nodes[$state];

        if (isset($this->forward_edges[$id])) {
            if (!isset($this->root_entries[$state])
                && count($this->state_ids[$id]) >= self::CONVERGING_STATES
                && ($this->isOutsideOfCalls($this->state_contexts[$state])
                    || count($this->call_entries_reaching[$id] ?? []) > 1)
            ) {
                $this->enterConvergence($state, $id);
            } else {
                $this->walkEdges($state, $id);
            }

            return;
        }

        $node = $this->getNode($id);

        if ($node === null) {
            return;
        }

        $specialization_key = $node->specialization_key;

        if ($specialization_key !== null && isset($this->specialized_calls[$specialization_key])) {
            // A specialized node: de-specialize, entering its shared body.
            /** @var string $unspecialized_id */
            $unspecialized_id = $node->unspecialized_id;

            if (!isset($this->forward_edges[$unspecialized_id])) {
                return;
            }

            if (isset($this->despecialized_calls[$specialization_key])) {
                // A despecialized call is entered like an unspecialized one: the body is walked in the
                // context of the flow, and it is exited through all of its call sites.
                $this->reach(
                    $unspecialized_id,
                    $this->state_contexts[$state],
                    $this->state_open_assignments[$state],
                    $this->state_kept[$state],
                    $this->state_added[$state],
                    $state,
                    0,
                    -1,
                );
            } else {
                $this->enterSpecializedCall($state, $unspecialized_id, $specialization_key);
            }

            return;
        }

        if (!isset($this->specializations[$id])) {
            return;
        }

        // A node with first level specializations (an unspecialized one): leave through all of them outside
        // of any specialized call, else through those of the calls the flow's body was entered through (see
        // addEntryExit()). The call sites of despecialized calls are all exited, keeping the context.
        $context = $this->state_contexts[$state];
        $outside_of_calls = $this->isOutsideOfCalls($context);

        if ($outside_of_calls
            && !isset($this->root_entries[$state])
            && count($this->state_ids[$id]) >= self::CONVERGING_STATES
        ) {
            // leaving through all the call sites once for the flows reaching the exit in many states, relative
            // to them, as where flows converge at a node with edges (e.g. the exit of a function-like reading
            // a property many flows reach, see exitThroughCaller())
            $this->enterConvergence($state, $id);

            return;
        }
        $has_specialized_calls = false;

        foreach ($this->specializations[$id] as $specialization_key => $specialized_id) {
            if ($outside_of_calls || isset($this->despecialized_calls[$specialization_key])) {
                $this->reach(
                    $specialized_id,
                    $context,
                    $this->state_open_assignments[$state],
                    $this->state_kept[$state],
                    $this->state_added[$state],
                    $state,
                    0,
                    -1,
                );
            } else {
                $has_specialized_calls = true;
            }
        }

        if ($has_specialized_calls) {
            $this->addEntryExit(
                $context,
                $id,
                $this->state_open_assignments[$state],
                $state,
                0,
                $this->state_kept[$state],
                $this->state_added[$state],
            );
        }
    }

    /**
     * Follows every outgoing edge of node $from_id from a state.
     */
    private function walkEdges(int $state, string $from_id): void
    {
        $from = $this->getNode($from_id);

        if ($from !== null && !$this->isWalkedFrom($from)) {
            return;
        }

        $context = $this->state_contexts[$state];
        $open_assignments = $this->state_open_assignments[$state];
        $kept = $this->state_kept[$state];
        $added = $this->state_added[$state];

        foreach ($this->forward_edges[$from_id] as $to_id => $path) {
            $removed_taints = $path->removed_taints;

            $this->takeEdge(
                $context,
                $open_assignments,
                $kept & ~$removed_taints,
                ($added | $path->added_taints) & ~$removed_taints,
                $state,
                0,
                $from_id,
                $to_id,
                $this->path_type_ids[$path->type] ?? $this->getPathTypeId($path->type),
            );
        }
    }

    /**
     * Takes the edge from $from_id to $to_id of type $path_type with the flows in context $context with open
     * assignments $open_assignments before it, and keeping $kept and adding $added taints past it, from state
     * $predecessor (seen through the calls of $link): reports the flow into the sink it reaches if any, and
     * reaches its destination.
     */
    private function takeEdge(
        int $context,
        int $open_assignments,
        int $kept,
        int $added,
        int $predecessor,
        int $link,
        string $from_id,
        string $to_id,
        int $path_type,
    ): void {
        $next_open_assignments = $this->open_assignment_transitions[$open_assignments][$path_type]
            ?? $this->getNextOpenAssignments($open_assignments, $path_type);

        if ($next_open_assignments === self::IGNORED) {
            return;
        }

        if ($next_open_assignments === self::OBSERVES_CALL) {
            // it goes on for the calls whose open assignment it doesn't ignore
            [$observed_family, $observed_key] = $this->path_type_effects[$path_type];
            $observed_key ??= '';
            $depth = $this->open_assignments[$open_assignments][1][$observed_family] + 1;
            $fact = $this->entry_facts[$context][self::getPosition($observed_family, $depth)] ?? null;

            if ($fact !== null && $fact[0] !== null) {
                if (!self::classPassesFetch($fact[0], $observed_key)) {
                    return;
                }
            } elseif ($fact === null || !isset($fact[1][$observed_key])) {
                $context = $this->getFilter($context, $observed_family, $depth, $observed_key);
            }

            $next_open_assignments = $this->applyPathType($open_assignments, $path_type);
        }

        // a flow is reported at its sink, or else at the node it reaches the sink from: a plugin can connect a node
        // without a location to a sink
        if (isset($this->sinks[$to_id])
            && ($this->getNode($from_id)?->code_location !== null || $this->sinks[$to_id]->code_location !== null)
        ) {
            $sink_taints = $this->sinks[$to_id]->taints;

            if ((($kept | $added) & $sink_taints) !== 0) {
                if ($context === -1) {
                    $this->reportFlow($to_id, $predecessor, -1, $link, $added & $sink_taints);
                } elseif ($this->entry_kinds[$context] !== self::ENTRY_CALL) {
                    $this->addConvergenceSink($context, $to_id, $from_id, $predecessor, $link, $kept, $added);
                } else {
                    $this->addEntrySink($context, $to_id, $from_id, $predecessor, $link, $kept, $added);
                }
            }
        }

        $this->reach($to_id, $context, $next_open_assignments, $kept, $added, $predecessor, $link, $path_type);
    }

    /**
     * The node of id $id. An unspecialized node whose edges a flow takes past a specialized node may not be
     * registered: it has the label and location of the specialized nodes, which are those of their
     * unspecialized base.
     *
     * @psalm-external-mutation-free
     */
    private function getNode(string $id): ?DataFlowNode
    {
        if (isset($this->nodes[$id])) {
            return $this->nodes[$id];
        }

        if (isset($this->sources[$id])) {
            return $this->sources[$id];
        }

        if (!array_key_exists($id, $this->derived_nodes)) {
            $this->derived_nodes[$id] = null;

            // the same one whichever order the analysis of the files added them in
            $linked_ids = $this->specialization_links[$id] ?? [];
            ksort($linked_ids, SORT_STRING);

            foreach ($linked_ids as $linked_id => $_) {
                $linked = $this->nodes[$linked_id] ?? $this->sources[$linked_id] ?? null;

                if ($linked !== null) {
                    $this->derived_nodes[$id] = $linked->withSpecialization($id, null, null, null);

                    break;
                }
            }
        }

        return $this->derived_nodes[$id];
    }

    /**
     * Whether the resolution follows the flows from a node: not from those in project files where tainted
     * input isn't reported.
     */
    private function isWalkedFrom(DataFlowNode $node): bool
    {
        if ($node->code_location === null) {
            return true;
        }

        $file_path = $node->code_location->file_path;

        if (!isset($this->unwalked_files[$file_path])) {
            $this->unwalked_files[$file_path] = $this->project_analyzer->canReportIssues($file_path)
                && !$this->config->reportIssueInFile('TaintedInput', $file_path);
        }

        return !$this->unwalked_files[$file_path];
    }

    /**
     * A flow enters the shared body of a specialized function-like through the specialized node of state
     * $caller of the call identified by $specialization_key.
     *
     * Only the entry nodes of such a body are specialized: all of its inner nodes are shared by every call.
     * So the body is walked once per entry -- the unspecialized node entered -- with the states of that walk
     * carrying the entry as their context, and their taints and open assignments relative to those of the
     * call. What the walk reaches depends on the call: an exit back to the call site (addEntryExit()) as well
     * as a sink (addEntrySink()), inside the body or past it, and a fetch of an open assignment of the call
     * (getFilter()). So it is recorded against the entry and applied to each call entering it, including
     * calls that arrive after the walk, and calls that get more taints after: those are not walked again, the
     * recorded outcomes are replayed for them instead.
     *
     * This keeps the resolution context-sensitive for specialized calls, however many rounds apart their
     * flows arrive, while the states stay bounded by the number of entries rather than by the number of
     * calls or call chains.
     */
    private function enterSpecializedCall(int $caller, string $unspecialized_id, string $specialization_key): void
    {
        $this->addEntryCaller(
            $this->getEntry($unspecialized_id, self::ENTRY_CALL, $caller),
            $caller,
            $specialization_key,
        );
    }

    /**
     * Flows reach node $id in many states: with many open assignments, or in many contexts (e.g. a parameter
     * of a function called with many different arrays, or a property set from many places). Where they go
     * from there depends on their open assignments only through the fetches observing them (see
     * getFilter()), and the sinks they reach report their taints: like the body of a specialized call, it is
     * walked once, relative to them, as a convergence entered by the flows of state $caller and the others.
     *
     * The taints of a convergence's flows are the union of those of the flows entering it: whether a taint
     * reaches a sink doesn't depend on the others (see addConvergenceSink()). The flows of a convergence can
     * enter another one, which then gets their taints, past those of the flows entering the first one.
     *
     * The exits a convergence reaches lead to all their call sites, as outside of any specialized call (see
     * walk()). Flows in specialized calls only converge at a node the walks of several entries reach: there,
     * a value is shared by the calls (e.g. a property), and any call reading it may return it, so the call
     * site each flow was entered through doesn't matter anymore.
     */
    private function enterConvergence(int $caller, string $id): void
    {
        $this->addEntryCaller(
            $this->getEntry($id, self::ENTRY_CONVERGENCE, $caller, $this->getConvergenceOpenAssignments($caller, $id)),
            $caller,
            null,
        );
    }

    /**
     * The open assignments the walk of the convergence of node $id for the flows of state $caller starts with:
     * those of its innermost open assignment of each expression type, so that the flows with different ones
     * don't share it.
     *
     * The flows converging at a node often differ by little more: e.g. the values of the properties of an
     * object, each assigned to its own array key, converge at the array of all of them. A fetch past the
     * convergence ignores all of them but one there, and in any convergence their flows enter later on, where
     * the open assignments of the flows entering it are only known through those of the flows entering the
     * first one (see getAssignmentClass()). Past MAX_CONVERGENCE_KEYS of them at a node, the flows share one
     * convergence that knows none.
     *
     * @psalm-external-mutation-free
     */
    private function getConvergenceOpenAssignments(int $caller, string $id): int
    {
        [$made] = $this->open_assignments[$this->state_open_assignments[$caller]];
        $known = [];
        $known_count = [];

        foreach (self::FAMILIES as $family => $_) {
            $known[$family] = array_slice($made[$family] ?? [], -1);
            $known_count[$family] = count($known[$family]);
        }

        $open_assignments = $this->truncateOpenAssignments($this->internOpenAssignments($known, $known_count), $id);

        if (!isset($this->convergence_open_assignments[$id][$open_assignments])) {
            if (count($this->convergence_open_assignments[$id] ?? []) >= self::MAX_CONVERGENCE_KEYS) {
                return self::CALL_OPEN_ASSIGNMENTS;
            }

            $this->convergence_open_assignments[$id][$open_assignments] = true;
        }

        return $open_assignments;
    }

    /**
     * Whether the flows in context $context are outside of any specialized call: then an exit leads to all
     * the call sites (see walk()).
     *
     * @psalm-mutation-free
     */
    private function isOutsideOfCalls(int $context): bool
    {
        return $context === -1 || $this->entry_kinds[$context] === self::ENTRY_CONVERGENCE;
    }

    /**
     * The entry of kind $kind for the node $id whose walk starts with open assignments $open_assignments,
     * made if needed with $caller as the first call entering it
     *
     * @param self::ENTRY_* $kind
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function getEntry(
        string $id,
        int $kind,
        int $caller,
        int $open_assignments = self::CALL_OPEN_ASSIGNMENTS,
    ): int {
        $entry_key = $kind . ' ' . $id . ' ' . $open_assignments;

        if (isset($this->entry_ids[$entry_key])) {
            return $this->entry_ids[$entry_key];
        }

        $entry = $this->addEntry($id, $kind, []);
        $this->entry_ids[$entry_key] = $entry;
        $this->root_entries[count($this->state_nodes)] = $entry;

        $this->reach($id, $entry, $open_assignments, self::ALL_TAINTS, 0, $caller, 0, -1);

        return $entry;
    }

    /**
     * @param self::ENTRY_* $kind
     * @param array<int, array{?string, array<string, true>}> $facts
     * @psalm-external-mutation-free
     */
    private function addEntry(string $id, int $kind, array $facts, ?int $base = null): int
    {
        $entry = count($this->entry_nodes);
        $this->entry_bases[] = $base ?? $entry;
        $this->entry_nodes[] = $id;
        $this->entry_kinds[] = $kind;
        $this->entry_facts[] = $facts;
        $this->entry_filters[$entry] = [];
        $this->entry_class_filters[$entry] = [];
        $this->entry_class_dependents[$entry] = [];
        $this->entry_callers[$entry] = [];
        $this->entry_exits[$entry] = [];
        $this->entry_sinks[$entry] = [];

        return $entry;
    }

    /**
     * Makes the call of state $caller one entering $entry, and the filters of $entry it belongs to (see
     * getFilter() and dependOnClass()), and applies to it what their walks reached.
     */
    private function addEntryCaller(int $entry, int $caller, ?string $specialization_key): void
    {
        $this->entry_callers[$entry][$caller] = $specialization_key;

        $caller_context = $this->state_contexts[$caller];

        if ($caller_context !== -1) {
            $this->entry_taint_dependents[$caller_context][$caller][$entry] = true;
        }

        $this->addEntryTaints($entry, $caller);

        if ($this->entry_kinds[$entry] === self::ENTRY_CALL) {
            foreach ($this->entry_sinks[$entry] as [$sink_id, , , $reaches]) {
                foreach ($reaches as [$state, $link, $kept, $added]) {
                    $this->addSinkThroughCaller($sink_id, $state, $link, $kept, $added, $caller);
                }
            }
        }

        foreach ($this->entry_exits[$entry] as [$exit_id, $open_assignments, , , $reaches]) {
            foreach ($reaches as [$state, $link, $kept, $added]) {
                $this->exitThroughCaller(
                    $exit_id,
                    $open_assignments,
                    $state,
                    $link,
                    $kept,
                    $added,
                    $caller,
                    $specialization_key,
                );
            }
        }

        foreach ($this->entry_filters[$entry] as $filter) {
            [$family, $depth, $fetched_key] = $this->filter_fetches[$filter];

            if ($this->passesFetch($caller, $family, $depth, $fetched_key, self::CONVERGENCE_LEVELS) === true) {
                $this->addEntryCaller($filter, $caller, $specialization_key);
            }
        }

        foreach ($this->entry_class_filters[$entry] as $position => $_) {
            $this->addClassFilterCaller($entry, $position, $caller, $specialization_key);
        }
    }

    /**
     * The filter of $entry for the calls whose open assignment of type $family at depth $depth (counting
     * from the innermost) a fetch of key $fetched_key doesn't ignore (see getNextOpenAssignments()), made if
     * needed.
     *
     * A fetch in a walk of $entry observed that open assignment, which the walk doesn't know: the flow goes
     * on in the filter, the context of the walks for the calls the fetch doesn't ignore, which only they
     * enter. Past the fetch, the open assignments of the flow are the same whatever the open assignment it
     * observed was: a fetch of a given key closes it, and an array key fetch leaves it unknown. So a filter
     * is shared by all the calls passing it.
     */
    private function getFilter(int $entry, int $family, int $depth, string $fetched_key): int
    {
        $filter_key = $family . ' ' . $depth . ' ' . $fetched_key;

        if (isset($this->entry_filters[$entry][$filter_key])) {
            return $this->entry_filters[$entry][$filter_key];
        }

        $facts = $this->entry_facts[$entry];
        $position = self::getPosition($family, $depth);
        $fact = $facts[$position] ?? [null, []];
        $passed_keys = $fact[1];
        $passed_keys[$fetched_key] = true;
        $facts[$position] = [$fact[0], $passed_keys];

        $filter = $this->addEntry(
            $this->entry_nodes[$entry],
            $this->entry_kinds[$entry],
            $facts,
            $this->entry_bases[$entry],
        );
        $this->entry_filters[$entry][$filter_key] = $filter;
        $this->filter_fetches[$filter] = [$family, $depth, $fetched_key];

        foreach ($this->entry_callers[$entry] as $caller => $specialization_key) {
            if ($this->passesFetch($caller, $family, $depth, $fetched_key, self::CONVERGENCE_LEVELS) === true) {
                $this->addEntryCaller($filter, $caller, $specialization_key);
            }
        }

        return $filter;
    }

    /**
     * Whether a fetch of key $fetched_key doesn't ignore the open assignment of type $family at depth $depth
     * of the flows of state $state, or null if they don't know it: it is one of those of the flows entering
     * their context, which decides (see dependOnClass()), through $levels more convergences at most (see
     * getAssignmentClass()).
     */
    private function passesFetch(int $state, int $family, int $depth, string $fetched_key, int $levels): ?bool
    {
        [$made, $closed] = $this->open_assignments[$this->state_open_assignments[$state]];
        $count = count($made[$family]);

        $index = $count - $depth;

        if ($index >= 0) {
            return self::classPassesFetch($this->getClass($made[$family][$index], $family), $fetched_key);
        }

        $call_depth = $closed[$family] + $depth - $count;

        if ($closed[$family] === self::NO_CALL
            || $closed[$family] === self::FORGOTTEN
            || $call_depth > self::MAX_CALL_OPEN_ASSIGNMENT_DEPTH
        ) {
            // there is none, or the flows don't know it
            return true;
        }

        $context = $this->state_contexts[$state];

        $is_convergence = $this->entry_kinds[$context] !== self::ENTRY_CALL;

        if ($is_convergence && $levels === 0) {
            // see getAssignmentClass()
            return true;
        }

        $fact = $this->entry_facts[$context][self::getPosition($family, $call_depth)] ?? null;

        if ($fact !== null && $fact[0] !== null) {
            return self::classPassesFetch($fact[0], $fetched_key);
        }

        if ($fact !== null && isset($fact[1][$fetched_key])) {
            return true;
        }

        if ($this->entry_bases[$context] !== $context) {
            // see getAssignmentClass()
            return true;
        }

        $this->dependOnClass(
            $context,
            self::getPosition($family, $call_depth),
            $state,
            $is_convergence ? $levels - 1 : $levels,
        );

        return null;
    }

    /**
     * The class (see getClass()) of the open assignment of type $family at depth $depth of the flows of state
     * $state, or null if they don't know it: it is one of those of the flows entering their context, which
     * decides (see dependOnClass()), through $levels more convergences at most.
     */
    private function getAssignmentClass(int $state, int $family, int $depth, int $levels): ?string
    {
        [$made, $closed] = $this->open_assignments[$this->state_open_assignments[$state]];
        $count = count($made[$family]);

        $index = $count - $depth;

        if ($index >= 0) {
            return $this->getClass($made[$family][$index], $family);
        }

        $call_depth = $closed[$family] + $depth - $count;

        if ($closed[$family] === self::NO_CALL
            || $closed[$family] === self::FORGOTTEN
            || $call_depth > self::MAX_CALL_OPEN_ASSIGNMENT_DEPTH
        ) {
            // there is none, or the flows don't know it: no fetch ignores it
            return '';
        }

        $context = $this->state_contexts[$state];

        $is_convergence = $this->entry_kinds[$context] !== self::ENTRY_CALL;

        if ($is_convergence && $levels === 0) {
            // Made before the flows entering a convergence reached the convergence they are in themselves, and
            // so on through CONVERGENCE_LEVELS convergences. Telling apart the flows entering each of them by
            // the class of their open assignment there, in filters of it, would multiply the walks for every
            // combination of classes of the convergences they went through. So no fetch ignores it there: past
            // one, a flow may take taints it doesn't have, but takes all those it has.
            return '';
        }

        $position = self::getPosition($family, $call_depth);
        $class = $this->entry_facts[$context][$position][0] ?? null;

        if ($class !== null) {
            return $class;
        }

        if ($this->entry_bases[$context] !== $context) {
            // Already in a filter, for the calls agreeing on another open assignment: a filter of it for each
            // class there too would make one for every combination of classes of the open assignments a walk
            // observes. So no fetch ignores it, as above.
            return '';
        }

        $this->dependOnClass($context, $position, $state, $is_convergence ? $levels - 1 : $levels);

        return null;
    }

    /**
     * The class of an open assignment of type $family: what decides whether a fetch ignores it (see
     * shouldIgnoreFetch()). That's its key, prefixed with ':', KEY_CLASS for an array key, or '' if no fetch
     * ignores it.
     *
     * @psalm-mutation-free
     */
    private function getClass(int $assignment, int $family): string
    {
        $assignment_type = $this->path_types[$assignment];
        $expression_type = self::FAMILIES[$family];

        if ($assignment_type === 'arraykey-assignment') {
            return self::KEY_CLASS;
        }

        return $assignment_type !== $expression_type . '-assignment'
            && str_starts_with($assignment_type, $expression_type . '-assignment-')
            ? ':' . substr($assignment_type, strlen($expression_type) + 12)
            : '';
    }

    /**
     * The position of the open assignment of type $family at depth $depth (counting from the innermost)
     *
     * @psalm-pure
     */
    private static function getPosition(int $family, int $depth): int
    {
        return ($family << self::DEPTH_BITS) | $depth;
    }

    /**
     * @psalm-pure
     */
    private static function classPassesFetch(string $class, string $fetched_key): bool
    {
        if (str_starts_with($fetched_key, '!')) {
            // the replacement of the value under a key (see getPathTypeEffects()): only what was assigned under
            // that key goes
            return $class !== ':' . substr($fetched_key, 1);
        }

        if ($class === self::KEY_CLASS) {
            // only a fetch of the key takes what was assigned to it
            return $fetched_key === '';
        }

        return $class === '' || $class === ':' . $fetched_key;
    }

    /**
     * What the flows of state $state, in the walk of $entry, do depends on the class of the open assignment
     * at position $position of the calls entering $entry. They go on in each filter of $entry for the calls
     * with a given class there (see dependOnClass()), as they would in $entry, but knowing it. The calls
     * that don't know it either find it through $levels more convergences at most (see getAssignmentClass()).
     */
    private function dependOnClass(int $entry, int $position, int $state, int $levels): void
    {
        $this->entry_class_dependents[$entry][$position][$state] = true;

        if (isset($this->entry_class_filters[$entry][$position])) {
            // again if the state got more taints since
            foreach ($this->entry_class_filters[$entry][$position] as $filter) {
                $this->copyToFilter($state, $filter);
            }

            return;
        }

        $this->entry_class_filters[$entry][$position] = [];
        $this->entry_class_levels[$entry][$position] = $levels;

        foreach ($this->entry_callers[$entry] as $caller => $specialization_key) {
            $this->addClassFilterCaller($entry, $position, $caller, $specialization_key);
        }
    }

    /**
     * Makes the call of state $caller one entering the filter of $entry for the calls with its class of open
     * assignment at position $position, unless it doesn't know it yet (see getAssignmentClass()).
     *
     * A filter for the calls of a given class knows it, and its walk goes on from the states of the walk of
     * $entry that depend on it (see dependOnClass()), like the walk of $entry for those calls.
     */
    private function addClassFilterCaller(int $entry, int $position, int $caller, ?string $specialization_key): void
    {
        $class = $this->getAssignmentClass(
            $caller,
            $position >> self::DEPTH_BITS,
            $position & self::DEPTH_MASK,
            $this->entry_class_levels[$entry][$position],
        );

        if ($class === null) {
            return;
        }

        if (!isset($this->entry_class_filters[$entry][$position][$class])) {
            $facts = $this->entry_facts[$entry];
            $facts[$position] = [$class, []];

            $filter = $this->addEntry(
                $this->entry_nodes[$entry],
                $this->entry_kinds[$entry],
                $facts,
                $this->entry_bases[$entry],
            );
            $this->entry_class_filters[$entry][$position][$class] = $filter;

            foreach ($this->entry_class_dependents[$entry][$position] ?? [] as $state => $_) {
                $this->copyToFilter($state, $filter);
            }
        }

        $this->addEntryCaller($this->entry_class_filters[$entry][$position][$class], $caller, $specialization_key);
    }

    /**
     * @psalm-external-mutation-free
     */
    private function copyToFilter(int $state, int $filter): void
    {
        $this->reach(
            $this->state_nodes[$state],
            $filter,
            $this->state_open_assignments[$state],
            $this->state_kept[$state],
            $this->state_added[$state],
            $state,
            0,
            -1,
        );
    }

    /**
     * The body walk of $entry reached $exit_id, an unspecialized node whose specializations lead back to
     * call sites, from state $state (seen through the calls of $link, see buildTrace()). Continues it at the
     * call site of each call entering $entry.
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function addEntryExit(
        int $entry,
        string $exit_id,
        int $open_assignments,
        int $state,
        int $link,
        int $kept,
        int $added,
    ): void {
        $exit_key = $exit_id . ' ' . $open_assignments;
        $exit = $this->entry_exits[$entry][$exit_key] ?? [$exit_id, $open_assignments, 0, 0, []];
        $new_kept = $kept & ~$exit[2];
        $new_added = $added & ~$exit[3];

        if ($new_kept === 0 && $new_added === 0 && $exit[4]) {
            return;
        }

        $reaches = $exit[4];
        $reaches[] = [$state, $link, $new_kept, $new_added];
        $this->entry_exits[$entry][$exit_key] = [
            $exit_id,
            $open_assignments,
            $exit[2] | $new_kept,
            $exit[3] | $new_added,
            $reaches,
        ];

        foreach ($this->entry_callers[$entry] as $caller => $specialization_key) {
            $this->exitThroughCaller(
                $exit_id,
                $open_assignments,
                $state,
                $link,
                $new_kept,
                $new_added,
                $caller,
                $specialization_key,
            );
        }
    }

    /**
     * Continues an exit reached by the body walk of an entry in the context of one call entering it: at that
     * call's specialization of the exit node if it has one. Else, if the exit is one of the function-like the call
     * enters, the call site doesn't use it, and the flows end. If it is one of another function-like, reached
     * through something the calls share (a property, a static property, ...), any call to that function-like may
     * return what the flows hold: they leave through an enclosing call of it if any, as an exit of the entry the
     * call is made from, and outside of any specialized call through all of its call sites, as a flow reaching
     * the exit there would (see walk()).
     *
     * A convergence of flows in specialized calls is left like its node would be left in the context of the
     * call (see walk()): as an exit of the entry it is in.
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function exitThroughCaller(
        string $exit_id,
        int $open_assignments,
        int $state,
        int $link,
        int $kept,
        int $added,
        int $caller,
        ?string $specialization_key,
    ): void {
        $context = $this->state_contexts[$caller];
        $caller_open_assignments = $this->composeOpenAssignments(
            $this->state_open_assignments[$caller],
            $open_assignments,
        );
        $caller_kept = $this->state_kept[$caller] & $kept;
        $caller_added = ($this->state_added[$caller] & $kept) | $added;

        if ($specialization_key !== null && isset($this->specializations[$exit_id][$specialization_key])) {
            $this->reach(
                $this->specializations[$exit_id][$specialization_key],
                $context,
                $caller_open_assignments,
                $caller_kept,
                $caller_added,
                $state,
                $link,
                -1,
                $caller,
            );
        } elseif ($specialization_key !== null && $this->isExitOfEntered($exit_id, $caller)) {
            // the call site doesn't use the exit
            return;
        } elseif ($this->isOutsideOfCalls($context)) {
            if ($specialization_key !== null) {
                // as a flow reaching the exit there, which leaves through all its call sites (see walk())
                $this->reach(
                    $exit_id,
                    $context,
                    $caller_open_assignments,
                    $caller_kept,
                    $caller_added,
                    $state,
                    $link,
                    -1,
                    $caller,
                );
            }
        } else {
            $exit = $this->entry_exits[$context][$exit_id . ' ' . $caller_open_assignments] ?? null;

            // the link is made only for a flow that gets recorded
            if ($exit !== null && ($caller_kept & ~$exit[2]) === 0 && ($caller_added & ~$exit[3]) === 0) {
                return;
            }

            $this->addEntryExit(
                $context,
                $exit_id,
                $caller_open_assignments,
                $state,
                $this->getLink($caller, $link),
                $caller_kept,
                $caller_added,
            );
        }
    }

    /**
     * Whether exit $exit_id is one of the function-like whose specialized node the call of state $caller entered:
     * that one is specialized for some of the calls the exit is (whether or not the call of $caller uses the exit
     * itself, that is whether it has a specialization of it: see TaintFlowGraph::connectSinksAndSources()).
     *
     * @psalm-external-mutation-free
     */
    private function isExitOfEntered(string $exit_id, int $caller): bool
    {
        $entered_id = $this->getNode($this->state_nodes[$caller])?->unspecialized_id;

        if ($entered_id === null) {
            return false;
        }

        if (!isset($this->exits_of_entered[$entered_id][$exit_id])) {
            $is_exit = false;

            foreach ($this->specializations[$exit_id] ?? [] as $specialization_key => $_) {
                if (isset($this->nodes[$entered_id . TaintFlowGraph::SPECIALIZATION_SEPARATOR . $specialization_key])) {
                    $is_exit = true;

                    break;
                }
            }

            $this->exits_of_entered[$entered_id][$exit_id] = $is_exit;
        }

        return $this->exits_of_entered[$entered_id][$exit_id];
    }

    /**
     * The body walk of $entry reached sink $sink_id from node $predecessor_id in state $state (seen through
     * the calls of $link, see buildTrace()). Like everything the walk reaches, that flow depends on the call
     * entering $entry, so it is a finding for every such call (see addSinkThroughCaller()).
     */
    private function addEntrySink(
        int $entry,
        string $sink_id,
        string $predecessor_id,
        int $state,
        int $link,
        int $kept,
        int $added,
    ): void {
        $sink_key = $sink_id . ' ' . $predecessor_id;
        $sink = $this->entry_sinks[$entry][$sink_key] ?? [$sink_id, 0, 0, []];
        $new_kept = $kept & ~$sink[1];
        $new_added = $added & ~$sink[2];

        if ($new_kept === 0 && $new_added === 0) {
            return;
        }

        $reaches = $sink[3];
        $reaches[] = [$state, $link, $new_kept, $new_added];
        $this->entry_sinks[$entry][$sink_key] = [$sink_id, $sink[1] | $new_kept, $sink[2] | $new_added, $reaches];

        foreach ($this->entry_callers[$entry] as $caller => $_) {
            $this->addSinkThroughCaller($sink_id, $state, $link, $new_kept, $new_added, $caller);
        }
    }

    /**
     * The walk of convergence $convergence reached sink $sink_id from node $predecessor_id in state $state (seen
     * through the calls of $link): reports the taints of the flows entering the convergence it keeps, and
     * those it adds, and records it to report those that the flows entering it later have.
     */
    private function addConvergenceSink(
        int $convergence,
        string $sink_id,
        string $predecessor_id,
        int $state,
        int $link,
        int $kept,
        int $added,
    ): void {
        $sink_key = $sink_id . ' ' . $predecessor_id;
        $sink = $this->entry_sinks[$convergence][$sink_key] ?? [$sink_id, 0, 0, []];
        $new_kept = $kept & ~$sink[1];
        $new_added = $added & ~$sink[2];

        if ($new_kept === 0 && $new_added === 0) {
            return;
        }

        $reaches = $sink[3];
        $reaches[] = [$state, $link, $new_kept, $new_added];
        $this->entry_sinks[$convergence][$sink_key] = [$sink_id, $sink[1] | $new_kept, $sink[2] | $new_added, $reaches];

        $taints = ((($this->entry_taints[$convergence] ?? 0) & $new_kept) | $new_added)
            & $this->sinks[$sink_id]->taints;

        if ($taints !== 0) {
            $this->reportFlow($sink_id, $state, -1, $link, $taints);
        }
    }

    /**
     * The taints of the flows of state $state, whatever call entered their context: its taints are those of
     * all the flows entering it (see addEntryTaints())
     *
     * @psalm-mutation-free
     */
    private function getUnionTaints(int $state): int
    {
        $context = $this->state_contexts[$state];

        return $context === -1
            ? $this->state_added[$state]
            : (($this->entry_taints[$context] ?? 0) & $this->state_kept[$state]) | $this->state_added[$state];
    }

    /**
     * The flows of state $caller entering entry $convergence have its taints (see getUnionTaints()): if it
     * is a convergence, reports those that the sinks its walk reached keep (see addConvergenceSink()). So do
     * the flows of its walk entering other entries.
     */
    private function addEntryTaints(int $convergence, int $caller): void
    {
        $new_taints = $this->getUnionTaints($caller) & ~($this->entry_taints[$convergence] ?? 0);

        if ($new_taints === 0 && isset($this->entry_taints[$convergence])) {
            return;
        }

        $this->entry_taints[$convergence] = ($this->entry_taints[$convergence] ?? 0) | $new_taints;

        for ($taint = 1, $remaining = $new_taints; $remaining !== 0; $taint <<= 1) {
            if (($remaining & $taint) !== 0) {
                $remaining &= ~$taint;
                $this->entry_taint_callers[$convergence][$taint] = $caller;
            }
        }

        // the sinks of a specialized call entry are reported through each call (see addEntrySink())
        $sinks = $this->entry_kinds[$convergence] === self::ENTRY_CALL ? [] : $this->entry_sinks[$convergence];

        foreach ($sinks as [$sink_id, , , $reaches]) {
            foreach ($reaches as [$state, $link, $kept]) {
                $reported = $new_taints & $kept & $this->sinks[$sink_id]->taints;

                if ($reported !== 0) {
                    $this->reportFlow($sink_id, $state, -1, $link, $reported);
                }
            }
        }

        // so do the flows of its walk entering other convergences
        foreach ($this->entry_taint_dependents[$convergence] ?? [] as $caller => $convergences) {
            foreach ($convergences as $dependent => $_) {
                $this->addEntryTaints($dependent, $caller);
            }
        }
    }

    /**
     * Reports a flow into a sink reached by the body walk of an entry, as seen from one call entering it. If
     * that call is itself made inside the body walk of an enclosing entry, the flow is a finding for every
     * call entering that one instead.
     */
    private function addSinkThroughCaller(
        string $sink_id,
        int $state,
        int $link,
        int $kept,
        int $added,
        int $caller,
    ): void {
        $caller_kept = $this->state_kept[$caller] & $kept;
        $caller_added = ($this->state_added[$caller] & $kept) | $added;
        $context = $this->state_contexts[$caller];
        $sink_taints = $this->sinks[$sink_id]->taints;

        if ($context === -1) {
            if (($caller_added & $sink_taints) !== 0) {
                $this->reportFlow($sink_id, $state, $caller, $link, $caller_added & $sink_taints);
            }

            return;
        }

        if ((($caller_kept | $caller_added) & $sink_taints) === 0) {
            return;
        }

        $predecessor_id = $this->state_nodes[$state];

        if ($this->entry_kinds[$context] !== self::ENTRY_CALL) {
            $this->addConvergenceSink(
                $context,
                $sink_id,
                $predecessor_id,
                $state,
                $this->getLink($caller, $link),
                $caller_kept,
                $caller_added,
            );

            return;
        }

        $sink = $this->entry_sinks[$context][$sink_id . ' ' . $predecessor_id] ?? null;

        // the link is made only for a flow that gets recorded
        if ($sink !== null && ($caller_kept & ~$sink[1]) === 0 && ($caller_added & ~$sink[2]) === 0) {
            return;
        }

        $this->addEntrySink(
            $context,
            $sink_id,
            $predecessor_id,
            $state,
            $this->getLink($caller, $link),
            $caller_kept,
            $caller_added,
        );
    }

    /**
     * The caller link (see buildTrace()) of the call of state $caller, enclosing those of $link
     *
     * @psalm-external-mutation-free
     */
    private function getLink(int $caller, int $link): int
    {
        $key = $caller . ' ' . $link;

        if (!isset($this->link_ids[$key])) {
            $this->link_ids[$key] = count($this->links);
            $this->links[] = [$caller, $link];
        }

        return $this->link_ids[$key];
    }

    /**
     * Reports the flows of taints $taints from state $state (seen through the call of state $caller if any,
     * and those of $link, see buildTrace()) into sink $sink_id, unless already reported.
     *
     * A flow is identified by its origin and by the node it enters the sink from: the origin keeps distinct
     * sources apart where they share that node (e.g. two calls to a specialized function), the node keeps
     * distinct call sites of the same source apart. Each taint kind is reported once per flow.
     */
    private function reportFlow(string $sink_id, int $state, int $caller, int $link, int $taints): void
    {
        $pointer_key = $state . ' ' . $caller . ' ' . $link;
        $looked_at = $this->reported_pointers[$sink_id][$pointer_key] ?? 0;
        $taints &= ~$looked_at;

        if ($taints === 0) {
            return;
        }

        $this->reported_pointers[$sink_id][$pointer_key] = $looked_at | $taints;

        // a value choosing the server of a URL can also inject any URL syntax in it, such as the `..` segments of
        // its path: each is reported as the most general issue alone
        if (($taints & TaintKind::INPUT_SSRF) !== 0) {
            $taints &= ~(TaintKind::INPUT_URL_COMPONENT | TaintKind::INPUT_URL_PATH);
        } elseif (($taints & TaintKind::INPUT_URL_COMPONENT) !== 0) {
            $taints &= ~TaintKind::INPUT_URL_PATH;
        }

        $sink = $this->sinks[$sink_id];
        $predecessor_id = $this->state_nodes[$state];

        if ($caller !== -1) {
            $link = $this->getLink($caller, $link);
        }

        for ($taint = 1; $taints !== 0; $taint <<= 1) {
            if (($taints & $taint) === 0) {
                continue;
            }

            $taints &= ~$taint;

            $trace = $this->buildTrace($state, $link, $taint);

            if ($trace === []) {
                continue;
            }

            $origin = $trace[0][0];
            $reported = $this->reported_flows[$sink_id][$predecessor_id][$origin] ?? 0;

            if (($reported & $taint) !== 0) {
                continue;
            }

            $this->reported_flows[$sink_id][$predecessor_id][$origin] = $reported | $taint | match ($taint) {
                TaintKind::INPUT_SSRF => TaintKind::INPUT_URL_COMPONENT | TaintKind::INPUT_URL_PATH,
                TaintKind::INPUT_URL_COMPONENT => TaintKind::INPUT_URL_PATH,
                default => 0,
            };

            $predecessor = null;

            foreach ($trace as [$id, $path_type]) {
                $node = $predecessor === null ? $this->sources[$id] ?? $this->getNode($id) : $this->getNode($id);

                if ($node === null) {
                    continue;
                }

                $predecessor = $predecessor === null
                    ? $node
                    : $node->withFlow(
                        $taint,
                        $predecessor,
                        $path_type === -1 ? [] : [$this->path_types[$path_type]],
                        null,
                    );
            }

            if ($predecessor !== null) {
                $this->graph->reportTaintedFlow($predecessor, $sink, $taint, $this->config, $this->codebase);
            }
        }
    }

    /**
     * Rebuilds the steps (node id and type of the edge it was reached through) of a flow of taint $taint
     * from its origin to state $state, seen through the calls of $link.
     *
     * Each state remembers the state it was first reached from, and those that gave it more taints later
     * on. Going back from $state, the trace follows the one that gave it $taint, and whether that taint is
     * one the state keeps of the call entering its context, or one it adds (see $state_kept): a trace
     * leaving the root of the body walk of an entry through a kept taint goes on with the call it was
     * entered by. That's the innermost call it left (a flow reported through the calls entering a body walk,
     * or that left it through them) if any, else the first call to enter it.
     *
     * @return list<array{string, int}>
     * @psalm-mutation-free
     */
    private function buildTrace(int $state, int $link, int $taint): array
    {
        $callers = $this->pushCallers([], $link);

        // the context the trace entered the walk it is in through: the filter whose calls it comes from
        $context = $this->state_contexts[$state];
        $jumped = [];

        $which = ($this->state_added[$state] & $taint) !== 0 ? self::TRACE_ADDED : self::TRACE_KEPT;
        $before = PHP_INT_MAX;
        $steps = [];

        while (true) {
            if (isset($this->root_entries[$state])
                && ($which !== self::TRACE_ADDED || $this->findReach($state, $taint, $which, $before) === null)
            ) {
                // the root of a body walk takes the place of the specialized node of the call entering it
                $entry = $this->root_entries[$state];

                $before = PHP_INT_MAX;

                if ($callers) {
                    $state = array_pop($callers);
                } else {
                    // one of the flows entering the walk with the taint, through the filter of it the trace came
                    $state = ($context !== -1 && $this->entry_bases[$context] === $entry
                        ? $this->findTaintCaller($context, $taint)
                        : null)
                        ?? $this->findTaintCaller($entry, $taint)
                        ?? $this->state_predecessors[$state];
                }

                $context = $this->state_contexts[$state];

                // back to a call the trace already left (going after a taint a convergence got first from
                // somewhere else, see findTaintCaller()): it ends there rather than loop
                if (isset($jumped[$state])) {
                    $steps[] = [$this->state_nodes[$state], -1];

                    break;
                }

                $jumped[$state] = true;

                if ($which !== self::TRACE_ANY) {
                    $which = $this->getTraced($state, $taint, $before);
                }

                continue;
            }

            [$predecessor, $link, $path_type, $sequence] = $this->findReach($state, $taint, $which, $before)
                ?? [
                    $this->state_predecessors[$state],
                    $this->state_links[$state] ?? 0,
                    $this->state_path_types[$state],
                    $this->state_sequence[$state],
                ];

            if ($predecessor === -1) {
                $steps[] = [$this->state_nodes[$state], -1];

                break;
            }

            if ($path_type !== -1) {
                $steps[] = [$this->state_nodes[$state], $path_type];
            }

            if ($link !== 0) {
                // the flow left the body walk of the calls of $link: what it adds is added in that walk, or
                // kept of the call (and added before it), or added by the edge it took past it
                $callers = $this->pushCallers($callers, $link);

                if ($which === self::TRACE_ADDED) {
                    $which = $this->getTraced($predecessor, $taint, $sequence);
                }
            } elseif ($which === self::TRACE_ADDED
                && $path_type !== -1
                && $this->findReach($predecessor, $taint, self::TRACE_ADDED, $sequence) === null
            ) {
                // the edge added it
                $which = self::TRACE_ANY;
            }

            $state = $predecessor;
            $before = $sequence;
        }

        $trace = [];

        for ($i = count($steps) - 1; $i >= 0; $i--) {
            $trace[] = $steps[$i];
        }

        return $trace;
    }

    /**
     * The flow entering entry $entry that gave it taint $taint first, or else the first flow entering it, or
     * null if none did yet.
     *
     * Following those, a trace leaves each walk for one that got the taint earlier, so it ends.
     *
     * @psalm-mutation-free
     */
    private function findTaintCaller(int $entry, int $taint): ?int
    {
        return $this->entry_taint_callers[$entry][$taint] ?? array_key_first($this->entry_callers[$entry]);
    }

    /**
     * Which taints of state $state $taint came from before sequence number $before (see buildTrace())
     *
     * @psalm-mutation-free
     */
    private function getTraced(int $state, int $taint, int $before): int
    {
        if ($this->findReach($state, $taint, self::TRACE_ADDED, $before) !== null) {
            return self::TRACE_ADDED;
        }

        if ($this->findReach($state, $taint, self::TRACE_KEPT, $before) !== null) {
            return self::TRACE_KEPT;
        }

        return self::TRACE_ANY;
    }

    /**
     * $callers, with the calls of $link on top
     *
     * @param list<int> $callers
     * @return list<int>
     * @psalm-mutation-free
     */
    private function pushCallers(array $callers, int $link): array
    {
        // a link starts with the outermost call: the innermost one ends up on top
        while ($link !== 0) {
            [$caller, $link] = $this->links[$link];
            $callers[] = $caller;
        }

        return $callers;
    }

    /**
     * How state $state was reached, before sequence number $before, by a flow giving it $taint (as one of
     * the taints $which), or null if none did
     *
     * @return array{int, int, int, int}|null
     * @psalm-mutation-free
     */
    private function findReach(int $state, int $taint, int $which, int $before): ?array
    {
        $first_sequence = $this->state_sequence[$state];

        if ($first_sequence >= $before) {
            return null;
        }

        $later_kept = 0;
        $later_added = 0;

        foreach ($this->later_reaches[$state] ?? [] as [, , , , $kept, $added]) {
            $later_kept |= $kept;
            $later_added |= $added;
        }

        if ($which === self::TRACE_ANY
            || ($which === self::TRACE_KEPT && ($this->state_kept[$state] & ~$later_kept & $taint) !== 0)
            || ($which === self::TRACE_ADDED && ($this->state_added[$state] & ~$later_added & $taint) !== 0)
        ) {
            return [
                $this->state_predecessors[$state],
                $this->state_links[$state] ?? 0,
                $this->state_path_types[$state],
                $first_sequence,
            ];
        }

        foreach ($this->later_reaches[$state] ?? [] as [$predecessor, $link, $path_type, $sequence, $kept, $added]) {
            if ($sequence >= $before) {
                break;
            }

            if (($which === self::TRACE_KEPT && ($kept & $taint) !== 0)
                || ($which === self::TRACE_ADDED && ($added & $taint) !== 0)
            ) {
                return [$predecessor, $link, $path_type, $sequence];
            }
        }

        return null;
    }
}
