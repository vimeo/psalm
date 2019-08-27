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
     * @var array<string, bool>
     */
    public $cond_assigned_var_ids;

    /**
     * @param array<string, bool>   $cond_referenced_var_ids
     * @param array<string, bool>   $cond_assigned_var_ids
     */
    public function __construct(
        Context $if_context,
        Context $original_context,
        array $cond_referenced_var_ids,
        array $cond_assigned_var_ids
    ) {
        $this->if_context = $if_context;
        $this->original_context = $original_context;
        $this->cond_referenced_var_ids = $cond_referenced_var_ids;
        $this->cond_assigned_var_ids = $cond_assigned_var_ids;
    }
}
