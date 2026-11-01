<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Override;

use function file_exists;
use function usleep;

/**
 * @implements Task<string, void, void>
 */
final class PoolTestMainTask implements Task
{
    /**
     * @api
     */
    public function __construct(private readonly string $file)
    {
    }

    #[Override]
    public function run(Channel $channel, Cancellation $cancellation): string
    {
        if ($this->file === 'slow') {
            // 20s at most, so that a missing final task fails the test instead of hanging it
            for ($i = 0; $i < 2000 && !file_exists(PoolTest::$marker); $i++) {
                usleep(10_000);
            }

            return file_exists(PoolTest::$marker) ? 'slow' : 'timeout';
        }

        return $this->file;
    }
}
