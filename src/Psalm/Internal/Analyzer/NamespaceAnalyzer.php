<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use PhpParser\Node\Stmt\Namespace_;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;
use function implode;
use function strtolower;
use function trim;
use function strpos;
use function preg_replace;

/**
 * @internal
 */
class NamespaceAnalyzer extends SourceAnalyzer implements StatementsSource
{
    use CanAlias;

    /**
     * @var FileAnalyzer
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
     * @param FileAnalyzer       $source
     */
    public function __construct(Namespace_ $namespace, FileAnalyzer $source)
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

        if (!isset(self::$public_namespace_constants[$this->namespace_name])) {
            self::$public_namespace_constants[$this->namespace_name] = [];
        }

        $codebase = $this->getCodebase();

        foreach ($this->namespace->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassLike) {
                $this->collectAnalyzableClassLike($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                $this->visitUse($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\GroupUse) {
                $this->visitGroupUse($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Const_) {
                foreach ($stmt->consts as $const) {
                    self::$public_namespace_constants[$this->namespace_name][$const->name->name] = Type::getMixed();
                }

                $leftover_stmts[] = $stmt;
            } else {
                $leftover_stmts[] = $stmt;
            }
        }

        if ($leftover_stmts) {
            $statements_analyzer = new StatementsAnalyzer($this, new \Psalm\Internal\Provider\NodeDataProvider());
            $context = new Context();
            $context->is_global = true;
            $context->defineGlobals();
            $context->collect_exceptions = $codebase->config->check_for_throws_in_global_scope;
            $statements_analyzer->analyze($leftover_stmts, $context);

            $file_context = $this->source->context;
            if ($file_context) {
                $file_context->mergeExceptions($context);
            }
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

        $fq_class_name = Type::getFQCLNFromString($stmt->name->name, $this->getAliases());

        if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            $this->source->addNamespacedClassAnalyzer(
                $fq_class_name,
                new ClassAnalyzer($stmt, $this, $fq_class_name)
            );
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
            $this->source->addNamespacedInterfaceAnalyzer(
                $fq_class_name,
                new InterfaceAnalyzer($stmt, $this, $fq_class_name)
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
        // @todo this does not allow for loading in namespace constants not already defined in the current sweep
        if (!isset(self::$public_namespace_constants[$namespace_name])) {
            self::$public_namespace_constants[$namespace_name] = [];
        }

        if ($visibility === \ReflectionProperty::IS_PUBLIC) {
            return self::$public_namespace_constants[$namespace_name];
        }

        throw new \InvalidArgumentException('Given $visibility not supported');
    }

    public function getFileAnalyzer() : FileAnalyzer
    {
        return $this->source;
    }

    /**
     * Returns true if $className is the same as, or starts with $namespace, in a case-insensitive comparison.
     *
     * @return bool
     */
    public static function isWithin(string $calling_namespace, string $namespace): bool
    {
        if ($namespace === '') {
            return true; // required to prevent a warning from strpos with empty needle in PHP < 8
        }

        $calling_namespace = strtolower(trim($calling_namespace, '\\') . '\\');
        $namespace = strtolower(trim($namespace, '\\') . '\\');

        return $calling_namespace === $namespace
            || strpos($calling_namespace, $namespace) === 0;
    }

    /**
     * @param string $fullyQualifiedClassName, e.g. '\Psalm\Internal\Analyzer\NamespaceAnalyzer'
     * @return string , e.g. 'Psalm'
     */
    public static function getNameSpaceRoot(string $fullyQualifiedClassName): string
    {
        return preg_replace('/^([^\\\]+).*/', '$1', $fullyQualifiedClassName);
    }
}
