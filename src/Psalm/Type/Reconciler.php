<?php
namespace Psalm\Type;

use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\Issue\PsalmInternalError;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\RedundantConditionGivenDocblockType;
use Psalm\Issue\TypeDoesNotContainNull;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TGenericParam;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;

class Reconciler
{
    /** @var array<string, array<int, string>> */
    private static $broken_paths = [];

    /**
     * Takes two arrays and consolidates them, removing null values from existing types where applicable
     *
     * @param  array<string, string[][]> $new_types
     * @param  array<string, Type\Union> $existing_types
     * @param  array<string>             $changed_var_ids
     * @param  array<string, bool>       $referenced_var_ids
     * @param  StatementsAnalyzer         $statements_analyzer
     * @param  CodeLocation|null         $code_location
     * @param  array<string>             $suppressed_issues
     *
     * @return array<string, Type\Union>
     */
    public static function reconcileKeyedTypes(
        array $new_types,
        array $existing_types,
        array &$changed_var_ids,
        array $referenced_var_ids,
        StatementsAnalyzer $statements_analyzer,
        bool $inside_loop = false,
        CodeLocation $code_location = null
    ) {
        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        foreach ($new_types as $nk => $type) {
            if ((strpos($nk, '[') || strpos($nk, '->'))
                && ($type[0][0] === '=isset'
                    || $type[0][0] === '!=empty'
                    || $type[0][0] === 'isset'
                    || $type[0][0] === '!empty')
            ) {
                $isset_or_empty = $type[0][0] === 'isset' || $type[0][0] === '=isset'
                    ? '=isset'
                    : '!=empty';

                $key_parts = Reconciler::breakUpPathIntoParts($nk);

                $base_key = array_shift($key_parts);

                if (!isset($new_types[$base_key])) {
                    $new_types[$base_key] = [['!=bool'], ['!=int'], ['=isset']];
                } else {
                    $new_types[$base_key][] = ['!=bool'];
                    $new_types[$base_key][] = ['!=int'];
                    $new_types[$base_key][] = ['=isset'];
                }

                while ($key_parts) {
                    $divider = array_shift($key_parts);

                    if ($divider === '[') {
                        $array_key = array_shift($key_parts);
                        array_shift($key_parts);

                        $new_base_key = $base_key . '[' . $array_key . ']';

                        $base_key = $new_base_key;
                    } elseif ($divider === '->') {
                        $property_name = array_shift($key_parts);
                        $new_base_key = $base_key . '->' . $property_name;

                        $base_key = $new_base_key;
                    } else {
                        throw new \InvalidArgumentException('Unexpected divider ' . $divider);
                    }

                    if (!$key_parts) {
                        break;
                    }

                    if (!isset($new_types[$base_key])) {
                        $new_types[$base_key] = [['!=bool'], ['!=int'], ['=isset']];
                    } else {
                        $new_types[$base_key][] = ['!=bool'];
                        $new_types[$base_key][] = ['!=int'];
                        $new_types[$base_key][] = ['=isset'];
                    }
                }

                // replace with a less specific check
                $new_types[$nk][0][0] = $isset_or_empty;
            }
        }

        // make sure array keys come after base keys
        ksort($new_types);

        if (empty($new_types)) {
            return $existing_types;
        }

        $codebase = $statements_analyzer->getCodebase();

        foreach ($new_types as $key => $new_type_parts) {
            $result_type = isset($existing_types[$key])
                ? clone $existing_types[$key]
                : self::getValueForKey($codebase, $key, $existing_types, $code_location);

            if ($result_type && empty($result_type->getTypes())) {
                throw new \InvalidArgumentException('Union::$types cannot be empty after get value for ' . $key);
            }

            $before_adjustment = $result_type ? clone $result_type : null;

            $failed_reconciliation = false;
            $has_negation = false;
            $has_equality = false;
            $has_isset = false;

            foreach ($new_type_parts as $new_type_part_parts) {
                $orred_type = null;

                foreach ($new_type_part_parts as $new_type_part_part) {
                    switch ($new_type_part_part[0]) {
                        case '!':
                            $has_negation = true;
                            break;
                        case '=':
                        case '~':
                            $has_equality = true;
                    }

                    $has_isset = $has_isset
                        || $new_type_part_part === 'isset'
                        || $new_type_part_part === 'array-key-exists';

                    $result_type_candidate = self::reconcileTypes(
                        $new_type_part_part,
                        $result_type ? clone $result_type : null,
                        $key,
                        $statements_analyzer,
                        $inside_loop,
                        $code_location && isset($referenced_var_ids[$key]) ? $code_location : null,
                        $suppressed_issues,
                        $failed_reconciliation
                    );

                    if (!$result_type_candidate->getTypes()) {
                        $result_type_candidate->addType(new TEmpty);
                    }

                    $orred_type = $orred_type
                        ? Type::combineUnionTypes($result_type_candidate, $orred_type)
                        : $result_type_candidate;
                }

                $result_type = $orred_type;
            }

            if (!$result_type) {
                throw new \UnexpectedValueException('$result_type should not be null');
            }

            $type_changed = !$before_adjustment || !$result_type->equals($before_adjustment);

            if ($type_changed || $failed_reconciliation) {
                $changed_var_ids[] = $key;

                if (substr($key, -1) === ']') {
                    $key_parts = self::breakUpPathIntoParts($key);
                    self::adjustObjectLikeType(
                        $key_parts,
                        $existing_types,
                        $changed_var_ids,
                        $result_type
                    );
                }
            } elseif ($code_location
                && isset($referenced_var_ids[$key])
                && !$has_negation
                && !$has_equality
                && !$result_type->hasMixed()
                && (!$has_isset || substr($key, -1, 1) !== ']')
            ) {
                $reconcile_key = implode(
                    '&',
                    array_map(
                        /**
                         * @return string
                         */
                        function (array $new_type_part_parts) {
                            return implode('|', $new_type_part_parts);
                        },
                        $new_type_parts
                    )
                );

                self::triggerIssueForImpossible(
                    $result_type,
                    $before_adjustment ? $before_adjustment->getId() : '',
                    $key,
                    $reconcile_key,
                    !$type_changed,
                    $code_location,
                    $suppressed_issues
                );
            }

            if ($failed_reconciliation) {
                $result_type->failed_reconciliation = true;
            }

            $existing_types[$key] = $result_type;
        }

        return $existing_types;
    }

