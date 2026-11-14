<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use Psalm\Internal\CliUtils;

use function array_slice;
use function array_values;
use function defined;
use function extension_loaded;
use function feof;
use function fgets;
use function fmod;
use function fread;
use function function_exists;
use function fwrite;
use function getenv;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;
use function max;
use function microtime;
use function min;
use function realpath;
use function str_starts_with;
use function stream_select;
use function stream_set_blocking;
use function strlen;
use function strpos;
use function substr;
use function trim;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_BINARY;
use const STDIN;
use const STDOUT;

/**
 * `psalm --overlay-server`: owns STDIN and STDOUT and holds no project state. A snapshot process (this command
 * again, in the snapshot role) scans the project and answers; "project changed" and a crash of the snapshot both
 * start a new one. JSON-RPC 2.0, one message per line. Loaded before the autoloaders: plain PHP only.
 *
 * Nothing it writes blocks it: the client and the snapshot can each send more than a pipe holds while the other
 * does.
 *
 * @internal
 */
final class OverlayServer
{
    public const ROLE_ENV = 'PSALM_OVERLAY_ROLE';
    public const ROLE_SNAPSHOT = 'snapshot';

    /** The notification a snapshot process sends when it fails, with the kind and the message of the failure */
    public const SNAPSHOT_FAILED = 'psalm/snapshotFailed';

    private const SWITCH_ID = 'psalm-overlay-server:switch';

    /** How long the answer of a snapshot that could not start stands, after each failure in a row */
    private const RETRY_DELAYS = [1.0, 2.0, 4.0, 8.0, 16.0, 30.0];

    private ?OverlaySnapshotProcess $snapshot = null;

    /** Whether the snapshot answered anything: a failure before is a failure to start */
    private bool $answered = false;

    /** @var array<string, int|string> internal id => public id, answered by the snapshot */
    private array $in_flight = [];

    /** @var list<int|string> the projectChanged requests the snapshot answers when it is ready */
    private array $waiting = [];

    /** @var array{kind: string, message: string}|null why the last snapshot could not start */
    private ?array $failure = null;

    /** @var int<0, max> */
    private int $failures = 0;

    private float $retry_at = 0.0;

    /** What the client sent after its last complete line */
    private string $input = '';

    /** What the client is still to read */
    private string $output = '';

    /** @var array<string, float> internal id => deadline of the requests in flight */
    private array $deadlines = [];

    /** @var array<string, string> public id key => internal id of the requests in flight */
    private array $by_public = [];

    /** The last internal id */
    private int $generation = 0;

    /**
     * @param list<string> $command
     * @param array<string, string> $env
     */
    private function __construct(
        private readonly array $command,
        private readonly array $env,
    ) {
    }

