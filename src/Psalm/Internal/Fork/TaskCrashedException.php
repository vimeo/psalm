<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use RuntimeException;
use Throwable;

/**
 * A worker crashed on a file; the message is the report of the crash, meant for the user
 *
 * @internal
 */
final class TaskCrashedException extends RuntimeException
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(public readonly string $path, string $message, Throwable $previous)
    {
        parent::__construct($message, 0, $previous);
    }
}
