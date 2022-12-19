<?php

namespace Psalm\Type;

use InvalidArgumentException;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\AssertionReconciler;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\PsalmInternalError;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\RedundantConditionGivenDocblockType;
use Psalm\Issue\RedundantPropertyInitializationCheck;
use Psalm\Issue\TypeDoesNotContainNull;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\ArrayKeyExists;
use Psalm\Storage\Assertion\Empty_;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\HasArrayKey;
use Psalm\Storage\Assertion\HasIntOrStringArrayAccess;
use Psalm\Storage\Assertion\HasStringArrayAccess;
use Psalm\Storage\Assertion\IsEqualIsset;
use Psalm\Storage\Assertion\IsIdentical;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Storage\Assertion\IsNotIsset;
use Psalm\Storage\Assertion\IsNotLooselyEqual;
use Psalm\Storage\Assertion\NestedAssertions;
use Psalm\Storage\Assertion\NonEmpty;
use Psalm\Storage\Assertion\NonEmptyCountable;
use Psalm\Storage\Assertion\NotNestedAssertions;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use ReflectionProperty;
use UnexpectedValueException;

use function array_keys;
use function array_merge;
use function array_pop;
use function array_shift;
use function array_values;
use function count;
use function explode;
use function implode;
use function is_numeric;
use function key;
use function ksort;
use function preg_match;
use function preg_quote;
use function str_replace;
use function str_split;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

class Reconciler
{
    public const RECONCILIATION_OK = 0;
    public const RECONCILIATION_REDUNDANT = 1;
    public const RECONCILIATION_EMPTY = 2;

    /** @var array<string, non-empty-list<string>> */
    private static array $broken_paths = [];

