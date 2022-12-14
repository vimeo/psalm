<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

/**
 * Generates unique, incremental IDs for use as request IDs
 *
 * @internal
 */
class IdGenerator
{
    public int $counter = 1;

    /**
     * Returns a unique ID
     */
    public function generate(): int
    {
        return $this->counter++;
    }
}
