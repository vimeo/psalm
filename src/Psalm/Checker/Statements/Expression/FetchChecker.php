<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\InterfaceChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TraitChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DeprecatedProperty;
use Psalm\Issue\EmptyArrayAccess;
use Psalm\Issue\InaccessibleClassConstant;
use Psalm\Issue\InvalidArrayAccess;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\Issue\InvalidArrayOffset;
use Psalm\Issue\InvalidPropertyFetch;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\MixedArrayAccess;
use Psalm\Issue\MixedArrayAssignment;
use Psalm\Issue\MixedArrayOffset;
use Psalm\Issue\MixedPropertyFetch;
use Psalm\Issue\MixedStringOffsetAssignment;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullArrayAccess;
use Psalm\Issue\NullArrayOffset;
use Psalm\Issue\NullPropertyFetch;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\PossiblyInvalidArrayAccess;
use Psalm\Issue\PossiblyInvalidArrayAssignment;
use Psalm\Issue\PossiblyInvalidArrayOffset;
use Psalm\Issue\PossiblyInvalidPropertyFetch;
use Psalm\Issue\PossiblyNullArrayAccess;
use Psalm\Issue\PossiblyNullArrayAssignment;
use Psalm\Issue\PossiblyNullArrayOffset;
use Psalm\Issue\PossiblyNullPropertyFetch;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedConstant;
use Psalm\Issue\UndefinedPropertyFetch;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;

