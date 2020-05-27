<?php
namespace Psalm\Type;

use function array_map;
use function array_pop;
use function array_shift;
use function count;
use function explode;
use function implode;
use function ksort;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Type\AssertionReconciler;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\PsalmInternalError;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\RedundantConditionGivenDocblockType;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableObjectLikeArray;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use function sort;
use function str_replace;
use function str_split;
use function strpos;
use function strtolower;
use function substr;
use Exception;

class Reconciler
{
    /** @var array<string, array<int, string>> */
    private static $broken_paths = [];

    /**
     * Takes two arrays and consolidates them, removing null values from existing types where applicable
     *
     * @param  array<string, string[][]> $new_types
     * @param  array<string, string[][]> $active_new_types - types we can complain about
     * @param  array<string, Type\Union> $existing_types
     * @param  array<string, bool>       $changed_var_ids
     * @param  array<string, bool>       $referenced_var_ids
     * @param  StatementsAnalyzer         $statements_analyzer
     * @param  CodeLocation|null         $code_location
     * @param  array<string, array<string, array{Type\Union}>> $template_type_map
     *
     * @return array<string, Type\Union>
     */
    public static function reconcileKeyedTypes(
        array $new_types,
        array $active_new_types,
        array $existing_types,
        array &$changed_var_ids,
        array $referenced_var_ids,
        StatementsAnalyzer $statements_analyzer,
        array $template_type_map = [],
        bool $inside_loop = false,
        CodeLocation $code_location = null
    ) {
        if (!$new_types) {
            return $existing_types;
        }

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        $old_new_types = $new_types;

        foreach ($new_types as $nk => $type) {
            if (strpos($nk, '[') || strpos($nk, '->')) {
                if ($type[0][0] === '=isset'
                    || $type[0][0] === '!=empty'
                    || $type[0][0] === 'isset'
                    || $type[0][0] === '!empty'
                ) {
                    $isset_or_empty = $type[0][0] === 'isset' || $type[0][0] === '=isset'
                        ? '=isset'
                        : '!=empty';

                    $key_parts = Reconciler::breakUpPathIntoParts($nk);

                    if (!$key_parts) {
                        throw new \UnexpectedValueException('There should be some key parts');
                    }

                    $base_key = array_shift($key_parts);

                    if ($base_key[0] !== '$' && count($key_parts) > 2 && $key_parts[0] === '::$') {
                        $base_key .= array_shift($key_parts);
                        $base_key .= array_shift($key_parts);
                    }

                    if (!isset($existing_types[$base_key]) || $existing_types[$base_key]->isNullable()) {
                        if (!isset($new_types[$base_key])) {
                            $new_types[$base_key] = [['=isset']];
                        } else {
                            $new_types[$base_key][] = ['=isset'];
                        }
                    }

                    while ($key_parts) {
                        $divider = array_shift($key_parts);

                        if ($divider === '[') {
                            $array_key = array_shift($key_parts);
                            array_shift($key_parts);

                            $new_base_key = $base_key . '[' . $array_key . ']';

                            if (strpos($array_key, '\'') !== false) {
                                $new_types[$base_key][] = ['=string-array-access'];
                            } else {
                                $new_types[$base_key][] = ['=int-or-string-array-access'];
                            }

                            $base_key = $new_base_key;

                            continue;
                        }

                        if ($divider === '->') {
                            $property_name = array_shift($key_parts);
                            $new_base_key = $base_key . '->' . $property_name;

                            $base_key = $new_base_key;
                        } else {
                            break;
                        }

                        if (!$key_parts) {
                            break;
                        }

                        if (!isset($new_types[$base_key])) {
                            $new_types[$base_key] = [['!~bool'], ['!~int'], ['=isset']];
                        } else {
                            $new_types[$base_key][] = ['!~bool'];
                            $new_types[$base_key][] = ['!~int'];
                            $new_types[$base_key][] = ['=isset'];
                        }
                    }

                    // replace with a less specific check
                    $new_types[$nk][0][0] = $isset_or_empty;
                }

                if ($type[0][0] === 'array-key-exists') {
                    $key_parts = Reconciler::breakUpPathIntoParts($nk);

                    if (count($key_parts) === 4 && $key_parts[1] === '[') {
                        if (isset($new_types[$key_parts[2]])) {
                            $new_types[$key_parts[2]][] = ['=in-array-' . $key_parts[0]];
                        } else {
                            $new_types[$key_parts[2]] = [['=in-array-' . $key_parts[0]]];
                        }

                        if ($key_parts[0][0] === '$') {
                            if (isset($new_types[$key_parts[0]])) {
                                $new_types[$key_parts[0]][] = ['=has-array-key-' . $key_parts[2]];
                            } else {
                                $new_types[$key_parts[0]] = [['=has-array-key-' . $key_parts[2]]];
                            }
                        }
                    }
                }
            }
        }

        // make sure array keys come after base keys
        ksort($new_types);

        $codebase = $statements_analyzer->getCodebase();

        foreach ($new_types as $key => $new_type_parts) {
            $has_negation = false;
            $has_isset = false;
            $has_inverted_isset = false;
            $has_falsyish = false;
            $has_empty = false;
            $has_count_check = false;
            $is_equality = ($old_new_types[$key] ?? null) === $new_type_parts;

            foreach ($new_type_parts as $new_type_part_parts) {
                foreach ($new_type_part_parts as $new_type_part_part) {
                    switch ($new_type_part_part[0]) {
                        case '!':
                            $has_negation = true;
                            break;
                    }

                    $has_isset = $has_isset
                        || $new_type_part_part === 'isset'
                        || $new_type_part_part === '=isset'
                        || $new_type_part_part === 'array-key-exists'
                        || $new_type_part_part === '=string-array-access';

                    $has_empty = $has_empty || $new_type_part_part === 'empty';

                    $has_falsyish = $has_falsyish
                        || $new_type_part_part === 'empty'
                        || $new_type_part_part === 'falsy';

                    $is_equality = $is_equality
                        && $new_type_part_part[0] === '='
                        && $new_type_part_part !== '=isset';

                    $has_inverted_isset = $has_inverted_isset || $new_type_part_part === '!isset';

                    $has_count_check = $has_count_check
                        || $new_type_part_part === 'non-empty-countable';
                }
            }

            $did_type_exist = isset($existing_types[$key]);

            $has_object_array_access = false;

            $result_type = isset($existing_types[$key])
                ? clone $existing_types[$key]
                : self::getValueForKey(
                    $codebase,
                    $key,
                    $existing_types,
                    $new_types,
                    $code_location,
                    $has_isset,
                    $has_inverted_isset,
                    $has_empty,
                    $inside_loop,
                    $has_object_array_access
                );

            if ($result_type && empty($result_type->getAtomicTypes())) {
                throw new \InvalidArgumentException('Union::$types cannot be empty after get value for ' . $key);
            }

            $before_adjustment = $result_type ? clone $result_type : null;

            $failed_reconciliation = 0;

            foreach ($new_type_parts as $offset => $new_type_part_parts) {
                $orred_type = null;

                foreach ($new_type_part_parts as $new_type_part_part) {
                    $result_type_candidate = AssertionReconciler::reconcile(
                        $new_type_part_part,
                        $result_type ? clone $result_type : null,
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
                        $failed_reconciliation
                    );

                    if (!$result_type_candidate->getAtomicTypes()) {
                        $result_type_candidate->addType(new TEmpty);
                    }

                    $orred_type = $orred_type
                        ? Type::combineUnionTypes(
                            $result_type_candidate,
                            $orred_type,
                            $codebase
                        )
                        : $result_type_candidate;
                }

                $result_type = $orred_type;
            }

            if (!$result_type) {
                throw new \UnexpectedValueException('$result_type should not be null');
            }

            if (!$did_type_exist && $result_type->isEmpty()) {
                continue;
            }

            $type_changed = !$before_adjustment || !$result_type->equals($before_adjustment);

            if ($type_changed || $failed_reconciliation) {
                $changed_var_ids[$key] = true;

                if (substr($key, -1) === ']' && !$has_inverted_isset && !$has_empty && !$is_equality) {
                    $key_parts = self::breakUpPathIntoParts($key);
                    self::adjustObjectLikeType(
                        $key_parts,
                        $existing_types,
                        $changed_var_ids,
                        $result_type
                    );
                }
            } elseif (!$has_negation && !$has_falsyish && !$has_isset) {
                $changed_var_ids[$key] = true;
            }

            if ($failed_reconciliation === 2) {
                $result_type->failed_reconciliation = true;
            }

            if (!$has_object_array_access) {
                $existing_types[$key] = $result_type;
            }
        }

        return $existing_types;
    }

