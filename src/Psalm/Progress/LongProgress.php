<?php

declare(strict_types=1);

namespace Psalm\Progress;

use LogicException;
use Override;

use function implode;
use function in_array;
use function intdiv;
use function max;
use function microtime;
use function number_format;
use function sprintf;
use function str_repeat;
use function stream_isatty;
use function strlen;

use const PHP_EOL;
use const STDERR;

/**
 * Line-based progress output.
 *
 * In quiet mode (CI, or a stderr that isn't a terminal) every phase prints the same row as the interactive table
 * (see formatRow()) when it ends, and a status line every 30 seconds meanwhile, so that a long run doesn't look stuck.
 * Otherwise (--long-progress) it prints a grid with a marker per task.
 *
 * @api
 */
class LongProgress extends Progress
{
    final public const NUMBER_OF_COLUMNS = 60;

    /** Seconds between status lines in quiet mode */
    private const STATUS_INTERVAL = 30.0;

    protected ?int $number_of_tasks = null;

    protected int $progress = 0;

    protected bool $fixed_size = false;

    /**
     * True when the current phase runs an unknown number of tasks (e.g. taint
     * graph resolution, which loops to a fixed point). A percentage is
     * meaningless in that case.
     */
    protected bool $indeterminate = false;

    protected ?Phase $phase = null;

    protected int $threads = 1;

    protected float $started = 0.0;

    private float $last_status = 0.0;

    private ?bool $color_enabled = null;

    /** Whether the grid left the cursor in the middle of a line */
    private bool $mid_line = false;

    /** Whether lines were written since the last finish(): the output that follows is then set apart */
    private bool $wrote_lines = false;

    /** Whether the last lines written were a blank one: finish() doesn't add another */
    private bool $ends_with_blank_line = false;

