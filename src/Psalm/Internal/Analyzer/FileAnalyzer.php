<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\CodeLocation\DocblockTypeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Exception\UnpreparedAnalysisException;
use Psalm\Internal\Codebase\Functions;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\TypeAlias\LinkableTypeAlias;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Issue\InvalidTypeImport;
use Psalm\Issue\UncaughtThrowInGlobalScope;
use Psalm\IssueBuffer;
use Psalm\NodeTypeProvider;
use Psalm\Plugin\EventHandler\Event\AfterFileAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\BeforeFileAnalysisEvent;
use Psalm\Type;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_combine;
use function array_diff_key;
use function array_keys;
use function count;
use function str_starts_with;
use function strtolower;

/**
 * @internal
 * @psalm-consistent-constructor
 */
class FileAnalyzer extends SourceAnalyzer
{
    use CanAlias;

    private ?string $root_file_path = null;

    private ?string $root_file_name = null;

    /**
     * @var array<string, bool>
     */
    private array $required_file_paths = [];

    /**
     * @var array<string, bool>
     */
    private array $parent_file_paths = [];

    /**
     * @var array<string>
     */
    private array $suppressed_issues = [];

    /**
     * @var array<string, array<string, string>>
     */
    private array $namespace_aliased_classes = [];

    /**
     * @var array<string, array<lowercase-string, string>>
     */
    private array $namespace_aliased_classes_flipped = [];

    /**
     * @var array<string, array<string, string>>
     */
    private array $namespace_aliased_classes_flipped_replaceable = [];

    /**
     * @var array<lowercase-string, InterfaceAnalyzer>
     */
    public array $interface_analyzers_to_analyze = [];

    /**
     * @var array<lowercase-string, ClassAnalyzer>
     */
    public array $class_analyzers_to_analyze = [];

    public ?Context $context = null;

    public Codebase $codebase;

    private int $first_statement_offset = -1;

    private ?NodeDataProvider $node_data = null;

    private ?Union $return_type = null;

    public function __construct(
        public ProjectAnalyzer $project_analyzer,
        protected string $file_path,
        protected string $file_name,
    ) {
        $this->source = $this;
        $this->codebase = $project_analyzer->getCodebase();
    }

    public function analyze(
        ?Context $file_context = null,
        ?Context $global_context = null,
    ): void {
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
        } catch (PhpParser\Error) {
            return;
        }

        $event = new BeforeFileAnalysisEvent($this, $this->context, $file_storage, $codebase, $stmts);
        $codebase->config->eventDispatcher->dispatchBeforeFileAnalysis($event);

        if ($codebase->alter_code) {
            foreach ($stmts as $stmt) {
                if (!$stmt instanceof PhpParser\Node\Stmt\Declare_) {
                    $this->first_statement_offset = (int) $stmt->getAttribute('startFilePos');
                    break;
                }
            }
        }

        $leftover_stmts = $this->populateCheckers($stmts);

        $this->node_data = new NodeDataProvider();
        $statements_analyzer = new StatementsAnalyzer($this, $this->node_data);

        foreach ($file_storage->docblock_issues as $docblock_issue) {
            IssueBuffer::maybeAdd($docblock_issue);
        }

        // if there are any leftover statements, evaluate them,
        // in turn causing the classes/interfaces be evaluated
        if ($leftover_stmts) {
            $statements_analyzer->analyze($leftover_stmts, $this->context, $global_context, true);

            foreach ($leftover_stmts as $leftover_stmt) {
                if ($leftover_stmt instanceof PhpParser\Node\Stmt\Return_) {
                    if ($leftover_stmt->expr) {
                        $this->return_type =
                            $statements_analyzer->node_data->getType($leftover_stmt->expr) ?? Type::getMixed();
                    } else {
                        $this->return_type = Type::getVoid();
                    }

                    break;
                }
            }
        }

        // check any leftover interfaces not already evaluated
        foreach ($this->interface_analyzers_to_analyze as $interface_analyzer) {
            $interface_analyzer->analyze();
        }

        // check any leftover classes not already evaluated

        foreach ($this->class_analyzers_to_analyze as $class_analyzer) {
            $class_analyzer->analyze(null, $this->context);
        }

        if ($codebase->config->check_for_throws_in_global_scope) {
            $uncaught_throws = $statements_analyzer->getUncaughtThrows($this->context);
            foreach ($uncaught_throws as $possibly_thrown_exception => $codelocations) {
                foreach ($codelocations as $codelocation) {
                    // issues are suppressed in ThrowAnalyzer, CallAnalyzer, etc.
                    IssueBuffer::maybeAdd(
                        new UncaughtThrowInGlobalScope(
                            $possibly_thrown_exception . ' is thrown but not caught in global scope',
                            $codelocation,
                        ),
                    );
                }
            }
        }

