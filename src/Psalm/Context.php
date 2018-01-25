<?php
namespace Psalm;

use PhpParser;
use Psalm\Checker\StatementsChecker;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

class Context
{
    /**
     * @var array<string, Type\Union>
     */
    public $vars_in_scope = [];

    /**
     * @var array<string, bool>
     */
    public $vars_possibly_in_scope = [];

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
     * Whether or not we're inside an isset call
     *
     * Inside isssets Psalm is more lenient about certain things
     *
     * @var bool
     */
    public $inside_isset = false;

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
     * Stored to prevent re-analysing methods when checking for initialised properties
     *
     * @var array<string, bool>|null
     */
    public $initialized_methods = null;

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
    public $referenced_var_ids = [];

    /**
     * A list of variables that have never been referenced
     *
     * @var array<string, CodeLocation>
     */
    public $unreferenced_vars = [];

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
    public $assigned_var_ids = [];

    /**
     * @var bool
     */
    public $is_global = false;

    /**
     * @var array<string, bool>
     */
    public $protected_var_ids = [];

    /**
     * If we've branched from the main scope, a byte offset for where that branch happened
     *
     * @var int|null
     */
    public $branch_point;

    /**
     * If we're inside case statements we allow continue; statements as an alias of break;
     *
     * @var bool
     */
    public $inside_case = false;

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
            $type = clone $type;
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
                    if ((!$new_type || $old_type->getId() !== $new_type->getId())
                        && ($new_type || count($context_type->getTypes()) > 1)
                    ) {
                        $context_type->substitute($old_type, $new_type);

                        if ($new_type && $new_type->from_docblock) {
                            $context_type->setFromDocblock();
                        }

                        $updated_vars[$var] = true;
                    }
                }
            }
        }
    }

    /**
     * @param  array<string, Type\Union> $vars_in_scope
     *
     * @return array<string,Type\Union>
     */
    public function getRedefinedVars(array $vars_in_scope)
    {
        $redefined_vars = [];

        foreach ($vars_in_scope as $var => $context_type) {
            if (!isset($this->vars_in_scope[$var])) {
                continue;
            }

            $this_var = $this->vars_in_scope[$var];

            if (!$this_var->failed_reconciliation
                && !$this_var->isEmpty()
                && !$context_type->isEmpty()
                && $this_var->getId() !== $context_type->getId()
            ) {
                $redefined_vars[$var] = $this_var;
            }
        }

        return $redefined_vars;
    }

    /**
     * @return void
     */
    public function inferType(
        PhpParser\Node\Expr $expr,
        FunctionLikeStorage $function_storage,
        Type\Union $inferred_type
    ) {
        if (!isset($expr->inferredType)) {
            return;
        }

        $expr_type = $expr->inferredType;

        if (($expr_type->isMixed() || $expr_type->getId() === $inferred_type->getId())
            && $expr instanceof PhpParser\Node\Expr\Variable
            && is_string($expr->name)
            && !isset($this->assigned_var_ids['$' . $expr->name])
            && array_key_exists($expr->name, $function_storage->param_types)
            && !$function_storage->param_types[$expr->name]
        ) {
            if (isset($this->possible_param_types[$expr->name])) {
                $this->possible_param_types[$expr->name] = Type::combineUnionTypes(
                    $this->possible_param_types[$expr->name],
                    $inferred_type
                );
            } else {
                $this->possible_param_types[$expr->name] = $inferred_type;
                $this->vars_in_scope['$' . $expr->name] = clone $inferred_type;
            }
        }
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
                $original_context->vars_in_scope[$var_id]->getId() !== $context_type->getId()
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
            $this->referenced_var_ids[$remove_var_id],
            $this->vars_possibly_in_scope[$remove_var_id]
        );

        if (isset($this->vars_in_scope[$remove_var_id])) {
            $existing_type = $this->vars_in_scope[$remove_var_id];
            unset($this->vars_in_scope[$remove_var_id]);

            $this->removeDescendents($remove_var_id, $existing_type);
        }
    }

    /**
     * @param  string[]             $changed_var_ids
     *
     * @return void
     */
    public function removeReconciledClauses(array $changed_var_ids)
    {
        $this->clauses = array_filter(
            $this->clauses,
            /** @return bool */
            function (Clause $c) use ($changed_var_ids) {
                return count($c->possibilities) > 1
                    || !in_array(array_keys($c->possibilities)[0], $changed_var_ids, true);
            }
        );
    }

    /**
     * @param  string                 $remove_var_id
     * @param  Clause[]               $clauses
     * @param  Union|null             $new_type
     * @param  StatementsChecker|null $statements_checker
     *
     * @return array<int, Clause>
     */
    public static function filterClauses(
        $remove_var_id,
        array $clauses,
        Union $new_type = null,
        StatementsChecker $statements_checker = null
    ) {
        $new_type_string = $new_type ? $new_type->getId() : '';

        $clauses_to_keep = [];

        foreach ($clauses as $clause) {
            \Psalm\Checker\AlgebraChecker::calculateNegation($clause);

            $quoted_remove_var_id = preg_quote($remove_var_id);

            foreach ($clause->possibilities as $var_id => $_) {
                if (preg_match('/^' . $quoted_remove_var_id . '[\[\-]/', $var_id)) {
                    break 2;
                }
            }

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
                // by the new type
                foreach ($clause->possibilities[$remove_var_id] as $type) {
                    // empty and !empty are not definitive for arrays and scalar types
                    if (($type === '!falsy' || $type === 'falsy') &&
                        ($new_type->hasArray() || $new_type->hasNumericType())
                    ) {
                        $type_changed = true;
                        break;
                    }

                    $result_type = Reconciler::reconcileTypes(
                        $type,
                        clone $new_type,
                        null,
                        $statements_checker,
                        null,
                        [],
                        $failed_reconciliation
                    );

                    if ($result_type->getId() !== $new_type_string) {
                        $type_changed = true;
                        break;
                    }
                }

                if (!$type_changed) {
                    $clauses_to_keep[] = $clause;
                }
            }
        }

        return $clauses_to_keep;
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
        $this->clauses = self::filterClauses($remove_var_id, $this->clauses, $new_type, $statements_checker);

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

        if ($this->clauses) {
            $this->removeVarFromConflictingClauses(
                $remove_var_id,
                $existing_type->isMixed()
                    || ($new_type && $existing_type->from_docblock !== $new_type->from_docblock)
                    ? null
                    : $new_type,
                $statements_checker
            );
        }

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
        if (!$var_name ||
            (!isset($this->vars_possibly_in_scope[$var_name]) &&
                !isset($this->vars_in_scope[$var_name]))
        ) {
            return false;
        }

        $stripped_var = preg_replace('/(->|\[).*$/', '', $var_name);

        if ($stripped_var[0] === '$' && $stripped_var !== '$this') {
            $this->referenced_var_ids[$var_name] = true;

            if ($this->collect_references) {
                unset($this->unreferenced_vars[$var_name]);
            }
        }

        return isset($this->vars_in_scope[$var_name]);
    }
}
