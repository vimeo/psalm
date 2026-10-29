<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Future;
use Amp\Parallel\Context\Context;
use Amp\Parallel\Context\ContextFactory;
use Amp\Parallel\Ipc\IpcHub;
use Amp\Parallel\Ipc\LocalIpcHub;
use Amp\Parallel\Worker\ContextWorkerFactory;
use Amp\Parallel\Worker\ContextWorkerPool;
use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\Worker;
use Amp\Parallel\Worker\WorkerPool;
use AssertionError;
use Closure;
use Generator;
use Override;
use Psalm\Progress\Progress;
use Revolt\EventLoop;
use RuntimeException;

use function Amp\Future\await;
use function Amp\Future\awaitAll;
use function array_map;
use function count;
use function file_get_contents;
use function gc_collect_cycles;
use function is_file;
use function ksort;
use function unlink;

use const PHP_EOL;

/**
 * Adapted with relatively few changes from
 * https://github.com/etsy/phan/blob/1ccbe7a43a6151ca7c0759d6c53e2c3686994e53/src/Phan/ForkPool.php
 *
 * Authors: https://github.com/morria, https://github.com/TysonAndre
 *
 * Fork off to n-processes and divide up tasks between
 * each process.
 *
 * @internal
 */
final class Pool
{
    private readonly WorkerPool $pool;
    public function __serialize(): array
    {
        return [];
    }
    /**
     * @param int<2, max> $threads
     */
    public function __construct(
        public readonly int $threads,
        private readonly float $timeLimit,
        private readonly Progress $progress,
    ) {
        $this->pool = new ContextWorkerPool(
            $threads,
            new ContextWorkerFactory(
                contextFactory: new class() implements ContextFactory {
                    public function __construct(
                        private readonly int $childConnectTimeout = 5,
                        private readonly IpcHub $ipcHub = new LocalIpcHub(),
                    ) {
                    }
                    #[Override]
                    public function start(string|array $script, ?Cancellation $cancellation = null): Context
                    {
                        return ForkContext::start($script, $this->ipcHub, $cancellation, $this->childConnectTimeout);
                    }
                },
            ),
        );
    }

    /**
     * @template TResult
     * @param array<string> $process_task_data_iterator
     * An array of task data items to be divided up among the
     * workers. The size of this is the number of forked processes.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     * @param class-string<Task<TResult, void, void>> $main_task A task to execute on each task data.
     *                                                           It must return an array (to be gathered).
     *
     * @param Closure(TResult $data):void $task_done_closure A closure to execute when a task is done
     */
    public function run(
        array $process_task_data_iterator,
        string $main_task,
        ?Closure $task_done_closure = null,
    ): void {
        $total = count($process_task_data_iterator);
        $this->progress->debug("Processing ".$total." tasks...".PHP_EOL);

        $cnt = 0;

        $results = [];
        foreach ($process_task_data_iterator as $file) {
            $results []= $f = $this->pool->submit(new $main_task($file))->getFuture();
            if ($task_done_closure) {
                $f->map($task_done_closure);
            }
            $id = EventLoop::repeat($this->timeLimit, function () use ($file): void {
                static $seconds = 0.0;
                /** @psalm-suppress MixedAssignment, MixedOperand */
                $seconds += $this->timeLimit;
                $this->progress->write(PHP_EOL."Processing $file is taking $seconds seconds...".PHP_EOL);
            });
            $f->finally(static function () use ($id): void {
                EventLoop::cancel($id);
            });
            $f->map(function () use (&$cnt, $total): void {
                $cnt++;
                if (!($cnt % 10)) {
                    $percent = (int) (($cnt*100) / $total);
                    $this->progress->debug("Processing tasks: $cnt/$total ($percent%)...".PHP_EOL);
                }
            });
        }
        await($results);
    }

    /**
     * @template T
     * @param Task<T, void, void> $task
     * @return array<int, Future<T>>
     */
    public function runAll(Task $task): array
    {
        if ($this->pool->getIdleWorkerCount() !== $this->pool->getWorkerCount()) {
            throw new AssertionError("Some workers are busy!");
        }

        gc_collect_cycles();
        $workers = [];
        for ($x = 0; $x < $this->threads; $x++) {
            $workers []= $this->pool->getWorker();
        }
        return array_map(fn(Worker $w): Future => $w->submit($task)->getFuture(), $workers);
    }

    /**
     * Runs a task on every worker, then kills the workers before handing back
     * the results one at a time.
     *
     * Each worker holds its whole analysis state until it is killed. Waiting
     * for the pool to go out of scope keeps that state alive while the
     * results are unpacked and merged, so kill the workers first. The pool
     * cannot be used again afterwards.
     *
     * @template T
     * @param Task<T, void, void> $task
     * @return Generator<int, T, mixed, void>
     */
    public function runAllThenKill(Task $task): Generator
    {
        [$errors, $paths] = awaitAll($this->runAll(new FileResultTask($task)));
        $this->pool->kill();
        ksort($paths);

        try {
            foreach ($errors as $error) {
                throw $error;
            }

            $serializer = ForkContext::createSerializer();
            foreach ($paths as $key => $path) {
                $serialized = file_get_contents($path);
                unlink($path);
                unset($paths[$key]);
                if ($serialized === false) {
                    throw new RuntimeException("Could not read the worker result from $path");
                }

                /** @var T */
                $result = $serializer->unserialize($serialized);
                unset($serialized);
                yield $result;
            }
        } finally {
            foreach ($paths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }
}
