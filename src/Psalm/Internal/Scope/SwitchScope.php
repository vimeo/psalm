<?php
namespace Psalm\Internal\Scope;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Internal\Clause;
use Psalm\Type;

/**
 * @internal
 */
class SwitchScope
{
    /**
     * @var array<string, Type\Union>|null
     */
    public $new_vars_in_scope = null;

    /**
     * @var array<string, bool>
     */
    public $new_vars_possibly_in_scope = [];

    /**
     * @var array<string, Type\Union>|null
     */
    public $redefined_vars = null;

    /**
     * @var array<string, Type\Union>|null
     */
    public $possibly_redefined_vars = null;

    /**
     * @var array<PhpParser\Node\Stmt>
     */
    public $leftover_statements = [];

    /**
     * @var PhpParser\Node\Expr|null
     */
    public $leftover_case_equality_expr = null;

    /**
     * @var array<int, Clause>
     */
    public $negated_clauses = [];

    /**
     * @var array<string, array<string, CodeLocation>>
     */
    public $new_unreferenced_vars = [];

    /**
     * @var array<string, bool>|null
     */
    public $new_assigned_var_ids = null;

    /**
     * @var array<string, bool>
     */
    public $new_possibly_assigned_var_ids = [];
}
