<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\ComplicatedExpressionException;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Scope\LoopScope;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\UndefinedGlobalVariable;
use Psalm\Issue\UndefinedVariable;
use Psalm\IssueBuffer;
use Psalm\Type\Reconciler;
use UnexpectedValueException;

use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function preg_match;
use function preg_quote;
use function spl_object_id;

/**
 * @internal
 */
final class DoAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Do_ $stmt,
        Context $context,
    ): ?bool {
        $do_context = clone $context;
        $do_context->break_types[] = 'loop';
        $do_context->inside_loop = true;

        $codebase = $statements_analyzer->getCodebase();

        if ($codebase->alter_code && $do_context->branch_point === null) {
            $do_context->branch_point = (int) $stmt->getAttribute('startFilePos');
        }

        $loop_scope = new LoopScope($do_context, $context);
        $loop_scope->protected_var_ids = $context->protected_var_ids;

        $first_iteration_issues = self::analyzeDoNaively($statements_analyzer, $stmt, $do_context, $loop_scope);

        $mixed_var_ids = [];

        foreach ($do_context->vars_in_scope as $var_id => $type) {
            if ($type->hasMixed()) {
                $mixed_var_ids[] = $var_id;
            }
        }

        IssueBuffer::startRecording();
        IssueBuffer::startCollectingRaisedIssues();

        try {
            $loop_result = LoopAnalyzer::analyze(
                $statements_analyzer,
                $stmt->stmts,
                WhileAnalyzer::getAndExpressions($stmt->cond),
                [],
                $loop_scope,
                $inner_loop_context,
                true,
                true,
            );
        } finally {
            $loop_raised_issue_keys = IssueBuffer::stopCollectingRaisedIssues();
        }

        $loop_issues = IssueBuffer::clearRecordingLevel();
        IssueBuffer::stopRecording();

        self::reportIssues(
            $statements_analyzer->getFilePath(),
            $stmt->stmts,
            $first_iteration_issues,
            $loop_issues,
            $loop_raised_issue_keys,
        );

        if ($loop_result === false) {
            return false;
        }

        // because it's a do {} while, inner loop vars belong to the main context
        if (!$inner_loop_context) {
            throw new UnexpectedValueException('There should be an inner loop context');
        }

        $cond_id = spl_object_id($stmt->cond);

        // what the condition asserts can depend on the types of its operands (`$i-- > 0`), known once it is analysed
        $while_clauses = FormulaGenerator::getFormula(
            $cond_id,
            $cond_id,
            $stmt->cond,
            $context->self,
            $statements_analyzer,
            $codebase,
            cache: false,
        );

        // a clause on an offset or property of a mixed variable says nothing, but its negation is not true either
        $while_clauses = array_map(
            static function (Clause $c) use ($mixed_var_ids, $cond_id): Clause {
                $keys = array_keys($c->possibilities);

                $mixed_var_ids = array_diff($mixed_var_ids, $keys);

                foreach ($keys as $key) {
                    foreach ($mixed_var_ids as $mixed_var_id) {
                        if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                            return new Clause([], $cond_id, $cond_id, true);
                        }
                    }
                }

                return $c;
            },
            $while_clauses,
        );

        if (!$while_clauses) {
            $while_clauses = [new Clause([], $cond_id, $cond_id, true)];
        }

        try {
            $negated_while_clauses = FormulaGenerator::getNegatedFormula(
                $while_clauses,
                $stmt->cond,
                $cond_id,
                $context->self,
                $statements_analyzer,
                $codebase,
            );
        } catch (ComplicatedExpressionException) {
            $negated_while_clauses = [];
        }

        $negated_while_types = Algebra::getTruthsFromFormula(
            Algebra::simplifyCNF(
                [...$context->clauses, ...$negated_while_clauses],
            ),
        );

        if ($negated_while_types) {
            $changed_var_ids = [];

            [$inner_loop_context->vars_in_scope, $inner_loop_context->references_in_scope] =
                Reconciler::reconcileKeyedTypes(
                    $negated_while_types,
                    [],
                    $inner_loop_context->vars_in_scope,
                    $inner_loop_context->references_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    [],
                    true,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->cond),
                );
        }

        $cond_type = $statements_analyzer->node_data->getType($stmt->cond);
        $is_only_left_by_break = $cond_type && $cond_type->isAlwaysTruthy();

        LoopAnalyzer::setLoopVars(
            $inner_loop_context,
            $context,
            $loop_scope,
            is_only_left_by_break: $is_only_left_by_break,
        );

        if (!$is_only_left_by_break) {
            LoopAnalyzer::addVarsWrittenByTheExitingCondition($context, $loop_scope);
        }

        // the body of a do-while runs once, unless it leaves the loop
        if (!in_array(ScopeAnalyzer::ACTION_BREAK, $loop_scope->final_actions, true)
            && !in_array(ScopeAnalyzer::ACTION_CONTINUE, $loop_scope->final_actions, true)
            && !self::hasExitToTheLoop($stmt->stmts, 0)
        ) {
            $context->assigned_var_ids = [
                ...$context->assigned_var_ids,
                ...$inner_loop_context->assigned_var_ids,
            ];
        }

        $do_context->loop_scope = null;

        $context->vars_possibly_in_scope = [
            ...$context->vars_possibly_in_scope,
            ...$do_context->vars_possibly_in_scope,
        ];

        if ($context->collect_exceptions) {
            $context->mergeExceptions($inner_loop_context);
        }

        return null;
    }

    /**
     * @return array<int, CodeIssue> the issues of the first iteration
     */
    private static function analyzeDoNaively(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Do_ $stmt,
        Context $context,
        LoopScope $loop_scope,
    ): array {
        $do_context = clone $context;

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        if (!isset($suppressed_issues['RedundantCondition'])) {
            $statements_analyzer->addSuppressedIssues(['RedundantCondition']);
        }
        if (!isset($suppressed_issues['RedundantConditionGivenDocblockType'])) {
            $statements_analyzer->addSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }
        if (!isset($suppressed_issues['TypeDoesNotContainType'])) {
            $statements_analyzer->addSuppressedIssues(['TypeDoesNotContainType']);
        }
        // a branch only impossible in the first iteration is reachable in the next ones
        if (!isset($suppressed_issues['NoValue'])) {
            $statements_analyzer->addSuppressedIssues(['NoValue']);
        }

        $do_context->loop_scope = $loop_scope;

        IssueBuffer::startRecording();

        $statements_analyzer->analyze($stmt->stmts, $do_context);

        $issues = IssueBuffer::clearRecordingLevel();
        IssueBuffer::stopRecording();

        if (!isset($suppressed_issues['RedundantCondition'])) {
            $statements_analyzer->removeSuppressedIssues(['RedundantCondition']);
        }
        if (!isset($suppressed_issues['RedundantConditionGivenDocblockType'])) {
            $statements_analyzer->removeSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }
        if (!isset($suppressed_issues['TypeDoesNotContainType'])) {
            $statements_analyzer->removeSuppressedIssues(['TypeDoesNotContainType']);
        }
        if (!isset($suppressed_issues['NoValue'])) {
            $statements_analyzer->removeSuppressedIssues(['NoValue']);
        }

        return $issues;
    }

    /**
     * The body runs once with the types it has before the loop: what the loop analysis finds possibly wrong with the
     * types of all the iterations is wrong for sure when the first one has it, in the code the first iteration runs
     * for sure. The other issues of the first iteration are left to the loop analysis, which also sees what this first
     * pass assumes wrongly: the code the first iteration may skip (a branch only taken in the next ones), a variable
     * the body defines after reading it, which is possibly undefined, the files it includes and the closures capturing
     * by reference, which can run with the types of a later iteration.
     *
     * @param array<PhpParser\Node\Stmt> $stmts the body, in $file_path
     * @param array<int, CodeIssue> $first_iteration_issues
     * @param array<int, CodeIssue> $loop_issues
     * @param array<string, true> $loop_raised_issue_keys the issues the loop analysis raised, reported or not
     */
    private static function reportIssues(
        string $file_path,
        array $stmts,
        array $first_iteration_issues,
        array $loop_issues,
        array $loop_raised_issue_keys,
    ): void {
        $replaced_issue_keys = [];
        $kept_issues = [];
        $unsure_bounds = null;

        foreach ($first_iteration_issues as $issue) {
            if ($issue instanceof UndefinedVariable || $issue instanceof UndefinedGlobalVariable) {
                continue;
            }

            $possible_issue_key = IssueBuffer::getIssueLocationKey('Possibly' . $issue::getIssueType(), $issue);

            if (!isset($loop_raised_issue_keys[$possible_issue_key]) || $issue->getFilePath() !== $file_path) {
                continue;
            }

            $unsure_bounds ??= self::getUnsureBounds($stmts);
            [$from] = $issue->code_location->getSelectionBounds();

            foreach ($unsure_bounds as [$unsure_from, $unsure_to]) {
                if ($from >= $unsure_from && $from <= $unsure_to) {
                    continue 2;
                }
            }

            $replaced_issue_keys[$possible_issue_key] = true;
            $kept_issues[] = $issue;
        }

        foreach ($loop_issues as $issue) {
            if (!isset($replaced_issue_keys[IssueBuffer::getIssueLocationKey($issue::getIssueType(), $issue)])) {
                IssueBuffer::bubbleUp($issue);
            }
        }

        foreach ($kept_issues as $issue) {
            IssueBuffer::bubbleUp($issue);
        }
    }

    /**
     * @param array<PhpParser\Node\Stmt> $stmts the body
     * @return list<array{int, int}> the bounds of the code of the body the first iteration may not run, or not with
     *                               its types: what follows a statement that may go to the next iteration, the
     *                               branches and operands evaluated on a condition, the bodies of the closures
     *                               capturing by reference
     */
    private static function getUnsureBounds(array $stmts): array
    {
        $bounds = [];

        self::addSkippableStatementBounds(array_values($stmts), 0, $bounds);

        foreach ((new PhpParser\NodeFinder())->find($stmts, static fn(PhpParser\Node $_): bool => true) as $node) {
            $unsure_nodes = match (true) {
                $node instanceof PhpParser\Node\Stmt\If_ => [$node->stmts, ...$node->elseifs, $node->else],
                $node instanceof PhpParser\Node\Expr\Ternary => [$node->if, $node->else],
                $node instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd,
                $node instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr,
                $node instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd,
                $node instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr,
                $node instanceof PhpParser\Node\Expr\BinaryOp\Coalesce => [$node->right],
                $node instanceof PhpParser\Node\Expr\AssignOp\Coalesce => [$node->expr],
                $node instanceof PhpParser\Node\Expr\Match_ => [
                    ...array_map(
                        static fn(PhpParser\Node\MatchArm $arm): PhpParser\Node\Expr => $arm->body,
                        $node->arms,
                    ),
                    ...self::getConditionsAfterTheFirst(array_map(
                        static fn(PhpParser\Node\MatchArm $arm): ?array => $arm->conds,
                        $node->arms,
                    )),
                ],
                $node instanceof PhpParser\Node\Stmt\Switch_ => [
                    ...array_map(static fn(PhpParser\Node\Stmt\Case_ $case): array => $case->stmts, $node->cases),
                    ...self::getConditionsAfterTheFirst(array_map(
                        static fn(PhpParser\Node\Stmt\Case_ $case): ?array
                            => $case->cond !== null ? [$case->cond] : null,
                        $node->cases,
                    )),
                ],
                // a null object skips the rest of the chain
                $node instanceof PhpParser\Node\Expr\MethodCall,
                $node instanceof PhpParser\Node\Expr\NullsafeMethodCall
                    => self::isAfterANullsafe($node) ? [$node->args, $node->name] : [],
                $node instanceof PhpParser\Node\Expr\PropertyFetch,
                $node instanceof PhpParser\Node\Expr\NullsafePropertyFetch
                    => self::isAfterANullsafe($node) ? [$node->name] : [],
                $node instanceof PhpParser\Node\Expr\ArrayDimFetch
                    => self::isAfterANullsafe($node) ? [$node->dim] : [],
                $node instanceof PhpParser\Node\Expr\StaticCall
                    => self::isAfterANullsafe($node) ? [$node->args, $node->name] : [],
                $node instanceof PhpParser\Node\Expr\StaticPropertyFetch
                    => self::isAfterANullsafe($node) ? [$node->name] : [],
                // the body of a nested do-while may leave it before its condition
                $node instanceof PhpParser\Node\Stmt\Do_
                    => self::hasExitToTheLoop($node->stmts, 0) ? [$node->cond] : [],
                $node instanceof PhpParser\Node\Stmt\For_ => [$node->stmts, $node->loop],
                $node instanceof PhpParser\Node\Stmt\Foreach_,
                $node instanceof PhpParser\Node\Stmt\While_ => [$node->stmts],
                $node instanceof PhpParser\Node\Stmt\TryCatch => $node->catches,
                $node instanceof PhpParser\Node\Expr\Closure
                    && array_filter($node->uses, static fn(PhpParser\Node\ClosureUse $use): bool => $use->byRef)
                    => [$node->stmts],
                default => [],
            };

            foreach ($unsure_nodes as $unsure_node) {
                if ($unsure_node instanceof PhpParser\Node) {
                    $unsure_node = [$unsure_node];
                }

                if ($unsure_node !== null && $unsure_node !== []) {
                    $bounds[] = self::getBounds(array_values($unsure_node));
                }
            }
        }

        return $bounds;
    }

    /**
     * @param array<?array<PhpParser\Node\Expr>> $conditions the conditions of the arms or cases, null for the default
     * @return list<PhpParser\Node\Expr> the conditions evaluated only when the first one is false
     * @psalm-pure
     */
    private static function getConditionsAfterTheFirst(array $conditions): array
    {
        $after_the_first = [];
        $first_seen = false;

        foreach ($conditions as $arm_conditions) {
            foreach ($arm_conditions ?? [] as $condition) {
                if ($first_seen) {
                    $after_the_first[] = $condition;
                }

                $first_seen = true;
            }
        }

        return $after_the_first;
    }

    /**
     * Whether the node is a nullsafe call or fetch, or follows one in a chain
     *
     * @psalm-mutation-free
     */
    private static function isAfterANullsafe(PhpParser\Node\Expr $node): bool
    {
        if ($node instanceof PhpParser\Node\Expr\NullsafeMethodCall
            || $node instanceof PhpParser\Node\Expr\NullsafePropertyFetch
        ) {
            return true;
        }

        if ($node instanceof PhpParser\Node\Expr\MethodCall
            || $node instanceof PhpParser\Node\Expr\PropertyFetch
            || $node instanceof PhpParser\Node\Expr\ArrayDimFetch
        ) {
            return self::isAfterANullsafe($node->var);
        }

        if (($node instanceof PhpParser\Node\Expr\StaticCall
                || $node instanceof PhpParser\Node\Expr\StaticPropertyFetch)
            && $node->class instanceof PhpParser\Node\Expr
        ) {
            return self::isAfterANullsafe($node->class);
        }

        return false;
    }

    /**
     * Adds the bounds of the statements that follow one that may go to the next iteration, in the list and the lists
     * nested in it: the first iteration may skip them. After a `break`, a `return` or a `throw` leaving the loop,
     * there is no next iteration, so what follows runs first in the first one; a `break` or a `continue` of a loop
     * nested in the body may lead to a later iteration of the loop.
     *
     * @param list<PhpParser\Node\Stmt> $stmts
     * @param int $depth the loops and switches between the statements and the loop
     * @param list<array{int, int}> $bounds
     */
    private static function addSkippableStatementBounds(array $stmts, int $depth, array &$bounds): void
    {
        foreach ($stmts as $i => $stmt) {
            self::addSkippableStatementBoundsIn($stmt, $depth, $bounds);

            if ($i + 1 < count($stmts)
                && (self::hasExitToTheLoop([$stmt], $depth, to_the_next_iteration: true)
                    || ($depth > 0 && self::hasExitToTheLoop([$stmt], 0)))
            ) {
                $bounds[] = self::getBounds([$stmts[$i + 1], $stmts[count($stmts) - 1]]);

                return;
            }
        }
    }

    /**
     * @param int $depth the loops and switches between the node and the loop
     * @param list<array{int, int}> $bounds
     */
    private static function addSkippableStatementBoundsIn(PhpParser\Node $node, int $depth, array &$bounds): void
    {
        if ($node instanceof PhpParser\Node\FunctionLike || $node instanceof PhpParser\Node\Stmt\ClassLike) {
            return;
        }

        $nested_depth = self::isLoopOrSwitch($node) ? $depth + 1 : $depth;

        foreach ($node->getSubNodeNames() as $name) {
            /** @var mixed $child */
            $child = $node->{$name};

            if ($child instanceof PhpParser\Node) {
                self::addSkippableStatementBoundsIn($child, $nested_depth, $bounds);

                continue;
            }

            if (!is_array($child)) {
                continue;
            }

            $stmts = [];

            /** @var mixed $item */
            foreach ($child as $item) {
                if ($item instanceof PhpParser\Node\Stmt) {
                    $stmts[] = $item;
                } elseif ($item instanceof PhpParser\Node) {
                    self::addSkippableStatementBoundsIn($item, $nested_depth, $bounds);
                }
            }

            self::addSkippableStatementBounds($stmts, $nested_depth, $bounds);
        }
    }

    /**
     * @psalm-pure
     */
    private static function isLoopOrSwitch(PhpParser\Node $node): bool
    {
        return $node instanceof PhpParser\Node\Stmt\For_
            || $node instanceof PhpParser\Node\Stmt\Foreach_
            || $node instanceof PhpParser\Node\Stmt\While_
            || $node instanceof PhpParser\Node\Stmt\Do_
            || $node instanceof PhpParser\Node\Stmt\Switch_;
    }

    /**
     * @param non-empty-list<PhpParser\Node> $nodes
     * @return array{int, int}
     */
    private static function getBounds(array $nodes): array
    {
        return [
            (int) $nodes[0]->getAttribute('startFilePos'),
            (int) $nodes[count($nodes) - 1]->getAttribute('endFilePos'),
        ];
    }

    /**
     * Whether a `break` or `continue` in the statements leaves the loop, from inside loops or switches nested in it
     * (`break 2` in a nested loop is recorded on the nested one only)
     *
     * @param array<PhpParser\Node> $nodes
     * @param int $depth the loops and switches between the statements and the loop
     * @param bool $to_the_next_iteration only a `continue` to the loop, or a `goto`, which may jump there
     */
    private static function hasExitToTheLoop(array $nodes, int $depth, bool $to_the_next_iteration = false): bool
    {
        foreach ($nodes as $node) {
            if ($node instanceof PhpParser\Node\Stmt\Break_ || $node instanceof PhpParser\Node\Stmt\Continue_) {
                $levels = $node->num instanceof PhpParser\Node\Scalar\Int_ ? $node->num->value : 1;

                if ($levels > $depth && (!$to_the_next_iteration || $node instanceof PhpParser\Node\Stmt\Continue_)) {
                    return true;
                }

                continue;
            }

            if ($to_the_next_iteration && $node instanceof PhpParser\Node\Stmt\Goto_) {
                return true;
            }

            if ($node instanceof PhpParser\Node\FunctionLike || $node instanceof PhpParser\Node\Stmt\ClassLike) {
                continue;
            }

            $nested_depth = self::isLoopOrSwitch($node) ? $depth + 1 : $depth;

            foreach ($node->getSubNodeNames() as $name) {
                /** @var mixed $child */
                $child = $node->{$name};

                if ($child instanceof PhpParser\Node) {
                    $child = [$child];
                }

                if (is_array($child)) {
                    $children = [];

                    /** @var mixed $item */
                    foreach ($child as $item) {
                        if ($item instanceof PhpParser\Node) {
                            $children[] = $item;
                        }
                    }

                    if (self::hasExitToTheLoop($children, $nested_depth, $to_the_next_iteration)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
