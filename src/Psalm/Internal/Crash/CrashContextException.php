<?php

declare(strict_types=1);

namespace Psalm\Internal\Crash;

use RuntimeException;
use Throwable;

/**
 * Carries the serialized CrashContext of an exception from a worker to the parent process
 *
 * @internal
 */
final class CrashContextException extends RuntimeException
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(string $serialized_context, Throwable $previous)
    {
        parent::__construct($serialized_context, 0, $previous);
    }
}
