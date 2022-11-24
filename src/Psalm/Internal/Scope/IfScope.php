<?php

namespace Psalm\Internal\Scope;

use Psalm\Context;
use Psalm\Internal\Clause;
use Psalm\Internal\ClauseConjunction;
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
    public $new_vars;

    /**
     * @var array<string, bool>
     */
    public $new_vars_possibly_in_scope = [];

    /**
     * @var array<string, Union>|null
     */
    public $redefined_vars;

    /**
     * @var array<string, int>|null
     */
    public $assigned_var_ids;

    /**
     * @var array<string, bool>
     */
    public $possibly_assigned_var_ids = [];

    /**
     * @var array<string, Union>
     */
    public $possibly_redefined_vars = [];

    /**
     * @var array<string, bool>
     */
    public $updated_vars = [];

    /**
     * @var array<string, list<array<int, Assertion>>>
     */
    public $negated_types = [];

    /**
     * @var array<string, bool>
     */
    public $if_cond_changed_var_ids = [];

    /**
     * @var array<string, string>|null
     */
    public $negatable_if_types;

    /**
     * @var ClauseConjunction
     */
    public $negated_clauses;

    /**
     * These are the set of clauses that could be applied after the `if`
     * statement, if the `if` statement contains branches with leaving statements,
     * and the else leaves too
     *
     * @var ClauseConjunction
     */
    public $reasonable_clauses;

    /**
     * @var string[]
     */
    public $if_actions = [];

    /**
     * @var string[]
     */
    public $final_actions = [];

    /**
     * @var ?Context
     */
    public $post_leaving_if_context;

    public function __construct()
    {
        $this->reasonable_clauses = new ClauseConjunction([]);
        $this->negated_clauses = new ClauseConjunction([]);
    }
}
