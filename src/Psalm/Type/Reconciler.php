<?php
namespace Psalm\Type;

use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TraitChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\ParadoxicalCondition;
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
     * @param  StatementsChecker         $statements_checker
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
        StatementsChecker $statements_checker,
        CodeLocation $code_location = null,
        array $suppressed_issues = []
    ) {
        foreach ($new_types as $nk => $type) {
            if (strpos($nk, '[') && ($type[0][0] === '^isset' || $type[0][0] === '!^empty')) {
                $path_parts = self::breakUpPathIntoParts($nk);

                if (count($path_parts) > 1) {
                    $base_key = array_shift($path_parts);

                    if (!isset($new_types[$base_key])) {
                        $new_types[$base_key] = [['!^bool'],['!^int']];
                    } else {
                        $new_types[$base_key][] = ['!^bool'];
                        $new_types[$base_key][] = ['!^int'];
                    }
                }
            }
        }

        if (empty($new_types)) {
            return $existing_types;
        }

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        foreach ($new_types as $key => $new_type_parts) {
            $result_type = isset($existing_types[$key])
                ? clone $existing_types[$key]
                : self::getValueForKey($project_checker, $key, $existing_types);

            if ($result_type && empty($result_type->getTypes())) {
                throw new \InvalidArgumentException('Union::$types cannot be empty after get value for ' . $key);
            }

            $before_adjustment = clone $result_type;

            $failed_reconciliation = false;
            $from_docblock = $result_type && $result_type->from_docblock;
            $possibly_undefined = $result_type && $result_type->possibly_undefined;
            $from_calculation = $result_type && $result_type->from_calculation;

            foreach ($new_type_parts as $new_type_part_parts) {
                $orred_type = null;

                foreach ($new_type_part_parts as $new_type_part_part) {
                    $result_type_candidate = self::reconcileTypes(
                        $new_type_part_part,
                        $result_type ? clone $result_type : null,
                        $key,
                        $statements_checker,
                        $code_location && isset($referenced_var_ids[$key]) ? $code_location : null,
                        $suppressed_issues,
                        $failed_reconciliation
                    );

                    $orred_type = $orred_type
                        ? Type::combineUnionTypes($result_type_candidate, $orred_type)
                        : $result_type_candidate;
                }

                $result_type = $orred_type;
            }

            if ($result_type === null) {
                continue;
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
            } elseif (!$type_changed
                && $code_location
                && isset($referenced_var_ids[$key])
            ) {
                $reconcile_key = implode(
                    '&',
                    array_map(
                        function (array $new_type_part_parts) {
                            return implode('|', $new_type_part_parts);
                        },
                        $new_type_parts
                    )
                );
                self::triggerIssueForImpossible(
                    $result_type,
                    $before_adjustment->getId(),
                    $key,
                    $reconcile_key,
                    true,
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
     * @param   StatementsChecker   $statements_checker
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
        StatementsChecker $statements_checker,
        CodeLocation $code_location = null,
        array $suppressed_issues = [],
        &$failed_reconciliation = false
    ) {
        $project_checker = $statements_checker->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        $is_strict_equality = false;
        $is_loose_equality = false;
        $is_equality = false;
        $is_negation = false;

        if ($new_var_type[0] === '!') {
            $new_var_type = substr($new_var_type, 1);
            $is_negation = true;
        }

        if ($new_var_type[0] === '^') {
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
                return Type::getMixed();
            }

            if ($new_var_type === 'array-key-exists') {
                return Type::getMixed();
            }

            if (!$is_negation && $new_var_type !== 'falsy' && $new_var_type !== 'empty') {
                if ($is_strict_equality) {
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
                $statements_checker,
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

        if ($new_var_type === 'mixed' && $existing_var_type->isMixed()) {
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
                $existing_var_type->addType(new TMixed(true));
            }

            $existing_var_type->possibly_undefined = false;

            return $existing_var_type;
        }

        if ($new_var_type === 'array-key-exists') {
            $existing_var_type->possibly_undefined = false;

            return $existing_var_type;
        }

        $existing_var_atomic_types = $existing_var_type->getTypes();

        if ($new_var_type === 'falsy' || $new_var_type === 'empty') {
            if ($existing_var_type->isMixed()) {
                return new Type\Union([new Type\Atomic\TEmptyMixed]);
            }

            $did_remove_type = $existing_var_type->hasScalar()
                || $existing_var_type->hasDefinitelyNumericType();

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
                    foreach ($existing_string_types as $key => $literal_type) {
                        if (!$literal_type->value) {
                            $existing_var_type->removeType($key);
                            $did_remove_type = true;
                        }
                    }
                } else {
                    $did_remove_type = true;
                    $existing_var_type->addType(new Type\Atomic\TLiteralString(''));
                    $existing_var_type->addType(new Type\Atomic\TLiteralString('0'));
                }
            }

            if ($existing_var_type->hasInt()) {
                $existing_int_types = $existing_var_type->getLiteralInts();

                if ($existing_int_types) {
                    foreach ($existing_int_types as $key => $literal_type) {
                        if (!$literal_type->value) {
                            $existing_var_type->removeType($key);
                            $did_remove_type = true;
                        }
                    }
                } else {
                    $did_remove_type = true;
                    $existing_var_type->addType(new Type\Atomic\TLiteralInt(0));
                }
            }

            if (isset($existing_var_atomic_types['array'])
                && $existing_var_atomic_types['array']->getId() !== 'array<empty, empty>'
            ) {
                $did_remove_type = true;
                $existing_var_type->addType(new TArray(
                    [
                        new Type\Union([new TEmpty]),
                        new Type\Union([new TEmpty]),
                    ]
                ));
            }

            foreach ($existing_var_atomic_types as $type_key => $type) {
                if ($type instanceof TNamedObject
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

        if ($new_var_type === 'object' && !$existing_var_type->isMixed()) {
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

        if ($new_var_type === 'numeric' && !$existing_var_type->isMixed()) {
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

        if ($new_var_type === 'scalar' && !$existing_var_type->isMixed()) {
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

        if ($new_var_type === 'bool' && !$existing_var_type->isMixed()) {
            $bool_types = [];
            $did_remove_type = false;

            foreach ($existing_var_atomic_types as $type) {
                if ($type instanceof TBool) {
                    $bool_types[] = $type;
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
            if ($existing_var_type->isMixed()) {
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
            $is_strict_equality = true;
            $is_equality = true;
        } else {
            $bracket_pos = strpos($new_var_type, '(');

            if ($bracket_pos) {
                return self::handleLiteralEquality(
                    $new_var_type,
                    $bracket_pos,
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($existing_var_type->isMixed()) {
            return $new_type;
        }

        $has_interface = false;

        if ($new_type->hasObjectType()) {
            foreach ($new_type->getTypes() as $new_type_part) {
                if ($new_type_part instanceof TNamedObject &&
                    $codebase->interfaceExists($new_type_part->value)
                ) {
                    $has_interface = true;
                    break;
                }
            }
        }

        if ($has_interface) {
            $new_type_part = new TNamedObject($new_var_type);

            $acceptable_atomic_types = [];

            foreach ($existing_var_type->getTypes() as $existing_var_type_part) {
                if (TypeChecker::isAtomicContainedBy(
                    $codebase,
                    $existing_var_type_part,
                    $new_type_part,
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
        } elseif ($code_location && !$new_type->isMixed()) {
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

                    if (TypeChecker::isAtomicContainedBy(
                        $project_checker->codebase,
                        $new_type_part,
                        $existing_var_type_part,
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
                        break;
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
                        break;
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
                    || !($statements_checker->getSource()->getSource() instanceof TraitChecker)
                ) {
                    if ($existing_var_type->from_docblock) {
                        if (IssueBuffer::accepts(
                            new DocblockTypeContradiction(
                                'Cannot resolve types for ' . $key . ' - docblock-defined type '
                                    . $existing_var_type . ' does not contain ' . $new_type,
                                $code_location
                            ),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new TypeDoesNotContainType(
                                'Cannot resolve types for ' . $key . ' - ' . $existing_var_type .
                                ' does not contain ' . $new_type,
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
        StatementsChecker $statements_checker,
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

        if ($new_var_type === 'object' && !$existing_var_type->isMixed()) {
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

        if ($new_var_type === 'scalar' && !$existing_var_type->isMixed()) {
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

        if ($new_var_type === 'bool' && !$existing_var_type->isMixed()) {
            $non_bool_types = [];
            $did_remove_type = false;

            foreach ($existing_var_atomic_types as $type) {
                if (!$type instanceof TBool
                    || ($is_equality && get_class($type) === TBool::class)
                ) {
                    $non_bool_types[] = $type;
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

        if ($new_var_type === 'numeric' && !$existing_var_type->isMixed()) {
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

        if (($new_var_type === 'falsy' || $new_var_type === 'empty')) {
            if ($existing_var_type->isMixed()) {
                if ($existing_var_atomic_types['mixed'] instanceof Type\Atomic\TEmptyMixed) {
                    if ($code_location
                        && $key
                        && IssueBuffer::accepts(
                            new ParadoxicalCondition(
                                'Found a redundant condition when evaluating ' . $key,
                                $code_location
                            ),
                            $suppressed_issues
                        )
                    ) {
                        // fall through
                    }

                    return Type::getMixed();
                }

                return $existing_var_type;
            }

            if ($is_strict_equality && $new_var_type === 'empty') {
                $existing_var_type->removeType('null');
                $existing_var_type->removeType('false');

                if ($existing_var_type->hasType('array')
                    && $existing_var_type->getTypes()['array']->getId() === 'array<empty, empty>'
                ) {
                    $existing_var_type->removeType('array');
                }

                if ($existing_var_type->getTypes()) {
                    return $existing_var_type;
                }

                $failed_reconciliation = true;

                return Type::getMixed();
            }

            $did_remove_type = $existing_var_type->hasDefinitelyNumericType()
                || $existing_var_type->isEmpty()
                || $existing_var_type->hasType('bool')
                || $existing_var_type->possibly_undefined;

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
                    foreach ($existing_string_types as $key => $literal_type) {
                        if ($literal_type->value) {
                            $existing_var_type->removeType($key);
                        } else {
                            $did_remove_type = true;
                        }
                    }
                } else {
                    $did_remove_type = true;
                }
            }

            if ($existing_var_type->hasType('array')) {
                $did_remove_type = true;

                if ($existing_var_type->getTypes()['array']->getId() === 'array<empty, empty>') {
                    $existing_var_type->removeType('array');
                }
            }

            $existing_var_type->possibly_undefined = false;

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

        if ($new_var_type === 'null' && !$existing_var_type->isMixed()) {
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
            $existing_var_type->removeType($new_var_type);
        }

        if (empty($existing_var_type->getTypes())) {
            if ($key !== '$this'
                || !($statements_checker->getSource()->getSource() instanceof TraitChecker)
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

            return Type::getMixed();
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
     * @return void
     */
    private static function handleLiteralEquality(
        $new_var_type,
        $bracket_pos,
        Type\Union $existing_var_type,
        $old_var_type_string,
        $key,
        $code_location,
        $suppressed_issues
    ) {
        $value = substr($new_var_type, $bracket_pos + 1, -1);

        $scalar_type = substr($new_var_type, 0, $bracket_pos);

        $existing_var_atomic_types = $existing_var_type->getTypes();

        if ($scalar_type === 'int') {
            $value = (int) $value;

            if ($existing_var_type->hasInt()) {
                $existing_int_types = $existing_var_type->getLiteralInts();

                if ($existing_int_types) {
                    $can_be_equal = false;

                    foreach ($existing_int_types as $key => $value_type) {
                        if ($key !== $new_var_type) {
                            $existing_var_type->removeType($key);
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($key
                        && $code_location
                        && (!$can_be_equal || count($existing_int_types) === 1)
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $key,
                            $new_var_type,
                            $can_be_equal,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                } else {
                    $existing_var_type->addType(new Type\Atomic\TLiteralInt($value));
                }
            }
        } elseif ($scalar_type === 'string') {
            if ($existing_var_type->hasString()) {
                $existing_string_types = $existing_var_type->getLiteralStrings();

                if ($existing_string_types) {
                    $can_be_equal = false;

                    foreach ($existing_string_types as $key => $value_type) {
                        if ($key !== $new_var_type) {
                            $existing_var_type->removeType($key);
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($key
                        && $code_location
                        && (!$can_be_equal || count($existing_string_types) === 1)
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $key,
                            $new_var_type,
                            $can_be_equal,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                } else {
                    $existing_var_type->addType(new Type\Atomic\TLiteralString($value));
                }
            }
        } elseif ($scalar_type === 'float') {
            $value = (float) $value;

            if ($existing_var_type->hasInt()) {
                $existing_float_types = $existing_var_type->getLiteralFloats();

                if ($existing_float_types) {
                    $can_be_equal = false;

                    foreach ($existing_float_types as $key => $value_type) {
                        if ($key !== $new_var_type) {
                            $existing_var_type->removeType($key);
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($key
                        && $code_location
                        && (!$can_be_equal || count($existing_float_types) === 1)
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $key,
                            $new_var_type,
                            $can_be_equal,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                } else {
                    $existing_var_type->addType(new Type\Atomic\TLiteralFloat($value));
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
        } elseif ($scalar_type === 'string') {
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
                    continue;

                case '\'':
                case '"':
                    if (!isset($parts[$parts_offset])) {
                        $parts[$parts_offset] = '';
                    }
                    $parts[$parts_offset] .= $char;
                    $string_char = $char;

                    continue;

                case '-':
                    if ($i < $char_count - 1 && $chars[$i + 1] === '>') {
                        ++$i;

                        $parts_offset++;
                        $parts[$parts_offset] = '->';
                        $parts_offset++;
                        continue;
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
     * @param  ProjectChecker            $project_checker
     * @param  string                    $key
     * @param  array<string,Type\Union>  $existing_keys
     *
     * @return Type\Union|null
     */
    private static function getValueForKey(ProjectChecker $project_checker, $key, array &$existing_keys)
    {
        $key_parts = self::breakUpPathIntoParts($key);

        if (count($key_parts) === 1) {
            return isset($existing_keys[$key_parts[0]]) ? clone $existing_keys[$key_parts[0]] : null;
        }

        $base_key = array_shift($key_parts);

        if (!isset($existing_keys[$base_key])) {
            return null;
        }

        $codebase = $project_checker->codebase;

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
                            return null;
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

                            $class_storage = $project_checker->classlike_storage_provider->get(
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
                throw new \InvalidArgumentException('Unexpected divider ' . $divider);
            }
        }

        return $existing_keys[$base_key];
    }
}
