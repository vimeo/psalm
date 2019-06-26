<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Assignment;

use PhpParser;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\PropertyProperty;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DeprecatedProperty;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InternalProperty;
use Psalm\Issue\InvalidPropertyAssignment;
use Psalm\Issue\InvalidPropertyAssignmentValue;
use Psalm\Issue\LoopInvalidation;
use Psalm\Issue\MixedAssignment;
use Psalm\Issue\MixedPropertyAssignment;
use Psalm\Issue\MixedPropertyTypeCoercion;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullPropertyAssignment;
use Psalm\Issue\PossiblyFalsePropertyAssignmentValue;
use Psalm\Issue\PossiblyInvalidPropertyAssignment;
use Psalm\Issue\PossiblyInvalidPropertyAssignmentValue;
use Psalm\Issue\PossiblyNullPropertyAssignment;
use Psalm\Issue\PossiblyNullPropertyAssignmentValue;
use Psalm\Issue\PropertyTypeCoercion;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedPropertyAssignment;
use Psalm\Issue\UndefinedThisPropertyAssignment;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use function count;
use function in_array;
use function strtolower;
use function explode;

/**
 * @internal
 */
class PropertyAssignmentAnalyzer
{
    /**
     * @param   StatementsAnalyzer               $statements_analyzer
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
        StatementsAnalyzer $statements_analyzer,
        $stmt,
        $prop_name,
        $assignment_value,
        Type\Union $assignment_value_type,
        Context $context,
        $direct_assignment = true
    ) {
        $class_property_types = [];

        $codebase = $statements_analyzer->getCodebase();

        $property_exists = false;

        $property_ids = [];

        if ($stmt instanceof PropertyProperty) {
            if (!$context->self || !$stmt->default) {
                return null;
            }

            $property_id = $context->self . '::$' . $prop_name;
            $property_ids[] = $property_id;

            $property_exists = true;

            $class_property_type = $codebase->properties->getPropertyType(
                $property_id,
                true,
                $statements_analyzer,
                $context
            );

            if ($class_property_type && $context->self) {
                $class_storage = $codebase->classlike_storage_provider->get($context->self);

                $class_property_type = ExpressionAnalyzer::fleshOutType(
                    $codebase,
                    $class_property_type,
                    $context->self,
                    null,
                    $class_storage->parent_class
                );
            }

            $class_property_types[] = $class_property_type ?: Type::getMixed();

            $var_id = '$this->' . $prop_name;
        } else {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
                return false;
            }

            $lhs_type = isset($stmt->var->inferredType) ? $stmt->var->inferredType : null;

            if ($lhs_type === null) {
                return null;
            }

            $lhs_var_id = ExpressionAnalyzer::getVarId(
                $stmt->var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            $var_id = ExpressionAnalyzer::getVarId(
                $stmt,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            if ($var_id) {
                $context->assigned_var_ids[$var_id] = true;

                if ($direct_assignment && isset($context->protected_var_ids[$var_id])) {
                    if (IssueBuffer::accepts(
                        new LoopInvalidation(
                            'Variable ' . $var_id . ' has already been assigned in a for/foreach loop',
                            new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if ($lhs_type->hasMixed()) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                if ($stmt->name instanceof PhpParser\Node\Identifier) {
                    $codebase->analyzer->addMixedMemberName(
                        '$' . $stmt->name->name,
                        $context->calling_method_id ?: $statements_analyzer->getFileName()
                    );
                }

                if (IssueBuffer::accepts(
                    new MixedPropertyAssignment(
                        $lhs_var_id . ' of type mixed cannot be assigned to',
                        new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }

                return null;
            }

            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
            }

            if ($lhs_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullPropertyAssignment(
                        $lhs_var_id . ' of type null cannot be assigned to',
                        new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }

                return null;
            }

            if ($lhs_type->isNullable() && !$lhs_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullPropertyAssignment(
                        $lhs_var_id . ' with possibly null type \'' . $lhs_type . '\' cannot be assigned to',
                        new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
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

                if ($lhs_type_part instanceof Type\Atomic\TFalse
                    && $lhs_type->ignore_falsable_issues
                    && count($lhs_type->getTypes()) > 1
                ) {
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

                if (ExpressionAnalyzer::isMock($lhs_type_part->value)) {
                    if ($var_id) {
                        $context->vars_in_scope[$var_id] = Type::getMixed();
                    }

                    return null;
                }

                $intersection_types = $lhs_type_part->getIntersectionTypes() ?: [];

                $fq_class_name = $lhs_type_part->value;

                $override_property_visibility = false;

                $class_exists = false;
                $interface_exists = false;

                if (!$codebase->classExists($lhs_type_part->value)) {
                    if ($codebase->interfaceExists($lhs_type_part->value)) {
                        $interface_exists = true;
                        $interface_storage = $codebase->classlike_storage_provider->get($lhs_type_part->value);

                        $override_property_visibility = $interface_storage->override_property_visibility;

                        foreach ($intersection_types as $intersection_type) {
                            if ($intersection_type instanceof TNamedObject
                                && $codebase->classExists($intersection_type->value)
                            ) {
                                $fq_class_name = $intersection_type->value;
                                $class_exists = true;
                                break;
                            }
                        }

                        if (!$class_exists) {
                            if (IssueBuffer::accepts(
                                new NoInterfaceProperties(
                                    'Interfaces cannot have properties',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $lhs_type_part->value
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                return null;
                            }

                            if (!$codebase->methodExists($fq_class_name . '::__set')) {
                                return null;
                            }
                        }
                    }

                    if (!$class_exists && !$interface_exists) {
                        if (IssueBuffer::accepts(
                            new UndefinedClass(
                                'Cannot set properties of undefined class ' . $lhs_type_part->value,
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $lhs_type_part->value
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }

                        return null;
                    }
                } else {
                    $class_exists = true;
                }

                $property_id = $fq_class_name . '::$' . $prop_name;
                $property_ids[] = $property_id;

                if ($codebase->methodExists($fq_class_name . '::__set')
                    && (!$codebase->properties->propertyExists($property_id, false, $statements_analyzer, $context)
                        || ($lhs_var_id !== '$this'
                            && $fq_class_name !== $context->self
                            && ClassLikeAnalyzer::checkPropertyVisibility(
                                $property_id,
                                $context,
                                $statements_analyzer,
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $statements_analyzer->getSuppressedIssues(),
                                false
                            ) !== true)
                    )
                ) {
                    $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                    if ($var_id) {
                        if (isset($class_storage->pseudo_property_set_types['$' . $prop_name])) {
                            $class_property_types[] =
                                clone $class_storage->pseudo_property_set_types['$' . $prop_name];

                            $has_regular_setter = true;
                            $property_exists = true;
                            continue;
                        }
                    }

                    if ($assignment_value) {
                        $fake_method_call = new PhpParser\Node\Expr\MethodCall(
                            $stmt->var,
                            new PhpParser\Node\Identifier('__set', $stmt->name->getAttributes()),
                            [
                                new PhpParser\Node\Arg(
                                    new PhpParser\Node\Scalar\String_(
                                        $prop_name,
                                        $stmt->name->getAttributes()
                                    )
                                ),
                                new PhpParser\Node\Arg(
                                    $assignment_value
                                )
                            ]
                        );

                        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

                        if (!in_array('PossiblyNullReference', $suppressed_issues, true)) {
                            $statements_analyzer->addSuppressedIssues(['PossiblyNullReference']);
                        }

                        \Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer::analyze(
                            $statements_analyzer,
                            $fake_method_call,
                            $context,
                            false
                        );

                        if (!in_array('PossiblyNullReference', $suppressed_issues, true)) {
                            $statements_analyzer->removeSuppressedIssues(['PossiblyNullReference']);
                        }
                    }

                    /*
                     * If we have an explicit list of all allowed magic properties on the class, and we're
                     * not in that list, fall through
                     */
                    if (!$var_id || !$class_storage->sealed_properties) {
                        continue;
                    }

