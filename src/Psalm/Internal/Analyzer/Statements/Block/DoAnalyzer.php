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
use function array_keys;
use function array_map;
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

        self::reportIssues($first_iteration_issues, $loop_issues, $loop_raised_issue_keys);

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
     * types of all the iterations is wrong for sure when the first one has it. The other issues of the first
     * iteration are left to the loop analysis, which also sees what this first pass assumes wrongly (a branch only
     * impossible in the first iteration, a variable the body defines after reading it, which is possibly undefined).
     *
     * @param array<int, CodeIssue> $first_iteration_issues
     * @param array<int, CodeIssue> $loop_issues
     * @param array<string, true> $loop_raised_issue_keys the issues the loop analysis raised, reported or not
     */
    private static function reportIssues(
        array $first_iteration_issues,
        array $loop_issues,
        array $loop_raised_issue_keys,
    ): void {
        $replaced_issue_keys = [];
        $kept_issues = [];

        foreach ($first_iteration_issues as $issue) {
            if ($issue instanceof UndefinedVariable || $issue instanceof UndefinedGlobalVariable) {
                continue;
            }

            $possible_issue_key = IssueBuffer::getIssueLocationKey('Possibly' . $issue::getIssueType(), $issue);

            if (isset($loop_raised_issue_keys[$possible_issue_key])) {
                $replaced_issue_keys[$possible_issue_key] = true;
                $kept_issues[] = $issue;
            }
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
     * Whether a `break` or `continue` in the statements leaves the loop, from inside loops or switches nested in it
     * (`break 2` in a nested loop is recorded on the nested one only)
     *
     * @param array<PhpParser\Node> $nodes
     * @param int $depth the loops and switches between the statements and the loop
     */
    private static function hasExitToTheLoop(array $nodes, int $depth): bool
    {
        foreach ($nodes as $node) {
            if ($node instanceof PhpParser\Node\Stmt\Break_ || $node instanceof PhpParser\Node\Stmt\Continue_) {
                $levels = $node->num instanceof PhpParser\Node\Scalar\Int_ ? $node->num->value : 1;

                if ($levels > $depth) {
                    return true;
                }

                continue;
            }

            if ($node instanceof PhpParser\Node\FunctionLike || $node instanceof PhpParser\Node\Stmt\ClassLike) {
                continue;
            }

            $nested_depth = $node instanceof PhpParser\Node\Stmt\For_
                || $node instanceof PhpParser\Node\Stmt\Foreach_
                || $node instanceof PhpParser\Node\Stmt\While_
                || $node instanceof PhpParser\Node\Stmt\Do_
                || $node instanceof PhpParser\Node\Stmt\Switch_
                ? $depth + 1
                : $depth;

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

                    if (self::hasExitToTheLoop($children, $nested_depth)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
