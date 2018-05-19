<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\Statements\Expression\Assignment\ArrayAssignmentChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\FalseOperand;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyFalseOperand;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\Issue\PossiblyNullOperand;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericParam;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Reconciler;
use Psalm\Type\TypeCombination;
use Psalm\Type\Union;

class BinaryOpChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\BinaryOp    $stmt
     * @param   Context                         $context
     * @param   int                             $nesting
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\BinaryOp $stmt,
        Context $context,
        $nesting = 0
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat && $nesting > 20) {
            // ignore deeply-nested string concatenation
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            $left_clauses = Algebra::getFormula(
                $stmt->left,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            $pre_referenced_var_ids = $context->referenced_var_ids;
            $context->referenced_var_ids = [];
            $original_vars_in_scope = $context->vars_in_scope;

            $pre_assigned_var_ids = $context->assigned_var_ids;

            if (ExpressionChecker::analyze($statements_checker, $stmt->left, $context) === false) {
                return false;
            }

            $new_referenced_var_ids = $context->referenced_var_ids;
            $context->referenced_var_ids = array_merge($pre_referenced_var_ids, $new_referenced_var_ids);

            $new_assigned_var_ids = array_diff_key($context->assigned_var_ids, $pre_assigned_var_ids);

            $new_referenced_var_ids = array_diff_key($new_referenced_var_ids, $new_assigned_var_ids);

            // remove all newly-asserted var ids too
            $new_referenced_var_ids = array_filter(
                $new_referenced_var_ids,
                /**
                 * @param string $var_id
                 *
                 * @return bool
                 */
                function ($var_id) use ($original_vars_in_scope) {
                    return isset($original_vars_in_scope[$var_id]);
                },
                ARRAY_FILTER_USE_KEY
            );

            $simplified_clauses = Algebra::simplifyCNF(array_merge($context->clauses, $left_clauses));

            $left_type_assertions = Algebra::getTruthsFromFormula($simplified_clauses);

            $changed_var_ids = [];

            // while in an and, we allow scope to boil over to support
            // statements of the form if ($x && $x->foo())
            $op_vars_in_scope = Reconciler::reconcileKeyedTypes(
                $left_type_assertions,
                $context->vars_in_scope,
                $changed_var_ids,
                $new_referenced_var_ids,
                $statements_checker,
                new CodeLocation($statements_checker->getSource(), $stmt),
                $statements_checker->getSuppressedIssues()
            );

            $op_context = clone $context;
            $op_context->vars_in_scope = $op_vars_in_scope;

            $op_context->removeReconciledClauses($changed_var_ids);

            if (ExpressionChecker::analyze($statements_checker, $stmt->right, $op_context) === false) {
                return false;
            }

            $context->referenced_var_ids = array_merge(
                $op_context->referenced_var_ids,
                $context->referenced_var_ids
            );

            if ($context->collect_references) {
                $context->unreferenced_vars = $op_context->unreferenced_vars;
            }

            foreach ($op_context->vars_in_scope as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes($context->vars_in_scope[$var_id], $type);
                }
            }

            if ($context->inside_conditional) {
                foreach ($op_context->vars_in_scope as $var => $type) {
                    if (!isset($context->vars_in_scope[$var])) {
                        $context->vars_in_scope[$var] = $type;
                        continue;
                    }
                }

                $context->updateChecks($op_context);

                $context->vars_possibly_in_scope = array_merge(
                    $op_context->vars_possibly_in_scope,
                    $context->vars_possibly_in_scope
                );

                $context->assigned_var_ids = array_merge(
                    $context->assigned_var_ids,
                    $op_context->assigned_var_ids
                );
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
            $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            $pre_referenced_var_ids = $context->referenced_var_ids;
            $context->referenced_var_ids = [];

            $pre_assigned_var_ids = $context->assigned_var_ids;

            if (ExpressionChecker::analyze($statements_checker, $stmt->left, $context) === false) {
                return false;
            }

            $new_referenced_var_ids = $context->referenced_var_ids;
            $context->referenced_var_ids = array_merge($pre_referenced_var_ids, $new_referenced_var_ids);

            $new_assigned_var_ids = array_diff_key($context->assigned_var_ids, $pre_assigned_var_ids);

            $new_referenced_var_ids = array_diff_key($new_referenced_var_ids, $new_assigned_var_ids);

            $left_clauses = Algebra::getFormula(
                $stmt->left,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            $negated_left_clauses = Algebra::negateFormula($left_clauses);

            $clauses_for_right_analysis = Algebra::simplifyCNF(
                array_merge(
                    $context->clauses,
                    $negated_left_clauses
                )
            );

            $negated_type_assertions = Algebra::getTruthsFromFormula($clauses_for_right_analysis);

            $changed_var_ids = [];

            // while in an or, we allow scope to boil over to support
            // statements of the form if ($x === null || $x->foo())
            $op_vars_in_scope = Reconciler::reconcileKeyedTypes(
                $negated_type_assertions,
                $context->vars_in_scope,
                $changed_var_ids,
                $new_referenced_var_ids,
                $statements_checker,
                new CodeLocation($statements_checker->getSource(), $stmt),
                $statements_checker->getSuppressedIssues()
            );

            $op_context = clone $context;
            $op_context->clauses = $clauses_for_right_analysis;
            $op_context->vars_in_scope = $op_vars_in_scope;

            $op_context->removeReconciledClauses($changed_var_ids);

            if (ExpressionChecker::analyze($statements_checker, $stmt->right, $op_context) === false) {
                return false;
            }

            if (!($stmt->right instanceof PhpParser\Node\Expr\Exit_)) {
                foreach ($op_context->vars_in_scope as $var_id => $type) {
                    if (isset($context->vars_in_scope[$var_id])) {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $context->vars_in_scope[$var_id],
                            $type
                        );
                    }
                }
            } elseif ($stmt->left instanceof PhpParser\Node\Expr\Assign) {
                $var_id = ExpressionChecker::getVarId($stmt->left->var, $context->self);

                if ($var_id && isset($context->vars_in_scope[$var_id])) {
                    $left_inferred_reconciled = Reconciler::reconcileTypes(
                        '!falsy',
                        $context->vars_in_scope[$var_id],
                        '',
                        $statements_checker,
                        new CodeLocation($statements_checker->getSource(), $stmt->left),
                        $statements_checker->getSuppressedIssues()
                    );

                    $context->vars_in_scope[$var_id] = $left_inferred_reconciled;
                }
            }

            if ($context->inside_conditional) {
                $context->updateChecks($op_context);
            }

            $context->referenced_var_ids = array_merge(
                $op_context->referenced_var_ids,
                $context->referenced_var_ids
            );

            if ($context->collect_references) {
                $context->unreferenced_vars = array_intersect_key(
                    $op_context->unreferenced_vars,
                    $context->unreferenced_vars
                );
            }

            $context->vars_possibly_in_scope = array_merge(
                $op_context->vars_possibly_in_scope,
                $context->vars_possibly_in_scope
            );
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            $stmt->inferredType = Type::getString();

            if (ExpressionChecker::analyze($statements_checker, $stmt->left, $context) === false) {
                return false;
            }

            if (ExpressionChecker::analyze($statements_checker, $stmt->right, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
            $t_if_context = clone $context;

            $if_clauses = Algebra::getFormula(
                $stmt,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            $ternary_clauses = Algebra::simplifyCNF(array_merge($context->clauses, $if_clauses));

            $negated_clauses = Algebra::negateFormula($if_clauses);

            $negated_if_types = Algebra::getTruthsFromFormula($negated_clauses);

            $reconcilable_if_types = Algebra::getTruthsFromFormula($ternary_clauses);

            $changed_var_ids = [];

            $t_if_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                $reconcilable_if_types,
                $t_if_context->vars_in_scope,
                $changed_var_ids,
                [],
                $statements_checker,
                new CodeLocation($statements_checker->getSource(), $stmt->left),
                $statements_checker->getSuppressedIssues()
            );

            $t_if_context->vars_in_scope = $t_if_vars_in_scope_reconciled;

            if (ExpressionChecker::analyze($statements_checker, $stmt->left, $t_if_context) === false) {
                return false;
            }

            foreach ($t_if_context->vars_in_scope as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes($context->vars_in_scope[$var_id], $type);
                } else {
                    $context->vars_in_scope[$var_id] = $type;
                }
            }

            $context->referenced_var_ids = array_merge(
                $context->referenced_var_ids,
                $t_if_context->referenced_var_ids
            );

            if ($context->collect_references) {
                $context->unreferenced_vars = array_intersect_key(
                    $t_if_context->unreferenced_vars,
                    $context->unreferenced_vars
                );
            }

            $t_else_context = clone $context;

            if ($negated_if_types) {
                $t_else_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                    $negated_if_types,
                    $t_else_context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_checker,
                    new CodeLocation($statements_checker->getSource(), $stmt->right),
                    $statements_checker->getSuppressedIssues()
                );

                $t_else_context->vars_in_scope = $t_else_vars_in_scope_reconciled;
            }

            if (ExpressionChecker::analyze($statements_checker, $stmt->right, $t_else_context) === false) {
                return false;
            }

            $context->referenced_var_ids = array_merge(
                $context->referenced_var_ids,
                $t_else_context->referenced_var_ids
            );

            if ($context->collect_references) {
                $context->unreferenced_vars = array_intersect_key(
                    $t_else_context->unreferenced_vars,
                    $context->unreferenced_vars
                );
            }

            $lhs_type = null;

            if (isset($stmt->left->inferredType)) {
                $if_return_type_reconciled = Reconciler::reconcileTypes(
                    '!null',
                    $stmt->left->inferredType,
                    '',
                    $statements_checker,
                    new CodeLocation($statements_checker->getSource(), $stmt),
                    $statements_checker->getSuppressedIssues()
                );

                $lhs_type = $if_return_type_reconciled;
            }

            if (!$lhs_type || !isset($stmt->right->inferredType)) {
                $stmt->inferredType = Type::getMixed();
            } else {
                $stmt->inferredType = Type::combineUnionTypes($lhs_type, $stmt->right->inferredType);
            }
        } else {
            if ($stmt->left instanceof PhpParser\Node\Expr\BinaryOp) {
                if (self::analyze($statements_checker, $stmt->left, $context, ++$nesting) === false) {
                    return false;
                }
            } else {
                if (ExpressionChecker::analyze($statements_checker, $stmt->left, $context) === false) {
                    return false;
                }
            }

            if ($stmt->right instanceof PhpParser\Node\Expr\BinaryOp) {
                if (self::analyze($statements_checker, $stmt->right, $context, ++$nesting) === false) {
                    return false;
                }
            } else {
                if (ExpressionChecker::analyze($statements_checker, $stmt->right, $context) === false) {
                    return false;
                }
            }
        }

        // let's do some fun type assignment
        if (isset($stmt->left->inferredType) && isset($stmt->right->inferredType)) {
            if ($stmt->left->inferredType->hasString()
                && $stmt->right->inferredType->hasString()
                && ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd
                )
            ) {
                $stmt->inferredType = Type::getString();
            } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mod
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Pow
                || (($stmt->left->inferredType->hasInt() || $stmt->right->inferredType->hasInt())
                    && ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr
                        || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
                        || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd
                        || $stmt instanceof PhpParser\Node\Expr\BinaryOp\ShiftLeft
                        || $stmt instanceof PhpParser\Node\Expr\BinaryOp\ShiftRight
                    )
                )
            ) {
                self::analyzeNonDivArithmenticOp(
                    $statements_checker,
                    $stmt->left,
                    $stmt->right,
                    $stmt,
                    $result_type,
                    $context
                );

                if ($result_type) {
                    $stmt->inferredType = $result_type;
                }
            } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div) {
                $project_checker = $statements_checker->getFileChecker()->project_checker;

                if ($project_checker->infer_types_from_usage
                    && isset($stmt->left->inferredType)
                    && isset($stmt->right->inferredType)
                    && ($stmt->left->inferredType->isMixed() || $stmt->right->inferredType->isMixed())
                ) {
                    $source_checker = $statements_checker->getSource();

                    if ($source_checker instanceof FunctionLikeChecker) {
                        $function_storage = $source_checker->getFunctionLikeStorage($statements_checker);

                        $context->inferType($stmt->left, $function_storage, new Type\Union([new TInt, new TFloat]));
                        $context->inferType($stmt->right, $function_storage, new Type\Union([new TInt, new TFloat]));
                    }
                }

                self::analyzeNonDivArithmenticOp(
                    $statements_checker,
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

                    $stmt->inferredType = $result_type;
                }
            } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
                self::analyzeConcatOp(
                    $statements_checker,
                    $stmt->left,
                    $stmt->right,
                    $context,
                    $result_type
                );

                if ($result_type) {
                    $stmt->inferredType = $result_type;
                }
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
            $stmt->inferredType = Type::getBool();
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Spaceship) {
            $stmt->inferredType = Type::getInt();
        }

        return null;
    }

    /**
     * @param  StatementsSource|null $statements_source
     * @param  PhpParser\Node\Expr   $left
     * @param  PhpParser\Node\Expr   $right
     * @param  PhpParser\Node        $parent
     * @param  Type\Union|null   &$result_type
     *
     * @return void
     */
    public static function analyzeNonDivArithmenticOp(
        $statements_source,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        PhpParser\Node $parent,
        Type\Union &$result_type = null,
        Context $context = null
    ) {
        $project_checker = $statements_source
            ? $statements_source->getFileChecker()->project_checker
            : null;

        $codebase = $project_checker ? $project_checker->codebase : null;

        $left_type = isset($left->inferredType) ? $left->inferredType : null;
        $right_type = isset($right->inferredType) ? $right->inferredType : null;
        $config = Config::getInstance();

        if ($project_checker
            && $project_checker->infer_types_from_usage
            && $statements_source
            && $context
            && $left_type
            && $right_type
            && ($left_type->isMixedNotFromIsset() || $right_type->isMixedNotFromIsset())
            && ($left_type->hasDefinitelyNumericType() || $right_type->hasDefinitelyNumericType())
        ) {
            $source_checker = $statements_source->getSource();
            if ($source_checker instanceof FunctionLikeChecker
                && $statements_source instanceof StatementsChecker
            ) {
                $function_storage = $source_checker->getFunctionLikeStorage($statements_source);

                $context->inferType($left, $function_storage, new Type\Union([new TInt, new TFloat]));
                $context->inferType($right, $function_storage, new Type\Union([new TInt, new TFloat]));
            }
        }

        if ($left_type && $right_type) {
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
            } elseif ($left_type->isNull()) {
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

            if ($right_type->isNullable() && $right_type->ignore_falsable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Right operand cannot be nullable, got ' . $right_type,
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            } elseif ($right_type->isNull()) {
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
            } elseif ($left_type->isFalse()) {
                if ($statements_source && IssueBuffer::accepts(
                    new FalseOperand(
                        'Left operand cannot be null',
                        new CodeLocation($statements_source, $left)
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
            } elseif ($right_type->isFalse()) {
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

            $invalid_left_messages = [];
            $invalid_right_messages = [];
            $has_valid_left_operand = false;
            $has_valid_right_operand = false;

            foreach ($left_type->getTypes() as $left_type_part) {
                foreach ($right_type->getTypes() as $right_type_part) {
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
        }
    }

    /**
     * @param  StatementsSource|null $statements_source
     * @param  \Psalm\Codebase|null  $codebase
     * @param  Context|null $context
     * @param  string[]        &$invalid_left_messages
     * @param  string[]        &$invalid_right_messages
     * @param  bool            &$has_valid_left_operand
     * @param  bool            &$has_valid_right_operand
     *
     * @return Type\Union|null
     */
    public static function analyzeNonDivOperands(
        $statements_source,
        $codebase,
        Config $config,
        $context,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        PhpParser\Node $parent,
        Type\Atomic $left_type_part,
        Type\Atomic $right_type_part,
        array &$invalid_left_messages,
        array &$invalid_right_messages,
        &$has_valid_left_operand,
        &$has_valid_right_operand,
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

        if ($left_type_part instanceof TMixed
            || $right_type_part instanceof TMixed
            || $left_type_part instanceof TGenericParam
            || $right_type_part instanceof TGenericParam
        ) {
            if ($statements_source && $codebase) {
                $codebase->analyzer->incrementMixedCount($statements_source->getCheckedFilePath());
            }

            if ($left_type_part instanceof TMixed || $left_type_part instanceof TGenericParam) {
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
                && $left_type_part->from_isset
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

            $from_isset = (!($left_type_part instanceof TMixed) || $left_type_part->from_isset)
                && (!($right_type_part instanceof TMixed) || $right_type_part->from_isset);

            $result_type = Type::getMixed($from_isset);

            return $result_type;
        }

        if ($statements_source && $codebase) {
            $codebase->analyzer->incrementNonMixedCount($statements_source->getCheckedFilePath());
        }

        if ($left_type_part instanceof TArray
            || $right_type_part instanceof TArray
            || $left_type_part instanceof ObjectLike
            || $right_type_part instanceof ObjectLike
        ) {
            if ((!$right_type_part instanceof TArray && !$right_type_part instanceof ObjectLike)
                || (!$left_type_part instanceof TArray && !$left_type_part instanceof ObjectLike)
            ) {
                if (!$left_type_part instanceof TArray && !$left_type_part instanceof ObjectLike) {
                    $invalid_left_messages[] = 'Cannot add an array to a non-array ' . $left_type_part;
                } else {
                    $invalid_right_messages[] = 'Cannot add an array to a non-array ' . $right_type_part;
                }

                if ($left_type_part instanceof TArray || $left_type_part instanceof ObjectLike) {
                    $has_valid_left_operand = true;
                } elseif ($right_type_part instanceof TArray || $right_type_part instanceof ObjectLike) {
                    $has_valid_right_operand = true;
                }

                $result_type = Type::getArray();

                return;
            }

            $has_valid_right_operand = true;
            $has_valid_left_operand = true;

            if ($left_type_part instanceof ObjectLike && $right_type_part instanceof ObjectLike) {
                $properties = $left_type_part->properties + $right_type_part->properties;

                $result_type_member = new Type\Union([new ObjectLike($properties)]);
            } else {
                $result_type_member = TypeCombination::combineTypes([$left_type_part, $right_type_part]);
            }

            if (!$result_type) {
                $result_type = $result_type_member;
            } else {
                $result_type = Type::combineUnionTypes($result_type_member, $result_type);
            }

            if ($left instanceof PhpParser\Node\Expr\ArrayDimFetch
                && $context
                && $statements_source instanceof StatementsChecker
            ) {
                ArrayAssignmentChecker::updateArrayType(
                    $statements_source,
                    $left,
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
                if (!$result_type) {
                    $result_type = Type::getNumeric();
                } else {
                    $result_type = Type::combineUnionTypes(Type::getNumeric(), $result_type);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return;
            }

            if ($left_type_part instanceof TInt && $right_type_part instanceof TInt) {
                if (!$result_type) {
                    $result_type = Type::getInt(true);
                } else {
                    $result_type = Type::combineUnionTypes(Type::getInt(true), $result_type);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return;
            }

            if ($left_type_part instanceof TFloat && $right_type_part instanceof TFloat) {
                if (!$result_type) {
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

                if (!$result_type) {
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

                if (!$result_type) {
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
     * @param  StatementsChecker     $statements_checker
     * @param  PhpParser\Node\Expr   $left
     * @param  PhpParser\Node\Expr   $right
     * @param  Type\Union|null       &$result_type
     *
     * @return void
     */
    public static function analyzeConcatOp(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        Context $context,
        Type\Union &$result_type = null
    ) {
        $project_checker = $statements_checker->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        $left_type = isset($left->inferredType) ? $left->inferredType : null;
        $right_type = isset($right->inferredType) ? $right->inferredType : null;
        $config = Config::getInstance();

        if ($project_checker->infer_types_from_usage
            && $left_type
            && $right_type
            && ($left_type->isMixed() || $right_type->isMixed())
        ) {
            $source_checker = $statements_checker->getSource();

            if ($source_checker instanceof FunctionLikeChecker) {
                $function_storage = $source_checker->getFunctionLikeStorage($statements_checker);

                $context->inferType($left, $function_storage, Type::getString());
                $context->inferType($right, $function_storage, Type::getString());
            }
        }

        if ($left_type && $right_type) {
            $result_type = Type::getString();

            if ($left_type->isMixed() || $right_type->isMixed()) {
                $codebase->analyzer->incrementMixedCount($statements_checker->getCheckedFilePath());

                if ($left_type->isMixed()) {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Left operand cannot be mixed',
                            new CodeLocation($statements_checker->getSource(), $left)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Right operand cannot be mixed',
                            new CodeLocation($statements_checker->getSource(), $right)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                return;
            }

            $codebase->analyzer->incrementNonMixedCount($statements_checker->getCheckedFilePath());

            if ($left_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullOperand(
                        'Cannot concatenate with a ' . $left_type,
                        new CodeLocation($statements_checker->getSource(), $left)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullOperand(
                        'Cannot concatenate with a ' . $right_type,
                        new CodeLocation($statements_checker->getSource(), $right)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($left_type->isFalse()) {
                if (IssueBuffer::accepts(
                    new FalseOperand(
                        'Cannot concatenate with a ' . $left_type,
                        new CodeLocation($statements_checker->getSource(), $left)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isFalse()) {
                if (IssueBuffer::accepts(
                    new FalseOperand(
                        'Cannot concatenate with a ' . $right_type,
                        new CodeLocation($statements_checker->getSource(), $right)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($left_type->isNullable() && !$left_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Cannot concatenate with a possibly null ' . $left_type,
                        new CodeLocation($statements_checker->getSource(), $left)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isNullable() && !$right_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Cannot concatenate with a possibly null ' . $right_type,
                        new CodeLocation($statements_checker->getSource(), $right)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($left_type->isFalsable() && !$left_type->ignore_falsable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyFalseOperand(
                        'Cannot concatenate with a possibly false ' . $left_type,
                        new CodeLocation($statements_checker->getSource(), $left)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isFalsable() && !$right_type->ignore_falsable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyFalseOperand(
                        'Cannot concatenate with a possibly false ' . $right_type,
                        new CodeLocation($statements_checker->getSource(), $right)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $project_checker = $statements_checker->getFileChecker()->project_checker;

            $left_type_match = true;
            $right_type_match = true;

            $left_has_scalar_match = false;
            $right_has_scalar_match = false;

            $has_valid_left_operand = false;
            $has_valid_right_operand = false;

            foreach ($left_type->getTypes() as $left_type_part) {
                if ($left_type_part instanceof Type\Atomic\TGenericParam) {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Left operand cannot be mixed',
                            new CodeLocation($statements_checker->getSource(), $left)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return;
                }

                if ($left_type_part instanceof Type\Atomic\TNull || $left_type_part instanceof Type\Atomic\TFalse) {
                    continue;
                }

                $left_type_part_match = TypeChecker::isAtomicContainedBy(
                    $project_checker->codebase,
                    $left_type_part,
                    new Type\Atomic\TString,
                    $left_has_scalar_match,
                    $left_type_coerced,
                    $left_type_coerced_from_mixed,
                    $left_to_string_cast
                );

                $left_type_match = $left_type_match && $left_type_part_match;

                $has_valid_left_operand = $has_valid_left_operand || $left_type_part_match;

                if ($left_to_string_cast && $config->strict_binary_operands) {
                    if (IssueBuffer::accepts(
                        new ImplicitToStringCast(
                            'Left side of concat op expects string, '
                                . '\'' . $left_type . '\' provided with a __toString method',
                            new CodeLocation($statements_checker->getSource(), $left)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            foreach ($right_type->getTypes() as $right_type_part) {
                if ($right_type_part instanceof Type\Atomic\TGenericParam) {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Right operand cannot be a template param',
                            new CodeLocation($statements_checker->getSource(), $right)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return;
                }

                if ($right_type_part instanceof Type\Atomic\TNull || $right_type_part instanceof Type\Atomic\TFalse) {
                    continue;
                }

                $right_type_part_match = TypeChecker::isAtomicContainedBy(
                    $project_checker->codebase,
                    $right_type_part,
                    new Type\Atomic\TString,
                    $right_has_scalar_match,
                    $right_type_coerced,
                    $right_type_coerced_from_mixed,
                    $right_to_string_cast
                );

                $right_type_match = $right_type_match && $right_type_part_match;

                $has_valid_right_operand = $has_valid_right_operand || $right_type_part_match;

                if ($right_to_string_cast && $config->strict_binary_operands) {
                    if (IssueBuffer::accepts(
                        new ImplicitToStringCast(
                            'Right side of concat op expects string, '
                                . '\'' . $right_type . '\' provided with a __toString method',
                            new CodeLocation($statements_checker->getSource(), $right)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if (!$left_type_match && (!$left_has_scalar_match || $config->strict_binary_operands)) {
                if ($has_valid_left_operand) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidOperand(
                            'Cannot concatenate with a ' . $left_type,
                            new CodeLocation($statements_checker->getSource(), $left)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot concatenate with a ' . $left_type,
                            new CodeLocation($statements_checker->getSource(), $left)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if (!$right_type_match && (!$right_has_scalar_match || $config->strict_binary_operands)) {
                if ($has_valid_right_operand) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidOperand(
                            'Cannot concatenate with a ' . $right_type,
                            new CodeLocation($statements_checker->getSource(), $right)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot concatenate with a ' . $right_type,
                            new CodeLocation($statements_checker->getSource(), $right)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }
        // When concatenating two known string literals (with only one possibility),
        // put the concatenated string into $result_type
        if ($left_type && $right_type && $left_type->isSingleStringLiteral() && $right_type->isSingleStringLiteral()) {
            $literal = $left_type->getSingleStringLiteral() . $right_type->getSingleStringLiteral();
            if (strlen($literal) <= 10000) {
                // Limit these to 10000 bytes to avoid extremely large union types from repeated concatenations, etc
                $result_type = new Union([new TLiteralString($literal)]);
            }
        }
    }
}
