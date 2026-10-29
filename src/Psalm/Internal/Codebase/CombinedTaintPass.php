<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Closure;
use Psalm\Config;
use Psalm\Exception\CombinedTaintPassException;
use Psalm\Internal\Analyzer\DataFlowNodeData;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Throwable;

use function bin2hex;
use function explode;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function fopen;
use function function_exists;
use function fwrite;
use function getenv;
use function is_array;
use function is_file;
use function is_writable;
use function pcntl_async_signals;
use function pcntl_fork;
use function pcntl_get_last_error;
use function pcntl_signal;
use function pcntl_signal_get_handler;
use function pcntl_sigprocmask;
use function pcntl_strerror;
use function pcntl_waitpid;
use function pcntl_wexitstatus;
use function pcntl_wifexited;
use function pcntl_wifsignaled;
use function pcntl_wtermsig;
use function posix_getpid;
use function posix_getppid;
use function posix_kill;
use function posix_setpgid;
use function random_bytes;
use function register_shutdown_function;
use function rtrim;
use function serialize;
use function sleep;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strpos;
use function strrpos;
use function substr;
use function sys_get_temp_dir;
use function umask;
use function unlink;
use function unserialize;

use const DIRECTORY_SEPARATOR;
use const PCNTL_EINTR;
use const PHP_EOL;
use const SIGABRT;
use const SIGHUP;
use const SIGINT;
use const SIGKILL;
use const SIGTERM;
use const SIGUSR2;
use const SIG_BLOCK;
use const SIG_DFL;
use const SIG_IGN;
use const SIG_SETMASK;
use const STDERR;
use const STDIN;
use const STDOUT;

/**
 * Runs taint analysis in a forked copy of the fully populated codebase, so that a
 * normal analysis and a taint analysis can share one scan and one report.
 *
 * The child process gets the state a `--taint-analysis` run has once scanning is
 * done, including the taint graph that plugins and scan workers filled in while the
 * codebase was scanned and populated. It analyzes every file, connects sources and
 * sinks, and writes only its Tainted* issues to a result file. The parent has no taint
 * graph, so its own analysis is the same as a normal run.
 *
 * The parent waits for the child, so only one of them analyzes at a time. They share
 * the scanned codebase copy-on-write: pages the child's analysis does not touch are
 * resident once, not twice.
 *
 * @internal
 */
final class CombinedTaintPass
{
    private const SIGNALS = [SIGINT, SIGTERM, SIGHUP];

    private const PAYLOAD_MAGIC = 'psalm-combined-taint-pass-v1:';

    /** The signal the child ends itself with once it has written its results. */
    private const DONE_SIGNAL = SIGUSR2;

    /**
     * Only for Psalm's own tests: "crash" or "garbage" make the taint pass fail, "hang"
     * makes it wait up to a minute, stopping as soon as the parent is gone, and "stall" makes
     * it wait without checking, as one slow file or connectSinksAndSources() would.
     */
    public const FAILURE_ENV = 'PSALM_INTERNAL_TEST_COMBINED_TAINT_PASS_FAILURE';

    private const REQUIRED_FUNCTIONS = [
        'pcntl_async_signals',
        'pcntl_fork',
        'pcntl_get_last_error',
        'pcntl_signal',
        'pcntl_signal_get_handler',
        'pcntl_sigprocmask',
        'pcntl_strerror',
        'pcntl_waitpid',
        'pcntl_wexitstatus',
        'pcntl_wifexited',
        'pcntl_wifsignaled',
        'pcntl_wtermsig',
        'posix_getpid',
        'posix_getppid',
        'posix_kill',
        'posix_setpgid',
    ];

    /**
     * Returns null when combined taint analysis can run, or the reason it can't.
     */
    public static function getUnsupportedReason(): ?string
    {
        foreach (self::REQUIRED_FUNCTIONS as $function) {
            if (!function_exists($function)) {
                return 'Combined taint analysis needs the pcntl and posix extensions, and ' . $function
                    . '() is not available (is it in disable_functions?)';
            }
        }

        // The taint pass hands its results back in a file there, after the whole scan.
        if (!is_writable(sys_get_temp_dir())) {
            return 'Combined taint analysis writes its results to the temporary directory, and '
                . sys_get_temp_dir() . ' is not writable';
        }

        return null;
    }

