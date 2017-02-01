<?php
namespace Psalm\Checker;

use PhpParser\Node\Stmt\Namespace_;
use PhpParser;
use Psalm\Context;
use Psalm\Exception\RedefinedPredefinedClassException;
use Psalm\IssueBuffer;
use Psalm\Issue\DuplicateClass;
use Psalm\StatementsSource;
use Psalm\Type;

class NamespaceChecker extends SourceChecker implements StatementsSource
{
    use CanAlias;

    /**
     * @var FileChecker
     */
    protected $source;

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
    public $class_checkers = [];

    /**
     * A lookup table for public namespace constants
     *
     * @var array<string, array<string, Type\Union>>
     */
    protected static $public_namespace_constants = [];

    /**
     * @param Namespace_        $namespace
     * @param FileChecker       $source
     */
    public function __construct(Namespace_ $namespace, FileChecker $source)
    {
        $this->source = $source;
        $this->namespace = $namespace;
        $this->namespace_name = $this->namespace->name ? implode('\\', $this->namespace->name->parts) : '';
    }

    /**
     * @return  void
     */
    public function visit()
    {
        $leftover_stmts = [];

        if (!isset(self::$public_namespace_constants[$this->namespace_name])) {
            self::$public_namespace_constants[$this->namespace_name] = [];
        }

        $classlike_checkers = [];

        $namespace_context = new Context();

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
                $classlike_checker->visit(null, null);
                $this->class_checkers[] = $classlike_checker;
            } elseif ($classlike_checker instanceof InterfaceChecker) {
                $classlike_checker->visit();
            }
        }

        if ($leftover_stmts) {
            $statements_checker = new StatementsChecker($this);
            $context = new Context();
            $statements_checker->analyze($leftover_stmts, $context);
        }
    }

    /**
     * @param  Context $context
     * @param  bool    $preserve_checkers
     * @param  bool    $update_docblocks
     * @return void
     */
    public function analyze(Context $context, $preserve_checkers = false, $update_docblocks = false)
    {
        foreach ($this->class_checkers as $class_checker) {
            $class_checker->analyze(null, $context, $update_docblocks);
        }

        if (!$preserve_checkers) {
            $this->class_checkers = [];
        }
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

        $config = \Psalm\Config::getInstance();

        $predefined_classlikes = $config->getPredefinedClassLikes();

        $fq_class_name = ClassLikeChecker::getFQCLNFromString($stmt->name, $this);

        if (isset($predefined_classlikes[strtolower($fq_class_name)])) {
            if (IssueBuffer::accepts(
                new DuplicateClass(
                    'Class ' . $fq_class_name . ' has already been defined internally',
                    new \Psalm\CodeLocation($this, $stmt, true)
                )
            )) {
                // fall through
            }

            return;
        }

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
