<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use Closure;

use function array_filter;
use function array_values;
use function explode;
use function fclose;
use function feof;
use function fread;
use function is_array;
use function is_resource;
use function is_string;
use function json_decode;
use function microtime;
use function posix_kill;
use function proc_close;
use function proc_get_status;
use function stream_set_blocking;

use const SIGKILL;

/**
 * One request answered by passes in processes forked from the snapshot. The overlay pass decides which files both
 * passes analyse and says so first, so the pass without the overlays starts while the overlay pass runs.
 *
 * @psalm-import-type IssueArray from IssueDiff
 * @psalm-import-type Removed from RuntimeDeclarations
 * @internal
 */
final class Job
{
    /** @var array{protocol: int, new_issues: list<IssueArray>, analyzed_files: list<string>,
     *     not_checked: list<string>}|null */
    public ?array $envelope = null;

    public ?OverlayFailure $failure = null;

    /** @var Strategy::*|null */
    public ?string $strategy = null;

    /** @var list<string>|null */
    public ?array $files = null;

    private ?Worker $overlay_worker = null;

    private ?Worker $base_worker = null;

    /** @var list<IssueArray>|null */
    private ?array $overlay_issues = null;

    /** @var list<IssueArray>|null */
    private ?array $base_issues = null;

    /** @var resource|null */
    private mixed $fresh_process = null;

    /** @var resource|null */
    private mixed $fresh_stdout = null;

    private string $fresh_output = '';

    /**
     * @param Closure(list<string>, bool): (list<IssueArray>|null) $memo_get
     * @param Closure(list<string>, bool, list<IssueArray>): void $memo_set
     * @param Closure(Request, ?float, Removed): array{resource, resource} $start_fresh a fresh process and its
     *      STDOUT, given the deadline and what the overlays no longer declare
     */
    public function __construct(
        private readonly Engine $engine,
        public readonly Request $request,
        public readonly ?float $deadline,
        private readonly Closure $memo_get,
        private readonly Closure $memo_set,
        private readonly Closure $start_fresh,
    ) {
    }

    public function start(): void
    {
        $engine = $this->engine;
        $request = $this->request;

        $this->overlay_worker = Worker::fork(static function (Closure $send) use ($engine, $request): void {
            [$strategy, $files, $removed] = $engine->chooseFiles($request);
            $send(['strategy' => $strategy, 'files' => $files, 'removed' => $removed]);

            if ($strategy !== Strategy::FRESH && $files) {
                $send(['issues' => $engine->analyze($engine->project_analyzer, $files, $request->whole_project)]);
            }
        });
    }

    /**
     * @return list<resource>
     * @psalm-capabilities read-props
     */
    public function getStreams(): array
    {
        $streams = [];
        foreach ([$this->overlay_worker, $this->base_worker] as $worker) {
            if ($worker !== null && !$worker->closed) {
                $streams[] = $worker->socket;
            }
        }

        if ($this->fresh_stdout !== null) {
            $streams[] = $this->fresh_stdout;
        }

        return $streams;
    }

    /**
     * @psalm-capabilities read-props
     */
    public function isDone(): bool
    {
        return $this->envelope !== null || $this->failure !== null;
    }

    /**
     * Reads what the processes sent and moves on
     */
    public function poll(): void
    {
        if ($this->isDone()) {
            return;
        }

        if ($this->deadline !== null && microtime(true) >= $this->deadline) {
            $this->fail(new OverlayFailure(OverlayFailure::TIMEOUT, 'The request did not finish in time'));

            return;
        }

        if ($this->overlay_worker !== null) {
            $this->readOverlayWorker($this->overlay_worker);
        }

        if ($this->base_worker !== null && $this->failure === null) {
            $this->base_issues ??= $this->readIssues($this->base_worker);
        }

        if ($this->fresh_stdout !== null && $this->failure === null) {
            $this->readFreshProcess();
        }

        if ($this->failure === null && $this->files !== null && $this->overlay_issues !== null
            && $this->base_issues !== null
        ) {
            ($this->memo_set)($this->files, $this->request->whole_project, $this->base_issues);
            $this->envelope = $this->engine->buildEnvelope(
                $this->request,
                $this->files,
                $this->base_issues,
                $this->overlay_issues,
            );
            $this->stop();
        }
    }

    public function fail(OverlayFailure $failure): void
    {
        if (!$this->isDone()) {
            $this->failure = $failure;
        }

        $this->stop();
    }

