<?php
namespace Psalm\Checker;

use PhpParser;
use PhpParser\Node\Stmt\Namespace_;
use Psalm\Context;
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
    private $namespace;

    /**
     * @var string
     */
    private $namespace_name;

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
    public function collectAnalyzableInformation()
    {
        $leftover_stmts = [];
        $function_stmts = [];

        if (!isset(self::$public_namespace_constants[$this->namespace_name])) {
            self::$public_namespace_constants[$this->namespace_name] = [];
        }

        $namespace_context = new Context();
        $namespace_context->collect_references = $this->getFileChecker()->project_checker->collect_references;

        foreach ($this->namespace->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassLike) {
                $this->collectAnalyzableClassLike($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                $this->visitUse($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\GroupUse) {
                $this->visitGroupUse($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Const_) {
                foreach ($stmt->consts as $const) {
                    self::$public_namespace_constants[$this->namespace_name][$const->name] = Type::getMixed();
                }

                $leftover_stmts[] = $stmt;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_stmts[] = $stmt;
            } else {
                $leftover_stmts[] = $stmt;
            }
        }

        // hoist functions to the top
        foreach ($function_stmts as $stmt) {
            $function_checker = new FunctionChecker($stmt, $this);

            $this->source->addNamespacedFunctionChecker(
                (string)$function_checker->getMethodId(),
                $function_checker
            );
        }

        if ($leftover_stmts) {
            $statements_checker = new StatementsChecker($this);
            $context = new Context();
            $context->collect_references = $this->getFileChecker()->project_checker->collect_references;
            $statements_checker->analyze($leftover_stmts, $context);
        }
    }

    /**
     * @param  PhpParser\Node\Stmt\ClassLike $stmt
     *
     * @return void
     */
    public function collectAnalyzableClassLike(PhpParser\Node\Stmt\ClassLike $stmt)
    {
        if (!$stmt->name) {
            throw new \UnexpectedValueException('Did not expect anonymous class here');
        }

        $fq_class_name = ClassLikeChecker::getFQCLNFromString($stmt->name, $this->getAliases());

        if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            $this->source->addNamespacedClassChecker(
                $fq_class_name,
                new ClassChecker($stmt, $this, $fq_class_name)
            );
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
            $this->source->addNamespacedInterfaceChecker(
                $fq_class_name,
                new InterfaceChecker($stmt, $this, $fq_class_name)
            );
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
     *
     * @return void
     */
    public function setConstType($const_name, Type\Union $const_type)
    {
        self::$public_namespace_constants[$this->namespace_name][$const_name] = $const_type;
    }

    /**
     * @param  string $namespace_name
     * @param  mixed  $visibility
     *
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

    /**
     * @return FileChecker
     */
    public function getFileChecker()
    {
        return $this->source;
    }
}
