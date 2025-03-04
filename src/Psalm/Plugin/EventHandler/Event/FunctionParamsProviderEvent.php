<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

final class FunctionParamsProviderEvent
{
    /**
     * @param  list<PhpParser\Node\Arg>    $call_args
     * @internal
     */
    public function __construct(
        public readonly StatementsSource $statements_source,
        public readonly string $function_id,
        public readonly array $call_args,
        public readonly ?Context $context = null,
        public readonly ?CodeLocation $code_location = null,
    ) {
    }
}