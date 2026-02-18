<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;

/**
 * @psalm-immutable
 */
final class AfterCodebasePopulatedEvent
{
    /**
     * Called after codebase has been populated
     *
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Codebase $codebase,
    ) {
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }
}
