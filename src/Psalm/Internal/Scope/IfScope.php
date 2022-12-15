<?php

namespace Psalm\Internal\Scope;

use Psalm\Context;
use Psalm\Internal\Clause;
use Psalm\Storage\Assertion;
use Psalm\Type\Union;

/**
 * @internal
 */
class IfScope
{
    /**
     * @var array<string, Union>|null
     */
    public ?array $new_vars = null;

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

    public ?Context $post_leaving_if_context = null;
}
