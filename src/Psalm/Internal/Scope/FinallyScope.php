<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Type\Union;

/**
 * @internal
 */
class FinallyScope
{
    /**
     * @var array<string, Union>
     */
    public array $vars_in_scope = [];

    /**
     * @param array<string, Union> $vars_in_scope
     */
    public function __construct(array $vars_in_scope)
    {
        $this->vars_in_scope = $vars_in_scope;
    }
}
