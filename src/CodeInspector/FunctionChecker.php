<?php

namespace CodeInspector;

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

    protected $_function_params = [];
    protected $_function_return_types = [];

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

        if ($function instanceof PhpParser\Node\Stmt\ClassMethod) {
            $this->_is_static = $function->isStatic();
        }

        $this->_statements_checker = new StatementsChecker($this, substr($this->_file_name, -4) === '.php');
    }

    public function check($extra_scope_vars = [])
    {
        if ($this->_function->stmts) {
            $vars_in_scope = $extra_scope_vars;
            $vars_possibly_in_scope = $extra_scope_vars;

            foreach ($this->_function->params as $param) {
                if ($param->type) {
                    if (is_object($param->type)) {
                        if (!in_array($param->type->parts[0], ['self', 'parent'])) {
                            ClassChecker::checkClassName($param->type, $this->_namespace, $this->_aliased_classes, $this->_file_name);
                        }
                    }
                }

                $is_nullable = $param->default !== null &&
                                $param->default instanceof \PhpParser\Node\Expr\ConstFetch &&
                                $param->default->name instanceof PhpParser\Node\Name &&
                                $param->default->name->parts = ['null'];

                $vars_in_scope[$param->name] = true;
                $vars_possibly_in_scope[$param->name] = true;
                $this->_statements_checker->registerVariable($param->name, $param->getLine());

                if ($param->type && is_object($param->type)) {
                    $vars_in_scope[$param->name] =
                        $param->type->parts === ['self'] ?
                            $this->_absolute_class :
                            ClassChecker::getAbsoluteClassFromName($param->type, $this->_namespace, $this->_aliased_classes);

                    if ($is_nullable) {
                        $vars_in_scope[$param->name] .= '|null';
                    }
                }
            }

            $this->_statements_checker->check($this->_function->stmts, $vars_in_scope, $vars_possibly_in_scope);
        }
    }

    public function getMethodId()
    {
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
}
