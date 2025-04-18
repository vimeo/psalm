<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Context;
use Psalm\Type\Union;

/**
 * @internal
 */
final class LoopScope
{
    public int $iteration_count = 0;

    /**
     * @var array<string, Union>
     */
    public array $redefined_loop_vars = [];

    /**
     * @var array<string, Union>
     */
    public array $possibly_redefined_loop_vars = [];

    /**
     * @var array<string, Union>
     */
    public array $possibly_redefined_loop_parent_vars = [];

    /**
     * @var array<string, Union>
     */
    public array $possibly_defined_loop_parent_vars = [];

    /**
     * @var array<string, bool>
     */
    public array $vars_possibly_in_scope = [];

    /**
     * @var array<string, bool>
     */
    public array $protected_var_ids = [];

    /**
     * @var string[]
     */
    public array $final_actions = [];

    public function __construct(public Context $loop_context, public Context $loop_parent_context)
    {
    }

    public function __destruct()
    {
        unset($this->loop_context);
        unset($this->loop_parent_context);
    }
}
