<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\DuplicateClass;
use Psalm\IssueBuffer;
use Psalm\Provider\FileProvider;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;
use Psalm\Type;

class FileChecker extends SourceChecker implements StatementsSource
{
    use CanAlias;

    /**
     * @var string
     */
    protected $file_name;

    /**
     * @var string
     */
    protected $file_path;

    /**
     * @var string|null
     */
    protected $actual_file_name;

    /**
     * @var string|null
     */
    protected $actual_file_path;

    /**
     * @var array<string, string>
     */
    protected $suppressed_issues = [];

    /**
     * @var array<string, array<string, string>>
     */
    protected $namespace_aliased_classes = [];

    /**
     * @var array<string, array<string, string>>
     */
    protected $namespace_aliased_classes_flipped = [];

    /**
     * @var array<int, \PhpParser\Node\Stmt>
     */
    protected $preloaded_statements = [];

    /**
     * @var bool
     */
    public static $show_notices = true;

    /**
     * A list of data useful to analyse files
     *
     * @var array<string, FileStorage>
     */
    public static $storage = [];

    /**
     * @var array<string, ClassLikeChecker>
     */
    protected $interface_checkers_to_analyze = [];

    /**
     * @var array<string, ClassLikeChecker>
     */
    protected $class_checkers_to_analyze = [];

    /**
     * @var array<string, FunctionChecker>
     */
    protected $function_checkers = [];

    /**
     * @var array<int, NamespaceChecker>
     */
    protected $namespace_checkers = [];

    /**
     * @var array<string, bool>
     */
    private $included_file_paths = [];

    /**
     * @var ?Context
     */
    public $context;

    /**
     * @var ProjectChecker
     */
    public $project_checker;

    /**
     * @var bool
     */
    public $will_analyze;

    /**
     * @param string                                $file_path
     * @param ProjectChecker                        $project_checker
     * @param array<int, PhpParser\Node\Stmt>|null  $preloaded_statements
     * @param bool                                  $will_analyze
     * @param array<string, bool>                   $included_file_paths
     */
    public function __construct(
        $file_path,
        ProjectChecker $project_checker,
        array $preloaded_statements = null,
        $will_analyze = true,
        array $included_file_paths = []
    ) {
        $this->file_path = $file_path;
        $this->file_name = Config::getInstance()->shortenFileName($this->file_path);
        $this->project_checker = $project_checker;
        $this->will_analyze = $will_analyze;

        if ($preloaded_statements) {
            $this->preloaded_statements = $preloaded_statements;
        }

        if (!isset(self::$storage[$file_path])) {
            self::$storage[$file_path] = new FileStorage();
        }
    }

    /**
     * @param   Context|null    $file_context
     *
     * @return  void
     */
    public function scan()
    {
        $config = Config::getInstance();

        $stmts = $this->getStatements();

        $traverser = new PhpParser\NodeTraverser();
        $traverser->addVisitor(new \Psalm\Visitor\DependencyFinderVisitor($this->project_checker, $this));
        $traverser->traverse($stmts);
    }

    /**
     * @param  bool $update_docblocks
     * @param  bool $preserve_checkers
     *
     * @return void
     */
    public function analyze(Context $file_context = null, $update_docblocks = false, $preserve_checkers = false)
    {
        if ($file_context) {
            $this->context = $file_context;
        }

        if (!$this->context) {
            $this->context = new Context();
            $this->context->collect_references = $this->project_checker->collect_references;
            $this->context->vars_in_scope['$argc'] = Type::getInt();
            $this->context->vars_in_scope['$argv'] = new Type\Union([
                new Type\Atomic\TArray([
                    Type::getInt(),
                    Type::getString(),
                ]),
            ]);
        }

        $config = Config::getInstance();

        $stmts = $this->getStatements();

        /** @var array<int, PhpParser\Node\Expr|PhpParser\Node\Stmt> */
        $leftover_stmts = [];

        $statements_checker = new StatementsChecker($this);

        $predefined_classlikes = $config->getPredefinedClassLikes();

        $function_stmts = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassLike && $stmt->name) {
                if (isset($predefined_classlikes[strtolower($stmt->name)])) {
                    if (IssueBuffer::accepts(
                        new DuplicateClass(
                            'Class ' . $stmt->name . ' has already been defined internally',
                            new \Psalm\CodeLocation($this, $stmt, null, true)
                        )
                    )) {
                        // fall through
                    }

                    continue;
                }

                if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                    $class_checker = new ClassChecker($stmt, $this, $stmt->name);

                    $fq_class_name = $class_checker->getFQCLN();

                    $this->class_checkers_to_analyze[$fq_class_name] = $class_checker;
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
                    $class_checker = new InterfaceChecker($stmt, $this, $stmt->name);

                    $fq_class_name = $class_checker->getFQCLN();

                    $this->interface_checkers_to_analyze[$fq_class_name] = $class_checker;
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
                    new TraitChecker($stmt, $this, $stmt->name);
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                $namespace_name = $stmt->name ? implode('\\', $stmt->name->parts) : '';

                $namespace_checker = new NamespaceChecker($stmt, $this);
                $namespace_checker->visit();

                $this->namespace_aliased_classes[$namespace_name] = $namespace_checker->getAliases()->uses;
                $this->namespace_aliased_classes_flipped[$namespace_name] =
                    $namespace_checker->getAliasedClassesFlipped();
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_stmts[] = $stmt;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                $this->visitUse($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\GroupUse) {
                $this->visitGroupUse($stmt);
            } else {
                $leftover_stmts[] = $stmt;
            }
        }

