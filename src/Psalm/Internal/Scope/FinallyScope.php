<?php
namespace Psalm\Internal\Scope;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;

/**
 * @internal
 */
class FinallyScope
{
    /**
     * @var array<string, Type\Union>
     */
    public $vars_in_scope = [];

    /**
     * @param array<string, Type\Union> $vars_in_scope
     */
    public function __construct(array $vars_in_scope)
    {
        $this->vars_in_scope = $vars_in_scope;
    }
}
