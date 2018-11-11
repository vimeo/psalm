<?php
namespace Psalm\Internal\Scope;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;

class LoopScope
{
    /**
     * @var int
     */
    public $iteration_count = 0;

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
    public $redefined_loop_vars = [];

    /**
     * @var array<string, Type\Union>
     */
    public $possibly_redefined_loop_vars = [];

    /**
     * @var array<string, Type\Union>|null
     */
    public $possibly_redefined_loop_parent_vars = null;

    /**
     * @var array<string, Type\Union>
     */
    public $possibly_defined_loop_parent_vars = [];

    /**
     * @var array<string, bool>
     */
    public $vars_possibly_in_scope = [];

    /**
     * @var array<string, bool>
     */
    public $protected_var_ids = [];

    /**
     * @var array<string, array<string, CodeLocation>>
     */
    public $unreferenced_vars = [];

    /**
     * @var array<string, array<string, CodeLocation>>
     */
    public $possibly_unreferenced_vars = [];

    /**
     * @var string[]
     */
    public $final_actions = [];

    public function __construct(Context $loop_context, Context $parent_context)
    {
        $this->loop_context = $loop_context;
        $this->loop_parent_context = $parent_context;
    }
}
