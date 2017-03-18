<?php
namespace Psalm;

class IfScope
{
    /**
     * @var array<string, Type\Union>|null
     */
    public $new_vars = null;

    /**
     * @var array<string, boolean|string>
     */
    public $new_vars_possibly_in_scope = [];

     /**
     * @var array<string, Type\Union>|null
     */
    public $forced_new_vars = null;

    /**
     * @var array<string, Type\Union>|null
     */
    public $redefined_vars = null;

    /**
     * @var array<string, Type\Union>
     */
    public $possibly_redefined_vars = [];

    /**
     * @var array<string, Type\Union>|null
     */
    public $redefined_loop_vars = null;

    /**
     * @var array<string, Type\Union>
     */
    public $possibly_redefined_loop_vars = [];

    /**
     * @var array<string, bool>
     */
    public $updated_vars = [];

    /**
     * @var array<string, string>
     */
    public $negated_types = [];

    /**
     * @var array<string, string>|null
     */
    public $negatable_if_types = null;

    /**
     * @var Context|null
     */
    public $loop_context;

    /**
     * @var bool
     */
    public $has_elseifs;

    /**
     * @var array<int, Clause>
     */
    public $negated_clauses;

    /**
     * @var array<int, Clause>
     */
    public $reasonable_clauses = [];
}