    /**
     * Reconciles types
     *
     * think of this as a set of functions e.g. empty(T), notEmpty(T), null(T), notNull(T) etc. where
     *  - empty(Object) => null,
     *  - empty(bool) => false,
     *  - notEmpty(Object|null) => Object,
     *  - notEmpty(Object|false) => Object
     *
     * @param   string              $new_var_type
     * @param   Type\Union|null     $existing_var_type
     * @param   string|null         $key
     * @param   StatementsAnalyzer   $statements_analyzer
     * @param   CodeLocation        $code_location
     * @param   string[]            $suppressed_issues
     * @param   bool                $failed_reconciliation if the types cannot be reconciled, we need to know
     *
     * @return  Type\Union
     */
    public static function reconcileTypes(
        $new_var_type,
        $existing_var_type,
        $key,
        StatementsAnalyzer $statements_analyzer,
        bool $inside_loop,
        CodeLocation $code_location = null,
        array $suppressed_issues = [],
        &$failed_reconciliation = false
    ) {
        $codebase = $statements_analyzer->getCodebase();

        $is_strict_equality = false;
        $is_loose_equality = false;
        $is_equality = false;
        $is_negation = false;

        if ($new_var_type[0] === '!') {
            $new_var_type = substr($new_var_type, 1);
            $is_negation = true;
        }

        if ($new_var_type[0] === '=') {
            $new_var_type = substr($new_var_type, 1);
            $is_strict_equality = true;
            $is_equality = true;
        }

        if ($new_var_type[0] === '~') {
            $new_var_type = substr($new_var_type, 1);
            $is_loose_equality = true;
            $is_equality = true;
        }

        if ($existing_var_type === null) {
            if (($new_var_type === 'isset' && !$is_negation)
                || ($new_var_type === 'empty' && $is_negation)
            ) {
                return Type::getMixed($inside_loop);
            }

            if ($new_var_type === 'array-key-exists') {
                return Type::getMixed();
            }

            if (!$is_negation && $new_var_type !== 'falsy' && $new_var_type !== 'empty') {
                if ($is_equality) {
                    $bracket_pos = strpos($new_var_type, '(');

                    if ($bracket_pos) {
                        $new_var_type = substr($new_var_type, 0, $bracket_pos);
                    }
                }

                return Type::parseString($new_var_type);
            }

            return Type::getMixed();
        }

        $old_var_type_string = $existing_var_type->getId();

        if ($is_negation) {
            return self::handleNegatedType(
                $statements_analyzer,
                $new_var_type,
                $is_strict_equality,
                $is_loose_equality,
                $existing_var_type,
                $old_var_type_string,
                $key,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation
            );
        }

        if ($new_var_type === 'mixed' && $existing_var_type->hasMixed()) {
            return $existing_var_type;
        }

        if ($new_var_type === 'isset') {
            $existing_var_type->removeType('null');

            if (empty($existing_var_type->getTypes())) {
                $failed_reconciliation = true;

                // @todo - I think there's a better way to handle this, but for the moment
                // mixed will have to do.
                return Type::getMixed();
            }

            if ($existing_var_type->hasType('empty')) {
                $existing_var_type->removeType('empty');
                $existing_var_type->addType(new TMixed($inside_loop));
            }

            $existing_var_type->possibly_undefined = false;
            $existing_var_type->possibly_undefined_from_try = false;

            return $existing_var_type;
        }

        if ($new_var_type === 'array-key-exists') {
            $existing_var_type->possibly_undefined = false;

            return $existing_var_type;
        }

        $existing_var_atomic_types = $existing_var_type->getTypes();

        if ($new_var_type === 'falsy' || $new_var_type === 'empty') {
            $did_remove_type = $existing_var_type->hasDefinitelyNumericType(false);

            if ($existing_var_type->hasMixed()) {
                if ($existing_var_type->isMixed()
                    && $existing_var_atomic_types['mixed'] instanceof Type\Atomic\TNonEmptyMixed
                ) {
                    if ($code_location
                        && $key
                        && IssueBuffer::accepts(
                            new ParadoxicalCondition(
                                'Found a paradox when evaluating ' . $key
                                    . ' and trying to reconcile it with a ' . $new_var_type . ' assertion',
                                $code_location
                            ),
                            $suppressed_issues
                        )
                    ) {
                        // fall through
                    }

                    return Type::getMixed();
                }

                if (!$existing_var_atomic_types['mixed'] instanceof Type\Atomic\TEmptyMixed) {
                    $did_remove_type = true;
                    $existing_var_type->removeType('mixed');

                    if (!$existing_var_atomic_types['mixed'] instanceof Type\Atomic\TNonEmptyMixed) {
                        $existing_var_type->addType(new Type\Atomic\TEmptyMixed);
                    }
                } elseif ($existing_var_type->isMixed()) {
                    if ($code_location
                        && $key
                        && IssueBuffer::accepts(
                            new RedundantCondition(
                                'Found a redundant condition when evaluating ' . $key
                                    . ' and trying to reconcile it with a ' . $new_var_type . ' assertion',
                                $code_location
                            ),
                            $suppressed_issues
                        )
                    ) {
                        // fall through
                    }
                }

                if ($existing_var_type->isMixed()) {
                    return $existing_var_type;
                }
            }

            if ($existing_var_type->hasType('bool')) {
                $did_remove_type = true;
                $existing_var_type->removeType('bool');
                $existing_var_type->addType(new TFalse);
            }

            if ($existing_var_type->hasType('true')) {
                $did_remove_type = true;
                $existing_var_type->removeType('true');
            }

            if ($existing_var_type->hasString()) {
                $existing_string_types = $existing_var_type->getLiteralStrings();

                if ($existing_string_types) {
                    foreach ($existing_string_types as $string_key => $literal_type) {
                        if ($literal_type->value) {
                            $existing_var_type->removeType($string_key);
                            $did_remove_type = true;
                        }
                    }
                } else {
                    $did_remove_type = true;
                    if ($existing_var_type->hasType('class-string')) {
                        $existing_var_type->removeType('class-string');
                    }

                    if ($existing_var_type->hasType('string')) {
                        $existing_var_type->removeType('string');
                        $existing_var_type->addType(new Type\Atomic\TLiteralString(''));
                        $existing_var_type->addType(new Type\Atomic\TLiteralString('0'));
                    }
                }
            }

            if ($existing_var_type->hasInt()) {
                $existing_int_types = $existing_var_type->getLiteralInts();

                if ($existing_int_types) {
                    foreach ($existing_int_types as $int_key => $literal_type) {
                        if ($literal_type->value) {
                            $existing_var_type->removeType($int_key);
                            $did_remove_type = true;
                        }
                    }
                } else {
                    $did_remove_type = true;
                    $existing_var_type->removeType('int');
                    $existing_var_type->addType(new Type\Atomic\TLiteralInt(0));
                }
            }

            if ($existing_var_type->hasFloat()) {
                $existing_float_types = $existing_var_type->getLiteralFloats();

                if ($existing_float_types) {
                    foreach ($existing_float_types as $float_key => $literal_type) {
                        if ($literal_type->value) {
                            $existing_var_type->removeType($float_key);
                            $did_remove_type = true;
                        }
                    }
                } else {
                    $did_remove_type = true;
                    $existing_var_type->removeType('float');
                    $existing_var_type->addType(new Type\Atomic\TLiteralFloat(0));
                }
            }

            if (isset($existing_var_atomic_types['array'])) {
                $array_atomic_type = $existing_var_atomic_types['array'];

                if ($array_atomic_type instanceof Type\Atomic\TNonEmptyArray
                    || ($array_atomic_type instanceof Type\Atomic\ObjectLike && $array_atomic_type->sealed)
                ) {
                    $did_remove_type = true;

                    $existing_var_type->removeType('array');
                } elseif ($array_atomic_type->getId() !== 'array<empty, empty>') {
                    $did_remove_type = true;

                    $existing_var_type->addType(new TArray(
                        [
                            new Type\Union([new TEmpty]),
                            new Type\Union([new TEmpty]),
                        ]
                    ));
                }
            }

            if (isset($existing_var_atomic_types['scalar'])
                && $existing_var_atomic_types['scalar']->getId() !== 'empty-scalar'
            ) {
                $did_remove_type = true;
                $existing_var_type->addType(new Type\Atomic\TEmptyScalar);
            }

            foreach ($existing_var_atomic_types as $type_key => $type) {
                if ($type instanceof TNamedObject
                    || $type instanceof TObject
                    || $type instanceof TResource
                    || $type instanceof TCallable
                ) {
                    $did_remove_type = true;

                    $existing_var_type->removeType($type_key);
                }
            }

            if (!$did_remove_type || empty($existing_var_type->getTypes())) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $new_var_type,
                        !$did_remove_type,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            if ($existing_var_type->getTypes()) {
                return $existing_var_type;
            }

            $failed_reconciliation = true;

            return Type::getMixed();
        }

        if ($new_var_type === 'object' && !$existing_var_type->hasMixed()) {
            $object_types = [];
            $did_remove_type = false;

            foreach ($existing_var_atomic_types as $type) {
                if ($type->isObjectType()) {
                    $object_types[] = $type;
                } else {
                    $did_remove_type = true;
                }
            }

            if ((!$object_types || !$did_remove_type) && !$is_equality) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $new_var_type,
                        !$did_remove_type,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            if ($object_types) {
                return new Type\Union($object_types);
            }

            $failed_reconciliation = true;

            return Type::getMixed();
        }

        if ($new_var_type === 'numeric' && !$existing_var_type->hasMixed()) {
            $numeric_types = [];
            $did_remove_type = false;

            if ($existing_var_type->hasString()) {
                $did_remove_type = true;
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TNumericString);
            }

            foreach ($existing_var_type->getTypes() as $type) {
                if ($type instanceof TNumeric || $type instanceof TNumericString) {
                    // this is a workaround for a possible issue running
                    // is_numeric($a) && is_string($a)
                    $did_remove_type = true;
                    $numeric_types[] = $type;
                } elseif ($type->isNumericType()) {
                    $numeric_types[] = $type;
                } elseif ($type instanceof TScalar) {
                    $did_remove_type = true;
                    $numeric_types[] = new TNumeric();
                } else {
                    $did_remove_type = true;
                }
            }

            if ((!$did_remove_type || !$numeric_types) && !$is_equality) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $new_var_type,
                        !$did_remove_type,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            if ($numeric_types) {
                return new Type\Union($numeric_types);
            }

            $failed_reconciliation = true;

            return Type::getMixed();
        }

