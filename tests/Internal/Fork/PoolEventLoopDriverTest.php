<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Fork\Pool;
use Psalm\Progress\VoidProgress;
use Revolt\EventLoop;
use Revolt\EventLoop\Driver;
use Revolt\EventLoop\Driver\StreamSelectDriver;
use Revolt\EventLoop\Driver\TracingDriver;

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

    public function testWorkersAreForkedWithTheStreamSelectDriver(): void
    {
        EventLoop::setDriver(new TracingDriver(new StreamSelectDriver()));

        new Pool(2, 1000.0, new VoidProgress());

        $this->assertInstanceOf(StreamSelectDriver::class, EventLoop::getDriver());
    }

    public function testAStreamSelectDriverInPlaceIsKept(): void
    {
        $driver = new StreamSelectDriver();
        EventLoop::setDriver($driver);

        new Pool(2, 1000.0, new VoidProgress());

        $this->assertSame($driver, EventLoop::getDriver());
    }
}
