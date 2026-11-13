<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

use function array_key_exists;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * The sinks of `@psalm-taint-sink (<conditional>) $param`: the taints the argument of a call is a sink of are those of
 * the conditional type resolved with the arguments of the call, as `@psalm-taint-escape (<conditional>)` resolves the
 * taints a call escapes. A condition the arguments don't decide (an argument whose type is not known, or that may or
 * may not be of the type of the condition) gives the taints of both branches.
 *
 * @internal
 */
final class ConditionalTaintSinks
{
    /** The prefix of the templates the parameters a conditional type names become (see FunctionLikeDocblockScanner) */
    private const PARAM_TEMPLATE_PREFIX = 'TGeneratedFromParam';

    /**
     * Makes the arguments of the call given to parameters with conditional sinks sinks of the taints their conditional
     * types resolve to
     *
     * @param array<array-key, Arg> $args
     */
    public static function taintCall(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        FunctionLikeStorage $storage,
        array $args,
        string $cased_function_id,
        CodeLocation $call_location,
    ): void {
        if ($storage->conditional_taint_sinks === []) {
            return;
        }

        // an unpacked or unknown named argument may be given to any parameter: the conditions are not known, and any
        // argument may be given to the sink
        $args_by_param = TaintStore::getArgsByParam($storage, $args);

        $nodes_by_param = [];
        $types_by_param = [];
        foreach ($args_by_param ?? [] as $offset => $param_args) {
            $nodes = [];
            $type = null;
            $is_type_known = true;
            foreach ($param_args as $arg) {
                $arg_type = $statements_analyzer->node_data->getType($arg->value);
                if ($arg_type === null) {
                    $is_type_known = false;
                    continue;
                }

                foreach ($arg_type->parent_nodes as $parent_node) {
                    $nodes[] = $parent_node;
                }

                $type = $type === null ? $arg_type : Type::combineUnionTypes($type, $arg_type);
            }

            $nodes_by_param[$offset] = $nodes;
            // a parameter given an argument whose type isn't known has none
            $types_by_param[$offset] = $is_type_known ? $type : null;
        }

        $all_nodes = [];
        if ($args_by_param === null) {
            foreach ($args as $arg) {
                foreach ($statements_analyzer->node_data->getType($arg->value)?->parent_nodes ?? [] as $parent_node) {
                    $all_nodes[] = $parent_node;
                }
            }
        }

        self::addSinks(
            $statements_analyzer->getCodebase(),
            $graph,
            $storage,
            self::getTemplateResult($storage, $args_by_param === null ? null : $types_by_param),
            $cased_function_id,
            $call_location,
            $args_by_param === null ? null : $nodes_by_param,
            $all_nodes,
        );
    }

    /**
     * Makes what a call gives the parameters with conditional sinks sinks of the taints their conditional types resolve
     * to, given $template_result (see getTemplateResult())
     *
     * @param array<int, list<DataFlowNode>>|null $nodes_by_param the nodes of the arguments, by the offset of the
     *                                                            parameter taking them; null if it isn't known
     * @param list<DataFlowNode> $all_nodes the nodes of all the arguments, when $nodes_by_param is null
     */
    public static function addSinks(
        Codebase $codebase,
        TaintFlowGraph $graph,
        FunctionLikeStorage $storage,
        TemplateResult $template_result,
        string $cased_function_id,
        CodeLocation $call_location,
        ?array $nodes_by_param,
        array $all_nodes = [],
    ): void {
        foreach ($storage->conditional_taint_sinks as $sink) {
            $type = TypeExpander::expandUnion(
                $codebase,
                TemplateInferredTypeReplacer::replace($sink['type'], $template_result, $codebase),
                null,
                null,
                null,
                true,
                true,
            );

            $taints = self::getTaints($codebase, $type, $storage->location);
            $sink_nodes = $nodes_by_param === null ? $all_nodes : ($nodes_by_param[$sink['param']] ?? []);
            if ($taints === 0 || $sink_nodes === []) {
                continue;
            }

            $sink_node = DataFlowNode::getForAssignment(
                'conditional sink ' . $cased_function_id . '#' . ($sink['param'] + 1),
                $call_location,
            )->setTaints($taints);

            $graph->addSink($sink_node);

            foreach ($sink_nodes as $node) {
                $graph->addPath($node, $sink_node, 'arg');
            }
        }
    }

    /**
     * The types of the arguments given to the parameters, as the lower bounds of the templates the conditional types
     * (of sinks and escapes) name them by; the default value of a parameter not given one. A parameter whose type
     * isn't known (null) has none, which leaves both branches of a condition on it.
     *
     * @param array<int, Union|null>|null $types_by_param by the offset of the parameter; null if which parameters
     *                                                    the arguments are given to isn't known
     * @psalm-mutation-free
     */
    public static function getTemplateResult(FunctionLikeStorage $storage, ?array $types_by_param): TemplateResult
    {
        if ($types_by_param === null) {
            return new TemplateResult([], []);
        }

        $bounds = [];

        foreach ($storage->template_types ?? [] as $template_name => $template_bounds) {
            if (!str_starts_with($template_name, self::PARAM_TEMPLATE_PREFIX)) {
                continue;
            }

            $offset = (int) substr($template_name, strlen(self::PARAM_TEMPLATE_PREFIX));
            $param = $storage->params[$offset] ?? null;
            if ($param === null) {
                continue;
            }

            if (array_key_exists($offset, $types_by_param)) {
                $type = $types_by_param[$offset];
            } else {
                $type = $param->default_type instanceof Union ? $param->default_type : null;
            }

            if ($type === null) {
                continue;
            }

            foreach ($template_bounds as $defining_class => $_) {
                $bounds[$template_name][$defining_class] = $type;
            }
        }

        return new TemplateResult([], $bounds);
    }

    /**
     * The taints the literal strings of the type name, in both branches of a conditional type left unresolved
     */
    private static function getTaints(Codebase $codebase, Union $type, ?CodeLocation $location): int
    {
        $taints = 0;

        foreach ($type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TConditional) {
                $taints |= self::getTaints($codebase, $atomic->if_type, $location);
                $taints |= self::getTaints($codebase, $atomic->else_type, $location);
            } elseif ($atomic instanceof TLiteralString) {
                $taints |= $codebase->getOrRegisterTaint($atomic->value, $location) ?? 0;
            }
        }

        return $taints;
    }
}