        if ($new_var_type === 'scalar' && !$existing_var_type->hasMixed()) {
            $scalar_types = [];
            $did_remove_type = false;

            foreach ($existing_var_atomic_types as $type) {
                if ($type instanceof Scalar) {
                    $scalar_types[] = $type;
                } else {
                    $did_remove_type = true;
                }
            }

            if ((!$did_remove_type || !$scalar_types) && !$is_equality) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $new_var_type,
                        !$did_remove_type,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            if ($scalar_types) {
                return new Type\Union($scalar_types);
            }

            $failed_reconciliation = true;

            return Type::getMixed();
        }

        if ($new_var_type === 'bool' && !$existing_var_type->hasMixed()) {
            $bool_types = [];
            $did_remove_type = false;

            foreach ($existing_var_atomic_types as $type) {
                if ($type instanceof TBool) {
                    $bool_types[] = $type;
                } elseif ($type instanceof TScalar) {
                    $bool_types[] = new TBool;
                    $did_remove_type = true;
                } else {
                    $did_remove_type = true;
                }
            }

            if ((!$did_remove_type || !$bool_types) && !$is_equality) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $new_var_type,
                        !$did_remove_type,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            if ($bool_types) {
                return new Type\Union($bool_types);
            }

            $failed_reconciliation = true;

            return Type::getMixed();
        }

        if (isset($existing_var_atomic_types['int'])
            && $existing_var_type->from_calculation
            && ($new_var_type === 'int' || $new_var_type === 'float')
        ) {
            if ($new_var_type === 'int') {
                return Type::getInt();
            }

            return Type::getFloat();
        }

        if (substr($new_var_type, 0, 4) === 'isa-') {
            if ($existing_var_type->hasMixed()) {
                return Type::getMixed();
            }

            $new_var_type = substr($new_var_type, 4);

            $existing_has_object = $existing_var_type->hasObjectType();
            $existing_has_string = $existing_var_type->hasString();

            if ($existing_has_object && !$existing_has_string) {
                $new_type = Type::parseString($new_var_type);
            } elseif ($existing_has_string && !$existing_has_object) {
                $new_type = Type::getClassString($new_var_type);
            } else {
                $new_type = Type::getMixed();
            }
        } elseif (substr($new_var_type, 0, 9) === 'getclass-') {
            $new_var_type = substr($new_var_type, 9);
            $new_type = Type::parseString($new_var_type);
        } else {
            $bracket_pos = strpos($new_var_type, '(');

            if ($bracket_pos) {
                return self::handleLiteralEquality(
                    $new_var_type,
                    $bracket_pos,
                    $is_loose_equality,
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $code_location,
                    $suppressed_issues
                );
            }

            $new_type = Type::parseString($new_var_type);
        }

        if ($existing_var_type->hasMixed()) {
            return $new_type;
        }

        $new_type_has_interface = false;

