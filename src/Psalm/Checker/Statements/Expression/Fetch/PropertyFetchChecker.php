<?php
namespace Psalm\Checker\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\InterfaceChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DeprecatedProperty;
use Psalm\Issue\InvalidPropertyFetch;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\MixedPropertyFetch;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullPropertyFetch;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\PossiblyInvalidPropertyFetch;
use Psalm\Issue\PossiblyNullPropertyFetch;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedPropertyFetch;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;

class PropertyFetchChecker
{
    /**
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\PropertyFetch   $stmt
     * @param   Context                             $context
     *
     * @return  false|null
     */
    public static function analyzeInstance(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        Context $context
    ) {
        if (!is_string($stmt->name)) {
            if (ExpressionChecker::analyze($statements_checker, $stmt->name, $context) === false) {
                return false;
            }
        }

        if (ExpressionChecker::analyze($statements_checker, $stmt->var, $context) === false) {
            return false;
        }

        $project_checker = $statements_checker->getFileChecker()->project_checker;

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

        if ($var_id && $context->hasVariable($var_id, $statements_checker)) {
            // we don't need to check anything
            $stmt->inferredType = $context->vars_in_scope[$var_id];

            if ($context->collect_references
                && isset($stmt->var->inferredType)
                && $stmt->var->inferredType->hasObjectType()
                && is_string($stmt->name)
            ) {
                // log the appearance
                foreach ($stmt->var->inferredType->getTypes() as $lhs_type_part) {
                    if ($lhs_type_part instanceof TNamedObject) {
                        if (!ClassChecker::classExists($project_checker, $lhs_type_part->value)) {
                            continue;
                        }

                        $property_id = $lhs_type_part->value . '::$' . $stmt->name;

                        ClassLikeChecker::propertyExists(
                            $project_checker,
                            $property_id,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        );
                    }
                }
            }

            return null;
        }

        if ($stmt_var_id && $context->hasVariable($stmt_var_id, $statements_checker)) {
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

        if ($stmt_var_type->isNullable() && !$stmt_var_type->ignore_nullable_issues && !$context->inside_isset) {
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

        foreach ($stmt_var_type->getTypes() as $lhs_type_part) {
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

            if (!ClassLikeChecker::propertyExists(
                $project_checker,
                $property_id,
                $context->collect_references ? new CodeLocation($statements_checker->getSource(), $stmt) : null
            )
            ) {
                if ($context->inside_isset) {
                    return;
                }

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
                    $declaring_property_class,
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
     * @param   StatementsChecker                       $statements_checker
     * @param   PhpParser\Node\Expr\StaticPropertyFetch $stmt
     * @param   Context                                 $context
     *
     * @return  null|false
     */
    public static function analyzeStatic(
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
                        $statements_checker,
                        $fq_class_name,
                        new CodeLocation($statements_checker->getSource(), $stmt->class),
                        $statements_checker->getSuppressedIssues(),
                        false
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

            $property_id = $fq_class_name . '::$' . $stmt->name;

            if ($var_id && $context->hasVariable($var_id, $statements_checker)) {
                // we don't need to check anything
                $stmt->inferredType = $context->vars_in_scope[$var_id];

                if ($context->collect_references) {
                    // log the appearance
                    ClassLikeChecker::propertyExists(
                        $project_checker,
                        $property_id,
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    );
                }

                return null;
            }

            if (!ClassLikeChecker::propertyExists(
                $project_checker,
                $property_id,
                $context->collect_references ? new CodeLocation($statements_checker->getSource(), $stmt) : null
            )
            ) {
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
}
