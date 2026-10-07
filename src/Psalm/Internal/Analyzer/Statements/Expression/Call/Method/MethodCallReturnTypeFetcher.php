<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use Exception;
use PDOException;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallReturnTypeFetcher;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Internal\Type\TypeVariableTracker;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

use function count;

/**
 * @internal
 */
final class MethodCallReturnTypeFetcher
{
    /**
     * @param  TNamedObject|TTemplateParam|null  $static_type
     * @param list<PhpParser\Node\Arg> $args
     */
    public static function fetch(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        MethodIdentifier $method_id,
        ?MethodIdentifier $declaring_method_id,
        MethodIdentifier $premixin_method_id,
        string $cased_method_id,
        Atomic $lhs_type_part,
        ?Atomic $static_type,
        array $args,
        AtomicMethodCallAnalysisResult $result,
        TemplateResult $template_result,
    ): Union {
        $call_map_id = $declaring_method_id ?? $method_id;

        $fq_class_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;

        $class_storage = $codebase->methods->getClassLikeStorageForMethod($method_id);
        $method_storage = ($class_storage->methods[$method_id->method_name] ?? null);

        if ($stmt->isFirstClassCallable()) {
            if ($method_storage) {
                return new Union([new TClosure(
                    $method_storage->params,
                    $method_storage->return_type,
                    $method_storage->capabilities,
                )]);
            }

            return Type::getClosure();
        }

        if ($codebase->methods->return_type_provider->has($premixin_method_id->fq_class_name)) {
            $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                $statements_analyzer,
                $premixin_method_id->fq_class_name,
                $premixin_method_id->method_name,
                $stmt,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                $lhs_type_part instanceof TGenericObject ? $lhs_type_part->type_params : null,
            );

            if ($return_type_candidate) {
                return $return_type_candidate;
            }
        }

        if ($premixin_method_id->method_name === 'getcode'
            && $premixin_method_id->fq_class_name !== Exception::class
            && $premixin_method_id->fq_class_name !== RuntimeException::class
            && $premixin_method_id->fq_class_name !== PDOException::class
            && (
                $codebase->classImplements($premixin_method_id->fq_class_name, Throwable::class)
                || $codebase->interfaceExtends($premixin_method_id->fq_class_name, Throwable::class)
            )
        ) {
            return Type::getInt();
        }

