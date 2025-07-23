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
        public readonly StatementsSource $statements_source,
        public readonly string $function_id,
        public readonly FuncCall $stmt,
        public readonly Context $context,
        public readonly CodeLocation $code_location,
    ) {
    }
}