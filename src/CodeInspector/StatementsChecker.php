<?php

namespace CodeInspector;

use PhpParser;

use CodeInspector\IssueHandler;
use CodeInspector\Issue\ForbiddenCode;
use CodeInspector\Issue\InvalidArgument;
use CodeInspector\Issue\InvalidNamespace;
use CodeInspector\Issue\InvalidIterator;
use CodeInspector\Issue\NullReference;
use CodeInspector\Issue\ParentNotFound;
use CodeInspector\Issue\PossiblyUndefinedVariable;
use CodeInspector\Issue\InvalidArrayAssignment;
use CodeInspector\Issue\InvalidArrayAccess;
use CodeInspector\Issue\InvalidScope;
use CodeInspector\Issue\InvalidStaticInvocation;
use CodeInspector\Issue\InvalidStaticVariable;
use CodeInspector\Issue\FailedTypeResolution;
use CodeInspector\Issue\UndefinedConstant;
use CodeInspector\Issue\UndefinedFunction;
use CodeInspector\Issue\UndefinedProperty;
use CodeInspector\Issue\UndefinedVariable;

class StatementsChecker
{
    protected $_stmts;

    protected $_source;
    protected $_all_vars = [];
    protected $_warn_vars = [];
    protected $_check_classes = true;
    protected $_check_variables = true;
    protected $_check_methods = true;
    protected $_check_consts = true;
    protected $_check_functions = true;
    protected $_class_name;
    protected $_class_extends;

    protected $_namespace;
    protected $_aliased_classes;
    protected $_file_name;
    protected $_is_static;
    protected $_absolute_class;
    protected $_type_checker;

    protected $_available_functions = [];

    protected $_require_file_name = null;

    protected static $_method_call_index = [];
    protected static $_existing_functions = [];
    protected static $_reflection_functions = [];
    protected static $_this_assignments = [];
    protected static $_this_calls = [];

    protected static $_existing_static_vars = [];
    protected static $_existing_properties = [];
    protected static $_mock_interfaces = [];

    public function __construct(StatementsSource $source, $enforce_variable_checks = false, $check_methods = true)
    {
        $this->_source = $source;
        $this->_check_classes = true;
        $this->_check_methods = $check_methods;

        $this->_check_consts = true;

        $this->_file_name = $this->_source->getFileName();
        $this->_aliased_classes = $this->_source->getAliasedClasses();
        $this->_namespace = $this->_source->getNamespace();
        $this->_is_static = $this->_source->isStatic();
        $this->_absolute_class = $this->_source->getAbsoluteClass();
        $this->_class_name = $this->_source->getClassName();
        $this->_class_extends = $this->_source->getParentClass();

        $this->_check_variables = !Config::getInstance()->doesInheritVariables($this->_file_name) || $enforce_variable_checks;

        $this->_type_checker = new TypeChecker($source, $this);
    }

