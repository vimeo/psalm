<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

final class FunctionParamsProviderEvent
{
    /**
     * @param  list<PhpParser\Node\Arg>    $call_args
     * @internal
     */
    public function __construct(
        private readonly StatementsSource $statements_source,
        private readonly string $function_id,
        private readonly array $call_args,
        private readonly ?Context $context = null,
        private readonly ?CodeLocation $code_location = null,
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

    /**
     * @return list<PhpParser\Node\Arg>
     */
    public function getCallArgs(): array
    {
        return $this->call_args;
    }

    public function getContext(): ?Context
    {
        return $this->context;
    }

    public function getCodeLocation(): ?CodeLocation
    {
        return $this->code_location;
    }
}
