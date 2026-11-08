<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Override;

use function getmypid;
use function touch;

/**
 * @implements Task<int, void, void>
 */
final class PoolTestFinalTask implements Task
{
    #[Override]
    public function run(Channel $channel, Cancellation $cancellation): int
    {
        touch(PoolTest::$marker);

        return (int) getmypid();
    }
}
