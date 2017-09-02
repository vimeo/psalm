<?php
namespace Psalm;

use Psalm\Checker\StatementsChecker;
use Psalm\Type\Union;

class Context
{
    /**
     * @var array<string, Type\Union>
     */
    public $vars_in_scope = [];

    /**
     * @var array<string, bool|string>
     */
    public $vars_possibly_in_scope = [];

    /**
     * @var bool
     */
    public $inside_loop = false;

    /**
     * Whether or not we're inside the conditional of an if/where etc.
     *
     * This changes whether or not the context is cloned
     *
     * @var bool
     */
    public $inside_conditional = false;

    /**
     * Whether or not we're inside a __construct function
     *
     * @var bool
     */
    public $inside_constructor = false;

    /**
     * @var ?CodeLocation
     */
    public $include_location = null;

    /**
     * @var string|null
     */
    public $self;

    /**
     * @var string|null
     */
    public $parent;

    /**
     * @var bool
     */
    public $check_classes = true;

    /**
     * @var bool
     */
    public $check_variables = true;

    /**
     * @var bool
     */
    public $check_methods = true;

    /**
     * @var bool
     */
    public $check_consts = true;

    /**
     * @var bool
     */
    public $check_functions = true;

    /**
     * A list of classes checked with class_exists
     *
     * @var array<string,bool>
     */
    private $phantom_classes = [];

    /**
     * A list of clauses in Conjunctive Normal Form
     *
     * @var array<int, Clause>
     */
    public $clauses = [];

    /**
     * Whether or not to do a deep analysis and collect mutations to this context
     *
     * @var bool
     */
    public $collect_mutations = false;

    /**
     * Whether or not to do a deep analysis and collect initializations from private methods
     *
     * @var bool
     */
    public $collect_initializations = false;

    /**
     * @var array<string, Type\Union>
     */
    public $constants = [];

    /**
     * Whether or not to track how many times a variable is used
     *
     * @var bool
     */
    public $collect_references = false;

    /**
     * A list of variables that have been referenced
     *
     * @var array<string, bool>
     */
    public $referenced_vars = [];

    /**
     * A list of variables that have been passed by reference (where we know their type)
     *
     * @var array<string, \Psalm\ReferenceConstraint>|null
     */
    public $byref_constraints;

    /**
     * If this context inherits from a context, it is here
     *
     * @var Context|null
     */
    public $parent_context;

    /**
     * @var array<string, Type\Union>
     */
    public $possible_param_types = [];

    /**
     * A list of vars that have been assigned to
     *
     * @var array<string, bool>
     */
    public $assigned_vars = [];

    /**
     * @param string|null $self
     */
    public function __construct($self = null)
    {
        $this->self = $self;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        foreach ($this->vars_in_scope as &$type) {
            if ($type) {
                $type = clone $type;
            }
        }

        foreach ($this->clauses as &$clause) {
            $clause = clone $clause;
        }

        foreach ($this->constants as &$constant) {
            $constant = clone $constant;
        }
    }

    /**
     * Updates the parent context, looking at the changes within a block and then applying those changes, where
     * necessary, to the parent context
     *
     * @param  Context     $start_context
     * @param  Context     $end_context
     * @param  bool        $has_leaving_statements   whether or not the parent scope is abandoned between
     *                                               $start_context and $end_context
     * @param  array       $vars_to_update
     * @param  array       $updated_vars
     *
     * @return void
     */
    public function update(
        Context $start_context,
        Context $end_context,
        $has_leaving_statements,
        array $vars_to_update,
        array &$updated_vars
    ) {
        foreach ($this->vars_in_scope as $var => &$context_type) {
            if (isset($start_context->vars_in_scope[$var])) {
                $old_type = $start_context->vars_in_scope[$var];

                // this is only true if there was some sort of type negation
                if (in_array($var, $vars_to_update, true)) {
                    // if we're leaving, we're effectively deleting the possibility of the if types
                    $new_type = !$has_leaving_statements && $end_context->hasVariable($var)
                        ? $end_context->vars_in_scope[$var]
                        : null;

                    // if the type changed within the block of statements, process the replacement
                    // also never allow ourselves to remove all types from a union
                    if ((string)$old_type !== (string)$new_type && ($new_type || count($context_type->types) > 1)) {
                        $context_type->substitute($old_type, $new_type);
                        $updated_vars[$var] = true;
                    }
                }
            }
        }
    }

