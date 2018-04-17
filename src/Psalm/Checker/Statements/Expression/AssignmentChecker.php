<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\Statements\Expression\Assignment\ArrayAssignmentChecker;
use Psalm\Checker\Statements\Expression\Assignment\PropertyAssignmentChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Issue\AssignmentToVoid;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\LoopInvalidation;
use Psalm\Issue\MissingDocblockType;
use Psalm\Issue\MixedAssignment;
use Psalm\Issue\ReferenceConstraintViolation;
use Psalm\IssueBuffer;
use Psalm\Type;

class AssignmentChecker
{
    /**
     * @param  StatementsChecker        $statements_checker
     * @param  PhpParser\Node\Expr      $assign_var
     * @param  PhpParser\Node\Expr|null $assign_value  This has to be null to support list destructuring
     * @param  Type\Union|null          $assign_value_type
     * @param  Context                  $context
     * @param  string                   $doc_comment
     * @param  int|null                 $came_from_line_number
     *
     * @return false|Type\Union
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $assign_var,
        $assign_value,
        $assign_value_type,
        Context $context,
        $doc_comment,
        $came_from_line_number = null
    ) {
        $var_id = ExpressionChecker::getVarId(
            $assign_var,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        // gets a variable id that *may* contain array keys
        $array_var_id = ExpressionChecker::getArrayVarId(
            $assign_var,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $var_comments = [];
        $comment_type = null;

        if ($doc_comment) {
            try {
                $var_comments = CommentChecker::getTypeFromComment(
                    $doc_comment,
                    $statements_checker->getSource(),
                    $statements_checker->getAliases(),
                    null,
                    $came_from_line_number
                );
            } catch (IncorrectDocblockException $e) {
                if (IssueBuffer::accepts(
                    new MissingDocblockType(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_checker->getSource(), $assign_var)
                    )
                )) {
                    // fall through
                }
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_checker->getSource(), $assign_var)
                    )
                )) {
                    // fall through
                }
            }

            foreach ($var_comments as $var_comment) {
                try {
                    $var_comment_type = ExpressionChecker::fleshOutType(
                        $statements_checker->getFileChecker()->project_checker,
                        $var_comment->type,
                        $context->self,
                        $context->self
                    );

                    $var_comment_type->setFromDocblock();

                    if (!$var_comment->var_id || $var_comment->var_id === $var_id) {
                        $comment_type = $var_comment_type;
                        continue;
                    }

                    $context->vars_in_scope[$var_comment->var_id] = $var_comment_type;
                } catch (\UnexpectedValueException $e) {
                    if (IssueBuffer::accepts(
                        new InvalidDocblock(
                            (string)$e->getMessage(),
                            new CodeLocation($statements_checker->getSource(), $assign_var)
                        )
                    )) {
                        // fall through
                    }
                }
            }
        }

        if ($assign_value && ExpressionChecker::analyze($statements_checker, $assign_value, $context) === false) {
            if ($var_id) {
                if ($array_var_id) {
                    $context->removeDescendents($array_var_id, null, $assign_value_type);
                }

                // if we're not exiting immediately, make everything mixed
                $context->vars_in_scope[$var_id] = $comment_type ?: Type::getMixed();
            }

            return false;
        }

        if ($comment_type) {
            $assign_value_type = $comment_type;
        } elseif (!$assign_value_type) {
            if (isset($assign_value->inferredType)) {
                $assign_value_type = $assign_value->inferredType;
            } else {
                $assign_value_type = Type::getMixed();
            }
        }

        if ($array_var_id && isset($context->vars_in_scope[$array_var_id])) {
            // removes dependennt vars from $context
            $context->removeDescendents(
                $array_var_id,
                $context->vars_in_scope[$array_var_id],
                $assign_value_type,
                $statements_checker
            );
        } else {
            $root_var_id = ExpressionChecker::getRootVarId(
                $assign_var,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            if ($root_var_id && isset($context->vars_in_scope[$root_var_id])) {
                $context->removeVarFromConflictingClauses(
                    $root_var_id,
                    $context->vars_in_scope[$root_var_id],
                    $statements_checker
                );
            }
        }

        $project_checker = $statements_checker->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        if ($assign_value_type->isMixed()) {
            $codebase->analyzer->incrementMixedCount($statements_checker->getCheckedFilePath());

            if (!$assign_var instanceof PhpParser\Node\Expr\PropertyFetch) {
                if (IssueBuffer::accepts(
                    new MixedAssignment(
                        'Cannot assign ' . $var_id . ' to a mixed type',
                        new CodeLocation($statements_checker->getSource(), $assign_var)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        } else {
            $codebase->analyzer->incrementNonMixedCount($statements_checker->getCheckedFilePath());

            if ($var_id
                && isset($context->byref_constraints[$var_id])
                && ($outer_constraint_type = $context->byref_constraints[$var_id]->type)
            ) {
                if (!TypeChecker::isContainedBy(
                    $codebase,
                    $assign_value_type,
                    $outer_constraint_type,
                    $assign_value_type->ignore_nullable_issues,
                    $assign_value_type->ignore_falsable_issues
                )
                ) {
                    if (IssueBuffer::accepts(
                        new ReferenceConstraintViolation(
                            'Variable ' . $var_id . ' is limited to values of type '
                                . $context->byref_constraints[$var_id]->type
                                . ' because it is passed by reference',
                            new CodeLocation($statements_checker->getSource(), $assign_var)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        if ($var_id === '$this' && IssueBuffer::accepts(
            new InvalidScope(
                'Cannot re-assign ' . $var_id,
                new CodeLocation($statements_checker->getSource(), $assign_var)
            ),
            $statements_checker->getSuppressedIssues()
        )) {
            return false;
        }

        if (isset($context->protected_var_ids[$var_id])) {
            if (IssueBuffer::accepts(
                new LoopInvalidation(
                    'Variable ' . $var_id . ' has already been assigned in a for/foreach loop',
                    new CodeLocation($statements_checker->getSource(), $assign_var)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if ($assign_var instanceof PhpParser\Node\Expr\Variable && is_string($assign_var->name) && $var_id) {
            $context->vars_in_scope[$var_id] = $assign_value_type;
            $context->vars_possibly_in_scope[$var_id] = true;
            $context->assigned_var_ids[$var_id] = true;

            $location = new CodeLocation($statements_checker, $assign_var);

            if ($context->collect_references) {
                $context->unreferenced_vars[$var_id] = $location;
            }

            if (!$statements_checker->hasVariable($var_id)) {
                $statements_checker->registerVariable(
                    $var_id,
                    $location,
                    $context->branch_point
                );
            } else {
                $statements_checker->registerVariableAssignment(
                    $var_id,
                    $location
                );
            }

            if (isset($context->byref_constraints[$var_id])) {
                $statements_checker->registerVariableUse($location);
            }
        } elseif ($assign_var instanceof PhpParser\Node\Expr\List_
            || $assign_var instanceof PhpParser\Node\Expr\Array_
        ) {
            /** @var int $offset */
            foreach ($assign_var->items as $offset => $assign_var_item) {
                // $assign_var_item can be null e.g. list($a, ) = ['a', 'b']
                if (!$assign_var_item) {
                    continue;
                }

                $var = $assign_var_item->value;

                if ($assign_value instanceof PhpParser\Node\Expr\Array_
                    && isset($assign_var_item->value->inferredType)
                ) {
                    self::analyze(
                        $statements_checker,
                        $var,
                        $assign_var_item->value,
                        null,
                        $context,
                        $doc_comment
                    );

                    continue;
                }

                if (isset($assign_value_type->getTypes()['array'])
                    && ($array_atomic_type = $assign_value_type->getTypes()['array'])
                    && $array_atomic_type instanceof Type\Atomic\ObjectLike
                    && !$assign_var_item->key
                    && isset($array_atomic_type->properties[$offset]) // if object-like has int offsets
                ) {
                    self::analyze(
                        $statements_checker,
                        $var,
                        null,
                        $array_atomic_type->properties[(string)$offset],
                        $context,
                        $doc_comment
                    );

                    continue;
                }

                if ($var instanceof PhpParser\Node\Expr\List_
                    || $var instanceof PhpParser\Node\Expr\Array_
                ) {
                    /** @var Type\Atomic\ObjectLike|Type\Atomic\TArray|null */
                    $array_value_type = isset($assign_value_type->getTypes()['array'])
                        ? $assign_value_type->getTypes()['array']
                        : null;

                    if ($array_value_type instanceof Type\Atomic\ObjectLike) {
                        $array_value_type = $array_value_type->getGenericArrayType();
                    }

                    self::analyze(
                        $statements_checker,
                        $var,
                        null,
                        $array_value_type ? clone $array_value_type->type_params[1] : Type::getMixed(),
                        $context,
                        $doc_comment
                    );
                }

                $list_var_id = ExpressionChecker::getArrayVarId(
                    $var,
                    $statements_checker->getFQCLN(),
                    $statements_checker
                );

                if ($list_var_id) {
                    $context->vars_possibly_in_scope[$list_var_id] = true;

                    if (strpos($list_var_id, '-') === false && strpos($list_var_id, '[') === false) {
                        $location = new CodeLocation($statements_checker, $assign_var);

                        if ($context->collect_references) {
                            $context->unreferenced_vars[$list_var_id] = $location;
                        }

                        if (!$statements_checker->hasVariable($list_var_id)) {
                            $statements_checker->registerVariable(
                                $list_var_id,
                                $location,
                                $context->branch_point
                            );
                        } else {
                            $statements_checker->registerVariableAssignment(
                                $list_var_id,
                                $location
                            );
                        }

                        if (isset($context->byref_constraints[$list_var_id])) {
                            $statements_checker->registerVariableUse($location);
                        }
                    }

                    $new_assign_type = null;

                    if (isset($assign_value_type->getTypes()['array'])) {
                        $array_atomic_type = $assign_value_type->getTypes()['array'];

                        if ($array_atomic_type instanceof Type\Atomic\TArray) {
                            $new_assign_type = clone $array_atomic_type->type_params[1];
                        } elseif ($array_atomic_type instanceof Type\Atomic\ObjectLike) {
                            if ($assign_var_item->key
                                && ($assign_var_item->key instanceof PhpParser\Node\Scalar\String_
                                    || $assign_var_item->key instanceof PhpParser\Node\Scalar\LNumber)
                                && isset($array_atomic_type->properties[$assign_var_item->key->value])
                            ) {
                                $new_assign_type =
                                    clone $array_atomic_type->properties[$assign_var_item->key->value];
                            }
                        }
                    }

                    if ($context->hasVariable($list_var_id)) {
                        // removes dependennt vars from $context
                        $context->removeDescendents(
                            $list_var_id,
                            $context->vars_in_scope[$list_var_id],
                            $new_assign_type,
                            $statements_checker
                        );
                    }

                    $context->vars_in_scope[$list_var_id] = $new_assign_type ?: Type::getMixed();
                }
            }
        } elseif ($assign_var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            ArrayAssignmentChecker::analyze(
                $statements_checker,
                $assign_var,
                $context,
                $assign_value_type
            );
        } elseif ($assign_var instanceof PhpParser\Node\Expr\PropertyFetch) {
            if ($assign_var->name instanceof PhpParser\Node\Identifier) {
                PropertyAssignmentChecker::analyzeInstance(
                    $statements_checker,
                    $assign_var,
                    $assign_var->name->name,
                    $assign_value,
                    $assign_value_type,
                    $context
                );
            } else {
                if (ExpressionChecker::analyze($statements_checker, $assign_var->name, $context) === false) {
                    return false;
                }

                if (ExpressionChecker::analyze($statements_checker, $assign_var->var, $context) === false) {
                    return false;
                }
            }

            if ($var_id) {
                $context->vars_possibly_in_scope[$var_id] = true;
            }
        } elseif ($assign_var instanceof PhpParser\Node\Expr\StaticPropertyFetch &&
            $assign_var->class instanceof PhpParser\Node\Name
        ) {
            if (ExpressionChecker::analyze($statements_checker, $assign_var, $context) === false) {
                return false;
            }

            if ($context->check_classes) {
                PropertyAssignmentChecker::analyzeStatic(
                    $statements_checker,
                    $assign_var,
                    $assign_value,
                    $assign_value_type,
                    $context
                );
            }

            if ($var_id) {
                $context->vars_possibly_in_scope[$var_id] = true;
            }
        }

        if ($var_id && isset($context->vars_in_scope[$var_id]) && $context->vars_in_scope[$var_id]->isVoid()) {
            if (IssueBuffer::accepts(
                new AssignmentToVoid(
                    'Cannot assign ' . $var_id . ' to type void',
                    new CodeLocation($statements_checker->getSource(), $assign_var)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            $context->vars_in_scope[$var_id] = Type::getMixed();

            return Type::getMixed();
        }

        return $assign_value_type;
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\AssignOp    $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyzeAssignmentOperation(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\AssignOp $stmt,
        Context $context
    ) {
        if (ExpressionChecker::analyze($statements_checker, $stmt->var, $context) === false) {
            return false;
        }

        if (ExpressionChecker::analyze($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }

        $array_var_id = ExpressionChecker::getArrayVarId(
            $stmt->var,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $var_type = isset($stmt->var->inferredType) ? clone $stmt->var->inferredType : null;
        $expr_type = isset($stmt->expr->inferredType) ? $stmt->expr->inferredType : null;

        if ($stmt instanceof PhpParser\Node\Expr\AssignOp\Plus ||
            $stmt instanceof PhpParser\Node\Expr\AssignOp\Minus ||
            $stmt instanceof PhpParser\Node\Expr\AssignOp\Mod ||
            $stmt instanceof PhpParser\Node\Expr\AssignOp\Mul ||
            $stmt instanceof PhpParser\Node\Expr\AssignOp\Pow
        ) {
            BinaryOpChecker::analyzeNonDivArithmenticOp(
                $statements_checker,
                $stmt->var,
                $stmt->expr,
                $stmt,
                $result_type,
                $context
            );

            if ($result_type && $array_var_id) {
                $context->vars_in_scope[$array_var_id] = $result_type;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp\Div
            && $var_type
            && $expr_type
            && $var_type->hasNumericType()
            && $expr_type->hasNumericType()
            && $array_var_id
        ) {
            $context->vars_in_scope[$array_var_id] = Type::combineUnionTypes(Type::getFloat(), Type::getInt());
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp\Concat) {
            BinaryOpChecker::analyzeConcatOp(
                $statements_checker,
                $stmt->var,
                $stmt->expr,
                $context,
                $result_type
            );

            if ($result_type && $array_var_id) {
                $context->vars_in_scope[$array_var_id] = $result_type;
            }
        }

        return null;
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\AssignRef   $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyzeAssignmentRef(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\AssignRef $stmt,
        Context $context
    ) {
        if (self::analyze(
            $statements_checker,
            $stmt->var,
            $stmt->expr,
            null,
            $context,
            (string)$stmt->getDocComment()
        ) === false) {
            return false;
        }

        $lhs_var_id = ExpressionChecker::getVarId(
            $stmt->var,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $rhs_var_id = ExpressionChecker::getVarId(
            $stmt->expr,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        if ($lhs_var_id) {
            $context->vars_in_scope[$lhs_var_id] = Type::getMixed();
        }

        if ($rhs_var_id) {
            $context->vars_in_scope[$rhs_var_id] = Type::getMixed();
        }
    }
}
