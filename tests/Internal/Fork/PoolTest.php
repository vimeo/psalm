<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Fork\Pool;
use Psalm\Progress\VoidProgress;

use function Amp\Future\await;
use function array_unique;
use function extension_loaded;
use function file_exists;
use function getmypid;
use function iterator_to_array;
use function posix_kill;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class PoolTest extends TestCase
{
    public static string $marker = '';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pcntl') || !extension_loaded('posix')) {
            $this->markTestSkipped('Pool forks with pcntl and checks processes with posix');
        }

        $marker = tempnam(sys_get_temp_dir(), 'psalm-pool-test');
        $this->assertNotFalse($marker);
        unlink($marker);
        self::$marker = $marker;
    }

    #[Override]
    protected function tearDown(): void
    {
        if (file_exists(self::$marker)) {
            unlink(self::$marker);
        }

        parent::tearDown();
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

    public function testFinalTaskRunsOnceOnEveryWorker(): void
    {
        $pool = new Pool(3, 60.0, new VoidProgress());

        await($pool->runAll(new PoolTestNoopTask()));

        $final_pids = iterator_to_array($pool->runThenAllThenKill(
            ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
            PoolTestMainTask::class,
            new PoolTestFinalTask(),
        ), false);

        $this->assertCount(3, $final_pids);
        $this->assertCount(3, array_unique($final_pids));
    }

    public function testFinalTaskOfAnIdleWorkerRunsWhileAnotherWorkerIsStillBusy(): void
    {
        $pool = new Pool(3, 60.0, new VoidProgress());

        await($pool->runAll(new PoolTestNoopTask()));

        // the slow tasks only finish once the final task of the fast worker has run
        $done = [];
        $final_results = $pool->runThenAllThenKill(
            ['slow', 'slow', 'fast'],
            PoolTestMainTask::class,
            new PoolTestFinalTask(),
            static function (string $result) use (&$done): void {
                $done []= $result;
            },
        );

        $this->assertSame(['fast', 'slow', 'slow'], $done);

        // the first worker to finish must not be handed the final task a second time in place of the busy one
        $final_pids = [];
        foreach ($final_results as $pid) {
            $this->assertFalse(posix_kill($pid, 0), "worker $pid is still running while its result is unpacked");
            $final_pids []= $pid;
        }
        $this->assertCount(3, array_unique($final_pids));
    }
}
