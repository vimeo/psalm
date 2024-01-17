<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;

final class StringInterpreterEvent
{
    /**
     * Called after a statement has been checked
     *
     * @psalm-external-mutation-free
     * @internal
     */
    public function __construct(
        private readonly string $value,
        private readonly Codebase $codebase,
    ) {
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
