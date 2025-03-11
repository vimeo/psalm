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
        public readonly ArgTypeInferer $arg_type_inferer,
        public readonly DynamicTemplateProvider $template_provider,
        public readonly StatementsSource $statement_source,
        public readonly string $function_id,
        public readonly PhpParser\Node\Expr\FuncCall $func_call,
        public readonly Context $context,
        public readonly CodeLocation $code_location,
    ) {
    }
}