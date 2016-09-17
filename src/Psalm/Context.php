<?php

namespace Psalm;

use PhpParser;
use Psalm\Checker\StatementsChecker;

class Context
{
    /** @var array<string, Type\Union> */
    public $vars_in_scope = [];

    public $vars_possibly_in_scope = [];

    /** @var boolean */
    public $in_loop = false;

    /** @var string|null */
    public $self;

    /** @var string|null */
    public $parent;

    /** @var string */
    public $file_name;

    /**
     * @param string      $file_name
     * @param string|null $self
     */
    public function __construct($file_name, $self = null)
    {
        $this->file_name = $file_name;
        $this->self = $self;
    }

    public function __clone()
    {
        foreach ($this->vars_in_scope as $key => &$type) {
            $type = clone $type;
        }
    }

    /**
     * Updates the parent context, looking at the changes within a block
     * and then applying those changes, where necessary, to the parent context
     *
     * @param  Context $start_context
     * @param  Context $end_context
     * @param  bool    $has_leaving_statements   whether or not the parent scope is abandoned between $start_context and $end_context
     * @return void
     */
    public function update(Context $start_context, Context $end_context, $has_leaving_statements, array $vars_to_update, array &$updated_vars)
    {
        foreach ($this->vars_in_scope as $var => &$context_type) {
            $old_type = $start_context->vars_in_scope[$var];

            // this is only true if there was some sort of type negation
            if (in_array($var, $vars_to_update)) {
                // if we're leaving, we're effectively deleting the possibility of the if types
                $new_type = !$has_leaving_statements ? $end_context->vars_in_scope[$var] : null;

                // if the type changed within the block of statements, process the replacement
                if ((string)$old_type !== (string)$new_type) {
                    $context_type->substitute($old_type, $new_type);
                    $updated_vars[$var] = true;
                }
            }
        }
    }

    public static function getRedefinedVars(Context $original_context, Context $new_context)
    {
        $redefined_vars = [];

        foreach ($original_context->vars_in_scope as $var => $context_type) {
            if (isset($new_context->vars_in_scope[$var]) && (string)$new_context->vars_in_scope[$var] !== (string)$context_type) {
                $redefined_vars[$var] = $new_context->vars_in_scope[$var];
            }
        }

        return $redefined_vars;
    }

    public function remove($remove_var_id)
    {
        if (isset($this->vars_in_scope[$remove_var_id])) {
            $type = $this->vars_in_scope[$remove_var_id];
            unset($this->vars_in_scope[$remove_var_id]);

            $this->removeDescendents($remove_var_id, $type);
        }
    }

    public function removeDescendents($remove_var_id, \Psalm\Type\Union $type = null)
    {
        if (!$type && isset($this->vars_in_scope[$remove_var_id])) {
            $type = $this->vars_in_scope[$remove_var_id];
        }

        if (!$type) {
            return;
        }

        if ($type->hasArray() || $type->hasObjectType() || $type->isMixed()) {
            $vars_to_remove = [];

            foreach ($this->vars_in_scope as $var_id => $context_type) {
                if (preg_match('/^' . preg_quote($var_id, '/') . '[\[\-]/', $var_id)) {
                    $vars_to_remove[] = $var_id;
                }
            }

            foreach ($vars_to_remove as $var_id) {
                unset($this->vars_in_scope[$var_id]);
            }
        }
    }
}
