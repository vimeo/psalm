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
final class MethodCapabilitiesProviderEvent
{
    /**
     * @param lowercase-string $method_name_lowercase
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly StatementsSource $source,
        private readonly string $fq_classlike_name,
        private readonly string $method_name_lowercase,
        private readonly string $called_fq_classlike_name,
        private readonly PhpParser\Node\Expr\MethodCall $stmt,
        private readonly Context $context,
        private readonly CodeLocation $code_location,
    ) {
    }

    public function getSource(): StatementsSource
    {
        return $this->source;
    }

    /**
     * The class declaring the method
     */
    public function getFqClasslikeName(): string
    {
        return $this->fq_classlike_name;
    }

    /**
     * @return lowercase-string
     */
    public function getMethodNameLowercase(): string
    {
        return $this->method_name_lowercase;
    }

    /**
     * The class of the receiver the method is called on
     */
    public function getCalledFqClasslikeName(): string
    {
        return $this->called_fq_classlike_name;
    }

    public function getStmt(): PhpParser\Node\Expr\MethodCall
    {
        return $this->stmt;
    }

    /**
     * @return list<PhpParser\Node\Arg>
     * @psalm-mutation-free
     */
    public function getCallArgs(): array
    {
        return $this->stmt->getArgs();
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getCodeLocation(): CodeLocation
    {
        return $this->code_location;
    }
}
