<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;

class TernaryAnalyzer
{
    /**
     * @param   StatementsAnalyzer           $statements_analyzer
     * @param   PhpParser\Node\Expr\Ternary $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Ternary $stmt,
        Context $context
    ) {
        $pre_referenced_var_ids = $context->referenced_var_ids;
        $context->referenced_var_ids = [];

        $context->inside_conditional = true;
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->cond, $context) === false) {
            return false;
        }

        $new_referenced_var_ids = $context->referenced_var_ids;
        $context->referenced_var_ids = array_merge($pre_referenced_var_ids, $new_referenced_var_ids);

        $context->inside_conditional = false;

        $codebase = $statements_analyzer->getCodebase();

        $t_if_context = clone $context;

        $if_clauses = \Psalm\Type\Algebra::getFormula(
            $stmt->cond,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
            $codebase
        );

        $mixed_var_ids = [];

        foreach ($context->vars_in_scope as $var_id => $type) {
            if ($type->isMixed()) {
                $mixed_var_ids[] = $var_id;
            }
        }

        foreach ($context->vars_possibly_in_scope as $var_id => $_) {
            if (!isset($context->vars_in_scope[$var_id])) {
                $mixed_var_ids[] = $var_id;
            }
        }


        $if_clauses = array_values(
            array_map(
                /**
                 * @return \Psalm\Internal\Clause
                 */
                function (\Psalm\Internal\Clause $c) use ($mixed_var_ids) {
                    $keys = array_keys($c->possibilities);

                    foreach ($keys as $key) {
                        foreach ($mixed_var_ids as $mixed_var_id) {
                            if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                                return new \Psalm\Internal\Clause([], true);
                            }
                        }
                    }

                    return $c;
                },
                $if_clauses
            )
        );

        $ternary_clauses = Algebra::simplifyCNF(array_merge($context->clauses, $if_clauses));

        $negated_clauses = Algebra::negateFormula($if_clauses);

        $negated_if_types = Algebra::getTruthsFromFormula(
            Algebra::simplifyCNF(
                array_merge($context->clauses, $negated_clauses)
            )
        );

        $reconcilable_if_types = Algebra::getTruthsFromFormula($ternary_clauses, $new_referenced_var_ids);

        $changed_var_ids = [];

        $t_if_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
            $reconcilable_if_types,
            $t_if_context->vars_in_scope,
            $changed_var_ids,
            $new_referenced_var_ids,
            $statements_analyzer,
            new CodeLocation($statements_analyzer->getSource(), $stmt->cond)
        );

        $t_if_context->vars_in_scope = $t_if_vars_in_scope_reconciled;
        $t_else_context = clone $context;

        if ($stmt->if) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->if, $t_if_context) === false) {
                return false;
            }

            foreach ($t_if_context->vars_in_scope as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes($context->vars_in_scope[$var_id], $type);
                }
            }

            $context->referenced_var_ids = array_merge(
                $context->referenced_var_ids,
                $t_if_context->referenced_var_ids
            );

            $context->unreferenced_vars = array_intersect_key(
                $context->unreferenced_vars,
                $t_if_context->unreferenced_vars
            );
        }

        if ($negated_if_types) {
            $t_else_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                $negated_if_types,
                $t_else_context->vars_in_scope,
                $changed_var_ids,
                $new_referenced_var_ids,
                $statements_analyzer,
                new CodeLocation($statements_analyzer->getSource(), $stmt->else)
            );

            $t_else_context->vars_in_scope = $t_else_vars_in_scope_reconciled;
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->else, $t_else_context) === false) {
            return false;
        }

        foreach ($t_else_context->vars_in_scope as $var_id => $type) {
            if (isset($context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                    $context->vars_in_scope[$var_id],
                    $type
                );
            } elseif (isset($t_if_context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                    $t_if_context->vars_in_scope[$var_id],
                    $type
                );
            }
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $t_if_context->vars_possibly_in_scope,
            $t_else_context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $t_else_context->referenced_var_ids
        );

        $context->unreferenced_vars = array_intersect_key(
            $context->unreferenced_vars,
            $t_else_context->unreferenced_vars
        );

        $lhs_type = null;

        if ($stmt->if) {
            if (isset($stmt->if->inferredType)) {
                $lhs_type = $stmt->if->inferredType;
            }
        } elseif (isset($stmt->cond->inferredType)) {
            $if_return_type_reconciled = Reconciler::reconcileTypes(
                '!falsy',
                $stmt->cond->inferredType,
                '',
                $statements_analyzer,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer->getSuppressedIssues()
            );

            $lhs_type = $if_return_type_reconciled;
        }

        if (!$lhs_type || !isset($stmt->else->inferredType)) {
            $stmt->inferredType = Type::getMixed();
        } else {
            $stmt->inferredType = Type::combineUnionTypes($lhs_type, $stmt->else->inferredType);
        }

        return null;
    }
}
