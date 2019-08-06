<?php

namespace Psalm\Internal\Codebase;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Taint\TypeSource;
use Psalm\IssueBuffer;
use Psalm\Issue\TaintedInput;
use function array_merge;
use function array_merge_recursive;
use function strtolower;
use UnexpectedValueException;

class Taint
{
    /**
     * @var array<string, ?TypeSource>
     */
    private $new_sinks = [];

    /**
     * @var array<string, ?TypeSource>
     */
    private $new_sources = [];

    /**
     * @var array<string, ?TypeSource>
     */
    private static $previous_sinks = [];

    /**
     * @var array<string, ?TypeSource>
     */
    private static $previous_sources = [];

    /**
     * @var array<string, ?TypeSource>
     */
    private static $archived_sinks = [];

    /**
     * @var array<string, ?TypeSource>
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

    public function hasExistingSink(TypeSource $source) : ?TypeSource
    {
        return self::$archived_sinks[$source->id] ?? null;
    }

    public function hasExistingSource(TypeSource $source) : ?TypeSource
    {
        return self::$archived_sources[$source->id] ?? null;
    }

    /**
     * @param ?array<string> $suffixes
     */
    public function hasPreviousSink(TypeSource $source, ?array &$suffixes = null) : bool
    {
        if (isset($this->specializations[$source->id])) {
            $suffixes = $this->specializations[$source->id];

            foreach ($suffixes as $suffix) {
                if (isset(self::$previous_sinks[$source->id . '-' . $suffix])) {
                    return true;
                }
            }

            return false;
        }

        return isset(self::$previous_sinks[$source->id]);
    }

    /**
     * @param ?array<string> $suffixes
     */
    public function hasPreviousSource(TypeSource $source, ?array &$suffixes = null) : bool
    {
        if (isset($this->specializations[$source->id])) {
            $suffixes = $this->specializations[$source->id];

            foreach ($suffixes as $suffix) {
                if (isset(self::$previous_sources[$source->id . '-' . $suffix])) {
                    return true;
                }
            }

            return false;
        }

        return isset(self::$previous_sources[$source->id]);
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
     * @param array<TypeSource> $sources
     */
    public function addSources(
        StatementsAnalyzer $statements_analyzer,
        array $sources,
        \Psalm\CodeLocation $code_location,
        ?TypeSource $previous_source
    ) : void {
        foreach ($sources as $source) {
            if ($this->hasExistingSource($source)) {
                continue;
            }

            if ($this->hasExistingSink($source)) {
                if (IssueBuffer::accepts(
                    new TaintedInput(
                        ($previous_source ? 'in path ' . $this->getPredecessorPath($previous_source) : '')
                            . ' out path ' . $this->getSuccessorPath($source),
                        $code_location
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $this->new_sources[$source->id] = $previous_source;
        }
    }

    /**
     * @var array<string, bool> $visited_paths
     */
    public function getPredecessorPath(TypeSource $source, array $visited_paths = []) : string
    {
        $location_summary = '';

        if ($source->code_location) {
            $location_summary = $source->code_location->getQuickSummary();

            if (isset($visited_paths[$location_summary])) {
                return '';
            }

            $visited_paths[$location_summary] = true;
        }

        $source_descriptor = $source->id . ($location_summary ? ' (' . $location_summary . ')' : '');

        if ($previous_source = $this->new_sources[$source->id] ?? self::$archived_sources[$source->id] ?? null) {
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
    public function getSuccessorPath(TypeSource $source, array $visited_paths = []) : string
    {
        $location_summary = '';

        if ($source->code_location) {
            $location_summary = $source->code_location->getQuickSummary();

            if (isset($visited_paths[$location_summary])) {
                return '';
            }

            $visited_paths[$location_summary] = true;
        }

        $source_descriptor = $source->id . ($location_summary ? ' (' . $location_summary . ')' : '');

        if ($next_source = $this->new_sinks[$source->id] ?? self::$archived_sinks[$source->id] ?? null) {
            if ($next_source === $source) {
                return '';
            }

            return $source_descriptor . ' -> ' . $this->getSuccessorPath($next_source, $visited_paths);
        }

        return $source_descriptor;
    }

    /**
     * @param array<TypeSource> $sources
     */
    public function addSinks(
        StatementsAnalyzer $statements_analyzer,
        array $sources,
        \Psalm\CodeLocation $code_location,
        ?TypeSource $previous_source
    ) : void {
        foreach ($sources as $source) {
            if ($this->hasExistingSink($source)) {
                continue;
            }

            if ($this->hasExistingSource($source)) {
                if (IssueBuffer::accepts(
                    new TaintedInput(
                        'in path ' . $this->getPredecessorPath($source)
                            . ($previous_source ? ' out path ' . $this->getSuccessorPath($previous_source) : ''),
                        $code_location
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $this->new_sinks[$source->id] = $previous_source;
        }
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
