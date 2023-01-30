<?php

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

final class FunctionReturnTypeProviderEvent
{
    private StatementsSource $statements_source;
    /**
     * @var non-empty-string
     */
    private string $function_id;
    private FuncCall $stmt;
    private Context $context;
    private CodeLocation $code_location;

    /**
     * Use this hook for providing custom return type logic. If this plugin does not know what a function should
     * return but another plugin may be able to determine the type, return null. Otherwise return a mixed union type
     * if something should be returned, but can't be more specific.
     *
     * @param non-empty-string $function_id
     * @internal
     */
    public function __construct(
        StatementsSource $statements_source,
        string $function_id,
        FuncCall $stmt,
        Context $context,
        CodeLocation $code_location
    ) {
        $this->statements_source = $statements_source;
        $this->function_id = $function_id;
        $this->stmt = $stmt;
        $this->context = $context;
        $this->code_location = $code_location;
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
