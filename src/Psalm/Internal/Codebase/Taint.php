<?php

namespace Psalm\Internal\Codebase;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Taint\Sink;
use Psalm\Internal\Taint\Source;
use Psalm\Internal\Taint\Taintable;
use Psalm\IssueBuffer;
use Psalm\Issue\TaintedInput;
use function array_merge;
use function array_merge_recursive;
use function strtolower;
use UnexpectedValueException;

class Taint
{
    /**
     * @var array<string, ?Sink>
     */
    private $new_sinks = [];

    /**
     * @var array<string, ?Source>
     */
    private $new_sources = [];

    /**
     * @var array<string, ?Sink>
     */
    private static $previous_sinks = [];

    /**
     * @var array<string, ?Source>
     */
    private static $previous_sources = [];

    /**
     * @var array<string, ?Sink>
     */
    private static $archived_sinks = [];

    /**
     * @var array<string, ?Source>
     */
    private static $archived_sources = [];

    /**
     * @var array<string, array<string>>
     */
    private $specializations = [];

    public function __construct()
    {
        self::$previous_sinks = [];
        self::$previous_sources = [];
        self::$archived_sinks = [];
        self::$archived_sources = [];
    }

    public function hasExistingSink(Taintable $sink) : ?Sink
    {
        return self::$archived_sinks[$sink->id] ?? null;
    }

    public function hasExistingSource(Taintable $source) : ?Source
    {
        return self::$archived_sources[$source->id] ?? null;
    }

    /**
     * @param ?array<string> $suffixes
     */
    public function hasPreviousSink(Sink $source, ?array &$suffixes = null) : ?Sink
    {
        if (isset($this->specializations[$source->id])) {
            $suffixes = $this->specializations[$source->id];

            foreach ($suffixes as $suffix) {
                if (isset(self::$previous_sinks[$source->id . '-' . $suffix])) {
                    return self::$previous_sinks[$source->id . '-' . $suffix];
                }
            }

            return null;
        }

        return self::$previous_sinks[$source->id] ?? null;
    }

    /**
     * @param ?array<string> $suffixes
     */
    public function hasPreviousSource(Source $source, ?array &$suffixes = null) : ?Source
    {
        if (isset($this->specializations[$source->id])) {
            $suffixes = $this->specializations[$source->id];

            foreach ($suffixes as $suffix) {
                if (isset(self::$previous_sources[$source->id . '-' . $suffix])) {
                    return self::$previous_sources[$source->id . '-' . $suffix];
                }
            }

            return null;
        }

        return self::$previous_sources[$source->id] ?? null;
    }

    public function addSpecialization(string $base_id, string $suffix) : void
    {
        if (isset($this->specializations[$base_id])) {
            if (!\in_array($suffix, $this->specializations)) {
                $this->specializations[$base_id][] = $suffix;
            }
        } else {
            $this->specializations[$base_id] = [$suffix];
        }
    }

    /**
     * @param array<Source> $sources
     */
    public function addSources(
        StatementsAnalyzer $statements_analyzer,
        array $sources
    ) : void {
        foreach ($sources as $source) {
            if ($this->hasExistingSource($source)) {
                continue;
            }

            if (($existing_sink = $this->hasExistingSink($source)) && $source->code_location) {
                if (IssueBuffer::accepts(
                    new TaintedInput(
                        'in path ' . $this->getPredecessorPath($source)
                            . ' out path ' . $this->getSuccessorPath($existing_sink),
                        $source->code_location
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $this->new_sources[$source->id] = $source;
        }
    }

    /**
     * @param array<Sink> $sinks
     */
    public function addSinks(
        StatementsAnalyzer $statements_analyzer,
        array $sinks
    ) : void {
        foreach ($sinks as $sink) {
            if ($this->hasExistingSink($sink)) {
                continue;
            }

            if (($existing_source = $this->hasExistingSource($sink)) && $sink->code_location) {
                if (IssueBuffer::accepts(
                    new TaintedInput(
                        'in path ' . $this->getPredecessorPath($existing_source)
                            . ' out path ' . $this->getSuccessorPath($sink),
                        $sink->code_location
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $this->new_sinks[$sink->id] = $sink;
        }
    }

    /**
     * @var array<string, bool> $visited_paths
     */
    public function getPredecessorPath(Source $source, array $visited_paths = []) : string
    {
        $location_summary = '';

        if ($source->code_location) {
            $location_summary = $source->code_location->getQuickSummary();
        }

        if (isset($visited_paths[$source->id . ' ' . $location_summary])) {
            return '';
        }

        $visited_paths[$source->id . ' ' . $location_summary] = true;

        $source_descriptor = $source->id . ($location_summary ? ' (' . $location_summary . ')' : '');

        $previous_source = $source->parents[0] ?? null;

        if ($previous_source) {
            if ($previous_source === $source) {
                return '';
            }

            return $this->getPredecessorPath($previous_source, $visited_paths) . ' -> ' . $source_descriptor;
        }

        return $source_descriptor;
    }

    /**
     * @var array<string, bool> $visited_paths
     */
    public function getSuccessorPath(Sink $sink, array $visited_paths = []) : string
    {
        $location_summary = '';

        if ($sink->code_location) {
            $location_summary = $sink->code_location->getQuickSummary();
        }

        if (isset($visited_paths[$sink->id . ' ' . $location_summary])) {
            return '';
        }

        $visited_paths[$sink->id . ' ' . $location_summary] = true;

        $sink_descriptor = $sink->id . ($location_summary ? ' (' . $location_summary . ')' : '');

        $next_sink = $sink->children[0] ?? null;

        if ($next_sink) {
            if ($next_sink === $sink) {
                return '';
            }

            return $sink_descriptor . ' -> ' . $this->getSuccessorPath($next_sink, $visited_paths);
        }

        return $sink_descriptor;
    }

    public function hasNewSinksAndSources() : bool
    {
        return $this->new_sinks || $this->new_sources;
    }

    public function addThreadData(self $taint) : void
    {
        $this->new_sinks = array_merge(
            $this->new_sinks,
            $taint->new_sinks
        );

        $this->new_sources = array_merge(
            $this->new_sources,
            $taint->new_sources
        );

        foreach ($taint->specializations as $id => $specializations) {
            if (!isset($this->specializations[$id])) {
                $this->specializations[$id] = $specializations;
            } else {
                $this->specializations[$id] = \array_unique(
                    array_merge($this->specializations[$id], $specializations)
                );
            }
        }
    }

    public function clearNewSinksAndSources() : void
    {
        self::$archived_sinks = array_merge(
            self::$archived_sinks,
            $this->new_sinks
        );

        self::$previous_sinks = $this->new_sinks;

        $this->new_sinks = [];

        self::$archived_sources = array_merge(
            self::$archived_sources,
            $this->new_sources
        );

        self::$previous_sources = $this->new_sources;

        $this->new_sources = [];
    }
}
