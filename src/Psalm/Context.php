<?php
namespace Psalm;

class Context
{
    /**
     * @var array<string,Type\Union>
     */
    public $vars_in_scope = [];

    /**
     * @var array<string,bool>
     */
    public $vars_possibly_in_scope = [];

    /**
     * @var boolean
     */
    public $in_loop = false;

    /**
     * @var string|null
     */
    public $self;

    /**
     * @var string|null
     */
    public $parent;

    /**
     * @var string
     */
    public $file_name;

    /**
     * @var boolean
     */
    public $check_classes = true;

    /**
     * @var boolean
     */
    public $check_variables = true;

    /**
     * @var boolean
     */
    public $check_methods = true;

    /**
     * @var boolean
     */
    public $check_consts = true;

    /**
     * @var boolean
     */
    public $check_functions = true;

    /**
     * A list of classes checked with class_exists
     *
     * @var array<string,bool>
     */
    protected $phantom_classes = [];

    /**
     * A list of clauses in Conjunctive Normal Form
     *
     * @var array<Clause>
     */
    public $clauses = [];

    /**
     * @param string      $file_name
     * @param string|null $self
     */
    public function __construct($file_name, $self = null)
    {
        $this->file_name = $file_name;
        $this->self = $self;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        foreach ($this->vars_in_scope as $key => &$type) {
            if ($type) {
                $type = clone $type;
            }
        }

        foreach ($this->clauses as &$clause) {
            $clause = clone $clause;
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
                if (in_array($var, $vars_to_update)) {
                    // if we're leaving, we're effectively deleting the possibility of the if types
                    $new_type = !$has_leaving_statements && isset($end_context->vars_in_scope[$var])
                        ? $end_context->vars_in_scope[$var]
                        : null;

                    // if the type changed within the block of statements, process the replacement
                    if ((string)$old_type !== (string)$new_type) {
                        $context_type->substitute($old_type, $new_type);
                        $updated_vars[$var] = true;
                    }
                }
            }
        }
    }

    /**
     * @param  Context $original_context
     * @param  Context $new_context
     * @return array<string,Type\Union>
     */
    public static function getRedefinedVars(Context $original_context, Context $new_context)
    {
        $redefined_vars = [];

        foreach ($original_context->vars_in_scope as $var => $context_type) {
            if (isset($new_context->vars_in_scope[$var]) &&
                (string)$new_context->vars_in_scope[$var] !== (string)$context_type
            ) {
                $redefined_vars[$var] = $new_context->vars_in_scope[$var];
            }
        }

        return $redefined_vars;
    }

    /**
     * @param  string $remove_var_id
     * @return void
     */
    public function remove($remove_var_id)
    {
        if (isset($this->vars_in_scope[$remove_var_id])) {
            $type = $this->vars_in_scope[$remove_var_id];
            unset($this->vars_in_scope[$remove_var_id]);

            $this->removeDescendents($remove_var_id, $type);
        }
    }

    /**
     * @param  string                 $remove_var_id
     * @param  \Psalm\Type\Union|null $type
     * @return void
     */
    public function removeDescendents($remove_var_id, \Psalm\Type\Union $type = null)
    {
        if (!$type && isset($this->vars_in_scope[$remove_var_id])) {
            $type = $this->vars_in_scope[$remove_var_id];
        }

        if (!$type) {
            return;
        }

        $clauses_to_keep = [];

        foreach ($this->clauses as $clause) {
            if (!isset($clause->possibilities[$remove_var_id])) {
                $clauses_to_keep[] = $clause;
            }
        }

        $this->clauses = $clauses_to_keep;

        if ($type->hasArray() || $type->isMixed()) {
            $vars_to_remove = [];

            foreach ($this->vars_in_scope as $var_id => $context_type) {
                if (preg_match('/^' . preg_quote($remove_var_id, '/') . '[\[\-]/', $var_id)) {
                    $vars_to_remove[] = $var_id;
                }
            }

            foreach ($vars_to_remove as $var_id) {
                unset($this->vars_in_scope[$var_id]);
            }
        }
    }

    /**
     * @param   Context $op_context
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
     * @return  bool
     */
    public function isPhantomClass($class_name)
    {
        return isset($this->phantom_classes[$class_name]);
    }

    /**
     * @param   string $class_name
     * @return  void
     */
    public function addPhantomClass($class_name)
    {
        $this->phantom_classes[$class_name] = true;
    }
}
