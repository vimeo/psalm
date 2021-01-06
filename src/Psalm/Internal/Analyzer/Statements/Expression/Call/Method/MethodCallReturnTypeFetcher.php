<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use function strtolower;
use Psalm\Internal\DataFlow\TaintSource;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Codebase\TaintFlowGraph;

class MethodCallReturnTypeFetcher
{
    /**
     * @param  Type\Atomic\TNamedObject|Type\Atomic\TTemplateParam  $static_type
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
        Type\Atomic $lhs_type_part,
        ?Type\Atomic $static_type,
        array $args,
        AtomicMethodCallAnalysisResult $result,
        TemplateResult $template_result
    ) : Type\Union {
        $call_map_id = $declaring_method_id ?: $method_id;

        $fq_class_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;

        if ($codebase->methods->return_type_provider->has($premixin_method_id->fq_class_name)) {
            $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                $statements_analyzer,
                $premixin_method_id->fq_class_name,
                $premixin_method_id->method_name,
                $stmt->args,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                $lhs_type_part instanceof TGenericObject ? $lhs_type_part->type_params : null
            );

            if ($return_type_candidate) {
                return $return_type_candidate;
            }
        }

        if ($declaring_method_id && $declaring_method_id !== $method_id) {
            $declaring_fq_class_name = $declaring_method_id->fq_class_name;
            $declaring_method_name = $declaring_method_id->method_name;

            if ($codebase->methods->return_type_provider->has($declaring_fq_class_name)) {
                $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                    $statements_analyzer,
                    $declaring_fq_class_name,
                    $declaring_method_name,
                    $stmt->args,
                    $context,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                    $lhs_type_part instanceof TGenericObject ? $lhs_type_part->type_params : null,
                    $fq_class_name,
                    $method_name
                );

                if ($return_type_candidate) {
                    return $return_type_candidate;
                }
            }
        }

        $class_storage = $codebase->methods->getClassLikeStorageForMethod($method_id);

        if (InternalCallMapHandler::inCallMap((string) $call_map_id)) {
            if (($template_result->upper_bounds || $class_storage->stubbed)
                && ($method_storage = ($class_storage->methods[$method_id->method_name] ?? null))
                && $method_storage->return_type
            ) {
                $return_type_candidate = clone $method_storage->return_type;

                $return_type_candidate = self::replaceTemplateTypes(
                    $return_type_candidate,
                    $template_result,
                    $method_id,
                    \count($stmt->args),
                    $codebase
                );
            } else {
                $callmap_callables = InternalCallMapHandler::getCallablesFromCallMap((string) $call_map_id);

                if (!$callmap_callables || $callmap_callables[0]->return_type === null) {
                    throw new \UnexpectedValueException('Shouldn’t get here');
                }

                $return_type_candidate = $callmap_callables[0]->return_type;
            }

            if (($call_map_id->fq_class_name === 'Iterator'
                    || $call_map_id->fq_class_name === 'IteratorIterator'
                    || $call_map_id->fq_class_name === 'Generator'
                    || $call_map_id->fq_class_name === 'SplDoublyLinkedList'
                    || $call_map_id->fq_class_name === 'SplObjectStorage'
                )
                && $method_name === 'current'
            ) {
                if ($stmt->getAttributes()) {
                    $return_type_candidate->addType(new Type\Atomic\TNull());
                    $return_type_candidate->ignore_nullable_issues = true;
                }
            }

            if ($return_type_candidate->isFalsable()) {
                $return_type_candidate->ignore_falsable_issues = true;
            }

            $return_type_candidate = \Psalm\Internal\Type\TypeExpander::expandUnion(
                $codebase,
                $return_type_candidate,
                $fq_class_name,
                $static_type,
                $class_storage->parent_class
            );
        } else {
            $self_fq_class_name = $fq_class_name;

            $return_type_candidate = $codebase->methods->getMethodReturnType(
                $method_id,
                $self_fq_class_name,
                $statements_analyzer,
                $args
            );

            if ($return_type_candidate) {
                $return_type_candidate = clone $return_type_candidate;

                $return_type_candidate = self::replaceTemplateTypes(
                    $return_type_candidate,
                    $template_result,
                    $method_id,
                    \count($stmt->args),
                    $codebase
                );

                $return_type_candidate = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $return_type_candidate,
                    $self_fq_class_name,
                    $static_type,
                    $class_storage->parent_class,
                    true,
                    false,
                    $static_type instanceof Type\Atomic\TNamedObject
                        && $codebase->classlike_storage_provider->get($static_type->value)->final
                );

                $return_type_location = $codebase->methods->getMethodReturnTypeLocation(
                    $method_id,
                    $secondary_return_type_location
                );

                if ($secondary_return_type_location) {
                    $return_type_location = $secondary_return_type_location;
                }

                $config = \Psalm\Config::getInstance();

                // only check the type locally if it's defined externally
                if ($return_type_location && !$config->isInProjectDirs($return_type_location->file_path)) {
                    $return_type_candidate->check(
                        $statements_analyzer,
                        new CodeLocation($statements_analyzer, $stmt),
                        $statements_analyzer->getSuppressedIssues(),
                        $context->phantom_classes,
                        true,
                        false,
                        false,
                        $context->calling_method_id
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

        self::taintMethodCallResult(
            $statements_analyzer,
            $return_type_candidate,
            $stmt->name,
            $stmt->var,
            $method_id,
            $declaring_method_id,
            $cased_method_id,
            $context
        );

        return $return_type_candidate;
    }

    public static function taintMethodCallResult(
        StatementsAnalyzer $statements_analyzer,
        Type\Union $return_type_candidate,
        PhpParser\Node $name_expr,
        PhpParser\Node\Expr $var_expr,
        MethodIdentifier $method_id,
        ?MethodIdentifier $declaring_method_id,
        string $cased_method_id,
        Context $context
    ) : void {
        $codebase = $statements_analyzer->getCodebase();

        if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
            && $declaring_method_id
            && !\in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
        ) {
            $method_storage = $codebase->methods->getStorage(
                $declaring_method_id
            );

            $node_location = new CodeLocation($statements_analyzer, $name_expr);

            $is_declaring = (string) $declaring_method_id === (string) $method_id;

            $var_id = ExpressionIdentifier::getArrayVarId(
                $var_expr,
                null,
                $statements_analyzer
            );

            if ($method_storage->specialize_call && $var_id && isset($context->vars_in_scope[$var_id])) {
                $var_nodes = [];

                $parent_nodes = $context->vars_in_scope[$var_id]->parent_nodes;

                $unspecialized_parent_nodes = \array_filter(
                    $parent_nodes,
                    function ($parent_node) {
                        return !$parent_node->specialization_key;
                    }
                );

                $specialized_parent_nodes = \array_filter(
                    $parent_nodes,
                    function ($parent_node) {
                        return (bool) $parent_node->specialization_key;
                    }
                );

                $var_node = DataFlowNode::getForAssignment(
                    $var_id,
                    new CodeLocation($statements_analyzer, $var_expr)
                );

                if ($method_storage->location) {
                    $this_parent_node = DataFlowNode::getForAssignment(
                        '$this in ' . $method_id,
                        $method_storage->location
                    );

                    foreach ($parent_nodes as $parent_node) {
                        $statements_analyzer->data_flow_graph->addPath($parent_node, $this_parent_node, '=');
                    }
                }

                $var_nodes[$var_node->id] = $var_node;

                $method_call_nodes = [];

                if ($unspecialized_parent_nodes) {
                    $method_call_node = DataFlowNode::getForMethodReturn(
                        (string) $method_id,
                        $cased_method_id,
                        $is_declaring ? ($method_storage->signature_return_type_location
                            ?: $method_storage->location) : null,
                        $node_location
                    );

                    $method_call_nodes[$method_call_node->id] = $method_call_node;
                }

                foreach ($specialized_parent_nodes as $parent_node) {
                    $universal_method_call_node = DataFlowNode::getForMethodReturn(
                        (string) $method_id,
                        $cased_method_id,
                        $is_declaring ? ($method_storage->signature_return_type_location
                            ?: $method_storage->location) : null,
                        null
                    );

                    $method_call_node = new DataFlowNode(
                        strtolower((string) $method_id),
                        $cased_method_id,
                        $is_declaring ? ($method_storage->signature_return_type_location
                            ?: $method_storage->location) : null,
                        $parent_node->specialization_key
                    );

                    $statements_analyzer->data_flow_graph->addPath(
                        $universal_method_call_node,
                        $method_call_node,
                        '='
                    );

                    $method_call_nodes[$method_call_node->id] = $method_call_node;
                }

                foreach ($method_call_nodes as $method_call_node) {
                    $statements_analyzer->data_flow_graph->addNode($method_call_node);

                    foreach ($var_nodes as $var_node) {
                        $statements_analyzer->data_flow_graph->addNode($var_node);

                        $statements_analyzer->data_flow_graph->addPath(
                            $method_call_node,
                            $var_node,
                            'method-call-' . $method_id->method_name
                        );
                    }

                    if (!$is_declaring) {
                        $cased_declaring_method_id = $codebase->methods->getCasedMethodId($declaring_method_id);

                        $declaring_method_call_node = new DataFlowNode(
                            strtolower((string) $declaring_method_id),
                            $cased_declaring_method_id,
                            $method_storage->signature_return_type_location ?: $method_storage->location,
                            $method_call_node->specialization_key
                        );

                        $statements_analyzer->data_flow_graph->addNode($declaring_method_call_node);
                        $statements_analyzer->data_flow_graph->addPath(
                            $declaring_method_call_node,
                            $method_call_node,
                            'parent'
                        );
                    }
                }

                $return_type_candidate->parent_nodes = $method_call_nodes;

                $stmt_var_type = clone $context->vars_in_scope[$var_id];

                $stmt_var_type->parent_nodes = $var_nodes;

                $context->vars_in_scope[$var_id] = $stmt_var_type;
            } else {
                $method_call_node = DataFlowNode::getForMethodReturn(
                    (string) $method_id,
                    $cased_method_id,
                    $is_declaring
                        ? ($method_storage->signature_return_type_location ?: $method_storage->location)
                        : null,
                    null
                );

                if (!$is_declaring) {
                    $cased_declaring_method_id = $codebase->methods->getCasedMethodId($declaring_method_id);

                    $declaring_method_call_node = DataFlowNode::getForMethodReturn(
                        (string) $declaring_method_id,
                        $cased_declaring_method_id,
                        $method_storage->signature_return_type_location ?: $method_storage->location,
                        null
                    );

                    $statements_analyzer->data_flow_graph->addNode($declaring_method_call_node);
                    $statements_analyzer->data_flow_graph->addPath(
                        $declaring_method_call_node,
                        $method_call_node,
                        'parent'
                    );
                }

                $statements_analyzer->data_flow_graph->addNode($method_call_node);

                $return_type_candidate->parent_nodes = [
                    $method_call_node->id => $method_call_node
                ];
            }

            if ($method_storage->taint_source_types) {
                $method_node = TaintSource::getForMethodReturn(
                    (string) $method_id,
                    $cased_method_id,
                    $method_storage->signature_return_type_location ?: $method_storage->location
                );

                $method_node->taints = $method_storage->taint_source_types;

                $statements_analyzer->data_flow_graph->addSource($method_node);
            }
        }
    }

    private static function replaceTemplateTypes(
        Type\Union $return_type_candidate,
        TemplateResult $template_result,
        MethodIdentifier $method_id,
        int $arg_count,
        Codebase $codebase
    ) : Type\Union {
        if ($template_result->template_types) {
            $bindable_template_types = $return_type_candidate->getTemplateTypes();

            foreach ($bindable_template_types as $template_type) {
                if ($template_type->defining_class !== $method_id->fq_class_name
                    && !isset(
                        $template_result->upper_bounds
                            [$template_type->param_name]
                            [$template_type->defining_class]
                    )
                ) {
                    if ($template_type->param_name === 'TFunctionArgCount') {
                        $template_result->upper_bounds[$template_type->param_name] = [
                            'fn-' . strtolower((string) $method_id) => new TemplateBound(
                                Type::getInt(false, $arg_count)
                            )
                        ];
                    } elseif ($template_type->param_name === 'TPhpMajorVersion') {
                        $template_result->upper_bounds[$template_type->param_name] = [
                            'fn-' . strtolower((string) $method_id) => new TemplateBound(
                                Type::getInt(false, $codebase->php_major_version)
                            )
                        ];
                    } else {
                        $template_result->upper_bounds[$template_type->param_name] = [
                            ($template_type->defining_class) => new TemplateBound(Type::getEmpty())
                        ];
                    }
                }
            }
        }

        if ($template_result->upper_bounds) {
            $return_type_candidate = \Psalm\Internal\Type\TypeExpander::expandUnion(
                $codebase,
                $return_type_candidate,
                null,
                null,
                null
            );

            TemplateInferredTypeReplacer::replace(
                $return_type_candidate,
                $template_result,
                $codebase
            );
        }

        return $return_type_candidate;
    }
}
