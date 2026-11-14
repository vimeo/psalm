<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use Closure;
use Psalm\Internal\CliUtils;

use function array_key_first;
use function array_shift;
use function array_values;
use function count;
use function feof;
use function fmod;
use function fread;
use function fwrite;
use function implode;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function json_decode;
use function max;
use function microtime;
use function min;
use function serialize;
use function stream_select;
use function stream_set_blocking;
use function strlen;
use function strpos;
use function substr;
use function trim;

use const PSALM_VERSION;
use const STDIN;
use const STDOUT;

/**
 * The snapshot process of `psalm --overlay-server`: answers the requests the supervisor forwards, up to $threads
 * at once, each in workers forked from the snapshot
 *
 * @psalm-import-type IssueArray from IssueDiff
 * @internal
 */
final class SnapshotServer
{
    /** The base passes it keeps, by the files they analysed */
    private const MEMO_BYTES = 256 * 1024 * 1024;

    private const TIMEOUT_MESSAGE = 'The request did not finish in time';

    /** @var array<string, array{Job, int|string}> */
    private array $running = [];

    /** @var list<array{Request, int|string, ?float}> with their deadlines */
    private array $queue = [];

    /** What the supervisor sent after the last complete line */
    private string $input = '';

    /** @var array<string, array{string, list<IssueArray>}> least recently used first */
    private array $memo = [];

    private int $memo_bytes = 0;

    /**
     * @param positive-int $threads
     * @param Closure(Request, ?float): array{resource, resource} $start_fresh
     */
    public function __construct(
        private readonly ?Engine $engine,
        private readonly ?OverlayFailure $failure,
        private readonly string $root,
        private readonly int $threads,
        private readonly Closure $start_fresh,
    ) {
    }

    public function run(): void
    {
        // a request comes in pieces: the rest of it never waits for the answers
        stream_set_blocking(STDIN, false);

        while (true) {
            $read = [STDIN];
            $deadline = null;
            foreach ($this->running as [$job]) {
                foreach ($job->getStreams() as $stream) {
                    $read[] = $stream;
                }
                if ($job->deadline !== null) {
                    $deadline = $deadline === null ? $job->deadline : min($deadline, $job->deadline);
                }
            }
            foreach ($this->queue as [, , $queued_deadline]) {
                if ($queued_deadline !== null) {
                    $deadline = $deadline === null ? $queued_deadline : min($deadline, $queued_deadline);
                }
            }

            $write = null;
            $except = null;
            $timeout = $deadline !== null ? max(0.0, $deadline - microtime(true)) : 1.0;
            @stream_select($read, $write, $except, (int) $timeout, (int) (fmod($timeout, 1.0) * 1_000_000.0));

            foreach ($read as $stream) {
                if ($stream !== STDIN) {
                    continue;
                }

                $chunk = fread(STDIN, 65_536);
                if ($chunk === false || ($chunk === '' && feof(STDIN))) {
                    $this->stopAll();

                    return;
                }

                $this->input .= CliUtils::sentByOperator($chunk);
                while (($end = strpos($this->input, "\n")) !== false) {
                    $line = substr($this->input, 0, $end);
                    $this->input = substr($this->input, $end + 1);

                    if (trim($line) !== '' && !$this->handle($line)) {
                        $this->stopAll();

                        return;
                    }
                }
            }

            foreach ($this->running as $key => [$job, $id]) {
                $job->poll();

                if ($job->isDone()) {
                    unset($this->running[$key]);
                    $job->failure !== null
                        ? $this->sendError($id, $job->failure)
                        : $this->send(['jsonrpc' => '2.0', 'id' => $id, 'result' => $job->envelope]);
                }
            }

            $this->expireQueued();
            $this->startQueued();
        }
    }

    /**
     * A request that waits for a thread times out like one that runs
     */
    private function expireQueued(): void
    {
        $now = microtime(true);
        foreach ($this->queue as $i => [, $id, $deadline]) {
            if ($deadline !== null && $deadline <= $now) {
                unset($this->queue[$i]);
                $this->sendError($id, new OverlayFailure(OverlayFailure::TIMEOUT, self::TIMEOUT_MESSAGE));
            }
        }
        $this->queue = array_values($this->queue);
    }

