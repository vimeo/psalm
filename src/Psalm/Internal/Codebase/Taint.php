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
use function strpos;
use function array_reverse;

class Taint
{
    /** @var array<string, Source> */
    private $sources = [];

    /** @var array<string, Taintable> */
    private $nodes = [];

    /** @var array<string, Sink> */
    private $sinks = [];

    /** @var array<string, array<string, Path>> */
    private $forward_edges = [];

    /** @var array<string, array<string, true>> */
    private $specialized_calls = [];

    /** @var array<string, array<string, true>> */
    private $specializations = [];

    public function addSource(Source $node) : void
    {
        $this->sources[$node->id] = $node;
    }

    public function addSink(Sink $node) : void
    {
        $this->sinks[$node->id] = $node;
        // in the rare case the sink is the _next_ node, this is necessary
        $this->nodes[$node->id] = $node;
    }

    public function addTaintNode(TaintNode $node) : void
    {
        $this->nodes[$node->id] = $node;

        if ($node->unspecialized_id && $node->specialization_key) {
            $this->specialized_calls[$node->specialization_key][$node->unspecialized_id] = true;
            $this->specializations[$node->unspecialized_id][$node->specialization_key] = true;
        }
    }

    /**
     * @param array<string> $added_taints
     * @param array<string> $removed_taints
     */
    public function addPath(
        Taintable $from,
        Taintable $to,
        string $path_type,
        array $added_taints = [],
        array $removed_taints = []
    ) : void {
        $from_id = $from->id;
        $to_id = $to->id;

        if ($from_id === $to_id) {
            return;
        }

        $this->forward_edges[$from_id][$to_id] = new Path($path_type, $added_taints, $removed_taints);
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
                $this->forward_edges[$key] += $map;
            }
        }

        foreach ($taint->specializations as $key => $map) {
            if (!isset($this->specializations[$key])) {
                $this->specializations[$key] = $map;
            } else {
                $this->specializations[$key] += $map;
            }
        }
    }

    public function connectSinksAndSources() : void
    {
        $visited_source_ids = [];

        $sources = $this->sources;
        $sinks = $this->sinks;

        for ($i = 0; count($sinks) && count($sources) && $i < 40; $i++) {
            $new_sources = [];

            foreach ($sources as $source) {
                $source_taints = $source->taints;
                \sort($source_taints);

                $visited_source_ids[$source->id][implode(',', $source_taints)] = true;

                $generated_sources = $this->getSpecializedSources($source);

                foreach ($generated_sources as $generated_source) {
                    $new_sources = array_merge(
                        $new_sources,
                        $this->getChildNodes(
                            $generated_source,
                            $source_taints,
                            $sinks,
                            $visited_source_ids
                        )
                    );
                }
            }

            $sources = $new_sources;
        }
    }

    /**
     * @param array<string> $source_taints
     * @param array<Taintable> $sinks
     * @return array<Taintable>
     */
    private function getChildNodes(
        Taintable $generated_source,
        array $source_taints,
        array $sinks,
        array $visited_source_ids
    ) : array {
        $new_sources = [];

        foreach ($this->forward_edges[$generated_source->id] as $to_id => $path) {
            $path_type = $path->type;
            $added_taints = $path->unescaped_taints;
            $removed_taints = $path->escaped_taints;

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

            if (strpos($path_type, 'array-fetch-') === 0) {
                $previous_path_types = array_reverse($generated_source->path_types);

                foreach ($previous_path_types as $previous_path_type) {
                    if ($previous_path_type === 'array-assignment') {
                        break;
                    }

                    if (strpos($previous_path_type, 'array-assignment-') === 0) {
                        if (substr($previous_path_type, 17) === substr($path_type, 12)) {
                            break;
                        }

                        continue 2;
                    }
                }
            }

            if (strpos($path_type, 'property-fetch-') === 0) {
                $previous_path_types = array_reverse($generated_source->path_types);

                foreach ($previous_path_types as $previous_path_type) {
                    if ($previous_path_type === 'property-assignment') {
                        break;
                    }

                    if (strpos($previous_path_type, 'property-assignment-') === 0) {
                        if (substr($previous_path_type, 20) === substr($path_type, 15)) {
                            break;
                        }

                        continue 2;
                    }
                }
            }

            if (isset($sinks[$to_id])) {
                $matching_taints = array_intersect($sinks[$to_id]->taints, $new_taints);

                if ($matching_taints && $generated_source->code_location) {
                    $config = \Psalm\Config::getInstance();

                    if ($sinks[$to_id]->code_location
                        && $config->reportIssueInFile('TaintedInput', $sinks[$to_id]->code_location->file_path)
                    ) {
                        $issue_location = $sinks[$to_id]->code_location;
                    } else {
                        $issue_location = $generated_source->code_location;
                    }

                    if (IssueBuffer::accepts(
                        new TaintedInput(
                            'Detected tainted ' . implode(', ', $matching_taints)
                                . ' in path: ' . $this->getPredecessorPath($generated_source)
                                . ' -> ' . $this->getSuccessorPath($sinks[$to_id]),
                            $issue_location
                        )
                    )) {
                        // fall through
                    }

                    continue;
                }
            }

            $new_destination = clone $destination_node;
            $new_destination->previous = $generated_source;
            $new_destination->taints = $new_taints;
            $new_destination->specialized_calls = $generated_source->specialized_calls;
            $new_destination->path_types = array_merge($generated_source->path_types, [$path_type]);

            $new_sources[$to_id] = $new_destination;
        }

        return $new_sources;
    }

    /** @return array<Taintable> */
    private function getSpecializedSources(Taintable $source) : array
    {
        $generated_sources = [];

        if (isset($this->forward_edges[$source->id])) {
            return [$source];
        }

        if ($source->specialization_key && isset($this->specialized_calls[$source->specialization_key])) {
            $generated_source = clone $source;

            $generated_source->specialized_calls[$source->specialization_key]
                = $this->specialized_calls[$source->specialization_key];

            $generated_source->id = substr($source->id, 0, -strlen($source->specialization_key) - 1);

            $generated_sources[] = $generated_source;
        } elseif (isset($this->specializations[$source->id])) {
            foreach ($this->specializations[$source->id] as $specialization => $_) {
                if (isset($source->specialized_calls[$specialization])) {
                    $new_source = clone $source;

                    $new_source->id = $source->id . '-' . $specialization;

                    $generated_sources[] = $new_source;
                }
            }
        } else {
            foreach ($source->specialized_calls as $key => $map) {
                if (isset($map[$source->id]) && isset($this->forward_edges[$source->id . '-' . $key])) {
                    $new_source = clone $source;

                    $new_source->id = $source->id . '-' . $key;

                    $generated_sources[] = $new_source;
                }
            }
        }

        return \array_filter(
            $generated_sources,
            function ($new_source) {
                return isset($this->forward_edges[$new_source->id]);
            }
        );
    }
}