    /**
     * @param  Context $original_context
     *
     * @return array<string,Type\Union>
     */
    public function getRedefinedVars(Context $original_context)
    {
        $redefined_vars = [];

        foreach ($original_context->vars_in_scope as $var => $context_type) {
            if (!isset($this->vars_in_scope[$var])) {
                continue;
            }

            $this_var = $this->vars_in_scope[$var];

            if (!$this_var->failed_reconciliation &&
                !$this_var->isEmpty() &&
                !$context_type->isEmpty() &&
                (string)$this_var !== (string)$context_type
            ) {
                $redefined_vars[$var] = $this_var;
            }
        }

        return $redefined_vars;
    }

    /**
     * @param  Context $original_context
     * @param  Context $new_context
     *
     * @return array<int, string>
     */
    public static function getNewOrUpdatedVarIds(Context $original_context, Context $new_context)
    {
        $redefined_var_ids = [];

        foreach ($new_context->vars_in_scope as $var_id => $context_type) {
            if (!isset($original_context->vars_in_scope[$var_id]) ||
                (string)$original_context->vars_in_scope[$var_id] !== (string)$context_type
            ) {
                $redefined_var_ids[] = $var_id;
            }
        }

        return $redefined_var_ids;
    }

    /**
     * @param  string $remove_var_id
     *
     * @return void
     */
    public function remove($remove_var_id)
    {
        unset(
            $this->referenced_vars[$remove_var_id],
            $this->vars_possibly_in_scope[$remove_var_id]
        );

        if (isset($this->vars_in_scope[$remove_var_id])) {
            $existing_type = $this->vars_in_scope[$remove_var_id];
            unset($this->vars_in_scope[$remove_var_id]);

            $this->removeDescendents($remove_var_id, $existing_type);
        }
    }

    /**
     * @param  string               $remove_var_id
     * @param  Union|null           $new_type
     * @param  ?StatementsChecker   $statements_checker
     *
     * @return void
     */
    public function removeVarFromConflictingClauses(
        $remove_var_id,
        Union $new_type = null,
        StatementsChecker $statements_checker = null
    ) {
        $clauses_to_keep = [];

        $new_type_string = (string)$new_type;

        foreach ($this->clauses as $clause) {
            \Psalm\Checker\AlgebraChecker::calculateNegation($clause);

            if (!isset($clause->possibilities[$remove_var_id]) ||
                $clause->possibilities[$remove_var_id] === [$new_type_string]
            ) {
                $clauses_to_keep[] = $clause;
            } elseif ($statements_checker &&
                $new_type &&
                !$new_type->isMixed()
            ) {
                $type_changed = false;

                // if the clause contains any possibilities that would be altered
                foreach ($clause->possibilities[$remove_var_id] as $type) {
                    // empty and !empty are not definitive for arrays and scalar types
                    if (($type === '!empty' || $type === 'empty') &&
                        ($new_type->hasArray() || $new_type->hasNumericType())
                    ) {
                        $type_changed = true;
                        break;
                    }

                    $result_type = \Psalm\Checker\TypeChecker::reconcileTypes(
                        $type,
                        clone $new_type,
                        null,
                        $statements_checker,
                        null,
                        [],
                        $failed_reconciliation
                    );

                    if ((string)$result_type !== $new_type_string) {
                        $type_changed = true;
                        break;
                    }
                }

                if (!$type_changed) {
                    $clauses_to_keep[] = $clause;
                }
            }
        }

        $this->clauses = $clauses_to_keep;

        if ($this->parent_context) {
            $this->parent_context->removeVarFromConflictingClauses($remove_var_id);
        }
    }

