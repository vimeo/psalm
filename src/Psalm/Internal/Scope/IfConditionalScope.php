<?php

declare(strict_types=1);

namespace Psalm\Internal\Scope;

use Psalm\Context;
use Psalm\Internal\Clause;

/**
 * @internal
 */
final class IfConditionalScope
{
    public Context $if_context;

    public Context $post_if_context;

    /**
     * @var array<string, bool>
     */
    public array $cond_referenced_var_ids;

    /**
     * @var array<string, int>
     */
    public array $assigned_in_conditional_var_ids;

    /**
     * @param array<string, bool>   $cond_referenced_var_ids
     * @param array<string, int>   $assigned_in_conditional_var_ids
     */
    public function __construct(
        Context $if_context,
        Context $post_if_context,
        array $cond_referenced_var_ids,
        array $assigned_in_conditional_var_ids,
    ) {
        $this->if_context = $if_context;
        $this->post_if_context = $post_if_context;
        $this->cond_referenced_var_ids = $cond_referenced_var_ids;
        $this->assigned_in_conditional_var_ids = $assigned_in_conditional_var_ids;
    }
}