    /**
     * Takes two arrays and consolidates them, removing null values from existing types where applicable.
     * Returns a tuple of [new_types, new_references].
     *
     * @param  array<string, array<array<int, Assertion>>> $new_types
     * @param  array<string, array<array<int, Assertion>>> $active_new_types - types we can complain about
     * @param  array<string, Union> $existing_types
     * @param  array<string, string> $existing_references Maps keys of $existing_types that are references to other
     *                                                    keys of $existing_types that they are references to.
     * @param  array<string, bool>       $changed_var_ids
     * @param  array<string, bool>       $referenced_var_ids
     * @param  array<string, array<string, Union>> $template_type_map
     * @return array{array<string, Union>, array<string, string>}
     * @psalm-suppress ComplexMethod
     */
    public static function reconcileKeyedTypes(
        array $new_types,
        array $active_new_types,
        array $existing_types,
        array $existing_references,
        array &$changed_var_ids,
        array $referenced_var_ids,
        StatementsAnalyzer $statements_analyzer,
        array $template_type_map = [],
        bool $inside_loop = false,
        ?CodeLocation $code_location = null,
        bool $negated = false
    ): array {
        if (!$new_types) {
            return [$existing_types, $existing_references];
        }

        $reference_graph = [];
        if (!empty($existing_references)) {
            // PHP behaves oddly when passing an array containing references: https://bugs.php.net/bug.php?id=20993
            // To work around the issue, if there are any references, we have to recreate the array and fix the
            // references so they're properly scoped and won't affect the caller. Starting with a new array is
            // required for some unclear reason, just cloning elements of the existing array doesn't work properly.
            $old_existing_types = $existing_types;
            $existing_types = [];

            $cloned_referenceds = [];
            foreach ($existing_references as $reference => $referenced) {
                if (!isset($cloned_referenceds[$referenced])) {
                    $existing_types[$referenced] = $old_existing_types[$referenced];
                    $cloned_referenceds[$referenced] = true;
                }
                $existing_types[$reference] = &$existing_types[$referenced];
            }
            $existing_types += $old_existing_types;

            // Build a map from reference/referenced variables to other variables with the same reference
            foreach ($existing_references as $reference => $referenced) {
                $reference_graph[$reference][$referenced] = true;
                foreach ($reference_graph[$referenced] ?? [] as $existing_referenced => $_) {
                    $reference_graph[$existing_referenced][$reference] = true;
                    $reference_graph[$reference][$existing_referenced] = true;
                }
                $reference_graph[$referenced][$reference] = true;
            }
        }

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        $old_new_types = $new_types;

        $new_types = self::addNestedAssertions($new_types, $existing_types);

        // make sure array keys come after base keys
        ksort($new_types);

        $codebase = $statements_analyzer->getCodebase();

        foreach ($new_types as $key => $new_type_parts) {
            if (strpos($key, '::')
                && !strpos($key, '$')
                && !strpos($key, '[')
            ) {
                continue;
            }

            $has_negation = false;
            $has_isset = false;
            $has_inverted_isset = false;
            $has_truthy_or_falsy_or_empty = false;
            $has_empty = false;
            $has_count_check = false;
            $is_real = ($old_new_types[$key] ?? null) === $new_type_parts;
            $is_equality = $is_real;

            foreach ($new_type_parts as $new_type_part_parts) {
                foreach ($new_type_part_parts as $new_type_part_part) {
                    if ($new_type_part_part->isNegation()) {
                        $has_negation = true;
                    }

                    $has_isset = $has_isset
                        || $new_type_part_part instanceof IsIsset
                        || $new_type_part_part instanceof IsEqualIsset
                        || $new_type_part_part instanceof ArrayKeyExists
                        || $new_type_part_part instanceof HasStringArrayAccess;

                    $has_empty = $has_empty || $new_type_part_part instanceof Empty_;

                    $has_truthy_or_falsy_or_empty = $has_truthy_or_falsy_or_empty
                        || $new_type_part_part instanceof NonEmpty
                        || $new_type_part_part instanceof Truthy
                        || $new_type_part_part instanceof Empty_
                        || $new_type_part_part instanceof Falsy;

                    $is_equality = $is_equality
                        && $new_type_part_part instanceof IsIdentical;

                    $has_inverted_isset = $has_inverted_isset || $new_type_part_part instanceof IsNotIsset;

                    $has_count_check = $has_count_check
                        || $new_type_part_part instanceof NonEmptyCountable;
                }
            }

            $did_type_exist = isset($existing_types[$key]);

            $has_object_array_access = false;

            $result_type = $existing_types[$key] ?? self::getValueForKey(
                $codebase,
                $key,
                $existing_types,
                $new_types,
                $code_location,
                $has_isset,
                $has_inverted_isset,
                $has_empty,
                $inside_loop,
                $has_object_array_access,
            );

            if ($result_type && $result_type->isUnionEmpty()) {
                throw new InvalidArgumentException('Union::$types cannot be empty after get value for ' . $key);
            }

            $before_adjustment = $result_type;

            $failed_reconciliation = self::RECONCILIATION_OK;

            foreach ($new_type_parts as $offset => $new_type_part_parts) {
                $orred_type = null;

                foreach ($new_type_part_parts as $new_type_part_part) {
                    if ($new_type_part_part instanceof NestedAssertions
                        || $new_type_part_part instanceof NotNestedAssertions
                    ) {
                        $data = $new_type_part_part->assertions;

                        if ($new_type_part_part instanceof NotNestedAssertions) {
                            $nested_negated = !$negated;
                        } else {
                            $nested_negated = $negated;
                        }

                        [$existing_types, $_] = self::reconcileKeyedTypes(
                            $data,
                            $data,
                            $existing_types,
                            $existing_references,
                            $changed_var_ids,
                            $referenced_var_ids,
                            $statements_analyzer,
                            $template_type_map,
                            $inside_loop,
                            $code_location,
                            $nested_negated,
                        );

                        $new_type_part_part = $nested_negated ? new Falsy() : new Truthy();
                    }

                    $result_type_candidate = AssertionReconciler::reconcile(
                        $new_type_part_part,
                        $result_type,
                        $key,
                        $statements_analyzer,
                        $inside_loop,
                        $template_type_map,
                        $code_location
                            && isset($referenced_var_ids[$key])
                            && isset($active_new_types[$key][$offset])
                            ? $code_location
                            : null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $negated,
                    );

                    if ($result_type_candidate->isUnionEmpty()) {
                        $result_type_candidate = $result_type_candidate->getBuilder()->addType(new TNever)->freeze();
                    }

                    $orred_type = Type::combineUnionTypes(
                        $result_type_candidate,
                        $orred_type,
                        $codebase,
                    );
                }

                $result_type = $orred_type;
            }

            if (!$result_type) {
                throw new UnexpectedValueException('$result_type should not be null');
            }

            if (!$did_type_exist && $result_type->isNever()) {
                continue;
            }

            if (($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                    && (!$result_type->hasScalarType()
                        || ($result_type->hasString() && !$result_type->hasLiteralString())))
                || $statements_analyzer->data_flow_graph instanceof VariableUseGraph
            ) {
                if ($before_adjustment && $before_adjustment->parent_nodes) {
                    $result_type = $result_type->setParentNodes($before_adjustment->parent_nodes);
                } elseif (!$did_type_exist && $code_location) {
                    $result_type = $result_type->setParentNodes(
                        $statements_analyzer->getParentNodesForPossiblyUndefinedVariable(
                            $key,
                        ),
                    );
                }
            }

            if ($before_adjustment && $before_adjustment->by_ref) {
                $result_type = $result_type->setByRef(true);
            }

            $type_changed = !$before_adjustment
                || !$result_type->equals($before_adjustment)
                || $result_type->different
                || $before_adjustment->different;

            $key_parts = self::breakUpPathIntoParts($key);
            if ($type_changed || $failed_reconciliation) {
                $changed_var_ids[$key] = true;

                if (substr($key, -1) === ']' && !$has_inverted_isset && !$has_empty && !$is_equality) {
                    self::adjustTKeyedArrayType(
                        $key_parts,
                        $existing_types,
                        $changed_var_ids,
                        $result_type,
                    );
                } elseif ($key !== '$this') {
                    foreach ($existing_types as $new_key => $_) {
                        if ($new_key === $key) {
                            continue;
                        }

                        if (!isset($new_types[$new_key])
                            && preg_match('/' . preg_quote($key, '/') . '[\]\[\-]/', $new_key)
                            && $is_real
                        ) {
                            // Fix any references to the type before removing it.
                            $references_to_fix = array_keys($reference_graph[$new_key] ?? []);
                            if (count($references_to_fix) > 1) {
                                // Still multiple references, just remove $new_key
                                foreach ($references_to_fix as $reference_to_fix) {
                                    unset($reference_graph[$reference_to_fix][$new_key]);
                                }
                                // Set references pointing to $new_key to point
                                // to the first other reference from the same group
                                $new_primary_reference = key($reference_graph[$references_to_fix[0]]);
                                unset($existing_references[$new_primary_reference]);
                                foreach ($existing_references as $existing_reference => $existing_referenced) {
                                    if ($existing_referenced === $new_key) {
                                        $existing_references[$existing_reference] = $new_primary_reference;
                                    }
                                }
                            } elseif (count($references_to_fix) === 1) {
                                // Since reference target is going to be removed,
                                // pretend the reference is just a normal variable
                                $reference_to_fix = $references_to_fix[0];
                                unset($reference_graph[$reference_to_fix], $existing_references[$reference_to_fix]);
                            }
                            unset(
                                $existing_types[$new_key],
                                $reference_graph[$new_key],
                                $existing_references[$new_key],
                            );
                        }
                    }
                }
            } elseif (!$has_negation && !$has_truthy_or_falsy_or_empty && !$has_isset) {
                $changed_var_ids[$key] = true;
            }

            if ($failed_reconciliation === self::RECONCILIATION_EMPTY) {
                $result_type = $result_type->setProperties(['failed_reconciliation' => true]);
            }

            if (!$has_object_array_access) {
                $existing_types[$key] = $result_type;
            }

            if (!$did_type_exist && isset($existing_types[$key]) && isset($reference_graph[$key_parts[0]])) {
                // If key is new, create references for other variables that reference the root variable.
                $reference_key_parts = $key_parts;
                foreach ($reference_graph[$key_parts[0]] as $reference => $_) {
                    $reference_key_parts[0] = $reference;
                    $reference_key = implode("", $reference_key_parts);
                    $existing_types[$reference_key] = &$existing_types[$key];
                }
            }
        }

        return [$existing_types, $existing_references];
    }

