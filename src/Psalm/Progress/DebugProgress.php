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

        $threads = $threads === 1 ? '' : " · $threads threads";
        $this->write(match ($phase) {
            Phase::SCAN => "Scanning files$threads...\n",
            Phase::ANALYSIS => "Analyzing files$threads...\n",
            Phase::ALTERING => "Altering files$threads...\n",
            Phase::TAINT_GRAPH_RESOLUTION => "Resolving taint graph$threads...\n",
            Phase::MERGING_THREAD_RESULTS => "Merging thread results$threads...\n",
            Phase::LOADING_CACHE => "Loading cached results$threads...\n",
            Phase::FINISHING => "Finishing$threads...\n",
        });
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
