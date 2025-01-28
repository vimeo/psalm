<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Closure;
use Psalm\Internal\Analyzer\ProjectAnalyzer;

interface PoolInterface {

    /**
     * @param int<2, max> $threads
     */
    public function __construct(int $threads, ProjectAnalyzer $project_analyzer);

    /**
     * @template TFinalResult
     * @template TResult as array
     * @param list<mixed> $process_task_data_iterator
     * An array of task data items to be divided up among the
     * workers. The size of this is the number of forked processes.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     * @param Task<void, void, void> $startup_task A task to execute upon starting a child
     *
     * @param class-string<Task<void, void, TResult>> $main_task A task to execute on each task data.
     *                                                           It must return an array (to be gathered).
     *
     * @param Task<void, void, T> $shutdown_task A task to execute upon shutting down a child
     * @param Closure(TResult $data):void $task_done_closure A closure to execute when a task is done
     * @return list<TFinalResult>
     * @psalm-suppress MixedAssignment
     */
    public function run(
        array $process_task_data_iterator,
        string $main_task,
        ?Closure $task_done_closure = null
    ): void;


    /**
     * @template T
     * @param Task<void, void, T> $task
     * @return list<T>
     */
    public function runAll(Task $task): array;
}