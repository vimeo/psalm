<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\ComplicatedExpressionException;
use Psalm\Exception\ScopeAnalysisException;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\Statements\Block\IfConditionalAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfElse\IfAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfElseAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Scope\IfScope;
use Psalm\Internal\Type\AssertionReconciler;
use Psalm\Node\Expr\VirtualBooleanNot;
use Psalm\Node\Stmt\VirtualExpression;
use Psalm\Node\Stmt\VirtualIf;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Type;
use Psalm\Type\Reconciler;

use function array_diff_key;
use function array_filter;
use function array_merge;
use function array_values;
use function count;
use function in_array;
use function spl_object_id;

/**
 * @internal
 */
class OrAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp $stmt,
        Context $context,
        bool $from_stmt = false
    ): bool {
        if ($from_stmt) {
            $fake_if_stmt = new VirtualIf(
                new VirtualBooleanNot($stmt->left, $stmt->left->getAttributes()),
                [
                    'stmts' => [
                        new VirtualExpression(
                            $stmt->right
                        )
                    ]
                ],
                $stmt->getAttributes()
            );

            return IfElseAnalyzer::analyze($statements_analyzer, $fake_if_stmt, $context) !== false;
        }

        $codebase = $statements_analyzer->getCodebase();

        $post_leaving_if_context = null;

        // we cap this at max depth of 4 to prevent quadratic behaviour
        // when analysing <expr> || <expr> || <expr> || <expr> || <expr>
        if (!$stmt->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            || !$stmt->left->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            || !$stmt->left->left->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
        ) {
            $if_scope = new IfScope();

            try {
                $if_conditional_scope = IfConditionalAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->left,
                    $context,
                    $codebase,
                    $if_scope,
                    $context->branch_point ?: (int) $stmt->getAttribute('startFilePos')
                );

                $left_context = $if_conditional_scope->if_context;

                $left_referenced_var_ids = $if_conditional_scope->cond_referenced_var_ids;
                $left_assigned_var_ids = $if_conditional_scope->assigned_in_conditional_var_ids;

                if ($stmt->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
                    $post_leaving_if_context = clone $context;
                }
            } catch (ScopeAnalysisException $e) {
                return false;
            }
        } else {
            $pre_referenced_var_ids = $context->cond_referenced_var_ids;
            $context->cond_referenced_var_ids = [];

            $pre_assigned_var_ids = $context->assigned_var_ids;

            $post_leaving_if_context = clone $context;

            $left_context = clone $context;
            $left_context->if_body_context = null;
            $left_context->assigned_var_ids = [];

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, $left_context) === false) {
                return false;
            }

            foreach ($left_context->parent_remove_vars as $var_id => $_) {
                $context->removeVarFromConflictingClauses($var_id);
            }

            IfConditionalAnalyzer::handleParadoxicalCondition($statements_analyzer, $stmt->left);

            foreach ($left_context->vars_in_scope as $var_id => $type) {
                if (!isset($context->vars_in_scope[$var_id])) {
                    if (isset($left_context->assigned_var_ids[$var_id])) {
                        $context->vars_in_scope[$var_id] = clone $type;
                    }
                } else {
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $context->vars_in_scope[$var_id],
                        $type,
                        $codebase
                    );
                }
            }

            $left_referenced_var_ids = $left_context->cond_referenced_var_ids;
            $left_context->cond_referenced_var_ids = array_merge($pre_referenced_var_ids, $left_referenced_var_ids);

            $left_assigned_var_ids = array_diff_key($left_context->assigned_var_ids, $pre_assigned_var_ids);
            $left_context->assigned_var_ids = array_merge($pre_assigned_var_ids, $left_context->assigned_var_ids);

            $left_referenced_var_ids = array_diff_key($left_referenced_var_ids, $left_assigned_var_ids);
        }

        $left_cond_id = spl_object_id($stmt->left);

        $left_clauses = FormulaGenerator::getFormula(
            $left_cond_id,
            $left_cond_id,
            $stmt->left,
            $context->self,
            $statements_analyzer,
            $codebase
        );

        try {
            $negated_left_clauses = Algebra::negateFormula($left_clauses);
        } catch (ComplicatedExpressionException $e) {
            try {
                $negated_left_clauses = FormulaGenerator::getFormula(
                    $left_cond_id,
                    $left_cond_id,
                    new VirtualBooleanNot($stmt->left),
                    $context->self,
                    $statements_analyzer,
                    $codebase,
                    false
                );
            } catch (ComplicatedExpressionException $e) {
                return false;
            }
        }

        if ($left_context->reconciled_expression_clauses) {
            $reconciled_expression_clauses = $left_context->reconciled_expression_clauses;

            $negated_left_clauses = array_values(
                array_filter(
                    $negated_left_clauses,
                    static fn(Clause $c): bool => !in_array($c->hash, $reconciled_expression_clauses)
                )
            );

            if (count($negated_left_clauses) === 1
                && $negated_left_clauses[0]->wedge
                && !$negated_left_clauses[0]->possibilities
            ) {
                $negated_left_clauses = [];
            }
        }

        $clauses_for_right_analysis = Algebra::simplifyCNF(
            [...$context->clauses, ...$negated_left_clauses]
        );

        $active_negated_type_assertions = [];

        $negated_type_assertions = Algebra::getTruthsFromFormula(
            $clauses_for_right_analysis,
            $left_cond_id,
            $left_referenced_var_ids,
            $active_negated_type_assertions
        );

        $changed_var_ids = [];

        $right_context = clone $context;

        if ($stmt->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            && $left_assigned_var_ids
            && $post_leaving_if_context
        ) {
            IfAnalyzer::addConditionallyAssignedVarsToContext(
                $statements_analyzer,
                $stmt->left,
                $post_leaving_if_context,
                $right_context,
                $left_assigned_var_ids
            );
        }

        if ($negated_type_assertions) {
            // while in an or, we allow scope to boil over to support
            // statements of the form if ($x === null || $x->foo())
            [$right_context->vars_in_scope, $right_context->references_in_scope] = Reconciler::reconcileKeyedTypes(
                $negated_type_assertions,
                $active_negated_type_assertions,
                $right_context->vars_in_scope,
                $right_context->references_in_scope,
                $changed_var_ids,
                $left_referenced_var_ids,
                $statements_analyzer,
                [],
                $left_context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt->left),
                !$context->inside_negation
            );
        }

        $right_context->clauses = $clauses_for_right_analysis;

        if ($changed_var_ids) {
            $partitioned_clauses = Context::removeReconciledClauses($right_context->clauses, $changed_var_ids);
            $right_context->clauses = $partitioned_clauses[0];
            $right_context->reconciled_expression_clauses = $context->reconciled_expression_clauses;

            foreach ($partitioned_clauses[1] as $clause) {
                $right_context->reconciled_expression_clauses[] = $clause->hash;
            }

            $partitioned_clauses = Context::removeReconciledClauses($context->clauses, $changed_var_ids);
            $context->clauses = $partitioned_clauses[0];

            foreach ($partitioned_clauses[1] as $clause) {
                $context->reconciled_expression_clauses[] = $clause->hash;
            }
        }

        $right_context->if_body_context = null;

        $pre_referenced_var_ids = $right_context->cond_referenced_var_ids;
        $right_context->cond_referenced_var_ids = [];

        $pre_assigned_var_ids = $right_context->assigned_var_ids;
        $right_context->assigned_var_ids = [];

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->right, $right_context) === false) {
            return false;
        }

        IfConditionalAnalyzer::handleParadoxicalCondition($statements_analyzer, $stmt->right);

        $right_referenced_var_ids = $right_context->cond_referenced_var_ids;
        $right_context->cond_referenced_var_ids = array_merge($pre_referenced_var_ids, $right_referenced_var_ids);

        $right_assigned_var_ids = $right_context->assigned_var_ids;
        $right_context->assigned_var_ids = array_merge($pre_assigned_var_ids, $right_assigned_var_ids);

        $right_cond_id = spl_object_id($stmt->right);

        $right_clauses = FormulaGenerator::getFormula(
            $right_cond_id,
            $right_cond_id,
            $stmt->right,
            $context->self,
            $statements_analyzer,
            $codebase
        );

        $clauses_for_right_analysis = Context::removeReconciledClauses(
            $clauses_for_right_analysis,
            $right_assigned_var_ids
        )[0];

        $combined_right_clauses = Algebra::simplifyCNF(
            [...$clauses_for_right_analysis, ...$right_clauses]
        );

        $active_right_type_assertions = [];

        $right_type_assertions = Algebra::getTruthsFromFormula(
            $combined_right_clauses,
            $right_cond_id,
            $right_referenced_var_ids,
            $active_right_type_assertions
        );

        if ($right_type_assertions) {
            $right_changed_var_ids = [];

            Reconciler::reconcileKeyedTypes(
                $right_type_assertions,
                $active_right_type_assertions,
                $right_context->vars_in_scope,
                $right_context->references_in_scope,
                $right_changed_var_ids,
                $right_referenced_var_ids,
                $statements_analyzer,
                [],
                $left_context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt->right),
                $context->inside_negation
            );
        }

        if (!($stmt->right instanceof PhpParser\Node\Expr\Exit_)) {
            foreach ($right_context->vars_in_scope as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $context->vars_in_scope[$var_id],
                        $type,
                        $codebase
                    );
                }
            }
        } elseif ($stmt->left instanceof PhpParser\Node\Expr\Assign) {
            $var_id = ExpressionIdentifier::getVarId($stmt->left->var, $context->self);

            if ($var_id && isset($left_context->vars_in_scope[$var_id])) {
                $left_inferred_reconciled = AssertionReconciler::reconcile(
                    new Truthy(),
                    clone $left_context->vars_in_scope[$var_id],
                    '',
                    $statements_analyzer,
                    $context->inside_loop,
                    [],
                    new CodeLocation($statements_analyzer->getSource(), $stmt->left),
                    $statements_analyzer->getSuppressedIssues()
                );

                $context->vars_in_scope[$var_id] = $left_inferred_reconciled;
            }
        }

        if ($context->inside_conditional) {
            $context->updateChecks($right_context);
        }

        $context->cond_referenced_var_ids = array_merge(
            $right_context->cond_referenced_var_ids,
            $context->cond_referenced_var_ids
        );

        $context->assigned_var_ids = array_merge(
            $context->assigned_var_ids,
            $right_context->assigned_var_ids
        );

        if ($context->if_body_context) {
            $if_body_context = $context->if_body_context;

            foreach ($right_context->vars_in_scope as $var_id => $type) {
                if (isset($if_body_context->vars_in_scope[$var_id])) {
                    $if_body_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $type,
                        $if_body_context->vars_in_scope[$var_id],
                        $codebase
                    );
                } elseif (isset($left_context->vars_in_scope[$var_id])) {
                    $if_body_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $type,
                        $left_context->vars_in_scope[$var_id],
                        $codebase
                    );
                }
            }

            $if_body_context->cond_referenced_var_ids = array_merge(
                $context->cond_referenced_var_ids,
                $if_body_context->cond_referenced_var_ids
            );

            $if_body_context->assigned_var_ids = array_merge(
                $context->assigned_var_ids,
                $if_body_context->assigned_var_ids
            );

            $if_body_context->updateChecks($context);
        }

        $context->vars_possibly_in_scope = array_merge(
            $right_context->vars_possibly_in_scope,
            $context->vars_possibly_in_scope
        );

        return true;
    }
}