    /**
     * @param Closure(Closure():void):void $run_taint_analysis runs the taint analysis in the child
     *     process; it gets a closure to call between files, which stops the child once the parent is gone
     * @return array{
     *     issues: array<string, list<IssueData>>,
     *     issue_handler_suppressions: list<array{type: string, index: int, count: int}>
     * }
     * @throws CombinedTaintPassException
     */
    public static function run(Closure $run_taint_analysis): array
    {
        $unsupported_reason = self::getUnsupportedReason();
        if ($unsupported_reason !== null) {
            throw new CombinedTaintPassException($unsupported_reason);
        }

        $parent_pid = posix_getpid();
        $result_file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . 'psalm-taint-pass-' . $parent_pid . '-' . bin2hex(random_bytes(6));

        // Shared with the signal handlers and the shutdown function, which may run at any point.
        $child = new class {
            public ?int $pid = null;
        };
        $cleanup = static function () use ($child, $result_file, $parent_pid): void {
            // Forked processes inherit this shutdown function; only the parent cleans up.
            if (posix_getpid() !== $parent_pid) {
                return;
            }
            if ($child->pid !== null) {
                // The child leads its own process group, so this also stops its pool workers.
                if (!posix_kill(-$child->pid, SIGKILL)) {
                    posix_kill($child->pid, SIGKILL);
                }
                pcntl_waitpid($child->pid, $_status);
                $child->pid = null;
            }
            if (is_file($result_file)) {
                @unlink($result_file);
            }
        };

        // Covers exit() and fatal errors in the parent while the child runs.
        register_shutdown_function($cleanup);

        $previous_async_signals = pcntl_async_signals(true);
        $previous_handlers = [];
        foreach (self::SIGNALS as $signal) {
            $previous_handler = pcntl_signal_get_handler($signal);
            if ($previous_handler === SIG_IGN) {
                // Ignored by PHP code earlier in the run (a plugin, say), so it wouldn't stop it.
                // An ignore inherited from the shell (nohup) never gets here: PHP's startup resets
                // it, so pcntl_signal_get_handler() reads SIG_DFL and the run stops either way.
                continue;
            }
            $previous_handlers[$signal] = $previous_handler;
            // No restarting system calls, so the signal interrupts pcntl_waitpid() below.
            pcntl_signal($signal, static function (int $signal) use ($cleanup): void {
                $cleanup();
                pcntl_signal($signal, SIG_DFL);
                posix_kill(posix_getpid(), $signal);
            }, false);
        }

        // A signal between the fork and recording the child's pid below would leave
        // the child running, so hold them back until the cleanup knows about it.
        pcntl_sigprocmask(SIG_BLOCK, self::SIGNALS, $previous_mask);
        $pid = pcntl_fork();
        if ($pid < 0) {
            $error = pcntl_strerror(pcntl_get_last_error());
            $_restored = pcntl_sigprocmask(SIG_SETMASK, $previous_mask);
            self::restoreSignals($previous_handlers, $previous_async_signals);
            $cleanup();
            throw new CombinedTaintPassException('Could not fork the taint analysis pass: ' . $error);
        }

        // The child leads its own process group, so one kill stops it and the pool workers it
        // forks later. Both sides set it, so the group exists whichever runs first.
        $_grouped = @posix_setpgid($pid === 0 ? 0 : $pid, $pid === 0 ? 0 : $pid);

        if ($pid === 0) {
            foreach (self::SIGNALS as $signal) {
                pcntl_signal($signal, SIG_DFL);
            }
            $_restored = pcntl_sigprocmask(SIG_SETMASK, $previous_mask);
            self::runChild($parent_pid, $result_file, $run_taint_analysis);
        }

        $child->pid = $pid;
        $_restored = pcntl_sigprocmask(SIG_SETMASK, $previous_mask);

        do {
            $waited = pcntl_waitpid($pid, $status);
        } while ($waited === -1 && pcntl_get_last_error() === PCNTL_EINTR);

        $wait_error = $waited === -1 ? pcntl_strerror(pcntl_get_last_error()) : null;
        if ($waited === $pid) {
            $child->pid = null;
        }

        $payload = @file_get_contents($result_file);
        $cleanup();
        self::restoreSignals($previous_handlers, $previous_async_signals);

        if ($wait_error !== null) {
            throw new CombinedTaintPassException('Could not wait for the taint analysis pass: ' . $wait_error);
        }

        $result = self::decodePayload($payload === false ? '' : $payload);

        if ($result === null) {
            throw new CombinedTaintPassException(
                'The taint analysis pass ' . self::describeExit($status) . ' without sending complete results',
            );
        }

        return $result;
    }

