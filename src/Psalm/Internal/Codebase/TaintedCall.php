<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallReturnTypeFetcher;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Provider\AddRemoveTaints\ConstantFlags;
use Psalm\Internal\Provider\AddRemoveTaints\HtmlFunctionTainter;
use Psalm\Internal\Provider\AddRemoveTaints\JsonFunctionTainter;
use Psalm\Plugin\EventHandler\Event\AddRemoveCallTaintsEvent;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Union;

use function array_key_exists;
use function array_keys;
use function count;
use function strtolower;

/**
 * A call of a function-like wired into the taint graph from the nodes of its arguments, without PHP code: what
 * Codebase::addTaintedCall() does for plugins analyzing calls made by other languages (templates, configuration).
 * Like the call of PHP code (see ArgumentAnalyzer::processTaintedness() and
 * FunctionCallReturnTypeFetcher::taintReturnType()):
 *  - each argument goes into the node of the parameter taking it, which its body takes, specialized to the call if
 *    Psalm specializes the calls of the function-like (see TaintFlowGraph::isCallSpecialized()), and which is a sink
 *    of the taints its `@psalm-taint-sink` names;
 *  - an argument given to a parameter with a conditional sink (`@psalm-taint-sink (<conditional>) $param`) goes into
 *    a sink of the taints the types of the arguments resolve it to (see ConditionalTaintSinks);
 *  - what the body returns, the `@psalm-flow` of its parameters and its `@psalm-taint-source` flow into the node of
 *    the call, without the taints it removes, nor those its conditional escapes
 *    (`@psalm-taint-escape (<conditional>)`) remove given the types of the arguments;
 *  - the handlers of AddRemoveCallTaintsEvent add and remove taints on the way of each argument and of what returns.
 * The arguments a variadic parameter takes after its first go into the node of the parameter, which its body takes.
 *
 * Not wired: the dispatch of a method call to the overrides of the method, by-reference parameters, the callables
 * given to callable parameters, and the keyed stores of `@psalm-flow` (`Store::$data[$key]`).
 *
 * @internal
 */
