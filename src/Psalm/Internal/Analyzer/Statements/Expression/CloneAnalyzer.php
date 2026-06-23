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
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidClone;
use Psalm\Issue\InvalidPropertyAssignmentValue;
use Psalm\Issue\MixedClone;
use Psalm\Issue\ParseError;
use Psalm\Issue\PossiblyInvalidClone;
use Psalm\Issue\PossiblyInvalidPropertyAssignmentValue;
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
     * Handles the PHP 8.5 clone-with-properties form, which php-parser routes as a
     * call to the `clone` function rather than a `Clone_` node:
     *   - `clone($object, ['prop' => $value, ...])`
     *   - `clone(object: $object, withProperties: [...])`
     *
     * The cloned type is preserved (instead of the flat `object` the CallMap would
     * return) and the same validity checks as the bare `clone $x` form are applied.
     */
    public static function analyzeFuncCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        Context $context,
    ): bool {
        $location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        // The function form of clone only exists on PHP 8.5+. Below that it is a
        // compile error, so report it while still analysing the call for tooling.
        if ($statements_analyzer->getCodebase()->analysis_php_version_id < 8_05_00) {
            IssueBuffer::maybeAdd(
                new ParseError(
                    'Calling clone() as a function with arguments requires PHP 8.5',
                    $location,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        // Analyze every argument value up front so no sub-expression is skipped, even
        // for malformed calls (extra or unknown-named arguments).
        foreach ($stmt->getArgs() as $arg) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context) === false) {
                return false;
            }
        }

        $object_arg = null;
        $with_properties_arg = null;

        foreach ($stmt->getArgs() as $i => $arg) {
            $arg_name = $arg->name?->name;

            if ($arg_name === 'object' || ($arg_name === null && $i === 0)) {
                $object_arg = $arg;
            } elseif ($arg_name === 'withProperties' || ($arg_name === null && $i === 1)) {
                $with_properties_arg = $arg;
            }
        }

        // No object to clone (e.g. `clone(withProperties: [...])`); fall back to `object`.
        $object_type = $object_arg !== null
            ? $statements_analyzer->node_data->getType($object_arg->value)
            : null;

        $result_type = $object_type !== null
            ? self::analyzeClonedType($statements_analyzer, $context, $location, $object_type)
            : null;

        if ($object_arg !== null && $with_properties_arg !== null) {
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
     * Validates a clone of the given type, emitting clone-validity issues
     * (MixedClone/InvalidClone/PossiblyInvalidClone, __clone visibility) and
     * returning the result type with immutability flags applied.
     *
     * Returns null when the clone is invalid (the relevant issue has already
     * been emitted), mirroring the bare `clone $x` behaviour of leaving the
     * expression untyped.
     */
    public static function analyzeClonedType(
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

    /**
     * Validates the `withProperties` array of a clone-with expression.
     *
     * Each literal-string key must name an accessible property of the cloned class,
     * and each value must be assignable to that property's declared type. Unlike a
     * regular property write, replacing `readonly` properties is permitted here (the
     * point of the RFC), so this validation deliberately does not route through
     * InstancePropertyAssignmentAnalyzer, whose readonly write guard would reject
     * top-level clone-with calls (it requires `$context->self`, which is null outside
     * a class). The type/visibility/existence checks are replicated instead.
     *
     * Not (yet) replicated from the regular property-write path: data-flow/taint of the
     * replacement values, magic `__set` value typing, and the softer coercion diagnostics
     * (PropertyTypeCoercion, ImplicitToStringCast, PossiblyNull/FalsePropertyAssignmentValue).
     */
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

        // Per-key validation needs both a literal array and a single, known class.
        // Anything else (dynamic array, union/templated/object target) is left
        // unchecked to avoid false positives; the result type is preserved regardless.
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

    /**
     * Validates a single `prop => value` entry of a clone-with array against the
     * cloned class, emitting undefined-property, visibility and value-type issues.
     */
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
            $has_magic_setter = $codebase->methods->methodExists(
                new MethodIdentifier($fq_class_name, '__set'),
            );

            // A magic __set (or a declared @property-write) can accept an unknown key.
            if (!$has_magic_setter
                && !isset($class_storage->pseudo_property_set_types['$' . $prop_name])
            ) {
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

        // The value comparison is skipped when it cannot be performed faithfully:
        //  - a generic cloned class, whose property types may reference template
        //    parameters that are not resolved here (the type arguments are not threaded
        //    into getExpandedPropertyType, so they stay as `T as ...`), or
        //  - a null/mixed declared or value type.
        // Skipping avoids false positives at the cost of not validating these values.
        if ($class_property_type === null
            || $class_storage->template_types !== null
            || $class_property_type->hasMixed()
            || $value_type->hasMixed()
        ) {
            return;
        }

        $comparison_result = new TypeComparisonResult();

        $type_match_found = UnionTypeComparator::isContainedBy(
            $codebase,
            $value_type,
            $class_property_type,
            true,
            true,
            $comparison_result,
        );

        if ($type_match_found || $comparison_result->type_coerced) {
            return;
        }

        if (UnionTypeComparator::canBeContainedBy($codebase, $value_type, $class_property_type, true, true)) {
            IssueBuffer::maybeAdd(
                new PossiblyInvalidPropertyAssignmentValue(
                    $property_id . ' with declared type \'' . $class_property_type->getId()
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
                $property_id . ' with declared type \'' . $class_property_type->getId()
                    . '\' cannot be assigned type \'' . $value_type->getId() . '\'',
                $location,
                $property_id,
            ),
            $statements_analyzer->getSuppressedIssues(),
        );
    }

    /**
     * Returns the single named class behind the cloned object's type, or null when it
     * is not exactly one known class (union, intersection, template, plain object,
     * interface, ...), in which case per-key validation is skipped.
     */
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