    /**
     * This generates a list of extra assertions for an assertion on a nested key.
     *
     * For example  ['$a[0]->foo->bar' => 'isset']
     *
     * generates the assertions
     *
     * [
     *     '$a' => '=int-or-string-array-access',
     *     '$a[0]' => '=isset',
     *     '$a[0]->foo' => '=isset',
     *     '$a[0]->foo->bar' => 'isset' // original assertion
     * ]
     *
     * @param array<string, array<array<int, Assertion>>> $new_types
     * @param array<string, Union> $existing_types
     * @return array<string, array<array<int, Assertion>>>
     */
    private static function addNestedAssertions(array $new_types, array $existing_types): array
    {
        foreach ($new_types as $nk => $type) {
            if (strpos($nk, '[') || strpos($nk, '->')) {
                if ($type[0][0] instanceof IsEqualIsset
                    || $type[0][0] instanceof IsIsset
                    || $type[0][0] instanceof NonEmpty
                ) {
                    $key_parts = self::breakUpPathIntoParts($nk);

                    $base_key = array_shift($key_parts);

                    if ($base_key[0] !== '$' && count($key_parts) > 2 && $key_parts[0] === '::$') {
                        $base_key .= array_shift($key_parts);
                        $base_key .= array_shift($key_parts);
                    }

                    if (!isset($existing_types[$base_key]) || $existing_types[$base_key]->isNullable()) {
                        if (!isset($new_types[$base_key])) {
                            $new_types[$base_key] = [[new IsEqualIsset()]];
                        } else {
                            $new_types[$base_key][] = [new IsEqualIsset()];
                        }
                    }

                    while ($key_parts) {
                        $divider = array_shift($key_parts);

                        if ($divider === '[') {
                            $array_key = array_shift($key_parts);
                            array_shift($key_parts);

                            $new_base_key = $base_key . '[' . $array_key . ']';

                            if (strpos($array_key, '\'') !== false) {
                                $new_types[$base_key][] = [new HasStringArrayAccess()];
                            } else {
                                $new_types[$base_key][] = [new HasIntOrStringArrayAccess()];
                            }

                            $base_key = $new_base_key;

                            continue;
                        }

                        if ($divider === '->') {
                            $property_name = array_shift($key_parts);
                            $new_base_key = $base_key . '->' . $property_name;

                            if (!isset($new_types[$base_key])) {
                                $new_types[$base_key] = [[new IsEqualIsset()]];
                            }

                            $base_key = $new_base_key;
                        } else {
                            break;
                        }

                        if (!$key_parts) {
                            break;
                        }

                        if (!isset($new_types[$base_key])) {
                            $new_types[$base_key] = [
                                [new IsNotLooselyEqual(new TBool())],
                                [new IsNotLooselyEqual(new TInt())],
                                [new IsEqualIsset()],
                            ];
                        } else {
                            $new_types[$base_key][] = [new IsNotLooselyEqual(new TBool())];
                            $new_types[$base_key][] = [new IsNotLooselyEqual(new TInt())];
                            $new_types[$base_key][] = [new IsEqualIsset()];
                        }
                    }
                }

                if ($type[0][0] instanceof ArrayKeyExists) {
                    $key_parts = self::breakUpPathIntoParts($nk);

                    if (count($key_parts) === 4
                        && $key_parts[1] === '['
                        && $key_parts[2][0] !== '\''
                        && !is_numeric($key_parts[2])
                        && strpos($key_parts[2], '::class') === (strlen($key_parts[2])-7)
                    ) {
                        if ($key_parts[0][0] === '$') {
                            if (isset($new_types[$key_parts[0]])) {
                                $new_types[$key_parts[0]][] = [new HasArrayKey($key_parts[2])];
                            } else {
                                $new_types[$key_parts[0]] = [[new HasArrayKey($key_parts[2])]];
                            }
                        }
                    }
                }
            }
        }

        return $new_types;
    }

