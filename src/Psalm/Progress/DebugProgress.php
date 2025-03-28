<?php

declare(strict_types=1);

namespace Psalm\Progress;

use Override;

use function error_reporting;

use const E_ALL;

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
        $threads = $threads === 1 ? '' : " ($threads threads)";
        $this->write(match ($phase) {
            Phase::SCAN => "\nScanning files$threads...\n\n",
            Phase::ANALYSIS => "\nAnalyzing files$threads...\n",
            Phase::ALTERING => "\nUpdating files$threads...\n",
            Phase::TAINT_GRAPH_RESOLUTION => "\nResolving taint graph$threads...\n",
            Phase::JIT_COMPILATION => "\nJIT compilation in progress$threads...\n",
            Phase::PRELOADING => "\nPreloading in progress$threads...\n",
            Phase::MERGING_THREAD_RESULTS => "\nMerging thread results$threads...\n",
        });
    }
    
    #[Override]
    public function expand(int $number_of_tasks): void
    {
    }

    #[Override]
    public function taskDone(int $level): void
    {
    }

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
