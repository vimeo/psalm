<?php

namespace CodeInspector;

use PhpParser;

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
    protected $_class_name;
    protected $_class_extends;

    protected $_namespace;
    protected $_aliased_classes;
    protected $_file_name;
    protected $_is_static;
    protected $_absolute_class;

    protected static $_method_return_types = [];
    protected static $_existing_methods = [];
    protected static $_reflection_functions = [];
    protected static $_method_comments = [];
    protected static $_method_files = [];
    protected static $_method_params = [];
    protected static $_method_namespaces = [];
    protected static $_method_param_types = [];
    protected static $_static_methods = [];
    protected static $_declaring_classes = [];
    protected static $_existing_static_vars = [];
    protected static $_existing_properties = [];

    public function __construct(StatementsSource $source = null, $check_variables = true)
    {
        $this->_source = $source;
        $this->_check_classes = true;
        $this->_check_methods = true;
        $this->_check_variables = $check_variables;
        $this->_check_consts = true;

        $this->_file_name = $this->_source->getFileName();
        $this->_aliased_classes = $this->_source->getAliasedClasses();
        $this->_namespace = $this->_source->getNamespace();
        $this->_is_static = $this->_source->isStatic();
        $this->_absolute_class = $this->_source->getAbsoluteClass();
        $this->_class_name = $this->_source->getClassName();
        $this->_class_extends = $this->_source->getClassExtends();
    }

    public function check(array $stmts, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $has_returned = false;

        foreach ($stmts as $stmt) {
            if ($has_returned) {
                echo('Warning: Expressions after return in ' . $this->_file_name . ' on line ' . $stmt->getLine() . PHP_EOL);
                break;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $this->_checkIf($stmt, $vars_in_scope, $vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $this->_checkTryCatch($stmt, $vars_in_scope, $vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                $this->_checkFor($stmt, $vars_in_scope, $vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                $this->_checkForeach($stmt, $vars_in_scope, $vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                $this->_checkWhile($stmt, $vars_in_scope, $vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                $this->_checkDo($stmt, $vars_in_scope, $vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Const_) {
                foreach ($stmt->consts as $const) {
                    $this->_checkExpression($const->value, $vars_in_scope, $vars_possibly_in_scope);
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Unset_) {
                // do nothing

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                $has_returned = true;
                $this->_checkReturn($stmt, $vars_in_scope, $vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Throw_) {
                $this->_checkThrow($stmt, $vars_in_scope, $vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                $this->_checkSwitch($stmt, $vars_in_scope, $vars_possibly_in_scope);

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Break_) {
                // do nothing

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Continue_) {
                // do nothing

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Static_) {
                foreach ($stmt->vars as $var) {
                    if ($var instanceof PhpParser\Node\Stmt\StaticVar) {
                        if (is_string($var->name)) {
                            if ($this->_check_variables) {
                                $vars_in_scope[$var->name] = true;
                                $vars_possibly_in_scope[$var->name] = true;
                                $this->registerVariable($var->name, $var->getLine());
                            }
                        } else {
                            $this->_checkExpression($var->name, $vars_in_scope, $vars_possibly_in_scope);
                        }

                        if ($var->default) {
                            $this->_checkExpression($var->default, $vars_in_scope, $vars_possibly_in_scope);
                        }
                    } else {
                        $this->_checkExpression($var, $vars_in_scope, $vars_possibly_in_scope);
                    }
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
                foreach ($stmt->exprs as $expr) {
                    $this->_checkExpression($expr, $vars_in_scope, $vars_possibly_in_scope);
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_checker = new FunctionChecker($stmt, $this->_source);
                $function_checker->check();
                $file_checker = FileChecker::getFileCheckerFromFileName($this->_file_name);
                $file_checker->registerFunction($stmt, $this->_absolute_class);

            } elseif ($stmt instanceof PhpParser\Node\Expr) {
                $this->_checkExpression($stmt, $vars_in_scope, $vars_possibly_in_scope);

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
                            $vars_in_scope[$var->name] = true;
                            $vars_possibly_in_scope[$var->name] = true;
                        } else {
                            $this->_checkExpression($var, $vars_in_scope, $vars_possibly_in_scope);
                        }
                    }
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->default) {
                        $this->_checkExpression($prop->default, $vars_in_scope, $vars_possibly_in_scope);
                    }
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {


            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                // do nothing

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                // do nothing

            } else {
                var_dump('Unrecognised statement in ' . $this->_file_name);
                var_dump($stmt);
            }
        }
    }

    protected function _checkIf(PhpParser\Node\Stmt\If_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkCondition($stmt->cond, $vars_in_scope, $vars_possibly_in_scope);

        $instanceof_class = null;

        $if_types = [];

        if ($stmt->cond instanceof PhpParser\Node\Expr\Instanceof_) {
            $if_types = $this->_getInstanceOfTypes($stmt->cond);
        }

        $if_vars = array_merge($vars_in_scope, $if_types);
        $if_vars_possibly_in_scope = array_merge($vars_possibly_in_scope, $if_types);

        $this->check($stmt->stmts, $if_vars, $if_vars_possibly_in_scope);

        $new_vars = null;
        $new_vars_possibly_in_scope = [];

        if (count($stmt->stmts)) {
            $last_stmt = $stmt->stmts[count($stmt->stmts) - 1];

            if (!($last_stmt instanceof PhpParser\Node\Stmt\Return_ || $last_stmt instanceof PhpParser\Node\Stmt\Continue_)) {
                $new_vars = array_diff_key($if_vars, $vars_in_scope);
            }

            if (!($last_stmt instanceof PhpParser\Node\Stmt\Return_)) {
                $new_vars_possibly_in_scope = array_merge(array_diff_key($if_vars_possibly_in_scope, $vars_possibly_in_scope), $new_vars_possibly_in_scope);
            }
        }

        foreach ($stmt->elseifs as $elseif) {
            $elseif_vars = array_merge([], $vars_in_scope);
            $elseif_vars_possibly_in_scope = array_merge([], $vars_possibly_in_scope);

            $this->_checkElseIf($elseif, $elseif_vars, $elseif_vars_possibly_in_scope);

            if (count($elseif->stmts)) {
                $last_stmt = $elseif->stmts[count($elseif->stmts) - 1];

                if (!($last_stmt instanceof PhpParser\Node\Stmt\Return_ || $last_stmt instanceof PhpParser\Node\Stmt\Continue_)) {
                    if ($new_vars === null) {
                        $new_vars = array_diff_key($elseif_vars, $vars_in_scope);
                    } else {
                        foreach ($new_vars as $new_var => $type) {
                            if (!isset($elseif_vars[$new_var])) {
                                unset($new_vars[$new_var]);
                            }
                        }
                    }
                }

                if (!($last_stmt instanceof PhpParser\Node\Stmt\Return_)) {
                    $new_vars_possibly_in_scope = array_merge(array_diff_key($elseif_vars_possibly_in_scope, $vars_possibly_in_scope), $new_vars_possibly_in_scope);
                }
            }
        }

        if ($stmt->else) {
            $else_vars = array_merge([], $vars_in_scope);
            $else_vars_possibly_in_scope = array_merge([], $vars_possibly_in_scope);

            $this->_checkElse($stmt->else, $else_vars, $else_vars_possibly_in_scope);

            if (count($stmt->else->stmts)) {
                $last_stmt = $stmt->else->stmts[count($stmt->else->stmts) - 1];

                if (!($last_stmt instanceof PhpParser\Node\Stmt\Return_ || $last_stmt instanceof PhpParser\Node\Stmt\Continue_)) {
                    // if it doesn't end in a return
                    if ($new_vars === null) {
                        $new_vars = array_diff_key($else_vars, $vars_in_scope);
                    } else {
                        foreach ($new_vars as $new_var => $type) {
                            if (!isset($else_vars[$new_var])) {
                                unset($new_vars[$new_var]);
                            }
                        }
                    }
                }

                if (!($last_stmt instanceof PhpParser\Node\Stmt\Return_)) {
                    $new_vars_possibly_in_scope = array_merge(array_diff_key($else_vars_possibly_in_scope, $vars_possibly_in_scope), $new_vars_possibly_in_scope);
                }
            }

            if ($new_vars) {
                // only update vars if there is an else
                $vars_in_scope = array_merge($vars_in_scope, $new_vars);
            }
        }

        $vars_possibly_in_scope = array_merge($vars_possibly_in_scope, $new_vars_possibly_in_scope);
    }

    protected function _checkElseIf(PhpParser\Node\Stmt\ElseIf_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkCondition($stmt->cond, $vars_in_scope, $vars_possibly_in_scope);

        $if_types = [];

        if ($stmt->cond instanceof PhpParser\Node\Expr\Instanceof_) {
            $if_types = $this->_getInstanceOfTypes($stmt->cond);
        }

        $elseif_vars = array_merge($vars_in_scope, $if_types);

        $this->check($stmt->stmts, $elseif_vars, $vars_possibly_in_scope);

        $vars_in_scope = $elseif_vars;
    }

    protected function _checkElse(PhpParser\Node\Stmt\Else_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->check($stmt->stmts, $vars_in_scope, $vars_possibly_in_scope);
    }

    protected function _checkCondition(PhpParser\Node\Expr $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkExpression($stmt, $vars_in_scope, $vars_possibly_in_scope);
    }

    protected function _getInstanceOfTypes(PhpParser\Node\Expr $stmt)
    {
        $if_types = [];

        if ($stmt->expr instanceof PhpParser\Node\Expr\Variable && is_string($stmt->expr->name) && $stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                $instanceof_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
                $if_types[$stmt->expr->name] = $instanceof_class;
            } elseif ($stmt->class->parts === ['self']) {
                $if_types[$stmt->expr->name] = $this->_absolute_class;
            }
        }

        return $if_types;
    }

    protected function _checkExpression(PhpParser\Node\Expr $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope = [])
    {
        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            $this->_checkVariable($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Assign) {
            $this->_checkAssignment($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp) {
            $this->_checkAssignmentOperation($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\MethodCall) {
            $this->_checkMethodCall($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\StaticCall) {
            $this->_checkStaticCall($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            $this->_checkConstFetch($stmt);

        } elseif ($stmt instanceof PhpParser\Node\Scalar\String_) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Scalar\EncapsedStringPart) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Expr\UnaryMinus) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Isset_) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            $this->_checkClassConstFetch($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            $this->_checkPropertyFetch($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
            $this->_checkStaticPropertyFetch($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\BitwiseNot) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            $this->_checkExpression($stmt->left, $vars_in_scope, $vars_possibly_in_scope);
            $this->_checkExpression($stmt->right, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PostInc) {
            $this->_checkExpression($stmt->var, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PostDec) {
            $this->_checkExpression($stmt->var, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PreInc) {
            $this->_checkExpression($stmt->var, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\PreDec) {
            $this->_checkExpression($stmt->var, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\New_) {
            $this->_checkNew($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Array_) {
            $this->_checkArray($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Scalar\Encapsed) {
            $this->_checkEncapsulatedString($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall) {
            $this->_checkFunctionCall($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            $this->_checkTernary($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            $this->_checkBooleanNot($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Empty_) {
            $this->_checkEmpty($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Closure) {
            $closure_checker = new ClosureChecker($stmt, $this->_source);
            $closure_checker->check();

            foreach ($stmt->uses as $use) {
                if (!isset($vars_in_scope[$use->var])) {
                    if ($use->byRef) {
                        $vars_in_scope[$use->var] = true;
                        $vars_possibly_in_scope[$use->var] = true;
                        $this->registerVariable($use->var, $use->getLine());

                    } elseif (!isset($vars_possibly_in_scope[$use->var])) {
                        throw new CodeException('Cannot find referenced variable $' . $use->var, $this->_file_name, $use->getLine());

                    } elseif (isset($this->_all_vars[$use->var])) {
                        if (!isset($this->_warn_vars[$use->var])) {
                            if (FileChecker::$show_notices) {
                                echo('Notice: ' . $this->_file_name . ' - possibly undefined variable $' . $use->var . ' on line ' . $use->getLine() . ', first seen on line ' . $this->_all_vars[$use->var] . PHP_EOL);
                            }

                            $this->_warn_vars[$use->var] = true;
                        }

                    } else {
                        throw new CodeException('Cannot find referenced variable $' . $use->var, $this->_file_name, $use->getLine());
                    }
                }
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            $this->_checkArrayAccess($stmt, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Clone_) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\Instanceof_) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

            if ($stmt->class instanceof PhpParser\Node\Name && !in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                if ($this->_check_classes) {
                    ClassChecker::checkClassName($stmt->class, $this->_namespace, $this->_aliased_classes, $this->_file_name);
                }
            }

        } elseif ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Expr\Include_) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);
            $this->_check_classes = false;
            $this->_check_variables = false;

        } elseif ($stmt instanceof PhpParser\Node\Expr\Eval_) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);
            $this->_check_classes = false;
            $this->_check_variables = false;

        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignRef) {
            if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
                $vars_in_scope[$stmt->var->name] = true;
                $vars_possibly_in_scope[$stmt->var->name] = true;
                $this->registerVariable($stmt->var->name, $stmt->var->getLine());
            } else {
                $this->_checkExpression($stmt->var, $vars_in_scope, $vars_possibly_in_scope);
            }

            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        } elseif ($stmt instanceof PhpParser\Node\Expr\ErrorSuppress) {
            // do nothing

        } elseif ($stmt instanceof PhpParser\Node\Expr\ShellExec) {
            throw new CodeException('Use of shell_exec', $this->_file_name, $stmt->getLine());

        } elseif ($stmt instanceof PhpParser\Node\Expr\Print_) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        } else {
            var_dump('Unrecognised expression in ' . $this->_file_name);
            var_dump($stmt);
        }
    }

    protected function _checkVariable(PhpParser\Node\Expr\Variable $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope, $method_id = null, $argument_offset = -1)
    {
        if ($stmt->name === 'this' && $this->_is_static) {
            throw new CodeException('Invalid reference to $this in a static context', $this->_file_name, $stmt->getLine());
        }

        if (!$this->_check_variables) {
            return;
        }

        if (in_array($stmt->name, ['this', '_SERVER', '_GET', '_POST', '_COOKIE', '_REQUEST', '_FILES', '_ENV', 'GLOBALS', 'argv'])) {
            return;
        }

        if (!is_string($stmt->name)) {
            $this->_checkExpression($stmt->name, $vars_in_scope, $vars_possibly_in_scope);
            return;
        }

        if (!isset($vars_in_scope[$stmt->name])) {
            if ($method_id) {
                if ($this->_isPassedByReference($method_id, $argument_offset)) {
                    $vars_in_scope[$stmt->name] = true;
                    $vars_possibly_in_scope[$stmt->name] = true;
                    $this->registerVariable($stmt->name, $stmt->getLine());
                    return;
                }
            }

            if (!isset($vars_possibly_in_scope[$stmt->name])) {
                throw new CodeException('Cannot find referenced variable $' . $stmt->name, $this->_file_name, $stmt->getLine());

            } elseif (isset($this->_all_vars[$stmt->name])) {
                if (!isset($this->_warn_vars[$stmt->name])) {
                    if (FileChecker::$show_notices) {
                        echo('Notice: ' . $this->_file_name . ' - possibly undefined variable $' . $stmt->name . ' on line ' . $stmt->getLine() . ', first seen on line ' . $this->_all_vars[$stmt->name] . PHP_EOL);
                    }

                    $this->_warn_vars[$stmt->name] = true;
                }

            } else {
                throw new CodeException('Cannot find referenced variable $' . $stmt->name, $this->_file_name, $stmt->getLine());
            }

        } else {
            if (isset($vars_in_scope[$stmt->name]) && is_string($vars_in_scope[$stmt->name])) {
                $stmt->returnType = $vars_in_scope[$stmt->name];
            }
        }
    }

    protected function _checkPropertyFetch(PhpParser\Node\Expr\PropertyFetch $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if ($stmt->var->name === 'this') {
                $class_checker = $this->_source->getClassChecker();
                if (!FileChecker::shouldCheckClassProperties($this->_file_name, $class_checker)) {
                    // do nothing
                } elseif ($class_checker) {
                    if (is_string($stmt->name)) {
                        $property_names = $class_checker->getPropertyNames();

                        if (!in_array($stmt->name, $property_names)) {
                            if (!self::_propertyExists($this->_absolute_class . '::' . $stmt->name)) {
                                throw new CodeException('$this->' . $stmt->name . ' is not defined', $this->_file_name, $stmt->getLine());
                            }
                        }
                    }
                } else {
                    throw new CodeException('Cannot use $this when not inside class', $this->_file_name, $stmt->getLine());
                }
            } else {
                $this->_checkVariable($stmt->var, $vars_in_scope, $vars_possibly_in_scope);
            }
        } else {
            $this->_checkExpression($stmt->var, $vars_in_scope, $vars_possibly_in_scope);
        }

        if (!is_string($stmt->name)) {
            $this->_checkExpression($stmt->name, $vars_in_scope, $vars_possibly_in_scope);
        }
    }

    protected function _checkNew(PhpParser\Node\Expr\New_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $absolute_class = null;

        if ($stmt->class instanceof PhpParser\Node\Name && !in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
            if ($this->_check_classes) {
                ClassChecker::checkClassName($stmt->class, $this->_namespace, $this->_aliased_classes, $this->_file_name);

                $absolute_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
                $stmt->returnType = $absolute_class;
            }
        }

        if ($absolute_class) {
            $method_id = $absolute_class . '::__construct';

            $this->_checkMethodParams($stmt->args, $method_id, $vars_in_scope, $vars_possibly_in_scope);
        }
    }

    protected function _checkArray(PhpParser\Node\Expr\Array_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        foreach ($stmt->items as $item) {
            if ($item->key) {
                $this->_checkExpression($item->key, $vars_in_scope, $vars_possibly_in_scope);
            }

            $this->_checkExpression($item->value, $vars_in_scope, $vars_possibly_in_scope);
        }

        $stmt->returnType = 'array';
    }

    protected function _checkTryCatch(PhpParser\Node\Stmt\TryCatch $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->check($stmt->stmts, $vars_in_scope, $vars_possibly_in_scope);

        foreach ($stmt->catches as $catch) {
            $vars_in_scope[$catch->var] = ClassChecker::getAbsoluteClassFromName($catch->type, $this->_namespace, $this->_aliased_classes);
            $vars_possibly_in_scope[$catch->var] = true;
            $this->registerVariable($catch->var, $catch->getLine());

            if ($this->_check_classes) {
                ClassChecker::checkClassName($catch->type, $this->_namespace, $this->_aliased_classes, $this->_file_name);
            }

            $this->check($catch->stmts, $vars_in_scope, $vars_possibly_in_scope);
        }

        if ($stmt->finallyStmts) {
            $this->check($stmt->finallyStmts, $vars_in_scope, $vars_possibly_in_scope);
        }
    }

    protected function _checkFor(PhpParser\Node\Stmt\For_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $for_vars = array_merge([], $vars_in_scope);

        foreach ($stmt->init as $init) {
            $this->_checkExpression($init, $for_vars, $vars_possibly_in_scope);
        }

        foreach ($stmt->cond as $condition) {
            $this->_checkCondition($init, $for_vars, $vars_possibly_in_scope);
        }

        foreach ($stmt->loop as $expr) {
            $this->_checkExpression($expr, $for_vars, $vars_possibly_in_scope);
        }

        $this->check($stmt->stmts, $for_vars, $vars_possibly_in_scope);
    }

    protected function _checkForeach(PhpParser\Node\Stmt\Foreach_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        $foreach_vars = [];

        if ($stmt->keyVar) {
            $foreach_vars[$stmt->keyVar->name] = true;
            $vars_possibly_in_scope[$stmt->keyVar->name] = true;
            $this->registerVariable($stmt->keyVar->name, $stmt->getLine());
        }

        if ($stmt->valueVar) {
            $foreach_vars[$stmt->valueVar->name] = true;
            $vars_possibly_in_scope[$stmt->valueVar->name] = true;
            $this->registerVariable($stmt->valueVar->name, $stmt->getLine());
        }

        $foreach_vars = array_merge($vars_in_scope, $foreach_vars);

        $this->check($stmt->stmts, $foreach_vars, $vars_possibly_in_scope);
    }

    protected function _checkWhile(PhpParser\Node\Stmt\While_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkCondition($stmt->cond, $vars_in_scope, $vars_possibly_in_scope);

        $while_vars_in_scope = array_merge([], $vars_in_scope);

        $this->check($stmt->stmts, $while_vars_in_scope, $vars_possibly_in_scope);
    }

    protected function _checkDo(PhpParser\Node\Stmt\Do_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->check($stmt->stmts, $vars_in_scope, $vars_possibly_in_scope);

        $this->_checkCondition($stmt->cond, array_merge([], $vars_in_scope), $vars_possibly_in_scope);
    }

    protected function _checkAssignment(PhpParser\Node\Expr\Assign $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable && is_string($stmt->var->name)) {
            $vars_in_scope[$stmt->var->name] = true;
            $vars_possibly_in_scope[$stmt->var->name] = true;
            $this->registerVariable($stmt->var->name, $stmt->var->getLine());

            $comments = [];
            $doc_comment = $stmt->getDocComment();

            if ($doc_comment) {
                $comments = self::_parseDocComment($doc_comment);
            }

            if ($comments && isset($comments['specials']['var'][0])) {
                $type = explode(' ', $comments['specials']['var'][0])[0];

                if ($type[0] === strtoupper($type[0])) {
                    $vars_in_scope[$stmt->var->name] = ClassChecker::getAbsoluteClassFromString($type, $this->_namespace, $this->_aliased_classes);
                }

            } elseif (isset($stmt->expr->returnType)) {
                $var_name = $stmt->var->name;

                if ($stmt->expr->returnType === 'null') {
                    if (isset($vars_in_scope[$var_name])) {
                        $vars_in_scope[$var_name] = 'mixed';
                    }

                } elseif (isset($vars_in_scope[$var_name])) {
                    $existing_type = $vars_in_scope[$var_name];

                    if ($existing_type !== 'mixed') {
                        if (is_a($existing_type, $stmt->expr->returnType, true)) {
                            // downcast
                            $vars_in_scope[$var_name] = $stmt->expr->returnType;
                        } elseif (is_a($stmt->expr->returnType, $existing_type, true)) {
                            // upcast, catch later
                            $vars_in_scope[$var_name] = $stmt->expr->returnType;
                        } else {
                            $vars_in_scope[$stmt->var->name] = 'mixed';
                        }
                    }

                } else {
                    $vars_in_scope[$stmt->var->name] = $stmt->expr->returnType;
                }
            }

        } elseif ($stmt->var instanceof PhpParser\Node\Expr\List_) {
            foreach ($stmt->var->vars as $var) {
                if ($var) {
                    $vars_in_scope[$var->name] = true;
                    $vars_possibly_in_scope[$var->name] = true;
                    $this->registerVariable($var->name, $var->getLine());
                }
            }

        } else if ($stmt->var instanceof PhpParser\Node\Expr\ArrayDimFetch && $stmt->var->var instanceof PhpParser\Node\Expr\Variable) {
            // if it's an array assignment
            $vars_in_scope[$stmt->var->var->name] = true;
            $vars_possibly_in_scope[$stmt->var->var->name] = true;
            $this->registerVariable($stmt->var->var->name, $stmt->var->var->getLine());
        } else if ($stmt->var instanceof PhpParser\Node\Expr\PropertyFetch) {
            if ($stmt->var->var instanceof PhpParser\Node\Expr\Variable) {
                if ($stmt->var->var->name === 'this' && is_string($stmt->var->name)) {
                    self::$_existing_properties[$this->_absolute_class . '::' . $stmt->var->name] = 1;
                }
            }
        }
    }

    protected function _checkAssignmentOperation(PhpParser\Node\Expr\AssignOp $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkExpression($stmt->var, $vars_in_scope, $vars_possibly_in_scope);
        $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);
    }

    protected function _checkMethodCall(PhpParser\Node\Expr\MethodCall $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkExpression($stmt->var, $vars_in_scope, $vars_possibly_in_scope);

        $absolute_class = null;
        $method_id = null;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if ($stmt->var->name === 'this') {
                if (!$this->_class_name) {
                    throw new CodeException('Use of $this in non-class context', $this->_file_name, $stmt->getLine());
                }

                $absolute_class = $this->_absolute_class;
            } elseif (!is_string($stmt->var->name)) {
                $this->_checkExpression($stmt->var->name, $vars_in_scope, $vars_possibly_in_scope);
            } elseif (isset($vars_in_scope[$stmt->var->name])) {
                if (isset($vars_in_scope[$stmt->var->name]) && is_string($vars_in_scope[$stmt->var->name])) {
                    $absolute_class = $vars_in_scope[$stmt->var->name];
                } else {
                    $absolute_class = $vars_in_scope[$stmt->var->name];
                }
            }
        } elseif ($stmt->var instanceof PhpParser\Node\Expr) {
            $this->_checkExpression($stmt->var, $vars_in_scope, $vars_possibly_in_scope);
        }

        if (!$absolute_class && isset($stmt->var->returnType)) {
            $absolute_class = $stmt->var->returnType;
        }

        if ($absolute_class && $absolute_class[0] === strtoupper($absolute_class[0]) && $this->_check_methods && is_string($stmt->name) && !method_exists($absolute_class, '__call')) {
            $method_id = $absolute_class . '::' . $stmt->name;

            if (!self::_methodExists($method_id)) {
                throw new CodeException('Method ' . $method_id . ' does not exist', $this->_file_name, $stmt->getLine());
            }

            $return_types = $this->_getMethodReturnTypes($method_id);

            if ($return_types) {
                // @todo should work for multiple types
                $return_type = $return_types[0];

                $stmt->returnType = $return_type;
            }
        }

        $this->_checkMethodParams($stmt->args, $method_id, $vars_in_scope, $vars_possibly_in_scope);
    }

    protected function _checkStaticCall(PhpParser\Node\Expr\StaticCall $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
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
                $absolute_class = ClassChecker::getAbsoluteClassFromName($this->_class_extends, $this->_namespace, $this->_aliased_classes);
            } else {
                $absolute_class = ($this->_namespace ? '\\' : '') . $this->_namespace . '\\' . $this->_class_name;
            }
        } elseif ($this->_check_classes) {
            ClassChecker::checkClassName($stmt->class, $this->_namespace, $this->_aliased_classes, $this->_file_name);
            $absolute_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
        }

        if ($absolute_class && $this->_check_methods && is_string($stmt->name) && !method_exists($absolute_class, '__callStatic')) {
            $method_id = $absolute_class . '::' . $stmt->name;

            if (!self::_methodExists($method_id)) {
                throw new CodeException('Method ' . $method_id . ' does not exist', $this->_file_name, $stmt->getLine());
            }

            if ($this->_is_static) {
                if (!isset(self::$_static_methods[$method_id])) {
                    self::_extractReflectionMethodInfo($method_id);
                }

                if (!self::$_static_methods[$method_id]) {
                    throw new CodeException('Method ' . $method_id . ' is not static', $this->_file_name, $stmt->getLine());
                }
            }

            $return_types = $this->_getMethodReturnTypes($method_id);

            if ($return_types) {
                // @todo should work for multiple types
                $return_type = $return_types[0];

                $stmt->returnType = $return_type;
            }
        }

        $this->_checkMethodParams($stmt->args, $method_id, $vars_in_scope, $vars_possibly_in_scope);
    }

    protected function _checkMethodParams(array $args, $method_id, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        foreach ($args as $i => $arg) {
            if ($arg->value instanceof PhpParser\Node\Expr\Variable) {
                if ($method_id) {
                    $this->_checkVariable($arg->value, $vars_in_scope, $vars_possibly_in_scope, $method_id, $i);
                } elseif (is_string($arg->value->name)) {
                    // we don't know if it exists, assume it's passed by reference
                    $vars_in_scope[$arg->value->name] = true;
                    $vars_possibly_in_scope[$arg->value->name] = true;
                    $this->registerVariable($arg->value->name, $arg->value->getLine());
                }
            } else {
                $this->_checkExpression($arg->value, $vars_in_scope, $vars_possibly_in_scope);
            }

            if ($method_id && isset($arg->value->returnType)) {
                if (!self::_isCorrectType($arg->value->returnType, $method_id, $i)) {
                    throw new CodeException('Argument ' . ($i + 1) . ' of ' . $method_id . ' has incorrect type of ' . $arg->value->returnType, $this->_file_name, $arg->value->getLine());
                }
            }
        }
    }

    protected function _checkConstFetch(PhpParser\Node\Expr\ConstFetch $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name) {
            if ($stmt->name->parts === ['null']) {
                $stmt->returnType = 'null';
            }
        }
    }

    protected function _checkClassConstFetch(PhpParser\Node\Expr\ClassConstFetch $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        if ($this->_check_consts && $stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts !== ['static']) {
            if ($stmt->class->parts === ['self']) {
                $absolute_class = $this->_absolute_class;
            } else {
                $absolute_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
            }

            $const_id = $absolute_class . '::' . $stmt->name;

            if (!defined($const_id)) {
                throw new CodeException('Const ' . $const_id . ' is not defined', $this->_file_name, $stmt->getLine());
            }
        } elseif ($stmt->class instanceof PhpParser\Node\Expr) {
            $this->_checkExpression($stmt->class, $vars_in_scope, $vars_possibly_in_scope);
        }
    }

    protected function _checkStaticPropertyFetch(PhpParser\Node\Expr\StaticPropertyFetch $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
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
                $absolute_class = ClassChecker::getAbsoluteClassFromName($this->_class_extends, $this->_namespace, $this->_aliased_classes);
            } else {
                $absolute_class = ($this->_namespace ? '\\' : '') . $this->_namespace . '\\' . $this->_class_name;
            }
        } elseif ($this->_check_classes) {
            ClassChecker::checkClassName($stmt->class, $this->_namespace, $this->_aliased_classes, $this->_file_name);
            $absolute_class = ClassChecker::getAbsoluteClassFromName($stmt->class, $this->_namespace, $this->_aliased_classes);
        }

        if ($absolute_class && $this->_check_variables && is_string($stmt->name)) {
            $var_id = $absolute_class . '::$' . $stmt->name;

            if (!self::_staticVarExists($var_id)) {
                throw new CodeException('Static variable ' . $var_id . ' does not exist', $this->_file_name, $stmt->getLine());
            }
        }
    }

    protected function _checkReturn(PhpParser\Node\Stmt\Return_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        if ($stmt->expr) {
            $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);
        }
    }

    protected function _checkTernary(PhpParser\Node\Expr\Ternary $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkCondition($stmt->cond, $vars_in_scope, $vars_possibly_in_scope);

        $if_types = [];

        if ($stmt->cond instanceof PhpParser\Node\Expr\Instanceof_) {
            $if_types = $this->_getInstanceOfTypes($stmt->cond);
        }

        if ($stmt->if) {
            $this->_checkExpression($stmt->if, array_merge($vars_in_scope, $if_types), $vars_possibly_in_scope);
        }

        $this->_checkExpression($stmt->else, $vars_in_scope, $vars_possibly_in_scope);
    }

    protected function _checkBooleanNot(PhpParser\Node\Expr\BooleanNot $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);
    }

    protected function _checkEmpty(PhpParser\Node\Expr\Empty_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);
    }

    protected function _checkThrow(PhpParser\Node\Stmt\Throw_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkExpression($stmt->expr, $vars_in_scope, $vars_possibly_in_scope);
    }

    protected function _checkSwitch(PhpParser\Node\Stmt\Switch_ $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkCondition($stmt->cond, $vars_in_scope, $vars_possibly_in_scope);

        foreach ($stmt->cases as $case) {
            if ($case->cond) {
                $this->_checkCondition($case->cond, $vars_in_scope, $vars_possibly_in_scope);
            }

            $this->check($case->stmts, $vars_in_scope, $vars_possibly_in_scope);
        }
    }

    protected function _checkFunctionCall(PhpParser\Node\Expr\FuncCall $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $method = $stmt->name;

        if ($method instanceof PhpParser\Node\Name) {
            if ($method->parts === ['method_exists']) {
                $this->_check_methods = false;

            } elseif ($method->parts === ['defined']) {
                $this->_check_consts = false;

            } elseif ($method->parts === ['extract']) {
                $this->_check_variables = false;

            } elseif ($method->parts === ['var_dump'] || $method->parts === ['die'] || $method->parts === ['exit']) {
                if (FileChecker::shouldCheckVarDumps($this->_file_name)) {
                    throw new CodeException('Unsafe ' . implode('', $method->parts), $this->_file_name, $stmt->getLine());
                }
            }
        }

        foreach ($stmt->args as $i => $arg) {
            if ($arg->value instanceof PhpParser\Node\Expr\Variable) {
                if ($method instanceof PhpParser\Node\Name) {
                    $method_id = implode('', $stmt->name->parts);
                    $this->_checkVariable($arg->value, $vars_in_scope, $vars_possibly_in_scope, $method_id, $i);
                } else {
                    $this->_checkVariable($arg->value, $vars_in_scope, $vars_possibly_in_scope);
                }
            } else {
                $this->_checkExpression($arg->value, $vars_in_scope, $vars_possibly_in_scope);
            }
        }
    }

    protected function _checkArrayAccess(PhpParser\Node\Expr\ArrayDimFetch $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        $this->_checkExpression($stmt->var, $vars_in_scope, $vars_possibly_in_scope);
        if ($stmt->dim) {
            $this->_checkExpression($stmt->dim, $vars_in_scope, $vars_possibly_in_scope);
        }
    }

    protected function _checkEncapsulatedString(PhpParser\Node\Scalar\Encapsed $stmt, array &$vars_in_scope, array &$vars_possibly_in_scope)
    {
        foreach ($stmt->parts as $part) {
            $this->_checkExpression($part, $vars_in_scope, $vars_possibly_in_scope);
        }
    }

    public function registerMethod(PhpParser\Node\Stmt\ClassMethod $method)
    {
        $method_id = $this->_absolute_class . '::' . $method->name;

        if (!isset(self::$_method_return_types[$method_id])) {
            $comments = self::_parseDocComment($method->getDocComment());

            $return_types = [];

            if (isset($comments['specials']['return'])) {
                $return_blocks = explode(' ', $comments['specials']['return'][0]);
                foreach ($return_blocks as $block) {
                    if ($block) {
                        if ($block && preg_match('/^\\\?[A-Za-z0-9|\\\]+[A-Za-z0-9]$/', $block)) {
                            $return_types = explode('|', $block);
                            break;
                        }
                    }
                }
            }

            $return_types = array_filter($return_types, function ($entry) {
                return !empty($entry) && $entry !== '[type]';
            });

            foreach ($return_types as &$return_type) {
                if ($return_type[0] === strtoupper($return_type[0])) {
                    if ($return_type === '$this') {
                        $return_type = $this->_absolute_class;
                    } else {
                        $return_type = ClassChecker::getAbsoluteClassFromString($return_type, $this->_namespace, $this->_aliased_classes);
                    }
                }
            }

            self::$_method_return_types[$method_id] = $return_types;
        }

        self::$_method_namespaces[$method_id] = $this->_namespace;
        self::$_method_files[$method_id] = $this->_file_name;

        if (!isset(self::$_method_params[$method_id])) {
            self::$_method_params[$method_id] = [];

            foreach ($method->params as $param) {
                self::$_method_params[$method_id][] = $param->byRef;
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

        foreach ($properties as $reflection_property){
            if ($reflection_property->isPrivate() || $reflection_property->isStatic()) {
                continue;
            }

            self::$_existing_properties[$absolute_class_name . '::' . $reflection_property->getName()] = 1;
        }

        $parent_reflection_class = $reflection_class->getParentClass();

        if ($parent_reflection_class){
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

    protected static function _methodExists($method_id)
    {
        if (isset(self::$_existing_methods[$method_id])) {
            return true;
        }

        try {
            new \ReflectionMethod($method_id);
            self::$_existing_methods[$method_id] = 1;
            return true;

        } catch (\ReflectionException $e) {
            return false;
        }
    }

    protected static function _staticVarExists($var_id)
    {
        if (isset(self::$_existing_static_vars[$var_id])) {
            return true;
        }

        $absolute_class = explode('::', $var_id)[0];

        $reflection_class = new \ReflectionClass($absolute_class);

        $static_properties = $reflection_class->getStaticProperties();

        foreach ($static_properties as $property => $value) {
            self::$_existing_static_vars[$absolute_class . '::$' . $property] = 1;
        }

        return isset(self::$_existing_static_vars[$var_id]);
    }

    protected function _getMethodReturnTypes($method_id)
    {
        if (isset(self::$_method_return_types[$method_id])) {
            return self::$_method_return_types[$method_id];
        }

        if (!isset(self::$_method_comments[$method_id])) {
            self::_extractReflectionMethodInfo($method_id);
        }

        $comments = self::_parseDocComment(self::$_method_comments[$method_id]);

        $absolute_class = explode('::', $method_id)[0];

        $return_types = [];

        if (isset($comments['specials']['return'])) {
            $return_blocks = explode(' ', $comments['specials']['return'][0]);
            foreach ($return_blocks as $block) {
                if ($block && preg_match('/^\\\?[A-Za-z0-9|\\\]+[A-Za-z0-9]$/', $block)) {
                    $return_types = explode('|', $block);
                    break;
                }
            }
        }

        $return_types = array_filter($return_types, function ($entry) {
            return !empty($entry) && $entry !== '[type]';
        });

        if ($return_types) {
            foreach ($return_types as &$return_type) {
                if ($return_type[0] === strtoupper($return_type[0])) {
                    if ($return_type === '$this') {
                        $return_type = $absolute_class;
                    } elseif (self::$_declaring_classes[$method_id] === $this->_absolute_class) {
                        $return_type = ClassChecker::getAbsoluteClassFromString($return_type, $this->_namespace, $this->_aliased_classes);
                    } else {
                        //var_dump($method_id, $return_type);
                        //var_dump(self::$_method_namespaces[$method_id], self::$_method_files[$method_id]);
                        $return_type = FileChecker::getAbsoluteClassFromNameInFile($return_type, self::$_method_namespaces[$method_id], self::$_method_files[$method_id]);
                    }
                }
            }
        }

        self::$_method_return_types[$method_id] = $return_types;

        return $return_types;
    }

    /**
     * Parse a docblock comment into its parts.
     *
     * Taken from advanced api docmaker
     * Which was taken from https://github.com/facebook/libphutil/blob/master/src/parser/docblock/PhutilDocblockParser.php
     *
     * @return array Array of the main comment and specials
     */
    public static function _parseDocComment($docblock)
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

    protected function _isPassedByReference($method_id, $argument_offset)
    {
        if (strpos($method_id, '::') !== false) {
            if (!isset(self::$_method_params[$method_id])) {
                self::_extractReflectionMethodInfo($method_id);
            }

            return $argument_offset < count(self::$_method_params[$method_id]) && self::$_method_params[$method_id][$argument_offset];
        }

        $file_checker = FileChecker::getFileCheckerFromFileName($this->_file_name);

        if ($file_checker->hasFunction($method_id)) {
            return $file_checker->isPassedByReference($method_id, $argument_offset);
        }

        $reflection_parameters = (new \ReflectionFunction($method_id))->getParameters();

        // if value is passed by reference
        return $argument_offset < count($reflection_parameters) && $reflection_parameters[$argument_offset]->isPassedByReference();
    }

    protected static function _isCorrectType($return_type, $method_id, $arg_offset)
    {
        if ($return_type === 'mixed' || $return_type === 'null') {
            return true;
        }

        if (!isset(self::$_method_param_types[$method_id])) {
            self::_extractReflectionMethodInfo($method_id);
        }

        if ($arg_offset >= count(self::$_method_param_types[$method_id])) {
            return true;
        }

        $expected_type = self::$_method_param_types[$method_id][$arg_offset];

        if (!$expected_type) {
            return true;
        }

        if ($return_type === $expected_type) {
            return true;
        }

        return is_a($return_type, $expected_type, true) || is_a($expected_type, $return_type, true);
    }

    protected static function _extractReflectionMethodInfo($method_id)
    {
        $method = new \ReflectionMethod($method_id);
        $params = $method->getParameters();

        self::$_method_params[$method_id] = [];
        self::$_method_param_types[$method_id] = [];
        foreach ($params as $param) {
            self::$_method_params[$method_id][] = $param->isPassedByReference();
            self::$_method_param_types[$method_id][] = $param->getClass() ? '\\' . $param->getClass()->getName() : ($param->isArray() ? 'array' : null);
        }

        self::$_static_methods[$method_id] = $method->isStatic();
        self::$_method_comments[$method_id] = $method->getDocComment() ?: '';
        self::$_method_files[$method_id] = $method->getFileName();
        self::$_method_namespaces[$method_id] = $method->getDeclaringClass()->getNamespaceName();
        self::$_declaring_classes[$method_id] = '\\' . $method->getDeclaringClass()->name;
    }
}
