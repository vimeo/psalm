<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfElse\IfAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Scope\SwitchScope;
use Psalm\Type;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;
use SplFixedArray;

use function array_intersect_key;
use function array_merge;
use function count;
use function in_array;
use function str_contains;
use function str_starts_with;

/**
 * @internal
 */
final class SwitchAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Switch_ $stmt,
        Context $context,
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        $was_inside_conditional = $context->inside_conditional;

        $context->inside_conditional = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->cond, $context) === false) {
            $context->inside_conditional = $was_inside_conditional;

            return;
        }

        $context->inside_conditional = $was_inside_conditional;

        $switch_var_id = ExpressionIdentifier::getExtendedVarId(
            $stmt->cond,
            null,
            $statements_analyzer,
        );

        $temporary_switch_var_id = null;

        if (!$switch_var_id
            && ($stmt->cond instanceof PhpParser\Node\Expr\FuncCall
                || $stmt->cond instanceof PhpParser\Node\Expr\MethodCall
                || $stmt->cond instanceof PhpParser\Node\Expr\StaticCall
            )
        ) {
            $temporary_switch_var_id = '$__tmp_switch__' . (int) $stmt->cond->getAttribute('startFilePos');
            $switch_var_id = $temporary_switch_var_id;

            $condition_type = $statements_analyzer->node_data->getType($stmt->cond) ?? Type::getMixed();

            $context->vars_in_scope[$switch_var_id] = $condition_type;
        }

        $original_context = clone $context;

        // the last statement always breaks, by default
        $last_case_exit_type = 'break';

        $case_exit_types = new SplFixedArray(count($stmt->cases));

        $has_default = false;

        $case_action_map = [];

        // the switches directly around this one are targets of `continue N` too, which leaves them like `break N`,
        // while a loop around them is left to the actions of the switch (a `continue` of it ends the case)
        $break_types = ['switch'];

        for ($j = count($context->break_types) - 1; $j >= 0 && $context->break_types[$j] === 'switch'; $j--) {
            $break_types = ['outer_switch', ...$break_types];
        }

        // create a map of case statement -> ultimate exit type
        for ($i = count($stmt->cases) - 1; $i >= 0; --$i) {
            $case = $stmt->cases[$i];

            $case_actions = $case_action_map[$i] = ScopeAnalyzer::getControlActions(
                $case->stmts,
                $statements_analyzer->node_data,
                $break_types,
            );

            if (!in_array(ScopeAnalyzer::ACTION_NONE, $case_actions, true)) {
                if ($case_actions === [ScopeAnalyzer::ACTION_END]) {
                    $last_case_exit_type = 'return_throw';
                } elseif ($case_actions === [ScopeAnalyzer::ACTION_CONTINUE]) {
                    $last_case_exit_type = 'continue';
                } elseif (in_array(ScopeAnalyzer::ACTION_LEAVE_SWITCH, $case_actions, true)) {
                    $last_case_exit_type = 'break';
                } elseif ($case_actions) {
                    // `break 2` or more out of an enclosing structure: the case never reaches the end of the switch
                    $last_case_exit_type = 'leave';
                }
            } elseif (count($case_actions) !== 1) {
                $last_case_exit_type = 'hybrid';
            }

            $case_exit_types[$i] = $last_case_exit_type;
        }

        $switch_scope = new SwitchScope();

        $was_caching_assertions = $statements_analyzer->node_data->cache_assertions;

        $statements_analyzer->node_data->cache_assertions = false;

        $all_options_returned = true;

        for ($i = 0, $l = count($stmt->cases); $i < $l; $i++) {
            $case = $stmt->cases[$i];

            /** @var string */
            $case_exit_type = $case_exit_types[$i];
            if ($case_exit_type !== 'return_throw' && $case_exit_type !== 'leave') {
                $all_options_returned = false;
            }

            $case_actions = $case_action_map[$i];

            if (!$case->cond) {
                $has_default = true;
            }

            if (SwitchCaseAnalyzer::analyze(
                $statements_analyzer,
                $codebase,
                $stmt,
                $switch_var_id,
                $case,
                $context,
                $original_context,
                $case_exit_type,
                $case_actions,
                $i === $l - 1,
                $switch_scope,
            ) === false
            ) {
                return;
            }
        }

        $all_options_matched = $has_default;

        if (!$has_default && $switch_scope->negated_clauses) {
            $entry_clauses = Algebra::simplifyCNF(
                [...$original_context->clauses, ...$switch_scope->negated_clauses],
            );

            $reconcilable_if_types = Algebra::getTruthsFromFormula($entry_clauses);

            // if the if has an || in the conditional, we cannot easily reason about it
            if ($reconcilable_if_types) {
                $changed_var_ids = [];

                [$case_vars_in_scope_reconciled, $_] =
                    Reconciler::reconcileKeyedTypes(
                        $reconcilable_if_types,
                        [],
                        $original_context->vars_in_scope,
                        $original_context->references_in_scope,
                        $changed_var_ids,
                        [],
                        $statements_analyzer,
                        [],
                        $original_context->inside_loop,
                    );

                // the cases of `switch (true)` narrow the variables of their conditions, but the negated conditions
                // describe the values after the conditions ran, not those of the original context
                [$assigned_var_ids, $has_call] = self::getVarsChangedByTheConditions($statements_analyzer, $stmt);

                foreach ($reconcilable_if_types as $var_id => $_) {
                    if (isset($case_vars_in_scope_reconciled[$var_id])
                        && $case_vars_in_scope_reconciled[$var_id]->isNever()
                        && !self::mayBeChangedByTheConditions($var_id, $assigned_var_ids, $has_call)
                    ) {
                        $all_options_matched = true;
                        break;
                    }
                }

                if (!$all_options_matched
                    && $all_options_returned
                    && $switch_var_id !== null
                    && isset($case_vars_in_scope_reconciled[$switch_var_id])
                ) {
                    // every case leaves the switch, so only no case matching reaches the code after it (cases
                    // sharing a body, `case 'a': case 'b': return;`, are not narrowed)
                    $context->vars_in_scope[$switch_var_id] = $case_vars_in_scope_reconciled[$switch_var_id];
                }
            }
        }

        if ($was_caching_assertions) {
            $statements_analyzer->node_data->cache_assertions = true;
        }

        // only update vars if there is a default or all possible cases accounted for
        // if the default has a throw/return/continue, that should be handled above
        if ($all_options_matched) {
            foreach ($switch_scope->unknown_offsets as $var_id => $_) {
                unset($context->vars_in_scope[$var_id], $context->vars_possibly_in_scope[$var_id]);
            }

            if ($switch_scope->new_vars_in_scope) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $switch_scope->new_vars_in_scope);
            }

            if ($switch_scope->redefined_vars) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $switch_scope->redefined_vars);
            }

            if ($switch_scope->possibly_redefined_vars) {
                foreach ($switch_scope->possibly_redefined_vars as $var_id => $type) {
                    if (!isset($switch_scope->redefined_vars[$var_id])
                        && !isset($switch_scope->new_vars_in_scope[$var_id])
                        && isset($context->vars_in_scope[$var_id])
                    ) {
                        $context->vars_in_scope[$var_id] = self::addPossiblyRedefinedType(
                            $statements_analyzer,
                            $stmt,
                            $var_id,
                            $type,
                            $context->vars_in_scope[$var_id],
                        );
                    }
                }
            }

            // every way through the switch assigns these, as the branches of an if with an else do
            $assigned_offset = (int) $stmt->getAttribute('startFilePos');
            $assigned_vars = [...($switch_scope->new_vars_in_scope ?? []), ...($switch_scope->redefined_vars ?? [])];

            foreach ($assigned_vars as $var_id => $_) {
                $context->assigned_var_ids[$var_id] ??= $assigned_offset;
                $context->possibly_assigned_var_ids[$var_id] = true;
            }

            $stmt->setAttribute('allMatched', true);
        } elseif ($switch_scope->possibly_redefined_vars) {
            foreach ($switch_scope->possibly_redefined_vars as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = self::addPossiblyRedefinedType(
                        $statements_analyzer,
                        $stmt,
                        $var_id,
                        $type,
                        $context->vars_in_scope[$var_id],
                    );
                }
            }
        }

        if ($switch_scope->new_assigned_var_ids) {
            $context->assigned_var_ids += $switch_scope->new_assigned_var_ids;
        }

        // the offsets read on every way out of the cases, and, when a value may match no case, on the way past them
        if ($switch_scope->null_array_offsets_read !== null) {
            $context->null_array_offsets_read = $all_options_matched
                ? $switch_scope->null_array_offsets_read
                : array_intersect_key($context->null_array_offsets_read, $switch_scope->null_array_offsets_read);
        }

        $context->vars_possibly_in_scope = [
            ...$context->vars_possibly_in_scope,
            ...$switch_scope->new_vars_possibly_in_scope,
        ];

        if ($temporary_switch_var_id !== null) {
            unset($context->vars_in_scope[$temporary_switch_var_id]);
        }

        //a switch can't return in all options without a default
        $context->has_returned = $all_options_returned && $has_default;

        if ($context->has_returned && $context->case_scope && in_array('leave', $case_exit_types->toArray(), true)) {
            $context->case_scope->has_nested_switch_leaving_it = true;
        }
    }

    /**
     * The type of a variable after the switch, from its type before and the one some cases leave it with
     */
    private static function addPossiblyRedefinedType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Switch_ $stmt,
        string $var_id,
        Union $case_type,
        Union $type,
    ): Union {
        if (IfAnalyzer::isNarrowedMixed($statements_analyzer->getCodebase(), $case_type, $type)
            && !IfAnalyzer::mayAssignAsAWhole($stmt->cases, $var_id, $statements_analyzer)
        ) {
            return $type->addParentNodes($case_type->parent_nodes);
        }

        return Type::combineUnionTypes($case_type, $type);
    }

    /**
     * @return array{array<string, true>, bool} the variables the case conditions assign or pass by reference, and
     *     whether one of them calls something
     */
    private static function getVarsChangedByTheConditions(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Switch_ $stmt,
    ): array {
        $assigned_var_ids = [];
        $has_call = false;
        $node_finder = new PhpParser\NodeFinder();

        foreach ($stmt->cases as $case) {
            if (!$case->cond) {
                continue;
            }

            $assigned_var_ids += FormulaGenerator::getAssignedVarIds(
                $case->cond,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer->node_data,
            );

            $has_call = $has_call
                || $node_finder->findFirst(
                    $case->cond,
                    static fn(PhpParser\Node $node): bool => $node instanceof PhpParser\Node\Expr\CallLike,
                ) !== null;
        }

        return [$assigned_var_ids, $has_call];
    }

    /**
     * @param array<string, true> $assigned_var_ids
     * @psalm-pure
     */
    private static function mayBeChangedByTheConditions(string $var_id, array $assigned_var_ids, bool $has_call): bool
    {
        // a call may write the properties it can reach
        if ($has_call && (str_contains($var_id, '->') || str_contains($var_id, '::'))) {
            return true;
        }

        foreach ($assigned_var_ids as $assigned_var_id => $_) {
            if ($var_id === $assigned_var_id
                || str_starts_with($var_id, $assigned_var_id . '[')
                || str_starts_with($var_id, $assigned_var_id . '->')
            ) {
                return true;
            }
        }

        return false;
    }
}