    /**
     * @return non-empty-list<string>
     */
    public static function breakUpPathIntoParts(string $path): array
    {
        if (isset(self::$broken_paths[$path])) {
            return self::$broken_paths[$path];
        }

        $chars = str_split($path);

        $string_char = null;
        $escape_char = false;
        $brackets = 0;

        $parts = [''];
        $parts_offset = 0;

        for ($i = 0, $char_count = count($chars); $i < $char_count; ++$i) {
            $char = $chars[$i];

            if ($string_char) {
                if ($char === $string_char && !$escape_char) {
                    $string_char = null;
                }

                if ($char === '\\') {
                    $escape_char = !$escape_char;
                }

                $parts[$parts_offset] .= $char;
                continue;
            }

            switch ($char) {
                case '[':
                case ']':
                    $parts_offset++;
                    $parts[$parts_offset] = $char;
                    ++$parts_offset;

                    if ($char === '[') {
                        $brackets++;
                    } else {
                        $brackets--;
                    }

                    continue 2;

                case '\'':
                case '"':
                    if (!isset($parts[$parts_offset])) {
                        $parts[$parts_offset] = '';
                    }
                    $parts[$parts_offset] .= $char;
                    $string_char = $char;

                    continue 2;

                case ':':
                    if (!$brackets
                        && $i < $char_count - 2
                        && $chars[$i + 1] === ':'
                        && $chars[$i + 2] === '$'
                    ) {
                        ++$i;
                        ++$i;

                        ++$parts_offset;
                        $parts[$parts_offset] = '::$';
                        ++$parts_offset;
                        continue 2;
                    }
                    // fall through

                case '-':
                    if (!$brackets
                        && $i < $char_count - 1
                        && $chars[$i + 1] === '>'
                    ) {
                        ++$i;

                        ++$parts_offset;
                        $parts[$parts_offset] = '->';
                        ++$parts_offset;
                        continue 2;
                    }
                    // fall through

                    // no break
                default:
                    if (!isset($parts[$parts_offset])) {
                        $parts[$parts_offset] = '';
                    }
                    $parts[$parts_offset] .= $char;
            }
        }

        $parts = array_values($parts);

        self::$broken_paths[$path] = $parts;

        return $parts;
    }

