<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

final class FunctionReturnTypeProviderEvent
{
    /**
     * Use this hook for providing custom return type logic. If this plugin does not know what a function should
     * return but another plugin may be able to determine the type, return null. Otherwise return a mixed union type
     * if something should be returned, but can't be more specific.
     *
     * @param non-empty-string $function_id
     * @internal
     */
    public function __construct(
        private readonly StatementsSource $statements_source,
        private readonly string $function_id,
        private readonly FuncCall $stmt,
        private readonly Context $context,
        private readonly CodeLocation $code_location,
    ) {
    }

    public function getStatementsSource(): StatementsSource
    {
        return $this->statements_source;
    }

    /**
     * @return non-empty-string
     */
    public function getFunctionId(): string
    {
        return $this->function_id;
    }

    /**
     * @return list<PhpParser\Node\Arg>
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
