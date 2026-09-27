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
use function array_unshift;
use function count;
use function end;
use function json_encode;
use function ksort;
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

    public function connectSinksAndSources(Progress $progress): void
    {
        $progress->startPhase(Phase::TAINT_GRAPH_RESOLUTION);

        $visited_source_ids = [];

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
        // rounds): the (id, taints) visited guard below makes the state space
        // finite, so the loop is guaranteed to terminate on its own. Combined
        // with the sink-reachability pruning above, this converges quickly enough
        // that no artificial nesting limit is needed.
        //
        // The number of rounds is not known ahead of time, so the progress bar
        // renders this phase as indeterminate (a tick per round, no percentage).
        while (count($sinks) && count($sources)) {
            $new_sources = [];

            ksort($sources);

            foreach ($sources as $source) {
                $source_taints = $source->taints;

                $visited_source_ids[$source->id][$source_taints] = true;

                // If we have one or more edges starting at this node,
                // process destinations of those edges.
                if (isset($this->forward_edges[$source->id])) {
                    $this->getChildNodes(
                        $new_sources,
                        $source,
                        $source_taints,
                        $sinks,
                        $visited_source_ids,
                        $sink_reachable,
                        $config,
                        $project_analyzer,
                        $codebase,
                    );
                    continue;
                }
        
                // If this is a specialized node, de-specialize;
                // Then, if we have one or more edges starting at the de-specialized node,
                // process destinations of those edges.
                if ($source->specialization_key !== null
                    && isset($this->specialized_calls[$source->specialization_key])
                ) {
                    /** @var string $source->unspecialized_id */
                    if (!isset($this->forward_edges[$source->unspecialized_id])) {
                        continue;
                    }
                    $specialized_calls = $source->specialized_calls;
                    $specialized_calls[$source->specialization_key][$source->unspecialized_id] = $source->id;
                    $generated_source = $source->withSpecialization(
                        $source->unspecialized_id,
                        null,
                        null,
                        $specialized_calls,
                    );

                    $this->getChildNodes(
                        $new_sources,
                        $generated_source,
                        $source_taints,
                        $sinks,
                        $visited_source_ids,
                        $sink_reachable,
                        $config,
                        $project_analyzer,
                        $codebase,
                    );

                    // If this node has first level specializations (=> is first-level & unspecialized),
                    // process them all
                } elseif (isset($this->specializations[$source->id])) {
                    $specialized_calls = $source->specialized_calls;
                    // Assert that we're unspecialized.
                    Assert::null($source->specialization_key);

                    if ($specialized_calls) {
                        // If processing descendants of a specialized call, accept only descendants.
                        foreach ($this->specializations[$source->id] as $specialization => $specialized_id) {
                            if (!isset($specialized_calls[$specialization])) {
                                continue;
                            }
                            $copy = $specialized_calls;
                            unset($copy[$specialization]);

                            $new_source = $source->withSpecialization(
                                $specialized_id,
                                $source->id,
                                $specialization,
                                $copy,
                            );

                            $this->getChildNodes(
                                $new_sources,
                                $new_source,
                                $source_taints,
                                $sinks,
                                $visited_source_ids,
                                $sink_reachable,
                                $config,
                                $project_analyzer,
                                $codebase,
                            );
                        }
                    } else {
                        // If not processing descendants, accept all specializations.
                        foreach ($this->specializations[$source->id] as $specialization => $specialized_id) {
                            $new_source = $source->withSpecialization(
                                $specialized_id,
                                $source->id,
                                $specialization,
                                $specialized_calls,
                            );

                            $this->getChildNodes(
                                $new_sources,
                                $new_source,
                                $source_taints,
                                $sinks,
                                $visited_source_ids,
                                $sink_reachable,
                                $config,
                                $project_analyzer,
                                $codebase,
                            );
                        }
                    }
                } else {
                    // Process all descendants
                    foreach ($source->specialized_calls as $specialization => $map) {
                        if (isset($map[$source->id])) {
                            $specialized_id = $map[$source->id];
                            if (!isset($this->forward_edges[$specialized_id])) {
                                continue;
                            }
                            $new_source = $source->withSpecialization(
                                $specialized_id,
                                $source->id,
                                $specialization,
                                $source->specialized_calls,
                            );

                            $this->getChildNodes(
                                $new_sources,
                                $new_source,
                                $source_taints,
                                $sinks,
                                $visited_source_ids,
                                $sink_reachable,
                                $config,
                                $project_analyzer,
                                $codebase,
                            );
                        }
                    }
                }
            }

            $sources = $new_sources;
            unset($new_sources);

            $progress->taskDone(0);
        }

        $progress->taskDone(0);
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
     * @param array<DataFlowNode> $sinks
     * @param array<string, DataFlowNode> $new_sources
     * @param array<string, true> $sink_reachable
     * @param-out array<string, DataFlowNode> $new_sources
     */
    private function getChildNodes(
        array &$new_sources,
        DataFlowNode $generated_source,
        int $source_taints,
        array $sinks,
        array $visited_source_ids,
        array $sink_reachable,
        Config $config,
        ProjectAnalyzer $project_analyzer,
        Codebase $codebase,
    ): void {
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

            if (isset($visited_source_ids[$to_id][$new_taints])) {
                continue;
            }

            $path_type = $path->type;

            if (self::shouldIgnoreFetch($path_type, 'arraykey', $generated_source->path_types)) {
                continue;
            }

            if (self::shouldIgnoreFetch($path_type, 'arrayvalue', $generated_source->path_types)) {
                continue;
            }

            if (self::shouldIgnoreFetch($path_type, 'property', $generated_source->path_types)) {
                continue;
            }

            if ($generated_source->code_location
            && $project_analyzer->canReportIssues($generated_source->code_location->file_path)
            && !$config->reportIssueInFile('TaintedInput', $generated_source->code_location->file_path)
            ) {
                continue;
            }

            if (isset($sinks[$to_id])) {
                $sink = $sinks[$to_id];
                $matching_taints = $sink->taints & $new_taints;

                if ($matching_taints && $generated_source->code_location) {
                    if ($sink->code_location
                    && $config->reportIssueInFile('TaintedInput', $sink->code_location->file_path)
                    ) {
                        $issue_location = $sink->code_location;
                    } else {
                        $issue_location = $generated_source->code_location;
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

            $key = $to_id . ' ' . $specialized_calls_key . ' ' . $new_taints;

            if (isset($new_sources[$key])) {
                continue;
            }

            $old = $this->nodes[$to_id];
            $path_types = $generated_source->path_types;
            $path_types []= $path_type;
            $new_destination = $old->withFlow(
                $new_taints,
                $generated_source,
                $path_types,
                $generated_source->specialized_calls,
            );

            $new_sources[$key] = $new_destination;
        }
    }
}
