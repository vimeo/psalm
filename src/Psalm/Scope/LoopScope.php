<?php
namespace Psalm\Scope;

class LoopScope
{
    /**
     * @var array<string, Type\Union>
     */
    public $loop_vars_in_scope;

    /**
     * @var array<string, Type\Union>
     */
    public $loop_parent_vars_in_scope;
}
