<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Context;
use Psalm\Exception\UnpreparedAnalysisException;
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
    protected $root_file_path;

    /**
     * @var string|null
     */
    protected $root_file_name;

    /**
     * @var array<string, bool>
     */
    protected $required_file_paths = [];

    /**
     * @var array<string, bool>
     */
    protected $parent_file_paths = [];

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
    public function analyze(
        Context $file_context = null,
        $preserve_checkers = false,
        Context $global_context = null
    ) {
        $codebase = $this->project_checker->codebase;

        $file_storage = $codebase->file_storage_provider->get($this->file_path);

        if (!$file_storage->deep_scan && !$codebase->server_mode) {
            throw new UnpreparedAnalysisException('File ' . $this->file_path . ' has not been properly scanned');
        }

        if ($file_context) {
            $this->context = $file_context;
        }

        if (!$this->context) {
            $this->context = new Context();
            $this->context->collect_references = $codebase->collect_references;
        }

        if ($codebase->config->useStrictTypesForFile($this->file_path)) {
            $this->context->strict_types = true;
        }

        $this->context->is_global = true;

        try {
            $stmts = $codebase->getStatementsForFile($this->file_path);
        } catch (PhpParser\Error $e) {
            return;
        }

        $statements_checker = new StatementsChecker($this);

        $leftover_stmts = $this->populateCheckers($stmts);

        // if there are any leftover statements, evaluate them,
        // in turn causing the classes/interfaces be evaluated
        if ($leftover_stmts) {
            $statements_checker->analyze($leftover_stmts, $this->context, $global_context, true);
        }

        // check any leftover interfaces not already evaluated
        foreach ($this->interface_checkers_to_analyze as $interface_checker) {
            $interface_checker->analyze();
        }

        // check any leftover classes not already evaluated

        foreach ($this->class_checkers_to_analyze as $class_checker) {
            $class_checker->analyze(null, $this->context);
        }

        if (!$preserve_checkers) {
            $this->class_checkers_to_analyze = [];
        }
    }

    /**
     * @param  array<int, PhpParser\Node\Stmt>  $stmts
     *
     * @return array<int, PhpParser\Node\Stmt>
     */
    public function populateCheckers(array $stmts)
    {
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
            } else {
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
            $class_checker = new ClassChecker($stmt, $this, $stmt->name->name);

            $fq_class_name = $class_checker->getFQCLN();

            $this->class_checkers_to_analyze[strtolower($fq_class_name)] = $class_checker;
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
            $class_checker = new InterfaceChecker($stmt, $this, $stmt->name->name);

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
     * @return null|string
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
        \Psalm\Provider\FileReferenceProvider::clearCache();
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
    public function getRootFileName()
    {
        return $this->root_file_name ?: $this->file_name;
    }

    /**
     * @return string
     */
    public function getRootFilePath()
    {
        return $this->root_file_path ?: $this->file_path;
    }

    /**
     * @param string $file_path
     * @param string $file_name
     *
     * @return void
     */
    public function setRootFilePath($file_path, $file_name)
    {
        $this->root_file_name = $file_name;
        $this->root_file_path = $file_path;
    }

    /**
     * @param string $file_path
     *
     * @return void
     */
    public function addRequiredFilePath($file_path)
    {
        $this->required_file_paths[$file_path] = true;
    }

    /**
     * @param string $file_path
     *
     * @return void
     */
    public function addParentFilePath($file_path)
    {
        $this->parent_file_paths[$file_path] = true;
    }

    /**
     * @param string $file_path
     *
     * @return bool
     */
    public function hasParentFilePath($file_path)
    {
        return $this->file_path === $file_path || isset($this->parent_file_paths[$file_path]);
    }

    /**
     * @param string $file_path
     *
     * @return bool
     */
    public function hasAlreadyRequiredFilePath($file_path)
    {
        return isset($this->required_file_paths[$file_path]);
    }

    /**
     * @return array<int, string>
     */
    public function getRequiredFilePaths()
    {
        return array_keys($this->required_file_paths);
    }

    /**
     * @return array<int, string>
     */
    public function getParentFilePaths()
    {
        return array_keys($this->parent_file_paths);
    }

    /**
     * @return int
     */
    public function getRequireNesting()
    {
        return count($this->parent_file_paths);
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
     * @return null|string
     */
    public function getFQCLN()
    {
        return null;
    }

    /**
     * @return null|string
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
