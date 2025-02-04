<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\Statements\Expression\Call\StaticMethod\AtomicStaticCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\TaintSource;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\NonStaticSelfCall;
use Psalm\Issue\ParentNotFound;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function array_merge;
use function count;
use function in_array;
use function md5;
use function strtolower;

/**
 * @internal
 */
final class StaticCallAnalyzer extends CallAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        Context $context,
        ?TemplateResult $template_result = null,
    ): bool {
        $method_id = null;

        $lhs_type = null;

        $codebase = $statements_analyzer->getCodebase();
        $source = $statements_analyzer->getSource();

        $config = $codebase->config;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (count($stmt->class->getParts()) === 1
                && in_array(strtolower($stmt->class->getFirst()), ['self', 'static', 'parent'], true)
            ) {
                if ($stmt->class->getFirst() === 'parent') {
                    $child_fq_class_name = $context->self;

                    $class_storage = $child_fq_class_name
                        ? $codebase->classlike_storage_provider->get($child_fq_class_name)
                        : null;

                    if (!$class_storage || !$class_storage->parent_class) {
                        return !IssueBuffer::accepts(
                            new ParentNotFound(
                                'Cannot call method on parent as this class does not extend another',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }

                    $fq_class_name = $class_storage->parent_class;

                    $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);

                    $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                    $fq_class_name = $class_storage->name;

                    if ($context->collect_initializations
                        && isset($stmt->name->name)
                        && $stmt->name->name === '__construct'
                        && isset($class_storage->declaring_method_ids['__construct'])) {
                        $construct_fq_class_name = $class_storage->declaring_method_ids['__construct']->fq_class_name;
                        $construct_class_storage = $codebase->classlike_storage_provider->get($construct_fq_class_name);
                        $construct_fq_class_name = $construct_class_storage->name;

                        foreach ($construct_class_storage->properties as $property_name => $property_storage) {
                            if ($property_storage->is_promoted
                                && isset($context->vars_in_scope['$this->' . $property_name])) {
                                $context_type = $context->vars_in_scope['$this->' . $property_name];
                                $context->vars_in_scope['$this->' . $property_name] = $context_type->setProperties(
                                    [
                                        'initialized_class' => $construct_fq_class_name,
                                        'initialized' => true,
                                    ],
                                );
                            }
                        }
                    }
                } elseif ($context->self) {
                    if ($stmt->class->getFirst() === 'static' && isset($context->vars_in_scope['$this'])) {
                        $fq_class_name = (string) $context->vars_in_scope['$this'];
                        $lhs_type = $context->vars_in_scope['$this'];
                    } else {
                        $fq_class_name = $context->self;
                    }
                } else {
                    return !IssueBuffer::accepts(
                        new NonStaticSelfCall(
                            'Cannot use ' . $stmt->class->getFirst() . ' outside class context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                if ($context->isPhantomClass($fq_class_name)) {
                    return true;
                }
            } else {
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

                if ($context->isPhantomClass($fq_class_name)) {
                    return true;
                }

                $does_class_exist = false;

                if ($context->self) {
                    $self_storage = $codebase->classlike_storage_provider->get($context->self);

                    if (isset($self_storage->used_traits[strtolower($fq_class_name)])) {
                        $fq_class_name = $context->self;
                        $does_class_exist = true;
                    }
                }

                if (!isset($context->phantom_classes[strtolower($fq_class_name)])
                    && !$does_class_exist
                ) {
                    $does_class_exist = ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $fq_class_name,
                        new CodeLocation($source, $stmt->class),
                        !$context->collect_initializations
                            && !$context->collect_mutations
                            ? $context->self
                            : null,
                        !$context->collect_initializations
                            && !$context->collect_mutations
                            ? $context->calling_method_id
                            : null,
                        $statements_analyzer->getSuppressedIssues(),
                        new ClassLikeNameOptions(false, false, false, true),
                        $context->check_classes,
                    );
                }

                if (!$does_class_exist) {
                    return $does_class_exist !== false;
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
                    $fq_class_name,
                );
            }

            if ($fq_class_name && !$lhs_type) {
                $lhs_type = new Union([new TNamedObject($fq_class_name)]);
            }
        } else {
            $was_inside_general_use = $context->inside_general_use;
            $context->inside_general_use = true;
            ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context);
            $context->inside_general_use = $was_inside_general_use;
            $lhs_type = $statements_analyzer->node_data->getType($stmt->class) ?? Type::getMixed();
        }

        if (!$lhs_type) {
            if (ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                null,
                true,
                $context,
            ) === false) {
                return false;
            }

            return true;
        }

        $has_mock = false;
        $moved_call = false;
        $has_existing_method = false;

        foreach ($lhs_type->getAtomicTypes() as $lhs_type_part) {
            AtomicStaticCallAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $lhs_type_part,
                $lhs_type->ignore_nullable_issues,
                $moved_call,
                $has_mock,
                $has_existing_method,
                $template_result,
            );
        }

        if (!$stmt->isFirstClassCallable() && !$has_existing_method) {
            return self::checkMethodArgs(
                $method_id,
                $stmt->getArgs(),
                new TemplateResult([], []),
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer,
            );
        }

        if (!$config->remember_property_assignments_after_call && !$context->collect_initializations) {
            $context->removeMutableObjectVars();
        }

        if (!$statements_analyzer->node_data->getType($stmt)) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        }

        return true;
    }

    public static function taintReturnType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        MethodIdentifier $method_id,
        string $cased_method_id,
        Union &$return_type_candidate,
        ?MethodStorage $method_storage,
        ?TemplateResult $template_result,
        ?Context $context = null,
    ): void {
        if (!$statements_analyzer->data_flow_graph) {
            return;
        }

        if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
            && in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
        ) {
            return;
        }

        $node_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        $method_location = $method_storage
            ? ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                ? ($method_storage->signature_return_type_location ?: $method_storage->location)
                : ($method_storage->return_type_location ?: $method_storage->location))
            : null;

        if ($method_storage && $method_storage->specialize_call) {
            $method_source = DataFlowNode::getForMethodReturn(
                (string) $method_id,
                $cased_method_id,
                $method_location,
                $node_location,
            );
        } else {
            $method_source = DataFlowNode::getForMethodReturn(
                (string) $method_id,
                $cased_method_id,
                $method_location,
            );
        }

        $statements_analyzer->data_flow_graph->addNode($method_source);

        $codebase = $statements_analyzer->getCodebase();

        $conditionally_removed_taints = [];

        if ($method_storage && $template_result) {
            foreach ($method_storage->conditionally_removed_taints as $conditionally_removed_taint) {
                $conditionally_removed_taint = TemplateInferredTypeReplacer::replace(
                    $conditionally_removed_taint,
                    $template_result,
                    $codebase,
                );

                $expanded_type = TypeExpander::expandUnion(
                    $statements_analyzer->getCodebase(),
                    $conditionally_removed_taint,
                    null,
                    null,
                    null,
                    true,
                    true,
                );

                foreach ($expanded_type->getLiteralStrings() as $literal_string) {
                    $conditionally_removed_taints[] = $literal_string->value;
                }
            }
        }

        $added_taints = [];
        $removed_taints = [];

        if ($context) {
            $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

            $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
            $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);
        }

        if ($conditionally_removed_taints && $method_location) {
            $assignment_node = DataFlowNode::getForAssignment(
                $method_id . '-escaped',
                $method_location,
                $method_source->specialization_key,
            );

            $statements_analyzer->data_flow_graph->addPath(
                $method_source,
                $assignment_node,
                'conditionally-escaped',
                $added_taints,
                [...$conditionally_removed_taints, ...$removed_taints],
            );

            $return_type_candidate = $return_type_candidate->addParentNodes([$assignment_node->id => $assignment_node]);
        } else {
            $return_type_candidate = $return_type_candidate->setParentNodes([$method_source->id => $method_source]);
        }

        if ($method_storage
            && $method_storage->taint_source_types
            && $statements_analyzer->data_flow_graph instanceof TaintFlowGraph
        ) {
            $method_node = TaintSource::getForMethodReturn(
                (string) $method_id,
                $cased_method_id,
                $method_storage->signature_return_type_location ?: $method_storage->location,
            );

            $method_node->taints = $method_storage->taint_source_types;

            $statements_analyzer->data_flow_graph->addSource($method_node);
        }

        if ($method_storage && $statements_analyzer->data_flow_graph instanceof TaintFlowGraph) {
            FunctionCallReturnTypeFetcher::taintUsingFlows(
                $statements_analyzer,
                $method_storage,
                $statements_analyzer->data_flow_graph,
                (string) $method_id,
                $stmt->getArgs(),
                $node_location,
                $method_source,
                array_merge($method_storage->removed_taints, $removed_taints),
                $added_taints,
            );
        }
    }
}