    /** @var list<string>|null Output of a forked worker, kept for the main process */
    private ?array $worker_output = null;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        protected bool $print_errors = true,
        protected bool $print_infos = true,
        protected bool $in_ci = false,
        protected bool $use_color = false,
    ) {
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function debug(string $message): void
    {
    }

    #[Override]
    public function startPhase(Phase $phase, int $threads = 1): void
    {
        if ($phase === $this->phase) {
            return;
        }

        $this->endPhase();

        $this->phase = $phase;
        // shown only if the phase forks: see setThreads()
        $this->threads = 1;
        $this->progress = 0;
        $this->number_of_tasks = 0;
        $this->started = $this->last_status = microtime(true);
        $this->fixed_size = $phase !== Phase::SCAN && $phase !== Phase::TAINT_GRAPH_RESOLUTION;
        $this->indeterminate = $phase === Phase::TAINT_GRAPH_RESOLUTION;

        if (!self::isSilent($phase)) {
            $this->phaseStarted();
        }
    }

    #[Override]
    public function alterFileDone(string $file_name): void
    {
        // in quiet mode, the Alter row and the summary say how many files changed: a line per file would flood the log
        if (!$this->in_ci) {
            $this->writeLine('Altered ' . $file_name);
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function expand(int $number_of_tasks): void
    {
        $this->number_of_tasks += $number_of_tasks;
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function setThreads(int $threads): void
    {
        $this->threads = max($this->threads, $threads);
    }

    #[Override]
    public function taskDone(int $level): void
    {
        if ($this->number_of_tasks === null) {
            throw new LogicException('Progress::startPhase() should be called before Progress::taskDone()');
        }

        ++$this->progress;

        if ($this->phase !== null && !self::isSilent($this->phase)) {
            $this->reportTask($level);
        }
    }

    #[Override]
    public function finish(): void
    {
        $this->endPhase();

        if ($this->wrote_lines && !$this->ends_with_blank_line) {
            $this->write(PHP_EOL);
        }

        $this->wrote_lines = false;
        $this->ends_with_blank_line = false;
    }

    #[Override]
    public function relayWorkerOutput(string $output): void
    {
        parent::relayWorkerOutput($output);

        if ($output !== '') {
            $this->wrote_lines = true;
            $this->ends_with_blank_line = true;
        }
    }

    /**
     * In a forked worker, output is kept for the main process (see takeWorkerOutput()):
     * the worker would otherwise write over the status line the main process draws.
     */
    #[Override]
    public function write(string $message): void
    {
        if ($this->worker_output !== null) {
            $this->worker_output[] = $message;
            return;
        }

        parent::write($message);
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function startBufferingWorkerOutput(): void
    {
        $this->worker_output ??= [];
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function takeWorkerOutput(): string
    {
        if ($this->worker_output === null) {
            return '';
        }

        $output = implode('', $this->worker_output);
        $this->worker_output = [];

        return $output;
    }

    /**
     * @psalm-mutation-free
     */
    protected function isBufferingWorkerOutput(): bool
    {
        return $this->worker_output !== null;
    }

    protected function getPhaseDuration(): float
    {
        return microtime(true) - $this->started;
    }

    protected function phaseStarted(): void
    {
        // the grid needs a heading; in quiet mode, the row written when the phase ends says it all
        if (!$this->in_ci) {
            $this->writeLine($this->getLabel() . '...');
        }
    }

    protected function reportTask(int $level): void
    {
        if ($this->in_ci) {
            $now = microtime(true);
            if ($now - $this->last_status >= self::STATUS_INTERVAL && $this->phase !== null) {
                $this->last_status = $now;
                // e.g. "  Analysis: 4,320 / 8,629 files · 50% · 30s"
                $this->writeLine('  ' . self::getPhaseName($this->phase) . ': ' . $this->getStatus());
            }

            return;
        }

        if ($this->indeterminate) {
            $this->writeTick(self::doesTerminalSupportUtf8() ? '░' : '_');
            if (($this->progress % self::NUMBER_OF_COLUMNS) === 0) {
                $this->endGridLine('');
            }

            return;
        }

        if (!$this->fixed_size) {
            if ($this->progress === 1 || $this->progress === $this->number_of_tasks || $this->progress % 10 === 0) {
                $this->writeTick(sprintf("\r%s / %s...", $this->progress, (int) $this->number_of_tasks));
            }

            return;
        }

        if ($level === 0 || ($level === 1 && !$this->print_infos) || !$this->print_errors) {
            $this->writeTick(self::doesTerminalSupportUtf8() ? '░' : '_');
        } elseif ($level === 1) {
            $this->writeTick('I');
        } else {
            $this->writeTick('E');
        }

        if (($this->progress % self::NUMBER_OF_COLUMNS) !== 0) {
            if ($this->progress !== $this->number_of_tasks) {
                return;
            }
            if ($this->number_of_tasks > self::NUMBER_OF_COLUMNS) {
                $this->write(str_repeat(' ', self::NUMBER_OF_COLUMNS - ($this->progress % self::NUMBER_OF_COLUMNS)));
            }
        }

        $this->endGridLine($this->getOverview());
    }

    protected function phaseEnded(Phase $phase): void
    {
        $row = $this->formatRow($phase);
        if ($row !== null) {
            $this->writeLine($row);
        }
    }

    /**
     * The row a phase ends with, e.g. "✓ Analysis      8,629 files   21.3s  16 threads", or null for a phase
     * that isn't worth reporting
     */
    protected function formatRow(Phase $phase): ?string
    {
        $duration = $this->getPhaseDuration();
        if (!self::isWorthReporting($phase, $duration)) {
            return null;
        }

        // the number of files altered is in the summary: the phase visits every file, and most are left as they were
        $tasks = $phase === Phase::SCAN || $phase === Phase::ANALYSIS
            ? number_format($this->progress) . ($this->progress === 1 ? ' file' : ' files')
            : '';

        // the time and threads are shown dim, so that the eye goes to what was done
        $timing = sprintf('%8s', number_format($duration, 1) . 's')
            . ($this->threads > 1 ? "  {$this->threads} threads" : '');

        return sprintf(
            '%s %-12s %14s %s',
            self::doesTerminalSupportUtf8() ? '✓' : '*',
            self::getPhaseName($phase),
            $tasks,
            $this->shouldUseColor() ? "\e[2m{$timing}\e[22m" : $timing,
        );
    }

    /**
     * @psalm-pure
     */
    protected static function getPhaseName(Phase $phase): string
    {
        return match ($phase) {
            Phase::SCAN => 'Scan',
            Phase::ANALYSIS => 'Analysis',
            Phase::ALTERING => 'Alter',
            Phase::TAINT_GRAPH_RESOLUTION => 'Taint graph',
            Phase::MERGING_THREAD_RESULTS => 'Merge',
            Phase::LOADING_CACHE => 'Cache',
            Phase::FINISHING => 'Finishing',
            Phase::JIT_COMPILATION, Phase::PRELOADING => 'Preload',
        };
    }

    /**
     * What the current phase is doing, e.g. "Analyzing files · 16 threads"
     */
    protected function getLabel(bool $with_threads = true): string
    {
        $label = match ($this->phase) {
            Phase::SCAN => 'Scanning files',
            Phase::ANALYSIS => 'Analyzing files',
            Phase::ALTERING => 'Altering files',
            Phase::TAINT_GRAPH_RESOLUTION => 'Resolving taint graph',
            Phase::JIT_COMPILATION, Phase::PRELOADING => 'Preloading',
            Phase::MERGING_THREAD_RESULTS => 'Merging thread results',
            Phase::LOADING_CACHE => 'Loading cached results',
            Phase::FINISHING => 'Finishing',
            null => '',
        };

        return $with_threads ? $label . $this->getThreadsSuffix() : $label;
    }

    /**
     * How far the current phase got, e.g. "4,320 / 8,629 files · 50% · 12s"
     */
    protected function getStatus(): string
    {
        $elapsed = (int) (microtime(true) - $this->started) . 's';
        $separator = self::separator();

        if ($this->indeterminate) {
            return ($this->progress > 0 ? 'pass ' . $this->progress . $separator : '') . $elapsed;
        }

        if ($this->number_of_tasks === null || $this->number_of_tasks === 0) {
            return $elapsed;
        }

        $status = number_format($this->progress) . ' / ' . number_format($this->number_of_tasks)
            . ($this->phase === Phase::MERGING_THREAD_RESULTS ? ' threads' : ' files');

        if ($this->fixed_size) {
            $status .= $separator . intdiv($this->progress * 100, $this->number_of_tasks) . '%';
        }

        return $status . $separator . $elapsed;
    }

    /**
     * @psalm-mutation-free
     */
    protected function getOverview(): string
    {
        if ($this->number_of_tasks === null) {
            throw new LogicException('Progress::startPhase() should be called before Progress::getOverview()');
        }

        $leadingSpaces = 1 + strlen((string) $this->number_of_tasks) - strlen((string) $this->progress);
        // Don't show 100% unless this is the last line of the progress bar.
        $percentage = $this->number_of_tasks > 0 ? intdiv($this->progress * 100, $this->number_of_tasks) : 0;

        return sprintf(
            '%s%s / %s (%s%%)',
            str_repeat(' ', $leadingSpaces),
            $this->progress,
            $this->number_of_tasks,
            $percentage,
        );
    }

    /**
     * Writes a full line, ending a line the grid left open first.
     */
    protected function writeLine(string $line): void
    {
        if ($this->mid_line) {
            $this->mid_line = false;
            $this->write(PHP_EOL);
        }

        $this->write($line . PHP_EOL);
        $this->wrote_lines = true;
        $this->ends_with_blank_line = false;
    }

    /**
     * Ends the line the grid left open with $suffix, rather than starting a new line for it as writeLine() does.
     */
    private function endGridLine(string $suffix): void
    {
        $this->mid_line = false;
        $this->write($suffix . PHP_EOL);
        $this->wrote_lines = true;
        $this->ends_with_blank_line = false;
    }

    /**
     * Preloading takes a fraction of a second and only concerns Psalm itself, so it isn't reported.
     *
     * @psalm-pure
     */
    protected static function isSilent(Phase $phase): bool
    {
        return $phase === Phase::PRELOADING || $phase === Phase::JIT_COMPILATION;
    }

    /**
     * Some phases are usually quick; they're only worth reporting when they aren't.
     *
     * @psalm-pure
     */
    protected static function isWorthReporting(Phase $phase, float $duration): bool
    {
        return $duration >= 1.0 || !in_array(
            $phase,
            [Phase::TAINT_GRAPH_RESOLUTION, Phase::MERGING_THREAD_RESULTS, Phase::LOADING_CACHE, Phase::FINISHING],
            true,
        );
    }

    private function endPhase(): void
    {
        if ($this->phase === null) {
            return;
        }

        if (!self::isSilent($this->phase)) {
            $this->phaseEnded($this->phase);
        }

        $this->phase = null;
    }

    private function getThreadsSuffix(): string
    {
        return $this->threads > 1 ? self::separator() . "{$this->threads} threads" : '';
    }

    /**
     * Colors only go to a terminal that shows them: not into a log file or a pipe, nor to TERM=dumb
     */
    protected function shouldUseColor(): bool
    {
        return $this->color_enabled ??= $this->use_color && stream_isatty(STDERR) && !self::isDumbTerminal();
    }

    private function writeTick(string $tick): void
    {
        $this->mid_line = true;
        $this->write($tick);
    }
}
