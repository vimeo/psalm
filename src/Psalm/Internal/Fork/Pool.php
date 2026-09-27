<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Future;
use Amp\Parallel\Context\Context;
use Amp\Parallel\Context\ContextFactory;
use Amp\Parallel\Context\ForkContext;
use Amp\Parallel\Ipc\IpcHub;
use Amp\Parallel\Ipc\LocalIpcHub;
use Amp\Parallel\Worker\ContextWorkerFactory;
use Amp\Parallel\Worker\ContextWorkerPool;
use Amp\Parallel\Worker\Execution;
use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\Worker;
use Amp\Parallel\Worker\WorkerPool;
use Amp\Serialization\NativeSerializer;
use Amp\Sync\ChannelException;
use AssertionError;
use Closure;
use Override;
use Psalm\Progress\Progress;
use Revolt\EventLoop;

use function Amp\Future\await;
use function Amp\async;
use function array_map;
use function count;
use function extension_loaded;
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
    /**
     * @psalm-pure
     */
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
                    /**
                     * @psalm-mutation-free
                     */
                    public function __construct(
                        private readonly int $childConnectTimeout = 5,
                        private readonly IpcHub $ipcHub = new LocalIpcHub(),
                    ) {
                    }
                    #[Override]
                    public function start(string|array $script, ?Cancellation $cancellation = null): Context
                    {
                        $context = ForkContext::start(
                            $this->ipcHub,
                            $script,
                            $cancellation,
                            $this->childConnectTimeout,
                            extension_loaded('igbinary') ? new IgbinarySerializer() : new NativeSerializer(),
                        );

                        return new SignalDiagnosticContext($context);
                    }
                },
            ),
        );
    }

    /**
     * @template TResult
     * @template TReceive
     * @template TSend
     * @param array<string> $process_task_data_iterator
     * An array of task data items to be divided up among the
     * workers. The size of this is the number of forked processes.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     * @param class-string<Task<TResult, TReceive, TSend>> $main_task A task to execute on each task data.
     *                                                                It must return an array (to be gathered).
     *
     * @param Closure(TResult $data):void $task_done_closure A closure to execute when a task is done
     * @param null|Closure(TSend):TReceive $message_handler Handles a message sent by a worker over its task
     *        channel and returns the reply. Used to answer requests a task makes mid-execution (e.g.
     *        registering a custom taint in the parent process).
     */
    public function run(
        array $process_task_data_iterator,
        string $main_task,
        ?Closure $task_done_closure = null,
        ?Closure $message_handler = null,
    ): void {
        $total = count($process_task_data_iterator);
        $this->progress->debug("Processing ".$total." tasks...".PHP_EOL);

        $cnt = 0;

        $results = [];
        foreach ($process_task_data_iterator as $file) {
            $execution = $this->pool->submit(new $main_task($file));
            $results []= $f = $execution->getFuture();
            if ($message_handler !== null) {
                $results []= $this->pumpMessages($execution, $f, $message_handler);
            }
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
     * Answer messages a running task sends over its channel, until the task finishes.
     *
     * The worker blocks on the reply before continuing, so the task's own future only resolves once every
     * request has been answered; once it resolves we close the channel, which unblocks the receive loop.
     *
     * @template TResult
     * @template TReceive
     * @template TSend
     * @param Execution<TResult, TReceive, TSend> $execution
     * @param Future<TResult> $result
     * @param Closure(TSend):TReceive $message_handler
     * @return Future<void>
     */
    private function pumpMessages(Execution $execution, Future $result, Closure $message_handler): Future
    {
        $channel = $execution->getChannel();

        $result->finally(static function () use ($channel): void {
            $channel->close();
        });

        return async(static function () use ($channel, $message_handler): void {
            try {
                while (true) {
                    // The worker blocks on the reply, so answer each request in turn.
                    $channel->send($message_handler($channel->receive()));
                }
            } catch (ChannelException) {
                // The channel was closed once the task finished; nothing more to answer.
            }
        });
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
