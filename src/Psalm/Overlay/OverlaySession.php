<?php

declare(strict_types=1);

namespace Psalm\Overlay;

use function array_key_exists;
use function dirname;
use function fclose;
use function fgets;
use function function_exists;
use function fwrite;
use function is_array;
use function is_resource;
use function is_string;
use function json_decode;
use function json_encode;
use function proc_close;
use function proc_open;
use function stream_get_contents;
use function trim;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_BINARY;
use const PHP_OS_FAMILY;

/**
 * Analyses a project as if some of its files had other contents, and answers with the issues those contents
 * introduce. A handle to a Psalm process (`psalm --overlay-server`): Psalm is never loaded into the caller.
 * Where the server cannot run (Windows, or a PHP without pcntl), each analyze() runs `psalm --overlays-from=-`.
 *
 * @api
 */
final class OverlaySession
{
    private int $next_id = 1;

    /**
     * @param resource|null $process
     * @param resource|null $stdin
     * @param resource|null $stdout
     */
    private function __construct(
        private readonly OverlayOptions $options,
        private mixed $process,
        private mixed $stdin,
        private mixed $stdout,
    ) {
    }

    /**
     * Starts Psalm, which scans the project before it answers. The project needs a reference index:
     * `psalm --record-references`.
     *
     * @throws OverlayException
     */
    public static function open(OverlayOptions $options): self
    {
        if (PHP_OS_FAMILY === 'Windows' || !function_exists('proc_open')) {
            return new self($options, null, null, null);
        }

        $command = [
            ...self::getCommand($options),
            '--overlay-server',
            '--no-progress',
            '--threads=' . $options->threads,
        ];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w']], $pipes, $options->root);

        if (!is_resource($process) || !isset($pipes[0], $pipes[1])) {
            throw new OverlayException('worker_failed', 'Could not start ' . $command[0]);
        }

        $session = new self($options, $process, $pipes[0], $pipes[1]);
        try {
            $session->call('initialize', ['protocol' => 1]);
        } catch (OverlayException $e) {
            if ($e->kind !== 'unsupported') {
                throw $e;
            }

            // a PHP without pcntl: the command answers instead
            $session->close();

            return new self($options, null, null, null);
        }

        return $session;
    }

    /**
     * @param array<string, string|null> $overlays path of a project file (absolute or relative to the root) => its
     *      new contents, null for a deleted file (not supported yet)
     * @param bool $whole_project analyse every project file and run the passes over the whole project too
     * @throws OverlayException
     */
    public function analyze(array $overlays, bool $whole_project = false): OverlayResult
    {
        $params = ['overlays' => [], 'whole_project' => $whole_project];
        foreach ($overlays as $path => $contents) {
            $params['overlays'][] = $contents === null
                ? ['path' => $path, 'deleted' => true]
                : ['path' => $path, 'contents' => $contents];
        }

        if ($this->options->timeout !== null) {
            $params['timeout_ms'] = (int) ($this->options->timeout * 1_000.0);
        }

        $envelope = $this->stdin === null ? $this->runCommand($params) : $this->call('analyze', $params);

        return OverlayResult::fromEnvelope($envelope);
    }

    /**
     * Files changed on disk: later answers are about the project as it is then
     *
     * @param list<string>|null $paths null when it is not known which
     * @throws OverlayException
     */
    public function projectChanged(?array $paths = null): void
    {
        if ($this->stdin !== null) {
            $this->call('projectChanged', $paths === null ? [] : ['paths' => $paths]);
        }
    }

    public function close(): void
    {
        if ($this->stdin === null || $this->process === null) {
            return;
        }

        try {
            $this->call('shutdown', []);
        } catch (OverlayException) {
            // the server is gone already
        }

        $stdin = $this->stdin;
        $stdout = $this->stdout;
        $process = $this->process;
        $this->stdin = null;
        $this->stdout = null;
        $this->process = null;

        fclose($stdin);
        if ($stdout !== null) {
            fclose($stdout);
        }
        proc_close($process);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param array<string, mixed> $params
     * @return array<array-key, mixed>
     * @throws OverlayException
     */
    private function call(string $method, array $params): array
    {
        if ($this->stdin === null || $this->stdout === null) {
            throw new OverlayException('worker_failed', 'The session is closed');
        }

        $id = $this->next_id++;
        $request = ['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => (object) $params];
        fwrite($this->stdin, json_encode($request, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");

        while (($line = fgets($this->stdout)) !== false) {
            $message = json_decode($line, true);
            if (!is_array($message) || ($message['id'] ?? null) !== $id) {
                continue;
            }

            if (isset($message['error']) && is_array($message['error'])) {
                throw self::toException($message['error']['data'] ?? null, $message['error']['message'] ?? null);
            }

            if (!isset($message['result']) || !is_array($message['result'])) {
                return [];
            }

            return $message['result'];
        }

        throw new OverlayException('worker_failed', 'Psalm stopped without an answer');
    }

    /**
     * @param array<string, mixed> $params
     * @return array<array-key, mixed>
     * @throws OverlayException
     */
    private function runCommand(array $params): array
    {
        $command = [...self::getCommand($this->options), '--overlays-from=-', '--output-format=json', '--no-progress'];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w']], $pipes, $this->options->root);

        if (!is_resource($process) || !isset($pipes[0], $pipes[1])) {
            throw new OverlayException('worker_failed', 'Could not start ' . $command[0]);
        }

        fwrite($pipes[0], json_encode($params, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        fclose($pipes[0]);
        $output = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($process);

        $envelope = json_decode(trim($output), true);
        if (!is_array($envelope)) {
            throw new OverlayException('worker_failed', 'Psalm stopped without an answer');
        }

        if (isset($envelope['error']) && is_array($envelope['error'])) {
            throw self::toException($envelope['error'], $envelope['error']['message'] ?? null);
        }

        return $envelope;
    }

    /**
     * @psalm-pure
     */
    private static function toException(mixed $data, mixed $message): OverlayException
    {
        $kind = is_array($data) && array_key_exists('kind', $data) && is_string($data['kind'])
            ? $data['kind']
            : 'worker_failed';

        return new OverlayException($kind, is_string($message) ? $message : 'Psalm failed');
    }

    /**
     * @return list<string>
     * @psalm-pure
     */
    private static function getCommand(OverlayOptions $options): array
    {
        $command = $options->command
            ?? [PHP_BINARY, dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'psalm'];

        if ($options->config !== null) {
            $command[] = '--config=' . $options->config;
        }

        return $command;
    }
}
