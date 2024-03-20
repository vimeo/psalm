<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallReturnTypeFetcher;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodVisibilityAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\AbstractInstantiation;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InterfaceInstantiation;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InternalMethod;
use Psalm\Issue\InvalidStringClass;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\ParseError;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UnsafeGenericInstantiation;
use Psalm\Issue\UnsafeInstantiation;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Storage\Possibilities;
use Psalm\Type;
use Psalm\Type\Atomic\TAnonymousClassInstance;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TDependentGetClass;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TUnknownClassString;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;

use function array_map;
use function array_values;
use function count;
use function in_array;
use function md5;
use function preg_match;
use function reset;
use function strtolower;

/**
 * @internal
 */
final class NewAnalyzer extends CallAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\New_ $stmt,
        Context $context,
        ?TemplateResult $template_result = null
    ): bool {
        $fq_class_name = null;

        $codebase = $statements_analyzer->getCodebase();
        $config = $codebase->config;

        $can_extend = false;

        $from_static = false;

        if ($stmt->isFirstClassCallable()) {
            IssueBuffer::maybeAdd(new ParseError(
                'First-class callables cannot be used in new',
                new CodeLocation($statements_analyzer->getSource(), $stmt),
            ));
            return false;
        }

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array(strtolower($stmt->class->getFirst()), ['self', 'static', 'parent'], true)) {
                $aliases = $statements_analyzer->getAliases();

                if ($context->calling_method_id
                    && !$stmt->class instanceof PhpParser\Node\Name\FullyQualified
                ) {
                    $codebase->file_reference_provider->addMethodReferenceToClassMember(
                        $context->calling_method_id,
                        'use:' . $stmt->class->getFirst() . ':' . md5($statements_analyzer->getFilePath()),
                        false,
                    );
                }

                $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $stmt->class,
                    $aliases,
                );

                $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);
            } elseif ($context->self !== null) {
                switch ($stmt->class->getFirst()) {
                    case 'self':
                        $class_storage = $codebase->classlike_storage_provider->get($context->self);
                        $fq_class_name = $class_storage->name;
                        break;

                    case 'parent':
                        $fq_class_name = $context->parent;
                        break;

                    case 'static':
                        // @todo maybe we can do better here
                        $class_storage = $codebase->classlike_storage_provider->get($context->self);
                        $fq_class_name = $class_storage->name;

                        if (!$class_storage->final) {
                            $can_extend = true;
                            $from_static = true;
                        }

                        break;
                }
            }

            if ($codebase->store_node_types
                && $fq_class_name
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->class,
                    $codebase->classlikes->classExists($fq_class_name)
                        ? $fq_class_name
                        : '*'
                            . ($stmt->class instanceof PhpParser\Node\Name\FullyQualified
                                ? '\\'
                                : $statements_analyzer->getNamespace() . '-')
                            . $stmt->class->toString(),
                );
            }
        } elseif ($stmt->class instanceof PhpParser\Node\Stmt\Class_) {
            $statements_analyzer->analyze([$stmt->class], $context);
            $fq_class_name = ClassAnalyzer::getAnonymousClassName(
                $stmt->class,
                $statements_analyzer->getAliases(),
                $statements_analyzer->getFilePath(),
            );
        } else {
            self::analyzeConstructorExpression(
                $statements_analyzer,
                $codebase,
                $context,
                $stmt,
                $stmt->class,
                $config,
                $fq_class_name,
                $can_extend,
            );
        }

        if ($fq_class_name) {
            if ($codebase->alter_code
                && $stmt->class instanceof PhpParser\Node\Name
                && !in_array($stmt->class->getFirst(), ['parent', 'static'])
            ) {
                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context->calling_method_id,
                );
            }

            if ($context->check_classes) {
                if ($context->isPhantomClass($fq_class_name)) {
                    ArgumentsAnalyzer::analyze(
                        $statements_analyzer,
                        $stmt->getArgs(),
                        null,
                        null,
                        true,
                        $context,
                    );

                    return true;
                }

                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $fq_class_name,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                    $context->self,
                    $context->calling_method_id,
                    $statements_analyzer->getSuppressedIssues(),
                ) === false) {
                    ArgumentsAnalyzer::analyze(
                        $statements_analyzer,
                        $stmt->getArgs(),
                        null,
                        null,
                        true,
                        $context,
                    );

                    return true;
                }

                if ($codebase->interfaceExists($fq_class_name)) {
                    IssueBuffer::maybeAdd(
                        new InterfaceInstantiation(
                            'Interface ' . $fq_class_name . ' cannot be instantiated',
                            new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                    return true;
                }
            }

            if ($stmt->class instanceof PhpParser\Node\Stmt\Class_) {
                $extends = $stmt->class->extends ? (string) $stmt->class->extends : null;
                $result_atomic_type = new TAnonymousClassInstance($fq_class_name, false, $extends);
            } else {
                //if the class is a Name, it can't represent a child
                $definite_class = $stmt->class instanceof PhpParser\Node\Name;
                $result_atomic_type = new TNamedObject($fq_class_name, $from_static, $definite_class);
            }

            $statements_analyzer->node_data->setType(
                $stmt,
                new Union([$result_atomic_type]),
            );

            if (strtolower($fq_class_name) === 'stdclass' && $stmt->getArgs() !== []) {
                IssueBuffer::maybeAdd(
                    new TooManyArguments(
                        'stdClass::__construct() has no parameters',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        'stdClass::__construct',
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            if (strtolower($fq_class_name) !== 'stdclass' &&
                $codebase->classlikes->classExists($fq_class_name)
            ) {
                self::analyzeNamedConstructor(
                    $statements_analyzer,
                    $codebase,
                    $stmt,
                    $context,
                    $fq_class_name,
                    $from_static,
                    $can_extend,
                    $template_result,
                );
            } else {
                ArgumentsAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->getArgs(),
                    null,
                    null,
                    true,
                    $context,
                );

                if ($codebase->classlikes->enumExists($fq_class_name)) {
                    IssueBuffer::maybeAdd(new UndefinedClass(
                        'Enums cannot be instantiated',
                        new CodeLocation($statements_analyzer, $stmt),
                        $fq_class_name,
                    ));
                }
            }
        }

        if (!$config->remember_property_assignments_after_call && !$context->collect_initializations) {
            $context->removeMutableObjectVars();
        }

        return true;
    }

    private static function analyzeNamedConstructor(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\New_ $stmt,
        Context $context,
        string $fq_class_name,
        bool $from_static,
        bool $can_extend,
        ?TemplateResult $template_result = null
    ): void {
        $storage = $codebase->classlike_storage_provider->get($fq_class_name);

        if ($from_static) {
            if (!$storage->preserve_constructor_signature) {
                IssueBuffer::maybeAdd(
                    new UnsafeInstantiation(
                        'Cannot safely instantiate class ' . $fq_class_name . ' with "new static" as'
                        . ' its constructor might change in child classes',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } elseif ($storage->template_types
                && !$storage->enforce_template_inheritance
            ) {
                $source = $statements_analyzer->getSource();

                if ($source instanceof FunctionLikeAnalyzer) {
                    $function_storage = $source->getFunctionLikeStorage($statements_analyzer);

                    if ($function_storage->return_type
                        && preg_match('/\bstatic\b/', $function_storage->return_type->getId())
                    ) {
                        IssueBuffer::maybeAdd(
                            new UnsafeGenericInstantiation(
                                'Cannot safely instantiate generic class ' . $fq_class_name
                                    . ' with "new static" as'
                                    . ' its generic parameters may be constrained in child classes.',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }
                }
            }
        }

        // if we're not calling this constructor via new static()
        if ($storage->abstract && !$can_extend) {
            if (IssueBuffer::accepts(
                new AbstractInstantiation(
                    'Unable to instantiate a abstract class ' . $fq_class_name,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            )) {
                return;
            }
        }

        if ($storage->deprecated && strtolower($fq_class_name) !== strtolower((string)$context->self)) {
            IssueBuffer::maybeAdd(
                new DeprecatedClass(
                    $fq_class_name . ' is marked deprecated',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $fq_class_name,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }


        if ($context->self
            && !$context->collect_initializations
            && !$context->collect_mutations
            && !NamespaceAnalyzer::isWithinAny($context->self, $storage->internal)
        ) {
            IssueBuffer::maybeAdd(
                new InternalClass(
                    $fq_class_name . ' is internal to ' . InternalClass::listToPhrase($storage->internal)
                        . ' but called from ' . $context->self,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $fq_class_name,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        $method_id = new MethodIdentifier($fq_class_name, '__construct');

        if ($codebase->methods->methodExists(
            $method_id,
            $context->calling_method_id,
            $codebase->collect_locations ? new CodeLocation($statements_analyzer->getSource(), $stmt) : null,
            $statements_analyzer,
            $statements_analyzer->getFilePath(),
        )) {
            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                ArgumentMapPopulator::recordArgumentPositions(
                    $statements_analyzer,
                    $stmt,
                    $codebase,
                    (string)$method_id,
                );
            }

            $template_result ??= new TemplateResult([], []);

            if (self::checkMethodArgs(
                $method_id,
                $stmt->getArgs(),
                $template_result,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer,
            ) === false) {
                return;
            }

            if (MethodVisibilityAnalyzer::analyze(
                $method_id,
                $context,
                $statements_analyzer->getSource(),
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer->getSuppressedIssues(),
            ) === false) {
                return;
            }

            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            $method_storage = null;

            if ($declaring_method_id) {
                $method_storage = $codebase->methods->getStorage($declaring_method_id);

                $caller_identifier = $statements_analyzer->getFullyQualifiedFunctionMethodOrNamespaceName() ?: '';
                if (!NamespaceAnalyzer::isWithinAny($caller_identifier, $method_storage->internal)) {
                    IssueBuffer::maybeAdd(
                        new InternalMethod(
                            'Constructor ' . $codebase->methods->getCasedMethodId($declaring_method_id)
                                . ' is internal to ' . InternalClass::listToPhrase($method_storage->internal)
                                . ' but called from ' . ($caller_identifier ?: 'root namespace'),
                            new CodeLocation($statements_analyzer, $stmt),
                            (string) $method_id,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                if (!$method_storage->external_mutation_free && !$context->inside_throw) {
                    if ($context->pure) {
                        IssueBuffer::maybeAdd(
                            new ImpureMethodCall(
                                'Cannot call an impure constructor from a pure context',
                                new CodeLocation($statements_analyzer, $stmt),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    } elseif ($statements_analyzer->getSource()
                        instanceof FunctionLikeAnalyzer
                        && $statements_analyzer->getSource()->track_mutations
                    ) {
                        $statements_analyzer->getSource()->inferred_has_mutation = true;
                        $statements_analyzer->getSource()->inferred_impure = true;
                    }
                }

                if ($method_storage->assertions && $stmt->class instanceof PhpParser\Node\Name) {
                    self::applyAssertionsToContext(
                        $stmt->class,
                        null,
                        $method_storage->assertions,
                        $stmt->getArgs(),
                        $template_result,
                        $context,
                        $statements_analyzer,
                    );
                }

                if ($method_storage->if_true_assertions) {
                    $statements_analyzer->node_data->setIfTrueAssertions(
                        $stmt,
                        array_map(
                            static fn(Possibilities $assertion): Possibilities
                                => $assertion->getUntemplatedCopy($template_result, null, $codebase),
                            $method_storage->if_true_assertions,
                        ),
                    );
                }

                if ($method_storage->if_false_assertions) {
                    $statements_analyzer->node_data->setIfFalseAssertions(
                        $stmt,
                        array_map(
                            static fn(Possibilities $assertion): Possibilities
                                => $assertion->getUntemplatedCopy($template_result, null, $codebase),
                            $method_storage->if_false_assertions,
                        ),
                    );
                }
            }

            $generic_param_types = null;
            $self_out_candidate = null;

            if ($storage->template_types) {
                foreach ($storage->template_types as $template_name => $base_type) {
                    if (isset($template_result->lower_bounds[$template_name][$fq_class_name])) {
                        $generic_param_type = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                            $template_result->lower_bounds[$template_name][$fq_class_name],
                            $codebase,
                        );
                    } elseif ($storage->template_extended_params && $template_result->lower_bounds) {
                        $generic_param_type = self::getGenericParamForOffset(
                            $fq_class_name,
                            $template_name,
                            $storage->template_extended_params,
                            array_map(
                                static fn(array $type_map): array => array_map(
                                    static fn(array $bounds): Union
                                        => TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                                            $bounds,
                                            $codebase,
                                        ),
                                    $type_map,
                                ),
                                $template_result->lower_bounds,
                            ),
                        );
                    } else {
                        if ($fq_class_name === 'SplObjectStorage') {
                            $generic_param_type = Type::getNever();
                        } else {
                            $generic_param_type = array_values($base_type)[0];
                        }
                    }

                    $generic_param_types[] = $generic_param_type->setProperties([
                        'had_template' => true,
                    ]);
                }

                if ($method_storage && $method_storage->self_out_type) {
                    $self_out_candidate = $method_storage->self_out_type;

                    if ($template_result->lower_bounds) {
                        $self_out_candidate = TypeExpander::expandUnion(
                            $codebase,
                            $self_out_candidate,
                            $fq_class_name,
                            null,
                            $storage->parent_class,
                            true,
                            false,
                            false,
                            true,
                        );
                    }

                    $self_out_candidate = MethodCallReturnTypeFetcher::replaceTemplateTypes(
                        $self_out_candidate,
                        $template_result,
                        $method_id,
                        count($stmt->getArgs()),
                        $codebase,
                    );

                    $self_out_candidate = TypeExpander::expandUnion(
                        $codebase,
                        $self_out_candidate,
                        $fq_class_name,
                        $fq_class_name,
                        $storage->parent_class,
                        true,
                        false,
                        false,
                        true,
                    );
                    $statements_analyzer->node_data->setType($stmt, $self_out_candidate);
                }
            }

            // XXX: what if we need both?
            if ($generic_param_types && !$self_out_candidate) {
                $result_atomic_type = new TGenericObject(
                    $fq_class_name,
                    $generic_param_types,
                    false,
                    $from_static,
                );

                $statements_analyzer->node_data->setType(
                    $stmt,
                    new Union([$result_atomic_type]),
                );
            }
        } elseif ($stmt->getArgs()) {
            IssueBuffer::maybeAdd(
                new TooManyArguments(
                    'Class ' . $fq_class_name . ' has no __construct, but arguments were passed',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $fq_class_name . '::__construct',
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        } elseif ($storage->template_types) {
            $result_atomic_type = new TGenericObject(
                $fq_class_name,
                array_values(
                    array_map(
                        static fn($map) => reset($map),
                        $storage->template_types,
                    ),
                ),
                false,
                $from_static,
            );

            $statements_analyzer->node_data->setType(
                $stmt,
                new Union([$result_atomic_type]),
            );
        }

        if ($storage->external_mutation_free) {
            $stmt->setAttribute('external_mutation_free', true);
            $stmt_type = $statements_analyzer->node_data->getType($stmt);

            if ($stmt_type) {
                $stmt_type = $stmt_type->setProperties([
                    'reference_free' => true,
                ]);
                $statements_analyzer->node_data->setType($stmt, $stmt_type);
            }
        }

        if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
            && !in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
            && ($stmt_type = $statements_analyzer->node_data->getType($stmt))
        ) {
            $code_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

            $method_storage = null;

            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if ($declaring_method_id) {
                $method_storage = $codebase->methods->getStorage($declaring_method_id);
            }

            if ($storage->external_mutation_free
                || ($method_storage && $method_storage->specialize_call)
            ) {
                $method_source = DataFlowNode::getForMethodReturn(
                    (string)$method_id,
                    $fq_class_name . '::__construct',
                    $storage->location,
                    $code_location,
                );
            } else {
                $method_source = DataFlowNode::getForMethodReturn(
                    (string)$method_id,
                    $fq_class_name . '::__construct',
                    $storage->location,
                );
            }

            $statements_analyzer->data_flow_graph->addNode($method_source);

            $stmt_type = $stmt_type->setParentNodes([$method_source->id => $method_source]);
            $statements_analyzer->node_data->setType($stmt, $stmt_type);
        }
    }

    private static function analyzeConstructorExpression(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        Context $context,
        PhpParser\Node\Expr\New_ $stmt,
        PhpParser\Node\Expr $stmt_class,
        Config $config,
        ?string &$fq_class_name,
        bool &$can_extend
    ): void {
        $was_inside_general_use = $context->inside_general_use;
        $context->inside_general_use = true;
        ExpressionAnalyzer::analyze($statements_analyzer, $stmt_class, $context);
        $context->inside_general_use = $was_inside_general_use;

        $stmt_class_type = $statements_analyzer->node_data->getType($stmt_class);

        if (!$stmt_class_type) {
            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                null,
                true,
                $context,
            );

            return;
        }

        $has_single_class = $stmt_class_type->isSingleStringLiteral();

        if ($has_single_class) {
            $fq_class_name = $stmt_class_type->getSingleStringLiteral()->value;
        } else {
            if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                && $stmt_class_type->parent_nodes
                && !in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
            ) {
                $arg_location = new CodeLocation($statements_analyzer->getSource(), $stmt_class);

                $custom_call_sink = TaintSink::getForMethodArgument(
                    'variable-call',
                    'variable-call',
                    0,
                    $arg_location,
                    $arg_location,
                );

                $custom_call_sink->taints = [TaintKind::INPUT_CALLABLE];

                $statements_analyzer->data_flow_graph->addSink($custom_call_sink);

                $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

                $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
                $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

                foreach ($stmt_class_type->parent_nodes as $parent_node) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        $custom_call_sink,
                        'call',
                        $added_taints,
                        $removed_taints,
                    );
                }
            }

            if (self::checkMethodArgs(
                null,
                $stmt->getArgs(),
                new TemplateResult([], []),
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer,
            ) === false) {
                return;
            }
        }

        $new_type = self::getNewType(
            $statements_analyzer,
            $codebase,
            $context,
            $stmt,
            $stmt_class_type,
            $config,
            $can_extend,
        );

        if (!$has_single_class) {
            if ($new_type) {
                $statements_analyzer->node_data->setType($stmt, $new_type);
            }

            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                null,
                true,
                $context,
            );

            return;
        }
    }
    private static function getNewType(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        Context $context,
        PhpParser\Node\Expr\New_ $stmt,
        Union $stmt_class_type,
        Config $config,
        bool &$can_extend
    ): ?Union {
        $new_types = [];

        foreach ($stmt_class_type->getAtomicTypes() as $lhs_type_part) {
            if ($lhs_type_part instanceof TTemplateParam) {
                $as = self::getNewType(
                    $statements_analyzer,
                    $codebase,
                    $context,
                    $stmt,
                    $lhs_type_part->as,
                    $config,
                    $can_extend,
                );
                if ($as) {
                    $new_types []= new Union([$lhs_type_part->replaceAs($as)]);
                }
                continue;
            }

            if ($lhs_type_part instanceof TTemplateParamClass) {
                if (!$statements_analyzer->node_data->getType($stmt)) {
                    $new_type_part = new TTemplateParam(
                        $lhs_type_part->param_name,
                        $lhs_type_part->as_type
                            ? new Union([$lhs_type_part->as_type])
                            : Type::getObject(),
                        $lhs_type_part->defining_class,
                    );

                    if (!$lhs_type_part->as_type) {
                        IssueBuffer::maybeAdd(
                            new MixedMethodCall(
                                'Cannot call constructor on an unknown class',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }

                    $new_types []= new Union([$new_type_part]);

                    if ($lhs_type_part->as_type
                        && $codebase->classlikes->classExists($lhs_type_part->as_type->value)
                    ) {
                        $as_storage = $codebase->classlike_storage_provider->get(
                            $lhs_type_part->as_type->value,
                        );

                        if (!$as_storage->preserve_constructor_signature) {
                            IssueBuffer::maybeAdd(
                                new UnsafeInstantiation(
                                    'Cannot safely instantiate class ' . $lhs_type_part->as_type->value
                                    . ' with "new $class_name" as'
                                    . ' its constructor might change in child classes',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                ),
                                $statements_analyzer->getSuppressedIssues(),
                            );
                        }
                    }
                }

                if ($lhs_type_part->as_type) {
                    $codebase->methods->methodExists(
                        new MethodIdentifier(
                            $lhs_type_part->as_type->value,
                            '__construct',
                        ),
                        $context->calling_method_id,
                        $codebase->collect_locations
                            ? new CodeLocation($statements_analyzer->getSource(), $stmt) : null,
                        $statements_analyzer,
                        $statements_analyzer->getFilePath(),
                    );
                }

                continue;
            }

            if ($lhs_type_part instanceof TLiteralClassString
                || $lhs_type_part instanceof TClassString
                || $lhs_type_part instanceof TDependentGetClass
            ) {
                if (!$statements_analyzer->node_data->getType($stmt)) {
                    if ($lhs_type_part instanceof TClassString) {
                        $generated_type = $lhs_type_part->as_type ?? new TObject();
                        if ($lhs_type_part instanceof TUnknownClassString) {
                            $generated_type = $lhs_type_part->as_unknown_type ?? $generated_type;
                        }

                        if ($lhs_type_part->as_type
                            && $codebase->classlikes->classExists($lhs_type_part->as_type->value)
                        ) {
                            $as_storage = $codebase->classlike_storage_provider->get(
                                $lhs_type_part->as_type->value,
                            );

                            if (!$as_storage->preserve_constructor_signature) {
                                IssueBuffer::maybeAdd(
                                    new UnsafeInstantiation(
                                        'Cannot safely instantiate class ' . $lhs_type_part->as_type->value
                                        . ' with "new $class_name" as'
                                        . ' its constructor might change in child classes',
                                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    ),
                                    $statements_analyzer->getSuppressedIssues(),
                                );
                            }
                        }
                    } elseif ($lhs_type_part instanceof TDependentGetClass) {
                        $generated_type = new TObject();

                        if ($lhs_type_part->as_type->hasObjectType()
                            && $lhs_type_part->as_type->isSingle()
                        ) {
                            foreach ($lhs_type_part->as_type->getAtomicTypes() as $typeof_type_atomic) {
                                if ($typeof_type_atomic instanceof TNamedObject) {
                                    $generated_type = new TNamedObject(
                                        $typeof_type_atomic->value,
                                    );
                                }
                            }
                        }
                    } else {
                        $generated_type = new TNamedObject(
                            $lhs_type_part->value,
                        );
                    }

                    if ($lhs_type_part instanceof TClassString) {
                        $can_extend = true;
                    }

                    if ($generated_type instanceof TObject) {
                        IssueBuffer::maybeAdd(
                            new MixedMethodCall(
                                'Cannot call constructor on an unknown class',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }

                    $new_types []= new Union([$generated_type]);
                }

                continue;
            }

            if ($lhs_type_part instanceof TString) {
                if (!$config->allow_string_standin_for_class
                    || $lhs_type_part instanceof TNumericString
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidStringClass(
                            'String cannot be used as a class',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            } elseif ($lhs_type_part instanceof TMixed
                || $lhs_type_part instanceof TObject
            ) {
                IssueBuffer::maybeAdd(
                    new MixedMethodCall(
                        'Cannot call constructor on an unknown class',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } elseif ($lhs_type_part instanceof TFalse
                && $stmt_class_type->ignore_falsable_issues
            ) {
                // do nothing
            } elseif ($lhs_type_part instanceof TNull
                && $stmt_class_type->ignore_nullable_issues
            ) {
                // do nothing
            } elseif ($lhs_type_part instanceof TNamedObject) {
                $new_types []= new Union([$lhs_type_part]);
                continue;
            } else {
                IssueBuffer::maybeAdd(
                    new UndefinedClass(
                        'Type ' . $lhs_type_part . ' cannot be called as a class',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        (string)$lhs_type_part,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            $new_types []= Type::getObject();
        }

        if ($new_types) {
            return Type::combineUnionTypeArray($new_types, $codebase);
        }
        return null;
    }
}
