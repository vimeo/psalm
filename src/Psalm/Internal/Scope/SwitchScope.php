<?php
namespace Psalm\Internal\Scope;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;

class SwitchScope
{
    /**
     * @var array<string, array<string, CodeLocation>>
     */
    public $unreferenced_vars = [];

    /**
     * @var Context
     */
    public $parent_context;

    /**
     * @var array<string, Type\Union>|null
     */
    public $break_vars;
}
