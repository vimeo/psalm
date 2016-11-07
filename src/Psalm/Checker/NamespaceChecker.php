<?php
namespace Psalm\Checker;

use PhpParser\Node\Stmt\Namespace_;
use PhpParser;
use Psalm\Context;
use Psalm\StatementsSource;

class NamespaceChecker implements StatementsSource
{
    /**
     * @var Namespace_
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $namespace_name;

    /**
     * @var array
     */
    protected $declared_classes = [];

    /**
     * @var array
     */
    protected $aliased_classes = [];

    /**
     * @var string
     */
    protected $file_name;

    /**
     * @var string|null
     */
    protected $include_file_name;

    /**
     * @var array
     */
    protected $suppressed_issues;

    /**
     * @param Namespace_        $namespace
     * @param StatementsSource  $source
     */
    public function __construct(Namespace_ $namespace, StatementsSource $source)
    {
        $this->namespace = $namespace;
        $this->namespace_name = $this->namespace->name ? implode('\\', $this->namespace->name->parts) : '';
        $this->file_name = $source->getFileName();
        $this->include_file_name = $source->getIncludeFileName();
        $this->suppressed_issues = $source->getSuppressedIssues();
    }

    /**
     * @param   bool    $check_classes
     * @param   bool    $check_class_statements
     * @return  array
     */
    public function check($check_classes = true, $check_class_statements = true)
    {
        $leftover_stmts = [];

        foreach ($this->namespace->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassLike) {
                $fq_class_name = ClassLikeChecker::getFullQualifiedClassFromString($stmt->name, $this->namespace_name, []);

                if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                    $this->declared_classes[$fq_class_name] = 1;

                    if ($check_classes) {
                        $class_checker = ClassLikeChecker::getClassLikeCheckerFromClass($fq_class_name)
                            ?: new ClassChecker($stmt, $this, $fq_class_name);

                        $class_checker->check($check_class_statements);
                    }
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
                    if ($check_classes) {
                        $class_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name)
                            ?: new InterfaceChecker($stmt, $this, $fq_class_name);
                        $this->declared_classes[] = $class_checker->getFullQualifiedClass();
                        $class_checker->check(false);
                    }
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
                    if ($check_classes) {
                        // register the trait checker
                        ClassLikeChecker::getClassLikeCheckerFromClass($fq_class_name)
                            ?: new TraitChecker($stmt, $this, $fq_class_name);
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    $this->aliased_classes[strtolower($use->alias)] = implode('\\', $use->name->parts);
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
     *
     * @return array<string>
     */
    public function getDeclaredClasses()
    {
        return array_keys($this->declared_classes);
    }

    /**
     * @param   string $class_name
     * @return  bool
     */
    public function containsClass($class_name)
    {
        return isset($this->declared_classes[$class_name]);
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace_name;
    }

    /**
     * @return array
     */
    public function getAliasedClasses()
    {
        return $this->aliased_classes;
    }

    /**
     * @return null
     */
    public function getFullQualifiedClass()
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
     * @return string|null
     */
    public function getParentClass()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * @return null|string
     */
    public function getIncludeFileName()
    {
        return $this->include_file_name;
    }

    /**
     * @param string|null $file_name
     * @return void
     */
    public function setIncludeFileName($file_name)
    {
        $this->include_file_name = $file_name;
    }

    /**
     * @return string
     */
    public function getCheckedFileName()
    {
        return $this->include_file_name ?: $this->file_name;
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return false;
    }

    /**
     * @return null
     */
    public function getSource()
    {
        return null;
    }

    /**
     * Get a list of suppressed issues
     *
     * @return array<string>
     */
    public function getSuppressedIssues()
    {
        return $this->suppressed_issues;
    }
}
