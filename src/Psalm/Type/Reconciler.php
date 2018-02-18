<?php
namespace Psalm\Type;

use Psalm\Checker\ProjectChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TraitChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\RedundantConditionGivenDocblockType;
use Psalm\Issue\TypeDoesNotContainNull;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TTrue;

class Reconciler
{
    /**
     * Takes two arrays and consolidates them, removing null values from existing types where applicable
     *
     * @param  array<string, string>     $new_types
     * @param  array<string, Type\Union> $existing_types
     * @param  array<string>             $changed_var_ids
     * @param  array<string, bool>       $referenced_var_ids
     * @param  StatementsChecker         $statements_checker
     * @param  CodeLocation              $code_location
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
        CodeLocation $code_location,
        array $suppressed_issues = []
    ) {
        $keys = [];

        foreach ($existing_types as $ek => $_) {
            if (!in_array($ek, $keys, true)) {
                $keys[] = $ek;
            }
        }

        foreach ($new_types as $nk => $_) {
            if (!in_array($nk, $keys, true)) {
                $keys[] = $nk;
            }
        }

        if (empty($new_types)) {
            return $existing_types;
        }

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        foreach ($keys as $key) {
            if (!isset($new_types[$key])) {
                continue;
            }

            $new_type_parts = explode('&', $new_types[$key]);

            $result_type = isset($existing_types[$key])
                ? clone $existing_types[$key]
                : self::getValueForKey($project_checker, $key, $existing_types);

            if ($result_type && empty($result_type->getTypes())) {
                throw new \InvalidArgumentException('Union::$types cannot be empty after get value for ' . $key);
            }

            $before_adjustment = $result_type ? $result_type->getId() : '';

            $failed_reconciliation = false;
            $from_docblock = $result_type && $result_type->from_docblock;

            foreach ($new_type_parts as $new_type_part) {
                $new_type_part_parts = explode('|', $new_type_part);

                $orred_type = null;

                foreach ($new_type_part_parts as $new_type_part_part) {
                    $result_type_candidate = self::reconcileTypes(
                        $new_type_part_part,
                        $result_type,
                        $key,
                        $statements_checker,
                        isset($referenced_var_ids[$key]) ? $code_location : null,
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

            if ($result_type->getId() !== $before_adjustment
                || $result_type->from_docblock !== $from_docblock
                || $failed_reconciliation
            ) {
                $changed_var_ids[] = $key;
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

        if ($existing_var_type === null) {
            if ($new_var_type === '^isset' || $new_var_type === '^!empty') {
                return Type::getMixed();
            }

            if ($new_var_type === 'isset' || $new_var_type === '!empty') {
                return Type::getMixed();
            }

            if ($new_var_type[0] !== '!' && $new_var_type !== 'falsy' && $new_var_type !== 'empty') {
                if ($new_var_type[0] === '^') {
                    $new_var_type = substr($new_var_type, 1);
                }

                return Type::parseString($new_var_type);
            }

            if ($new_var_type === '!falsy') {
                return Type::getMixed();
            }

            return Type::getMixed();
        }

        if ($new_var_type === 'mixed' && $existing_var_type->isMixed()) {
            return $existing_var_type;
        }

        if ($new_var_type[0] === '!') {
            // this is a specific value comparison type that cannot be negated
            if ($new_var_type[1] === '^') {
                return $existing_var_type;
            }

            if ($new_var_type === '!isset') {
                return Type::getNull();
            }

            if ($new_var_type === '!object' && !$existing_var_type->isMixed()) {
                $non_object_types = [];
                $did_remove_type = false;

                foreach ($existing_var_type->getTypes() as $type) {
                    if (!$type->isObjectType()) {
                        $non_object_types[] = $type;
                    } else {
                        $did_remove_type = true;
                    }
                }

                if ((!$did_remove_type || !$non_object_types)) {
                    if ($key && $code_location) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $key,
                            $new_var_type,
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

            if ($new_var_type === '!scalar' && !$existing_var_type->isMixed()) {
                $non_scalar_types = [];
                $did_remove_type = false;

                foreach ($existing_var_type->getTypes() as $type) {
                    if (!($type instanceof Scalar)) {
                        $non_scalar_types[] = $type;
                    } else {
                        $did_remove_type = true;
                    }
                }

                if ((!$did_remove_type || !$non_scalar_types)) {
                    if ($key && $code_location) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $key,
                            $new_var_type,
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

            if ($new_var_type === '!bool' && !$existing_var_type->isMixed()) {
                $non_bool_types = [];
                $did_remove_type = false;

                foreach ($existing_var_type->getTypes() as $type) {
                    if (!$type instanceof TBool) {
                        $non_bool_types[] = $type;
                    } else {
                        $did_remove_type = true;
                    }
                }

                if (!$did_remove_type || !$non_bool_types) {
                    if ($key && $code_location) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $key,
                            $new_var_type,
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

            if ($new_var_type === '!numeric' && !$existing_var_type->isMixed()) {
                $non_numeric_types = [];
                $did_remove_type = $existing_var_type->hasString();

                foreach ($existing_var_type->getTypes() as $type) {
                    if (!$type->isNumericType()) {
                        $non_numeric_types[] = $type;
                    } else {
                        $did_remove_type = true;
                    }
                }

                if ((!$non_numeric_types || !$did_remove_type)) {
                    if ($key && $code_location) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $key,
                            $new_var_type,
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

            if (($new_var_type === '!falsy' || $new_var_type === '!empty')
                && !$existing_var_type->isMixed()
            ) {
                $did_remove_type = $existing_var_type->hasString()
                    || $existing_var_type->hasNumericType()
                    || $existing_var_type->isEmpty()
                    || $existing_var_type->hasType('bool');

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

                if ($existing_var_type->hasType('array')) {
                    $did_remove_type = true;

                    if ($existing_var_type->getTypes()['array']->getId() === 'array<empty, empty>') {
                        $existing_var_type->removeType('array');
                    }
                }

                if (!$did_remove_type || empty($existing_var_type->getTypes())) {
                    if ($key && $code_location) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $key,
                            $new_var_type,
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

            if ($new_var_type === '!null' && !$existing_var_type->isMixed()) {
                $did_remove_type = false;

                if ($existing_var_type->hasType('null')) {
                    $did_remove_type = true;
                    $existing_var_type->removeType('null');
                }

                if (!$did_remove_type || empty($existing_var_type->getTypes())) {
                    if ($key && $code_location) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $key,
                            $new_var_type,
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

            $negated_type = substr($new_var_type, 1);

            if ($negated_type === 'false' && isset($existing_var_type->getTypes()['bool'])) {
                $existing_var_type->removeType('bool');
                $existing_var_type->addType(new TTrue);
            } elseif ($negated_type === 'true' && isset($existing_var_type->getTypes()['bool'])) {
                $existing_var_type->removeType('bool');
                $existing_var_type->addType(new TFalse);
            } else {
                $existing_var_type->removeType($negated_type);
            }

            if (empty($existing_var_type->getTypes())) {
                if ($key !== '$this'
                    || !($statements_checker->getSource()->getSource() instanceof TraitChecker)
                ) {
                    if ($key && $code_location) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $key,
                            $new_var_type,
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

        if ($new_var_type === '^isset' || $new_var_type === 'isset') {
            $existing_var_type->removeType('null');

            if (empty($existing_var_type->getTypes())) {
                $failed_reconciliation = true;

                // @todo - I think there's a better way to handle this, but for the moment
                // mixed will have to do.
                return Type::getMixed();
            }

            return $existing_var_type;
        }

        if ($new_var_type === '^!empty') {
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

        $is_strict_equality = false;

        if ($new_var_type[0] === '^') {
            $new_var_type = substr($new_var_type, 1);
            $is_strict_equality = true;
        }

        if ($new_var_type === 'falsy' || $new_var_type === 'empty') {
            if ($existing_var_type->isMixed()) {
                return $existing_var_type;
            }

            $did_remove_type = $existing_var_type->hasString() || $existing_var_type->hasNumericType();

            if ($existing_var_type->hasType('bool')) {
                $did_remove_type = true;
                $existing_var_type->removeType('bool');
                $existing_var_type->addType(new TFalse);
            }

            if ($existing_var_type->hasType('true')) {
                $did_remove_type = true;
                $existing_var_type->removeType('true');
            }

            if ($existing_var_type->hasType('array')
                && $existing_var_type->getTypes()['array']->getId() !== 'array<empty, empty>'
            ) {
                $did_remove_type = true;
                $existing_var_type->addType(new TArray(
                    [
                        new Type\Union([new TEmpty]),
                        new Type\Union([new TEmpty]),
                    ]
                ));
            }

            foreach ($existing_var_type->getTypes() as $type_key => $type) {
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
                        $key,
                        $new_var_type,
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

            foreach ($existing_var_type->getTypes() as $type) {
                if ($type->isObjectType()) {
                    $object_types[] = $type;
                } else {
                    $did_remove_type = true;
                }
            }

            if ((!$object_types || !$did_remove_type) && !$is_strict_equality) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $key,
                        $new_var_type,
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

            if ((!$did_remove_type || !$numeric_types) && !$is_strict_equality) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $key,
                        $new_var_type,
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

            foreach ($existing_var_type->getTypes() as $type) {
                if ($type instanceof Scalar) {
                    $scalar_types[] = $type;
                } else {
                    $did_remove_type = true;
                }
            }

            if ((!$did_remove_type || !$scalar_types) && !$is_strict_equality) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $key,
                        $new_var_type,
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

            foreach ($existing_var_type->getTypes() as $type) {
                if ($type instanceof TBool) {
                    $bool_types[] = $type;
                } else {
                    $did_remove_type = true;
                }
            }

            if ((!$did_remove_type || !$bool_types) && !$is_strict_equality) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $key,
                        $new_var_type,
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

        $new_type = Type::parseString($new_var_type);

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
                    $acceptable_atomic_types[] = $existing_var_type_part;
                    continue;
                }

                if ($existing_var_type_part instanceof TNamedObject
                    && ($codebase->classExists($existing_var_type_part->value)
                        || $codebase->interfaceExists($existing_var_type_part->value))
                ) {
                    $existing_var_type_part->addIntersectionType($new_type_part);
                    $acceptable_atomic_types[] = $existing_var_type_part;
                }
            }

            if ($acceptable_atomic_types) {
                return new Type\Union($acceptable_atomic_types);
            }
        } elseif ($code_location && !$new_type->isMixed()) {
            $has_match = true;

            if ($key && $new_type->getId() === $existing_var_type->getId() && !$is_strict_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $key,
                    $new_var_type,
                    $code_location,
                    $suppressed_issues
                );
            }

            foreach ($new_type->getTypes() as $new_type_part) {
                $has_local_match = false;

                foreach ($existing_var_type->getTypes() as $existing_var_type_part) {
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
                        break;
                    }
                }

                if (!$has_local_match) {
                    $has_match = false;
                    break;
                }
            }

            if (!$has_match) {
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
     * @param  string       $key
     * @param  string       $new_var_type
     * @param  string[]     $suppressed_issues
     *
     * @return void
     */
    private static function triggerIssueForImpossible(
        Union $existing_var_type,
        $key,
        $new_var_type,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $reconciliation = ' and trying to reconcile type \'' . $existing_var_type . '\' to ' . $new_var_type;

        $existing_var_atomic_types = $existing_var_type->getTypes();
        $potential_key = str_replace('!', '', $new_var_type);

        $from_docblock = $existing_var_type->from_docblock
            || (isset($existing_var_atomic_types[$potential_key])
                && $existing_var_atomic_types[$potential_key]->from_docblock);

        if ($from_docblock) {
            if (IssueBuffer::accepts(
                new RedundantConditionGivenDocblockType(
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
                new RedundantCondition(
                    'Found a redundant condition when evaluating ' . $key . $reconciliation,
                    $code_location
                ),
                $suppressed_issues
            )) {
                // fall through
            }
        }
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
        $key_parts = preg_split('/(->|\[|\])/', $key, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

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
                        } elseif ($existing_key_type_part instanceof TMixed ||
                            $existing_key_type_part instanceof TObject ||
                            ($existing_key_type_part instanceof TNamedObject &&
                                strtolower($existing_key_type_part->value) === 'stdclass')
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