                    if (!$class_exists) {
                        if (IssueBuffer::accepts(
                            new UndefinedPropertyAssignment(
                                'Instance property ' . $property_id . ' is not defined',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $property_id
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }

                if (!$class_exists) {
                    continue;
                }

                $has_regular_setter = true;

                if ($stmt->var instanceof PhpParser\Node\Expr\Variable
                    && $stmt->var->name === 'this'
                    && $context->self
                ) {
                    $self_property_id = $context->self . '::$' . $prop_name;

                    if ($self_property_id !== $property_id
                        && $codebase->properties->propertyExists(
                            $self_property_id,
                            false,
                            $statements_analyzer,
                            $context
                        )
                    ) {
                        $property_id = $self_property_id;
                    }
                }

                if (!$codebase->properties->propertyExists(
                    $property_id,
                    false,
                    $statements_analyzer,
                    $context,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                )) {
                    if ($stmt->var instanceof PhpParser\Node\Expr\Variable && $stmt->var->name === 'this') {
                        // if this is a proper error, we'll see it on the first pass
                        if ($context->collect_mutations) {
                            continue;
                        }

                        if (IssueBuffer::accepts(
                            new UndefinedThisPropertyAssignment(
                                'Instance property ' . $property_id . ' is not defined',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $property_id
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new UndefinedPropertyAssignment(
                                'Instance property ' . $property_id . ' is not defined',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $property_id
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }

                    continue;
                }

                if ($codebase->store_node_types
                    && (!$context->collect_initializations
                        && !$context->collect_mutations)
                ) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt->name,
                        $property_id
                    );
                }

                $property_exists = true;

                if (!$override_property_visibility) {
                    if (!$context->collect_mutations) {
                        if (ClassLikeAnalyzer::checkPropertyVisibility(
                            $property_id,
                            $context,
                            $statements_analyzer,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $statements_analyzer->getSuppressedIssues()
                        ) === false) {
                            return false;
                        }
                    } else {
                        if (ClassLikeAnalyzer::checkPropertyVisibility(
                            $property_id,
                            $context,
                            $statements_analyzer,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $statements_analyzer->getSuppressedIssues(),
                            false
                        ) !== true) {
                            continue;
                        }
                    }
                }

                $declaring_property_class = (string) $codebase->properties->getDeclaringClassForProperty(
                    $property_id,
                    false
                );

                if ($codebase->properties_to_rename) {
                    $declaring_property_id = strtolower($declaring_property_class) . '::$' . $prop_name;

                    foreach ($codebase->properties_to_rename as $original_property_id => $new_property_name) {
                        if ($declaring_property_id === $original_property_id) {
                            $file_manipulations = [
                                new \Psalm\FileManipulation(
                                    (int) $stmt->name->getAttribute('startFilePos'),
                                    (int) $stmt->name->getAttribute('endFilePos') + 1,
                                    $new_property_name
                                )
                            ];

                            \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                                $statements_analyzer->getFilePath(),
                                $file_manipulations
                            );
                        }
                    }
                }

                $class_storage = $codebase->classlike_storage_provider->get($declaring_property_class);

                $property_storage = null;

                if (isset($class_storage->properties[$prop_name])) {
                    $property_storage = $class_storage->properties[$prop_name];

                    if ($property_storage->deprecated) {
                        if (IssueBuffer::accepts(
                            new DeprecatedProperty(
                                $property_id . ' is marked deprecated',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $property_id
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }

                    if ($property_storage->psalm_internal && $context->self) {
                        if (! NamespaceAnalyzer::isWithin($context->self, $property_storage->psalm_internal)) {
                            if (IssueBuffer::accepts(
                                new InternalProperty(
                                    $property_id . ' is marked internal to ' . $property_storage->psalm_internal,
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }

                    if ($property_storage->internal && $context->self) {
                        if (! NamespaceAnalyzer::nameSpaceRootsMatch($context->self, $declaring_property_class)) {
                            if (IssueBuffer::accepts(
                                new InternalProperty(
                                    $property_id . ' is marked internal',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }
                }

                $class_property_type = $codebase->properties->getPropertyType(
                    $property_id,
                    true,
                    $statements_analyzer,
                    $context
                );

                if (!$class_property_type) {
                    $class_property_type = Type::getMixed();

                    if (!$assignment_value_type->hasMixed() && $property_storage) {
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
                    $class_property_type = ExpressionAnalyzer::fleshOutType(
                        $codebase,
                        $class_property_type,
                        $fq_class_name,
                        $lhs_type_part,
                        $class_storage->parent_class
                    );

                    if (!$class_property_type->hasMixed() && $assignment_value_type->hasMixed()) {
                        if (IssueBuffer::accepts(
                            new MixedAssignment(
                                'Cannot assign' . ($var_id ? ' ' . $var_id . ' ' : ' ') . 'to a mixed type',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
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
                            new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidPropertyAssignment(
                            $lhs_var_id . ' with possible non-object type \'' . $invalid_assignment_type .
                            '\' cannot treated as an object',
                            new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
            }

            if (!$has_regular_setter) {
                return null;
            }

            if ($var_id) {
                if ($context->collect_initializations
                    && $var_id
                    && $lhs_var_id === '$this'
                ) {
                    $assignment_value_type->initialized_class = $context->self;
                }

                // because we don't want to be assigning for property declarations
                $context->vars_in_scope[$var_id] = $assignment_value_type;
            }
        }

        if (!$property_exists) {
            return null;
        }

        if ($assignment_value_type->hasMixed()) {
            return null;
        }

        $invalid_assignment_value_types = [];

        $has_valid_assignment_value_type = false;

        if ($codebase->store_node_types
            && (!$context->collect_initializations
                && !$context->collect_mutations)
            && count($class_property_types) === 1
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                (string) $class_property_types[0]
            );
        }

        foreach ($class_property_types as $class_property_type) {
            if ($class_property_type->hasMixed()) {
                continue;
            }

            $type_match_found = TypeAnalyzer::isContainedBy(
                $codebase,
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
                        new MixedPropertyTypeCoercion(
                            $var_id . ' expects \'' . $class_property_type->getId() . '\', '
                                . ' parent type `' . $assignment_value_type->getId() . '` provided',
                            new CodeLocation(
                                $statements_analyzer->getSource(),
                                $assignment_value ?: $stmt,
                                $context->include_location
                            ),
                            $property_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep soldiering on
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new PropertyTypeCoercion(
                            $var_id . ' expects \'' . $class_property_type->getId() . '\', '
                                . ' parent type \'' . $assignment_value_type->getId() . '\' provided',
                            new CodeLocation(
                                $statements_analyzer->getSource(),
                                $assignment_value ?: $stmt,
                                $context->include_location
                            ),
                            $property_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
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
                            $statements_analyzer->getSource(),
                            $assignment_value ?: $stmt,
                            $context->include_location
                        )
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if (!$type_match_found && !$type_coerced) {
                if (TypeAnalyzer::canBeContainedBy(
                    $codebase,
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
                                $statements_analyzer->getSource(),
                                $assignment_value ?: $stmt,
                                $context->include_location
                            ),
                            $property_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                if (!$assignment_value_type->ignore_falsable_issues
                    && $assignment_value_type->isFalsable()
                    && !$class_property_type->hasBool()
                    && !$class_property_type->hasScalar()
                ) {
                    if (IssueBuffer::accepts(
                        new PossiblyFalsePropertyAssignmentValue(
                            $var_id . ' with non-falsable declared type \'' . $class_property_type .
                                '\' cannot be assigned possibly false type \'' . $assignment_value_type . '\'',
                            new CodeLocation(
                                $statements_analyzer->getSource(),
                                $assignment_value ?: $stmt,
                                $context->include_location
                            ),
                            $property_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
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
                            $statements_analyzer->getSource(),
                            $assignment_value ?: $stmt,
                            $context->include_location
                        ),
                        $property_ids[0]
                    ),
                    $statements_analyzer->getSuppressedIssues()
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
                            $statements_analyzer->getSource(),
                            $assignment_value ?: $stmt,
                            $context->include_location
                        ),
                        $property_ids[0]
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        return null;
    }

    /**
     * @param   StatementsAnalyzer                         $statements_analyzer
     * @param   PhpParser\Node\Expr\StaticPropertyFetch   $stmt
     * @param   PhpParser\Node\Expr|null                  $assignment_value
     * @param   Type\Union                                $assignment_value_type
     * @param   Context                                   $context
     *
     * @return  false|null
     */
    public static function analyzeStatic(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticPropertyFetch $stmt,
        $assignment_value,
        Type\Union $assignment_value_type,
        Context $context
    ) {
        $var_id = ExpressionAnalyzer::getVarId(
            $stmt,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $fq_class_name = (string)$stmt->class->inferredType;

        $codebase = $statements_analyzer->getCodebase();

        $prop_name = $stmt->name;

        if (!$prop_name instanceof PhpParser\Node\Identifier) {
            if ($fq_class_name && !$context->ignore_variable_property) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($fq_class_name) . '::$',
                    $context->calling_method_id ?: $statements_analyzer->getFileName()
                );
            }

            return;
        }

        $property_id = $fq_class_name . '::$' . $prop_name;

        if (!$codebase->properties->propertyExists($property_id, false, $statements_analyzer, $context)) {
            if (IssueBuffer::accepts(
                new UndefinedPropertyAssignment(
                    'Static property ' . $property_id . ' is not defined',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $property_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return;
        }

        if (ClassLikeAnalyzer::checkPropertyVisibility(
            $property_id,
            $context,
            $statements_analyzer,
            new CodeLocation($statements_analyzer->getSource(), $stmt),
            $statements_analyzer->getSuppressedIssues()
        ) === false) {
            return false;
        }

        $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
            $fq_class_name . '::$' . $prop_name->name,
            false
        );

        $declaring_property_id = strtolower((string) $declaring_property_class) . '::$' . $prop_name;

        if ($codebase->alter_code && $stmt->class instanceof PhpParser\Node\Name) {
            $moved_class = $codebase->classlikes->handleClassLikeReferenceInMigration(
                $codebase,
                $statements_analyzer,
                $stmt->class,
                $fq_class_name,
                $context->calling_method_id
            );

            if (!$moved_class) {
                foreach ($codebase->property_transforms as $original_pattern => $transformation) {
                    if ($declaring_property_id === $original_pattern) {
                        list($old_declaring_fq_class_name) = explode('::$', $declaring_property_id);
                        list($new_fq_class_name, $new_property_name) = explode('::$', $transformation);

                        $file_manipulations = [];

                        if (strtolower($new_fq_class_name) !== strtolower($old_declaring_fq_class_name)) {
                            $file_manipulations[] = new \Psalm\FileManipulation(
                                (int) $stmt->class->getAttribute('startFilePos'),
                                (int) $stmt->class->getAttribute('endFilePos') + 1,
                                Type::getStringFromFQCLN(
                                    $new_fq_class_name,
                                    $statements_analyzer->getNamespace(),
                                    $statements_analyzer->getAliasedClassesFlipped(),
                                    null
                                )
                            );
                        }

                        $file_manipulations[] = new \Psalm\FileManipulation(
                            (int) $stmt->name->getAttribute('startFilePos'),
                            (int) $stmt->name->getAttribute('endFilePos') + 1,
                            '$' . $new_property_name
                        );

                        FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
                    }
                }
            }
        }

        $class_storage = $codebase->classlike_storage_provider->get((string)$declaring_property_class);

        $property_storage = $class_storage->properties[$prop_name->name];

        if ($var_id) {
            $context->vars_in_scope[$var_id] = $assignment_value_type;
        }

        $class_property_type = $codebase->properties->getPropertyType(
            $property_id,
            true,
            $statements_analyzer,
            $context
        );

        if (!$class_property_type) {
            $class_property_type = Type::getMixed();

            if (!$assignment_value_type->hasMixed()) {
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

        if ($assignment_value_type->hasMixed()) {
            return null;
        }

        if ($class_property_type->hasMixed()) {
            return null;
        }

        $class_property_type = ExpressionAnalyzer::fleshOutType(
            $codebase,
            $class_property_type,
            $fq_class_name,
            $fq_class_name,
            $class_storage->parent_class
        );

        $type_match_found = TypeAnalyzer::isContainedBy(
            $codebase,
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
                    new MixedPropertyTypeCoercion(
                        $var_id . ' expects \'' . $class_property_type . '\', '
                            . ' parent type `' . $assignment_value_type . '` provided',
                        new CodeLocation(
                            $statements_analyzer->getSource(),
                            $assignment_value ?: $stmt,
                            $context->include_location
                        ),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep soldiering on
                }
            } else {
                if (IssueBuffer::accepts(
                    new PropertyTypeCoercion(
                        $var_id . ' expects \'' . $class_property_type . '\', '
                            . ' parent type \'' . $assignment_value_type . '\' provided',
                        new CodeLocation(
                            $statements_analyzer->getSource(),
                            $assignment_value ?: $stmt,
                            $context->include_location
                        ),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
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
                        $statements_analyzer->getSource(),
                        $assignment_value ?: $stmt,
                        $context->include_location
                    )
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if (!$type_match_found && !$type_coerced) {
            if (TypeAnalyzer::canBeContainedBy($codebase, $assignment_value_type, $class_property_type)) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidPropertyAssignmentValue(
                        $var_id . ' with declared type \'' . $class_property_type . '\' cannot be assigned type \'' .
                            $assignment_value_type . '\'',
                        new CodeLocation(
                            $statements_analyzer->getSource(),
                            $assignment_value ?: $stmt
                        ),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidPropertyAssignmentValue(
                        $var_id . ' with declared type \'' . $class_property_type . '\' cannot be assigned type \'' .
                            $assignment_value_type . '\'',
                        new CodeLocation(
                            $statements_analyzer->getSource(),
                            $assignment_value ?: $stmt
                        ),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
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
