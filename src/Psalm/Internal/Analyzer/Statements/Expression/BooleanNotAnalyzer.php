<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\RiskyTruthyFalsyComparison;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

use function count;

/**
 * @internal
 */
final class BooleanNotAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BooleanNot $stmt,
        Context $context
    ): bool {


        $inside_negation = $context->inside_negation;

        $context->inside_negation = !$inside_negation;

        $result = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context);

        $context->inside_negation = $inside_negation;

        $expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        if ($expr_type) {
            if ($expr_type->isAlwaysTruthy()) {
                $stmt_type = new TFalse($expr_type->from_docblock);
            } elseif ($expr_type->isAlwaysFalsy()) {
                $stmt_type = new TTrue($expr_type->from_docblock);
            } else {
                if (count($expr_type->getAtomicTypes()) > 1) {
                    $both_types = $expr_type->getBuilder();
                    foreach ($both_types->getAtomicTypes() as $key => $atomic_type) {
                        if ($atomic_type->isTruthy()
                            || $atomic_type->isFalsy()
                            || $atomic_type instanceof TBool) {
                            $both_types->removeType($key);
                        }
                    }

                    if (count($both_types->getAtomicTypes()) > 0) {
                        $both_types = $both_types->freeze();
                        IssueBuffer::maybeAdd(
                            new RiskyTruthyFalsyComparison(
                                'Operand of type ' . $expr_type->getId() . ' contains ' .
                                'type' . (count($both_types->getAtomicTypes()) > 1 ? 's' : '') . ' ' .
                                $both_types->getId() . ', which can be falsy and truthy. ' .
                                'This can cause possibly unexpected behavior. Use strict comparison instead.',
                                new CodeLocation($statements_analyzer, $stmt),
                                $expr_type->getId(),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }
                }

                $stmt_type = new TBool();
            }

            $stmt_type = new Union([$stmt_type], [
                'parent_nodes' => $expr_type->parent_nodes,
            ]);
        } else {
            $stmt_type = Type::getBool();
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        return $result;
    }
}
