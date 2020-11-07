<?php
namespace Psalm\Internal\Analyzer\Statements\Block\IfElse;

use PhpParser;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ConflictingReferenceConstraint;
use Psalm\IssueBuffer;
use Psalm\Internal\Scope\IfScope;
use Psalm\Internal\Scope\IfConditionalScope;
use Psalm\Type;
use Psalm\Internal\Algebra;
use Psalm\Type\Reconciler;
use function array_merge;
use function array_diff_key;
use function array_keys;
use function array_unique;
use function count;
use function in_array;
use function array_intersect;
use function strpos;
use function substr;

class IfAnalyzer
{
    /**
     * @param  array<string,Type\Union> $pre_assignment_else_redefined_vars
     *
     * @return false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\If_ $stmt,
        IfScope $if_scope,
        IfConditionalScope $if_conditional_scope,
        Context $if_context,
        Context $old_if_context,
        Context $outer_context,
        array $pre_assignment_else_redefined_vars
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        $if_context->parent_context = $outer_context;

        $assigned_var_ids = $if_context->assigned_var_ids;
        $possibly_assigned_var_ids = $if_context->possibly_assigned_var_ids;
        $if_context->assigned_var_ids = [];
        $if_context->possibly_assigned_var_ids = [];

        if ($statements_analyzer->analyze(
            $stmt->stmts,
            $if_context
        ) === false
        ) {
            return false;
        }

        $final_actions = ScopeAnalyzer::getControlActions(
            $stmt->stmts,
            $statements_analyzer->node_data,
            $codebase->config->exit_functions,
            $outer_context->break_types
        );

        $has_ending_statements = $final_actions === [ScopeAnalyzer::ACTION_END];

        $has_leaving_statements = $has_ending_statements
            || (count($final_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $final_actions, true));

        $has_break_statement = $final_actions === [ScopeAnalyzer::ACTION_BREAK];
        $has_continue_statement = $final_actions === [ScopeAnalyzer::ACTION_CONTINUE];

        $if_scope->final_actions = $final_actions;

        /** @var array<string, int> */
        $new_assigned_var_ids = $if_context->assigned_var_ids;
        /** @var array<string, bool> */
        $new_possibly_assigned_var_ids = $if_context->possibly_assigned_var_ids;

        $if_context->assigned_var_ids = array_merge($assigned_var_ids, $new_assigned_var_ids);
        $if_context->possibly_assigned_var_ids = array_merge(
            $possibly_assigned_var_ids,
            $new_possibly_assigned_var_ids
        );

