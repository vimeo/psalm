<?php

declare(strict_types=1);

namespace Psalm\Progress;

use Override;
use Psalm\Internal\ErrorHandler;

use function ctype_digit;
use function exec;
use function function_exists;
use function getenv;
use function hrtime;
use function is_callable;
use function is_int;
use function is_string;
use function max;
use function mb_strlen;
use function mb_substr;
use function pcntl_alarm;
use function pcntl_async_signals;
use function pcntl_signal;
use function pcntl_signal_get_handler;
use function preg_match;
use function sapi_windows_vt100_support;
use function str_ends_with;
use function str_repeat;
use function stream_isatty;
use function stripos;

use const PHP_OS;
use const SIGALRM;
use const SIG_DFL;
use const STDERR;

/**
 * Interactive progress: one status line redrawn in place while a phase runs,
 * replaced by a row of the phase table when the phase ends.
 *
 * The status line is redrawn when tasks complete, and once a second by a SIGALRM
 * ticker where pcntl is available, so the elapsed time keeps moving even while
 * Psalm works on something that doesn't report progress.
 *
 * @api
 */
class DefaultProgress extends LongProgress
{
    private const BAR_WIDTH = 20;

    // Redraw the status line at most once per 0.1 seconds.
    // This reduces flickering and the time spent writing to STDERR.
    private const REFRESH_INTERVAL_NANOSECONDS = 100_000_000;

    // The alarm fires every second; skip the redraw only if a task just redrew the line.
    private const TICK_INTERVAL_NANOSECONDS = 900_000_000;

    private int $last_refresh = 0;

    /** Visible width of the status line currently on screen, 0 if none */
    private int $status_width = 0;

    private bool $drawing_status = false;

    private ?bool $supports_ansi = null;

    private ?int $columns = null;

    /** Non-zero while output is being written, so that the ticker doesn't interleave with it */
    private int $busy = 0;

    private bool $ticker_armed = false;

    /** @var int|callable */
    private mixed $previous_alarm_handler = SIG_DFL;

    private bool $previous_async_signals = false;

    /**
     * Other messages (e.g. warnings) are written above the status line: it's cleared first,
     * and drawn again below the message.
     */
    #[Override]
    public function write(string $message): void
    {
        if ($this->isBufferingWorkerOutput()) {
            parent::write($message);
            return;
        }

        ++$this->busy;
        try {
            if ($this->drawing_status) {
                parent::write($message);
                return;
            }

            $status_was_drawn = $this->status_width > 0;
            $this->clearStatus();

            parent::write($message);

            if ($status_was_drawn && str_ends_with($message, "\n")) {
                $this->drawStatus();
            }
        } finally {
            --$this->busy;
        }
    }

    #[Override]
    public function writeReport(string $message): void
    {
        if ($this->isBufferingWorkerOutput()) {
            parent::writeReport($message);
            return;
        }

        ++$this->busy;
        try {
            $status_was_drawn = $this->status_width > 0;
            $this->clearStatus();

            parent::writeReport($message);

            if ($status_was_drawn && str_ends_with($message, "\n")) {
                $this->drawStatus();
            }
        } finally {
            --$this->busy;
        }
    }

    #[Override]
    public function finish(): void
    {
        ErrorHandler::setBeforeFatalError(null);
        parent::finish();
        $this->disarmTicker();
    }

    /**
     * The Alter row and the summary say how many files changed: a line per file would push the table off screen
     *
     * @psalm-mutation-free
     */
    #[Override]
    public function alterFileDone(string $file_name): void
    {
    }

    #[Override]
    protected function phaseStarted(): void
    {
        $this->last_refresh = hrtime(true);
        $this->drawStatus();
        $this->armTicker();
        ErrorHandler::setBeforeFatalError($this->prepareForFatalError(...));
    }

    #[Override]
    protected function reportTask(int $level): void
    {
        $now = hrtime(true);
        if ($now - $this->last_refresh < self::REFRESH_INTERVAL_NANOSECONDS) {
            return;
        }

        $this->last_refresh = $now;
        $this->drawStatus();
    }

    #[Override]
    protected function phaseEnded(Phase $phase): void
    {
        $this->clearStatus();

        parent::phaseEnded($phase);
    }

    private function drawStatus(): void
    {
        ++$this->busy;
        try {
            $line = $this->composeStatusLine();
            $width = mb_strlen($line);

            $this->drawing_status = true;
            $this->write("\r" . $line . $this->eraseRestOfLine($this->status_width - $width));
            $this->drawing_status = false;

            $this->status_width = $width;
        } finally {
            --$this->busy;
        }
    }

    /**
     * The status line, e.g. "Analyzing files · 16 threads ████████░░░░░░░░░░░░ 4,320 / 8,629 files · 50% · 12s".
     *
     * A line wider than the terminal wraps, and the redraw then leaves the wrapped rows behind. It's kept one column
     * short of the width (a line that fills it makes some terminals wrap early), by dropping the bar, then the
     * thread count, and finally truncating.
     */
    private function composeStatusLine(): string
    {
        $max_width = max(1, $this->getColumns() - 1);
        $status = $this->getStatus();
        $label = $this->getLabel();

        $bar = $this->fixed_size && $this->number_of_tasks > 0
            ? self::renderInnerProgressBar(self::BAR_WIDTH, $this->progress / $this->number_of_tasks)
            : null;

        $candidates = [
            $bar !== null ? $label . ' ' . $bar . ' ' . $status : $label . self::separator() . $status,
            $label . self::separator() . $status,
            $this->getLabel(false) . self::separator() . $status,
        ];

        foreach ($candidates as $line) {
            if (mb_strlen($line) <= $max_width) {
                return $line;
            }
        }

        return mb_substr($candidates[2], 0, $max_width);
    }

