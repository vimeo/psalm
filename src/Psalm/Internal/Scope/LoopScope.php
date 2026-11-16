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
     * The variables evaluating the condition writes, with what they hold once it is evaluated
     *
     * @var array<string, Union>
     */
    public array $condition_written_vars = [];

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
     * What the variables hold where the latest pass over the body continues, null before a continue.
     * A variable missing at one of the continues is possibly undefined.
     *
     * @var array<string, Union>|null
     */
    public ?array $vars_at_continues = null;

    /**
     * Whether a continue leaves a `try` of the loop body that has a `finally`
     */
    public bool $continues_through_finally = false;

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

        $this->vars_at_breaks = self::addVarsAtJump($this->vars_at_breaks, $context->vars_in_scope);
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addContinue(Context $context): void
    {
        if ($context->finally_scope !== $this->loop_parent_context->finally_scope) {
            $this->continues_through_finally = true;
        }

        $this->vars_at_continues = self::addVarsAtJump($this->vars_at_continues, $context->vars_in_scope);
    }

    /**
     * @param array<string, Union>|null $vars_at_jumps
     * @param array<string, Union> $vars_in_scope
     * @return array<string, Union>
     * @psalm-external-mutation-free
     */
    private static function addVarsAtJump(?array $vars_at_jumps, array $vars_in_scope): array
    {
        if ($vars_at_jumps === null) {
            return $vars_in_scope;
        }

        // the variables of a context may be PHP references to each other: a new array is not written through them
        $merged_vars = [];

        foreach ($vars_at_jumps as $var_id => $type) {
            $merged_vars[$var_id] = isset($vars_in_scope[$var_id])
                ? Type::combineUnionTypes($type, $vars_in_scope[$var_id])
                : $type->setPossiblyUndefined(true);
        }

        foreach ($vars_in_scope as $var_id => $type) {
            $merged_vars[$var_id] ??= $type->setPossiblyUndefined(true);
        }

        return $merged_vars;
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