    /**
     * Gets the type for a given (non-existent key) based on the passed keys
     *
     * @param array<string, Union>  $existing_keys
     * @param array<string,mixed>       $new_assertions
     */
    private static function getValueForKey(
        Codebase $codebase,
        string $key,
        array &$existing_keys,
        array $new_assertions,
        ?CodeLocation $code_location,
        bool $has_isset,
        bool $has_inverted_isset,
        bool $has_empty,
        bool $inside_loop,
        bool &$has_object_array_access
    ): ?Union {
        $key_parts = self::breakUpPathIntoParts($key);

        if (count($key_parts) === 1) {
            return $existing_keys[$key_parts[0]] ?? null;
        }

        $base_key = array_shift($key_parts);

        if ($base_key[0] !== '$' && count($key_parts) > 2 && $key_parts[0] === '::$') {
            $base_key .= array_shift($key_parts);
            $base_key .= array_shift($key_parts);
        }

        if (!isset($existing_keys[$base_key])) {
            if (strpos($base_key, '::')) {
                [$fq_class_name, $const_name] = explode('::', $base_key);

                if (!$codebase->classlikes->classOrInterfaceExists($fq_class_name)) {
                    return null;
                }

                $class_constant = $codebase->classlikes->getClassConstantType(
                    $fq_class_name,
                    $const_name,
                    ReflectionProperty::IS_PRIVATE,
                    null,
                );

                if ($class_constant) {
                    $existing_keys[$base_key] = $class_constant;
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }

        while ($key_parts) {
            $divider = array_shift($key_parts);

            if ($divider === '[') {
                $array_key = array_shift($key_parts);
                array_shift($key_parts);

                $new_base_key = $base_key . '[' . $array_key . ']';

                if (!isset($existing_keys[$new_base_key])) {
                    $new_base_type = null;

                    $atomic_types = $existing_keys[$base_key]->getAtomicTypes();

                    while ($atomic_types) {
                        $existing_key_type_part = array_shift($atomic_types);

                        if ($existing_key_type_part instanceof TList) {
                            $existing_key_type_part = $existing_key_type_part->getKeyedArray();
                        }

                        if ($existing_key_type_part instanceof TTemplateParam) {
                            $atomic_types = array_merge($atomic_types, $existing_key_type_part->as->getAtomicTypes());
                            continue;
                        }

                        if ($existing_key_type_part instanceof TArray) {
                            if ($has_empty) {
                                return null;
                            }

                            $new_base_type_candidate = $existing_key_type_part->type_params[1];

                            if ($new_base_type_candidate->isMixed() && !$has_isset && !$has_inverted_isset) {
                                return $new_base_type_candidate;
                            }

                            if (($has_isset || $has_inverted_isset) && isset($new_assertions[$new_base_key])) {
                                if ($has_inverted_isset && $new_base_key === $key) {
                                    $new_base_type_candidate = $new_base_type_candidate->getBuilder();
                                    $new_base_type_candidate->addType(new TNull);
                                    $new_base_type_candidate->possibly_undefined = true;
                                    $new_base_type_candidate = $new_base_type_candidate->freeze();
                                } else {
                                    $new_base_type_candidate = $new_base_type_candidate->setPossiblyUndefined(true);
                                }
                            }
                        } elseif ($existing_key_type_part instanceof TNull
                            || $existing_key_type_part instanceof TFalse
                        ) {
                            $new_base_type_candidate = Type::getNull();

                            if ($existing_keys[$base_key]->ignore_nullable_issues) {
                                /** @psalm-suppress InaccessibleProperty We just created this type */
                                $new_base_type_candidate->ignore_nullable_issues = true;
                            }
                        } elseif ($existing_key_type_part instanceof TClassStringMap) {
                            return Type::getMixed();
                        } elseif ($existing_key_type_part instanceof TNever
                            || ($existing_key_type_part instanceof TMixed
                                && $existing_key_type_part->from_loop_isset)
                        ) {
                            return Type::getMixed($inside_loop);
                        } elseif ($existing_key_type_part instanceof TString) {
                            $new_base_type_candidate = Type::getString();
                        } elseif ($existing_key_type_part instanceof TNamedObject
                            && ($has_isset || $has_inverted_isset)
                        ) {
                            $has_object_array_access = true;

                            unset($existing_keys[$new_base_key]);

                            return null;
                        } elseif (!$existing_key_type_part instanceof TKeyedArray) {
                            return Type::getMixed();
                        } elseif ($array_key[0] === '$' || ($array_key[0] !== '\'' && !is_numeric($array_key[0]))) {
                            if ($has_empty) {
                                return null;
                            }

                            $new_base_type_candidate = $existing_key_type_part->getGenericValueType();
                        } else {
                            $array_properties = $existing_key_type_part->properties;

                            $key_parts_key = str_replace('\'', '', $array_key);

                            if (!isset($array_properties[$key_parts_key])) {
                                if ($existing_key_type_part->fallback_params !== null) {
                                    $new_base_type_candidate = $existing_key_type_part
                                        ->fallback_params[1]->setDifferent(true);
                                } else {
                                    return null;
                                }
                            } else {
                                $new_base_type_candidate = $array_properties[$key_parts_key];
                            }
                        }

                        $new_base_type = Type::combineUnionTypes(
                            $new_base_type,
                            $new_base_type_candidate,
                            $codebase,
                        );

                        $existing_keys[$new_base_key] = $new_base_type;
                    }
                }

                $base_key = $new_base_key;
            } elseif ($divider === '->' || $divider === '::$') {
                $property_name = array_shift($key_parts);
                $new_base_key = $base_key . $divider . $property_name;

                if (!isset($existing_keys[$new_base_key])) {
                    $new_base_type = null;

                    $atomic_types = $existing_keys[$base_key]->getAtomicTypes();

                    while ($atomic_types) {
                        $existing_key_type_part = array_shift($atomic_types);

                        if ($existing_key_type_part instanceof TTemplateParam) {
                            $atomic_types = array_merge($atomic_types, $existing_key_type_part->as->getAtomicTypes());
                            continue;
                        }

                        if ($existing_key_type_part instanceof TNull) {
                            $class_property_type = Type::getNull();
                        } elseif ($existing_key_type_part instanceof TMixed
                            || $existing_key_type_part instanceof TObject
                            || ($existing_key_type_part instanceof TNamedObject
                                && strtolower($existing_key_type_part->value) === 'stdclass')
                        ) {
                            $class_property_type = Type::getMixed();
                        } elseif ($existing_key_type_part instanceof TNamedObject) {
                            if (!$codebase->classOrInterfaceExists($existing_key_type_part->value)) {
                                $class_property_type = Type::getMixed();
                            } else {
                                if (substr($property_name, -2) === '()') {
                                    $method_id = new MethodIdentifier(
                                        $existing_key_type_part->value,
                                        strtolower(substr($property_name, 0, -2)),
                                    );

                                    if (!$codebase->methods->methodExists($method_id)) {
                                        return null;
                                    }

                                    $declaring_method_id = $codebase->methods->getDeclaringMethodId(
                                        $method_id,
                                    );

                                    if ($declaring_method_id === null) {
                                        return null;
                                    }

                                    $declaring_class = $declaring_method_id->fq_class_name;

                                    $method_return_type = $codebase->methods->getMethodReturnType(
                                        $method_id,
                                        $declaring_class,
                                        null,
                                        null,
                                    );

                                    if ($method_return_type) {
                                        $class_property_type = TypeExpander::expandUnion(
                                            $codebase,
                                            $method_return_type,
                                            $declaring_class,
                                            $declaring_class,
                                            null,
                                        );
                                    } else {
                                        $class_property_type = Type::getMixed();
                                    }
                                } else {
                                    $class_property_type = self::getPropertyType(
                                        $codebase,
                                        $existing_key_type_part->value,
                                        $property_name,
                                    );

                                    if (!$class_property_type) {
                                        return null;
                                    }
                                }
                            }
                        } else {
                            $class_property_type = Type::getMixed();
                        }

                        $new_base_type = Type::combineUnionTypes(
                            $new_base_type,
                            $class_property_type,
                            $codebase,
                        );

                        $existing_keys[$new_base_key] = $new_base_type;
                    }
                }

                $base_key = $new_base_key;
            } else {
                return null;
            }
        }

        if (!isset($existing_keys[$base_key])) {
            if ($code_location) {
                IssueBuffer::add(
                    new PsalmInternalError(
                        'Unknown key ' . $base_key,
                        $code_location,
                    ),
                );
            }

            return null;
        }

        return $existing_keys[$base_key];
    }

    private static function getPropertyType(
        Codebase $codebase,
        string $fq_class_name,
        string $property_name
    ): ?Union {
        $property_id = $fq_class_name . '::$' . $property_name;

        if (!$codebase->properties->propertyExists($property_id, true)) {
            $declaring_class_storage = $codebase->classlike_storage_provider->get(
                $fq_class_name,
            );

            if (isset($declaring_class_storage->pseudo_property_get_types['$' . $property_name])) {
                return $declaring_class_storage->pseudo_property_get_types['$' . $property_name];
            }

            return null;
        }

        $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
            $property_id,
            true,
        );

        if ($declaring_property_class === null) {
            return null;
        }

        $class_property_type = $codebase->properties->getPropertyType(
            $property_id,
            false,
            null,
            null,
        );

        $declaring_class_storage = $codebase->classlike_storage_provider->get(
            $declaring_property_class,
        );

        if ($class_property_type) {
            return TypeExpander::expandUnion(
                $codebase,
                $class_property_type,
                $declaring_class_storage->name,
                $declaring_class_storage->name,
                null,
            );
        }

        return Type::getMixed();
    }

    /**
     * @param Union|MutableUnion $existing_var_type
     * @param  string[]     $suppressed_issues
     */
    protected static function triggerIssueForImpossible(
        $existing_var_type,
        string $old_var_type_string,
        string $key,
        Assertion $assertion,
        bool $redundant,
        bool $negated,
        CodeLocation $code_location,
        array $suppressed_issues
    ): void {
        $assertion_string = (string)$assertion;
        $not = $assertion_string[0] === '!';

        if ($not) {
            $assertion_string = substr($assertion_string, 1);
        }

        $operator = substr($assertion_string, 0, 1);
        if ($operator === '>') {
            $assertion_string = '>= '.substr($assertion_string, 1);
        } elseif ($operator === '<') {
            $assertion_string = '<= '.substr($assertion_string, 1);
        }

        if ($negated) {
            $redundant = !$redundant;
            $not = !$not;
        }

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $from_docblock = $existing_var_type->from_docblock
            || (isset($existing_var_atomic_types[$assertion_string])
                && $existing_var_atomic_types[$assertion_string]->from_docblock);

        if ($redundant) {
            if ($existing_var_type->from_property
                && ($assertion instanceof IsIsset || $assertion instanceof IsNotIsset)
            ) {
                if ($existing_var_type->from_static_property) {
                    IssueBuffer::maybeAdd(
                        new RedundantPropertyInitializationCheck(
                            'Static property ' . $key . ' with type '
                                . $old_var_type_string
                                . ' has unexpected isset check — should it be nullable?',
                            $code_location,
                        ),
                        $suppressed_issues,
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new RedundantPropertyInitializationCheck(
                            'Property ' . $key . ' with type '
                                . $old_var_type_string . ' should already be set in the constructor',
                            $code_location,
                        ),
                        $suppressed_issues,
                    );
                }
            } elseif ($from_docblock) {
                IssueBuffer::maybeAdd(
                    new RedundantConditionGivenDocblockType(
                        'Docblock-defined type ' . $old_var_type_string
                        . ' for ' . $key
                        . ' is ' . ($not ? 'never ' : 'always ') . $assertion_string,
                        $code_location,
                        $old_var_type_string . ' ' . $assertion_string,
                    ),
                    $suppressed_issues,
                );
            } else {
                IssueBuffer::maybeAdd(
                    new RedundantCondition(
                        'Type ' . $old_var_type_string
                        . ' for ' . $key
                        . ' is ' . ($not ? 'never ' : 'always ') . $assertion_string,
                        $code_location,
                        $old_var_type_string . ' ' . $assertion_string,
                    ),
                    $suppressed_issues,
                );
            }
        } else {
            if ($from_docblock) {
                IssueBuffer::maybeAdd(
                    new DocblockTypeContradiction(
                        'Docblock-defined type ' . $old_var_type_string
                            . ' for ' . $key
                            . ' is ' . ($not ? 'always ' : 'never ') . $assertion_string,
                        $code_location,
                        $old_var_type_string . ' ' . $assertion_string,
                    ),
                    $suppressed_issues,
                );
            } else {
                if ($assertion_string === 'null' && !$not) {
                    $issue = new TypeDoesNotContainNull(
                        'Type ' . $old_var_type_string
                            . ' for ' . $key
                            . ' is never ' . $assertion_string,
                        $code_location,
                        $old_var_type_string . ' ' . $assertion_string,
                    );
                } else {
                    $issue = new TypeDoesNotContainType(
                        'Type ' . $old_var_type_string
                            . ' for ' . $key
                            . ' is ' . ($not ? 'always ' : 'never ') . $assertion,
                        $code_location,
                        $old_var_type_string . ' ' . $assertion,
                    );
                }

                IssueBuffer::maybeAdd(
                    $issue,
                    $suppressed_issues,
                );
            }
        }
    }

    /**
     * @param  string[]                  $key_parts
     * @param  array<string, Union>  $existing_types
     * @param  array<string, bool>       $changed_var_ids
     */
    private static function adjustTKeyedArrayType(
        array $key_parts,
        array &$existing_types,
        array &$changed_var_ids,
        Union $result_type
    ): void {
        array_pop($key_parts);
        $array_key = array_pop($key_parts);
        array_pop($key_parts);

        if ($array_key === null) {
            throw new UnexpectedValueException('Not expecting null array key');
        }

        if ($array_key[0] === '$') {
            return;
        }

        $array_key_offset = $array_key[0] === '\'' || $array_key[0] === '"' ? substr($array_key, 1, -1) : $array_key;

        $base_key = implode($key_parts);

        if (isset($existing_types[$base_key]) && $array_key_offset !== false) {
            foreach ($existing_types[$base_key]->getAtomicTypes() as $base_atomic_type) {
                if ($base_atomic_type instanceof TList) {
                    $base_atomic_type = $base_atomic_type->getKeyedArray();
                }
                if ($base_atomic_type instanceof TKeyedArray
                    || ($base_atomic_type instanceof TArray
                        && !$base_atomic_type->isEmptyArray())
                    || $base_atomic_type instanceof TClassStringMap
                ) {
                    $new_base_type = $existing_types[$base_key];

                    if ($base_atomic_type instanceof TArray) {
                        $fallback_key_type = $base_atomic_type->type_params[0];
                        $fallback_value_type = $base_atomic_type->type_params[1];

                        $base_atomic_type = new TKeyedArray(
                            [
                                $array_key_offset => $result_type,
                            ],
                            null,
                            $fallback_key_type->isNever() ? null : [$fallback_key_type, $fallback_value_type],
                        );
                    } elseif ($base_atomic_type instanceof TClassStringMap) {
                        // do nothing
                    } else {
                        $properties = $base_atomic_type->properties;
                        $properties[$array_key_offset] = $result_type;
                        if ($base_atomic_type->is_list
                            && (!is_numeric($array_key_offset)
                                || ($array_key_offset
                                    && !isset($properties[$array_key_offset-1])
                                )
                            )
                        ) {
                            if ($base_atomic_type->fallback_params && is_numeric($array_key_offset)) {
                                $fallback = $base_atomic_type->fallback_params[1]->setPossiblyUndefined(
                                    $result_type->isNever(),
                                );
                                for ($x = 0; $x < $array_key_offset; $x++) {
                                    $properties[$x] ??= $fallback;
                                }
                                ksort($properties);
                                $base_atomic_type = $base_atomic_type->setProperties($properties);
                            } else {
                                // This should actually be a paradox
                                $base_atomic_type = new TKeyedArray(
                                    $properties,
                                    null,
                                    $base_atomic_type->fallback_params,
                                    false,
                                    $base_atomic_type->from_docblock,
                                );
                            }
                        } else {
                            $base_atomic_type = $base_atomic_type->setProperties($properties);
                        }
                    }

                    $new_base_type = $new_base_type->getBuilder()->addType($base_atomic_type)->freeze();

                    $changed_var_ids[$base_key . '[' . $array_key . ']'] = true;

                    if ($key_parts[count($key_parts) - 1] === ']') {
                        self::adjustTKeyedArrayType(
                            $key_parts,
                            $existing_types,
                            $changed_var_ids,
                            $new_base_type,
                        );
                    }

                    $existing_types[$base_key] = $new_base_type;
                    break;
                }
            }
        }
    }

    protected static function refineArrayKey(Union $key_type): Union
    {
        return self::refineArrayKeyInner($key_type) ?? $key_type;
    }
    private static function refineArrayKeyInner(Union $key_type): ?Union
    {
        $refined = false;
        $types = [];
        foreach ($key_type->getAtomicTypes() as $cat) {
            if ($cat instanceof TTemplateParam) {
                $as = self::refineArrayKeyInner($cat->as);
                if ($as) {
                    $refined = true;
                    $types []= $cat->replaceAs($as);
                } else {
                    $types []= $cat;
                }
            } elseif ($cat instanceof TArrayKey || $cat instanceof TString || $cat instanceof TInt) {
                $types []= $cat;
            } else {
                $refined = true;
                $types []= new TArrayKey;
            }
        }

        if ($refined) {
            return $key_type->getBuilder()->setTypes($types)->freeze();
        }
        return null;
    }
}
