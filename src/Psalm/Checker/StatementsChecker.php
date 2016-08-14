<?php

namespace Psalm\Checker;

use PhpParser;

use Psalm\IssueBuffer;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidNamespace;
use Psalm\Issue\InvalidIterator;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\MissingPropertyDeclaration;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullPropertyFetch;
use Psalm\Issue\NullReference;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\Issue\InvalidArrayAccess;
use Psalm\Issue\InvalidPropertyAssignment;
use Psalm\Issue\InvalidScalarArgument;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\InvalidStaticVariable;
use Psalm\Issue\FailedTypeResolution;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedConstant;
use Psalm\Issue\UndefinedFunction;
use Psalm\Issue\UndefinedProperty;
use Psalm\Issue\UndefinedThisProperty;
use Psalm\Issue\UndefinedVariable;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;

use Psalm\Type;
use Psalm\StatementsSource;
use Psalm\Config;
use Psalm\Context;

class StatementsChecker
{
    protected $stmts;

    protected $source;
    protected $all_vars = [];
    protected $warn_vars = [];
    protected $check_classes = true;
    protected $check_variables = true;
    protected $check_methods = true;
    protected $check_consts = true;
    protected $check_functions = true;
    protected $class_name;
    protected $parent_class;

    protected $namespace;
    protected $aliased_classes;
    protected $file_name;
    protected $is_static;
    protected $absolute_class;
    protected $type_checker;

    protected $available_functions = [];

    /**
     * A list of suppressed issues
     * @var array
     */
    protected $suppressed_issues;

    protected $require_file_name = null;

    protected static $method_call_index = [];
    protected static $existing_functions = [];
    protected static $reflection_functions = [];
    protected static $this_assignments = [];
    protected static $this_calls = [];

    protected static $mock_interfaces = [];

    protected static $call_map = null;

