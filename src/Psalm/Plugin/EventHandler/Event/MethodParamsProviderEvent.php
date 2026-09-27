<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

/**
 * @psalm-immutable
 * @api
 */
final class MethodParamsProviderEvent
{
    /**
     * @param list<PhpParser\Node\Arg>    $call_args
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly int $fq_classlike_name,
        private readonly int $method_name,
        private readonly ?array $call_args = null,
        private readonly ?StatementsSource $statements_source = null,
        private readonly ?Context $context = null,
        private readonly ?CodeLocation $code_location = null,
    ) {
    }

    /**
     * Interned class name id, as declared (class names are case-sensitive).
     */
    public function getFqClasslikeName(): int
    {
        return $this->fq_classlike_name;
    }

    /**
     * Interned method name id, as written (method names are case-sensitive, no case folding is applied).
     */
    public function getMethodName(): int
    {
        return $this->method_name;
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
