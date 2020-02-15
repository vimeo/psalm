<?php

namespace Psalm\Internal\Codebase;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\FileStorageProvider;
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

    public function hasNewOrExistingSink(Taintable $sink) : ?Sink
    {
        return $this->new_sinks[$sink->id] ?? self::$archived_sinks[$sink->id] ?? null;
    }

    public function hasNewOrExistingSource(Taintable $source) : ?Source
    {
        return $this->new_sources[$source->id] ?? self::$archived_sources[$source->id] ?? null;
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
            $candidate_suffixes = $this->specializations[$source->id];

            foreach ($candidate_suffixes as $suffix) {
                if (isset(self::$previous_sources[$source->id . '-' . $suffix])) {
                    $suffixes = [$suffix];
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
            if (!\in_array($suffix, $this->specializations[$base_id])) {
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
        array $sources
    ) : void {
        foreach ($sources as $source) {
            if ($this->hasExistingSource($source)) {
                continue;
            }

            if ($this->hasExistingSink($source) && $source->code_location) {
                // do nothing
            }

            $this->new_sources[$source->id] = $source;
        }
    }

    /**
     * @param array<Sink> $sinks
     */
    public function addSinks(
        array $sinks
    ) : void {
        foreach ($sinks as $sink) {
            if ($this->hasExistingSink($sink)) {
                continue;
            }

            if ($this->hasExistingSource($sink) && $sink->code_location) {
                // do nothing
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
            $location_summary = $source->code_location->getShortSummary();
        }

        if (isset($visited_paths[$source->id . ' ' . $location_summary])) {
            return '';
        }

        $visited_paths[$source->id . ' ' . $location_summary] = true;

        $source_descriptor = $source->label . ($location_summary ? ' (' . $location_summary . ')' : '');

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
            $location_summary = $sink->code_location->getShortSummary();
        }

        if (isset($visited_paths[$sink->id . ' ' . $location_summary])) {
            return '';
        }

        $visited_paths[$sink->id . ' ' . $location_summary] = true;

        $sink_descriptor = $sink->label . ($location_summary ? ' (' . $location_summary . ')' : '');

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
        foreach ($this->new_sinks as $sink) {
            if ($sink && ($existing_source = $this->hasNewOrExistingSource($sink)) && $sink->code_location) {
                $last_location = $sink;

                while ($last_location->children) {
                    $first_child = \reset($last_location->children);
                    if (!$first_child->code_location) {
                        break;
                    }

                    $last_location = $first_child;
                }

                if (IssueBuffer::accepts(
                    new TaintedInput(
                        'path: ' . $this->getPredecessorPath($existing_source)
                            . ' -> ' . $this->getSuccessorPath($sink),
                        $last_location->code_location ?: $sink->code_location
                    )
                )) {
                    // fall through
                }
            }
        }

        foreach ($this->new_sources as $source) {
            if ($source && ($existing_sink = $this->hasNewOrExistingSink($source)) && $source->code_location) {
                $last_location = $existing_sink;

                while ($last_location->children) {
                    $first_child = \reset($last_location->children);
                    if (!$first_child->code_location) {
                        break;
                    }

                    $last_location = $first_child;
                }

                if (IssueBuffer::accepts(
                    new TaintedInput(
                        'path: ' . $this->getPredecessorPath($source)
                            . ' -> ' . $this->getSuccessorPath($existing_sink),
                        $last_location->code_location ?: $source->code_location
                    )
                )) {
                    // fall through
                }
            }
        }

        if (!self::$archived_sources && !$this->new_sources) {
            return false;
        }

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

    /**
     * @return array<string, string>
     */
    public function getFilesToAnalyze(
        FileReferenceProvider $reference_provider,
        FileStorageProvider $file_storage_provider,
        ClassLikeStorageProvider $classlike_storage_provider,
        \Psalm\Config $config
    ) : array {
        $files = [];

        $new_sink_file_paths = [];

        foreach ($this->new_sinks as $new_sink) {
            if ($new_sink && $new_sink->code_location) {
                $new_sink_file_paths[$new_sink->code_location->file_path] = $new_sink->code_location->file_path;
            }
        }

        foreach ($new_sink_file_paths as $file_path) {
            $files_referencing_file = $reference_provider->getFilesReferencingFile($file_path);

            $files = array_merge($files_referencing_file, $files);
        }

        $new_source_file_paths = [];

        foreach ($this->new_sources as $new_source) {
            if ($new_source && $new_source->code_location) {
                $new_source_file_paths[$new_source->code_location->file_path] = $new_source->code_location->file_path;
            }
        }

        foreach ($new_source_file_paths as $file_path) {
            $classlikes = $file_storage_provider->get($file_path)->classlikes_in_file;

            foreach ($classlikes as $classlike_lc => $_) {
                $class_storage = $classlike_storage_provider->get($classlike_lc);

                if ($class_storage->location) {
                    $files[] = $class_storage->location->file_path;
                }
            }
        }

        $files = \array_filter(
            $files,
            function ($file) use ($config) {
                return $config->isInProjectDirs($file);
            }
        );

        $arr = \array_values($files);

        return \array_combine($arr, $arr);
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