class FetchChecker
{
    /**
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\PropertyFetch   $stmt
     * @param   Context                             $context
     *
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

            $stmt->inferredType = Type::getMixed();

            return null;
        }

        if ($stmt_var_type->isNullable() && !$stmt_var_type->ignore_nullable_issues) {
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

        $invalid_fetch_types = [];
        $has_valid_fetch_type = false;

        foreach ($stmt_var_type->types as $lhs_type_part) {
            if ($lhs_type_part instanceof TNull) {
                continue;
            }

            if (!$lhs_type_part instanceof TNamedObject && !$lhs_type_part instanceof TObject) {
                $invalid_fetch_types[] = (string)$lhs_type_part;

                continue;
            }

            $has_valid_fetch_type = true;

            // stdClass and SimpleXMLElement are special cases where we cannot infer the return types
            // but we don't want to throw an error
            // Hack has a similar issue: https://github.com/facebook/hhvm/issues/5164
            if ($lhs_type_part instanceof TObject ||
                (
                    $lhs_type_part instanceof TNamedObject &&
                    in_array(strtolower($lhs_type_part->value), ['stdclass', 'simplexmlelement'], true)
                )
            ) {
                $stmt->inferredType = Type::getMixed();
                continue;
            }

            if (ExpressionChecker::isMock($lhs_type_part->value)) {
                $stmt->inferredType = Type::getMixed();
                continue;
            }

            $project_checker = $statements_checker->getFileChecker()->project_checker;

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

            $property_id = $lhs_type_part->value . '::$' . $stmt->name;

            if (MethodChecker::methodExists($project_checker, $lhs_type_part->value . '::__get')) {
                if ($stmt_var_id !== '$this' || !ClassLikeChecker::propertyExists($project_checker, $property_id)) {
                    $class_storage = $project_checker->classlike_storage_provider->get((string)$lhs_type_part);

                    if (isset($class_storage->pseudo_property_get_types['$' . $stmt->name])) {
                        $stmt->inferredType = clone $class_storage->pseudo_property_get_types['$' . $stmt->name];
                        continue;
                    }

                    $stmt->inferredType = Type::getMixed();
                    /*
                     * If we have an explicit list of all allowed magic properties on the class, and we're
                     * not in that list, fall through
                     */
                    if (!$class_storage->sealed_properties) {
                        continue;
                    }
                }
            }

            if (!ClassLikeChecker::propertyExists($project_checker, $property_id)) {
                if ($stmt_var_id === '$this') {
                    if ($context->collect_mutations) {
                        return;
                    }

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

            $declaring_property_class = ClassLikeChecker::getDeclaringClassForProperty($project_checker, $property_id);

            $declaring_class_storage = $project_checker->classlike_storage_provider->get(
                (string)$declaring_property_class
            );

            $property_storage = $declaring_class_storage->properties[$stmt->name];

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
                $class_property_type = ExpressionChecker::fleshOutType(
                    $project_checker,
                    clone $class_property_type,
                    $declaring_property_class
                );

                if ($lhs_type_part instanceof TGenericObject) {
                    $class_storage = $project_checker->classlike_storage_provider->get($lhs_type_part->value);

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

        if ($invalid_fetch_types) {
            $lhs_type_part = $invalid_fetch_types[0];

            if ($has_valid_fetch_type) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidPropertyFetch(
                        'Cannot fetch property on possible non-object ' . $stmt_var_id . ' of type ' . $lhs_type_part,
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidPropertyFetch(
                        'Cannot fetch property on non-object ' . $stmt_var_id . ' of type ' . $lhs_type_part,
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
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
     *
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
                $stmt->inferredType = Type::getTrue();
                break;

            default:
                $const_type = $statements_checker->getConstType(
                    $statements_checker,
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
     *
     * @return  null|false
     */
    public static function analyzeClassConstFetch(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ClassConstFetch $stmt,
        Context $context
    ) {
        if ($context->check_consts &&
            $stmt->class instanceof PhpParser\Node\Name &&
            strtolower($stmt->class->parts[0]) !== 'static' &&
            is_string($stmt->name)
        ) {
            if (strtolower($stmt->class->parts[0]) === 'self') {
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
                    $statements_checker->getAliases()
                );

                // edge case when evaluating single files
                if ($stmt->name === 'class' &&
                    $statements_checker->getFileChecker()->containsUnEvaluatedClassLike($fq_class_name)
                ) {
                    $stmt->inferredType = Type::getString();

                    return null;
                }

                if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                    $statements_checker->getFileChecker()->project_checker,
                    $fq_class_name,
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

            $project_checker = $statements_checker->getFileChecker()->project_checker;

            // if we're ignoring that the class doesn't exist, exit anyway
            if (!ClassLikeChecker::classOrInterfaceExists($project_checker, $fq_class_name)) {
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
                ClassChecker::classExtends($project_checker, $context->self, $fq_class_name)
            ) {
                $class_visibility = \ReflectionProperty::IS_PROTECTED;
            } else {
                $class_visibility = \ReflectionProperty::IS_PUBLIC;
            }

            $class_constants = ClassLikeChecker::getConstantsForClass(
                $project_checker,
                $fq_class_name,
                $class_visibility
            );

            if (!isset($class_constants[$stmt->name])) {
                $all_class_constants = [];

                if ($fq_class_name !== $context->self) {
                    $all_class_constants = ClassLikeChecker::getConstantsForClass(
                        $project_checker,
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
            }
            $stmt->inferredType = $class_constants[$stmt->name];

            return null;
        }

        $stmt->inferredType = Type::getMixed();

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
     *
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

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (count($stmt->class->parts) === 1
                && in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
            ) {
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
                    $statements_checker->getAliases()
                );

                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }

                if ($context->check_classes) {
                    if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $project_checker,
                        $fq_class_name,
                        new CodeLocation($statements_checker->getSource(), $stmt->class),
                        $statements_checker->getSuppressedIssues()
                    ) !== true) {
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

            if (!ClassLikeChecker::propertyExists($project_checker, $property_id)) {
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
                $project_checker,
                $fq_class_name . '::$' . $stmt->name
            );

            $class_storage = $project_checker->classlike_storage_provider->get((string)$declaring_property_class);
            $property = $class_storage->properties[$stmt->name];

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
     * @param  Type\Union $array_type
     * @param  Type\Union $offset_type
     * @param  ?string    $array_var_id
     * @param  bool       $in_assignment
     *
     * @return Type\Union
     */
    public static function getArrayAccessTypeGivenOffset(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Type\Union $array_type,
        Type\Union $offset_type,
        $in_assignment,
        $array_var_id,
        Type\Union $replacement_type = null
    ) {
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $has_array_access = false;
        $non_array_types = [];

        $has_valid_offset = false;
        $invalid_offset_types = [];

        $key_value = null;

        if ($stmt->dim instanceof PhpParser\Node\Scalar\String_
            || $stmt->dim instanceof PhpParser\Node\Scalar\LNumber
        ) {
            $key_value = $stmt->dim->value;
        }

        $array_access_type = null;

        if ($offset_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullArrayOffset(
                    'Cannot access value on variable ' . $array_var_id . ' using null offset',
                    new CodeLocation($statements_checker->getSource(), $stmt)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                // fall through
            }

            return Type::getMixed();
        }

        if ($offset_type->isNullable() && !$offset_type->ignore_nullable_issues) {
            if (IssueBuffer::accepts(
                new PossiblyNullArrayOffset(
                    'Cannot access value on variable ' . $array_var_id
                        . ' using possibly null offset ' . $offset_type,
                    new CodeLocation($statements_checker->getSource(), $stmt->var)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        foreach ($array_type->types as &$type) {
            if ($type instanceof TNull) {
                if ($in_assignment) {
                    if ($replacement_type) {
                        if ($array_access_type) {
                            $array_access_type = Type::combineUnionTypes($array_access_type, $replacement_type);
                        } else {
                            $array_access_type = clone $replacement_type;
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new PossiblyNullArrayAssignment(
                                'Cannot access array value on possibly null variable ' . $array_var_id .
                                    ' of type ' . $array_type,
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            // fall through
                        }

                        $array_access_type = new Type\Union([new TEmpty]);
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new PossiblyNullArrayAccess(
                            'Cannot access array value on possibly null variable ' . $array_var_id .
                                ' of type ' . $array_type,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    if ($array_access_type) {
                        $array_access_type = Type::combineUnionTypes($array_access_type, Type::getNull());
                    } else {
                        $array_access_type = Type::getNull();
                    }
                }

                continue;
            }

            if ($type instanceof TArray || $type instanceof ObjectLike) {
                $has_array_access = true;

                if ($in_assignment
                    && $type instanceof TArray
                    && $type->type_params[0]->isEmpty()
                    && $key_value !== null
                ) {
                    // ok, type becomes an ObjectLike

                    $type = new ObjectLike([$key_value => new Type\Union([new TEmpty])]);
                }

                if ($type instanceof TArray) {
                    // if we're assigning to an empty array with a key offset, refashion that array
                    if ($in_assignment) {
                        if ($type->type_params[0]->isEmpty()) {
                            $type->type_params[0] = $offset_type;
                        }
                    } elseif (!$type->type_params[0]->isEmpty()) {
                        if (!TypeChecker::isContainedBy(
                            $project_checker,
                            $offset_type,
                            $type->type_params[0],
                            true
                        )) {
                            $invalid_offset_types[] = (string)$type->type_params[0];
                        } else {
                            $has_valid_offset = true;
                        }
                    }

                    if ($in_assignment && $replacement_type) {
                        $type->type_params[1] = Type::combineUnionTypes(
                            $type->type_params[1],
                            $replacement_type
                        );
                    }

                    if (!$array_access_type) {
                        $array_access_type = $type->type_params[1];
                    } else {
                        $array_access_type = Type::combineUnionTypes(
                            $array_access_type,
                            $type->type_params[1]
                        );
                    }

                    if ($array_access_type->isEmpty() && !$in_assignment) {
                        if (IssueBuffer::accepts(
                            new EmptyArrayAccess(
                                'Cannot access value on empty array variable ' . $array_var_id,
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return Type::getMixed();
                        }

                        if (!IssueBuffer::isRecording()) {
                            $array_access_type = Type::getMixed();
                        }
                    }
                } elseif ($type instanceof ObjectLike) {
                    if ($key_value !== null) {
                        if (isset($type->properties[$key_value]) || $replacement_type) {
                            $has_valid_offset = true;

                            if ($replacement_type) {
                                if (isset($type->properties[$key_value])) {
                                    $type->properties[$key_value] = Type::combineUnionTypes(
                                        $type->properties[$key_value],
                                        $replacement_type
                                    );
                                } else {
                                    $type->properties[$key_value] = $replacement_type;
                                }
                            }

                            if (!$array_access_type) {
                                $array_access_type = clone $type->properties[$key_value];
                            } else {
                                $array_access_type = Type::combineUnionTypes(
                                    $array_access_type,
                                    $type->properties[$key_value]
                                );
                            }
                        } elseif ($in_assignment) {
                            $type->properties[$key_value] = new Type\Union([new TEmpty]);

                            if (!$array_access_type) {
                                $array_access_type = clone $type->properties[$key_value];
                            } else {
                                $array_access_type = Type::combineUnionTypes(
                                    $array_access_type,
                                    $type->properties[$key_value]
                                );
                            }
                        } else {
                            $object_like_keys = array_keys($type->properties);

                            if (count($object_like_keys) === 1) {
                                $expected_keys_string = '\'' . $object_like_keys[0] . '\'';
                            } else {
                                $last_key = array_pop($object_like_keys);
                                $expected_keys_string = '\'' . implode('\', \'', $object_like_keys) .
                                    '\' or \'' . $last_key . '\'';
                            }

                            $invalid_offset_types[] = $expected_keys_string;

                            $array_access_type = Type::getMixed();
                        }
                    } elseif (TypeChecker::isContainedBy(
                        $project_checker,
                        $offset_type,
                        $type->getGenericKeyType(),
                        true
                    )) {
                        if ($replacement_type) {
                            $generic_params = Type::combineUnionTypes(
                                $type->getGenericValueType(),
                                $replacement_type
                            );

                            $type = new TArray([
                                $type->getGenericKeyType(),
                                $generic_params,
                            ]);

                            if (!$array_access_type) {
                                $array_access_type = clone $generic_params;
                            } else {
                                $array_access_type = Type::combineUnionTypes(
                                    $array_access_type,
                                    $generic_params
                                );
                            }
                        } else {
                            if (!$array_access_type) {
                                $array_access_type = $type->getGenericValueType();
                            } else {
                                $array_access_type = Type::combineUnionTypes(
                                    $array_access_type,
                                    $type->getGenericValueType()
                                );
                            }
                        }

                        $has_valid_offset = true;
                    } else {
                        $invalid_offset_types[] = (string)$type->getGenericKeyType();

                        $array_access_type = Type::getMixed();
                    }
                }
                continue;
            }

            if ($type instanceof TString) {
                if ($in_assignment && $replacement_type && $replacement_type->isMixed()) {
                    if (IssueBuffer::accepts(
                        new MixedStringOffsetAssignment(
                            'Right-hand-side of string offset assignment cannot be mixed',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if (!TypeChecker::isContainedBy(
                    $project_checker,
                    $offset_type,
                    Type::getInt(),
                    true
                )) {
                    $invalid_offset_types[] = 'int';
                } else {
                    $has_valid_offset = true;
                }

                if (!$array_access_type) {
                    $array_access_type = Type::getString();
                } else {
                    $array_access_type = Type::combineUnionTypes(
                        $array_access_type,
                        Type::getString()
                    );
                }

                continue;
            }

            if ($type instanceof TMixed || $type instanceof TEmpty) {
                if ($in_assignment) {
                    if (IssueBuffer::accepts(
                        new MixedArrayAssignment(
                            'Cannot access array value on mixed variable ' . $array_var_id,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new MixedArrayAccess(
                            'Cannot access array value on mixed variable ' . $array_var_id,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $array_access_type = Type::getMixed();
                break;
            }

            if ($type instanceof TNamedObject) {
                if (strtolower($type->value) !== 'simplexmlelement'
                    && ClassChecker::classExists($project_checker, $type->value)
                    && !ClassChecker::classImplements($project_checker, $type->value, 'ArrayAccess')
                ) {
                    $non_array_types[] = (string)$type;
                } else {
                    $array_access_type = Type::getMixed();
                }
            } else {
                $non_array_types[] = (string)$type;
            }
        }

        if ($non_array_types) {
            if ($has_array_access) {
                if ($in_assignment) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidArrayAssignment(
                            'Cannot access array value on non-array variable ' .
                            $array_var_id . ' of type ' . $non_array_types[0],
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )
                    ) {
                        // do nothing
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidArrayAccess(
                            'Cannot access array value on non-array variable ' .
                            $array_var_id . ' of type ' . $non_array_types[0],
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )
                    ) {
                        // do nothing
                    }
                }
            } else {
                if ($in_assignment) {
                    if (IssueBuffer::accepts(
                        new InvalidArrayAssignment(
                            'Cannot access array value on non-array variable ' .
                            $array_var_id . ' of type ' . $non_array_types[0],
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidArrayAccess(
                            'Cannot access array value on non-array variable ' .
                            $array_var_id . ' of type ' . $non_array_types[0],
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $array_access_type = Type::getMixed();
            }
        }

        if ($offset_type->isMixed()) {
            if (IssueBuffer::accepts(
                new MixedArrayOffset(
                    'Cannot access value on variable ' . $array_var_id . ' using mixed offset',
                    new CodeLocation($statements_checker->getSource(), $stmt)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                // fall through
            }
        } elseif ($invalid_offset_types) {
            $invalid_offset_type = $invalid_offset_types[0];

            if ($has_valid_offset) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidArrayOffset(
                        'Cannot access value on variable ' . $array_var_id . ' using ' . $offset_type
                            . ' offset, expecting ' . $invalid_offset_type,
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidArrayOffset(
                        'Cannot access value on variable ' . $array_var_id . ' using ' . $offset_type
                            . ' offset, expecting ' . $invalid_offset_type,
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }

        if ($array_access_type === null) {
            throw new \InvalidArgumentException('This is a bad place');
        }

        return $array_access_type;
    }

    /**
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\ArrayDimFetch   $stmt
     * @param   Context                             $context
     *
     * @return  false|null
     */
    public static function analyzeArrayAccess(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context
    ) {
        $var_type = null;
        $used_key_type = null;

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
                $used_key_type = $stmt->dim->inferredType;
            } else {
                $used_key_type = Type::getMixed();
            }
        } else {
            $used_key_type = Type::getInt();
        }

        if (ExpressionChecker::analyze(
            $statements_checker,
            $stmt->var,
            $context
        ) === false) {
            return false;
        }

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

            $stmt->inferredType = self::getArrayAccessTypeGivenOffset(
                $statements_checker,
                $stmt,
                $stmt->var->inferredType,
                $used_key_type,
                false,
                $array_var_id
            );
        }

        if ($keyed_array_var_id && $context->hasVariable($keyed_array_var_id)) {
            $stmt->inferredType = $context->vars_in_scope[$keyed_array_var_id];
        }

        if (!isset($stmt->inferredType)) {
            $stmt->inferredType = Type::getMixed();
        }

        return null;
    }
}
