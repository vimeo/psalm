<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\Internal\MethodIdentifier;

/**
 * @internal
 */
class AtomicCallContext
{
    public MethodIdentifier $method_id;

    /** @var list<PhpParser\Node\Arg> */
    public array $args;

    /** @param list<PhpParser\Node\Arg> $args */
    public function __construct(MethodIdentifier $method_id, array $args)
    {
        $this->method_id = $method_id;
        $this->args = $args;
    }
}
