<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

final class MethodParamsProviderEvent
{
    /**
     * @param  list<PhpParser\Node\Arg>    $call_args
     * @internal
     */
    public function __construct(
        private readonly string $fq_classlike_name,
        private readonly string $method_name_lowercase,
        private readonly ?array $call_args = null,
        private readonly ?StatementsSource $statements_source = null,
        private readonly ?Context $context = null,
        private readonly ?CodeLocation $code_location = null,
    ) {
    }

    public function getFqClasslikeName(): string
    {
        return $this->fq_classlike_name;
    }

    public function getMethodNameLowercase(): string
    {
        return $this->method_name_lowercase;
    }

    /**
     * @return list<PhpParser\Node\Arg>|null
     */
    public function getCallArgs(): ?array
    {
        return $this->call_args;
    }

    public function getStatementsSource(): ?StatementsSource
    {
        return $this->statements_source;
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
