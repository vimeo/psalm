<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Amp\Parallel\Worker\TaskFailureException;
use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Fork\Pool;
use Psalm\Internal\Fork\TaskCrashedException;
use Psalm\Progress\VoidProgress;
use Psalm\Tests\TestConfig;
use Revolt\EventLoop;
use RuntimeException;

use function extension_loaded;
use function gc_collect_cycles;
use function getmypid;
use function posix_kill;
use function sort;
use function substr_count;

use const PHP_EOL;

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

    public function testEveryResultOfAFreshPoolReachesTheParent(): void
    {
        // a lost result leaves run() waiting forever
        $guard = EventLoop::delay(30, static function (): never {
            throw new RuntimeException('Pool::run() was still waiting for a result after 30s');
        });
        EventLoop::unreference($guard);

        $pool = new Pool(2, 1000.0, new VoidProgress());
        $done = [];
        $pool->run(
            ['a', 'b', 'c', 'd', 'e', 'f'],
            ThrowOnBoomTask::class,
            'analysing',
            static function (string $result) use (&$done): void {
                $done[] = $result;
            },
        );
        EventLoop::cancel($guard);

        sort($done);
        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f'], $done);
    }

    public function testATaskThatFailsIsReportedOnce(): void
    {
        new TestConfig();
        $pool = new Pool(2, 1000.0, new VoidProgress());

        try {
            $pool->run(
                ['a', 'b', 'boom', 'c', 'd'],
                ThrowOnBoomTask::class,
                'analysing',
                static function (): void {
                },
                static function (): void {
                },
            );
            $this->fail('The failing task should have been reported');
        } catch (TaskCrashedException $e) {
            $this->assertSame('boom', $e->path);
            $this->assertInstanceOf(TaskFailureException::class, $e->getPrevious());

            $message = $e->getMessage();
            $this->assertStringStartsWith(
                'Psalm crashed while analysing boom' . PHP_EOL . 'RuntimeException: boom' . PHP_EOL . '  at ',
                $message,
            );
            $this->assertMatchesRegularExpression('~\R  at \S*ThrowOnBoomTask\.php:\d+\R#0 ~', $message);
            // the trace of the worker, once, up to the task: not the event loop or the stack of the parent
            $this->assertSame(1, substr_count($message, 'ThrowOnBoomTask->run('));
            $this->assertSame(1, substr_count($message, "\n#0 "));
            // only as the caller of the task
            $this->assertSame(1, substr_count($message, 'task-runner.php'));
            $this->assertStringNotContainsString('Revolt', $message);
            $this->assertStringNotContainsString('Stack trace', $message);
        }

        unset($e);
        gc_collect_cycles();
        // the other tasks' derived futures, destroyed now, would report the failure again from the event loop
        EventLoop::run();
    }
}
