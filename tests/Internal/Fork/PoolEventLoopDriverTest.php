<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Closure;
use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Fork\Pool;
use Psalm\Progress\VoidProgress;
use Revolt\EventLoop;
use Revolt\EventLoop\CallbackType;
use Revolt\EventLoop\Driver;
use Revolt\EventLoop\Driver\StreamSelectDriver;
use Revolt\EventLoop\Driver\TracingDriver;
use Revolt\EventLoop\Suspension;
use RuntimeException;
use stdClass;

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
        EventLoop::setDriver($this->createKernelHandleDriver());

        new Pool(2, 1000.0, new VoidProgress());

        $this->assertNull(EventLoop::getDriver()->getHandle());
    }

    public function testForkingFromARunningLoopWithAKernelHandleFailsClearly(): void
    {
        EventLoop::setDriver($this->createKernelHandleDriver());

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

    private function createKernelHandleDriver(): Driver
    {
        $inner = new StreamSelectDriver();

        return new class ($inner) implements Driver {
            public function __construct(
                private readonly StreamSelectDriver $inner,
            ) {
            }

            #[Override]
            public function run(): void
            {
                $this->inner->run();
            }

            #[Override]
            public function stop(): void
            {
                $this->inner->stop();
            }

            #[Override]
            public function getSuspension(): Suspension
            {
                return $this->inner->getSuspension();
            }

            #[Override]
            public function isRunning(): bool
            {
                return $this->inner->isRunning();
            }

            #[Override]
            public function queue(Closure $closure, mixed ...$args): void
            {
                $this->inner->queue($closure, ...$args);
            }

            #[Override]
            public function defer(Closure $closure): string
            {
                return $this->inner->defer($closure);
            }

            #[Override]
            public function delay(float $delay, Closure $closure): string
            {
                return $this->inner->delay($delay, $closure);
            }

            #[Override]
            public function repeat(float $interval, Closure $closure): string
            {
                return $this->inner->repeat($interval, $closure);
            }

            #[Override]
            public function onReadable(mixed $stream, Closure $closure): string
            {
                return $this->inner->onReadable($stream, $closure);
            }

            #[Override]
            public function onWritable(mixed $stream, Closure $closure): string
            {
                return $this->inner->onWritable($stream, $closure);
            }

            #[Override]
            public function onSignal(int $signal, Closure $closure): string
            {
                return $this->inner->onSignal($signal, $closure);
            }

            #[Override]
            public function enable(string $callbackId): string
            {
                return $this->inner->enable($callbackId);
            }

            #[Override]
            public function cancel(string $callbackId): void
            {
                $this->inner->cancel($callbackId);
            }

            #[Override]
            public function disable(string $callbackId): string
            {
                return $this->inner->disable($callbackId);
            }

            #[Override]
            public function reference(string $callbackId): string
            {
                return $this->inner->reference($callbackId);
            }

            #[Override]
            public function unreference(string $callbackId): string
            {
                return $this->inner->unreference($callbackId);
            }

            #[Override]
            public function setErrorHandler(?Closure $errorHandler): void
            {
                $this->inner->setErrorHandler($errorHandler);
            }

            #[Override]
            public function getErrorHandler(): ?Closure
            {
                return $this->inner->getErrorHandler();
            }

            #[Override]
            public function getHandle(): mixed
            {
                return new stdClass();
            }

            #[Override]
            public function getIdentifiers(): array
            {
                return $this->inner->getIdentifiers();
            }

            #[Override]
            public function getType(string $callbackId): CallbackType
            {
                return $this->inner->getType($callbackId);
            }

            #[Override]
            public function isEnabled(string $callbackId): bool
            {
                return $this->inner->isEnabled($callbackId);
            }

            #[Override]
            public function isReferenced(string $callbackId): bool
            {
                return $this->inner->isReferenced($callbackId);
            }

            /**
             * @return array<array-key, mixed>
             */
            #[Override]
            public function __debugInfo(): array
            {
                return $this->inner->__debugInfo();
            }
        };
    }
}
