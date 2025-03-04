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
        public readonly Codebase $codebase,
    ) {
    }
}
