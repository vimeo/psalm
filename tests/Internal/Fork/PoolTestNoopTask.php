<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Override;

/**
 * @implements Task<null, void, void>
 */
final class PoolTestNoopTask implements Task
{
    /**
     * @psalm-pure
     */
    #[Override]
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        return null;
    }
}
