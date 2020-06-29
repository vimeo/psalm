<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CastAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Taint\Sink;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\ForbiddenEcho;
use Psalm\Issue\ImpureFunctionCall;
use Psalm\IssueBuffer;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;

class EchoAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Echo_ $stmt,
        Context $context
    ) : bool {
        $echo_param = new FunctionLikeParameter(
            'var',
            false
        );

        $codebase = $statements_analyzer->getCodebase();

        foreach ($stmt->exprs as $i => $expr) {
            $context->inside_call = true;
            ExpressionAnalyzer::analyze($statements_analyzer, $expr, $context);
            $context->inside_call = false;

            $expr_type = $statements_analyzer->node_data->getType($expr);

            if ($codebase->taint && $expr_type) {
                $expr_type = CastAnalyzer::castStringAttempt(
                    $statements_analyzer,
                    $context,
                    $expr_type,
                    $expr,
                    false
                );
            }

            if ($codebase->taint
                && $codebase->config->trackTaintsInPath($statements_analyzer->getFilePath())
            ) {
                $call_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

                $echo_param_sink = Sink::getForMethodArgument(
                    'echo',
                    'echo',
                    (int) $i,
                    null,
                    $call_location
                );

                $echo_param_sink->taints = [
                    Type\TaintKind::INPUT_HTML,
                    Type\TaintKind::USER_SECRET,
                    Type\TaintKind::SYSTEM_SECRET
                ];

                $codebase->taint->addSink($echo_param_sink);
            }

            if ($expr_type) {
                if (ArgumentAnalyzer::verifyType(
                    $statements_analyzer,
                    $expr_type,
                    Type::getString(),
                    null,
                    'echo',
                    (int)$i,
                    new CodeLocation($statements_analyzer->getSource(), $expr),
                    $expr,
                    $context,
                    $echo_param,
                    false,
                    null,
                    true,
                    true,
                    new CodeLocation($statements_analyzer, $stmt)
                ) === false) {
                    return false;
                }
            }
        }

        if ($codebase->config->forbid_echo) {
            if (IssueBuffer::accepts(
                new ForbiddenEcho(
                    'Use of echo',
                    new CodeLocation($statements_analyzer, $stmt)
                ),
                $statements_analyzer->getSource()->getSuppressedIssues()
            )) {
                return false;
            }
        } elseif (isset($codebase->config->forbidden_functions['echo'])) {
            if (IssueBuffer::accepts(
                new ForbiddenCode(
                    'Use of echo',
                    new CodeLocation($statements_analyzer, $stmt)
                ),
                $statements_analyzer->getSource()->getSuppressedIssues()
            )) {
                // continue
            }
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && ($context->mutation_free
                || $context->external_mutation_free)
        ) {
            if (IssueBuffer::accepts(
                new ImpureFunctionCall(
                    'Cannot call echo from a mutation-free context',
                    new CodeLocation($statements_analyzer, $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        return true;
    }
}
