<?php

declare(strict_types=1);

namespace Psalm\Progress;

use function error_reporting;
use function function_exists;
use function fwrite;
use function getenv;
use function is_string;
use function preg_match;
use function rtrim;
use function sapi_windows_cp_is_utf8;
use function stripos;

use const E_ERROR;
use const PHP_EOL;
use const PHP_OS;
use const STDERR;
use const STDOUT;

/**
 * @api
 */
abstract class Progress
{
    public function setErrorReporting(): void
    {
        error_reporting(E_ERROR);
    }

    abstract public function debug(string $message): void;


    /**
     * @param int $threads How many processes may work on the phase. What a phase shows is what really ran:
     *                     see setThreads()
     */
    abstract public function startPhase(Phase $phase, int $threads = 1): void;

    /**
     * Tells how many processes work on the current phase, once it is known that it forks (a phase that
     * doesn't fork has no thread count to show). Called again when a later pass of the phase forks more.
     */
    public function setThreads(int $threads): void
    {
    }

    abstract public function expand(int $number_of_tasks): void;

    abstract public function taskDone(int $level): void;

    abstract public function finish(): void;


    abstract public function alterFileDone(string $file_name): void;

    /**
     * Writes a message to the user. Psalm and plugins should write to the terminal through this method
     * (or warning()) rather than to STDERR directly, so the message doesn't get mixed with the progress output.
     */
    public function write(string $message): void
    {
        self::writeTo(STDERR, $message);
    }

    /**
     * Warns the user about something not related to a location in the code (e.g. a missing extension, a plugin
     * misconfiguration). Problems in the analyzed code should be reported as issues instead.
     */
    public function warning(string $message): void
    {
        $this->write('Warning: ' . $message . PHP_EOL);
    }

    /**
     * Writes output that is part of the result rather than a message (e.g. the --alter --dry-run diff) to STDOUT,
     * so that it doesn't get mixed with the progress output either.
     */
    public function writeReport(string $message): void
    {
        self::writeTo(STDOUT, $message);
    }

    /**
     * Writes the output a forked worker kept (see takeWorkerOutput()), set apart from the rows around it
     *
     * @internal
     */
    public function relayWorkerOutput(string $output): void
    {
        if ($output === '') {
            return;
        }

        $this->write(PHP_EOL . rtrim($output, "\r\n") . PHP_EOL . PHP_EOL);
    }

    /**
     * Called in a forked worker: from then on, the progress may keep what is written instead of writing it, for
     * the main process to write it (see takeWorkerOutput())
     *
     * @internal
     */
    public function startBufferingWorkerOutput(): void
    {
    }

    /**
     * Returns what was kept since startBufferingWorkerOutput() was called, and forgets it
     *
     * @internal
     */
    public function takeWorkerOutput(): string
    {
        return '';
    }

    /**
     * Separates the facts on one line (e.g. "33 errors in 3 files · 3 info hidden")
     */
    final public static function separator(): string
    {
        return self::doesTerminalSupportUtf8() ? ' · ' : ' - ';
    }

    /**
     * Whether non-ASCII characters (✓, ·, the progress bar) can be printed: not with a Windows code page other than
     * UTF-8, nor with a locale that isn't UTF-8 (e.g. LANG=C, where they'd show as "?" or mojibake in logs)
     */
    final protected static function doesTerminalSupportUtf8(): bool
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            return function_exists('sapi_windows_cp_is_utf8') && sapi_windows_cp_is_utf8();
        }

        // the first locale variable set wins, as for setlocale(); an unset locale is assumed to be UTF-8
        foreach (['LC_ALL', 'LC_CTYPE', 'LANG'] as $variable) {
            $locale = getenv($variable);
            if (is_string($locale) && $locale !== '') {
                return preg_match('/utf-?8/i', $locale) === 1;
            }
        }

        return true;
    }

    /**
     * Whether the terminal ignores escape sequences (TERM=dumb, as in Emacs' shell buffer): neither colors nor
     * cursor control can be used
     */
    final protected static function isDumbTerminal(): bool
    {
        return getenv('TERM') === 'dumb';
    }

    /**
     * A reader that went away (e.g. `psalm | head`) isn't an error: the output is dropped, as echo does,
     * rather than failing with a "Broken pipe" warning that the error handler turns into an exception.
     *
     * @param resource $stream
     */
    private static function writeTo($stream, string $message): void
    {
        @fwrite($stream, $message);
    }
}
