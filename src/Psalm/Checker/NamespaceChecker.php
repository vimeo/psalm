<?php
namespace Psalm\Checker;

use PhpParser\Node\Stmt\Namespace_;
use PhpParser;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class NamespaceChecker extends SourceChecker implements StatementsSource
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
     * @var array<int, ClassChecker>
     */
    protected $class_checkers = [];

    /**
     * A lookup table for public namespace constants
     *
     * @var array<string, array<string, Type\Union>>
     */
    protected static $public_namespace_constants = [];

    /**
     * @param Namespace_        $namespace
     * @param StatementsSource  $source
     */
    public function __construct(Namespace_ $namespace, StatementsSource $source)
    {
        $this->source = $source;
        $this->namespace = $namespace;
        $this->namespace_name = $this->namespace->name ? implode('\\', $this->namespace->name->parts) : '';
        $this->file_name = $source->getFileName();
        $this->file_path = $source->getFilePath();
        $this->include_file_name = $source->getIncludeFileName();
        $this->include_file_path = $source->getIncludeFilePath();
        $this->suppressed_issues = $source->getSuppressedIssues();
    }

    /**
     * @return  void
     */
    public function visit()
    {
        $leftover_stmts = [];

        self::$public_namespace_constants[$this->namespace_name] = [];

        $classlike_checkers = [];

        $namespace_context = new Context($this->file_name);

        foreach ($this->namespace->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassLike) {
                $this->visitClassLike($stmt, $classlike_checkers);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                $this->visitUse($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\GroupUse) {
                $this->visitGroupUse($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Const_) {
                foreach ($stmt->consts as $const) {
                    self::$public_namespace_constants[$this->namespace_name][$const->name] = Type::getMixed();
                }

                $leftover_stmts[] = $stmt;
            } else {
                $leftover_stmts[] = $stmt;
            }
        }

        $this->class_checkers = [];

        foreach ($classlike_checkers as $classlike_checker) {
            if ($classlike_checker instanceof ClassChecker) {
                $classlike_checker->check(null, null);
                $this->class_checkers[] = $classlike_checker;
            } elseif ($classlike_checker instanceof InterfaceChecker) {
                $classlike_checker->check();
            }
        }

        if ($leftover_stmts) {
            $statements_checker = new StatementsChecker($this);
            $context = new Context($this->file_name);
            $statements_checker->check($leftover_stmts, $context);
        }
    }

    /**
     * @param  Context $context
     * @return void
     */
    public function checkMethods(Context $context)
    {
        foreach ($this->class_checkers as $class_checker) {
            $class_checker->checkMethods(null, $context);
        }

        $this->class_checkers = [];
    }

    /**
     * @param  PhpParser\Node\Stmt\ClassLike $stmt
     * @param  array<ClassLikeChecker>       $classlike_checkers
     * @return void
     */
    public function visitClassLike(
        PhpParser\Node\Stmt\ClassLike $stmt,
        array &$classlike_checkers
    ) {
        if (!$stmt->name) {
            throw new \UnexpectedValueException('Did not expect anonymous class here');
        }

        $fq_class_name = ClassLikeChecker::getFQCLNFromString($stmt->name, $this->namespace_name, []);

        if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            $classlike_checkers[] = new ClassChecker($stmt, $this, $fq_class_name);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
            $classlike_checkers[] = new InterfaceChecker($stmt, $this, $fq_class_name);
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
            // register the trait checker
            new TraitChecker($stmt, $this, $fq_class_name);
        }
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace_name;
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

    /**
     * @param string     $const_name
     * @param Type\Union $const_type
     * @return void
     */
    public function setConstType($const_name, Type\Union $const_type)
    {
        self::$public_namespace_constants[$this->namespace_name][$const_name] = $const_type;
    }

    /**
     * @param  string $namespace_name
     * @param  mixed  $visibility
     * @return array<string,Type\Union>
     */
    public static function getConstantsForNamespace($namespace_name, $visibility)
    {
        // remove for PHP 7.1 support
        $visibility = \ReflectionProperty::IS_PUBLIC;

        // @todo this does not allow for loading in namespace constants not already defined in the current sweep
        if (!isset(self::$public_namespace_constants[$namespace_name])) {
            self::$public_namespace_constants[$namespace_name] = [];
        }

        if ($visibility === \ReflectionProperty::IS_PUBLIC) {
            return self::$public_namespace_constants[$namespace_name];
        }

        throw new \InvalidArgumentException('Given $visibility not supported');
    }
}
