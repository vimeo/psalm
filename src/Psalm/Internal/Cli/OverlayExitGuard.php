<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use Closure;

use function ceil;
use function error_get_last;
use function fclose;
use function function_exists;
use function fwrite;
use function getenv;
use function getmypid;
use function is_numeric;
use function is_string;
use function json_encode;
use function microtime;
use function min;
use function pcntl_async_signals;
use function pcntl_fork;
use function pcntl_signal;
use function pcntl_waitpid;
use function posix_getpgid;
use function posix_getppid;
use function posix_kill;
use function posix_setpgid;
use function putenv;
use function register_shutdown_function;
use function set_time_limit;
use function str_contains;
use function str_starts_with;
use function substr;
use function usleep;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_EOL;
use const SIGALRM;
use const SIGKILL;
use const STDERR;
use const STDIN;
use const STDOUT;

/**
 * In overlay mode an exit status says "no new error" (0) or "new errors" (2). Everything else Psalm can end with
 * (a config error, an exit(1) anywhere, an uncaught exception, a fatal error, a time limit) must not look like a
 * result to a caller that counts 1..100 as a kill: it ends with 255. Loaded before the autoloaders, so that it
 * covers the config load.
 *
 * @internal
 */
final class OverlayExitGuard
{

    private const REJECTED_OPTIONS = [
        'set-baseline',
        'update-baseline',
        'alter',
        'clear-cache',
        'clear-global-cache',
        'consolidate-cache',
        'no-cache',
        'diff',
        'record-references',
        'find-references-to',
        'generate-json-map',
        'generate-stubs',
        'init',
        'i',
    ];
    private const OVERLAY_OPTIONS = ['--overlay', '--overlays-from', '--overlay-server', '--overlay-timeout'];

    /** Set for the process that answers a request with fresh runs of the overlays, see Strategy::FRESH */
    public const FRESH_ENV = 'PSALM_OVERLAY_FRESH';

    /** The absolute deadline (a microtime(true)) of a run, for the processes it starts */
    public const DEADLINE_ENV = 'PSALM_OVERLAY_DEADLINE';

    /** Set for a process that leads a process group, which the process that started it stops at once */
    public const GROUP_ENV = 'PSALM_OVERLAY_GROUP';

    /** The process that started a process group: when it is gone, so is the group */
    public const OWNER_ENV = 'PSALM_OVERLAY_OWNER';

    /** The time the first process of a one-shot run started, for the processes it starts */
    private const STARTED_ENV = 'PSALM_OVERLAY_STARTED';

    private const TIMEOUT_MESSAGE = 'The request did not finish in time';

    /** Seconds between two looks of the watchdog at the processes it follows */
    private const WATCH_INTERVAL = 0.1;

    private static ?int $pid = null;

    private static bool $answered = false;

    private static bool $json = false;

    /** The kind of failure an unexpected exit is: what Psalm was doing */
    private static string $phase = 'config';

    private static float $started = 0.0;

    private static ?float $deadline = null;

    /** The process that interrupts this one at the deadline, and stops its group when its owner is gone */
    private static ?int $watchdog = null;

    /** The process that started this one, which stops it with what it started */
    private static ?int $owner = null;

    /** The deadline no longer interrupts the process: it writes its answer */
    private static bool $disarmed = false;

    /** @var (Closure(): void)|null */
    private static ?Closure $teardown = null;

    private static ?string $explanation = null;

