<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Type\Union;

/**
 * @internal
 */
final class FinallyScope
{
    /**
     * @param array<string, Union> $vars_in_scope
     * @psalm-mutation-free
     */
    public function __construct(public array $vars_in_scope)
    {
    }
}
