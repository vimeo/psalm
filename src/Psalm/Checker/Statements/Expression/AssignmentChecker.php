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
use Psalm\Issue\DeprecatedProperty;
use Psalm\Issue\FailedTypeResolution;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\Issue\InvalidPropertyAssignment;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\MissingPropertyDeclaration;
use Psalm\Issue\MixedAssignment;
use Psalm\Issue\MixedPropertyAssignment;
use Psalm\Issue\MixedStringOffsetAssignment;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullPropertyAssignment;
use Psalm\Issue\PossiblyNullPropertyAssignment;
use Psalm\Issue\ReferenceConstraintViolation;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedPropertyAssignment;
use Psalm\Issue\UndefinedThisPropertyAssignment;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;

class AssignmentChecker
{
    /**
     * @param  StatementsChecker        $statements_checker
     * @param  PhpParser\Node\Expr      $assign_var
     * @param  PhpParser\Node\Expr|null $assign_value  This has to be null to support list destructuring
     * @param  Type\Union|null          $assign_value_type
     * @param  Context                  $context
     * @param  string                   $doc_comment
     * @param  bool                     $by_reference
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
        $by_reference = false,
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
            $var_comment = CommentChecker::getTypeFromComment(
                $doc_comment,
                $context,
                $statements_checker->getSource(),
                $statements_checker->getAliases(),
                null,
                $came_from_line_number
            );

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

        if ($assign_var instanceof PhpParser\Node\Expr\Variable && is_string($assign_var->name) && $var_id) {
            $context->vars_in_scope[$var_id] = $assign_value_type;
            $context->vars_possibly_in_scope[$var_id] = true;
            $context->assigned_vars[$var_id] = true;

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
                } elseif (isset($assign_value_type->types['array']) &&
                    $assign_value_type->types['array'] instanceof Type\Atomic\ObjectLike &&
                    !$assign_var_item->key &&
                    isset($assign_value_type->types['array']->properties[$offset]) // if object-like has int offsets
                ) {
                    self::analyze(
                        $statements_checker,
                        $var,
                        null,
                        $assign_value_type->types['array']->properties[$offset],
                        $context,
                        $doc_comment
                    );

                    continue;
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
                                && $assign_var_item->key instanceof PhpParser\Node\Scalar\String_
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
        } elseif ($assign_var instanceof PhpParser\Node\Expr\PropertyFetch && is_string($assign_var->name)) {
            self::analyzePropertyAssignment(
                $statements_checker,
                $assign_var,
                $assign_var->name,
                $assign_value,
                $assign_value_type,
                $context
            );

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
                new FailedTypeResolution(
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

        $var_id = ExpressionChecker::getVarId(
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

            if ($result_type && $var_id) {
                $context->vars_in_scope[$var_id] = $result_type;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp\Div
            && $var_type
            && $expr_type
            && $var_type->hasNumericType()
            && $expr_type->hasNumericType()
            && $var_id
        ) {
            $context->vars_in_scope[$var_id] = Type::combineUnionTypes(Type::getFloat(), Type::getInt());
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp\Concat) {
            ExpressionChecker::analyzeConcatOp(
                $statements_checker,
                $stmt->var,
                $stmt->expr,
                $context,
                $result_type
            );

            if ($result_type && $var_id) {
                $context->vars_in_scope[$var_id] = $result_type;
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
            (string)$stmt->getDocComment(),
            true
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
     *
     * @return  false|null
     */
    public static function analyzePropertyAssignment(
        StatementsChecker $statements_checker,
        $stmt,
        $prop_name,
        $assignment_value,
        Type\Union $assignment_value_type,
        Context $context
    ) {
        $class_property_types = [];

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        if ($stmt instanceof PropertyProperty) {
            if (!$context->self || !$stmt->default) {
                return null;
            }

            $property_id = $context->self . '::$' . $prop_name;

            if (!ClassLikeChecker::propertyExists($project_checker, $property_id)) {
                return null;
            }

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

            foreach ($lhs_type->types as $lhs_type_part) {
                if ($lhs_type_part instanceof TNull) {
                    continue;
                }

                if (!$lhs_type_part instanceof TObject && !$lhs_type_part instanceof TNamedObject) {
                    if (IssueBuffer::accepts(
                        new InvalidPropertyAssignment(
                            $lhs_var_id . ' with possible non-object type \'' . $lhs_type_part .
                            '\' cannot treated as an object',
                            new CodeLocation($statements_checker->getSource(), $stmt->var)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }

                // stdClass and SimpleXMLElement are special cases where we cannot infer the return types
                // but we don't want to throw an error
                // Hack has a similar issue: https://github.com/facebook/hhvm/issues/5164
                if ($lhs_type_part instanceof TObject ||
                    ($lhs_type_part instanceof TNamedObject &&
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
                    if ($var_id) {
                        $class_storage = $project_checker->classlike_storage_provider->get((string)$lhs_type_part);

                        if (isset($class_storage->pseudo_property_set_types['$' . $prop_name])) {
                            $class_property_types[] =
                                clone $class_storage->pseudo_property_set_types['$' . $prop_name];
                            $has_regular_setter = true;
                            continue;
                        }

                        $context->vars_in_scope[$var_id] = Type::getMixed();
                    }
                    continue;
                }

                $has_regular_setter = true;

                if (($stmt->var instanceof PhpParser\Node\Expr\Variable && $stmt->var->name === 'this')
                    || $lhs_type_part->value === $context->self
                ) {
                    $class_visibility = \ReflectionProperty::IS_PRIVATE;
                } elseif ($context->self &&
                    ClassChecker::classExtends($project_checker, $lhs_type_part->value, $context->self)
                ) {
                    $class_visibility = \ReflectionProperty::IS_PROTECTED;
                } else {
                    $class_visibility = \ReflectionProperty::IS_PUBLIC;
                }

                $property_id = $lhs_type_part->value . '::$' . $prop_name;

                if (!ClassLikeChecker::propertyExists($project_checker, $property_id)) {
                    if ($stmt->var instanceof PhpParser\Node\Expr\Variable && $stmt->var->name === 'this') {
                        // if this is a proper error, we'll see it on the first pass
                        if ($context->collect_mutations) {
                            return;
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

                $property_storage = $class_storage->properties[$stmt->name];

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

        if (!$class_property_types) {
            if (IssueBuffer::accepts(
                new MissingPropertyDeclaration(
                    'Missing property declaration for ' . $var_id,
                    new CodeLocation($statements_checker->getSource(), $stmt)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        if ($assignment_value_type->isMixed()) {
            return null;
        }

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
                if (IssueBuffer::accepts(
                    new InvalidPropertyAssignment(
                        $var_id . ' with declared type \'' . $class_property_type . '\' cannot be assigned type \'' .
                            $assignment_value_type . '\'',
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

        if (($stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts[0] === 'this') ||
            $fq_class_name === $context->self
        ) {
            $class_visibility = \ReflectionProperty::IS_PRIVATE;
        } elseif ($context->self &&
            ClassChecker::classExtends($project_checker, $fq_class_name, $context->self)
        ) {
            $class_visibility = \ReflectionProperty::IS_PROTECTED;
        } else {
            $class_visibility = \ReflectionProperty::IS_PUBLIC;
        }

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

        $property_storage = $class_storage->properties[$stmt->name];

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
        if ($stmt->dim && ExpressionChecker::analyze($statements_checker, $stmt->dim, $context, false) === false) {
            return false;
        }

        $assignment_key_type = null;
        $assignment_key_value = null;

        if ($stmt->dim) {
            if (isset($stmt->dim->inferredType)) {
                /** @var Type\Union */
                $assignment_key_type = $stmt->dim->inferredType;

                if ($stmt->dim instanceof PhpParser\Node\Scalar\String_) {
                    $assignment_key_value = $stmt->dim->value;
                }
            } else {
                $assignment_key_type = Type::getMixed();
            }
        } else {
            $assignment_key_type = Type::getInt();
        }

        $nesting = 0;
        $var_id = ExpressionChecker::getVarId(
            $stmt->var,
            $statements_checker->getFQCLN(),
            $statements_checker,
            $nesting
        );

        // checks whether or not the thing we're looking at implements ArrayAccess
        $is_object = $var_id
            && $context->hasVariable($var_id)
            && $context->vars_in_scope[$var_id]->hasObjectType();

        if (ExpressionChecker::analyze(
            $statements_checker,
            $stmt->var,
            $context,
            !$is_object,
            $assignment_key_type,
            $assignment_value_type,
            $assignment_key_value
        ) === false) {
            return false;
        }

        $array_var_id = ExpressionChecker::getArrayVarId(
            $stmt->var,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        if (isset($stmt->var->inferredType)) {
            $return_type = $stmt->var->inferredType;

            $keyed_array_var_id = $array_var_id && $stmt->dim instanceof PhpParser\Node\Scalar\String_
                ? $array_var_id . '[\'' . $stmt->dim->value . '\']'
                : null;

            $project_checker = $statements_checker->getFileChecker()->project_checker;

            if ($return_type->hasObjectType()) {
                foreach ($return_type->types as $left_type_part) {
                    if ($left_type_part instanceof TNamedObject &&
                        (strtolower($left_type_part->value) !== 'simplexmlelement' &&
                            ClassChecker::classExists($project_checker, $left_type_part->value) &&
                            !ClassChecker::classImplements($project_checker, $left_type_part->value, 'ArrayAccess')
                        )
                    ) {
                        if (IssueBuffer::accepts(
                            new InvalidArrayAssignment(
                                'Cannot assign array value on non-array variable ' .
                                $array_var_id . ' of type ' . $return_type,
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            $stmt->inferredType = Type::getMixed();
                            break;
                        }
                    }
                }
            } elseif ($return_type->hasString()) {
                foreach ($assignment_value_type->types as $value_type) {
                    if (!$value_type instanceof TString) {
                        if ($value_type instanceof TMixed) {
                            if (IssueBuffer::accepts(
                                new MixedStringOffsetAssignment(
                                    'Cannot assign a mixed variable to a string offset for ' . $var_id,
                                    new CodeLocation($statements_checker->getSource(), $stmt)
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                return false;
                            }

                            continue;
                        }

                        if (IssueBuffer::accepts(
                            new InvalidArrayAssignment(
                                'Cannot assign string offset for  ' . $var_id . ' of type ' . $value_type,
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        break;
                    }
                }
            } elseif ($return_type->hasScalarType()) {
                if (IssueBuffer::accepts(
                    new InvalidArrayAssignment(
                        'Cannot assign value on variable ' . $var_id . ' of scalar type ' .
                            $context->vars_in_scope[$var_id],
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            } else {
                // we want to support multiple array types:
                // - Dictionaries (which have the type array<string,T>)
                // - pseudo-objects (which have the type array<string,mixed>)
                // - typed arrays (which have the type array<int,T>)
                // and completely freeform arrays
                //
                // When making assignments, we generally only know the shape of the array
                // as it is being created.
                if ($keyed_array_var_id) {
                    // when we have a pattern like
                    // $a = [];
                    // $a['b']['c']['d'] = 1;
                    // $a['c'] = 2;
                    // we need to create each type in turn
                    // so we get
                    // typeof $a['b']['c']['d'] => int
                    // typeof $a['b']['c'] => array{d:int}
                    // typeof $a['b'] => array{c:array{d:int}}
                    // typeof $a['c'] => int
                    // typeof $a => array{b:array{c:array{d:int}},c:int}

                    $context->vars_in_scope[$keyed_array_var_id] = $assignment_value_type;

                    $stmt->inferredType = $assignment_value_type;
                }

                if (!$nesting) {
                    /** @var Type\Atomic\TArray|null */
                    $array_type = isset($context->vars_in_scope[$var_id]->types['array'])
                                    && $context->vars_in_scope[$var_id]->types['array'] instanceof Type\Atomic\TArray
                                    ? $context->vars_in_scope[$var_id]->types['array']
                                    : null;

                    if ($assignment_key_type->hasString()
                        && $assignment_key_value
                        && (!$context->hasVariable($var_id)
                            || $context->vars_in_scope[$var_id]->hasObjectLike()
                            || ($array_type && $array_type->type_params[0]->isEmpty()))
                    ) {
                        $assignment_value_type = new Type\Union([
                            new Type\Atomic\ObjectLike([
                                $assignment_key_value => $assignment_value_type,
                            ]),
                        ]);
                    } else {
                        $assignment_value_type = new Type\Union([
                            new Type\Atomic\TArray([
                                $assignment_key_type,
                                $assignment_value_type,
                            ]),
                        ]);
                    }

                    if ($stmt->var instanceof PhpParser\Node\Expr\PropertyFetch && is_string($stmt->var->name)) {
                        self::analyzePropertyAssignment(
                            $statements_checker,
                            $stmt->var,
                            $stmt->var->name,
                            null,
                            $assignment_value_type,
                            $context
                        );
                    } elseif ($var_id) {
                        if ($context->hasVariable($var_id)) {
                            $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                $context->vars_in_scope[$var_id],
                                $assignment_value_type
                            );
                        } else {
                            $context->vars_in_scope[$var_id] = $assignment_value_type;
                        }
                    }
                }
            }
        } elseif ($var_id) {
            $context->vars_in_scope[$var_id] = Type::getMixed();
        }

        return null;
    }
}
