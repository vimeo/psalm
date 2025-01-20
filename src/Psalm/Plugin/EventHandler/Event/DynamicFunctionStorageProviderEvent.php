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
    /**
     * @internal
     */
    public function __construct(
        private readonly ArgTypeInferer $arg_type_inferer,
        private readonly DynamicTemplateProvider $template_provider,
        private readonly StatementsSource $statement_source,
        private readonly string $function_id,
        private readonly PhpParser\Node\Expr\FuncCall $func_call,
        private readonly Context $context,
        private readonly CodeLocation $code_location,
    ) {
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