        // validate type imports
        if ($file_storage->type_aliases) {
            foreach ($file_storage->type_aliases as $alias) {
                if ($alias instanceof LinkableTypeAlias) {
                    $location = new DocblockTypeLocation(
                        $this->getSource(),
                        $alias->start_offset,
                        $alias->end_offset,
                        $alias->line_number,
                    );
                    $fq_source_classlike = $alias->declaring_fq_classlike_name;
                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $this->getSource(),
                        $fq_source_classlike,
                        $location,
                        null,
                        null,
                        $this->suppressed_issues,
                        new ClassLikeNameOptions(
                            true,
                            true,
                            true,
                            true,
                            true,
                        ),
                    ) === false) {
                        continue;
                    }

                    $referenced_class_storage = $codebase->classlike_storage_provider->get($fq_source_classlike);
                    if (!isset($referenced_class_storage->type_aliases[$alias->alias_name])) {
                        IssueBuffer::maybeAdd(
                            new InvalidTypeImport(
                                'Type alias ' . $alias->alias_name
                                . ' imported from ' . $fq_source_classlike
                                . ' is not defined on the source class',
                                $location,
                            ),
                        );
                    }
                }
            }
        }

        $event = new AfterFileAnalysisEvent($this, $this->context, $file_storage, $codebase, $stmts);
        $codebase->config->eventDispatcher->dispatchAfterFileAnalysis($event);

        $this->class_analyzers_to_analyze = [];
        $this->interface_analyzers_to_analyze = [];
    }

    /**
     * @param  array<int, PhpParser\Node\Stmt>  $stmts
     * @return list<PhpParser\Node\Stmt>
     */
    public function populateCheckers(array $stmts): array
    {
        $leftover_stmts = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
                $leftover_stmts[] = $stmt;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassLike) {
                $this->populateClassLikeAnalyzers($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                $namespace_name = $stmt->name ? $stmt->name->toString() : '';

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

    private function populateClassLikeAnalyzers(PhpParser\Node\Stmt\ClassLike $stmt): void
    {
        if ($stmt instanceof PhpParser\Node\Stmt\Class_ || $stmt instanceof PhpParser\Node\Stmt\Enum_) {
            if (!$stmt->name) {
                return;
            }

            // this can happen when stubbing
            if (!$this->codebase->classExists($stmt->name->name)
                && !$this->codebase->classlikes->enumExists($stmt->name->name)
            ) {
                return;
            }


            $class_analyzer = new ClassAnalyzer($stmt, $this, $stmt->name->name);

            $fq_class_name = $class_analyzer->getFQCLN();

            $this->class_analyzers_to_analyze[strtolower($fq_class_name)] = $class_analyzer;
        } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
            if (!$stmt->name) {
                return;
            }

            // this can happen when stubbing
            if (!$this->codebase->interfaceExists($stmt->name->name)) {
                return;
            }

            $class_analyzer = new InterfaceAnalyzer($stmt, $this, $stmt->name->name);

            $fq_class_name = $class_analyzer->getFQCLN();

            $this->interface_analyzers_to_analyze[strtolower($fq_class_name)] = $class_analyzer;
        }
    }

    public function addNamespacedClassAnalyzer(string $fq_class_name, ClassAnalyzer $class_analyzer): void
    {
        $this->class_analyzers_to_analyze[strtolower($fq_class_name)] = $class_analyzer;
    }

    public function addNamespacedInterfaceAnalyzer(string $fq_class_name, InterfaceAnalyzer $interface_analyzer): void
    {
        $this->interface_analyzers_to_analyze[strtolower($fq_class_name)] = $interface_analyzer;
    }

    public function getMethodMutations(
        MethodIdentifier $method_id,
        Context $this_context,
        bool $from_project_analyzer = false,
    ): void {
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
                    $this->getRootFileName(),
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
            if (str_starts_with($var, '$this->')) {
                $call_context->vars_possibly_in_scope[$var] = true;
            }
        }

        foreach ($this_context->vars_in_scope as $var => $type) {
            if (str_starts_with($var, '$this->')) {
                $call_context->vars_in_scope[$var] = $type;
            }
        }

        if (!isset($this_context->vars_in_scope['$this'])) {
            throw new UnexpectedValueException('Should exist');
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

    public function getFunctionLikeAnalyzer(MethodIdentifier $method_id): ?MethodAnalyzer
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

    /** @psalm-mutation-free */
    public function getNamespace(): ?string
    {
        return null;
    }

    /**
     * @psalm-mutation-free
     * @return array<lowercase-string, string>
     */
    public function getAliasedClassesFlipped(?string $namespace_name = null): array
    {
        if ($namespace_name && isset($this->namespace_aliased_classes_flipped[$namespace_name])) {
            return $this->namespace_aliased_classes_flipped[$namespace_name];
        }

        return $this->aliased_classes_flipped;
    }

    /**
     * @psalm-mutation-free
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable(?string $namespace_name = null): array
    {
        if ($namespace_name && isset($this->namespace_aliased_classes_flipped_replaceable[$namespace_name])) {
            return $this->namespace_aliased_classes_flipped_replaceable[$namespace_name];
        }

        return $this->aliased_classes_flipped_replaceable;
    }

    public static function clearCache(): void
    {
        TypeTokenizer::clearCache();
        Reflection::clearCache();
        Functions::clearCache();
        IssueBuffer::clearCache();
        FileManipulationBuffer::clearCache();
        FunctionLikeAnalyzer::clearCache();
        ClassLikeStorageProvider::deleteAll();
        FileStorageProvider::deleteAll();
        FileReferenceProvider::clearCache();
        InternalCallMapHandler::clearCache();
    }

    /** @psalm-mutation-free */
    public function getFileName(): string
    {
        return $this->file_name;
    }

    /** @psalm-mutation-free */
    public function getFilePath(): string
    {
        return $this->file_path;
    }

    /** @psalm-mutation-free */
    public function getRootFileName(): string
    {
        return $this->root_file_name ?: $this->file_name;
    }

    /** @psalm-mutation-free */
    public function getRootFilePath(): string
    {
        return $this->root_file_path ?: $this->file_path;
    }

    public function setRootFilePath(string $file_path, string $file_name): void
    {
        $this->root_file_name = $file_name;
        $this->root_file_path = $file_path;
    }

    public function addRequiredFilePath(string $file_path): void
    {
        $this->required_file_paths[$file_path] = true;
    }

    public function addParentFilePath(string $file_path): void
    {
        $this->parent_file_paths[$file_path] = true;
    }

    /** @psalm-mutation-free */
    public function hasParentFilePath(string $file_path): bool
    {
        return $this->file_path === $file_path || isset($this->parent_file_paths[$file_path]);
    }

    /** @psalm-mutation-free */
    public function hasAlreadyRequiredFilePath(string $file_path): bool
    {
        return isset($this->required_file_paths[$file_path]);
    }

    /**
     * @psalm-mutation-free
     * @return list<string>
     */
    public function getRequiredFilePaths(): array
    {
        return array_keys($this->required_file_paths);
    }

    /**
     * @psalm-mutation-free
     * @return list<string>
     */
    public function getParentFilePaths(): array
    {
        return array_keys($this->parent_file_paths);
    }

    /** @psalm-mutation-free */
    public function getRequireNesting(): int
    {
        return count($this->parent_file_paths);
    }

    /**
     * @psalm-mutation-free
     * @return array<string>
     */
    public function getSuppressedIssues(): array
    {
        return $this->suppressed_issues;
    }

    /**
     * @param array<int, string> $new_issues
     */
    public function addSuppressedIssues(array $new_issues): void
    {
        if (isset($new_issues[0])) {
            $new_issues = array_combine($new_issues, $new_issues);
        }

        $this->suppressed_issues = $new_issues + $this->suppressed_issues;
    }

    /**
     * @param array<int, string> $new_issues
     */
    public function removeSuppressedIssues(array $new_issues): void
    {
        if (isset($new_issues[0])) {
            $new_issues = array_combine($new_issues, $new_issues);
        }

        $this->suppressed_issues = array_diff_key($this->suppressed_issues, $new_issues);
    }

    /** @psalm-mutation-free */
    public function getFQCLN(): ?string
    {
        return null;
    }

    /** @psalm-mutation-free */
    public function getParentFQCLN(): ?string
    {
        return null;
    }

    /** @psalm-mutation-free */
    public function getClassName(): ?string
    {
        return null;
    }

    /**
     * @psalm-mutation-free
     * @return array<string, array<string, Union>>|null
     */
    public function getTemplateTypeMap(): ?array
    {
        return null;
    }

    /** @psalm-mutation-free */
    public function isStatic(): bool
    {
        return false;
    }

    /**
     * @psalm-mutation-free
     */
    public function getFileAnalyzer(): FileAnalyzer
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     */
    public function getProjectAnalyzer(): ProjectAnalyzer
    {
        return $this->project_analyzer;
    }

    /** @psalm-mutation-free */
    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }

    /** @psalm-mutation-free */
    public function getFirstStatementOffset(): int
    {
        return $this->first_statement_offset;
    }

    /** @psalm-mutation-free */
    public function getNodeTypeProvider(): NodeTypeProvider
    {
        if (!$this->node_data) {
            throw new UnexpectedValueException('There should be a node type provider');
        }

        return $this->node_data;
    }

    /** @psalm-mutation-free */
    public function getReturnType(): ?Union
    {
        return $this->return_type;
    }

    public function clearSourceBeforeDestruction(): void
    {
        unset($this->source);
    }
}
