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
        public readonly FuncCall $expr,
        public readonly string $function_id,
        public readonly Context $context,
        public readonly StatementsSource $statements_source,
        public readonly Codebase $codebase,
    ) {
    }
}
