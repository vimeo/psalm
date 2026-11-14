<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Block;

use Override;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Exception\ScopeAnalysisException;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Scope\IfConditionalScope;
use Psalm\Internal\Scope\IfScope;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\RedundantConditionGivenDocblockType;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_diff_key;
use function array_filter;
use function array_key_first;
use function array_merge;
use function array_values;
use function count;
use function explode;
use function in_array;
use function spl_object_id;

/**
 * @internal
 */
final class IfConditionalAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $cond,
        Context $outer_context,
        Codebase $codebase,
        IfScope $if_scope,
        int $branch_point,
    ): IfConditionalScope {
        $entry_clauses = [];

        // used when evaluating elseifs
        if ($if_scope->negated_clauses) {
            $entry_clauses = [...$outer_context->clauses, ...$if_scope->negated_clauses];

            $changed_var_ids = [];

            if ($if_scope->negated_types) {
                [$vars_reconciled, $references_reconciled] = Reconciler::reconcileKeyedTypes(
                    $if_scope->negated_types,
                    [],
                    $outer_context->vars_in_scope,
                    $outer_context->references_in_scope,
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
                        false,
                    ),
                );

                if ($changed_var_ids) {
                    $outer_context = clone $outer_context;
                    $outer_context->vars_in_scope = $vars_reconciled;
                    $outer_context->references_in_scope = $references_reconciled;

                    $entry_clauses = array_values(
                        array_filter(
                            $entry_clauses,
                            static fn(Clause $c): bool => count($c->possibilities) > 1
                                || $c->wedge
                                || (count($c->possibilities) > 0
                                    && !isset($changed_var_ids[array_key_first($c->possibilities)])
                                ),
                        ),
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

        $pre_condition_vars_in_scope = $outer_context->vars_in_scope;

        $referenced_var_ids = $outer_context->cond_referenced_var_ids;
        $outer_context->cond_referenced_var_ids = [];

        $pre_assigned_var_ids = $outer_context->assigned_var_ids;
        $outer_context->assigned_var_ids = [];

        $if_context = null;

        if ($internally_applied_if_cond_expr !== $externally_applied_if_cond_expr) {
            $if_context = clone $outer_context;
        }

        $was_inside_conditional = $outer_context->inside_conditional;

        $outer_context->inside_conditional = true;

        if (ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $externally_applied_if_cond_expr,
            $outer_context,
        ) === false) {
            throw new ScopeAnalysisException();
        }

        $first_cond_assigned_var_ids = $outer_context->assigned_var_ids;
        $outer_context->assigned_var_ids = array_merge(
            $pre_assigned_var_ids,
            $first_cond_assigned_var_ids,
        );

        $first_cond_referenced_var_ids = $outer_context->cond_referenced_var_ids;
        $outer_context->cond_referenced_var_ids = array_merge(
            $referenced_var_ids,
            $first_cond_referenced_var_ids,
        );

        $outer_context->inside_conditional = $was_inside_conditional;

        if (!$if_context) {
            $if_context = clone $outer_context;
        }

        $if_conditional_context = clone $if_context;

        // here we set up a context specifically for the statements in the first `if`, which can
        // be affected by statements in the if condition
        $if_conditional_context->if_body_context = $if_context;

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
            $if_conditional_context->cond_referenced_var_ids = [];

            $was_inside_conditional = $if_conditional_context->inside_conditional;

            $if_conditional_context->inside_conditional = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $cond, $if_conditional_context) === false) {
                throw new ScopeAnalysisException();
            }

            $if_conditional_context->inside_conditional = $was_inside_conditional;

            /** @var array<string, bool> */
            $more_cond_referenced_var_ids = $if_conditional_context->cond_referenced_var_ids;
            $if_conditional_context->cond_referenced_var_ids = array_merge(
                $more_cond_referenced_var_ids,
                $referenced_var_ids,
            );

            $cond_referenced_var_ids = array_merge(
                $first_cond_referenced_var_ids,
                $more_cond_referenced_var_ids,
            );

            /** @var array<string, int> */
            $more_cond_assigned_var_ids = $if_conditional_context->assigned_var_ids;
            $if_conditional_context->assigned_var_ids = array_merge(
                $more_cond_assigned_var_ids,
                $assigned_var_ids,
            );

            $assigned_in_conditional_var_ids = array_merge(
                $first_cond_assigned_var_ids,
                $more_cond_assigned_var_ids,
            );
        } else {
            $cond_referenced_var_ids = $first_cond_referenced_var_ids;

            $assigned_in_conditional_var_ids = $first_cond_assigned_var_ids;
        }

        // an assignment the condition may skip (the right of an `&&`) leaves the earlier type possible
        $assigns_by_var_id = [];

        foreach (self::getAssignmentsInCondition($cond) as $assign) {
            $assigned_var_id = ExpressionIdentifier::getExtendedVarId(
                $assign->var,
                $outer_context->self,
                $statements_analyzer,
            );
            $assigned_type = $statements_analyzer->node_data->getType($assign);

            if ($assigned_var_id !== null && $assigned_type !== null) {
                $assigns_by_var_id[$assigned_var_id][spl_object_id($assign)] = $assigned_type;
            }
        }

        foreach ($assigns_by_var_id as $assigned_var_id => $assigned_types) {
            if (!isset($post_if_context->vars_in_scope[$assigned_var_id])) {
                continue;
            }

            [$true_states, $false_states] = self::getAssignmentStates(
                $statements_analyzer,
                $cond,
                $assigned_var_id,
                $outer_context->self,
                ['earlier' => true],
            );

            if (isset($true_states['earlier']) || isset($false_states['earlier'])) {
                $post_if_context->vars_in_scope[$assigned_var_id] = Type::combineUnionTypeArray(
                    [$post_if_context->vars_in_scope[$assigned_var_id], ...array_values($assigned_types)],
                    $statements_analyzer->getCodebase(),
                );
                $outer_context->vars_in_scope[$assigned_var_id] = $post_if_context->vars_in_scope[$assigned_var_id];
            }

            // where the assignment may be skipped, the body only runs on the paths that make the condition true;
            // it only narrows what the analysis of the condition already left for the body
            $body_type = isset($true_states['earlier'])
                ? self::getTypeWhereConditionIsTrue(
                    $statements_analyzer,
                    $true_states,
                    $pre_condition_vars_in_scope[$assigned_var_id] ?? null,
                    $assigned_types,
                )
                : null;

            if ($body_type !== null
                && isset($if_context->vars_in_scope[$assigned_var_id])
                && UnionTypeComparator::isContainedBy(
                    $statements_analyzer->getCodebase(),
                    $body_type,
                    $if_context->vars_in_scope[$assigned_var_id],
                )
            ) {
                $if_context->vars_in_scope[$assigned_var_id] = $body_type;
            }
        }

        $newish_var_ids = [];

        foreach (array_diff_key(
            $if_conditional_context->vars_in_scope,
            $pre_condition_vars_in_scope,
            $cond_referenced_var_ids,
            $assigned_in_conditional_var_ids,
        ) as $name => $_value) {
            $newish_var_ids[$name] = true;
        }

        self::handleParadoxicalCondition($statements_analyzer, $cond, true);

        // get all the var ids that were referenced in the conditional, but not assigned in it
        $cond_referenced_var_ids = array_diff_key($cond_referenced_var_ids, $assigned_in_conditional_var_ids);

        $cond_referenced_var_ids = [...$newish_var_ids, ...$cond_referenced_var_ids];

        return new IfConditionalScope(
            $if_context,
            $post_if_context,
            $cond_referenced_var_ids,
            $assigned_in_conditional_var_ids,
            $entry_clauses,
        );
    }

    /**
     * Returns statements that are definitely evaluated before any statements after the end of the
     * if/elseif/else blocks
     */
    private static function getDefinitelyEvaluatedExpressionAfterIf(PhpParser\Node\Expr $stmt): PhpParser\Node\Expr
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
        ) {
            if ($stmt->left instanceof PhpParser\Node\Expr\ConstFetch
                && $stmt->left->name->getParts() === ['true']
            ) {
                return self::getDefinitelyEvaluatedExpressionAfterIf($stmt->right);
            }

            if ($stmt->right instanceof PhpParser\Node\Expr\ConstFetch
                && $stmt->right->name->getParts() === ['true']
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
    private static function getDefinitelyEvaluatedExpressionInsideIf(PhpParser\Node\Expr $stmt): PhpParser\Node\Expr
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
        ) {
            if ($stmt->left instanceof PhpParser\Node\Expr\ConstFetch
                && $stmt->left->name->getParts() === ['true']
            ) {
                return self::getDefinitelyEvaluatedExpressionInsideIf($stmt->right);
            }

            if ($stmt->right instanceof PhpParser\Node\Expr\ConstFetch
                && $stmt->right->name->getParts() === ['true']
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

    public static function handleParadoxicalCondition(
        StatementsAnalyzer  $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        bool $emit_redundant_with_assignation = false,
    ): void {
        $type = $statements_analyzer->node_data->getType($stmt);

        if ($type !== null) {
            if ($type->isAlwaysFalsy()) {
                if ($type->from_docblock) {
                    IssueBuffer::maybeAdd(
                        new DocblockTypeContradiction(
                            'Operand of type ' . $type->getId() . ' is always falsy',
                            new CodeLocation($statements_analyzer, $stmt),
                            $type->getId() . ' falsy',
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new TypeDoesNotContainType(
                            'Operand of type ' . $type->getId() . ' is always falsy',
                            new CodeLocation($statements_analyzer, $stmt),
                            $type->getId() . ' falsy',
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            } elseif ($type->isAlwaysTruthy() &&
                (!$stmt instanceof PhpParser\Node\Expr\Assign || $emit_redundant_with_assignation)
            ) {
                if ($type->from_docblock) {
                    IssueBuffer::maybeAdd(
                        new RedundantConditionGivenDocblockType(
                            'Operand of type ' . $type->getId() . ' is always truthy',
                            new CodeLocation($statements_analyzer, $stmt),
                            $type->getId() . ' falsy',
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new RedundantCondition(
                            'Operand of type ' . $type->getId() . ' is always truthy',
                            new CodeLocation($statements_analyzer, $stmt),
                            $type->getId() . ' falsy',
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            } elseif (!($stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical)
                && !($stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical)
                && !($stmt instanceof PhpParser\Node\Expr\BooleanNot)) {
                ExpressionAnalyzer::checkRiskyTruthyFalsyComparison($type, $statements_analyzer, $stmt);
            }
        }
    }

    /**
     * The type a variable the condition may assign has where the whole condition is true: the earlier type where
     * the assignment is skipped, the assigned type where it runs and nothing after it tests the variable, its
     * truthy part where the assignment is itself what the condition needs.
     *
     * @param array<string, true> $true_states see getAssignmentStates()
     * @param array<int, Union> $assigned_types the type of each assignment, by the id of its node
     * @return ?Union null when the paths can't be told
     */
    private static function getTypeWhereConditionIsTrue(
        StatementsAnalyzer $statements_analyzer,
        array $true_states,
        ?Union $earlier_type,
        array $assigned_types,
    ): ?Union {
        $types = [];

        foreach ($true_states as $state => $_) {
            if ($state === 'earlier') {
                if ($earlier_type === null) {
                    return null;
                }

                $types[] = $earlier_type;

                continue;
            }

            $state_parts = explode('#', $state);

            if (count($state_parts) !== 2) {
                return null;
            }

            [$kind, $assign_id] = $state_parts;
            $assigned_type = $assigned_types[(int) $assign_id] ?? null;

            if ($assigned_type === null) {
                return null;
            }

            if ($kind === 'full') {
                $types[] = $assigned_type;

                continue;
            }

            $truthy_builder = $assigned_type->getBuilder();
            $truthy_builder->removeType('null');
            $truthy_builder->removeType('false');
            $truthy_type = $truthy_builder->freeze();

            if (!$truthy_type->isUnionEmpty()) {
                $types[] = $truthy_type;
            }
        }

        return $types === [] ? null : Type::combineUnionTypeArray($types, $statements_analyzer->getCodebase());
    }

    /**
     * What the variable is, as the states `earlier` (not assigned yet), `full` (the assigned value) and `truthy` (the
     * assigned value, known to be truthy), after the expression, where it is true and where it is false.
     *
     * @param array<string, true> $incoming
     * @return array{array<string, true>, array<string, true>}
     */
    private static function getAssignmentStates(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $expr,
        string $var_id,
        ?string $self,
        array $incoming,
    ): array {
        // the value of `$a ??= $b` is the value `$a` has afterwards, like that of `$a = $b`
        if (($expr instanceof PhpParser\Node\Expr\Assign || $expr instanceof PhpParser\Node\Expr\AssignOp\Coalesce)
            && ExpressionIdentifier::getExtendedVarId($expr->var, $self, $statements_analyzer) === $var_id
        ) {
            return [['truthy#' . spl_object_id($expr) => true], ['full#' . spl_object_id($expr) => true]];
        }

        if ($expr instanceof PhpParser\Node\Expr\BooleanNot) {
            [$true_states, $false_states] = self::getAssignmentStates(
                $statements_analyzer,
                $expr->expr,
                $var_id,
                $self,
                $incoming,
            );

            return [$false_states, $true_states];
        }

        if ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
            || $expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            [$left_true, $left_false] = self::getAssignmentStates(
                $statements_analyzer,
                $expr->left,
                $var_id,
                $self,
                $incoming,
            );
            [$right_true, $right_false] = self::getAssignmentStates(
                $statements_analyzer,
                $expr->right,
                $var_id,
                $self,
                $left_true,
            );

            return [$right_true, $left_false + $right_false];
        }

        if ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            || $expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            [$left_true, $left_false] = self::getAssignmentStates(
                $statements_analyzer,
                $expr->left,
                $var_id,
                $self,
                $incoming,
            );
            [$right_true, $right_false] = self::getAssignmentStates(
                $statements_analyzer,
                $expr->right,
                $var_id,
                $self,
                $left_false,
            );

            return [$left_true + $right_true, $right_false];
        }

        if ($expr instanceof PhpParser\Node\Expr\Ternary) {
            [$cond_true, $cond_false] = self::getAssignmentStates(
                $statements_analyzer,
                $expr->cond,
                $var_id,
                $self,
                $incoming,
            );

            // `$a ?: $b` is the value of `$a` where that is truthy
            [$if_true, $if_false] = $expr->if !== null
                ? self::getAssignmentStates($statements_analyzer, $expr->if, $var_id, $self, $cond_true)
                : [$cond_true, []];
            [$else_true, $else_false] = self::getAssignmentStates(
                $statements_analyzer,
                $expr->else,
                $var_id,
                $self,
                $cond_false,
            );

            return [$if_true + $else_true, $if_false + $else_false];
        }

        if ($expr instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
            [$left_true, $left_false] = self::getAssignmentStates(
                $statements_analyzer,
                $expr->left,
                $var_id,
                $self,
                $incoming,
            );
            // the right operand only runs where the left one is null
            [$right_true, $right_false] = self::getAssignmentStates(
                $statements_analyzer,
                $expr->right,
                $var_id,
                $self,
                $left_false + $left_true,
            );

            return [$left_true + $right_true, $left_false + $right_false];
        }

        // any other expression: an assignment in a part it may skip (a nested ternary, `&&`, `??`, `match`, `?->`)
        // leaves the incoming states possible
        $full_states = [];
        $unconditional_assignments = self::getAssignmentsInCondition($expr, true);

        foreach (self::getAssignmentsInCondition($expr) as $assign) {
            if (ExpressionIdentifier::getExtendedVarId($assign->var, $self, $statements_analyzer) === $var_id) {
                $full_states['full#' . spl_object_id($assign)] = true;

                if (!in_array($assign, $unconditional_assignments, true)
                    && !self::isOverwrittenByAnUnconditionalAssignment(
                        $statements_analyzer,
                        $assign,
                        $unconditional_assignments,
                        $var_id,
                        $self,
                    )
                ) {
                    $full_states += $incoming;
                }
            }
        }

        if ($full_states !== []) {
            return [$full_states, $full_states];
        }

        return [$incoming, $incoming];
    }

    /**
     * Whether an assignment that may be skipped is in the value of an assignment of the same variable that always runs
     * (`$a = match (...) { f($a = 1) => ... }`): the value of the outer one is what the variable is after both
     *
     * @param list<PhpParser\Node\Expr\Assign|PhpParser\Node\Expr\AssignOp\Coalesce> $unconditional_assignments
     */
    private static function isOverwrittenByAnUnconditionalAssignment(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Assign|PhpParser\Node\Expr\AssignOp\Coalesce $assign,
        array $unconditional_assignments,
        string $var_id,
        ?string $self,
    ): bool {
        foreach ($unconditional_assignments as $outer) {
            if ($outer !== $assign
                && ExpressionIdentifier::getExtendedVarId($outer->var, $self, $statements_analyzer) === $var_id
                && (new PhpParser\NodeFinder())->findFirst(
                    $outer->expr,
                    static fn(PhpParser\Node $node): bool => $node === $assign,
                ) !== null
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param bool $only_unconditional leave out assignments in a part of the expression that may not run
     * @return list<PhpParser\Node\Expr\Assign|PhpParser\Node\Expr\AssignOp\Coalesce>
     */
    private static function getAssignmentsInCondition(
        PhpParser\Node\Expr $cond,
        bool $only_unconditional = false,
    ): array {
        $visitor = new class ($only_unconditional) extends PhpParser\NodeVisitorAbstract {
            /** @var list<PhpParser\Node\Expr\Assign|PhpParser\Node\Expr\AssignOp\Coalesce> */
            public array $assignments = [];

            public function __construct(private readonly bool $only_unconditional)
            {
            }

            /** @psalm-capabilities read-props|write-this-props|write-refs */
            #[Override]
            public function enterNode(PhpParser\Node $node): ?int
            {
                if ($node instanceof PhpParser\Node\Expr\Closure
                    || $node instanceof PhpParser\Node\Expr\ArrowFunction
                    || $node instanceof PhpParser\Node\Stmt\ClassLike
                ) {
                    return PhpParser\NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                // `$a ??= $b` always gives `$a` its value, but its right operand may not run
                if ($node instanceof PhpParser\Node\Expr\AssignOp\Coalesce) {
                    $this->assignments[] = $node;
                }

                if ($this->only_unconditional
                    && ($node instanceof PhpParser\Node\Expr\Ternary
                        || $node instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                        || $node instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                        || $node instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
                        || $node instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
                        || $node instanceof PhpParser\Node\Expr\BinaryOp\Coalesce
                        || $node instanceof PhpParser\Node\Expr\AssignOp\Coalesce
                        || $node instanceof PhpParser\Node\Expr\Match_
                        || $node instanceof PhpParser\Node\Expr\NullsafePropertyFetch
                        || $node instanceof PhpParser\Node\Expr\NullsafeMethodCall)
                ) {
                    return PhpParser\NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                if ($node instanceof PhpParser\Node\Expr\Assign) {
                    $this->assignments[] = $node;
                }

                return null;
            }
        };

        $traverser = new PhpParser\NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse([$cond]);

        return $visitor->assignments;
    }
}
