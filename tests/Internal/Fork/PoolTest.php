<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Amp\Parallel\Context\ContextException;
use Amp\Parallel\Worker\TaskFailureException;
use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Fork\Pool;
use Psalm\Progress\VoidProgress;
use Revolt\EventLoop;

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
            $pool->run(['a', 'b', 'boom', 'c', 'd'], ThrowOnBoomTask::class, static function (): void {
            });
            $this->fail('The failing task should have been reported');
        } catch (TaskFailureException $e) {
            $this->assertStringContainsString('boom', $e->getMessage());
        }

        unset($e);
        gc_collect_cycles();
        // the other tasks' futures, destroyed now, would report the failure again from the event loop
        EventLoop::run();
    }

    public function testATaskThatFailsIsReportedOnceWhileMessagesAreHandled(): void
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
        } catch (TaskFailureException $e) {
            $this->assertStringContainsString('boom', $e->getMessage());
        }

        unset($e);
        gc_collect_cycles();
        EventLoop::run();
    }

    public function testATaskThatSucceedsWhileMessagesAreHandledCompletes(): void
    {
        $pool = new Pool(2, 1000.0, new VoidProgress());

        $done = 0;
        $pool->run(
            ['a', 'b', 'c', 'd', 'e'],
            ThrowOnBoomTask::class,
            static function () use (&$done): void {
                $done++;
            },
            static function (): void {
            },
        );

        $this->assertSame(5, $done);
        gc_collect_cycles();
        EventLoop::run();
    }

    public function testAWorkerThatCannotStartIsReportedOnce(): void
    {
        $pool = new Pool(2, 1000.0, new VoidProgress(), new SecondContextFailsFactory());

        try {
            // the first task keeps its worker busy, so the second one needs the worker that can't start; the pool is
            // killed then, and the first task fails with it
            $pool->run(['a', 'b'], SleepTask::class, static function (): void {
            });
            $this->fail('The worker that could not start should have been reported');
        } catch (ContextException $e) {
            $this->assertSame('The second worker could not be started', $e->getMessage());
        }

        unset($e);
        gc_collect_cycles();
        EventLoop::run();
    }
}
