<?php

namespace CodeInspector;

use PhpParser;

class NamespaceChecker implements StatementsSource
{
    protected $_namespace;
    protected $_namespace_name;
    protected $_contained_classes = [];
    protected $_aliased_classes = [];
    protected $_file_name;

    public function __construct(\PhpParser\Node\Stmt\Namespace_ $namespace, StatementsSource $source)
    {
        $this->_namespace = $namespace;
        $this->_namespace_name = implode('\\', $this->_namespace->name->parts);
        $this->_file_name = $source->getFileName();
    }

    public function check($check_classes)
    {
        $leftover_stmts = [];

        foreach ($this->_namespace->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                $absolute_class = ClassChecker::getAbsoluteClassFromString($stmt->name, $this->_namespace_name, []);
                $this->_contained_classes[$absolute_class] = 1;

                if ($check_classes) {
                    (new ClassChecker($stmt, $this, $absolute_class))->check();
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
                // @todo check interface

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
                // @todo check trait

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    $this->_aliased_classes[$use->alias] = implode('\\', $use->name->parts);
                }
            } else {
                $leftover_stmts[] = $stmt;
            }
        }

        if ($leftover_stmts) {
            $statments_checker = new StatementsChecker($this);
            $existing_vars = [];
            $existing_vars_in_scope = [];
            $statments_checker->check($leftover_stmts, $existing_vars, $existing_vars_in_scope);
        }

        return $this->_aliased_classes;
    }

    public function containsClass($class_name)
    {
        return isset($this->_contained_classes[$class_name]);
    }

    public function getNamespace()
    {
        return $this->_namespace_name;
    }

    public function getAliasedClasses()
    {
        return $this->_aliased_classes;
    }

    public function getAbsoluteClass()
    {
        return null;
    }

    public function getClassName()
    {
        return null;
    }

    public function getClassChecker()
    {
        return null;
    }

    public function getParentClass()
    {
        return null;
    }

    public function getFileName()
    {
        return $this->_file_name;
    }

    public function isStatic()
    {
        return false;
    }
}
