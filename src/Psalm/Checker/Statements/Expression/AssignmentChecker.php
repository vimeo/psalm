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
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\FailedTypeResolution;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\Issue\InvalidPropertyAssignment;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\InaccessibleProperty;
use Psalm\Issue\MissingPropertyDeclaration;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\MixedAssignment;
use Psalm\Issue\MixedPropertyAssignment;
use Psalm\Issue\MixedStringOffsetAssignment;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullPropertyAssignment;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedPropertyAssignment;
use Psalm\Issue\UndefinedThisPropertyAssignment;
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
     * @return false|Type\Union
     */
    public static function check(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $assign_var,
        PhpParser\Node\Expr $assign_value = null,
        Type\Union $assign_value_type = null,
        Context $context,
        $doc_comment
    ) {
        $var_id = ExpressionChecker::getVarId(
            $assign_var,
            $statements_checker->getFQCLN(),
            $statements_checker->getNamespace(),
            $statements_checker->getAliasedClasses()
        );

        $array_var_id = ExpressionChecker::getArrayVarId(
            $assign_var,
            $statements_checker->getFQCLN(),
            $statements_checker->getNamespace(),
            $statements_checker->getAliasedClasses()
        );

        if ($array_var_id) {
            // removes dependennt vars from $context
            $context->removeDescendents($array_var_id);
        }

        $type_in_comments = CommentChecker::getTypeFromComment(
            $doc_comment,
            $context,
            $statements_checker->getSource(),
            $var_id
        );

        if ($assign_value && ExpressionChecker::check($statements_checker, $assign_value, $context) === false) {
            // if we're not exiting immediately, make everything mixed
            $context->vars_in_scope[$var_id] = $type_in_comments ?: Type::getMixed();

            return false;
        }

        if (!$assign_value_type) {
            if ($type_in_comments) {
                $assign_value_type = $type_in_comments;
            } elseif (isset($assign_value->inferredType)) {
                /** @var Type\Union */
                $assign_value_type = $assign_value->inferredType;
            } else {
                $assign_value_type = Type::getMixed();
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
            $statements_checker->registerVariable($var_id, $assign_var->getLine());
        } elseif ($assign_var instanceof PhpParser\Node\Expr\List_
                || $assign_var instanceof PhpParser\Node\Expr\Array_
        ) {
            foreach ($assign_var->items as $offset => $assign_var_item) {
                // $var can be null e.g. list($a, ) = ['a', 'b']
                $var = $assign_var_item->value;

                if (!$var) {
                    continue;
                }

                if ($assign_value instanceof PhpParser\Node\Expr\Array_
                    && isset($assign_value->items[$offset]->value->inferredType)
                ) {
                    self::check(
                        $statements_checker,
                        $var,
                        $assign_value->items[$offset]->value,
                        null,
                        $context,
                        $doc_comment
                    );

                    continue;
                }

                $list_var_id = ExpressionChecker::getVarId(
                    $var,
                    $statements_checker->getFQCLN(),
                    $statements_checker->getNamespace(),
                    $statements_checker->getAliasedClasses()
                );

                if ($list_var_id) {
                    $context->vars_possibly_in_scope[$list_var_id] = true;
                    $statements_checker->registerVariable($list_var_id, $var->getLine());

                    if (isset($assign_value_type->types['array'])) {
                        if ($assign_value_type->types['array'] instanceof Type\Generic) {
                            $context->vars_in_scope[$list_var_id] = clone $assign_value_type->types['array']->type_params[1];

                            continue;
                        } elseif ($assign_value_type->types['array'] instanceof Type\ObjectLike) {
                            if ($assign_var_item->key
                                && $assign_var_item->key instanceof PhpParser\Node\Scalar\String_
                                && isset($assign_value_type->types['array']->properties[$assign_var_item->key->value])
                            ) {
                                $context->vars_in_scope[$list_var_id] =
                                    clone $assign_value_type->types['array']->properties[$assign_var_item->key->value];

                                continue;
                            }
                        }
                    }

                    $context->vars_in_scope[$list_var_id] = Type::getMixed();
                }
            }
        } elseif ($assign_var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            if (self::checkArrayAssignment($statements_checker, $assign_var, $context, $assign_value_type) === false) {
                return false;
            }
        } elseif ($assign_var instanceof PhpParser\Node\Expr\PropertyFetch && is_string($assign_var->name)) {
            self::checkPropertyAssignment(
                $statements_checker,
                $assign_var,
                $assign_var->name,
                $assign_value,
                $assign_value_type,
                $context
            );

            $context->vars_possibly_in_scope[$var_id] = true;
        } elseif ($assign_var instanceof PhpParser\Node\Expr\StaticPropertyFetch &&
            $assign_var->class instanceof PhpParser\Node\Name &&
            is_string($assign_var->name)
        ) {
            if (ExpressionChecker::check($statements_checker, $assign_var, $context) === false) {
                return false;
            }

            self::checkStaticPropertyAssignment(
                $statements_checker,
                $assign_var,
                $assign_value,
                $assign_value_type,
                $context
            );

            $context->vars_possibly_in_scope[$var_id] = true;
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
     * @return  false|null
     */
    public static function checkAssignmentOperation(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\AssignOp $stmt,
        Context $context
    ) {
        if (ExpressionChecker::check($statements_checker, $stmt->var, $context) === false) {
            return false;
        }

        if (ExpressionChecker::check($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }

        $var_id = ExpressionChecker::getVarId(
            $stmt->var,
            $statements_checker->getFQCLN(),
            $statements_checker->getNamespace(),
            $statements_checker->getAliasedClasses()
        );

        $var_type = isset($stmt->var->inferredType) ? clone $stmt->var->inferredType : null;
        $expr_type = isset($stmt->expr->inferredType) ? $stmt->expr->inferredType : null;

        if ($stmt instanceof PhpParser\Node\Expr\AssignOp\Plus ||
            $stmt instanceof PhpParser\Node\Expr\AssignOp\Minus ||
            $stmt instanceof PhpParser\Node\Expr\AssignOp\Mod ||
            $stmt instanceof PhpParser\Node\Expr\AssignOp\Mul ||
            $stmt instanceof PhpParser\Node\Expr\AssignOp\Pow
        ) {
            ExpressionChecker::checkNonDivArithmenticOp($var_type, $expr_type, $result_type);

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
        }

        return null;
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PropertyFetch|PropertyProperty  $stmt
     * @param   string                          $prop_name
     * @param   PhpParser\Node\Expr|null        $assignment_value
     * @param   Type\Union                      $assignment_value_type
     * @param   Context                         $context
     * @return  false|null
     */
    public static function checkPropertyAssignment(
        StatementsChecker $statements_checker,
        $stmt,
        $prop_name,
        PhpParser\Node\Expr $assignment_value = null,
        Type\Union $assignment_value_type,
        Context $context
    ) {
        $class_property_types = [];

        if ($stmt instanceof PropertyProperty) {
            if (!$context->self || !$stmt->default) {
                return null;
            }

            $class_properties = ClassLikeChecker::getInstancePropertiesForClass(
                $context->self,
                \ReflectionProperty::IS_PRIVATE
            );

            $class_property_type = $class_properties[$prop_name];

            $class_property_types[] = $class_property_type ? clone $class_property_type : Type::getMixed();

            $assignment_var = $stmt->default;

            $var_id = '$this->' . $prop_name;
        } else {
            $assignment_var = $stmt;

            if (ExpressionChecker::check($statements_checker, $stmt->var, $context) === false) {
                return false;
            }

            /** @var Type\Union */
            $lhs_type = $stmt->var->inferredType;

            $lhs_var_id = ExpressionChecker::getVarId(
                $stmt->var,
                $statements_checker->getFQCLN(),
                $statements_checker->getNamespace(),
                $statements_checker->getAliasedClasses()
            );

            $var_id = ExpressionChecker::getVarId(
                $stmt,
                $statements_checker->getFQCLN(),
                $statements_checker->getNamespace(),
                $statements_checker->getAliasedClasses()
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

            if ($lhs_type->isNullable()) {
                if (IssueBuffer::accepts(
                    new NullPropertyAssignment(
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
                if ($lhs_type_part->isNull()) {
                    continue;
                }

                if (MethodChecker::methodExists($lhs_type_part . '::__set')) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    continue;
                }

                $has_regular_setter = true;

                if (!$lhs_type_part->isObjectType()) {
                    if (IssueBuffer::accepts(
                        new InvalidPropertyAssignment(
                            $lhs_var_id . ' with possible non-object type \'' . $lhs_type_part . '\' cannot treated as an object',
                            new CodeLocation($statements_checker->getSource(), $stmt->var)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }

                if ($lhs_type_part->isObject()) {
                    continue;
                }

                // stdClass and SimpleXMLElement are special cases where we cannot infer the return types
                // but we don't want to throw an error
                // Hack has a similar issue: https://github.com/facebook/hhvm/issues/5164
                if ($lhs_type_part->isObject() ||
                    in_array(
                        strtolower($lhs_type_part->value),
                        ['stdclass', 'simplexmlelement', 'dateinterval', 'domdocument', 'domnode']
                    )
                ) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    return null;
                }

                if (ExpressionChecker::isMock($lhs_type_part->value)) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    return null;
                }

                if (($stmt->var instanceof PhpParser\Node\Expr\Variable && $stmt->var->name === 'this')
                    || $lhs_type_part->value === $context->self
                ) {
                    $class_visibility = \ReflectionProperty::IS_PRIVATE;
                } elseif ($context->self && ClassChecker::classExtends($lhs_type_part->value, $context->self)) {
                    $class_visibility = \ReflectionProperty::IS_PROTECTED;
                } else {
                    $class_visibility = \ReflectionProperty::IS_PUBLIC;
                }

                if (!ClassChecker::classExists($lhs_type_part->value)) {
                    if (InterfaceChecker::interfaceExists($lhs_type_part->value)) {
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

                $class_properties = ClassLikeChecker::getInstancePropertiesForClass(
                    $lhs_type_part->value,
                    $class_visibility
                );

                if (!isset($class_properties[$prop_name])) {
                    if ($stmt->var instanceof PhpParser\Node\Expr\Variable && $stmt->var->name === 'this') {
                        if (IssueBuffer::accepts(
                            new UndefinedThisPropertyAssignment(
                                'Instance property ' . $lhs_type_part->value . '::$' . $prop_name . ' is not defined',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new UndefinedPropertyAssignment(
                                'Instance property ' . $lhs_type_part->value . '::$' . $prop_name . ' is not defined',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }

                    continue;
                }

                $class_property_type = $class_properties[$prop_name];

                if ($class_property_type === false) {
                    if (IssueBuffer::accepts(
                        new MissingPropertyType(
                            'Property ' . $lhs_type_part->value . '::$' . $stmt->name . ' does not have a declared ' .
                                'type',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    $class_property_type = Type::getMixed();
                } else {
                    $class_property_type = ExpressionChecker::fleshOutTypes(
                        clone $class_property_type,
                        [],
                        $lhs_type_part->value
                    );
                }

                $class_property_types[] = $class_property_type;
            }

            if (!$has_regular_setter) {
                return null;
            }

            // because we don't want to be assigning for property declarations
            $context->vars_in_scope[$var_id] = $assignment_value_type;
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

            if (!$assignment_value_type->isIn($class_property_type)) {
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
        }

        return null;
    }

    /**
     * @param   StatementsChecker                         $statements_checker
     * @param   PhpParser\Node\Expr\StaticPropertyFetch   $stmt
     * @param   PhpParser\Node\Expr|null                  $assignment_value
     * @param   Type\Union                                $assignment_value_type
     * @param   Context                                   $context
     * @return  false|null
     */
    protected static function checkStaticPropertyAssignment(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\StaticPropertyFetch $stmt,
        PhpParser\Node\Expr $assignment_value = null,
        Type\Union $assignment_value_type,
        Context $context
    ) {
        $class_property_types = [];

        $var_id = ExpressionChecker::getVarId(
            $stmt,
            $statements_checker->getFQCLN(),
            $statements_checker->getNamespace(),
            $statements_checker->getAliasedClasses()
        );

        $fq_class_name = (string)$stmt->class->inferredType;

        if (($stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts[0] === 'this') ||
            $fq_class_name === $context->self
        ) {
            $class_visibility = \ReflectionProperty::IS_PRIVATE;
        } elseif ($context->self && ClassChecker::classExtends($fq_class_name, $context->self)) {
            $class_visibility = \ReflectionProperty::IS_PROTECTED;
        } else {
            $class_visibility = \ReflectionProperty::IS_PUBLIC;
        }

        $class_properties = ClassLikeChecker::getStaticPropertiesForClass(
            $fq_class_name,
            $class_visibility
        );

        $all_class_properties = ClassLikeChecker::getStaticPropertiesForClass(
            $fq_class_name,
            $class_visibility
        );

        $prop_name = $stmt->name;

        if (!isset($class_properties[$prop_name])) {
            $all_class_properties = null;

            if ($class_visibility !== \ReflectionProperty::IS_PRIVATE) {
                $all_class_properties = ClassLikeChecker::getStaticPropertiesForClass(
                    $fq_class_name,
                    \ReflectionProperty::IS_PRIVATE
                );
            }

            if (isset($all_class_properties[$prop_name])) {
                if (IssueBuffer::accepts(
                    new InaccessibleProperty(
                        'Static property ' . $var_id . ' is not visible in this context',
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            } else {
                if ($stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts[0] === 'this') {
                    if (IssueBuffer::accepts(
                        new UndefinedThisPropertyAssignment(
                            'Static property ' . $var_id . ' is not defined',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedPropertyAssignment(
                            'Static property ' . $var_id . ' is not defined',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
            }

            return null;
        }

        $context->vars_in_scope[$var_id] = $assignment_value_type;

        $class_property_type = $class_properties[$prop_name];

        if ($class_property_type === false) {
            if (IssueBuffer::accepts(
                new MissingPropertyType(
                    'Property ' . $fq_class_name . '::$' . $prop_name . ' does not have a declared type',
                    new CodeLocation($statements_checker->getSource(), $stmt)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                // fall through
            }

            $class_property_type = Type::getMixed();
        } else {
            $class_property_type = clone $class_property_type;
        }

        if ($assignment_value_type->isMixed()) {
            return null;
        }

        if ($class_property_type->isMixed()) {
            return null;
        }

        $class_property_type = ExpressionChecker::fleshOutTypes($class_property_type, [], $fq_class_name);

        if (!$assignment_value_type->isIn($class_property_type)) {
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

        $context->vars_in_scope[$var_id] = $assignment_value_type;
        return null;
    }

    /**
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\ArrayDimFetch   $stmt
     * @param   Context                             $context
     * @param   Type\Union                          $assignment_value_type
     * @return  false|null
     * @psalm-suppress MixedMethodCall - some funky logic here
     */
    protected static function checkArrayAssignment(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        Type\Union $assignment_value_type
    ) {
        if ($stmt->dim && ExpressionChecker::check($statements_checker, $stmt->dim, $context, false) === false) {
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
            $statements_checker->getNamespace(),
            $statements_checker->getAliasedClasses(),
            $nesting
        );

        // checks whether or not the thing we're looking at implements ArrayAccess
        $is_object = $var_id
            && isset($context->vars_in_scope[$var_id])
            && $context->vars_in_scope[$var_id]->hasObjectType();

        if (ExpressionChecker::check(
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
            $statements_checker->getNamespace(),
            $statements_checker->getAliasedClasses()
        );

        $is_string = isset($stmt->var->inferredType)
            && $stmt->var->inferredType->hasString();

        $has_scalar = isset($stmt->var->inferredType)
            && $stmt->var->inferredType->hasScalarType();

        $keyed_array_var_id = $array_var_id && $stmt->dim instanceof PhpParser\Node\Scalar\String_
            ? $array_var_id . '[\'' . $stmt->dim->value . '\']'
            : null;

        if (isset($stmt->var->inferredType)) {
            $return_type = $stmt->var->inferredType;

            if ($is_object) {
                // do nothing
            } elseif ($is_string) {
                foreach ($assignment_value_type->types as $value_type) {
                    if (!$value_type->isString()) {
                        if ($value_type->isMixed()) {
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
            } elseif ($has_scalar) {

                if (IssueBuffer::accepts(
                    new InvalidArrayAssignment(
                        'Cannot assign value on variable ' . $var_id . ' of scalar type ' . $context->vars_in_scope[$var_id],
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
                    /** @var Type\Generic|null */
                    $array_type = isset($context->vars_in_scope[$var_id]->types['array'])
                                    && $context->vars_in_scope[$var_id]->types['array'] instanceof Type\Generic
                                    ? $context->vars_in_scope[$var_id]->types['array']
                                    : null;

                    if ($assignment_key_type->hasString()
                        && $assignment_key_value
                        && (!isset($context->vars_in_scope[$var_id])
                            || $context->vars_in_scope[$var_id]->hasObjectLike()
                            || ($array_type && $array_type->type_params[0]->isEmpty()))
                    ) {
                        $assignment_value_type = new Type\Union([
                            new Type\ObjectLike(
                                'array',
                                [
                                    $assignment_key_value => $assignment_value_type
                                ]
                            )
                        ]);
                    } else {
                        $assignment_value_type = new Type\Union([
                            new Type\Generic(
                                'array',
                                [
                                    $assignment_key_type,
                                    $assignment_value_type
                                ]
                            )
                        ]);
                    }

                    if (isset($context->vars_in_scope[$var_id])) {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $context->vars_in_scope[$var_id],
                            $assignment_value_type
                        );
                    } else {
                        $context->vars_in_scope[$var_id] = $assignment_value_type;
                    }
                }
            }
        } else {
            $context->vars_in_scope[$var_id] = Type::getMixed();
        }

        return null;
    }
}
