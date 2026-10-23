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

        $codebase = $statements_analyzer->getCodebase();

        // an unpacked or unknown named argument may be given to any parameter: the conditions are not known, and any
        // argument may be given to the sink
        $args_by_param = TaintStore::getArgsByParam($storage, $args);

        $template_result = new TemplateResult(
            [],
            $args_by_param === null ? [] : self::getParamBounds($statements_analyzer, $storage, $args_by_param),
        );

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
            $sink_args = $args_by_param === null ? $args : ($args_by_param[$sink['param']] ?? []);
            if ($taints === 0 || $sink_args === []) {
                continue;
            }

            $sink_node = DataFlowNode::getForAssignment(
                'conditional sink ' . $cased_function_id . '#' . ($sink['param'] + 1),
                $call_location,
            )->setTaints($taints);

            $graph->addSink($sink_node);

            foreach ($sink_args as $arg) {
                foreach ($statements_analyzer->node_data->getType($arg->value)?->parent_nodes ?? [] as $parent_node) {
                    $graph->addPath($parent_node, $sink_node, 'arg');
                }
            }
        }
    }

    /**
     * The types of the arguments given to the parameters, as the lower bounds of the templates the conditional types
     * name them by; the default value of a parameter not given one. A parameter whose type isn't known has none,
     * which leaves both branches of a condition on it.
     *
     * @param array<int, list<Arg>> $args_by_param
     * @return array<string, array<string, Union>>
     * @psalm-external-mutation-free
     */
    private static function getParamBounds(
        StatementsAnalyzer $statements_analyzer,
        FunctionLikeStorage $storage,
        array $args_by_param,
    ): array {
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

            $type = null;
            if (isset($args_by_param[$offset])) {
                foreach ($args_by_param[$offset] as $arg) {
                    $arg_type = $statements_analyzer->node_data->getType($arg->value);
                    if ($arg_type === null) {
                        continue 2;
                    }

                    $type = $type === null ? $arg_type : Type::combineUnionTypes($type, $arg_type);
                }
            } elseif ($param->default_type instanceof Union) {
                $type = $param->default_type;
            }

            if ($type === null) {
                continue;
            }

            foreach ($template_bounds as $defining_class => $_) {
                $bounds[$template_name][$defining_class] = $type;
            }
        }

        return $bounds;
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
