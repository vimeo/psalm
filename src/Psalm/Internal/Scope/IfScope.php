<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Context;
use Psalm\Internal\Clause;
use Psalm\Storage\Assertion;
use Psalm\Type\Union;

use function array_intersect_key;

/**
 * @internal
 */
final class IfScope
{
    /**
     * @var array<string, Union>|null
     */
    public ?array $new_vars = null;

    /**
     * Offsets some branches reaching the end know to be set and others not (e.g. by `isset()`): the type of the
     * array tells as much
     *
     * @var array<string, true>
     */
    public array $unknown_offsets = [];

    /**
     * Types of the variables some branches define but not all, combined over the branches that define them
     *
     * @var array<string, Union>
     */
    public array $partial_new_vars = [];

    /**
     * @var array<string, bool>
     */
    public array $new_vars_possibly_in_scope = [];

    /**
     * @var array<string, Union>|null
     */
    public ?array $redefined_vars = null;

    /**
     * @var array<string, int>|null
     */
    public ?array $assigned_var_ids = null;

    /**
     * @var array<string, bool>
     */
    public array $possibly_assigned_var_ids = [];

    /**
     * The variables a branch assigned as a whole (not only an offset or a property of them)
     *
     * @var array<string, bool>
     */
    public array $reassigned_var_ids = [];

    /**
     * The variables the conditions of the elseifs assigned, which the else runs after
     *
     * @var array<string, int>
     */
    public array $elseif_cond_assigned_var_ids = [];

    /**
     * @var array<string, Union>
     */
    public array $possibly_redefined_vars = [];

    /**
     * @var array<string, bool>
     */
    public array $updated_vars = [];

    /**
     * @var array<string, list<array<int, Assertion>>>
     */
    public array $negated_types = [];

    /**
     * @var array<string, bool>
     */
    public array $if_cond_changed_var_ids = [];

    /**
     * @var array<string, string>|null
     */
    public ?array $negatable_if_types = null;

    /**
     * @var list<Clause>
     */
    public array $negated_clauses = [];

    /**
     * These are the set of clauses that could be applied after the `if`
     * statement, if the `if` statement contains branches with leaving statements,
     * and the else leaves too
     *
     * @var list<Clause>
     */
    public array $reasonable_clauses = [];

    /**
     * @var string[]
     */
    public array $if_actions = [];

    /**
     * @var string[]
     */
    public array $final_actions = [];

    /**
     * The classes the code after the if may use without them being reported when every other branch leaves, so it
     * is only reached through the if block
     *
     * @var array<lowercase-string, true>|null
     */
    public ?array $phantom_classes_of_if_block = null;

    /**
     * Whether there is no else and the conditions matched every value of a variable
     */
    public bool $implicit_else_is_impossible = false;

    public ?Context $post_leaving_if_context = null;

    /**
     * The offsets of nullable arrays read on every branch that reaches the end of the if so far, null before one does
     *
     * @var array<string, true>|null
     */
    public ?array $null_array_offsets_read = null;

    /**
     * The contexts of the branches that reach the end of the if
     *
     * @var list<Context>
     */
    public array $end_contexts = [];

    /**
     * @psalm-external-mutation-free
     */
    public function addBranchReachingTheEnd(Context $branch_context): void
    {
        $this->end_contexts[] = $branch_context;
        $this->null_array_offsets_read = $this->null_array_offsets_read === null
            ? $branch_context->null_array_offsets_read
            : array_intersect_key($this->null_array_offsets_read, $branch_context->null_array_offsets_read);
    }
}
