<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Plugin\ArgTypeInferer;
use Psalm\Plugin\DynamicTemplateProvider;
use Psalm\StatementsSource;

final class DynamicFunctionStorageProviderEvent
{
    private ArgTypeInferer $arg_type_inferer;
    private DynamicTemplateProvider $template_provider;
    private StatementsSource $statement_source;
    private string $function_id;
    private PhpParser\Node\Expr\FuncCall $func_call;
    private Context $context;
    private CodeLocation $code_location;

    /**
     * @internal
     */
    public function __construct(
        ArgTypeInferer $arg_type_inferer,
        DynamicTemplateProvider $template_provider,
        StatementsSource $statements_source,
        string $function_id,
        PhpParser\Node\Expr\FuncCall $func_call,
        Context $context,
        CodeLocation $code_location
    ) {
        $this->statement_source = $statements_source;
        $this->function_id = $function_id;
        $this->func_call = $func_call;
        $this->context = $context;
        $this->code_location = $code_location;
        $this->arg_type_inferer = $arg_type_inferer;
        $this->template_provider = $template_provider;
    }

    public function getArgTypeInferer(): ArgTypeInferer
    {
        return $this->arg_type_inferer;
    }

    public function getTemplateProvider(): DynamicTemplateProvider
    {
        return $this->template_provider;
    }

    public function getCodebase(): Codebase
    {
        return $this->statement_source->getCodebase();
    }

    public function getStatementSource(): StatementsSource
    {
        return $this->statement_source;
    }

    public function getFunctionId(): string
    {
        return $this->function_id;
    }

    /**
     * @return list<PhpParser\Node\Arg>
     */
    public function getArgs(): array
    {
        return $this->func_call->getArgs();
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
