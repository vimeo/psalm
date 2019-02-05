<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\ArrayAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\PropertyAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
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
use Psalm\Issue\NoValue;
use Psalm\Issue\PossiblyUndefinedArrayOffset;
use Psalm\Issue\ReferenceConstraintViolation;
use Psalm\IssueBuffer;
use Psalm\Type;

/**
 * @internal
 */
class AssignmentAnalyzer
{
    /**
     * @param  StatementsAnalyzer        $statements_analyzer
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
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $assign_var,
        $assign_value,
        $assign_value_type,
        Context $context,
        $doc_comment,
        $came_from_line_number = null
    ) {
        $var_id = ExpressionAnalyzer::getVarId(
            $assign_var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        // gets a variable id that *may* contain array keys
        $array_var_id = ExpressionAnalyzer::getArrayVarId(
            $assign_var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $var_comments = [];
        $comment_type = null;

        $codebase = $statements_analyzer->getCodebase();

        if ($doc_comment) {
            $file_path = $statements_analyzer->getRootFilePath();
            $project_analyzer = $statements_analyzer->getFileAnalyzer()->project_analyzer;

            $file_storage_provider = $codebase->file_storage_provider;

            $file_storage = $file_storage_provider->get($file_path);

            $template_type_map = $statements_analyzer->getTemplateTypeMap();

            try {
                $var_comments = CommentAnalyzer::getTypeFromComment(
                    $doc_comment,
                    $statements_analyzer->getSource(),
                    $statements_analyzer->getAliases(),
                    $template_type_map,
                    $came_from_line_number,
                    null,
                    $file_storage->type_aliases
                );
            } catch (IncorrectDocblockException $e) {
                if (IssueBuffer::accepts(
                    new MissingDocblockType(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_analyzer->getSource(), $assign_var)
                    )
                )) {
                    // fall through
                }
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_analyzer->getSource(), $assign_var)
                    )
                )) {
                    // fall through
                }
            }

            foreach ($var_comments as $var_comment) {
                try {
                    $var_comment_type = ExpressionAnalyzer::fleshOutType(
                        $codebase,
                        $var_comment->type,
                        $context->self,
                        $context->self
                    );

                    $var_comment_type->setFromDocblock();

                    $var_comment_type->check(
                        $statements_analyzer,
                        new CodeLocation($statements_analyzer->getSource(), $assign_var),
                        $statements_analyzer->getSuppressedIssues()
                    );

                    if (!$var_comment->var_id || $var_comment->var_id === $var_id) {
                        $comment_type = $var_comment_type;
                        continue;
                    }

                    $context->vars_in_scope[$var_comment->var_id] = $var_comment_type;
                } catch (\UnexpectedValueException $e) {
                    if (IssueBuffer::accepts(
                        new InvalidDocblock(
                            (string)$e->getMessage(),
                            new CodeLocation($statements_analyzer->getSource(), $assign_var)
                        )
                    )) {
                        // fall through
                    }
                }
            }
        }

        if ($assign_value) {
            if ($var_id && $assign_value instanceof PhpParser\Node\Expr\Closure) {
                foreach ($assign_value->uses as $closure_use) {
                    if ($closure_use->byRef
                        && is_string($closure_use->var->name)
                        && $var_id === '$' . $closure_use->var->name
                    ) {
                        $context->vars_in_scope[$var_id] = Type::getClosure();
                        $context->vars_possibly_in_scope[$var_id] = true;
                    }
                }
            }

            if (ExpressionAnalyzer::analyze($statements_analyzer, $assign_value, $context) === false) {
                if ($var_id) {
                    if ($array_var_id) {
                        $context->removeDescendents($array_var_id, null, $assign_value_type);
                    }

                    // if we're not exiting immediately, make everything mixed
                    $context->vars_in_scope[$var_id] = $comment_type ?: Type::getMixed();
                }

                return false;
            }
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
                $statements_analyzer
            );
        } else {
            $root_var_id = ExpressionAnalyzer::getRootVarId(
                $assign_var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            if ($root_var_id && isset($context->vars_in_scope[$root_var_id])) {
                $context->removeVarFromConflictingClauses(
                    $root_var_id,
                    $context->vars_in_scope[$root_var_id],
                    $statements_analyzer
                );
            }
        }

        $project_analyzer = $statements_analyzer->getFileAnalyzer()->project_analyzer;
        $codebase = $statements_analyzer->getCodebase();

        if ($assign_value_type->hasMixed()) {
            $root_var_id = ExpressionAnalyzer::getRootVarId(
                $assign_var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());

            if (!$assign_var instanceof PhpParser\Node\Expr\PropertyFetch
                && !strpos($root_var_id ?? '', '->')
            ) {
                if (IssueBuffer::accepts(
                    new MixedAssignment(
                        'Cannot assign ' . $var_id . ' to a mixed type',
                        new CodeLocation($statements_analyzer->getSource(), $assign_var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        } else {
            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());

            if ($var_id
                && isset($context->byref_constraints[$var_id])
                && ($outer_constraint_type = $context->byref_constraints[$var_id]->type)
            ) {
                if (!TypeAnalyzer::isContainedBy(
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
                            new CodeLocation($statements_analyzer->getSource(), $assign_var)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        if ($var_id === '$this' && IssueBuffer::accepts(
            new InvalidScope(
                'Cannot re-assign ' . $var_id,
                new CodeLocation($statements_analyzer->getSource(), $assign_var)
            ),
            $statements_analyzer->getSuppressedIssues()
        )) {
            return false;
        }

        if (isset($context->protected_var_ids[$var_id])) {
            if (IssueBuffer::accepts(
                new LoopInvalidation(
                    'Variable ' . $var_id . ' has already been assigned in a for/foreach loop',
                    new CodeLocation($statements_analyzer->getSource(), $assign_var)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if ($assign_var instanceof PhpParser\Node\Expr\Variable && is_string($assign_var->name) && $var_id) {
            $context->vars_in_scope[$var_id] = $assign_value_type;
            $context->vars_possibly_in_scope[$var_id] = true;
            $context->assigned_var_ids[$var_id] = true;
            $context->possibly_assigned_var_ids[$var_id] = true;

            $location = new CodeLocation($statements_analyzer, $assign_var);

            if ($context->collect_references) {
                $context->unreferenced_vars[$var_id] = [$location->getHash() => $location];
            }

            if (!$statements_analyzer->hasVariable($var_id)) {
                $statements_analyzer->registerVariable(
                    $var_id,
                    $location,
                    $context->branch_point
                );
            } else {
                $statements_analyzer->registerVariableAssignment(
                    $var_id,
                    $location
                );
            }

            if (isset($context->byref_constraints[$var_id])) {
                $statements_analyzer->registerVariableUses([$location->getHash() => $location]);
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
                        $statements_analyzer,
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
                    $offset_type = $array_atomic_type->properties[(string)$offset];

                    if ($offset_type->possibly_undefined) {
                        if (IssueBuffer::accepts(
                            new PossiblyUndefinedArrayOffset(
                                'Possibly undefined array key',
                                new CodeLocation($statements_analyzer->getSource(), $var)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }

                        $offset_type = clone $offset_type;
                        $offset_type->possibly_undefined = false;
                    }

                    self::analyze(
                        $statements_analyzer,
                        $var,
                        null,
                        $offset_type,
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
                        $statements_analyzer,
                        $var,
                        null,
                        $array_value_type ? clone $array_value_type->type_params[1] : Type::getMixed(),
                        $context,
                        $doc_comment
                    );
                }

                $list_var_id = ExpressionAnalyzer::getArrayVarId(
                    $var,
                    $statements_analyzer->getFQCLN(),
                    $statements_analyzer
                );

                if ($list_var_id) {
                    $context->vars_possibly_in_scope[$list_var_id] = true;
                    $context->assigned_var_ids[$list_var_id] = true;
                    $context->possibly_assigned_var_ids[$list_var_id] = true;

                    $already_in_scope = isset($context->vars_in_scope[$var_id]);

                    if (strpos($list_var_id, '-') === false && strpos($list_var_id, '[') === false) {
                        $location = new CodeLocation($statements_analyzer, $var);

                        if ($context->collect_references) {
                            $context->unreferenced_vars[$list_var_id] = [$location->getHash() => $location];
                        }

                        if (!$statements_analyzer->hasVariable($list_var_id)) {
                            $statements_analyzer->registerVariable(
                                $list_var_id,
                                $location,
                                $context->branch_point
                            );
                        } else {
                            $statements_analyzer->registerVariableAssignment(
                                $list_var_id,
                                $location
                            );
                        }

                        if (isset($context->byref_constraints[$list_var_id])) {
                            $statements_analyzer->registerVariableUses([$location->getHash() => $location]);
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

                                if ($new_assign_type->possibly_undefined) {
                                    if (IssueBuffer::accepts(
                                        new PossiblyUndefinedArrayOffset(
                                            'Possibly undefined array key',
                                            new CodeLocation($statements_analyzer->getSource(), $var)
                                        ),
                                        $statements_analyzer->getSuppressedIssues()
                                    )) {
                                        // fall through
                                    }

                                    $new_assign_type->possibly_undefined = false;
                                }
                            }
                        }
                    }

                    if ($already_in_scope) {
                        // removes dependennt vars from $context
                        $context->removeDescendents(
                            $list_var_id,
                            $context->vars_in_scope[$list_var_id],
                            $new_assign_type,
                            $statements_analyzer
                        );
                    }

                    foreach ($var_comments as $var_comment) {
                        try {
                            if ($var_comment->var_id === $list_var_id) {
                                $var_comment_type = ExpressionAnalyzer::fleshOutType(
                                    $codebase,
                                    $var_comment->type,
                                    $context->self,
                                    $context->self
                                );

                                $var_comment_type->setFromDocblock();

                                $new_assign_type = $var_comment_type;
                                break;
                            }
                        } catch (\UnexpectedValueException $e) {
                            if (IssueBuffer::accepts(
                                new InvalidDocblock(
                                    (string)$e->getMessage(),
                                    new CodeLocation($statements_analyzer->getSource(), $assign_var)
                                )
                            )) {
                                // fall through
                            }
                        }
                    }

                    $context->vars_in_scope[$list_var_id] = $new_assign_type ?: Type::getMixed();
                }
            }
        } elseif ($assign_var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            ArrayAssignmentAnalyzer::analyze(
                $statements_analyzer,
                $assign_var,
                $context,
                $assign_value,
                $assign_value_type
            );
        } elseif ($assign_var instanceof PhpParser\Node\Expr\PropertyFetch) {
            if (!$assign_var->name instanceof PhpParser\Node\Identifier) {
                // this can happen when the user actually means to type $this-><autocompleted>, but there's
                // a variable on the next line
                if (ExpressionAnalyzer::analyze($statements_analyzer, $assign_var->var, $context) === false) {
                    return false;
                }

                if (ExpressionAnalyzer::analyze($statements_analyzer, $assign_var->name, $context) === false) {
                    return false;
                }
            }

            if ($assign_var->name instanceof PhpParser\Node\Identifier) {
                $prop_name = $assign_var->name->name;
            } elseif (isset($assign_var->name->inferredType)
                && $assign_var->name->inferredType->isSingleStringLiteral()
            ) {
                $prop_name = $assign_var->name->inferredType->getSingleStringLiteral()->value;
            } else {
                $prop_name = null;
            }

            if ($prop_name) {
                PropertyAssignmentAnalyzer::analyzeInstance(
                    $statements_analyzer,
                    $assign_var,
                    $prop_name,
                    $assign_value,
                    $assign_value_type,
                    $context
                );
            } else {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $assign_var->var, $context) === false) {
                    return false;
                }
            }

            if ($var_id) {
                $context->vars_possibly_in_scope[$var_id] = true;
            }
        } elseif ($assign_var instanceof PhpParser\Node\Expr\StaticPropertyFetch &&
            $assign_var->class instanceof PhpParser\Node\Name
        ) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $assign_var, $context) === false) {
                return false;
            }

            if ($context->check_classes) {
                PropertyAssignmentAnalyzer::analyzeStatic(
                    $statements_analyzer,
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

        if ($var_id && isset($context->vars_in_scope[$var_id])) {
            if ($context->vars_in_scope[$var_id]->isVoid()) {
                if (IssueBuffer::accepts(
                    new AssignmentToVoid(
                        'Cannot assign ' . $var_id . ' to type void',
                        new CodeLocation($statements_analyzer->getSource(), $assign_var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                $context->vars_in_scope[$var_id] = Type::getNull();

                return $context->vars_in_scope[$var_id];
            }

            if ($context->vars_in_scope[$var_id]->isNever()) {
                if (IssueBuffer::accepts(
                    new NoValue(
                        'This function or method call never returns output',
                        new CodeLocation($statements_analyzer->getSource(), $assign_var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }

                $context->vars_in_scope[$var_id] = Type::getEmpty();

                return $context->vars_in_scope[$var_id];
            }
        }

        return $assign_value_type;
    }

    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Expr\AssignOp    $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyzeAssignmentOperation(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\AssignOp $stmt,
        Context $context
    ) {
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
            return false;
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        $array_var_id = ExpressionAnalyzer::getArrayVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($array_var_id && $context->collect_references && $stmt->var instanceof PhpParser\Node\Expr\Variable) {
            $location = new CodeLocation($statements_analyzer, $stmt->var);
            $context->assigned_var_ids[$array_var_id] = true;
            $context->possibly_assigned_var_ids[$array_var_id] = true;
            $statements_analyzer->registerVariableAssignment(
                $array_var_id,
                $location
            );
            $context->unreferenced_vars[$array_var_id] = [$location->getHash() => $location];
        }

        $var_type = isset($stmt->var->inferredType) ? clone $stmt->var->inferredType : null;
        $expr_type = isset($stmt->expr->inferredType) ? $stmt->expr->inferredType : null;

        if ($stmt instanceof PhpParser\Node\Expr\AssignOp\Plus ||
            $stmt instanceof PhpParser\Node\Expr\AssignOp\Minus ||
            $stmt instanceof PhpParser\Node\Expr\AssignOp\Mod ||
            $stmt instanceof PhpParser\Node\Expr\AssignOp\Mul ||
            $stmt instanceof PhpParser\Node\Expr\AssignOp\Pow
        ) {
            BinaryOpAnalyzer::analyzeNonDivArithmenticOp(
                $statements_analyzer,
                $stmt->var,
                $stmt->expr,
                $stmt,
                $result_type,
                $context
            );

            if ($stmt->var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
                ArrayAssignmentAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->var,
                    $context,
                    $stmt->expr,
                    $result_type ?: Type::getMixed($context->inside_loop)
                );
            } elseif ($result_type && $array_var_id) {
                $context->vars_in_scope[$array_var_id] = $result_type;
                $stmt->inferredType = clone $context->vars_in_scope[$array_var_id];
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp\Div
            && $var_type
            && $expr_type
            && $var_type->hasDefinitelyNumericType()
            && $expr_type->hasDefinitelyNumericType()
            && $array_var_id
        ) {
            $context->vars_in_scope[$array_var_id] = Type::combineUnionTypes(Type::getFloat(), Type::getInt());
            $stmt->inferredType = clone $context->vars_in_scope[$array_var_id];
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp\Concat) {
            BinaryOpAnalyzer::analyzeConcatOp(
                $statements_analyzer,
                $stmt->var,
                $stmt->expr,
                $context,
                $result_type
            );

            if ($result_type && $array_var_id) {
                $context->vars_in_scope[$array_var_id] = $result_type;
                $stmt->inferredType = clone $context->vars_in_scope[$array_var_id];
            }
        }

        return null;
    }

    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Expr\AssignRef   $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyzeAssignmentRef(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\AssignRef $stmt,
        Context $context
    ) {
        if (self::analyze(
            $statements_analyzer,
            $stmt->var,
            $stmt->expr,
            null,
            $context,
            (string)$stmt->getDocComment()
        ) === false) {
            return false;
        }

        $lhs_var_id = ExpressionAnalyzer::getVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $rhs_var_id = ExpressionAnalyzer::getVarId(
            $stmt->expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($lhs_var_id) {
            $context->vars_in_scope[$lhs_var_id] = Type::getMixed();
        }

        if ($rhs_var_id) {
            $context->vars_in_scope[$rhs_var_id] = Type::getMixed();
        }
    }
}
