<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use Closure;
use Throwable;

use function fclose;
use function feof;
use function fread;
use function fwrite;
use function pack;
use function pcntl_alarm;
use function pcntl_async_signals;
use function pcntl_fork;
use function pcntl_signal;
use function pcntl_waitpid;
use function posix_getpid;
use function posix_getppid;
use function posix_kill;
use function serialize;
use function stream_set_blocking;
use function stream_socket_pair;
use function strlen;
use function substr;
use function unpack;
use function unserialize;

use const SIGALRM;
use const SIGKILL;
use const STREAM_IPPROTO_IP;
use const STREAM_PF_UNIX;
use const STREAM_SOCK_STREAM;

/**
 * A process forked from this one that runs a pass and sends what it finds as frames over a socket. It is killed
 * once it has sent them, without running destructors or shutdown functions, so nothing it did reaches this
 * process but the frames.
 *
 * @internal
 */
final class Worker
{
    /** @var list<array<string, mixed>> */
    public array $frames = [];

    public bool $closed = false;

    private string $buffer = '';

    /**
     * @param resource $socket
     */
    private function __construct(
        public readonly int $pid,
        public readonly mixed $socket,
    ) {
    }

    /**
     * @param Closure(Closure(array<string, mixed>): void): void $work runs in the child, sends frames
     */
    public static function fork(Closure $work): self
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if ($pair === false || !isset($pair[0], $pair[1])) {
            throw new OverlayFailure(OverlayFailure::WORKER_FAILED, 'Could not create a socket pair');
        }

        $parent_pid = posix_getpid();
        $pid = pcntl_fork();

        if ($pid < 0) {
            throw new OverlayFailure(OverlayFailure::WORKER_FAILED, 'Could not fork a worker');
        }

        [$parent_end, $child_end] = $pair;

        if ($pid === 0) {
            fclose($parent_end);
            self::watchParent($parent_pid);

            $socket = $child_end;
            $send = static function (array $frame) use ($socket): void {
                $payload = serialize($frame);
                $payload = pack('N', strlen($payload)) . $payload;
                while ($payload !== '') {
                    $written = fwrite($socket, $payload);
                    if ($written === false || $written === 0) {
                        // the parent is gone
                        self::stop();
                    }
                    $payload = substr($payload, $written);
                }
            };

            try {
                $work($send);
            } catch (Throwable $e) {
                $send(['error' => (string) $e]);
            }

            fclose($socket);
            self::stop();
        }

        fclose($child_end);
        stream_set_blocking($parent_end, false);

        return new self($pid, $parent_end);
    }

    /**
     * Without destructors or shutdown functions, which could write into memory or files the parent shares
     */
    private static function stop(): never
    {
        posix_kill(posix_getpid(), SIGKILL);

        exit(70);
    }

    /**
     * Reads what the socket has: complete frames are appended to $frames
     */
    public function read(): void
    {
        while (($chunk = fread($this->socket, 65_536)) !== false && $chunk !== '') {
            $this->buffer .= $chunk;
        }

        if (feof($this->socket)) {
            $this->closed = true;
        }

        while (strlen($this->buffer) >= 4) {
            /** @var array{1: int} $length */
            $length = unpack('N', $this->buffer);
            if (strlen($this->buffer) < 4 + $length[1]) {
                break;
            }

            /** @var array<string, mixed>|false $frame the frames are arrays the worker serialized */
            $frame = unserialize(substr($this->buffer, 4, $length[1]), ['allowed_classes' => false]);
            $this->buffer = substr($this->buffer, 4 + $length[1]);

            if ($frame !== false) {
                $this->frames[] = $frame;
            }
        }
    }

    public function kill(): void
    {
        posix_kill($this->pid, SIGKILL);
        $this->reap();
    }

    /**
     * The socket closes with this object
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public function reap(): void
    {
        $this->closed = true;
        pcntl_waitpid($this->pid, $status);
    }

    /**
     * A worker whose parent is gone has nobody to answer: it stops instead of analysing on as an orphan
     */
    private static function watchParent(int $parent_pid): void
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGALRM, static function () use ($parent_pid): void {
            if (posix_getppid() !== $parent_pid) {
                posix_kill(posix_getpid(), SIGKILL);
            }

            pcntl_alarm(1);
        });
        pcntl_alarm(1);
    }
}