        if ($new_type->hasObjectType()) {
            foreach ($new_type->getTypes() as $new_type_part) {
                if ($new_type_part instanceof TNamedObject &&
                    $codebase->interfaceExists($new_type_part->value)
                ) {
                    $new_type_has_interface = true;
                    break;
                }
            }
        }

        $old_type_has_interface = false;

        if ($existing_var_type->hasObjectType()) {
            foreach ($existing_var_type->getTypes() as $existing_type_part) {
                if ($existing_type_part instanceof TNamedObject &&
                    $codebase->interfaceExists($existing_type_part->value)
                ) {
                    $old_type_has_interface = true;
                    break;
                }
            }
        }

        $new_type_part = Atomic::create($new_var_type);

        if ($new_type_part instanceof TNamedObject
            && (($new_type_has_interface
                    && !TypeAnalyzer::isContainedBy(
                        $codebase,
                        $existing_var_type,
                        $new_type
                    )
                )
                || ($old_type_has_interface
                    && !TypeAnalyzer::isContainedBy(
                        $codebase,
                        $new_type,
                        $existing_var_type
                    )
                ))
        ) {
            $acceptable_atomic_types = [];

            foreach ($existing_var_type->getTypes() as $existing_var_type_part) {
                if (TypeAnalyzer::isAtomicContainedBy(
                    $codebase,
                    $existing_var_type_part,
                    $new_type_part,
                    false,
                    false,
                    $scalar_type_match_found,
                    $type_coerced,
                    $type_coerced_from_mixed,
                    $atomic_to_string_cast
                )) {
                    $acceptable_atomic_types[] = clone $existing_var_type_part;
                    continue;
                }

                if ($existing_var_type_part instanceof TNamedObject
                    && ($codebase->classExists($existing_var_type_part->value)
                        || $codebase->interfaceExists($existing_var_type_part->value))
                ) {
                    $existing_var_type_part = clone $existing_var_type_part;
                    $existing_var_type_part->addIntersectionType($new_type_part);
                    $acceptable_atomic_types[] = $existing_var_type_part;
                }
            }

            if ($acceptable_atomic_types) {
                return new Type\Union($acceptable_atomic_types);
            }
        } elseif ($code_location && !$new_type->hasMixed()) {
            $has_match = true;

            if ($key
                && $new_type->getId() === $existing_var_type->getId()
                && !$is_equality
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $new_var_type,
                    true,
                    $code_location,
                    $suppressed_issues
                );
            }

            $any_scalar_type_match_found = false;

            $matching_atomic_types = [];

            foreach ($new_type->getTypes() as $new_type_part) {
                $has_local_match = false;

                foreach ($existing_var_type->getTypes() as $existing_var_type_part) {
                    // special workaround because PHP allows floats to contain ints, but we don’t want this
                    // behaviour here
                    if ($existing_var_type_part instanceof Type\Atomic\TFloat
                        && $new_type_part instanceof Type\Atomic\TInt
                    ) {
                        $any_scalar_type_match_found = true;
                        continue;
                    }

                    $scalar_type_match_found = false;
                    $type_coerced = false;
                    $type_coerced_from_mixed = false;
                    $atomic_to_string_cast = false;

                    if (TypeAnalyzer::isAtomicContainedBy(
                        $codebase,
                        $new_type_part,
                        $existing_var_type_part,
                        false,
                        false,
                        $scalar_type_match_found,
                        $type_coerced,
                        $type_coerced_from_mixed,
                        $atomic_to_string_cast
                    ) || $type_coerced
                    ) {
                        $has_local_match = true;
                        if ($type_coerced) {
                            $matching_atomic_types[] = $existing_var_type_part;
                        }
                        continue;
                    }

                    if ($scalar_type_match_found) {
                        $any_scalar_type_match_found = true;
                    }

                    if ($new_type_part instanceof TCallable &&
                        (
                            $existing_var_type_part instanceof TString ||
                            $existing_var_type_part instanceof TArray ||
                            $existing_var_type_part instanceof ObjectLike ||
                            (
                                $existing_var_type_part instanceof TNamedObject &&
                                $codebase->classExists($existing_var_type_part->value) &&
                                $codebase->methodExists($existing_var_type_part->value . '::__invoke')
                            )
                        )
                    ) {
                        $has_local_match = true;
                        continue;
                    }
                }

                if (!$has_local_match) {
                    $has_match = false;
                    break;
                }
            }

            if ($matching_atomic_types) {
                $new_type = new Type\Union($matching_atomic_types);
            }

