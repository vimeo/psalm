<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Codebase;

/**
 * @psalm-immutable
 */
final class StringInterpreterEvent
{
    /**
     * Called after a statement has been checked
     *
     * @internal
     * @psalm-mutation-free
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
