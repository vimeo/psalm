<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use Psalm\Internal\CliUtils;

use function fclose;
use function feof;
use function fread;
use function fwrite;
use function is_resource;
use function posix_kill;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function stream_set_blocking;
use function strpos;
use function substr;

use const SIGKILL;
use const STDERR;

/**
 * A snapshot process of `psalm --overlay-server`. Its pipes never block: what it is sent waits here until it reads,
 * so that the supervisor keeps reading its answers meanwhile.
 *
 * @internal
 */
final class OverlaySnapshotProcess
{
    /** What it is still to read */
    private string $input = '';

    /** What it wrote after its last complete line */
    private string $output = '';

    /**
     * @param resource $process
     * @param resource $stdin
     * @param resource $stdout
     */
    private function __construct(
        private readonly mixed $process,
        public readonly mixed $stdin,
        public readonly mixed $stdout,
    ) {
    }

    /**
     * @param list<string> $command
     * @param array<string, string> $env
     */
    public static function start(array $command, array $env): self
    {
        $process = proc_open(
            CliUtils::withoutShell($command),
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => STDERR],
            $pipes,
            null,
            $env,
        );

        if (!is_resource($process) || !isset($pipes[0], $pipes[1])) {
            OverlayExitGuard::fail('worker_failed', 'Could not start the snapshot process');
        }

        stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);

        return new self($process, $pipes[0], $pipes[1]);
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public function send(string $line): void
    {
        $this->input .= $line;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasInput(): bool
    {
        return $this->input !== '';
    }

    /**
     * Writes what the pipe takes
     *
     * @return bool false if the process is gone
     */
    public function flush(): bool
    {
        $written = @fwrite($this->stdin, $this->input);
        if ($written === false) {
            return false;
        }

        $this->input = substr($this->input, $written);

        return true;
    }

    /**
     * @return list<string>|null the lines it completed, each with its "\n"; null once it has stopped
     */
    public function receive(): ?array
    {
        $chunk = fread($this->stdout, 65_536);
        if ($chunk === false || ($chunk === '' && feof($this->stdout))) {
            return null;
        }

        $this->output .= $chunk;

        $lines = [];
        while (($end = strpos($this->output, "\n")) !== false) {
            $lines[] = substr($this->output, 0, $end + 1);
            $this->output = substr($this->output, $end + 1);
        }

        return $lines;
    }

    /**
     * Killed with its process group, which holds the process Psalm restarts into and the workers: a snapshot that is
     * still scanning would read nothing before it is done
     */
    public function stop(): void
    {
        $status = proc_get_status($this->process);
        if ($status['running']) {
            // the group may not exist yet: a snapshot that has just started has not made it
            posix_kill($status['pid'], SIGKILL);
            posix_kill(-$status['pid'], SIGKILL);
        }

        $stdin = $this->stdin;
        $stdout = $this->stdout;
        fclose($stdin);
        fclose($stdout);
        proc_close($this->process);
    }
}
