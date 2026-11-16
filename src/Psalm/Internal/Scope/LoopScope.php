<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Context;
use Psalm\Storage\Assertion;
use Psalm\Type;
use Psalm\Type\Union;

use function array_intersect_key;

/**
 * @internal
 */
final class LoopScope
{
    public int $iteration_count = 0;

    /**
     * What the condition being false says of the variables, when the loop has no break
     *
     * @var array<string, list<list<Assertion>>>
     */
    public array $exit_truths = [];

    /**
     * The variables the loop (and the loops around it) may assign, written to, or pass to a call, as the root of
     * what is changed: `$a` for `$a['x']++` and `$a->p = 1`
     *
     * @var array<string, bool>
     */
    public array $assigned_root_var_ids = [];

    /**
     * @var array<string, Union>
     */
    public array $redefined_loop_vars = [];

    /**
     * @var array<string, Union>
     */
    public array $possibly_redefined_loop_vars = [];

    /**
     * What the variables the loop defines, which weren't defined before it, hold where an iteration continues
     *
     * @var array<string, Union>
     */
    public array $possibly_defined_loop_vars = [];

    /**
     * @var array<string, Union>
     */
    public array $possibly_redefined_loop_parent_vars = [];

    /**
     * @var array<string, Union>
     */
    public array $possibly_defined_loop_parent_vars = [];

    /**
     * Offsets some breaks know to be set and others not (e.g. by `isset()`): the type of the array tells as much
     *
     * @var array<string, true>
     */
    public array $unknown_offsets = [];

    /**
     * What the variables hold where the latest pass over the body breaks out of the loop, null before a break.
     * A variable missing at one of the breaks is possibly undefined.
     *
     * @var array<string, Union>|null
     */
    public ?array $vars_at_breaks = null;

    /**
     * Whether a break leaves a `try` of the loop body that has a `finally`, which changes the variables after the break
     */
    public bool $breaks_through_finally = false;

    /**
     * The number of break statements seen in the first iteration
     */
    public int $break_count = 0;

    /**
     * @var array<string, bool>
     */
    public array $vars_possibly_in_scope = [];

    /**
     * @var array<string, bool|int>
     */
    public array $protected_var_ids = [];

    /**
     * @var string[]
     */
    public array $final_actions = [];

    /**
     * The offsets of nullable arrays read on every `break` and `continue` path so far, null before there is one
     *
     * @var array<string, true>|null
     */
    public ?array $null_array_offsets_read_at_jumps = null;

    /**
     * @psalm-external-mutation-free
     */
    public function addJump(Context $context): void
    {
        $this->null_array_offsets_read_at_jumps = $this->null_array_offsets_read_at_jumps === null
            ? $context->null_array_offsets_read
            : array_intersect_key($this->null_array_offsets_read_at_jumps, $context->null_array_offsets_read);
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addBreak(Context $context): void
    {
        if ($context->finally_scope !== $this->loop_parent_context->finally_scope) {
            $this->breaks_through_finally = true;
        }

        if ($this->vars_at_breaks === null) {
            $this->vars_at_breaks = $context->vars_in_scope;

            return;
        }

        $vars_at_breaks = [];

        foreach ($this->vars_at_breaks as $var_id => $type) {
            $vars_at_breaks[$var_id] = isset($context->vars_in_scope[$var_id])
                ? Type::combineUnionTypes($type, $context->vars_in_scope[$var_id])
                : $type->setPossiblyUndefined(true);
        }

        foreach ($context->vars_in_scope as $var_id => $type) {
            $vars_at_breaks[$var_id] ??= $type->setPossiblyUndefined(true);
        }

        $this->vars_at_breaks = $vars_at_breaks;
    }

    /**
     * @psalm-mutation-free
     */
    public function __construct(public Context $loop_context, public Context $loop_parent_context)
    {
    }

    /**
     * @psalm-external-mutation-free
     */
    public function __destruct()
    {
        unset($this->loop_context);
        unset($this->loop_parent_context);
    }
}
