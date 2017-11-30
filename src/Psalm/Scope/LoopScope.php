<?php
namespace Psalm\Scope;

use Psalm\Context;
use Psalm\Type;

class LoopScope
{
    /**
     * @var Context
     */
    public $loop_context;

    /**
     * @var Context
     */
    public $loop_parent_context;

    /**
     * @var array<string, Type\Union>|null
     */
    public $redefined_loop_vars = null;

    /**
     * @var array<string, Type\Union>
     */
    public $possibly_redefined_loop_vars = [];

    /**
     * @var array<string, Type\Union>|null
     */
    public $possibly_redefined_loop_parent_vars = [];
}
