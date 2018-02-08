<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\Type\Reconciler;

class TernaryChecker
{
    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Ternary $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Ternary $stmt,
        Context $context
    ) {
        $pre_referenced_var_ids = $context->referenced_var_ids;
        $context->referenced_var_ids = [];

        $context->inside_conditional = true;
        if (ExpressionChecker::analyze($statements_checker, $stmt->cond, $context) === false) {
            return false;
        }

        $new_referenced_var_ids = $context->referenced_var_ids;
        $context->referenced_var_ids = array_merge($pre_referenced_var_ids, $new_referenced_var_ids);

        $context->inside_conditional = false;

        $t_if_context = clone $context;

        $if_clauses = AlgebraChecker::getFormula(
            $stmt->cond,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $ternary_clauses = AlgebraChecker::simplifyCNF(array_merge($context->clauses, $if_clauses));

        $negated_clauses = AlgebraChecker::negateFormula($if_clauses);

        $negated_if_types = AlgebraChecker::getTruthsFromFormula($negated_clauses);

        $reconcilable_if_types = AlgebraChecker::getTruthsFromFormula($ternary_clauses);

        $changed_var_ids = [];

        $t_if_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
            $reconcilable_if_types,
            $t_if_context->vars_in_scope,
            $changed_var_ids,
            $new_referenced_var_ids,
            $statements_checker,
            new CodeLocation($statements_checker->getSource(), $stmt->cond),
            $statements_checker->getSuppressedIssues()
        );

        $t_if_context->vars_in_scope = $t_if_vars_in_scope_reconciled;
        $t_else_context = clone $context;

        if ($stmt->if) {
            if (ExpressionChecker::analyze($statements_checker, $stmt->if, $t_if_context) === false) {
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
                $statements_checker,
                new CodeLocation($statements_checker->getSource(), $stmt->else),
                $statements_checker->getSuppressedIssues()
            );

            $t_else_context->vars_in_scope = $t_else_vars_in_scope_reconciled;
        }

        if (ExpressionChecker::analyze($statements_checker, $stmt->else, $t_else_context) === false) {
            return false;
        }

        foreach ($t_else_context->vars_in_scope as $var_id => $type) {
            if (isset($context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes($context->vars_in_scope[$var_id], $type);
            }
        }

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
                $statements_checker,
                new CodeLocation($statements_checker->getSource(), $stmt),
                $statements_checker->getSuppressedIssues()
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