    /**
     * @param array<int, mixed> $previous_handlers what pcntl_signal_get_handler() returned
     * @psalm-suppress MixedAssignment, MixedArgument Psalm's stub types that as int|string, but it
     *     returns whatever handler was set, closures included, which pcntl_signal() takes back
     */
    private static function restoreSignals(array $previous_handlers, bool $previous_async_signals): void
    {
        foreach ($previous_handlers as $signal => $handler) {
            pcntl_signal($signal, $handler);
        }
        pcntl_async_signals($previous_async_signals);
    }

    private static function describeExit(int $status): string
    {
        if (pcntl_wifsignaled($status)) {
            $signal = pcntl_wtermsig($status);
            if ($signal === self::DONE_SIGNAL) {
                return 'finished';
            }
            if ($signal === SIGKILL) {
                return 'was killed by signal 9 (likely by the OOM killer: add swap or try arrayCache="false")';
            }
            if ($signal === 11) {
                return 'crashed with signal 11 (a PHP bug: please report it to https://github.com/vimeo/psalm/issues'
                    . ' and https://github.com/php/php-src/issues)';
            }
            return 'was killed by signal ' . $signal;
        }

        if (pcntl_wifexited($status)) {
            $code = pcntl_wexitstatus($status);
            return $code === 255
                ? 'exited with code 255 (a PHP fatal error, e.g. memory_limit; see the output above)'
                : 'exited with code ' . $code;
        }

        return sprintf('stopped (wait status %d)', $status);
    }

    /**
     * @return array{
     *     issues: array<string, list<IssueData>>,
     *     issue_handler_suppressions: list<array{type: string, index: int, count: int}>
     * }|null
     * @throws CombinedTaintPassException when the child reports that it failed
     */
    private static function decodePayload(string $payload): ?array
    {
        if (!str_starts_with($payload, self::PAYLOAD_MAGIC)) {
            return null;
        }

        $newline = strpos($payload, "\n");
        if ($newline === false) {
            return null;
        }

        $length = (int) substr($payload, strlen(self::PAYLOAD_MAGIC), $newline - strlen(self::PAYLOAD_MAGIC));
        $body = substr($payload, $newline + 1);
        if ($length <= 0 || strlen($body) !== $length) {
            return null;
        }

        try {
            /** @var mixed $result */
            $result = @unserialize($body, ['allowed_classes' => [IssueData::class, DataFlowNodeData::class]]);
        } catch (Throwable) {
            return null;
        }

        if (!is_array($result)) {
            return null;
        }

        if (isset($result['error'])) {
            throw new CombinedTaintPassException('The taint analysis pass failed: ' . (string) $result['error']);
        }

        if (!isset($result['issues'], $result['issue_handler_suppressions'])
            || !is_array($result['issues'])
            || !is_array($result['issue_handler_suppressions'])
        ) {
            return null;
        }

        /**
         * @var array{
         *     issues: array<string, list<IssueData>>,
         *     issue_handler_suppressions: list<array{type: string, index: int, count: int}>
         * } $result
         */
        return $result;
    }