    /**
     * @param array<int, string> $argv
     */
    public static function run(array $argv): never
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')
            || !extension_loaded('pcntl')
            || !extension_loaded('posix')
            || !function_exists('pcntl_fork')
        ) {
            self::refuse('--overlay-server needs pcntl and posix (Linux or macOS)');
        }

        $env = getenv();
        $env[self::ROLE_ENV] = self::ROLE_SNAPSHOT;
        $env[OverlayExitGuard::GROUP_ENV] = '1';

        (new self([PHP_BINARY, (string) realpath($argv[0] ?? ''), ...array_slice($argv, 1)], $env))->serve();
    }

    private function serve(): never
    {
        stream_set_blocking(STDIN, false);
        stream_set_blocking(STDOUT, false);

        // scanning before the first request
        $this->startSnapshot();

        while (true) {
            $read = [STDIN];
            $write = $this->output !== '' ? [STDOUT] : [];
            $snapshot = $this->snapshot;
            if ($snapshot !== null) {
                $read[] = $snapshot->stdout;
                if ($snapshot->hasInput()) {
                    $write[] = $snapshot->stdin;
                }
            }

            $except = null;
            $timeout = $this->getTimeout();
            $selected = $timeout === null
                ? @stream_select($read, $write, $except, null)
                : @stream_select($read, $write, $except, (int) $timeout, (int) (fmod($timeout, 1.0) * 1_000_000.0));
            if ($selected === false) {
                continue;
            }

            if (in_array(STDOUT, $write, true)) {
                $written = @fwrite(STDOUT, $this->output);
                if ($written === false) {
                    // the client is gone
                    $this->stopSnapshot();
                    stream_set_blocking(STDIN, true);
                    OverlayExitGuard::answered();
                    exit(0);
                }
                $this->output = substr($this->output, $written);
            }

            if ($snapshot !== null && $snapshot === $this->snapshot) {
                if (in_array($snapshot->stdin, $write, true) && !$snapshot->flush()) {
                    $this->onSnapshotStopped();
                } elseif (in_array($snapshot->stdout, $read, true)) {
                    $lines = $snapshot->receive();
                    if ($lines === null) {
                        $this->onSnapshotStopped();
                    } else {
                        foreach ($lines as $line) {
                            $this->fromSnapshot($line);
                        }
                    }
                }
            }

            if (in_array(STDIN, $read, true)) {
                $chunk = fread(STDIN, 65_536);
                if ($chunk === false || ($chunk === '' && feof(STDIN))) {
                    // the client is gone
                    $this->finish();
                }

                $this->input .= CliUtils::sentByOperator($chunk);
                while (($end = strpos($this->input, "\n")) !== false) {
                    $line = substr($this->input, 0, $end + 1);
                    $this->input = substr($this->input, $end + 1);
                    $this->fromClient($line);
                }
            }

            $this->expire();
        }
    }

    /**
     * Seconds until the next deadline, null without one
     */
    private function getTimeout(): ?float
    {
        $next = null;
        foreach ($this->deadlines as $deadline) {
            $next = $next === null ? $deadline : min($next, $deadline);
        }

        return $next === null ? null : max(0.0, $next - microtime(true));
    }

    private function fromClient(string $line): void
    {
        if (trim($line) === '') {
            return;
        }

        $message = json_decode($line, true);
        if (!is_array($message) || !isset($message['method']) || !is_string($message['method'])) {
            $this->send(['jsonrpc' => '2.0', 'id' => null, 'error' => [
                'code' => -32_700,
                'message' => 'Parse error',
                'data' => ['kind' => 'invalid_request'],
            ]]);

            return;
        }

        $id = self::getIdOf($message);

        if ($message['method'] === 'shutdown') {
            foreach ([...$this->in_flight, ...$this->waiting] as $pending) {
                $this->sendError($pending, 'cancelled', 'The server shut down');
            }
            $this->send(['jsonrpc' => '2.0', 'id' => $id, 'result' => (object) []]);
            $this->finish();
        }

        if ($message['method'] === 'projectChanged') {
            // what the old snapshot answers now would be about the project as it was
            foreach ($this->takeInFlight() as $pending) {
                $this->sendError($pending, 'project_changed', 'The project changed: send the request again');
            }
            $this->failure = null;
            $this->failures = 0;
            $this->stopSnapshot();

            // the snapshot started for an earlier projectChanged answers it too
            $this->startSnapshot();
            $this->snapshot?->send(
                self::encode(['jsonrpc' => '2.0', 'id' => self::SWITCH_ID, 'method' => 'initialize']),
            );
            if ($id !== null) {
                $this->waiting[] = $id;
            }

            return;
        }

        if ($id !== null && isset($this->by_public[(string) $id])) {
            // its answer would be taken for the answer to the other one
            $this->send(['jsonrpc' => '2.0', 'id' => $id, 'error' => [
                'code' => -32_600,
                'message' => 'A request with this id is in flight',
                'data' => ['kind' => 'invalid_request'],
            ]]);

            return;
        }

        if ($this->snapshot === null) {
            if ($this->failure !== null && microtime(true) < $this->retry_at) {
                if ($id !== null) {
                    $this->sendError($id, $this->failure['kind'], $this->failure['message']);
                }

                return;
            }

            $this->startSnapshot();
        }

        if ($id !== null) {
            // its own id towards the snapshot: a public id is reused once it has its answer, and a late answer to
            // the request that had it before is not the answer to this one
            $key = 'r' . ++$this->generation;
            $this->in_flight[$key] = $id;
            $this->by_public[(string) $id] = $key;
            $message['id'] = $key;
            if ($message['method'] === 'analyze') {
                $message = $this->withDeadline($message, $key);
            }
            $line = self::encode($message);
        } elseif ($message['method'] === '$/cancel') {
            $cancelled = self::getIdOf($message['params'] ?? null);
            $cancelled = $cancelled !== null ? $this->by_public[(string) $cancelled] ?? null : null;
            if ($cancelled === null) {
                // nothing in flight has that id
                return;
            }
            $line = self::encode([...$message, 'params' => ['id' => $cancelled]]);
        }
        $this->snapshot?->send($line);
    }

    private function fromSnapshot(string $line): void
    {
        /** @var mixed $message */
        $message = json_decode($line, true);

        if (is_array($message) && ($message['method'] ?? null) === self::SNAPSHOT_FAILED) {
            /** @var mixed $params */
            $params = $message['params'] ?? null;
            $kind = 'worker_failed';
            $error = 'The snapshot process failed';
            if (is_array($params)
                && isset($params['kind'], $params['message'])
                && is_string($params['kind'])
                && is_string($params['message'])
            ) {
                $kind = $params['kind'];
                $error = $params['message'];
            }

            if (!$this->answered) {
                // it would fail the same way at once: its answer stands for a while
                $this->failure = ['kind' => $kind, 'message' => $error];
                $this->retry_at = microtime(true) + self::RETRY_DELAYS[min($this->failures, 5)];
                $this->failures++;
            }

            foreach ([...$this->takeInFlight(), ...$this->waiting] as $pending) {
                $this->sendError($pending, $kind, $error);
            }
            $this->waiting = [];

            return;
        }

        $this->answered = true;
        $this->failure = null;
        $this->failures = 0;

        $id = self::getIdOf($message);
        if ($id === self::SWITCH_ID) {
            foreach ($this->waiting as $waiting) {
                $this->send(['jsonrpc' => '2.0', 'id' => $waiting, 'result' => (object) []]);
            }
            $this->waiting = [];

            return;
        }

        if ($id !== null) {
            $key = (string) $id;
            if (!isset($this->in_flight[$key])) {
                // the late answer to a request it answered already, when its deadline passed
                return;
            }

            $public = $this->in_flight[$key];
            $this->forget($key);

            // the snapshot writes the id first: the rest of the line stays as it is
            $prefix = '{"jsonrpc":"2.0","id":' . self::encodeValue($id) . ',';
            $line = str_starts_with($line, $prefix)
                ? '{"jsonrpc":"2.0","id":' . self::encodeValue($public) . ',' . substr($line, strlen($prefix))
                : self::encode([...(array) $message, 'id' => $public]);
        }

        $this->output .= $line;
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function forget(string $key): void
    {
        $public = $this->in_flight[$key] ?? null;
        if ($public !== null) {
            unset($this->by_public[(string) $public]);
        }
        unset($this->in_flight[$key], $this->deadlines[$key]);
    }

    /**
     * The public ids of the requests in flight, which it forgets
     *
     * @return list<int|string>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function takeInFlight(): array
    {
        $in_flight = array_values($this->in_flight);
        $this->in_flight = [];
        $this->by_public = [];
        $this->deadlines = [];

        return $in_flight;
    }

    /**
     * Answers the requests whose deadline passed, wherever they are: still to be read by a snapshot that starts, or
     * waiting for a thread, or running
     */
    private function expire(): void
    {
        $now = microtime(true);
        foreach ($this->deadlines as $key => $deadline) {
            if ($deadline <= $now && isset($this->in_flight[$key])) {
                $public = $this->in_flight[$key];
                $this->forget($key);
                $this->sendError($public, 'timeout', 'The request did not finish in time');
            }
        }
    }

    /**
     * The request with the deadline its timeout_ms sets from now, which the snapshot keeps
     *
     * @param array<array-key, mixed> $message
     * @return array<array-key, mixed>
     */
    private function withDeadline(array $message, string $key): array
    {
        $params = $message['params'] ?? null;
        if (!is_array($params) || !isset($params['timeout_ms']) || !is_int($params['timeout_ms'])
            || $params['timeout_ms'] < 1
        ) {
            return $message;
        }

        $deadline = microtime(true) + (float) $params['timeout_ms'] / 1_000.0;
        $this->deadlines[$key] = $deadline;
        $params['deadline_at'] = $deadline;
        $message['params'] = $params;

        return $message;
    }

    /**
     * What it was asked fails, the next request starts a new one
     */
    private function onSnapshotStopped(): void
    {
        // what it wrote before, such as why it failed
        while ($this->snapshot !== null && ($lines = $this->snapshot->receive())) {
            foreach ($lines as $line) {
                $this->fromSnapshot($line);
            }
        }

        foreach ([...$this->takeInFlight(), ...$this->waiting] as $pending) {
            $this->sendError($pending, 'worker_failed', 'The snapshot process stopped, see STDERR');
        }
        $this->waiting = [];
        $this->stopSnapshot();
    }

    private function startSnapshot(): void
    {
        $this->snapshot = OverlaySnapshotProcess::start($this->command, $this->env);
        $this->answered = false;
    }

    private function stopSnapshot(): void
    {
        $this->snapshot?->stop();
        $this->snapshot = null;
    }

    /**
     * Writes what the client is still to read, and exits
     */
    private function finish(): never
    {
        $this->stopSnapshot();

        stream_set_blocking(STDIN, true);
        stream_set_blocking(STDOUT, true);
        @fwrite(STDOUT, $this->output);

        OverlayExitGuard::answered();
        exit(0);
    }

    /**
     * Answers every request with the reason it cannot serve, so that a client knows to run the command instead
     */
    private static function refuse(string $reason): never
    {
        while (($line = fgets(STDIN)) !== false) {
            /** @var mixed $message */
            $message = json_decode(CliUtils::sentByOperator($line), true);
            $id = self::getIdOf($message);

            if (is_array($message) && ($message['method'] ?? null) === 'shutdown') {
                fwrite(STDOUT, self::encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => (object) []]));
                OverlayExitGuard::answered();
                exit(0);
            }

            if ($id !== null) {
                fwrite(STDOUT, self::encode(['jsonrpc' => '2.0', 'id' => $id, 'error' => [
                    'code' => -32_000,
                    'message' => $reason,
                    'data' => ['kind' => 'unsupported'],
                ]]));
            }
        }

        OverlayExitGuard::fail('unsupported', $reason);
    }

    /**
     * The id of a message, null for a notification or a line that is not one
     *
     * @psalm-pure
     */
    private static function getIdOf(mixed $message): int|string|null
    {
        if (!is_array($message) || !isset($message['id'])) {
            return null;
        }

        return is_int($message['id']) || is_string($message['id']) ? $message['id'] : null;
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function sendError(int|string $id, string $kind, string $message): void
    {
        $this->send(['jsonrpc' => '2.0', 'id' => $id, 'error' => [
            'code' => -32_000,
            'message' => $message,
            'data' => ['kind' => $kind],
        ]]);
    }

    /**
     * @param array<string, mixed> $message
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function send(array $message): void
    {
        $this->output .= self::encode($message);
    }

    /**
     * @param array<array-key, mixed> $message
     * @psalm-pure
     */
    private static function encode(array $message): string
    {
        return self::encodeValue($message) . "\n";
    }

    /**
     * @psalm-pure
     */
    private static function encodeValue(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        );
    }
}
