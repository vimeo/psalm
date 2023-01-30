<?php

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;

final class AfterCodebasePopulatedEvent
{
    private Codebase $codebase;

    /**
     * Called after codebase has been populated
     *
     * @internal
     */
    public function __construct(Codebase $codebase)
    {
        $this->codebase = $codebase;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }
}
