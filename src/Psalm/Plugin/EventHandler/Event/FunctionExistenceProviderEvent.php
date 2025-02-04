<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\StatementsSource;

final class FunctionExistenceProviderEvent
{
    /**
     * Use this hook for informing whether or not a global function exists. If you know the function does
     * not exist, return false. If you aren't sure if it exists or not, return null and the default analysis
     * will continue to determine if the function actually exists.
     *
     * @internal
     */
    public function __construct(
        private readonly StatementsSource $statements_source,
        private readonly string $function_id,
    ) {
    }

    public function getStatementsSource(): StatementsSource
    {
        return $this->statements_source;
    }

    public function getFunctionId(): string
    {
        return $this->function_id;
    }
}
