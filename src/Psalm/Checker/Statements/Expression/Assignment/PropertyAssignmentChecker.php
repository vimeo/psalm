<?php
namespace Psalm\Checker\Statements\Expression\Assignment;

use PhpParser;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\PropertyProperty;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DeprecatedProperty;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InvalidPropertyAssignment;
use Psalm\Issue\InvalidPropertyAssignmentValue;
use Psalm\Issue\LoopInvalidation;
use Psalm\Issue\MixedAssignment;
use Psalm\Issue\MixedPropertyAssignment;
use Psalm\Issue\MixedTypeCoercion;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullPropertyAssignment;
use Psalm\Issue\PossiblyFalsePropertyAssignmentValue;
use Psalm\Issue\PossiblyInvalidPropertyAssignment;
use Psalm\Issue\PossiblyInvalidPropertyAssignmentValue;
use Psalm\Issue\PossiblyNullPropertyAssignment;
use Psalm\Issue\PossiblyNullPropertyAssignmentValue;
use Psalm\Issue\TypeCoercion;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedPropertyAssignment;
use Psalm\Issue\UndefinedThisPropertyAssignment;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;

class PropertyAssignmentChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PropertyFetch|PropertyProperty  $stmt
     * @param   string                          $prop_name
     * @param   PhpParser\Node\Expr|null        $assignment_value
     * @param   Type\Union                      $assignment_value_type
     * @param   Context                         $context
     * @param   bool                            $direct_assignment whether the variable is assigned explicitly
     *
     * @return  false|null
     */
    public static function analyzeInstance(
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
        $codebase = $project_checker->codebase;

        $property_exists = false;

        $property_ids = [];

        if ($stmt instanceof PropertyProperty) {
            if (!$context->self || !$stmt->default) {
                return null;
            }

            $property_id = $context->self . '::$' . $prop_name;
            $property_ids[] = $property_id;

            if (!$codebase->properties->propertyExists($property_id)) {
                return null;
            }

            $property_exists = true;

            $declaring_property_class = $codebase->properties->getDeclaringClassForProperty($property_id);

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
                $codebase->analyzer->incrementMixedCount($statements_checker->getFilePath());

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

            $codebase->analyzer->incrementNonMixedCount($statements_checker->getFilePath());

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

            foreach ($lhs_type->getTypes() as $lhs_type_part) {
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
                    if ($var_id) {
                        $context->vars_in_scope[$var_id] = Type::getMixed();
                    }

                    return null;
                }

                if (!$codebase->classExists($lhs_type_part->value)) {
                    if ($codebase->interfaceExists($lhs_type_part->value)) {
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
                            new CodeLocation($statements_checker->getSource(), $stmt),
                            $lhs_type_part->value
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return null;
                }

                $property_id = $lhs_type_part->value . '::$' . $prop_name;
                $property_ids[] = $property_id;

                $statements_checker_source = $statements_checker->getSource();

                if ($codebase->methodExists($lhs_type_part->value . '::__set')
                    && (!$statements_checker_source instanceof FunctionLikeChecker
                        || $statements_checker_source->getMethodId() !== $lhs_type_part->value . '::__set')
                    && (!$context->self || !$codebase->classExtends($context->self, $lhs_type_part->value))
                    && (!$codebase->properties->propertyExists($property_id)
                        || ($lhs_var_id !== '$this'
                            && $lhs_type_part->value !== $context->self
                            && ClassLikeChecker::checkPropertyVisibility(
                                $property_id,
                                $context->self,
                                $statements_checker_source,
                                new CodeLocation($statements_checker->getSource(), $stmt),
                                $statements_checker->getSuppressedIssues(),
                                false
                            ) !== true)
                    )
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

                if ($stmt->var instanceof PhpParser\Node\Expr\Variable
                    && $stmt->var->name === 'this'
                    && $context->self
                ) {
                    $self_property_id = $context->self . '::$' . $prop_name;

                    if ($self_property_id !== $property_id
                        && $codebase->properties->propertyExists($self_property_id)
                    ) {
                        $property_id = $self_property_id;
                    }
                }

                if (!$codebase->properties->propertyExists($property_id, $context->calling_method_id)) {
                    if ($stmt->var instanceof PhpParser\Node\Expr\Variable && $stmt->var->name === 'this') {
                        // if this is a proper error, we'll see it on the first pass
                        if ($context->collect_mutations) {
                            continue;
                        }

                        if (IssueBuffer::accepts(
                            new UndefinedThisPropertyAssignment(
                                'Instance property ' . $property_id . ' is not defined',
                                new CodeLocation($statements_checker->getSource(), $stmt),
                                $property_id
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new UndefinedPropertyAssignment(
                                'Instance property ' . $property_id . ' is not defined',
                                new CodeLocation($statements_checker->getSource(), $stmt),
                                $property_id
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }

                    continue;
                }

                $property_exists = true;

                if (!$context->collect_mutations) {
                    if (ClassLikeChecker::checkPropertyVisibility(
                        $property_id,
                        $context->self,
                        $statements_checker->getSource(),
                        new CodeLocation($statements_checker->getSource(), $stmt),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                } else {
                    if (ClassLikeChecker::checkPropertyVisibility(
                        $property_id,
                        $context->self,
                        $statements_checker->getSource(),
                        new CodeLocation($statements_checker->getSource(), $stmt),
                        $statements_checker->getSuppressedIssues(),
                        false
                    ) !== true) {
                        continue;
                    }
                }

                $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
                    $property_id
                );

                $class_storage = $project_checker->classlike_storage_provider->get((string)$declaring_property_class);

                $property_storage = $class_storage->properties[$prop_name];

                if ($property_storage->deprecated) {
                    if (IssueBuffer::accepts(
                        new DeprecatedProperty(
                            $property_id . ' is marked deprecated',
                            new CodeLocation($statements_checker->getSource(), $stmt),
                            $property_id
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
                        $lhs_type_part->value,
                        $lhs_type_part->value
                    );

                    if (!$class_property_type->isMixed() && $assignment_value_type->isMixed()) {
                        if (IssueBuffer::accepts(
                            new MixedAssignment(
                                'Cannot assign ' . $var_id . ' to a mixed type',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
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

            $type_match_found = TypeChecker::isContainedBy(
                $project_checker->codebase,
                $assignment_value_type,
                $class_property_type,
                true,
                true,
                $has_scalar_match,
                $type_coerced,
                $type_coerced_from_mixed,
                $to_string_cast
            );

            if ($type_coerced) {
                if ($type_coerced_from_mixed) {
                    if (IssueBuffer::accepts(
                        new MixedTypeCoercion(
                            $var_id . ' expects \'' . $class_property_type . '\', '
                                . ' parent type `' . $assignment_value_type . '` provided',
                            new CodeLocation(
                                $statements_checker->getSource(),
                                $assignment_value ?: $stmt,
                                $context->include_location
                            )
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // keep soldiering on
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new TypeCoercion(
                            $var_id . ' expects \'' . $class_property_type . '\', '
                                . ' parent type \'' . $assignment_value_type . '\' provided',
                            new CodeLocation(
                                $statements_checker->getSource(),
                                $assignment_value ?: $stmt,
                                $context->include_location
                            )
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // keep soldiering on
                    }
                }
            }

            if ($to_string_cast) {
                if (IssueBuffer::accepts(
                    new ImplicitToStringCast(
                        $var_id . ' expects \'' . $class_property_type . '\', '
                            . '\'' . $assignment_value_type . '\' provided with a __toString method',
                        new CodeLocation(
                            $statements_checker->getSource(),
                            $assignment_value ?: $stmt,
                            $context->include_location
                        )
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if (!$type_match_found && !$type_coerced) {
                if (TypeChecker::canBeContainedBy(
                    $project_checker->codebase,
                    $assignment_value_type,
                    $class_property_type,
                    true,
                    true
                )) {
                    $has_valid_assignment_value_type = true;
                }

                $invalid_assignment_value_types[] = $class_property_type->getId();
            } else {
                $has_valid_assignment_value_type = true;
            }

            if ($type_match_found) {
                if (!$assignment_value_type->ignore_nullable_issues
                    && $assignment_value_type->isNullable()
                    && !$class_property_type->isNullable()
                ) {
                    if (IssueBuffer::accepts(
                        new PossiblyNullPropertyAssignmentValue(
                            $var_id . ' with non-nullable declared type \'' . $class_property_type .
                                '\' cannot be assigned nullable type \'' . $assignment_value_type . '\'',
                            new CodeLocation(
                                $statements_checker->getSource(),
                                $assignment_value ?: $stmt,
                                $context->include_location
                            ),
                            $property_ids[0]
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                if (!$assignment_value_type->ignore_falsable_issues
                    && $assignment_value_type->isFalsable()
                    && !$class_property_type->hasBool()
                ) {
                    if (IssueBuffer::accepts(
                        new PossiblyFalsePropertyAssignmentValue(
                            $var_id . ' with non-falsable declared type \'' . $class_property_type .
                                '\' cannot be assigned possibly false type \'' . $assignment_value_type . '\'',
                            new CodeLocation(
                                $statements_checker->getSource(),
                                $assignment_value ?: $stmt,
                                $context->include_location
                            ),
                            $property_ids[0]
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
            }
        }

        if ($invalid_assignment_value_types) {
            $invalid_class_property_type = $invalid_assignment_value_types[0];

            if (!$has_valid_assignment_value_type) {
                if (IssueBuffer::accepts(
                    new InvalidPropertyAssignmentValue(
                        $var_id . ' with declared type \'' . $invalid_class_property_type .
                            '\' cannot be assigned type \'' . $assignment_value_type->getId() . '\'',
                        new CodeLocation(
                            $statements_checker->getSource(),
                            $assignment_value ?: $stmt,
                            $context->include_location
                        ),
                        $property_ids[0]
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            } else {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidPropertyAssignmentValue(
                        $var_id . ' with declared type \'' . $invalid_class_property_type .
                            '\' cannot be assigned possibly different type \'' .
                            $assignment_value_type->getId() . '\'',
                        new CodeLocation(
                            $statements_checker->getSource(),
                            $assignment_value ?: $stmt,
                            $context->include_location
                        ),
                        $property_ids[0]
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
    public static function analyzeStatic(
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
        $codebase = $project_checker->codebase;

        $prop_name = $stmt->name;

        if (!$prop_name instanceof PhpParser\Node\Identifier) {
            return;
        }

        $property_id = $fq_class_name . '::$' . $prop_name;

        if (!$codebase->properties->propertyExists($property_id, $context->calling_method_id)) {
            if (IssueBuffer::accepts(
                new UndefinedPropertyAssignment(
                    'Static property ' . $property_id . ' is not defined',
                    new CodeLocation($statements_checker->getSource(), $stmt),
                    $property_id
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

        $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
            $fq_class_name . '::$' . $prop_name->name
        );

        $class_storage = $project_checker->classlike_storage_provider->get((string)$declaring_property_class);

        $property_storage = $class_storage->properties[$prop_name->name];

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
            $fq_class_name,
            $fq_class_name
        );

        $type_match_found = TypeChecker::isContainedBy(
            $project_checker->codebase,
            $assignment_value_type,
            $class_property_type,
            true,
            true,
            $has_scalar_match,
            $type_coerced,
            $type_coerced_from_mixed,
            $to_string_cast
        );

        if ($type_coerced) {
            if ($type_coerced_from_mixed) {
                if (IssueBuffer::accepts(
                    new MixedTypeCoercion(
                        $var_id . ' expects \'' . $class_property_type . '\', '
                            . ' parent type `' . $assignment_value_type . '` provided',
                        new CodeLocation(
                            $statements_checker->getSource(),
                            $assignment_value ?: $stmt,
                            $context->include_location
                        )
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // keep soldiering on
                }
            } else {
                if (IssueBuffer::accepts(
                    new TypeCoercion(
                        $var_id . ' expects \'' . $class_property_type . '\', '
                            . ' parent type \'' . $assignment_value_type . '\' provided',
                        new CodeLocation(
                            $statements_checker->getSource(),
                            $assignment_value ?: $stmt,
                            $context->include_location
                        )
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // keep soldiering on
                }
            }
        }

        if ($to_string_cast) {
            if (IssueBuffer::accepts(
                new ImplicitToStringCast(
                    $var_id . ' expects \'' . $class_property_type . '\', '
                        . '\'' . $assignment_value_type . '\' provided with a __toString method',
                    new CodeLocation(
                        $statements_checker->getSource(),
                        $assignment_value ?: $stmt,
                        $context->include_location
                    )
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if (!$type_match_found && !$type_coerced) {
            if (TypeChecker::canBeContainedBy($codebase, $assignment_value_type, $class_property_type)) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidPropertyAssignmentValue(
                        $var_id . ' with declared type \'' . $class_property_type . '\' cannot be assigned type \'' .
                            $assignment_value_type . '\'',
                        new CodeLocation(
                            $statements_checker->getSource(),
                            $assignment_value ?: $stmt
                        ),
                        $property_id
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidPropertyAssignmentValue(
                        $var_id . ' with declared type \'' . $class_property_type . '\' cannot be assigned type \'' .
                            $assignment_value_type . '\'',
                        new CodeLocation(
                            $statements_checker->getSource(),
                            $assignment_value ?: $stmt
                        ),
                        $property_id
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        if ($var_id) {
            $context->vars_in_scope[$var_id] = $assignment_value_type;
        }

        return null;
    }
}