            if (!$has_match && (!$is_loose_equality || !$any_scalar_type_match_found)) {
                if ($new_var_type === 'null') {
                    if ($existing_var_type->from_docblock) {
                        if (IssueBuffer::accepts(
                            new DocblockTypeContradiction(
                                'Cannot resolve types for ' . $key . ' - docblock-defined type '
                                    . $existing_var_type . ' does not contain null',
                                $code_location
                            ),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new TypeDoesNotContainNull(
                                'Cannot resolve types for ' . $key . ' - ' . $existing_var_type
                                    . ' does not contain null',
                                $code_location
                            ),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    }
                } elseif ($key !== '$this'
                    || !($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
                ) {
                    if ($existing_var_type->from_docblock) {
                        if (IssueBuffer::accepts(
                            new DocblockTypeContradiction(
                                'Cannot resolve types for ' . $key . ' - docblock-defined type '
                                    . $existing_var_type->getId() . ' does not contain ' . $new_type->getId(),
                                $code_location
                            ),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new TypeDoesNotContainType(
                                'Cannot resolve types for ' . $key . ' - ' . $existing_var_type->getId() .
                                ' does not contain ' . $new_type->getId(),
                                $code_location
                            ),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    }
                }

                $failed_reconciliation = true;
            }
        }

        if ($existing_var_type->hasType($new_var_type)) {
            $atomic_type = clone $existing_var_type->getTypes()[$new_var_type];
            $atomic_type->from_docblock = false;

            return new Type\Union([$atomic_type]);
        }

        return $new_type;
    }

    /**
     * @param  string     $new_var_type
     * @param  bool       $is_strict_equality
     * @param  bool       $is_loose_equality
     * @param  string     $old_var_type_string
     * @param  string|null $key
     * @param  CodeLocation|null $code_location
     * @param  string[]   $suppressed_issues
     * @param  bool       $failed_reconciliation
     *
     * @return Type\Union
     */
    private static function handleNegatedType(
        StatementsAnalyzer $statements_analyzer,
        $new_var_type,
        $is_strict_equality,
        $is_loose_equality,
        Type\Union $existing_var_type,
        $old_var_type_string,
        $key,
        $code_location,
        $suppressed_issues,
        &$failed_reconciliation
    ) {
        $is_equality = $is_strict_equality || $is_loose_equality;

        // this is a specific value comparison type that cannot be negated
        if ($is_equality && $bracket_pos = strpos($new_var_type, '(')) {
            if ($existing_var_type->hasMixed()) {
                return $existing_var_type;
            }

            return self::handleLiteralNegatedEquality(
                $new_var_type,
                $bracket_pos,
                $existing_var_type,
                $old_var_type_string,
                $key,
                $code_location,
                $suppressed_issues
            );
        }

        if (!$is_equality && ($new_var_type === 'isset' || $new_var_type === 'array-key-exists')) {
            return Type::getNull();
        }

        $existing_var_atomic_types = $existing_var_type->getTypes();

        if ($new_var_type === 'object' && !$existing_var_type->hasMixed()) {
            $non_object_types = [];
            $did_remove_type = false;

            foreach ($existing_var_atomic_types as $type) {
                if (!$type->isObjectType()) {
                    $non_object_types[] = $type;
                } else {
                    $did_remove_type = true;
                }
            }

            if ((!$did_remove_type || !$non_object_types)) {
                if ($key && $code_location && !$is_equality) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $new_var_type,
                        !$did_remove_type,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            if ($non_object_types) {
                return new Type\Union($non_object_types);
            }

            $failed_reconciliation = true;

            return Type::getMixed();
        }

        if ($new_var_type === 'scalar' && !$existing_var_type->hasMixed()) {
            $non_scalar_types = [];
            $did_remove_type = false;

            foreach ($existing_var_atomic_types as $type) {
                if (!($type instanceof Scalar)) {
                    $non_scalar_types[] = $type;
                } else {
                    $did_remove_type = true;
                }
            }

            if ((!$did_remove_type || !$non_scalar_types)) {
                if ($key && $code_location && !$is_equality) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $new_var_type,
                        !$did_remove_type,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            if ($non_scalar_types) {
                return new Type\Union($non_scalar_types);
            }

            $failed_reconciliation = true;

            return Type::getMixed();
        }

        if ($new_var_type === 'bool' && !$existing_var_type->hasMixed()) {
            $non_bool_types = [];
            $did_remove_type = false;

            foreach ($existing_var_atomic_types as $type) {
                if (!$type instanceof TBool
                    || ($is_equality && get_class($type) === TBool::class)
                ) {
                    $non_bool_types[] = $type;

                    if ($type instanceof TScalar) {
                        $did_remove_type = true;
                    }
                } else {
                    $did_remove_type = true;
                }
            }

            if (!$did_remove_type || !$non_bool_types) {
                if ($key && $code_location && !$is_equality) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $new_var_type,
                        !$did_remove_type,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            if ($non_bool_types) {
                return new Type\Union($non_bool_types);
            }

            $failed_reconciliation = true;

            return Type::getMixed();
        }

        if ($new_var_type === 'numeric' && !$existing_var_type->hasMixed()) {
            $non_numeric_types = [];
            $did_remove_type = $existing_var_type->hasString();

            foreach ($existing_var_atomic_types as $type) {
                if (!$type->isNumericType()) {
                    $non_numeric_types[] = $type;
                } else {
                    $did_remove_type = true;
                }
            }

            if ((!$non_numeric_types || !$did_remove_type)) {
                if ($key && $code_location && !$is_equality) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $new_var_type,
                        $did_remove_type,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            if ($non_numeric_types) {
                return new Type\Union($non_numeric_types);
            }

            $failed_reconciliation = true;

            return Type::getMixed();
        }

        if ($new_var_type === 'falsy' || $new_var_type === 'empty') {
            $did_remove_type = $existing_var_type->hasDefinitelyNumericType(false)
                || $existing_var_type->isEmpty()
                || $existing_var_type->hasType('bool')
                || $existing_var_type->possibly_undefined
                || $existing_var_type->possibly_undefined_from_try;

            if ($existing_var_type->hasMixed()) {
                if ($existing_var_type->isMixed()
                    && $existing_var_atomic_types['mixed'] instanceof Type\Atomic\TEmptyMixed
                ) {
                    if ($code_location
                        && $key
                        && IssueBuffer::accepts(
                            new ParadoxicalCondition(
                                'Found a paradox when evaluating ' . $key
                                    . ' and trying to reconcile it with a non-' . $new_var_type . ' assertion',
                                $code_location
                            ),
                            $suppressed_issues
                        )
                    ) {
                        // fall through
                    }

                    return Type::getMixed();
                }

                if (!$existing_var_atomic_types['mixed'] instanceof Type\Atomic\TNonEmptyMixed) {
                    $did_remove_type = true;
                    $existing_var_type->removeType('mixed');

                    if (!$existing_var_atomic_types['mixed'] instanceof Type\Atomic\TEmptyMixed) {
                        $existing_var_type->addType(new Type\Atomic\TNonEmptyMixed);
                    }
                } elseif ($existing_var_type->isMixed()) {
                    if ($code_location
                        && $key
                        && IssueBuffer::accepts(
                            new RedundantCondition(
                                'Found a redundant condition when evaluating ' . $key
                                    . ' and trying to reconcile it with a non-' . $new_var_type . ' assertion',
                                $code_location
                            ),
                            $suppressed_issues
                        )
                    ) {
                        // fall through
                    }
                }

                if ($existing_var_type->isMixed()) {
                    return $existing_var_type;
                }
            }

            if ($is_strict_equality && $new_var_type === 'empty') {
                $existing_var_type->removeType('null');
                $existing_var_type->removeType('false');

                if ($existing_var_type->hasType('array')
                    && $existing_var_type->getTypes()['array']->getId() === 'array<empty, empty>'
                ) {
                    $existing_var_type->removeType('array');
                }

                $existing_var_type->possibly_undefined = false;
                $existing_var_type->possibly_undefined_from_try = false;

                if ($existing_var_type->getTypes()) {
                    return $existing_var_type;
                }

                $failed_reconciliation = true;

                return Type::getMixed();
            }

            if ($existing_var_type->hasType('null')) {
                $did_remove_type = true;
                $existing_var_type->removeType('null');
            }

            if ($existing_var_type->hasType('false')) {
                $did_remove_type = true;
                $existing_var_type->removeType('false');
            }

            if ($existing_var_type->hasType('bool')) {
                $did_remove_type = true;
                $existing_var_type->removeType('bool');
                $existing_var_type->addType(new TTrue);
            }

            if ($existing_var_type->hasString()) {
                $existing_string_types = $existing_var_type->getLiteralStrings();

                if ($existing_string_types) {
                    foreach ($existing_string_types as $string_key => $literal_type) {
                        if (!$literal_type->value) {
                            $existing_var_type->removeType($string_key);
                            $did_remove_type = true;
                        }
                    }
                } else {
                    $did_remove_type = true;
                }
            }

            if ($existing_var_type->hasInt()) {
                $existing_int_types = $existing_var_type->getLiteralInts();

                if ($existing_int_types) {
                    foreach ($existing_int_types as $int_key => $literal_type) {
                        if (!$literal_type->value) {
                            $existing_var_type->removeType($int_key);
                            $did_remove_type = true;
                        }
                    }
                } else {
                    $did_remove_type = true;
                }
            }

            if ($existing_var_type->hasType('array')) {
                $array_atomic_type = $existing_var_type->getTypes()['array'];

                if ($array_atomic_type instanceof Type\Atomic\TArray
                    && !$array_atomic_type instanceof Type\Atomic\TNonEmptyArray
                ) {
                    $did_remove_type = true;

                    if ($array_atomic_type->getId() === 'array<empty, empty>') {
                        $existing_var_type->removeType('array');
                    } else {
                        $existing_var_type->addType(
                            new Type\Atomic\TNonEmptyArray(
                                $array_atomic_type->type_params
                            )
                        );
                    }
                } elseif ($array_atomic_type instanceof Type\Atomic\ObjectLike
                    && !$array_atomic_type->sealed
                ) {
                    $did_remove_type = true;
                }
            }

            $existing_var_type->possibly_undefined = false;
            $existing_var_type->possibly_undefined_from_try = false;

            if (!$did_remove_type || empty($existing_var_type->getTypes())) {
                if ($key && $code_location && !$is_equality) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        '!' . $new_var_type,
                        !$did_remove_type,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            if ($existing_var_type->getTypes()) {
                return $existing_var_type;
            }

            $failed_reconciliation = true;

            return Type::getEmpty();
        }

        if ($new_var_type === 'null' && !$existing_var_type->hasMixed()) {
            $did_remove_type = false;

            if ($existing_var_type->hasType('null')) {
                $did_remove_type = true;
                $existing_var_type->removeType('null');
            }

            if (!$did_remove_type || empty($existing_var_type->getTypes())) {
                if ($key && $code_location && !$is_equality) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $new_var_type,
                        !$did_remove_type,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            if ($existing_var_type->getTypes()) {
                return $existing_var_type;
            }

            $failed_reconciliation = true;

            return Type::getMixed();
        }

        if (isset($existing_var_atomic_types['int'])
            && $existing_var_type->from_calculation
            && ($new_var_type === 'int' || $new_var_type === 'float')
        ) {
            $existing_var_type->removeType($new_var_type);

            if ($new_var_type === 'int') {
                $existing_var_type->addType(new Type\Atomic\TFloat);
            } else {
                $existing_var_type->addType(new Type\Atomic\TInt);
            }

            $existing_var_type->from_calculation = false;

            return $existing_var_type;
        }

        if ($new_var_type === 'false' && isset($existing_var_atomic_types['bool'])) {
            $existing_var_type->removeType('bool');
            $existing_var_type->addType(new TTrue);
        } elseif ($new_var_type === 'true' && isset($existing_var_atomic_types['bool'])) {
            $existing_var_type->removeType('bool');
            $existing_var_type->addType(new TFalse);
        } elseif (strtolower($new_var_type) === 'traversable'
            && isset($existing_var_type->getTypes()['iterable'])
        ) {
            $existing_var_type->removeType('iterable');
            $existing_var_type->addType(new TArray(
                [
                    new Type\Union([new TMixed]),
                    new Type\Union([new TMixed]),
                ]
            ));
        } elseif (strtolower($new_var_type) === 'array'
            && isset($existing_var_type->getTypes()['iterable'])
        ) {
            $existing_var_type->removeType('iterable');
            $existing_var_type->addType(new TNamedObject('Traversable'));
        } elseif (substr($new_var_type, 0, 9) === 'getclass-') {
            $new_var_type = substr($new_var_type, 9);
        } elseif (!$is_equality) {
            $new_type_part = new TNamedObject($new_var_type);

            $codebase = $statements_analyzer->getCodebase();

            // if there wasn't a direct hit, go deeper, eliminating subtypes
            if (!$existing_var_type->removeType($new_var_type)) {
                foreach ($existing_var_type->getTypes() as $part_name => $existing_var_type_part) {
                    if (!$existing_var_type_part->isObjectType()) {
                        continue;
                    }

                    if (TypeAnalyzer::isAtomicContainedBy(
                        $codebase,
                        $existing_var_type_part,
                        $new_type_part,
                        false,
                        false,
                        $scalar_type_match_found,
                        $type_coerced,
                        $type_coerced_from_mixed,
                        $atomic_to_string_cast
                    )) {
                        $existing_var_type->removeType($part_name);
                    }
                }
            }
        }

        if (empty($existing_var_type->getTypes())) {
            if ($key !== '$this'
                || !($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
            ) {
                if ($key && $code_location && !$is_equality) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        $new_var_type,
                        true,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            $failed_reconciliation = true;

            return new Type\Union([new Type\Atomic\TEmptyMixed]);
        }

        return $existing_var_type;
    }

    /**
     * @param  string     $new_var_type
     * @param  int        $bracket_pos
     * @param  bool       $is_loose_equality
     * @param  string     $old_var_type_string
     * @param  string|null $var_id
     * @param  CodeLocation|null $code_location
     * @param  string[]   $suppressed_issues
     *
     * @return Type\Union
     */
    private static function handleLiteralEquality(
        $new_var_type,
        $bracket_pos,
        $is_loose_equality,
        Type\Union $existing_var_type,
        $old_var_type_string,
        $var_id,
        $code_location,
        $suppressed_issues
    ) {
        $value = substr($new_var_type, $bracket_pos + 1, -1);

        $scalar_type = substr($new_var_type, 0, $bracket_pos);

        $existing_var_atomic_types = $existing_var_type->getTypes();

        if ($scalar_type === 'int') {
            $value = (int) $value;

            if ($existing_var_type->hasMixed()) {
                return new Type\Union([new Type\Atomic\TLiteralInt($value)]);
            }

            if ($existing_var_type->hasInt()) {
                $existing_int_types = $existing_var_type->getLiteralInts();

                if ($existing_int_types) {
                    $can_be_equal = false;
                    $did_remove_type = false;

                    foreach ($existing_var_atomic_types as $atomic_key => $_) {
                        if ($atomic_key !== $new_var_type) {
                            $existing_var_type->removeType($atomic_key);
                            $did_remove_type = true;
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($var_id
                        && $code_location
                        && (!$can_be_equal || (!$did_remove_type && count($existing_var_atomic_types) === 1))
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $new_var_type,
                            $can_be_equal,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                } else {
                    $existing_var_type = new Type\Union([new Type\Atomic\TLiteralInt($value)]);
                }
            } elseif ($var_id && $code_location && !$is_loose_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $var_id,
                    $new_var_type,
                    false,
                    $code_location,
                    $suppressed_issues
                );
            } elseif ($is_loose_equality && $existing_var_type->hasFloat()) {
                // convert floats to ints
                $existing_float_types = $existing_var_type->getLiteralFloats();

                if ($existing_float_types) {
                    $can_be_equal = false;
                    $did_remove_type = false;

                    foreach ($existing_var_atomic_types as $atomic_key => $_) {
                        if (substr($atomic_key, 0, 6) === 'float(') {
                            $atomic_key = 'int(' . substr($atomic_key, 6);
                        }
                        if ($atomic_key !== $new_var_type) {
                            $existing_var_type->removeType($atomic_key);
                            $did_remove_type = true;
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($var_id
                        && $code_location
                        && (!$can_be_equal || (!$did_remove_type && count($existing_var_atomic_types) === 1))
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $new_var_type,
                            $can_be_equal,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                }
            }
        } elseif ($scalar_type === 'string' || $scalar_type === 'class-string') {
            if ($existing_var_type->hasMixed()) {
                if ($scalar_type === 'class-string') {
                    return new Type\Union([new Type\Atomic\TLiteralClassString($value)]);
                }

                return new Type\Union([new Type\Atomic\TLiteralString($value)]);
            }

            if ($existing_var_type->hasString()) {
                $existing_string_types = $existing_var_type->getLiteralStrings();

                if ($existing_string_types) {
                    $can_be_equal = false;
                    $did_remove_type = false;

                    foreach ($existing_var_atomic_types as $atomic_key => $_) {
                        if ($atomic_key !== $new_var_type) {
                            $existing_var_type->removeType($atomic_key);
                            $did_remove_type = true;
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($var_id
                        && $code_location
                        && (!$can_be_equal || (!$did_remove_type && count($existing_var_atomic_types) === 1))
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $new_var_type,
                            $can_be_equal,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                } else {
                    if ($scalar_type === 'class-string') {
                        $existing_var_type = new Type\Union([new Type\Atomic\TLiteralClassString($value)]);
                    } else {
                        $existing_var_type = new Type\Union([new Type\Atomic\TLiteralString($value)]);
                    }
                }
            } elseif ($var_id && $code_location && !$is_loose_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $var_id,
                    $new_var_type,
                    false,
                    $code_location,
                    $suppressed_issues
                );
            }
        } elseif ($scalar_type === 'float') {
            $value = (float) $value;

            if ($existing_var_type->hasMixed()) {
                return new Type\Union([new Type\Atomic\TLiteralFloat($value)]);
            }

            if ($existing_var_type->hasFloat()) {
                $existing_float_types = $existing_var_type->getLiteralFloats();

                if ($existing_float_types) {
                    $can_be_equal = false;
                    $did_remove_type = false;

                    foreach ($existing_var_atomic_types as $atomic_key => $_) {
                        if ($atomic_key !== $new_var_type) {
                            $existing_var_type->removeType($atomic_key);
                            $did_remove_type = true;
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($var_id
                        && $code_location
                        && (!$can_be_equal || (!$did_remove_type && count($existing_var_atomic_types) === 1))
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $new_var_type,
                            $can_be_equal,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                } else {
                    $existing_var_type = new Type\Union([new Type\Atomic\TLiteralFloat($value)]);
                }
            } elseif ($var_id && $code_location && !$is_loose_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $var_id,
                    $new_var_type,
                    false,
                    $code_location,
                    $suppressed_issues
                );
            } elseif ($is_loose_equality && $existing_var_type->hasInt()) {
                // convert ints to floats
                $existing_float_types = $existing_var_type->getLiteralInts();

                if ($existing_float_types) {
                    $can_be_equal = false;
                    $did_remove_type = false;

                    foreach ($existing_var_atomic_types as $atomic_key => $_) {
                        if (substr($atomic_key, 0, 4) === 'int(') {
                            $atomic_key = 'float(' . substr($atomic_key, 4);
                        }
                        if ($atomic_key !== $new_var_type) {
                            $existing_var_type->removeType($atomic_key);
                            $did_remove_type = true;
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($var_id
                        && $code_location
                        && (!$can_be_equal || (!$did_remove_type && count($existing_var_atomic_types) === 1))
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $new_var_type,
                            $can_be_equal,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                }
            }
        }

        return $existing_var_type;
    }

    /**
     * @param  string     $new_var_type
     * @param  int        $bracket_pos
     * @param  string     $old_var_type_string
     * @param  string|null $key
     * @param  CodeLocation|null $code_location
     * @param  string[]   $suppressed_issues
     *
     * @return Type\Union
     */
    private static function handleLiteralNegatedEquality(
        $new_var_type,
        $bracket_pos,
        Type\Union $existing_var_type,
        $old_var_type_string,
        $key,
        $code_location,
        $suppressed_issues
    ) {
        $scalar_type = substr($new_var_type, 0, $bracket_pos);

        $existing_var_atomic_types = $existing_var_type->getTypes();

        $did_remove_type = false;
        $did_match_literal_type = false;

        if ($scalar_type === 'int') {
            if ($existing_var_type->hasInt() && $existing_int_types = $existing_var_type->getLiteralInts()) {
                $did_match_literal_type = true;

                if (isset($existing_int_types[$new_var_type])) {
                    $existing_var_type->removeType($new_var_type);

                    $did_remove_type = true;
                }
            }
        } elseif ($scalar_type === 'string' || $scalar_type === 'class-string') {
            if ($existing_var_type->hasString() && $existing_string_types = $existing_var_type->getLiteralStrings()) {
                $did_match_literal_type = true;

                if (isset($existing_string_types[$new_var_type])) {
                    $existing_var_type->removeType($new_var_type);

                    $did_remove_type = true;
                }
            }
        } elseif ($scalar_type === 'float') {
            if ($existing_var_type->hasFloat() && $existing_float_types = $existing_var_type->getLiteralFloats()) {
                $did_match_literal_type = true;

                if (isset($existing_float_types[$new_var_type])) {
                    $existing_var_type->removeType($new_var_type);

                    $did_remove_type = true;
                }
            }
        }

        if ($key
            && $code_location
            && $did_match_literal_type
            && (!$did_remove_type || count($existing_var_atomic_types) === 1)
        ) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $old_var_type_string,
                $key,
                $new_var_type,
                !$did_remove_type,
                $code_location,
                $suppressed_issues
            );
        }

        return $existing_var_type;
    }

    /**
     * @param  string       $key
     * @param  string       $old_var_type_string
     * @param  string       $new_var_type
     * @param  bool         $redundant
     * @param  string[]     $suppressed_issues
     *
     * @return void
     */
    private static function triggerIssueForImpossible(
        Union $existing_var_type,
        $old_var_type_string,
        $key,
        $new_var_type,
        $redundant,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $reconciliation = ' and trying to reconcile type \'' . $old_var_type_string . '\' to ' . $new_var_type;

        $existing_var_atomic_types = $existing_var_type->getTypes();

        $from_docblock = $existing_var_type->from_docblock
            || (isset($existing_var_atomic_types[$new_var_type])
                && $existing_var_atomic_types[$new_var_type]->from_docblock);

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
     * @param  array<string>             $changed_var_ids
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

        if ($array_key[0] === '$') {
            return;
        }

        $array_key_offset = substr($array_key, 1, -1);

        $base_key = implode($key_parts);

        if (isset($existing_types[$base_key])) {
            $base_atomic_types = $existing_types[$base_key]->getTypes();

            if (isset($base_atomic_types['array'])) {
                if ($base_atomic_types['array'] instanceof Type\Atomic\ObjectLike) {
                    $base_atomic_types['array']->properties[$array_key_offset] = clone $result_type;
                    $changed_var_ids[] = $base_key . '[' . $array_key . ']';

                    if ($key_parts[count($key_parts) - 1] === ']') {
                        self::adjustObjectLikeType(
                            $key_parts,
                            $existing_types,
                            $changed_var_ids,
                            $existing_types[$base_key]
                        );
                    }

                    $existing_types[$base_key]->bustCache();
                }
            }
        }
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
                    $parts_offset++;
                    continue 2;

                case '\'':
                case '"':
                    if (!isset($parts[$parts_offset])) {
                        $parts[$parts_offset] = '';
                    }
                    $parts[$parts_offset] .= $char;
                    $string_char = $char;

                    continue 2;

                case '-':
                    if ($i < $char_count - 1 && $chars[$i + 1] === '>') {
                        ++$i;

                        $parts_offset++;
                        $parts[$parts_offset] = '->';
                        $parts_offset++;
                        continue 2;
                    }
                    // fall through

                default:
                    if (!isset($parts[$parts_offset])) {
                        $parts[$parts_offset] = '';
                    }
                    $parts[$parts_offset] .= $char;
            }
        }

        self::$broken_paths[$path] = $parts;

        return $parts;
    }

    /**
     * Gets the type for a given (non-existent key) based on the passed keys
     *
     * @param  string                    $key
     * @param  array<string,Type\Union>  $existing_keys
     *
     * @return Type\Union|null
     */
    private static function getValueForKey(
        Codebase $codebase,
        $key,
        array &$existing_keys,
        CodeLocation $code_location = null
    ) {
        $key_parts = self::breakUpPathIntoParts($key);

        if (count($key_parts) === 1) {
            return isset($existing_keys[$key_parts[0]]) ? clone $existing_keys[$key_parts[0]] : null;
        }

        $base_key = array_shift($key_parts);

        if (!isset($existing_keys[$base_key])) {
            return null;
        }

        while ($key_parts) {
            $divider = array_shift($key_parts);

            if ($divider === '[') {
                $array_key = array_shift($key_parts);
                array_shift($key_parts);

                $new_base_key = $base_key . '[' . $array_key . ']';

                if (!isset($existing_keys[$new_base_key])) {
                    $new_base_type = null;

                    foreach ($existing_keys[$base_key]->getTypes() as $existing_key_type_part) {
                        if ($existing_key_type_part instanceof Type\Atomic\TArray) {
                            $new_base_type_candidate = clone $existing_key_type_part->type_params[1];
                        } elseif (!$existing_key_type_part instanceof Type\Atomic\ObjectLike) {
                            return Type::getMixed();
                        } elseif ($array_key[0] === '$') {
                            $new_base_type_candidate = $existing_key_type_part->getGenericValueType();
                        } else {
                            $array_properties = $existing_key_type_part->properties;

                            $key_parts_key = str_replace('\'', '', $array_key);

                            if (!isset($array_properties[$key_parts_key])) {
                                return null;
                            }

                            $new_base_type_candidate = clone $array_properties[$key_parts_key];
                        }

                        if (!$new_base_type) {
                            $new_base_type = $new_base_type_candidate;
                        } else {
                            $new_base_type = Type::combineUnionTypes(
                                $new_base_type,
                                $new_base_type_candidate
                            );
                        }

                        $existing_keys[$new_base_key] = $new_base_type;
                    }
                }

                $base_key = $new_base_key;
            } elseif ($divider === '->') {
                $property_name = array_shift($key_parts);
                $new_base_key = $base_key . '->' . $property_name;

                if (!isset($existing_keys[$new_base_key])) {
                    $new_base_type = null;

                    foreach ($existing_keys[$base_key]->getTypes() as $existing_key_type_part) {
                        if ($existing_key_type_part instanceof TNull) {
                            $class_property_type = Type::getNull();
                        } elseif ($existing_key_type_part instanceof TMixed
                            || $existing_key_type_part instanceof TGenericParam
                            || $existing_key_type_part instanceof TObject
                            || ($existing_key_type_part instanceof TNamedObject
                                && strtolower($existing_key_type_part->value) === 'stdclass')
                        ) {
                            $class_property_type = Type::getMixed();
                        } elseif ($existing_key_type_part instanceof TNamedObject) {
                            if (!$codebase->classOrInterfaceExists($existing_key_type_part->value)) {
                                continue;
                            }

                            $property_id = $existing_key_type_part->value . '::$' . $property_name;

                            if (!$codebase->properties->propertyExists($property_id)) {
                                return null;
                            }

                            $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
                                $property_id
                            );

                            $class_storage = $codebase->classlike_storage_provider->get(
                                (string)$declaring_property_class
                            );

                            $class_property_type = $class_storage->properties[$property_name]->type;

                            $class_property_type = $class_property_type ? clone $class_property_type : Type::getMixed();
                        } else {
                            // @todo handle this
                            continue;
                        }

                        if ($new_base_type instanceof Type\Union) {
                            $new_base_type = Type::combineUnionTypes($new_base_type, $class_property_type);
                        } else {
                            $new_base_type = $class_property_type;
                        }

                        $existing_keys[$new_base_key] = $new_base_type;
                    }
                }

                $base_key = $new_base_key;
            } else {
                if ($code_location) {
                    IssueBuffer::add(
                        new PsalmInternalError(
                            'Unexpected divider ' . $divider,
                            $code_location
                        )
                    );
                }

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
}
