<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Override;
use RuntimeException;

/**
 * Pool::run() constructs it with each input
 *
 * @internal
 * @psalm-api
 * @implements Task<string, void, void>
 */
final class ThrowOnBoomTask implements Task
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(private readonly string $input)
    {
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public function run(Channel $channel, Cancellation $cancellation): string
    {
        if ($this->input === 'boom') {
            throw new RuntimeException('boom');
        }

        return $this->input;
    }
}
