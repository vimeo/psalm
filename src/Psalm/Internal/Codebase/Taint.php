<?php

namespace Psalm\Internal\Codebase;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Taint\Path;
use Psalm\Internal\Taint\Sink;
use Psalm\Internal\Taint\Source;
use Psalm\Internal\Taint\TaintNode;
use Psalm\Internal\Taint\Taintable;
use Psalm\IssueBuffer;
use Psalm\Issue\TaintedInput;
use function array_merge;
use function array_merge_recursive;
use function strtolower;
use UnexpectedValueException;
use function count;
use function implode;
use function substr;
use function strlen;
use function array_intersect;

class Taint
{
    /** @var array<string, Source> */
    private $sources = [];

    /** @var array<string, TaintNode> */
    private $nodes = [];

    /** @var array<string, Sink> */
    private $sinks = [];

    /** @var array<string, array<string, array{array<string>, array<string>}>> */
    private $forward_edges = [];

    /** @var array<string, array<string, array{array<string>, array<string>}>> */
    private $backward_edges = [];

    /** @var array<string, array<string, true>> */
    private $specialized_calls = [];

    public function addSource(Source $node) : void
    {
        $this->sources[$node->id] = $node;
    }

    public function addSink(Sink $node) : void
    {
        $this->sinks[$node->id] = $node;
    }

    public function addTaintNode(TaintNode $node) : void
    {
        $this->nodes[$node->id] = $node;

        if ($node->unspecialized_id && $node->specialization_key) {
            $this->specialized_calls[$node->specialization_key][$node->unspecialized_id] = true;
        }
    }

    /**
     * @param array<string> $added_taints
     * @param array<string> $removed_taints
     */
    public function addPath(
        Taintable $from,
        Taintable $to,
        array $added_taints = [],
        array $removed_taints = []
    ) : void {
        $from_id = $from->id;
        $to_id = $to->id;

        $this->forward_edges[$from_id][$to_id] = [$added_taints, $removed_taints];
        $this->backward_edges[$to_id][$from_id] = [$added_taints, $removed_taints];
    }

    public function getPredecessorPath(Taintable $source) : string
    {
        $location_summary = '';

        if ($source->code_location) {
            $location_summary = $source->code_location->getShortSummary();
        }

        $source_descriptor = $source->label . ($location_summary ? ' (' . $location_summary . ')' : '');

        $previous_source = $source->previous;

        if ($previous_source) {
            if ($previous_source === $source) {
                return '';
            }

            return $this->getPredecessorPath($previous_source) . ' -> ' . $source_descriptor;
        }

        return $source_descriptor;
    }

    public function getSuccessorPath(Taintable $sink) : string
    {
        $location_summary = '';

        if ($sink->code_location) {
            $location_summary = $sink->code_location->getShortSummary();
        }

        $sink_descriptor = $sink->label . ($location_summary ? ' (' . $location_summary . ')' : '');

        $next_sink = $sink->previous;

        if ($next_sink) {
            if ($next_sink === $sink) {
                return '';
            }

            return $sink_descriptor . ' -> ' . $this->getSuccessorPath($next_sink);
        }

        return $sink_descriptor;
    }

    public function addThreadData(self $taint) : void
    {
        $this->sources += $taint->sources;
        $this->sinks += $taint->sinks;
        $this->nodes += $taint->nodes;
        $this->specialized_calls += $taint->specialized_calls;

        foreach ($taint->forward_edges as $key => $map) {
            if (!isset($this->forward_edges[$key])) {
                $this->forward_edges[$key] = $map;
            } else {
                $this->forward_edges[$key] = \array_merge(
                    $this->forward_edges[$key],
                    $map
                );
            }
        }

        foreach ($taint->backward_edges as $key => $map) {
            if (!isset($this->backward_edges[$key])) {
                $this->backward_edges[$key] = $map;
            } else {
                $this->backward_edges[$key] = \array_merge(
                    $this->backward_edges[$key],
                    $map
                );
            }
        }
    }