    /**
     * @param array<int, string> $argv
     * @return bool whether the command runs in overlay mode
     */
    public static function installIfRequested(array $argv): bool
    {
        $overlay = false;
        $server = false;
        $timeout = null;
        foreach ($argv as $i => $arg) {
            foreach (self::OVERLAY_OPTIONS as $option) {
                if ($arg === $option || str_starts_with($arg, $option . '=')) {
                    $overlay = true;
                }
            }

            if ($arg === '--overlay-server') {
                $server = true;
            } elseif (str_starts_with($arg, '--overlay-timeout=')) {
                $timeout = substr($arg, 18);
            } elseif ($arg === '--overlay-timeout') {
                $timeout = $argv[$i + 1] ?? null;
            }

            if ($arg === '--output-format=json' || ($arg === '--output-format' && ($argv[$i + 1] ?? '') === 'json')) {
                self::$json = true;
            }
        }

        if (!$overlay) {
            return false;
        }

        $pid = getmypid();
        self::$pid = $pid === false ? null : $pid;
        register_shutdown_function(self::onShutdown(...));

        if (getenv(self::GROUP_ENV) === '1') {
            // with the process Psalm restarts into and its workers, so that the process that started it can stop
            // them all at once; they stay in it
            putenv(self::GROUP_ENV);
            if (!posix_setpgid(0, 0)) {
                @fwrite(STDERR, 'The overlay process could not start a process group of its own' . PHP_EOL);
            }
        }

        if ($server) {
            // each request has a deadline of its own
            return true;
        }

        $started = getenv(self::STARTED_ENV);
        self::$started = is_string($started) && is_numeric($started) ? (float) $started : microtime(true);
        // the process Psalm restarts into, and a fresh process, keep the time this one started
        putenv(self::STARTED_ENV . '=' . (string) self::$started);

        // a process started for a run: it stops with the process that started it
        $owner = getenv(self::OWNER_ENV);
        if (is_string($owner) && is_numeric($owner) && self::canWatch()) {
            self::$owner = (int) $owner;
            self::startWatchdog();
        }

        // a fresh process gets the deadline of the run it answers for
        $inherited = getenv(self::DEADLINE_ENV);
        if (is_string($inherited) && is_numeric($inherited)) {
            self::setDeadline((float) $inherited);
        }

        if (is_string($timeout) && is_numeric($timeout) && (float) $timeout > 0) {
            self::setDeadline(self::$started + (float) $timeout);
        }

        return true;
    }

    /**
     * What overlay mode cannot be combined with: other modes, and whatever writes
     *
     * @param array<string, mixed> $options
     */
    public static function rejectOptions(array $options): void
    {
        if (isset($options['overlay-server']) && (isset($options['overlay']) || isset($options['overlays-from']))) {
            self::fail('invalid_request', '--overlay-server takes its overlays as requests');
        }

        if (isset($options['overlay']) && isset($options['overlays-from'])) {
            self::fail('invalid_request', '--overlay and --overlays-from exclude each other');
        }

        if (!isset($options['overlay']) && !isset($options['overlays-from']) && !isset($options['overlay-server'])) {
            self::fail('invalid_request', 'No overlay given: use --overlay or --overlays-from');
        }

        foreach (self::REJECTED_OPTIONS as $option) {
            if (isset($options[$option])) {
                self::fail('invalid_request', '--' . $option . ' cannot be used with overlays');
            }
        }

        $timeout = $options['overlay-timeout'] ?? null;
        if ($timeout !== null && (!is_string($timeout) || !is_numeric($timeout) || (float) $timeout <= 0)) {
            self::fail('invalid_request', '--overlay-timeout expects a number of seconds');
        }
    }

    public static function setPhase(string $kind): void
    {
        self::$phase = $kind;
    }

    /**
     * Ends the process with a timeout at the deadline (a microtime(true)), from wherever it is then. An earlier
     * deadline stays. The processes this one starts get it too.
     */
    public static function setDeadline(float $deadline): void
    {
        if (self::$deadline !== null && self::$deadline <= $deadline) {
            return;
        }

        self::$deadline = $deadline;
        putenv(self::DEADLINE_ENV . '=' . (string) $deadline);

        $remaining = $deadline - microtime(true);
        if ($remaining <= 0.0) {
            self::fail('timeout', self::TIMEOUT_MESSAGE);
        }

        if (!self::canWatch()) {
            // without pcntl (Windows), where the time limit counts wall-clock time
            set_time_limit((int) ceil($remaining));

            return;
        }

        self::startWatchdog();
    }

    /**
     * The deadline no longer interrupts the process: what it writes now is its answer
     */
    public static function disarm(): void
    {
        self::$disarmed = true;
        self::stopWatchdog();
    }

    /**
     * @psalm-pure
     */
    private static function canWatch(): bool
    {
        return function_exists('pcntl_fork') && function_exists('pcntl_async_signals') && function_exists('posix_kill');
    }

