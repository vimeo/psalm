<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Override;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
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
use Psalm\Issue\TaintedUserSecret;
use Psalm\Issue\TaintedXpath;
use Psalm\IssueBuffer;
use Psalm\Progress\Phase;
use Psalm\Progress\Progress;
use Psalm\Type\TaintKind;
use Webmozart\Assert\Assert;

use function array_pop;
use function array_splice;
use function array_unshift;
use function count;
use function end;
use function json_encode;
use function ksort;
use function str_starts_with;
use function strpos;
use function substr;

use const JSON_THROW_ON_ERROR;

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

    /**
     * Returns the id of the node the flow into $node started at: the root of its taintSource chain.
     *
     * @psalm-pure
     */
    private static function getFlowOrigin(DataFlowNode $node): string
    {
        while (($previous = $node->taintSource) !== null && $previous !== $node) {
            $node = $previous;
        }

        return $node->id;
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
        $sink_reachable = $this->getSinkReachableNodes($sources, $sinks);

        foreach ($sources as $id => $_) {
            if (!isset($sink_reachable[$id])) {
                unset($sources[$id]);
            }
        }

        // Resolution runs to a fixed point (rather than for a fixed number of
        // rounds): the (id, taints) visited guard in getChildNodes() makes the
        // state space finite, so the loop is guaranteed to terminate on its own.
        // Combined with the sink-reachability pruning above, this converges
        // quickly enough that no artificial nesting limit is needed.
        //
        // Node id => taints => true
        $visited_source_ids = [];

        // Sink id => predecessor id => origin id => taints already reported for that flow
        $reported_flows = [];

        // The number of rounds is not known ahead of time, so the progress bar
        // renders this phase as indeterminate (a tick per round, no percentage).
        while (count($sinks) && count($sources)) {
            $new_sources = [];

            ksort($sources);

            foreach ($sources as $source) {
                $visited_source_ids[$source->id][$source->taints] = true;

                foreach ($this->getPropagatingNodes($source) as $generated_source) {
                    $this->getChildNodes(
                        $new_sources,
                        $reported_flows,
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

            $sources = $new_sources;

            $progress->taskDone(0);
        }

        $progress->taskDone(0);
    }

    /**
     * Returns the nodes whose outgoing edges carry the taint of $source onward.
     *
     * That is $source itself if it has outgoing edges. Otherwise a specialized
     * node continues as its de-specialized form, remembering the call it was
     * entered through, and an unspecialized node continues as its applicable
     * specializations.
     *
     * @return list<DataFlowNode>
     * @psalm-mutation-free
     */
    private function getPropagatingNodes(DataFlowNode $source): array
    {
        if (isset($this->forward_edges[$source->id])) {
            return [$source];
        }

        // If this is a specialized node, de-specialize.
        if ($source->specialization_key !== null
            && isset($this->specialized_calls[$source->specialization_key])
        ) {
            /** @var string $source->unspecialized_id */
            if (!isset($this->forward_edges[$source->unspecialized_id])) {
                return [];
            }
            $specialized_calls = $source->specialized_calls;
            $specialized_calls[$source->specialization_key][$source->unspecialized_id] = $source->id;

            return [$source->withSpecialization(
                $source->unspecialized_id,
                null,
                null,
                $specialized_calls,
            )];
        }

        $nodes = [];

        // If this node has first level specializations (=> is first-level & unspecialized),
        // process them.
        if (isset($this->specializations[$source->id])) {
            $specialized_calls = $source->specialized_calls;
            // Assert that we're unspecialized.
            Assert::null($source->specialization_key);

            foreach ($this->specializations[$source->id] as $specialization => $specialized_id) {
                if (!$specialized_calls) {
                    // If not processing descendants of a specialized call, accept all specializations.
                    $nodes[] = $source->withSpecialization(
                        $specialized_id,
                        $source->id,
                        $specialization,
                        $specialized_calls,
                    );
                } elseif (isset($specialized_calls[$specialization])) {
                    // If processing descendants of a specialized call, accept only descendants.
                    $copy = $specialized_calls;
                    unset($copy[$specialization]);

                    $nodes[] = $source->withSpecialization(
                        $specialized_id,
                        $source->id,
                        $specialization,
                        $copy,
                    );
                }
            }

            return $nodes;
        }

        // Process all descendants
        foreach ($source->specialized_calls as $specialization => $map) {
            if (!isset($map[$source->id])) {
                continue;
            }
            $specialized_id = $map[$source->id];
            if (!isset($this->forward_edges[$specialized_id])) {
                continue;
            }
            $nodes[] = $source->withSpecialization(
                $specialized_id,
                $source->id,
                $specialization,
                $source->specialized_calls,
            );
        }

        return $nodes;
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
     * Follows every outgoing edge of $generated_source: reports the flow into
     * each sink it reaches and enqueues the destinations not visited yet.
     *
     * @param array<string, DataFlowNode> $new_sources
     * @param-out array<string, DataFlowNode> $new_sources
     * @param array<string, array<string, array<string, int>>> $reported_flows
     * @param-out array<string, array<string, array<string, int>>> $reported_flows
     * @param array<string, array<int, true>> $visited_source_ids
     * @param array<string, DataFlowNode> $sinks
     * @param array<string, true> $sink_reachable
     */
    private function getChildNodes(
        array &$new_sources,
        array &$reported_flows,
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
        $open_assignments = self::getOpenAssignments($generated_source->path_types);

        // $generated_source->specialized_calls is constant across all of this node's outgoing
        // edges, so encode it once here rather than re-serialising it for every edge in the
        // frontier-dedup key below (this is the hottest loop in taint resolution).
        $specialized_calls_key = json_encode($generated_source->specialized_calls, JSON_THROW_ON_ERROR);

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

            // The visited guard keeps the fixed point finite, so a visited node is never
            // propagated from again. A visited sink still gets to report, though: the flow
            // arriving through this edge may be a different one from the flow that visited it
            // first (it can arrive rounds later when its path is longer).
            $already_visited = isset($visited_source_ids[$to_id][$new_taints]);

            if ($already_visited && !isset($sinks[$to_id])) {
                continue;
            }

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

            if (isset($sinks[$to_id]) && $generated_source->code_location) {
                $sink = $sinks[$to_id];
                $matching_taints = $sink->taints & $new_taints;

                if ($matching_taints) {
                    // A flow is identified by its origin and by the edge it enters the sink
                    // through: the origin keeps distinct sources apart where they share that
                    // edge (e.g. two calls to a specialized function), the edge keeps distinct
                    // call sites of the same source apart. Each taint kind is reported once per
                    // flow, however many rounds, taint masks or specialization contexts carry it.
                    $origin = self::getFlowOrigin($generated_source);
                    $reported_taints = $reported_flows[$to_id][$generated_source->id][$origin] ?? 0;
                    $unreported_taints = $matching_taints & ~$reported_taints;

                    if ($unreported_taints) {
                        $reported_flows[$to_id][$generated_source->id][$origin]
                            = $reported_taints | $unreported_taints;

                        $this->reportTaintedFlow(
                            $generated_source,
                            $generated_source->code_location,
                            $sink,
                            $unreported_taints,
                            $config,
                            $codebase,
                        );
                    }
                }
            }

            if ($already_visited) {
                continue;
            }

            $key = $to_id . ' ' . $specialized_calls_key . ' ' . $new_taints;

            if (isset($new_sources[$key])) {
                continue;
            }

            $new_sources[$key] = $this->nodes[$to_id]->withFlow(
                $new_taints,
                $generated_source,
                self::appendPathType($open_assignments, $path_type),
                $generated_source->specialized_calls,
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

    private function reportTaintedFlow(
        DataFlowNode $generated_source,
        CodeLocation $source_location,
        DataFlowNode $sink,
        int $matching_taints,
        Config $config,
        Codebase $codebase,
    ): void {
        if ($sink->code_location
            && $config->reportIssueInFile('TaintedInput', $sink->code_location->file_path)
        ) {
            $issue_location = $sink->code_location;
        } else {
            $issue_location = $source_location;
        }

        $issue_trace = $this->getIssueTrace($generated_source);
        $path = $this->getPredecessorPath($generated_source)
            . ' -> ' . $this->getSuccessorPath($sink);

        $max = $codebase->taint_count;
        for ($x = 0; $x < $max; $x++) {
            $t = 1 << $x;
            if (!($matching_taints & $t)) {
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
