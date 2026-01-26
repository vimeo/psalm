<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\ImpureFunctionCall;
use Psalm\IssueBuffer;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\Mutations;
use Psalm\Type;
use Psalm\Type\TaintKind;

/**
 * @internal
 */
final class PrintAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Print_ $stmt,
        Context $context,
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        if ($statements_analyzer->taint_flow_graph) {
            $call_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

            $print_param_sink = DataFlowNode::getForMethodArgument(
                'print',
                'print',
                0,
                null,
                $call_location,
                TaintKind::INPUT_HTML
                    | TaintKind::INPUT_HAS_QUOTES
                    | TaintKind::USER_SECRET
                    | TaintKind::SYSTEM_SECRET,
            );

            $statements_analyzer->taint_flow_graph->addSink($print_param_sink);
        }

        if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
            if (ArgumentAnalyzer::verifyType(
                $statements_analyzer,
                $stmt_expr_type,
                Type::getString(),
                null,
                'print',
                null,
                0,
                new CodeLocation($statements_analyzer->getSource(), $stmt->expr),
                $stmt->expr,
                $context,
                new FunctionLikeParameter('var', false),
                false,
                null,
                true,
                true,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
            ) === false) {
                return false;
            }
        }

        if (isset($codebase->config->forbidden_functions['print'])) {
            IssueBuffer::maybeAdd(
                new ForbiddenCode(
                    'You have forbidden the use of print',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        $statements_analyzer->signalMutation(
            Mutations::LEVEL_EXTERNAL,
            $context,
            'print',
            ImpureFunctionCall::class,
            $stmt,
        );

        $statements_analyzer->node_data->setType($stmt, Type::getInt(false, 1));

        return true;
    }
}
