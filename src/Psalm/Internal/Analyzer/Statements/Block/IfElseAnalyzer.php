<?php

namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\ComplicatedExpressionException;
use Psalm\Exception\ScopeAnalysisException;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfElse\ElseAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfElse\ElseIfAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfElse\IfAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Scope\IfScope;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualBooleanNot;
use Psalm\Type;
use Psalm\Type\Reconciler;

use function array_diff;
use function array_filter;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function preg_match;
use function preg_quote;
use function spl_object_id;
use function substr;

/**
 * @internal
 */
final class IfElseAnalyzer
{
    /**
     * System of type substitution and deletion
     *
     * for example
     *
     * x: A|null
     *
     * if (x)
     *   (x: A)
     *   x = B  -- effects: remove A from the type of x, add B
     * else
     *   (x: null)
     *   x = C  -- effects: remove null from the type of x, add C
     *
     *
     * x: A|null
     *
     * if (!x)
     *   (x: null)
     *   throw new Exception -- effects: remove null from the type of x
     *
     * @return null|false
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\If_ $stmt,
        Context $context
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        $if_scope = new IfScope();

        // We need to clone the original context for later use if we're exiting in this if conditional
        if ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp
            || ($stmt->cond instanceof PhpParser\Node\Expr\BooleanNot
                && $stmt->cond->expr instanceof PhpParser\Node\Expr\BinaryOp)
        ) {
            $final_actions = ScopeAnalyzer::getControlActions(
                $stmt->stmts,
                null,
                [],
            );

            $has_leaving_statements = $final_actions === [ScopeAnalyzer::ACTION_END]
                || (count($final_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $final_actions, true));

            if ($has_leaving_statements) {
                $if_scope->post_leaving_if_context = clone $context;
            }
        }

        try {
            $if_conditional_scope = IfConditionalAnalyzer::analyze(
                $statements_analyzer,
                $stmt->cond,
                $context,
                $codebase,
                $if_scope,
                $context->branch_point ?: (int) $stmt->getAttribute('startFilePos'),
            );

            // this is the context for stuff that happens within the `if` block
            $if_context = $if_conditional_scope->if_context;

            // this is the context for stuff that happens after the `if` block
            $post_if_context = $if_conditional_scope->post_if_context;
            $assigned_in_conditional_var_ids = $if_conditional_scope->assigned_in_conditional_var_ids;
        } catch (ScopeAnalysisException $e) {
            return false;
        }

        $mixed_var_ids = [];

        foreach ($if_context->vars_in_scope as $var_id => $type) {
            if ($type->isMixed() && isset($context->vars_in_scope[$var_id])) {
                $mixed_var_ids[] = $var_id;
            }
        }

        $cond_object_id = spl_object_id($stmt->cond);

        $if_clauses = FormulaGenerator::getFormula(
            $cond_object_id,
            $cond_object_id,
            $stmt->cond,
            $context->self,
            $statements_analyzer,
            $codebase,
        );

        if (count($if_clauses) > 200) {
            $if_clauses = [];
        }

        $if_clauses_handled = [];
        foreach ($if_clauses as $clause) {
            $keys = array_keys($clause->possibilities);

            $mixed_var_ids = array_diff($mixed_var_ids, $keys);

            foreach ($keys as $key) {
                foreach ($mixed_var_ids as $mixed_var_id) {
                    if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                        $clause = new Clause([], $cond_object_id, $cond_object_id, true);
                        break 2;
                    }
                }
            }

            $if_clauses_handled[] = $clause;
        }

        $if_clauses = $if_clauses_handled;

        $entry_clauses = $context->clauses;

        // this will see whether any of the clauses in set A conflict with the clauses in set B
        AlgebraAnalyzer::checkForParadox(
            $context->clauses,
            $if_clauses,
            $statements_analyzer,
            $stmt->cond,
            $assigned_in_conditional_var_ids,
        );

        $if_clauses = Algebra::simplifyCNF($if_clauses);

        $if_context_clauses = [...$entry_clauses, ...$if_clauses];

        $if_context->clauses = $entry_clauses
            ? Algebra::simplifyCNF($if_context_clauses)
            : $if_context_clauses;

        if ($if_context->reconciled_expression_clauses) {
            $reconciled_expression_clauses = $if_context->reconciled_expression_clauses;

            $if_context->clauses = array_values(
                array_filter(
                    $if_context->clauses,
                    static fn(Clause $c): bool => !in_array($c->hash, $reconciled_expression_clauses),
                ),
            );

            if (count($if_context->clauses) === 1
                && $if_context->clauses[0]->wedge
                && !$if_context->clauses[0]->possibilities
            ) {
                $if_context->clauses = [];
                $if_context->reconciled_expression_clauses = [];
            }
        }

        // define this before we alter local clauses after reconciliation
        $if_scope->reasonable_clauses = $if_context->clauses;

        try {
            $if_scope->negated_clauses = Algebra::negateFormula($if_clauses);
        } catch (ComplicatedExpressionException $e) {
            try {
                $if_scope->negated_clauses = FormulaGenerator::getFormula(
                    $cond_object_id,
                    $cond_object_id,
                    new VirtualBooleanNot($stmt->cond),
                    $context->self,
                    $statements_analyzer,
                    $codebase,
                    false,
                );
            } catch (ComplicatedExpressionException $e) {
                $if_scope->negated_clauses = [];
            }
        }

        $if_scope->negated_types = Algebra::getTruthsFromFormula(
            Algebra::simplifyCNF(
                [...$context->clauses, ...$if_scope->negated_clauses],
            ),
        );

        $temp_else_context = clone $post_if_context;

        $changed_var_ids = [];

        if ($if_scope->negated_types) {
            [$temp_else_context->vars_in_scope, $temp_else_context->references_in_scope] =
                Reconciler::reconcileKeyedTypes(
                    $if_scope->negated_types,
                    [],
                    $temp_else_context->vars_in_scope,
                    $temp_else_context->references_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    $statements_analyzer->getTemplateTypeMap() ?: [],
                    $context->inside_loop,
                    $context->check_variables
                        ? new CodeLocation(
                            $statements_analyzer->getSource(),
                            $stmt->cond instanceof PhpParser\Node\Expr\BooleanNot
                                ? $stmt->cond->expr
                                : $stmt->cond,
                            $context->include_location,
                        ) : null,
                );
        }

        // we calculate the vars redefined in a hypothetical else statement to determine
        // which vars of the if we can safely change
        $pre_assignment_else_redefined_vars = array_intersect_key(
            $temp_else_context->getRedefinedVars($context->vars_in_scope, true),
            $changed_var_ids,
        );

        // check the if
        if (IfAnalyzer::analyze(
            $statements_analyzer,
            $stmt,
            $if_scope,
            $if_conditional_scope,
            $if_context,
            $context,
            $pre_assignment_else_redefined_vars,
        ) === false) {
            return false;
        }

        // this has to go on a separate line because the phar compactor messes with precedence
        $scope_to_clone = $if_scope->post_leaving_if_context ?? $post_if_context;
        $else_context = clone $scope_to_clone;
        $else_context->clauses = Algebra::simplifyCNF(
            [...$else_context->clauses, ...$if_scope->negated_clauses],
        );

        // check the elseifs
        foreach ($stmt->elseifs as $elseif) {
            if (ElseIfAnalyzer::analyze(
                $statements_analyzer,
                $elseif,
                $if_scope,
                $else_context,
                $context,
                $codebase,
                $else_context->branch_point ?: (int) $stmt->getAttribute('startFilePos'),
            ) === false) {
                return false;
            }
        }

        if ($stmt->else) {
            if ($codebase->alter_code) {
                $else_context->branch_point =
                    $else_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
            }
        }

        if (ElseAnalyzer::analyze(
            $statements_analyzer,
            $stmt->else,
            $if_scope,
            $else_context,
            $context,
        ) === false) {
            return false;
        }

        if (count($if_scope->if_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $if_scope->if_actions, true)
            && !$stmt->elseifs
        ) {
            $context->clauses = $else_context->clauses;
            foreach ($else_context->vars_in_scope as $var_id => $type) {
                $context->vars_in_scope[$var_id] = $type;
            }

            foreach ($pre_assignment_else_redefined_vars as $var_id => $reconciled_type) {
                $first_appearance = $statements_analyzer->getFirstAppearance($var_id);

                if ($first_appearance
                    && isset($post_if_context->vars_in_scope[$var_id])
                    && $post_if_context->vars_in_scope[$var_id]->hasMixed()
                    && !$reconciled_type->hasMixed()
                ) {
                    if (!$post_if_context->collect_initializations
                        && !$post_if_context->collect_mutations
                        && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    ) {
                        $parent_source = $statements_analyzer->getSource();

                        $functionlike_storage = $parent_source instanceof FunctionLikeAnalyzer
                            ? $parent_source->getFunctionLikeStorage($statements_analyzer)
                            : null;

                        if (!$functionlike_storage
                                || (!$parent_source->getSource() instanceof TraitAnalyzer
                                    && !isset($functionlike_storage->param_lookup[substr($var_id, 1)]))
                        ) {
                            $codebase = $statements_analyzer->getCodebase();
                            $codebase->analyzer->decrementMixedCount($statements_analyzer->getFilePath());
                        }
                    }

                    IssueBuffer::remove(
                        $statements_analyzer->getFilePath(),
                        'MixedAssignment',
                        $first_appearance->raw_file_start,
                    );
                }
            }
        }

        if ($context->loop_scope) {
            $context->loop_scope->final_actions = array_unique(
                array_merge(
                    $context->loop_scope->final_actions,
                    $if_scope->final_actions,
                ),
            );
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $if_scope->new_vars_possibly_in_scope,
        );

        $context->possibly_assigned_var_ids = array_merge(
            $context->possibly_assigned_var_ids,
            $if_scope->possibly_assigned_var_ids ?: [],
        );

        // vars can only be defined/redefined if there was an else (defined in every block)
        $context->assigned_var_ids = array_merge(
            $context->assigned_var_ids,
            $if_scope->assigned_var_ids ?: [],
        );

        if ($if_scope->new_vars) {
            foreach ($if_scope->new_vars as $var_id => &$type) {
                if (isset($context->vars_possibly_in_scope[$var_id])
                    && $statements_analyzer->data_flow_graph
                ) {
                    $type = $type->addParentNodes(
                        $statements_analyzer->getParentNodesForPossiblyUndefinedVariable($var_id),
                    );
                }

                $context->vars_in_scope[$var_id] = $type;
            }
            unset($type);
        }

        if ($if_scope->redefined_vars) {
            foreach ($if_scope->redefined_vars as $var_id => $type) {
                $context->vars_in_scope[$var_id] = $type;
                $if_scope->updated_vars[$var_id] = true;

                if ($if_scope->reasonable_clauses) {
                    $if_scope->reasonable_clauses = Context::filterClauses(
                        $var_id,
                        $if_scope->reasonable_clauses,
                        $context->vars_in_scope[$var_id] ?? null,
                        $statements_analyzer,
                    );
                }
            }
        }

        if ($if_scope->reasonable_clauses
            && (count($if_scope->reasonable_clauses) > 1 || !$if_scope->reasonable_clauses[0]->wedge)
        ) {
            $context->clauses = Algebra::simplifyCNF(
                [...$if_scope->reasonable_clauses, ...$context->clauses],
            );
        }

        if ($if_scope->possibly_redefined_vars) {
            foreach ($if_scope->possibly_redefined_vars as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    if (!$type->failed_reconciliation
                        && !isset($if_scope->updated_vars[$var_id])
                    ) {
                        $combined_type = Type::combineUnionTypes(
                            $context->vars_in_scope[$var_id],
                            $type,
                            $codebase,
                        );

                        if (!$combined_type->equals($context->vars_in_scope[$var_id])) {
                            $context->removeDescendents($var_id, $combined_type);
                        }

                        $context->vars_in_scope[$var_id] = $combined_type;
                    } else {
                        $context->vars_in_scope[$var_id] =
                            $context->vars_in_scope[$var_id]->addParentNodes($type->parent_nodes);
                    }
                }
            }
        }

        if (!in_array(ScopeAnalyzer::ACTION_NONE, $if_scope->final_actions, true)) {
            $context->has_returned = true;
        }

        return null;
    }
}
