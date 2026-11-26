<?php

declare(strict_types=1);

namespace Psalm\Internal\Crash;

use Amp\Parallel\Worker\TaskFailureThrowable;
use Throwable;
use WeakMap;

use function serialize;
use function unserialize;

/**
 * What Psalm was doing when an exception was thrown. It is kept beside the exception, not in a wrapper, so that
 * Psalm's own catches and the tests still get the exception that was thrown.
 *
 * @internal
 */
final class CrashContext
{
    /** @var WeakMap<Throwable, self>|null */
    private static ?WeakMap $contexts = null;

    /** The interface of the plugin hook that threw */
    public ?string $hook = null;

    /**
     * The class of the handler of that hook
     *
     * @var class-string|null
     */
    public ?string $handler = null;

    /** What Psalm was doing to $file_path: "scanning", "analysing" */
    public ?string $activity = null;

    public ?string $file_path = null;

    /**
     * The innermost hook wins: the exception goes through the hooks that called the one that threw.
     *
     * @param class-string $handler
     */
    public static function recordHook(Throwable $e, string $hook, string $handler): void
    {
        $context = self::get($e);
        if ($context->handler === null) {
            $context->hook = $hook;
            $context->handler = $handler;
        }
    }

    public static function recordFile(Throwable $e, string $activity, string $file_path): void
    {
        $context = self::get($e);
        $context->activity ??= $activity;
        $context->file_path ??= $file_path;
    }

    /**
     * The context of an exception and of the exceptions it wraps
     */
    public static function of(Throwable $e): self
    {
        $merged = new self();
        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            $context = self::$contexts[$current] ?? null;
            if ($context === null) {
                continue;
            }

            if ($merged->handler === null) {
                $merged->hook = $context->hook;
                $merged->handler = $context->handler;
            }

            $merged->activity ??= $context->activity;
            $merged->file_path ??= $context->file_path;
        }

        return $merged;
    }

    /**
     * The exception a worker throws for the parent process, which gets only its class, message, code and trace:
     * the context travels in the message of a wrapper
     */
    public static function forParent(Throwable $e): Throwable
    {
        return isset(self::$contexts[$e]) ? new CrashContextException(serialize(self::$contexts[$e]), $e) : $e;
    }

    /**
     * The failure of a task in the parent process, with the context the worker sent
     */
    public static function fromWorker(TaskFailureThrowable $e): TaskFailureThrowable
    {
        $previous = $e->getPrevious();
        if ($e->getOriginalClassName() !== CrashContextException::class
            || !$previous instanceof TaskFailureThrowable
        ) {
            return $e;
        }

        /** @var self|false $context the only class it may make */
        $context = unserialize($e->getOriginalMessage(), ['allowed_classes' => [self::class]]);
        if ($context instanceof self) {
            self::$contexts ??= new WeakMap();
            self::$contexts[$previous] = $context;
        }

        return $previous;
    }

    private static function get(Throwable $e): self
    {
        self::$contexts ??= new WeakMap();

        return self::$contexts[$e] ??= new self();
    }
}
