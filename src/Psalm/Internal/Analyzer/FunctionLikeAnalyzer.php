<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Attribute;
use Override;
use PhpParser;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\UnresolvableConstantException;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\FunctionLike\ReturnTypeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLike\ReturnTypeCollector;
use Psalm\Internal\Analyzer\Statements\DeclareAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\InstancePropertyAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\CallPurityResolver;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallReturnTypeFetcher;
use Psalm\Internal\Analyzer\Statements\Expression\DestructorAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Codebase\CliOutput;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\Codebase\PhpDeprecations;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\Codebase\TaintStore;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\FileManipulation\FunctionDocblockManipulator;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Perf;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\IterationPurity;
use Psalm\Internal\Type\PurityWildcardPaths;
use Psalm\Internal\Type\TemplateDefaults;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Internal\TypeVisitor\TypeChecker;
use Psalm\Issue\DeprecatedSyntax;
use Psalm\Issue\ImpureFunctionCall;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidDocblockParamName;
use Psalm\Issue\InvalidOverride;
use Psalm\Issue\InvalidParamDefault;
use Psalm\Issue\InvalidThrow;
use Psalm\Issue\MethodSignatureMismatch;
use Psalm\Issue\MismatchingDocblockParamType;
use Psalm\Issue\MissingAbstractPureAnnotation;
use Psalm\Issue\MissingClosureParamType;
use Psalm\Issue\MissingOverrideAttribute;
use Psalm\Issue\MissingParamType;
use Psalm\Issue\MissingThrowsDocblock;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\Issue\ReferenceConstraintViolation;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\UnresolvableConstant;
use Psalm\Issue\UnusedClosureParam;
use Psalm\Issue\UnusedDocblockParam;
use Psalm\Issue\UnusedParam;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualPropertyFetch;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Node\Stmt\VirtualWhile;
use Psalm\Node\VirtualIdentifier;
use Psalm\Node\VirtualNode;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;
use Psalm\Storage\Capabilities;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\FunctionStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\TemplateVariance;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_combine;
use function array_diff_key;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_search;
use function array_slice;
use function array_values;
use function count;
use function end;
use function explode;
use function hash;
use function implode;
use function in_array;
use function is_string;
use function krsort;
use function mb_strpos;
use function preg_match;
use function reset;
use function sort;
use function str_ends_with;
use function str_starts_with;
use function strpos;
use function strtolower;
use function strval;
use function substr;

use const SORT_NUMERIC;

/**
 * @internal
 * @template-covariant TFunction as Closure|Function_|ClassMethod|ArrowFunction
 * @psalm-import-type CapabilitySet from Capabilities
 */
abstract class FunctionLikeAnalyzer extends SourceAnalyzer
{
    private const MAGIC_METHODS_WITH_REQUIRED_PARAMS = [
        '__call',
        '__callstatic',
        '__get',
        '__set',
        '__isset',
        '__unset',
        '__unserialize',
        '__set_state',
    ];

    protected Codebase $codebase;

    /**
     * @var array<string>
     */
    protected array $suppressed_issues;

    protected bool $is_static = false;

    /**
     * @var ?array<string, Union>
     */
    protected ?array $return_vars_in_scope = [];

    /**
     * @var ?array<string, bool>
     */
    protected ?array $return_vars_possibly_in_scope = [];

    private ?Union $local_return_type = null;

    /**
     * @var array<string, bool>
     */
    protected static array $no_effects_hashes = [];

    public bool $track_mutations = false;

    /** @var CapabilitySet */
    public int $inferred_capabilities = Capabilities::NONE;

    /**
     * The part of $inferred_capabilities a closure needs for something other than reading and
     * writing the variables it captured by reference: a closure literal passed as an argument
     * reads and writes them in the scope calling with it, which already required what writing
     * them needs where it created the closure.
     *
     * @var CapabilitySet
     */
    public int $inferred_capabilities_beyond_captures = Capabilities::NONE;

    /**
     * Whether the mutation being signalled is the closure's reading or writing of a variable it
     * captured by reference.
     */
    public bool $signalling_by_ref_capture = false;

    /**
     * The mutations performed by this function-like itself, excluding those of
     * the unannotated callees in $deferred_callees.
     *
     * @var CapabilitySet
     */
    public int $intrinsic_capabilities = Capabilities::NONE;

    /**
     * The unannotated project function-likes called by this function-like
     * (graph node => whether mutations of the callee's own instance are fine),
     * whose levels are only known after analysis.
     *
     * @var array<string, bool>
     */
    public array $deferred_callees = [];

    /**
     * Whether the parameter default values are being analysed: what they do is recorded in
     * $param_default_intrinsic_capabilities and $param_default_callees instead.
     */
    public bool $tracking_param_defaults = false;

    /**
     * The mutations performed by the parameter default values themselves.
     *
     * @var CapabilitySet
     */
    public int $param_default_intrinsic_capabilities = Capabilities::NONE;

    /**
     * The unannotated project function-likes called by the parameter default values.
     *
     * @var array<string, bool>
     */
    public array $param_default_callees = [];

    /**
     * The purity templates of the enclosing scopes this closure called closures of: its purity
     * depends on them, so its type carries them (name => template).
     *
     * @var array<string, TTemplateParam>
     */
    public array $used_purity_templates = [];

    /**
     * Where the parameters of this function-like would take the `_` purity (`Closure[_]`,
     * `Traversable[_]`): what it does by calling the closures, iterating the iterables and calling
     * the methods found there, which the `_` would charge to its callers instead. Its purity is
     * inferred without those, and `--alter` adds the `_` (param name => paths =>
     * true, {@see PurityWildcardPaths}).
     *
     * @var array<string, array<string, true>>
     */
    public array $purity_wildcard_candidates = [];

    /**
     * The purity templates standing for the purity arguments of receivers a parameter's `_` could
     * take, while the purity of a call on them is inferred (marker name => param name and path,
     * {@see PurityWildcardInference}).
     *
     * @var array<string, array{string, string}>
     */
    public array $purity_wildcard_markers = [];

    /**
     * The variables the body may assign, lazily computed (variable name => true), besides the
     * value variables of foreach loops, which are in $foreach_values.
     *
     * @var array<string, true>|null
     */
    private ?array $reassigned_params = null;

    /**
     * What the value variables of the foreach loops of the body iterate over, lazily computed.
     *
     * @var array<string, list<PhpParser\Node\Expr>>|null
     */
    private ?array $foreach_values = null;

    /**
     * The docblock types of the parameters as written, or their native types, lazily computed.
     *
     * @var array<string, list<string>>|null
     */
    private ?array $param_type_strings = null;

    /**
     * Holds param nodes for functions with func_get_args calls
     *
     * @var array<string, DataFlowNode>
     */
    public array $param_nodes = [];

    /**
     * @param TFunction $function
     * @psalm-mutation-free
     */
    public function __construct(
        protected Closure|Function_|ClassMethod|ArrowFunction $function,
        SourceAnalyzer $source,
        protected FunctionLikeStorage $storage,
    ) {
        $this->source = $source;
        $this->suppressed_issues = $source->getSuppressedIssues();
        $this->codebase = $source->getCodebase();
    }

