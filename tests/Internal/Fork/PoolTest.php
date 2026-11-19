<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Fork\Pool;
use Psalm\Progress\VoidProgress;

use function extension_loaded;
use function getmypid;
use function posix_kill;

final class PoolTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pcntl') || !extension_loaded('posix')) {
            $this->markTestSkipped('Pool forks with pcntl and checks processes with posix');
        }
    }

    public function testWorkersAreGoneBeforeTheirResultsAreHandedBack(): void
    {
        $pool = new Pool(2, 1000.0, new VoidProgress());

        $pids = [];
        foreach ($pool->runAllThenKill(new ReportPidTask()) as $pid) {
            $this->assertFalse(posix_kill($pid, 0), "worker $pid is still running while its result is unpacked");
            $pids[] = $pid;
        }

        $this->assertCount(2, $pids);
        $this->assertNotContains(getmypid(), $pids);
    }
}
