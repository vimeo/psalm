<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Issue\ImpureFunctionCall;
use Psalm\IssueBuffer;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TString;

class ExitAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Exit_ $stmt,
        Context $context
    ) : bool {
        $expr_type = null;

        if ($stmt->expr) {
            $context->inside_call = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph) {
                $call_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

                $echo_param_sink = TaintSink::getForMethodArgument(
                    'exit',
                    'exit',
                    0,
                    null,
                    $call_location
                );

                $echo_param_sink->taints = [
                    Type\TaintKind::INPUT_HTML,
                    Type\TaintKind::INPUT_HAS_QUOTES,
                    Type\TaintKind::USER_SECRET,
                    Type\TaintKind::SYSTEM_SECRET
                ];

                $statements_analyzer->data_flow_graph->addSink($echo_param_sink);
            }

            if ($expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
                $exit_param = new FunctionLikeParameter(
                    'var',
                    false
                );

                if (ArgumentAnalyzer::verifyType(
                    $statements_analyzer,
                    $expr_type,
                    new Type\Union([new TInt(), new TString()]),
                    null,
                    'exit',
                    null,
                    0,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->expr),
                    $stmt->expr,
                    $context,
                    $exit_param,
                    false,
                    null,
                    true,
                    true,
                    new CodeLocation($statements_analyzer, $stmt)
                ) === false) {
                    return false;
                }
            }

            $context->inside_call = false;
        }

        if ($expr_type
            && !$expr_type->isInt()
            && !$context->collect_mutations
            && !$context->collect_initializations
        ) {
            if ($context->mutation_free || $context->external_mutation_free) {
                $function_name = $stmt->getAttribute('kind') === PhpParser\Node\Expr\Exit_::KIND_DIE ? 'die' : 'exit';

                if (IssueBuffer::accepts(
                    new ImpureFunctionCall(
                        'Cannot call ' . $function_name . ' with a non-integer argument from a mutation-free context',
                        new CodeLocation($statements_analyzer, $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } elseif ($statements_analyzer->getSource() instanceof FunctionLikeAnalyzer
                && $statements_analyzer->getSource()->track_mutations
            ) {
                $statements_analyzer->getSource()->inferred_has_mutation = true;
                $statements_analyzer->getSource()->inferred_impure = true;
            }
        }

        $statements_analyzer->node_data->setType($stmt, Type::getEmpty());

        return true;
    }
}
