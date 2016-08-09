<?php

namespace Psalm;

ini_set('xdebug.max_nesting_level', 512);

use PhpParser;

class FunctionChecker implements StatementsSource
{
    protected $_function;
    protected $_aliased_classes = [];
    protected $_namespace;
    protected $_file_name;
    protected $_is_static = false;
    protected $_absolute_class;
    protected $_statements_checker;
    protected $_source;
    protected $_return_vars_in_scope = [];
    protected $_return_vars_possibly_in_scope = [];

    /**
     * @var array
     */
    protected $_suppressed_issues;

    protected static $_no_effects_hashes = [];

    protected $_function_params = [];

    public function __construct(PhpParser\Node\FunctionLike $function, StatementsSource $source)
    {
        $this->_function = $function;
        $this->_aliased_classes = $source->getAliasedClasses();
        $this->_namespace = $source->getNamespace();
        $this->_class_name = $source->getClassName();
        $this->_class_extends = $source->getParentClass();
        $this->_file_name = $source->getFileName();
        $this->_absolute_class = $source->getAbsoluteClass();
        $this->_source = $source;
        $this->_suppressed_issues = $source->getSuppressedIssues();
    }

    public function check(Context $context, $check_methods = true)
    {
        if ($this->_function->stmts) {
            $has_context = (bool) count($context->vars_in_scope);
            if ($this instanceof ClassMethodChecker) {
                if (ClassChecker::getThisClass()) {
                    $hash = $this->getMethodId() . json_encode([$context->vars_in_scope, $context->vars_possibly_in_scope]);

                    // if we know that the function has no effects on vars, we don't bother rechecking
                    if (isset(self::$_no_effects_hashes[$hash])) {
                        list($context->vars_in_scope, $context->vars_possibly_in_scope) = self::$_no_effects_hashes[$hash];

                        return;
                    }
                }
                else {
                    $context->vars_in_scope['this'] = new Type\Union([new Type\Atomic($context->self)]);
                }
            }

            $statements_checker = new StatementsChecker($this, $has_context, $check_methods);

            if ($this->_function instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_params = ClassMethodChecker::getMethodParams($this->getMethodId());

                foreach ($method_params as $method_param) {
                    $context->vars_in_scope[$method_param['name']] = StatementsChecker::fleshOutTypes(
                        clone $method_param['type'],
                        [],
                        $context->self,
                        $this->getMethodId()
                    );

                    $statements_checker->registerVariable($method_param['name'], $this->_function->getLine());
                }
            }
            else {
                // @todo deprecate this code
                foreach ($this->_function->params as $param) {
                    if ($param->type) {
                        if ($param->type instanceof PhpParser\Node\Name) {
                            if (!in_array($param->type->parts[0], ['self', 'parent'])) {
                                ClassChecker::checkClassName($param->type, $this->_namespace, $this->_aliased_classes, $this->_file_name, $this->_suppressed_issues);
                            }
                        }
                    }

                    $is_nullable = $param->default !== null &&
                                    $param->default instanceof \PhpParser\Node\Expr\ConstFetch &&
                                    $param->default->name instanceof PhpParser\Node\Name &&
                                    $param->default->name->parts = ['null'];

                    if ($param->type) {
                        if ($param->type instanceof Type) {
                            $context->vars_in_scope[$param->name] = clone $param->type;
                        }
                        else {
                            if (is_string($param->type)) {
                                $param_type_string = $param->type;
                            }
                            elseif ($param->type instanceof PhpParser\Node\Name) {
                                $param_type_string = $param->type->parts === ['self']
                                                        ? $this->_absolute_class
                                                        : ClassChecker::getAbsoluteClassFromName($param->type, $this->_namespace, $this->_aliased_classes);
                            }

                            if ($is_nullable) {
                                $param_type_string .= '|null';
                            }

                            $context->vars_in_scope[$param->name] = Type::parseString($param_type_string);
                        }
                    }
                    else {
                        $context->vars_in_scope[$param->name] = Type::getMixed();
                    }

                    $context->vars_possibly_in_scope[$param->name] = true;
                    $statements_checker->registerVariable($param->name, $param->getLine());
                }
            }

            $statements_checker->check($this->_function->stmts, $context);

            if (isset($this->_return_vars_in_scope[''])) {
                $context->vars_in_scope = TypeChecker::combineKeyedTypes($context->vars_in_scope, $this->_return_vars_in_scope['']);
            }

            if (isset($this->_return_vars_possibly_in_scope[''])) {
                $context->vars_possibly_in_scope = array_merge($context->vars_possibly_in_scope, $this->_return_vars_possibly_in_scope['']);
            }

            foreach ($context->vars_in_scope as $var => $type) {
                if (strpos($var, 'this->') !== 0) {
                    unset($context->vars_in_scope[$var]);
                }
            }

            foreach ($context->vars_possibly_in_scope as $var => $type) {
                if (strpos($var, 'this->') !== 0) {
                    unset($context->vars_possibly_in_scope[$var]);
                }
            }

            if (ClassChecker::getThisClass() && $this instanceof ClassMethodChecker) {
                self::$_no_effects_hashes[$hash] = [$context->vars_in_scope, $context->vars_possibly_in_scope];
            }
        }
    }

    /**
     * Adds return types for the given function
     * @param string        $return_type
     * @param array<Type>   $context->vars_in_scope
     * @param array<bool>   $context->vars_possibly_in_scope
     */
    public function addReturnTypes($return_type, Context $context)
    {
        if (isset($this->_return_vars_in_scope[$return_type])) {
            $this->_return_vars_in_scope[$return_type] = TypeChecker::combineKeyedTypes($context->vars_in_scope, $this->_return_vars_in_scope[$return_type]);
        }
        else {
            $this->_return_vars_in_scope[$return_type] = $context->vars_in_scope;
        }

        if (isset($this->_return_vars_possibly_in_scope[$return_type])) {
            $this->_return_vars_possibly_in_scope[$return_type] = array_merge($context->vars_possibly_in_scope, $this->_return_vars_possibly_in_scope[$return_type]);
        }
        else {
            $this->_return_vars_possibly_in_scope[$return_type] = $context->vars_possibly_in_scope;
        }
    }

    /**
     * @return null|string
     */
    public function getMethodId()
    {
        if ($this->_function instanceof PhpParser\Node\Expr\Closure) {
            return null;
        }

        return $this->getAbsoluteClass() . '::' . $this->_function->name;
    }

    public function getNamespace()
    {
        return $this->_namespace;
    }

    public function getAliasedClasses()
    {
        return $this->_aliased_classes;
    }

    public function getAbsoluteClass()
    {
        return $this->_absolute_class;
    }

    public function getClassName()
    {
        return $this->_class_name;
    }

    public function getClassChecker()
    {
        return $this->_source->getClassChecker();
    }

    public function getParentClass()
    {
        return $this->_class_extends;
    }

    public function getFileName()
    {
        return $this->_file_name;
    }

    public function isStatic()
    {
        return $this->_is_static;
    }

    public function getSource()
    {
        return $this->_source;
    }

    public function getSuppressedIssues()
    {
        return $this->_suppressed_issues;
    }
}
