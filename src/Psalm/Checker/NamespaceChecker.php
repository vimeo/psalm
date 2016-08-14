<?php

namespace Psalm\Checker;

use Psalm\StatementsSource;
use Psalm\Context;

use PhpParser;

class NamespaceChecker implements StatementsSource
{
    protected $namespace;
    protected $namespace_name;
    protected $declared_classes = [];
    protected $aliased_classes = [];
    protected $file_name;

    /**
     * @var array
     */
    protected $suppressed_issues;

    public function __construct(\PhpParser\Node\Stmt\Namespace_ $namespace, StatementsSource $source)
    {
        $this->namespace = $namespace;
        $this->namespace_name = implode('\\', $this->namespace->name->parts);
        $this->file_name = $source->getFileName();
        $this->suppressed_issues = $source->getSuppressedIssues();
    }

    public function check($check_classes = true, $check_class_statements = true)
    {
        $leftover_stmts = [];

        foreach ($this->namespace->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassLike) {
                $absolute_class = ClassLikeChecker::getAbsoluteClassFromString($stmt->name, $this->namespace_name, []);

                if ($stmt instanceof PhpParser\Node\Stmt\Class_) {

                    $this->declared_classes[$absolute_class] = 1;

                    if ($check_classes) {
                        $class_checker = ClassLikeChecker::getClassLikeCheckerFromClass($absolute_class) ?: new ClassChecker($stmt, $this, $absolute_class);
                        $class_checker->check($check_class_statements);
                    }
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
                    if ($check_classes) {
                        $class_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name) ?: new InterfaceChecker($stmt, $this, $absolute_class);
                        $this->declared_classes[] = $class_checker->getAbsoluteClass();
                        $class_checker->check(false);
                    }

                } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
                    if ($check_classes) {
                        // register the trait checker
                        ClassLikeChecker::getClassLikeCheckerFromClass($absolute_class) ?: new TraitChecker($stmt, $this, $absolute_class);
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    $this->aliased_classes[$use->alias] = implode('\\', $use->name->parts);
                }
            } else {
                $leftover_stmts[] = $stmt;
            }
        }

        if ($leftover_stmts) {
            $statments_checker = new StatementsChecker($this);
            $context = new Context($this->file_name);
            $statments_checker->check($leftover_stmts, $context);
        }

        return $this->aliased_classes;
    }

    /**
     * Gets a list of the classes declared
     * @return array<string>
     */
    public function getDeclaredClasses()
    {
        return array_keys($this->declared_classes);
    }

    public function containsClass($class_name)
    {
        return isset($this->declared_classes[$class_name]);
    }

    public function getNamespace()
    {
        return $this->namespace_name;
    }

    public function getAliasedClasses()
    {
        return $this->aliased_classes;
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
    public function getClassLikeChecker()
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
        return $this->file_name;
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

    public function getSuppressedIssues()
    {
        return $this->suppressed_issues;
    }
}