    private function readOverlayWorker(Worker $worker): void
    {
        $worker->read();

        while ($worker->frames) {
            $frame = $worker->frames[0];

            if (isset($frame['error'])) {
                $this->fail(new OverlayFailure(OverlayFailure::WORKER_FAILED, (string) $frame['error']));

                return;
            }

            if (!isset($frame['strategy'])) {
                break;
            }

            /** @var array{strategy: Strategy::*, files: list<string>, removed: Removed} $frame */
            unset($worker->frames[0]);
            $worker->frames = array_values($worker->frames);
            $this->strategy = $frame['strategy'];
            $this->onFilesChosen($frame['files'], $frame['removed']);

            if ($this->isDone()) {
                return;
            }
        }

        if ($this->strategy === null && $worker->closed) {
            $this->fail(new OverlayFailure(
                OverlayFailure::WORKER_FAILED,
                'A worker stopped without an answer (crash or out of memory, see STDERR)',
            ));

            return;
        }

        if ($this->files !== null && $this->strategy !== Strategy::FRESH) {
            $this->overlay_issues ??= $this->readIssues($worker);
        }
    }

    /**
     * @param list<string> $files
     * @param Removed $removed
     */
    private function onFilesChosen(array $files, array $removed): void
    {
        if ($this->strategy === Strategy::FRESH) {
            $this->overlay_worker?->kill();
            $this->overlay_worker = null;
            [$this->fresh_process, $this->fresh_stdout]
                = ($this->start_fresh)($this->request, $this->deadline, $removed);
            stream_set_blocking($this->fresh_stdout, false);

            return;
        }

        $this->files = $files;

        foreach ($files as $file) {
            if (!ReferenceIndex::isUnchanged($file, $this->engine->index->stamps[$file] ?? null)) {
                $this->fail(new OverlayFailure(
                    OverlayFailure::PROJECT_CHANGED,
                    $file . ' changed since the analysis started',
                ));

                return;
            }
        }

        if (!$files) {
            $this->overlay_issues = [];
            $this->base_issues = [];

            return;
        }

        $this->base_issues = ($this->memo_get)($files, $this->request->whole_project);

        if ($this->base_issues === null) {
            $engine = $this->engine;
            $whole_project = $this->request->whole_project;
            $this->base_worker = Worker::fork(
                static function (Closure $send) use ($engine, $files, $whole_project): void {
                    $send(['issues' => $engine->analyze($engine->project_analyzer, $files, $whole_project)]);
                },
            );
        }
    }

    /**
     * @return list<IssueArray>|null
     */
    private function readIssues(Worker $worker): ?array
    {
        $worker->read();

        foreach ($worker->frames as $frame) {
            if (isset($frame['error'])) {
                $this->fail(new OverlayFailure(OverlayFailure::WORKER_FAILED, (string) $frame['error']));

                return null;
            }

            if (isset($frame['issues']) && is_array($frame['issues'])) {
                /** @var list<IssueArray> */
                return $frame['issues'];
            }
        }

        if ($worker->closed) {
            $this->fail(new OverlayFailure(
                OverlayFailure::WORKER_FAILED,
                'A worker stopped without an answer (crash or out of memory, see STDERR)',
            ));
        }

        return null;
    }

    private function readFreshProcess(): void
    {
        $stdout = $this->fresh_stdout;
        if ($stdout === null) {
            return;
        }

        while (($chunk = fread($stdout, 65_536)) !== false && $chunk !== '') {
            $this->fresh_output .= $chunk;
        }

        if (!feof($stdout)) {
            return;
        }

        $lines = array_values(array_filter(explode("\n", $this->fresh_output)));
        $answer = $lines ? json_decode($lines[0], true) : null;

        if (is_array($answer) && isset($answer['error']) && is_array($answer['error'])) {
            $this->fail(new OverlayFailure(
                OverlayFailure::WORKER_FAILED,
                isset($answer['error']['message']) && is_string($answer['error']['message'])
                    ? $answer['error']['message']
                    : 'The fresh run failed',
            ));

            return;
        }

        if (!is_array($answer) || !isset($answer['new_issues'], $answer['analyzed_files'], $answer['not_checked'])) {
            $this->fail(new OverlayFailure(OverlayFailure::WORKER_FAILED, 'The fresh run gave no answer'));

            return;
        }

        /** @var array{protocol: int, new_issues: list<IssueArray>, analyzed_files: list<string>,
         *     not_checked: list<string>} $answer */
        $this->envelope = $answer;
        $this->stop();
    }

    private function stop(): void
    {
        foreach ([$this->overlay_worker, $this->base_worker] as $worker) {
            if ($worker !== null && !$worker->closed) {
                $worker->kill();
            } elseif ($worker !== null) {
                $worker->reap();
            }
        }

        $this->overlay_worker = null;
        $this->base_worker = null;

        if ($this->fresh_stdout !== null) {
            $stdout = $this->fresh_stdout;
            $this->fresh_stdout = null;
            fclose($stdout);
        }

        if (is_resource($this->fresh_process)) {
            $status = proc_get_status($this->fresh_process);
            if ($status['running']) {
                // with the process it restarted into and its workers: it leads a process group
                posix_kill(-$status['pid'], SIGKILL);
                posix_kill($status['pid'], SIGKILL);
            }
            proc_close($this->fresh_process);
        }

        $this->fresh_process = null;
    }
}