    /**
     * @param  string $path
     *
     * @return array<int, string>
     */
    public static function breakUpPathIntoParts($path)
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

        $parts = \array_values($parts);

        self::$broken_paths[$path] = $parts;

        return $parts;
    }

    /**
     * Gets the type for a given (non-existent key) based on the passed keys
     *
     * @param  string                    $key
     * @param  array<string,Type\Union>  $existing_keys
     * @param  array<string,mixed>       $new_assertions
     * @param  string[][]                $new_type_parts
     *
     * @return Type\Union|null
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
    ) {
        $key_parts = self::breakUpPathIntoParts($key);

        if (count($key_parts) === 1) {
            return isset($existing_keys[$key_parts[0]]) ? clone $existing_keys[$key_parts[0]] : null;
        }

        $base_key = array_shift($key_parts);

        if ($base_key[0] !== '$' && count($key_parts) > 2 && $key_parts[0] === '::$') {
            $base_key .= array_shift($key_parts);
            $base_key .= array_shift($key_parts);
        }

        if (!isset($existing_keys[$base_key])) {
            if (strpos($base_key, '::')) {
                list($fq_class_name, $const_name) = explode('::', $base_key);

                $class_constant = $codebase->classlikes->getConstantForClass(
                    $fq_class_name,
                    $const_name,
                    \ReflectionProperty::IS_PRIVATE,
                    null
                );

                if ($class_constant) {
                    $existing_keys[$base_key] = clone $class_constant;
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

                    foreach ($existing_keys[$base_key]->getAtomicTypes() as $existing_key_type_part) {
                        if ($existing_key_type_part instanceof Type\Atomic\TArray) {
                            if ($has_empty) {
                                return null;
                            }

                            $new_base_type_candidate = clone $existing_key_type_part->type_params[1];

                            if ($new_base_type_candidate->isMixed() && !$has_isset && !$has_inverted_isset) {
                                return $new_base_type_candidate;
                            }

                            if (($has_isset || $has_inverted_isset) && isset($new_assertions[$new_base_key])) {
                                if ($has_inverted_isset && $new_base_key === $key) {
                                    $new_base_type_candidate->addType(new Type\Atomic\TNull);
                                }

                                $new_base_type_candidate->possibly_undefined = true;
                            }
                        } elseif ($existing_key_type_part instanceof Type\Atomic\TList) {
                            if ($has_empty) {
                                return null;
                            }

                            $new_base_type_candidate = clone $existing_key_type_part->type_param;

                            if (($has_isset || $has_inverted_isset) && isset($new_assertions[$new_base_key])) {
                                if ($has_inverted_isset && $new_base_key === $key) {
                                    $new_base_type_candidate->addType(new Type\Atomic\TNull);
                                }

                                $new_base_type_candidate->possibly_undefined = true;
                            }
                        } elseif ($existing_key_type_part instanceof Type\Atomic\TNull
                            || $existing_key_type_part instanceof Type\Atomic\TFalse
                        ) {
                            $new_base_type_candidate = Type::getNull();

                            if ($existing_keys[$base_key]->ignore_nullable_issues) {
                                $new_base_type_candidate->ignore_nullable_issues = true;
                            }
                        } elseif ($existing_key_type_part instanceof Type\Atomic\TClassStringMap) {
                            return Type::getMixed();
                        } elseif ($existing_key_type_part instanceof Type\Atomic\TEmpty
                            || ($existing_key_type_part instanceof Type\Atomic\TMixed
                                && $existing_key_type_part->from_loop_isset)
                        ) {
                            return Type::getMixed($inside_loop);
                        } elseif ($existing_key_type_part instanceof TString) {
                            $new_base_type_candidate = Type::getString();
                        } elseif ($existing_key_type_part instanceof Type\Atomic\TNamedObject
                            && ($has_isset || $has_inverted_isset)
                        ) {
                            $has_object_array_access = true;
                            return null;
                        } elseif (!$existing_key_type_part instanceof Type\Atomic\ObjectLike) {
                            return Type::getMixed();
                        } elseif ($array_key[0] === '$' || ($array_key[0] !== '\'' && !\is_numeric($array_key[0]))) {
                            if ($has_empty) {
                                return null;
                            }

                            $new_base_type_candidate = $existing_key_type_part->getGenericValueType();
                        } else {
                            $array_properties = $existing_key_type_part->properties;

                            $key_parts_key = str_replace('\'', '', $array_key);

                            if (!isset($array_properties[$key_parts_key])) {
                                if ($existing_key_type_part->previous_value_type) {
                                    $new_base_type_candidate = clone $existing_key_type_part->previous_value_type;
                                    $new_base_type_candidate->different = true;
                                } else {
                                    return null;
                                }
                            } else {
                                $new_base_type_candidate = clone $array_properties[$key_parts_key];
                            }
                        }

                        if (!$new_base_type) {
                            $new_base_type = $new_base_type_candidate;
                        } else {
                            $new_base_type = Type::combineUnionTypes(
                                $new_base_type,
                                $new_base_type_candidate,
                                $codebase
                            );
                        }
                    }

                    $existing_keys[$new_base_key] = $new_base_type;
                }

                $base_key = $new_base_key;
            } elseif ($divider === '->' || $divider === '::$') {
                $property_name = array_shift($key_parts);
                $new_base_key = $base_key . $divider . $property_name;

                if (!isset($existing_keys[$new_base_key])) {
                    $new_base_type = null;

                    foreach ($existing_keys[$base_key]->getAtomicTypes() as $existing_key_type_part) {
                        if ($existing_key_type_part instanceof TNull) {
                            $class_property_type = Type::getNull();
                        } elseif ($existing_key_type_part instanceof TMixed
                            || $existing_key_type_part instanceof TTemplateParam
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
                                    $method_id = new \Psalm\Internal\MethodIdentifier(
                                        $existing_key_type_part->value,
                                        strtolower(substr($property_name, 0, -2))
                                    );

                                    if (!$codebase->methods->methodExists($method_id)) {
                                        return null;
                                    }

                                    $declaring_method_id = $codebase->methods->getDeclaringMethodId(
                                        $method_id
                                    );

                                    if ($declaring_method_id === null) {
                                        return null;
                                    }

                                    $declaring_class = $declaring_method_id->fq_class_name;

                                    $method_return_type = $codebase->methods->getMethodReturnType(
                                        $method_id,
                                        $declaring_class,
                                        null,
                                        null
                                    );

                                    if ($method_return_type) {
                                        $class_property_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                                            $codebase,
                                            clone $method_return_type,
                                            $declaring_class,
                                            $declaring_class,
                                            null
                                        );
                                    } else {
                                        $class_property_type = Type::getMixed();
                                    }
                                } else {
                                    $property_id = $existing_key_type_part->value . '::$' . $property_name;

                                    if (!$codebase->properties->propertyExists($property_id, true)) {
                                        return null;
                                    }

                                    $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
                                        $property_id,
                                        true
                                    );

                                    if ($declaring_property_class === null) {
                                        return null;
                                    }

                                    $class_property_type = $codebase->properties->getPropertyType(
                                        $property_id,
                                        false,
                                        null,
                                        null
                                    );

                                    $declaring_class_storage = $codebase->classlike_storage_provider->get(
                                        $declaring_property_class
                                    );

                                    if ($class_property_type) {
                                        $class_property_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                                            $codebase,
                                            clone $class_property_type,
                                            $declaring_class_storage->name,
                                            $declaring_class_storage->name,
                                            null
                                        );
                                    } else {
                                        $class_property_type = Type::getMixed();
                                    }
                                }
                            }
                        } else {
                            $class_property_type = Type::getMixed();
                        }

                        if ($new_base_type instanceof Type\Union) {
                            $new_base_type = Type::combineUnionTypes(
                                $new_base_type,
                                $class_property_type,
                                $codebase
                            );
                        } else {
                            $new_base_type = $class_property_type;
                        }

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
                        $code_location
                    )
                );
            }

            return null;
        }

        return $existing_keys[$base_key];
    }

    /**
     * @param  string       $key
     * @param  string       $old_var_type_string
     * @param  string       $assertion
     * @param  bool         $redundant
     * @param  string[]     $suppressed_issues
     *
     * @return void
     */
    protected static function triggerIssueForImpossible(
        Union $existing_var_type,
        string $old_var_type_string,
        string $key,
        string $assertion,
        bool $redundant,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $reconciliation = ' and trying to reconcile type \'' . $old_var_type_string . '\' to ' . $assertion;

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $from_docblock = $existing_var_type->from_docblock
            || (isset($existing_var_atomic_types[$assertion])
                && $existing_var_atomic_types[$assertion]->from_docblock);

        if ($redundant) {
            if ($from_docblock) {
                if (IssueBuffer::accepts(
                    new RedundantConditionGivenDocblockType(
                        'Found a redundant condition when evaluating docblock-defined type '
                            . $key . $reconciliation,
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new RedundantCondition(
                        'Found a redundant condition when evaluating ' . $key . $reconciliation,
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    // fall through
                }
            }
        } else {
            if ($from_docblock) {
                if (IssueBuffer::accepts(
                    new DocblockTypeContradiction(
                        'Found a contradiction with a docblock-defined type '
                            . 'when evaluating ' . $key . $reconciliation,
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new TypeDoesNotContainType(
                        'Found a contradiction when evaluating ' . $key . $reconciliation,
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    // fall through
                }
            }
        }
    }

    /**
     * @param  string[]                  $key_parts
     * @param  array<string,Type\Union>  $existing_types
     * @param  array<string, bool>       $changed_var_ids
     *
     * @return void
     */
    private static function adjustObjectLikeType(
        array $key_parts,
        array &$existing_types,
        array &$changed_var_ids,
        Type\Union $result_type
    ) {
        array_pop($key_parts);
        $array_key = array_pop($key_parts);
        array_pop($key_parts);

        if ($array_key === null) {
            throw new \UnexpectedValueException('Not expecting null array key');
        }

        if ($array_key[0] === '$') {
            return;
        }

        $array_key_offset = $array_key[0] === '\'' || $array_key[0] === '"' ? substr($array_key, 1, -1) : $array_key;

        $base_key = implode($key_parts);

        if (isset($existing_types[$base_key]) && $array_key_offset !== false) {
            foreach ($existing_types[$base_key]->getAtomicTypes() as $base_atomic_type) {
                if ($base_atomic_type instanceof Type\Atomic\ObjectLike
                    || ($base_atomic_type instanceof Type\Atomic\TArray
                        && !$base_atomic_type->type_params[1]->isEmpty())
                    || $base_atomic_type instanceof Type\Atomic\TList
                    || $base_atomic_type instanceof Type\Atomic\TClassStringMap
                ) {
                    $new_base_type = clone $existing_types[$base_key];

                    if ($base_atomic_type instanceof Type\Atomic\TArray) {
                        $previous_key_type = clone $base_atomic_type->type_params[0];
                        $previous_value_type = clone $base_atomic_type->type_params[1];

                        $base_atomic_type = new Type\Atomic\ObjectLike(
                            [
                                $array_key_offset => clone $result_type,
                            ],
                            null
                        );

                        if (!$previous_key_type->isEmpty()) {
                            $base_atomic_type->previous_key_type = $previous_key_type;
                        }
                        $base_atomic_type->previous_value_type = $previous_value_type;
                    } elseif ($base_atomic_type instanceof Type\Atomic\TList) {
                        $previous_key_type = Type::getInt();
                        $previous_value_type = clone $base_atomic_type->type_param;

                        $base_atomic_type = new Type\Atomic\ObjectLike(
                            [
                                $array_key_offset => clone $result_type,
                            ],
                            null
                        );

                        $base_atomic_type->is_list = true;

                        $base_atomic_type->previous_key_type = $previous_key_type;
                        $base_atomic_type->previous_value_type = $previous_value_type;
                    } elseif ($base_atomic_type instanceof Type\Atomic\TClassStringMap) {
                        // do nothing
                    } else {
                        $base_atomic_type = clone $base_atomic_type;
                        $base_atomic_type->properties[$array_key_offset] = clone $result_type;
                    }

                    $new_base_type->addType($base_atomic_type);

                    $changed_var_ids[$base_key . '[' . $array_key . ']'] = true;

                    if ($key_parts[count($key_parts) - 1] === ']') {
                        self::adjustObjectLikeType(
                            $key_parts,
                            $existing_types,
                            $changed_var_ids,
                            $new_base_type
                        );
                    }

                    $existing_types[$base_key] = $new_base_type;
                    break;
                }
            }
        }
    }
}