        if ($declaring_method_id && $declaring_method_id !== $method_id) {
            $declaring_fq_class_name = $declaring_method_id->fq_class_name;
            $declaring_method_name = $declaring_method_id->method_name;

            if ($codebase->methods->return_type_provider->has($declaring_fq_class_name)) {
                $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                    $statements_analyzer,
                    $declaring_fq_class_name,
                    $declaring_method_name,
                    $stmt,
                    $context,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                    $lhs_type_part instanceof TGenericObject ? $lhs_type_part->type_params : null,
                    $fq_class_name,
                    $method_name,
                );

                if ($return_type_candidate) {
                    return $return_type_candidate;
                }
            }
        }

        if (InternalCallMapHandler::inCallMap((string) $call_map_id)) {
            if (($template_result->lower_bounds || $class_storage->stubbed)
                && ($method_storage = ($class_storage->methods[$method_id->method_name] ?? null))
                && $method_storage->return_type
            ) {
                $return_type_candidate = $method_storage->return_type;

                $return_type_candidate = self::replaceTemplateTypes(
                    $return_type_candidate,
                    $template_result,
                    $method_id,
                    count($stmt->getArgs()),
                    $codebase,
                );
            } else {
                $callmap_callables = InternalCallMapHandler::getCallablesFromCallMap((string) $call_map_id);

                if (!$callmap_callables || $callmap_callables[0]->return_type === null) {
                    throw new UnexpectedValueException('Shouldn’t get here');
                }

                $return_type_candidate = $callmap_callables[0]->return_type;
            }

            if ($return_type_candidate->isFalsable()) {
                $return_type_candidate = $return_type_candidate->setProperties([
                    'ignore_falsable_issues' => true,
                ]);
            }

            $return_type_candidate = TypeExpander::expandUnion(
                $codebase,
                $return_type_candidate,
                $fq_class_name,
                $static_type,
                $class_storage->parent_class,
                true,
                false,
                false,
                true,
            );
        } else {
            $self_fq_class_name = $fq_class_name;

            $return_type_candidate = $codebase->methods->getMethodReturnType(
                $codebase,
                $method_id,
                $self_fq_class_name,
                $statements_analyzer,
                $args,
                $template_result,
            );

            if ($return_type_candidate) {
                if ($template_result->lower_bounds) {
                    $return_type_candidate = TypeExpander::expandUnion(
                        $codebase,
                        $return_type_candidate,
                        $fq_class_name,
                        null,
                        $class_storage->parent_class,
                        true,
                        false,
                        $static_type instanceof TNamedObject
                        && $codebase->classlike_storage_provider->get($static_type->value)->final,
                        true,
                    );
                }

                $return_type_candidate = self::replaceTemplateTypes(
                    $return_type_candidate,
                    $template_result,
                    $method_id,
                    count($stmt->getArgs()),
                    $codebase,
                );

                $return_type_candidate = TypeExpander::expandUnion(
                    $codebase,
                    $return_type_candidate,
                    $self_fq_class_name,
                    $static_type,
                    $class_storage->parent_class,
                    true,
                    false,
                    $static_type instanceof TNamedObject
                    && $codebase->classlike_storage_provider->get($static_type->value)->final,
                    true,
                );

                $return_type_location = $codebase->methods->getMethodReturnTypeLocation(
                    $method_id,
                    $secondary_return_type_location,
                );

                if ($secondary_return_type_location) {
                    $return_type_location = $secondary_return_type_location;
                }

                $config = Config::getInstance();

                // only check the type locally if it's defined externally
                if ($return_type_location && !$config->isInProjectDirs($return_type_location->file_path)) {
                    /** @psalm-suppress UnusedMethodCall Actually generates issues */
                    $return_type_candidate->check(
                        $statements_analyzer,
                        new CodeLocation($statements_analyzer, $stmt),
                        $statements_analyzer->getSuppressedIssues(),
                        $context->phantom_classes,
                        true,
                        false,
                        false,
                        $context,
                    );
                }
            } else {
                $result->returns_by_ref =
                    $result->returns_by_ref
                    || $codebase->methods->getMethodReturnsByRef($method_id);
            }
        }

        if (!$return_type_candidate) {
            $return_type_candidate = $method_name === '__tostring' ? Type::getString() : Type::getMixed();
        }

        $return_type_candidate = TypeVariableTracker::resolveTypeVariables($return_type_candidate, $codebase);

        self::taintMethodCallResult(
            $statements_analyzer,
            $return_type_candidate,
            $stmt->name,
            $stmt->var,
            $args,
            $method_id,
            $declaring_method_id,
            $cased_method_id,
            $context,
        );

        return $return_type_candidate;
    }

    /**
     * @param list<PhpParser\Node\Arg> $args
     */
    public static function taintMethodCallResult(
        StatementsAnalyzer $statements_analyzer,
        Union &$return_type_candidate,
        PhpParser\Node $name_expr,
        PhpParser\Node\Expr $var_expr,
        array $args,
        MethodIdentifier $method_id,
        ?MethodIdentifier $declaring_method_id,
        string $cased_method_id,
        Context $context,
    ): void {
        if (!($graph = $statements_analyzer->getDataFlowGraphWithSuppressed())
            || !$declaring_method_id
        ) {
            return;
        }
        $taint_flow_graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();

        $codebase = $statements_analyzer->getCodebase();

        $event = new AddRemoveTaintsEvent($var_expr, $context, $statements_analyzer, $codebase);

        $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
        $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

        $method_storage = $codebase->methods->getStorage(
            $declaring_method_id,
        );

        $node_location = new CodeLocation($statements_analyzer, $name_expr);

        $is_declaring = (string) $declaring_method_id === (string) $method_id;

        $var_id = ExpressionIdentifier::getExtendedVarId(
            $var_expr,
            null,
            $statements_analyzer,
        );

        $specialize_call = TaintFlowGraph::isCallSpecialized(
            $taint_flow_graph,
            $codebase,
            $method_storage,
            $node_location,
        );

        if ($specialize_call && $taint_flow_graph) {
            // the receiver is only tracked through calls explicitly specialized: see FunctionLikeAnalyzer
            // a receiver without a variable, like `(new A())->m()`, enters the body through its own type
            $receiver_type = $var_id !== null && isset($context->vars_in_scope[$var_id])
                ? $context->vars_in_scope[$var_id]
                : ($var_id === null ? $statements_analyzer->node_data->getType($var_expr) : null);

            if ($method_storage->specialize_call && $receiver_type !== null) {
                $parent_nodes = $receiver_type->parent_nodes;

                $var_node = $var_id !== null
                    ? DataFlowNode::getForAssignment($var_id, new CodeLocation($statements_analyzer, $var_expr))
                    : null;

                // This call is specialized by its own location, whatever specializations the receiver's nodes
                // carry: the parent of a receiver without a variable, like `(new A())->m()`, is the specialized
                // `$this out of A::__construct` of the `new`. Keyed by that, the return node would be a
                // specialization the call's body never exits into.
                $call_specialization_key = DataFlowNode::getSpecializationKey($node_location);

                if ($method_storage->location) {
                    // the body of the method, declared by this class or the one it inherits it from, takes `$this`
                    // from this node: this call enters it with its own specialization, like the arguments do, so
                    // that what it returns is this object's and not every object's
                    $this_parent_node = DataFlowNode::getForAssignment(
                        '$this in ' . (string) $declaring_method_id,
                        $method_storage->location,
                        $call_specialization_key,
                    );

                    $taint_flow_graph->addNode($this_parent_node);

                    foreach ($parent_nodes as $parent_node) {
                        $taint_flow_graph->addPath(
                            $parent_node,
                            $this_parent_node,
                            '=',
                            $added_taints,
                            $removed_taints,
                        );
                    }
                }

                // Build the return node the same way whether or not this class declares the
                // method (it used to get a dedicated location-less 'inherited-method' node when
                // inherited): a single getForMethodReturn() that derives the node's location from
                // the declaring-method storage. This gives the inherited-call node a meaningful
                // definition location -- the method's return-type location -- instead of null, so
                // a taint trace points at where the method is actually defined; and, since that
                // location is a pure function of the (declaring) storage, it keeps id -> location
                // deterministic across forked workers.
                $method_call_node = DataFlowNode::getForMethodReturn(
                    $cased_method_id,
                    $method_storage,
                    $node_location,
                );

                $taint_flow_graph->addNode($method_call_node);

                $cased_declaring_method_id = $codebase->methods->getCasedMethodId($declaring_method_id);

                // what the method leaves in the object, which isn't what it returns
                if ($var_node !== null && $method_storage->location) {
                    $taint_flow_graph->addNode($var_node);

                    $this_out_node = DataFlowNode::getForAssignment(
                        '$this out of ' . $cased_declaring_method_id,
                        $method_storage->location,
                        $method_call_node->specialization_key,
                    );

                    $taint_flow_graph->addNode($this_out_node);
                    $taint_flow_graph->addPath(
                        $this_out_node,
                        $var_node,
                        'method-call-' . $method_id->method_name,
                        $added_taints,
                        $removed_taints,
                    );
                }

                if (!$is_declaring) {
                    $declaring_method_call_node = DataFlowNode::getForMethodReturn(
                        $cased_declaring_method_id,
                        $method_storage,
                        null,
                        0,
                        $method_call_node->specialization_key,
                    );

                    $taint_flow_graph->addNode($declaring_method_call_node);
                    $taint_flow_graph->addPath(
                        $declaring_method_call_node,
                        $method_call_node,
                        'parent',
                        $added_taints,
                        $removed_taints,
                    );
                }

                $return_type_candidate = $return_type_candidate->setParentNodes([
                    $method_call_node->id => $method_call_node,
                ]);

                if ($var_id !== null && $var_node !== null) {
                    $context->vars_in_scope[$var_id] = $receiver_type->setParentNodes(
                        [$var_node->id => $var_node],
                    );
                }
            } else {
                $method_call_node = DataFlowNode::getForMethodReturn(
                    $cased_method_id,
                    $method_storage,
                    $node_location,
                );

                if (!$is_declaring) {
                    $cased_declaring_method_id = $codebase->methods->getCasedMethodId($declaring_method_id);

                    $declaring_method_call_node = DataFlowNode::getForMethodReturn(
                        $cased_declaring_method_id,
                        $method_storage,
                        $node_location,
                    );

                    $taint_flow_graph->addNode($declaring_method_call_node);
                    $taint_flow_graph->addPath(
                        $declaring_method_call_node,
                        $method_call_node,
                        'parent',
                        $added_taints,
                        $removed_taints,
                    );
                }

                $taint_flow_graph->addNode($method_call_node);

                $return_type_candidate = $return_type_candidate->setParentNodes([
                    $method_call_node->id => $method_call_node,
                ]);
            }
        } else {
            // only unspecialized calls take the body's own return node: it would connect every
            // specialized call's result to the taint returned by any call. Usage tracking works
            // through the specialized nodes just the same.
            $method_call_node = DataFlowNode::getForMethodReturn(
                $cased_method_id,
                $method_storage,
            );

            if (!$is_declaring) {
                $cased_declaring_method_id = $codebase->methods->getCasedMethodId($declaring_method_id);

                $declaring_method_call_node = DataFlowNode::getForMethodReturn(
                    $cased_declaring_method_id,
                    $method_storage,
                    null,
                );

                $graph->addNode($declaring_method_call_node);
                $graph->addPath(
                    $declaring_method_call_node,
                    $method_call_node,
                    'parent',
                    $added_taints,
                    $removed_taints,
                );
            }

            $graph->addNode($method_call_node);

            $return_type_candidate = $return_type_candidate->setParentNodes([
                $method_call_node->id => $method_call_node,
            ]);
        }

        if (!$taint_flow_graph) {
            return;
        }

        FunctionCallReturnTypeFetcher::taintUsingFlows(
            $method_storage,
            $taint_flow_graph,
            (string) $method_id,
            $args,
            $specialize_call ? $node_location : null,
            $method_call_node,
            $method_storage->removed_taints,
        );

        FunctionCallReturnTypeFetcher::taintUsingStorage(
            $method_storage,
            $taint_flow_graph,
            $method_call_node,
        );
    }

    public static function replaceTemplateTypes(
        Union $return_type_candidate,
        TemplateResult $template_result,
        MethodIdentifier $method_id,
        int $arg_count,
        Codebase $codebase,
    ): Union {
        if ($template_result->template_types) {
            $bindable_template_types = $return_type_candidate->getTemplateTypes();

            foreach ($bindable_template_types as $template_type) {
                if ($template_type->defining_class !== $method_id->fq_class_name
                    && !isset(
                        $template_result->lower_bounds
                            [$template_type->param_name]
                            [$template_type->defining_class],
                    )
                ) {
                    if ($template_type->param_name === 'TFunctionArgCount') {
                        $template_result->lower_bounds[$template_type->param_name] = [
                            'fn-' . $method_id->method_name => [
                                new TemplateBound(
                                    Type::getInt(false, $arg_count),
                                ),
                            ],
                        ];
                    } elseif ($template_type->param_name === 'TPhpMajorVersion') {
                        $template_result->lower_bounds[$template_type->param_name] = [
                            'fn-' . $method_id->method_name => [
                                new TemplateBound(
                                    Type::getInt(false, $codebase->getMajorAnalysisPhpVersion()),
                                ),
                            ],
                        ];
                    } elseif ($template_type->param_name === 'TPhpVersionId') {
                        $template_result->lower_bounds[$template_type->param_name] = [
                            'fn-' . $method_id->method_name => [
                                new TemplateBound(
                                    Type::getInt(
                                        false,
                                        $codebase->analysis_php_version_id,
                                    ),
                                ),
                            ],
                        ];
                    } else {
                        $template_result->lower_bounds[$template_type->param_name] = [
                            ($template_type->defining_class) => [
                                new TemplateBound(Type::getNever()),
                            ],
                        ];
                    }
                }
            }
        }

        if ($template_result->lower_bounds) {
            $return_type_candidate = TypeExpander::expandUnion(
                $codebase,
                $return_type_candidate,
                null,
                null,
                null,
            );

            $return_type_candidate = TemplateInferredTypeReplacer::replace(
                $return_type_candidate,
                $template_result,
                $codebase,
            );
        }

        return $return_type_candidate;
    }
}