        foreach ($if_context->byref_constraints as $var_id => $byref_constraint) {
            if (isset($outer_context->byref_constraints[$var_id])
                && $byref_constraint->type
                && ($outer_constraint_type = $outer_context->byref_constraints[$var_id]->type)
                && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $byref_constraint->type,
                    $outer_constraint_type
                )
            ) {
                if (IssueBuffer::accepts(
                    new ConflictingReferenceConstraint(
                        'There is more than one pass-by-reference constraint on ' . $var_id,
                        new CodeLocation($statements_analyzer, $stmt, $outer_context->include_location, true)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                $outer_context->byref_constraints[$var_id] = $byref_constraint;
            }
        }

        $mic_drop = false;

        if (!$has_leaving_statements) {
            $if_scope->new_vars = array_diff_key($if_context->vars_in_scope, $outer_context->vars_in_scope);

            $if_scope->redefined_vars = $if_context->getRedefinedVars($outer_context->vars_in_scope);
            $if_scope->possibly_redefined_vars = $if_scope->redefined_vars;
            $if_scope->assigned_var_ids = $new_assigned_var_ids;
            $if_scope->possibly_assigned_var_ids = $new_possibly_assigned_var_ids;

            $changed_var_ids = $new_assigned_var_ids;

            // if the variable was only set in the conditional, it's not possibly redefined
            foreach ($if_scope->possibly_redefined_vars as $var_id => $_) {
                if (!isset($new_possibly_assigned_var_ids[$var_id])
                    && isset($if_scope->if_cond_changed_var_ids[$var_id])
                ) {
                    unset($if_scope->possibly_redefined_vars[$var_id]);
                }
            }

            if ($if_scope->reasonable_clauses) {
                // remove all reasonable clauses that would be negated by the if stmts
                foreach ($changed_var_ids as $var_id => $_) {
                    $if_scope->reasonable_clauses = Context::filterClauses(
                        $var_id,
                        $if_scope->reasonable_clauses,
                        isset($if_context->vars_in_scope[$var_id]) ? $if_context->vars_in_scope[$var_id] : null,
                        $statements_analyzer
                    );
                }
            }
        } else {
            if (!$has_break_statement) {
                $if_scope->reasonable_clauses = [];
            }
        }

        if ($has_leaving_statements && !$has_break_statement && !$stmt->else && !$stmt->elseifs) {
            // If we're assigning inside
            if ($if_conditional_scope->cond_assigned_var_ids
                && $if_scope->mic_drop_context
            ) {
                self::addConditionallyAssignedVarsToContext(
                    $statements_analyzer,
                    $stmt->cond,
                    $if_scope->mic_drop_context,
                    $outer_context,
                    $if_conditional_scope->cond_assigned_var_ids
                );
            }

            if ($if_scope->negated_types) {
                $changed_var_ids = [];

                $outer_context_vars_reconciled = Reconciler::reconcileKeyedTypes(
                    $if_scope->negated_types,
                    [],
                    $outer_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    $statements_analyzer->getTemplateTypeMap() ?: [],
                    $outer_context->inside_loop,
                    new CodeLocation(
                        $statements_analyzer->getSource(),
                        $stmt->cond instanceof PhpParser\Node\Expr\BooleanNot
                            ? $stmt->cond->expr
                            : $stmt->cond,
                        $outer_context->include_location,
                        false
                    )
                );

                foreach ($changed_var_ids as $changed_var_id => $_) {
                    $outer_context->removeVarFromConflictingClauses($changed_var_id);
                }

                $changed_var_ids += $new_assigned_var_ids;

                foreach ($changed_var_ids as $var_id => $_) {
                    $if_scope->negated_clauses = Context::filterClauses(
                        $var_id,
                        $if_scope->negated_clauses
                    );
                }

                foreach ($changed_var_ids as $var_id => $_) {
                    $first_appearance = $statements_analyzer->getFirstAppearance($var_id);

                    if ($first_appearance
                        && isset($outer_context->vars_in_scope[$var_id])
                        && isset($outer_context_vars_reconciled[$var_id])
                        && $outer_context->vars_in_scope[$var_id]->hasMixed()
                        && !$outer_context_vars_reconciled[$var_id]->hasMixed()
                    ) {
                        if (!$outer_context->collect_initializations
                            && !$outer_context->collect_mutations
                            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                            && (!(($parent_source = $statements_analyzer->getSource())
                                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                        ) {
                            $codebase->analyzer->decrementMixedCount($statements_analyzer->getFilePath());
                        }

                        IssueBuffer::remove(
                            $statements_analyzer->getFilePath(),
                            'MixedAssignment',
                            $first_appearance->raw_file_start
                        );
                    }
                }

                $outer_context->vars_in_scope = $outer_context_vars_reconciled;
                $mic_drop = true;
            }

            $outer_context->clauses = Algebra::simplifyCNF(
                array_merge($outer_context->clauses, $if_scope->negated_clauses)
            );
        }

        // update the parent context as necessary, but only if we can safely reason about type negation.
        // We only update vars that changed both at the start of the if block and then again by an assignment
        // in the if statement.
        if ($if_scope->negated_types && !$mic_drop) {
            $vars_to_update = array_intersect(
                array_keys($pre_assignment_else_redefined_vars),
                array_keys($if_scope->negated_types)
            );

            $extra_vars_to_update = [];

            // if there's an object-like array in there, we also need to update the root array variable
            foreach ($vars_to_update as $var_id) {
                $bracked_pos = strpos($var_id, '[');
                if ($bracked_pos !== false) {
                    $extra_vars_to_update[] = substr($var_id, 0, $bracked_pos);
                }
            }

            if ($extra_vars_to_update) {
                $vars_to_update = array_unique(array_merge($extra_vars_to_update, $vars_to_update));
            }

            //update $if_context vars to include the pre-assignment else vars
            if (!$stmt->else && !$has_leaving_statements) {
                foreach ($pre_assignment_else_redefined_vars as $var_id => $type) {
                    if (isset($if_context->vars_in_scope[$var_id])) {
                        $if_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $if_context->vars_in_scope[$var_id],
                            $type,
                            $codebase
                        );
                    }
                }
            }

            $outer_context->update(
                $old_if_context,
                $if_context,
                $has_leaving_statements,
                $vars_to_update,
                $if_scope->updated_vars
            );
        }

        if (!$has_ending_statements) {
            $vars_possibly_in_scope = array_diff_key(
                $if_context->vars_possibly_in_scope,
                $outer_context->vars_possibly_in_scope
            );

            if ($if_context->loop_scope) {
                if (!$has_continue_statement && !$has_break_statement) {
                    $if_scope->new_vars_possibly_in_scope = $vars_possibly_in_scope;
                }

                $if_context->loop_scope->vars_possibly_in_scope = array_merge(
                    $vars_possibly_in_scope,
                    $if_context->loop_scope->vars_possibly_in_scope
                );
            } elseif (!$has_leaving_statements) {
                $if_scope->new_vars_possibly_in_scope = $vars_possibly_in_scope;
            }
        }

        if ($outer_context->collect_exceptions) {
            $outer_context->mergeExceptions($if_context);
        }

        return null;
    }

    /**
     * @param array<string, int> $cond_assigned_var_ids
     */
    public static function addConditionallyAssignedVarsToContext(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $cond,
        Context $mic_drop_context,
        Context $outer_context,
        array $cond_assigned_var_ids
    ) : void {
        // this filters out coercions to expeccted types in ArgumentAnalyzer
        $cond_assigned_var_ids = \array_filter($cond_assigned_var_ids);

        if (!$cond_assigned_var_ids) {
            return;
        }

        $exprs = self::getDefinitelyEvaluatedOredExpressions($cond);

        // if there was no assignment in the first expression it's safe to proceed
        $old_node_data = $statements_analyzer->node_data;
        $statements_analyzer->node_data = clone $old_node_data;

        IssueBuffer::startRecording();

        foreach ($exprs as $expr) {
            if ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
                $fake_not = new PhpParser\Node\Expr\BinaryOp\BooleanOr(
                    self::negateExpr($expr->left),
                    self::negateExpr($expr->right),
                    $expr->getAttributes()
                );
            } else {
                $fake_not = self::negateExpr($expr);
            }

            $fake_negated_expr = new PhpParser\Node\Expr\FuncCall(
                new PhpParser\Node\Name\FullyQualified('assert'),
                [new PhpParser\Node\Arg(
                    $fake_not,
                    false,
                    false,
                    $expr->getAttributes()
                )],
                $expr->getAttributes()
            );

            $mic_drop_context->inside_negation = !$mic_drop_context->inside_negation;

            ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $fake_negated_expr,
                $mic_drop_context
            );

            $mic_drop_context->inside_negation = !$mic_drop_context->inside_negation;
        }

        IssueBuffer::clearRecordingLevel();
        IssueBuffer::stopRecording();

        $statements_analyzer->node_data = $old_node_data;

        foreach ($cond_assigned_var_ids as $var_id => $_) {
            if (isset($mic_drop_context->vars_in_scope[$var_id])) {
                $outer_context->vars_in_scope[$var_id] = clone $mic_drop_context->vars_in_scope[$var_id];
            }
        }
    }

    /**
     * Returns all expressions inside an ored expression
     * @return non-empty-list<PhpParser\Node\Expr>
     */
    private static function getDefinitelyEvaluatedOredExpressions(PhpParser\Node\Expr $stmt): array
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalXor
        ) {
            return array_merge(
                self::getDefinitelyEvaluatedOredExpressions($stmt->left),
                self::getDefinitelyEvaluatedOredExpressions($stmt->right)
            );
        }

        return [$stmt];
    }

    private static function negateExpr(PhpParser\Node\Expr $expr) : PhpParser\Node\Expr
    {
        if ($expr instanceof PhpParser\Node\Expr\BooleanNot) {
            return $expr->expr;
        }

        return new PhpParser\Node\Expr\BooleanNot($expr, $expr->getAttributes());
    }
}
