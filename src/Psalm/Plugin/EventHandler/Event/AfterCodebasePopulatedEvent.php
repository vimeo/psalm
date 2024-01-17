<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;

final class AfterCodebasePopulatedEvent
{
    /**
     * Called after codebase has been populated
     *
     * @internal
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
