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

    public function __construct(public Context $parent_context)
    {
    }

    public function __destruct()
    {
        unset($this->parent_context);
    }
}
