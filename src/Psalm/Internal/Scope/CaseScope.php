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
