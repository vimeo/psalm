<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\InstancePropertyAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallProhibitionAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidClone;
use Psalm\Issue\InvalidNamedArgument;
use Psalm\Issue\InvalidPropertyAssignmentValue;
use Psalm\Issue\MixedClone;
use Psalm\Issue\ParseError;
use Psalm\Issue\PossiblyInvalidClone;
use Psalm\Issue\PossiblyInvalidPropertyAssignmentValue;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\UndefinedPropertyAssignment;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_merge;
use function array_pop;
use function count;

/**
 * @internal
 */
final class CloneAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Clone_ $stmt,
        Context $context,
    ): bool {
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        $location = new CodeLocation($statements_analyzer->getSource(), $stmt);
        $stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        if ($stmt_expr_type) {
            $result_type = self::analyzeClonedType(
                $statements_analyzer,
                $context,
                $location,
                $stmt_expr_type,
            );

            if ($result_type !== null) {
                $statements_analyzer->node_data->setType($stmt, $result_type);
            }
        }

        return true;
    }

    /**
     * Handles `clone($object, [...])`, PHP 8.5's function-call form of clone-with.
     */
    public static function analyzeFuncCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        Context $context,
    ): bool {
        $location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        // Below PHP 8.5 this is a compile error; still analyze for tooling.
        if ($statements_analyzer->getCodebase()->analysis_php_version_id < 8_05_00) {
            IssueBuffer::maybeAdd(
                new ParseError(
                    'Calling clone() as a function with arguments requires PHP 8.5',
                    $location,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        // Analyze all args up front so none is skipped even for malformed calls.
        foreach ($stmt->getArgs() as $arg) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context) === false) {
                return false;
            }
        }

        // Replicate argument-count/named-argument validation, since the normal call
        // path (which would raise it) is skipped by intercepting clone here.
        $object_arg = null;
        $with_properties_arg = null;
        $positional_count = 0;
        $named_arg_seen = false;

        foreach ($stmt->getArgs() as $arg) {
            $arg_name = $arg->name?->name;

            if ($arg_name === null) {
                if ($named_arg_seen) {
                    // PHP fatals on a positional arg after a named one; report it here too.
                    IssueBuffer::maybeAdd(
                        new InvalidNamedArgument(
                            'Cannot use positional argument after named argument',
                            new CodeLocation($statements_analyzer->getSource(), $arg),
                            'clone',
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } elseif ($positional_count === 0) {
                    $object_arg = $arg;
                } elseif ($positional_count === 1) {
                    $with_properties_arg = $arg;
                }

                ++$positional_count;
            } else {
                $named_arg_seen = true;

                if (($arg_name === 'object' && $object_arg !== null)
                    || ($arg_name === 'withProperties' && $with_properties_arg !== null)
                ) {
                    // Named argument overwrites one already passed by position.
                    IssueBuffer::maybeAdd(
                        new InvalidNamedArgument(
                            'Parameter $' . $arg_name . ' of function clone is already passed by position',
                            new CodeLocation($statements_analyzer->getSource(), $arg),
                            'clone',
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } elseif ($arg_name === 'object') {
                    $object_arg = $arg;
                } elseif ($arg_name === 'withProperties') {
                    $with_properties_arg = $arg;
                } else {
                    IssueBuffer::maybeAdd(
                        new InvalidNamedArgument(
                            'Parameter $' . $arg_name . ' does not exist on function clone',
                            new CodeLocation($statements_analyzer->getSource(), $arg),
                            'clone',
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            }
        }

        // Reported once per call, matching ArgumentsAnalyzer's convention.
        if ($positional_count > 2) {
            IssueBuffer::maybeAdd(
                new TooManyArguments(
                    'Too many arguments for clone - expecting 2 but saw ' . count($stmt->getArgs()),
                    $location,
                    'clone',
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if ($object_arg === null) {
            IssueBuffer::maybeAdd(
                new TooFewArguments(
                    'Too few arguments for clone - expecting object to be passed',
                    $location,
                    'clone',
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        // No object to clone (e.g. `clone(withProperties: [...])`); fall back to `object`.
        $object_type = $object_arg !== null
            ? $statements_analyzer->node_data->getType($object_arg->value)
            : null;

        $result_type = $object_type !== null
            ? self::analyzeClonedType($statements_analyzer, $context, $location, $object_type)
            : null;

        if ($with_properties_arg !== null) {
            self::analyzeWithProperties(
                $statements_analyzer,
                $context,
                $location,
                $with_properties_arg,
                $object_type,
            );
        }

        // Always set a type so downstream analysis never sees null for this expression.
        $statements_analyzer->node_data->setType($stmt, $result_type ?? Type::getObject());

        return true;
    }

    /**
     * Validates clone-ability of $clone_type; null return means an issue was already emitted.
     */
    private static function analyzeClonedType(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        CodeLocation $location,
        Union $clone_type,
    ): ?Union {
        $codebase = $statements_analyzer->getCodebase();
        $codebase_methods = $codebase->methods;

        $immutable_cloned = false;

        $invalid_clones = [];
        $mixed_clone = false;

        $possibly_valid = false;
        $atomic_types = $clone_type->getAtomicTypes();

        while ($atomic_types) {
            $clone_type_part = array_pop($atomic_types);

            if ($clone_type_part instanceof TMixed) {
                $mixed_clone = true;
            } elseif ($clone_type_part instanceof TObject) {
                $possibly_valid = true;
            } elseif ($clone_type_part instanceof TNamedObject) {
                if (!$codebase->classlikes->classOrInterfaceExists($clone_type_part->value)) {
                    $invalid_clones[] = $clone_type_part->getId();
                } else {
                    $clone_method_id = new MethodIdentifier(
                        $clone_type_part->value,
                        '__clone',
                    );

                    $does_method_exist = $codebase_methods->methodExists(
                        $clone_method_id,
                        $context->calling_method_id,
                        $location,
                    );
                    $is_method_visible = MethodAnalyzer::isMethodVisible(
                        $clone_method_id,
                        $context,
                        $statements_analyzer->getSource(),
                    );
                    if ($does_method_exist && !$is_method_visible) {
                        $invalid_clones[] = $clone_type_part->getId();
                    } else {
                        MethodCallProhibitionAnalyzer::analyze(
                            $codebase,
                            $context,
                            $clone_method_id,
                            $statements_analyzer->getNamespace(),
                            $location,
                            $statements_analyzer->getSuppressedIssues(),
                        );
                        $possibly_valid = true;
                        $immutable_cloned = true;
                    }
                }
            } elseif ($clone_type_part instanceof TTemplateParam) {
                $atomic_types = array_merge($atomic_types, $clone_type_part->as->getAtomicTypes());
            } else {
                if ($clone_type_part instanceof TFalse
                    && $clone_type->ignore_falsable_issues
                ) {
                    continue;
                }

                if ($clone_type_part instanceof TNull
                    && $clone_type->ignore_nullable_issues
                ) {
                    continue;
                }

                $invalid_clones[] = $clone_type_part->getId();
            }
        }

        if ($mixed_clone) {
            IssueBuffer::maybeAdd(
                new MixedClone(
                    'Cannot clone mixed',
                    $location,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if ($invalid_clones) {
            if ($possibly_valid) {
                IssueBuffer::maybeAdd(
                    new PossiblyInvalidClone(
                        'Cannot clone ' . $invalid_clones[0],
                        $location,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } else {
                IssueBuffer::maybeAdd(
                    new InvalidClone(
                        'Cannot clone ' . $invalid_clones[0],
                        $location,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            return null;
        }

        if ($immutable_cloned) {
            $clone_type = $clone_type->setProperties([
                'reference_free' => true,
                'allow_mutations' => true,
            ]);
        }

        return $clone_type;
    }

    /** Skips InstancePropertyAssignmentAnalyzer: its readonly guard would reject clone-with. */
    private static function analyzeWithProperties(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        CodeLocation $location,
        PhpParser\Node\Arg $with_properties_arg,
        ?Union $object_type,
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        $with_properties_value = $with_properties_arg->value;
        $with_properties_type = $statements_analyzer->node_data->getType($with_properties_value);

        // A second argument that cannot be an array is always invalid.
        if ($with_properties_type !== null
            && !$with_properties_type->hasArray()
            && !$with_properties_type->hasMixed()
            && !$with_properties_type->hasTemplate()
        ) {
            IssueBuffer::maybeAdd(
                new InvalidArgument(
                    'Argument 2 of clone expects array<string, mixed>, but '
                        . $with_properties_type->getId() . ' provided',
                    $location,
                    'clone',
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            return;
        }

        // Per-key validation needs a literal array and a single known class; anything
        // else is left unchecked to avoid false positives.
        if (!$with_properties_value instanceof PhpParser\Node\Expr\Array_) {
            return;
        }

        $fq_class_name = self::getClonedClassName($codebase, $object_type);

        if ($fq_class_name === null) {
            return;
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        foreach ($with_properties_value->items as $item) {
            if ($item === null || $item->unpack || $item->key === null) {
                continue;
            }

            $prop_name = self::getLiteralPropertyName($statements_analyzer, $item->key);

            if ($prop_name === null) {
                continue;
            }

            $value_type = $statements_analyzer->node_data->getType($item->value) ?? Type::getMixed();

            self::validateClonedProperty(
                $statements_analyzer,
                $context,
                $class_storage,
                $fq_class_name,
                $prop_name,
                $value_type,
                new CodeLocation($statements_analyzer->getSource(), $item->value),
            );
        }
    }

    /** Validates a single `prop => value` entry of a clone-with array. */
    private static function validateClonedProperty(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        ClassLikeStorage $class_storage,
        string $fq_class_name,
        string $prop_name,
        Union $value_type,
        CodeLocation $location,
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        $property_id = $fq_class_name . '::$' . $prop_name;

        if (!$codebase->properties->propertyExists($property_id, false, $statements_analyzer, $context)) {
            // A declared @property-write key is accepted, and its write type is checked.
            $pseudo_set_type = $class_storage->pseudo_property_set_types['$' . $prop_name] ?? null;

            if ($pseudo_set_type !== null) {
                if ($class_storage->template_types === null) {
                    self::validatePropertyValueType(
                        $statements_analyzer,
                        $value_type,
                        TypeExpander::expandUnion(
                            $codebase,
                            $pseudo_set_type,
                            $fq_class_name,
                            $fq_class_name,
                            $class_storage->parent_class,
                        ),
                        $property_id,
                        $location,
                    );
                }

                return;
            }

            // Otherwise only a magic __set can accept the unknown key.
            if (!$codebase->methods->methodExists(new MethodIdentifier($fq_class_name, '__set'))) {
                IssueBuffer::maybeAdd(
                    new UndefinedPropertyAssignment(
                        'Instance property ' . $property_id . ' is not defined',
                        $location,
                        $property_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            return;
        }

        try {
            // Emits InaccessibleProperty when the property is not visible from here.
            // The readonly write guard is intentionally skipped (see analyzeWithProperties).
            ClassLikeAnalyzer::checkPropertyVisibility(
                $property_id,
                $context,
                $statements_analyzer,
                $location,
                $statements_analyzer->getSuppressedIssues(),
            );

            $class_property_type = InstancePropertyAssignmentAnalyzer::getExpandedPropertyType(
                $codebase,
                $fq_class_name,
                $prop_name,
                $class_storage,
            );
        } catch (UnexpectedValueException) {
            // A plugin can report a property as existing without a resolvable declaring
            // class; skip the value check rather than crash.
            return;
        }

        // Skipped for generic classes: property types stay as unresolved template params.
        if ($class_property_type === null || $class_storage->template_types !== null) {
            return;
        }

        self::validatePropertyValueType(
            $statements_analyzer,
            $value_type,
            $class_property_type,
            $property_id,
            $location,
        );
    }

    /** Reports (Possibly)InvalidPropertyAssignmentValue for a non-assignable value. */
    private static function validatePropertyValueType(
        StatementsAnalyzer $statements_analyzer,
        Union $value_type,
        Union $declared_type,
        string $property_id,
        CodeLocation $location,
    ): void {
        if ($declared_type->hasMixed() || $value_type->hasMixed()) {
            return;
        }

        $codebase = $statements_analyzer->getCodebase();
        $comparison_result = new TypeComparisonResult();

        $type_match_found = UnionTypeComparator::isContainedBy(
            $codebase,
            $value_type,
            $declared_type,
            true,
            true,
            $comparison_result,
        );

        if ($type_match_found || $comparison_result->type_coerced) {
            return;
        }

        if (UnionTypeComparator::canBeContainedBy($codebase, $value_type, $declared_type, true, true)) {
            IssueBuffer::maybeAdd(
                new PossiblyInvalidPropertyAssignmentValue(
                    $property_id . ' with declared type \'' . $declared_type->getId()
                        . '\' cannot be assigned possibly different type \'' . $value_type->getId() . '\'',
                    $location,
                    $property_id,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            return;
        }

        IssueBuffer::maybeAdd(
            new InvalidPropertyAssignmentValue(
                $property_id . ' with declared type \'' . $declared_type->getId()
                    . '\' cannot be assigned type \'' . $value_type->getId() . '\'',
                $location,
                $property_id,
            ),
            $statements_analyzer->getSuppressedIssues(),
        );
    }

    /** Returns the single named class behind $object_type, or null if not exactly one. */
    private static function getClonedClassName(Codebase $codebase, ?Union $object_type): ?string
    {
        if ($object_type === null || !$object_type->isSingle()) {
            return null;
        }

        $atomic = $object_type->getSingleAtomic();

        if (!$atomic instanceof TNamedObject
            || $atomic->extra_types // intersection: members beyond the primary are not validated
            || !$codebase->classlikes->classExists($atomic->value)
        ) {
            return null;
        }

        return $atomic->value;
    }

    private static function getLiteralPropertyName(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $key,
    ): ?string {
        if ($key instanceof PhpParser\Node\Scalar\String_) {
            return $key->value;
        }

        $key_type = $statements_analyzer->node_data->getType($key);

        if ($key_type !== null && $key_type->isSingleStringLiteral()) {
            return $key_type->getSingleStringLiteral()->value;
        }

        return null;
    }
}