    /**
     * Checks an array of statements for validity
     *
     * @param  array<PhpParser\Node>        $stmts
     * @param  array<Type\Union>            &$context->vars_in_scope
     * @param  array                        &$context->vars_possibly_in_scope
     * @return null|false
     */
    public function check(array $stmts, Context $context, array &$for_vars_possibly_in_scope = [])
    {
        $has_returned = false;

        // register all functions first
        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $file_checker = FileChecker::getFileCheckerFromFileName($this->_file_name);
                $file_checker->registerFunction($stmt);
            }
        }

        foreach ($stmts as $stmt) {
            foreach (Config::getInstance()->getPlugins() as $plugin) {
                if ($plugin->checkStatement($stmt, $context, $this->_file_name) === false) {
                    return false;
                }
            }

            if ($has_returned && !($stmt instanceof PhpParser\Node\Stmt\Nop) && !($stmt instanceof PhpParser\Node\Stmt\InlineHTML)) {
                echo('Warning: Expressions after return/throw/continue in ' . $this->_file_name . ' on line ' . $stmt->getLine() . PHP_EOL);
                break;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $this->_checkIf($stmt, $context, $for_vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $this->_checkTryCatch($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                $this->_checkFor($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                $this->_checkForeach($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                $this->_checkWhile($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                $this->_checkDo($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Const_) {
                foreach ($stmt->consts as $const) {
                    $this->_checkExpression($const->value, $context);
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Unset_) {
                // do nothing

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                $has_returned = true;
                $this->_checkReturn($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Throw_) {
                $has_returned = true;
                $this->_checkThrow($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                $this->_checkSwitch($stmt, $context, $for_vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Break_) {
                // do nothing

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Continue_) {
                $has_returned = true;

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Static_) {
                $this->_checkStatic($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
                foreach ($stmt->exprs as $expr) {
                    $this->_checkExpression($expr, $context);
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_checker = new FunctionChecker($stmt, $this->_source);
                $function_checker->check(new Context());

            } elseif ($stmt instanceof PhpParser\Node\Expr) {
                $this->_checkExpression($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\InlineHTML) {
                // do nothing

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    $this->_aliased_classes[$use->alias] = implode('\\', $use->name->parts);
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Global_) {
                foreach ($stmt->vars as $var) {
                    if ($var instanceof PhpParser\Node\Expr\Variable) {
                        if (is_string($var->name)) {
                            $context->vars_in_scope[$var->name] = Type::getMixed();
                            $context->vars_possibly_in_scope[$var->name] = true;
                        } else {
                            $this->_checkExpression($var, $context);
                        }
                    }
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->default) {
                        $this->_checkExpression($prop->default, $context);
                    }

                    self::$_existing_static_vars[$this->_absolute_class . '::$' . $prop->name] = 1;
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {


            } elseif ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                (new ClassChecker($stmt, $this->_source, $stmt->name))->check();

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                // do nothing

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                if ($this->_namespace) {
                    if (IssueHandler::accepts(
                        new InvalidNamespace('Cannot redeclare namespace', $this->_require_file_name, $stmt->getLine())
                    )) {
                        return false;
                    }
                }

                $namespace_checker = new NamespaceChecker($stmt, $this->_source);
                $namespace_checker->check(true);
            } else {
                var_dump('Unrecognised statement in ' . $this->_file_name);
                var_dump($stmt);
            }
        }
    }

    /**
     * System of type substitution and deletion
     *
     * for example
     *
     * x: A|null
     *
     * if (x)
     *   (x: A)
     *   x = B  -- effects: remove A from the type of x, add B
     * else
     *   (x: null)
     *   x = C  -- effects: remove null from the type of x, add C
     *
     *
     * x: A|null
     *
     * if (!x)
     *   (x: null)
     *   throw new Exception -- effects: remove null from the type of x
     *
     *
     * @param  PhpParser\Node\Stmt\If_ $stmt
     * @param  array                   &$context->vars_in_scope
     * @param  array                   &$context->vars_possibly_in_scope
     * @param  array                   &$for_vars_possibly_in_scope
     * @return null|false
     */
    protected function _checkIf(PhpParser\Node\Stmt\If_ $stmt, Context $context, array &$for_vars_possibly_in_scope)
    {
        $if_context = clone $context;

        // we need to clone the current context so our ongoing updates to $context don't mess with elseif/else blocks
        $original_context = ($stmt->elseifs || $stmt->else) ? clone $context : null;

        if ($this->_checkCondition($stmt->cond, $if_context) === false) {
            return false;
        }

        $if_types = $this->_type_checker->getTypeAssertions($stmt->cond, true);

        $has_leaving_statments = ScopeChecker::doesLeaveBlock($stmt->stmts, true, true);

        // we only need to negate the if types if there are throw/return/break/continue or else/elseif blocks
        $need_to_negate_if_types = $has_leaving_statments || $stmt->elseifs || $stmt->else;

        $can_negate_if_types = !($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd);

        $negated_types = $if_types && $need_to_negate_if_types && $can_negate_if_types
                            ? TypeChecker::negateTypes($if_types)
                            : [];

        $negated_if_types = $negated_types;

        // if the if has an || in the conditional, we cannot easily reason about it
        if (!($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp) || !self::_containsBooleanOr($stmt->cond)) {
            $if_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes($if_types, $if_context->vars_in_scope, $this->_file_name, $stmt->getLine());
            if ($if_vars_in_scope_reconciled === false) {
                return false;
            }
            $if_context->vars_in_scope = $if_vars_in_scope_reconciled;
            $if_context->vars_possibly_in_scope = array_merge($if_types, $if_context->vars_possibly_in_scope);
        }

        $old_if_context = clone $if_context;
        $context->vars_possibly_in_scope = array_merge($if_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);

        if ($this->check($stmt->stmts, $if_context, $for_vars_possibly_in_scope) === false) {
            return false;
        }

        $new_vars = null;
        $new_vars_possibly_in_scope = [];
        $redefined_vars = null;
        $possibly_redefined_vars = [];

        $updated_vars = [];

        $mic_drop = false;

        if (count($stmt->stmts)) {
            if (!$has_leaving_statments) {
                $new_vars = array_diff_key($if_context->vars_in_scope, $context->vars_in_scope);

                $redefined_vars = Context::getRedefinedVars($context, $if_context);
                $possibly_redefined_vars = $redefined_vars;
            }
            elseif (!$stmt->else && !$stmt->elseifs && $negated_types) {
                $context_vars_reconciled = TypeChecker::reconcileKeyedTypes($negated_types, $context->vars_in_scope, $this->_file_name, $stmt->getLine());
                if ($context_vars_reconciled === false) {
                    return false;
                }
                $context->vars_in_scope = $context_vars_reconciled;
                $mic_drop = true;
            }

            // update the parent context as necessary, but only if we can safely reason about type negation
            if ($can_negate_if_types && !$mic_drop) {
                $context->update($old_if_context, $if_context, $has_leaving_statments, $updated_vars);
            }

            $has_ending_statments = ScopeChecker::doesReturnOrThrow($stmt->stmts);

            if (!$has_ending_statments) {
                $vars = array_diff_key($if_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);

                // if we're leaving this block, add vars to outer for loop scope
                if ($has_leaving_statments) {
                    $for_vars_possibly_in_scope = array_merge($for_vars_possibly_in_scope, $vars);
                }
                else {
                    $new_vars_possibly_in_scope = $vars;
                }
            }
        }

        foreach ($stmt->elseifs as $elseif) {
            $elseif_context = clone $original_context;

            if ($negated_types) {
                $elseif_vars_reconciled = TypeChecker::reconcileKeyedTypes($negated_types, $elseif_context->vars_in_scope, $this->_file_name, $stmt->getLine());
                if ($elseif_vars_reconciled === false) {
                    return false;
                }
                $elseif_context->vars_in_scope = $elseif_vars_reconciled;
            }

            $elseif_types = $this->_type_checker->getTypeAssertions($elseif->cond, true);

            $can_negate_elseif_types = !($elseif->cond instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd);

            $negated_elseif_types = $elseif_types && $can_negate_elseif_types
                                    ? TypeChecker::negateTypes($elseif_types)
                                    : [];

            $negated_types = array_merge($negated_types, $negated_elseif_types);

            // if the elseif has an || in the conditional, we cannot easily reason about it
            if (!($elseif->cond instanceof PhpParser\Node\Expr\BinaryOp) || !self::_containsBooleanOr($elseif->cond)) {
                $elseif_vars_reconciled = TypeChecker::reconcileKeyedTypes($elseif_types, $elseif_context->vars_in_scope, $this->_file_name, $stmt->getLine());

                if ($elseif_vars_reconciled === false) {
                    return false;
                }

                $elseif_context->vars_in_scope = $elseif_vars_reconciled;
            }

            // check the elseif
            if ($this->_checkCondition($elseif->cond, $elseif_context) === false) {
                return false;
            }

            $old_elseif_context = clone $elseif_context;

            if ($this->check($elseif->stmts, $elseif_context, $for_vars_possibly_in_scope) === false) {
                return false;
            }

            if (count($elseif->stmts)) {
                $has_leaving_statements = ScopeChecker::doesLeaveBlock($elseif->stmts, true, true);

                if (!$has_leaving_statements) {
                    // update the parent context as necessary
                    $elseif_redefined_vars = Context::getRedefinedVars($original_context, $elseif_context);

                    if ($redefined_vars === null) {
                        $redefined_vars = $elseif_redefined_vars;
                        $possibly_redefined_vars = $redefined_vars;
                    }
                    else {
                        foreach ($redefined_vars as $redefined_var => $type) {
                            if (!isset($elseif_redefined_vars[$redefined_var])) {
                                unset($redefined_vars[$redefined_var]);
                            }
                            else {
                                $redefined_vars[$redefined_var] = Type::combineUnionTypes($elseif_redefined_vars[$redefined_var], $type);
                            }
                        }

                        foreach ($elseif_redefined_vars as $var => $type) {
                            if ($type->isMixed()) {
                                $possibly_redefined_vars[$var] = $type;
                            }
                            else if (isset($possibly_redefined_vars[$var])) {
                                $possibly_redefined_vars[$var] = Type::combineUnionTypes($type, $possibly_redefined_vars[$var]);
                            }
                            else {
                                $possibly_redefined_vars[$var] = $type;
                            }
                        }
                    }

                    if ($new_vars === null) {
                        $new_vars = array_diff_key($elseif_context->vars_in_scope, $context->vars_in_scope);
                    }
                    else {
                        foreach ($new_vars as $new_var => $type) {
                            if (!isset($elseif_context->vars_in_scope[$new_var])) {
                                unset($new_vars[$new_var]);
                            }
                            else {
                                $new_vars[$new_var] = Type::combineUnionTypes($type, $elseif_context->vars_in_scope[$new_var]);
                            }
                        }
                    }
                }

                if ($can_negate_if_types) {
                    $context->update($old_elseif_context, $elseif_context, $has_leaving_statments, $updated_vars);
                }

                // has a return/throw at end
                $has_ending_statments = ScopeChecker::doesReturnOrThrow($elseif->stmts);

                if (!$has_ending_statments) {
                    $vars = array_diff_key($elseif_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);

                    // if we're leaving this block, add vars to outer for loop scope
                    if ($has_leaving_statements) {
                        $for_vars_possibly_in_scope = array_merge($vars, $for_vars_possibly_in_scope);
                    }
                    else {
                        $new_vars_possibly_in_scope = array_merge($vars, $new_vars_possibly_in_scope);
                    }
                }
            }
        }

        if ($stmt->else) {
            $else_context = clone $original_context;

            if ($negated_types) {
                $else_vars_reconciled = TypeChecker::reconcileKeyedTypes($negated_types, $else_context->vars_in_scope, $this->_file_name, $stmt->getLine());
                if ($else_vars_reconciled === false) {
                    return false;
                }
                $else_context->vars_in_scope = $else_vars_reconciled;
            }

            $old_else_context = clone $else_context;

            if ($this->check($stmt->else->stmts, $else_context, $for_vars_possibly_in_scope) === false) {
                return false;
            }

            if (count($stmt->else->stmts)) {
                $has_leaving_statements = ScopeChecker::doesLeaveBlock($stmt->else->stmts, true, true);

                // if it doesn't end in a return
                if (!$has_leaving_statements) {
                    $else_redefined_vars = Context::getRedefinedVars($original_context, $else_context);

                    if ($redefined_vars === null) {
                        $redefined_vars = $else_redefined_vars;
                        $possibly_redefined_vars = $redefined_vars;
                    }
                    else {
                        foreach ($redefined_vars as $redefined_var => $type) {
                            if (!isset($else_redefined_vars[$redefined_var])) {
                                unset($redefined_vars[$redefined_var]);
                            }
                            else {
                                $redefined_vars[$redefined_var] = Type::combineUnionTypes($else_redefined_vars[$redefined_var], $type);
                            }
                        }

                        foreach ($else_redefined_vars as $var => $type) {
                            if ($type->isMixed()) {
                                $possibly_redefined_vars[$var] = $type;
                            }
                            else if (isset($possibly_redefined_vars[$var])) {
                                $possibly_redefined_vars[$var] = Type::combineUnionTypes($type, $possibly_redefined_vars[$var]);
                            }
                            else {
                                $possibly_redefined_vars[$var] = $type;
                            }
                        }
                    }

                    if ($new_vars === null) {
                        $new_vars = array_diff_key($else_context->vars_in_scope, $context->vars_in_scope);
                    }
                    else {
                        foreach ($new_vars as $new_var => $type) {
                            if (!isset($else_context->vars_in_scope[$new_var])) {
                                unset($new_vars[$new_var]);
                            }
                            else {
                                $new_vars[$new_var] = Type::combineUnionTypes($type, $else_context->vars_in_scope[$new_var]);
                            }
                        }
                    }
                }

                // update the parent context as necessary
                if ($can_negate_if_types) {
                    $context->update($old_else_context, $else_context, $has_leaving_statments, $updated_vars);
                }

                // has a return/throw at end
                $has_ending_statments = ScopeChecker::doesReturnOrThrow($stmt->else->stmts);

                if (!$has_ending_statments) {
                    $vars = array_diff_key($else_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);

                    if ($has_leaving_statements) {
                        $for_vars_possibly_in_scope = array_merge($vars, $for_vars_possibly_in_scope);
                    }
                    else {
                        $new_vars_possibly_in_scope = array_merge($vars, $new_vars_possibly_in_scope);
                    }
                }
            }
        }

        if ($new_vars) {
            $context->vars_in_scope = array_merge($context->vars_in_scope, $new_vars);
        }
        $context->vars_possibly_in_scope = array_merge($context->vars_possibly_in_scope, $new_vars_possibly_in_scope);

        // vars can only be redefined if there was an else (defined in every block)
        if ($stmt->else && $redefined_vars) {
            foreach ($redefined_vars as $var => $type) {
                $context->vars_in_scope[$var] = $type;
                $updated_vars[$var] = true;
            }
        }

        if ($possibly_redefined_vars) {
            foreach ($possibly_redefined_vars as $var => $type) {
                if (isset($context->vars_in_scope[$var]) && !isset($updated_vars[$var])) {
                    $context->vars_in_scope[$var] = Type::combineUnionTypes($context->vars_in_scope[$var], $type);
                }
            }
        }
    }

    protected function _checkCondition(PhpParser\Node\Expr $stmt, Context $context)
    {
        return $this->_checkExpression($stmt, $context);
    }

    protected function _checkStatic(PhpParser\Node\Stmt\Static_ $stmt, Context $context)
    {
        foreach ($stmt->vars as $var) {
            if ($var instanceof PhpParser\Node\Stmt\StaticVar) {
                if (is_string($var->name)) {
                    if ($this->_check_variables) {
                        $context->vars_in_scope[$var->name] = Type::getMixed();
                        $context->vars_possibly_in_scope[$var->name] = true;
                        $this->registerVariable($var->name, $var->getLine());
                    }
                } else {
                    if ($this->_checkExpression($var->name, $context) === false) {
                        return false;
                    }
                }

                if ($var->default) {
                    if ($this->_checkExpression($var->default, $context) === false) {
                        return false;
                    }
                }
            } else {
                if ($this->_checkExpression($var, $context) === false) {
                    return false;
                }
            }
        }
    }

    /**
     * @return false|null
     */
    protected function _checkExpression(PhpParser\Node\Expr $stmt, Context $context, $array_assignment = false)
    {
        foreach (Config::getInstance()->getPlugins() as $plugin) {

            if ($plugin->checkExpression($stmt, $context, $this->_file_name) === false) {
                return false;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            return $this->_checkVariable($stmt, $context, null, -1, $array_assignment);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Assign) {
            return $this->_checkAssignment($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp) {
            return $this->_checkAssignmentOperation($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\MethodCall) {
            return $this->_checkMethodCall($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\StaticCall) {
            return $this->_checkStaticCall($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            return $this->_checkConstFetch($stmt);

        } elseif ($stmt instanceof PhpParser\Node\Scalar\String_) {
            $stmt->inferredType = Type::getString();

        } elseif ($stmt instanceof PhpParser\Node\Scalar\EncapsedStringPart) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            $stmt->inferredType = Type::getInt();

        } elseif ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            $stmt->inferredType = Type::getFloat();

        } elseif ($stmt instanceof PhpParser\Node\Expr\UnaryMinus) {
            return $this->_checkExpression($stmt->expr, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            return $this->_checkExpression($stmt->expr, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Isset_) {
            foreach ($stmt->vars as $isset_var) {
                if ($isset_var instanceof PhpParser\Node\Expr\PropertyFetch &&
                    $isset_var->var instanceof PhpParser\Node\Expr\Variable &&
                    $isset_var->var->name === 'this' &&
                    is_string($isset_var->name)
                ) {
                    $var_id = 'this->' . $isset_var->name;
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var_id] = true;
                }
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            return $this->_checkClassConstFetch($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            return $this->_checkPropertyFetch($stmt, $context, $array_assignment);

        } elseif ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
            return $this->_checkStaticPropertyFetch($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\BitwiseNot) {
            return $this->_checkExpression($stmt->expr, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            return $this->_checkBinaryOp($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PostInc) {
            return $this->_checkExpression($stmt->var, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PostDec) {
            return $this->_checkExpression($stmt->var, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PreInc) {
            return $this->_checkExpression($stmt->var, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PreDec) {
            return $this->_checkExpression($stmt->var, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\New_) {
            return $this->_checkNew($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Array_) {
            return $this->_checkArray($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Scalar\Encapsed) {
            return $this->_checkEncapsulatedString($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall) {
            return $this->_checkFunctionCall($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            return $this->_checkTernary($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            return $this->_checkBooleanNot($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Empty_) {
            return $this->_checkEmpty($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Closure) {
            $closure_checker = new ClosureChecker($stmt, $this->_source);

            if ($this->_checkClosureUses($stmt, $context) === false) {
                return false;
            }

            $use_context = new Context();

            if (!$this->_is_static) {
                $this_class = ClassChecker::getThisClass() && is_subclass_of(ClassChecker::getThisClass(), $this->_absolute_class) ?
                    ClassChecker::getThisClass() :
                    $this->_absolute_class;

                if ($this_class) {
                    $use_context->vars_in_scope['this'] = new Type\Union([new Type\Atomic($this_class)]);
                }
            }

            foreach ($context->vars_in_scope as $var => $type) {
                if (strpos($var, 'this->') === 0) {
                    $use_context->vars_in_scope[$var] = $type;
                }
            }

            foreach ($context->vars_possibly_in_scope as $var => $type) {
                if (strpos($var, 'this->') === 0) {
                    $use_context->var_possibly_in_scope[$var] = true;
                }
            }

            foreach ($stmt->uses as $use) {
                $use_context->vars_in_scope[$use->var] = isset($context->vars_in_scope[$use->var]) ? $context->vars_in_scope[$use->var] : Type::getMixed();
                $use_context->vars_possibly_in_scope[$use->var] = true;
            }

            $closure_checker->check($use_context, $this->_check_methods);

        } elseif ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            return $this->_checkArrayAccess($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getInt();

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getDouble();

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getBool();

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getString();

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getObject();

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getArray();

        } elseif ($stmt instanceof PhpParser\Node\Expr\Clone_) {
            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }

            if (property_exists($stmt->expr, 'inferredType')) {
                $stmt->inferredType = $stmt->expr->inferredType;
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\Instanceof_) {
            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }

            if ($stmt->class instanceof PhpParser\Node\Name && !in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                if ($this->_check_classes) {
                    if (ClassChecker::checkClassName($stmt->class, $this->_namespace, $this->_aliased_classes, $this->_file_name) === false) {
                        return false;
                    }
                }
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Expr\Include_) {
            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }

            $path_to_file = null;

            if ($stmt->expr instanceof PhpParser\Node\Scalar\String_) {
                $path_to_file = $stmt->expr->value;

                // attempts to resolve using get_include_path dirs
                $include_path = self::_resolveIncludePath($path_to_file, dirname($this->_file_name));
                $path_to_file = $include_path ? $include_path : $path_to_file;

                if ($path_to_file[0] !== '/') {
                    $path_to_file = getcwd() . '/' . $path_to_file;
                }
            }
            else {
                $path_to_file = self::_getPathTo($stmt->expr, $this->_file_name);
            }

            if ($path_to_file) {
                $reduce_pattern = '/\/[^\/]+\/\.\.\//';

                while (preg_match($reduce_pattern, $path_to_file)) {
                    $path_to_file = preg_replace($reduce_pattern, '/', $path_to_file);
                }

                // if the file is already included, we can't check much more
                if (in_array($path_to_file, get_included_files())) {
                    return;
                }

                if (in_array($path_to_file, FileChecker::getIncludesToIgnore())) {
                    return;
                }

                if (file_exists($path_to_file)) {
                    $include_stmts = FileChecker::getStatementsForFile($path_to_file);

                    $this->_require_file_name = $path_to_file;
                    $this->check($include_stmts, $context);
                    return;
                }
            }

            $this->_check_classes = false;
            $this->_check_variables = false;

        } elseif ($stmt instanceof PhpParser\Node\Expr\Eval_) {
            $this->_check_classes = false;
            $this->_check_variables = false;

            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignRef) {
            if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
                $context->vars_in_scope[$stmt->var->name] = Type::getMixed();
                $context->vars_possibly_in_scope[$stmt->var->name] = true;
                $this->registerVariable($stmt->var->name, $stmt->var->getLine());
            } else {
                if ($this->_checkExpression($stmt->var, $context) === false) {
                    return false;
                }
            }

            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\ErrorSuppress) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Expr\ShellExec) {
            if (IssueHandler::accepts(
                new ForbiddenCode('Use of shell_exec', $this->_file_name, $stmt->getLine())
            )) {
                return false;
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\Print_) {
            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }

        } else {
            var_dump('Unrecognised expression in ' . $this->_file_name);
            var_dump($stmt);
        }
    }

    /**
     * @return false|null
     */
    protected function _checkVariable(PhpParser\Node\Expr\Variable $stmt, Context $context, $method_id = null, $argument_offset = -1, $array_assignment = false)
    {
        if ($this->_is_static && $stmt->name === 'this') {
            if (IssueHandler::accepts(
                new InvalidStaticVariable('Invalid reference to $this in a static context', $this->_file_name, $stmt->getLine())
            )) {
                return false;
            }
        }

        if (!$this->_check_variables) {
            $stmt->inferredType = Type::getMixed();

            if (is_string($stmt->name)) {
                $context->vars_in_scope[$stmt->name] = Type::getMixed();
                $context->vars_possibly_in_scope[$stmt->name] = true;
            }

            return;
        }

        if (in_array($stmt->name, ['_SERVER', '_GET', '_POST', '_COOKIE', '_REQUEST', '_FILES', '_ENV', 'GLOBALS', 'argv'])) {
            return;
        }

        if (!is_string($stmt->name)) {
            return $this->_checkExpression($stmt->name, $context);
        }

        if ($method_id && isset($context->vars_in_scope[$stmt->name]) && !$context->vars_in_scope[$stmt->name]->isMixed()) {
            if ($this->_checkFunctionArgumentType($context->vars_in_scope[$stmt->name], $method_id, $argument_offset, $this->_file_name, $stmt->getLine()) === false) {
                return false;
            }
        }

        if ($stmt->name === 'this') {
            return;
        }

        if ($method_id && $this->_isPassedByReference($method_id, $argument_offset)) {
            $this->_assignByRefParam($stmt, $method_id, $context);
            return;
        }

        $var_name = $stmt->name;

        if (!isset($context->vars_in_scope[$var_name])) {
            if (!isset($context->vars_possibly_in_scope[$var_name]) || !isset($this->_all_vars[$var_name])) {
                if ($array_assignment) {
                    // if we're in an array assignment, let's assign the variable
                    // because PHP allows it

                    $context->vars_in_scope[$var_name] = Type::getArray();
                    $context->vars_possibly_in_scope[$var_name] = true;
                    $this->registerVariable($var_name, $stmt->getLine());
                }
                else {
                    if (IssueHandler::accepts(
                        new UndefinedVariable('Cannot find referenced variable $' . $var_name, $this->_file_name, $stmt->getLine())
                    )) {
                        return false;
                    }
                }
            }

            if (isset($this->_all_vars[$var_name]) && !isset($this->_warn_vars[$var_name])) {
                $this->_warn_vars[$var_name] = true;

                if (IssueHandler::accepts(
                    new PossiblyUndefinedVariable(
                        'Possibly undefined variable $' . $var_name .', first seen on line ' . $this->_all_vars[$var_name],
                        $this->_file_name,
                        $stmt->getLine()
                    )
                )) {
                    return false;
                }
            }

        } else {
            $stmt->inferredType = $context->vars_in_scope[$var_name];
        }
    }

    protected function _assignByRefParam(PhpParser\Node\Expr $stmt, $method_id, Context $context)
    {
        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            $property_id = $stmt->name;
        }
        else if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && $stmt->var->name === 'this') {
            $property_id = $stmt->var->name . '->' . $stmt->name;
        }
        else {
            throw new \InvalidArgumentException('Bad property passed to _checkMethodParam');
        }

        if (!isset($context->vars_in_scope[$property_id])) {
            $context->vars_possibly_in_scope[$property_id] = true;
            $this->registerVariable($property_id, $stmt->getLine());

            if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && $this->_source->getMethodId()) {
                $this_method_id = $this->_source->getMethodId();

                if (!isset(self::$_this_assignments[$this_method_id])) {
                    self::$_this_assignments[$this_method_id] = [];
                }

                self::$_this_assignments[$this_method_id][$stmt->name] = Type::getMixed();
            }
        }

        $context->vars_in_scope[$property_id] = Type::getMixed();
    }

    protected function _checkPropertyFetch(PhpParser\Node\Expr\PropertyFetch $stmt, Context $context, $array_assignment = false)
    {
        if (!is_string($stmt->name)) {
            if ($this->_checkExpression($stmt->name, $context) === false) {
                return false;
            }
        }

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if ($stmt->var->name === 'this') {
                if (is_string($stmt->name)) {
                    return $this->_checkThisPropertyFetch($stmt, $context, $array_assignment);
                }
            }

            return $this->_checkVariable($stmt->var, $context);

        }

        return $this->_checkExpression($stmt->var, $context);
    }

    protected function _checkThisPropertyFetch(PhpParser\Node\Expr\PropertyFetch $stmt, Context $context, $array_assignment = false)
    {
        $class_checker = $this->_source->getClassChecker();

        if (!$class_checker) {
            if (IssueHandler::accepts(
                new InvalidScope('Cannot use $this when not inside class', $this->_file_name, $stmt->getLine())
            )) {
                return false;
            }
        }

        $var_id = self::getVarId($stmt);
        $property_names = $class_checker->getPropertyNames();
        $this_class = $context->vars_in_scope['this'];

        if (isset($context->vars_in_scope[$var_id])) {
            $stmt->inferredType = $context->vars_in_scope[$var_id];
        }

        if (!in_array($stmt->name, $property_names)) {
            $property_id = $this_class . '::' . $stmt->name;

            $var_defined = isset($context->vars_in_scope[$var_id]) || isset($context->vars_possibly_in_scope[$var_id]);

            if ((ClassChecker::getThisClass() && !$var_defined) || (!ClassChecker::getThisClass() && !$var_defined && !self::_propertyExists($property_id))) {
                if ($array_assignment) {
                    // if we're in an array assignment, let's assign the variable
                    // because PHP allows it

                    $context->vars_in_scope[$var_id] = Type::getArray();
                    $context->vars_possibly_in_scope[$var_id] = true;
                    $this->registerVariable($var_id, $stmt->getLine());
                }
                else {
                    if (IssueHandler::accepts(
                        new UndefinedProperty('$' . $var_id . ' is not defined', $this->_file_name, $stmt->getLine())
                    )) {
                        return false;
                    }
                }

            }
        }
    }

    protected function _checkNew(PhpParser\Node\Expr\New_ $stmt, Context $context)
    {
        $absolute_class = null;

        if ($stmt->class instanceof PhpParser\Node\Name && !in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
            if ($this->_check_classes) {
                if (ClassChecker::checkClassName($stmt->class, $this->_namespace, $this->_aliased_classes, $this->_file_name) === false) {
                    return false;
                }

                $absolute_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
                $stmt->inferredType = new Type\Union([new Type\Atomic($absolute_class)]);
            }
        }

        if ($absolute_class) {
            $method_id = $absolute_class . '::__construct';

            if ($this->_checkMethodParams($stmt->args, $method_id, $context) === false) {
                return false;
            }
        }
    }

    protected function _checkArray(PhpParser\Node\Expr\Array_ $stmt, Context $context)
    {
        // if the array is empty, this special type allows us to match any other array type against it
        if (empty($stmt->items)) {
            $stmt->inferredType = new Type\Union([new Type\Generic('array', [new Type\Atomic('empty')], true)]);
            return;
        }

        foreach ($stmt->items as $item) {
            if ($item->key) {
                if ($this->_checkExpression($item->key, $context) === false) {
                    return false;
                }
            }

            if ($this->_checkExpression($item->value, $context) === false) {
                return false;
            }
        }

        $stmt->inferredType = Type::getArray();
    }

    protected function _checkTryCatch(PhpParser\Node\Stmt\TryCatch $stmt, Context $context)
    {
        $this->check($stmt->stmts, $context);

        // clone context for catches after running the try block, as
        // we optimistically assume it only failed at the very end
        $original_context = clone $context;

        foreach ($stmt->catches as $catch) {
            $catch_context = clone $original_context;

            if ($catch->type) {
                $catch_context->vars_in_scope[$catch->var] = new Type\Union([
                    new Type\Atomic(ClassChecker::getAbsoluteClassFromName($catch->type, $this->_namespace, $this->_aliased_classes))
                ]);
            }
            else {
                $catch_context->vars_in_scope[$catch->var] = Type::getMixed();
            }

            $catch_context->vars_possibly_in_scope[$catch->var] = true;

            $this->registerVariable($catch->var, $catch->getLine());

            if ($this->_check_classes) {
                if (ClassChecker::checkClassName($catch->type, $this->_namespace, $this->_aliased_classes, $this->_file_name) === false) {
                    return;
                }
            }

            $this->check($catch->stmts, $catch_context);

            if (!ScopeChecker::doesReturnOrThrow($catch->stmts, false, false)) {
                foreach ($catch_context->vars_in_scope as $catch_var => $type) {
                    if ($catch->var !== $catch_var && isset($context->vars_in_scope[$catch_var]) && (string) $context->vars_in_scope[$catch_var] !== (string) $type) {
                        $context->vars_in_scope[$catch_var] = Type::combineUnionTypes($context->vars_in_scope[$catch_var], $type);
                    }
                }

                $context->vars_possibly_in_scope = array_merge($catch_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);
            }
        }

        if ($stmt->finallyStmts) {
            $this->check($stmt->finallyStmts, $context);
        }
    }

    protected function _checkFor(PhpParser\Node\Stmt\For_ $stmt, Context $context)
    {
        $for_context = clone $context;
        $for_context->in_loop = true;

        foreach ($stmt->init as $init) {
            if ($this->_checkExpression($init, $for_context) === false) {
                return false;
            }
        }

        foreach ($stmt->cond as $condition) {
            if ($this->_checkCondition($condition, $for_context) === false) {
                return false;
            }
        }

        foreach ($stmt->loop as $expr) {
            if ($this->_checkExpression($expr, $for_context) === false) {
                return false;
            }
        }

        $this->check($stmt->stmts, $for_context, $for_context->vars_possibly_in_scope);

        foreach ($context->vars_in_scope as $var => $type) {
            if ($type->isMixed()) {
                continue;
            }

            if ($for_context->vars_in_scope[$var]->isMixed()) {
                $context->vars_in_scope[$var] = $for_context->vars_in_scope[$var];
            }

            if ((string) $for_context->vars_in_scope[$var] !== (string) $type) {
                $context->vars_in_scope[$var] = Type::combineUnionTypes($context->vars_in_scope[$var], $for_context->vars_in_scope[$var]);
            }
        }

        $context->vars_possibly_in_scope = array_merge($for_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);
    }

    protected function _checkForeach(PhpParser\Node\Stmt\Foreach_ $stmt, Context $context)
    {
        if ($this->_checkExpression($stmt->expr, $context) === false) {
            return false;
        }

        $foreach_context = clone $context;
        $foreach_context->in_loop = true;

        if ($stmt->keyVar) {
            $foreach_context->vars_in_scope[$stmt->keyVar->name] = Type::getMixed();
            $foreach_context->vars_possibly_in_scope[$stmt->keyVar->name] = true;
            $this->registerVariable($stmt->keyVar->name, $stmt->getLine());
        }

        if ($stmt->valueVar) {
            $value_type = null;

            $var_id = self::getVarId($stmt->expr);

            $iterator_type = isset($foreach_context->vars_in_scope[$var_id]) ? $foreach_context->vars_in_scope[$var_id] : null;

            if ($iterator_type) {
                foreach ($iterator_type->types as $return_type) {
                    switch ($return_type->value) {
                        case 'mixed':
                        case 'array':
                            // do nothing
                            break;

                        case 'null':
                            if (IssueHandler::accepts(
                                new NullReference('Cannot iterate over ' . $return_type->value, $this->_file_name, $stmt->getLine())
                            )) {
                                return false;
                            }
                            break;

                        case 'string':
                        case 'void':
                        case 'int':
                            if (IssueHandler::accepts(
                                new InvalidIterator('Cannot iterate over ' . $return_type->value, $this->_file_name, $stmt->getLine())
                            )) {
                                return false;
                            }
                            break;

                        default:
                            if ($iterator_type instanceof Type\Generic) {
                                $value_type = $iterator_type->type_params[0];
                            }

                            if ($return_type->value !== 'array' && $return_type->value !== 'Traversable' && $return_type->value !== $this->_class_name) {
                                if (ClassChecker::checkAbsoluteClass($return_type->value, $stmt, $this->_file_name) === false) {
                                    return false;
                                }
                            }
                    }
                }
            }

            $foreach_context->vars_in_scope[$stmt->valueVar->name] = $value_type ? $value_type : Type::getMixed();
            $foreach_context->vars_possibly_in_scope[$stmt->valueVar->name] = true;
            $this->registerVariable($stmt->valueVar->name, $stmt->getLine());
        }

        $this->check($stmt->stmts, $foreach_context, $foreach_context->vars_possibly_in_scope);

        foreach ($context->vars_in_scope as $var => $type) {
            if ($type->isMixed()) {
                continue;
            }

            if ($foreach_context->vars_in_scope[$var]->isMixed()) {
                $context->vars_in_scope[$var] = $foreach_context->vars_in_scope[$var];
            }

            if ((string) $foreach_context->vars_in_scope[$var] !== (string) $type) {
                $context->vars_in_scope[$var] = Type::combineUnionTypes($context->vars_in_scope[$var], $foreach_context->vars_in_scope[$var]);
            }
        }

        $context->vars_possibly_in_scope = array_merge($foreach_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);
    }

    protected function _checkWhile(PhpParser\Node\Stmt\While_ $stmt, Context $context)
    {
        $while_context = clone $context;

        if ($this->_checkCondition($stmt->cond, $while_context) === false) {
            return false;
        }

        $while_types = $this->_type_checker->getTypeAssertions($stmt->cond, true);

        // if the while has an or as the main component, we cannot safely reason about it
        if ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp && self::_containsBooleanOr($stmt->cond)) {
            // do nothing
        }
        else {
            $while_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes($while_types, $while_context->vars_in_scope, $this->_file_name, $stmt->getLine());

            if ($while_vars_in_scope_reconciled === false) {
                return false;
            }

            $while_context->vars_in_scope = $while_vars_in_scope_reconciled;
        }

        if ($this->check($stmt->stmts, $while_context) === false) {
            return false;
        }

        foreach ($context->vars_in_scope as $var => $type) {
            if ($type->isMixed()) {
                continue;
            }

            if ($while_context->vars_in_scope[$var]->isMixed()) {
                $context->vars_in_scope[$var] = $while_context->vars_in_scope[$var];
            }

            if ((string) $while_context->vars_in_scope[$var] !== (string) $type) {
                $context->vars_in_scope[$var] = Type::combineUnionTypes($while_context->vars_in_scope[$var], $type);
            }
        }

        $context->vars_possibly_in_scope = array_merge($context->vars_possibly_in_scope, $while_context->vars_possibly_in_scope);
    }

    protected function _checkDo(PhpParser\Node\Stmt\Do_ $stmt, Context $context)
    {
        // do not clone context for do, because it executes in current scope always
        if ($this->check($stmt->stmts, $context) === false) {
            return false;
        }

        return $this->_checkCondition($stmt->cond, $context);
    }

    protected function _checkBinaryOp(PhpParser\Node\Expr\BinaryOp $stmt, Context $context, $nesting = 0)
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat && $nesting > 20) {
            // ignore deeply-nested string concatenation
        }
        else if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            $left_type_assertions = $this->_type_checker->getTypeAssertions($stmt->left, true);

            if ($this->_checkExpression($stmt->left, $context) === false) {
                return false;
            }

            // while in an and, we allow scope to boil over to support
            // statements of the form if ($x && $x->foo())
            $op_vars_in_scope = TypeChecker::reconcileKeyedTypes($left_type_assertions, $context->vars_in_scope, $this->_file_name, $stmt->getLine());

            if ($op_vars_in_scope === false) {
                return false;
            }

            $op_context = clone $context;
            $op_context->vars_in_scope = $op_vars_in_scope;

            if ($this->_checkExpression($stmt->right, $op_context) === false) {
                return false;
            }

            foreach ($op_context->vars_in_scope as $var => $type) {
                if (!isset($context->vars_in_scope[$var])) {
                    $context->vars_in_scope[$var] = $type;
                    continue;
                }
            }

            $context->vars_possibly_in_scope = array_merge($op_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);
        }
        else if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            $left_type_assertions = $this->_type_checker->getTypeAssertions($stmt->left, true);

            $negated_type_assertions = TypeChecker::negateTypes($left_type_assertions);

            if ($this->_checkExpression($stmt->left, $context) === false) {
                return false;
            }

            // while in an or, we allow scope to boil over to support
            // statements of the form if ($x === null || $x->foo())
            $op_vars_in_scope = TypeChecker::reconcileKeyedTypes($negated_type_assertions, $context->vars_in_scope, $this->_file_name, $stmt->getLine());

            if ($op_vars_in_scope === false) {
                return false;
            }

            $op_context = clone $context;
            $op_context->vars_in_scope = $op_vars_in_scope;

            if ($this->_checkExpression($stmt->right, $op_context) === false) {
                return false;
            }

            $context->vars_possibly_in_scope = array_merge($op_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);
        }
        else {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
                $stmt->inferredType = Type::getString();
            }

            if ($stmt->left instanceof PhpParser\Node\Expr\BinaryOp) {
                if ($this->_checkBinaryOp($stmt->left, $context, ++$nesting) === false) {
                    return false;
                }
            }
            else {
                if ($this->_checkExpression($stmt->left, $context) === false) {
                    return false;
                }
            }

            if ($stmt->right instanceof PhpParser\Node\Expr\BinaryOp) {
                if ($this->_checkBinaryOp($stmt->right, $context, ++$nesting) === false) {
                    return false;
                }
            }
            else {
                if ($this->_checkExpression($stmt->right, $context) === false) {
                    return false;
                }
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\Greater ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\Smaller ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
        ) {
            $stmt->inferredType = Type::getBool();
        }
    }

    protected function _checkAssignment(PhpParser\Node\Expr\Assign $stmt, Context $context)
    {
        $var_id = self::getVarId($stmt->var);

        if ($this->_checkExpression($stmt->expr, $context) === false) {
            // if we're not exiting immediately, make everything mixed
            $context->vars_in_scope[$var_id] = Type::getMixed();

            return false;
        }

        $type_in_comments = null;
        $type_in_comments_var_id = null;
        $doc_comment = $stmt->getDocComment();

        if ($doc_comment) {
            $comments = self::parseDocComment($doc_comment);

            if ($comments && isset($comments['specials']['var'][0])) {
                $var_parts = array_filter(preg_split('/[\s\t]+/', $comments['specials']['var'][0]));

                if ($var_parts) {
                    $type_in_comments = $var_parts[0];

                    if ($type_in_comments[0] === strtoupper($type_in_comments[0])) {
                        $type_in_comments = ClassChecker::getAbsoluteClassFromString($type_in_comments, $this->_namespace, $this->_aliased_classes);
                    }

                    // support PHPStorm-style docblocks like
                    // @var Type $variable
                    if (count($var_parts) > 1 && $var_parts[1][0] === '$') {
                        $type_in_comments_var_id = substr($var_parts[1], 1);
                    }
                }
            }
        }

        if ($type_in_comments_var_id && $type_in_comments_var_id !== $var_id) {
            if (isset($context->vars_in_scope[$type_in_comments_var_id])) {
                $context->vars_in_scope[$type_in_comments_var_id] = Type::parseString($type_in_comments);
            }

            $type_in_comments = null;
        }

        if ($type_in_comments) {
            $return_type = Type::parseString($type_in_comments);
        }
        elseif (isset($stmt->expr->inferredType)) {
            $return_type = $stmt->expr->inferredType;
        }
        else {
            $return_type = Type::getMixed();
        }

        $stmt->inferredType = $return_type;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable && is_string($stmt->var->name)) {
            $context->vars_in_scope[$var_id] = $return_type;
            $context->vars_possibly_in_scope[$var_id] = true;
            $this->registerVariable($var_id, $stmt->var->getLine());

        } elseif ($stmt->var instanceof PhpParser\Node\Expr\List_) {
            foreach ($stmt->var->vars as $var) {
                if ($var) {
                    $context->vars_in_scope[$var->name] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var->name] = true;
                    $this->registerVariable($var->name, $var->getLine());
                }
            }

        } else if ($stmt->var instanceof PhpParser\Node\Expr\ArrayDimFetch) {

            if ($this->_checkArrayAssignment($stmt->var, $context, $return_type) === false) {
                return false;
            }

        } else if ($stmt->var instanceof PhpParser\Node\Expr\PropertyFetch &&
                    $stmt->var->var instanceof PhpParser\Node\Expr\Variable &&
                    $stmt->var->var->name === 'this' &&
                    is_string($stmt->var->name)) {

            $method_id = $this->_source->getMethodId();

            if (!isset(self::$_this_assignments[$method_id])) {
                self::$_this_assignments[$method_id] = [];
            }

            $property_id = $this->_absolute_class . '::' . $stmt->var->name;
            self::$_existing_properties[$property_id] = 1;

            $context->vars_in_scope[$var_id] = $return_type;
            $context->vars_possibly_in_scope[$var_id] = true;

            // right now we have to settle for mixed
            self::$_this_assignments[$method_id][$stmt->var->name] = Type::getMixed();
        }

        if ($var_id && isset($context->vars_in_scope[$var_id]) && $context->vars_in_scope[$var_id]->isVoid()) {
            if (IssueHandler::accepts(
                new FailedTypeResolution('Cannot assign $' . $var_id . ' to type void', $this->_file_name, $stmt->getLine())
            )) {
                return false;
            }
        }
    }

    public static function getVarId(PhpParser\Node\Expr $stmt)
    {
        if ($stmt instanceof PhpParser\Node\Expr\Variable && is_string($stmt->name)) {
            return $stmt->name;
        }
        else if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch &&
            $stmt->var instanceof PhpParser\Node\Expr\Variable &&
            is_string($stmt->name)) {

            $object_id = self::getVarId($stmt->var);

            if (!$object_id) {
                return null;
            }

            return $object_id . '->' . $stmt->name;
        }

        return null;
    }

    protected function _checkArrayAssignment(PhpParser\Node\Expr\ArrayDimFetch $stmt, Context $context, Type\Union $assignment_type)
    {
        if ($this->_checkExpression($stmt->var, $context, true) === false) {
            return false;
        }

        $var_id = self::getVarId($stmt->var);

        if (isset($stmt->var->inferredType)) {
            $return_type = $stmt->var->inferredType;

            if (!$return_type->isMixed()) {

                foreach ($return_type->types as &$type) {
                    if ($type->isScalar()) {
                        if (IssueHandler::accepts(
                            new InvalidArrayAssignment('Cannot assign value on variable $' . $var_id . ' of scalar type ' . $type->value, $this->_file_name, $stmt->getLine())
                        )) {
                            return false;
                        }

                        continue;
                    }
                    $refined_type = $this->_refineArrayType($type, $assignment_type, $var_id, $stmt->getLine());

                    if ($refined_type === false) {
                        return false;
                    }

                    $type = $refined_type;
                }

                $context->vars_in_scope[$var_id] = $return_type;
            }
        }
    }

    /**
     *
     * @param  Type\Atomic $type
     * @param  string      $var_id
     * @param  int         $line_number
     * @return Type\Atomic|false
     */
    protected function _refineArrayType(Type\Atomic $type, Type\Union $assignment_type, $var_id, $line_number)
    {
        if ($type->value === 'null') {
            if (IssueHandler::accepts(
                new NullReference('Cannot assign value on possibly null array ' . $var_id, $this->_file_name, $line_number)
            )) {
                return false;
            }

            return $type;
        }

        foreach ($assignment_type->types as $at) {
            if ($type->value === 'string' && $at->isString()) {
                if (IssueHandler::accepts(
                    new InvalidArrayAssignment('Cannot assign value on variable ' . $var_id . ' using string offset', $this->_file_name, $line_number)
                )) {
                    return false;
                }
            }
        }

        if ($type->value !== 'array' && !ClassChecker::classImplements($type->value, 'ArrayAccess')) {
            if (IssueHandler::accepts(
                new InvalidArrayAssignment('Cannot assign value on variable ' . $var_id . ' that does not implement ArrayAccess', $this->_file_name, $line_number)
            )) {
                return false;
            }

            return $type;
        }

        if ($type instanceof Type\Generic) {
            if ($type->is_empty) {
                // boil this down to a regular array
                if ($assignment_type->isMixed()) {
                    return new Type\Atomic($type->value);
                }

                $type->type_params = array_values($assignment_type->types);
                $type->is_empty = false;
                return $type;
            }

            $array_type = $type->type_params[0] instanceof Type\Union ? $type->type_params[0] : new Type\Union([$type->type_params[0]]);

            if ((string) $array_type !== (string) $assignment_type) {
                $type->type_params[0] = Type::combineUnionTypes($array_type, $assignment_type);
                return $type;
            }
        }



        return $type;
    }

    protected function _checkAssignmentOperation(PhpParser\Node\Expr\AssignOp $stmt, Context $context)
    {
        if ($this->_checkExpression($stmt->var, $context) === false) {
            return false;
        }

        return $this->_checkExpression($stmt->expr, $context);
    }

    protected function _checkMethodCall(PhpParser\Node\Expr\MethodCall $stmt, Context $context)
    {
        if ($this->_checkExpression($stmt->var, $context) === false) {
            return false;
        }

        $class_type = null;
        $method_id = null;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if (!is_string($stmt->var->name)) {
                if ($this->_checkExpression($stmt->var->name, $context) === false) {
                    return false;
                }
            }
            else if ($stmt->var->name === 'this' && !$this->_class_name) {
                if (IssueHandler::accepts(
                    new InvalidScope('Use of $this in non-class context', $this->_file_name, $stmt->getLine())
                )) {
                    return false;
                }
            }
        } elseif ($stmt->var instanceof PhpParser\Node\Expr) {
            if ($this->_checkExpression($stmt->var, $context) === false) {
                return false;
            }
        }

        $var_id = self::getVarId($stmt->var);

        $class_type = isset($context->vars_in_scope[$var_id]) ? $context->vars_in_scope[$var_id] : null;

        // make sure we stay vague here
        if (!$class_type) {
            $stmt->inferredType = Type::getMixed();
        }

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable && $stmt->var->name === 'this' && is_string($stmt->name)) {
            $this_method_id = $this->_source->getMethodId();

            if (!isset(self::$_this_calls[$this_method_id])) {
                self::$_this_calls[$this_method_id] = [];
            }

            self::$_this_calls[$this_method_id][] = $stmt->name;

            if (ClassChecker::getThisClass() &&
                (
                    ClassChecker::getThisClass() === $this->_absolute_class ||
                    is_subclass_of(ClassChecker::getThisClass(), $this->_absolute_class) ||
                    trait_exists($this->_absolute_class)
                )) {

                $method_id = $this->_absolute_class . '::' . $stmt->name;

                if ($this->_checkInsideMethod($method_id, $context) === false) {
                    return false;
                }
            }
        }

        if (!$this->_check_methods) {
            return;
        }

        if ($class_type && is_string($stmt->name)) {
            foreach ($class_type->types as $type) {
                $absolute_class = $type->value;

                switch ($absolute_class) {
                    case 'null':
                        if (IssueHandler::accepts(
                            new NullReference('Cannot call method ' . $stmt->name . ' on possibly null variable ' . $class_type, $this->_file_name, $stmt->getLine())
                        )) {
                            return false;
                        }
                        break;

                    case 'int':
                    case 'bool':
                    case 'array':
                        if (IssueHandler::accepts(
                            new InvalidArgument('Cannot call method ' . $stmt->name . ' on ' . $class_type . ' variable', $this->_file_name, $stmt->getLine())
                        )) {
                            return false;
                        }
                        break;

                    case 'mixed':
                        break;

                    default:
                        if ($absolute_class[0] === strtoupper($absolute_class[0]) && !method_exists($absolute_class, '__call') && !self::isMock($absolute_class)) {
                            if (ClassChecker::checkAbsoluteClass($absolute_class, $stmt, $this->_file_name) === false) {
                                return false;
                            }

                            $method_id = $absolute_class . '::' . $stmt->name;

                            if (!isset(self::$_method_call_index[$method_id])) {
                                self::$_method_call_index[$method_id] = [];
                            }

                            if ($this->_source instanceof ClassMethodChecker) {
                                self::$_method_call_index[$method_id][] = $this->_source->getMethodId();
                            }
                            else {
                                self::$_method_call_index[$method_id][] = $this->_source->getFileName();
                            }

                            if (ClassMethodChecker::checkMethodExists($method_id, $this->_file_name, $stmt) === false) {
                                return false;
                            }

                            if (!($this->_source->getSource() instanceof TraitChecker)) {
                                $calling_context = $this->_absolute_class;

                                if (ClassChecker::getThisClass() && is_subclass_of(ClassChecker::getThisClass(), $this->_absolute_class)) {
                                    $calling_context = $this->_absolute_class;
                                }

                                ClassMethodChecker::checkMethodVisibility($method_id, $calling_context, $this->_file_name, $stmt->getLine());
                            }

                            $return_types = ClassMethodChecker::getMethodReturnTypes($method_id);

                            if ($return_types) {
                                $return_types = self::_fleshOutReturnTypes($return_types, $stmt->args, $method_id);

                                $stmt->inferredType = $return_types;
                            }
                        }
                }
            }
        }

        if ($this->_checkMethodParams($stmt->args, $method_id, $context) === false) {
            return false;
        }
    }

    protected function _checkInsideMethod($method_id, Context $context)
    {
        $method_checker = ClassChecker::getMethodChecker($method_id);

        if ($method_checker && $method_checker->getMethodId() !== $this->_source->getMethodId()) {
            $this_context = new Context();

            foreach ($context->vars_possibly_in_scope as $var => $type) {
                if (strpos($var, 'this->') === 0) {
                    $this_context->vars_possibly_in_scope[$var] = true;
                }
            }

            foreach ($context->vars_in_scope as $var => $type) {
                if (strpos($var, 'this->') === 0) {
                    $this_context->vars_in_scope[$var] = $type;
                }
            }

            $this_context->vars_in_scope['this'] = $context->vars_in_scope['this'];

            $method_checker->check($this_context);

            foreach ($this_context->vars_in_scope as $var => $type) {
                $context->vars_possibly_in_scope[$var] = true;
            }

            foreach ($this_context->vars_in_scope as $var => $type) {
                $context->vars_in_scope[$var] = $type;
            }
        }
    }

    protected function _checkClosureUses(PhpParser\Node\Expr\Closure $stmt, Context $context)
    {
        foreach ($stmt->uses as $use) {
            if (!isset($context->vars_in_scope[$use->var])) {
                if ($use->byRef) {
                    $context->vars_in_scope[$use->var] = Type::getMixed();
                    $context->vars_possibly_in_scope[$use->var] = true;
                    $this->registerVariable($use->var, $use->getLine());
                    return;
                }

                if (!isset($context->vars_possibly_in_scope[$use->var])) {
                    if (IssueHandler::accepts(
                        new UndefinedVariable('Cannot find referenced variable $' . $use->var, $this->_file_name, $use->getLine())
                    )) {
                        return false;
                    }
                }

                if (isset($this->_all_vars[$use->var])) {
                    if (!isset($this->_warn_vars[$use->var])) {
                        $this->_warn_vars[$use->var] = true;
                        if (IssueHandler::accepts(
                            new PossiblyUndefinedVariable(
                                'Possibly undefined variable $' . $use->var . ', first seen on line ' . $this->_all_vars[$use->var],
                                $this->_file_name,
                                $use->getLine()
                            )
                        )) {
                            return false;
                        }
                    }

                    return;
                }

                if (IssueHandler::accepts(
                    new UndefinedVariable('Cannot find referenced variable $' . $use->var, $this->_file_name, $use->getLine())
                )) {
                    return false;
                }
            }
        }
    }

    /**
     * @return void
     */
    protected function _checkStaticCall(PhpParser\Node\Expr\StaticCall $stmt, Context $context)
    {
        if ($stmt->class instanceof PhpParser\Node\Expr\Variable || $stmt->class instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            // this is when calling $some_class::staticMethod() - which is a shitty way of doing things
            // because it can't be statically type-checked
            return;
        }

        $method_id = null;
        $absolute_class = null;

        if (count($stmt->class->parts) === 1 && in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
            if ($stmt->class->parts[0] === 'parent') {
                if ($this->_class_extends === null) {
                    if (IssueHandler::accepts(
                        new ParentNotFound('Cannot call method on parent as this class does not extend another', $this->_file_name, $stmt->getLine())
                    )) {
                        return false;
                    }
                }

                $absolute_class = $this->_class_extends;
            } else {
                $absolute_class = ($this->_namespace ? $this->_namespace . '\\' : '') . $this->_class_name;
            }

        } elseif ($this->_check_classes) {
            if (ClassChecker::checkClassName($stmt->class, $this->_namespace, $this->_aliased_classes, $this->_file_name) === false) {
                return false;
            }
            $absolute_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
        }

        if (!$this->_check_methods) {
            return;
        }

        if ($stmt->class->parts === ['parent'] && is_string($stmt->name)) {
            if (ClassChecker::getThisClass()) {
                $method_id = $absolute_class . '::' . $stmt->name;

                if ($this->_checkInsideMethod($method_id, $context) === false) {
                    return false;
                }
            }
        }

        if ($absolute_class && is_string($stmt->name) && !method_exists($absolute_class, '__callStatic') && !self::isMock($absolute_class)) {
            $method_id = $absolute_class . '::' . $stmt->name;

            if (!isset(self::$_method_call_index[$method_id])) {
                self::$_method_call_index[$method_id] = [];
            }

            if ($this->_source instanceof ClassMethodChecker) {
                self::$_method_call_index[$method_id][] = $this->_source->getMethodId();
            }
            else {
                self::$_method_call_index[$method_id][] = $this->_source->getFileName();
            }

            ClassMethodChecker::checkMethodExists($method_id, $this->_file_name, $stmt);
            ClassMethodChecker::checkMethodVisibility($method_id, $this->_absolute_class, $this->_file_name, $stmt->getLine());

            if ($this->_is_static) {
                if (!ClassMethodChecker::isGivenMethodStatic($method_id)) {
                    if (IssueHandler::accepts(
                        new InvalidStaticInvocation('Method ' . $method_id . ' is not static', $this->_file_name, $stmt->getLine())
                    )) {
                        return false;
                    }
                }
            }
            else {
                if ($stmt->class->parts[0] === 'self' && $stmt->name !== '__construct') {
                    if (!ClassMethodChecker::isGivenMethodStatic($method_id)) {
                        if (IssueHandler::accepts(
                            new InvalidStaticInvocation('Cannot call non-static method ' . $method_id . ' as if it were static', $this->_file_name, $stmt->getLine())
                        )) {
                            return false;
                        }
                    }
                }
            }

            $return_types = ClassMethodChecker::getMethodReturnTypes($method_id);

            if ($return_types) {
                $return_types = self::_fleshOutReturnTypes($return_types, $stmt->args, $method_id);
                $stmt->inferredType = $return_types;
            }
        }

        return $this->_checkMethodParams($stmt->args, $method_id, $context);
    }

    protected static function _fleshOutReturnTypes(Type\Union $return_type, array $args, $method_id)
    {
        foreach ($return_type->types as &$return_type_part) {
            self::_fleshOutAtomicReturnType($return_type_part, $args, $method_id);
        }

        return $return_type;
    }

    protected static function _fleshOutAtomicReturnType(Type\Atomic &$return_type, array $args, $method_id)
    {
        if ($return_type->value === '$this' || $return_type->value === 'static') {
            $absolute_class = explode('::', $method_id)[0];

            $return_type->value = $absolute_class;
        }
        else if ($return_type->value[0] === '$') {
            $method_params = ClassMethodChecker::getMethodParams($method_id);

            foreach ($args as $i => $arg) {
                $method_param = $method_params[$i];

                if ($return_type->value === '$' . $method_param['name']) {
                    if ($arg->value instanceof PhpParser\Node\Scalar\String_) {
                        $return_type->value = preg_replace('/^\\\/', '', $arg->value->value);
                    }
                }
            }

            if ($return_type->value[0] === '$') {
                $return_type = Type::getMixed(false);
            }
        }

        if ($return_type instanceof Type\Generic) {
            foreach ($return_type->type_params as $type_param) {
                if ($type_param instanceof Type\Union) {
                    $type_param = self::_fleshOutReturnTypes($type_param, $args, $method_id);
                }
                else {
                    $type_param = self::_fleshOutAtomicReturnType($type_param, $args, $method_id);
                }
            }
        }
    }

    protected static function _getMethodFromCallBlock($call, array $args, $method_id)
    {
        $absolute_class = explode('::', $method_id)[0];

        $original_call = $call;

        $call = preg_replace('/^\$this(->|::)/', $absolute_class . '::', $call);

        $call = preg_replace('/\(\)$/', '', $call);

        if (strpos($call, '$') !== false) {
            $method_params = ClassMethodChecker::getMethodParams($method_id);

            foreach ($args as $i => $arg) {
                $method_param = $method_params[$i];
                $preg_var_name = preg_quote('$' . $method_param['name']);

                if (preg_match('/::' . $preg_var_name . '$/', $call)) {
                    if ($arg->value instanceof PhpParser\Node\Scalar\String_) {
                        $call = preg_replace('/' . $preg_var_name . '$/', $arg->value->value, $call);
                        break;
                    }
                }
            }
        }

        return $original_call === $call || strpos($call, '$') !== false ? null : $call;
    }

    protected function _checkMethodParams(array $args, $method_id, Context $context)
    {
        foreach ($args as $i => $arg) {
            if ($arg->value instanceof PhpParser\Node\Expr\PropertyFetch &&
                $arg->value->var instanceof PhpParser\Node\Expr\Variable &&
                $arg->value->var->name === 'this' &&
                is_string($arg->value->name)
            ) {
                $property_id = 'this' . '->' . $arg->value->name;

                if ($method_id) {
                    if (isset($context->vars_in_scope[$property_id]) && !$context->vars_in_scope[$property_id]->isMixed()) {
                        if ($this->_checkFunctionArgumentType($context->vars_in_scope[$property_id], $method_id, $i, $this->_file_name, $arg->getLine()) === false) {
                            return false;
                        }
                    }

                    if ($this->_isPassedByReference($method_id, $i)) {
                        $this->_assignByRefParam($arg->value, $method_id, $context);
                    }
                    else {
                        if ($this->_checkPropertyFetch($arg->value, $context) === false) {
                            return false;
                        }
                    }
                } else {

                    if (false || !isset($context->vars_in_scope[$property_id]) || $context->vars_in_scope[$property_id]->isNull()) {
                        // we don't know if it exists, assume it's passed by reference
                        $context->vars_in_scope[$property_id] = Type::getMixed();
                        $context->vars_possibly_in_scope[$property_id] = true;
                        $this->registerVariable($property_id, $arg->value->getLine());
                    }

                }
            }
            elseif ($arg->value instanceof PhpParser\Node\Expr\Variable) {
                if ($method_id) {
                    if ($this->_checkVariable($arg->value, $context, $method_id, $i) === false) {
                        return false;
                    }

                } elseif (is_string($arg->value->name)) {
                    if (false || !isset($context->vars_in_scope[$arg->value->name]) || $context->vars_in_scope[$arg->value->name]->isNull()) {
                        // we don't know if it exists, assume it's passed by reference
                        $context->vars_in_scope[$arg->value->name] = Type::getMixed();
                        $context->vars_possibly_in_scope[$arg->value->name] = true;
                        $this->registerVariable($arg->value->name, $arg->value->getLine());
                    }
                }
            } else {
                if ($this->_checkExpression($arg->value, $context) === false) {
                    return false;
                }
            }

            if ($method_id && isset($arg->value->inferredType)) {
                if ($this->_checkFunctionArgumentType($arg->value->inferredType, $method_id, $i, $this->_file_name, $arg->value->getLine()) === false) {
                    return false;
                }
            }
        }
    }

    protected function _checkConstFetch(PhpParser\Node\Expr\ConstFetch $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name) {
            switch ($stmt->name->parts) {
                case ['null']:
                    $stmt->inferredType = Type::getNull();
                    break;

                case ['false']:
                    // false is a subtype of bool
                    $stmt->inferredType = Type::getFalse();
                    break;

                case ['true']:
                    $stmt->inferredType = Type::getBool();
                    break;
            }
        }
    }

    protected function _checkClassConstFetch(PhpParser\Node\Expr\ClassConstFetch $stmt, Context $context)
    {
        if ($this->_check_consts && $stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts !== ['static']) {
            if ($stmt->class->parts === ['self']) {
                $absolute_class = $this->_absolute_class;
            } else {
                $absolute_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
                if (ClassChecker::checkAbsoluteClass($absolute_class, $stmt, $this->_file_name) === false) {
                    return false;
                }
            }

            $const_id = $absolute_class . '::' . $stmt->name;

            if (!defined($const_id)) {
                if (IssueHandler::accepts(
                    new UndefinedConstant('Const ' . $const_id . ' is not defined', $this->_file_name, $stmt->getLine())
                )) {
                    return false;
                }
            }

            return;
        }

        if ($stmt->class instanceof PhpParser\Node\Expr) {
            if ($this->_checkExpression($stmt->class, $context) === false) {
                return false;
            }
        }
    }

    /**
     * @return null|false
     */
    protected function _checkStaticPropertyFetch(PhpParser\Node\Expr\StaticPropertyFetch $stmt, Context $context)
    {
        if ($stmt->class instanceof PhpParser\Node\Expr\Variable || $stmt->class instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            // this is when calling $some_class::staticMethod() - which is a shitty way of doing things
            // because it can't be statically type-checked
            return;
        }

        $method_id = null;
        $absolute_class = null;

        if (count($stmt->class->parts) === 1 && in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
            if ($stmt->class->parts[0] === 'parent') {
                $absolute_class = $this->_class_extends;
            } else {
                $absolute_class = ($this->_namespace ? $this->_namespace . '\\' : '') . $this->_class_name;
            }
        } elseif ($this->_check_classes) {
            if (ClassChecker::checkClassName($stmt->class, $this->_namespace, $this->_aliased_classes, $this->_file_name) === false) {
                return false;
            }
            $absolute_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
        }

        if ($absolute_class && $this->_check_variables && is_string($stmt->name) && !self::isMock($absolute_class)) {
            $var_id = $absolute_class . '::$' . $stmt->name;

            if (!self::_staticVarExists($var_id)) {
                if (IssueHandler::accepts(
                    new UndefinedVariable('Static variable ' . $var_id . ' does not exist', $this->_file_name, $stmt->getLine())
                )) {
                    return false;
                }
            }
        }
    }

    protected function _checkReturn(PhpParser\Node\Stmt\Return_ $stmt, Context $context)
    {
        $type_in_comments = null;
        $type_in_comments_var_id = null;
        $doc_comment = $stmt->getDocComment();

        if ($doc_comment) {
            $comments = self::parseDocComment($doc_comment);

            if ($comments && isset($comments['specials']['var'][0])) {
                $var_parts = array_filter(preg_split('/[\s\t]+/', $comments['specials']['var'][0]));

                if ($var_parts) {
                    $type_in_comments = $var_parts[0];

                    if ($type_in_comments[0] === strtoupper($type_in_comments[0])) {
                        $type_in_comments = ClassChecker::getAbsoluteClassFromString($type_in_comments, $this->_namespace, $this->_aliased_classes);
                    }

                    // support PHPStorm-style docblocks like
                    // @var Type $variable
                    if (count($var_parts) > 1 && $var_parts[1][0] === '$') {
                        $type_in_comments_var_id = substr($var_parts[1], 1);
                    }
                }
            }
        }

        if ($type_in_comments_var_id) {
            if (isset($context->vars_in_scope[$type_in_comments_var_id])) {
                $context->vars_in_scope[$type_in_comments_var_id] = Type::parseString($type_in_comments);
            }

            $type_in_comments = null;
        }

        if ($stmt->expr) {
            if ($this->_checkExpression($stmt->expr, $context) === false) {
                return false;
            }

            if ($type_in_comments) {
                $stmt->inferredType = Type::parseString($type_in_comments);
            }
            elseif (isset($stmt->expr->inferredType)) {
                $stmt->inferredType = $stmt->expr->inferredType;
            }
            else {
                $stmt->inferredType = Type::getMixed();
            }
        }
        else {
            $stmt->inferredType = Type::getVoid();
        }

        if ($this->_source instanceof FunctionChecker) {
            $this->_source->addReturnTypes($stmt->expr ? (string) $stmt->inferredType : '', $context);
        }
    }

    protected function _checkTernary(PhpParser\Node\Expr\Ternary $stmt, Context $context)
    {
        if ($this->_checkCondition($stmt->cond, $context) === false) {
            return false;
        }

        $t_if_context = clone $context;

        $if_types = $this->_type_checker->getTypeAssertions($stmt->cond, true);

        $can_negate_if_types = !($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd);

        $if_return_type = null;

        $t_if_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes($if_types, $t_if_context->vars_in_scope, $this->_file_name, $stmt->getLine());

        if ($t_if_vars_in_scope_reconciled === false) {
            return false;
        }

        $t_if_context->vars_in_scope = $t_if_vars_in_scope_reconciled;

        if ($stmt->if) {
            if ($this->_checkExpression($stmt->if, $t_if_context) === false) {
                return false;
            }

            $if_return_type = isset($stmt->if->inferredType) ? $stmt->if->inferredType : Type::getMixed();
        }
        else {
            if (isset($stmt->cond->inferredType)) {
                $if_return_type_reconciled = TypeChecker::reconcileTypes('!empty', $stmt->cond->inferredType, $this->_file_name, $stmt->getLine());

                if ($if_return_type_reconciled === false) {
                    return false;
                }

                $if_return_type = $if_return_type_reconciled;
            }
            else {
                $if_return_type = Type::getMixed();
            }
        }

        $t_else_context = clone $context;

        if ($can_negate_if_types) {
            $negated_if_types = TypeChecker::negateTypes($if_types);
            $t_else_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes($negated_if_types, $t_else_context->vars_in_scope, $this->_file_name, $stmt->getLine());

            if ($t_else_vars_in_scope_reconciled === false) {
                return false;
            }
            $t_else_context->vars_in_scope = $t_else_vars_in_scope_reconciled;
        }

        if ($this->_checkExpression($stmt->else, $t_else_context) === false) {
            return false;
        }

        $lhs_type = null;

        if ($stmt->if) {
            if (isset($stmt->if->inferredType)) {
                $lhs_type = $stmt->if->inferredType;
            }
        }
        elseif ($stmt->cond) {
            if (isset($stmt->cond->inferredType)) {
                $lhs_type = $stmt->cond->inferredType;
            }
        }

        if (!$lhs_type || !isset($stmt->else->inferredType)) {
            $stmt->inferredType = Type::getMixed();
        }
        else {
            $stmt->inferredType = Type::combineUnionTypes($lhs_type, $stmt->else->inferredType);
        }
    }

    protected function _checkBooleanNot(PhpParser\Node\Expr\BooleanNot $stmt, Context $context)
    {
        return $this->_checkExpression($stmt->expr, $context);
    }

    protected function _checkEmpty(PhpParser\Node\Expr\Empty_ $stmt, Context $context)
    {
        return $this->_checkExpression($stmt->expr, $context);
    }

    protected function _checkThrow(PhpParser\Node\Stmt\Throw_ $stmt, Context $context)
    {
        return $this->_checkExpression($stmt->expr, $context);
    }

    protected function _checkSwitch(PhpParser\Node\Stmt\Switch_ $stmt, Context $context, array &$for_vars_possibly_in_scope)
    {
        $type_candidate_var = null;

        if ($stmt->cond instanceof PhpParser\Node\Expr\FuncCall &&
            $stmt->cond->name instanceof PhpParser\Node\Name &&
            $stmt->cond->name->parts === ['get_class']) {

            $var = $stmt->cond->args[0]->value;

            if ($var instanceof PhpParser\Node\Expr\Variable && is_string($var->name)) {
                $type_candidate_var = $var->name;
            }
        }

        if ($this->_checkCondition($stmt->cond, $context) === false) {
            return false;
        }

        $case_types = [];

        $new_vars_in_scope = null;
        $new_vars_possibly_in_scope = [];

        $redefined_vars = null;

        foreach ($stmt->cases as $case) {
            if ($case->cond) {
                if ($this->_checkCondition($case->cond, $context) === false) {
                    return false;
                }

                if ($type_candidate_var && $case->cond instanceof PhpParser\Node\Scalar\String_) {
                    $case_types[] = $case->cond->value;
                }
            }

            $last_stmt = null;

            if ($case->stmts) {
                $switch_vars = $type_candidate_var && !empty($case_types)
                                ? [$type_candidate_var => Type::parseString(implode('|', $case_types))]
                                : [];
                $case_context = clone $context;

                $case_context->vars_in_scope = array_merge($case_context->vars_in_scope, $switch_vars);
                $case_context->vars_possibly_in_scope = array_merge($case_context->vars_possibly_in_scope, $switch_vars);

                $old_case_context = clone $case_context;

                $this->check($case->stmts, $case_context);

                $last_stmt = $case->stmts[count($case->stmts) - 1];

                // has a return/throw at end
                $has_ending_statments = ScopeChecker::doesReturnOrThrow($case->stmts);

                if (!$has_ending_statments) {
                    $vars = array_diff_key($case_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);

                    $has_leaving_statements = ScopeChecker::doesLeaveBlock($case->stmts, true, false);

                    // if we're leaving this block, add vars to outer for loop scope
                    if ($has_leaving_statements) {
                        $for_vars_possibly_in_scope = array_merge($vars, $for_vars_possibly_in_scope);
                    }
                    else {
                        $case_redefined_vars = Context::getRedefinedVars($context, $case_context);

                        if ($redefined_vars === null) {
                            $redefined_vars = $case_redefined_vars;
                        }
                        else {
                            foreach ($redefined_vars as $redefined_var => $type) {
                                if (!isset($case_redefined_vars[$redefined_var])) {
                                    unset($redefined_vars[$redefined_var]);
                                }
                            }
                        }

                        if ($new_vars_in_scope === null) {
                            $new_vars_in_scope = array_diff_key($case_context->vars_in_scope, $context->vars_in_scope);
                            $new_vars_possibly_in_scope = array_diff_key($case_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);
                        }
                        else {
                            foreach ($new_vars_in_scope as $new_var => $type) {
                                if (!isset($case_context->vars_in_scope[$new_var])) {
                                    unset($new_vars_in_scope[$new_var]);
                                }
                            }

                            $new_vars_possibly_in_scope = array_merge(
                                array_diff_key(
                                    $case_context->vars_possibly_in_scope,
                                    $context->vars_possibly_in_scope
                                ),
                                $new_vars_possibly_in_scope
                            );
                        }
                    }
                }
            }

            if ($type_candidate_var && ($last_stmt instanceof PhpParser\Node\Stmt\Break_ || $last_stmt instanceof PhpParser\Node\Stmt\Return_)) {
                $case_types = [];
            }

            // only update vars if there is a default
            // if that default has a throw/return/continue, that should be handled above
            if ($case->cond === null) {
                if ($new_vars_in_scope) {
                    $context->vars_in_scope = array_merge($context->vars_in_scope, $new_vars_in_scope);
                }

                if ($redefined_vars) {
                    $context->vars_in_scope = array_merge($context->vars_in_scope, $redefined_vars);
                }
            }
        }

        $context->vars_possibly_in_scope = array_merge($context->vars_possibly_in_scope, $new_vars_possibly_in_scope);
    }

    protected function _checkFunctionArgumentType(Type\Union $input_type, $method_id, $argument_offset, $file_name, $line_number)
    {
        if (strpos($method_id, '::') !== false) {
            $method_params = ClassMethodChecker::getMethodParams($method_id);

            if (isset($method_params[$argument_offset])) {
                $param_type = $method_params[$argument_offset]['type'];

                if ($param_type->isMixed()) {
                    return;
                }

                if ($input_type->isMixed()) {
                    // @todo make this a config
                    return;
                }

                if ($param_type->isNullable() && !$param_type->isNullable()) {
                    if (IssueHandler::accepts(
                        new NullReference(
                            'Argument ' . ($argument_offset + 1) . ' of ' . $method_id . ' cannot be null, possibly null value provided',
                            $file_name,
                            $line_number
                        )
                    )) {
                        return false;
                    }
                }

                foreach ($input_type->types as $input_type_part) {
                    if ($input_type_part->isNull()) {
                        continue;
                    }

                    foreach ($param_type->types as $param_type_part) {
                        if ($param_type_part->isNull()) {
                            continue;
                        }

                        if ($input_type_part->value !== $param_type_part->value && !is_subclass_of($input_type_part->value, $param_type_part->value) && !self::isMock($input_type_part->value)) {
                            if (is_subclass_of($param_type_part->value, $input_type_part->value)) {
                                // @todo handle coercion
                                return;
                            }

                            if (IssueHandler::accepts(
                                new InvalidArgument(
                                    'Argument ' . ($argument_offset + 1) . ' expects ' . $param_type . ', ' . $input_type . ' provided',
                                    $file_name,
                                    $line_number
                                )
                            )) {
                                return false;
                            }
                        }
                    }
                }
            }
        }
    }

    protected function _checkFunctionCall(PhpParser\Node\Expr\FuncCall $stmt, Context $context)
    {
        $method = $stmt->name;

        if ($method instanceof PhpParser\Node\Name) {
            if ($method->parts === ['method_exists']) {
                $this->_check_methods = false;

            } elseif ($method->parts === ['function_exists']) {
                $this->_check_functions = false;

            } elseif ($method->parts === ['defined']) {
                $this->_check_consts = false;

            } elseif ($method->parts === ['extract']) {
                $this->_check_variables = false;

            } elseif ($method->parts === ['var_dump'] || $method->parts === ['die'] || $method->parts === ['exit']) {
                if (IssueHandler::accepts(
                    new ForbiddenCode('Unsafe ' . implode('', $method->parts), $this->_file_name, $stmt->getLine())
                )) {
                    return false;
                }
            }
        }

        $method_id = null;

        if ($stmt->name instanceof PhpParser\Node\Name && $this->_check_functions) {
            $method_id = implode('', $stmt->name->parts);

            if ($this->_absolute_class) {
                //$method_id = $this->_absolute_class . '::' . $method_id;
            }

            if ($this->_checkFunctionExists($method_id, $stmt) === false) {
                return false;
            }

            $stmt->inferredType = Type::getMixed();
        }

        foreach ($stmt->args as $i => $arg) {
            if ($arg->value instanceof PhpParser\Node\Expr\Variable) {
                if ($method_id) {
                    if ($this->_checkVariable($arg->value, $context, $method_id, $i) === false) {
                        return false;
                    }
                } else {
                    if ($this->_checkVariable($arg->value, $context) === false) {
                        return false;
                    }
                }
            } else {
                if ($this->_checkExpression($arg->value, $context) === false) {
                    return false;
                }
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr\ArrayDimFetch $stmt
     * @param  array                             &$context->vars_in_scope
     * @param  array                             &$context->vars_possibly_in_scope
     * @return false|null
     */
    protected function _checkArrayAccess(PhpParser\Node\Expr\ArrayDimFetch $stmt, Context $context)
    {
        if ($this->_checkExpression($stmt->var, $context) === false) {
            return false;
        }

        $var_type = null;

        if (isset($stmt->var->inferredType)) {
            $var_type = $stmt->var->inferredType;

            if ($var_type instanceof Type\Generic) {
                // create a union type to pass back to the statement
                $array_type = $var_type->type_params[0] instanceof Type\Union ? $var_type->type_params[0] : new Type\Union([$var_type->type_params[0]]);
                $stmt->inferredType = $array_type;
            }
        }

        if ($stmt->dim) {
            if ($this->_checkExpression($stmt->dim, $context) === false) {
                return false;
            }

            if (isset($stmt->dim->inferredType) && $var_type && $var_type->isString()) {
                foreach ($stmt->dim->inferredType->types as $at) {
                    if ($at->isString()) {
                        $var_id = self::getVarId($stmt->var);

                        if (IssueHandler::accepts(
                            new InvalidArrayAccess('Cannot access value on string variable ' . $var_id . ' using string offset', $this->_file_name, $stmt->getLine())
                        )) {
                            return false;
                        }
                    }
                }
            }
        }
    }

    protected function _checkEncapsulatedString(PhpParser\Node\Scalar\Encapsed $stmt, Context $context)
    {
        foreach ($stmt->parts as $part) {
            if ($this->_checkExpression($part, $context) === false) {
                return false;
            }
        }
    }

    public function registerVariable($var_name, $line_number)
    {
        if (!isset($this->_all_vars[$var_name])) {
            $this->_all_vars[$var_name] = $line_number;
        }
    }

    public static function _getClassProperties(\ReflectionClass $reflection_class, $absolute_class_name)
    {
        $properties = $reflection_class->getProperties();
        $props_arr = [];

        foreach ($properties as $reflection_property) {
            if ($reflection_property->isPrivate() || $reflection_property->isStatic()) {
                continue;
            }

            self::$_existing_properties[$absolute_class_name . '::' . $reflection_property->getName()] = 1;
        }

        $parent_reflection_class = $reflection_class->getParentClass();

        if ($parent_reflection_class) {
            self::_getClassProperties($parent_reflection_class, $absolute_class_name);
        }
    }

    protected static function _propertyExists($property_id)
    {
        if (isset(self::$_existing_properties[$property_id])) {
            return true;
        }

        $absolute_class = explode('::', $property_id)[0];

        $reflection_class = new \ReflectionClass($absolute_class);

        self::_getClassProperties($reflection_class, $absolute_class);

        return isset(self::$_existing_properties[$property_id]);
    }

    /**
     * @return false|null
     */
    public function _checkFunctionExists($method_id, $stmt)
    {
        if (isset(self::$_existing_functions[$method_id])) {
            return;
        }

        $file_checker = FileChecker::getFileCheckerFromFileName($this->_file_name);

        if ($file_checker->hasFunction($method_id)) {
            return;
        }

        if (strpos($method_id, '::') !== false) {
            $method_id = preg_replace('/^[^:]+::/', '', $method_id);
        }

        try {
            (new \ReflectionFunction($method_id));
        }
        catch (\ReflectionException $e) {
            if (IssueHandler::accepts(
                new UndefinedFunction('Function ' . $method_id . ' does not exist', $this->_file_name, $stmt->getLine())
            )) {
                return false;
            }
        }

        self::$_existing_functions[$method_id] = 1;
    }

    protected static function _staticVarExists($var_id)
    {
        if (isset(self::$_existing_static_vars[$var_id])) {
            return true;
        }

        $absolute_class = explode('::', $var_id)[0];

        try {
            $reflection_class = new \ReflectionClass($absolute_class);
        }
        catch (\ReflectionException $e) {
            return false;
        }

        $static_properties = $reflection_class->getStaticProperties();

        foreach ($static_properties as $property => $value) {
            self::$_existing_static_vars[$absolute_class . '::$' . $property] = 1;
        }

        return isset(self::$_existing_static_vars[$var_id]);
    }

    /**
     * Parse a docblock comment into its parts.
     *
     * Taken from advanced api docmaker
     * Which was taken from https://github.com/facebook/libphutil/blob/master/src/parser/docblock/PhutilDocblockParser.php
     *
     * @return array Array of the main comment and specials
     */
    public static function parseDocComment($docblock)
    {
        // Strip off comments.
        $docblock = trim($docblock);
        $docblock = preg_replace('@^/\*\*@', '', $docblock);
        $docblock = preg_replace('@\*/$@', '', $docblock);
        $docblock = preg_replace('@^\s*\*@m', '', $docblock);

        // Normalize multi-line @specials.
        $lines = explode("\n", $docblock);
        $last = false;
        foreach ($lines as $k => $line) {
            if (preg_match('/^\s?@\w/i', $line)) {
                $last = $k;
            } elseif (preg_match('/^\s*$/', $line)) {
                $last = false;
            } elseif ($last !== false) {
                $lines[$last] = rtrim($lines[$last]).' '.trim($line);
                unset($lines[$k]);
            }
        }
        $docblock = implode("\n", $lines);

        $special = array();

        // Parse @specials.
        $matches = null;
        $have_specials = preg_match_all('/^\s?@(\w+)\s*([^\n]*)/m', $docblock, $matches, PREG_SET_ORDER);
        if ($have_specials) {
            $docblock = preg_replace('/^\s?@(\w+)\s*([^\n]*)/m', '', $docblock);
            foreach ($matches as $match) {
                list($_, $type, $data) = $match;

                if (empty($special[$type])) {
                    $special[$type] = array();
                }

                $special[$type][] = $data;
            }
        }

        $docblock = str_replace("\t", '  ', $docblock);

        // Smush the whole docblock to the left edge.
        $min_indent = 80;
        $indent = 0;
        foreach (array_filter(explode("\n", $docblock)) as $line) {
            for ($ii = 0; $ii < strlen($line); $ii++) {
                if ($line[$ii] != ' ') {
                    break;
                }
                $indent++;
            }

            $min_indent = min($indent, $min_indent);
        }

        $docblock = preg_replace('/^' . str_repeat(' ', $min_indent) . '/m', '', $docblock);
        $docblock = rtrim($docblock);

        // Trim any empty lines off the front, but leave the indent level if there
        // is one.
        $docblock = preg_replace('/^\s*\n/', '', $docblock);

        return array('description' => $docblock, 'specials' => $special);
    }

    /**
     * @return string
     */
    public static function renderDocComment(array $parsed_doc_comment)
    {
        $doc_comment_text = '/**' . PHP_EOL;

        $description_lines = null;

        $trimmed_description = trim($parsed_doc_comment['description']);

        if (!empty($trimmed_description)) {
            $description_lines = explode(PHP_EOL, $parsed_doc_comment['description']);

            foreach ($description_lines as $line) {
                $doc_comment_text .= ' * ' . $line . PHP_EOL;
            }
        }

        if ($description_lines && $parsed_doc_comment['specials']) {
            $doc_comment_text .= ' *' . PHP_EOL;
        }

        if ($parsed_doc_comment['specials']) {
            $type_lengths = array_map('strlen', array_keys($parsed_doc_comment['specials']));
            $type_width = max($type_lengths) + 1;

            foreach ($parsed_doc_comment['specials'] as $type => $lines) {
                foreach ($lines as $line) {
                    $doc_comment_text .= ' * @' . str_pad($type, $type_width) . $line . PHP_EOL;
                }
            }
        }



        $doc_comment_text .= ' */';

        return $doc_comment_text;
    }

    protected function _isPassedByReference($method_id, $argument_offset)
    {
        if (strpos($method_id, '::') !== false) {
            try {
                $method_params = ClassMethodChecker::getMethodParams($method_id);

                return $argument_offset < count($method_params) && $method_params[$argument_offset]['by_ref'];
            }
            catch (\ReflectionException $e) {
                // we fall through to the functions below
            }
        }

        $file_checker = FileChecker::getFileCheckerFromFileName($this->_file_name);

        if ($file_checker->hasFunction($method_id)) {
            return $file_checker->isPassedByReference($method_id, $argument_offset);
        }

        if (strpos($method_id, '::') !== false) {
            $method_id = preg_replace('/^[^:]+::/', '', $method_id);
        }

        try {
            $reflection_parameters = (new \ReflectionFunction($method_id))->getParameters();

            // if value is passed by reference
            return $argument_offset < count($reflection_parameters) && $reflection_parameters[$argument_offset]->isPassedByReference();
        }
        catch (\ReflectionException $e) {
            return false;
        }
    }

    /**
     * @return string
     */
    public static function findEntryPoints($method_id)
    {
        $output = 'Entry points for ' . $method_id;
        if (empty(self::$_method_call_index[$method_id])) {
            list($absolute_class, $method_name) = explode('::', $method_id);

            $reflection_class = new \ReflectionClass($absolute_class);
            $parent_class = $reflection_class->getParentClass();

            if ($parent_class) {
                try {
                    $parent_class->getMethod($method_name);
                    $method_id = $parent_class->getName() . '::' . $method_name;
                    return $output . ' - NONE - it extends ' . $method_id . ' though';
                }
                catch (\ReflectionException $e) {
                    // do nothing
                }
            }

            return $output . ' - NONE';
        }

        $parents = self::$_method_call_index[$method_id];
        $ignore = [$method_id];
        $entry_points = [];

        while (!empty($parents)) {
            $parent_method_id = array_shift($parents);
            $ignore[] = $parent_method_id;
            $new_parents = self::_findParents($parent_method_id, $ignore);

            if ($new_parents === null) {
                $entry_points[] = $parent_method_id;
            }
            else {
                $parents = array_merge($parents, $new_parents);
            }
        }

        $entry_points = array_unique($entry_points);

        if (count($entry_points) > 20) {
            return $output . PHP_EOL . ' - ' . implode(PHP_EOL . ' - ', array_slice($entry_points, 0, 20)) . ' and more...';
        }

        return $output . PHP_EOL . ' - ' . implode(PHP_EOL . ' - ', $entry_points);
    }

    protected static function _findParents($method_id, array $ignore)
    {
        if (empty(self::$_method_call_index[$method_id])) {
            return null;
        }

        return array_diff(array_unique(self::$_method_call_index[$method_id]), $ignore);
    }

    protected static function _getPathTo(PhpParser\Node\Expr $stmt, $file_name)
    {
        if ($file_name[0] !== '/') {
            $file_name = getcwd() . '/' . $file_name;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            return $stmt->value;

        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            $left_string = self::_getPathTo($stmt->left, $file_name);
            $right_string = self::_getPathTo($stmt->right, $file_name);

            if ($left_string && $right_string) {
                return $left_string . $right_string;
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall &&
            $stmt->name instanceof PhpParser\Node\Name &&
            $stmt->name->parts === ['dirname']) {

            if ($stmt->args) {
                $evaled_path = self::_getPathTo($stmt->args[0]->value, $file_name);

                if (!$evaled_path) {
                    return;
                }

                return dirname($evaled_path);
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\ConstFetch && $stmt->name instanceof PhpParser\Node\Name) {
            $const_name = implode('', $stmt->name->parts);

            if (defined($const_name)) {
                return constant($const_name);
            }

        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Dir) {
            return dirname($file_name);

        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\File) {
            return $file_name;
        }

        return null;
    }

    /**
     * @return string|null
     */
    protected static function _resolveIncludePath($file_name, $current_directory)
    {
        $paths = PATH_SEPARATOR == ':' ?
            preg_split('#(?<!phar):#', get_include_path()) :
            explode(PATH_SEPARATOR, get_include_path());

        foreach ($paths as $prefix) {
            $ds = substr($prefix, -1) == DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR;

            if ($prefix === '.') {
                $prefix = $current_directory;
            }

            $file = $prefix . $ds . $file_name;

            if (file_exists($file)) {
                return $file;
            }
        }
    }

    public static function setMockInterfaces(array $classes)
    {
        self::$_mock_interfaces = $classes;
    }

    public static function isMock($absolute_class)
    {
        return in_array($absolute_class, Config::getInstance()->getMockClasses());
    }

    /**
     * @return bool
     */
    protected static function _containsBooleanOr(PhpParser\Node\Expr\BinaryOp $stmt)
    {
        // we only want to discount expressions where either the whole thing is an or
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            return true;
        }

        // or both sides are ors
        if (($stmt->left instanceof PhpParser\Node\Expr\BinaryOp && $stmt->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) &&
            ($stmt->right instanceof PhpParser\Node\Expr\BinaryOp && $stmt->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr)) {
            return true;
        }

        return false;
    }

    public function getAliasedClasses()
    {
        return $this->_aliased_classes;
    }

    public static function getThisAssignments($method_id, $include_constructor = false)
    {
        $absolute_class = explode('::', $method_id)[0];

        $this_assignments = [];

        if ($include_constructor && isset(self::$_this_assignments[$absolute_class . '::__construct'])) {
            $this_assignments = self::$_this_assignments[$absolute_class . '::__construct'];
        }

        if (isset(self::$_this_assignments[$method_id])) {
            $this_assignments = TypeChecker::combineKeyedTypes($this_assignments, self::$_this_assignments[$method_id]);
        }

        if (isset(self::$_this_calls[$method_id])) {
            foreach (self::$_this_calls[$method_id] as $call) {
                $call_assingments = self::getThisAssignments($absolute_class . '::' . $call);
                $this_assignments = TypeChecker::combineKeyedTypes($this_assignments, $call_assingments);
            }
        }

        return $this_assignments;
    }
}
