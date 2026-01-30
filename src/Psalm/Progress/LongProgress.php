<?php

declare(strict_types=1);

namespace Psalm\Progress;

use LogicException;
use Override;

use function floor;
use function microtime;
use function round;
use function sprintf;
use function str_repeat;
use function strlen;

use const PHP_EOL;

class LongProgress extends Progress
{
    final public const NUMBER_OF_COLUMNS = 60;

    protected ?int $number_of_tasks = null;

    protected int $progress = 0;

    protected bool $fixed_size = false;

    protected ?Phase $prevPhase = null;
    protected float $started = 0.0;

    public function __construct(
        protected bool $print_errors = true,
        protected bool $print_infos = true,
        protected bool $in_ci = false,
    ) {
    }

    #[Override]
    public function debug(string $message): void
    {
    }

    #[Override]
    public function startPhase(Phase $phase, int $threads = 1): void
    {
        $threads = $threads === 1 ? '' : " ($threads threads)";
        $this->reportPhaseDuration($phase);
        $this->write(match ($phase) {
            Phase::SCAN => "\nScanning files$threads...\n\n",
            Phase::ANALYSIS => "\nAnalyzing files$threads...\n\n",
            Phase::ALTERING => "\nUpdating files$threads...\n",
            Phase::TAINT_GRAPH_RESOLUTION => "\n\nResolving taint graph$threads...\n\n",
            Phase::JIT_COMPILATION => "JIT compilation in progress$threads...\n\n",
            Phase::PRELOADING => "Preloading in progress$threads...\n\n",
            Phase::MERGING_THREAD_RESULTS => "\nMerging thread results$threads...\n\n",
        });
        $this->fixed_size = $phase === Phase::ANALYSIS
            || $phase === Phase::ALTERING
            || $phase === Phase::JIT_COMPILATION
            || $phase === Phase::PRELOADING
            || $phase === Phase::MERGING_THREAD_RESULTS
            || $phase === Phase::TAINT_GRAPH_RESOLUTION;
    }

    protected function reportPhaseDuration(?Phase $newPhase = null): void
    {
        if ($this->prevPhase === $newPhase) {
            return;
        }
        $this->progress = 0;
        $this->number_of_tasks = 0;
        if ($this->prevPhase !== null) {
            $took = round(microtime(true) - $this->started, 1);
            $this->write(match ($this->prevPhase) {
                Phase::SCAN => "\n\nScan took $took seconds.\n",
                Phase::ANALYSIS => "\n\nAnalysis took $took seconds.\n",
                Phase::ALTERING => "\n\nUpdating files took $took seconds.\n",
                Phase::TAINT_GRAPH_RESOLUTION => "\n\nTaint graph resolution took $took seconds.\n",
                Phase::JIT_COMPILATION => "JIT compilation took $took seconds.\n\n",
                Phase::PRELOADING => "Preloading took $took seconds.\n\n",
                Phase::MERGING_THREAD_RESULTS => "\nMerging thread results took $took seconds.\n\n",
            });
        }
        $this->started = microtime(true);
        $this->prevPhase = $newPhase;
    }

    #[Override]
    public function alterFileDone(string $file_name): void
    {
        $this->write('Altered ' . $file_name . "\n");
    }

    #[Override]
    public function expand(int $number_of_tasks): void
    {
        $this->number_of_tasks += $number_of_tasks;
    }

    #[Override]
    public function taskDone(int $level): void
    {
        if ($this->number_of_tasks === null) {
            throw new LogicException('Progress::start() should be called before Progress::taskDone()');
        }

        ++$this->progress;

        if (!$this->fixed_size) {
            if ($this->in_ci) {
                return;
            }
            if ($this->progress == 1 || $this->progress == $this->number_of_tasks || $this->progress % 10 == 0) {
                $this->write(sprintf(
                    "\r%s / %s...",
                    $this->progress,
                    $this->number_of_tasks,
                ));
            }
            return;
        }

        if ($level === 0 || ($level === 1 && !$this->print_infos) || !$this->print_errors) {
            $this->write(self::doesTerminalSupportUtf8() ? '░' : '_');
        } elseif ($level === 1) {
            $this->write('I');
        } else {
            $this->write('E');
        }


        if (($this->progress % self::NUMBER_OF_COLUMNS) !== 0) {
            if ($this->progress !== $this->number_of_tasks) {
                return;
            }
            if ($this->number_of_tasks > self::NUMBER_OF_COLUMNS) {
                $this->write(str_repeat(' ', self::NUMBER_OF_COLUMNS - ($this->progress % self::NUMBER_OF_COLUMNS)));
            }
        }

        $this->printOverview();
        $this->write(PHP_EOL);
    }

    #[Override]
    public function finish(): void
    {
        $this->reportPhaseDuration(null);
    }

    protected function getOverview(): string
    {
        if ($this->number_of_tasks === null) {
            throw new LogicException('Progress::start() should be called before Progress::startDone()');
        }

        $leadingSpaces = 1 + strlen((string) $this->number_of_tasks) - strlen((string) $this->progress);
        // Don't show 100% unless this is the last line of the progress bar.
        $percentage = floor($this->progress / $this->number_of_tasks * 100);

        return sprintf(
            '%s%s / %s (%s%%)',
            str_repeat(' ', $leadingSpaces),
            $this->progress,
            $this->number_of_tasks,
            $percentage,
        );
    }

    private function printOverview(): void
    {
        $this->write($this->getOverview());
    }
}
