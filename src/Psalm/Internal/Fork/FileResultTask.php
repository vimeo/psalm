<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Override;
use RuntimeException;

use function file_put_contents;
use function strlen;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Runs a task and writes its serialized result to a temporary file, returning the file's path.
 *
 * A result sent over the worker channel is copied several times on the way: the
 * worker holds the serialized string, the framed packet and the unsent remainder,
 * and the parent buffers every chunk before it unserializes. The worker also
 * stays alive until the parent has unserialized it. A file skips the copies and
 * lets the parent stop the workers before it reads anything back.
 *
 * @internal
 * @template TResult
 * @implements Task<string, void, void>
 */
final class FileResultTask implements Task
{
    /**
     * @param Task<TResult, void, void> $task
     */
    public function __construct(private readonly Task $task)
    {
    }

    #[Override]
    public function run(Channel $channel, Cancellation $cancellation): string
    {
        $serialized = ForkContext::createSerializer()->serialize($this->task->run($channel, $cancellation));

        $path = tempnam(sys_get_temp_dir(), 'psalm-worker-');
        if ($path === false) {
            throw new RuntimeException('Could not create a temporary file for the worker result');
        }

        if (file_put_contents($path, $serialized) !== strlen($serialized)) {
            unlink($path);
            throw new RuntimeException("Could not write the worker result to $path");
        }

        return $path;
    }
}