    /**
     * @param Closure(Closure():void):void $run_taint_analysis
     */
    private static function runChild(int $parent_pid, string $result_file, Closure $run_taint_analysis): never
    {
        $stop_if_orphaned = static function () use ($parent_pid, $result_file): void {
            if (posix_getppid() !== $parent_pid) {
                // The parent is gone, so nobody will read the results or delete the file.
                @unlink($result_file);
                self::terminate();
            }
        };

        // The child creates the file, so a parent killed between the fork and this point
        // has nothing to leave behind. The parent removes it by name, and so does the child
        // when it finds the parent gone. It holds issue messages from the user's code,
        // so it is private.
        $previous_umask = umask(0077);
        $handle = @fopen($result_file, 'x');
        umask($previous_umask);
        if ($handle === false) {
            fwrite(STDERR, 'Could not create the taint analysis result file ' . $result_file . PHP_EOL);
            self::terminate();
        }
        fclose($handle);
        $stop_if_orphaned();
        self::startWatchdog($parent_pid, $result_file);

        try {
            $failure = getenv(self::FAILURE_ENV);
            if ($failure === 'crash') {
                fwrite(STDERR, 'Crashing the taint analysis pass on purpose' . PHP_EOL);
                posix_kill(posix_getpid(), SIGABRT);
            }

            if ($failure === 'stall') {
                sleep(60);
            }

            if ($failure === 'hang') {
                for ($i = 0; $i < 60; $i++) {
                    $stop_if_orphaned();
                    sleep(1);
                }
            }

            $run_taint_analysis($stop_if_orphaned);

            if ($failure === 'garbage') {
                $stop_if_orphaned();
                file_put_contents($result_file, self::PAYLOAD_MAGIC . "100\nnot a serialized result");
                self::terminate();
            }

            $issues = [];
            foreach (IssueBuffer::getIssuesData() as $file_path => $file_issues) {
                foreach ($file_issues as $issue) {
                    if (str_starts_with($issue->type, 'Tainted')) {
                        $issues[$file_path][] = $issue;
                    }
                }
            }

            $issue_handler_suppressions = [];
            foreach (Config::getInstance()->getIssueHandlerSuppressions() as $suppression) {
                if ($suppression['count'] > 0 && str_starts_with($suppression['type'], 'Tainted')) {
                    $issue_handler_suppressions[] = $suppression;
                }
            }

            $body = serialize([
                'issues' => $issues,
                'issue_handler_suppressions' => $issue_handler_suppressions,
            ]);
        } catch (Throwable $e) {
            $body = serialize(['error' => $e::class . ': ' . $e->getMessage()
                . ' in ' . $e->getFile() . ':' . $e->getLine()]);
        }

        $stop_if_orphaned();

        $payload = self::PAYLOAD_MAGIC . strlen($body) . "\n" . $body;
        if (@file_put_contents($result_file, $payload) !== strlen($payload)) {
            fwrite(STDERR, 'The taint analysis pass could not write its results to ' . $result_file . PHP_EOL);
        }

        // The parent may have died while the file was being written.
        $stop_if_orphaned();

        self::terminate();
    }

    /**
     * The child checks for a dead parent between files, but one slow file or
     * connectSinksAndSources() can take minutes. So a small process watches the parent
     * once a second and stops the child and its pool workers if it is gone. It only sleeps
     * and checks, holds none of the inherited streams, and exits once the child does.
     */
    private static function startWatchdog(int $parent_pid, string $result_file): void
    {
        $child_pid = posix_getpid();
        $pid = pcntl_fork();
        if ($pid < 0) {
            fwrite(STDERR, 'Could not start the taint analysis watchdog; a killed Psalm may leave'
                . ' the taint analysis running until its next file' . PHP_EOL);
            return;
        }
        if ($pid > 0) {
            return;
        }

        // A pipe reader (CI logs, `psalm | tee`) waits for every holder of stdout to exit.
        foreach ([STDIN, STDOUT, STDERR] as $stream) {
            fclose($stream);
        }

        while (posix_getppid() === $child_pid) {
            sleep(1);
            if (self::parentHasExited($parent_pid, $child_pid) && posix_getppid() === $child_pid) {
                @unlink($result_file);
                // The child leads its own process group, which holds its pool workers and this.
                posix_kill(-$child_pid, SIGKILL);
                break;
            }
        }
        // No shutdown functions or destructors: this process shares the child's state.
        posix_kill(posix_getpid(), SIGKILL);
        exit(1);
    }

    /**
     * Whether the parent has exited. A killed parent nobody has reaped yet still answers
     * kill(0), but the kernel reparents its children when it exits, so on Linux the
     * child's parent pid in /proc says so first.
     */
    private static function parentHasExited(int $parent_pid, int $child_pid): bool
    {
        $stat = @file_get_contents('/proc/' . $child_pid . '/stat');
        if ($stat !== false) {
            // pid (comm) state ppid ...; comm can hold spaces and parentheses, so start after its last ')'
            $fields = explode(' ', substr($stat, (int) strrpos($stat, ')') + 2));

            return (int) ($fields[1] ?? 0) !== $parent_pid;
        }

        return !posix_kill($parent_pid, 0);
    }

    /**
     * Ends the child without running shutdown functions or destructors inherited from
     * the parent (progress output, worker pools, PHPUnit): the parent carries on with them.
     */
    private static function terminate(): never
    {
        // SIGUSR2 rather than SIGKILL, so the parent can tell this from the OOM killer.
        pcntl_signal(self::DONE_SIGNAL, SIG_DFL);
        posix_kill(posix_getpid(), self::DONE_SIGNAL);
        posix_kill(posix_getpid(), SIGKILL);
        exit(1);
    }
}
