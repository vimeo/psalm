<?php

namespace CodeInspector;

use PhpParser;

class NamespaceChecker implements StatementsSource
{
    protected $_namespace;
    protected $_namespace_name;
    protected $_declared_classes = [];
    protected $_aliased_classes = [];
    protected $_file_name;

    public function __construct(\PhpParser\Node\Stmt\Namespace_ $namespace, StatementsSource $source)
    {
        $this->_namespace = $namespace;
        $this->_namespace_name = implode('\\', $this->_namespace->name->parts);
        $this->_file_name = $source->getFileName();
    }

    public function check($check_classes = true, $check_class_statements = true)
    {
        $leftover_stmts = [];

        foreach ($this->_namespace->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                $absolute_class = ClassChecker::getAbsoluteClassFromString($stmt->name, $this->_namespace_name, []);
                $this->_declared_classes[$absolute_class] = 1;

                if ($check_classes) {
                    $class_checker = ClassChecker::getClassCheckerFromClass($absolute_class) ?: new ClassChecker($stmt, $this, $absolute_class);
                    $class_checker->check($check_class_statements);
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
                // @todo check interface

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
                $absolute_class = ClassChecker::getAbsoluteClassFromString($stmt->name, $this->_namespace_name, []);

                if ($check_classes) {
                    $trait_checker = ClassChecker::getClassCheckerFromClass($absolute_class) ?: new TraitChecker($stmt, $this, $absolute_class);
                    $trait_checker->check($check_class_statements);
                }

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

    /**
     * Gets a list of the classes declared
     * @return array<string>
     */
    public function getDeclaredClasses()
    {
        return array_keys($this->_declared_classes);
    }

    public function containsClass($class_name)
    {
        return isset($this->_declared_classes[$class_name]);
    }

    public function getNamespace()
    {
        return $this->_namespace_name;
    }

    public function getAliasedClasses()
    {
        return $this->_aliased_classes;
    }

    /**
     * @return null
     */
    public function getAbsoluteClass()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getClassName()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getClassChecker()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getParentClass()
    {
        return null;
    }

    public function getFileName()
    {
        return $this->_file_name;
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return false;
    }

    public function getSource()
    {
        return null;
    }
}
