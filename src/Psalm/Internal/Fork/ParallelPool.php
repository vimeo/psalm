<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Future;
use Amp\Parallel\Context\Context;
use Amp\Parallel\Context\ContextFactory;
use Amp\Parallel\Context\ProcessContextFactory;
use Amp\Parallel\Worker\ContextWorkerFactory;
use Amp\Parallel\Worker\ContextWorkerPool;
use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\Worker;
use Amp\Parallel\Worker\WorkerPool;
use AssertionError;
use Closure;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Progress\Progress;
use Throwable;

use function Amp\async;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use function Amp\ByteStream\pipe;
use function Amp\Future\await;
use function array_map;
use function count;
use function extension_loaded;
use function gc_collect_cycles;

use const PHP_BINARY;
use const PHP_EOL;
use const SIGALRM;
use const SIGTERM;
use const STREAM_IPPROTO_IP;
use const STREAM_PF_UNIX;
use const STREAM_SOCK_STREAM;

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
final class ParallelPool implements PoolInterface
{
    private readonly WorkerPool $pool;
    private readonly Progress $progress;
    /**
     * @param int<2, max> $threads
     */
    public function __construct(private readonly int $threads, ProjectAnalyzer $project_analyzer)
    {
        // TODO: disable xdebug
        $additional_options = [];
        $opcache_loaded = extension_loaded('opcache') || extension_loaded('Zend OPcache');

        if ($opcache_loaded) {
            foreach (PsalmRestarter::REQUIRED_OPCACHE_SETTINGS as $k => $v) {
                $additional_options []= "-d opcache.$k=$v";
            }
        }

        $this->pool = new ContextWorkerPool(
            $threads,
            new ContextWorkerFactory(
                contextFactory: new class($additional_options) implements ContextFactory {
                    private ProcessContextFactory $factory;
                    public function __construct(array $additional_options)
                    {
                        $this->factory = new ProcessContextFactory(
                            binary: [PHP_BINARY, ...$additional_options],
                        );
                    }
                    public function start(string|array $script, ?Cancellation $cancellation = null): Context
                    {
                        $context = $this->factory->start($script, $cancellation);
                        async(pipe(...), $context->getStdout(), getStdout())->ignore();
                        async(pipe(...), $context->getStderr(), getStderr())->ignore();
                        return $context;
                    }
                }
            ),
        );

        $this->runAll(new InitStartupTask($project_analyzer));
        $this->runAll(new InitScannerTask());

        $this->progress = $project_analyzer->progress;
    }

    public function run(
        array $process_task_data_iterator,
        string $main_task,
        ?Closure $task_done_closure = null
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
            $f->catch(fn(Throwable $e) => throw $e);
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
        return await(
            array_map(fn(Worker $w): Future => $w->submit($task)->getFuture(), $workers),
        );
    }
}
