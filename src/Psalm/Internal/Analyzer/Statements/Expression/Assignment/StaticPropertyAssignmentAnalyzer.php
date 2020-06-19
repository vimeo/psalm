<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Assignment;

use PhpParser;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\PropertyProperty;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\InstancePropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DeprecatedProperty;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InaccessibleProperty;
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
use Psalm\Issue\UndefinedMagicPropertyAssignment;
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
use Psalm\Internal\Taint\TaintNode;

/**
 * @internal
 */
class StaticPropertyAssignmentAnalyzer
{
    /**
     * @param   StatementsAnalyzer                         $statements_analyzer
     * @param   PhpParser\Node\Expr\StaticPropertyFetch   $stmt
     * @param   PhpParser\Node\Expr|null                  $assignment_value
     * @param   Type\Union                                $assignment_value_type
     * @param   Context                                   $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticPropertyFetch $stmt,
        $assignment_value,
        Type\Union $assignment_value_type,
        Context $context
    ) {
        $var_id = ExpressionIdentifier::getArrayVarId(
            $stmt,
            $context->self,
            $statements_analyzer
        );

        $fq_class_name = (string) $statements_analyzer->node_data->getType($stmt->class);

        $codebase = $statements_analyzer->getCodebase();

        $prop_name = $stmt->name;

        if (!$prop_name instanceof PhpParser\Node\Identifier) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $prop_name, $context) === false) {
                return false;
            }

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

        $declaring_property_class = (string) $codebase->properties->getDeclaringClassForProperty(
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

        $class_storage = $codebase->classlike_storage_provider->get($declaring_property_class);

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

        $class_property_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
            $codebase,
            $class_property_type,
            $fq_class_name,
            $fq_class_name,
            $class_storage->parent_class
        );

        $union_comparison_results = new \Psalm\Internal\Analyzer\TypeComparisonResult();

        $type_match_found = TypeAnalyzer::isContainedBy(
            $codebase,
            $assignment_value_type,
            $class_property_type,
            true,
            true,
            $union_comparison_results
        );

        if ($union_comparison_results->type_coerced) {
            if ($union_comparison_results->type_coerced_from_mixed) {
                if (IssueBuffer::accepts(
                    new MixedPropertyTypeCoercion(
                        $var_id . ' expects \'' . $class_property_type->getId() . '\', '
                            . ' parent type `' . $assignment_value_type->getId() . '` provided',
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
                        $var_id . ' expects \'' . $class_property_type->getId() . '\', '
                            . ' parent type \'' . $assignment_value_type->getId() . '\' provided',
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

        if ($union_comparison_results->to_string_cast) {
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

        if (!$type_match_found && !$union_comparison_results->type_coerced) {
            if (TypeAnalyzer::canBeContainedBy($codebase, $assignment_value_type, $class_property_type)) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidPropertyAssignmentValue(
                        $var_id . ' with declared type \''
                            . $class_property_type->getId() . '\' cannot be assigned type \''
                            . $assignment_value_type->getId() . '\'',
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
                        $var_id . ' with declared type \'' . $class_property_type->getId()
                            . '\' cannot be assigned type \''
                            . $assignment_value_type->getId() . '\'',
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
