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
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidArrayAccess;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\Issue\InvalidPropertyFetch;
use Psalm\Issue\InaccessibleClassConstant;
use Psalm\Issue\InaccessibleProperty;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\MixedArrayAccess;
use Psalm\Issue\MixedArrayOffset;
use Psalm\Issue\MixedPropertyFetch;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullArrayAccess;
use Psalm\Issue\NullPropertyFetch;
use Psalm\Issue\NullReference;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\PossiblyNullArrayAccess;
use Psalm\Issue\PossiblyNullPropertyFetch;
use Psalm\Issue\PossiblyNullReference;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedConstant;
use Psalm\Issue\UndefinedPropertyFetch;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\Generic;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TVoid;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNumericString;

class FetchChecker
{
    /**
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\PropertyFetch   $stmt
     * @param   Context                             $context
     * @return  false|null
     */
    public static function analyzePropertyFetch(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        Context $context
    ) {
        if (!is_string($stmt->name)) {
            if (ExpressionChecker::analyze($statements_checker, $stmt->name, $context) === false) {
                return false;
            }
        }

        $var_id = null;

        if (ExpressionChecker::analyze($statements_checker, $stmt->var, $context) === false) {
            return false;
        }

        $stmt_var_id = ExpressionChecker::getVarId(
            $stmt->var,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $var_id = ExpressionChecker::getVarId(
            $stmt,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $stmt_var_type = null;

        if ($var_id && $context->hasVariable($var_id)) {
            // we don't need to check anything
            $stmt->inferredType = $context->vars_in_scope[$var_id];
            return null;
        }

        if ($stmt_var_id && $context->hasVariable($stmt_var_id)) {
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
                new NullPropertyFetch(
                    'Cannot get property on null variable ' . $stmt_var_id,
                    new CodeLocation($statements_checker->getSource(), $stmt)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        if ($stmt_var_type->isEmpty()) {
            if (IssueBuffer::accepts(
                new MixedPropertyFetch(
                    'Cannot fetch property on empty var ' . $stmt_var_id,
                    new CodeLocation($statements_checker->getSource(), $stmt)
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
                    new CodeLocation($statements_checker->getSource(), $stmt)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                // fall through
            }

            return null;
        }

        if ($stmt_var_type->isNullable()) {
            if (IssueBuffer::accepts(
                new PossiblyNullPropertyFetch(
                    'Cannot get property on possibly null variable ' . $stmt_var_id . ' of type ' . $stmt_var_type,
                    new CodeLocation($statements_checker->getSource(), $stmt)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                // fall through
            }

            $stmt->inferredType = Type::getNull();
        }

        if (!is_string($stmt->name)) {
            return null;
        }

        foreach ($stmt_var_type->types as $lhs_type_part) {
            if ($lhs_type_part instanceof TNull) {
                continue;
            }

            if (!$lhs_type_part->isObjectType()) {
                if (IssueBuffer::accepts(
                    new InvalidPropertyFetch(
                        'Cannot fetch property on non-object ' . $stmt_var_id . ' of type ' . $lhs_type_part,
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }

                continue;
            }

            // stdClass and SimpleXMLElement are special cases where we cannot infer the return types
            // but we don't want to throw an error
            // Hack has a similar issue: https://github.com/facebook/hhvm/issues/5164
            if ($lhs_type_part instanceof TObject ||
                ($lhs_type_part instanceof TNamedObject &&
                    in_array(strtolower($lhs_type_part->value), ['stdclass', 'simplexmlelement'])
                )
            ) {
                $stmt->inferredType = Type::getMixed();
                continue;
            }

            $file_checker = $statements_checker->getFileChecker();

            if (!$lhs_type_part instanceof TNamedObject) {
                // @todo deal with this
                continue;
            }

            if (!ClassChecker::classExists($lhs_type_part->value, $file_checker)) {
                if (InterfaceChecker::interfaceExists($lhs_type_part->value, $file_checker)) {
                    if (IssueBuffer::accepts(
                        new NoInterfaceProperties(
                            'Interfaces cannot have properties',
                            new CodeLocation($statements_checker->getSource(), $stmt)
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
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                continue;
            }

            if ($stmt_var_id !== '$this' && MethodChecker::methodExists($lhs_type_part->value . '::__get', $file_checker)) {
                $stmt->inferredType = Type::getMixed();
                continue;
            }

            $property_id = $lhs_type_part->value . '::$' . $stmt->name;

            if (!ClassLikeChecker::propertyExists($property_id)) {
                if ($stmt_var_id === '$this') {
                    if (IssueBuffer::accepts(
                        new UndefinedThisPropertyFetch(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedPropertyFetch(
                            'Instance property ' . $property_id . ' is not defined',
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

            $declaring_property_class = ClassLikeChecker::getDeclaringClassForProperty($property_id);

            $declaring_class_storage = ClassLikeChecker::$storage[strtolower((string)$declaring_property_class)];

            $property_storage = $declaring_class_storage->properties[$stmt->name];

            $class_property_type = $property_storage->type;

            if ($class_property_type === false) {
                if (IssueBuffer::accepts(
                    new MissingPropertyType(
                        'Property ' . $lhs_type_part->value . '::$' . $stmt->name . ' does not have a declared type',
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }

                $class_property_type = Type::getMixed();
            } else {
                $class_property_type = clone $class_property_type;

                if ($lhs_type_part instanceof TGenericObject) {
                    $class_storage = ClassLikeChecker::$storage[strtolower($lhs_type_part->value)];

                    if ($class_storage->template_types) {
                        $class_template_params = [];

                        $reversed_class_template_types = array_reverse(array_keys($class_storage->template_types));

                        $provided_type_param_count = count($lhs_type_part->type_params);

                        foreach ($reversed_class_template_types as $i => $type_name) {
                            if (isset($lhs_type_part->type_params[$provided_type_param_count - 1 - $i])) {
                                $class_template_params[$type_name] =
                                    (string)$lhs_type_part->type_params[$provided_type_param_count - 1 - $i];
                            } else {
                                $class_template_params[$type_name] = 'mixed';
                            }
                        }

                        $type_tokens = Type::tokenize((string)$class_property_type);

                        foreach ($type_tokens as &$type_token) {
                            if (isset($class_template_params[$type_token])) {
                                $type_token = $class_template_params[$type_token];
                            }
                        }

                        $class_property_type = Type::parseString(implode('', $type_tokens));
                    }
                }
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
    public static function analyzeConstFetch(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ConstFetch $stmt,
        Context $context
    ) {
        $const_name = implode('\\', $stmt->name->parts);
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
                $const_type = $statements_checker->getConstType(
                    $const_name,
                    $stmt->name instanceof PhpParser\Node\Name\FullyQualified,
                    $context
                );

                if ($const_type) {
                    $stmt->inferredType = clone $const_type;
                } elseif ($context->check_consts) {
                    if (IssueBuffer::accepts(
                        new UndefinedConstant(
                            'Const ' . $const_name . ' is not defined',
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

    /**
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\ClassConstFetch $stmt
     * @param   Context                             $context
     * @return  null|false
     */
    public static function analyzeClassConstFetch(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ClassConstFetch $stmt,
        Context $context
    ) {
        if ($context->check_consts &&
            $stmt->class instanceof PhpParser\Node\Name &&
            $stmt->class->parts !== ['static'] &&
            is_string($stmt->name)
        ) {
            if ($stmt->class->parts === ['self']) {
                if (!$context->self) {
                    throw new \UnexpectedValueException('$context->self cannot be null');
                }

                $fq_class_name = (string)$context->self;
            } elseif ($stmt->class->parts[0] === 'parent') {
                $fq_class_name = $statements_checker->getParentFQCLN();

                if ($fq_class_name === null) {
                    if (IssueBuffer::accepts(
                        new ParentNotFound(
                            'Cannot check property fetch on parent as this class does not extend another',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }
            } else {
                $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $statements_checker
                );

                // edge case when evaluating single files
                if ($stmt->name === 'class' &&
                    $statements_checker->getFileChecker()->containsUnEvaluatedClassLike($fq_class_name)
                ) {
                    $stmt->inferredType = Type::getString();
                    return null;
                }

                if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                    $fq_class_name,
                    $statements_checker->getFileChecker(),
                    new CodeLocation($statements_checker->getSource(), $stmt->class),
                    $statements_checker->getSuppressedIssues()
                ) === false) {
                    return false;
                }
            }

            if ($stmt->name === 'class') {
                $stmt->inferredType = Type::getString();
                return null;
            }

            // if we're ignoring that the class doesn't exist, exit anyway
            if (!ClassLikeChecker::classOrInterfaceExists($fq_class_name, $statements_checker->getFileChecker())) {
                $stmt->inferredType = Type::getMixed();
                return null;
            }

            $const_id = $fq_class_name . '::' . $stmt->name;

            if ($fq_class_name === $context->self
                || (
                    $statements_checker->getSource()->getSource() instanceof TraitChecker &&
                    $fq_class_name === $statements_checker->getSource()->getFQCLN()
                )
            ) {
                $class_visibility = \ReflectionProperty::IS_PRIVATE;
            } elseif ($context->self &&
                ClassChecker::classExtends($context->self, $fq_class_name)
            ) {
                $class_visibility = \ReflectionProperty::IS_PROTECTED;
            } else {
                $class_visibility = \ReflectionProperty::IS_PUBLIC;
            }

            $class_constants = ClassLikeChecker::getConstantsForClass($fq_class_name, $class_visibility);

            if (!isset($class_constants[$stmt->name])) {
                $all_class_constants = [];

                if ($fq_class_name !== $context->self) {
                    $all_class_constants = ClassLikeChecker::getConstantsForClass(
                        $fq_class_name,
                        \ReflectionProperty::IS_PRIVATE
                    );
                }

                if ($all_class_constants && isset($all_class_constants[$stmt->name])) {
                    IssueBuffer::add(
                        new InaccessibleClassConstant(
                            'Constant ' . $const_id . ' is not visible in this context',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        )
                    );
                } else {
                    IssueBuffer::add(
                        new UndefinedConstant(
                            'Constant ' . $const_id . ' is not defined',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        )
                    );
                }

                return false;
            } else {
                $stmt->inferredType = $class_constants[$stmt->name];
            }

            return null;
        }

        if ($stmt->class instanceof PhpParser\Node\Expr) {
            if (ExpressionChecker::analyze($statements_checker, $stmt->class, $context) === false) {
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
    public static function analyzeStaticPropertyFetch(
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

        $fq_class_name = null;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (count($stmt->class->parts) === 1 && in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                if ($stmt->class->parts[0] === 'parent') {
                    $fq_class_name = $statements_checker->getParentFQCLN();

                    if ($fq_class_name === null) {
                        if (IssueBuffer::accepts(
                            new ParentNotFound(
                                'Cannot check property fetch on parent as this class does not extend another',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return;
                    }
                } else {
                    $fq_class_name = (string)$context->self;
                }

                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }
            } else {
                $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $statements_checker
                );

                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }

                if ($context->check_classes) {
                    if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $fq_class_name,
                        $statements_checker->getFileChecker(),
                        new CodeLocation($statements_checker->getSource(), $stmt->class),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                }
            }

            $stmt->class->inferredType = $fq_class_name ? new Type\Union([new TNamedObject($fq_class_name)]) : null;
        }

        if ($fq_class_name &&
            $context->check_classes &&
            $context->check_variables &&
            is_string($stmt->name) &&
            !ExpressionChecker::isMock($fq_class_name)
        ) {
            $var_id = ExpressionChecker::getVarId(
                $stmt,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            if ($var_id && $context->hasVariable($var_id)) {
                // we don't need to check anything
                $stmt->inferredType = $context->vars_in_scope[$var_id];
                return null;
            }

            $property_id = $fq_class_name . '::$' . $stmt->name;

            if (!ClassLikeChecker::propertyExists($property_id)) {
                if (IssueBuffer::accepts(
                    new UndefinedPropertyFetch(
                        'Static property ' . $property_id . ' is not defined',
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
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
                $fq_class_name . '::$' . $stmt->name
            );

            $property =
                ClassLikeChecker::$storage[strtolower((string)$declaring_property_class)]->properties[$stmt->name];

            if ($var_id) {
                $context->vars_in_scope[$var_id] = $property->type
                    ? clone $property->type
                    : Type::getMixed();

                $stmt->inferredType = clone $context->vars_in_scope[$var_id];
            } else {
                $stmt->inferredType = Type::getMixed();
            }
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
    public static function analyzeArrayAccess(
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
        $string_key_value = null;
        $int_key_value = null;

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

        $array_var_id = ExpressionChecker::getArrayVarId(
            $stmt->var,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $keyed_array_var_id = ExpressionChecker::getArrayVarId(
            $stmt,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        if ($stmt->dim && ExpressionChecker::analyze($statements_checker, $stmt->dim, $context) === false) {
            return false;
        }

        if ($stmt->dim) {
            if (isset($stmt->dim->inferredType)) {
                /** @var Type\Union */
                $key_type = $stmt->dim->inferredType;

                if ($stmt->dim instanceof PhpParser\Node\Scalar\String_) {
                    $string_key_value = $stmt->dim->value;
                } elseif ($stmt->dim instanceof PhpParser\Node\Scalar\LNumber) {
                    $int_key_value = $stmt->dim->value;
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
                $keyed_array_var_id && $context->hasVariable($keyed_array_var_id)
                    ? $context->vars_in_scope[$keyed_array_var_id]
                    : null;

            if (!$keyed_assignment_type || $keyed_assignment_type->isEmpty()) {
                if (!$assignment_key_type->isMixed() && !$assignment_key_type->hasInt() && $assignment_key_value) {
                    $keyed_assignment_type = new Type\Union([
                        new Type\Atomic\ObjectLike([
                            $assignment_key_value => $assignment_value_type
                        ])
                    ]);
                } else {
                    $keyed_assignment_type = Type::getEmptyArray();
                    /** @var Type\Atomic\TArray */
                    $keyed_assignment_type_array = $keyed_assignment_type->types['array'];
                    $keyed_assignment_type_array->type_params[0] = $assignment_key_type;
                    $keyed_assignment_type_array->type_params[1] = $assignment_value_type;
                }
            } else {
                if ($keyed_assignment_type->isNull()) {
                    if (IssueBuffer::accepts(
                        new NullReference(
                            'Cannot assign value on null array' . ($var_id ? ' ' . $var_id : ''),
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }

                foreach ($keyed_assignment_type->types as &$type) {
                    if ($type instanceof TInt || $type instanceof TFloat || $type instanceof TBool) {
                        if (IssueBuffer::accepts(
                            new InvalidArrayAssignment(
                                'Cannot assign value on variable ' . $var_id . ' of scalar type ' . $type->getKey(),
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        continue;
                    }

                    if ($type instanceof TString || $type instanceof Type\Atomic\TArray) {
                        $refined_type = self::refineArrayType(
                            $statements_checker,
                            $type,
                            $assignment_key_type,
                            $assignment_value_type,
                            $var_id,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        );

                        if ($refined_type === false) {
                            return false;
                        }

                        if ($refined_type === null) {
                            continue;
                        }

                        $type = $refined_type;
                    } elseif ($type instanceof Type\Atomic\ObjectLike && $assignment_key_value) {
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

        if (ExpressionChecker::analyze(
            $statements_checker,
            $stmt->var,
            $context,
            $array_assignment,
            $key_type,
            $keyed_assignment_type,
            $string_key_value
        ) === false) {
            return false;
        }

        $inferred_key_type = null;

        if (isset($stmt->var->inferredType)) {
            /** @var Type\Union */
            $var_type = $stmt->var->inferredType;

            if ($var_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullArrayAccess(
                        'Cannot access array value on null variable ' . $array_var_id,
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }

                if (isset($stmt->inferredType)) {
                    $stmt->inferredType = Type::combineUnionTypes($stmt->inferredType, Type::getNull());
                } else {
                    $stmt->inferredType = Type::getNull();
                }

                return;
            }

            foreach ($var_type->types as &$type) {
                if ($type instanceof TNull) {
                    if (IssueBuffer::accepts(
                        new PossiblyNullArrayAccess(
                            'Cannot access array value on possibly null variable ' . $array_var_id .
                                ' of type ' . $var_type,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    if (isset($stmt->inferredType)) {
                        $stmt->inferredType = Type::combineUnionTypes($stmt->inferredType, Type::getNull());
                    } else {
                        $stmt->inferredType = Type::getNull();
                    }

                    continue;
                }

                if ($type instanceof Type\Atomic\TArray || $type instanceof Type\Atomic\ObjectLike) {
                    $value_index = null;

                    if ($type instanceof Type\Atomic\TArray) {
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

                                if ($inferred_key_type) {
                                    $inferred_key_type = Type::combineUnionTypes(
                                        $inferred_key_type,
                                        $type->type_params[0]
                                    );
                                } else {
                                    $inferred_key_type = $type->type_params[0];
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
                        if ($keyed_assignment_type) {
                            if ($keyed_array_var_id) {
                                if ($context->hasVariable($keyed_array_var_id)) {
                                    $context->vars_in_scope[$keyed_array_var_id] = Type::combineUnionTypes(
                                        $keyed_assignment_type,
                                        $context->vars_in_scope[$keyed_array_var_id]
                                    );
                                } else {
                                    $context->vars_in_scope[$keyed_array_var_id] = $keyed_assignment_type;
                                }
                            }

                            $stmt->inferredType = $keyed_assignment_type;
                        }

                        if ($array_var_id === $var_id) {
                            if ($type instanceof Type\Atomic\ObjectLike ||
                                (
                                    $type instanceof TArray &&
                                    !$key_type->hasInt() &&
                                    $type->type_params[1]->isEmpty()
                                )
                            ) {
                                $properties = $keyed_assignment_type && $string_key_value
                                    ? [$string_key_value => $keyed_assignment_type]
                                    : [];

                                if ($properties) {
                                    $assignment_type = new Type\Union([
                                        new Type\Atomic\ObjectLike($properties)
                                    ]);
                                } else {
                                    $assignment_type = new Type\Union([
                                        new Type\Atomic\TArray([
                                            $key_type,
                                            $keyed_assignment_type
                                        ])
                                    ]);
                                }
                            } else {
                                if (!$keyed_assignment_type) {
                                    throw new \UnexpectedValueException('$keyed_assignment_type cannot be null');
                                }

                                $assignment_type = new Type\Union([
                                    new Type\Atomic\TArray([
                                        $key_type,
                                        $keyed_assignment_type
                                    ])
                                ]);
                            }

                            if ($var_id) {
                                if ($context->hasVariable($var_id)) {
                                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                        $context->vars_in_scope[$var_id],
                                        $assignment_type
                                    );
                                } else {
                                    $context->vars_in_scope[$var_id] = $assignment_type;
                                }
                            }
                        }

                        if ($type instanceof Type\Atomic\TArray &&
                            $type->type_params[$value_index]->isEmpty()
                        ) {
                            $empty_type = Type::getEmptyArray();

                            if (!isset($stmt->inferredType)) {
                                // if in array assignment and the referenced variable does not have
                                // an array at this level, create one
                                $stmt->inferredType = $empty_type;
                            }

                            $context_type = clone $context->vars_in_scope[$var_id];

                            $array_type = $context_type;

                            for ($i = 0; $i < (int)$nesting + 1; $i++) {
                                if (isset($array_type->types['array']) &&
                                    $array_type->types['array'] instanceof Type\Atomic\TArray
                                ) {
                                    $atomic_array = $array_type->types['array'];

                                    if ($i < $nesting) {
                                        if ($atomic_array->type_params[1]->isEmpty()) {
                                            $new_empty = clone $empty_type;
                                            /** @var Type\Atomic\TArray */
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

                            if ($var_id) {
                                $context->vars_in_scope[$var_id] = $context_type;
                            }
                        }
                    } elseif ($type instanceof Type\Atomic\TArray && $value_index !== null) {
                        $stmt->inferredType = $type->type_params[$value_index];
                    } elseif ($type instanceof Type\Atomic\ObjectLike) {
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

                        if ($string_key_value && isset($type->properties[$string_key_value])) {
                            $stmt->inferredType = clone $type->properties[$string_key_value];
                        } elseif ($int_key_value !== null && isset($type->properties[$int_key_value])) {
                            $stmt->inferredType = clone $type->properties[$int_key_value];
                        } elseif ($key_type->hasInt()) {
                            if (IssueBuffer::accepts(
                                new InvalidArrayAccess(
                                    'Cannot access value on array variable ' . $var_id . ' using int offset - ' .
                                        'expecting ' . $expected_keys_string,
                                    new CodeLocation($statements_checker->getSource(), $stmt)
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                return false;
                            }
                        }
                    }
                    continue;
                }

                if ($type instanceof TString) {
                    if ($key_type) {
                        $key_type = Type::combineUnionTypes($key_type, Type::getInt());
                    } else {
                        $key_type = Type::getInt();
                    }

                    if (!$inferred_key_type) {
                        $inferred_key_type = Type::getInt();
                    } else {
                        $inferred_key_type = Type::combineUnionTypes($inferred_key_type, Type::getInt());
                    }

                    $stmt->inferredType = Type::getString();
                    continue;
                }

                if ($type instanceof TMixed || $type instanceof TEmpty) {
                    if (IssueBuffer::accepts(
                        new MixedArrayAccess(
                            'Cannot access array value on mixed variable ' . $array_var_id,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        $stmt->inferredType = Type::getMixed();
                        break;
                    }
                    continue;
                }

                if (!$type instanceof TNamedObject ||
                    (strtolower($type->value) !== 'simplexmlelement' &&
                        ClassChecker::classExists($type->value, $statements_checker->getFileChecker()) &&
                        !ClassChecker::classImplements($type->value, 'ArrayAccess')
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidArrayAccess(
                            'Cannot access array value on non-array variable ' .
                            $array_var_id . ' of type ' . $var_type,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        $stmt->inferredType = Type::getMixed();
                        break;
                    }
                }
            }
        }

        if ($keyed_array_var_id && $context->hasVariable($keyed_array_var_id)) {
            $stmt->inferredType = $context->vars_in_scope[$keyed_array_var_id];
        }

        if (!isset($stmt->inferredType)) {
            $stmt->inferredType = Type::getMixed();
        }

        if (!$key_type) {
            $key_type = new Type\Union([
                new TInt,
                new TString
            ]);
        }

        if ($stmt->dim) {
            if (isset($stmt->dim->inferredType) && $key_type && !$key_type->isEmpty()) {
                foreach ($stmt->dim->inferredType->types as $at) {
                    if (($at instanceof TMixed || $at instanceof TEmpty) &&
                        $inferred_key_type &&
                        !$inferred_key_type->isMixed() &&
                        !$inferred_key_type->isEmpty()
                    ) {
                        if (IssueBuffer::accepts(
                            new MixedArrayOffset(
                                'Cannot access value on variable ' . $var_id . ' using mixed offset - expecting ' .
                                    $inferred_key_type,
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    } elseif (!$at->isIn($key_type, $statements_checker->getFileChecker())) {
                        if (IssueBuffer::accepts(
                            new InvalidArrayAccess(
                                'Cannot access value on variable ' . $var_id . ' using ' . $at . ' offset - ' .
                                    'expecting ' . $key_type,
                                new CodeLocation($statements_checker->getSource(), $stmt)
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
     * @param   CodeLocation           $code_location
     * @return  Type\Atomic|null|false
     */
    protected static function refineArrayType(
        StatementsChecker $statements_checker,
        Type\Atomic $type,
        Type\Union $assignment_key_type,
        Type\Union $assignment_value_type,
        $var_id,
        CodeLocation $code_location
    ) {
        if ($type instanceof TNull) {
            if (IssueBuffer::accepts(
                new PossiblyNullReference(
                    'Cannot assign value on possibly null array' . ($var_id ? ' ' . $var_id : ''),
                    $code_location
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return $type;
        }

        if ($type instanceof TString && $assignment_value_type->hasString() && !$assignment_key_type->hasString()) {
            return null;
        }

        if (!$type instanceof TArray &&
            (!$type instanceof TNamedObject || !ClassChecker::classImplements($type->value, 'ArrayAccess'))
        ) {
            if (IssueBuffer::accepts(
                new InvalidArrayAssignment(
                    'Cannot assign value on variable' . ($var_id ? ' ' . $var_id  : '') . ' of type ' . $type . ' that does not ' .
                        'implement ArrayAccess',
                    $code_location
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return $type;
        }

        if ($type instanceof Type\Atomic\Generic && $type instanceof TArray) {
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
