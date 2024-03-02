<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

/**
 * @internal
 */
final class EmptyAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Empty_ $stmt,
        Context $context
    ): void {
        IssetAnalyzer::analyzeIssetVar($statements_analyzer, $stmt->expr, $context);

        $codebase = $statements_analyzer->getCodebase();

        if (isset($codebase->config->forbidden_functions['empty'])) {
            IssueBuffer::maybeAdd(
                new ForbiddenCode(
                    'You have forbidden the use of empty',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        $expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        if ($expr_type) {
            if ($expr_type->hasBool()
                && $expr_type->isSingle()
                && !$expr_type->from_docblock
            ) {
                IssueBuffer::maybeAdd(
                    new InvalidArgument(
                        'Calling empty on a boolean value is almost certainly unintended',
                        new CodeLocation($statements_analyzer->getSource(), $stmt->expr),
                        'empty',
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            if ($expr_type->isAlwaysTruthy() && $expr_type->possibly_undefined === false) {
                $stmt_type = new TFalse($expr_type->from_docblock);
            } elseif ($expr_type->isAlwaysFalsy()) {
                $stmt_type = new TTrue($expr_type->from_docblock);
            } else {
                ExpressionAnalyzer::checkRiskyTruthyFalsyComparison($expr_type, $statements_analyzer, $stmt);
                $stmt_type = new TBool();
            }

            $stmt_type = new Union([$stmt_type], [
                'parent_nodes' => $expr_type->parent_nodes,
            ]);
        } else {
            $stmt_type = Type::getBool();
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);
    }
}
