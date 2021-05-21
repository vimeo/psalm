<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use Psalm\Internal\MethodIdentifier;
use PhpParser;

class AtomicCallContext
{
    /** @var MethodIdentifier */
    public $method_id;

    /** @var list<PhpParser\Node\Arg> */
    public $args;

    /** @param list<PhpParser\Node\Arg> $args */
    public function __construct(MethodIdentifier $method_id, array $args)
    {
        $this->method_id = $method_id;
        $this->args = $args;
    }
}