    /**
     * @param bool          $add_mutations  whether or not to add mutations to this method
     * @param array<string, Union> $byref_vars
     * @param-out array<string, Union> $byref_vars
     * @return false|null
     * @psalm-suppress PossiblyUnusedReturnValue unused but seems important
     * @psalm-suppress ComplexMethod Unavoidably complex
     */
    public function analyze(
        Context $context,
        NodeDataProvider $type_provider,
        ?Context $global_context = null,
        bool $add_mutations = false,
        array &$byref_vars = [],
    ): ?bool {
        $storage = $this->storage;

        // the code runs under the strict_types of its own file, which for a trait is not the file of the class
        $context->strict_types = DeclareAnalyzer::declaresStrictTypes($this->codebase, $this->getFilePath());

        $function_stmts = $this->function->getStmts() ?: [];

        if ($this->function instanceof ArrowFunction
            && isset($function_stmts[0])
            && $function_stmts[0] instanceof PhpParser\Node\Stmt\Return_
            && $function_stmts[0]->expr
        ) {
            $function_stmts[0]->setAttributes($function_stmts[0]->expr->getAttributes());
        }

        if ($global_context) {
            foreach ($global_context->constants as $const_name => $var_type) {
                if (!$context->hasVariable($const_name)) {
                    $context->vars_in_scope[$const_name] = $var_type;
                }
            }
        }

        $codebase = $this->codebase;
        $project_analyzer = $this->getProjectAnalyzer();

        $docblock_suppressed_issues = $storage->docblock_suppressed_issues;

        if ($codebase->track_unused_suppressions && $docblock_suppressed_issues !== []) {
            if (count($docblock_suppressed_issues) === 1 // UnusedPsalmSuppress by itself should be marked as unused
                || !$codebase->config->suppressesIssueType($docblock_suppressed_issues, 'UnusedPsalmSuppress')
            ) {
                foreach ($docblock_suppressed_issues as $offset => $issue_name) {
                    IssueBuffer::addUnusedSuppression(
                        $storage->location !== null
                            ? $storage->location->file_path
                            : $this->getFilePath(),
                        $offset,
                        $issue_name,
                        $codebase->taint_flow_graph !== null,
                    );
                }
            }
        }

        foreach ($storage->docblock_issues as $docblock_issue) {
            IssueBuffer::maybeAdd($docblock_issue);
        }

        $this->checkOverloadsAgainstSignature($storage);

        TemplateDefaults::checkBounds(
            $this,
            $storage->template_types ?? [],
            $storage->template_defaults,
            $storage->template_default_locations,
            $context->self,
            $context->parent,
            ($storage instanceof MethodStorage ? $storage->defining_fqcln . '::' : '') . $storage->cased_name,
            $this->getSuppressedIssues(),
        );

        $function_information = $this->getFunctionInformation(
            $context,
            $codebase,
            $type_provider,
            $storage,
            $add_mutations,
        );

        if ($function_information === null) {
            return null;
        }

        [
            $real_method_id,
            $method_id,
            $appearing_class_storage,
            $hash,
            $cased_method_id,
            $overridden_method_ids,
        ] = $function_information;

        $this->suppressed_issues = $this->getSource()->getSuppressedIssues() + $storage->suppressed_issues;
        if ($appearing_class_storage) {
            $this->suppressed_issues += $appearing_class_storage->suppressed_issues;
        }

        if (($storage instanceof MethodStorage || $storage instanceof FunctionStorage)
            && $storage->is_static
        ) {
            $this->is_static = true;
        }

        $statements_analyzer = new StatementsAnalyzer($this, $type_provider, true);

        // A method can be analyzed again to collect the properties it initializes, in the context of a
        // class calling or inheriting it (see CallAnalyzer::collectSpecialInformation()): its parameters stay those of
        // every call, so linking its nodes to those of that class there would make any call to it, on any object,
        // reach the properties of that class. Its regular analysis adds its taint paths.
        if ($context->collect_initializations) {
            $statements_analyzer->taint_flow_graph = null;
            $statements_analyzer->data_flow_graph = $statements_analyzer->variable_use_graph;
        }

        $byref_uses = [];
        if ($this instanceof ClosureAnalyzer && $this->function instanceof Closure) {
            foreach ($this->function->uses as $use) {
                if (!is_string($use->var->name)) {
                    continue;
                }

                $use_var_id = '$' . $use->var->name;

                $use_location = new CodeLocation($this, $use);

                $use_assignment = null;

                if ($statements_analyzer->data_flow_graph) {
                    $use_assignment = DataFlowNode::getForAssignment(
                        $use_var_id,
                        $use_location,
                    );

                    $statements_analyzer->data_flow_graph->addNode($use_assignment);

                    if ($statements_analyzer->taint_flow_graph) {
                        TaintStore::recordClosureUse(
                            $statements_analyzer,
                            $statements_analyzer->taint_flow_graph,
                            $context->vars_in_scope[$use_var_id],
                            $use_assignment,
                        );
                    }

                    $context->vars_in_scope[$use_var_id] =
                        $context->vars_in_scope[$use_var_id]->addParentNodes(
                            [$use_assignment->id => $use_assignment],
                        );
                }

                if ($use->byRef) {
                    $byref_uses[$use_var_id] = true;

                    if ($statements_analyzer->data_flow_graph && $use_assignment) {
                        $statements_analyzer->data_flow_graph->addPath(
                            $use_assignment,
                            DataFlowNode::getForClosureUse(),
                            'closure-use',
                        );
                    }
                } else {
                    $statements_analyzer->registerVariable($use_var_id, $use_location, null);
                }
            }

            $statements_analyzer->setByRefUses($byref_uses);
        } elseif ($this instanceof ClosureAnalyzer
            && $this->function instanceof ArrowFunction
            && $this->source instanceof StatementsAnalyzer
        ) {
            $param_var_ids = [];

            foreach ($this->function->params as $param) {
                if ($param->var instanceof PhpParser\Node\Expr\Variable && is_string($param->var->name)) {
                    $param_var_ids['$' . $param->var->name] = true;
                }
            }

            // an arrow function reads the variables of the enclosing scope, possibly undefined ones included
            foreach ($context->vars_in_scope as $var_id => $type) {
                if (!isset($param_var_ids[$var_id])
                    && ($type->possibly_undefined || $type->possibly_undefined_from_try)
                    && ($first_appearance = $this->source->getFirstAppearance($var_id))
                ) {
                    $statements_analyzer->registerEnclosingVariable($var_id, $first_appearance);
                }
            }
        }

        if ($storage->template_types) {
            foreach ($storage->template_types as $param_name => $_) {
                $fq_classlike_name = Type::getFQCLNFromString(
                    $param_name,
                    $this->getAliases(),
                );

                if ($codebase->classOrInterfaceExists($fq_classlike_name, null, $context)) {
                    IssueBuffer::maybeAdd(
                        new ReservedWord(
                            'Cannot use ' . $param_name . ' as template name since the class already exists',
                            new CodeLocation($this, $this->function),
                            'resource',
                        ),
                        $this->getSuppressedIssues(),
                    );
                }
            }
        }

        $template_types = $storage->template_types;

        if ($appearing_class_storage && $appearing_class_storage->template_types) {
            $template_types = array_merge($template_types ?: [], $appearing_class_storage->template_types);
        }

        $params = $storage->params;

        if ($codebase->alter_code) {
            $this->alterParams($codebase, $storage, $params, $context);
        }

        foreach ($codebase->methods_to_rename as $original_method_id => $new_method_name) {
            if ($this instanceof MethodAnalyzer
                && strtolower((string) $this->getMethodId()) === $original_method_id
            ) {
                $file_manipulations = [
                    new FileManipulation(
                        (int) $this->function->name->getAttribute('startFilePos'),
                        (int) $this->function->name->getAttribute('endFilePos') + 1,
                        $new_method_name,
                    ),
                ];

                FileManipulationBuffer::add(
                    $this->getFilePath(),
                    $file_manipulations,
                );
            }
        }

        if ($storage instanceof MethodStorage
            && $method_id instanceof MethodIdentifier
            && $overridden_method_ids
        ) {
            $params = $codebase->methods->getMethodParams(
                $method_id,
                $this,
            );
        }

        $context->capabilities = $storage->capabilities;
        if ($storage instanceof MethodStorage) {
            if (
                // Allow constructors to mutate (override immutability)
                str_ends_with((string) $storage->cased_name, '__construct')

                // ???
                || $storage->mutation_free_assumed
            ) {
                $context->capabilities |= Capabilities::EXTERNAL_MUTATION_FREE;
            }
        }

        $this->checkDeprecatedSignature();

        $check_stmts = $this->processParams(
            $statements_analyzer,
            $storage,
            $cased_method_id,
            $params,
            array_values($this->function->params),
            $context,
            (bool) $template_types,
        );

        if (!$this->checkGlobalTypes($storage, $context)) {
            $check_stmts = false;
        }

        $this->taintPromotedProperties($statements_analyzer, $context);
        // the context of an arrow function is that of the function-like it is in
        $context->by_ref_param_out_nodes = [];

        // see ArgumentsAnalyzer::getByRefParamOutNode() and FunctionCallReturnTypeFetcher::taintCallableReturnType()
        $out_method_id = $cased_method_id ?? ($this instanceof ClosureAnalyzer ? $this->getClosureId() : null);

        if ($out_method_id !== null && $codebase->taint_flow_graph) {
            foreach ($storage->params as $offset => $param) {
                if ($param->by_ref) {
                    $context->by_ref_param_out_nodes['$' . $param->name]
                        = DataFlowNode::getForMethodArgumentOut($out_method_id, $offset, $storage);
                }
            }
        }

        if ($byref_uses) {
            $ref_context = clone $context;
            $tmp_var_id = '$__tmp_byref_closure_if__' . (int) $this->function->getAttribute('startFilePos');

            $ref_context->vars_in_scope[$tmp_var_id] = Type::getBool();

            $bounds_before = $statements_analyzer->type_variable_tracker->snapshotBounds();

            $var = new VirtualVariable(
                substr($tmp_var_id, 1),
            );
            $virtual_while = new VirtualWhile(
                $var,
                $function_stmts,
            );

            $statements_analyzer->analyze(
                [$virtual_while],
                $ref_context,
            );

            foreach ($byref_uses as $var_id => $_) {
                // unsetting the variable only drops the closure's own reference to it: what the
                // closure wrote through the reference before is not known anymore
                $context->vars_in_scope[$var_id] = self::combineByRefPathTypes(
                    $ref_context->vars_in_scope[$var_id] ?? null,
                    $this instanceof ClosureAnalyzer ? $this->captured_by_ref_types_at_return[$var_id] ?? [] : [],
                    $codebase,
                );
            }

            // the body saw the literals of what it captured by reference widened, as the enclosing scope can change
            // them after the closure is created; the code after the closure is made of what that scope held, which
            // a body that does not write it leaves alone
            if ($this instanceof ClosureAnalyzer && $this->captured_by_ref_enclosing_types) {
                $this->captured_by_ref_types_at_return = [];

                $ref_context = clone $context;
                $ref_context->vars_in_scope[$tmp_var_id] = Type::getBool();

                foreach ($this->captured_by_ref_enclosing_types as $enclosed_var_id => $enclosing_type) {
                    $ref_context->vars_in_scope[$enclosed_var_id] = isset($byref_uses[$enclosed_var_id])
                        ? $enclosing_type->setProperties(['by_ref' => true])
                        : $enclosing_type;
                }

                IssueBuffer::startRecording();
                $statements_analyzer->analyze([$virtual_while], $ref_context);
                IssueBuffer::clearRecordingLevel();
                IssueBuffer::stopRecording();
            }

            // the analysis of the body below records the bounds of these passes again, with the types they found
            $statements_analyzer->type_variable_tracker->discardBoundsRecordedBetween($bounds_before);

            foreach ($byref_uses as $var_id => $_) {
                $byref_vars[$var_id] = self::combineByRefPathTypes(
                    $ref_context->vars_in_scope[$var_id] ?? null,
                    $this instanceof ClosureAnalyzer ? $this->captured_by_ref_types_at_return[$var_id] ?? [] : [],
                    $codebase,
                );
            }
        }

        foreach ($storage->unused_docblock_parameters as $param_name => $param_location) {
            if ($storage->has_undertyped_native_parameters) {
                IssueBuffer::maybeAdd(
                    new InvalidDocblockParamName(
                        'Incorrect param name $' . $param_name . ' in docblock for ' . $cased_method_id,
                        $param_location,
                    ),
                );
            } elseif ($codebase->find_unused_code) {
                 IssueBuffer::maybeAdd(
                     new UnusedDocblockParam(
                         'Docblock parameter $' . $param_name . ' in docblock for ' . $cased_method_id
                         . ' does not have a counterpart in signature parameter list',
                         $param_location,
                     ),
                 );
            }
        }

        if ($storage->signature_return_type && $storage->signature_return_type_location) {
            [$start, $end] = $storage->signature_return_type_location->getSelectionBounds();

            $codebase->analyzer->addOffsetReference(
                $this->getFilePath(),
                $start,
                $end,
                $storage->signature_return_type->getId(false),
            );
        }

        if ($storage instanceof MethodStorage && $storage->location && !$storage->allowsNamedArguments()) {
            foreach ($overridden_method_ids as $overridden_method_id) {
                $overridden_storage = $codebase->methods->getStorage($overridden_method_id);
                // a method without parameters can't be passed named arguments
                if ($overridden_storage->params && $overridden_storage->allowsNamedArguments()) {
                    IssueBuffer::maybeAdd(new MethodSignatureMismatch(
                        'Method ' . (string) $method_id . ' should accept named arguments'
                            . ' as ' . (string) $overridden_method_id . ' does',
                        $storage->location,
                    ));
                }
            }
        }

        if (ReturnTypeAnalyzer::checkReturnType(
            $this->function,
            $project_analyzer,
            $this,
            $storage,
            $context,
        ) === false) {
            $check_stmts = false;
        }

        // the type @psalm-self-out gives is checked like a @return type: its classes must exist, and its
        // template and purity arguments must fit their bounds
        if ($storage instanceof MethodStorage && $storage->self_out_type && $storage->self_out_type_location) {
            $classlike_storage = $context->self ? $codebase->classlike_storage_provider->get($context->self) : null;

            /** @psalm-suppress UnusedMethodCall This call actually has the side effect of creating issues */
            TypeExpander::expandUnion(
                $codebase,
                $storage->self_out_type,
                $classlike_storage->name ?? null,
                $classlike_storage->name ?? null,
                $classlike_storage->parent_class ?? null,
                true,
                true,
            )->setFromDocblock()->check(
                $this,
                $storage->self_out_type_location,
                $storage->suppressed_issues,
                [],
                false,
                false,
                false,
                $context,
            );
        }

        // what an assertion narrows a value to is handed out, as a return type is (a negated one hands nothing out)
        if ($storage->location !== null) {
            $asserted_types = [];
            $possibilities_list = [
                ...$storage->assertions,
                ...$storage->if_true_assertions,
                ...$storage->if_false_assertions,
            ];

            foreach ($possibilities_list as $possibilities) {
                foreach ($possibilities->rule as $assertion) {
                    if (!$assertion->isNegation() && ($asserted_type = $assertion->getAtomicType()) !== null) {
                        $asserted_types[] = $asserted_type;
                    }
                }
            }

            if ($asserted_types
                && !$this->checkOutputTypeTemplates(new Union($asserted_types), $storage->location, $storage, $context)
            ) {
                $check_stmts = false;
            }
        }

        // a signature that fails the check skips the body, but what the body initialises still counts:
        // its regular analysis reports the signature
        if (!$check_stmts && !$context->collect_initializations) {
            // the body is not analysed, so its uses of the params are unknown
            if ($storage instanceof MethodStorage
                && $this instanceof MethodAnalyzer
                && $appearing_class_storage
            ) {
                $this->recordMethodParamUses($storage, $appearing_class_storage, $this->getMethodId(), []);
            }

            return false;
        }

        if ($context->collect_initializations) {
            $statements_analyzer->addSuppressedIssues([
                'DocblockTypeContradiction',
                'InvalidReturnStatement',
                'RedundantCondition',
                'RedundantConditionGivenDocblockType',
                'TypeDoesNotContainNull',
                'TypeDoesNotContainType',
                'LoopInvalidation',
                'UndefinedInterfaceMethod',
                'UndefinedMethod',
                'PossiblyUndefinedMethod',
            ]);
        } elseif ($cased_method_id && strpos($cased_method_id, '__destruct')) {
            $statements_analyzer->addSuppressedIssues([
                'InvalidPropertyAssignmentValue',
                'PossiblyNullPropertyAssignmentValue',
            ]);
        }

        $perf_start = Perf\Collector::$enabled ? Perf\Collector::functionStart() : null;

        $project_analyzer = $statements_analyzer->getProjectAnalyzer();

        $this->track_mutations = true;

        if ($this->function instanceof ArrowFunction && (!$storage->return_type || $storage->return_type->isNever())) {
            // ArrowFunction perform a return implicitly so if the return type is never, we have to suppress the error
            // note: the never can only come from phpdoc. PHP will refuse short closures with never in signature
            $statements_analyzer->addSuppressedIssues(['NoValue']);
        }

        $cli_instance_guard = $this->function instanceof ClassMethod
            && $storage instanceof MethodStorage
            && CliOutput::isInstanceGuard($codebase, $storage, $context)
                ? $this->function
                : null;

        if ($cli_instance_guard !== null) {
            $statements_analyzer->cli_instance_guard = $function_stmts[0] ?? null;
        }

        $statements_analyzer->analyze($function_stmts, $context, $global_context);

        if ($cli_instance_guard !== null) {
            CliOutput::checkInstanceGuard($statements_analyzer, $cli_instance_guard);
        }

        // the objects created here that are still held when the function-like ends die with it
        $param_ids = [];

        foreach ($this->function->params as $param) {
            if ($param->var instanceof PhpParser\Node\Expr\Variable && is_string($param->var->name)) {
                $param_ids['$' . $param->var->name] = true;
            }
        }

        DestructorAnalyzer::chargeDroppedObjects(
            $statements_analyzer,
            $context,
            array_values($function_stmts),
            $param_ids,
        );

        if ($statements_analyzer->owns_type_variable_tracker) {
            $statements_analyzer->type_variable_tracker->reconcile(
                $codebase,
                $storage->location ?? new CodeLocation($this, $this->function),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if (!$this->function instanceof VirtualNode
            && !$context->collect_initializations
        ) {
            if ($storage instanceof MethodStorage
                && $storage->has_mutations_annotation
                && !Capabilities::allowsAnyOf($storage->containing_class_capabilities, $storage->capabilities)
            ) {
                IssueBuffer::maybeAdd(
                    new ImpureFunctionCall(
                        $storage->cased_name . ' is marked @'
                            . Capabilities::toFunctionAnnotation($storage->capabilities)
                            . ' but its containing class allows fewer capabilities'
                        .', @'.Capabilities::toClassAnnotation($storage->containing_class_capabilities),
                        $storage->location,
                    ),
                    $storage->suppressed_issues,
                );
            }

            if ($this->function->getStmts() === null) {
                $isVoid = $storage->return_type
                    ? $storage->return_type->isVoid()
                    : false;
            } else {
                $yield_types = [];
                $inferred_return_types = ReturnTypeCollector::getReturnTypes(
                    $codebase,
                    $type_provider,
                    $function_stmts,
                    $yield_types,
                    true,
                );

                $inferred_return = $inferred_return_types
                    ? Type::combineUnionTypeArray(
                        $inferred_return_types,
                        $codebase,
                    )
                    : Type::getVoid();

                // a generator returns the generator, whatever its own return statements return
                $isVoid = !$storage->has_yield && $inferred_return->isVoid();
            }
            if ($isVoid
                && !$this->function instanceof Closure
                && !$this->function instanceof ArrowFunction
                && !(
                    $storage->throw_locations
                    || $storage->throws
                )
                // its effects may be those of the closures it is given
                && $storage->purity_from_templates === []
            ) {
                // a function that may neither read state nor have an effect (no write, no
                // by-reference write, no IO) and returns nothing is useless: not pure by intent
                $this->signalMutationOnlyInferred(Capabilities::MUTATION_FREE);

                if (Capabilities::allowsAnyOf(Capabilities::READ_GLOBALS, $context->capabilities)) {
                    IssueBuffer::maybeAdd(
                        new ImpureFunctionCall(
                            'pure functions cannot have void return type'
                            . ' (at least one non-empty return statement or @throws annotation is required)',
                            new CodeLocation($this, $this->function),
                        ),
                        $this->getSuppressedIssues(),
                    );
                }
            }

            if ($this->function->getStmts() === null) {
                if ($project_analyzer->suggestsPurityAnnotations()
                    && !$storage->has_mutations_annotation
                    && $storage->location
                ) {
                    IssueBuffer::maybeAdd(
                        new MissingAbstractPureAnnotation(
                            $storage->cased_name . ' could be marked with one of @psalm-pure,'
                            . ' @psalm-capabilities or @psalm-impure '
                            . $project_analyzer->getPuritySuggestionReason(),
                            $storage->location,
                        ),
                        $storage->suppressed_issues,
                    );
                }
            } elseif ($storage->location
                && ($project_analyzer->suggestsPurityAnnotations() || $codebase->taint_flow_graph !== null)
                && ($node_id = $this->getMutationNodeId()) !== null
            ) {
                // the final level depends on the callees' levels: resolved after analysis, which reports
                // MissingPureAnnotation and queues the fix (see MutationLevelResolver), and which taint
                // analysis reads to specialize the calls of unannotated functions
                $codebase->code_use_graph->addMutationInfo($node_id, [
                    // the calls of an unannotated overridden method may run any of its overrides,
                    // which could do anything: annotating it would restrict them
                    'intrinsic' => ($storage instanceof MethodStorage
                        && $storage->overridden_somewhere
                        && !$storage->has_mutations_annotation
                            ? Capabilities::ALL
                            : $this->intrinsic_capabilities)
                        | $this->getPurityTemplateLowerBounds($codebase, $storage),
                    'allowed' => $storage->capabilities,
                    'callees' => $this->deferred_callees,
                    'default_intrinsic' => $this->param_default_intrinsic_capabilities,
                    'default_callees' => $this->param_default_callees,
                    'location' => $storage->location,
                    'cased_name' => $storage->cased_name ?? '{closure}',
                    'suppressed_issues' => $storage->suppressed_issues,
                    'class' => $storage instanceof MethodStorage ? $storage->defining_fqcln : null,
                    'start' => (int) $this->function->getAttribute('startFilePos'),
                    'fresh' => true,
                    // inline callbacks are not worth annotating, closures assigned to a variable are;
                    // an explicit `@psalm-impure` is a deliberate choice (e.g. a hook overrides may use
                    // freely), not a missing annotation, and so is the explicit annotation of an
                    // overridden method: it is what its overrides may do, not what its own body does
                    'report' => (!($this->function instanceof Closure || $this->function instanceof ArrowFunction)
                            || $this->function->getAttribute('assigned_var_id') !== null)
                        && !($storage->has_mutations_annotation
                            && ($storage->capabilities === Capabilities::ALL
                                || ($storage instanceof MethodStorage && $storage->overridden_somewhere))),
                ] + ($project_analyzer->suggestsPurityAnnotations()
                    ? [
                        'wildcards' => array_map(
                            static fn(array $paths): array => array_keys($paths),
                            $this->purity_wildcard_candidates,
                        ),
                    ]
                    : []));
            }
        }

        if ($perf_start !== null) {
            // the id of a method of an anonymous class names the file, and so does the path of the record
            Perf\Collector::functionEnd(
                $this->getFilePath(),
                $this->getFileName(),
                $this->function->getLine(),
                $appearing_class_storage?->anonymous === true
                    ? '{anonymous-class}::' . ($storage->cased_name ?? '')
                    : $cased_method_id ?? '{closure}',
                $context->collect_initializations,
                $perf_start,
            );
        }

        $final_actions = ScopeAnalyzer::getControlActions(
            $this->function->getStmts() ?? [],
            null,
            [],
        );

        // a method without a body (abstract, interface) assigns nothing to its by-reference parameters
        if ($final_actions !== [ScopeAnalyzer::ACTION_END] && $this->function->getStmts() !== null) {
            $this->examineParamTypes($statements_analyzer, $context, $codebase);
        }

        foreach ($params as $function_param) {
            // only complain if there's no type defined by a parent type
            if (!$function_param->type
                && $function_param->location
            ) {
                if ($this instanceof ClosureAnalyzer && $this->getPropertyHookId() !== null) {
                    // the property says what an untyped `$value` is
                    continue;
                }

                if ($this->function instanceof Closure
                    || $this->function instanceof ArrowFunction
                ) {
                    IssueBuffer::maybeAdd(
                        new MissingClosureParamType(
                            'Parameter $' . $function_param->name . ' has no provided type',
                            $function_param->location,
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new MissingParamType(
                            'Parameter $' . $function_param->name . ' has no provided type',
                            $function_param->location,
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues(),
                    );
                }
            }
        }

        if ($this->function instanceof Closure
            || $this->function instanceof ArrowFunction
        ) {
            $closure_declared_return_type = ClassAnalyzer::localizeClosureReturnType(
                $codebase,
                $storage->return_type,
                $context,
            );

            $this->verifyReturnType(
                $function_stmts,
                $statements_analyzer,
                $closure_declared_return_type,
                $this->source->getFQCLN(),
                $storage->return_type_location,
                $context->has_returned,
                $global_context && ($global_context->inside_call || $global_context->inside_return),
            );

            $closure_yield_types = [];

            $closure_return_types = ReturnTypeCollector::getReturnTypes(
                $codebase,
                $type_provider,
                $function_stmts,
                $closure_yield_types,
                true,
            );

            if (!$storage->return_type) {
                $closure_return_types = ReturnTypeAnalyzer::addImplicitNullReturn(
                    $closure_return_types,
                    $closure_yield_types !== [],
                    $function_stmts,
                    $type_provider,
                    $context->has_returned,
                );
            }

            $closure_return_types = ReturnTypeAnalyzer::addImplicitVoidReturn(
                $closure_return_types,
                $closure_yield_types !== [],
                $function_stmts,
                $type_provider,
                $context->has_returned,
            );

            $closure_return_type = $closure_return_types
                ? Type::combineUnionTypeArray(
                    $closure_return_types,
                    $codebase,
                )
                : Type::getVoid();

            $closure_yield_type = $closure_yield_types
                ? Type::combineUnionTypeArray(
                    $closure_yield_types,
                    $codebase,
                )
                : null;

            if ($closure_yield_type) {
                $closure_return_type = $closure_yield_type;
            }

            if ($function_type = $statements_analyzer->node_data->getType($this->function)) {
                /**
                 * @var TClosure
                 */
                $closure_atomic = $function_type->getSingleAtomic();

                $new_closure_return_type = $closure_atomic->return_type;
                if (($storage->return_type === $storage->signature_return_type)
                    && (!$storage->return_type
                        || $storage->return_type->hasMixed()
                        || UnionTypeComparator::isContainedBy(
                            $codebase,
                            $closure_return_type,
                            $storage->return_type,
                        ))
                ) {
                    $new_closure_return_type = $closure_return_type;
                } elseif ($storage->return_type
                    && !$storage->return_type_location
                    && $closure_return_type->isNullable()
                    && !UnionTypeComparator::isContainedBy($codebase, Type::getNull(), $storage->return_type)
                ) {
                    // a return type taken from the callable the closure is returned as lets it return null like a
                    // declared one, but the callable does not: keep what the closure returns, to be checked there
                    $new_closure_return_type = $closure_return_type;
                }

                // the closure's purity: what its body does, plus the purity templates of the
                // enclosing scopes it relies on
                $closure_purity = self::getClosurePurity($this->inferred_capabilities, $this->used_purity_templates);

                // consuming the generator a generator closure returns runs its body; the generator
                // is a new one at every call
                if ($storage->has_yield && $new_closure_return_type !== null) {
                    $new_closure_return_type = IterationPurity::bindGenerators(
                        $new_closure_return_type,
                        self::getClosurePurity(
                            $this->inferred_capabilities & ~Capabilities::READ_PROPS,
                            $this->used_purity_templates,
                        ),
                    )->setProperties(['reference_free' => true]);
                }

                $this->function->setAttribute(
                    CallPurityResolver::ARGUMENT_PURITY_ATTRIBUTE,
                    self::getClosurePurity(
                        $this->inferred_capabilities_beyond_captures,
                        $this->used_purity_templates,
                    ),
                );

                // a closure is a fresh object: its enclosing scope may call it freely
                $statements_analyzer->node_data->setType(
                    $this->function,
                    new Union([
                        new TClosure(
                            $closure_atomic->params,
                            $new_closure_return_type,
                            $closure_purity,
                            $closure_atomic->byref_uses,
                            $closure_atomic->extra_types,
                            $closure_atomic->from_docblock,
                            $closure_atomic->callable_id,
                            $closure_atomic->byref_use_scopes,
                        ),
                    ], ['reference_free' => true]),
                );
            }
        }

        if ($codebase->collect_references
            && !$context->collect_initializations
            && $codebase->find_unused_variables
        ) {
            $this->checkParamReferences(
                $statements_analyzer,
                $storage,
                $appearing_class_storage,
                $context,
            );
        }

        $reported_throw_groups = [];

        foreach ($storage->throws as $expected_exception => $_) {
            if (($expected_exception === 'self'
                    || $expected_exception === 'static')
                && $context->self
            ) {
                $expected_exception = $context->self;
            }

            if (isset($storage->throw_locations[$expected_exception])) {
                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $expected_exception,
                    $storage->throw_locations[$expected_exception],
                    $context,
                    $statements_analyzer->getSuppressedIssues(),
                    new ClassLikeNameOptions(
                        false,
                        false,
                        true,
                        true,
                        true,
                    ),
                )) {
                    $input_type = new Union([new TNamedObject($expected_exception)]);
                    $container_type = new Union([new TNamedObject('Exception'), new TNamedObject('Throwable')]);

                    // each intersection type (`A&B`) the class is part of has to be a throwable
                    $thrown_types = [$expected_exception => $input_type];

                    foreach ($storage->throw_intersections[$expected_exception] ?? [] as $intersected_exceptions) {
                        $intersected_types = [];

                        foreach ($intersected_exceptions as $intersected_exception) {
                            $intersected_type = new TNamedObject($intersected_exception);
                            $intersected_types[$intersected_type->getKey()] = $intersected_type;
                        }

                        $thrown_types[implode('&', [$expected_exception, ...$intersected_exceptions])] = new Union([
                            new TNamedObject($expected_exception, false, false, $intersected_types),
                        ]);
                    }

                    if (isset($storage->throw_intersections[$expected_exception])) {
                        unset($thrown_types[$expected_exception]);
                    }

                    foreach ($thrown_types as $thrown_name => $thrown_type) {
                        if (UnionTypeComparator::isContainedBy($codebase, $thrown_type, $container_type)) {
                            continue;
                        }

                        // a group is reported once, not for each of its classes
                        $group_members = explode('&', $thrown_name);
                        sort($group_members);
                        $group_key = implode('&', $group_members);

                        if (isset($reported_throw_groups[$group_key])) {
                            continue;
                        }

                        $reported_throw_groups[$group_key] = true;

                        IssueBuffer::maybeAdd(
                            new InvalidThrow(
                                'Class supplied for @throws ' . $thrown_name
                                    . ' does not implement Throwable',
                                $storage->throw_locations[$expected_exception],
                                $expected_exception,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }

                    if ($codebase->alter_code) {
                        $codebase->classlikes->handleDocblockTypeInMigration(
                            $codebase,
                            $this,
                            $input_type,
                            $storage->throw_locations[$expected_exception],
                            $context->calling_method_id,
                        );
                    }
                }
            }
        }

        $fix_missing_throws = $codebase->alter_code
            && isset($project_analyzer->getIssuesToFix()['MissingThrowsDocblock']);
        $missing_throws_docblock_errors = [];
        foreach ($statements_analyzer->getUncaughtThrows($context) as $possibly_thrown_exception => $codelocations) {
            $is_expected = false;

            foreach ($storage->throws as $expected_exception => $_) {
                if ($expected_exception === $possibly_thrown_exception
                    || (
                        $codebase->classOrInterfaceExists($possibly_thrown_exception, null, $context)
                        && (
                            $codebase->interfaceExtends($possibly_thrown_exception, $expected_exception)
                            || $codebase->classExtendsOrImplements($possibly_thrown_exception, $expected_exception)
                        )
                    )
                ) {
                    $is_expected = true;
                    break;
                }
            }

            if (!$is_expected) {
                $add_to_docblock = false;
                foreach ($codelocations as $codelocation) {
                    // issues are suppressed in ThrowAnalyzer, CallAnalyzer, etc.
                    $issue = new MissingThrowsDocblock(
                        $possibly_thrown_exception . ' is thrown but not caught - please either catch'
                        . ' or add a @throws annotation',
                        $codelocation,
                        $possibly_thrown_exception,
                    );

                    IssueBuffer::maybeAdd(
                        $issue,
                        $this->getSuppressedIssues(),
                        true,
                    );

                    if ($fix_missing_throws
                        && !$add_to_docblock
                        && !IssueBuffer::isSuppressed($issue, $this->getSuppressedIssues())
                    ) {
                        $add_to_docblock = true;
                    }
                }

                if ($add_to_docblock) {
                    $missing_docblock_exception = new TNamedObject($possibly_thrown_exception);
                    $missing_throws_docblock_errors[] = $missing_docblock_exception->toNamespacedString(
                        $this->source->getNamespace(),
                        $this->source->getAliasedClassesFlipped(),
                        $this->source->getFQCLN(),
                        true,
                    );
                }
            }
        }

        if ($missing_throws_docblock_errors !== []
            && !$this->function instanceof VirtualNode
        ) {
            $manipulator = FunctionDocblockManipulator::getForFunction(
                $project_analyzer,
                $this->source->getFilePath(),
                $this->function,
            );
            $manipulator->addThrowsDocblock($missing_throws_docblock_errors);
        }

        if ($codebase->taint_flow_graph
            && $this->function instanceof ClassMethod
            && $cased_method_id
            && $storage->specialize_call
            && $storage->location
            && isset($context->vars_in_scope['$this'])
            && $context->vars_in_scope['$this']->parent_nodes
        ) {
            // what the method leaves in the object, apart from what it returns: see MethodCallReturnTypeFetcher
            $this_out_node = DataFlowNode::getForAssignment('$this out of ' . $cased_method_id, $storage->location);

            $codebase->taint_flow_graph->addNode($this_out_node);

            foreach ($context->vars_in_scope['$this']->parent_nodes as $parent_node) {
                $codebase->taint_flow_graph->addPath(
                    $parent_node,
                    $this_out_node,
                    '$this',
                );
            }
        }

        // what the end of the function-like leaves in its by-reference parameters (see ReturnAnalyzer
        // for what each return leaves)
        self::taintByRefParamsOut($codebase, $context);

        if ($cased_method_id !== null
            && $this instanceof MethodAnalyzer
            && $context->self !== null
            && $overridden_method_ids
        ) {
            self::taintOverriddenByRefParamsOut(
                $codebase,
                $storage,
                $cased_method_id,
                $context->self,
                $overridden_method_ids,
            );
        }

        // Class methods are analyzed deferred, therefor it's required to
        // add taint sources additionally on analyze not only on call
        if ($codebase->taint_flow_graph
            && $this->function instanceof ClassMethod
            && $cased_method_id) {
            $method_source = DataFlowNode::getForMethodReturn(
                $cased_method_id,
                $storage,
            );

            FunctionCallReturnTypeFetcher::taintUsingStorage(
                $storage,
                $codebase->taint_flow_graph,
                $method_source,
                $codebase->getInputTaints(),
            );
        }

        if ($add_mutations) {
            if ($this->return_vars_in_scope !== null) {
                $context->vars_in_scope = TypeAnalyzer::combineKeyedTypes(
                    $context->vars_in_scope,
                    $this->return_vars_in_scope,
                );
            }

            if ($this->return_vars_possibly_in_scope !== null) {
                $context->vars_possibly_in_scope = [
                    ...$context->vars_possibly_in_scope,
                    ...$this->return_vars_possibly_in_scope,
                ];
            }

            foreach ($context->vars_in_scope as $var => $_) {
                if (!str_starts_with($var, '$this->') && $var !== '$this') {
                    $context->removePossibleReference($var);
                }
            }

            foreach ($context->vars_possibly_in_scope as $var => $_) {
                if (!str_starts_with($var, '$this->') && $var !== '$this') {
                    unset($context->vars_possibly_in_scope[$var]);
                }
            }

            if ($hash
                && $real_method_id
                && $this instanceof MethodAnalyzer
                && !$context->collect_initializations
            ) {
                $new_hash = hash('xxh3', $real_method_id . '::' . $context->getScopeSummary());

                if ($new_hash === $hash) {
                    self::$no_effects_hashes[$hash] = true;
                }
            }
        }

        $event = new AfterFunctionLikeAnalysisEvent(
            $this->function,
            $storage,
            $this,
            $codebase,
            [],
            $type_provider,
            $context,
        );

        if ($codebase->config->eventDispatcher->dispatchAfterFunctionLikeAnalysis($event) === false) {
            return false;
        }

        $file_manipulations = $event->getFileReplacements();

        if ($file_manipulations) {
            FileManipulationBuffer::add(
                $this->getFilePath(),
                $file_manipulations,
            );
        }

        $t = $storage instanceof MethodStorage
            || ($this instanceof ClosureAnalyzer && $this->getPropertyHookId() !== null)
            ? Attribute::TARGET_METHOD
            : Attribute::TARGET_FUNCTION;
        AttributesAnalyzer::analyze(
            $this,
            $context,
            $storage,
            $this->function->attrGroups,
            $t,
            $storage->suppressed_issues + $this->getSuppressedIssues(),
        );

        return null;
    }

    private function checkParamReferences(
        StatementsAnalyzer $statements_analyzer,
        FunctionLikeStorage $storage,
        ?ClassLikeStorage $class_storage,
        Context $context,
    ): void {
        // after `eval`, `extract` or an include the uses of the params are unknown
        $unused_params = $context->check_variables
            ? $this->detectUnusedParameters($statements_analyzer, $storage, $context)
            : [];

        if (!$storage instanceof MethodStorage
            || !$storage->cased_name
            || $storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
        ) {
            $last_unused_argument_position = $this->detectPreviousUnusedArgumentPosition(
                $storage,
                count($storage->params) - 1,
            );

            // Sort parameters in reverse order so that we can start from the end of parameters
            krsort($unused_params, SORT_NUMERIC);

            foreach ($unused_params as $unused_param_position => $unused_param_code_location) {
                $unused_param_var_name = $storage->params[$unused_param_position]->name;
                $unused_param_message = 'Param ' . $unused_param_var_name . ' is never referenced in this method';

                // Remove the key as we already report the issue
                unset($unused_params[$unused_param_position]);

                // Do not report unused required parameters
                if ($unused_param_position !== $last_unused_argument_position) {
                    break;
                }

                $last_unused_argument_position = $this->detectPreviousUnusedArgumentPosition(
                    $storage,
                    $unused_param_position - 1,
                );

                if ($this instanceof ClosureAnalyzer) {
                    // a hook cannot leave its parameter out
                    if ($this->getPropertyHookId() !== null) {
                        continue;
                    }

                    IssueBuffer::maybeAdd(
                        new UnusedClosureParam(
                            $unused_param_message,
                            $unused_param_code_location,
                        ),
                        $this->getSuppressedIssues(),
                    );
                    continue;
                }

                IssueBuffer::maybeAdd(
                    new UnusedParam(
                        $unused_param_message,
                        $unused_param_code_location,
                    ),
                    $this->getSuppressedIssues(),
                );
            }
        }

        if ($storage instanceof MethodStorage
            && $this instanceof MethodAnalyzer
            && $class_storage
        ) {
            $this->recordMethodParamUses($storage, $class_storage, $this->getMethodId(), $unused_params);
        }
    }

    /**
     * @param array<int, mixed> $unused_params
     */
    private function recordMethodParamUses(
        MethodStorage $storage,
        ClassLikeStorage $class_storage,
        MethodIdentifier $method_id,
        array $unused_params,
    ): void {
        $codebase = $this->codebase;

        if ($storage->cased_name === null || $storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
            return;
        }

        $method_id_lc = strtolower((string) $method_id);

        foreach ($storage->params as $i => $_) {
            if (!isset($unused_params[$i])) {
                $codebase->file_reference_provider->addMethodParamUse(
                    $method_id_lc,
                    $i,
                    $method_id_lc,
                );

                $method_name_lc = strtolower($storage->cased_name);

                if (!isset($class_storage->overridden_method_ids[$method_name_lc])) {
                    continue;
                }

                foreach ($class_storage->overridden_method_ids[$method_name_lc] as $parent_method_id) {
                    $codebase->file_reference_provider->addMethodParamUse(
                        strtolower((string) $parent_method_id),
                        $i,
                        $method_id_lc,
                    );
                }
            }
        }
    }

    /**
     * The values the by-reference parameters of a function-like hold where it returns, with $context,
     * flow into what the variables passed to them hold after the call (see
     * ArgumentsAnalyzer::handlePossiblyMatchingByRefParam()).
     */
    public static function taintByRefParamsOut(Codebase $codebase, Context $context): void
    {
        foreach ($context->by_ref_param_out_nodes as $var_id => $_) {
            self::taintByRefParamOut($codebase, $context, $var_id);
        }
    }

    /**
     * The by-reference parameter $var_id stops referencing the variable passed to it (unset(), =&, global,
     * static, ...): that variable keeps what the parameter holds now. Also the case for a variable bound to
     * a global. What it was bound to no longer limits what it may hold.
     */
    public static function unbindByRefParam(Codebase $codebase, Context $context, string $var_id): void
    {
        unset(
            $context->bound_globals[$var_id],
            $context->byref_constraints[$var_id],
            $context->references_rebound_in_blocks[$var_id],
        );

        if (isset($context->by_ref_param_out_nodes[$var_id])) {
            self::taintByRefParamOut($codebase, $context, $var_id);

            unset($context->by_ref_param_out_nodes[$var_id]);
        }
    }

    private static function taintByRefParamOut(Codebase $codebase, Context $context, string $var_id): void
    {
        if (!$codebase->taint_flow_graph
            || !isset($context->by_ref_param_out_nodes[$var_id])
            || !isset($context->vars_in_scope[$var_id])
        ) {
            return;
        }

        $out_node = $context->by_ref_param_out_nodes[$var_id];

        $codebase->taint_flow_graph->addNode($out_node);

        foreach ($context->vars_in_scope[$var_id]->parent_nodes as $parent_node) {
            $codebase->taint_flow_graph->addPath($parent_node, $out_node, 'param-out');
        }
    }

    /**
     * The id of the method whose body a call of $method_id runs, as the nodes of that body are keyed: the
     * body of a method of a trait is analyzed as one of each class using it, by its name in the trait.
     *
     * @psalm-capabilities read-props
     */
    public static function getBodyMethodId(Codebase $codebase, MethodIdentifier $method_id): MethodIdentifier
    {
        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id) ?? $method_id;
        $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id) ?? $declaring_method_id;

        $body_method_id = new MethodIdentifier(
            $appearing_method_id->fq_class_name,
            $declaring_method_id->method_name,
        );

        // a class using a trait method under an alias only, as it declares a method of the same name, doesn't
        // analyze the body of the trait method (see ClassAnalyzer::analyzeClassMethod())
        return (string) $codebase->methods->getDeclaringMethodId($body_method_id) === (string) $declaring_method_id
            ? $body_method_id
            : $declaring_method_id;
    }

    /**
     * The cased id of the method whose body a call of $method_id runs (see getBodyMethodId()), which keys the
     * nodes of its parameters, its by-reference parameters (see DataFlowNode::getForMethodArgumentOut()), its
     * return and the `$this` it takes and leaves.
     *
     * @psalm-capabilities read-props
     */
    public static function getCasedBodyMethodId(Codebase $codebase, MethodIdentifier $method_id): string
    {
        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id) ?? $method_id;

        return self::getBodyMethodId($codebase, $method_id)->fq_class_name
            . '::' . $codebase->methods->getStorage($declaring_method_id)->cased_name;
    }

    /**
     * A call of a method a method overrides may run it: what it leaves in its by-reference parameters is
     * left to that call too.
     *
     * When the calls of the method are specialized, its by-reference parameter out nodes are the exits of
     * its body, which only lead to its call sites through their specializations (see
     * TaintFlowGraph::connectSinksAndSources()): an edge of their own would cut its calls off from them.
     * The calls of the overridden method are then linked to as a call of its own, specialized at the
     * location of the method, which the flows a specialized call of it brings into its body don't exit
     * through.
     *
     * @param array<string, MethodIdentifier> $overridden_method_ids
     */
    private static function taintOverriddenByRefParamsOut(
        Codebase $codebase,
        FunctionLikeStorage $storage,
        string $cased_method_id,
        string $fq_class_name,
        array $overridden_method_ids,
    ): void {
        if (!$codebase->taint_flow_graph) {
            return;
        }

        $overridden_method_ids = array_map(strval(...), $overridden_method_ids);
        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        // specialized like the by-reference parameter out nodes of its calls (see
        // ArgumentsAnalyzer::getByRefParamOutNode())
        $specialization_location = $storage->specialize_call
            || TaintFlowGraph::getSpeculativelySpecializedCallee($codebase, $storage) !== null
                ? $storage->location
                : null;

        // the classes a method overridden is called through (see getCasedBodyMethodId())
        foreach ($class_storage->parent_classes + $class_storage->class_implements as $ancestor) {
            $ancestor_method_id = new MethodIdentifier($ancestor, strtolower((string) $storage->cased_name));
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($ancestor_method_id);

            if ($declaring_method_id === null
                || !in_array((string) $declaring_method_id, $overridden_method_ids, true)
            ) {
                continue;
            }

            $overridden_storage = $codebase->methods->getStorage($declaring_method_id);
            $overridden_cased_method_id = self::getCasedBodyMethodId($codebase, $ancestor_method_id);

            foreach ($storage->params as $offset => $param) {
                if (!$param->by_ref
                    || !isset($overridden_storage->params[$offset])
                    || !$overridden_storage->params[$offset]->by_ref
                ) {
                    continue;
                }

                $out_node = DataFlowNode::getForMethodArgumentOut(
                    $cased_method_id,
                    $offset,
                    $storage,
                    $specialization_location,
                );
                $overridden_out_node = DataFlowNode::getForMethodArgumentOut(
                    $overridden_cased_method_id,
                    $offset,
                    $overridden_storage,
                );

                $codebase->taint_flow_graph->addNode($out_node);
                $codebase->taint_flow_graph->addNode($overridden_out_node);
                $codebase->taint_flow_graph->addPath($out_node, $overridden_out_node, 'param-out');
            }
        }
    }

    /**
     * A template without a bound may be given null as a parameter's default value (#2431), although null is not
     * contained by it elsewhere: the caller may pick a type without null
     *
     * @psalm-pure
     */
    private static function hasTemplateWithoutBound(Union $param_type): bool
    {
        foreach ($param_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TTemplateParam && $atomic_type->as->isMixed()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<FunctionLikeParameter> $params
     * @param list<Param> $param_stmts
     */
    /**
     * Analyses a parameter default value. Like in Hack, a default value is evaluated with no
     * capabilities, or with the globals when the function-like may write them, whatever the
     * function-like may otherwise do. Function-likes without a purity annotation are not
     * restricted, so `new` in the defaults of unannotated code stays free.
     */
    private function analyzeParamDefault(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $default,
        Context $context,
    ): void {
        $capabilities = $context->capabilities;
        $track_mutations = $this->track_mutations;

        $context->capabilities = self::getParamDefaultCapabilities($capabilities);
        $context->inside_param_default = true;
        // what the default values do is recorded separately: an annotation of the function-like
        // must allow them to do it (see MutationLevelResolver)
        $this->track_mutations = true;
        $this->tracking_param_defaults = true;

        ExpressionAnalyzer::analyze($statements_analyzer, $default, $context);

        $this->tracking_param_defaults = false;
        $this->track_mutations = $track_mutations;
        $context->inside_param_default = false;
        $context->capabilities = $capabilities;
    }

    /**
     * The capabilities the parameter default values of a function-like with $capabilities may use.
     *
     * @param CapabilitySet $capabilities
     * @return CapabilitySet
     * @psalm-pure
     */
    public static function getParamDefaultCapabilities(int $capabilities): int
    {
        if ($capabilities === Capabilities::ALL) {
            return Capabilities::ALL;
        }

        return ($capabilities & Capabilities::WRITE_GLOBALS) !== 0
            ? $capabilities & (Capabilities::READ_GLOBALS | Capabilities::WRITE_GLOBALS)
            : Capabilities::NONE;
    }

    /**
     * Checks the classes of the `@global` types, like those of the `@param` types
     *
     * @psalm-capabilities read-props
     */
    private function checkGlobalTypes(FunctionLikeStorage $storage, Context $context): bool
    {
        $is_valid = true;

        foreach ($storage->global_types as $var_name => $global_type) {
            if (isset($storage->global_type_locations[$var_name])
                && $global_type->check(
                    $this,
                    $storage->global_type_locations[$var_name],
                    $storage->suppressed_issues,
                    [],
                    false,
                    false,
                    false,
                    $context,
                ) === false
            ) {
                $is_valid = false;
            }
        }

        return $is_valid;
    }

    /**
     * A promoted constructor parameter is assigned to its property: what the constructor is given flows into it,
     * as with `$this->name = $name`.
     */
    private function taintPromotedProperties(StatementsAnalyzer $statements_analyzer, Context $context): void
    {
        if (!$statements_analyzer->getCodebase()->taint_flow_graph
            || !$this->function instanceof ClassMethod
            || $this->function->name->toLowerString() !== '__construct'
            || !isset($context->vars_in_scope['$this'])
        ) {
            return;
        }

        foreach ($this->function->params as $param) {
            $var = $param->var;
            if (!$param->isPromoted() || !$var instanceof PhpParser\Node\Expr\Variable || !is_string($var->name)) {
                continue;
            }

            $name = $var->name;
            if (!isset($context->vars_in_scope['$' . $name])) {
                continue;
            }

            $attributes = $param->getAttributes();
            InstancePropertyAssignmentAnalyzer::analyze(
                $statements_analyzer,
                new VirtualPropertyFetch(
                    new VirtualVariable('this', $attributes),
                    new VirtualIdentifier($name, $attributes),
                    ['promoted_parameter' => true] + $attributes,
                ),
                $name,
                new VirtualVariable($name, $var->getAttributes()),
                $context->vars_in_scope['$' . $name],
                $context,
            );
        }
    }

    /**
     * An `@psalm-overload` is a way to call the function, so what it declares has to be what the function accepts
     * and returns: each parameter is the real one at its position (arguments reach that one at run time, by position
     * and by name), its type fits that one and every other a variadic covers, and its return type fits the declared one
     */
    private function checkOverloadsAgainstSignature(FunctionLikeStorage $storage): void
    {
        $codebase = $this->codebase;
        $location = new CodeLocation($this, $this->function, null, true);
        $function_name = $storage->cased_name ?? $this->getId();

        $last_real_param = $storage->params === [] ? null : $storage->params[count($storage->params) - 1];
        $real_variadic = $last_real_param !== null && $last_real_param->is_variadic ? $last_real_param : null;

        foreach ($storage->overloads as $overload) {
            foreach ($overload->params as $position => $param) {
                $real_param = $storage->params[$position] ?? $real_variadic;

                if ($real_param === null) {
                    IssueBuffer::maybeAdd(
                        new InvalidDocblock(
                            'Parameter $' . $param->name . ' of an @psalm-overload of ' . $function_name
                                . ' is not a parameter of its signature',
                            $location,
                        ),
                        $this->getSuppressedIssues(),
                    );

                    continue;
                }

                if (!$param->is_variadic && !$real_param->is_variadic && $param->name !== $real_param->name) {
                    IssueBuffer::maybeAdd(
                        new InvalidDocblock(
                            'Parameter $' . $param->name . ' of an @psalm-overload of ' . $function_name
                                . ' is at the position of $' . $real_param->name . ' in its signature',
                            $location,
                        ),
                        $this->getSuppressedIssues(),
                    );

                    continue;
                }

                $covered_params = $param->is_variadic
                    ? (array_slice($storage->params, $position) ?: [$real_param])
                    : [$real_param];

                if ($param->is_optional || $param->is_variadic) {
                    foreach ($covered_params as $covered_param) {
                        if (!$covered_param->is_optional && !$covered_param->is_variadic) {
                            IssueBuffer::maybeAdd(
                                new InvalidDocblock(
                                    'Parameter $' . $param->name . ' of an @psalm-overload of ' . $function_name
                                        . ' is optional, but $' . $covered_param->name
                                        . ' is required in its signature',
                                    $location,
                                ),
                                $this->getSuppressedIssues(),
                            );

                            break;
                        }
                    }
                }

                foreach ($covered_params as $covered_param) {
                    if (self::isOverloadTypeOutsideOfTheSignature($codebase, $param->type, $covered_param->type)) {
                        IssueBuffer::maybeAdd(
                            new InvalidDocblock(
                                'The type ' . $param->type?->getId() . ' of parameter $' . $param->name
                                    . ' of an @psalm-overload of ' . $function_name
                                    . ' is not accepted by its signature, which declares '
                                    . $covered_param->type?->getId(),
                                $location,
                            ),
                            $this->getSuppressedIssues(),
                        );

                        break;
                    }
                }
            }

            $last_overload_param = $overload->params === [] ? null : $overload->params[count($overload->params) - 1];

            if ($last_overload_param === null || !$last_overload_param->is_variadic) {
                foreach (array_slice($storage->params, count($overload->params)) as $omitted_param) {
                    if (!$omitted_param->is_optional && !$omitted_param->is_variadic) {
                        IssueBuffer::maybeAdd(
                            new InvalidDocblock(
                                'An @psalm-overload of ' . $function_name . ' leaves out $' . $omitted_param->name
                                    . ', which is required in its signature',
                                $location,
                            ),
                            $this->getSuppressedIssues(),
                        );

                        break;
                    }
                }
            }

            if (self::isOverloadTypeOutsideOfTheSignature($codebase, $overload->return_type, $storage->return_type)) {
                IssueBuffer::maybeAdd(
                    new InvalidDocblock(
                        'The return type ' . $overload->return_type?->getId() . ' of an @psalm-overload of '
                            . $function_name . ' is not contained by the declared one, '
                            . $storage->return_type?->getId(),
                        $location,
                    ),
                    $this->getSuppressedIssues(),
                );
            }
        }
    }

    /**
     * `self` and `static` stand for the class a call is made on, so what an overload says of them is not compared
     *
     * @psalm-pure
     */
    private static function isSelfOrStatic(Union $type): bool
    {
        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TNamedObject
                && ($atomic_type->is_static || in_array(strtolower($atomic_type->value), ['self', 'static'], true))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the type an overload declares is not contained by the one of the signature: only decided for types
     * without templates, which the overload may bind differently
     */
    private static function isOverloadTypeOutsideOfTheSignature(
        Codebase $codebase,
        ?Union $overload_type,
        ?Union $signature_type,
    ): bool {
        return $overload_type !== null
            && $signature_type !== null
            && !self::isSelfOrStatic($overload_type)
            && !self::isSelfOrStatic($signature_type)
            && !$overload_type->hasTemplate()
            && !$signature_type->hasTemplate()
            && $overload_type->getTemplateTypes() === []
            && $signature_type->getTemplateTypes() === []
            && !UnionTypeComparator::isContainedBy($codebase, $overload_type, $signature_type);
    }

    /**
     * Checks a type a method hands out other than as its return type (`@param-out`, a by-ref parameter, what an
     * assertion narrows to) for the contravariant templates of its class. Covariant ones were never checked there.
     *
     * @return bool whether the type has no errors, as for check()
     * @psalm-capabilities read-props
     */
    private function checkOutputTypeTemplates(
        Union $type,
        CodeLocation $location,
        FunctionLikeStorage $storage,
        Context $context,
    ): bool {
        if (!$storage instanceof MethodStorage
            || $storage->defining_fqcln === null
            || !$this->function instanceof ClassMethod
            || strtolower($this->function->name->name) === '__construct'
            || $this->codebase->classlike_storage_provider->get($storage->defining_fqcln)->template_contravariants
                === null
        ) {
            return true;
        }

        return (new TypeChecker(
            $this,
            $location,
            $storage->suppressed_issues,
            [],
            false,
            false,
            TemplateVariance::Covariant,
            $context,
            true,
        ))->check($type);
    }

    /**
     * A trait method is analyzed as a method of the class using the trait, where the templates of the trait
     * are the types given to `@use`: an argument of such a template type fits the parameter of a call the
     * concrete type fits.
     */
    public static function bindTraitTemplatesToUse(
        Codebase $codebase,
        FunctionLikeStorage $storage,
        Context $context,
        Union $type,
    ): Union {
        if (!$storage instanceof MethodStorage
            || $storage->defining_fqcln === null
            || $storage->cased_name === null
            || $context->self === null
            || strtolower($storage->defining_fqcln) === strtolower($context->self)
        ) {
            return $type;
        }

        $declaring_storage = $codebase->classlike_storage_provider->get($storage->defining_fqcln);

        if (!$declaring_storage->is_trait || $declaring_storage->template_types === null) {
            return $type;
        }

        $class_template_params = ClassTemplateParamCollector::collect(
            $codebase,
            $declaring_storage,
            $codebase->classlike_storage_provider->get($context->self),
            strtolower($storage->cased_name),
        );

        if ($class_template_params === null) {
            return $type;
        }

        // a template narrowed by a check (`T as string` after `is_string($x)`) is not the type it is bound to
        $bound_atomic_types = [];
        $narrowed_atomic_types = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TTemplateParam
                && isset($declaring_storage->template_types[$atomic_type->param_name][$atomic_type->defining_class])
                && !$atomic_type->as->equals(
                    $declaring_storage->template_types[$atomic_type->param_name][$atomic_type->defining_class],
                )
            ) {
                $narrowed_atomic_types[] = $atomic_type;
            } else {
                $bound_atomic_types[] = $atomic_type;
            }
        }

        if ($bound_atomic_types === []) {
            return $type;
        }

        $bound_type = TemplateStandinTypeReplacer::replace(
            $narrowed_atomic_types === [] ? $type : $type->getBuilder()->setTypes($bound_atomic_types)->freeze(),
            new TemplateResult($class_template_params, []),
            $codebase,
            null,
            null,
            null,
            $context->self,
        );

        return $narrowed_atomic_types === []
            ? $bound_type
            : Type::combineUnionTypes($bound_type, new Union($narrowed_atomic_types), $codebase);
    }

    /**
     * Reports the parameters and the by-reference void returns PHP deprecates, whatever PHP version is analysed
     */
    private function checkDeprecatedSignature(): void
    {
        $function = $this->function;

        if ($function->byRef
            && $function->returnType instanceof PhpParser\Node\Identifier
            && $function->returnType->toLowerString() === 'void'
        ) {
            $this->reportDeprecatedSyntax(
                PhpDeprecations::getDeprecatedSinceMessage(
                    'Returning by reference from a void function',
                    8_01_00,
                    null,
                ),
                $function->returnType,
            );
        }

        $last_required_param = null;

        foreach ($function->params as $param) {
            if ($param->default === null && !$param->variadic) {
                $last_required_param = $param;
            }
        }

        $before_required = $last_required_param !== null;

        foreach ($function->params as $param) {
            if ($param === $last_required_param) {
                $before_required = false;
            }

            if ($param->default === null || !$param->var instanceof PhpParser\Node\Expr\Variable) {
                continue;
            }

            $name = '$' . (is_string($param->var->name) ? $param->var->name : '');
            $null_default = $param->default instanceof PhpParser\Node\Expr\ConstFetch
                && $param->default->name->toLowerString() === 'null';

            // a promoted parameter can't be implicitly nullable at all
            if ($null_default && $param->type !== null && $param->flags === 0 && !self::typeAllowsNull($param->type)) {
                $this->reportDeprecatedSyntax(
                    PhpDeprecations::getDeprecatedSinceMessage(
                        'Making the type of ' . $name . ' nullable with its null default',
                        8_04_00,
                        null,
                    ) . ', declare the type nullable',
                    $param->type,
                );
            }

            if ($before_required
                && $last_required_param !== null
                && $last_required_param->var instanceof PhpParser\Node\Expr\Variable
            ) {
                $this->reportDeprecatedSyntax(
                    PhpDeprecations::getDeprecatedSinceMessage(
                        'Declaring the optional parameter ' . $name . ' before the required parameter $'
                            . (is_string($last_required_param->var->name) ? $last_required_param->var->name : ''),
                        // PHP 8.0 to 8.3 accepted a typed parameter whose default is null
                        $null_default && $param->type !== null ? 8_04_00 : 8_00_00,
                        null,
                    ) . ', remove its default',
                    $param,
                );
            }
        }
    }

    private static function typeAllowsNull(
        PhpParser\Node\ComplexType|PhpParser\Node\Identifier|PhpParser\Node\Name $type,
    ): bool {
        if ($type instanceof PhpParser\Node\NullableType) {
            return true;
        }

        $types = $type instanceof PhpParser\Node\UnionType ? $type->types : [$type];

        foreach ($types as $atomic_type) {
            if ($atomic_type instanceof PhpParser\Node\Identifier
                && in_array($atomic_type->toLowerString(), ['null', 'mixed'], true)
            ) {
                return true;
            }
        }

        return false;
    }

    private function reportDeprecatedSyntax(string $message, PhpParser\Node $node): void
    {
        IssueBuffer::maybeAdd(
            new DeprecatedSyntax($message, new CodeLocation($this, $node)),
            $this->getSuppressedIssues(),
        );
    }

    /**
     * @param list<FunctionLikeParameter> $params
     * @param list<Param> $param_stmts
     */
    private function processParams(
        StatementsAnalyzer $statements_analyzer,
        FunctionLikeStorage $storage,
        ?string $cased_method_id,
        array $params,
        array $param_stmts,
        Context $context,
        bool $has_template_types,
    ): bool {
        $check_stmts = true;
        $codebase = $statements_analyzer->getCodebase();
        $project_analyzer = $statements_analyzer->getProjectAnalyzer();

        foreach ($params as $offset => $function_param) {
            $function_param_id = '$' . $function_param->name;
            $signature_type = $function_param->signature_type;
            $signature_type_location = $function_param->signature_type_location;

            // the property type a promoted parameter inherits is checked where it is written, the native type here
            $inherited_type = $function_param->promoted_property
                && $storage instanceof MethodStorage
                && $storage->defining_fqcln !== null
                && ($codebase->classlike_storage_provider->get($storage->defining_fqcln)
                    ->properties[$function_param->name]->inherited_type ?? false);

            // a docblock type inherited from an overridden method keeps the native types the override adds, which
            // are checked where the override declares them
            $inherits_docblock_type = $function_param->has_docblock_type
                && isset($storage->params[$offset])
                && !$storage->params[$offset]->has_docblock_type;

            if ($signature_type && $signature_type_location && $signature_type->hasObjectType()) {
                $referenced_type = $signature_type;
                if ($referenced_type->isNullable()) {
                    $referenced_type = $referenced_type->getBuilder();
                    $referenced_type->removeType('null');
                    $referenced_type = $referenced_type->freeze();
                }
                [$start, $end] = $signature_type_location->getSelectionBounds();
                $codebase->analyzer->addOffsetReference(
                    $this->getFilePath(),
                    $start,
                    $end,
                    $referenced_type->getId(false),
                );
            }

            if ($signature_type) {
                $signature_type = TypeExpander::expandUnion(
                    $codebase,
                    $signature_type,
                    $context->self,
                    $context->self,
                    $this->getParentFQCLN(),
                );
            }


            $parent_nodes = [];
            if ($statements_analyzer->data_flow_graph
                && $function_param->location
            ) {
                $param_assignment = DataFlowNode::getForAssignment(
                    $function_param_id,
                    $function_param->location,
                );

                $statements_analyzer->data_flow_graph->addNode($param_assignment);

                // the arguments a closure is called with through a variable flow into its parameters, see
                // FunctionCallReturnTypeFetcher::taintCallableReturnType()
                $param_method_id = $cased_method_id
                    ?? ($this instanceof ClosureAnalyzer ? $this->getClosureId() : null);

                if ($param_method_id !== null) {
                    $type_source = DataFlowNode::getForMethodArgument(
                        $param_method_id,
                        $offset,
                        $storage,
                        null,
                    );

                    $removed_taints = $function_param->signature_type?->getTaintsToRemove($codebase->getInputTaints())
                        ?? 0;

                    $statements_analyzer->data_flow_graph->addPath(
                        $type_source,
                        $param_assignment,
                        'param',
                        0,
                        $removed_taints,
                    );

                    // the arguments after the first, see ArgumentAnalyzer::addVariadicArgumentPath()
                    if ($function_param->is_variadic) {
                        $variadic_source = DataFlowNode::getForVariadicArguments($type_source);

                        $statements_analyzer->data_flow_graph->addPath(
                            $variadic_source,
                            $param_assignment,
                            'param',
                            0,
                            $removed_taints,
                        );
                    }
                }

                if ($storage->variadic) {
                    $this->param_nodes += [$param_assignment->id => $param_assignment];
                }

                $parent_nodes = [$param_assignment->id => $param_assignment];
            }

            if ($function_param->type) {
                $param_type = $function_param->type;

                if ($function_param->type_location && !$inherited_type) {
                    TypeChecker::checkDeferredIntersections(
                        $param_type,
                        $this,
                        $function_param->type_location,
                        $storage->suppressed_issues,
                        $context,
                    );
                }

                // `static` is what `$this` is, with the templates of the class, and the class itself in a final one
                $this_type = $context->vars_in_scope['$this'] ?? null;
                $this_object = $this_type !== null && $this_type->isSingle() ? $this_type->getSingleAtomic() : null;

                try {
                    $param_type = TypeExpander::expandUnion(
                        $codebase,
                        $param_type,
                        $context->self,
                        $this_object instanceof TNamedObject ? $this_object : $context->self,
                        $this->getParentFQCLN(),
                        true,
                        $param_type->hasConditional(),
                        ($this_object instanceof TNamedObject && !$this_object->is_static)
                            || ($context->self !== null
                                && $codebase->classlike_storage_provider->has($context->self)
                                && $codebase->classlike_storage_provider->get($context->self)->final),
                        true,
                        false,
                        true,
                    );
                } catch (UnresolvableConstantException $e) {
                    if ($function_param->type_location !== null && !$inherited_type) {
                        IssueBuffer::maybeAdd(
                            new UnresolvableConstant(
                                "Could not resolve constant {$e->class_name}::{$e->const_name}",
                                $function_param->type_location,
                            ),
                            $storage->suppressed_issues,
                            true,
                        );
                    }
                }

                $checked_type = $param_type;

                if ($inherits_docblock_type && $signature_type) {
                    $checked_types = array_diff_key($param_type->getAtomicTypes(), $signature_type->getAtomicTypes());
                    $checked_type = $checked_types ? new Union($checked_types) : null;
                }

                if ($function_param->type_location && !$inherited_type) {
                    $own_param = $storage->params[$offset] ?? null;

                    // a type the parameter takes from the overridden method is checked there, for its templates
                    if ($checked_type
                        && !$checked_type->checked
                        && !(new TypeChecker(
                            $this,
                            $function_param->type_location,
                            $storage->suppressed_issues,
                            [],
                            false,
                            false,
                            $this->function instanceof ClassMethod
                                && strtolower($this->function->name->name) !== '__construct'
                                && $function_param === $own_param
                                    ? TemplateVariance::Contravariant
                                    : null,
                            $context,
                            false,
                            $function_param !== $own_param,
                        ))->check($checked_type)
                    ) {
                        $check_stmts = false;
                    }

                    // the method's own `@param-out` is checked here even if the type it takes is the overridden
                    // method's, which checks only what it declares
                    $output_type = $function_param->out_type
                        ?? ($function_param->by_ref && $function_param === $own_param ? $param_type : null);

                    if ($output_type !== null
                        && $own_param !== null
                        && !$this->checkOutputTypeTemplates(
                            $output_type,
                            $function_param === $own_param
                                ? $function_param->type_location
                                : $own_param->location ?? $function_param->type_location,
                            $storage,
                            $context,
                        )
                    ) {
                        $check_stmts = false;
                    }
                }

                $param_type = $param_type->addParentNodes($parent_nodes);
            } else {
                $param_type = new Union([new TMixed()], [
                    'by_ref' => $function_param->by_ref,
                    'parent_nodes' => $parent_nodes,
                ]);
                $checked_type = $param_type;
            }

            $var_type = $param_type;

            if ($function_param->is_variadic) {
                if ($storage->allowsNamedArguments()) {
                    $var_type = new Union([
                        new TArray([self::getVariadicKeyType($storage), $param_type]),
                    ], [
                        'by_ref' => $function_param->by_ref,
                        'parent_nodes' => $parent_nodes,
                    ]);
                } else {
                    $var_type = new Union([
                        Type::getListAtomic($param_type),
                    ], [
                        'by_ref' => $function_param->by_ref,
                        'parent_nodes' => $parent_nodes,
                    ]);
                }
            }

            $context->vars_in_scope[$function_param_id] = $var_type;
            $context->vars_possibly_in_scope[$function_param_id] = true;

            if ($function_param->by_ref) {
                $context->vars_in_scope[$function_param_id] =
                    $context->vars_in_scope[$function_param_id]->setProperties(['by_ref' => true]);
                $context->references_to_external_scope[$function_param_id] = true;
            }

            $parser_param = $this->function->getParams()[$offset] ?? null;

            if ($function_param->location) {
                $statements_analyzer->registerVariable(
                    $function_param_id,
                    $function_param->location,
                    null,
                );
            }

            if (!$function_param->type_location || !$function_param->location) {
                if ($parser_param && $parser_param->default) {
                    $this->analyzeParamDefault($statements_analyzer, $parser_param->default, $context);
                }

                continue;
            }

            if ($signature_type && !$inherited_type) {
                $union_comparison_result = new TypeComparisonResult();

                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $param_type,
                    $signature_type,
                    false,
                    false,
                    $union_comparison_result,
                ) && !$union_comparison_result->type_coerced_from_mixed
                ) {
                    if ($codebase->alter_code
                        && isset($project_analyzer->getIssuesToFix()['MismatchingDocblockParamType'])
                    ) {
                        $this->addOrUpdateParamType($project_analyzer, $function_param->name, $signature_type, true);

                        continue;
                    }

                    IssueBuffer::maybeAdd(
                        new MismatchingDocblockParamType(
                            'Parameter ' . $function_param_id . ' has wrong type \'' . $param_type->getId() .
                                '\', should be \'' . $signature_type->getId() . '\'',
                            $function_param->type_location,
                        ),
                        $storage->suppressed_issues,
                        true,
                    );

                    if ($signature_type->check(
                        $this,
                        $function_param->type_location,
                        $storage->suppressed_issues,
                        [],
                        false,
                    ) === false) {
                        $check_stmts = false;
                    }

                    continue;
                }
            }

            if ($parser_param && $parser_param->default) {
                $this->analyzeParamDefault($statements_analyzer, $parser_param->default, $context);

                $default_type = $statements_analyzer->node_data->getType($parser_param->default);

                if ($default_type
                    && !$default_type->hasMixed()
                    && !($default_type->isNull() && self::hasTemplateWithoutBound($param_type))
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $default_type,
                        $param_type,
                        false,
                        false,
                        null,
                        !$parser_param->default instanceof New_,
                    )
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidParamDefault(
                            'Default value type ' . $default_type->getId() . ' for argument ' . ($offset + 1)
                                . ' of method ' . $cased_method_id
                                . ' does not match the given type ' . $param_type->getId(),
                            $function_param->type_location,
                        ),
                        $storage->suppressed_issues,
                    );
                }

                if ($default_type
                    && !$default_type->isNull()
                    && $param_type->isSingleAndMaybeNullable()
                    && $param_type->getCallableTypes()
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidParamDefault(
                            'Default value type for ' . $param_type->getId() . ' argument ' . ($offset + 1)
                                . ' of method ' . $cased_method_id
                                . ' can only be null, ' . $default_type->getId() . ' specified',
                            $function_param->type_location,
                        ),
                        $storage->suppressed_issues,
                    );
                }
            }

            if ($has_template_types && !$inherited_type) {
                if ($checked_type?->check(
                    $this->source,
                    $function_param->type_location,
                    $this->suppressed_issues,
                    [],
                    false,
                ) === false) {
                    $check_stmts = false;
                }
            } elseif (!$inherited_type) {
                if ($param_type->isVoid()) {
                    IssueBuffer::maybeAdd(
                        new ParadoxicalCondition(
                            'Parameter cannot be void',
                            $function_param->type_location,
                        ),
                        $this->suppressed_issues,
                    );
                }

                if ($param_type->isNever()) {
                    IssueBuffer::maybeAdd(
                        new ParadoxicalCondition(
                            'Parameter cannot be never',
                            $function_param->type_location,
                        ),
                        $this->suppressed_issues,
                    );
                }

                if ($checked_type?->check(
                    $this->source,
                    $function_param->type_location,
                    $this->suppressed_issues,
                    [],
                    false,
                ) === false) {
                    $check_stmts = false;
                }
            }

            if (($inherited_type
                    || $inherits_docblock_type
                    || ($function_param->type_location !== $function_param->signature_type_location
                        && $function_param->type?->getId() !== $function_param->signature_type?->getId()))
                && $function_param->signature_type_location
                && $function_param->signature_type
            ) {
                // only the classes: the docblock type replaces the native one everywhere else
                $signature_classes = array_filter(
                    $function_param->signature_type->getAtomicTypes(),
                    static fn(Atomic $atomic): bool => $atomic instanceof TNamedObject
                        && !ClassLikeAnalyzer::isReservedClassName($atomic->value),
                );

                if ($signature_classes && (new Union($signature_classes))->check(
                    $this->source,
                    $function_param->signature_type_location,
                    $this->suppressed_issues,
                    [],
                    false,
                ) === false) {
                    $check_stmts = false;
                }
            }

            if ($function_param->by_ref) {
                // register by ref params as having been used, to avoid false positives
                // @todo change the assignment analysis *just* for byref params
                // so that we don't have to do this
                $context->hasVariable('$' . $function_param->name);
            }

            if (count($param_stmts) === count($params)) {
                AttributesAnalyzer::analyze(
                    $this,
                    $context,
                    $function_param,
                    $param_stmts[$offset]->attrGroups,
                    Attribute::TARGET_PARAMETER
                        | ($function_param->promoted_property ? Attribute::TARGET_PROPERTY : 0),
                    $storage->suppressed_issues + $this->getSuppressedIssues(),
                );
            }
        }

        return $check_stmts;
    }

    /**
     * A variadic parameter gets arguments passed by position only when the parameters before it are passed by
     * position too, which `@arguments-by-name` and `@optional-arguments-by-name` may forbid
     */
    private static function getVariadicKeyType(FunctionLikeStorage $storage): Union
    {
        foreach ($storage->params as $param) {
            if ($storage->requiresNamedArgument($param)) {
                return Type::getString();
            }
        }

        return Type::getArrayKey();
    }

    /**
     * @param FunctionLikeParameter[] $params
     */
    private function alterParams(
        Codebase $codebase,
        FunctionLikeStorage $storage,
        array $params,
        Context $context,
    ): void {
        foreach ($this->function->params as $param) {
            $param_name_node = null;

            if ($param->type instanceof PhpParser\Node\Name) {
                $param_name_node = $param->type;
            } elseif ($param->type instanceof PhpParser\Node\NullableType
                && $param->type->type instanceof PhpParser\Node\Name
            ) {
                $param_name_node = $param->type->type;
            }

            if ($param_name_node) {
                $resolved_name = ClassLikeAnalyzer::getFQCLNFromNameObject($param_name_node, $this->getAliases());

                $parent_fqcln = $this->getParentFQCLN();

                if ($resolved_name === 'self' && $context->self) {
                    $resolved_name = $context->self;
                } elseif ($resolved_name === 'parent' && $parent_fqcln) {
                    $resolved_name = $parent_fqcln;
                }

                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $this,
                    $param_name_node,
                    $resolved_name,
                    $context,
                    false,
                    true,
                );
            }
        }

        if ($this->function->returnType) {
            $return_name_node = null;

            if ($this->function->returnType instanceof PhpParser\Node\Name) {
                $return_name_node = $this->function->returnType;
            } elseif ($this->function->returnType instanceof PhpParser\Node\NullableType
                && $this->function->returnType->type instanceof PhpParser\Node\Name
            ) {
                $return_name_node = $this->function->returnType->type;
            }

            if ($return_name_node) {
                $resolved_name = ClassLikeAnalyzer::getFQCLNFromNameObject($return_name_node, $this->getAliases());

                $parent_fqcln = $this->getParentFQCLN();

                if ($resolved_name === 'self' && $context->self) {
                    $resolved_name = $context->self;
                } elseif ($resolved_name === 'parent' && $parent_fqcln) {
                    $resolved_name = $parent_fqcln;
                }

                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $this,
                    $return_name_node,
                    $resolved_name,
                    $context,
                    false,
                    true,
                );
            }
        }

        if ($storage->return_type
            && $storage->return_type_location
            && $storage->return_type_location !== $storage->signature_return_type_location
        ) {
            $replace_type = TypeExpander::expandUnion(
                $codebase,
                $storage->return_type,
                $context->self,
                'static',
                $this->getParentFQCLN(),
                false,
            );

            $codebase->classlikes->handleDocblockTypeInMigration(
                $codebase,
                $this,
                $replace_type,
                $storage->return_type_location,
                $context->calling_method_id,
            );
        }

        foreach ($params as $function_param) {
            if ($function_param->type
                && $function_param->type_location
                && $function_param->type_location !== $function_param->signature_type_location
                && $function_param->type_location->file_path === $this->getFilePath()
            ) {
                $replace_type = TypeExpander::expandUnion(
                    $codebase,
                    $function_param->type,
                    $context->self,
                    'static',
                    $this->getParentFQCLN(),
                    false,
                );

                $codebase->classlikes->handleDocblockTypeInMigration(
                    $codebase,
                    $this,
                    $replace_type,
                    $function_param->type_location,
                    $context->calling_method_id,
                );
            }
        }
    }

    /**
     * @param array<PhpParser\Node\Stmt> $function_stmts
     */
    public function verifyReturnType(
        array $function_stmts,
        StatementsAnalyzer $statements_analyzer,
        ?Union $return_type = null,
        ?string $fq_class_name = null,
        ?CodeLocation $return_type_location = null,
        bool $did_explicitly_return = false,
        bool $closure_inside_call = false,
    ): void {
        ReturnTypeAnalyzer::verifyReturnType(
            $this->function,
            $function_stmts,
            $statements_analyzer,
            $statements_analyzer->node_data,
            $this,
            $return_type,
            $fq_class_name,
            $fq_class_name,
            $return_type_location,
            [],
            $did_explicitly_return,
            $closure_inside_call,
        );
    }

    public function addOrUpdateParamType(
        ProjectAnalyzer $project_analyzer,
        string $param_name,
        Union $inferred_return_type,
        bool $docblock_only = false,
    ): void {
        $manipulator = FunctionDocblockManipulator::getForFunction(
            $project_analyzer,
            $this->source->getFilePath(),
            $this->function,
        );

        $codebase = $project_analyzer->getCodebase();
        $is_final = true;
        $fqcln = $this->source->getFQCLN();

        if ($fqcln !== null && $this instanceof MethodAnalyzer) {
            $class_storage = $codebase->classlike_storage_provider->get($fqcln);
            $is_final = $this->function->isFinal() || $class_storage->final;
        }

        $allow_native_type = !$docblock_only
            && $codebase->analysis_php_version_id >= 7_00_00
            && (
                $codebase->allow_backwards_incompatible_changes
                || $is_final
                || !$this instanceof MethodAnalyzer
            );

        $manipulator->setParamType(
            $param_name,
            $allow_native_type
                ? $inferred_return_type->toPhpString(
                    $this->source->getNamespace(),
                    $this->source->getAliasedClassesFlipped(),
                    $this->source->getFQCLN(),
                    $project_analyzer->getCodebase()->analysis_php_version_id,
                ) : null,
            $inferred_return_type->toNamespacedString(
                $this->source->getNamespace(),
                $this->source->getAliasedClassesFlipped(),
                $this->source->getFQCLN(),
                false,
            ),
            $inferred_return_type->toNamespacedString(
                $this->source->getNamespace(),
                $this->source->getAliasedClassesFlipped(),
                $this->source->getFQCLN(),
                true,
            ),
        );
    }

    /**
     * Adds return types for the given function
     *
     * @psalm-external-mutation-free
     */
    public function addReturnTypes(Context $context): void
    {
        if ($this->return_vars_in_scope !== null) {
            $this->return_vars_in_scope = TypeAnalyzer::combineKeyedTypes(
                $context->vars_in_scope,
                $this->return_vars_in_scope,
            );
        } else {
            $this->return_vars_in_scope = $context->vars_in_scope;
        }

        if ($this->return_vars_possibly_in_scope !== null) {
            $this->return_vars_possibly_in_scope = [
                ...$context->vars_possibly_in_scope,
                ...$this->return_vars_possibly_in_scope,
            ];
        } else {
            $this->return_vars_possibly_in_scope = $context->vars_possibly_in_scope;
        }
    }

    public function examineParamTypes(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        Codebase $codebase,
        ?PhpParser\Node $stmt = null,
    ): void {
        $storage = $this->getFunctionLikeStorage($statements_analyzer);

        foreach ($storage->params as $param) {
            if ($param->by_ref && isset($context->vars_in_scope['$' . $param->name]) && !$param->is_variadic) {
                $actual_type = $context->vars_in_scope['$' . $param->name];
                $param_out_type = $param->out_type ?: $param->type;

                if ($param_out_type && !$actual_type->hasMixed() && $param->location) {
                    // as the parameter's own type, e.g. with the class constants of an int-mask resolved
                    $param_out_type = TypeExpander::expandUnion(
                        $codebase,
                        $param_out_type,
                        $context->self,
                        $context->self,
                        $this->getParentFQCLN(),
                    );

                    if (!UnionTypeComparator::isContainedBy(
                        $codebase,
                        $actual_type,
                        $param_out_type,
                        $actual_type->ignore_nullable_issues,
                        $actual_type->ignore_falsable_issues,
                    )
                    ) {
                        IssueBuffer::maybeAdd(
                            new ReferenceConstraintViolation(
                                'Variable ' . '$' . $param->name . ' is limited to values of type '
                                    . $param_out_type->getId()
                                    . ' because it is passed by reference, '
                                    . $actual_type->getId() . ' type found. Use @param-out to specify '
                                    . 'a different output type',
                                $stmt
                                    ? new CodeLocation($this, $stmt)
                                    : $param->location,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }
                }
            }
        }
    }

    /**
     * @psalm-mutation-free
     */
    public function getMethodName(): ?string
    {
        if ($this->function instanceof ClassMethod) {
            return (string)$this->function->name;
        }

        return null;
    }

    public function getCorrectlyCasedMethodId(?string $context_self = null): string
    {
        if ($this->function instanceof ClassMethod) {
            $function_name = (string)$this->function->name;

            return ($context_self ?: $this->source->getFQCLN()) . '::' . $function_name;
        }

        if ($this->function instanceof Function_) {
            $namespace = $this->source->getNamespace();

            return ($namespace ? $namespace . '\\' : '') . $this->function->name;
        }

        if (!$this instanceof ClosureAnalyzer) {
            throw new UnexpectedValueException('This is weird');
        }

        return $this->getPropertyHookId() ?? $this->getClosureId();
    }

    /**
     * Whether the body may give the parameter another value than the argument: by assigning or
     * unsetting it, iterating into it, or capturing it by reference.
     */
    public function isParamReassigned(string $param_name): bool
    {
        $this->findAssignments();

        return isset($this->reassigned_params[$param_name]) || isset($this->foreach_values[$param_name]);
    }

    /**
     * What a variable of the body holds an element of, if it is only ever assigned as the value
     * variable of one foreach loop (`foreach ($fs as $f)`).
     */
    public function getForeachSource(string $var_name): ?PhpParser\Node\Expr
    {
        $this->findAssignments();

        $sources = $this->foreach_values[$var_name] ?? [];

        return count($sources) === 1 && !isset($this->reassigned_params[$var_name]) ? $sources[0] : null;
    }

    /**
     * The types of a parameter as written in the docblock (every `@param`, `@psalm-param` and
     * `@phpstan-param` of it), or its native type if it has none there.
     *
     * @return list<string>
     */
    public function getParamTypeStrings(string $param_name): array
    {
        if ($this->param_type_strings === null) {
            $this->param_type_strings = [];

            $doc_comment = $this->function->getDocComment();

            if ($doc_comment !== null) {
                $parsed_docblock = DocComment::parsePreservingLength($doc_comment);

                foreach (['psalm-param', 'phpstan-param', 'param'] as $tag) {
                    foreach ($parsed_docblock->tags[$tag] ?? [] as $param_block) {
                        try {
                            $doc_parts = CommentAnalyzer::splitDocLine($param_block);
                        } catch (DocblockParseException) {
                            continue;
                        }

                        if (isset($doc_parts[1])
                            && preg_match('/^(?:\.\.\.)?&?\$(\w+)/', $doc_parts[1], $matches)
                            && isset($matches[1])
                        ) {
                            $this->param_type_strings[$matches[1]][] = $doc_parts[0];
                        }
                    }
                }
            }

            foreach ($this->function->getParams() as $param) {
                if ($param->var instanceof PhpParser\Node\Expr\Variable
                    && is_string($param->var->name)
                    && !isset($this->param_type_strings[$param->var->name])
                    && $param->type !== null
                    && ($native_type = PurityWildcardPaths::getNativeTypeString($param->type)) !== null
                ) {
                    $this->param_type_strings[$param->var->name] = [$native_type];
                }
            }
        }

        return $this->param_type_strings[$param_name] ?? [];
    }

    private function findAssignments(): void
    {
        if ($this->reassigned_params !== null) {
            return;
        }

        $this->reassigned_params = [];
        $this->foreach_values = [];

        $finder = new NodeFinder();

        $targets = [];

        foreach ($finder->find(
            $this->function->getStmts() ?? [],
            static fn(PhpParser\Node $node): bool => $node instanceof PhpParser\Node\Expr\Assign
                || $node instanceof PhpParser\Node\Expr\AssignRef
                || $node instanceof PhpParser\Node\Expr\AssignOp
                || $node instanceof PhpParser\Node\Stmt\Foreach_
                || $node instanceof PhpParser\Node\Stmt\Unset_
                || ($node instanceof PhpParser\Node\ClosureUse && $node->byRef),
        ) as $node) {
            if ($node instanceof PhpParser\Node\Stmt\Foreach_) {
                if ($node->valueVar instanceof PhpParser\Node\Expr\Variable
                    && is_string($node->valueVar->name)
                    && !$node->byRef
                ) {
                    $this->foreach_values[$node->valueVar->name][] = $node->expr;
                } else {
                    // by reference, the loop may also change what it iterates over
                    $targets[] = $node->valueVar;

                    if ($node->byRef) {
                        $targets[] = $node->expr;
                    }
                }

                if ($node->keyVar !== null) {
                    $targets[] = $node->keyVar;
                }
            } elseif ($node instanceof PhpParser\Node\Stmt\Unset_) {
                $targets = [...$targets, ...$node->vars];
            } elseif ($node instanceof PhpParser\Node\Expr\Assign
                || $node instanceof PhpParser\Node\Expr\AssignRef
                || $node instanceof PhpParser\Node\Expr\AssignOp
                || $node instanceof PhpParser\Node\ClosureUse
            ) {
                $targets[] = $node->var;
            }
        }

        foreach ($finder->findInstanceOf($targets, PhpParser\Node\Expr\Variable::class) as $variable) {
            if (is_string($variable->name)) {
                $this->reassigned_params[$variable->name] = true;
            }
        }
    }

    /**
     * @psalm-mutation-free
     */
    public function getFunctionNode(): Closure|Function_|ClassMethod|ArrowFunction
    {
        return $this->function;
    }

    /**
     * The storage this analyzer was created with (the one of the function-like being analysed).
     *
     * @psalm-mutation-free
     */
    public function getStorage(): FunctionLikeStorage
    {
        return $this->storage;
    }

    /**
     * The node of the function-like being analysed
     *
     * @return TFunction
     * @psalm-mutation-free
     */
    public function getFunctionLikeNode(): Closure|Function_|ClassMethod|ArrowFunction
    {
        return $this->function;
    }

    /**
     * A purity made of capabilities and purity templates, where no capabilities (`pure`) are left
     * out of a union with templates: `P|pure` is just `P`.
     *
     * @param CapabilitySet $capabilities
     * @param array<string, TTemplateParam> $templates
     * @psalm-pure
     */
    private static function getClosurePurity(int $capabilities, array $templates): Union
    {
        if ($capabilities === Capabilities::NONE && $templates !== []) {
            return new Union(array_values($templates));
        }

        return new Union([new TCapabilities($capabilities), ...array_values($templates)]);
    }

    public function getFunctionLikeStorage(?StatementsAnalyzer $statements_analyzer = null): FunctionLikeStorage
    {
        $codebase = $this->codebase;

        if ($this->function instanceof ClassMethod && $this instanceof MethodAnalyzer) {
            $method_id = $this->getMethodId();
            $codebase_methods = $codebase->methods;

            try {
                return $codebase_methods->getStorage($method_id);
            } catch (UnexpectedValueException) {
                $declaring_method_id = $codebase_methods->getDeclaringMethodId($method_id);

                if ($declaring_method_id === null) {
                    throw new UnexpectedValueException('Cannot get storage for function that doesn‘t exist');
                }

                // happens for fake constructors
                return $codebase_methods->getStorage($declaring_method_id);
            }
        }

        if ($this instanceof FunctionAnalyzer) {
            $function_id = $this->getFunctionId();
        } elseif ($this instanceof ClosureAnalyzer) {
            $function_id = $this->getClosureId();
        } else {
            throw new UnexpectedValueException('This is weird');
        }

        return $codebase->functions->getStorage($statements_analyzer, $function_id);
    }

    /** @return non-empty-string */
    public function getId(): string
    {
        if ($this instanceof MethodAnalyzer) {
            return (string) $this->getMethodId();
        }

        if ($this instanceof FunctionAnalyzer) {
            return $this->getFunctionId();
        }

        if ($this instanceof ClosureAnalyzer) {
            return $this->getClosureId();
        }

        throw new UnexpectedValueException('This is weird');
    }

    /**
     * @psalm-mutation-free
     * @return array<lowercase-string, string>
     */
    #[Override]
    public function getAliasedClassesFlipped(): array
    {
        if ($this->source instanceof NamespaceAnalyzer ||
            $this->source instanceof FileAnalyzer ||
            $this->source instanceof ClassLikeAnalyzer
        ) {
            return $this->source->getAliasedClassesFlipped();
        }

        return [];
    }

    /**
     * @psalm-mutation-free
     * @return array<string, string>
     */
    #[Override]
    public function getAliasedClassesFlippedReplaceable(): array
    {
        if ($this->source instanceof NamespaceAnalyzer ||
            $this->source instanceof FileAnalyzer ||
            $this->source instanceof ClassLikeAnalyzer
        ) {
            return $this->source->getAliasedClassesFlippedReplaceable();
        }

        return [];
    }

    /**
     * The lower bounds of the class purity templates the method inherits its purity from: it
     * declares them as its own capabilities (see FunctionLikeDocblockScanner::applyPurityTemplateLowerBounds)
     * and its callers pay them anyway, so no stricter annotation can be suggested without them.
     *
     * @return CapabilitySet
     * @psalm-capabilities read-props
     */
    private function getPurityTemplateLowerBounds(Codebase $codebase, FunctionLikeStorage $storage): int
    {
        if (!$storage instanceof MethodStorage
            || $storage->defining_fqcln === null
            || $storage->purity_from_templates === []
        ) {
            return Capabilities::NONE;
        }

        $lower_bounds = $codebase->classlike_storage_provider->get($storage->defining_fqcln)->template_lower_bounds;
        $capabilities = Capabilities::NONE;

        foreach ($storage->purity_from_templates as $purity_template) {
            if (isset($lower_bounds[$purity_template]) && !isset($storage->template_types[$purity_template])) {
                $capabilities |= $lower_bounds[$purity_template];
            }
        }

        return $capabilities;
    }

    /**
     * The graph node used to resolve the mutation level of this function-like.
     *
     * @psalm-mutation-free
     */
    public function getMutationNodeId(): ?string
    {
        return CodeUseGraph::functionLikeNodeForStorage($this->storage);
    }

    /**
     * @psalm-mutation-free
     * @return array<string, array<string, Union>>|null
     */
    #[Override]
    public function getTemplateTypeMap(): ?array
    {
        if ($this->source instanceof ClassLikeAnalyzer) {
            return ($this->source->getTemplateTypeMap() ?: [])
                + ($this->storage->template_types ?: []);
        }

        return $this->storage->template_types;
    }

    #[Override]
    public function isStatic(): bool
    {
        return $this->is_static;
    }

    /**
     * @psalm-mutation-free
     */
    public function isArrowFunction(): bool
    {
        return $this->function instanceof ArrowFunction;
    }

    #[Override]
    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }

    /**
     * Get a list of suppressed issues
     *
     * @return array<string>
     */
    #[Override]
    public function getSuppressedIssues(): array
    {
        return $this->suppressed_issues;
    }

    /**
     * @param array<array-key, string> $new_issues
     * @psalm-external-mutation-free
     */
    #[Override]
    public function addSuppressedIssues(array $new_issues): void
    {
        if (isset($new_issues[0])) {
            $new_issues = array_combine($new_issues, $new_issues);
        }

        $this->suppressed_issues = $new_issues + $this->suppressed_issues;
    }

    /**
     * @param array<array-key, string> $new_issues
     * @psalm-external-mutation-free
     */
    #[Override]
    public function removeSuppressedIssues(array $new_issues): void
    {
        if (isset($new_issues[0])) {
            $new_issues = array_combine($new_issues, $new_issues);
        }

        $this->suppressed_issues = array_diff_key($this->suppressed_issues, $new_issues);
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function clearCache(): void
    {
        self::$no_effects_hashes = [];
    }

    public function getLocalReturnType(Union $storage_return_type, bool $final = false): Union
    {
        if ($this->local_return_type) {
            return $this->local_return_type;
        }

        $this->local_return_type = TypeExpander::expandUnion(
            $this->codebase,
            $storage_return_type,
            $this->getFQCLN(),
            $this->getFQCLN(),
            $this->getParentFQCLN(),
            true,
            true,
            $final,
        );

        return $this->local_return_type;
    }

    /**
     * @return array{
     *        MethodIdentifier|null,
     *        MethodIdentifier|null,
     *        ClassLikeStorage|null,
     *        ?string,
     *        ?string,
     *        array<string, MethodIdentifier>
     * }|null
     */
    private function getFunctionInformation(
        Context $context,
        Codebase $codebase,
        NodeDataProvider $type_provider,
        FunctionLikeStorage $storage,
        bool $add_mutations,
    ): ?array {
        $classlike_storage_provider = $codebase->classlike_storage_provider;
        $real_method_id = null;
        $method_id = null;

        $cased_method_id = null;
        $hash = null;
        $appearing_class_storage = null;
        $overridden_method_ids = [];

        if ($this instanceof MethodAnalyzer) {
            if (!$storage instanceof MethodStorage) {
                throw new UnexpectedValueException('$storage must be MethodStorage');
            }

            $real_method_id = $this->getMethodId();

            $method_id = $this->getMethodId($context->self);

            $fq_class_name = (string)$context->self;
            $appearing_class_storage = $classlike_storage_provider->get($fq_class_name);

            if ($add_mutations) {
                if (!$context->collect_initializations) {
                    $hash = hash('xxh3', $real_method_id . '::' . $context->getScopeSummary());

                    // if we know that the function has no effects on vars, we don't bother rechecking
                    if (isset(self::$no_effects_hashes[$hash])) {
                        return null;
                    }
                }
            } elseif ($context->self !== null) {
                if ($appearing_class_storage->template_types) {
                    $template_params = [];

                    foreach ($appearing_class_storage->template_types as $param_name => $template_map) {
                        $key = array_keys($template_map)[0];

                        $template_params[] = new Union([
                            new TTemplateParam(
                                $param_name,
                                reset($template_map),
                                $key,
                            ),
                        ]);
                    }

                    $this_object_type = new TGenericObject(
                        $context->self,
                        $template_params,
                        false,
                        !$storage->final,
                    );
                } else {
                    $this_object_type = new TNamedObject(
                        $context->self,
                        !$storage->final,
                    );
                }

                $props = [];
                if ($storage->isMutationFree()
                    && !$storage->mutation_free_assumed
                ) {
                    $props = ['reference_free' => true];
                    if ($this->function->name->name !== '__construct') {
                        $props['allow_mutations'] = false;
                    }
                }

                if ($codebase->taint_flow_graph
                    && $storage->specialize_call
                    && $storage->location
                ) {
                    $new_parent_node = DataFlowNode::getForAssignment('$this in ' . $method_id, $storage->location);

                    $codebase->taint_flow_graph->addNode($new_parent_node);
                    $props['parent_nodes'] = [$new_parent_node->id => $new_parent_node];
                }

                if ($this->storage instanceof MethodStorage && $this->storage->if_this_is_type) {
                    $if_this_is_type = TypeExpander::expandUnion(
                        $codebase,
                        $this->storage->if_this_is_type,
                        $context->self,
                        $context->self,
                        $this->getParentFQCLN(),
                    );

                    $template_result = new TemplateResult($this->getTemplateTypeMap() ?? [], []);

                    TemplateStandinTypeReplacer::fillTemplateResult(
                        new Union([$this_object_type]),
                        $template_result,
                        $codebase,
                        null,
                        $if_this_is_type,
                    );

                    foreach ($context->vars_in_scope as $var_name => &$var_type) {
                        if (0 === mb_strpos($var_name, '$this->')) {
                            $var_type = TemplateInferredTypeReplacer::replace($var_type, $template_result, $codebase);
                        }
                    }

                    $context->vars_in_scope['$this'] = $if_this_is_type->setProperties($props);
                } else {
                    $context->vars_in_scope['$this'] = new Union([$this_object_type], $props);
                }

                $context->vars_possibly_in_scope['$this'] = true;
            }

            if ($appearing_class_storage->has_visitor_issues) {
                return null;
            }

            $cased_method_id = $fq_class_name . '::' . $storage->cased_name;

            $overridden_method_ids = $codebase->methods->getOverriddenMethodIds($method_id);

            $codeLocation = new CodeLocation(
                $this,
                $this->function,
                null,
                true,
            );

            $has_override_attribute = false;
            foreach ($storage->attributes as $s) {
                if ($s->fq_class_name === 'Override') {
                    $has_override_attribute = true;
                    break;
                }
            }

            if ($has_override_attribute
                && (!$overridden_method_ids || $storage->cased_name === '__construct')
            ) {
                IssueBuffer::maybeAdd(
                    new InvalidOverride(
                        'Method ' . $storage->cased_name . ' does not match any parent method',
                        $codeLocation,
                    ),
                    $this->getSuppressedIssues(),
                );
            }

            if (!$has_override_attribute
                && $codebase->config->ensure_override_attribute
                && $overridden_method_ids
                && ($storage->defining_fqcln === null
                    || !$codebase->classlike_storage_provider->get($storage->defining_fqcln)->is_trait
                ) && $storage->cased_name !== '__construct'
                && ($storage->cased_name !== '__toString'
                    || isset($appearing_class_storage->direct_class_interfaces['stringable']))
            ) {
                IssueBuffer::maybeAdd(
                    new MissingOverrideAttribute(
                        'Method ' . $cased_method_id . ' should have the "Override" attribute',
                        $codeLocation,
                    ),
                    $this->getSuppressedIssues(),
                    true,
                );

                if ($codebase->alter_code
                    && $storage->stmt_location !== null
                    && isset($this->getProjectAnalyzer()->getIssuesToFix()['MissingOverrideAttribute'])
                ) {
                    $idx = $storage->stmt_location->getSelectionBounds()[0];
                    FileManipulationBuffer::add($storage->stmt_location->file_path, [
                        new FileManipulation($idx, $idx, "#[\\Override]\n", true),
                    ]);
                }
            }

            if ($overridden_method_ids
                && !$context->collect_initializations
            ) {
                foreach ($overridden_method_ids as $overridden_method_id) {
                    $parent_method_storage = $codebase->methods->getStorage($overridden_method_id);

                    $overridden_fq_class_name = $overridden_method_id->fq_class_name;

                    $parent_storage = $classlike_storage_provider->get($overridden_fq_class_name);

                    if ($this->function->name->name === '__construct'
                        && !$parent_storage->preserve_constructor_signature
                        && !$parent_method_storage->abstract
                    ) {
                        continue;
                    }

                    $implementer_visibility = $storage->visibility;

                    $implementer_appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);
                    $implementer_declaring_method_id = $real_method_id;

                    $declaring_class_storage = $appearing_class_storage;

                    if ($implementer_appearing_method_id
                        && $implementer_appearing_method_id !== $implementer_declaring_method_id
                    ) {
                        $appearing_fq_class_name = $implementer_appearing_method_id->fq_class_name;
                        $appearing_method_name = $implementer_appearing_method_id->method_name;

                        $declaring_fq_class_name = $implementer_declaring_method_id->fq_class_name;

                        $appearing_class_storage = $classlike_storage_provider->get(
                            $appearing_fq_class_name,
                        );

                        $declaring_class_storage = $classlike_storage_provider->get(
                            $declaring_fq_class_name,
                        );

                        if (isset($appearing_class_storage->trait_visibility_map[$appearing_method_name])) {
                            $implementer_visibility
                                = $appearing_class_storage->trait_visibility_map[$appearing_method_name];
                        }
                    }

                    // we've already checked this in the class checker
                    if (!isset($appearing_class_storage->class_implements[strtolower($overridden_fq_class_name)])) {
                        MethodComparator::compare(
                            $codebase,
                            count($overridden_method_ids) === 1 ? $this->function : null,
                            $declaring_class_storage,
                            $parent_storage,
                            $storage,
                            $parent_method_storage,
                            $fq_class_name,
                            $implementer_visibility,
                            $codeLocation,
                            $storage->suppressed_issues,
                        );
                    }
                }
            }

            MethodAnalyzer::checkMethodSignatureMustOmitReturnType($storage, $codeLocation);
            MethodAnalyzer::checkDeprecatedMagicMethodSignature($storage, $codeLocation, $this->getSuppressedIssues());
            MethodAnalyzer::checkSoftDeprecatedMagicMethod(
                $storage,
                $appearing_class_storage,
                $codeLocation,
                $this->getSuppressedIssues(),
            );

            if ($appearing_class_storage->is_enum) {
                MethodAnalyzer::checkForbiddenEnumMethod(
                    $storage,
                    $appearing_class_storage,
                    $codebase->analysis_php_version_id,
                );
            }

            if (!$context->calling_method_id || !$context->collect_initializations) {
                $context->calling_method_id = strtolower((string)$method_id);
            }
        } elseif ($this instanceof FunctionAnalyzer) {
            $function_name = $this->function->name->name;

            $name_message = PhpDeprecations::getFunctionNameMessage($function_name);

            if ($name_message !== null) {
                IssueBuffer::maybeAdd(
                    new ReservedWord($name_message, new CodeLocation($this, $this->function->name), $function_name),
                    $this->getSuppressedIssues(),
                );
            }
            $namespace_prefix = $this->getNamespace();
            $cased_method_id = ($namespace_prefix !== null ? $namespace_prefix . '\\' : '') . $function_name;
            $context->calling_function_id = strtolower($cased_method_id);
        } elseif ($this instanceof ClosureAnalyzer) {
            if ($storage->return_type) {
                $closure_return_type = TypeExpander::expandUnion(
                    $codebase,
                    $storage->return_type,
                    $context->self,
                    $context->self,
                    $this->getParentFQCLN(),
                );
            } else {
                $closure_return_type = Type::getMixed();
            }

            // `self` in the signature is the class the closure is written in (the using class, in a trait), wherever
            // the closure is called from
            $closure_params = [];

            foreach ($storage->params as $param) {
                $closure_params[] = $param->type
                    ? $param->setType(TypeExpander::expandUnion(
                        $codebase,
                        $param->type,
                        $context->self,
                        null,
                        $this->getParentFQCLN(),
                    ))
                    : $param;
            }

            $byref_uses = $storage instanceof FunctionStorage ? $storage->byref_uses : [];

            $closure_type = new TClosure(
                $closure_params,
                $closure_return_type,
                $storage->capabilities,
                $byref_uses,
                callable_id: $this->getClosureId(),
                byref_use_scopes: ClosureAnalyzer::getByRefUseScopes($this->source, $byref_uses),
            );
            $this->closure_type = $closure_type;

            $type_provider->setType(
                $this->function,
                new Union([
                    $closure_type,
                ], ['reference_free' => true]),
            );
        } else {
            throw new UnexpectedValueException('Impossible');
        }

        return [
            $real_method_id,
            $method_id,
            $appearing_class_storage,
            $hash,
            $cased_method_id,
            $overridden_method_ids,
        ];
    }

    /**
     * The type of a variable captured by reference after the closure, from its type at the end of the body and at
     * each return: the closure leaves through its returns, so where it also has no end, nothing else is possible
     *
     * @param list<Union> $types_at_return
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function combineByRefPathTypes(?Union $end_type, array $types_at_return, Codebase $codebase): Union
    {
        if ($types_at_return === []) {
            return $end_type ?? Type::getMixed();
        }

        $path_types = $end_type !== null ? [$end_type, ...$types_at_return] : $types_at_return;

        // a path on which the closure left the variable as it was (mixed in the closure) makes it mixed
        foreach ($path_types as $path_type) {
            if ($path_type->isMixed()) {
                return Type::getMixed();
            }
        }

        return Type::combineUnionTypeArray($path_types, $codebase);
    }

    /**
     * @return array<int,CodeLocation>
     */
    private function detectUnusedParameters(
        StatementsAnalyzer $statements_analyzer,
        FunctionLikeStorage $storage,
        Context $context,
    ): array {
        $codebase = $statements_analyzer->getCodebase();

        $unused_params = [];

        foreach ($statements_analyzer->getUnusedVarLocations() as [$var_name, $original_location]) {
            if (!array_key_exists(substr($var_name, 1), $storage->param_lookup)) {
                continue;
            }

            if ($this->isIgnoredForUnusedParam($var_name)) {
                continue;
            }

            $position = array_search(substr($var_name, 1), array_keys($storage->param_lookup), true);

            if ($position === false) {
                throw new UnexpectedValueException('$position should not be false here');
            }

            if ($storage->params[$position]->promoted_property) {
                continue;
            }

            $did_match_param = false;

            foreach ($this->function->params as $param) {
                if ($param->var->getAttribute('endFilePos') === $original_location->raw_file_end) {
                    $did_match_param = true;
                    break;
                }
            }

            if (!$did_match_param) {
                continue;
            }

            $assignment_node = DataFlowNode::getForAssignment($var_name, $original_location);

            if ($statements_analyzer->variable_use_graph?->isVariableUsed($assignment_node)) {
                continue;
            }

            if (!$storage instanceof MethodStorage
                || !$storage->cased_name
                || $storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
            ) {
                $unused_params[$position] = $original_location;
                continue;
            }

            $fq_class_name = (string)$context->self;

            $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

            $method_name_lc = strtolower($storage->cased_name);

            // PHP requires these magic methods to take exactly the parameters they declare
            if ($storage->abstract || in_array($method_name_lc, self::MAGIC_METHODS_WITH_REQUIRED_PARAMS, true)) {
                continue;
            }

            if (isset($class_storage->overridden_method_ids[$method_name_lc])) {
                $parent_method_id = end($class_storage->overridden_method_ids[$method_name_lc]);

                if ($parent_method_id) {
                    $parent_method_storage = $codebase->methods->getStorage($parent_method_id);

                    // if the parent method has a param at that position and isn't abstract
                    if (!$parent_method_storage->abstract
                        && isset($parent_method_storage->params[$position])
                    ) {
                        continue;
                    }
                }
            }

            $unused_params[$position] = $original_location;
        }

        return $unused_params;
    }

    /**
     * @psalm-mutation-free
     */
    private function detectPreviousUnusedArgumentPosition(FunctionLikeStorage $function, int $position): int
    {
        $params = $function->params;
        krsort($params, SORT_NUMERIC);

        foreach ($params as $index => $param) {
            if ($index > $position) {
                continue;
            }

            if ($this->isIgnoredForUnusedParam($param->name)) {
                continue;
            }

            return $index;
        }

        return 0;
    }

    /**
     * @psalm-pure
     */
    private function isIgnoredForUnusedParam(string $var_name): bool
    {
        return str_starts_with($var_name, '$_') || (str_starts_with($var_name, '$unused') && $var_name !== '$unused');
    }
}
