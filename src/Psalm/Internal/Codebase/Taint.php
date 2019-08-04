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
    private $previous_sinks = [];

    /**
     * @var array<string, ?TypeSource>
     */
    private $previous_sources = [];

    /**
     * @var array<string, ?TypeSource>
     */
    private $archived_sinks = [];

    /**
     * @var array<string, ?TypeSource>
     */
    private $archived_sources = [];

    public function hasExistingSink(TypeSource $source) : ?TypeSource
    {
        return $this->archived_sinks[$source->id] ?? null;
    }

    public function hasPreviousSink(TypeSource $source) : bool
    {
        return isset($this->previous_sinks[$source->id]);
    }

    public function hasPreviousSource(TypeSource $source) : bool
    {
        return isset($this->previous_sources[$source->id]);
    }

    public function hasExistingSource(TypeSource $source) : ?TypeSource
    {
        return $this->archived_sources[$source->id] ?? null;
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

    public function getPredecessorPath(TypeSource $source) : string
    {
        $source_descriptor = $source->id
            . ($source->code_location ? ' (' . $source->code_location->getShortSummary() . ')' : '');

        if ($previous_source = $this->new_sources[$source->id] ?? $this->archived_sources[$source->id] ?? null) {
            if ($previous_source === $source) {
                throw new \UnexpectedValueException('bad');
            }

            return $this->getPredecessorPath($previous_source) . ' -> ' . $source_descriptor;
        }

        return $source_descriptor;
    }

    public function getSuccessorPath(TypeSource $source) : string
    {
        $source_descriptor = $source->id
            . ($source->code_location ? ' (' . $source->code_location->getShortSummary() . ')' : '');

        if ($next_source = $this->new_sinks[$source->id] ?? $this->archived_sinks[$source->id] ?? null) {
            return $source_descriptor . ' -> ' . $this->getSuccessorPath($next_source);
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
        return $this->new_sinks && $this->new_sources;
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
    }

    public function clearNewSinksAndSources() : void
    {
        $this->archived_sinks = array_merge(
            $this->archived_sinks,
            $this->new_sinks
        );

        $this->previous_sinks = $this->new_sinks;

        $this->new_sinks = [];

        $this->archived_sources = array_merge(
            $this->archived_sources,
            $this->new_sources
        );

        $this->previous_sources = $this->new_sources;

        $this->new_sources = [];
    }
}
