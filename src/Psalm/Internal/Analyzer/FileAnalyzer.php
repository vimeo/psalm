<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Exception\UnpreparedAnalysisException;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\UncaughtThrowInGlobalScope;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use function implode;
use function strtolower;
use function strpos;
use function array_keys;
use function count;

/**
 * @internal
 */
class FileAnalyzer extends SourceAnalyzer implements StatementsSource
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
    private $required_file_paths = [];

    /**
     * @var array<string, bool>
     */
    private $parent_file_paths = [];

    /**
     * @var array<string>
     */
    private $suppressed_issues = [];

    /**
     * @var array<string, array<string, string>>
     */
    private $namespace_aliased_classes = [];

    /**
     * @var array<string, array<string, string>>
     */
    private $namespace_aliased_classes_flipped = [];

    /**
     * @var array<string, array<string, string>>
     */
    private $namespace_aliased_classes_flipped_replaceable = [];

    /**
     * @var array<string, InterfaceAnalyzer>
     */
    public $interface_analyzers_to_analyze = [];

    /**
     * @var array<lowercase-string, ClassAnalyzer>
     */
    public $class_analyzers_to_analyze = [];

    /**
     * @var null|Context
     */
    public $context;

    /**
     * @var ProjectAnalyzer
     */
    public $project_analyzer;

    /**
     * @var Codebase
     */
    public $codebase;

    /**
     * @var int
     */
    private $first_statement_offset = -1;

    /** @var ?\Psalm\Internal\Provider\NodeDataProvider */
    private $node_data;

    /**
     * @param string  $file_path
     * @param string  $file_name
     */
    public function __construct(ProjectAnalyzer $project_analyzer, $file_path, $file_name)
    {
        $this->source = $this;
        $this->file_path = $file_path;
        $this->file_name = $file_name;
        $this->project_analyzer = $project_analyzer;
        $this->codebase = $project_analyzer->getCodebase();
    }

    /**
     * @param  bool $preserve_analyzers
     *
     * @return void
     */
    public function analyze(
        Context $file_context = null,
        $preserve_analyzers = false,
        Context $global_context = null
    ) {
        $codebase = $this->project_analyzer->getCodebase();

        $file_storage = $codebase->file_storage_provider->get($this->file_path);

        if (!$file_storage->deep_scan && !$codebase->server_mode) {
            throw new UnpreparedAnalysisException('File ' . $this->file_path . ' has not been properly scanned');
        }

        if ($file_storage->has_visitor_issues) {
            return;
        }

        if ($file_context) {
            $this->context = $file_context;
        }

        if (!$this->context) {
            $this->context = new Context();
        }

        if ($codebase->config->useStrictTypesForFile($this->file_path)) {
            $this->context->strict_types = true;
        }

        $this->context->is_global = true;
        $this->context->defineGlobals();
        $this->context->collect_exceptions = $codebase->config->check_for_throws_in_global_scope;

        try {
            $stmts = $codebase->getStatementsForFile($this->file_path);
        } catch (PhpParser\Error $e) {
            return;
        }
        foreach ($codebase->config->before_analyze_file as $plugin_class) {
            $plugin_class::beforeAnalyzeFile($this);
        }

        if ($codebase->alter_code) {
            foreach ($stmts as $stmt) {
                if (!$stmt instanceof PhpParser\Node\Stmt\Declare_) {
                    $this->first_statement_offset = (int) $stmt->getAttribute('startFilePos');
                    break;
                }
            }
        }

        $leftover_stmts = $this->populateCheckers($stmts);

        $this->node_data = new \Psalm\Internal\Provider\NodeDataProvider();
        $statements_analyzer = new StatementsAnalyzer($this, $this->node_data);

        foreach ($file_storage->docblock_issues as $docblock_issue) {
            IssueBuffer::add($docblock_issue);
        }

        // if there are any leftover statements, evaluate them,
        // in turn causing the classes/interfaces be evaluated
        if ($leftover_stmts) {
            $statements_analyzer->analyze($leftover_stmts, $this->context, $global_context, true);
        }

        // check any leftover interfaces not already evaluated
        foreach ($this->interface_analyzers_to_analyze as $interface_analyzer) {
            $interface_analyzer->analyze();
        }

        // check any leftover classes not already evaluated

        foreach ($this->class_analyzers_to_analyze as $class_analyzer) {
            $class_analyzer->analyze(null, $this->context);
        }

        if (!$preserve_analyzers) {
            $this->class_analyzers_to_analyze = [];
            $this->interface_analyzers_to_analyze = [];
        }

        if ($codebase->config->check_for_throws_in_global_scope) {
            $uncaught_throws = $statements_analyzer->getUncaughtThrows($this->context);
            foreach ($uncaught_throws as $possibly_thrown_exception => $codelocations) {
                foreach ($codelocations as $codelocation) {
                    // issues are suppressed in ThrowAnalyzer, CallAnalyzer, etc.
                    if (IssueBuffer::accepts(
                        new UncaughtThrowInGlobalScope(
                            $possibly_thrown_exception . ' is thrown but not caught in global scope',
                            $codelocation
                        )
                    )) {
                        // fall through
                    }
                }
            }
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
                $this->populateClassLikeAnalyzers($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                $namespace_name = $stmt->name ? implode('\\', $stmt->name->parts) : '';

                $namespace_analyzer = new NamespaceAnalyzer($stmt, $this);
                $namespace_analyzer->collectAnalyzableInformation();

                $this->namespace_aliased_classes[$namespace_name] = $namespace_analyzer->getAliases()->uses;
                $this->namespace_aliased_classes_flipped[$namespace_name] =
                    $namespace_analyzer->getAliasedClassesFlipped();
                $this->namespace_aliased_classes_flipped_replaceable[$namespace_name] =
                    $namespace_analyzer->getAliasedClassesFlippedReplaceable();
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                $this->visitUse($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\GroupUse) {
                $this->visitGroupUse($stmt);
            } else {
                if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                    foreach ($stmt->stmts as $if_stmt) {
                        if ($if_stmt instanceof PhpParser\Node\Stmt\ClassLike) {
                            $this->populateClassLikeAnalyzers($if_stmt);
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
    private function populateClassLikeAnalyzers(PhpParser\Node\Stmt\ClassLike $stmt)
    {
        if (!$stmt->name) {
            return;
        }

        // this can happen when stubbing
        if (!$this->codebase->classOrInterfaceExists($stmt->name->name)) {
            return;
        }

        if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
            $class_analyzer = new ClassAnalyzer($stmt, $this, $stmt->name->name);

            $fq_class_name = $class_analyzer->getFQCLN();

            $this->class_analyzers_to_analyze[strtolower($fq_class_name)] = $class_analyzer;
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
            $class_analyzer = new InterfaceAnalyzer($stmt, $this, $stmt->name->name);

            $fq_class_name = $class_analyzer->getFQCLN();

            $this->interface_analyzers_to_analyze[$fq_class_name] = $class_analyzer;
        }
    }

    /**
     * @param string       $fq_class_name
     * @param ClassAnalyzer $class_analyzer
     *
     * @return  void
     */
    public function addNamespacedClassAnalyzer($fq_class_name, ClassAnalyzer $class_analyzer)
    {
        $this->class_analyzers_to_analyze[strtolower($fq_class_name)] = $class_analyzer;
    }

    /**
     * @param string            $fq_class_name
     * @param InterfaceAnalyzer  $interface_analyzer
     *
     * @return  void
     */
    public function addNamespacedInterfaceAnalyzer($fq_class_name, InterfaceAnalyzer $interface_analyzer)
    {
        $this->interface_analyzers_to_analyze[strtolower($fq_class_name)] = $interface_analyzer;
    }

    /**
     * @return void
     */
    public function getMethodMutations(
        \Psalm\Internal\MethodIdentifier $method_id,
        Context $this_context,
        bool $from_project_analyzer = false
    ) {
        $fq_class_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;
        $fq_class_name_lc = strtolower($fq_class_name);

        if (isset($this->class_analyzers_to_analyze[$fq_class_name_lc])) {
            $class_analyzer_to_examine = $this->class_analyzers_to_analyze[$fq_class_name_lc];
        } else {
            if (!$from_project_analyzer) {
                $this->project_analyzer->getMethodMutations(
                    $method_id,
                    $this_context,
                    $this->getRootFilePath(),
                    $this->getRootFileName()
                );
            }

            return;
        }

        $call_context = new Context($this_context->self);
        $call_context->collect_mutations = $this_context->collect_mutations;
        $call_context->collect_initializations = $this_context->collect_initializations;
        $call_context->collect_nonprivate_initializations = $this_context->collect_nonprivate_initializations;
        $call_context->initialized_methods = $this_context->initialized_methods;
        $call_context->include_location = $this_context->include_location;
        $call_context->calling_method_id = $this_context->calling_method_id;

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

        if (!isset($this_context->vars_in_scope['$this'])) {
            throw new \UnexpectedValueException('Should exist');
        }

        $call_context->vars_in_scope['$this'] = $this_context->vars_in_scope['$this'];

        $class_analyzer_to_examine->getMethodMutations($method_name, $call_context);

        foreach ($call_context->vars_possibly_in_scope as $var => $_) {
            $this_context->vars_possibly_in_scope[$var] = true;
        }

        foreach ($call_context->vars_in_scope as $var => $type) {
            $this_context->vars_in_scope[$var] = $type;
        }
    }

    public function getFunctionLikeAnalyzer(\Psalm\Internal\MethodIdentifier $method_id) : ?FunctionLikeAnalyzer
    {
        $fq_class_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;

        $fq_class_name_lc = strtolower($fq_class_name);

        if (!isset($this->class_analyzers_to_analyze[$fq_class_name_lc])) {
            return null;
        }

        $class_analyzer_to_examine = $this->class_analyzers_to_analyze[$fq_class_name_lc];

        return $class_analyzer_to_examine->getFunctionLikeAnalyzer($method_name);
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
     * @param  string|null $namespace_name
     *
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable($namespace_name = null)
    {
        if ($namespace_name && isset($this->namespace_aliased_classes_flipped_replaceable[$namespace_name])) {
            return $this->namespace_aliased_classes_flipped_replaceable[$namespace_name];
        }

        return $this->aliased_classes_flipped_replaceable;
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        Type::clearCache();
        \Psalm\Internal\Codebase\Reflection::clearCache();
        \Psalm\Internal\Codebase\Functions::clearCache();
        IssueBuffer::clearCache();
        FileManipulationBuffer::clearCache();
        FunctionLikeAnalyzer::clearCache();
        \Psalm\Internal\Provider\ClassLikeStorageProvider::deleteAll();
        \Psalm\Internal\Provider\FileStorageProvider::deleteAll();
        \Psalm\Internal\Provider\FileReferenceProvider::clearCache();
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
     * @return array<string>
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
        if (isset($new_issues[0])) {
            $new_issues = \array_combine($new_issues, $new_issues);
        }

        $this->suppressed_issues = $new_issues + $this->suppressed_issues;
    }

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function removeSuppressedIssues(array $new_issues)
    {
        if (isset($new_issues[0])) {
            $new_issues = \array_combine($new_issues, $new_issues);
        }

        $this->suppressed_issues = \array_diff_key($this->suppressed_issues, $new_issues);
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
    public function getParentFQCLN()
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
     * @return array<string, array<string, array{Type\Union}>>|null
     */
    public function getTemplateTypeMap()
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

    public function getFileAnalyzer() : FileAnalyzer
    {
        return $this;
    }

    public function getProjectAnalyzer() : ProjectAnalyzer
    {
        return $this->project_analyzer;
    }

    public function getCodebase() : Codebase
    {
        return $this->codebase;
    }

    public function getFirstStatementOffset() : int
    {
        return $this->first_statement_offset;
    }

    public function getNodeTypeProvider() : \Psalm\NodeTypeProvider
    {
        if (!$this->node_data) {
            throw new \UnexpectedValueException('There should be a node type provider');
        }

        return $this->node_data;
    }

    public function clearSourceBeforeDestruction() : void
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->source = null;
    }
}
