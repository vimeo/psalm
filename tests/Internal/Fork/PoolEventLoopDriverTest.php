<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Fork\Pool;
use Psalm\Progress\VoidProgress;
use Revolt\EventLoop;
use Revolt\EventLoop\Driver;
use Revolt\EventLoop\Driver\IoPollDriver;
use Revolt\EventLoop\Driver\StreamSelectDriver;
use Revolt\EventLoop\Driver\TracingDriver;
use RuntimeException;

use function class_exists;

final class PoolEventLoopDriverTest extends TestCase
{
    private Driver $original_driver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->original_driver = EventLoop::getDriver();
    }

    #[Override]
    protected function tearDown(): void
    {
        EventLoop::setDriver($this->original_driver);

        parent::tearDown();
    }

    public function testADriverHoldingAKernelHandleIsReplaced(): void
    {
        if (!class_exists(IoPollDriver::class) || !IoPollDriver::isSupported()) {
            $this->markTestSkipped('IoPollDriver needs revolt/event-loop >= 1.1 and the Io\Poll API of PHP 8.6');
        }

        EventLoop::setDriver(new IoPollDriver());

        new Pool(2, 1000.0, new VoidProgress());

        $this->assertNull(EventLoop::getDriver()->getHandle());
    }

    public function testForkingFromARunningLoopWithAKernelHandleFailsClearly(): void
    {
        if (!class_exists(IoPollDriver::class) || !IoPollDriver::isSupported()) {
            $this->markTestSkipped('IoPollDriver needs revolt/event-loop >= 1.1 and the Io\Poll API of PHP 8.6');
        }

        EventLoop::setDriver(new IoPollDriver());

        $exception = null;
        EventLoop::queue(static function () use (&$exception): void {
            try {
                new Pool(2, 1000.0, new VoidProgress());
            } catch (RuntimeException $e) {
                $exception = $e;
            }
        });
        EventLoop::run();

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertStringContainsString('REVOLT_DRIVER', $exception->getMessage());
    }

    public function testADriverWithoutKernelHandleIsKept(): void
    {
        $driver = new TracingDriver(new StreamSelectDriver());
        EventLoop::setDriver($driver);

        new Pool(2, 1000.0, new VoidProgress());

        $this->assertSame($driver, EventLoop::getDriver());
    }
}
