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
}
