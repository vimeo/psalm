<?php

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;

final class StringInterpreterEvent
{
    private string $value;
    private Codebase $codebase;

    /**
     * Called after a statement has been checked
     *
     * @psalm-external-mutation-free
     * @internal
     */
    public function __construct(string $value, Codebase $codebase)
    {
        $this->value = $value;
        $this->codebase = $codebase;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }
}
