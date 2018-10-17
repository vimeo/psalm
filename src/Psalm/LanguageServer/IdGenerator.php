<?php
declare(strict_types = 1);

namespace Psalm\LanguageServer;

/**
 * Generates unique, incremental IDs for use as request IDs
 */
class IdGenerator
{
    /**
     * @var int
     */
    public $counter = 1;

    /**
     * Returns a unique ID
     *
     * @return int
     */
    public function generate()
    {
        return $this->counter++;
    }
}
