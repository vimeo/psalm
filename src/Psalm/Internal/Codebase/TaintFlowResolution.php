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

use function array_filter;
use function array_key_exists;
use function array_key_first;
use function array_map;
use function array_merge;
use function array_pop;
use function array_slice;
use function array_values;
use function count;
use function explode;
use function implode;
use function in_array;
use function ksort;
use function max;
use function min;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strpos;
use function substr;

use const PHP_EOL;
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
    private const PROPERTY_FAMILY = 1;

    /**
     * The prefix of the type of an edge from an array to the array it becomes once its value under a key is replaced
     * (see ArrayAssignmentAnalyzer::getOverwritePathType())
     */
    private const OVERWRITE_PREFIX = 'arrayvalue-overwrite-';

    /**
     * The class (see getClass()) of an open assignment of an array key: a fetch of an array value ignores
     * it, a fetch of an array key doesn't.
     */
    private const KEY_CLASS = 'key';

    /**
     * The class (see getAssignmentClass()) of an open assignment of the calls entering a filter not split by class
     * (see tracksClasses()), which know it only through the calls entering them: a fetch observing it in the walk
     * of a call made there goes on per fetched key (see passesFetch()).
     */
    private const DEFERRED_CLASS = '?';

    /**
     * The class (see getAssignmentClass()) of an open assignment of the calls entering a filter split by class, for
     * all of the calls whose class there is that of a key known exactly, not only by its start (see getClass() and
     * DataFlowGraph::keysMayBeEqual()): a fetch observing it goes on in the filter for the calls of the class of the
     * key it fetches, the only one passing it, or of its start (see getFilter()). A filter for each key would make
     * one for every key the arrays reaching the entry hold there, and for every key of another open assignment in
     * each of those, though the walks observing them fetch few of them.
     */
    private const EXACT_KEY_CLASS = '?:';

    /**
     * The class of an open assignment of an array value under a key not known: a fetch of an array value takes
     * it, under any key, but a fetch of an array key doesn't, as the keys of the array don't hold it.
     */
    private const UNKEYED_VALUE_CLASS = 'value';

    /**
     * The class of the open assignment a value has in the string a builtin encodes all of it in (see
     * DataFlowGraph::SERIALIZATION_PATH_TYPE): any fetch from the string takes it.
     */
    private const SERIALIZATION_CLASS = 'serialization';

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
     * The element slots of a flow (see $open_assignment_slots) that aren't ids of $slots: none; in the body
     * walk of a specialized call, that of the call entering it; and none in such a walk once the flow went
     * through a node the value may outlive the call in (see scopeSlot()).
     */
    private const NO_SLOT = -1;
    private const INHERITED_SLOT = -2;
    private const BLOCKED_SLOT = -3;

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
     * it in more forget what they would only use to tell apart deeper fetches. On Psalm's own code, 1024 made
     * 14M states, most of the 20 GB its self-analysis took: past 16, a fetch takes the flows of a node whose keys
     * it would tell apart.
     */
    private const WIDENING_STATES = 16;

    /**
     * How many states a node must have for the flows reaching it in more to forget all their open assignments, not
     * only those below their innermost one (see widenOpenAssignments()). Widened flows are still told apart by the
     * key of their innermost open array assignment: a node of the code that handles a render array of any keys (a
     * Drupal `$form`, passed through many functions) is reached with each of them, in each of the contexts reaching
     * it. Past those, a fetch takes the flows of a node whatever the key it was assigned under.
     */
    private const FORGETTING_STATES = 64;

    /**
     * How many filters an entry has at most (see getFilter()): each fact a filter knows about the calls entering
     * it can be combined with the others, in a filter of the filter, so they grow with the product of the keys
     * fetched at each depth (a constructor of a value object reading every property of its arguments, ...). Past
     * those, a fetch observing an open assignment of the calls entering the walk doesn't ignore it: the flow may
     * take taints it doesn't have, but takes all those it has.
     */
    private const MAX_FILTERS = 64;

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
     * How many facts (see addEntry()) a filter can know and still be split by the class of another open
     * assignment of the calls entering it (see tracksClasses())
     */
    private const MAX_TRACKING_FILTER_FACTS = 1;

    /**
     * How many convergences of a node know the innermost open assignments of the flows entering them at most
     * (see getConvergenceOpenAssignments()).
     *
     * 8 rather than 32: on Psalm's own code, 32 made 630k convergences, two thirds of the memory of the resolution.
     */
    private const MAX_CONVERGENCE_KEYS = 8;

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
     *     the foreach loop whose element values it fetches, or that it assigns under the key of the element
     *     (see getForeachMarker() in ForeachAnalyzer), or null,
     *     whether it fetches them,
     * ]
     *
     * @var list<array{int, ?string, int, int, ?string, bool}>
     */
    private array $path_type_effects = [];

    /**
     * The path types converting to a scalar a value that may be an array (see
     * TaintFlowGraph::ARRAY_CONVERSION_SUFFIX)
     *
     * @var array<int, true>
     */
    private array $array_conversion_path_types = [];

    /**
     * The path types of the edges to what an array becomes once its value under a key is replaced (see
     * getNextOpenAssignments() and addOverwrite())
     *
     * @var array<int, true>
     */
    private array $overwrite_path_types = [];

    /**
     * Open assignments => key (see internOpenAssignments())
     *
     * @var array<string, int>
     */
    private array $open_assignment_ids = [];

    /**
     * Open assignments key => [
     *     for each expression type, the open assignments the flow made from the outermost to the innermost,
     *     as path type ids, or as minus the depth of one of the call entering its context it put back (see
     *     applyPathType()),
     *     for each expression type, how many of those of the call entering its context it closed, FORGOTTEN
     *     if it doesn't know, or NO_CALL outside of any context,
     * ]
     *
     * @var list<array{array{list<int>, list<int>}, array{int, int}}>
     */
    private array $open_assignments = [];

    /**
     * Open assignments key => the element slot of the flows: the element of the array a foreach loop iterates
     * over whose value they went through, as an id of $slots, so that an assignment in the body of the loop
     * under the key of the element (see applyPathType()) puts their open assignments back as they were in the
     * array; or NO_SLOT, INHERITED_SLOT or BLOCKED_SLOT.
     *
     * @var list<int>
     */
    private array $open_assignment_slots = [];

    /**
     * Slot id => [the marker of the foreach loop, the open assignment of the element the value fetch of the
     * loop closed, as a path type id]
     *
     * @var list<array{string, int}>
     */
    private array $slots = [];

    /** @var array<string, int> */
    private array $slot_ids = [];

    /**
     * Foreach loop marker => [path of its file, start and end of its function (or of the loop outside of
     * any), start and end of the loop, start of its body]
     *
     * @var array<string, array{string, int, int, int, int, int}>
     */
    private array $foreach_markers = [];

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
     * Widening depth (1 at WIDENING_STATES, 0 at FORGETTING_STATES: how many of their innermost open assignments of
     * each expression type the flows keep) => open assignments key => the open assignments widened to that depth
     * (see widenOpenAssignments())
     *
     * @var array<int, array<int, int>>
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
     * Entry => expression type . ' ' . depth . ' ' . fetched key => [the filter of the entry for the calls
     * whose open assignment there a fetch of that key doesn't ignore (see getFilter()), expression type, depth,
     * fetched key]
     *
     * @var array<int, array<string, array{int, int, int, string}>>
     */
    private array $entry_filters = [];

    /**
     * Base entry and facts => the filter knowing them (see addEntry())
     *
     * @var array<string, int>
     */
    private array $filter_ids = [];

    /**
     * Entry => 'conversion ' . the open assignments closed of each expression type => the filter of the entry for
     * the calls a conversion of an array doesn't drop (see getConversionFilter())
     *
     * @var array<int, array<string, int>>
     */
    private array $entry_conversion_filters = [];

    /**
     * Entry => ('anywhere ' or 'not anywhere ') . the open assignments closed of each expression type => the filter
     * of the entry for the calls a flow going through an overwrite is anywhere in an array of, or for the others
     * (see getAnywhereFilter())
     *
     * @var array<int, array<string, int>>
     */
    private array $entry_anywhere_filters = [];

    /**
     * Filter => how many of their open assignments of each expression type the flows converting an array
     * closed, for the calls entering it whose innermost open assignment left isn't one of an array (see
     * getConversionFilter())
     *
     * @var array<int, array{int, int}>
     */
    private array $filter_conversions = [];

    /**
     * Filter => [how many of their open assignments of each expression type the flows going through an overwrite
     * closed, whether it is for the calls they are anywhere in an array of (see getAnywhereFilter())]
     *
     * @var array<int, array{array{int, int}, bool}>
     */
    private array $filter_anywhere = [];

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
     * Entry => the conversions of an array its calls pass, if it is a filter for them (see getConversionFilter()),
     * and the overwrites they are anywhere in an array through or not (see getAnywhereFilter()), which tell it apart
     * from the filters with the same facts (see addEntry())
     *
     * @var list<string>
     */
    private array $entry_conversions = [];

    /**
     * Unspecialized node entered by a specialized call => exit node id => whether the exit is one of the
     * function-like of the node entered (see isExitOfEntered())
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
     * Entry => the flow entering it that gave it some taints first, which traces go back to (see buildTrace())
     * => those taints. A few flows give an entry all its taints: keyed by taint, it took millions of entries.
     *
     * @var array<int, array<int, int>>
     */
    private array $entry_taint_callers = [];

    /**
     * Entry => the states of its walk that left the calls entering it (see leaveCalls()) => the node they left
     * them at and their open assignments outside of any call
     *
     * @var array<int, array<int, array{string, int}>>
     */
    private array $entry_leaving_states = [];

    /**
     * Entry => how many filters were made for it (see MAX_FILTERS)
     *
     * @var array<int, int>
     */
    private array $entry_filter_counts = [];

    /**
     * Entry => the flows of its walk entering another entry: (state << 32 | that entry) => true. One flat map per
     * entry: a nested array per state took a third of the memory of the resolution on large code bases.
     *
     * @var array<int, array<int, true>>
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
     * Entry => the calls entering it, as their state, or as -1 minus that of a copy entering it as such (see
     * addEntryCaller()) => how many times they had got more taints (see $later_reaches) when they last did, for a
     * copy shifted 16 bits up, above the positions it was copied for (see getPositionBit())
     *
     * @var array<int, array<int, int>>
     */
    private array $entry_caller_versions = [];

    /**
     * State of a call => the states of its copies (see copyToFilter()) => true
     *
     * @var array<int, array<int, true>>
     */
    private array $state_copies = [];

    /**
     * State of a copy of a call into a filter of its context (see copyToFilter()) that no other flow reached
     * => the positions of the open assignments of the calls entering that context it was copied for, as a bit
     * set (see getPositionBit())
     *
     * @var array<int, int>
     */
    private array $copy_positions = [];

    /**
     * The bit of the position a call is being copied for (see copyToFilter()), or 0
     */
    private int $copied_position = 0;

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
     * Ids of the states that only flows widened by widenOpenAssignments() reached, or flows from such a state: they
     * pass fetches of keys other than those they were assigned under, so a sink they reach is reported once per
     * taint kind (see reportFlow())
     *
     * @var array<int, true>
     */
    private array $widened_states = [];

    /**
     * Sink id => taints of the issues added for it so far (not those of flows into a sink where issues aren't
     * reported, or that were suppressed)
     *
     * @var array<string, int>
     */
    private array $reported_sink_taints = [];

    /**
     * @param array<string, array<string, Path>> $forward_edges
     * @param array<string, DataFlowNode> $nodes
     * @param array<string, DataFlowNode> $sources
     * @param array<string, DataFlowNode> $sinks
     * @param array<string, array<string, string>> $specializations
     * @param array<string, true> $specialized_calls
     * @param array<string, true> $despecialized_calls
     * @param array<string, true> $shared_state see TaintFlowGraph::addSharedState()
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
        private readonly array $shared_state,
        private readonly Config $config,
        private readonly ProjectAnalyzer $project_analyzer,
        private readonly Codebase $codebase,
    ) {
        // NO_OPEN_ASSIGNMENTS and CALL_OPEN_ASSIGNMENTS
        $this->internOpenAssignments([[], []], [self::NO_CALL, self::NO_CALL]);
        $this->internOpenAssignments([[], []], [0, 0], self::INHERITED_SLOT);
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

        $progress->debug('Taint flow resolution reached ' . count($this->state_nodes) . ' states' . PHP_EOL);
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
        [$observed_family, , $closed_family, $added_family, $foreach_marker, $fetches_elements]
            = $this->path_type_effects[$path_type];

        if ($foreach_marker !== null && $fetches_elements) {
            // the element slot is the open assignment it closes (see applyPathType())
            $observed_family = $closed_family;
        }

        if (isset($this->array_conversion_path_types[$path_type])) {
            // it observes the innermost open assignment of every expression type (see getNextOpenAssignments())
            $result = $depths;

            foreach (self::FAMILIES as $family => $_) {
                $result = self::maxDepths($result, 1 << ($family * self::DEPTH_BITS));
            }

            return $result;
        }

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

        if (str_ends_with($path_type, TaintFlowGraph::ARRAY_CONVERSION_SUFFIX)) {
            $this->array_conversion_path_types[$id] = true;
        } elseif (str_starts_with($path_type, self::OVERWRITE_PREFIX)) {
            $this->overwrite_path_types[$id] = true;
        }

        return $id;
    }

    /**
     * What an edge of type $path_type does with the open assignments of a flow, as shouldIgnoreFetch() and
     * appendPathType() treat them, except that the array keys are at the level of the array values (see
     * FAMILIES). The edge to what an array becomes once its value under a key is replaced (see
     * ArrayAssignmentAnalyzer::getOverwritePathType()) has that key prefixed with '!' as observed key, and observes,
     * closes and adds nothing (see getNextOpenAssignments()).
     *
     * @return array{int, ?string, int, int, ?string, bool}
     * @psalm-pure
     */
    private static function getPathTypeEffects(string $path_type): array
    {
        if ($path_type === 'arraykey-assignment') {
            return [-1, null, -1, self::ARRAY_FAMILY, null, false];
        }

        if ($path_type === 'arraykey-fetch') {
            // it fetches the key '' (see classPassesFetch()): not the key of a value assigned under a known key
            return [self::ARRAY_FAMILY, '', self::ARRAY_FAMILY, -1, null, false];
        }

        $foreach_marker = null;
        $fetches_elements = str_starts_with($path_type, 'arrayvalue-fetch@');

        if ($fetches_elements || str_starts_with($path_type, 'arrayvalue-assignment@')) {
            $foreach_marker = substr($path_type, (int) strpos($path_type, '@') + 1);
        }

        if (str_starts_with($path_type, self::OVERWRITE_PREFIX)) {
            return [-1, '!' . substr($path_type, strlen(self::OVERWRITE_PREFIX)), -1, -1, null, false];
        }

        if ($path_type === DataFlowGraph::SERIALIZATION_PATH_TYPE) {
            // what a value holds, all in the string it is encoded in (see getClass())
            return [-1, null, -1, self::ARRAY_FAMILY, null, false];
        }

        if ($path_type === DataFlowGraph::UNSERIALIZATION_PATH_TYPE) {
            // the value decoded from a string is the one encoded in it: that closes its innermost open array
            // assignment, whatever it is, without a fetch observing it (see applyPathType())
            return [-1, null, self::ARRAY_FAMILY, -1, null, false];
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

        return [$observed_family, $observed_key, $closed_family, $added_family, $foreach_marker, $fetches_elements];
    }

    /**
     * @param array<int, list<int>> $made
     * @param array<int, int> $closed
     * @psalm-external-mutation-free
     */
    private function internOpenAssignments(array $made, array $closed, int $slot = self::NO_SLOT): int
    {
        $key = (string) $slot . '|';

        foreach (self::FAMILIES as $family => $_) {
            $key .= implode(',', $made[$family] ?? []) . '|' . ($closed[$family] ?? self::NO_CALL) . '|';
        }

        if (isset($this->open_assignment_ids[$key])) {
            return $this->open_assignment_ids[$key];
        }

        $id = count($this->open_assignments);
        $this->open_assignment_ids[$key] = $id;
        $this->open_assignments[] = [
            [$made[self::ARRAY_FAMILY] ?? [], $made[self::PROPERTY_FAMILY] ?? []],
            [$closed[self::ARRAY_FAMILY] ?? self::NO_CALL, $closed[self::PROPERTY_FAMILY] ?? self::NO_CALL],
        ];
        $this->open_assignment_slots[] = $slot;

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

        // the innermost open array assignment the flow made, if any: the overwrites of a flow anywhere in an array
        // (see addOverwrite()) are no assignment, and only ever below the others
        $innermost = $array_assignments
            && !isset($this->overwrite_path_types[$array_assignments[count($array_assignments) - 1]])
            ? $array_assignments[count($array_assignments) - 1]
            : -1;
        $property_assignments = $made[self::PROPERTY_FAMILY];
        $innermost_property = $property_assignments ? $property_assignments[count($property_assignments) - 1] : -1;

        if (isset($this->array_conversion_path_types[$path_type])) {
            // An array converts to "Array", or to 0/1, never to what it holds: the conversion takes nothing the
            // flow put in an array as its innermost open assignment, under a key or as a key. That's the one it
            // made last, past any open assignment of a property it made: those of the call entering its context
            // are all outer ones. How the two expression types interleave where it made both isn't known, so the
            // flow goes on, and where it made none, the innermost one is the call's (see passesConversion()). A
            // value encoded in a string (see DataFlowGraph::SERIALIZATION_PATH_TYPE) isn't in an array, and a flow
            // anywhere in an array is all of one.
            if ($innermost >= 0 || $property_assignments) {
                $next = $innermost >= 0
                    && !$property_assignments
                    && $this->path_types[$innermost] !== DataFlowGraph::SERIALIZATION_PATH_TYPE
                    ? self::IGNORED
                    : $open_assignments;
            } elseif ($array_assignments
                || $closed[self::ARRAY_FAMILY] === self::NO_CALL
                || $closed[self::ARRAY_FAMILY] === self::FORGOTTEN
                || $closed[self::PROPERTY_FAMILY] === self::NO_CALL
                || $closed[self::PROPERTY_FAMILY] === self::FORGOTTEN
            ) {
                // there is none, or the flow doesn't know it
                $next = $open_assignments;
            } else {
                $next = self::OBSERVES_CALL;
            }
        } elseif ($observed_family === -1 && $observed_key !== null) {
            // The replacement of the value under a key (see getPathTypeEffects()) stops a flow of what was
            // assigned under that key, where the flow knows that's its innermost open array assignment. Any other
            // goes on, also one that doesn't know it: deciding that for each call entering its context, in
            // filters, would lose the precision of the fetches in them (see getAssignmentClass()) for no flow
            // they would take otherwise. Nor is it an observation keeping the open assignments of the flows
            // reaching it (see computeObservableDepths()): where no fetch past it can observe the one it would
            // stop, the flow goes on as it did through the plain edge it replaces. A flow anywhere in the array
            // isn't under that key anymore, though (see addOverwrite()).
            if ($innermost >= 0) {
                $next = self::classPassesFetch($this->getClass($innermost, self::ARRAY_FAMILY), $observed_key)
                    ? $open_assignments
                    : self::IGNORED;
            } else {
                $anywhere = $this->isAnywhereInArray($made, $closed);

                $next = $anywhere === null
                    ? self::OBSERVES_CALL
                    : ($anywhere ? $this->addOverwrite($open_assignments, $path_type) : $open_assignments);
            }
        } elseif ($innermost >= 0
            && $this->path_types[$innermost] === 'arraykey-assignment'
            && ($this->path_types[$path_type] === 'arrayvalue-fetch'
                || str_starts_with($this->path_types[$path_type], 'arrayvalue-fetch@'))
        ) {
            // The value of an item under an unknown key doesn't take what was assigned to its key either. Only
            // where the flow knows that's the innermost one: an unknown key is fetched too often for the walks
            // to be told apart by it in filters (see getFilter()).
            $next = self::IGNORED;
        } elseif ($observed_family === -1) {
            $next = $this->applyPathType($open_assignments, $path_type);
        } elseif ($made[$observed_family] && $made[$observed_family][count($made[$observed_family]) - 1] < 0) {
            // one of the call put back
            $next = self::OBSERVES_CALL;
        } elseif ($this->isOverwrittenAnywhere($made)) {
            // A fetch from an array the flow is anywhere in but under the keys overwritten (see addOverwrite()):
            // not of one of those, and else anywhere in what it fetches (see applyPathType())
            $overwrite = $observed_family === self::ARRAY_FAMILY && $observed_key !== ''
                ? $this->path_type_ids[self::OVERWRITE_PREFIX . (string) $observed_key] ?? -1
                : -1;

            $next = $overwrite !== -1 && in_array($overwrite, $array_assignments, true)
                ? self::IGNORED
                : $this->applyPathType($open_assignments, $path_type);
        } elseif ($observed_family === self::ARRAY_FAMILY ? $innermost !== -1 : $innermost_property !== -1) {
            $next = self::classPassesFetch(
                $this->getClass(
                    $observed_family === self::ARRAY_FAMILY ? $innermost : $innermost_property,
                    $observed_family,
                ),
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
     * The depth in the open assignments of the call entering their context of the innermost open assignment of
     * type $family of flows with open assignments $open_assignments, which is one of those of the call
     *
     * @psalm-mutation-free
     */
    private function getObservedCallDepth(int $open_assignments, int $family): int
    {
        [$made, $closed] = $this->open_assignments[$open_assignments];
        $innermost = $made[$family] ? $made[$family][count($made[$family]) - 1] : 0;

        return $innermost < 0 ? -$innermost : $closed[$family] + 1;
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
        [, , $closed_family, $added_family, $foreach_marker, $fetches_elements] = $this->path_type_effects[$path_type];

        if ($closed_family === -1 && $added_family === -1) {
            return $open_assignments;
        }

        [$made, $closed] = $this->open_assignments[$open_assignments];
        $slot = $this->open_assignment_slots[$open_assignments];

        if ($closed_family !== -1) {
            $array_assignments = $made[self::ARRAY_FAMILY];
            $made_overwrites = $array_assignments
                && isset($this->overwrite_path_types[$array_assignments[count($array_assignments) - 1]]);

            if ($made_overwrites && !$made[self::PROPERTY_FAMILY]) {
                // Anywhere in an array but under the keys overwritten (see addOverwrite()): a fetch takes the flow
                // anywhere in what it fetches, where those keys aren't overwritten, nor those overwritten in the
                // array of the call entering its context (see composeOpenAssignments()). The decoding of a string
                // closes nothing it put in one.
                if ($this->path_types[$path_type] !== DataFlowGraph::UNSERIALIZATION_PATH_TYPE) {
                    $made[self::ARRAY_FAMILY] = [];

                    if ($closed[self::ARRAY_FAMILY] !== self::NO_CALL
                        && $closed[self::ARRAY_FAMILY] !== self::FORGOTTEN
                    ) {
                        $closed[self::ARRAY_FAMILY]++;
                    }
                }
            } elseif ($closed_family === self::ARRAY_FAMILY
                ? $array_assignments && !$made_overwrites
                : $made[$closed_family] !== []
            ) {
                $closed_assignment = array_pop($made[$closed_family]);

                if ($foreach_marker !== null && $fetches_elements && $closed_assignment !== null) {
                    $slot = $this->getSlot($foreach_marker, $closed_assignment);
                }
            } elseif ($closed[$closed_family] !== self::NO_CALL && $closed[$closed_family] !== self::FORGOTTEN) {
                // past MAX_CALL_OPEN_ASSIGNMENT_DEPTH, FORGOTTEN
                $closed[$closed_family]++;

                if ($foreach_marker !== null && $fetches_elements && $closed[$closed_family] !== self::FORGOTTEN) {
                    // one of the call entering the context, by its depth there
                    $slot = $this->getSlot($foreach_marker, -$closed[$closed_family]);
                }
            }
        }

        if ($added_family !== -1) {
            if ($foreach_marker !== null
                && !$fetches_elements
                && $slot >= 0
                && $this->slots[$slot][0] === $foreach_marker
            ) {
                // the value of the element, or what was made of it, back under its key
                $made[$added_family][] = $this->slots[$slot][1];
                $slot = self::NO_SLOT;
            } else {
                $made[$added_family][] = $path_type;
            }

            self::capMadeOpenAssignments($made, $closed, $added_family);
        }

        return $this->internOpenAssignments($made, $closed, $slot);
    }

    /**
     * The id of the slot of the element of the foreach loop marked $foreach_marker whose open assignment, as a
     * path type id, is $assignment
     *
     * @psalm-external-mutation-free
     */
    private function getSlot(string $foreach_marker, int $assignment): int
    {
        $key = $assignment . ' ' . $foreach_marker;

        if (!isset($this->slot_ids[$key])) {
            $this->slot_ids[$key] = count($this->slots);
            $this->slots[] = [$foreach_marker, $assignment];
        }

        return $this->slot_ids[$key];
    }

    /**
     * The open assignments $open_assignments, with element slot $slot, of a flow reaching node $node_id: without
     * the slot if the value of the element may outlive the iteration it was fetched in there. That's where it
     * may be kept: in a node without a location (a property, ...), in a variable of the body of the loop, which
     * the next iterations see, or in the function of the loop out of the loop, the function being called again
     * through its parameters and left through its return value. In the body walk of a specialized call, only
     * the first is known.
     *
     * @psalm-external-mutation-free
     */
    private function scopeSlot(int $open_assignments, int $slot, string $node_id): int
    {
        $node = $this->getNode($node_id);
        $location = $node?->code_location;

        if ($node === null || $location === null) {
            $scoped_slot = $slot === self::INHERITED_SLOT ? self::BLOCKED_SLOT : self::NO_SLOT;
        } elseif ($slot >= 0) {
            $foreach_marker = $this->slots[$slot][0];
            [$file_path, $function_start, $function_end, $start, $end, $body_start]
                = $this->foreach_markers[$foreach_marker] ??= self::parseForeachMarker($foreach_marker);

            $scoped_slot = $location->file_path === $file_path
                && $location->raw_file_start >= $function_start
                && $location->raw_file_end <= $function_end
                && ($location->raw_file_start < $start
                    || $location->raw_file_end > $end
                    || ($location->raw_file_start >= $body_start && str_starts_with($node->label, '$')))
                ? self::NO_SLOT
                : $slot;
        } else {
            return $open_assignments;
        }

        if ($scoped_slot === $slot) {
            return $open_assignments;
        }

        [$made, $closed] = $this->open_assignments[$open_assignments];

        return $this->internOpenAssignments($made, $closed, $scoped_slot);
    }

    /**
     * @return array{string, int, int, int, int, int}
     * @psalm-pure
     */
    private static function parseForeachMarker(string $foreach_marker): array
    {
        $parts = explode(':', $foreach_marker, 6);

        return [
            $parts[5] ?? '',
            (int) $parts[0],
            (int) ($parts[1] ?? 0),
            (int) ($parts[2] ?? 0),
            (int) ($parts[3] ?? 0),
            (int) ($parts[4] ?? 0),
        ];
    }

    /**
     * Whether a flow that made the open assignments $made is anywhere in an array but under the keys it went
     * through the overwrites of (see addOverwrite()): the open assignments it made are only those overwrites.
     *
     * @param array<int, list<int>> $made
     * @psalm-mutation-free
     */
    private function isOverwrittenAnywhere(array $made): bool
    {
        $array_assignments = $made[self::ARRAY_FAMILY] ?? [];

        return $array_assignments !== []
            && ($made[self::PROPERTY_FAMILY] ?? []) === []
            && isset($this->overwrite_path_types[$array_assignments[count($array_assignments) - 1]]);
    }

    /**
     * Whether a flow that made the open assignments $made, and closed $closed of those of the call entering its
     * context, is anywhere in an array, rather than under a key or in a property of it: it has no open
     * assignment but overwrites (see addOverwrite()). Null if that depends on the open assignments of the call,
     * which it doesn't know (see passesAnywhere()). A flow that forgot those of an array doesn't know it is.
     *
     * One that only forgot those of a property still asks the call: they are forgotten where no fetch of a
     * property can observe them (see truncateOpenAssignments()), and a flow taken anywhere in the array only
     * stops at a fetch of the key overwritten, which takes the value that replaced it, also where the flow of the
     * call was in a property of the array (an object implementing ArrayAccess). Else every flow entering a
     * specialized call with a whole array would go on through the overwrites in its body.
     *
     * @param array{list<int>, list<int>} $made
     * @param array{int, int} $closed
     * @psalm-mutation-free
     */
    private function isAnywhereInArray(array $made, array $closed): ?bool
    {
        if ($made[self::PROPERTY_FAMILY] ?? []) {
            return false;
        }

        if ($made[self::ARRAY_FAMILY] ?? []) {
            return $this->isOverwrittenAnywhere($made);
        }

        if ($closed[self::ARRAY_FAMILY] === self::NO_CALL) {
            return true;
        }

        if ($closed[self::ARRAY_FAMILY] === self::FORGOTTEN) {
            return false;
        }

        return null;
    }

    /**
     * The open assignments $open_assignments of a flow anywhere in an array (see isAnywhereInArray()) after it
     * goes through the edge of type $path_type to what the array becomes once its value under a key is replaced:
     * the flow is anywhere in it but under that key. It keeps the overwrite among its open assignments, which a
     * fetch of that key drops it at, and any other fetch closes with the others (see getNextOpenAssignments()).
     * A key overwritten again is kept once, or a loop overwriting it would make new open assignments forever.
     *
     * @psalm-external-mutation-free
     */
    private function addOverwrite(int $open_assignments, int $path_type): int
    {
        [$made, $closed] = $this->open_assignments[$open_assignments];

        if (in_array($path_type, $made[self::ARRAY_FAMILY], true)) {
            return $open_assignments;
        }

        $made[self::ARRAY_FAMILY][] = $path_type;
        self::capMadeOpenAssignments($made, $closed, self::ARRAY_FAMILY);

        return $this->internOpenAssignments($made, $closed, $this->open_assignment_slots[$open_assignments]);
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
                $call_left = array_slice($call_made[$family], 0, $count - $flow_closed_count);

                // A fetch the flow took anywhere in the array of the call takes it out of the overwrites of the call
                // too (see applyPathType()), as may one of a property of it
                if ($family === self::ARRAY_FAMILY
                    && $call_left
                    && isset($this->overwrite_path_types[$call_left[count($call_left) - 1]])
                    && (($flow_closed_count > 0
                            && isset($this->overwrite_path_types[$call_made[$family][count($call_left)]]))
                        || $flow_closed[self::PROPERTY_FAMILY] > count($call_made[self::PROPERTY_FAMILY]))
                ) {
                    $call_left = [];
                }

                $made[$family] = array_merge($call_left, $flow_made[$family]);
                $closed[$family] = $call_closed[$family];
            } else {
                $made[$family] = $flow_made[$family];
                $closed[$family] = $call_closed[$family] === self::NO_CALL || $call_closed[$family] === self::FORGOTTEN
                    ? $call_closed[$family]
                    : min(self::FORGOTTEN, $call_closed[$family] + $flow_closed_count - $count);
            }

            $made[$family] = array_map(
                fn(int $assignment): int => $assignment < 0
                    ? $this->resolvePutBackAssignment($call_made[$family], $call_closed[$family], -$assignment)
                    : $assignment,
                $made[$family],
            );

            self::capMadeOpenAssignments($made, $closed, $family);
        }

        $flow_slot = $this->open_assignment_slots[$open_assignments];
        $call_slot = $this->open_assignment_slots[$call_open_assignments];

        if ($flow_slot === self::INHERITED_SLOT) {
            $slot = $call_slot;
        } elseif ($flow_slot >= 0) {
            [$foreach_marker, $assignment] = $this->slots[$flow_slot];
            $slot = $assignment < 0
                ? $this->getSlot(
                    $foreach_marker,
                    $this->resolvePutBackAssignment(
                        $call_made[self::ARRAY_FAMILY] ?? [],
                        $call_closed[self::ARRAY_FAMILY] ?? self::NO_CALL,
                        -$assignment,
                    ),
                )
                : $flow_slot;
        } else {
            $slot = $call_slot === self::INHERITED_SLOT || $call_slot === self::BLOCKED_SLOT
                ? self::BLOCKED_SLOT
                : self::NO_SLOT;
        }

        $result = $this->internOpenAssignments($made, $closed, $slot);
        $this->open_assignment_compositions[$call_open_assignments][$open_assignments] = $result;

        return $result;
    }

    /**
     * The open assignments $open_assignments of a flow reaching a node with WIDENING_STATES states: only their
     * innermost open assignment of each expression type, and none of those of the call entering their context
     * known; of a flow reaching a node with FORGETTING_STATES states ($depth 0): none at all. A fetch only ignores
     * an open assignment the flow knows, so forgetting some only lets the flow go on through more fetches.
     *
     * Nor does a widened flow know its element slot: without it, an assignment of the element of a loop under its
     * key (see applyPathType()) is one under a key not known, which every fetch takes. The slots of a node's flows
     * would otherwise multiply with their innermost open assignment, by the loops of the code they go through.
     *
     * @param int $depth how many of their innermost open assignments of each expression type the flows keep: 1 at
     *     WIDENING_STATES, 0 at FORGETTING_STATES
     * @psalm-external-mutation-free
     */
    private function widenOpenAssignments(int $open_assignments, int $depth): int
    {
        if (isset($this->open_assignment_widenings[$depth][$open_assignments])) {
            return $this->open_assignment_widenings[$depth][$open_assignments];
        }

        [$made, $closed] = $this->open_assignments[$open_assignments];

        foreach (self::FAMILIES as $family => $_) {
            $made[$family] ??= [];
            $closed[$family] ??= self::NO_CALL;
            self::capOpenAssignments($made, $closed, $family, $depth);

            if ($closed[$family] !== self::NO_CALL) {
                $closed[$family] = self::FORGOTTEN;
            }
        }

        $slot = $this->open_assignment_slots[$open_assignments];

        return $this->open_assignment_widenings[$depth][$open_assignments] = $this->internOpenAssignments(
            $made,
            $closed,
            $slot >= 0 ? self::NO_SLOT : $slot,
        );
    }

    /**
     * The open assignment at depth $depth of the open assignments of a call, made $call_made and with $call_closed
     * of those of its own call closed (see $open_assignments): as a path type id, minus its depth in those of its
     * own call, or the unkeyed array value assignment if the call doesn't know it.
     *
     * @param list<int> $call_made
     * @psalm-external-mutation-free
     */
    private function resolvePutBackAssignment(array $call_made, int $call_closed, int $depth): int
    {
        $count = count($call_made);
        $index = $count - $depth;

        if ($index >= 0 && isset($call_made[$index])) {
            return $call_made[$index];
        }

        if ($call_closed !== self::NO_CALL && $call_closed !== self::FORGOTTEN
            && $call_closed + $depth - $count < self::FORGOTTEN
        ) {
            return $count - $call_closed - $depth;
        }

        return $this->getPathTypeId('arrayvalue-assignment');
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

            // a fetch of a key drops a flow anywhere in an array whichever of its overwrites is of that key (see
            // getNextOpenAssignments())
            if ($depth > 0 && $family === self::ARRAY_FAMILY && $this->isOverwrittenAnywhere($made)) {
                $depth = max($depth, count($made[$family] ?? []));
            }

            self::capOpenAssignments($made, $closed, $family, $depth);

            // nor can it observe those of the call below
            if ($depth <= count($made[$family]) && $closed[$family] !== self::NO_CALL) {
                $closed[$family] = self::FORGOTTEN;
            }
        }

        $result = $this->internOpenAssignments($made, $closed, $this->open_assignment_slots[$open_assignments]);
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
        $slot = $this->open_assignment_slots[$open_assignments];

        if ($slot >= 0 || $slot === self::INHERITED_SLOT) {
            $open_assignments = $this->scopeSlot($open_assignments, $slot, $node_id);
        }

        $copied_position = $this->copied_position;

        $widened = $predecessor !== -1 && isset($this->widened_states[$predecessor]);

        if (isset($this->state_ids[$node_id])) {
            $node_states = count($this->state_ids[$node_id]);

            if ($node_states >= self::WIDENING_STATES) {
                $widened_assignments = $this->widenOpenAssignments(
                    $open_assignments,
                    $node_states >= self::FORGETTING_STATES ? 0 : 1,
                );

                if ($widened_assignments !== $open_assignments) {
                    $open_assignments = $widened_assignments;
                    $widened = true;
                }
            }
        }

        $key = (($context + 1) << 32) | $open_assignments;

        if (!isset($this->state_ids[$node_id][$key])) {
            if ($copied_position !== 0) {
                $this->copy_positions[count($this->state_nodes)] = $copied_position;
                $this->state_copies[$predecessor][count($this->state_nodes)] = true;
            }

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

            if ($widened) {
                $this->widened_states[$state] = true;
            }

            return;
        }

        $state = $this->state_ids[$node_id][$key];

        if (!$widened) {
            unset($this->widened_states[$state]);
        }

        $new_kept = $kept & ~$this->state_kept[$state];
        $new_added = $added & ~$this->state_added[$state];

        if (isset($this->copy_positions[$state])
            && ($copied_position === 0 || ($this->copy_positions[$state] & $copied_position) === 0)
        ) {
            // reached by another flow, or copied for another position too: walked again as such
            if ($copied_position === 0) {
                unset($this->copy_positions[$state]);
            } else {
                $this->copy_positions[$state] |= $copied_position;
                $this->state_copies[$predecessor][$state] = true;
            }

            if (!isset($this->queued[$state])) {
                $this->queue[] = $state;
                $this->queued[$state] = true;
            }
        }

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

        if (isset($this->shared_state[$id])
            && isset($this->forward_edges[$id])
            && !$this->isOutsideOfCalls($this->state_contexts[$state])
        ) {
            // What a specialized call leaves in shared state (see TaintFlowGraph::addSharedState()), any other call
            // may read: the flow leaves the calls there (see leaveCalls()), rather than staying in the call until it
            // reaches an exit of it, which would then only lead back to the call site.
            $this->leaveCalls($state, $id);

            return;
        }

        if (isset($this->forward_edges[$id])) {
            if (!isset($this->root_entries[$state])
                && count($this->state_ids[$id]) >= self::CONVERGING_STATES
                && ($this->isOutsideOfCalls($this->state_contexts[$state])
                    || count($this->call_entries_reaching[$id] ?? []) > 1
                    || $this->getNode($id)?->code_location === null)
            ) {
                $this->enterConvergence($state, $id);
            } else {
                $this->walkEdges($state, $id);
            }

            return;
        }

        $node = $this->getNode($id);

        // as through its edges (see walkEdges()), a flow doesn't enter a body or leave one from a node it isn't
        // walked from
        if ($node === null || !$this->isWalkedFrom($node)) {
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
            // like any node many flows reach (see enterConvergence()): else each would leave through all its call
            // sites, a function-like's return reached from many convergences through a property its class sets
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

        if ($has_specialized_calls && !$this->isExitOfEntered($id, $this->entry_nodes[$context])) {
            // An exit of another function-like, reached through something the calls share (a property set in one
            // method and read in another, ...): no call entering this walk has its specialization of the exit, so
            // any call to that function-like may return what the flow holds (see leaveCalls()).
            $this->leaveCalls($state, $id);
        } elseif ($has_specialized_calls) {
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
            $next_added = ($added | $path->added_taints) & ~$removed_taints;

            // What a builtin decodes from a string (json_decode(), unserialize(), parse_str(), ...) can be an array
            // or an object again, whatever the string's type took away: decoded user input can be a NoSQL query.
            // In the walk of an entry, the taints the calls keep aren't known: any of them may be user input.
            if ($path->type === DataFlowGraph::UNSERIALIZATION_PATH_TYPE
                && (($kept | $next_added) & ~$removed_taints & $this->codebase->getInputTaints()) !== 0
            ) {
                $next_added |= TaintKind::ARRAY_ONLY;
            }

            $this->takeEdge(
                $context,
                $open_assignments,
                $kept & ~$removed_taints,
                $next_added,
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

        if ($next_open_assignments === self::OBSERVES_CALL
            && isset($this->array_conversion_path_types[$path_type])
        ) {
            // it goes on for the calls whose innermost open assignment isn't one of an array
            $context = $this->getConversionFilter($context, $this->open_assignments[$open_assignments][1]);
            $next_open_assignments = $open_assignments;
        } elseif ($next_open_assignments === self::OBSERVES_CALL
            && isset($this->overwrite_path_types[$path_type])
        ) {
            // For the calls the flow is anywhere in an array of, it goes on anywhere in it but under the key
            // overwritten (see addOverwrite()). For the others, the overwrite stops it where the innermost open
            // array assignment of the call is one under that key, as a fetch would (see classPassesFetch()), and
            // it goes on as through the plain edge it replaces elsewhere. (An overwrite leads from an array to an
            // array, never into a sink.)
            $closed = $this->open_assignments[$open_assignments][1];

            $this->reach(
                $to_id,
                $this->getAnywhereFilter($context, $closed, true),
                $this->addOverwrite($open_assignments, $path_type),
                $kept,
                $added,
                $predecessor,
                $link,
                $path_type,
            );

            $context = $this->getAnywhereFilter($context, $closed, false);
            $overwritten_key = (string) $this->path_type_effects[$path_type][1];
            $depth = $closed[self::ARRAY_FAMILY] + 1;
            $fact = $this->entry_facts[$context][self::getPosition(self::ARRAY_FAMILY, $depth)] ?? null;

            if ($fact !== null && $fact[0] !== null) {
                if (!self::classPassesFetch($fact[0], $overwritten_key)) {
                    return;
                }
            } elseif ($fact === null || !isset($fact[1][$overwritten_key])) {
                $context = $this->getFilter($context, self::ARRAY_FAMILY, $depth, $overwritten_key);
            }

            $next_open_assignments = $open_assignments;
        } elseif ($next_open_assignments === self::OBSERVES_CALL) {
            // it goes on for the calls whose open assignment it doesn't ignore
            [$observed_family, $observed_key] = $this->path_type_effects[$path_type];
            $observed_key ??= '';
            $depth = $this->getObservedCallDepth($open_assignments, $observed_family);
            $fact = $this->entry_facts[$context][self::getPosition($observed_family, $depth)] ?? null;

            if ($fact !== null && $fact[0] !== null && !self::isDeferredClass($fact[0])) {
                if (!self::classPassesFetch($fact[0], $observed_key)) {
                    return;
                }
            } elseif ($fact !== null && $fact[0] === self::EXACT_KEY_CLASS && $observed_key === '') {
                // a fetch of the keys takes nothing assigned under a key (see classPassesFetch())
                return;
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
            isset($this->copy_positions[$caller]),
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
     * walk()). Flows in specialized calls only converge at a node the walks of several entries reach, or one
     * without a location (a property, ...): there, a value is shared by the calls, and any call reading it may
     * return it, so the call site each flow was entered through doesn't matter anymore. (The filters of an
     * entry share its node, so a property only their walks reach would otherwise be walked in each of them,
     * with all that its value reaches.)
     */
    private function enterConvergence(int $caller, string $id): void
    {
        $this->addEntryCaller(
            $this->getEntry($id, self::ENTRY_CONVERGENCE, $caller, $this->getConvergenceOpenAssignments($caller, $id)),
            $caller,
            null,
            isset($this->copy_positions[$caller]),
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
        $slot = $this->open_assignment_slots[$this->state_open_assignments[$caller]];
        $known = [];
        $known_count = [];

        foreach (self::FAMILIES as $family => $_) {
            $innermost = array_slice($made[$family] ?? [], -1);
            // one of the call put back is not one the convergence knows
            $known[$family] = $innermost !== [] && $innermost[0] >= 0 ? $innermost : [];
            $known_count[$family] = count($known[$family]);
        }

        // the walk of a convergence isn't composed with its callers: it can't inherit their slot
        $open_assignments = $this->truncateOpenAssignments(
            $this->internOpenAssignments(
                $known,
                $known_count,
                $slot >= 0 && $this->slots[$slot][1] >= 0 ? $slot : self::NO_SLOT,
            ),
            $id,
        );

        if (!isset($this->convergence_open_assignments[$id][$open_assignments])) {
            if (count($this->convergence_open_assignments[$id] ?? []) >= self::MAX_CONVERGENCE_KEYS) {
                return self::CALL_OPEN_ASSIGNMENTS;
            }

            $this->convergence_open_assignments[$id][$open_assignments] = true;
        }

        return $open_assignments;
    }

    /**
     * The flows of state $state, in the walk of an entry, reached node $id, shared state or an exit of another
     * function-like, which any call may read or return: they go on from it outside of any call, with the taints
     * of all the calls entering the entry that they keep (see getUnionTaints()), and again with those the entry
     * gets later (see addEntryTaints()). They know the open assignments they made, not those of the calls: a fetch
     * only ignores an open assignment the flow knows, so forgetting some only lets the flow go on through more
     * fetches.
     *
     * Leaving through each call entering the entry instead, as an exit of the entry the call is made in, up to one
     * outside of any call (see exitThroughCaller()), would keep the taints of each call, but costs the product of
     * the calls and of the open assignments of the flows of each walk on the way: a constructor called from
     * thousands of places, setting a property the getters of its class return, would leave through all of them
     * with each getter's return.
     *
     * @psalm-external-mutation-free
     */
    private function leaveCalls(int $state, string $id): void
    {
        [$made] = $this->open_assignments[$this->state_open_assignments[$state]];

        foreach ($made as $family => $assignments) {
            // one of the call put back (see applyPathType()) is one of those of the calls, which it forgets
            $made[$family] = array_values(
                array_filter($assignments, static fn(int $assignment): bool => $assignment >= 0),
            );
        }

        // the element slot too: the slot of a loop of the entry doesn't hold past shared state or another
        // function-like's exit (see scopeSlot())
        $open_assignments = $this->internOpenAssignments($made, [self::NO_CALL, self::NO_CALL]);
        $this->entry_leaving_states[$this->state_contexts[$state]][$state] = [$id, $open_assignments];

        $this->reach($id, -1, $open_assignments, 0, $this->getUnionTaints($state), $state, 0, -1);
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
     * Whether the walks of entry $entry are split by the class of the open assignments of the calls entering
     * it (see dependOnClass()). Those of an entry are, and those of a filter knowing a single fact (e.g. the
     * key of the property its walk read) too: each of its walks only observes that one so far, so splitting
     * them by the class of another one (e.g. the key of the array that property holds, fetched further on)
     * multiplies them by the classes of that one at most. A filter knowing more isn't split by class: that
     * would make a walk for every combination of classes of all the open assignments the walks observe. A
     * fetch in the walk of a call it makes is told apart in it by whether it ignores the open assignment
     * only (see passesFetch()). Nor is a filter of an entry with MAX_FILTERS filters already (filters by class
     * included): past those, its fetches ask per key too, and take everything (see getFilter()).
     *
     * @psalm-mutation-free
     */
    private function tracksClasses(int $entry): bool
    {
        return $this->entry_bases[$entry] === $entry
            || (count($this->entry_facts[$entry]) <= self::MAX_TRACKING_FILTER_FACTS
                && ($this->entry_filter_counts[$this->entry_bases[$entry]] ?? 0) < self::MAX_FILTERS);
    }

    /**
     * @param self::ENTRY_* $kind
     * @param array<int, array{?string, array<string, true>}> $facts
     * @psalm-external-mutation-free
     */
    private function addEntry(string $id, int $kind, array $facts, ?int $base = null, string $conversions = ''): int
    {
        if ($base !== null) {
            // A filter is the walks of its base for the calls agreeing with its facts, and passing its conversions,
            // whatever the order it got them in: it is shared by the filters it is a filter of (its walk is the
            // union of the walks they continue in it, each for calls it holds).
            ksort($facts);
            $filter_key = $conversions . "\2" . $base;

            foreach ($facts as $position => [$class, $passed_keys]) {
                ksort($passed_keys, SORT_STRING);
                $facts[$position] = [$class, $passed_keys];
                $filter_key .= "\0" . $position . ($class === null ? '' : '=' . $class);

                foreach ($passed_keys as $passed_key => $_) {
                    $filter_key .= "\1" . $passed_key;
                }
            }

            if (isset($this->filter_ids[$filter_key])) {
                return $this->filter_ids[$filter_key];
            }

            $this->filter_ids[$filter_key] = count($this->entry_nodes);
        }

        $entry = count($this->entry_nodes);
        $this->entry_bases[] = $base ?? $entry;
        $this->entry_conversions[] = $conversions;

        if ($base !== null) {
            $this->entry_filter_counts[$base] = ($this->entry_filter_counts[$base] ?? 0) + 1;
        }

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
     * getFilter() and dependOnClass()), and applies to it what their walks reached. A copy of a call (see
     * copyToFilter()) enters $entry $as_copy: the walk of the entry is the same for it as for the call it copies,
     * only the filters for the positions it was copied for differ.
     */
    private function addEntryCaller(
        int $entry,
        int $caller,
        ?string $specialization_key,
        bool $as_copy = false,
    ): void {
        // A filter is one of each filter it is a filter of (see addEntry()): a call enters it through each of them.
        // What it gets only depends on its taints, and for a copy on the positions it was copied for: what the walks
        // of an entry reach later is applied to the calls entering it then.
        $version = count($this->later_reaches[$caller] ?? []);
        $version_key = $caller;

        if ($as_copy) {
            $version = ($version << 16) | ($this->copy_positions[$caller] ?? 0);
            $version_key = -1 - $caller;
        }

        if (($this->entry_caller_versions[$entry][$version_key] ?? null) === $version) {
            return;
        }

        $this->entry_caller_versions[$entry][$version_key] = $version;

        if ($as_copy) {
            foreach ($this->entry_filters[$entry] as [$filter, $family, $depth, $fetched_key]) {
                $this->addFilterCopy($filter, $family, $depth, $fetched_key, $caller, $specialization_key);
            }

            foreach ($this->entry_conversion_filters[$entry] ?? [] as $filter) {
                if ($this->passesConversion($caller, $this->filter_conversions[$filter])) {
                    $this->addEntryCaller($filter, $caller, $specialization_key, true);
                }
            }

            foreach ($this->entry_anywhere_filters[$entry] ?? [] as $filter) {
                [$closed, $anywhere] = $this->filter_anywhere[$filter];

                if ($this->passesAnywhere($caller, $closed) === $anywhere) {
                    $this->addEntryCaller($filter, $caller, $specialization_key, true);
                }
            }

            foreach ($this->entry_class_filters[$entry] as $position => $_) {
                $this->addClassFilterCaller($entry, $position, $caller, $specialization_key, true);
            }

            return;
        }

        $this->entry_callers[$entry][$caller] = $specialization_key;

        $caller_context = $this->state_contexts[$caller];

        if ($caller_context !== -1) {
            $this->entry_taint_dependents[$caller_context][($caller << 32) | $entry] = true;
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

        foreach ($this->entry_filters[$entry] as [$filter, $family, $depth, $fetched_key]) {
            if ($this->passesFetch($caller, $family, $depth, $fetched_key, self::CONVERGENCE_LEVELS) === true) {
                $this->addEntryCaller($filter, $caller, $specialization_key);
            }
        }

        foreach ($this->entry_conversion_filters[$entry] ?? [] as $filter) {
            if ($this->passesConversion($caller, $this->filter_conversions[$filter])) {
                $this->addEntryCaller($filter, $caller, $specialization_key);
            }
        }

        foreach ($this->entry_anywhere_filters[$entry] ?? [] as $filter) {
            [$closed, $anywhere] = $this->filter_anywhere[$filter];

            if ($this->passesAnywhere($caller, $closed) === $anywhere) {
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
            return $this->entry_filters[$entry][$filter_key][0];
        }

        if (($this->entry_filter_counts[$this->entry_bases[$entry]] ?? 0) >= self::MAX_FILTERS) {
            return $entry;
        }

        $facts = $this->entry_facts[$entry];
        $position = self::getPosition($family, $depth);
        $fact = $facts[$position] ?? [null, []];
        $passed_keys = $fact[1];
        $passed_keys[$fetched_key] = true;

        // Only the class of none passes fetches of two different keys (see classPassesFetch()), and it passes any:
        // the filter knows it. Else the filters would be told apart by every set of keys fetched there: a call
        // made in a filter is copied into a filter of its context for each key fetched in its walk (see
        // passesFetch()), and its copy, which still observes the same open assignment, into a filter of that one
        // for each other key (e.g. a function-like whose walk reaches a call of itself passing it its parameter).
        if ($fact[0] === self::EXACT_KEY_CLASS) {
            // the calls of the class of that key, the only one passing it, or of keys with that start (a fetch of
            // the keys passes none)
            $facts[$position] = [':' . $fetched_key, []];
        } else {
            $facts[$position] = count($passed_keys) > 1 ? ['', []] : [$fact[0], $passed_keys];
        }

        $filter = $this->addEntry(
            $this->entry_nodes[$entry],
            $this->entry_kinds[$entry],
            $facts,
            $this->entry_bases[$entry],
            $this->entry_conversions[$entry],
        );
        $this->entry_filters[$entry][$filter_key] = [$filter, $family, $depth, $fetched_key];

        foreach ($this->entry_callers[$entry] as $caller => $specialization_key) {
            if ($this->passesFetch($caller, $family, $depth, $fetched_key, self::CONVERGENCE_LEVELS) === true) {
                $this->addEntryCaller($filter, $caller, $specialization_key);
            }
        }

        foreach ($this->getEntryCopies($entry) as $copy => $specialization_key) {
            $this->addFilterCopy($filter, $family, $depth, $fetched_key, $copy, $specialization_key);
        }

        return $filter;
    }

    /**
     * The copies (see copyToFilter()) of the calls entering $entry, which enter it as copies (see addEntryCaller()):
     * state => the specialization key of the call. A copy goes through the filters a call it copies enters, but
     * those for the positions it was copied for, whose fetches it tells apart itself.
     *
     * @return array<int, ?string>
     * @psalm-mutation-free
     */
    private function getEntryCopies(int $entry): array
    {
        $copies = [];
        $pending = [];

        foreach ($this->entry_callers[$entry] as $caller => $specialization_key) {
            if (isset($this->state_copies[$caller])) {
                $pending[] = [$caller, $specialization_key];
            }
        }

        while ($pending) {
            [$state, $specialization_key] = array_pop($pending);

            foreach ($this->state_copies[$state] as $copy => $_) {
                if (isset($this->copy_positions[$copy]) && !array_key_exists($copy, $copies)) {
                    $copies[$copy] = $specialization_key;

                    if (isset($this->state_copies[$copy])) {
                        $pending[] = [$copy, $specialization_key];
                    }
                }
            }
        }

        return $copies;
    }

    /**
     * Makes the copy of a call $copy (see copyToFilter()) one entering the filter $filter for the calls whose open
     * assignment of type $family at depth $depth a fetch of key $fetched_key doesn't ignore (see getFilter()), if
     * it doesn't ignore it: as a call if that's one of the calls entering its context at a position it was copied
     * for, which decides; else, as a copy if that's known without the calls entering its context, the call it
     * copies telling them apart by it otherwise.
     */
    private function addFilterCopy(
        int $filter,
        int $family,
        int $depth,
        string $fetched_key,
        int $copy,
        ?string $specialization_key,
    ): void {
        $is_copied_for = $this->isCopiedFor($copy, $family, $depth, $fetched_key);

        if ($this->passesFetch($copy, $family, $depth, $fetched_key, self::CONVERGENCE_LEVELS, $is_copied_for)
            === true
        ) {
            $this->addEntryCaller($filter, $copy, $specialization_key, !$is_copied_for);
        }
    }

    /**
     * Whether the copy of a call $copy (see copyToFilter()) tells apart the calls entering its context by whether a
     * fetch of key $fetched_key, or by the class (if null), of its open assignment of type $family at depth $depth:
     * that's one of those of the calls at a position it was copied for, and its context knows the class there, or
     * that the calls don't ignore that key, or nothing yet but that they know the class only per key (see
     * DEFERRED_CLASS). Else the call it copies tells them apart: telling them apart further for another key, after
     * the one the copy was made for, would make a filter for every set of keys fetched there.
     *
     * @psalm-mutation-free
     */
    private function isCopiedFor(int $copy, int $family, int $depth, ?string $fetched_key): bool
    {
        [$made, $closed] = $this->open_assignments[$this->state_open_assignments[$copy]];
        $count = count($made[$family]);
        $index = $count - $depth;

        if ($index >= 0 && $made[$family][$index] >= 0) {
            return false;
        }

        // one of the call, or one of the call put back (see passesFetch())
        $call_depth = $index >= 0 ? -$made[$family][$index] : $closed[$family] + $depth - $count;

        if (($index < 0 && ($closed[$family] === self::NO_CALL || $closed[$family] === self::FORGOTTEN))
            || $call_depth > self::MAX_CALL_OPEN_ASSIGNMENT_DEPTH
        ) {
            return false;
        }

        $position = self::getPosition($family, $call_depth);

        if ((($this->copy_positions[$copy] ?? 0) & self::getPositionBit($position)) === 0) {
            return false;
        }

        [$class, $passed_keys] = $this->entry_facts[$this->state_contexts[$copy]][$position] ?? [null, []];

        if ($fetched_key === null || ($class !== null && !self::isDeferredClass($class))) {
            return $class !== null;
        }

        return isset($passed_keys[$fetched_key]) || (self::isDeferredClass($class) && $passed_keys === []);
    }

    /**
     * The filter of $entry for the calls a conversion of an array (see getNextOpenAssignments()) doesn't
     * drop, in a walk of $entry whose flow made no open assignment and closed $closed of those of the call
     * of each expression type, made if needed (see getFilter()).
     *
     * @param array{int, int} $closed
     */
    private function getConversionFilter(int $entry, array $closed): int
    {
        $filter_key = 'conversion ' . implode(' ', $closed);

        if (isset($this->entry_conversion_filters[$entry][$filter_key])) {
            return $this->entry_conversion_filters[$entry][$filter_key];
        }

        if (($this->entry_filter_counts[$this->entry_bases[$entry]] ?? 0) >= self::MAX_FILTERS) {
            return $entry;
        }

        $filter = $this->addEntry(
            $this->entry_nodes[$entry],
            $this->entry_kinds[$entry],
            $this->entry_facts[$entry],
            $this->entry_bases[$entry],
            $this->entry_conversions[$entry] . $filter_key . "\3",
        );
        $this->entry_conversion_filters[$entry][$filter_key] = $filter;
        $this->filter_conversions[$filter] = $closed;

        foreach ($this->entry_callers[$entry] as $caller => $specialization_key) {
            if ($this->passesConversion($caller, $closed)) {
                $this->addEntryCaller($filter, $caller, $specialization_key);
            }
        }

        return $filter;
    }

    /**
     * Whether a conversion of an array doesn't drop the flows of state $state entering a walk whose flow made
     * no open assignment and closed $closed of theirs of each expression type: unless the innermost open
     * assignment left is one of an array they made past any of a property they made. Those of the call
     * entering their own context are all outer ones, and how the two expression types interleave there isn't
     * known, so a conversion of an array of theirs doesn't drop them either. A value encoded in a string isn't
     * in an array, and a flow anywhere in an array is all of one (see getNextOpenAssignments()).
     *
     * @param array{int, int} $closed
     * @psalm-mutation-free
     */
    private function passesConversion(int $state, array $closed): bool
    {
        [$made] = $this->open_assignments[$this->state_open_assignments[$state]];
        $array_assignments = $made[self::ARRAY_FAMILY] ?? [];
        $array_left = count($array_assignments) - $closed[self::ARRAY_FAMILY];

        if ($array_left <= 0 || count($made[self::PROPERTY_FAMILY] ?? []) > $closed[self::PROPERTY_FAMILY]) {
            return true;
        }

        $innermost = $array_assignments[$array_left - 1];

        return isset($this->overwrite_path_types[$innermost])
            || $this->path_types[$innermost] === DataFlowGraph::SERIALIZATION_PATH_TYPE;
    }

    /**
     * The filter of $entry for the calls a flow of its walk that made no open assignment and closed $closed of
     * theirs of each expression type is anywhere in an array of (see isAnywhereInArray()), or for the others if
     * $anywhere is false, made if needed (see getFilter()).
     *
     * @param array{int, int} $closed
     */
    private function getAnywhereFilter(int $entry, array $closed, bool $anywhere): int
    {
        $filter_key = ($anywhere ? 'anywhere ' : 'not anywhere ') . implode(' ', $closed);

        if (isset($this->entry_anywhere_filters[$entry][$filter_key])) {
            return $this->entry_anywhere_filters[$entry][$filter_key];
        }

        if (($this->entry_filter_counts[$this->entry_bases[$entry]] ?? 0) >= self::MAX_FILTERS) {
            return $entry;
        }

        $filter = $this->addEntry(
            $this->entry_nodes[$entry],
            $this->entry_kinds[$entry],
            $this->entry_facts[$entry],
            $this->entry_bases[$entry],
            $this->entry_conversions[$entry] . $filter_key . "\3",
        );
        $this->entry_anywhere_filters[$entry][$filter_key] = $filter;
        $this->filter_anywhere[$filter] = [$closed, $anywhere];

        foreach ($this->entry_callers[$entry] as $caller => $specialization_key) {
            if ($this->passesAnywhere($caller, $closed) === $anywhere) {
                $this->addEntryCaller($filter, $caller, $specialization_key);
            }
        }

        return $filter;
    }

    /**
     * Whether the flows of state $state entering a walk whose flow made no open assignment and closed $closed of
     * theirs of each expression type are anywhere in an array there (see isAnywhereInArray()): what is left of
     * their open assignments is none or overwrites. Only those outside of any context know it.
     *
     * @param array{int, int} $closed
     * @psalm-mutation-free
     */
    private function passesAnywhere(int $state, array $closed): bool
    {
        [$made, $state_closed] = $this->open_assignments[$this->state_open_assignments[$state]];

        if ($state_closed[self::ARRAY_FAMILY] !== self::NO_CALL
            || count($made[self::PROPERTY_FAMILY] ?? []) > $closed[self::PROPERTY_FAMILY]
        ) {
            return false;
        }

        $array_assignments = $made[self::ARRAY_FAMILY] ?? [];
        $array_left = count($array_assignments) - $closed[self::ARRAY_FAMILY];

        return $array_left <= 0 || isset($this->overwrite_path_types[$array_assignments[$array_left - 1]]);
    }

    /**
     * Whether a fetch of key $fetched_key doesn't ignore the open assignment of type $family at depth $depth
     * of the flows of state $state, or null if they don't know it: it is one of those of the flows entering
     * their context, which decides (see dependOnClass(), or getFilter() in a filter not split by class),
     * through $levels more convergences at most (see getAssignmentClass()).
     */
    private function passesFetch(
        int $state,
        int $family,
        int $depth,
        string $fetched_key,
        int $levels,
        bool $defer = true,
    ): ?bool {
        [$made, $closed] = $this->open_assignments[$this->state_open_assignments[$state]];
        $count = count($made[$family]);

        $index = $count - $depth;

        if ($index >= 0 && $made[$family][$index] >= 0) {
            return self::classPassesFetch($this->getClass($made[$family][$index], $family), $fetched_key);
        }

        // one of the call, or one of the call put back
        $call_depth = $index >= 0 ? -$made[$family][$index] : $closed[$family] + $depth - $count;

        if (($index < 0 && ($closed[$family] === self::NO_CALL || $closed[$family] === self::FORGOTTEN))
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

        if ($fact !== null && $fact[0] !== null && !self::isDeferredClass($fact[0])) {
            return self::classPassesFetch($fact[0], $fetched_key);
        }

        if ($fact !== null && isset($fact[1][$fetched_key])) {
            return true;
        }

        $is_exact_key_class = $fact !== null && $fact[0] === self::EXACT_KEY_CLASS;

        if ($is_exact_key_class && $fetched_key === '') {
            return false;
        }

        if (!$defer) {
            return null;
        }

        if (!$this->tracksClasses($context) || ($fact !== null && self::isDeferredClass($fact[0]))) {
            // (the calls of a convergence with the class of a key know it: asking them per key goes no further)
            if (!$is_convergence || $is_exact_key_class) {
                // A fetch in the walk of a call made there only asks whether it ignores that open assignment: the
                // flows go on in the filter of their context for the calls whose open assignment it doesn't ignore
                // (see getFilter()), one per fetched key and not per class. Else a call made past two observed
                // open assignments would take everything assigned under any key (e.g. the values of an array as
                // its keys).
                $filter = $this->getFilter($context, $family, $call_depth, $fetched_key);

                if ($filter === $context) {
                    // past MAX_FILTERS, the fetch doesn't ignore it
                    return true;
                }

                $this->copyToFilter($state, $filter, self::getPosition($family, $call_depth));

                return null;
            }

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
    private function getAssignmentClass(int $state, int $family, int $depth, int $levels, bool $defer = true): ?string
    {
        [$made, $closed] = $this->open_assignments[$this->state_open_assignments[$state]];
        $count = count($made[$family]);

        $index = $count - $depth;

        if ($index >= 0 && $made[$family][$index] >= 0) {
            return $this->getClass($made[$family][$index], $family);
        }

        // one of the call, or one of the call put back
        $call_depth = $index >= 0 ? -$made[$family][$index] : $closed[$family] + $depth - $count;

        if (($index < 0 && ($closed[$family] === self::NO_CALL || $closed[$family] === self::FORGOTTEN))
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

        if (!$this->tracksClasses($context)) {
            // Already in a filter, for the calls agreeing on other open assignments: a filter of it for each
            // class there too would make one for every combination of classes of the open assignments a walk
            // observes. So the calls entering it only know it per fetched key: the flows of a walk of the call
            // depending on it go on in a filter of that walk for the calls with a deferred class there, whose
            // fetches observing it ask them per key too (see passesFetch()). In a convergence, no fetch ignores
            // it, as above.
            return $is_convergence ? '' : self::DEFERRED_CLASS;
        }

        if ($defer) {
            $this->dependOnClass($context, $position, $state, $is_convergence ? $levels - 1 : $levels);
        }

        return null;
    }

    /**
     * The class of an open assignment of type $family: what decides whether a fetch ignores it (see
     * shouldIgnoreFetch()). That's its key, prefixed with ':' (see DataFlowGraph::keysMayBeEqual()), KEY_CLASS
     * for an array key, UNKEYED_VALUE_CLASS for an array value under a key not known, SERIALIZATION_CLASS for a
     * value encoded in a string, or '' if no fetch ignores it.
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

        if ($assignment_type === DataFlowGraph::SERIALIZATION_PATH_TYPE) {
            return self::SERIALIZATION_CLASS;
        }

        if ($family === self::ARRAY_FAMILY && $assignment_type === 'arrayvalue-assignment') {
            return self::UNKEYED_VALUE_CLASS;
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
     * The bit of position $position (of an open assignment of the calls entering a context, see getPosition()) in a
     * bit set of positions: the depth of one of those is MAX_CALL_OPEN_ASSIGNMENT_DEPTH at most
     *
     * @psalm-pure
     */
    private static function getPositionBit(int $position): int
    {
        return 1 << (($position >> self::DEPTH_BITS) * (self::MAX_CALL_OPEN_ASSIGNMENT_DEPTH + 1)
            + ($position & self::DEPTH_MASK));
    }

    /**
     * Whether the calls with class $class (see getAssignmentClass()) are told apart per fetched key: by the calls
     * entering their context (DEFERRED_CLASS), or by their key (EXACT_KEY_CLASS)
     *
     * @psalm-pure
     */
    private static function isDeferredClass(?string $class): bool
    {
        return $class === self::DEFERRED_CLASS || $class === self::EXACT_KEY_CLASS;
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

        if ($class === self::UNKEYED_VALUE_CLASS) {
            // a value assigned under any key: the keys of the array don't hold it
            return $fetched_key !== '';
        }

        if ($class === '' || $class === self::SERIALIZATION_CLASS) {
            return true;
        }

        foreach (self::getFetchedKeys($fetched_key) as $key) {
            if (DataFlowGraph::keysMayBeEqual(substr($class, 1), $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The keys a fetch of key $fetched_key may fetch: the quoted keys a fetch of a key that is one of a few literals
     * separates by '|' (see ArrayFetchAnalyzer::getFetchedKeys()), or that key
     *
     * @return non-empty-list<string>
     * @psalm-pure
     */
    private static function getFetchedKeys(string $fetched_key): array
    {
        if (!str_starts_with($fetched_key, "'") || !str_contains($fetched_key, "'|'")) {
            return [$fetched_key];
        }

        $keys = [];
        foreach (explode("'|'", substr($fetched_key, 1, -1)) as $key) {
            $keys[] = "'" . $key . "'";
        }

        return $keys;
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
                $this->copyToFilter($state, $filter, $position);
            }

            return;
        }

        $this->entry_class_filters[$entry][$position] = [];
        $this->entry_class_levels[$entry][$position] = $levels;

        foreach ($this->entry_callers[$entry] as $caller => $specialization_key) {
            $this->addClassFilterCaller($entry, $position, $caller, $specialization_key);
        }

        foreach ($this->getEntryCopies($entry) as $copy => $specialization_key) {
            $this->addClassFilterCaller($entry, $position, $copy, $specialization_key, true);
        }
    }

    /**
     * Makes the call of state $caller one entering the filter of $entry for the calls with its class of open
     * assignment at position $position, unless it doesn't know it yet (see getAssignmentClass()).
     *
     * A filter for the calls of a given class knows it, and its walk goes on from the states of the walk of
     * $entry that depend on it (see dependOnClass()), like the walk of $entry for those calls.
     */
    private function addClassFilterCaller(
        int $entry,
        int $position,
        int $caller,
        ?string $specialization_key,
        bool $as_copy = false,
    ): void {
        if ($as_copy) {
            // the walk of the filter of its class only differs for it at a position it was copied for (see
            // addEntryCaller())
            $as_copy = !$this->isCopiedFor($caller, $position >> self::DEPTH_BITS, $position & self::DEPTH_MASK, null);
        }

        $class = $this->getAssignmentClass(
            $caller,
            $position >> self::DEPTH_BITS,
            $position & self::DEPTH_MASK,
            $this->entry_class_levels[$entry][$position],
            !$as_copy,
        );

        if ($class === null) {
            return;
        }

        if (str_starts_with($class, ':') && !str_ends_with($class, "'*")) {
            // (the class of a key whose start only is known passes fetches of other keys too: it gets its own)
            $class = self::EXACT_KEY_CLASS;
        } elseif ($class === self::DEFERRED_CLASS && $this->entry_kinds[$entry] !== self::ENTRY_CALL) {
            // no fetch in the walk of a convergence asks per key (see passesFetch())
            $class = '';
        }

        if (!isset($this->entry_class_filters[$entry][$position][$class])
            && ($this->entry_filter_counts[$this->entry_bases[$entry]] ?? 0) >= self::MAX_FILTERS
        ) {
            // past MAX_FILTERS, the calls of a class with no filter yet go on in the filter of the class of none,
            // which every fetch takes (see tracksClasses()): one walk for each class of the arrays a function is
            // called with made most of the states on large code bases
            $class = '';
        }

        if (!isset($this->entry_class_filters[$entry][$position][$class])) {
            $facts = $this->entry_facts[$entry];
            $facts[$position] = [$class, []];

            $filter = $this->addEntry(
                $this->entry_nodes[$entry],
                $this->entry_kinds[$entry],
                $facts,
                $this->entry_bases[$entry],
                $this->entry_conversions[$entry],
            );
            $this->entry_class_filters[$entry][$position][$class] = $filter;

            foreach ($this->entry_class_dependents[$entry][$position] ?? [] as $state => $_) {
                $this->copyToFilter($state, $filter, $position);
            }
        }

        $this->addEntryCaller(
            $this->entry_class_filters[$entry][$position][$class],
            $caller,
            $specialization_key,
            $as_copy,
        );
    }

    /**
     * Copies state $state into filter $filter of its context. For a call whose walk depends on the open assignment
     * at position $position of the calls entering that context, the copy only enters the filters of the walk of
     * the call that depend on it (see addEntryCaller()): the rest of the walk is the same for the calls of the
     * filter as for the others, and the state itself goes on through it. It is widened like any flow past
     * WIDENING_STATES at its node (see reach()): it then loses what the state knows of those calls, which bounds
     * the states the copies of many filters make at a node.
     *
     * @psalm-external-mutation-free
     */
    private function copyToFilter(int $state, int $filter, int $position): void
    {
        $this->copied_position = self::getPositionBit($position);
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
        $this->copied_position = 0;
    }

    /**
     * The body walk of $entry reached $exit_id, an exit of the function-like entered: an unspecialized node whose
     * specializations lead back to call sites (see walk()), from state $state (seen through the calls of $link, see
     * buildTrace()). Continues it at the call site of each call entering $entry (see exitThroughCaller()).
     *
     * @psalm-external-mutation-free
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
     * Continues an exit of the function-like entered, reached by the body walk of $entry, in the context of one call
     * entering it: at that call's specialization of the exit node if it has one. Else the call site doesn't use it,
     * and the flow ends: its specialization of the exit, if any, was left out with the others that have no
     * outgoing edge (see TaintFlowGraph::connectSinksAndSources()). The walk leaves the calls at the exits of other
     * function-likes and at shared state instead (see leaveCalls()).
     *
     * @psalm-external-mutation-free
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
        if ($specialization_key === null || !isset($this->specializations[$exit_id][$specialization_key])) {
            return;
        }

        $this->reach(
            $this->specializations[$exit_id][$specialization_key],
            $this->state_contexts[$caller],
            $this->composeOpenAssignments($this->state_open_assignments[$caller], $open_assignments),
            $this->state_kept[$caller] & $kept,
            ($this->state_added[$caller] & $kept) | $added,
            $state,
            $link,
            -1,
            $caller,
        );
    }

    /**
     * Whether exit $exit_id is one of the function-like of the unspecialized node $entered_id a specialized call
     * entered: that one is specialized for some of the calls the exit is (whether or not a given call uses the
     * exit, that is whether it has a specialization of it: see TaintFlowGraph::connectSinksAndSources()).
     *
     * @psalm-external-mutation-free
     */
    private function isExitOfEntered(string $exit_id, string $entered_id): bool
    {
        if (!isset($this->exits_of_entered[$entered_id][$exit_id])) {
            $is_exit = false;

            foreach ($this->specializations[$exit_id] ?? [] as $specialization_key => $_) {
                if (isset($this->nodes[$entered_id . TaintFlowGraph::SPECIALIZATION_SEPARATOR . $specialization_key])) {
                    $is_exit = true;

                    break;
                }
            }

            // or for a call that doesn't use it: its specialization of the exit has no outgoing edge
            $prefix = $entered_id . TaintFlowGraph::SPECIALIZATION_SEPARATOR;

            foreach ($is_exit ? [] : $this->specialization_links[$entered_id] ?? [] as $specialized_id => $_) {
                if (str_starts_with($specialized_id, $prefix)
                    && isset($this->nodes[
                        $exit_id . TaintFlowGraph::SPECIALIZATION_SEPARATOR . substr($specialized_id, strlen($prefix))
                    ])
                ) {
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

        if ($new_taints !== 0) {
            $this->entry_taint_callers[$convergence][$caller] = ($this->entry_taint_callers[$convergence][$caller] ?? 0)
                | $new_taints;
        }

        // the flows of its walk that left the calls entering it get the new taints too (see leaveCalls())
        foreach ($this->entry_leaving_states[$convergence] ?? [] as $state => [$id, $open_assignments]) {
            $this->reach($id, -1, $open_assignments, 0, $this->getUnionTaints($state), $state, 0, -1);
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
        foreach ($this->entry_taint_dependents[$convergence] ?? [] as $pair => $_) {
            $this->addEntryTaints($pair & 0xFFFFFFFF, $pair >> 32);
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
     *
     * A flow from a state of $widened_states passes the fetches of the keys it doesn't know: each of the many
     * sources of a node widened that way would be reported as a flow of its own, though a sink they all reach is
     * one finding. So such a flow is only reported if none of that taint kind was reported into the sink before.
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

            if (isset($this->widened_states[$state])
                && (($this->reported_sink_taints[$sink_id] ?? 0) & $taint) !== 0
            ) {
                continue;
            }

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

            if ($predecessor !== null
                && $this->graph->reportTaintedFlow($predecessor, $sink, $taint, $this->config, $this->codebase)
            ) {
                $this->reported_sink_taints[$sink_id] = ($this->reported_sink_taints[$sink_id] ?? 0) | $taint;
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
        foreach ($this->entry_taint_callers[$entry] ?? [] as $caller => $taints) {
            if (($taints & $taint) !== 0) {
                return $caller;
            }
        }

        return array_key_first($this->entry_callers[$entry]);
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
