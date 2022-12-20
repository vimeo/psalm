<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser\Node\Expr\FuncCall;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\StatementsSource;

final class AfterEveryFunctionCallAnalysisEvent
{
    private FuncCall $expr;
    private string $function_id;
    private Context $context;
    private StatementsSource $statements_source;
    private Codebase $codebase;

    /** @internal */
    public function __construct(
        FuncCall $expr,
        string $function_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase
    ) {
        $this->expr = $expr;
        $this->function_id = $function_id;
        $this->context = $context;
        $this->statements_source = $statements_source;
        $this->codebase = $codebase;
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