    public function __construct(StatementsSource $source, $enforce_variable_checks = false, $check_methods = true)
    {
        $this->source = $source;
        $this->check_classes = true;
        $this->check_methods = $check_methods;

        $this->check_consts = true;

        $this->file_name = $this->source->getFileName();
        $this->aliased_classes = $this->source->getAliasedClasses();
        $this->namespace = $this->source->getNamespace();
        $this->is_static = $this->source->isStatic();
        $this->absolute_class = $this->source->getAbsoluteClass();
        $this->class_name = $this->source->getClassName();
        $this->parent_class = $this->source->getParentClass();
        $this->suppressed_issues = $this->source->getSuppressedIssues();

        $config = Config::getInstance();

        $this->check_variables = !$config->excludeIssueInFile('UndefinedVariable', $this->file_name) || $enforce_variable_checks;

        $this->type_checker = new TypeChecker($source, $this);
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
                $file_checker = FileChecker::getFileCheckerFromFileName($this->file_name);
                $file_checker->registerFunction($stmt);
            }
        }

        foreach ($stmts as $stmt) {
            foreach (Config::getInstance()->getPlugins() as $plugin) {
                if ($plugin->checkStatement($stmt, $context, $this->file_name) === false) {
                    return false;
                }
            }

            if ($has_returned && !($stmt instanceof PhpParser\Node\Stmt\Nop) && !($stmt instanceof PhpParser\Node\Stmt\InlineHTML)) {
                echo('Warning: Expressions after return/throw/continue in ' . $this->file_name . ' on line ' . $stmt->getLine() . PHP_EOL);
                break;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $this->checkIf($stmt, $context, $for_vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $this->checkTryCatch($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                $this->checkFor($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                $this->checkForeach($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                $this->checkWhile($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                $this->checkDo($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Const_) {
                foreach ($stmt->consts as $const) {
                    $this->checkExpression($const->value, $context);

                    if (isset($const->value->inferredType) && !$const->value->inferredType->isMixed()) {
                        var_dump((string) $const->value->inferredType);
                    }
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Unset_) {
                // do nothing

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                $has_returned = true;
                $this->checkReturn($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Throw_) {
                $has_returned = true;
                $this->checkThrow($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                $this->checkSwitch($stmt, $context, $for_vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Break_) {
                // do nothing

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Continue_) {
                $has_returned = true;

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Static_) {
                $this->checkStatic($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
                foreach ($stmt->exprs as $expr) {
                    $this->checkExpression($expr, $context);
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_checker = new FunctionChecker($stmt, $this->source);
                $function_context = new Context($this->file_name, $context->self);
                $function_checker->check($function_context);

            } elseif ($stmt instanceof PhpParser\Node\Expr) {
                $this->checkExpression($stmt, $context);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\InlineHTML) {
                // do nothing

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    $this->aliased_classes[$use->alias] = implode('\\', $use->name->parts);
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Global_) {
                foreach ($stmt->vars as $var) {
                    if ($var instanceof PhpParser\Node\Expr\Variable) {
                        if (is_string($var->name)) {
                            $context->vars_in_scope[$var->name] = Type::getMixed();
                            $context->vars_possibly_in_scope[$var->name] = true;
                        } else {
                            $this->checkExpression($var, $context);
                        }
                    }
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->default) {
                        $this->checkExpression($prop->default, $context);

                        if (isset($prop->default->inferredType)) {
                            if (!$stmt->isStatic()) {
                                if ($this->checkPropertyAssignment($prop, $prop->name, $prop->default->inferredType, $context) === false) {
                                    return false;
                                }
                            }

                        }
                    }
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                foreach ($stmt->consts as $const) {
                    $this->checkExpression($const->value, $context);

                    if (isset($const->value->inferredType) && !$const->value->inferredType->isMixed()) {
                        ClassLikeChecker::setConstantType($this->absolute_class, $const->name, $const->value->inferredType);
                    }
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                (new ClassChecker($stmt, $this->source, $stmt->name))->check();

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                // do nothing

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                if ($this->namespace) {
                    if (IssueBuffer::accepts(
                        new InvalidNamespace('Cannot redeclare namespace', $this->require_file_name, $stmt->getLine()),
                        $this->suppressed_issues
                    )) {
                        return false;
                    }
                }

                $namespace_checker = new NamespaceChecker($stmt, $this->source);
                $namespace_checker->check(true);
            } else {
                var_dump('Unrecognised statement in ' . $this->file_name);
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
    protected function checkIf(PhpParser\Node\Stmt\If_ $stmt, Context $context, array &$for_vars_possibly_in_scope)
    {
        $if_context = clone $context;

        // we need to clone the current context so our ongoing updates to $context don't mess with elseif/else blocks
        $original_context = clone $context;

        if ($this->checkExpression($stmt->cond, $if_context) === false) {
            return false;
        }

        if ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp) {
            $reconcilable_if_types = $this->type_checker->getReconcilableTypeAssertions($stmt->cond, true);
            $negatable_if_types = $this->type_checker->getNegatableTypeAssertions($stmt->cond, true);
        }
        else {
            $reconcilable_if_types = $negatable_if_types = $this->type_checker->getTypeAssertions($stmt->cond, true);
        }

        $has_ending_statments = ScopeChecker::doesReturnOrThrow($stmt->stmts);

        $has_leaving_statments = $has_ending_statments || ScopeChecker::doesLeaveBlock($stmt->stmts, true, true);

        // we only need to negate the if types if there are throw/return/break/continue or else/elseif blocks
        $need_to_negate_if_types = $has_leaving_statments || $stmt->elseifs || $stmt->else;

        $negated_types = $negatable_if_types && $need_to_negate_if_types
                            ? TypeChecker::negateTypes($negatable_if_types)
                            : [];

        $negated_if_types = $negated_types;

        // if the if has an || in the conditional, we cannot easily reason about it
        if ($reconcilable_if_types) {
            $if_vars_in_scope_reconciled =
                TypeChecker::reconcileKeyedTypes(
                    $reconcilable_if_types,
                    $if_context->vars_in_scope,
                    $this->file_name,
                    $stmt->getLine(),
                    $this->suppressed_issues
                );

            if ($if_vars_in_scope_reconciled === false) {
                return false;
            }

            $if_context->vars_in_scope = $if_vars_in_scope_reconciled;
            $if_context->vars_possibly_in_scope = array_merge($reconcilable_if_types, $if_context->vars_possibly_in_scope);
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
                $context_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                    $negated_types,
                    $context->vars_in_scope,
                    $this->file_name,
                    $stmt->getLine(),
                    $this->suppressed_issues
                );

                if ($context_vars_reconciled === false) {
                    return false;
                }
                $context->vars_in_scope = $context_vars_reconciled;
                $mic_drop = true;
            }

            // update the parent context as necessary, but only if we can safely reason about type negation
            if ($negatable_if_types && !$mic_drop) {
                $context->update($old_if_context, $if_context, $has_leaving_statments, array_keys($negatable_if_types), $updated_vars);
            }

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
                $elseif_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                    $negated_types,
                    $elseif_context->vars_in_scope,
                    $this->file_name,
                    $stmt->getLine(),
                    $this->suppressed_issues
                );

                if ($elseif_vars_reconciled === false) {
                    return false;
                }
                $elseif_context->vars_in_scope = $elseif_vars_reconciled;
            }

            if ($elseif->cond instanceof PhpParser\Node\Expr\BinaryOp) {
                $reconcilable_elseif_types = $this->type_checker->getReconcilableTypeAssertions($elseif->cond, true);
                $negatable_elseif_types = $this->type_checker->getNegatableTypeAssertions($elseif->cond, true);
            }
            else {
                $reconcilable_elseif_types = $negatable_elseif_types = $this->type_checker->getTypeAssertions($elseif->cond, true);
            }

            $negated_elseif_types = $negatable_elseif_types
                                    ? TypeChecker::negateTypes($negatable_elseif_types)
                                    : [];

            $negated_types = array_merge($negated_types, $negated_elseif_types);

            // if the elseif has an || in the conditional, we cannot easily reason about it
            if (!($elseif->cond instanceof PhpParser\Node\Expr\BinaryOp) || !self::containsBooleanOr($elseif->cond)) {
                $elseif_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                    $reconcilable_elseif_types,
                    $elseif_context->vars_in_scope,
                    $this->file_name,
                    $stmt->getLine(),
                    $this->suppressed_issues
                );

                if ($elseif_vars_reconciled === false) {
                    return false;
                }

                $elseif_context->vars_in_scope = $elseif_vars_reconciled;
            }

            // check the elseif
            if ($this->checkExpression($elseif->cond, $elseif_context) === false) {
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

                if ($negatable_elseif_types) {
                    $context->update($old_elseif_context, $elseif_context, $has_leaving_statments, array_keys($negated_elseif_types), $updated_vars);
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
                $else_vars_reconciled = TypeChecker::reconcileKeyedTypes(
                    $negated_types,
                    $else_context->vars_in_scope,
                    $this->file_name,
                    $stmt->getLine(),
                    $this->suppressed_issues
                );

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
                    /** @var Context $original_context */
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
                if ($negatable_if_types) {
                    $context->update($old_else_context, $else_context, $has_leaving_statments, array_keys($negatable_if_types), $updated_vars);
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

    protected function checkStatic(PhpParser\Node\Stmt\Static_ $stmt, Context $context)
    {
        foreach ($stmt->vars as $var) {
            if (is_string($var->name)) {
                if ($this->check_variables) {
                    $context->vars_in_scope[$var->name] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var->name] = true;
                    $this->registerVariable($var->name, $var->getLine());
                }
            } else {
                if ($this->checkExpression($var->name, $context) === false) {
                    return false;
                }
            }

            if ($var->default) {
                if ($this->checkExpression($var->default, $context) === false) {
                    return false;
                }
            }
        }
    }

    /**
     * @return false|null
     */
    protected function checkExpression(PhpParser\Node\Expr $stmt, Context $context, $array_assignment = false)
    {
        foreach (Config::getInstance()->getPlugins() as $plugin) {

            if ($plugin->checkExpression($stmt, $context, $this->file_name) === false) {
                return false;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            return $this->checkVariable($stmt, $context, null, -1, $array_assignment);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Assign) {
            return $this->checkAssignment($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp) {
            return $this->checkAssignmentOperation($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\MethodCall) {
            return $this->checkMethodCall($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\StaticCall) {
            return $this->checkStaticCall($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            return $this->checkConstFetch($stmt);

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
            return $this->checkExpression($stmt->expr, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            return $this->checkExpression($stmt->expr, $context);

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
            return $this->checkClassConstFetch($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            return $this->checkPropertyFetch($stmt, $context, $array_assignment);

        } elseif ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
            return $this->checkStaticPropertyFetch($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\BitwiseNot) {
            return $this->checkExpression($stmt->expr, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            return $this->checkBinaryOp($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PostInc) {
            return $this->checkExpression($stmt->var, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PostDec) {
            return $this->checkExpression($stmt->var, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PreInc) {
            return $this->checkExpression($stmt->var, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PreDec) {
            return $this->checkExpression($stmt->var, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\New_) {
            return $this->checkNew($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Array_) {
            return $this->checkArray($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Scalar\Encapsed) {
            return $this->checkEncapsulatedString($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall) {
            return $this->checkFunctionCall($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            return $this->checkTernary($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            return $this->checkBooleanNot($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Empty_) {
            return $this->checkEmpty($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Closure) {
            $closure_checker = new ClosureChecker($stmt, $this->source);

            if ($this->checkClosureUses($stmt, $context) === false) {
                return false;
            }

            $use_context = new Context($this->file_name, $context->self);

            if (!$this->is_static) {
                $this_class = ClassLikeChecker::getThisClass() && ClassChecker::classExtends(ClassLikeChecker::getThisClass(), $this->absolute_class) ?
                    ClassLikeChecker::getThisClass() :
                    $context->self;

                if ($this_class) {
                    $use_context->vars_in_scope['this'] = new Type\Union([new Type\Atomic($this_class)]);
                }
            }

            foreach ($context->vars_in_scope as $var => $type) {
                if (strpos($var, 'this->') === 0) {
                    $use_context->vars_in_scope[$var] = clone $type;
                }
            }

            foreach ($context->vars_possibly_in_scope as $var => $type) {
                if (strpos($var, 'this->') === 0) {
                    $use_context->vars_possibly_in_scope[$var] = true;
                }
            }

            foreach ($stmt->uses as $use) {
                $use_context->vars_in_scope[$use->var] = isset($context->vars_in_scope[$use->var]) ? clone $context->vars_in_scope[$use->var] : Type::getMixed();
                $use_context->vars_possibly_in_scope[$use->var] = true;
            }

            $closure_checker->check($use_context, $this->check_methods);

        } elseif ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            return $this->checkArrayAccess($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            if ($this->checkExpression($stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getInt();

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            if ($this->checkExpression($stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getDouble();

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            if ($this->checkExpression($stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getBool();

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            if ($this->checkExpression($stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getString();

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            if ($this->checkExpression($stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getObject();

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            if ($this->checkExpression($stmt->expr, $context) === false) {
                return false;
            }
            $stmt->inferredType = Type::getArray();

        } elseif ($stmt instanceof PhpParser\Node\Expr\Clone_) {
            if ($this->checkExpression($stmt->expr, $context) === false) {
                return false;
            }

            if (property_exists($stmt->expr, 'inferredType')) {
                $stmt->inferredType = $stmt->expr->inferredType;
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\Instanceof_) {
            if ($this->checkExpression($stmt->expr, $context) === false) {
                return false;
            }

            if ($stmt->class instanceof PhpParser\Node\Name && !in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                if ($this->check_classes) {
                    if (ClassLikeChecker::checkClassName($stmt->class, $this->namespace, $this->aliased_classes, $this->file_name, $this->suppressed_issues) === false) {
                        return false;
                    }
                }
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Expr\Include_) {
            $this->checkInclude($stmt, $context);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Eval_) {
            $this->check_classes = false;
            $this->check_variables = false;

            if ($this->checkExpression($stmt->expr, $context) === false) {
                return false;
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignRef) {
            if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
                $context->vars_in_scope[$stmt->var->name] = Type::getMixed();
                $context->vars_possibly_in_scope[$stmt->var->name] = true;
                $this->registerVariable($stmt->var->name, $stmt->var->getLine());
            } else {
                if ($this->checkExpression($stmt->var, $context) === false) {
                    return false;
                }
            }

            if ($this->checkExpression($stmt->expr, $context) === false) {
                return false;
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\ErrorSuppress) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Expr\ShellExec) {
            if (IssueBuffer::accepts(
                new ForbiddenCode('Use of shell_exec', $this->file_name, $stmt->getLine()),
                $this->suppressed_issues
            )) {
                return false;
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\Print_) {
            if ($this->checkExpression($stmt->expr, $context) === false) {
                return false;
            }

        } else {
            var_dump('Unrecognised expression in ' . $this->file_name);
            var_dump($stmt);
        }
    }

    /**
     * @return false|null
     */
    protected function checkVariable(PhpParser\Node\Expr\Variable $stmt, Context $context, $method_id = null, $argument_offset = -1, $array_assignment = false)
    {
        if ($this->is_static && $stmt->name === 'this') {
            if (IssueBuffer::accepts(
                new InvalidStaticVariable('Invalid reference to $this in a static context', $this->file_name, $stmt->getLine()),
                $this->suppressed_issues
            )) {
                return false;
            }
        }

        if (!$this->check_variables) {
            $stmt->inferredType = Type::getMixed();

            if (is_string($stmt->name) && !isset($context->vars_in_scope[$stmt->name])) {
                $context->vars_in_scope[$stmt->name] = Type::getMixed();
                $context->vars_possibly_in_scope[$stmt->name] = true;
            }

            return;
        }

        if (in_array($stmt->name, ['_SERVER', '_GET', '_POST', '_COOKIE', '_REQUEST', '_FILES', '_ENV', 'GLOBALS', 'argv'])) {
            return;
        }

        if (!is_string($stmt->name)) {
            return $this->checkExpression($stmt->name, $context);
        }

        if ($method_id && isset($context->vars_in_scope[$stmt->name]) && !$context->vars_in_scope[$stmt->name]->isMixed()) {
            if ($this->checkFunctionArgumentType($context->vars_in_scope[$stmt->name], $method_id, $argument_offset, $this->file_name, $stmt->getLine()) === false) {
                return false;
            }
        }

        if ($stmt->name === 'this') {
            return;
        }

        if ($method_id && $this->isPassedByReference($method_id, $argument_offset)) {
            $this->assignByRefParam($stmt, $method_id, $context);
            return;
        }

        $var_name = $stmt->name;

        if (!isset($context->vars_in_scope[$var_name])) {
            if (!isset($context->vars_possibly_in_scope[$var_name]) || !isset($this->all_vars[$var_name])) {
                if ($array_assignment) {
                    // if we're in an array assignment, let's assign the variable
                    // because PHP allows it

                    $context->vars_in_scope[$var_name] = Type::getArray();
                    $context->vars_possibly_in_scope[$var_name] = true;
                    $this->registerVariable($var_name, $stmt->getLine());
                }
                else {
                    IssueBuffer::add(
                        new UndefinedVariable('Cannot find referenced variable $' . $var_name, $this->file_name, $stmt->getLine())
                    );

                    return false;
                }
            }

            if (isset($this->all_vars[$var_name]) && !isset($this->warn_vars[$var_name])) {
                $this->warn_vars[$var_name] = true;

                if (IssueBuffer::accepts(
                    new PossiblyUndefinedVariable(
                        'Possibly undefined variable $' . $var_name .', first seen on line ' . $this->all_vars[$var_name],
                        $this->file_name,
                        $stmt->getLine()
                    ),
                    $this->suppressed_issues
                )) {
                    return false;
                }
            }

        } else {
            $stmt->inferredType = $context->vars_in_scope[$var_name];
        }
    }

    protected function assignByRefParam(PhpParser\Node\Expr $stmt, $method_id, Context $context)
    {
        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            $property_id = $stmt->name;
        }
        else if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && $stmt->var->name === 'this') {
            $property_id = $stmt->var->name . '->' . $stmt->name;
        }
        else {
            throw new \InvalidArgumentException('Bad property passed to checkMethodParam');
        }

        if (!isset($context->vars_in_scope[$property_id])) {
            $context->vars_possibly_in_scope[$property_id] = true;
            $this->registerVariable($property_id, $stmt->getLine());

            if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && $this->source->getMethodId()) {
                $this_method_id = $this->source->getMethodId();

                if (!isset(self::$this_assignments[$this_method_id])) {
                    self::$this_assignments[$this_method_id] = [];
                }

                self::$this_assignments[$this_method_id][$stmt->name] = Type::getMixed();
            }
        }

        $context->vars_in_scope[$property_id] = Type::getMixed();
    }

    protected function checkPropertyFetch(PhpParser\Node\Expr\PropertyFetch $stmt, Context $context, $array_assignment = false)
    {
        if (!is_string($stmt->name)) {
            if ($this->checkExpression($stmt->name, $context) === false) {
                return false;
            }
        }

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if ($this->checkVariable($stmt->var, $context) === false) {
                return false;
            }

            $var_name = is_string($stmt->name) ? $stmt->name : null;
            $var_id = self::getVarId($stmt);

            $stmt_var_type = null;

            if (isset($context->vars_in_scope[$var_id])) {
                // we don't need to check anything
                $stmt->inferredType = $context->vars_in_scope[$var_id];
                return;
            }

            if (isset($context->vars_in_scope[$stmt->var->name])) {
                $stmt_var_type = $context->vars_in_scope[$stmt->var->name];
            }
            elseif (isset($stmt->var->inferredType)) {
                $stmt_var_type = $stmt->var->inferredType;
            }

            if ($stmt_var_type) {
                if (!$stmt_var_type->isMixed()) {
                    if ($stmt_var_type->isNull()) {
                        if (IssueBuffer::accepts(
                            new NullReference(
                                'Cannot get property on null variable $' . $var_id,
                                $this->file_name,
                                $stmt->getLine()
                            ),
                            $this->suppressed_issues
                        )) {
                            return false;
                        }

                        return;
                    }

                    if ($stmt_var_type->isNullable()) {
                        if (IssueBuffer::accepts(
                            new NullPropertyFetch(
                                'Cannot get property on possibly null variable $' . $var_id,
                                $this->file_name,
                                $stmt->getLine()
                            ),
                            $this->suppressed_issues
                        )) {
                            return false;
                        }

                        $stmt->inferredType = Type::getNull();
                    }

                    if ($stmt_var_type->isObjectType() && is_string($stmt->name)) {
                        foreach ($stmt_var_type->types as $lhs_type_part) {
                            if ($lhs_type_part->isNull()) {
                                continue;
                            }

                            // stdClass and SimpleXMLElement are special cases where we cannot infer the return types
                            // but we don't want to throw an error
                            // Hack has a similar issue: https://github.com/facebook/hhvm/issues/5164
                            if ($lhs_type_part->isObject() || in_array(strtolower($lhs_type_part->value), ['stdclass', 'simplexmlelement'])) {
                                $stmt->inferredType = Type::getMixed();
                                continue;
                            }

                            if (!$lhs_type_part->isObjectType()) {
                                // @todo InvalidPropertyFetch
                                continue;
                            }

                            if (method_exists((string) $lhs_type_part, '__get')) {
                                continue;
                            }

                            if (!ClassChecker::classExists($lhs_type_part->value)) {
                                if (InterfaceChecker::interfaceExists($lhs_type_part->value)) {
                                    if (IssueBuffer::accepts(
                                        new NoInterfaceProperties(
                                            'Interfaces cannot have properties',
                                            $this->file_name,
                                            $stmt->getLine()
                                        ),
                                        $this->suppressed_issues
                                    )) {
                                        return false;
                                    }

                                    return;
                                }

                                if (IssueBuffer::accepts(
                                    new UndefinedClass(
                                        'Cannot get properties of undefined class ' . $lhs_type_part->value,
                                        $this->file_name,
                                        $stmt->getLine()
                                    ),
                                    $this->suppressed_issues
                                )) {
                                    return false;
                                }

                                return;
                            }

                            if ($var_name === 'this'
                                || $lhs_type_part->value === $context->self
                                || ($this->source->getSource() instanceof TraitChecker && $lhs_type_part->value === $this->source->getAbsoluteClass())
                            ) {
                                $class_visibility = \ReflectionProperty::IS_PRIVATE;
                            }
                            elseif (ClassChecker::classExtends($lhs_type_part->value, $context->self)) {
                                $class_visibility = \ReflectionProperty::IS_PROTECTED;
                            }
                            else {
                                $class_visibility = \ReflectionProperty::IS_PUBLIC;
                            }

                            $class_properties = ClassLikeChecker::getInstancePropertiesForClass(
                                $lhs_type_part->value,
                                $class_visibility
                            );

                            if (!$class_properties || !isset($class_properties[$stmt->name])) {
                                if ($stmt->var->name === 'this') {
                                    if (IssueBuffer::accepts(
                                        new UndefinedThisProperty(
                                            'Property ' . $lhs_type_part->value .'::$' . $stmt->name . ' is not defined',
                                            $this->file_name,
                                            $stmt->getLine()
                                        ),
                                        $this->suppressed_issues
                                    )) {
                                        return false;
                                    }
                                }
                                else {
                                    if (IssueBuffer::accepts(
                                        new UndefinedProperty(
                                            'Property ' . $lhs_type_part->value .'::$' . $stmt->name . ' is not defined',
                                            $this->file_name,
                                            $stmt->getLine()
                                        ),
                                        $this->suppressed_issues
                                    )) {
                                        return false;
                                    }
                                }

                                return;
                            }

                            if (isset($stmt->inferredType)) {
                                $stmt->inferredType = Type::combineUnionTypes($class_properties[$stmt->name], $stmt->inferredType);
                            }
                            else {
                                $stmt->inferredType = $class_properties[$stmt->name];
                            }
                        }

                        return;
                    }
                    else {
                        // @todo ScalarPropertyFetch issue
                    }
                }
                else {
                    // @todo MixedPropertyFetch issue
                }
            }

            return;
        }

        return $this->checkExpression($stmt->var, $context);
    }

    /**
     * @param  PhpParser\Node\Expr\PropertyFetch|PhpParser\Node\Stmt\PropertyProperty    $stmt
     * @param  string     $prop_name
     * @param  Type\Union $assignment_type
     * @param  Context    $context
     * @return false|null
     */
    protected function checkPropertyAssignment($stmt, $prop_name, Type\Union $assignment_type, Context $context)
    {
        $class_property_types = [];

        if ($stmt instanceof PhpParser\Node\Stmt\PropertyProperty) {
            $class_properties = ClassLikeChecker::getInstancePropertiesForClass($context->self, \ReflectionProperty::IS_PRIVATE);

            $class_property_types[] = $class_properties[$prop_name];

            $var_id = 'this->' . $prop_name;
        }
        else {
            if (!isset($context->vars_in_scope[$stmt->var->name])) {
                if ($this->checkVariable($stmt->var, $context) === false) {
                    return false;
                }

                return;
            }

            $lhs_type = $context->vars_in_scope[$stmt->var->name];

            if ($stmt->var->name === 'this' && !$this->source->getClassLikeChecker()) {
                if (IssueBuffer::accepts(
                    new InvalidScope('Cannot use $this when not inside class', $this->file_name, $stmt->getLine()),
                    $this->suppressed_issues
                )) {
                    return false;
                }
            }

            $var_id = self::getVarId($stmt);

            if ($lhs_type->isMixed()) {
                // @todo MixedAssignment
                return;
            }

            if ($lhs_type->isNull()) {
                // @todo NullPropertyAssignment
                return;
            }

            if ($lhs_type->isNullable()) {
                // @todo NullablePropertyAssignment
            }

            $has_regular_setter = false;

            foreach ($lhs_type->types as $lhs_type_part) {
                if ($lhs_type_part->isNull()) {
                    continue;
                }

                if (method_exists((string) $lhs_type_part, '__set')) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    continue;
                }

                $has_regular_setter = true;

                if (!$lhs_type_part->isObjectType()) {
                    if (IssueBuffer::accepts(
                        new InvalidPropertyAssignment(
                            '$' . $var_id . ' with possible non-object type \'' . $lhs_type_part . '\' cannot be assigned to',
                            $this->file_name,
                            $stmt->getLine()
                        ),
                        $this->suppressed_issues
                    )) {
                        return false;
                    }

                    continue;
                }

                if ($lhs_type_part->isObject()) {
                    continue;
                }

                if ($lhs_type_part->value === 'stdClass') {
                    $class_property_types[] = new Type\Union([$lhs_type_part]);
                    continue;
                }

                if (self::isMock($lhs_type_part->value)) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    return;
                }

                if ($stmt->var->name === 'this' || $lhs_type_part->value === $context->self) {
                    $class_visibility = \ReflectionProperty::IS_PRIVATE;
                }
                elseif (ClassChecker::classExtends($lhs_type_part->value, $context->self)) {
                    $class_visibility = \ReflectionProperty::IS_PROTECTED;
                }
                else {
                    $class_visibility = \ReflectionProperty::IS_PUBLIC;
                }

                if (!ClassChecker::classExists($lhs_type_part->value)) {
                    if (InterfaceChecker::interfaceExists($lhs_type_part->value)) {
                        if (IssueBuffer::accepts(
                            new NoInterfaceProperties(
                                'Interfaces cannot have properties',
                                $this->file_name,
                                $stmt->getLine()
                            ),
                            $this->suppressed_issues
                        )) {
                            return false;
                        }

                        return;
                    }

                    if (IssueBuffer::accepts(
                        new UndefinedClass(
                            'Cannot set properties of undefined class ' . $lhs_type_part->value,
                            $this->file_name,
                            $stmt->getLine()
                        ),
                        $this->suppressed_issues
                    )) {
                        return false;
                    }

                    return;
                }

                $class_properties = ClassLikeChecker::getInstancePropertiesForClass(
                    $lhs_type_part->value,
                    $class_visibility
                );

                if (!isset($class_properties[$prop_name])) {
                    // @todo UndefinedProperty
                    continue;
                }

                $class_property_types[] = $class_properties[$prop_name];
            }

            if (!$has_regular_setter) {
                return;
            }

            // because we don't want to be assigning for property declarations
            $context->vars_in_scope[$var_id] = $assignment_type;
        }

        if (count($class_property_types) === 1 && isset($class_property_types[0]->types['stdClass'])) {
            $context->vars_in_scope[$var_id] = Type::getMixed();
            return;
        }

        if (!$class_property_types) {
            if (IssueBuffer::accepts(
                new MissingPropertyDeclaration(
                    'Missing property declaration for $' . $var_id,
                    $this->file_name,
                    $stmt->getLine()
                ),
                $this->suppressed_issues
            )) {
                return false;
            }

            return;
        }

        if ($assignment_type->isMixed()) {
            return;
        }

        foreach ($class_property_types as $class_property_type) {
            if ($class_property_type->isMixed()) {
                continue;
            }

            if (!$assignment_type->isIn($class_property_type)) {
                if (IssueBuffer::accepts(
                    new InvalidPropertyAssignment(
                        '$' . $var_id . ' with declared type \'' . $class_property_type . '\' cannot be assigned type \'' . $assignment_type . '\'',
                        $this->file_name,
                        $stmt->getLine()
                    ),
                    $this->suppressed_issues
                )) {
                    return false;
                }
            }
        }
    }

    protected function checkNew(PhpParser\Node\Expr\New_ $stmt, Context $context)
    {
        $absolute_class = null;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                if ($this->check_classes) {
                    if (ClassLikeChecker::checkClassName($stmt->class, $this->namespace, $this->aliased_classes, $this->file_name, $this->suppressed_issues) === false) {
                        return false;
                    }
                }

                $absolute_class = ClassLikeChecker::getAbsoluteClassFromName($stmt->class, $this->namespace, $this->aliased_classes);
            }
            else {
                switch ($stmt->class->parts[0]) {
                    case 'self':
                        $absolute_class = $context->self;
                        break;

                    case 'parent':
                        $absolute_class = $context->parent;
                        break;

                    case 'static':
                        // @todo maybe we can do better here
                        $absolute_class = $context->self;
                        break;
                }
            }
        }

        if ($absolute_class) {
            $stmt->inferredType = new Type\Union([new Type\Atomic($absolute_class)]);

            if (method_exists($absolute_class, '__construct')) {
                $method_id = $absolute_class . '::__construct';

                if ($this->checkMethodParams($stmt->args, $method_id, $context, $stmt->getLine()) === false) {
                    return false;
                }
            }
        }
    }

    protected function checkArray(PhpParser\Node\Expr\Array_ $stmt, Context $context)
    {
        // if the array is empty, this special type allows us to match any other array type against it
        if (empty($stmt->items)) {
            $stmt->inferredType = new Type\Union([new Type\Generic('array', [new Type\Union([new Type\Atomic('empty')])], true)]);
            return;
        }

        foreach ($stmt->items as $item) {
            if ($item->key) {
                if ($this->checkExpression($item->key, $context) === false) {
                    return false;
                }
            }

            if ($this->checkExpression($item->value, $context) === false) {
                return false;
            }
        }

        $stmt->inferredType = Type::getArray();
    }

    protected function checkTryCatch(PhpParser\Node\Stmt\TryCatch $stmt, Context $context)
    {
        $this->check($stmt->stmts, $context);

        // clone context for catches after running the try block, as
        // we optimistically assume it only failed at the very end
        $original_context = clone $context;

        foreach ($stmt->catches as $catch) {
            $catch_context = clone $original_context;

            if ($catch->type) {
                $catch_context->vars_in_scope[$catch->var] = new Type\Union([
                    new Type\Atomic(ClassLikeChecker::getAbsoluteClassFromName($catch->type, $this->namespace, $this->aliased_classes))
                ]);
            }
            else {
                $catch_context->vars_in_scope[$catch->var] = Type::getMixed();
            }

            $catch_context->vars_possibly_in_scope[$catch->var] = true;

            $this->registerVariable($catch->var, $catch->getLine());

            if ($this->check_classes) {
                if (ClassLikeChecker::checkClassName($catch->type, $this->namespace, $this->aliased_classes, $this->file_name, $this->suppressed_issues) === false) {
                    return;
                }
            }

            $this->check($catch->stmts, $catch_context);

            if (!ScopeChecker::doesReturnOrThrow($catch->stmts)) {
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

    protected function checkFor(PhpParser\Node\Stmt\For_ $stmt, Context $context)
    {
        $for_context = clone $context;
        $for_context->in_loop = true;

        foreach ($stmt->init as $init) {
            if ($this->checkExpression($init, $for_context) === false) {
                return false;
            }
        }

        foreach ($stmt->cond as $condition) {
            if ($this->checkExpression($condition, $for_context) === false) {
                return false;
            }
        }

        foreach ($stmt->loop as $expr) {
            if ($this->checkExpression($expr, $for_context) === false) {
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

    protected function checkForeach(PhpParser\Node\Stmt\Foreach_ $stmt, Context $context)
    {
        if ($this->checkExpression($stmt->expr, $context) === false) {
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

            if (isset($stmt->expr->inferredType)) {
                $iterator_type = $stmt->expr->inferredType;
            }
            elseif (isset($foreach_context->vars_in_scope[$var_id])) {
                $iterator_type = $foreach_context->vars_in_scope[$var_id];
            }
            else {
                $iterator_type = null;
            }

            if ($iterator_type) {
                foreach ($iterator_type->types as $return_type) {
                    // if it's an empty array, we cannot iterate over it
                    if ((string) $return_type === 'array<empty>') {
                        continue;
                    }

                    if ($return_type instanceof Type\Generic) {
                        $value_type = $return_type->type_params[0];
                    }

                    switch ($return_type->value) {
                        case 'mixed':
                        case 'empty':
                            break;

                        case 'array':
                        case 'object':
                            break;

                        case 'null':
                            if (IssueBuffer::accepts(
                                new NullReference('Cannot iterate over ' . $return_type->value, $this->file_name, $stmt->getLine()),
                                $this->suppressed_issues
                            )) {
                                return false;
                            }
                            break;

                        case 'string':
                        case 'void':
                        case 'int':
                        case 'bool':
                        case 'false':
                            if (IssueBuffer::accepts(
                                new InvalidIterator('Cannot iterate over ' . $return_type->value, $this->file_name, $stmt->getLine()),
                                $this->suppressed_issues
                            )) {
                                return false;
                            }
                            break;

                        default:
                            if (ClassChecker::classImplements($return_type->value, 'Iterator')) {
                                $iterator_method = $return_type->value . '::current';
                                $iterator_class_type = ClassMethodChecker::getMethodReturnTypes($iterator_method);

                                if ($iterator_class_type) {
                                    $value_type = self::fleshOutTypes($iterator_class_type, [], $return_type->value, $iterator_method);
                                }
                            }

                            if ($return_type->value !== 'Traversable' && $return_type->value !== $this->class_name) {
                                if (ClassLikeChecker::checkAbsoluteClassOrInterface($return_type->value, $this->file_name, $stmt->getLine(), $this->suppressed_issues) === false) {
                                    return false;
                                }
                            }
                    }
                }
            }

            if ($value_type && $value_type instanceof Type\Atomic) {
                $value_type = new Type\Union([$value_type]);
            }

            $foreach_context->vars_in_scope[$stmt->valueVar->name] = $value_type ? $value_type : Type::getMixed();
            $foreach_context->vars_possibly_in_scope[$stmt->valueVar->name] = true;
            $this->registerVariable($stmt->valueVar->name, $stmt->getLine());
        }

        CommentChecker::getTypeFromComment((string) $stmt->getDocComment(), $foreach_context, $this->source, null);

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

    protected function checkWhile(PhpParser\Node\Stmt\While_ $stmt, Context $context)
    {
        $while_context = clone $context;

        if ($this->checkExpression($stmt->cond, $while_context) === false) {
            return false;
        }

        $while_types = $this->type_checker->getTypeAssertions($stmt->cond, true);

        // if the while has an or as the main component, we cannot safely reason about it
        if ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp && self::containsBooleanOr($stmt->cond)) {
            // do nothing
        }
        else {
            $while_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
                $while_types,
                $while_context->vars_in_scope,
                $this->file_name,
                $stmt->getLine(),
                $this->suppressed_issues
            );

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

    protected function checkDo(PhpParser\Node\Stmt\Do_ $stmt, Context $context)
    {
        // do not clone context for do, because it executes in current scope always
        if ($this->check($stmt->stmts, $context) === false) {
            return false;
        }

        return $this->checkExpression($stmt->cond, $context);
    }

    protected function checkBinaryOp(PhpParser\Node\Expr\BinaryOp $stmt, Context $context, $nesting = 0)
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat && $nesting > 20) {
            // ignore deeply-nested string concatenation
        }
        else if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
            $left_type_assertions = $this->type_checker->getReconcilableTypeAssertions($stmt->left);

            if ($this->checkExpression($stmt->left, $context) === false) {
                return false;
            }

            // while in an and, we allow scope to boil over to support
            // statements of the form if ($x && $x->foo())
            $op_vars_in_scope = TypeChecker::reconcileKeyedTypes(
                $left_type_assertions,
                $context->vars_in_scope,
                $this->file_name,
                $stmt->getLine(),
                $this->suppressed_issues
            );

            if ($op_vars_in_scope === false) {
                return false;
            }

            $op_context = clone $context;
            $op_context->vars_in_scope = $op_vars_in_scope;

            if ($this->checkExpression($stmt->right, $op_context) === false) {
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
            $left_type_assertions = $this->type_checker->getNegatableTypeAssertions($stmt->left);

            $negated_type_assertions = TypeChecker::negateTypes($left_type_assertions);

            if ($this->checkExpression($stmt->left, $context) === false) {
                return false;
            }

            // while in an or, we allow scope to boil over to support
            // statements of the form if ($x === null || $x->foo())
            $op_vars_in_scope = TypeChecker::reconcileKeyedTypes(
                $negated_type_assertions,
                $context->vars_in_scope,
                $this->file_name,
                $stmt->getLine(),
                $this->suppressed_issues
            );

            if ($op_vars_in_scope === false) {
                return false;
            }

            $op_context = clone $context;
            $op_context->vars_in_scope = $op_vars_in_scope;

            if ($this->checkExpression($stmt->right, $op_context) === false) {
                return false;
            }

            $context->vars_possibly_in_scope = array_merge($op_context->vars_possibly_in_scope, $context->vars_possibly_in_scope);
        }
        else {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
                $stmt->inferredType = Type::getString();
            }

            if ($stmt->left instanceof PhpParser\Node\Expr\BinaryOp) {
                if ($this->checkBinaryOp($stmt->left, $context, ++$nesting) === false) {
                    return false;
                }
            }
            else {
                if ($this->checkExpression($stmt->left, $context) === false) {
                    return false;
                }
            }

            if ($stmt->right instanceof PhpParser\Node\Expr\BinaryOp) {
                if ($this->checkBinaryOp($stmt->right, $context, ++$nesting) === false) {
                    return false;
                }
            }
            else {
                if ($this->checkExpression($stmt->right, $context) === false) {
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

    protected function checkAssignment(PhpParser\Node\Expr\Assign $stmt, Context $context)
    {
        $var_id = self::getVarId($stmt->var);

        if ($this->checkExpression($stmt->expr, $context) === false) {
            // if we're not exiting immediately, make everything mixed
            $context->vars_in_scope[$var_id] = Type::getMixed();

            return false;
        }

        $type_in_comments = CommentChecker::getTypeFromComment((string) $stmt->getDocComment(), $context, $this->source, $var_id);

        if ($type_in_comments) {
            $return_type = $type_in_comments;
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
            if ($this->checkArrayAssignment($stmt->var, $context, $return_type) === false) {
                return false;
            }

        } else if ($stmt->var instanceof PhpParser\Node\Expr\PropertyFetch &&
                    $stmt->var->var instanceof PhpParser\Node\Expr\Variable &&
                    is_string($stmt->var->name)) {

            $this->checkPropertyAssignment($stmt->var, $stmt->var->name, $return_type, $context);

            $context->vars_possibly_in_scope[$var_id] = true;
        }

        if ($var_id && isset($context->vars_in_scope[$var_id]) && $context->vars_in_scope[$var_id]->isVoid()) {
            if (IssueBuffer::accepts(
                new FailedTypeResolution('Cannot assign $' . $var_id . ' to type void', $this->file_name, $stmt->getLine()),
                $this->suppressed_issues
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

    protected function checkArrayAssignment(PhpParser\Node\Expr\ArrayDimFetch $stmt, Context $context, Type\Union $assignment_type)
    {
        if ($this->checkExpression($stmt->var, $context, true) === false) {
            return false;
        }

        $var_id = self::getVarId($stmt->var);

        if (isset($stmt->var->inferredType)) {
            $return_type = $stmt->var->inferredType;

            if (!$return_type->isMixed()) {

                foreach ($return_type->types as &$type) {
                    if ($type->isScalarType() && !$type->isString()) {
                        if (IssueBuffer::accepts(
                            new InvalidArrayAssignment(
                                'Cannot assign value on variable $' . $var_id . ' of scalar type ' . $type->value,
                                $this->file_name,
                                $stmt->getLine()
                            ),
                            $this->suppressed_issues
                        )) {
                            return false;
                        }

                        continue;
                    }
                    $refined_type = $this->refineArrayType($type, $assignment_type, $var_id, $stmt->getLine());

                    if ($refined_type === false) {
                        return false;
                    }

                    if ($refined_type === null) {
                        continue;
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
     * @return Type\Atomic|null|false
     */
    protected function refineArrayType(Type\Atomic $type, Type\Union $assignment_type, $var_id, $line_number)
    {
        if ($type->value === 'null') {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Cannot assign value on possibly null array ' . $var_id,
                    $this->file_name,
                    $line_number
                ),
                $this->suppressed_issues
            )) {
                return false;
            }

            return $type;
        }

        foreach ($assignment_type->types as $at) {
            if ($type->value === 'string' && $at->isString()) {
                return;
            }
        }

        if ($type->value !== 'array' && !ClassChecker::classImplements($type->value, 'ArrayAccess')) {
            if (IssueBuffer::accepts(
                new InvalidArrayAssignment(
                    'Cannot assign value on variable ' . $var_id . ' that does not implement ArrayAccess',
                    $this->file_name,
                    $line_number
                ),
                $this->suppressed_issues
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

                $type->type_params[0] = $assignment_type;
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

    protected function checkAssignmentOperation(PhpParser\Node\Expr\AssignOp $stmt, Context $context)
    {
        if ($this->checkExpression($stmt->var, $context) === false) {
            return false;
        }

        return $this->checkExpression($stmt->expr, $context);
    }

    protected function checkMethodCall(PhpParser\Node\Expr\MethodCall $stmt, Context $context)
    {
        if ($this->checkExpression($stmt->var, $context) === false) {
            return false;
        }

        $class_type = null;
        $method_id = null;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if (is_string($stmt->var->name) && $stmt->var->name === 'this' && !$this->class_name) {
                if (IssueBuffer::accepts(
                    new InvalidScope('Use of $this in non-class context', $this->file_name, $stmt->getLine()),
                    $this->suppressed_issues
                )) {
                    return false;
                }
            }
        }

        $var_id = self::getVarId($stmt->var);

        $class_type = isset($context->vars_in_scope[$var_id]) ? $context->vars_in_scope[$var_id] : null;

        if (isset($stmt->var->inferredType)) {
            $class_type = $stmt->var->inferredType;
        }
        elseif (!$class_type) {
            $stmt->inferredType = Type::getMixed();
        }

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable && $stmt->var->name === 'this' && is_string($stmt->name)) {
            $this_method_id = $this->source->getMethodId();

            if (!isset(self::$this_calls[$this_method_id])) {
                self::$this_calls[$this_method_id] = [];
            }

            self::$this_calls[$this_method_id][] = $stmt->name;

            if (ClassLikeChecker::getThisClass() &&
                (
                    ClassLikeChecker::getThisClass() === $this->absolute_class ||
                    ClassChecker::classExtends(ClassLikeChecker::getThisClass(), $this->absolute_class) ||
                    trait_exists($this->absolute_class)
                )) {

                $method_id = $this->absolute_class . '::' . $stmt->name;

                if ($this->checkInsideMethod($method_id, $context) === false) {
                    return false;
                }
            }
        }

        if (!$this->check_methods) {
            return;
        }

        if ($class_type && is_string($stmt->name)) {
            foreach ($class_type->types as $type) {
                $absolute_class = $type->value;

                switch ($absolute_class) {
                    case 'null':
                        if (IssueBuffer::accepts(
                            new NullReference(
                                'Cannot call method ' . $stmt->name . ' on possibly null variable ' . $class_type,
                                $this->file_name,
                                $stmt->getLine()
                            ),
                            $this->suppressed_issues
                        )) {
                            return false;
                        }
                        break;

                    case 'int':
                    case 'bool':
                    case 'array':
                        if (IssueBuffer::accepts(
                            new InvalidArgument(
                                'Cannot call method ' . $stmt->name . ' on ' . $class_type . ' variable',
                                $this->file_name,
                                $stmt->getLine()
                            ),
                            $this->suppressed_issues
                        )) {
                            return false;
                        }
                        break;

                    case 'mixed':
                        if (IssueBuffer::accepts(
                            new MixedMethodCall(
                                'Cannot call method ' . $stmt->name . ' on a mixed variable',
                                $this->file_name,
                                $stmt->getLine()
                            ),
                            $this->suppressed_issues
                        )) {
                            return false;
                        }
                        break;

                    default:
                        if ($absolute_class[0] === strtoupper($absolute_class[0]) && !method_exists($absolute_class, '__call') && !self::isMock($absolute_class)) {
                            $does_class_exist = ClassLikeChecker::checkAbsoluteClassOrInterface($absolute_class, $this->file_name, $stmt->getLine(), $this->suppressed_issues);

                            if (!$does_class_exist) {
                                return $does_class_exist;
                            }

                            $method_id = $absolute_class . '::' . $stmt->name;

                            if (!isset(self::$method_call_index[$method_id])) {
                                self::$method_call_index[$method_id] = [];
                            }

                            if ($this->source instanceof ClassMethodChecker) {
                                self::$method_call_index[$method_id][] = $this->source->getMethodId();
                            }
                            else {
                                self::$method_call_index[$method_id][] = $this->source->getFileName();
                            }



                            $does_method_exist = ClassMethodChecker::checkMethodExists($method_id, $this->file_name, $stmt->getLine(), $this->suppressed_issues);

                            if (!$does_method_exist) {
                                return $does_method_exist;
                            }

                            /**
                            if (ClassLikeChecker::getThisClass() && ClassChecker::classExtends(ClassLikeChecker::getThisClass(), $this->absolute_class)) {
                                $calling_context = $context->self;
                            }
                            **/

                            if (ClassMethodChecker::checkMethodVisibility($method_id, $context->self, $this->source, $stmt->getLine(), $this->suppressed_issues) === false) {
                                return false;
                            }

                            if (ClassMethodChecker::checkMethodNotDeprecated($method_id, $this->file_name, $stmt->getLine(), $this->suppressed_issues) === false) {
                                return false;
                            }

                            $return_types = ClassMethodChecker::getMethodReturnTypes($method_id);

                            if ($return_types) {
                                $return_types = self::fleshOutTypes($return_types, $stmt->args, $absolute_class, $method_id);

                                $stmt->inferredType = $return_types;
                            }
                        }
                }
            }
        }

        if ($this->checkMethodParams($stmt->args, $method_id, $context, $stmt->getLine()) === false) {
            return false;
        }
    }

    protected function checkInsideMethod($method_id, Context $context)
    {
        $method_checker = ClassLikeChecker::getMethodChecker($method_id);

        if ($method_checker && $method_checker->getMethodId() !== $this->source->getMethodId()) {
            $this_context = new Context($this->file_name, (string) $context->vars_in_scope['this']);

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

    protected function checkClosureUses(PhpParser\Node\Expr\Closure $stmt, Context $context)
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
                    if ($this->check_variables) {
                        IssueBuffer::add(
                            new UndefinedVariable('Cannot find referenced variable $' . $use->var, $this->file_name, $use->getLine())
                        );

                        return false;
                    }
                }

                if (isset($this->all_vars[$use->var])) {
                    if (!isset($this->warn_vars[$use->var])) {
                        $this->warn_vars[$use->var] = true;
                        if (IssueBuffer::accepts(
                            new PossiblyUndefinedVariable(
                                'Possibly undefined variable $' . $use->var . ', first seen on line ' . $this->all_vars[$use->var],
                                $this->file_name,
                                $use->getLine()
                            ),
                            $this->suppressed_issues
                        )) {
                            return false;
                        }
                    }

                    return;
                }

                if ($this->check_variables) {
                    IssueBuffer::add(
                        new UndefinedVariable('Cannot find referenced variable $' . $use->var, $this->file_name, $use->getLine())
                    );

                    return false;
                }
            }
        }
    }

    /**
     * @return void
     */
    protected function checkStaticCall(PhpParser\Node\Expr\StaticCall $stmt, Context $context)
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
                if ($this->parent_class === null) {
                    if (IssueBuffer::accepts(
                        new ParentNotFound('Cannot call method on parent as this class does not extend another', $this->file_name, $stmt->getLine()),
                        $this->suppressed_issues
                    )) {
                        return false;
                    }
                }

                $absolute_class = $this->parent_class;
            } else {
                $absolute_class = ($this->namespace ? $this->namespace . '\\' : '') . $this->class_name;
            }

        } elseif ($this->check_classes) {
            $does_class_exist = ClassLikeChecker::checkClassName($stmt->class, $this->namespace, $this->aliased_classes, $this->file_name, $this->suppressed_issues);

            if (!$does_class_exist) {
                return $does_class_exist;
            }

            $absolute_class = ClassLikeChecker::getAbsoluteClassFromName($stmt->class, $this->namespace, $this->aliased_classes);
        }

        if (!$this->check_methods) {
            return;
        }

        if ($stmt->class->parts === ['parent'] && is_string($stmt->name)) {
            if (ClassLikeChecker::getThisClass()) {
                $method_id = $absolute_class . '::' . $stmt->name;

                if ($this->checkInsideMethod($method_id, $context) === false) {
                    return false;
                }
            }
        }

        if ($absolute_class && is_string($stmt->name) && !method_exists($absolute_class, '__callStatic') && !self::isMock($absolute_class)) {
            $method_id = $absolute_class . '::' . $stmt->name;

            if (!isset(self::$method_call_index[$method_id])) {
                self::$method_call_index[$method_id] = [];
            }

            if ($this->source instanceof ClassMethodChecker) {
                self::$method_call_index[$method_id][] = $this->source->getMethodId();
            }
            else {
                self::$method_call_index[$method_id][] = $this->source->getFileName();
            }

            $does_method_exist = ClassMethodChecker::checkMethodExists($method_id, $this->file_name, $stmt->getLine(), $this->suppressed_issues);

            if (!$does_method_exist) {
                return $does_method_exist;
            }

            if (ClassMethodChecker::checkMethodVisibility($method_id, $context->self, $this->source, $stmt->getLine(), $this->suppressed_issues) === false) {
                return false;
            }

            if ($this->is_static) {
                if (ClassMethodChecker::checkMethodStatic($method_id, $this->file_name, $stmt->getLine(), $this->suppressed_issues) === false) {
                    return false;
                }
            }
            else {
                if ($stmt->class->parts[0] === 'self' && $stmt->name !== '__construct') {
                    if (ClassMethodChecker::checkMethodStatic($method_id, $this->file_name, $stmt->getLine(), $this->suppressed_issues) === false) {
                        return false;
                    }
                }
            }

            if (ClassMethodChecker::checkMethodNotDeprecated($method_id, $this->file_name, $stmt->getLine(), $this->suppressed_issues) === false) {
                return false;
            }

            $return_types = ClassMethodChecker::getMethodReturnTypes($method_id);

            if ($return_types) {
                $return_types = self::fleshOutTypes($return_types, $stmt->args, $stmt->class->parts === ['parent'] ? $this->absolute_class : $absolute_class, $method_id);
                $stmt->inferredType = $return_types;
            }
        }

        return $this->checkMethodParams($stmt->args, $method_id, $context, $stmt->getLine());
    }

    /**
     * @param  Type\Atomic                  $return_type
     * @param  array<PhpParser\Node\Arg>    $args
     * @param  string|null                  $calling_class
     * @param  string|null                  $method_id
     * @return Type\Union
     */
    public static function fleshOutTypes(Type\Union $return_type, array $args, $calling_class, $method_id)
    {
        foreach ($return_type->types as $key => $return_type_part) {
            self::fleshOutAtomicType($return_type_part, $args, $calling_class, $method_id);

            if ($return_type_part->value !== $key) {
                unset($return_type->types[$key]);
                $return_type->types[$return_type_part->value] = $return_type_part;
            }
        }

        return $return_type;
    }

    /**
     * @param  Type\Atomic                  &$return_type
     * @param  array<PhpParser\Node\Arg>    $args
     * @param  string|null                  $calling_class
     * @param  string|null                  $method_id
     * @return void
     */
    protected static function fleshOutAtomicType(Type\Atomic &$return_type, array $args, $calling_class, $method_id)
    {
        if ($return_type->value === '$this' || $return_type->value === 'static' || $return_type->value === 'self') {
            if (!$calling_class) {
                throw new \InvalidArgumentException('Cannot handle ' . $return_type->value . ' when $calling_class is empty');
            }

            $return_type->value = $calling_class;
        }
        else if ($return_type->value[0] === '$') {
            $method_params = ClassMethodChecker::getMethodParams($method_id);

            foreach ($args as $i => $arg) {
                $method_param = $method_params[$i];

                if ($return_type->value === '$' . $method_param['name']) {
                    $arg_value = $arg->value;
                    if ($arg_value instanceof PhpParser\Node\Scalar\String_) {
                        $return_type->value = preg_replace('/^\\\/', '', $arg_value->value);
                    }
                }
            }

            if ($return_type->value[0] === '$') {
                $return_type = Type::getMixed(false);
            }
        }

        if ($return_type instanceof Type\Generic) {
            foreach ($return_type->type_params as $type_param) {
                $type_param = self::fleshOutTypes($type_param, $args, $calling_class, $method_id);
            }
        }
    }

    protected static function getMethodFromCallBlock($call, array $args, $method_id)
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

    protected function checkMethodParams(array $args, $method_id, Context $context, $line_number)
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
                        if ($this->checkFunctionArgumentType($context->vars_in_scope[$property_id], $method_id, $i, $this->file_name, $arg->getLine()) === false) {
                            return false;
                        }
                    }

                    if ($this->isPassedByReference($method_id, $i)) {
                        $this->assignByRefParam($arg->value, $method_id, $context);
                    }
                    else {
                        if ($this->checkPropertyFetch($arg->value, $context) === false) {
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
                    if ($this->checkVariable($arg->value, $context, $method_id, $i) === false) {
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
                if ($this->checkExpression($arg->value, $context) === false) {
                    return false;
                }
            }

            if ($method_id && isset($arg->value->inferredType)) {
                if ($this->checkFunctionArgumentType($arg->value->inferredType, $method_id, $i, $this->file_name, $arg->value->getLine()) === false) {
                    return false;
                }
            }
        }

        if ($method_id) {
            $method_params = ClassMethodChecker::getMethodParams($method_id);

            if (count($args) > count($method_params)) {
                if (IssueBuffer::accepts(
                    new TooManyArguments('Too many arguments for method ' . $method_id, $this->file_name, $line_number),
                    $this->suppressed_issues
                )) {
                    return false;
                }

                return;
            }

            if (count($args) < count($method_params)) {
                for ($i = count($args); $i < count($method_params); $i++) {
                    $param = $method_params[$i];

                    if (!$param['is_optional']) {
                        if (IssueBuffer::accepts(
                            new TooFewArguments('Too few arguments for method ' . $method_id, $this->file_name, $line_number),
                            $this->suppressed_issues
                        )) {
                            return false;
                        }

                        break;
                    }
                }
            }
        }
    }

    protected function checkConstFetch(PhpParser\Node\Expr\ConstFetch $stmt)
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

    protected function checkClassConstFetch(PhpParser\Node\Expr\ClassConstFetch $stmt, Context $context)
    {
        if ($this->check_consts && $stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts !== ['static']) {
            if ($stmt->class->parts === ['self']) {
                $absolute_class = $context->self;
            } else {
                $absolute_class = ClassLikeChecker::getAbsoluteClassFromName($stmt->class, $this->namespace, $this->aliased_classes);
                if (ClassLikeChecker::checkAbsoluteClassOrInterface($absolute_class, $this->file_name, $stmt->getLine(), $this->suppressed_issues) === false) {
                    return false;
                }
            }

            $const_id = $absolute_class . '::' . $stmt->name;

            $class_constants = ClassLikeChecker::getConstantsForClass($absolute_class, \ReflectionProperty::IS_PUBLIC);

            if (!isset($class_constants[$stmt->name])) {
                if (IssueBuffer::accepts(
                    new UndefinedConstant('Const ' . $const_id . ' is not defined', $this->file_name, $stmt->getLine()),
                    $this->suppressed_issues
                )) {
                    return false;
                }
            }
            else {
                $stmt->inferredType = $class_constants[$stmt->name];
            }

            return;
        }

        if ($stmt->class instanceof PhpParser\Node\Expr) {
            if ($this->checkExpression($stmt->class, $context) === false) {
                return false;
            }
        }
    }

    /**
     * @return null|false
     */
    protected function checkStaticPropertyFetch(PhpParser\Node\Expr\StaticPropertyFetch $stmt, Context $context)
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
                $absolute_class = $this->parent_class;
            } else {
                $absolute_class = ($this->namespace ? $this->namespace . '\\' : '') . $this->class_name;
            }
        }
        elseif ($this->check_classes) {
            if (ClassLikeChecker::checkClassName($stmt->class, $this->namespace, $this->aliased_classes, $this->file_name, $this->suppressed_issues) === false) {
                return false;
            }
            $absolute_class = ClassLikeChecker::getAbsoluteClassFromName($stmt->class, $this->namespace, $this->aliased_classes);
        }

        if ($absolute_class && $this->check_variables && is_string($stmt->name) && !self::isMock($absolute_class)) {
            if ($absolute_class === $context->self
                || ($this->source->getSource() instanceof TraitChecker && $absolute_class === $this->source->getAbsoluteClass())
            ) {
                $class_visibility = \ReflectionProperty::IS_PRIVATE;
            }
            elseif ($context->self && ClassChecker::classExtends($context->self, $absolute_class)) {
                $class_visibility = \ReflectionProperty::IS_PROTECTED;
            }
            else {
                $class_visibility = \ReflectionProperty::IS_PUBLIC;
            }

            $visible_class_properties = ClassLikeChecker::getStaticPropertiesForClass(
                $absolute_class,
                $class_visibility
            );

            if (!isset($visible_class_properties[$stmt->name])) {
                $var_id = $absolute_class . '::$' . $stmt->name;

                $all_class_properties = [];

                if ($absolute_class !== $context->self) {
                    $all_class_properties = ClassLikeChecker::getStaticPropertiesForClass(
                        $absolute_class,
                        \ReflectionProperty::IS_PRIVATE
                    );
                }

                if ($all_class_properties && isset($all_class_properties[$stmt->name])) {
                    // @todo change issue type
                    IssueBuffer::add(
                        new UndefinedProperty('Static property ' . $var_id . ' is not visible in this context', $this->file_name, $stmt->getLine())
                    );
                }
                else {
                    IssueBuffer::add(
                        new UndefinedProperty('Static property ' . $var_id . ' does not exist', $this->file_name, $stmt->getLine())
                    );
                }

                return false;
            }
        }
    }

    protected function checkReturn(PhpParser\Node\Stmt\Return_ $stmt, Context $context)
    {
        $type_in_comments = CommentChecker::getTypeFromComment((string) $stmt->getDocComment(), $context, $this->source);

        if ($stmt->expr) {
            if ($this->checkExpression($stmt->expr, $context) === false) {
                return false;
            }

            if ($type_in_comments) {
                $stmt->inferredType = $type_in_comments;
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

        if ($this->source instanceof FunctionChecker) {
            $this->source->addReturnTypes($stmt->expr ? (string) $stmt->inferredType : '', $context);
        }
    }

    protected function checkTernary(PhpParser\Node\Expr\Ternary $stmt, Context $context)
    {
        if ($this->checkExpression($stmt->cond, $context) === false) {
            return false;
        }

        $t_if_context = clone $context;

        if ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp) {
            $reconcilable_if_types = $this->type_checker->getReconcilableTypeAssertions($stmt->cond, true);
            $negatable_if_types = $this->type_checker->getNegatableTypeAssertions($stmt->cond, true);
        }
        else {
            $reconcilable_if_types = $negatable_if_types = $this->type_checker->getTypeAssertions($stmt->cond, true);
        }

        $if_return_type = null;

        $t_if_vars_in_scope_reconciled =
            TypeChecker::reconcileKeyedTypes(
                $reconcilable_if_types,
                $t_if_context->vars_in_scope,
                $this->file_name,
                $stmt->getLine(),
                $this->suppressed_issues
            );

        if ($t_if_vars_in_scope_reconciled === false) {
            return false;
        }

        $t_if_context->vars_in_scope = $t_if_vars_in_scope_reconciled;

        if ($stmt->if) {
            if ($this->checkExpression($stmt->if, $t_if_context) === false) {
                return false;
            }
        }

        $t_else_context = clone $context;

        if ($negatable_if_types) {
            $negated_if_types = TypeChecker::negateTypes($negatable_if_types);

            $t_else_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
                $negated_if_types,
                $t_else_context->vars_in_scope,
                $this->file_name,
                $stmt->getLine(),
                $this->suppressed_issues
            );

            if ($t_else_vars_in_scope_reconciled === false) {
                return false;
            }
            $t_else_context->vars_in_scope = $t_else_vars_in_scope_reconciled;
        }

        if ($this->checkExpression($stmt->else, $t_else_context) === false) {
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
                $if_return_type_reconciled = TypeChecker::reconcileTypes('!empty', $stmt->cond->inferredType, '', $this->file_name, $stmt->getLine(), $this->suppressed_issues);

                if ($if_return_type_reconciled === false) {
                    return false;
                }

                $lhs_type = $if_return_type_reconciled;
            }
        }

        if (!$lhs_type || !isset($stmt->else->inferredType)) {
            $stmt->inferredType = Type::getMixed();
        }
        else {
            $stmt->inferredType = Type::combineUnionTypes($lhs_type, $stmt->else->inferredType);
        }
    }

    protected function checkBooleanNot(PhpParser\Node\Expr\BooleanNot $stmt, Context $context)
    {
        return $this->checkExpression($stmt->expr, $context);
    }

    protected function checkEmpty(PhpParser\Node\Expr\Empty_ $stmt, Context $context)
    {
        return $this->checkExpression($stmt->expr, $context);
    }

    protected function checkThrow(PhpParser\Node\Stmt\Throw_ $stmt, Context $context)
    {
        return $this->checkExpression($stmt->expr, $context);
    }

    protected function checkSwitch(PhpParser\Node\Stmt\Switch_ $stmt, Context $context, array &$for_vars_possibly_in_scope)
    {
        $type_candidate_var = null;

        if ($this->checkExpression($stmt->cond, $context) === false) {
            return false;
        }

        if (isset($stmt->cond->inferredType) && array_values($stmt->cond->inferredType->types)[0] instanceof Type\T) {
            $type_candidate_var = array_values($stmt->cond->inferredType->types)[0]->typeof;
        }

        $original_context = clone $context;

        $case_types = [];

        $new_vars_in_scope = null;
        $new_vars_possibly_in_scope = [];

        $redefined_vars = null;

        // the last statement always breaks, by default
        $last_case_exit_type = 'break';

        $case_exit_types = new \SplFixedArray(count($stmt->cases));

        $has_default = false;

        // create a map of case statement -> ultimate exit type
        for ($i = count($stmt->cases) - 1; $i >= 0; $i--) {
            $case = $stmt->cases[$i];

            if (ScopeChecker::doesReturnOrThrow($case->stmts)) {
                $last_case_exit_type = 'return_throw';
            }
            elseif (ScopeChecker::doesEverBreakOrContinue($case->stmts, true)) {
                $last_case_exit_type = 'continue';
            }
            elseif (ScopeChecker::doesEverBreakOrContinue($case->stmts)) {
                $last_case_exit_type = 'break';
            }

            $case_exit_types[$i] = $last_case_exit_type;
        }

        for ($i = 0, $l = count($stmt->cases); $i < $l; $i++) {
            $case = $stmt->cases[$i];
            $case_exit_type = $case_exit_types[$i];

            if ($case->cond) {
                if ($this->checkExpression($case->cond, $context) === false) {
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
                $case_context = clone $original_context;

                $case_context->vars_in_scope = array_merge($case_context->vars_in_scope, $switch_vars);
                $case_context->vars_possibly_in_scope = array_merge($case_context->vars_possibly_in_scope, $switch_vars);

                $old_case_context = clone $case_context;

                $this->check($case->stmts, $case_context);

                $last_stmt = $case->stmts[count($case->stmts) - 1];

                // has a return/throw at end
                $has_ending_statments = ScopeChecker::doesReturnOrThrow($case->stmts);

                if ($case_exit_type !== 'return_throw') {
                    $vars = array_diff_key($case_context->vars_possibly_in_scope, $original_context->vars_possibly_in_scope);

                    // if we're leaving this block, add vars to outer for loop scope
                    if ($case_exit_type === 'continue') {
                        $for_vars_possibly_in_scope = array_merge($vars, $for_vars_possibly_in_scope);
                    }
                    else {
                        $case_redefined_vars = Context::getRedefinedVars($original_context, $case_context);

                        Type::redefineGenericUnionTypes($case_redefined_vars, $context);

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

            if (!$case->cond) {
                $has_default = true;
            }
        }

        // only update vars if there is a default
        // if that default has a throw/return/continue, that should be handled above
        if ($has_default) {
            if ($new_vars_in_scope) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $new_vars_in_scope);
            }

            if ($redefined_vars) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $redefined_vars);
            }
        }

        $context->vars_possibly_in_scope = array_merge($context->vars_possibly_in_scope, $new_vars_possibly_in_scope);
    }

    protected function checkFunctionArgumentType(Type\Union $input_type, $method_id, $argument_offset, $file_name, $line_number)
    {
        if (strpos($method_id, '::') === false) {
            return;
        }

        $method_params = ClassMethodChecker::getMethodParams($method_id);

        if (!isset($method_params[$argument_offset])) {
            return;
        }

        $param_type = $method_params[$argument_offset]['type'];

        if ($param_type->isMixed()) {
            return;
        }

        if ($input_type->isMixed()) {
            // @todo make this a config
            return;
        }

        if ($input_type->isNullable() && !$param_type->isNullable()) {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Argument ' . ($argument_offset + 1) . ' of ' . $method_id . ' cannot be null, possibly null value provided',
                    $file_name,
                    $line_number
                ),
                $this->suppressed_issues
            )) {
                return false;
            }
        }

        foreach ($input_type->types as $input_type_part) {
            if ($input_type_part->isNull()) {
                continue;
            }

            $type_match_found = false;
            $scalar_type_match_found = false;

            foreach ($param_type->types as $param_type_part) {
                if ($param_type_part->isNull()) {
                    continue;
                }

                if ($input_type_part->value === $param_type_part->value ||
                    ClassChecker::classExtendsOrImplements($input_type_part->value, $param_type_part->value) ||
                    self::isMock($input_type_part->value)
                ) {
                    $type_match_found = true;
                    break;
                }

                if ($input_type_part->value === 'false' && $param_type_part->value === 'bool') {
                    $type_match_found = true;
                }

                if ($input_type_part->value === 'int' && $param_type_part->value === 'float') {
                    $type_match_found = true;
                }

                if ($param_type_part->isNumeric() && $input_type_part->isNumericType()) {
                    $type_match_found = true;
                }

                if ($param_type_part->isCallable() && ($input_type_part->value === 'string' || $input_type_part->value === 'array')) {
                    // @todo add value checks if possible here
                    $type_match_found = true;
                }

                if ($input_type_part->isScalarType()) {
                    if ($param_type_part->isScalarType()) {
                        $scalar_type_match_found = true;
                    }
                }
                else if ($param_type_part->isObject()) {
                    $type_match_found = true;
                }

                if (ClassChecker::classExtendsOrImplements($param_type_part->value, $input_type_part->value)) {
                    // @todo handle coercion
                    $type_match_found = true;
                    break;
                }

            }

            if (!$type_match_found) {
                if ($scalar_type_match_found) {
                    if (IssueBuffer::accepts(
                        new InvalidScalarArgument(
                            'Argument ' . ($argument_offset + 1) . ' of ' . $method_id . ' expects ' . $param_type . ', ' . $input_type . ' provided',
                            $file_name,
                            $line_number
                        ),
                        $this->suppressed_issues
                    )) {
                        return false;
                    }
                }
                else if (IssueBuffer::accepts(
                    new InvalidArgument(
                        'Argument ' . ($argument_offset + 1) . ' of ' . $method_id . ' expects ' . $param_type . ', ' . $input_type . ' provided',
                        $file_name,
                        $line_number
                    ),
                    $this->suppressed_issues
                )) {
                    return false;
                }
            }
        }
    }

    protected function checkFunctionCall(PhpParser\Node\Expr\FuncCall $stmt, Context $context)
    {
        $method = $stmt->name;

        if ($method instanceof PhpParser\Node\Name) {
            if ($method->parts === ['method_exists']) {
                $this->check_methods = false;

            } elseif ($method->parts === ['function_exists']) {
                $this->check_functions = false;

            } elseif ($method->parts === ['defined']) {
                $this->check_consts = false;

            } elseif ($method->parts === ['extract']) {
                $this->check_variables = false;

            } elseif ($method->parts === ['var_dump'] || $method->parts === ['die'] || $method->parts === ['exit']) {
                if (IssueBuffer::accepts(
                    new ForbiddenCode('Unsafe ' . implode('', $method->parts), $this->file_name, $stmt->getLine()),
                    $this->suppressed_issues
                )) {
                    return false;
                }
            }
        }

        $method_id = null;

        if ($stmt->name instanceof PhpParser\Node\Name && $this->check_functions) {
            $method_id = implode('', $stmt->name->parts);

            if ($context->self) {
                //$method_id = $this->absolute_class . '::' . $method_id;
            }

            if ($this->checkFunctionExists($method_id, $stmt) === false) {
                return false;
            }
        }

        foreach ($stmt->args as $i => $arg) {
            if ($arg->value instanceof PhpParser\Node\Expr\Variable) {
                if ($method_id) {
                    if ($this->checkVariable($arg->value, $context, $method_id, $i) === false) {
                        return false;
                    }
                }
                else {
                    if ($this->checkVariable($arg->value, $context) === false) {
                        return false;
                    }
                }
            }
            else {
                if ($this->checkExpression($arg->value, $context) === false) {
                    return false;
                }
            }
        }

        if ($stmt->name instanceof PhpParser\Node\Name && $this->check_functions) {
            $stmt->inferredType = self::getFunctionReturnTypeFromCallMap($method_id, $stmt->args);
        }

        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['get_class'] && $stmt->args) {
            $var = $stmt->args[0]->value;

            if ($var instanceof PhpParser\Node\Expr\Variable && is_string($var->name)) {
                $stmt->inferredType = new Type\Union([new Type\T($var->name)]);
            }
        }
    }

    protected static function getFunctionReturnTypeFromCallMap($function_id, array $call_args)
    {
        $call_map = self::getCallMap();

        $call_map_key = strtolower($function_id);

        if (!isset($call_map[$call_map_key]) || !$call_map[$call_map_key][0]) {
            return Type::getMixed();
        }

        if (in_array($call_map_key, ['str_replace', 'preg_replace', 'preg_replace_callback'])) {
            if (isset($call_args[2]->value->inferredType)) {

                $subject_type = $call_args[2]->value->inferredType;

                if (!$subject_type->isString() && $subject_type->isArray()) {
                    return Type::getArray();
                }

                return Type::getString();
            }
        }

        if (in_array($call_map_key, ['pathinfo'])) {
            if (isset($call_args[1])) {
                return Type::getString();
            }

            return Type::getArray();
        }

        return Type::parseString($call_map[$call_map_key][0]);
    }

    /**
     * @param  PhpParser\Node\Expr\ArrayDimFetch $stmt
     * @param  array                             &$context->vars_in_scope
     * @param  array                             &$context->vars_possibly_in_scope
     * @return false|null
     */
    protected function checkArrayAccess(PhpParser\Node\Expr\ArrayDimFetch $stmt, Context $context)
    {
        if ($this->checkExpression($stmt->var, $context) === false) {
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
            if ($this->checkExpression($stmt->dim, $context) === false) {
                return false;
            }

            if (isset($stmt->dim->inferredType) && $var_type && $var_type->isString()) {
                foreach ($stmt->dim->inferredType->types as $at) {
                    if ($at->isString()) {
                        $var_id = self::getVarId($stmt->var);

                        if (IssueBuffer::accepts(
                            new InvalidArrayAccess(
                                'Cannot access value on string variable ' . $var_id . ' using string offset',
                                $this->file_name,
                                $stmt->getLine()
                            ),
                            $this->suppressed_issues
                        )) {
                            return false;
                        }
                    }
                }
            }
        }
    }

    protected function checkEncapsulatedString(PhpParser\Node\Scalar\Encapsed $stmt, Context $context)
    {
        foreach ($stmt->parts as $part) {
            if ($this->checkExpression($part, $context) === false) {
                return false;
            }
        }
    }

    public function registerVariable($var_name, $line_number)
    {
        if (!isset($this->all_vars[$var_name])) {
            $this->all_vars[$var_name] = $line_number;
        }
    }

    /**
     * @return false|null
     */
    public function checkFunctionExists($method_id, $stmt)
    {
        if (isset(self::$existing_functions[$method_id])) {
            return;
        }

        $file_checker = FileChecker::getFileCheckerFromFileName($this->file_name);

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
            if (IssueBuffer::accepts(
                new UndefinedFunction('Function ' . $method_id . ' does not exist', $this->file_name, $stmt->getLine()),
                $this->suppressed_issues
            )) {
                return false;
            }
        }

        self::$existing_functions[$method_id] = 1;
    }

    protected function checkInclude(PhpParser\Node\Expr\Include_ $stmt, Context $context)
    {
        if ($this->checkExpression($stmt->expr, $context) === false) {
            return false;
        }

        $path_to_file = null;

        if ($stmt->expr instanceof PhpParser\Node\Scalar\String_) {
            $path_to_file = $stmt->expr->value;

            // attempts to resolve using get_include_path dirs
            $include_path = self::resolveIncludePath($path_to_file, dirname($this->file_name));
            $path_to_file = $include_path ? $include_path : $path_to_file;

            if ($path_to_file[0] !== '/') {
                $path_to_file = getcwd() . '/' . $path_to_file;
            }
        }
        else {
            $path_to_file = self::getPathTo($stmt->expr, $this->file_name);
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

            /*
            if (in_array($path_to_file, FileChecker::getIncludesToIgnore())) {
                $this->check_classes = false;
                $this->check_variables = false;

                return;
            }
             */

            if (file_exists($path_to_file)) {
                if ($this->source instanceof FileChecker) {
                    $file_checker = new FileChecker($path_to_file, []);
                    $file_checker->check(true, true, $context);
                }
                else {
                    $include_stmts = FileChecker::getStatementsForFile($path_to_file);
                    $this->check($include_stmts, $context);
                }

                return;
            }

            $this->check_classes = false;
            $this->check_variables = false;
        }
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

    protected function isPassedByReference($method_id, $argument_offset)
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

        $file_checker = FileChecker::getFileCheckerFromFileName($this->file_name);

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
        if (empty(self::$method_call_index[$method_id])) {
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

        $parents = self::$method_call_index[$method_id];
        $ignore = [$method_id];
        $entry_points = [];

        while (!empty($parents)) {
            $parent_method_id = array_shift($parents);
            $ignore[] = $parent_method_id;
            $new_parents = self::findParents($parent_method_id, $ignore);

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

    protected static function findParents($method_id, array $ignore)
    {
        if (empty(self::$method_call_index[$method_id])) {
            return null;
        }

        return array_diff(array_unique(self::$method_call_index[$method_id]), $ignore);
    }

    protected static function getPathTo(PhpParser\Node\Expr $stmt, $file_name)
    {
        if ($file_name[0] !== '/') {
            $file_name = getcwd() . '/' . $file_name;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            return $stmt->value;

        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            $left_string = self::getPathTo($stmt->left, $file_name);
            $right_string = self::getPathTo($stmt->right, $file_name);

            if ($left_string && $right_string) {
                return $left_string . $right_string;
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall &&
            $stmt->name instanceof PhpParser\Node\Name &&
            $stmt->name->parts === ['dirname']) {

            if ($stmt->args) {
                $evaled_path = self::getPathTo($stmt->args[0]->value, $file_name);

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
    protected static function resolveIncludePath($file_name, $current_directory)
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
        self::$mock_interfaces = $classes;
    }

    public static function isMock($absolute_class)
    {
        return in_array($absolute_class, Config::getInstance()->getMockClasses());
    }

    /**
     * @return bool
     */
    protected static function containsBooleanOr(PhpParser\Node\Expr\BinaryOp $stmt)
    {
        // we only want to discount expressions where either the whole thing is an or
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
            return true;
        }

        return false;
    }

    public function getAliasedClasses()
    {
        return $this->aliased_classes;
    }

    public static function getThisAssignments($method_id, $include_constructor = false)
    {
        $absolute_class = explode('::', $method_id)[0];

        $this_assignments = [];

        if ($include_constructor && isset(self::$this_assignments[$absolute_class . '::__construct'])) {
            $this_assignments = self::$this_assignments[$absolute_class . '::__construct'];
        }

        if (isset(self::$this_assignments[$method_id])) {
            $this_assignments = TypeChecker::combineKeyedTypes($this_assignments, self::$this_assignments[$method_id]);
        }

        if (isset(self::$this_calls[$method_id])) {
            foreach (self::$this_calls[$method_id] as $call) {
                $call_assingments = self::getThisAssignments($absolute_class . '::' . $call);
                $this_assignments = TypeChecker::combineKeyedTypes($this_assignments, $call_assingments);
            }
        }

        return $this_assignments;
    }

    /**
     * Gets the method/function call map
     *
     * @return array<array<string>>
     */
    protected static function getCallMap()
    {
        if (self::$call_map !== null) {
            return self::$call_map;
        }

        $call_map = require_once(__DIR__.'/../CallMap.php');

        self::$call_map = [];

        foreach ($call_map as $key => $value) {
            self::$call_map[strtolower($key)] = $value;
        }

        return self::$call_map;
    }
}
