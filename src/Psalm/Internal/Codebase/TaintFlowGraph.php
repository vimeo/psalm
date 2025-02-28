<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Override;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Internal\DataFlow\TaintSource;
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
use Psalm\Type\TaintKind;
use Psalm\Type\TaintKindGroup;

use function count;
use function end;
use function json_encode;
use function ksort;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final class TaintFlowGraph extends DataFlowGraph
{
    /** @var array<string, TaintSource> */
    private array $sources = [];

    /** @var array<string, DataFlowNode> */
    private array $nodes = [];

    /** @var array<string, TaintSink> */
    private array $sinks = [];

    /**
     * Unspecialized ID => (Specialization key => Specialized ID)
     *
     * @var array<string, array<string, string>>
     */
    private array $specializations = [];

    #[Override]
    public function addNode(DataFlowNode $node): void
    {
        $this->nodes[$node->id] = $node;

        if ($node->unspecialized_id !== null) {
            /** @var string $node->specialization_key */
            $node->is_first_level_and_specialized = true;
            $this->specializations[$node->unspecialized_id][$node->specialization_key] = $node->id;
        }
    }

    public function addSource(TaintSource $node): void
    {
        $this->sources[$node->id] = $node;
    }

    public function addSink(TaintSink $node): void
    {
        $this->sinks[$node->id] = $node;
        // in the rare case the sink is the _next_ node, this is necessary
        $this->nodes[$node->id] = $node;
    }

    public function addGraph(self $other): void
    {
        $this->sources += $other->sources;
        $this->sinks += $other->sinks;
        $this->nodes += $other->nodes;

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
     */
    public function getIssueTrace(DataFlowNode $source): array
    {
        $previous_source = $source->taintSource;

        $node = [
            'location' => $source->code_location,
            'label' => $source->label,
            'entry_path_type' => end($source->path_types) ?: '',
        ];

        if ($previous_source) {
            if ($previous_source === $source) {
                return [];
            }

            return [...$this->getIssueTrace($previous_source), $node];
        }

        return [$node];
    }

    public function connectSinksAndSources(): void
    {
        $visited_source_ids = [];

        $sources = $this->sources;
        $sinks = $this->sinks;

        $this->sinks = [];
        $this->sources = [];

        ksort($this->forward_edges);

        $config = Config::getInstance();

        $project_analyzer = ProjectAnalyzer::getInstance();

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

        // reprocess resolved descendants up to a maximum nesting level of 40
        for ($i = 0; count($sinks) && count($sources) && $i < 40; $i++) {
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
                        $config,
                        $project_analyzer,
                    );
                    continue;
                }
        
                // If this is a specialized node AND it was added using addNode, de-specialize;
                // Then, if we have one or more edges starting at the de-specialized node,
                // process destinations of those edges.
                if ($source->is_first_level_and_specialized) {
                    /** @var string $source->unspecialized_id */
                    if (!isset($this->forward_edges[$source->unspecialized_id])) {
                        continue;
                    }
                    $generated_source = clone $source;
                    $generated_source->id = $source->unspecialized_id;
                    $generated_source->is_first_level_and_specialized = false;
                    $generated_source->processing_specialized_descendants_of[$source->specialization_key][$source->unspecialized_id] = $source->id;
        
                    $this->getChildNodes(
                        $new_sources,
                        $generated_source,
                        $source_taints,
                        $sinks,
                        $visited_source_ids,
                        $config,
                        $project_analyzer,
                    );

                    // If this node has first level specializations (=> is first-level & unspecialized),
                    // process them all
                } elseif (isset($this->specializations[$source->id])) {
                    if ($source->processing_specialized_descendants_of) {
                        // If processing descendants of a specialized call, accept only descendants.
                        foreach ($this->specializations[$source->id] as $specialization => $specialized_id) {
                            if (!isset($source->processing_specialized_descendants_of[$specialization])) {
                                continue;
                            }
                            $new_source = clone $source;
                            $new_source->is_first_level_and_specialized = false;
                            $new_source->id = $specialized_id;
                            unset($new_source->processing_specialized_descendants_of[$specialization]);
            
                            $this->getChildNodes(
                                $new_sources,
                                $new_source,
                                $source_taints,
                                $sinks,
                                $visited_source_ids,
                                $config,
                                $project_analyzer,
                            );
                        }
                    } else {
                        // If not processing descendants, accept all specializations.
                        foreach ($this->specializations[$source->id] as $specialization => $specialized_id) {
                            $new_source = clone $source;
                            $new_source->is_first_level_and_specialized = false;
                            $new_source->id = $specialized_id;

                            $this->getChildNodes(
                                $new_sources,
                                $new_source,
                                $source_taints,
                                $sinks,
                                $visited_source_ids,
                                $config,
                                $project_analyzer,
                            );
                        }
                    }
                } else {
                    // Process all descendants 
                    foreach ($source->processing_specialized_descendants_of as $map) {
                        if (isset($map[$source->id])) {
                            $specialized_id = $map[$source->id];
                            if (!isset($this->forward_edges[$specialized_id])) {
                                continue;
                            }
                            $new_source = clone $source;
                            $new_source->is_first_level_and_specialized = false;
                            $new_source->id = $specialized_id;
        
                            $this->getChildNodes(
                                $new_sources,
                                $new_source,
                                $source_taints,
                                $sinks,
                                $visited_source_ids,
                                $config,
                                $project_analyzer,
                            );
                        }
                    }
                }
            }

            $sources = $new_sources;
            unset($new_sources);
        }
    }

    /**
     * @param array<DataFlowNode> $sinks
     * @param array<string, DataFlowNode> $new_sources
     * @param-out array<string, DataFlowNode> $new_sources
     */
    private function getChildNodes(
        array &$new_sources,
        DataFlowNode $generated_source,
        int $source_taints,
        array $sinks,
        array $visited_source_ids,
        Config $config,
        ProjectAnalyzer $project_analyzer,
    ): void {
        foreach ($this->forward_edges[$generated_source->id] as $to_id => $path) {
            if (!isset($this->nodes[$to_id])) {
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

                    foreach (TaintKindGroup::TAINT_TO_NAME as $matching_taint => $_) {
                        if (!($matching_taints & $matching_taint)) {
                            continue;
                        }
                        $issue = match ($matching_taint) {
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
                            default => new TaintedCustom(
                                'Detected tainted ' . $matching_taint,
                                $issue_location,
                                $issue_trace,
                                $path,
                            ),
                        };

                        IssueBuffer::maybeAdd($issue);
                    }
                }
            }

            $key = $to_id .
            ' ' . json_encode($generated_source->processing_specialized_descendants_of, JSON_THROW_ON_ERROR) .
            ' ' . $new_taints;

            if (isset($new_sources[$key])) {
                continue;
            }

            $new_destination = clone $this->nodes[$to_id];
            $new_destination->is_first_level_and_specialized = false;
            $new_destination->taintSource = $generated_source;
            $new_destination->taints = $new_taints;
            $new_destination->processing_specialized_descendants_of = $generated_source->processing_specialized_descendants_of;
            $new_destination->path_types = [...$generated_source->path_types, $path_type];

            $new_sources[$key] = $new_destination;
        }
    }
}