        $function_checkers = [];

        // hoist functions to the top
        foreach ($function_stmts as $stmt) {
            $function_checkers[$stmt->name] = new FunctionChecker($stmt, $this);
            $function_id = (string)$function_checkers[$stmt->name]->getMethodId();
            $this->function_checkers[$function_id] = $function_checkers[$stmt->name];
        }

        // if there are any leftover statements, evaluate them,
        // in turn causing the classes/interfaces be evaluated
        if ($leftover_stmts) {
            $statements_checker->analyze($leftover_stmts, $this->context);
        }

        // check any leftover interfaces not already evaluated
        foreach ($this->interface_checkers_to_analyze as $interface_checker) {
            $interface_checker->analyze();
        }

        // check any leftover classes not already evaluated
        foreach ($this->class_checkers_to_analyze as $class_checker) {
            $class_checker->analyze(null, $this->context, $update_docblocks);
        }

        $config = Config::getInstance();

        foreach ($this->function_checkers as $function_checker) {
            $function_context = new Context($this->context->self);
            $function_context->collect_references = $this->project_checker->collect_references;
            $function_checker->analyze($function_context, $this->context);

            if ($config->reportIssueInFile('InvalidReturnType', $this->file_path)) {
                /** @var string */
                $method_id = $function_checker->getMethodId();

                $function_storage = FunctionChecker::getStorage($method_id, $this->file_path);

                if (!$function_storage->has_template_return_type) {
                    $return_type = $function_storage->return_type;

                    $return_type_location = $function_storage->return_type_location;

                    $function_checker->verifyReturnType(
                        false,
                        $return_type,
                        null,
                        $return_type_location
                    );
                }
            }
        }

        if (!$preserve_checkers) {
            $this->class_checkers_to_analyze = [];
            $this->function_checkers = [];
        }

