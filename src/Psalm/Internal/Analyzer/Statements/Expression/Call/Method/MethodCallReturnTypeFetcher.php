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
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\InheritedMethodTaints;
use Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallReturnTypeFetcher;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\ConditionalTaintSinks;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\ReceiverPropertyTaints;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\Codebase\TaintStore;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\CallSiteVarianceProjector;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Internal\Type\TypeVariableTracker;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Storage\MethodStorage;
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
use function strtolower;

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
        ?CallSiteVarianceProjector $variance_projector = null,
    ): Union {
        $call_map_id = $declaring_method_id ?? $method_id;

        $fq_class_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;

        $class_storage = $codebase->methods->getClassLikeStorageForMethod($method_id);
        $method_storage = ($class_storage->methods[$method_id->method_name] ?? null);

        if ($stmt->isFirstClassCallable()) {
            if ($method_storage) {
                return new Union([new TClosure(
                    $variance_projector?->projectParams($method_storage->params) ?? $method_storage->params,
                    $variance_projector && $method_storage->return_type
                        ? $variance_projector->project($method_storage->return_type, false)
                        : $method_storage->return_type,
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

                $return_type_candidate = $variance_projector?->project($return_type_candidate, false)
                    ?? $return_type_candidate;

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
                $return_type_candidate = $variance_projector?->project($return_type_candidate, false)
                    ?? $return_type_candidate;

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

        // what is sent to a generator becomes the value of its yield expressions (what it gives back, see
        // YieldAnalyzer::taintGenerator(), flows as dictionaries/InternalTaintObjectFlowMap.php says)
        if (strtolower((string) $declaring_method_id) === 'generator::send'
            && isset($args[0])
            && ($taint_flow_graph = $statements_analyzer->getTaintFlowGraphWithSuppressed())
            && ($var_type = $statements_analyzer->node_data->getType($stmt->var))
            && $var_type->parent_nodes
            && ($sent_type = $statements_analyzer->node_data->getType($args[0]->value))
            && $sent_type->parent_nodes
        ) {
            $sent_node = DataFlowNode::getForAssignment(
                'sent to the generator',
                new CodeLocation($statements_analyzer, $args[0]->value),
            );

            $taint_flow_graph->addNode($sent_node);

            foreach ($sent_type->parent_nodes as $parent_node) {
                $taint_flow_graph->addPath($parent_node, $sent_node, 'arg');
            }

            $taint_flow_graph->addGeneratorSend($sent_node, $var_type->parent_nodes);
        }

        return NativeClassTaintAnalyzer::taint(
            $statements_analyzer,
            $stmt,
            $context,
            $method_id,
            $args,
            $return_type_candidate,
        );
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

        // the nodes of the body of the method are keyed by the class it is analyzed as one of, which for a method
        // of a trait is the class using it
        $body_method_id = FunctionLikeAnalyzer::getBodyMethodId($codebase, $method_id);
        $cased_body_method_id = FunctionLikeAnalyzer::getCasedBodyMethodId($codebase, $method_id);

        $is_declaring = (string) $body_method_id === (string) $method_id;

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

        // what the object holds before the call, which a specialized call may replace in the context below
        $receiver_parent_nodes = (($var_id !== null && isset($context->vars_in_scope[$var_id])
            ? $context->vars_in_scope[$var_id]
            : $statements_analyzer->node_data->getType($var_expr))?->parent_nodes ?? []);

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

                // the body of an inherited method analyzed for the class of the object (see InheritedMethodTaints)
                $body_suffix = $is_declaring
                    ? null
                    : InheritedMethodTaints::getBodySuffix(
                        $statements_analyzer,
                        $method_id->fq_class_name,
                        $declaring_method_id,
                    );

                $method_location = $method_storage->location;

                if ($method_location) {
                    // the body of the method, declared by this class or the one it inherits it from, takes `$this`
                    // from this node: this call enters it with its own specialization, like the arguments do, so
                    // that what it returns is this object's and not every object's
                    $this_parent_node = InheritedMethodTaints::withMethodSuffix(
                        $declaring_method_id,
                        $body_suffix,
                        static fn(): DataFlowNode => DataFlowNode::getForAssignment(
                            '$this in ' . (string) $body_method_id,
                            $method_location,
                            $call_specialization_key,
                        ),
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

                // what the method leaves in the object, which isn't what it returns
                if ($var_node !== null && $method_location) {
                    $taint_flow_graph->addNode($var_node);

                    $this_out_node = InheritedMethodTaints::withMethodSuffix(
                        $declaring_method_id,
                        $body_suffix,
                        static fn(): DataFlowNode => DataFlowNode::getForAssignment(
                            '$this out of ' . $cased_body_method_id,
                            $method_location,
                            $method_call_node->specialization_key,
                        ),
                    );

                    $taint_flow_graph->addNode($this_out_node);
                    $taint_flow_graph->addPath(
                        $this_out_node,
                        $var_node,
                        'method-call-' . $method_id->method_name,
                        $added_taints,
                        $removed_taints,
                    );

                    // a stubbed method has no body to take `$this` through: the object keeps what it has
                    if ($method_storage->stubbed) {
                        $taint_flow_graph->addPath(
                            InheritedMethodTaints::withMethodSuffix(
                                $declaring_method_id,
                                $body_suffix,
                                static fn(): DataFlowNode => DataFlowNode::getForAssignment(
                                    '$this in ' . (string) $body_method_id,
                                    $method_location,
                                    $call_specialization_key,
                                ),
                            ),
                            $this_out_node,
                            '$this',
                        );
                    }
                }

                if (!$is_declaring) {
                    $declaring_method_call_node = InheritedMethodTaints::withMethodSuffix(
                        $declaring_method_id,
                        $body_suffix,
                        static fn(): DataFlowNode => DataFlowNode::getForMethodReturn(
                            $cased_body_method_id,
                            $method_storage,
                            null,
                            0,
                            $method_call_node->specialization_key,
                        ),
                    );

                    $taint_flow_graph->addNode($declaring_method_call_node);
                    $taint_flow_graph->addPath($declaring_method_call_node, $method_call_node, 'parent');
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
                    $declaring_method_call_node = InheritedMethodTaints::withMethodSuffix(
                        $declaring_method_id,
                        InheritedMethodTaints::getBodySuffix(
                            $statements_analyzer,
                            $method_id->fq_class_name,
                            $declaring_method_id,
                        ),
                        static fn(): DataFlowNode => DataFlowNode::getForMethodReturn(
                            $cased_body_method_id,
                            $method_storage,
                            $node_location,
                        ),
                    );

                    $taint_flow_graph->addNode($declaring_method_call_node);
                    $taint_flow_graph->addPath($declaring_method_call_node, $method_call_node, 'parent');
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
                $declaring_method_call_node = InheritedMethodTaints::withMethodSuffix(
                    $declaring_method_id,
                    InheritedMethodTaints::getBodySuffix(
                        $statements_analyzer,
                        $method_id->fq_class_name,
                        $declaring_method_id,
                    ),
                    static fn(): DataFlowNode => DataFlowNode::getForMethodReturn(
                        $cased_body_method_id,
                        $method_storage,
                        null,
                    ),
                );

                $graph->addNode($declaring_method_call_node);
                $graph->addPath($declaring_method_call_node, $method_call_node, 'parent');
            }

            $graph->addNode($method_call_node);

            $return_type_candidate = $return_type_candidate->setParentNodes([
                $method_call_node->id => $method_call_node,
            ]);
        }

        if (!$taint_flow_graph) {
            return;
        }

        // `@psalm-flow ($this) -> return`: what the method returns holds what the object it is called on holds
        if ($method_storage->return_source_this !== null) {
            // what the native return type cannot hold, since PHP enforces it
            $type_removed_taints = $method_storage->signature_return_type?->getTaintsToRemove(
                $codebase->getInputTaints(),
            ) ?? 0;

            foreach ($receiver_parent_nodes as $parent_node) {
                $taint_flow_graph->addPath(
                    $parent_node,
                    $method_call_node,
                    $method_storage->return_source_this,
                    $method_storage->added_taints,
                    $method_storage->removed_taints | $type_removed_taints,
                );
            }
        }

        $dispatch_node = self::getReturnDispatchNode(
            $codebase,
            $taint_flow_graph,
            $method_id,
            $method_storage,
            $node_location,
            $receiver_parent_nodes,
        );

        if ($dispatch_node) {
            $return_type_candidate = $return_type_candidate->addParentNodes([$dispatch_node->id => $dispatch_node]);
        }

        FunctionCallReturnTypeFetcher::taintUsingFlows(
            $method_storage,
            $taint_flow_graph,
            (string) $method_id,
            $args,
            $specialize_call ? $node_location : null,
            $method_call_node,
            $codebase->getInputTaints(),
            $method_storage->removed_taints,
        );

        FunctionCallReturnTypeFetcher::taintUsingCallableFlows(
            $codebase,
            $method_storage,
            $taint_flow_graph,
            $cased_method_id,
            $method_id,
            $specialize_call ? $node_location : null,
            $method_call_node,
            $method_storage->removed_taints,
        );

        FunctionCallReturnTypeFetcher::taintUsingStorage(
            $method_storage,
            $taint_flow_graph,
            $method_call_node,
            $codebase->getInputTaints(),
        );

        $return_type_candidate = TaintStore::taintCall(
            $statements_analyzer,
            $taint_flow_graph,
            $method_storage,
            $args,
            $node_location,
            $return_type_candidate,
            $cased_method_id,
            $method_id,
            $specialize_call ? $node_location : null,
        );

        ConditionalTaintSinks::taintCall(
            $statements_analyzer,
            $taint_flow_graph,
            $method_storage,
            $args,
            $cased_method_id,
            $node_location,
        );

        $return_type_candidate = ReceiverPropertyTaints::taintCall(
            $statements_analyzer,
            $taint_flow_graph,
            $method_storage,
            $args,
            $method_id->fq_class_name,
            $receiver_parent_nodes,
            $var_id,
            $context,
            $node_location,
            $return_type_candidate,
        );

        // what the plugins add to and remove from the result of this call, on a node of its own: the nodes the
        // result comes from may be shared with the other calls, the last call deciding for all
        if ($added_taints !== 0 || $removed_taints !== 0) {
            $call_result_node = DataFlowNode::getForAssignment($cased_method_id, $node_location);

            $graph->addNode($call_result_node);

            foreach ($return_type_candidate->parent_nodes as $parent_node) {
                $graph->addPath($parent_node, $call_result_node, '=', $added_taints, $removed_taints);
            }

            $return_type_candidate = $return_type_candidate->setParentNodes([
                $call_result_node->id => $call_result_node,
            ]);
        }
    }

    /**
     * The node of what a virtual call of a method at $call_location gets from the methods that may run instead, those
     * of the classes extending the class it is called on, or null if there are none.
     *
     * That can't be the return node of the method: the calls on the classes inheriting it go there too, and they
     * can't run the methods of the others. The methods are linked to a dispatch node (see
     * DataFlowNode::getForReturnDispatch()) shared by the virtual calls of the method on the class, once, so that
     * the number of calls doesn't multiply its paths. Each call takes it through a node of its own, so that what is
     * added to the result of a call doesn't go to the others.
     *
     * The overrides specialized by call take the object of the call, from $receiver_parent_nodes, as their `$this`,
     * through a node shared the same way (see DataFlowNode::getForThisDispatch()).
     *
     * @param array<string, DataFlowNode> $receiver_parent_nodes
     */
    public static function getReturnDispatchNode(
        Codebase $codebase,
        TaintFlowGraph $taint_flow_graph,
        MethodIdentifier $method_id,
        MethodStorage $method_storage,
        CodeLocation $call_location,
        array $receiver_parent_nodes = [],
    ): ?DataFlowNode {
        // a constructor is called on the class it constructs, a private method is not overridden
        if ($method_id->method_name === '__construct'
            || $method_storage->final
            || $method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
        ) {
            return null;
        }

        $class_storage = $codebase->classlike_storage_provider->get($method_id->fq_class_name);

        if ($class_storage->dependent_classlikes === []) {
            return null;
        }

        $cased_method_id = $class_storage->name . '::' . (string) $method_storage->cased_name;
        $dispatch_node = DataFlowNode::getForReturnDispatch($cased_method_id);
        $this_dispatch_node = DataFlowNode::getForThisDispatch($cased_method_id);

        if ($taint_flow_graph->isEmptyDispatch($dispatch_node)) {
            return null;
        }

        if (!$taint_flow_graph->hasNode($dispatch_node)) {
            $return_nodes = [];
            $this_nodes = [];
            $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

            foreach ($class_storage->dependent_classlikes as $dependent_classlike_lc => $_) {
                $dependent_method_id = new MethodIdentifier($dependent_classlike_lc, $method_id->method_name);

                // the method the objects of the class run, which it may inherit from a class not extending this one
                $dependent_appearing_method_id = $codebase->methods->getAppearingMethodId($dependent_method_id);
                $dependent_declaring_method_id = $codebase->methods->getDeclaringMethodId($dependent_method_id);

                if ($dependent_appearing_method_id === null
                    || $dependent_declaring_method_id === null
                    || (string) $dependent_appearing_method_id === (string) $appearing_method_id
                ) {
                    continue;
                }

                $dependent_storage = $codebase->methods->getStorage($dependent_declaring_method_id);

                // What the body of a method specialized by call site returns leaves its return node through the
                // specializations of the node, and only when the node has no path of its own: the dispatch is
                // then one more call site of it, keyed by the location of the method
                $specialization_location = $dependent_storage->location !== null
                    && TaintFlowGraph::isCallSpecialized(
                        $taint_flow_graph,
                        $codebase,
                        $dependent_storage,
                        $dependent_storage->location,
                    ) ? $dependent_storage->location : null;

                // the body of a method of a trait is analyzed as one of each class using it
                $return_node = DataFlowNode::getForMethodReturn(
                    FunctionLikeAnalyzer::getCasedBodyMethodId($codebase, $dependent_method_id),
                    $dependent_storage,
                    $specialization_location,
                );

                $return_nodes[$return_node->id] = $return_node;

                // The body of a method specialized by call takes `$this` from a node (see FunctionLikeAnalyzer): the
                // one of the specialization the return node above leaves, so that what it returns of the object, as a
                // parent class's method calling a method its subclass overrides to return `$this` or a property,
                // reaches the call
                if ($specialization_location !== null
                    && $dependent_storage->specialize_call
                    && !$method_storage->is_static
                ) {
                    $this_node = DataFlowNode::getForAssignment(
                        '$this in ' . (string) FunctionLikeAnalyzer::getBodyMethodId($codebase, $dependent_method_id),
                        $specialization_location,
                        DataFlowNode::getSpecializationKey($specialization_location),
                    );

                    $this_nodes[$this_node->id] = $this_node;
                }
            }

            // no other method may run: the classes extending this one inherit the method
            if ($return_nodes === []) {
                $taint_flow_graph->addEmptyDispatch($dispatch_node);

                return null;
            }

            $taint_flow_graph->addNode($dispatch_node);

            foreach ($return_nodes as $return_node) {
                $taint_flow_graph->addNode($return_node);
                $taint_flow_graph->addPath($return_node, $dispatch_node, 'dispatch');
            }

            if ($this_nodes !== []) {
                $taint_flow_graph->addNode($this_dispatch_node);

                foreach ($this_nodes as $this_node) {
                    $taint_flow_graph->addNode($this_node);
                    $taint_flow_graph->addPath($this_dispatch_node, $this_node, '=');
                }
            }
        }

        $call_node = DataFlowNode::getForAssignment($dispatch_node->label, $call_location);

        $taint_flow_graph->addNode($call_node);
        $taint_flow_graph->addPath($dispatch_node, $call_node, 'dispatch');

        // Without what the plugins add to and remove from this call: the parent nodes of the object may be shared with
        // the other calls, the last call deciding for all. They apply to the result of the call on a node of its own.
        if ($receiver_parent_nodes !== [] && $taint_flow_graph->hasNode($this_dispatch_node)) {
            foreach ($receiver_parent_nodes as $parent_node) {
                $taint_flow_graph->addPath($parent_node, $this_dispatch_node, '=');
            }
        }

        return $call_node;
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
