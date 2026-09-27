<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

/**
 * @psalm-immutable
 * @api
 */
final class FunctionReturnTypeProviderEvent
{
    /**
     * Use this hook for providing custom return type logic. If this plugin does not know what a function should
     * return but another plugin may be able to determine the type, return null. Otherwise return a mixed union type
     * if something should be returned, but can't be more specific.
     *
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly StatementsSource $statements_source,
        private readonly int $function_id,
        private readonly FuncCall $stmt,
        private readonly Context $context,
        private readonly CodeLocation $code_location,
    ) {
    }

    public function getStatementsSource(): StatementsSource
    {
        return $this->statements_source;
    }

    public function getFunctionId(): int
    {
        return $this->function_id;
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

    public function getStmt(): FuncCall
    {
        return $this->stmt;
    }
}
