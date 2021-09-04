<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Scope\IfConditionalScope;
use Psalm\Internal\Scope\IfScope;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\RedundantConditionGivenDocblockType;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Reconciler;

use function array_diff_key;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function count;

class IfConditionalAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $cond,
        Context $outer_context,
        Codebase $codebase,
        IfScope $if_scope,
        ?int $branch_point
    ): IfConditionalScope {
        $entry_clauses = [];

        // used when evaluating elseifs
        if ($if_scope->negated_clauses) {
            $entry_clauses = array_merge($outer_context->clauses, $if_scope->negated_clauses);

            $changed_var_ids = [];

            if ($if_scope->negated_types) {
                $vars_reconciled = Reconciler::reconcileKeyedTypes(
                    $if_scope->negated_types,
                    [],
                    $outer_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    [],
                    $outer_context->inside_loop,
                    new CodeLocation(
                        $statements_analyzer->getSource(),
                        $cond instanceof PhpParser\Node\Expr\BooleanNot
                            ? $cond->expr
                            : $cond,
                        $outer_context->include_location,
                        false
                    )
                );

                if ($changed_var_ids) {
                    $outer_context = clone $outer_context;
                    $outer_context->vars_in_scope = $vars_reconciled;

                    $entry_clauses = array_values(
                        array_filter(
                            $entry_clauses,
                            function (Clause $c) use ($changed_var_ids): bool {
                                return count($c->possibilities) > 1
                                    || $c->wedge
                                    || !isset($changed_var_ids[array_keys($c->possibilities)[0]]);
                            }
                        )
                    );
                }
            }
        }

        // get the first expression in the if, which should be evaluated on its own
        // this allows us to update the context of $matches in
        // if (!preg_match('/a/', 'aa', $matches)) {
        //   exit
        // }
        // echo $matches[0];
        $externally_applied_if_cond_expr = self::getDefinitelyEvaluatedExpressionAfterIf($cond);

        $internally_applied_if_cond_expr = self::getDefinitelyEvaluatedExpressionInsideIf($cond);

        $was_inside_conditional = $outer_context->inside_conditional;

        $outer_context->inside_conditional = true;

        $pre_condition_vars_in_scope = $outer_context->vars_in_scope;

        $referenced_var_ids = $outer_context->referenced_var_ids;
        $outer_context->referenced_var_ids = [];

        $pre_assigned_var_ids = $outer_context->assigned_var_ids;
        $outer_context->assigned_var_ids = [];

        $if_context = null;

        if ($internally_applied_if_cond_expr !== $externally_applied_if_cond_expr) {
            $if_context = clone $outer_context;
        }

        if ($externally_applied_if_cond_expr) {
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $externally_applied_if_cond_expr,
                $outer_context
            ) === false) {
                throw new \Psalm\Exception\ScopeAnalysisException();
            }
        }

        $first_cond_assigned_var_ids = $outer_context->assigned_var_ids;
        $outer_context->assigned_var_ids = array_merge(
            $pre_assigned_var_ids,
            $first_cond_assigned_var_ids
        );

        $first_cond_referenced_var_ids = $outer_context->referenced_var_ids;
        $outer_context->referenced_var_ids = array_merge(
            $referenced_var_ids,
            $first_cond_referenced_var_ids
        );

        if (!$was_inside_conditional) {
            $outer_context->inside_conditional = false;
        }

        if (!$if_context) {
            $if_context = clone $outer_context;
        }

        $if_conditional_context = clone $if_context;
        $if_conditional_context->if_context = $if_context;
        $if_conditional_context->if_scope = $if_scope;

        if ($codebase->alter_code) {
            $if_context->branch_point = $branch_point;
        }

        // we need to clone the current context so our ongoing updates
        // to $outer_context don't mess with elseif/else blocks
        $post_if_context = clone $outer_context;

        if ($internally_applied_if_cond_expr !== $cond
            || $externally_applied_if_cond_expr !== $cond
        ) {
            $assigned_var_ids = $first_cond_assigned_var_ids;
            $if_conditional_context->assigned_var_ids = [];

            $referenced_var_ids = $first_cond_referenced_var_ids;
            $if_conditional_context->referenced_var_ids = [];

            $if_conditional_context->inside_conditional = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $cond, $if_conditional_context) === false) {
                throw new \Psalm\Exception\ScopeAnalysisException();
            }

            $if_conditional_context->inside_conditional = false;

            /** @var array<string, bool> */
            $more_cond_referenced_var_ids = $if_conditional_context->referenced_var_ids;
            $if_conditional_context->referenced_var_ids = array_merge(
                $more_cond_referenced_var_ids,
                $referenced_var_ids
            );

            $cond_referenced_var_ids = array_merge(
                $first_cond_referenced_var_ids,
                $more_cond_referenced_var_ids
            );

            /** @var array<string, int> */
            $more_cond_assigned_var_ids = $if_conditional_context->assigned_var_ids;
            $if_conditional_context->assigned_var_ids = array_merge(
                $more_cond_assigned_var_ids,
                $assigned_var_ids
            );

            $assigned_in_conditional_var_ids = array_merge(
                $first_cond_assigned_var_ids,
                $more_cond_assigned_var_ids
            );
        } else {
            $cond_referenced_var_ids = $first_cond_referenced_var_ids;

            $assigned_in_conditional_var_ids = $first_cond_assigned_var_ids;
        }

        $newish_var_ids = array_map(
            /**
             * @param Type\Union $_
             *
             * @return true
             */
            function (Type\Union $_): bool {
                return true;
            },
            array_diff_key(
                $if_conditional_context->vars_in_scope,
                $pre_condition_vars_in_scope,
                $cond_referenced_var_ids,
                $assigned_in_conditional_var_ids
            )
        );

        $cond_type = $statements_analyzer->node_data->getType($cond);

        if ($cond_type !== null) {
            if ($cond_type->isAlwaysFalsy()) {
                if ($cond_type->from_docblock) {
                    if (IssueBuffer::accepts(
                        new DocblockTypeContradiction(
                            'if (false) is impossible',
                            new CodeLocation($statements_analyzer, $cond),
                            'false falsy'
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainType(
                            'if (false) is impossible',
                            new CodeLocation($statements_analyzer, $cond),
                            'false falsy'
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            } elseif ($cond_type->isTrue()) {
                if ($cond_type->from_docblock) {
                    if (IssueBuffer::accepts(
                        new RedundantConditionGivenDocblockType(
                            'if (true) is redundant',
                            new CodeLocation($statements_analyzer, $cond),
                            'true falsy'
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new RedundantCondition(
                            'if (true) is redundant',
                            new CodeLocation($statements_analyzer, $cond),
                            'true falsy'
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        // get all the var ids that were referenced in the conditional, but not assigned in it
        $cond_referenced_var_ids = array_diff_key($cond_referenced_var_ids, $assigned_in_conditional_var_ids);

        $cond_referenced_var_ids = array_merge($newish_var_ids, $cond_referenced_var_ids);

        return new \Psalm\Internal\Scope\IfConditionalScope(
            $if_context,
            $post_if_context,
            $cond_referenced_var_ids,
            $assigned_in_conditional_var_ids,
            $entry_clauses
        );
    }

    /**
     * Returns statements that are definitely evaluated before any statements after the end of the
     * if/elseif/else blocks
     */
    private static function getDefinitelyEvaluatedExpressionAfterIf(PhpParser\Node\Expr $stmt): ?PhpParser\Node\Expr
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
        ) {
            if ($stmt->left instanceof PhpParser\Node\Expr\ConstFetch
                && $stmt->left->name->parts === ['true']
            ) {
                return self::getDefinitelyEvaluatedExpressionAfterIf($stmt->right);
            }

            if ($stmt->right instanceof PhpParser\Node\Expr\ConstFetch
                && $stmt->right->name->parts === ['true']
            ) {
                return self::getDefinitelyEvaluatedExpressionAfterIf($stmt->left);
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalXor
            ) {
                return self::getDefinitelyEvaluatedExpressionAfterIf($stmt->left);
            }

            return $stmt;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            $inner_stmt = self::getDefinitelyEvaluatedExpressionInsideIf($stmt->expr);

            if ($inner_stmt !== $stmt->expr) {
                return $inner_stmt;
            }
        }

        return $stmt;
    }

    /**
     * Returns statements that are definitely evaluated before any statements inside
     * the if block
     */
    private static function getDefinitelyEvaluatedExpressionInsideIf(PhpParser\Node\Expr $stmt): ?PhpParser\Node\Expr
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
        ) {
            if ($stmt->left instanceof PhpParser\Node\Expr\ConstFetch
                && $stmt->left->name->parts === ['true']
            ) {
                return self::getDefinitelyEvaluatedExpressionInsideIf($stmt->right);
            }

            if ($stmt->right instanceof PhpParser\Node\Expr\ConstFetch
                && $stmt->right->name->parts === ['true']
            ) {
                return self::getDefinitelyEvaluatedExpressionInsideIf($stmt->left);
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalXor
            ) {
                return self::getDefinitelyEvaluatedExpressionInsideIf($stmt->left);
            }

            return $stmt;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            $inner_stmt = self::getDefinitelyEvaluatedExpressionAfterIf($stmt->expr);

            if ($inner_stmt !== $stmt->expr) {
                return $inner_stmt;
            }
        }

        return $stmt;
    }
}
