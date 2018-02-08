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
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyNullOperand;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Reconciler;

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
            $if_clauses = AlgebraChecker::getFormula(
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

            $simplified_clauses = AlgebraChecker::simplifyCNF(array_merge($context->clauses, $if_clauses));

            $left_type_assertions = AlgebraChecker::getTruthsFromFormula($simplified_clauses);

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

            $left_clauses = AlgebraChecker::getFormula(
                $stmt->left,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            $rhs_clauses = AlgebraChecker::simplifyCNF(
                array_merge(
                    $context->clauses,
                    AlgebraChecker::negateFormula($left_clauses)
                )
            );

            $negated_type_assertions = AlgebraChecker::getTruthsFromFormula($rhs_clauses);

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
            $op_context->clauses = $rhs_clauses;
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

            $if_clauses = AlgebraChecker::getFormula(
                $stmt,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            $ternary_clauses = AlgebraChecker::simplifyCNF(array_merge($context->clauses, $if_clauses));

            $negated_clauses = AlgebraChecker::negateFormula($if_clauses);

            $negated_if_types = AlgebraChecker::getTruthsFromFormula($negated_clauses);

            $reconcilable_if_types = AlgebraChecker::getTruthsFromFormula($ternary_clauses);

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
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mod ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Pow
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

                $stmt->inferredType = new Type\Union([new TInt, new TFloat]);
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
            && ($left_type->isMixed() || $right_type->isMixed())
            && ($left_type->hasNumericType() || $right_type->hasNumericType())
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
            if ($left_type->isNullable()) {
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

            if ($right_type->isNullable()) {
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

            foreach ($left_type->getTypes() as $left_type_part) {
                foreach ($right_type->getTypes() as $right_type_part) {
                    if ($left_type_part instanceof TNull) {
                        // null case is handled above
                        continue;
                    }

                    if ($left_type_part instanceof TMixed || $right_type_part instanceof TMixed) {
                        if ($statements_source && $codebase) {
                            $codebase->analyzer->incrementMixedCount($statements_source->getCheckedFilePath());
                        }

                        if ($left_type_part instanceof TMixed) {
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

                        $result_type = Type::getMixed();

                        return;
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
                                if ($statements_source && IssueBuffer::accepts(
                                    new InvalidOperand(
                                        'Cannot add an array to a non-array ' . $left_type_part,
                                        new CodeLocation($statements_source, $left)
                                    ),
                                    $statements_source->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            } else {
                                if ($statements_source && IssueBuffer::accepts(
                                    new InvalidOperand(
                                        'Cannot add an array to a non-array ' . $right_type_part,
                                        new CodeLocation($statements_source, $right)
                                    ),
                                    $statements_source->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            }

                            $result_type = Type::getArray();

                            return;
                        }

                        if ($left_type_part instanceof ObjectLike && $right_type_part instanceof ObjectLike) {
                            $properties = $left_type_part->properties + $right_type_part->properties;

                            $result_type_member = new Type\Union([new ObjectLike($properties)]);
                        } else {
                            $result_type_member = Type::combineTypes([$left_type_part, $right_type_part]);
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

                        continue;
                    }

                    if ($left_type_part->isNumericType() || $right_type_part->isNumericType()) {
                        if ($left_type_part instanceof TInt && $right_type_part instanceof TInt) {
                            if (!$result_type) {
                                $result_type = Type::getInt();
                            } else {
                                $result_type = Type::combineUnionTypes(Type::getInt(), $result_type);
                            }

                            continue;
                        }

                        if ($left_type_part instanceof TFloat && $right_type_part instanceof TFloat) {
                            if (!$result_type) {
                                $result_type = Type::getFloat();
                            } else {
                                $result_type = Type::combineUnionTypes(Type::getFloat(), $result_type);
                            }

                            continue;
                        }

                        if (($left_type_part instanceof TFloat && $right_type_part instanceof TInt) ||
                            ($left_type_part instanceof TInt && $right_type_part instanceof TFloat)
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

                            continue;
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

                            continue;
                        }

                        $non_numeric_type = $left_type_part->isNumericType() ? $right_type_part : $left_type_part;

                        if ($statements_source && IssueBuffer::accepts(
                            new InvalidOperand(
                                'Cannot add a numeric type to a non-numeric type ' . $non_numeric_type,
                                new CodeLocation($statements_source, $parent)
                            ),
                            $statements_source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
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

            $project_checker = $statements_checker->getFileChecker()->project_checker;

            $left_type_match = TypeChecker::isContainedBy(
                $project_checker->codebase,
                $left_type,
                Type::getString(),
                true,
                false,
                $left_has_scalar_match
            );

            $right_type_match = TypeChecker::isContainedBy(
                $project_checker->codebase,
                $right_type,
                Type::getString(),
                true,
                false,
                $right_has_scalar_match
            );

            if (!$left_type_match && (!$left_has_scalar_match || $config->strict_binary_operands)) {
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

            if (!$right_type_match && (!$right_has_scalar_match || $config->strict_binary_operands)) {
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
}
