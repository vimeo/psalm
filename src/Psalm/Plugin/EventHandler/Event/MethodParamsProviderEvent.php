<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

final class MethodParamsProviderEvent
{
    /**
     * @param  list<PhpParser\Node\Arg>    $call_args
     * @internal
     */
    public function __construct(
        public readonly string $fq_classlike_name,
        public readonly string $method_name_lowercase,
        public readonly ?array $call_args = null,
        public readonly ?StatementsSource $statements_source = null,
        public readonly ?Context $context = null,
        public readonly ?CodeLocation $code_location = null,
    ) {
    }
}