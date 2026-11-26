<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Parallel\Worker\TaskFailureThrowable;
use Psalm\Config;
use RuntimeException;
use Throwable;

use function implode;
use function strpos;
use function substr;

use const PHP_EOL;

/**
 * Psalm crashed while it scanned or analysed a file; the message is the report of the crash, meant for the user
 *
 * @internal
 */
final class TaskCrashedException extends RuntimeException
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(public readonly string $path, string $message, Throwable $previous)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @param class-string $main_task the task the worker ran
     */
    public static function inWorker(string $path, TaskFailureThrowable $failure, string $main_task): self
    {
        $causes = [];

        for ($cause = $failure->getPrevious(); $cause instanceof TaskFailureThrowable; $cause = $cause->getPrevious()) {
            $causes[] = 'Caused by: ' . $cause->getOriginalClassName() . ': ' . $cause->getOriginalMessage();
        }

        return new self(
            $path,
            self::report(
                $path,
                $failure->getOriginalClassName(),
                $failure->getOriginalMessage(),
                $failure->getOriginalFile(),
                $failure->getOriginalLine(),
                $causes,
                self::traceUpToTask($failure->getOriginalTraceAsString(), $main_task),
            ),
            $failure,
        );
    }

    public static function inProcess(string $path, Throwable $throwable): self
    {
        $causes = [];

        for ($cause = $throwable->getPrevious(); $cause !== null; $cause = $cause->getPrevious()) {
            $causes[] = 'Caused by: ' . $cause::class . ': ' . $cause->getMessage();
        }

        return new self(
            $path,
            self::report(
                $path,
                $throwable::class,
                $throwable->getMessage(),
                $throwable->getFile(),
                $throwable->getLine(),
                $causes,
                $throwable->getTraceAsString(),
            ),
            $throwable,
        );
    }

    /**
     * The worker did not report a failure of the task, it is gone: killed, or out of its channel
     */
    public static function workerLost(string $path, Throwable $throwable): self
    {
        return new self(
            $path,
            'Psalm crashed on ' . Config::getInstance()->shortenFileName($path) . PHP_EOL
                . 'The worker process died: out of memory, a segfault or a signal.' . PHP_EOL
                . $throwable::class . ': ' . $throwable->getMessage(),
            $throwable,
        );
    }

    /**
     * @param list<string> $causes
     */
    private static function report(
        string $path,
        string $class,
        string $message,
        string $file,
        int $line,
        array $causes,
        string $trace,
    ): string {
        $config = Config::getInstance();

        $lines = [
            'Psalm crashed on ' . $config->shortenFileName($path),
            $class . ': ' . $message,
            '  at ' . $config->shortenFileName($file) . ':' . $line,
            ...$causes,
            $trace,
        ];

        return implode(PHP_EOL, $lines);
    }

    /**
     * A forked worker's trace goes on past its task through the task runner of amphp, the event loop and the stack
     * the parent had when it forked, none of which says anything about the crash.
     *
     * @param class-string $main_task
     * @psalm-pure
     */
    private static function traceUpToTask(string $trace, string $main_task): string
    {
        $entry = strpos($trace, $main_task . '->run(');

        if ($entry === false) {
            return $trace;
        }

        $end = strpos($trace, "\n#", $entry);

        return $end === false ? $trace : substr($trace, 0, $end);
    }
}