    /**
     * A process of its own, as alarms count whole seconds, that holds none of the output. It interrupts this process
     * at the deadline, and stops the process group this process leads once this process, or the process that
     * started it, is gone.
     */
    private static function startWatchdog(): void
    {
        self::stopWatchdog();

        pcntl_async_signals(true);
        pcntl_signal(SIGALRM, static function (): void {
            // a signal the watchdog sent before it was stopped
            if (!self::$answered && !self::$disarmed) {
                self::fail('timeout', self::TIMEOUT_MESSAGE);
            }
        });

        $parent = (int) getmypid();
        $group = posix_getpgid(0) === $parent ? $parent : null;
        $deadline = self::$deadline;
        $owner = self::$owner;

        $watchdog = pcntl_fork();
        if ($watchdog === 0) {
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);

            while (true) {
                if (posix_getppid() !== $parent || ($owner !== null && !posix_kill($owner, 0))) {
                    if ($group !== null) {
                        posix_kill(-$group, SIGKILL);
                    }
                    break;
                }

                $remaining = $deadline === null ? self::WATCH_INTERVAL : $deadline - microtime(true);
                if ($remaining <= 0.0) {
                    posix_kill($parent, SIGALRM);
                    break;
                }

                usleep((int) (min($remaining, self::WATCH_INTERVAL) * 1_000_000.0));
            }

            posix_kill((int) getmypid(), SIGKILL);
        }

        self::$watchdog = $watchdog > 0 ? $watchdog : null;
    }

    /**
     * When the process started, or the process that restarted into it
     */
    public static function getStartTime(): float
    {
        return self::$started;
    }

    /**
     * The process has answered (or hands the answer over to the process it restarted into): the deadline no longer
     * interrupts it
     */
    public static function answered(): void
    {
        self::$answered = true;
        self::stopWatchdog();
    }

    /**
     * What stops the processes this one started, when it ends early
     *
     * @param (Closure(): void)|null $teardown
     */
    public static function setTeardown(?Closure $teardown): void
    {
        self::$teardown = $teardown;
    }

    /**
     * Why the process is about to stop, for an exit that does not say
     */
    public static function explain(string $message): void
    {
        self::$explanation = $message;
    }

    public static function fail(string $kind, string $message): never
    {
        self::$answered = true;

        $teardown = self::$teardown;
        self::$teardown = null;
        if ($teardown !== null && getmypid() === self::$pid) {
            $teardown();
        }

        self::report($kind, $message);

        exit(255);
    }

    private static function onShutdown(): void
    {
        if (self::$pid === null || getmypid() !== self::$pid) {
            return;
        }

        self::stopWatchdog();

        if (self::$answered) {
            return;
        }

        $error = error_get_last();
        $message = self::$explanation
            ?? $error['message']
            ?? 'Psalm stopped before it answered, see the messages above';
        $kind = str_contains($message, 'Maximum execution time') ? 'timeout' : self::$phase;

        self::report($kind, $message);

        exit(255);
    }

    private static function stopWatchdog(): void
    {
        if (self::$watchdog === null || self::$pid === null || getmypid() !== self::$pid) {
            return;
        }

        posix_kill(self::$watchdog, SIGKILL);
        pcntl_waitpid(self::$watchdog, $status);
        self::$watchdog = null;
    }

    private static function report(string $kind, string $message): void
    {
        @fwrite(STDERR, 'Overlay analysis failed (' . $kind . '): ' . $message . PHP_EOL);

        if (getenv(OverlayServer::ROLE_ENV) === OverlayServer::ROLE_SNAPSHOT) {
            // what the supervisor answers in its place
            @fwrite(STDOUT, json_encode(
                ['jsonrpc' => '2.0', 'method' => OverlayServer::SNAPSHOT_FAILED, 'params' => [
                    'kind' => $kind,
                    'message' => $message,
                ]],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
            ) . "\n");

            return;
        }

        if (self::$json) {
            // a message that is not valid UTF-8 is still an answer; the output may be what failed
            @fwrite(STDOUT, json_encode(
                ['protocol' => 1, 'error' => ['kind' => $kind, 'message' => $message]],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
            ) . "\n");
        }
    }
}
