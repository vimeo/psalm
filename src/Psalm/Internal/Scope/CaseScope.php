<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Context;
use Psalm\Type\Union;

/**
 * @internal
 */
final class CaseScope
{
    /**
     * @var array<string, Union>|null
     */
    public ?array $break_vars = null;

    /**
     * The references where a break leaves the switch: one that a break rebound is not bound as before after it
     *
     * @var list<array<string, string>>
     */
    public array $references_in_scope_at_breaks = [];

    /**
     * @psalm-mutation-free
     */
    public function __construct(public Context $parent_context)
    {
    }

    /**
     * @psalm-external-mutation-free
     */
    public function __destruct()
    {
        unset($this->parent_context);
    }
}
