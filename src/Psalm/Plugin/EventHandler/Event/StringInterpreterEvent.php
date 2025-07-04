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
        public readonly string $value,
        public readonly Codebase $codebase,
    ) {
    }
}