    /**
     * The width of the terminal, read once: COLUMNS, else asked of the terminal, else 80
     */
    private function getColumns(): int
    {
        return $this->columns ??= self::detectColumns();
    }

    private static function detectColumns(): int
    {
        $columns = getenv('COLUMNS');
        if (is_string($columns) && ctype_digit($columns) && (int) $columns > 0) {
            return (int) $columns;
        }

        if (function_exists('exec') && stripos(PHP_OS, 'WIN') !== 0 && stream_isatty(STDERR)) {
            // both commands ask the terminal on their stdin: hand them the one STDERR is
            foreach (['stty size <&2 2>/dev/null', 'tput cols <&2 2>/dev/null'] as $command) {
                // "40 120" for stty (rows, columns), "120" for tput
                preg_match('/(\d+)\s*$/', (string) exec($command), $matches);
                $detected = (int) ($matches[1] ?? 0);

                if ($detected > 0) {
                    return $detected;
                }
            }
        }

        return 80;
    }

    private function clearStatus(): void
    {
        if ($this->status_width === 0) {
            return;
        }

        ++$this->busy;
        try {
            $width = $this->status_width;
            $this->status_width = 0;

            $this->drawing_status = true;
            $this->write("\r" . $this->eraseRestOfLine($width) . "\r");
            $this->drawing_status = false;
        } finally {
            --$this->busy;
        }
    }

    /**
     * An uncaught exception is about to be written to STDERR: it would land on the status line
     * (e.g. "Loading cached results 0sUncaught ..."), and the ticker would draw the line again below it.
     */
    private function prepareForFatalError(): void
    {
        $this->disarmTicker();
        $this->clearStatus();
    }

    /**
     * Erases the rest of the line, so that copying the terminal output doesn't copy trailing spaces.
     * Writes $width spaces instead where escape sequences aren't understood (TERM=dumb, Windows terminals without
     * ANSI support).
     */
    private function eraseRestOfLine(int $width): string
    {
        $this->supports_ansi ??= stripos(PHP_OS, 'WIN') === 0
            ? function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(STDERR, true)
            : !self::isDumbTerminal();

        return $this->supports_ansi ? "\e[K" : str_repeat(' ', max(0, $width));
    }

    /**
     * Psalm can spend many seconds without completing a task (e.g. populating the codebase
     * after the scan). A once-a-second alarm keeps the elapsed time moving meanwhile.
     *
     * The alarm is armed only after Psalm has restarted itself (it would survive exec),
     * and forked workers don't inherit it.
     */
    private function armTicker(): void
    {
        if ($this->ticker_armed
            || !function_exists('pcntl_alarm')
            || !function_exists('pcntl_async_signals')
            || !function_exists('pcntl_signal_get_handler')
        ) {
            return;
        }

        $this->ticker_armed = true;
        $previous_handler = pcntl_signal_get_handler(SIGALRM);
        $this->previous_alarm_handler = is_int($previous_handler) || is_callable($previous_handler)
            ? $previous_handler
            : SIG_DFL;
        $this->previous_async_signals = pcntl_async_signals(true);
        pcntl_signal(SIGALRM, $this->tick(...));
        pcntl_alarm(1);
    }

    private function disarmTicker(): void
    {
        if (!$this->ticker_armed) {
            return;
        }

        $this->ticker_armed = false;
        pcntl_alarm(0);
        /** @psalm-suppress MixedArgumentTypeCoercion it was registered as a signal handler before */
        pcntl_signal(SIGALRM, $this->previous_alarm_handler);
        pcntl_async_signals($this->previous_async_signals);
    }

    private function tick(): void
    {
        if (!$this->ticker_armed) {
            return;
        }

        pcntl_alarm(1);

        if ($this->busy > 0 || $this->phase === null || self::isSilent($this->phase)) {
            return;
        }

        $now = hrtime(true);
        if ($now - $this->last_refresh < self::TICK_INTERVAL_NANOSECONDS) {
            return;
        }

        $this->last_refresh = $now;
        $this->drawStatus();
    }

    /**
     * Fully stolen from
     * https://github.com/phan/phan/blob/d61a624b1384ea220f39927d53fd656a65a75fac/src/Phan/CLI.php
     * Renders a unicode progress bar that goes from light (left) to dark (right)
     * The length in the console is the positive integer $length
     *
     * @see https://en.wikipedia.org/wiki/Block_Elements
     */
    private static function renderInnerProgressBar(int $length, float $p): string
    {
        $current_float = $p * (float) $length;
        $current = (int)$current_float;
        $rest = max($length - $current, 0);

        if (!self::doesTerminalSupportUtf8()) {
            // Show a progress bar of "XXXX>......" in Windows when utf-8 is unsupported.
            // (not "-": it would look like the " - " that separates the facts on the line)
            $progress_bar = str_repeat('X', $current);
            $delta = $current_float - (float) $current;
            if ($delta > 0.5) {
                $progress_bar .= '>' . str_repeat('.', $rest - 1);
            } else {
                $progress_bar .= str_repeat('.', $rest);
            }

            return $progress_bar;
        }

        // The left-most characters are "Light shade"
        $progress_bar = str_repeat("\u{2588}", $current);
        $delta = $current_float - (float) $current;
        if ($delta > 0.75) {
            $progress_bar .= "\u{258A}" . str_repeat("\u{2591}", $rest - 1);
        } elseif ($delta > 0.5) {
            $progress_bar .= "\u{258C}" . str_repeat("\u{2591}", $rest - 1);
        } elseif ($delta > 0.25) {
            $progress_bar .= "\u{258E}" . str_repeat("\u{2591}", $rest - 1);
        } else {
            $progress_bar .= str_repeat("\u{2591}", $rest);
        }

        return $progress_bar;
    }
}
