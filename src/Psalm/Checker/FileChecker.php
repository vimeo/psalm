<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Context;
use Psalm\FileManipulation\FileManipulationBuffer;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
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
     * @var array<int, string>
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
     * @var array<string, InterfaceChecker>
     */
    protected $interface_checkers_to_analyze = [];

    /**
     * @var array<string, ClassChecker>
     */
    protected $class_checkers_to_analyze = [];

    /**
     * @var array<string, FunctionChecker>
     */
    protected $function_checkers = [];

    /**
     * @var null|Context
     */
    public $context;

    /**
     * @var ProjectChecker
     */
    public $project_checker;

    /**
     * @param string  $file_path
     * @param string  $file_name
     * @param ProjectChecker  $project_checker
     */
    public function __construct(ProjectChecker $project_checker, $file_path, $file_name)
    {
        $this->file_path = $file_path;
        $this->file_name = $file_name;
        $this->project_checker = $project_checker;
    }

    /**
     * @param  bool $preserve_checkers
     *
     * @return void
     */
    public function analyze(Context $file_context = null, $preserve_checkers = false)
    {
        if ($file_context) {
            $this->context = $file_context;
        }

        $codebase = $this->project_checker->codebase;

        if (!$this->context) {
            $this->context = new Context();
            $this->context->collect_references = $codebase->collect_references;
            $this->context->vars_in_scope['$argc'] = Type::getInt();
            $this->context->vars_in_scope['$argv'] = new Type\Union([
                new Type\Atomic\TArray([
                    Type::getInt(),
                    Type::getString(),
                ]),
            ]);
        }

        $this->context->is_global = true;

        $codebase = $this->project_checker->codebase;

        $config = $codebase->config;

        $stmts = $codebase->getStatementsForFile($this->file_path);

        $statements_checker = new StatementsChecker($this);

        $leftover_stmts = $this->populateCheckers($stmts);

        $function_stmts = [];
        $function_checkers = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_stmts[] = $stmt;
            }
        }

        // hoist functions to the top
        foreach ($function_stmts as $stmt) {
            $function_checkers[$stmt->name] = new FunctionChecker($stmt, $this);
            $function_id = (string)$function_checkers[$stmt->name]->getMethodId();
            $this->function_checkers[$function_id] = $function_checkers[$stmt->name];
        }

        // if there are any leftover statements, evaluate them,
        // in turn causing the classes/interfaces be evaluated
        if ($leftover_stmts) {
            $statements_checker->analyze($leftover_stmts, $this->context, null, null, true);
        }

        // check any leftover interfaces not already evaluated
        foreach ($this->interface_checkers_to_analyze as $interface_checker) {
            $interface_checker->analyze();
        }

        // check any leftover classes not already evaluated
        foreach ($this->class_checkers_to_analyze as $class_checker) {
            $class_checker->analyze(null, $this->context);
        }

        foreach ($this->function_checkers as $function_checker) {
            $function_context = new Context($this->context->self);
            $function_context->collect_references = $codebase->collect_references;
            $function_checker->analyze($function_context, $this->context);

            if ($config->reportIssueInFile('InvalidReturnType', $this->file_path)) {
                /** @var string */
                $method_id = $function_checker->getMethodId();

                $function_storage = $codebase->getFunctionStorage(
                    $statements_checker,
                    $method_id
                );

                if (!$function_storage->has_template_return_type) {
                    $return_type = $function_storage->return_type;

                    $return_type_location = $function_storage->return_type_location;

                    $function_checker->verifyReturnType(
                        $this->project_checker,
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
    }

    /**
     * @param  array<int, PhpParser\Node\Expr|PhpParser\Node\Stmt>  $stmts
     *
     * @return array<int, PhpParser\Node\Expr|PhpParser\Node\Stmt>
     */
    public function populateCheckers(array $stmts)
    {
        /** @var array<int, PhpParser\Node\Expr|PhpParser\Node\Stmt> */
        $leftover_stmts = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassLike) {
                $this->populateClassLikeCheckers($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                $namespace_name = $stmt->name ? implode('\\', $stmt->name->parts) : '';

                $namespace_checker = new NamespaceChecker($stmt, $this);
                $namespace_checker->collectAnalyzableInformation();

                $this->namespace_aliased_classes[$namespace_name] = $namespace_checker->getAliases()->uses;
                $this->namespace_aliased_classes_flipped[$namespace_name] =
                    $namespace_checker->getAliasedClassesFlipped();
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                $this->visitUse($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\GroupUse) {
                $this->visitGroupUse($stmt);
            } elseif (!($stmt instanceof PhpParser\Node\Stmt\Function_)) {
                if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                    foreach ($stmt->stmts as $if_stmt) {
                        if ($if_stmt instanceof PhpParser\Node\Stmt\ClassLike) {
                            $this->populateClassLikeCheckers($if_stmt);
                        }
                    }
                }

                $leftover_stmts[] = $stmt;
            }
        }

        return $leftover_stmts;
    }

    /**
     * @return void
     */
    private function populateClassLikeCheckers(PhpParser\Node\Stmt\ClassLike $stmt)
    {
        if (!$stmt->name) {
            return;
        }

        if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            $class_checker = new ClassChecker($stmt, $this, $stmt->name);

            $fq_class_name = $class_checker->getFQCLN();

            $this->class_checkers_to_analyze[strtolower($fq_class_name)] = $class_checker;
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
            $class_checker = new InterfaceChecker($stmt, $this, $stmt->name);

            $fq_class_name = $class_checker->getFQCLN();

            $this->interface_checkers_to_analyze[$fq_class_name] = $class_checker;
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
        $this->class_checkers_to_analyze[strtolower($fq_class_name)] = $class_checker;
    }

    /**
     * @param string            $fq_class_name
     * @param InterfaceChecker  $interface_checker
     *
     * @return  void
     */
    public function addNamespacedInterfaceChecker($fq_class_name, InterfaceChecker $interface_checker)
    {
        $this->interface_checkers_to_analyze[strtolower($fq_class_name)] = $interface_checker;
    }

    /**
     * @param string            $function_id
     * @param FunctionChecker   $function_checker
     *
     * @return  void
     */
    public function addNamespacedFunctionChecker($function_id, FunctionChecker $function_checker)
    {
        $this->function_checkers[strtolower($function_id)] = $function_checker;
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

        if (isset($this->class_checkers_to_analyze[strtolower($fq_class_name)])) {
            $class_checker_to_examine = $this->class_checkers_to_analyze[strtolower($fq_class_name)];
        } else {
            $this->project_checker->getMethodMutations($method_id, $this_context);

            return;
        }

        $call_context = new Context($this_context->self);
        $call_context->collect_mutations = true;
        $call_context->collect_initializations = $this_context->collect_initializations;
        $call_context->initialized_methods = $this_context->initialized_methods;
        $call_context->include_location = $this_context->include_location;

        foreach ($this_context->vars_possibly_in_scope as $var => $_) {
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
     * @return ?string
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
        IssueBuffer::clearCache();
        FileManipulationBuffer::clearCache();
        FunctionLikeChecker::clearCache();
        \Psalm\Provider\ClassLikeStorageProvider::deleteAll();
        \Psalm\Provider\FileStorageProvider::deleteAll();
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

    /**
     * @return array<int, string>
     */
    public function getSuppressedIssues()
    {
        return $this->suppressed_issues;
    }

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function addSuppressedIssues(array $new_issues)
    {
        $this->suppressed_issues = array_merge($new_issues, $this->suppressed_issues);
    }

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function removeSuppressedIssues(array $new_issues)
    {
        $this->suppressed_issues = array_diff($this->suppressed_issues, $new_issues);
    }

    /**
     * @return ?string
     */
    public function getFQCLN()
    {
        return null;
    }

    /**
     * @return ?string
     */
    public function getClassName()
    {
        return null;
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return false;
    }

    /**
     * @return FileChecker
     */
    public function getFileChecker()
    {
        return $this;
    }
}