    /**
     * @return bool false to stop
     */
    private function handle(string $line): bool
    {
        $message = json_decode($line, true);
        if (!is_array($message) || !isset($message['method']) || !is_string($message['method'])) {
            $this->sendError(null, new OverlayFailure(OverlayFailure::INVALID_REQUEST, 'Parse error'), -32_700);

            return true;
        }

        $id = isset($message['id']) && (is_int($message['id']) || is_string($message['id'])) ? $message['id'] : null;
        $params = isset($message['params']) && is_array($message['params']) ? $message['params'] : [];

        switch ($message['method']) {
            case 'initialize':
                if ($this->failure !== null) {
                    $this->sendError($id, $this->failure);
                } else {
                    $this->send(['jsonrpc' => '2.0', 'id' => $id, 'result' => [
                        'protocol' => Protocol::VERSION,
                        'psalm_version' => PSALM_VERSION,
                        'root' => $this->root,
                        'capabilities' => [
                            'whole_project' => true,
                            'new_files' => false,
                            'deleted_files' => false,
                            'cancel' => true,
                        ],
                    ]]);
                }

                return true;

            case 'analyze':
                if ($id === null) {
                    return true;
                }

                if ($this->failure !== null || $this->engine === null) {
                    $this->sendError($id, $this->failure ?? new OverlayFailure(OverlayFailure::CONFIG, 'No engine'));

                    return true;
                }

                try {
                    $request = Protocol::parseAnalyzeParams($params, $this->root);
                    $this->engine->validate($request);
                } catch (OverlayFailure $failure) {
                    $code = $failure->kind === OverlayFailure::INVALID_REQUEST ? -32_602 : null;
                    $this->sendError($id, $failure, $code);

                    return true;
                }

                if ($this->isInFlight($id)) {
                    $this->sendError(
                        $id,
                        new OverlayFailure(OverlayFailure::INVALID_REQUEST, 'A request with this id is in flight'),
                        -32_600,
                    );

                    return true;
                }

                // set by the supervisor when it received the request: the time before it got here counts
                /** @var mixed $deadline_at */
                $deadline_at = $params['deadline_at'] ?? null;
                $deadline = is_float($deadline_at) || is_int($deadline_at)
                    ? (float) $deadline_at
                    : ($request->timeout_ms !== null ? microtime(true) + (float) $request->timeout_ms / 1_000.0 : null);
                if ($deadline !== null && $deadline <= microtime(true)) {
                    $this->sendError($id, new OverlayFailure(OverlayFailure::TIMEOUT, self::TIMEOUT_MESSAGE));

                    return true;
                }

                $this->queue[] = [$request, $id, $deadline];
                $this->startQueued();

                return true;

            case '$/cancel':
                $this->cancel($params['id'] ?? null);

                return true;

            case 'shutdown':
                $this->send(['jsonrpc' => '2.0', 'id' => $id, 'result' => []]);

                return false;

            default:
                if ($id !== null) {
                    $this->send(['jsonrpc' => '2.0', 'id' => $id, 'error' => [
                        'code' => -32_601,
                        'message' => 'Method not found',
                        'data' => ['kind' => OverlayFailure::INVALID_REQUEST],
                    ]]);
                }

                return true;
        }
    }

    private function startQueued(): void
    {
        while ($this->queue && count($this->running) < $this->threads && $this->engine !== null) {
            [$request, $id, $deadline] = array_shift($this->queue);
            $job = new Job(
                $this->engine,
                $request,
                $deadline,
                $this->getMemo(...),
                $this->setMemo(...),
                $this->start_fresh,
            );

            try {
                $job->start();
            } catch (OverlayFailure $failure) {
                $this->sendError($id, $failure);
                continue;
            }

            $this->running[(string) $id] = [$job, $id];
        }
    }

    private function cancel(mixed $id): void
    {
        if (!is_int($id) && !is_string($id)) {
            return;
        }

        $cancelled = new OverlayFailure(OverlayFailure::CANCELLED, 'The request was cancelled');

        if (isset($this->running[(string) $id])) {
            [$job] = $this->running[(string) $id];
            unset($this->running[(string) $id]);
            $job->fail($cancelled);
            $this->sendError($id, $cancelled);

            return;
        }

        foreach ($this->queue as $i => [, $queued_id]) {
            if ($queued_id === $id) {
                unset($this->queue[$i]);
                $this->queue = array_values($this->queue);
                $this->sendError($id, $cancelled);

                return;
            }
        }
    }

    /**
     * @psalm-mutation-free
     */
    private function isInFlight(int|string $id): bool
    {
        if (isset($this->running[(string) $id])) {
            return true;
        }

        foreach ($this->queue as [, $queued_id]) {
            if ($queued_id === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $files
     * @return list<IssueArray>|null
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function getMemo(array $files, bool $whole_project): ?array
    {
        $key = ($whole_project ? '1' : '0') . implode("\0", $files);
        if (!isset($this->memo[$key])) {
            return null;
        }

        $entry = $this->memo[$key];
        unset($this->memo[$key]);
        $this->memo[$key] = $entry;

        return $entry[1];
    }

    /**
     * @param list<string> $files
     * @param list<IssueArray> $issues
     */
    private function setMemo(array $files, bool $whole_project, array $issues): void
    {
        $key = ($whole_project ? '1' : '0') . implode("\0", $files);
        if (isset($this->memo[$key])) {
            return;
        }

        $size = strlen($key) + strlen(serialize($issues));
        $this->memo[$key] = [(string) $size, $issues];
        $this->memo_bytes += $size;

        while ($this->memo_bytes > self::MEMO_BYTES && $this->memo) {
            $oldest = array_key_first($this->memo);
            $this->memo_bytes -= (int) $this->memo[$oldest][0];
            unset($this->memo[$oldest]);
        }
    }

    private function stopAll(): void
    {
        foreach ($this->running as [$job]) {
            $job->fail(new OverlayFailure(OverlayFailure::CANCELLED, 'The server stopped'));
        }

        $this->running = [];
    }

    private function sendError(int|string|null $id, OverlayFailure $failure, ?int $code = null): void
    {
        $this->send(['jsonrpc' => '2.0', 'id' => $id, 'error' => [
            'code' => $code ?? -32_000,
            'message' => $failure->getMessage(),
            'data' => ['kind' => $failure->kind],
        ]]);
    }

    /** @param array<string, mixed> $message */
    private function send(array $message): void
    {
        // nobody reads the answers any more: the supervisor stopped this snapshot
        if (@fwrite(STDOUT, Protocol::encode($message) . "\n") === false) {
            $this->stopAll();

            exit(0);
        }
    }
}
