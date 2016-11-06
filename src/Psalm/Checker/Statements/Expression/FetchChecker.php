<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\InterfaceChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\TraitChecker;
use Psalm\Context;
use Psalm\Issue\InvalidArrayAccess;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\Issue\InvalidPropertyFetch;
use Psalm\Issue\InvisibleProperty;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\MixedArrayOffset;
use Psalm\Issue\MixedPropertyFetch;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullPropertyFetch;
use Psalm\Issue\NullReference;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedConstant;
use Psalm\Issue\UndefinedPropertyFetch;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\IssueBuffer;
use Psalm\Type;

class FetchChecker
{
    /**
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\PropertyFetch   $stmt
     * @param   Context                             $context
     * @param   bool                                $array_assignment
     * @return  false|null
     */
    public static function checkPropertyFetch(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        Context $context,
        $array_assignment = false
    ) {
        if (!is_string($stmt->name)) {
            if (ExpressionChecker::check($statements_checker, $stmt->name, $context) === false) {
                return false;
            }
        }

        $var_id = null;

        if (ExpressionChecker::check($statements_checker, $stmt->var, $context) === false) {
            return false;
        }

        $stmt_var_id = ExpressionChecker::getVarId(
            $stmt->var,
            $statements_checker->getAbsoluteClass(),
            $statements_checker->getNamespace(),
            $statements_checker->getAliasedClasses()
        );

        $var_id = ExpressionChecker::getVarId(
            $stmt,
            $statements_checker->getAbsoluteClass(),
            $statements_checker->getNamespace(),
            $statements_checker->getAliasedClasses()
        );

        $var_name = is_string($stmt->name) ? $stmt->name : null;

        $stmt_var_type = null;

        if ($var_id && isset($context->vars_in_scope[$var_id])) {
            // we don't need to check anything
            $stmt->inferredType = $context->vars_in_scope[$var_id];
            return null;
        }

        if ($stmt_var_id && isset($context->vars_in_scope[$stmt_var_id])) {
            $stmt_var_type = $context->vars_in_scope[$stmt_var_id];
        } elseif (isset($stmt->var->inferredType)) {
            /** @var Type\Union */
            $stmt_var_type = $stmt->var->inferredType;
        }

        if (!$stmt_var_type) {
            return null;
        }

        if ($stmt_var_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Cannot get property on null variable ' . $stmt_var_id,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine()
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        if ($stmt_var_type->isEmpty()) {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Cannot fetch property on empty var ' . $stmt_var_id,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine()
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        if ($stmt_var_type->isMixed()) {
            if (IssueBuffer::accepts(
                new MixedPropertyFetch(
                    'Cannot fetch property on mixed var ' . $stmt_var_id,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine()
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        if ($stmt_var_type->isNullable()) {
            if (IssueBuffer::accepts(
                new NullPropertyFetch(
                    'Cannot get property on possibly null variable ' . $stmt_var_id,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine()
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            $stmt->inferredType = Type::getNull();
        }

        if (!is_string($stmt->name)) {
            return null;
        }

        foreach ($stmt_var_type->types as $lhs_type_part) {
            if ($lhs_type_part->isNull()) {
                continue;
            }

            if (!$lhs_type_part->isObjectType()) {
                $stmt_var_id = ExpressionChecker::getVarId(
                    $stmt->var,
                    $statements_checker->getAbsoluteClass(),
                    $statements_checker->getNamespace(),
                    $statements_checker->getAliasedClasses()
                );

                if (IssueBuffer::accepts(
                    new InvalidPropertyFetch(
                        'Cannot fetch property on non-object ' . $stmt_var_id . ' of type ' . $lhs_type_part,
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
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
            if ($lhs_type_part->isObject() ||
                in_array(strtolower($lhs_type_part->value), ['stdclass', 'simplexmlelement'])
            ) {
                $stmt->inferredType = Type::getMixed();
                continue;
            }

            if (MethodChecker::methodExists($lhs_type_part . '::__get')) {
                $stmt->inferredType = Type::getMixed();
                continue;
            }

            if (!ClassChecker::classExists($lhs_type_part->value)) {
                if (InterfaceChecker::interfaceExists($lhs_type_part->value)) {
                    if (IssueBuffer::accepts(
                        new NoInterfaceProperties(
                            'Interfaces cannot have properties',
                            $statements_checker->getCheckedFileName(),
                            $stmt->getLine()
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }

                if (IssueBuffer::accepts(
                    new UndefinedClass(
                        'Cannot get properties of undefined class ' . $lhs_type_part->value,
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                continue;
            }

            if ($var_name === 'this'
                || $lhs_type_part->value === $context->self
                || (
                    $statements_checker->getSource()->getSource() instanceof TraitChecker &&
                    $lhs_type_part->value === $statements_checker->getSource()->getAbsoluteClass()
                )
            ) {
                $class_visibility = \ReflectionProperty::IS_PRIVATE;
            } elseif ($context->self && ClassChecker::classExtends($lhs_type_part->value, $context->self)) {
                $class_visibility = \ReflectionProperty::IS_PROTECTED;
            } else {
                $class_visibility = \ReflectionProperty::IS_PUBLIC;
            }

            $class_properties = ClassLikeChecker::getInstancePropertiesForClass(
                $lhs_type_part->value,
                $class_visibility
            );

            if (!$class_properties || !isset($class_properties[$stmt->name])) {
                $stmt->inferredType = Type::getMixed();

                if ($var_id) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                }

                if ($stmt_var_id === '$this') {
                    if (IssueBuffer::accepts(
                        new UndefinedThisPropertyFetch(
                            'Instance property ' . $lhs_type_part->value .'::$' . $stmt->name . ' is not defined',
                            $statements_checker->getCheckedFileName(),
                            $stmt->getLine()
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedPropertyFetch(
                            'Instance property ' . $lhs_type_part->value .'::$' . $stmt->name . ' is not defined',
                            $statements_checker->getCheckedFileName(),
                            $stmt->getLine()
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                return null;
            }

            $class_property_type = $class_properties[$stmt->name];

            if ($class_property_type === false) {
                if (IssueBuffer::accepts(
                    new MissingPropertyType(
                        'Property ' . $lhs_type_part->value . '::$' . $stmt->name . ' does not have a declared type',
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }

                $class_property_type = Type::getMixed();
            } else {
                $class_property_type = clone $class_property_type;
            }

            if (isset($stmt->inferredType)) {
                $stmt->inferredType = Type::combineUnionTypes($class_property_type, $stmt->inferredType);
            } else {
                $stmt->inferredType = $class_property_type;
            }
        }

        if ($var_id) {
            $context->vars_in_scope[$var_id] = isset($stmt->inferredType) ? $stmt->inferredType : Type::getMixed();
        }
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\ConstFetch  $stmt
     * @param   Context                         $context
     * @return  false|null
     */
    public static function checkConstFetch(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ConstFetch $stmt,
        Context $context
    ) {
        $const_name = implode('', $stmt->name->parts);
        switch (strtolower($const_name)) {
            case 'null':
                $stmt->inferredType = Type::getNull();
                break;

            case 'false':
                // false is a subtype of bool
                $stmt->inferredType = Type::getFalse();
                break;

            case 'true':
                $stmt->inferredType = Type::getBool();
                break;

            default:
                if ($const_type = $statements_checker->getConstType($const_name)) {
                    $stmt->inferredType = clone $const_type;
                } elseif ($context->check_consts && !defined($const_name)) {
                    if (IssueBuffer::accepts(
                        new UndefinedConstant(
                            'Const ' . $const_name . ' is not defined',
                            $statements_checker->getCheckedFileName(),
                            $stmt->getLine()
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
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\ClassConstFetch $stmt
     * @param   Context                             $context
     * @return  null|false
     */
    public static function checkClassConstFetch(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ClassConstFetch $stmt,
        Context $context
    ) {
        if ($context->check_consts &&
            $stmt->class instanceof PhpParser\Node\Name &&
            $stmt->class->parts !== ['static']
        ) {
            if ($stmt->class->parts === ['self']) {
                $absolute_class = (string)$context->self;
            } else {
                $absolute_class = ClassLikeChecker::getAbsoluteClassFromName(
                    $stmt->class,
                    $statements_checker->getNamespace(),
                    $statements_checker->getAliasedClasses()
                );

                if (ClassLikeChecker::checkAbsoluteClassOrInterface(
                    $absolute_class,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine(),
                    $statements_checker->getSuppressedIssues()
                ) === false) {
                    return false;
                }
            }

            $const_id = $absolute_class . '::' . $stmt->name;

            if ($stmt->name === 'class') {
                $stmt->inferredType = Type::getString();
                return null;
            }

            $class_constants = ClassLikeChecker::getConstantsForClass($absolute_class, \ReflectionProperty::IS_PUBLIC);

            if (!isset($class_constants[$stmt->name])) {
                if (IssueBuffer::accepts(
                    new UndefinedConstant(
                        'Const ' . $const_id . ' is not defined',
                        $statements_checker->getCheckedFileName(),
                        $stmt->getLine()
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            } else {
                $stmt->inferredType = $class_constants[$stmt->name];
            }

            return null;
        }

        if ($stmt->class instanceof PhpParser\Node\Expr) {
            if (ExpressionChecker::check($statements_checker, $stmt->class, $context) === false) {
                return false;
            }
        }

        return null;
    }

    /**
     * @param   StatementsChecker                       $statements_checker
     * @param   PhpParser\Node\Expr\StaticPropertyFetch $stmt
     * @param   Context                                 $context
     * @return  null|false
     */
    public static function checkStaticPropertyFetch(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\StaticPropertyFetch $stmt,
        Context $context
    ) {
        if ($stmt->class instanceof PhpParser\Node\Expr\Variable ||
            $stmt->class instanceof PhpParser\Node\Expr\ArrayDimFetch
        ) {
            // @todo check this
            return null;
        }

        $method_id = null;
        $absolute_class = null;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (count($stmt->class->parts) === 1 && in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                if ($stmt->class->parts[0] === 'parent') {
                    $absolute_class = $statements_checker->getParentClass();

                    if ($absolute_class === null) {
                        if (IssueBuffer::accepts(
                            new ParentNotFound(
                                'Cannot check property fetch on parent as this class does not extend another',
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return;
                    }
                } else {
                    $absolute_class = ($statements_checker->getNamespace()
                        ? $statements_checker->getNamespace() . '\\'
                        : '') . $statements_checker->getClassName();
                }

                if ($context->isPhantomClass($absolute_class)) {
                    return null;
                }
            } elseif ($context->check_classes) {
                $absolute_class = ClassLikeChecker::getAbsoluteClassFromName(
                    $stmt->class,
                    $statements_checker->getNamespace(),
                    $statements_checker->getAliasedClasses()
                );

                if ($context->isPhantomClass($absolute_class)) {
                    return null;
                }

                if (ClassLikeChecker::checkAbsoluteClassOrInterface(
                    $absolute_class,
                    $statements_checker->getCheckedFileName(),
                    $stmt->getLine(),
                    $statements_checker->getSuppressedIssues()
                ) === false) {
                    return false;
                }
            }

            $stmt->class->inferredType = $absolute_class ? new Type\Union([new Type\Atomic($absolute_class)]) : null;
        }

        if ($absolute_class &&
            $context->check_variables &&
            is_string($stmt->name) &&
            !ExpressionChecker::isMock($absolute_class)
        ) {
            $var_id = ExpressionChecker::getVarId(
                $stmt,
                $statements_checker->getAbsoluteClass(),
                $statements_checker->getNamespace(),
                $statements_checker->getAliasedClasses()
            );

            if ($var_id && isset($context->vars_in_scope[$var_id])) {
                // we don't need to check anything
                $stmt->inferredType = $context->vars_in_scope[$var_id];
                return null;
            }

            if ($absolute_class === $context->self
                || (
                    $statements_checker->getSource()->getSource() instanceof TraitChecker &&
                    $absolute_class === $statements_checker->getSource()->getAbsoluteClass()
                )
            ) {
                $class_visibility = \ReflectionProperty::IS_PRIVATE;
            } elseif ($context->self && ClassChecker::classExtends($context->self, $absolute_class)) {
                $class_visibility = \ReflectionProperty::IS_PROTECTED;
            } else {
                $class_visibility = \ReflectionProperty::IS_PUBLIC;
            }

            $visible_class_properties = ClassLikeChecker::getStaticPropertiesForClass(
                $absolute_class,
                $class_visibility
            );

            if (!isset($visible_class_properties[$stmt->name])) {
                $all_class_properties = [];

                if ($absolute_class !== $context->self) {
                    $all_class_properties = ClassLikeChecker::getStaticPropertiesForClass(
                        $absolute_class,
                        \ReflectionProperty::IS_PRIVATE
                    );
                }

                if ($all_class_properties && isset($all_class_properties[$stmt->name])) {
                    IssueBuffer::add(
                        new InvisibleProperty(
                            'Static property ' . $var_id . ' is not visible in this context',
                            $statements_checker->getCheckedFileName(),
                            $stmt->getLine()
                        )
                    );
                } else {
                    IssueBuffer::add(
                        new UndefinedPropertyFetch(
                            'Static property ' . $var_id . ' does not exist',
                            $statements_checker->getCheckedFileName(),
                            $stmt->getLine()
                        )
                    );
                }

                return false;
            }

            $visible_class_property = $visible_class_properties[$stmt->name];

            $context->vars_in_scope[$var_id] = $visible_class_property
                ? clone $visible_class_property
                : Type::getMixed();

            $stmt->inferredType = clone $context->vars_in_scope[$var_id];
        }

        return null;
    }

    /**
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\ArrayDimFetch   $stmt
     * @param   Context                             $context
     * @param   bool                                $array_assignment
     * @param   Type\Union|null                     $assignment_key_type
     * @param   Type\Union|null                     $assignment_value_type
     * @param   string|null                         $assignment_key_value
     * @return  false|null
     */
    public static function checkArrayAccess(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        $array_assignment = false,
        Type\Union $assignment_key_type = null,
        Type\Union $assignment_value_type = null,
        $assignment_key_value = null
    ) {
        $var_type = null;
        $key_type = null;
        $key_value = null;

        $nesting = 0;
        $var_id = ExpressionChecker::getVarId(
            $stmt->var,
            $statements_checker->getAbsoluteClass(),
            $statements_checker->getNamespace(),
            $statements_checker->getAliasedClasses(),
            $nesting
        );

        // checks whether or not the thing we're looking at implements ArrayAccess
        $is_object = $var_id
            && isset($context->vars_in_scope[$var_id])
            && $context->vars_in_scope[$var_id]->hasObjectType();

        $array_var_id = ExpressionChecker::getArrayVarId(
            $stmt->var,
            $statements_checker->getAbsoluteClass(),
            $statements_checker->getNamespace(),
            $statements_checker->getAliasedClasses()
        );

        $keyed_array_var_id = $array_var_id && $stmt->dim instanceof PhpParser\Node\Scalar\String_
            ? $array_var_id . '[\'' . $stmt->dim->value . '\']'
            : null;

        if ($stmt->dim && ExpressionChecker::check($statements_checker, $stmt->dim, $context) === false) {
            return false;
        }

        if ($stmt->dim) {
            if (isset($stmt->dim->inferredType)) {
                /** @var Type\Union */
                $key_type = $stmt->dim->inferredType;

                if ($stmt->dim instanceof PhpParser\Node\Scalar\String_) {
                    $key_value = $stmt->dim->value;
                }
            } else {
                $key_type = Type::getMixed();
            }
        } else {
            $key_type = Type::getInt();
        }

        $keyed_assignment_type = null;

        if ($array_assignment && $assignment_key_type && $assignment_value_type) {
            $keyed_assignment_type =
                $keyed_array_var_id && isset($context->vars_in_scope[$keyed_array_var_id])
                    ? $context->vars_in_scope[$keyed_array_var_id]
                    : null;

            if (!$keyed_assignment_type || $keyed_assignment_type->isEmpty()) {
                if (!$assignment_key_type->isMixed() && !$assignment_key_type->hasInt() && $assignment_key_value) {
                    $keyed_assignment_type = new Type\Union([
                        new Type\ObjectLike(
                            'array',
                            [
                                $assignment_key_value => $assignment_value_type
                            ]
                        )
                    ]);
                } else {
                    $keyed_assignment_type = Type::getEmptyArray();
                    /** @var Type\Generic */
                    $keyed_assignment_type_array = $keyed_assignment_type->types['array'];
                    $keyed_assignment_type_array->type_params[0] = $assignment_key_type;
                    $keyed_assignment_type_array->type_params[1] = $assignment_value_type;
                }
            } else {
                foreach ($keyed_assignment_type->types as &$type) {
                    if ($type->isScalarType() && !$type->isString()) {
                        if (IssueBuffer::accepts(
                            new InvalidArrayAssignment(
                                'Cannot assign value on variable ' . $var_id . ' of scalar type ' . $type->value,
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        continue;
                    }

                    if ($type instanceof Type\Generic) {
                        $refined_type = self::refineArrayType(
                            $statements_checker,
                            $type,
                            $assignment_key_type,
                            $assignment_value_type,
                            $var_id,
                            $stmt->getLine()
                        );

                        if ($refined_type === false) {
                            return false;
                        }

                        if ($refined_type === null) {
                            continue;
                        }

                        $type = $refined_type;
                    } elseif ($type instanceof Type\ObjectLike && $assignment_key_value) {
                        if (isset($type->properties[$assignment_key_value])) {
                            $type->properties[$assignment_key_value] = Type::combineUnionTypes(
                                $type->properties[$assignment_key_value],
                                $assignment_value_type
                            );
                        } else {
                            $type->properties[$assignment_key_value] = $assignment_value_type;
                        }
                    }
                }
            }
        }

        if (ExpressionChecker::check(
            $statements_checker,
            $stmt->var,
            $context,
            $array_assignment,
            $key_type,
            $keyed_assignment_type,
            $key_value
        ) === false) {
            return false;
        }

        if (isset($stmt->var->inferredType)) {
            /** @var Type\Union */
            $var_type = $stmt->var->inferredType;

            foreach ($var_type->types as &$type) {
                if ($type instanceof Type\Generic || $type instanceof Type\ObjectLike) {
                    $value_index = null;

                    if ($type instanceof Type\Generic) {
                        // create a union type to pass back to the statement
                        $value_index = count($type->type_params) - 1;

                        if ($value_index) {
                            // if we're assigning to an empty array with a key offset, refashion that array
                            if ($array_assignment && $type->type_params[0]->isEmpty()) {
                                if ($key_type) {
                                    $type->type_params[0] = $key_type;
                                }
                            } else {
                                if ($key_type) {
                                    $key_type = Type::combineUnionTypes($key_type, $type->type_params[0]);
                                } else {
                                    $key_type = $type->type_params[0];
                                }
                            }
                        }
                    }

                    if ($array_assignment && !$is_object) {
                        // if we're in an array assignment then we need to create some variables
                        // e.g.
                        // $a = [];
                        // $a['b']['c']['d'] = 3;
                        //
                        // means we need add $a['b'], $a['b']['c'] to the current context
                        // (but not $a['b']['c']['d'], which is handled in checkArrayAssignment)
                        if ($keyed_array_var_id && $keyed_assignment_type) {
                            if (isset($context->vars_in_scope[$keyed_array_var_id])) {
                                $context->vars_in_scope[$keyed_array_var_id] = Type::combineUnionTypes(
                                    $keyed_assignment_type,
                                    $context->vars_in_scope[$keyed_array_var_id]
                                );
                            } else {
                                $context->vars_in_scope[$keyed_array_var_id] = $keyed_assignment_type;
                            }

                            $stmt->inferredType = $keyed_assignment_type;
                        }

                        if ($array_var_id === $var_id) {
                            if ($type instanceof Type\ObjectLike ||
                                ($type->isGenericArray() && !$key_type->hasInt() && $type->type_params[1]->isEmpty())
                            ) {
                                $properties = $key_value ? [$key_value => $keyed_assignment_type] : [];

                                $assignment_type = new Type\Union([
                                    new Type\ObjectLike(
                                        'array',
                                        $properties
                                    )
                                ]);
                            } else {
                                $assignment_type = new Type\Union([
                                    new Type\Generic(
                                        'array',
                                        [
                                            $key_type,
                                            $keyed_assignment_type
                                        ]
                                    )
                                ]);
                            }

                            if (isset($context->vars_in_scope[$var_id])) {
                                $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                    $context->vars_in_scope[$var_id],
                                    $assignment_type
                                );
                            } else {
                                $context->vars_in_scope[$var_id] = $assignment_type;
                            }
                        }

                        if ($type instanceof Type\Generic && $type->type_params[$value_index]->isEmpty()) {
                            $empty_type = Type::getEmptyArray();

                            if (!isset($stmt->inferredType)) {
                                // if in array assignment and the referenced variable does not have
                                // an array at this level, create one
                                $stmt->inferredType = $empty_type;
                            }

                            $context_type = clone $context->vars_in_scope[$var_id];

                            $array_type = $context_type;

                            for ($i = 0; $i < $nesting + 1; $i++) {
                                if (isset($array_type->types['array']) &&
                                    $array_type->types['array'] instanceof Type\Generic
                                ) {
                                    $atomic_array = $array_type->types['array'];

                                    if ($i < $nesting) {
                                        if ($atomic_array->type_params[1]->isEmpty()) {
                                            $new_empty = clone $empty_type;
                                            /** @var Type\Generic */
                                            $new_atomic_empty = $new_empty->types['array'];
                                            $new_atomic_empty->type_params[0] = $key_type;

                                            $atomic_array->type_params[1] = $new_empty;
                                            continue;
                                        }

                                        $array_type = $atomic_array->type_params[1];
                                    } else {
                                        $atomic_array->type_params[0] = $key_type;

                                        if ($nesting === 0 && $keyed_assignment_type) {
                                            $atomic_array->type_params[1] = $keyed_assignment_type;
                                        }
                                    }
                                }
                            }

                            $context->vars_in_scope[$var_id] = $context_type;
                        }
                    } elseif ($type instanceof Type\Generic && $value_index !== null) {
                        $stmt->inferredType = $type->type_params[$value_index];
                    } elseif ($type instanceof Type\ObjectLike) {
                        if ($key_value && isset($type->properties[$key_value])) {
                            $stmt->inferredType = clone $type->properties[$key_value];
                        } elseif ($key_type->hasInt()) {
                            $object_like_keys = array_keys($type->properties);
                            if ($object_like_keys) {
                                if (count($object_like_keys) === 1) {
                                    $expected_keys_string = '\'' . $object_like_keys[0] . '\'';
                                } else {
                                    $last_key = array_pop($object_like_keys);
                                    $expected_keys_string = '\'' . implode('\', \'', $object_like_keys) .
                                        '\' or \'' . $last_key . '\'';
                                }
                            } else {
                                $expected_keys_string = 'string';
                            }

                            if (IssueBuffer::accepts(
                                new InvalidArrayAccess(
                                    'Cannot access value on array variable ' . $var_id . ' using int offset - ' .
                                        'expecting ' . $expected_keys_string,
                                    $statements_checker->getCheckedFileName(),
                                    $stmt->getLine()
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                return false;
                            }
                        }
                    }
                } elseif ($type->isString()) {
                    if ($key_type) {
                        $key_type = Type::combineUnionTypes($key_type, Type::getInt());
                    } else {
                        $key_type = Type::getInt();
                    }

                    $stmt->inferredType = Type::getString();
                }
            }
        }

        if ($keyed_array_var_id && isset($context->vars_in_scope[$keyed_array_var_id])) {
            $stmt->inferredType = $context->vars_in_scope[$keyed_array_var_id];
        }

        if (!isset($stmt->inferredType)) {
            $stmt->inferredType = Type::getMixed();
        }

        if (!$key_type) {
            $key_type = new Type\Union([
                new Type\Atomic('int'),
                new Type\Atomic('string')
            ]);
        }

        if ($stmt->dim) {
            if (isset($stmt->dim->inferredType) && $key_type && !$key_type->isEmpty()) {
                foreach ($stmt->dim->inferredType->types as $at) {
                    if (($at->isMixed() || $at->isEmpty()) && !$key_type->isMixed()) {
                        if (IssueBuffer::accepts(
                            new MixedArrayOffset(
                                'Cannot access value on variable ' . $var_id . ' using mixed offset - expecting ' .
                                    $key_type,
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    } elseif (!$at->isIn($key_type)) {
                        if (IssueBuffer::accepts(
                            new InvalidArrayAccess(
                                'Cannot access value on variable ' . $var_id . ' using ' . $at . ' offset - ' .
                                    'expecting ' . $key_type,
                                $statements_checker->getCheckedFileName(),
                                $stmt->getLine()
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param   StatementsChecker       $statements_checker
     * @param   Type\Atomic             $type
     * @param   Type\Union              $assignment_key_type
     * @param   Type\Union              $assignment_value_type
     * @param   string|null             $var_id
     * @param   int                     $line_number
     * @return  Type\Atomic|null|false
     */
    protected static function refineArrayType(
        StatementsChecker $statements_checker,
        Type\Atomic $type,
        Type\Union $assignment_key_type,
        Type\Union $assignment_value_type,
        $var_id,
        $line_number
    ) {
        if ($type->value === 'null') {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Cannot assign value on possibly null array' . ($var_id ? ' ' . $var_id : ''),
                    $statements_checker->getCheckedFileName(),
                    $line_number
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return $type;
        }

        if ($type->value === 'string' && $assignment_value_type->hasString() && !$assignment_key_type->hasString()) {
            return null;
        }

        if (!$type->isArray() && !ClassChecker::classImplements($type->value, 'ArrayAccess')) {
            if (IssueBuffer::accepts(
                new InvalidArrayAssignment(
                    'Cannot assign value on variable' . ($var_id ? ' ' . $var_id  : '') . ' of type ' . $type->value . ' that does not ' .
                        'implement ArrayAccess',
                    $statements_checker->getCheckedFileName(),
                    $line_number
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return $type;
        }

        if ($type instanceof Type\Generic && $type->value === 'array') {
            if ($type->type_params[1]->isEmpty()) {
                $type->type_params[0] = $assignment_key_type;
                $type->type_params[1] = $assignment_value_type;
                return $type;
            }

            if ((string) $type->type_params[0] !== (string) $assignment_key_type) {
                $type->type_params[0] = Type::combineUnionTypes($type->type_params[0], $assignment_key_type);
            }

            if ((string) $type->type_params[1] !== (string) $assignment_value_type) {
                $type->type_params[1] = Type::combineUnionTypes($type->type_params[1], $assignment_value_type);
            }
        }

        return $type;
    }
}
