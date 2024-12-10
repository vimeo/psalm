<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Expr\FuncCall;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;

final class AfterEveryFunctionCallAnalysisEvent
{
    /** @internal */
    public function __construct(
        private readonly FuncCall $expr,
        private readonly string $function_id,
        private readonly Context $context,
        private readonly StatementsSource $statements_source,
        private readonly Codebase $codebase,
    ) {
    }

    public function getExpr(): FuncCall
    {
        return $this->expr;
    }

    public function getFunctionId(): string
    {
        return $this->function_id;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getStatementsSource(): StatementsSource
    {
        return $this->statements_source;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }
}
