<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\ArrayAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\FalseOperand;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyFalseOperand;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\Issue\PossiblyNullOperand;
use Psalm\Issue\StringIncrement;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Reconciler;
use Psalm\Internal\Type\AssertionReconciler;
use Psalm\Internal\Type\TypeCombination;
use function array_merge;
use function array_diff_key;
use function array_filter;
use function array_intersect_key;
use function array_values;
use function array_map;
use function array_keys;
use function preg_match;
use function preg_quote;
use function strtolower;
use function strlen;

/**
 * @internal
 */
class BinaryOpAnalyzer
{
    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Expr\BinaryOp    $stmt
     * @param   Context                         $context
     * @param   int                             $nesting
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp $stmt,
        Context $context,
        int $nesting = 0,
        bool $from_stmt = false
    ) {
        $codebase = $statements_analyzer->getCodebase();

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat && $nesting > 20) {
            // ignore deeply-nested string concatenation
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            if ($from_stmt) {
                $fake_if_stmt = new PhpParser\Node\Stmt\If_(
                    $stmt->left,
                    [
                        'stmts' => [
                            new PhpParser\Node\Stmt\Expression(
                                $stmt->right
                            )
                        ]
                    ],
                    $stmt->getAttributes()
                );

                if (IfAnalyzer::analyze($statements_analyzer, $fake_if_stmt, $context) === false) {
                    return false;
                }

                return null;
            }

            $pre_referenced_var_ids = $context->referenced_var_ids;

            $pre_assigned_var_ids = $context->assigned_var_ids;

            $left_context = clone $context;

            $left_context->referenced_var_ids = [];
            $left_context->assigned_var_ids = [];

            /** @var list<string> */
            $left_context->reconciled_expression_clauses = [];

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, $left_context) === false) {
                return false;
            }

            $left_clauses = Algebra::getFormula(
                \spl_object_id($stmt->left),
                $stmt->left,
                $context->self,
                $statements_analyzer,
                $codebase
            );

            foreach ($left_context->vars_in_scope as $var_id => $type) {
                if (isset($left_context->assigned_var_ids[$var_id])) {
                    $context->vars_in_scope[$var_id] = $type;
                }
            }

            /** @var array<string, bool> */
            $left_referenced_var_ids = $left_context->referenced_var_ids;
            $context->referenced_var_ids = array_merge($pre_referenced_var_ids, $left_referenced_var_ids);

            $left_assigned_var_ids = array_diff_key($left_context->assigned_var_ids, $pre_assigned_var_ids);

            $left_referenced_var_ids = array_diff_key($left_referenced_var_ids, $left_assigned_var_ids);

            $context_clauses = array_merge($left_context->clauses, $left_clauses);

            if ($left_context->reconciled_expression_clauses) {
                $reconciled_expression_clauses = $left_context->reconciled_expression_clauses;

                $context_clauses = array_values(
                    array_filter(
                        $context_clauses,
                        function ($c) use ($reconciled_expression_clauses) {
                            return !\in_array($c->getHash(), $reconciled_expression_clauses);
                        }
                    )
                );

                if (\count($context_clauses) === 1
                    && $context_clauses[0]->wedge
                    && !$context_clauses[0]->possibilities
                ) {
                    $context_clauses = [];
                }
            }

            $simplified_clauses = Algebra::simplifyCNF($context_clauses);

            $active_left_assertions = [];

            $left_type_assertions = Algebra::getTruthsFromFormula(
                $simplified_clauses,
                \spl_object_id($stmt->left),
                $left_referenced_var_ids,
                $active_left_assertions
            );

            $changed_var_ids = [];

            $right_context = clone $left_context;

            if ($left_type_assertions) {
                // while in an and, we allow scope to boil over to support
                // statements of the form if ($x && $x->foo())
                $right_vars_in_scope = Reconciler::reconcileKeyedTypes(
                    $left_type_assertions,
                    $active_left_assertions,
                    $context->vars_in_scope,
                    $changed_var_ids,
                    $left_referenced_var_ids,
                    $statements_analyzer,
                    [],
                    $context->inside_loop,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                );

                $right_context->vars_in_scope = $right_vars_in_scope;

                if ($context->if_scope) {
                    $context->if_scope->if_cond_changed_var_ids += $changed_var_ids;
                }
            }

            $partitioned_clauses = Context::removeReconciledClauses($left_clauses, $changed_var_ids);

            $right_context->clauses = $partitioned_clauses[0];

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->right, $right_context) === false) {
                return false;
            }

            $context->referenced_var_ids = array_merge(
                $right_context->referenced_var_ids,
                $left_context->referenced_var_ids
            );

            if ($codebase->find_unused_variables) {
                $context->unreferenced_vars = $right_context->unreferenced_vars;
            }

            if ($context->inside_conditional) {
                $context->updateChecks($right_context);

                $context->vars_possibly_in_scope = array_merge(
                    $right_context->vars_possibly_in_scope,
                    $left_context->vars_possibly_in_scope
                );

                $context->assigned_var_ids = array_merge(
                    $left_context->assigned_var_ids,
                    $right_context->assigned_var_ids
                );
            }

            if ($context->if_context && !$context->inside_negation) {
                $context->vars_in_scope = $right_context->vars_in_scope;
                $if_context = $context->if_context;

                foreach ($right_context->vars_in_scope as $var_id => $type) {
                    if (!isset($if_context->vars_in_scope[$var_id])) {
                        $if_context->vars_in_scope[$var_id] = $type;
                    } elseif (isset($context->vars_in_scope[$var_id])) {
                        $if_context->vars_in_scope[$var_id] = $context->vars_in_scope[$var_id];
                    }
                }

                $if_context->referenced_var_ids = array_merge(
                    $context->referenced_var_ids,
                    $if_context->referenced_var_ids
                );

                $if_context->assigned_var_ids = array_merge(
                    $context->assigned_var_ids,
                    $if_context->assigned_var_ids
                );

                if ($codebase->find_unused_variables) {
                    $if_context->unreferenced_vars = $context->unreferenced_vars;
                }

                $if_context->reconciled_expression_clauses = array_merge(
                    $if_context->reconciled_expression_clauses,
                    array_map(
                        function ($c) {
                            return $c->getHash();
                        },
                        $partitioned_clauses[1]
                    )
                );

                $if_context->vars_possibly_in_scope = array_merge(
                    $context->vars_possibly_in_scope,
                    $if_context->vars_possibly_in_scope
                );

                $if_context->updateChecks($context);
            } else {
                $context->vars_in_scope = $left_context->vars_in_scope;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            if ($from_stmt) {
                $fake_if_stmt = new PhpParser\Node\Stmt\If_(
                    new PhpParser\Node\Expr\BooleanNot($stmt->left, $stmt->left->getAttributes()),
                    [
                        'stmts' => [
                            new PhpParser\Node\Stmt\Expression(
                                $stmt->right
                            )
                        ]
                    ],
                    $stmt->getAttributes()
                );

                if (IfAnalyzer::analyze($statements_analyzer, $fake_if_stmt, $context) === false) {
                    return false;
                }

                return null;
            }

            if (!$stmt->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                && !($stmt->left instanceof PhpParser\Node\Expr\BooleanNot
                    && $stmt->left->expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd)
            ) {
                $if_scope = new \Psalm\Internal\Scope\IfScope();

                try {
                    $if_conditional_scope = IfAnalyzer::analyzeIfConditional(
                        $statements_analyzer,
                        $stmt->left,
                        $context,
                        $codebase,
                        $if_scope,
                        $context->branch_point ?: (int) $stmt->getAttribute('startFilePos')
                    );

                    $left_context = $if_conditional_scope->if_context;

                    $left_referenced_var_ids = $if_conditional_scope->cond_referenced_var_ids;
                    $left_assigned_var_ids = $if_conditional_scope->cond_assigned_var_ids;
                } catch (\Psalm\Exception\ScopeAnalysisException $e) {
                    return false;
                }
            } else {
                $pre_referenced_var_ids = $context->referenced_var_ids;
                $context->referenced_var_ids = [];

                $pre_assigned_var_ids = $context->assigned_var_ids;

                $left_context = clone $context;
                $left_context->parent_context = $context;
                $left_context->if_context = null;
                $left_context->assigned_var_ids = [];

                if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, $left_context) === false) {
                    return false;
                }

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

                if ($codebase->find_unused_variables) {
                    $context->unreferenced_vars = $left_context->unreferenced_vars;
                }

                $left_referenced_var_ids = $left_context->referenced_var_ids;
                $left_context->referenced_var_ids = array_merge($pre_referenced_var_ids, $left_referenced_var_ids);

                $left_assigned_var_ids = array_diff_key($left_context->assigned_var_ids, $pre_assigned_var_ids);

                $left_referenced_var_ids = array_diff_key($left_referenced_var_ids, $left_assigned_var_ids);
            }

            $left_clauses = Algebra::getFormula(
                \spl_object_id($stmt->left),
                $stmt->left,
                $context->self,
                $statements_analyzer,
                $codebase
            );

            try {
                $negated_left_clauses = Algebra::negateFormula($left_clauses);
            } catch (\Psalm\Exception\ComplicatedExpressionException $e) {
                return false;
            }

            if ($left_context->reconciled_expression_clauses) {
                $reconciled_expression_clauses = $left_context->reconciled_expression_clauses;

                $negated_left_clauses = array_values(
                    array_filter(
                        $negated_left_clauses,
                        function ($c) use ($reconciled_expression_clauses) {
                            return !\in_array($c->getHash(), $reconciled_expression_clauses);
                        }
                    )
                );

                if (\count($negated_left_clauses) === 1
                    && $negated_left_clauses[0]->wedge
                    && !$negated_left_clauses[0]->possibilities
                ) {
                    $negated_left_clauses = [];
                }
            }

            $clauses_for_right_analysis = Algebra::simplifyCNF(
                array_merge(
                    $context->clauses,
                    $negated_left_clauses
                )
            );

            $active_negated_type_assertions = [];

            $negated_type_assertions = Algebra::getTruthsFromFormula(
                $clauses_for_right_analysis,
                \spl_object_id($stmt->left),
                $left_referenced_var_ids,
                $active_negated_type_assertions
            );

            $changed_var_ids = [];

            $right_context = clone $context;

            if ($negated_type_assertions) {
                // while in an or, we allow scope to boil over to support
                // statements of the form if ($x === null || $x->foo())
                $right_vars_in_scope = Reconciler::reconcileKeyedTypes(
                    $negated_type_assertions,
                    $active_negated_type_assertions,
                    $right_context->vars_in_scope,
                    $changed_var_ids,
                    $left_referenced_var_ids,
                    $statements_analyzer,
                    [],
                    $left_context->inside_loop,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                );
                $right_context->vars_in_scope = $right_vars_in_scope;
            }

            $right_context->clauses = $clauses_for_right_analysis;

            if ($changed_var_ids) {
                $partitioned_clauses = Context::removeReconciledClauses($right_context->clauses, $changed_var_ids);
                $right_context->clauses = $partitioned_clauses[0];
                $right_context->reconciled_expression_clauses = array_merge(
                    $context->reconciled_expression_clauses,
                    array_map(
                        function ($c) {
                            return $c->getHash();
                        },
                        $partitioned_clauses[1]
                    )
                );

                $partitioned_clauses = Context::removeReconciledClauses($context->clauses, $changed_var_ids);
                $context->clauses = $partitioned_clauses[0];
                $context->reconciled_expression_clauses = array_merge(
                    $context->reconciled_expression_clauses,
                    array_map(
                        function ($c) {
                            return $c->getHash();
                        },
                        $partitioned_clauses[1]
                    )
                );
            }

            $right_context->if_context = null;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->right, $right_context) === false) {
                return false;
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
                $var_id = ExpressionAnalyzer::getVarId($stmt->left->var, $context->self);

                if ($var_id && isset($left_context->vars_in_scope[$var_id])) {
                    $left_inferred_reconciled = AssertionReconciler::reconcile(
                        '!falsy',
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

            $context->referenced_var_ids = array_merge(
                $right_context->referenced_var_ids,
                $context->referenced_var_ids
            );

            $context->assigned_var_ids = array_merge(
                $context->assigned_var_ids,
                $right_context->assigned_var_ids
            );

            if ($codebase->find_unused_variables) {
                foreach ($right_context->unreferenced_vars as $var_id => $locations) {
                    if (!isset($context->unreferenced_vars[$var_id])) {
                        $context->unreferenced_vars[$var_id] = $locations;
                    } else {
                        $new_locations = array_diff_key(
                            $locations,
                            $context->unreferenced_vars[$var_id]
                        );

                        if ($new_locations) {
                            $context->unreferenced_vars[$var_id] += $locations;
                        }
                    }
                }
            }

            if ($context->if_context) {
                $if_context = $context->if_context;

                foreach ($right_context->vars_in_scope as $var_id => $type) {
                    if (isset($if_context->vars_in_scope[$var_id])) {
                        $if_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $type,
                            $if_context->vars_in_scope[$var_id],
                            $codebase
                        );
                    } elseif (isset($left_context->vars_in_scope[$var_id])) {
                        $if_context->vars_in_scope[$var_id] = $left_context->vars_in_scope[$var_id];
                    }
                }

                $if_context->referenced_var_ids = array_merge(
                    $context->referenced_var_ids,
                    $if_context->referenced_var_ids
                );

                $if_context->assigned_var_ids = array_merge(
                    $context->assigned_var_ids,
                    $if_context->assigned_var_ids
                );

                if ($codebase->find_unused_variables) {
                    $if_context->unreferenced_vars = $context->unreferenced_vars;
                }

                $if_context->updateChecks($context);
            }

            $context->vars_possibly_in_scope = array_merge(
                $right_context->vars_possibly_in_scope,
                $context->vars_possibly_in_scope
            );
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, $context) === false) {
                return false;
            }

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->right, $context) === false) {
                return false;
            }

            $stmt_type = Type::getString();

            self::analyzeConcatOp(
                $statements_analyzer,
                $stmt->left,
                $stmt->right,
                $context,
                $result_type
            );

            if ($result_type) {
                $stmt_type = $result_type;
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
            $t_if_context = clone $context;

            $if_clauses = Algebra::getFormula(
                \spl_object_id($stmt),
                $stmt,
                $context->self,
                $statements_analyzer,
                $codebase
            );

            $mixed_var_ids = [];

            foreach ($context->vars_in_scope as $var_id => $type) {
                if ($type->hasMixed()) {
                    $mixed_var_ids[] = $var_id;
                }
            }

            foreach ($context->vars_possibly_in_scope as $var_id => $_) {
                if (!isset($context->vars_in_scope[$var_id])) {
                    $mixed_var_ids[] = $var_id;
                }
            }

            $if_clauses = array_values(
                array_map(
                    /**
                     * @return \Psalm\Internal\Clause
                     */
                    function (\Psalm\Internal\Clause $c) use ($mixed_var_ids) {
                        $keys = array_keys($c->possibilities);

                        $mixed_var_ids = \array_diff($mixed_var_ids, $keys);

                        foreach ($keys as $key) {
                            foreach ($mixed_var_ids as $mixed_var_id) {
                                if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                                    return new \Psalm\Internal\Clause([], true);
                                }
                            }
                        }

                        return $c;
                    },
                    $if_clauses
                )
            );

            $ternary_clauses = Algebra::simplifyCNF(array_merge($context->clauses, $if_clauses));

            $negated_clauses = Algebra::negateFormula($if_clauses);

            $negated_if_types = Algebra::getTruthsFromFormula($negated_clauses);

            $reconcilable_if_types = Algebra::getTruthsFromFormula($ternary_clauses);

            $changed_var_ids = [];

            if ($reconcilable_if_types) {
                $t_if_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                    $reconcilable_if_types,
                    [],
                    $t_if_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    [],
                    $t_if_context->inside_loop,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->left)
                );

                foreach ($context->vars_in_scope as $var_id => $_) {
                    if (isset($t_if_vars_in_scope_reconciled[$var_id])) {
                        $t_if_context->vars_in_scope[$var_id] = $t_if_vars_in_scope_reconciled[$var_id];
                    }
                }
            }

            if (!self::hasArrayDimFetch($stmt->left)) {
                // check first if the variable was good

                IssueBuffer::startRecording();

                ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, clone $context);

                IssueBuffer::clearRecordingLevel();
                IssueBuffer::stopRecording();

                $naive_type = $statements_analyzer->node_data->getType($stmt->left);

                if ($naive_type
                    && !$naive_type->possibly_undefined
                    && !$naive_type->hasMixed()
                    && !$naive_type->isNullable()
                ) {
                    $var_id = ExpressionAnalyzer::getVarId($stmt->left, $context->self);

                    if (!$var_id
                        || ($var_id !== '$_SESSION' && $var_id !== '$_SERVER' && !isset($changed_var_ids[$var_id]))
                    ) {
                        if ($naive_type->from_docblock) {
                            if (IssueBuffer::accepts(
                                new \Psalm\Issue\DocblockTypeContradiction(
                                    $naive_type->getId() . ' does not contain null',
                                    new CodeLocation($statements_analyzer, $stmt->left)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new \Psalm\Issue\TypeDoesNotContainType(
                                    $naive_type->getId() . ' is always defined and non-null',
                                    new CodeLocation($statements_analyzer, $stmt->left)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }
                }
            }

            $t_if_context->inside_isset = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, $t_if_context) === false) {
                return false;
            }

            $t_if_context->inside_isset = false;

            foreach ($t_if_context->vars_in_scope as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $context->vars_in_scope[$var_id],
                        $type,
                        $codebase
                    );
                } else {
                    $context->vars_in_scope[$var_id] = $type;
                }
            }

            $context->referenced_var_ids = array_merge(
                $context->referenced_var_ids,
                $t_if_context->referenced_var_ids
            );

            if ($codebase->find_unused_variables) {
                $context->unreferenced_vars = array_intersect_key(
                    $t_if_context->unreferenced_vars,
                    $context->unreferenced_vars
                );
            }

            $t_else_context = clone $context;

            if ($negated_if_types) {
                $t_else_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                    $negated_if_types,
                    [],
                    $t_else_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    [],
                    $t_else_context->inside_loop,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->right)
                );

                $t_else_context->vars_in_scope = $t_else_vars_in_scope_reconciled;
            }

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->right, $t_else_context) === false) {
                return false;
            }

            $context->referenced_var_ids = array_merge(
                $context->referenced_var_ids,
                $t_else_context->referenced_var_ids
            );

            if ($codebase->find_unused_variables) {
                $context->unreferenced_vars = array_intersect_key(
                    $t_else_context->unreferenced_vars,
                    $context->unreferenced_vars
                );
            }

            $lhs_type = null;

            if ($stmt_left_type = $statements_analyzer->node_data->getType($stmt->left)) {
                $if_return_type_reconciled = AssertionReconciler::reconcile(
                    'isset',
                    clone $stmt_left_type,
                    '',
                    $statements_analyzer,
                    $context->inside_loop,
                    [],
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $statements_analyzer->getSuppressedIssues()
                );

                $lhs_type = clone $if_return_type_reconciled;
            }

            $stmt_right_type = null;

            if (!$lhs_type || !($stmt_right_type = $statements_analyzer->node_data->getType($stmt->right))) {
                $stmt_type = Type::getMixed();

                $statements_analyzer->node_data->setType($stmt, $stmt_type);
            } else {
                $stmt_type = Type::combineUnionTypes(
                    $lhs_type,
                    $stmt_right_type,
                    $codebase
                );

                $statements_analyzer->node_data->setType($stmt, $stmt_type);
            }
        } else {
            if ($stmt->left instanceof PhpParser\Node\Expr\BinaryOp) {
                if (self::analyze($statements_analyzer, $stmt->left, $context, ++$nesting) === false) {
                    return false;
                }
            } else {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->left, $context) === false) {
                    return false;
                }
            }

            if ($stmt->right instanceof PhpParser\Node\Expr\BinaryOp) {
                if (self::analyze($statements_analyzer, $stmt->right, $context, ++$nesting) === false) {
                    return false;
                }
            } else {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->right, $context) === false) {
                    return false;
                }
            }
        }

        $stmt_left_type = $statements_analyzer->node_data->getType($stmt->left);
        $stmt_right_type = $statements_analyzer->node_data->getType($stmt->right);

        // let's do some fun type assignment
        if ($stmt_left_type && $stmt_right_type) {
            if ($stmt_left_type->hasString()
                && $stmt_right_type->hasString()
                && ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd
                )
            ) {
                $stmt_type = Type::getString();

                $statements_analyzer->node_data->setType($stmt, $stmt_type);
            } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mod
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Pow
                || (($stmt_left_type->hasInt() || $stmt_right_type->hasInt())
                    && ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr
                        || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
                        || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd
                        || $stmt instanceof PhpParser\Node\Expr\BinaryOp\ShiftLeft
                        || $stmt instanceof PhpParser\Node\Expr\BinaryOp\ShiftRight
                    )
                )
            ) {
                self::analyzeNonDivArithmeticOp(
                    $statements_analyzer,
                    $statements_analyzer->node_data,
                    $stmt->left,
                    $stmt->right,
                    $stmt,
                    $result_type,
                    $context
                );

                if ($result_type) {
                    $statements_analyzer->node_data->setType($stmt, $result_type);
                }
            } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
                && ($stmt_left_type->hasBool() || $stmt_right_type->hasBool())
            ) {
                $statements_analyzer->node_data->setType($stmt, Type::getInt());
            } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalXor
                && ($stmt_left_type->hasBool() || $stmt_right_type->hasBool())
            ) {
                $statements_analyzer->node_data->setType($stmt, Type::getBool());
            } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div) {
                self::analyzeNonDivArithmeticOp(
                    $statements_analyzer,
                    $statements_analyzer->node_data,
                    $stmt->left,
                    $stmt->right,
                    $stmt,
                    $result_type,
                    $context
                );

                if ($result_type) {
                    if ($result_type->hasInt()) {
                        $result_type->addType(new TFloat);
                    }

                    $statements_analyzer->node_data->setType($stmt, $result_type);
                }
            } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr) {
                self::analyzeNonDivArithmeticOp(
                    $statements_analyzer,
                    $statements_analyzer->node_data,
                    $stmt->left,
                    $stmt->right,
                    $stmt,
                    $result_type,
                    $context
                );
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Greater
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Smaller
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
        ) {
            $statements_analyzer->node_data->setType($stmt, Type::getBool());
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
            && $stmt_left_type
            && $stmt_right_type
            && $context->mutation_free
        ) {
            if ($stmt_left_type->hasString() && $stmt_right_type->hasObjectType()) {
                foreach ($stmt_right_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TNamedObject) {
                        try {
                            $storage = $codebase->methods->getStorage(
                                new \Psalm\Internal\MethodIdentifier(
                                    $atomic_type->value,
                                    '__tostring'
                                )
                            );
                        } catch (\UnexpectedValueException $e) {
                            continue;
                        }

                        if (!$storage->mutation_free) {
                            if (IssueBuffer::accepts(
                                new ImpureMethodCall(
                                    'Cannot call a possibly-mutating method '
                                        . $atomic_type->value . '::__toString from a pure context',
                                    new CodeLocation($statements_analyzer, $stmt)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }
                }
            } elseif ($stmt_right_type->hasString() && $stmt_left_type->hasObjectType()) {
                foreach ($stmt_left_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TNamedObject) {
                        try {
                            $storage = $codebase->methods->getStorage(
                                new \Psalm\Internal\MethodIdentifier(
                                    $atomic_type->value,
                                    '__tostring'
                                )
                            );
                        } catch (\UnexpectedValueException $e) {
                            continue;
                        }

                        if (!$storage->mutation_free) {
                            if (IssueBuffer::accepts(
                                new ImpureMethodCall(
                                    'Cannot call a possibly-mutating method '
                                        . $atomic_type->value . '::__toString from a pure context',
                                    new CodeLocation($statements_analyzer, $stmt)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }
                }
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Spaceship) {
            $statements_analyzer->node_data->setType($stmt, Type::getInt());
        }

        return null;
    }

    private static function hasArrayDimFetch(PhpParser\Node\Expr $expr) : bool
    {
        if ($expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            return true;
        }

        if ($expr instanceof PhpParser\Node\Expr\PropertyFetch
            || $expr instanceof PhpParser\Node\Expr\MethodCall
        ) {
            return self::hasArrayDimFetch($expr->var);
        }

        return false;
    }

    public static function analyzeNonDivArithmeticOp(
        ?StatementsSource $statements_source,
        \Psalm\Internal\Provider\NodeDataProvider $nodes,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        PhpParser\Node $parent,
        ?Type\Union &$result_type = null,
        ?Context $context = null
    ) : void {
        $codebase = $statements_source ? $statements_source->getCodebase() : null;

        $left_type = $nodes->getType($left);
        $right_type = $nodes->getType($right);
        $config = Config::getInstance();

        if ($left_type && $right_type) {
            if ($left_type->isNull()) {
                if ($statements_source && IssueBuffer::accepts(
                    new NullOperand(
                        'Left operand cannot be null',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($left_type->isNullable() && !$left_type->ignore_nullable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Left operand cannot be nullable, got ' . $left_type,
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isNull()) {
                if ($statements_source && IssueBuffer::accepts(
                    new NullOperand(
                        'Right operand cannot be null',
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isNullable() && !$right_type->ignore_nullable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Right operand cannot be nullable, got ' . $right_type,
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($left_type->isFalse()) {
                if ($statements_source && IssueBuffer::accepts(
                    new FalseOperand(
                        'Left operand cannot be false',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($left_type->isFalsable() && !$left_type->ignore_falsable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyFalseOperand(
                        'Left operand cannot be falsable, got ' . $left_type,
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isFalse()) {
                if ($statements_source && IssueBuffer::accepts(
                    new FalseOperand(
                        'Right operand cannot be false',
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isFalsable() && !$right_type->ignore_falsable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyFalseOperand(
                        'Right operand cannot be falsable, got ' . $right_type,
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $invalid_left_messages = [];
            $invalid_right_messages = [];
            $has_valid_left_operand = false;
            $has_valid_right_operand = false;
            $has_string_increment = false;

            foreach ($left_type->getAtomicTypes() as $left_type_part) {
                foreach ($right_type->getAtomicTypes() as $right_type_part) {
                    $candidate_result_type = self::analyzeNonDivOperands(
                        $statements_source,
                        $codebase,
                        $config,
                        $context,
                        $left,
                        $right,
                        $parent,
                        $left_type_part,
                        $right_type_part,
                        $invalid_left_messages,
                        $invalid_right_messages,
                        $has_valid_left_operand,
                        $has_valid_right_operand,
                        $has_string_increment,
                        $result_type
                    );

                    if ($candidate_result_type) {
                        $result_type = $candidate_result_type;
                        return;
                    }
                }
            }

            if ($invalid_left_messages && $statements_source) {
                $first_left_message = $invalid_left_messages[0];

                if ($has_valid_left_operand) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidOperand(
                            $first_left_message,
                            new CodeLocation($statements_source, $left)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidOperand(
                            $first_left_message,
                            new CodeLocation($statements_source, $left)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if ($invalid_right_messages && $statements_source) {
                $first_right_message = $invalid_right_messages[0];

                if ($has_valid_right_operand) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidOperand(
                            $first_right_message,
                            new CodeLocation($statements_source, $right)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidOperand(
                            $first_right_message,
                            new CodeLocation($statements_source, $right)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if ($has_string_increment && $statements_source) {
                if (IssueBuffer::accepts(
                    new StringIncrement(
                        'Possibly unintended string increment',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }
    }

    /**
     * @param  string[]        &$invalid_left_messages
     * @param  string[]        &$invalid_right_messages
     *
     * @return Type\Union|null
     */
    private static function analyzeNonDivOperands(
        ?StatementsSource $statements_source,
        ?\Psalm\Codebase $codebase,
        Config $config,
        ?Context $context,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        PhpParser\Node $parent,
        Type\Atomic $left_type_part,
        Type\Atomic $right_type_part,
        array &$invalid_left_messages,
        array &$invalid_right_messages,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        bool &$has_string_increment,
        Type\Union &$result_type = null
    ) {
        if ($left_type_part instanceof TNull || $right_type_part instanceof TNull) {
            // null case is handled above
            return;
        }

        if ($left_type_part instanceof TFalse || $right_type_part instanceof TFalse) {
            // null case is handled above
            return;
        }

        if ($left_type_part instanceof Type\Atomic\TString
            && $right_type_part instanceof TInt
            && $parent instanceof PhpParser\Node\Expr\PostInc
        ) {
            $has_string_increment = true;

            if (!$result_type) {
                $result_type = Type::getString();
            } else {
                $result_type = Type::combineUnionTypes(Type::getString(), $result_type);
            }

            $has_valid_left_operand = true;
            $has_valid_right_operand = true;

            return;
        }

        if ($left_type_part instanceof TTemplateParam
            && $right_type_part instanceof TTemplateParam
        ) {
            $combined_type = Type::combineUnionTypes(
                $left_type_part->as,
                $right_type_part->as
            );

            $combined_atomic_types = array_values($combined_type->getAtomicTypes());

            if (\count($combined_atomic_types) <= 2) {
                $left_type_part = $combined_atomic_types[0];
                $right_type_part = $combined_atomic_types[1] ?? $combined_atomic_types[0];
            }
        }

        if ($left_type_part instanceof TMixed
            || $right_type_part instanceof TMixed
            || $left_type_part instanceof TTemplateParam
            || $right_type_part instanceof TTemplateParam
        ) {
            if ($statements_source && $codebase && $context) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_source->getFilePath() === $statements_source->getRootFilePath()
                    && (!(($source = $statements_source->getSource())
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                        || !$source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_source->getFilePath());
                }
            }

            if ($left_type_part instanceof TMixed || $left_type_part instanceof TTemplateParam) {
                if ($statements_source && IssueBuffer::accepts(
                    new MixedOperand(
                        'Left operand cannot be mixed',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if ($statements_source && IssueBuffer::accepts(
                    new MixedOperand(
                        'Right operand cannot be mixed',
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($left_type_part instanceof TMixed
                && $left_type_part->from_loop_isset
                && $parent instanceof PhpParser\Node\Expr\AssignOp\Plus
                && !$right_type_part instanceof TMixed
            ) {
                $result_type_member = new Type\Union([$right_type_part]);

                if (!$result_type) {
                    $result_type = $result_type_member;
                } else {
                    $result_type = Type::combineUnionTypes($result_type_member, $result_type);
                }

                return;
            }

            $from_loop_isset = (!($left_type_part instanceof TMixed) || $left_type_part->from_loop_isset)
                && (!($right_type_part instanceof TMixed) || $right_type_part->from_loop_isset);

            $result_type = Type::getMixed($from_loop_isset);

            return $result_type;
        }

        if ($statements_source && $codebase && $context) {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_source->getFilePath() === $statements_source->getRootFilePath()
                && (!(($parent_source = $statements_source->getSource())
                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementNonMixedCount($statements_source->getFilePath());
            }
        }

        if ($left_type_part instanceof TArray
            || $right_type_part instanceof TArray
            || $left_type_part instanceof ObjectLike
            || $right_type_part instanceof ObjectLike
            || $left_type_part instanceof TList
            || $right_type_part instanceof TList
        ) {
            if ((!$right_type_part instanceof TArray
                    && !$right_type_part instanceof ObjectLike
                    && !$right_type_part instanceof TList)
                || (!$left_type_part instanceof TArray
                    && !$left_type_part instanceof ObjectLike
                    && !$left_type_part instanceof TList)
            ) {
                if (!$left_type_part instanceof TArray
                    && !$left_type_part instanceof ObjectLike
                    && !$left_type_part instanceof TList
                ) {
                    $invalid_left_messages[] = 'Cannot add an array to a non-array ' . $left_type_part;
                } else {
                    $invalid_right_messages[] = 'Cannot add an array to a non-array ' . $right_type_part;
                }

                if ($left_type_part instanceof TArray
                    || $left_type_part instanceof ObjectLike
                    || $left_type_part instanceof TList
                ) {
                    $has_valid_left_operand = true;
                } elseif ($right_type_part instanceof TArray
                    || $right_type_part instanceof ObjectLike
                    || $right_type_part instanceof TList
                ) {
                    $has_valid_right_operand = true;
                }

                $result_type = Type::getArray();

                return;
            }

            $has_valid_right_operand = true;
            $has_valid_left_operand = true;

            if ($left_type_part instanceof ObjectLike
                && $right_type_part instanceof ObjectLike
            ) {
                $definitely_existing_mixed_right_properties = array_diff_key(
                    $right_type_part->properties,
                    $left_type_part->properties
                );

                $properties = $left_type_part->properties + $right_type_part->properties;

                if (!$left_type_part->sealed) {
                    foreach ($definitely_existing_mixed_right_properties as $key => $type) {
                        $properties[$key] = Type::combineUnionTypes(Type::getMixed(), $type);
                    }
                }

                $result_type_member = new Type\Union([new ObjectLike($properties)]);
            } else {
                $result_type_member = TypeCombination::combineTypes(
                    [$left_type_part, $right_type_part],
                    $codebase,
                    true
                );
            }

            if (!$result_type) {
                $result_type = $result_type_member;
            } else {
                $result_type = Type::combineUnionTypes($result_type_member, $result_type, $codebase, true);
            }

            if ($left instanceof PhpParser\Node\Expr\ArrayDimFetch
                && $context
                && $statements_source instanceof StatementsAnalyzer
            ) {
                ArrayAssignmentAnalyzer::updateArrayType(
                    $statements_source,
                    $left,
                    $right,
                    $result_type,
                    $context
                );
            }

            return;
        }

        if (($left_type_part instanceof TNamedObject && strtolower($left_type_part->value) === 'gmp')
            || ($right_type_part instanceof TNamedObject && strtolower($right_type_part->value) === 'gmp')
        ) {
            if ((($left_type_part instanceof TNamedObject
                        && strtolower($left_type_part->value) === 'gmp')
                    && (($right_type_part instanceof TNamedObject
                            && strtolower($right_type_part->value) === 'gmp')
                        || ($right_type_part->isNumericType() || $right_type_part instanceof TMixed)))
                || (($right_type_part instanceof TNamedObject
                        && strtolower($right_type_part->value) === 'gmp')
                    && (($left_type_part instanceof TNamedObject
                            && strtolower($left_type_part->value) === 'gmp')
                        || ($left_type_part->isNumericType() || $left_type_part instanceof TMixed)))
            ) {
                if (!$result_type) {
                    $result_type = new Type\Union([new TNamedObject('GMP')]);
                } else {
                    $result_type = Type::combineUnionTypes(
                        new Type\Union([new TNamedObject('GMP')]),
                        $result_type
                    );
                }
            } else {
                if ($statements_source && IssueBuffer::accepts(
                    new InvalidOperand(
                        'Cannot add GMP to non-numeric type',
                        new CodeLocation($statements_source, $parent)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            return;
        }

        if ($left_type_part->isNumericType() || $right_type_part->isNumericType()) {
            if (($left_type_part instanceof TNumeric || $right_type_part instanceof TNumeric)
                && ($left_type_part->isNumericType() && $right_type_part->isNumericType())
            ) {
                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = Type::getInt();
                } elseif (!$result_type) {
                    $result_type = Type::getNumeric();
                } else {
                    $result_type = Type::combineUnionTypes(Type::getNumeric(), $result_type);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return;
            }

            if ($left_type_part instanceof TInt && $right_type_part instanceof TInt) {
                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = Type::getInt();
                } elseif (!$result_type) {
                    $result_type = Type::getInt(true);
                } else {
                    $result_type = Type::combineUnionTypes(Type::getInt(true), $result_type);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return;
            }

            if ($left_type_part instanceof TFloat && $right_type_part instanceof TFloat) {
                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = Type::getInt();
                } elseif (!$result_type) {
                    $result_type = Type::getFloat();
                } else {
                    $result_type = Type::combineUnionTypes(Type::getFloat(), $result_type);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return;
            }

            if (($left_type_part instanceof TFloat && $right_type_part instanceof TInt)
                || ($left_type_part instanceof TInt && $right_type_part instanceof TFloat)
            ) {
                if ($config->strict_binary_operands) {
                    if ($statements_source && IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot add ints to floats',
                            new CodeLocation($statements_source, $parent)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = Type::getInt();
                } elseif (!$result_type) {
                    $result_type = Type::getFloat();
                } else {
                    $result_type = Type::combineUnionTypes(Type::getFloat(), $result_type);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return;
            }

            if ($left_type_part->isNumericType() && $right_type_part->isNumericType()) {
                if ($config->strict_binary_operands) {
                    if ($statements_source && IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot add numeric types together, please cast explicitly',
                            new CodeLocation($statements_source, $parent)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = Type::getInt();
                } elseif (!$result_type) {
                    $result_type = Type::getFloat();
                } else {
                    $result_type = Type::combineUnionTypes(Type::getFloat(), $result_type);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return;
            }

            if (!$left_type_part->isNumericType()) {
                $invalid_left_messages[] = 'Cannot perform a numeric operation with a non-numeric type '
                    . $left_type_part;
                $has_valid_right_operand = true;
            } else {
                $invalid_right_messages[] = 'Cannot perform a numeric operation with a non-numeric type '
                    . $right_type_part;
                $has_valid_left_operand = true;
            }
        } else {
            $invalid_left_messages[] =
                'Cannot perform a numeric operation with non-numeric types ' . $left_type_part
                    . ' and ' . $right_type_part;
        }
    }

    /**
     * @param  StatementsAnalyzer     $statements_analyzer
     * @param  PhpParser\Node\Expr   $left
     * @param  PhpParser\Node\Expr   $right
     * @param  Type\Union|null       &$result_type
     *
     * @return void
     */
    public static function analyzeConcatOp(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        Context $context,
        Type\Union &$result_type = null
    ) {
        $codebase = $statements_analyzer->getCodebase();

        $left_type = $statements_analyzer->node_data->getType($left);
        $right_type = $statements_analyzer->node_data->getType($right);
        $config = Config::getInstance();

        if ($left_type && $right_type) {
            $result_type = Type::getString();

            if ($left_type->hasMixed() || $right_type->hasMixed()) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                if ($left_type->hasMixed()) {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Left operand cannot be mixed',
                            new CodeLocation($statements_analyzer->getSource(), $left)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Right operand cannot be mixed',
                            new CodeLocation($statements_analyzer->getSource(), $right)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                return;
            }

            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
            }

            if ($left_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullOperand(
                        'Cannot concatenate with a ' . $left_type,
                        new CodeLocation($statements_analyzer->getSource(), $left)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullOperand(
                        'Cannot concatenate with a ' . $right_type,
                        new CodeLocation($statements_analyzer->getSource(), $right)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($left_type->isFalse()) {
                if (IssueBuffer::accepts(
                    new FalseOperand(
                        'Cannot concatenate with a ' . $left_type,
                        new CodeLocation($statements_analyzer->getSource(), $left)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isFalse()) {
                if (IssueBuffer::accepts(
                    new FalseOperand(
                        'Cannot concatenate with a ' . $right_type,
                        new CodeLocation($statements_analyzer->getSource(), $right)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($left_type->isNullable() && !$left_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Cannot concatenate with a possibly null ' . $left_type,
                        new CodeLocation($statements_analyzer->getSource(), $left)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isNullable() && !$right_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Cannot concatenate with a possibly null ' . $right_type,
                        new CodeLocation($statements_analyzer->getSource(), $right)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($left_type->isFalsable() && !$left_type->ignore_falsable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyFalseOperand(
                        'Cannot concatenate with a possibly false ' . $left_type,
                        new CodeLocation($statements_analyzer->getSource(), $left)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isFalsable() && !$right_type->ignore_falsable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyFalseOperand(
                        'Cannot concatenate with a possibly false ' . $right_type,
                        new CodeLocation($statements_analyzer->getSource(), $right)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $left_type_match = true;
            $right_type_match = true;

            $has_valid_left_operand = false;
            $has_valid_right_operand = false;

            $left_comparison_result = new \Psalm\Internal\Analyzer\TypeComparisonResult();
            $right_comparison_result = new \Psalm\Internal\Analyzer\TypeComparisonResult();

            foreach ($left_type->getAtomicTypes() as $left_type_part) {
                if ($left_type_part instanceof Type\Atomic\TTemplateParam) {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Left operand cannot be mixed',
                            new CodeLocation($statements_analyzer->getSource(), $left)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return;
                }

                if ($left_type_part instanceof Type\Atomic\TNull || $left_type_part instanceof Type\Atomic\TFalse) {
                    continue;
                }

                $left_type_part_match = TypeAnalyzer::isAtomicContainedBy(
                    $codebase,
                    $left_type_part,
                    new Type\Atomic\TString,
                    false,
                    false,
                    $left_comparison_result
                );

                $left_type_match = $left_type_match && $left_type_part_match;

                $has_valid_left_operand = $has_valid_left_operand || $left_type_part_match;

                if ($left_comparison_result->to_string_cast && $config->strict_binary_operands) {
                    if (IssueBuffer::accepts(
                        new ImplicitToStringCast(
                            'Left side of concat op expects string, '
                                . '\'' . $left_type . '\' provided with a __toString method',
                            new CodeLocation($statements_analyzer->getSource(), $left)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($context->mutation_free) {
                    foreach ($left_type->getAtomicTypes() as $atomic_type) {
                        if ($atomic_type instanceof TNamedObject) {
                            try {
                                $storage = $codebase->methods->getStorage(
                                    new \Psalm\Internal\MethodIdentifier(
                                        $atomic_type->value,
                                        '__tostring'
                                    )
                                );
                            } catch (\UnexpectedValueException $e) {
                                continue;
                            }

                            if (!$storage->mutation_free) {
                                if (IssueBuffer::accepts(
                                    new ImpureMethodCall(
                                        'Cannot call a possibly-mutating method '
                                            . $atomic_type->value . '::__toString from a pure context',
                                        new CodeLocation($statements_analyzer, $left)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            }
                        }
                    }
                }
            }

            foreach ($right_type->getAtomicTypes() as $right_type_part) {
                if ($right_type_part instanceof Type\Atomic\TTemplateParam) {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Right operand cannot be a template param',
                            new CodeLocation($statements_analyzer->getSource(), $right)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return;
                }

                if ($right_type_part instanceof Type\Atomic\TNull || $right_type_part instanceof Type\Atomic\TFalse) {
                    continue;
                }

                $right_type_part_match = TypeAnalyzer::isAtomicContainedBy(
                    $codebase,
                    $right_type_part,
                    new Type\Atomic\TString,
                    false,
                    false,
                    $right_comparison_result
                );

                $right_type_match = $right_type_match && $right_type_part_match;

                $has_valid_right_operand = $has_valid_right_operand || $right_type_part_match;

                if ($right_comparison_result->to_string_cast && $config->strict_binary_operands) {
                    if (IssueBuffer::accepts(
                        new ImplicitToStringCast(
                            'Right side of concat op expects string, '
                                . '\'' . $right_type . '\' provided with a __toString method',
                            new CodeLocation($statements_analyzer->getSource(), $right)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($context->mutation_free) {
                    foreach ($right_type->getAtomicTypes() as $atomic_type) {
                        if ($atomic_type instanceof TNamedObject) {
                            try {
                                $storage = $codebase->methods->getStorage(
                                    new \Psalm\Internal\MethodIdentifier(
                                        $atomic_type->value,
                                        '__tostring'
                                    )
                                );
                            } catch (\UnexpectedValueException $e) {
                                continue;
                            }

                            if (!$storage->mutation_free) {
                                if (IssueBuffer::accepts(
                                    new ImpureMethodCall(
                                        'Cannot call a possibly-mutating method '
                                            . $atomic_type->value . '::__toString from a pure context',
                                        new CodeLocation($statements_analyzer, $right)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            }
                        }
                    }
                }
            }

            if (!$left_type_match
                && (!$left_comparison_result->scalar_type_match_found || $config->strict_binary_operands)
            ) {
                if ($has_valid_left_operand) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidOperand(
                            'Cannot concatenate with a ' . $left_type,
                            new CodeLocation($statements_analyzer->getSource(), $left)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot concatenate with a ' . $left_type,
                            new CodeLocation($statements_analyzer->getSource(), $left)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if (!$right_type_match
                && (!$right_comparison_result->scalar_type_match_found || $config->strict_binary_operands)
            ) {
                if ($has_valid_right_operand) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidOperand(
                            'Cannot concatenate with a ' . $right_type,
                            new CodeLocation($statements_analyzer->getSource(), $right)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot concatenate with a ' . $right_type,
                            new CodeLocation($statements_analyzer->getSource(), $right)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        // When concatenating two known string literals (with only one possibility),
        // put the concatenated string into $result_type
        if ($left_type && $right_type && $left_type->isSingleStringLiteral() && $right_type->isSingleStringLiteral()) {
            $literal = $left_type->getSingleStringLiteral()->value . $right_type->getSingleStringLiteral()->value;
            if (strlen($literal) <= 1000) {
                // Limit these to 10000 bytes to avoid extremely large union types from repeated concatenations, etc
                $result_type = Type::getString($literal);
            }
        } else {
            if ($left_type
                && $right_type
            ) {
                $left_type_literal_value = $left_type->isSingleStringLiteral()
                    ? $left_type->getSingleStringLiteral()->value
                    : null;

                $right_type_literal_value = $right_type->isSingleStringLiteral()
                    ? $right_type->getSingleStringLiteral()->value
                    : null;

                if (($left_type->getId() === 'lowercase-string'
                        || ($left_type_literal_value !== null
                            && strtolower($left_type_literal_value) === $left_type_literal_value))
                    && ($right_type->getId() === 'lowercase-string'
                        || ($right_type_literal_value !== null
                            && strtolower($right_type_literal_value) === $right_type_literal_value))
                ) {
                    $result_type = new Type\Union([new Type\Atomic\TLowercaseString()]);
                } elseif ($left_type->getId() === 'non-empty-string'
                    || $right_type->getId() === 'non-empty-string'
                    || $left_type_literal_value
                    || $right_type_literal_value
                ) {
                    $result_type = new Type\Union([new Type\Atomic\TNonEmptyString()]);
                }
            }
        }

        if ($codebase->taint && $result_type) {
            $sources = [];
            $either_tainted = 0;

            if ($left_type) {
                $sources = $left_type->sources ?: [];
                $either_tainted = $left_type->tainted;
            }

            if ($right_type) {
                $sources = array_merge($sources, $right_type->sources ?: []);
                $either_tainted = $either_tainted | $right_type->tainted;
            }

            if ($sources) {
                $result_type->sources = $sources;
            }

            if ($either_tainted) {
                $result_type->tainted = $either_tainted;
            }
        }
    }
}