final class TaintedCall
{
    /**
     * @param string $cased_function_id the id the body of the function-like keys the nodes of its parameters and of
     *                                  its return by (see FunctionLikeAnalyzer::getCasedBodyMethodId())
     * @param list<list<DataFlowNode>> $argument_nodes the nodes each argument comes from, by position
     * @param array<int, Union> $argument_types the types of the arguments, by position
     * @return DataFlowNode the node of what the call returns
     */
    public static function wire(
        Codebase $codebase,
        TaintFlowGraph $graph,
        string $cased_function_id,
        FunctionLikeStorage $storage,
        array $argument_nodes,
        array $argument_types,
        CodeLocation $call_location,
    ): DataFlowNode {
        $input_taints = $codebase->getInputTaints();
        $event_dispatcher = $codebase->config->eventDispatcher;
        $specialization_location = TaintFlowGraph::isCallSpecialized($graph, $codebase, $storage, $call_location)
            ? $call_location
            : null;

        $nodes_by_param = [];
        $types_by_param = [];
        foreach ($argument_nodes as $offset => $nodes) {
            $param_offset = self::getParameterOffset($storage, $offset);
            if ($param_offset === null) {
                // an argument no parameter takes
                continue;
            }

            $param = $storage->params[$param_offset];
            $argument_type = $argument_types[$offset] ?? null;

            [$added_taints, $removed_taints] = $event_dispatcher->dispatchAddRemoveCallTaints(
                new AddRemoveCallTaintsEvent($codebase, $cased_function_id, $offset, $argument_type, $call_location),
            );
            // a type that can't hold user input doesn't take its taints
            $removed_taints |= $argument_type?->getTaintsToRemove($input_taints) ?? 0;

            // a node of this call's own, as what it adds and removes is this call's
            $argument_node = DataFlowNode::getForAssignment(
                'argument ' . ($offset + 1) . ' of ' . $cased_function_id,
                $call_location,
            );
            $graph->addNode($argument_node);
            foreach ($nodes as $node) {
                $graph->addPath($node, $argument_node, 'arg');
            }

            $source_taints = $added_taints & ~$removed_taints;
            if ($source_taints !== 0) {
                $graph->addSource($argument_node->setTaints($source_taints));
            }

            $param_node = DataFlowNode::getForMethodArgument(
                $cased_function_id,
                $param_offset,
                $storage,
                $specialization_location,
            );
            $graph->addNode($param_node);
            $graph->addPath($argument_node, $param_node, 'arg', $added_taints, $removed_taints);

            if ($param->sinks !== 0) {
                $graph->addSink($param_node);
            }

            if ($param->value_sinks !== 0) {
                $graph->addArgumentValuesSink($param_node, $param->value_sinks);
            }

            $nodes_by_param[$param_offset][] = $argument_node;

            // the arguments of a variadic parameter all decide its conditions: one whose type isn't known, none
            if (!array_key_exists($param_offset, $types_by_param)) {
                $types_by_param[$param_offset] = $argument_type;
            } elseif ($types_by_param[$param_offset] !== null) {
                $types_by_param[$param_offset] = $argument_type === null
                    ? null
                    : Type::combineUnionTypes($types_by_param[$param_offset], $argument_type);
            }
        }

        $template_result = ConditionalTaintSinks::getTemplateResult($storage, $types_by_param);

        ConditionalTaintSinks::addSinks(
            $codebase,
            $graph,
            $storage,
            $template_result,
            $cased_function_id,
            $call_location,
            $nodes_by_param,
        );

        $return_node = DataFlowNode::getForMethodReturn($cased_function_id, $storage, $specialization_location);
        $graph->addNode($return_node);

        FunctionCallReturnTypeFetcher::taintUsingParamFlows(
            $storage,
            $graph,
            $cased_function_id,
            array_keys($nodes_by_param),
            $specialization_location,
            $return_node,
            $input_taints,
            $storage->removed_taints,
        );

        FunctionCallReturnTypeFetcher::taintUsingStorage($storage, $graph, $return_node, $input_taints);

        [$added_taints, $removed_taints] = $event_dispatcher->dispatchAddRemoveCallTaints(
            new AddRemoveCallTaintsEvent($codebase, $cased_function_id, null, null, $call_location),
        );

        // a node of this call's own: what it removes depends on its arguments, and $return_node may be shared with
        // the other calls, the last call deciding for all
        $call_node = DataFlowNode::getForAssignment('call to ' . $cased_function_id, $call_location);
        $graph->addNode($call_node);
        $removed_taints |= FunctionCallReturnTypeFetcher::getConditionallyRemovedTaints(
            $codebase,
            $storage,
            $template_result,
        );

        $handler_added_taints = $added_taints;

        // the builtins whose escapes the handlers of a PHP call read from its flags: the decoders give back taints to
        // the flows of the call only
        $builtin_id = strtolower($cased_function_id);
        if ($builtin_id === 'json_encode') {
            // without flags, it has none
            [$certain_bits, $possible_bits] = count($argument_nodes) > 1
                ? ConstantFlags::getTypeBits($argument_types[1] ?? null)
                : [0, 0];
            $removed_taints |= JsonFunctionTainter::getRemovedTaints($certain_bits, $possible_bits);
        } elseif ($builtin_id === 'json_decode') {
            $added_taints |= JsonFunctionTainter::GIVEN_BACK_TAINTS;
        } else {
            [$builtin_added_taints, $builtin_removed_taints] = HtmlFunctionTainter::getWiredCallTaints(
                $codebase->analysis_php_version_id,
                $builtin_id,
                count($argument_nodes),
                $argument_types,
            );
            $added_taints |= $builtin_added_taints;
            $removed_taints |= $builtin_removed_taints;
        }
        $graph->addPath($return_node, $call_node, 'arg', $added_taints, $removed_taints);

        // what a handler adds to the call is there whatever the call is given
        if (($handler_added_taints & ~$removed_taints) !== 0) {
            $graph->addSource($call_node->setTaints($handler_added_taints & ~$removed_taints));
        }

        return $call_node;
    }

    /**
     * The offset of the parameter taking the argument at $offset: the variadic parameter takes those after it. Null
     * if none does.
     *
     * @psalm-mutation-free
     */
    private static function getParameterOffset(FunctionLikeStorage $storage, int $offset): ?int
    {
        if (isset($storage->params[$offset])) {
            return $offset;
        }

        $last = count($storage->params) - 1;

        return $last >= 0 && $storage->params[$last]->is_variadic ? $last : null;
    }
}
