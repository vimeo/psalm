<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Context;
use Psalm\Internal\Scope\LoopScope;
use Psalm\Type;
use Psalm\Type\Algebra;

class DoAnalyzer
{
    /**
     * @return void
     */
    public static function analyze(
        StatementsAnalyzer $statements_checker,
        PhpParser\Node\Stmt\Do_ $stmt,
        Context $context
    ) {
        $do_context = clone $context;

        $do_context->inside_case = false;

        $codebase = $statements_checker->getCodebase();

        if ($codebase->alter_code) {
            $do_context->branch_point = $do_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
        }

        $loop_scope = new LoopScope($do_context, $context);
        $loop_scope->protected_var_ids = $context->protected_var_ids;

        $suppressed_issues = $statements_checker->getSuppressedIssues();

        if (!in_array('RedundantCondition', $suppressed_issues, true)) {
            $statements_checker->addSuppressedIssues(['RedundantCondition']);
        }
        if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
            $statements_checker->addSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }
        if (!in_array('TypeDoesNotContainType', $suppressed_issues, true)) {
            $statements_checker->addSuppressedIssues(['TypeDoesNotContainType']);
        }

        $do_context->loop_scope = $loop_scope;

        $statements_checker->analyze($stmt->stmts, $do_context);

        if (!in_array('RedundantCondition', $suppressed_issues, true)) {
            $statements_checker->removeSuppressedIssues(['RedundantCondition']);
        }
        if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
            $statements_checker->removeSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }
        if (!in_array('TypeDoesNotContainType', $suppressed_issues, true)) {
            $statements_checker->removeSuppressedIssues(['TypeDoesNotContainType']);
        }

        $loop_scope->iteration_count++;

        foreach ($context->vars_in_scope as $var => $type) {
            if ($type->isMixed()) {
                continue;
            }

            if ($do_context->hasVariable($var)) {
                if ($do_context->vars_in_scope[$var]->getId() !== $type->getId()) {
                    $do_context->vars_in_scope[$var] = Type::combineUnionTypes($do_context->vars_in_scope[$var], $type);
                }
            }
        }

        $mixed_var_ids = [];

        foreach ($do_context->vars_in_scope as $var_id => $type) {
            if ($type->isMixed()) {
                $mixed_var_ids[] = $var_id;
            }
        }

        $while_clauses = Algebra::getFormula(
            $stmt->cond,
            $context->self,
            $statements_checker,
            $codebase
        );

        $while_clauses = array_values(
            array_filter(
                $while_clauses,
                /** @return bool */
                function (Clause $c) use ($mixed_var_ids) {
                    $keys = array_keys($c->possibilities);

                    foreach ($keys as $key) {
                        foreach ($mixed_var_ids as $mixed_var_id) {
                            if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                                return false;
                            }
                        }
                    }

                    return true;
                }
            )
        );

        if (!$while_clauses) {
            $while_clauses = [new Clause([], true)];
        }

        $reconcilable_while_types = \Psalm\Type\Algebra::getTruthsFromFormula($while_clauses);

        if ($reconcilable_while_types) {
            $changed_var_ids = [];
            $while_vars_in_scope_reconciled =
                Type\Reconciler::reconcileKeyedTypes(
                    $reconcilable_while_types,
                    $do_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_checker,
                    new \Psalm\CodeLocation($statements_checker->getSource(), $stmt->cond),
                    $statements_checker->getSuppressedIssues()
                );

            $do_context->vars_in_scope = $while_vars_in_scope_reconciled;
        }

        $do_cond_context = clone $do_context;

        if (!in_array('RedundantCondition', $suppressed_issues, true)) {
            $statements_checker->addSuppressedIssues(['RedundantCondition']);
        }
        if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
            $statements_checker->addSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }

        ExpressionAnalyzer::analyze($statements_checker, $stmt->cond, $do_cond_context);

        if (!in_array('RedundantCondition', $suppressed_issues, true)) {
            $statements_checker->removeSuppressedIssues(['RedundantCondition']);
        }
        if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
            $statements_checker->removeSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }

        if ($context->collect_references) {
            $do_context->unreferenced_vars = $do_cond_context->unreferenced_vars;
        }

        foreach ($do_cond_context->vars_in_scope as $var_id => $type) {
            if (isset($context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes($context->vars_in_scope[$var_id], $type);
            }
        }

        LoopAnalyzer::analyze(
            $statements_checker,
            $stmt->stmts,
            [$stmt->cond],
            [],
            $loop_scope,
            $inner_loop_context,
            true
        );

        // because it's a do {} while, inner loop vars belong to the main context
        if (!$inner_loop_context) {
            throw new \UnexpectedValueException('Should never be null');
        }

        $negated_while_clauses = Algebra::negateFormula($while_clauses);

        $negated_while_types = Algebra::getTruthsFromFormula(
            Algebra::simplifyCNF(
                array_merge($context->clauses, $negated_while_clauses)
            )
        );

        ExpressionAnalyzer::analyze($statements_checker, $stmt->cond, $inner_loop_context);

        if ($negated_while_types) {
            $changed_var_ids = [];

            $inner_loop_context->vars_in_scope =
                Type\Reconciler::reconcileKeyedTypes(
                    $negated_while_types,
                    $inner_loop_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_checker,
                    new \Psalm\CodeLocation($statements_checker->getSource(), $stmt->cond),
                    $statements_checker->getSuppressedIssues()
                );
        }

        foreach ($inner_loop_context->vars_in_scope as $var_id => $type) {
            // if there are break statements in the loop it's not certain
            // that the loop has finished executing, so the assertions at the end
            // the loop in the while conditional may not hold
            if (in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true)) {
                if (isset($loop_scope->possibly_defined_loop_parent_vars[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $type,
                        $loop_scope->possibly_defined_loop_parent_vars[$var_id]
                    );
                }
            } else {
                $context->vars_in_scope[$var_id] = $type;
            }
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $do_context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $do_context->referenced_var_ids
        );

        if ($context->collect_references) {
            $context->unreferenced_vars = $do_context->unreferenced_vars;
        }

        if ($context->collect_exceptions) {
            $context->possibly_thrown_exceptions += $inner_loop_context->possibly_thrown_exceptions;
        }
    }
}
