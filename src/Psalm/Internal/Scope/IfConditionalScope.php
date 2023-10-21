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
    /**
     * @param array<string, bool>   $cond_referenced_var_ids
     * @param array<string, int>   $assigned_in_conditional_var_ids
     * @param list<Clause> $entry_clauses
     */
    public function __construct(
        public Context $if_context,
        public Context $post_if_context,
        public array $cond_referenced_var_ids,
        public array $assigned_in_conditional_var_ids,
        public array $entry_clauses,
    ) {
    }
}
