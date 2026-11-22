<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Amp\Parallel\Worker\TaskFailureException;
use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Fork\Pool;
use Psalm\Progress\VoidProgress;
use Revolt\EventLoop;
use RuntimeException;

use function extension_loaded;
use function gc_collect_cycles;
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

    public function testATaskThatFailsIsReportedOnce(): void
    {
        $pool = new Pool(2, 1000.0, new VoidProgress());

        try {
            $pool->run(
                ['a', 'b', 'boom', 'c', 'd'],
                ThrowOnBoomTask::class,
                static function (): void {
                },
                static function (): void {
                },
            );
            $this->fail('The failing task should have been reported');
        } catch (RuntimeException $e) {
            $this->assertStringStartsWith('Psalm crashed processing boom: ', $e->getMessage());
            $this->assertInstanceOf(TaskFailureException::class, $e->getPrevious());
        }

        unset($e);
        gc_collect_cycles();
        // the other tasks' derived futures, destroyed now, would report the failure again from the event loop
        EventLoop::run();
    }
}
