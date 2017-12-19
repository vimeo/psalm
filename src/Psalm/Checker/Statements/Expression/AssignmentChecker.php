<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\PropertyProperty;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\InterfaceChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Issue\AssignmentToVoid;
use Psalm\Issue\DeprecatedProperty;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidPropertyAssignment;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\LoopInvalidation;
use Psalm\Issue\MissingDocblockType;
use Psalm\Issue\MixedAssignment;
use Psalm\Issue\MixedPropertyAssignment;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullPropertyAssignment;
use Psalm\Issue\PossiblyInvalidPropertyAssignment;
use Psalm\Issue\PossiblyNullPropertyAssignment;
use Psalm\Issue\ReferenceConstraintViolation;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedPropertyAssignment;
use Psalm\Issue\UndefinedThisPropertyAssignment;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;

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

        $var_comment = null;
        $comment_type = null;

        if ($doc_comment) {
            try {
                $var_comment = CommentChecker::getTypeFromComment(
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

            if ($var_comment) {
                $comment_type = ExpressionChecker::fleshOutType(
                    $statements_checker->getFileChecker()->project_checker,
                    Type::parseString($var_comment->type),
                    $context->self
                );

                $comment_type->setFromDocblock();

                if ($var_comment->var_id && $var_comment->var_id !== $var_id) {
                    $context->vars_in_scope[$var_comment->var_id] = $comment_type;
                }
            }
        }

        if ($assign_value && ExpressionChecker::analyze($statements_checker, $assign_value, $context) === false) {
            if ($var_id) {
                if ($array_var_id) {
                    $context->removeDescendents($array_var_id, null, $assign_value_type);
                }

                // if we're not exiting immediately, make everything mixed
                $context->vars_in_scope[$var_id] =
                    $var_comment && (!$var_comment->var_id || $var_comment->var_id === $var_id) && $comment_type
                        ? $comment_type
                        : Type::getMixed();
            }

            return false;
        }

        if ($var_comment && (!$var_comment->var_id || $var_comment->var_id === $var_id) && $comment_type) {
            $assign_value_type = $comment_type;
        } elseif (!$assign_value_type) {
            if (isset($assign_value->inferredType)) {
                /** @var Type\Union */
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

        if ($assign_value_type->isMixed()) {
            if (IssueBuffer::accepts(
                new MixedAssignment(
                    'Cannot assign ' . $var_id . ' to a mixed type',
                    new CodeLocation($statements_checker->getSource(), $assign_var)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                // fall through
            }
        } elseif ($var_id && isset($context->byref_constraints[$var_id])) {
            if (!TypeChecker::isContainedBy(
                $statements_checker->getFileChecker()->project_checker,
                $assign_value_type,
                $context->byref_constraints[$var_id]->type
            )
            ) {
                if (IssueBuffer::accepts(
                    new ReferenceConstraintViolation(
                        'Variable ' . $var_id . ' is limited to values of type ' .
                            $context->byref_constraints[$var_id]->type . ' because it is passed by reference',
                        new CodeLocation($statements_checker->getSource(), $assign_var)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
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

            if (!$statements_checker->hasVariable($var_id)) {
                $statements_checker->registerVariable($var_id, new CodeLocation($statements_checker, $assign_var));
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
                    && isset($assign_value->items[$offset]->value->inferredType)
                ) {
                    self::analyze(
                        $statements_checker,
                        $var,
                        $assign_value->items[$offset]->value,
                        null,
                        $context,
                        $doc_comment
                    );

                    continue;
                }

                if (isset($assign_value_type->types['array']) &&
                    $assign_value_type->types['array'] instanceof Type\Atomic\ObjectLike &&
                    !$assign_var_item->key &&
                    isset($assign_value_type->types['array']->properties[$offset]) // if object-like has int offsets
                ) {
                    self::analyze(
                        $statements_checker,
                        $var,
                        null,
                        $assign_value_type->types['array']->properties[(string)$offset],
                        $context,
                        $doc_comment
                    );

                    continue;
                }

                if ($var instanceof PhpParser\Node\Expr\List_
                    || $var instanceof PhpParser\Node\Expr\Array_
                ) {
                    self::analyze(
                        $statements_checker,
                        $var,
                        null,
                        Type::getMixed(),
                        $context,
                        $doc_comment
                    );
                }

                $list_var_id = ExpressionChecker::getVarId(
                    $var,
                    $statements_checker->getFQCLN(),
                    $statements_checker
                );

                if ($list_var_id) {
                    $context->vars_possibly_in_scope[$list_var_id] = true;

                    if (!$statements_checker->hasVariable($list_var_id)) {
                        $statements_checker->registerVariable(
                            $list_var_id,
                            new CodeLocation($statements_checker, $var)
                        );
                    }

                    $new_assign_type = null;

                    if (isset($assign_value_type->types['array'])) {
                        if ($assign_value_type->types['array'] instanceof Type\Atomic\TArray) {
                            $new_assign_type = clone $assign_value_type->types['array']->type_params[1];
                        } elseif ($assign_value_type->types['array'] instanceof Type\Atomic\ObjectLike) {
                            if ($assign_var_item->key
                                && ($assign_var_item->key instanceof PhpParser\Node\Scalar\String_
                                    || $assign_var_item->key instanceof PhpParser\Node\Scalar\LNumber)
                                && isset($assign_value_type->types['array']->properties[$assign_var_item->key->value])
                            ) {
                                $new_assign_type =
                                    clone $assign_value_type->types['array']->properties[$assign_var_item->key->value];
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
            if (self::analyzeArrayAssignment(
                $statements_checker,
                $assign_var,
                $context,
                $assign_value_type
            ) === false
            ) {
                return false;
            }
        } elseif ($assign_var instanceof PhpParser\Node\Expr\PropertyFetch) {
            if (is_string($assign_var->name)) {
                self::analyzePropertyAssignment(
                    $statements_checker,
                    $assign_var,
                    $assign_var->name,
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
            $assign_var->class instanceof PhpParser\Node\Name &&
            is_string($assign_var->name)
        ) {
            if (ExpressionChecker::analyze($statements_checker, $assign_var, $context) === false) {
                return false;
            }

            if ($context->check_classes) {
                self::analyzeStaticPropertyAssignment(
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
            ExpressionChecker::analyzeNonDivArithmenticOp(
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
            ExpressionChecker::analyzeConcatOp(
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
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PropertyFetch|PropertyProperty  $stmt
     * @param   string                          $prop_name
     * @param   PhpParser\Node\Expr|null        $assignment_value
     * @param   Type\Union                      $assignment_value_type
     * @param   Context                         $context
     * @param   bool                            $direct_assignment whether the variable is assigned explictly
     *
     * @return  false|null
     */
    public static function analyzePropertyAssignment(
        StatementsChecker $statements_checker,
        $stmt,
        $prop_name,
        $assignment_value,
        Type\Union $assignment_value_type,
        Context $context,
        $direct_assignment = true
    ) {
        $class_property_types = [];

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $property_exists = false;

        if ($stmt instanceof PropertyProperty) {
            if (!$context->self || !$stmt->default) {
                return null;
            }

            $property_id = $context->self . '::$' . $prop_name;

            if (!ClassLikeChecker::propertyExists($project_checker, $property_id)) {
                return null;
            }

            $property_exists = true;

            $declaring_property_class = ClassLikeChecker::getDeclaringClassForProperty($project_checker, $property_id);

            $class_storage = $project_checker->classlike_storage_provider->get((string)$declaring_property_class);

            $class_property_type = $class_storage->properties[$prop_name]->type;

            $class_property_types[] = $class_property_type ? clone $class_property_type : Type::getMixed();

            $var_id = '$this->' . $prop_name;
        } else {
            if (ExpressionChecker::analyze($statements_checker, $stmt->var, $context) === false) {
                return false;
            }

            $lhs_type = isset($stmt->var->inferredType) ? $stmt->var->inferredType : null;

            if ($lhs_type === null) {
                return null;
            }

            $lhs_var_id = ExpressionChecker::getVarId(
                $stmt->var,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            $var_id = ExpressionChecker::getVarId(
                $stmt,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            if ($var_id) {
                $context->assigned_var_ids[$var_id] = true;

                if ($direct_assignment && isset($context->protected_var_ids[$var_id])) {
                    if (IssueBuffer::accepts(
                        new LoopInvalidation(
                            'Variable ' . $var_id . ' has already been assigned in a for/foreach loop',
                            new CodeLocation($statements_checker->getSource(), $stmt->var)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if ($lhs_type->isMixed()) {
                if (IssueBuffer::accepts(
                    new MixedPropertyAssignment(
                        $lhs_var_id . ' of type mixed cannot be assigned to',
                        new CodeLocation($statements_checker->getSource(), $stmt->var)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                return null;
            }

            if ($lhs_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullPropertyAssignment(
                        $lhs_var_id . ' of type null cannot be assigned to',
                        new CodeLocation($statements_checker->getSource(), $stmt->var)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                return null;
            }

            if ($lhs_type->isNullable() && !$lhs_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullPropertyAssignment(
                        $lhs_var_id . ' with possibly null type \'' . $lhs_type . '\' cannot be assigned to',
                        new CodeLocation($statements_checker->getSource(), $stmt->var)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }

            $has_regular_setter = false;

            $invalid_assignment_types = [];

            $has_valid_assignment_type = false;

            foreach ($lhs_type->types as $lhs_type_part) {
                if ($lhs_type_part instanceof TNull) {
                    continue;
                }

                if (!$lhs_type_part instanceof TObject && !$lhs_type_part instanceof TNamedObject) {
                    $invalid_assignment_types[] = (string)$lhs_type_part;

                    continue;
                }

                $has_valid_assignment_type = true;

                // stdClass and SimpleXMLElement are special cases where we cannot infer the return types
                // but we don't want to throw an error
                // Hack has a similar issue: https://github.com/facebook/hhvm/issues/5164
                if ($lhs_type_part instanceof TObject ||
                    (
                        $lhs_type_part instanceof TNamedObject &&
                        in_array(
                            strtolower($lhs_type_part->value),
                            ['stdclass', 'simplexmlelement', 'dateinterval', 'domdocument', 'domnode'],
                            true
                        )
                    )
                ) {
                    if ($var_id) {
                        if ($lhs_type_part instanceof TNamedObject &&
                            strtolower($lhs_type_part->value) === 'stdclass'
                        ) {
                            $context->vars_in_scope[$var_id] = $assignment_value_type;
                        } else {
                            $context->vars_in_scope[$var_id] = Type::getMixed();
                        }
                    }

                    return null;
                }

                if (ExpressionChecker::isMock($lhs_type_part->value)) {
                    $has_regular_setter = true;
                    if ($var_id) {
                        $context->vars_in_scope[$var_id] = Type::getMixed();
                    }

                    return null;
                }

                if (!ClassChecker::classExists($project_checker, $lhs_type_part->value)) {
                    if (InterfaceChecker::interfaceExists($project_checker, $lhs_type_part->value)) {
                        if (IssueBuffer::accepts(
                            new NoInterfaceProperties(
                                'Interfaces cannot have properties',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return null;
                    }

                    if (IssueBuffer::accepts(
                        new UndefinedClass(
                            'Cannot set properties of undefined class ' . $lhs_type_part->value,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return null;
                }

                if ($lhs_var_id !== '$this' &&
                    MethodChecker::methodExists($project_checker, $lhs_type_part . '::__set')
                ) {
                    $class_storage = $project_checker->classlike_storage_provider->get((string)$lhs_type_part);

                    if ($var_id) {
                        if (isset($class_storage->pseudo_property_set_types['$' . $prop_name])) {
                            $class_property_types[] =
                                clone $class_storage->pseudo_property_set_types['$' . $prop_name];
                            $has_regular_setter = true;
                            $property_exists = true;
                            continue;
                        }

                        $context->vars_in_scope[$var_id] = Type::getMixed();
                    }

                    /*
                     * If we have an explicit list of all allowed magic properties on the class, and we're
                     * not in that list, fall through
                     */
                    if (!$var_id || !$class_storage->sealed_properties) {
                        continue;
                    }
                }

                $has_regular_setter = true;

                $property_id = $lhs_type_part->value . '::$' . $prop_name;

                if (!ClassLikeChecker::propertyExists($project_checker, $property_id)) {
                    $has_regular_setter = true;

                    if ($stmt->var instanceof PhpParser\Node\Expr\Variable && $stmt->var->name === 'this') {
                        // if this is a proper error, we'll see it on the first pass
                        if ($context->collect_mutations) {
                            continue;
                        }

                        if (IssueBuffer::accepts(
                            new UndefinedThisPropertyAssignment(
                                'Instance property ' . $property_id . ' is not defined',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new UndefinedPropertyAssignment(
                                'Instance property ' . $property_id . ' is not defined',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }

                    continue;
                }

                $property_exists = true;

                if (ClassLikeChecker::checkPropertyVisibility(
                    $property_id,
                    $context->self,
                    $statements_checker->getSource(),
                    new CodeLocation($statements_checker->getSource(), $stmt),
                    $statements_checker->getSuppressedIssues()
                ) === false) {
                    return false;
                }

                $declaring_property_class = ClassLikeChecker::getDeclaringClassForProperty(
                    $project_checker,
                    $lhs_type_part->value . '::$' . $prop_name
                );

                $class_storage = $project_checker->classlike_storage_provider->get((string)$declaring_property_class);

                $property_storage = $class_storage->properties[$prop_name];

                if ($property_storage->deprecated) {
                    if (IssueBuffer::accepts(
                        new DeprecatedProperty(
                            $property_id . ' is marked deprecated',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $class_property_type = $property_storage->type;

                if ($class_property_type === false) {
                    $class_property_type = Type::getMixed();

                    if (!$assignment_value_type->isMixed()) {
                        if ($property_storage->suggested_type) {
                            $property_storage->suggested_type = Type::combineUnionTypes(
                                $assignment_value_type,
                                $property_storage->suggested_type
                            );
                        } else {
                            $property_storage->suggested_type =
                                $lhs_var_id === '$this' &&
                                    ($context->inside_constructor || $context->collect_initializations)
                                    ? $assignment_value_type
                                    : Type::combineUnionTypes(Type::getNull(), $assignment_value_type);
                        }
                    }
                } else {
                    $class_property_type = ExpressionChecker::fleshOutType(
                        $project_checker,
                        $class_property_type,
                        $lhs_type_part->value
                    );
                }

                $class_property_types[] = $class_property_type;
            }

            if ($invalid_assignment_types) {
                $invalid_assignment_type = $invalid_assignment_types[0];

                if (!$has_valid_assignment_type) {
                    if (IssueBuffer::accepts(
                        new InvalidPropertyAssignment(
                            $lhs_var_id . ' with non-object type \'' . $invalid_assignment_type .
                            '\' cannot treated as an object',
                            new CodeLocation($statements_checker->getSource(), $stmt->var)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidPropertyAssignment(
                            $lhs_var_id . ' with possible non-object type \'' . $invalid_assignment_type .
                            '\' cannot treated as an object',
                            new CodeLocation($statements_checker->getSource(), $stmt->var)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
            }

            if (!$has_regular_setter) {
                return null;
            }

            if ($var_id) {
                // because we don't want to be assigning for property declarations
                $context->vars_in_scope[$var_id] = $assignment_value_type;
            }
        }

        if ($var_id && count($class_property_types) === 1 && isset($class_property_types[0]->types['stdClass'])) {
            $context->vars_in_scope[$var_id] = Type::getMixed();

            return null;
        }

        if (!$property_exists) {
            return null;
        }

        if ($assignment_value_type->isMixed()) {
            return null;
        }

        $invalid_assignment_value_types = [];

        $has_valid_assignment_value_type = false;

        foreach ($class_property_types as $class_property_type) {
            if ($class_property_type->isMixed()) {
                continue;
            }

            if (!TypeChecker::isContainedBy(
                $project_checker,
                $assignment_value_type,
                $class_property_type,
                $assignment_value_type->ignore_nullable_issues
            )) {
                $invalid_assignment_value_types[] = [
                    (string)$class_property_type,
                    (string)$assignment_value_type,
                ];
            } else {
                $has_valid_assignment_value_type = true;
            }
        }

        if ($invalid_assignment_value_types) {
            list($class_property_type, $invalid_assignment_value_type)
                = $invalid_assignment_value_types[0];

            if (!$has_valid_assignment_value_type) {
                if (IssueBuffer::accepts(
                    new InvalidPropertyAssignment(
                        $var_id . ' with declared type \'' . $class_property_type .
                            '\' cannot be assigned type \'' . $invalid_assignment_value_type . '\'',
                        new CodeLocation(
                            $statements_checker->getSource(),
                            $assignment_value ?: $stmt,
                            $context->include_location
                        )
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            } else {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidPropertyAssignment(
                        $var_id . ' with declared type \'' . $class_property_type .
                            '\' cannot be assigned possibly different type \'' .
                            $invalid_assignment_value_type . '\'',
                        new CodeLocation(
                            $statements_checker->getSource(),
                            $assignment_value ?: $stmt,
                            $context->include_location
                        )
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        return null;
    }

    /**
     * @param   StatementsChecker                         $statements_checker
     * @param   PhpParser\Node\Expr\StaticPropertyFetch   $stmt
     * @param   PhpParser\Node\Expr|null                  $assignment_value
     * @param   Type\Union                                $assignment_value_type
     * @param   Context                                   $context
     *
     * @return  false|null
     */
    protected static function analyzeStaticPropertyAssignment(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\StaticPropertyFetch $stmt,
        $assignment_value,
        Type\Union $assignment_value_type,
        Context $context
    ) {
        $var_id = ExpressionChecker::getVarId(
            $stmt,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $fq_class_name = (string)$stmt->class->inferredType;

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $prop_name = $stmt->name;

        if (!is_string($prop_name)) {
            return;
        }

        $property_id = $fq_class_name . '::$' . $prop_name;

        if (!ClassLikeChecker::propertyExists($project_checker, $property_id)) {
            if ($stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts[0] === 'this') {
                if (IssueBuffer::accepts(
                    new UndefinedThisPropertyAssignment(
                        'Static property ' . $property_id . ' is not defined',
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            } else {
                if (IssueBuffer::accepts(
                    new UndefinedPropertyAssignment(
                        'Static property ' . $property_id . ' is not defined',
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }

            return;
        }

        if (ClassLikeChecker::checkPropertyVisibility(
            $property_id,
            $context->self,
            $statements_checker->getSource(),
            new CodeLocation($statements_checker->getSource(), $stmt),
            $statements_checker->getSuppressedIssues()
        ) === false) {
            return false;
        }

        $declaring_property_class = ClassLikeChecker::getDeclaringClassForProperty(
            $project_checker,
            $fq_class_name . '::$' . $prop_name
        );

        $class_storage = $project_checker->classlike_storage_provider->get((string)$declaring_property_class);

        $property_storage = $class_storage->properties[$prop_name];

        if ($var_id) {
            $context->vars_in_scope[$var_id] = $assignment_value_type;
        }

        $class_property_type = $property_storage->type;

        if ($class_property_type === false) {
            $class_property_type = Type::getMixed();

            if (!$assignment_value_type->isMixed()) {
                if ($property_storage->suggested_type) {
                    $property_storage->suggested_type = Type::combineUnionTypes(
                        $assignment_value_type,
                        $property_storage->suggested_type
                    );
                } else {
                    $property_storage->suggested_type = Type::combineUnionTypes(
                        Type::getNull(),
                        $assignment_value_type
                    );
                }
            }
        } else {
            $class_property_type = clone $class_property_type;
        }

        if ($assignment_value_type->isMixed()) {
            return null;
        }

        if ($class_property_type->isMixed()) {
            return null;
        }

        $class_property_type = ExpressionChecker::fleshOutType(
            $project_checker,
            $class_property_type,
            $fq_class_name
        );

        if (!TypeChecker::isContainedBy(
            $project_checker,
            $assignment_value_type,
            $class_property_type
        )) {
            if (IssueBuffer::accepts(
                new InvalidPropertyAssignment(
                    $var_id . ' with declared type \'' . $class_property_type . '\' cannot be assigned type \'' .
                        $assignment_value_type . '\'',
                    new CodeLocation(
                        $statements_checker->getSource(),
                        $assignment_value ?: $stmt
                    )
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        if ($var_id) {
            $context->vars_in_scope[$var_id] = $assignment_value_type;
        }

        return null;
    }

    /**
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\ArrayDimFetch   $stmt
     * @param   Context                             $context
     * @param   Type\Union                          $assignment_value_type
     *
     * @return  false|null
     * @psalm-suppress MixedMethodCall - some funky logic here
     */
    protected static function analyzeArrayAssignment(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        Type\Union $assignment_value_type
    ) {
        $nesting = 0;
        $var_id = ExpressionChecker::getVarId(
            $stmt->var,
            $statements_checker->getFQCLN(),
            $statements_checker,
            $nesting
        );

        AssignmentChecker::updateArrayType(
            $statements_checker,
            $stmt,
            $assignment_value_type,
            $context
        );

        if (!isset($stmt->var->inferredType) && $var_id) {
            $context->vars_in_scope[$var_id] = Type::getMixed();
        }

        return null;
    }

    /**
     * @param  StatementsChecker                 $statements_checker
     * @param  PhpParser\Node\Expr\ArrayDimFetch $stmt
     * @param  Type\Union                        $assignment_type
     * @param  Context                           $context
     *
     * @return false|null
     */
    public static function updateArrayType(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Type\Union $assignment_type,
        Context $context
    ) {
        $root_array_expr = $stmt;

        $child_stmts = [];

        while ($root_array_expr->var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            $child_stmts[] = $root_array_expr;
            $root_array_expr = $root_array_expr->var;
        }

        $child_stmts[] = $root_array_expr;
        $root_array_expr = $root_array_expr->var;

        if (ExpressionChecker::analyze(
            $statements_checker,
            $root_array_expr,
            $context,
            true
        ) === false) {
            // fall through
        }

        $root_type = isset($root_array_expr->inferredType) ? $root_array_expr->inferredType : Type::getMixed();

        if ($root_type->isMixed()) {
            return null;
        }

        $child_stmts = array_reverse($child_stmts);

        $current_type = $root_type;

        $current_dim = $stmt->dim;

        $reversed_child_stmts = [];

        // gets a variable id that *may* contain array keys
        $root_var_id = ExpressionChecker::getRootVarId(
            $root_array_expr,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $var_id_additions = [];

        $real_var_id = true;

        $child_stmt = null;

        // First go from the root element up, and go as far as we can to figure out what
        // array types there are
        while ($child_stmts) {
            /** @var PhpParser\Node\Expr\ArrayDimFetch */
            $child_stmt = array_shift($child_stmts);

            if (count($child_stmts)) {
                array_unshift($reversed_child_stmts, $child_stmt);
            }

            if ($child_stmt->dim) {
                if (ExpressionChecker::analyze(
                    $statements_checker,
                    $child_stmt->dim,
                    $context
                ) === false) {
                    return false;
                }

                if (!isset($child_stmt->dim->inferredType)) {
                    return null;
                }

                if ($child_stmt->dim instanceof PhpParser\Node\Scalar\String_) {
                    $var_id_additions[] = '[\'' . $child_stmt->dim->value . '\']';
                } else {
                    $var_id_additions[] = '[' . $child_stmt->dim->inferredType . ']';
                    $real_var_id = false;
                }
            } else {
                $var_id_additions[] = '';
                $real_var_id = false;
            }

            if (!isset($child_stmt->var->inferredType)) {
                return null;
            }

            if ($child_stmt->var->inferredType->isEmpty()) {
                $child_stmt->var->inferredType = Type::getEmptyArray();
            }

            $array_var_id = $root_var_id . implode('', $var_id_additions);

            $child_stmt->inferredType = FetchChecker::getArrayAccessTypeGivenOffset(
                $statements_checker,
                $child_stmt,
                $child_stmt->var->inferredType,
                isset($child_stmt->dim->inferredType) ? $child_stmt->dim->inferredType : Type::getInt(),
                true,
                $array_var_id,
                $child_stmts ? null : $assignment_type
            );

            if (!$child_stmts) {
                $child_stmt->inferredType = $assignment_type;
            }

            $current_type = $child_stmt->inferredType;
            $current_dim = $child_stmt->dim;

            if ($child_stmt->var->inferredType->isMixed()) {
                break;
            }
        }

        if ($root_var_id
            && $real_var_id
            && isset($child_stmt->var->inferredType)
            && !$child_stmt->var->inferredType->hasObjectType()
        ) {
            $array_var_id = $root_var_id . implode('', $var_id_additions);
            $context->vars_in_scope[$array_var_id] = clone $assignment_type;
        }

        // only update as many child stmts are we were able to process above
        foreach ($reversed_child_stmts as $child_stmt) {
            if (!isset($child_stmt->inferredType)) {
                throw new \InvalidArgumentException('Should never get here');
            }

            if ($current_dim instanceof PhpParser\Node\Scalar\String_
                || $current_dim instanceof PhpParser\Node\Scalar\LNumber
            ) {
                $key_value = $current_dim->value;

                $has_matching_objectlike_property = false;

                foreach ($child_stmt->inferredType->types as $type) {
                    if ($type instanceof ObjectLike) {
                        if (isset($type->properties[$key_value])) {
                            $has_matching_objectlike_property = true;

                            $type->properties[$key_value] = clone $current_type;
                        }
                    }
                }

                if (!$has_matching_objectlike_property) {
                    $array_assignment_type = new Type\Union([
                        new ObjectLike([$key_value => $current_type]),
                    ]);

                    $new_child_type = Type::combineUnionTypes(
                        $child_stmt->inferredType,
                        $array_assignment_type
                    );
                } else {
                    $new_child_type = $child_stmt->inferredType; // noop
                }
            } else {
                $array_assignment_type = new Type\Union([
                    new TArray([
                        isset($current_dim->inferredType) ? $current_dim->inferredType : Type::getInt(),
                        $current_type,
                    ]),
                ]);

                $new_child_type = Type::combineUnionTypes(
                    $child_stmt->inferredType,
                    $array_assignment_type
                );
            }

            unset($new_child_type->types['null']);

            if (!$child_stmt->inferredType->hasObjectType()) {
                $child_stmt->inferredType = $new_child_type;
            }

            $current_type = $child_stmt->inferredType;
            $current_dim = $child_stmt->dim;

            array_pop($var_id_additions);

            if ($root_var_id) {
                $array_var_id = $root_var_id . implode('', $var_id_additions);
                $context->vars_in_scope[$array_var_id] = clone $child_stmt->inferredType;
            }
        }

        $root_is_string = array_keys($root_type->types) === ['string'];

        if (($current_dim instanceof PhpParser\Node\Scalar\String_
                || $current_dim instanceof PhpParser\Node\Scalar\LNumber)
            && ($current_dim instanceof PhpParser\Node\Scalar\String_
                || !$root_is_string)
        ) {
            $key_value = $current_dim->value;

            $has_matching_objectlike_property = false;

            foreach ($root_type->types as $type) {
                if ($type instanceof ObjectLike) {
                    if (isset($type->properties[$key_value])) {
                        $has_matching_objectlike_property = true;

                        $type->properties[$key_value] = clone $current_type;
                    }
                }
            }

            if (!$has_matching_objectlike_property) {
                $array_assignment_type = new Type\Union([
                    new ObjectLike([$key_value => $current_type]),
                ]);

                $new_child_type = Type::combineUnionTypes(
                    $root_type,
                    $array_assignment_type
                );
            } else {
                $new_child_type = $root_type; // noop
            }
        } elseif (!$root_is_string) {
            $array_assignment_type = new Type\Union([
                new TArray([
                    isset($current_dim->inferredType) ? $current_dim->inferredType : Type::getInt(),
                    $current_type,
                ]),
            ]);

            $new_child_type = Type::combineUnionTypes(
                $root_type,
                $array_assignment_type
            );
        } else {
            $new_child_type = $root_type;
        }

        unset($new_child_type->types['null']);

        if (!$root_type->hasObjectType()) {
            $root_type = $new_child_type;
        }

        $root_array_expr->inferredType = $root_type;

        if ($root_array_expr instanceof PhpParser\Node\Expr\PropertyFetch) {
            if (is_string($root_array_expr->name)) {
                self::analyzePropertyAssignment(
                    $statements_checker,
                    $root_array_expr,
                    $root_array_expr->name,
                    null,
                    $root_type,
                    $context,
                    false
                );
            } else {
                if (ExpressionChecker::analyze($statements_checker, $root_array_expr->name, $context) === false) {
                    return false;
                }

                if (ExpressionChecker::analyze($statements_checker, $root_array_expr->var, $context) === false) {
                    return false;
                }
            }
        } elseif ($root_var_id) {
            if ($context->hasVariable($root_var_id)) {
                $context->vars_in_scope[$root_var_id] = $root_type;
            } else {
                $context->vars_in_scope[$root_var_id] = $root_type;
            }
        }

        return null;
    }
}
