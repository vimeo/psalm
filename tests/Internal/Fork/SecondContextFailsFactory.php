<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Context\Context;
use Amp\Parallel\Context\ContextException;
use Amp\Parallel\Context\ContextFactory;
use Amp\Parallel\Context\ForkContext;
use Amp\Parallel\Ipc\LocalIpcHub;
use Override;
use Psalm\Internal\Fork\Pool;

/**
 * Forks the first worker like Pool does, and fails to start any other
 *
 * @internal
 */
final class SecondContextFailsFactory implements ContextFactory
{
    private int $started = 0;

    private readonly LocalIpcHub $ipc_hub;

    public function __construct()
    {
        $this->ipc_hub = new LocalIpcHub();
    }

    #[Override]
    public function start(string|array $script, ?Cancellation $cancellation = null): Context
    {
        if ($this->started++ > 0) {
            throw new ContextException('The second worker could not be started');
        }

        return ForkContext::start($this->ipc_hub, $script, $cancellation, 5, Pool::createSerializer());
    }
}