        if ($update_docblocks) {
            \Psalm\Mutator\FileMutator::updateDocblocks($this->file_path);
        }
    }

    /**
     * @param string       $fq_class_name
     * @param ClassChecker $class_checker
     *
     * @return  void
     */
    public function addNamespacedClassChecker($fq_class_name, ClassChecker $class_checker)
    {
        $this->class_checkers_to_analyze[] = $class_checker;
    }

    /**
     * @param string            $fq_class_name
     * @param InterfaceChecker  $interface_checker
     *
     * @return  void
     */
    public function addNamespacedInterfaceChecker($fq_class_name, InterfaceChecker $interface_checker)
    {
        $this->interface_checkers_to_analyze[$fq_class_name] = $interface_checker;
    }

    /**
     * @param string            $function_id
     * @param FunctionChecker   $function_checker
     *
     * @return  void
     */
    public function addNamespacedFunctionChecker($function_id, FunctionChecker $function_checker)
    {
        $this->function_checkers[$function_id] = $function_checker;
    }

    /**
     * @param  string   $method_id
     * @param  Context  $this_context
     *
     * @return void
     */
    public function getMethodMutations($method_id, Context $this_context)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_checker_to_examine = null;

        foreach ($this->class_checkers_to_analyze as $class_checker) {
            if (strtolower($class_checker->getFQCLN()) === strtolower($fq_class_name)) {
                $class_checker_to_examine = $class_checker;
                break;
            }
        }

        if (!$class_checker_to_examine) {
            $this->project_checker->getMethodMutations($method_id, $this_context);

            return;
        }

        $call_context = new Context($this_context->self);
        $call_context->collect_mutations = true;
        $call_context->include_location = $this_context->include_location;

        foreach ($this_context->vars_possibly_in_scope as $var => $type) {
            if (strpos($var, '$this->') === 0) {
                $call_context->vars_possibly_in_scope[$var] = true;
            }
        }

        foreach ($this_context->vars_in_scope as $var => $type) {
            if (strpos($var, '$this->') === 0) {
                $call_context->vars_in_scope[$var] = $type;
            }
        }

        $call_context->vars_in_scope['$this'] = $this_context->vars_in_scope['$this'];

        $class_checker_to_examine->getMethodMutations($method_name, $call_context);

        foreach ($call_context->vars_possibly_in_scope as $var => $_) {
            $this_context->vars_possibly_in_scope[$var] = true;
        }

        foreach ($call_context->vars_in_scope as $var => $type) {
            $this_context->vars_in_scope[$var] = $type;
        }
    }

    /**
     * @param  Context|null $file_context
     * @param  bool      $update_docblocks
     *
     * @return void
     */
    public function visitAndAnalyzeMethods(Context $file_context = null, $update_docblocks = false)
    {
        $this->project_checker->registerAnalyzableFile($this->file_path);
        $this->project_checker->scanFiles();
        $this->project_checker->populateClassLikeStorages();
        $this->analyze($file_context, $update_docblocks);
    }

    /**
     * Used when checking single files with multiple classlike declarations
     *
     * @param  string $fq_class_name
     *
     * @return bool
     */
    public function containsUnEvaluatedClassLike($fq_class_name)
    {
        return isset($this->interface_checkers_to_visit[$fq_class_name]) ||
            isset($this->class_checkers_to_visit[$fq_class_name]);
    }

    /**
     * When evaluating a file, we wait until a class is actually used to evaluate its contents
     *
     * @param  string $fq_class_name
     *
     * @return null|false
     */
    public function evaluateClassLike($fq_class_name)
    {
        if (isset($this->interface_checkers_to_visit[$fq_class_name])) {
            $interface_checker = $this->interface_checkers_to_visit[$fq_class_name];

            unset($this->interface_checkers_to_visit[$fq_class_name]);

            if ($interface_checker->visit() === false) {
                return false;
            }

            return;
        }

        if (isset($this->class_checkers_to_visit[$fq_class_name])) {
            $class_checker = $this->class_checkers_to_visit[$fq_class_name];

            unset($this->class_checkers_to_visit[$fq_class_name]);

            if ($class_checker->visit(null, $this->context) === false) {
                return false;
            }

            return;
        }
    }

    /**
     * @return array<int, \PhpParser\Node\Stmt>
     */
    protected function getStatements()
    {
        return $this->preloaded_statements
            ? $this->preloaded_statements
            : FileProvider::getStatementsForFile(
                $this->project_checker,
                $this->file_path,
                $this->project_checker->debug_output
            );
    }

    /**
     * @param  string $file_path
     *
     * @return bool
     */
    public function fileExists($file_path)
    {
        return file_exists($file_path) || isset($this->project_checker->fake_files[$file_path]);
    }

    /**
     * @return null
     */
    public function getNamespace()
    {
        return null;
    }

    /**
     * @param  string|null $namespace_name
     *
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped($namespace_name = null)
    {
        if ($namespace_name && isset($this->namespace_aliased_classes_flipped[$namespace_name])) {
            return $this->namespace_aliased_classes_flipped[$namespace_name];
        }

        return $this->aliased_classes_flipped;
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$storage = [];

        ClassLikeChecker::clearCache();
        FunctionChecker::clearCache();
        StatementsChecker::clearCache();
        IssueBuffer::clearCache();
        FunctionLikeChecker::clearCache();
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->file_path;
    }

    /**
     * @param string $file_name
     * @param string $file_path
     *
     * @return void
     */
    public function setFileName($file_name, $file_path)
    {
        $this->actual_file_name = $this->file_name;
        $this->actual_file_path = $this->file_path;

        $this->file_name = $file_name;
        $this->file_path = $file_path;
    }

    /**
     * @return string
     */
    public function getCheckedFileName()
    {
        return $this->actual_file_name ?: $this->file_name;
    }

    /**
     * @return string
     */
    public function getCheckedFilePath()
    {
        return $this->actual_file_path ?: $this->file_path;
    }

    public function getSuppressedIssues()
    {
        return $this->suppressed_issues;
    }

    public function getFQCLN()
    {
        return null;
    }

    public function getClassName()
    {
        return null;
    }

    public function isStatic()
    {
        return false;
    }

    public function getFileChecker()
    {
        return $this;
    }

    /** @return array<string, bool> */
    public function getIncludedFilePaths()
    {
        return $this->included_file_paths;
    }

    /**
     * @param string $file_path
     *
     * @return void
     */
    public function addIncludedFilePath($file_path)
    {
        $this->included_file_paths[$file_path] = true;
    }
}
