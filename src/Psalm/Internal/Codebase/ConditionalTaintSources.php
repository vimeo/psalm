<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Union;

/**
 * The sources of `@psalm-taint-source (<conditional>)`: the taints of what a call returns are those of the conditional
 * type resolved with the arguments of the call, as for conditional sinks (see ConditionalTaintSinks). A condition the
 * arguments don't decide gives the taints of both branches.
 *
 * @internal
 */
final class ConditionalTaintSources
{
    /**
     * $type, the type of what the call returns, with a source of the taints the conditional types resolve to
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
        Union $type,
    ): Union {
        if ($storage->conditional_taint_sources === []) {
            return $type;
        }

        $codebase = $statements_analyzer->getCodebase();
        $args_by_param = TaintStore::getArgsByParam($storage, $args);

        $types_by_param = null;
        if ($args_by_param !== null) {
            $types_by_param = [];
            foreach ($args_by_param as $offset => $param_args) {
                $param_type = null;
                foreach ($param_args as $arg) {
                    $arg_type = $statements_analyzer->node_data->getType($arg->value);
                    if ($arg_type === null) {
                        $param_type = null;

                        break;
                    }

                    $param_type = $param_type === null ? $arg_type : Type::combineUnionTypes($param_type, $arg_type);
                }

                $types_by_param[$offset] = $param_type;
            }
        }

        $template_result = ConditionalTaintSinks::getTemplateResult($storage, $types_by_param);

        $taints = 0;
        foreach ($storage->conditional_taint_sources as $source) {
            $taints |= ConditionalTaintSinks::getTaints(
                $codebase,
                TypeExpander::expandUnion(
                    $codebase,
                    TemplateInferredTypeReplacer::replace($source, $template_result, $codebase),
                    null,
                    null,
                    null,
                    true,
                    true,
                ),
                $storage->location,
            );
        }

        // as for the sources of the whole return (see FunctionCallReturnTypeFetcher::taintUsingStorage())
        $taints &= ~$storage->removed_taints
            & ~($storage->signature_return_type?->getTaintsToRemove($codebase->getInputTaints()) ?? 0);

        if ($taints === 0) {
            return $type;
        }

        $source = DataFlowNode::getForTaint($cased_function_id . '-source', $call_location, $taints);

        $graph->addSource($source);

        return $type->addParentNodes([$source->id => $source]);
    }
}
