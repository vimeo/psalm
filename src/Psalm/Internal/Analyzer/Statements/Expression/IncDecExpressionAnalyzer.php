<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use PhpParser\Node\Expr\{PostInc, PostDec, PreInc, PreDec};
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ImpurePropertyAssignment;
use Psalm\IssueBuffer;
use Psalm\Type;
use function strpos;

class IncDecExpressionAnalyzer
{
    /**
     * @param PostInc|PostDec|PreInc|PreDec $stmt
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context
    ) : bool {
        $was_inside_assignment = $context->inside_assignment;
        $context->inside_assignment = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
            if (!$was_inside_assignment) {
                $context->inside_assignment = false;
            }
            return false;
        }

        if (!$was_inside_assignment) {
            $context->inside_assignment = false;
        }

        if ($stmt_var_type = $statements_analyzer->node_data->getType($stmt->var)) {
            $return_type = null;

            $fake_right_expr = new PhpParser\Node\Scalar\LNumber(1, $stmt->getAttributes());
            $statements_analyzer->node_data->setType($fake_right_expr, Type::getInt());

            BinaryOp\NonDivArithmeticOpAnalyzer::analyze(
                $statements_analyzer,
                $statements_analyzer->node_data,
                $stmt->var,
                $fake_right_expr,
                $stmt,
                $return_type,
                $context
            );

            $stmt_type = clone $stmt_var_type;

            $statements_analyzer->node_data->setType($stmt, $stmt_type);
            $stmt_type->from_calculation = true;

            foreach ($stmt_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof Type\Atomic\TLiteralInt) {
                    $stmt_type->addType(new Type\Atomic\TInt);
                } elseif ($atomic_type instanceof Type\Atomic\TLiteralFloat) {
                    $stmt_type->addType(new Type\Atomic\TFloat);
                }
            }

            $var_id = ExpressionIdentifier::getArrayVarId($stmt->var, null);

            if ($var_id && $context->mutation_free && strpos($var_id, '->')) {
                if (IssueBuffer::accepts(
                    new ImpurePropertyAssignment(
                        'Cannot assign to a property from a mutation-free context',
                        new CodeLocation($statements_analyzer, $stmt->var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $codebase = $statements_analyzer->getCodebase();

            if ($var_id && isset($context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = $stmt_type;

                if ($codebase->find_unused_variables && $stmt->var instanceof PhpParser\Node\Expr\Variable) {
                    $location = new CodeLocation($statements_analyzer, $stmt->var);
                    $context->assigned_var_ids[$var_id] = true;
                    $context->possibly_assigned_var_ids[$var_id] = true;

                    if (!$context->inside_isset) {
                        $statements_analyzer->registerVariableAssignment(
                            $var_id,
                            $location
                        );

                        $context->unreferenced_vars[$var_id] = [$location->getHash() => $location];
                    }
                }

                // removes dependent vars from $context
                $context->removeDescendents(
                    $var_id,
                    $context->vars_in_scope[$var_id],
                    $return_type,
                    $statements_analyzer
                );
            }
        } else {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        }

        return true;
    }
}
