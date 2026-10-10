<?php

declare(strict_types=1);

namespace Psalm\Progress;

use Override;

use function error_reporting;

use const E_ALL;

/**
 * @api
 */
final class DebugProgress extends Progress
{
    #[Override]
    public function setErrorReporting(): void
    {
        error_reporting(E_ALL);
    }

    #[Override]
    public function debug(string $message): void
    {
        $this->write($message);
    }

    #[Override]
    public function startPhase(Phase $phase, int $threads = 1): void
    {
        // Preloading happens before the header, and only concerns Psalm itself: the header stays the first line
        if ($phase === Phase::PRELOADING || $phase === Phase::JIT_COMPILATION) {
            return;
        }

        $this->write(match ($phase) {
            Phase::SCAN => "Scanning files...\n",
            Phase::ANALYSIS => "Analyzing files...\n",
            Phase::ALTERING => "Altering files...\n",
            Phase::TAINT_GRAPH_RESOLUTION => "Resolving taint graph...\n",
            Phase::MERGING_THREAD_RESULTS => "Merging thread results...\n",
            Phase::LOADING_CACHE => "Loading cached results...\n",
            Phase::FINISHING => "Finishing...\n",
        });
    }

    /**
     * Like the other progress classes, a thread count is only shown for a phase that forks
     */
    #[Override]
    public function setThreads(int $threads): void
    {
        if ($threads > 1) {
            $this->write("Forking $threads threads\n");
        }
    }
    
    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function expand(int $number_of_tasks): void
    {
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function taskDone(int $level): void
    {
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function finish(): void
    {
    }

    #[Override]
    public function alterFileDone(string $file_name): void
    {
        $this->write('Altered ' . $file_name . "\n");
    }
}
