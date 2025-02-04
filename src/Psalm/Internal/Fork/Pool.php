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
use Psalm\Progress\Progress;
use Revolt\EventLoop;

use function Amp\Future\await;
use function array_map;
use function count;
use function gc_collect_cycles;

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
    public function __sleep(): array
    {
        return [];
    }
    /**
     * @param int<2, max> $threads
     */
    public function __construct(public readonly int $threads, private readonly Progress $progress)
    {
        $this->pool = new ContextWorkerPool(
            $threads,
            new ContextWorkerFactory(
                contextFactory: new class() implements ContextFactory {
                    public function __construct(
                        private readonly int $childConnectTimeout = 5,
                        private readonly IpcHub $ipcHub = new LocalIpcHub(),
                    ) {
                    }
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
            $id = EventLoop::repeat(10.0, function () use ($file): void {
                static $seconds = 10;
                $this->progress->write(PHP_EOL."Processing $file is taking $seconds seconds...".PHP_EOL);
                /** @psalm-suppress MixedAssignment, MixedOperand */
                $seconds += 10;
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
}
