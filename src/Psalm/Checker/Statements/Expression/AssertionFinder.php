<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\FileSource;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\TypeDoesNotContainNull;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\Issue\UnevaluatedCode;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;

class AssertionFinder
{
    const ASSIGNMENT_TO_RIGHT = 1;
    const ASSIGNMENT_TO_LEFT = -1;

    /**
     * Gets all the type assertions in a conditional
     *
     * @param  PhpParser\Node\Expr      $conditional
     * @param  string|null              $this_class_name
     * @param  FileSource         $source
     *
     * @return array<string, string>
     */
    public static function getAssertions(
        PhpParser\Node\Expr $conditional,
        $this_class_name,
        FileSource $source
    ) {
        $if_types = [];

        $project_checker = $source instanceof StatementsSource
            ? $source->getFileChecker()->project_checker
            : null;

        if ($conditional instanceof PhpParser\Node\Expr\Instanceof_) {
            $instanceof_type = self::getInstanceOfTypes($conditional, $this_class_name, $source);

            if ($instanceof_type) {
                $var_name = ExpressionChecker::getArrayVarId(
                    $conditional->expr,
                    $this_class_name,
                    $source
                );

                if ($var_name) {
                    $if_types[$var_name] = $instanceof_type;
                }
            }

            return $if_types;
        }

        if ($var_name = ExpressionChecker::getArrayVarId(
            $conditional,
            $this_class_name,
            $source
        )) {
            $if_types[$var_name] = '!falsy';

            return $if_types;
        }

        if ($conditional instanceof PhpParser\Node\Expr\Assign) {
            $var_name = ExpressionChecker::getArrayVarId(
                $conditional->var,
                $this_class_name,
                $source
            );

            if ($var_name) {
                $if_types[$var_name] = '!falsy';
            }

            return $if_types;
        }

        if ($conditional instanceof PhpParser\Node\Expr\BooleanNot) {
            $if_types_to_negate = self::getAssertions(
                $conditional->expr,
                $this_class_name,
                $source
            );

            return TypeChecker::negateTypes($if_types_to_negate);
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
        ) {
            $null_position = self::hasNullVariable($conditional);
            $false_position = self::hasFalseVariable($conditional);
            $true_position = self::hasTrueVariable($conditional);
            $gettype_position = self::hasGetTypeCheck($conditional);
            $getclass_position = self::hasGetClassCheck($conditional);
            $typed_value_position = self::hasTypedValueComparison($conditional);

            if ($null_position !== null) {
                if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                    $base_conditional = $conditional->left;
                } elseif ($null_position === self::ASSIGNMENT_TO_LEFT) {
                    $base_conditional = $conditional->right;
                } else {
                    throw new \UnexpectedValueException('$null_position value');
                }

                $var_name = ExpressionChecker::getArrayVarId(
                    $base_conditional,
                    $this_class_name,
                    $source
                );

                $var_type = isset($base_conditional->inferredType) ? $base_conditional->inferredType : null;

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                        $if_types[$var_name] = 'null';
                    } else {
                        $if_types[$var_name] = 'falsy';
                    }
                } elseif ($var_type
                    && $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
                    && $source instanceof StatementsSource
                    && $project_checker
                ) {
                    $null_type = Type::getNull();

                    if (!TypeChecker::isContainedBy(
                        $project_checker->codebase,
                        $var_type,
                        $null_type
                    ) && !TypeChecker::isContainedBy(
                        $project_checker->codebase,
                        $null_type,
                        $var_type
                    )) {
                        if (IssueBuffer::accepts(
                            new TypeDoesNotContainNull(
                                $var_type . ' does not contain ' . $null_type,
                                new CodeLocation($source, $conditional)
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }

                return $if_types;
            }

            if ($true_position) {
                if ($true_position === self::ASSIGNMENT_TO_RIGHT) {
                    $base_conditional = $conditional->left;
                } elseif ($true_position === self::ASSIGNMENT_TO_LEFT) {
                    $base_conditional = $conditional->right;
                } else {
                    throw new \UnexpectedValueException('Unrecognised position');
                }

                if ($base_conditional instanceof PhpParser\Node\Expr\FuncCall) {
                    return self::processFunctionCall(
                        $base_conditional,
                        $this_class_name,
                        $source,
                        false
                    );
                }

                $var_name = ExpressionChecker::getArrayVarId(
                    $base_conditional,
                    $this_class_name,
                    $source
                );

                if ($var_name) {
                    $if_types[$var_name] = '!falsy';
                } else {
                    return self::getAssertions($base_conditional, $this_class_name, $source);
                }

                return $if_types;
            }

            if ($false_position) {
                if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
                    $base_conditional = $conditional->left;
                } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
                    $base_conditional = $conditional->right;
                } else {
                    throw new \UnexpectedValueException('$false_position value');
                }

                if ($base_conditional instanceof PhpParser\Node\Expr\FuncCall) {
                    return self::processFunctionCall(
                        $base_conditional,
                        $this_class_name,
                        $source,
                        true
                    );
                }

                $var_name = ExpressionChecker::getArrayVarId(
                    $base_conditional,
                    $this_class_name,
                    $source
                );

                $var_type = isset($base_conditional->inferredType) ? $base_conditional->inferredType : null;

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                        $if_types[$var_name] = 'false';
                    } else {
                        $if_types[$var_name] = 'falsy';
                    }
                } elseif ($var_type) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
                        && $source instanceof StatementsSource
                        && $project_checker
                    ) {
                        $false_type = Type::getFalse();

                        if (!TypeChecker::isContainedBy(
                            $project_checker->codebase,
                            $var_type,
                            $false_type
                        ) && !TypeChecker::isContainedBy(
                            $project_checker->codebase,
                            $false_type,
                            $var_type
                        )) {
                            if (IssueBuffer::accepts(
                                new TypeDoesNotContainType(
                                    $var_type . ' does not contain ' . $false_type,
                                    new CodeLocation($source, $conditional)
                                ),
                                $source->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }

                    $notif_types = self::getAssertions($base_conditional, $this_class_name, $source);

                    if (count($notif_types) === 1) {
                        $if_types = TypeChecker::negateTypes($notif_types);
                    }
                }

                return $if_types;
            }

            if ($gettype_position) {
                if ($gettype_position === self::ASSIGNMENT_TO_RIGHT) {
                    $string_expr = $conditional->left;
                    $gettype_expr = $conditional->right;
                } elseif ($gettype_position === self::ASSIGNMENT_TO_LEFT) {
                    $string_expr = $conditional->right;
                    $gettype_expr = $conditional->left;
                } else {
                    throw new \UnexpectedValueException('$gettype_position value');
                }

                /** @var PhpParser\Node\Expr\FuncCall $gettype_expr */
                $var_name = ExpressionChecker::getArrayVarId(
                    $gettype_expr->args[0]->value,
                    $this_class_name,
                    $source
                );

                /** @var PhpParser\Node\Scalar\String_ $string_expr */
                $var_type = $string_expr->value;

                if (!isset(ClassLikeChecker::$GETTYPE_TYPES[$var_type])
                    && $source instanceof StatementsSource
                ) {
                    if (IssueBuffer::accepts(
                        new UnevaluatedCode(
                            'gettype cannot return this value',
                            new CodeLocation($source, $string_expr)
                        )
                    )) {
                        // fall through
                    }
                } else {
                    if ($var_name && $var_type) {
                        $if_types[$var_name] = $var_type;
                    }
                }

                return $if_types;
            }

            if ($getclass_position) {
                if ($getclass_position === self::ASSIGNMENT_TO_RIGHT) {
                    $whichclass_expr = $conditional->left;
                    $getclass_expr = $conditional->right;
                } elseif ($getclass_position === self::ASSIGNMENT_TO_LEFT) {
                    $whichclass_expr = $conditional->right;
                    $getclass_expr = $conditional->left;
                } else {
                    throw new \UnexpectedValueException('$getclass_position value');
                }

                /** @var PhpParser\Node\Expr\FuncCall $getclass_expr */
                $var_name = ExpressionChecker::getArrayVarId(
                    $getclass_expr->args[0]->value,
                    $this_class_name,
                    $source
                );

                if ($whichclass_expr instanceof PhpParser\Node\Scalar\String_) {
                    $var_type = $whichclass_expr->value;
                } elseif ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $whichclass_expr->class instanceof PhpParser\Node\Name
                ) {
                    $var_type = ClassLikeChecker::getFQCLNFromNameObject(
                        $whichclass_expr->class,
                        $source->getAliases()
                    );
                } else {
                    throw new \UnexpectedValueException('Shouldn’t get here');
                }

                if ($source instanceof StatementsSource
                    && ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $source,
                        $var_type,
                        new CodeLocation($source, $whichclass_expr),
                        $source->getSuppressedIssues(),
                        false
                    ) === false
                ) {
                    // fall through
                } else {
                    if ($var_name && $var_type) {
                        $if_types[$var_name] = 'getclass-' . $var_type;
                    }
                }

                return $if_types;
            }

            if ($typed_value_position) {
                if ($typed_value_position === self::ASSIGNMENT_TO_RIGHT) {
                    /** @var PhpParser\Node\Expr $conditional->right */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $source
                    );

                    $other_type = isset($conditional->left->inferredType) ? $conditional->left->inferredType : null;
                    $var_type = isset($conditional->right->inferredType) ? $conditional->right->inferredType : null;
                } elseif ($typed_value_position === self::ASSIGNMENT_TO_LEFT) {
                    /** @var PhpParser\Node\Expr $conditional->left */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $source
                    );

                    $var_type = isset($conditional->left->inferredType) ? $conditional->left->inferredType : null;
                    $other_type = isset($conditional->right->inferredType) ? $conditional->right->inferredType : null;
                } else {
                    throw new \UnexpectedValueException('$typed_value_position value');
                }

                if ($var_type) {
                    if ($var_name) {
                        $if_types[$var_name] = '^' . $var_type;
                    } elseif ($other_type
                        && $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
                        && $source instanceof StatementsSource
                        && $project_checker
                    ) {
                        if (!TypeChecker::isContainedBy(
                            $project_checker->codebase,
                            $var_type,
                            $other_type,
                            true
                        ) && !TypeChecker::isContainedBy(
                            $project_checker->codebase,
                            $other_type,
                            $var_type,
                            true
                        )) {
                            if (IssueBuffer::accepts(
                                new TypeDoesNotContainType(
                                    $var_type . ' does not contain ' . $other_type,
                                    new CodeLocation($source, $conditional)
                                ),
                                $source->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }
                }

                return $if_types;
            }

            $var_type = isset($conditional->left->inferredType) ? $conditional->left->inferredType : null;
            $other_type = isset($conditional->right->inferredType) ? $conditional->right->inferredType : null;

            if ($var_type
                && $other_type
                && $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
                && $source instanceof StatementsSource
                && $project_checker
            ) {
                if (!TypeChecker::canBeIdenticalTo($project_checker->codebase, $var_type, $other_type)) {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainType(
                            $var_type . ' does not contain ' . $other_type,
                            new CodeLocation($source, $conditional)
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            return [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
        ) {
            $null_position = self::hasNullVariable($conditional);
            $false_position = self::hasFalseVariable($conditional);
            $true_position = self::hasTrueVariable($conditional);
            $gettype_position = self::hasGetTypeCheck($conditional);
            $getclass_position = self::hasGetClassCheck($conditional);
            $typed_value_position = self::hasTypedValueComparison($conditional);

            if ($null_position !== null) {
                if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
                    $base_conditional = $conditional->left;
                } elseif ($null_position === self::ASSIGNMENT_TO_LEFT) {
                    $base_conditional = $conditional->right;
                } else {
                    throw new \UnexpectedValueException('Bad null variable position');
                }

                $var_name = ExpressionChecker::getArrayVarId(
                    $base_conditional,
                    $this_class_name,
                    $source
                );

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                        $if_types[$var_name] = '!null';
                    } else {
                        $if_types[$var_name] = '!falsy';
                    }
                }

                return $if_types;
            }

            if ($false_position) {
                if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
                    $base_conditional = $conditional->left;
                } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
                    $base_conditional = $conditional->right;
                } else {
                    throw new \UnexpectedValueException('Bad false variable position');
                }

                $var_name = ExpressionChecker::getArrayVarId(
                    $base_conditional,
                    $this_class_name,
                    $source
                );

                if ($var_name) {
                    if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                        $if_types[$var_name] = '!false';
                    } else {
                        $if_types[$var_name] = '!falsy';
                    }
                }

                return $if_types;
            }

            if ($true_position) {
                if ($true_position === self::ASSIGNMENT_TO_RIGHT) {
                    if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall) {
                        return self::processFunctionCall(
                            $conditional->left,
                            $this_class_name,
                            $source,
                            true
                        );
                    }
                } elseif ($true_position === self::ASSIGNMENT_TO_LEFT) {
                    if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall) {
                        return self::processFunctionCall(
                            $conditional->right,
                            $this_class_name,
                            $source,
                            true
                        );
                    }
                } else {
                    throw new \UnexpectedValueException('Bad null variable position');
                }

                return [];
            }

            if ($gettype_position) {
                if ($gettype_position === self::ASSIGNMENT_TO_RIGHT) {
                    $whichclass_expr = $conditional->left;
                    $gettype_expr = $conditional->right;
                } elseif ($gettype_position === self::ASSIGNMENT_TO_LEFT) {
                    $whichclass_expr = $conditional->right;
                    $gettype_expr = $conditional->left;
                } else {
                    throw new \UnexpectedValueException('$gettype_position value');
                }

                /** @var PhpParser\Node\Expr\FuncCall $gettype_expr */
                $var_name = ExpressionChecker::getArrayVarId(
                    $gettype_expr->args[0]->value,
                    $this_class_name,
                    $source
                );

                if ($whichclass_expr instanceof PhpParser\Node\Scalar\String_) {
                    $var_type = $whichclass_expr->value;
                } elseif ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $whichclass_expr->class instanceof PhpParser\Node\Name
                ) {
                    $var_type = ClassLikeChecker::getFQCLNFromNameObject(
                        $whichclass_expr->class,
                        $source->getAliases()
                    );
                } else {
                    throw new \UnexpectedValueException('Shouldn’t get here');
                }

                if (!isset(ClassLikeChecker::$GETTYPE_TYPES[$var_type])) {
                    if (IssueBuffer::accepts(
                        new UnevaluatedCode(
                            'gettype cannot return this value',
                            new CodeLocation($source, $whichclass_expr)
                        )
                    )) {
                        // fall through
                    }
                } else {
                    if ($var_name && $var_type) {
                        $if_types[$var_name] = '!' . $var_type;
                    }
                }

                return $if_types;
            }

            if ($getclass_position) {
                if ($getclass_position === self::ASSIGNMENT_TO_RIGHT) {
                    $whichclass_expr = $conditional->left;
                    $getclass_expr = $conditional->right;
                } elseif ($getclass_position === self::ASSIGNMENT_TO_LEFT) {
                    $whichclass_expr = $conditional->right;
                    $getclass_expr = $conditional->left;
                } else {
                    throw new \UnexpectedValueException('$getclass_position value');
                }

                /** @var PhpParser\Node\Expr\FuncCall $getclass_expr */
                $var_name = ExpressionChecker::getArrayVarId(
                    $getclass_expr->args[0]->value,
                    $this_class_name,
                    $source
                );

                if ($whichclass_expr instanceof PhpParser\Node\Scalar\String_) {
                    $var_type = $whichclass_expr->value;
                } elseif ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $whichclass_expr->class instanceof PhpParser\Node\Name
                ) {
                    $var_type = ClassLikeChecker::getFQCLNFromNameObject(
                        $whichclass_expr->class,
                        $source->getAliases()
                    );
                } else {
                    throw new \UnexpectedValueException('Shouldn’t get here');
                }

                if ($source instanceof StatementsSource
                    && $project_checker
                    && ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $source,
                        $var_type,
                        new CodeLocation($source, $whichclass_expr),
                        $source->getSuppressedIssues(),
                        false
                    ) === false
                ) {
                    // fall through
                } else {
                    if ($var_name && $var_type) {
                        $if_types[$var_name] = '!getclass-' . $var_type;
                    }
                }

                return $if_types;
            }

            if ($typed_value_position) {
                if ($typed_value_position === self::ASSIGNMENT_TO_RIGHT) {
                    /** @var PhpParser\Node\Expr $conditional->right */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $source
                    );

                    $other_type = isset($conditional->left->inferredType) ? $conditional->left->inferredType : null;
                    $var_type = isset($conditional->right->inferredType) ? $conditional->right->inferredType : null;
                } elseif ($typed_value_position === self::ASSIGNMENT_TO_LEFT) {
                    /** @var PhpParser\Node\Expr $conditional->left */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $source
                    );

                    $var_type = isset($conditional->left->inferredType) ? $conditional->left->inferredType : null;
                    $other_type = isset($conditional->right->inferredType) ? $conditional->right->inferredType : null;
                } else {
                    throw new \UnexpectedValueException('$typed_value_position value');
                }

                if ($var_type) {
                    if ($var_name) {
                        $if_types[$var_name] = '!^' . $var_type;
                    }

                    if ($other_type
                        && $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
                        && $source instanceof StatementsSource
                        && $project_checker
                    ) {
                        if (!TypeChecker::isContainedBy(
                            $project_checker->codebase,
                            $var_type,
                            $other_type,
                            true
                        ) && !TypeChecker::isContainedBy(
                            $project_checker->codebase,
                            $other_type,
                            $var_type,
                            true
                        )) {
                            if (IssueBuffer::accepts(
                                new RedundantCondition(
                                    $var_type . ' can never contain ' . $other_type,
                                    new CodeLocation($source, $conditional)
                                ),
                                $source->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }
                }

                return $if_types;
            }

            return [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater) {
            $typed_value_position = self::hasTypedValueComparison($conditional);

            if ($typed_value_position) {
                if ($typed_value_position === self::ASSIGNMENT_TO_RIGHT) {
                    /** @var PhpParser\Node\Expr $conditional->right */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $source
                    );
                } elseif ($typed_value_position === self::ASSIGNMENT_TO_LEFT) {
                    $var_name = null;
                } else {
                    throw new \UnexpectedValueException('$typed_value_position value');
                }

                if ($var_name) {
                    $if_types[$var_name] = '^isset';
                }

                return $if_types;
            }

            return [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller) {
            $typed_value_position = self::hasTypedValueComparison($conditional);

            if ($typed_value_position) {
                if ($typed_value_position === self::ASSIGNMENT_TO_RIGHT) {
                    $var_name = null;
                } elseif ($typed_value_position === self::ASSIGNMENT_TO_LEFT) {
                    /** @var PhpParser\Node\Expr $conditional->left */
                    $var_name = ExpressionChecker::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $source
                    );
                } else {
                    throw new \UnexpectedValueException('$typed_value_position value');
                }

                if ($var_name) {
                    $if_types[$var_name] = '^isset';
                }

                return $if_types;
            }

            return [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\FuncCall) {
            return self::processFunctionCall($conditional, $this_class_name, $source, false);
        }

        if ($conditional instanceof PhpParser\Node\Expr\Empty_) {
            $var_name = ExpressionChecker::getArrayVarId(
                $conditional->expr,
                $this_class_name,
                $source
            );

            if ($var_name) {
                $if_types[$var_name] = 'empty';
            } else {
                // look for any variables we *can* use for an isset assertion
                $array_root = $conditional->expr;

                while ($array_root instanceof PhpParser\Node\Expr\ArrayDimFetch && !$var_name) {
                    $array_root = $array_root->var;

                    $var_name = ExpressionChecker::getArrayVarId(
                        $array_root,
                        $this_class_name,
                        $source
                    );
                }

                if ($var_name) {
                    $if_types[$var_name] = '^empty';
                }
            }

            return $if_types;
        }

        if ($conditional instanceof PhpParser\Node\Expr\Isset_) {
            foreach ($conditional->vars as $isset_var) {
                $var_name = ExpressionChecker::getArrayVarId(
                    $isset_var,
                    $this_class_name,
                    $source
                );

                if ($var_name) {
                    $if_types[$var_name] = 'isset';
                } else {
                    // look for any variables we *can* use for an isset assertion
                    $array_root = $isset_var;

                    while ($array_root instanceof PhpParser\Node\Expr\ArrayDimFetch && !$var_name) {
                        $array_root = $array_root->var;

                        $var_name = ExpressionChecker::getArrayVarId(
                            $array_root,
                            $this_class_name,
                            $source
                        );
                    }

                    if ($var_name) {
                        $if_types[$var_name] = '^isset';
                    }
                }
            }

            return $if_types;
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
            $var_name = ExpressionChecker::getArrayVarId(
                $conditional->left,
                $this_class_name,
                $source
            );

            if ($var_name) {
                $if_types[$var_name] = 'isset';
            } else {
                // look for any variables we *can* use for an isset assertion
                $array_root = $conditional->left;

                while ($array_root instanceof PhpParser\Node\Expr\ArrayDimFetch && !$var_name) {
                    $array_root = $array_root->var;

                    $var_name = ExpressionChecker::getArrayVarId(
                        $array_root,
                        $this_class_name,
                        $source
                    );
                }

                if ($var_name) {
                    $if_types[$var_name] = '^isset';
                }
            }

            return $if_types;
        }

        return [];
    }

    /**
     * @param  PhpParser\Node\Expr\FuncCall $expr
     * @param  string|null                  $this_class_name
     * @param  FileSource                   $source
     * @param  bool                         $negate
     *
     * @return array<string, string>
     */
    protected static function processFunctionCall(
        PhpParser\Node\Expr\FuncCall $expr,
        $this_class_name,
        FileSource $source,
        $negate = false
    ) {
        $prefix = $negate ? '!' : '';

        $first_var_name = isset($expr->args[0]->value)
            ? ExpressionChecker::getArrayVarId(
                $expr->args[0]->value,
                $this_class_name,
                $source
            )
            : null;

        $if_types = [];

        if (self::hasNullCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'null';
            }
        } elseif (self::hasIsACheck($expr)) {
            if ($first_var_name) {
                $first_arg = $expr->args[1]->value;

                $is_a_prefix = '';

                if (isset($expr->args[2]->value)) {
                    $third_arg = $expr->args[2]->value;

                    if (!$third_arg instanceof PhpParser\Node\Expr\ConstFetch
                        || !in_array(strtolower($third_arg->name->parts[0]), ['true', 'false'])
                    ) {
                        return $if_types;
                    }

                    $is_a_prefix = strtolower($third_arg->name->parts[0]) === 'true' ? 'isa-' : '';
                }

                if ($first_arg instanceof PhpParser\Node\Scalar\String_) {
                    $if_types[$first_var_name] = $prefix . $is_a_prefix . $first_arg->value;
                } elseif ($first_arg instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $first_arg->class instanceof PhpParser\Node\Name
                    && is_string($first_arg->name)
                    && strtolower($first_arg->name) === 'class'
                ) {
                    $class_node = $first_arg->class;

                    if ($class_node->parts === ['static'] || $class_node->parts === ['self']) {
                        $if_types[$first_var_name] = $prefix . $is_a_prefix . $this_class_name;
                    } elseif ($class_node->parts === ['parent']) {
                        // do nothing
                    } else {
                        $if_types[$first_var_name] = $prefix . $is_a_prefix . ClassLikeChecker::getFQCLNFromNameObject(
                            $class_node,
                            $source->getAliases()
                        );
                    }
                }
            }
        } elseif (self::hasArrayCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'array';
            }
        } elseif (self::hasBoolCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'bool';
            }
        } elseif (self::hasStringCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'string';
            }
        } elseif (self::hasObjectCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'object';
            }
        } elseif (self::hasNumericCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'numeric';
            }
        } elseif (self::hasIntCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'int';
            }
        } elseif (self::hasFloatCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'float';
            }
        } elseif (self::hasResourceCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'resource';
            }
        } elseif (self::hasScalarCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'scalar';
            }
        } elseif (self::hasCallableCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = $prefix . 'callable';
            }
        } elseif (self::hasArrayKeyExistsCheck($expr)) {
            $array_root = isset($expr->args[1]->value)
                ? ExpressionChecker::getArrayVarId(
                    $expr->args[1]->value,
                    $this_class_name,
                    $source
                )
                : null;

            if ($first_var_name === null && isset($expr->args[0])) {
                $first_arg = $expr->args[0];

                if ($first_arg->value instanceof PhpParser\Node\Scalar\String_) {
                    $first_var_name = '"' . $first_arg->value->value . '"';
                } elseif ($first_arg->value instanceof PhpParser\Node\Scalar\LNumber) {
                    $first_var_name = (string) $first_arg->value->value;
                }
            }

            if ($first_var_name !== null
                && $array_root
                && !strpos($first_var_name, '->')
                && !strpos($first_var_name, '[')
            ) {
                $if_types[$array_root . '[' . $first_var_name . ']'] = $prefix . 'array-key-exists';
            }
        }

        return $if_types;
    }

    /**
     * @param  PhpParser\Node\Expr\Instanceof_ $stmt
     * @param  string|null                     $this_class_name
     * @param  FileSource                $source
     *
     * @return string|null
     */
    protected static function getInstanceOfTypes(
        PhpParser\Node\Expr\Instanceof_ $stmt,
        $this_class_name,
        FileSource $source
    ) {
        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)) {
                $instanceof_class = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $source->getAliases()
                );

                return $instanceof_class;
            } elseif ($this_class_name
                && (in_array(strtolower($stmt->class->parts[0]), ['self', 'static'], true))
            ) {
                return $this_class_name;
            }
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     *
     * @return  int|null
     */
    protected static function hasNullVariable(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->right->name->parts[0]) === 'null'
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->left->name->parts[0]) === 'null'
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     *
     * @return  int|null
     */
    protected static function hasFalseVariable(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->right->name->parts[0]) === 'false'
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->left->name->parts[0]) === 'false'
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     *
     * @return  int|null
     */
    protected static function hasTrueVariable(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->right->name->parts[0]) === 'true'
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->left->name->parts[0]) === 'true'
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     *
     * @return  false|int
     */
    protected static function hasGetTypeCheck(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->right->name->parts[0]) === 'gettype' &&
            $conditional->left instanceof PhpParser\Node\Scalar\String_) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->left->name->parts[0]) === 'gettype' &&
            $conditional->right instanceof PhpParser\Node\Scalar\String_) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     *
     * @return  false|int
     */
    protected static function hasGetClassCheck(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall &&
            $conditional->right->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->right->name->parts[0]) === 'get_class' &&
            (
                $conditional->left instanceof PhpParser\Node\Scalar\String_
                || ($conditional->left instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $conditional->left->class instanceof PhpParser\Node\Name
                    && is_string($conditional->left->name)
                    && strtolower($conditional->left->name) === 'class')
            )
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall &&
            $conditional->left->name instanceof PhpParser\Node\Name &&
            strtolower($conditional->left->name->parts[0]) === 'get_class' &&
            (
                $conditional->right instanceof PhpParser\Node\Scalar\String_
                || ($conditional->right instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $conditional->right->class instanceof PhpParser\Node\Name
                    && is_string($conditional->right->name)
                    && strtolower($conditional->right->name) === 'class')
            )
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\BinaryOp    $conditional
     *
     * @return  false|int
     */
    protected static function hasTypedValueComparison(PhpParser\Node\Expr\BinaryOp $conditional)
    {
        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal) {
            return false;
        }

        if (isset($conditional->right->inferredType)
            && count($conditional->right->inferredType->getTypes()) === 1
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if (isset($conditional->left->inferredType)
            && count($conditional->left->inferredType->getTypes()) === 1
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasNullCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_null') {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasIsACheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name
            && strtolower($stmt->name->parts[0]) === 'is_a'
            && isset($stmt->args[1])
        ) {
            $second_arg = $stmt->args[1]->value;

            if ($second_arg instanceof PhpParser\Node\Scalar\String_
                || (
                    $second_arg instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $second_arg->class instanceof PhpParser\Node\Name
                    && is_string($second_arg->name)
                    && strtolower($second_arg->name) === 'class'
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasArrayCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_array') {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasStringCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_string') {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasBoolCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_bool') {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasObjectCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_object']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasNumericCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_numeric']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasIntCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name &&
            ($stmt->name->parts === ['is_int'] ||
                $stmt->name->parts === ['is_integer'] ||
                $stmt->name->parts === ['is_long'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasFloatCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name &&
            ($stmt->name->parts === ['is_float'] ||
                $stmt->name->parts === ['is_real'] ||
                $stmt->name->parts === ['is_double'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasResourceCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_resource']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasScalarCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_scalar']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasCallableCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_callable']) {
            return true;
        }

        return false;
    }

    /**
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     *
     * @return  bool
     */
    protected static function hasArrayKeyExistsCheck(PhpParser\Node\Expr\FuncCall $stmt)
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['array_key_exists']) {
            return true;
        }

        return false;
    }
}