    public function connectSinksAndSources() : void
    {
        $visited_source_ids = [];
        //$visited_sink_ids = [];

        $sources = $this->sources;
        $sinks = $this->sinks;

        for ($i = 0; count($sinks) && count($sources) && $i < 10; $i++) {
            $new_sources = [];

            foreach ($sources as $source) {
                $source_taints = $source->taints;
                \sort($source_taints);

                $visited_source_ids[$source->id][implode(',', $source_taints)] = true;

                if (!isset($this->forward_edges[$source->id])) {
                    $source = clone $source;

                    if ($source->specialization_key && isset($this->specialized_calls[$source->specialization_key])) {
                        $source->specialized_calls[$source->specialization_key]
                            = $this->specialized_calls[$source->specialization_key];

                        $source->id = substr($source->id, 0, -strlen($source->specialization_key) - 1);
                    } else {
                        foreach ($source->specialized_calls as $key => $map) {
                            if (isset($map[$source->id]) && isset($this->forward_edges[$source->id . '-' . $key])) {
                                $source->id = $source->id . '-' . $key;
                            }
                        }
                    }

                    if (!isset($this->forward_edges[$source->id])) {
                        continue;
                    }
                }

                foreach ($this->forward_edges[$source->id] as $to_id => [$added_taints, $removed_taints]) {
                    if (!isset($this->nodes[$to_id])) {
                        continue;
                    }

                    $new_taints = \array_unique(
                        \array_diff(
                            \array_merge($source_taints, $added_taints),
                            $removed_taints
                        )
                    );

                    \sort($new_taints);

                    $destination_node = $this->nodes[$to_id];

                    if (isset($visited_source_ids[$to_id][implode(',', $new_taints)])) {
                        continue;
                    }

                    if (isset($sinks[$to_id])) {
                        $matching_taints = array_intersect($sinks[$to_id]->taints, $new_taints);

                        if ($matching_taints && $source->code_location) {
                            if (IssueBuffer::accepts(
                                new TaintedInput(
                                    'Detected tainted ' . implode(', ', $matching_taints)
                                        . ' in path: ' . $this->getPredecessorPath($source)
                                        . ' -> ' . $this->getSuccessorPath($sinks[$to_id]),
                                    $sinks[$to_id]->code_location ?: $source->code_location
                                )
                            )) {
                                // fall through
                            }

                            continue;
                        }
                    }

                    $new_destination = clone $destination_node;
                    $new_destination->previous = $source;
                    $new_destination->taints = $new_taints;
                    $new_destination->specialized_calls = $source->specialized_calls;

                    $new_sources[$to_id] = $new_destination;
                }
            }

            /**
            $new_sinks = [];

            foreach ($sinks as $sink) {
                $sink_taints = $sink->taints;
                \sort($sink_taints);

                $visited_sink_ids[$sink->id][implode(',', $sink_taints)] = true;

                if (!isset($this->backward_edges[$sink->id])) {
                    continue;
                }

                foreach ($this->backward_edges[$sink->id] as $from_id => [$added_taints, $removed_taints]) {
                    if (!isset($this->nodes[$from_id])) {
                        continue;
                    }

                    $new_taints = \array_unique(
                        \array_diff(
                            \array_merge($sink_taints, $added_taints),
                            $removed_taints
                        )
                    );

                    \sort($new_taints);

                    $destination_node = $this->nodes[$from_id];

                    if (isset($visited_sink_ids[$from_id][implode(',', $new_taints)])) {
                        continue;
                    }

                    if (isset($sources[$from_id])) {
                        $matching_taints = array_intersect($sources[$from_id]->taints, $new_taints);

                        if ($matching_taints) {
                            if (IssueBuffer::accepts(
                                new TaintedInput(
                                    'Detected taints ' . implode(', ', $matching_taints)
                                        . ' in path: ' . $this->getPredecessorPath($sources[$from_id])
                                        . ' -> ' . $this->getSuccessorPath($sink),
                                    $sink->code_location
                                )
                            )) {
                                // fall through
                            }
                        }
                    } elseif (isset($new_sources[$from_id])) {
                        $matching_taints = array_intersect($new_sources[$from_id]->taints, $new_taints);

                        if ($matching_taints) {
                            if (IssueBuffer::accepts(
                                new TaintedInput(
                                    'Detected taints ' . implode(', ', $matching_taints)
                                        . ' in path: ' . $this->getPredecessorPath($new_sources[$from_id])
                                        . ' -> ' . $this->getSuccessorPath($sink),
                                    $sink->code_location
                                )
                            )) {
                                // fall through
                            }
                        }
                    }

                    $new_destination = clone $this->nodes[$from_id];
                    $new_destination->taints = $new_taints;
                    $new_destination->previous = $sink;
                    $new_destination->specialized_calls = $source->specialized_calls;

                    $new_sinks[$from_id] = $new_destination;
                }
            }

            $sinks = $new_sinks;
            */

            $sources = $new_sources;
        }
    }
}
