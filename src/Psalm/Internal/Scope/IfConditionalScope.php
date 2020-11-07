<?php
namespace Psalm\Internal\Scope;

use Psalm\Context;

/**
 * @internal
 */
class IfConditionalScope
{
    public $if_context;

    public $original_context;

    /**
     * @var array<string, bool>
     */
    public $cond_referenced_var_ids;

    /**
     * @var array<string, int>
     */
    public $assigned_in_conditional_var_ids;

    /** @var list<\Psalm\Internal\Clause> */
    public $entry_clauses;

    /**
     * @param array<string, bool>   $cond_referenced_var_ids
     * @param array<string, int>   $assigned_in_conditional_var_ids
     * @param list<\Psalm\Internal\Clause> $entry_clauses
     */
    public function __construct(
        Context $if_context,
        Context $original_context,
        array $cond_referenced_var_ids,
        array $assigned_in_conditional_var_ids,
        array $entry_clauses
    ) {
        $this->if_context = $if_context;
        $this->original_context = $original_context;
        $this->cond_referenced_var_ids = $cond_referenced_var_ids;
        $this->assigned_in_conditional_var_ids = $assigned_in_conditional_var_ids;
        $this->entry_clauses = $entry_clauses;
    }
}