    /**
     * @param  string                 $remove_var_id
     * @param  \Psalm\Type\Union|null $existing_type
     * @param  \Psalm\Type\Union|null $new_type
     * @param  ?StatementsChecker     $statements_checker
     *
     * @return void
     */
    public function removeDescendents(
        $remove_var_id,
        Union $existing_type = null,
        Union $new_type = null,
        StatementsChecker $statements_checker = null
    ) {
        if (!$existing_type && isset($this->vars_in_scope[$remove_var_id])) {
            $existing_type = $this->vars_in_scope[$remove_var_id];
        }

        if (!$existing_type) {
            return;
        }

        $this->removeVarFromConflictingClauses(
            $remove_var_id,
            $existing_type->isMixed() ? null : $new_type,
            $statements_checker
        );

        if ($existing_type->hasArray() || $existing_type->isMixed()) {
            $vars_to_remove = [];

            foreach ($this->vars_in_scope as $var_id => $_) {
                if (preg_match('/^' . preg_quote($remove_var_id, DIRECTORY_SEPARATOR) . '[\[\-]/', $var_id)) {
                    $vars_to_remove[] = $var_id;
                }
            }

            foreach ($vars_to_remove as $var_id) {
                unset($this->vars_in_scope[$var_id]);
            }
        }
    }

    /**
     * @return void
     */
    public function removeAllObjectVars()
    {
        $vars_to_remove = [];

        foreach ($this->vars_in_scope as $var_id => $_) {
            if (strpos($var_id, '->') !== false || strpos($var_id, '::') !== false) {
                $vars_to_remove[] = $var_id;
            }
        }

        if (!$vars_to_remove) {
            return;
        }

        foreach ($vars_to_remove as $var_id) {
            unset($this->vars_in_scope[$var_id]);
        }

        $clauses_to_keep = [];

        foreach ($this->clauses as $clause) {
            $abandon_clause = false;

            foreach (array_keys($clause->possibilities) as $key) {
                if (strpos($key, '->') !== false || strpos($key, '::') !== false) {
                    $abandon_clause = true;
                    break;
                }
            }

            if (!$abandon_clause) {
                $clauses_to_keep[] = $clause;
            }
        }

        $this->clauses = $clauses_to_keep;

        if ($this->parent_context) {
            $this->parent_context->removeAllObjectVars();
        }
    }

    /**
     * @param   Context $op_context
     *
     * @return  void
     */
    public function updateChecks(Context $op_context)
    {
        $this->check_classes = $this->check_classes && $op_context->check_classes;
        $this->check_variables = $this->check_variables && $op_context->check_variables;
        $this->check_methods = $this->check_methods && $op_context->check_methods;
        $this->check_functions = $this->check_functions && $op_context->check_functions;
        $this->check_consts = $this->check_consts && $op_context->check_consts;
    }

    /**
     * @param   string $class_name
     *
     * @return  bool
     */
    public function isPhantomClass($class_name)
    {
        return isset($this->phantom_classes[strtolower($class_name)]);
    }

    /**
     * @param   string $class_name
     *
     * @return  void
     */
    public function addPhantomClass($class_name)
    {
        $this->phantom_classes[strtolower($class_name)] = true;
    }

    /**
     * @return  array<string, bool>
     */
    public function getPhantomClasses()
    {
        return $this->phantom_classes;
    }

    /**
     * @param  string|null  $var_name
     *
     * @return bool
     */
    public function hasVariable($var_name)
    {
        if ($this->collect_references) {
            if (!$var_name ||
                (!isset($this->vars_possibly_in_scope[$var_name]) &&
                    !isset($this->vars_in_scope[$var_name]))
            ) {
                return false;
            }

            $stripped_var = preg_replace('/(->|\[).*$/', '', $var_name);

            if ($stripped_var[0] === '$' && $stripped_var !== '$this') {
                $this->referenced_vars[$var_name] = true;
            }

            return isset($this->vars_in_scope[$var_name]);
        }

        return $var_name && isset($this->vars_in_scope[$var_name]);
    }
}
