<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Override;

use function Amp\delay;

/**
 * Still running when the pool kills its workers
 *
 * @internal
 * @psalm-api
 * @implements Task<string, void, void>
 */
final class SleepTask implements Task
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(private readonly string $input)
    {
    }

    #[Override]
    public function run(Channel $channel, Cancellation $cancellation): string
    {
        delay(30);

        return $this->input;
    }
}
