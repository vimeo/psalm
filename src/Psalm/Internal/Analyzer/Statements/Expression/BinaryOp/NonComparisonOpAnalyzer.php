<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\ArrayAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\FalseOperand;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyFalseOperand;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\Issue\PossiblyNullOperand;
use Psalm\Issue\StringIncrement;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Reconciler;
use Psalm\Internal\Type\AssertionReconciler;
use Psalm\Internal\Type\TypeCombination;
use function array_merge;
use function array_diff_key;
use function array_filter;
use function array_intersect_key;
use function array_values;
use function array_map;
use function array_keys;
use function preg_match;
use function preg_quote;
use function strtolower;
use function strlen;

/**
 * @internal
 */
class NonComparisonOpAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp $stmt,
        Context $context
    ) : void {
        $stmt_left_type = $statements_analyzer->node_data->getType($stmt->left);
        $stmt_right_type = $statements_analyzer->node_data->getType($stmt->right);

        if (!$stmt_left_type || !$stmt_right_type) {
            return;
        }

        if (($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd
            )
            && $stmt_left_type->hasString()
            && $stmt_right_type->hasString()
        ) {
            $stmt_type = Type::getString();

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mod
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Pow
            || (($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\ShiftLeft
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\ShiftRight
                )
                && ($stmt_left_type->hasInt() || $stmt_right_type->hasInt())
            )
        ) {
            NonDivArithmeticOpAnalyzer::analyze(
                $statements_analyzer,
                $statements_analyzer->node_data,
                $stmt->left,
                $stmt->right,
                $stmt,
                $result_type,
                $context
            );

            if ($result_type) {
                $statements_analyzer->node_data->setType($stmt, $result_type);
            }

            return;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor) {
            if ($stmt_left_type->hasBool() || $stmt_right_type->hasBool()) {
                $statements_analyzer->node_data->setType($stmt, Type::getInt());
            }

            return;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalXor) {
            if ($stmt_left_type->hasBool() || $stmt_right_type->hasBool()) {
                $statements_analyzer->node_data->setType($stmt, Type::getBool());
            }

            return;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div) {
            NonDivArithmeticOpAnalyzer::analyze(
                $statements_analyzer,
                $statements_analyzer->node_data,
                $stmt->left,
                $stmt->right,
                $stmt,
                $result_type,
                $context
            );

            if ($result_type) {
                if ($result_type->hasInt()) {
                    $result_type->addType(new TFloat);
                }

                $statements_analyzer->node_data->setType($stmt, $result_type);
            }

            return;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr) {
            NonDivArithmeticOpAnalyzer::analyze(
                $statements_analyzer,
                $statements_analyzer->node_data,
                $stmt->left,
                $stmt->right,
                $stmt,
                $result_type,
                $context
            );
        }
    }
}
