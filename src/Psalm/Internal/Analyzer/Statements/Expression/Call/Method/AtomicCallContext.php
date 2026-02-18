<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\Internal\MethodIdentifier;

/**
 * @internal
 * @psalm-immutable
 */
final class AtomicCallContext
{
    /**
     * @param list<PhpParser\Node\Arg> $args
     * @psalm-mutation-free
     */
    public function __construct(public MethodIdentifier $method_id, public array $args)
    {
    }
}
