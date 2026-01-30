<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Issue\ForbiddenCode;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type\TaintKind;

use function in_array;

/**
 * @internal
 */
final class EvalAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Eval_ $stmt,
        Context $context,
    ): void {
        $was_inside_call = $context->inside_call;
        $context->inside_call = true;
        ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context);
        $context->inside_call = $was_inside_call;

        $codebase = $statements_analyzer->getCodebase();

        $expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        if ($expr_type) {
            if ($statements_analyzer->taint_flow_graph
                && $expr_type->parent_nodes
                && !in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
            ) {
                $arg_location = new CodeLocation($statements_analyzer->getSource(), $stmt->expr);

                $eval_param_sink = DataFlowNode::getForMethodArgument(
                    'eval',
                    'eval',
                    0,
                    $arg_location,
                    $arg_location,
                    TaintKind::INPUT_EVAL,
                );

                $statements_analyzer->taint_flow_graph->addSink($eval_param_sink);

                $codebase = $statements_analyzer->getCodebase();
                $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

                $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
                $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

                $taints = $added_taints & ~$removed_taints;
                if ($taints !== 0) {
                    $taint_source = $eval_param_sink->setTaints($taints);
                    $statements_analyzer->taint_flow_graph->addSource($taint_source);
                }

                foreach ($expr_type->parent_nodes as $parent_node) {
                    $statements_analyzer->taint_flow_graph->addPath(
                        $parent_node,
                        $eval_param_sink,
                        'arg',
                        $added_taints,
                        $removed_taints,
                    );
                }
            }
        }

        if (isset($codebase->config->forbidden_functions['eval'])) {
            IssueBuffer::maybeAdd(
                new ForbiddenCode(
                    'You have forbidden the use of eval',
                    new CodeLocation($statements_analyzer, $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        $context->check_classes = false;
        $context->check_variables = false;
    }
}
