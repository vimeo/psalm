<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;

class FunctionDynamicStorageProviderEvent
{
    private StatementsAnalyzer $statements_analyzer;
    private string $function_id;
    private PhpParser\Node\Expr\FuncCall $func_call;
    private Context $context;
    private CodeLocation $code_location;

    public function __construct(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        PhpParser\Node\Expr\FuncCall $func_call,
        Context $context,
        CodeLocation $code_location
    ) {
        $this->statements_analyzer = $statements_analyzer;
        $this->function_id = $function_id;
        $this->func_call = $func_call;
        $this->context = $context;
        $this->code_location = $code_location;
    }

    public function getStatementsAnalyzer(): StatementsAnalyzer
    {
        return $this->statements_analyzer;
    }

    public function getFunctionId(): string
    {
        return $this->function_id;
    }

    public function getExpr(): PhpParser\Node\Expr\FuncCall
    {
        return $this->func_call;
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
