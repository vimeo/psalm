<?php

namespace Psalm\Internal\Type;

use function count;
use function get_class;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Reconciler;
use function strpos;
use function strtolower;
use function substr;

class NegatedAssertionReconciler extends Reconciler
{
    /**
     * @param  string     $assertion
     * @param  bool       $is_strict_equality
     * @param  bool       $is_loose_equality
     * @param   array<string, array<string, array{Type\Union}>> $template_type_map
     * @param  string     $old_var_type_string
     * @param  string|null $key
     * @param  CodeLocation|null $code_location
     * @param  string[]   $suppressed_issues
     * @param  0|1|2      $failed_reconciliation
     *
     * @return Type\Union
     */
    public static function reconcile(
        StatementsAnalyzer $statements_analyzer,
        $assertion,
        $is_strict_equality,
        $is_loose_equality,
        Type\Union $existing_var_type,
        array $template_type_map,
        $old_var_type_string,
        $key,
        $code_location,
        $suppressed_issues,
        &$failed_reconciliation
    ) {
        $is_equality = $is_strict_equality || $is_loose_equality;

        // this is a specific value comparison type that cannot be negated
        if ($is_equality && $bracket_pos = strpos($assertion, '(')) {
            if ($existing_var_type->hasMixed()) {
                return $existing_var_type;
            }

            return self::handleLiteralNegatedEquality(
                $statements_analyzer,
                $assertion,
                $bracket_pos,
                $existing_var_type,
                $old_var_type_string,
                $key,
                $code_location,
                $suppressed_issues,
                $is_strict_equality
            );
        }

        if (!$is_equality) {
            if ($assertion === 'isset') {
                if ($existing_var_type->possibly_undefined) {
                    return Type::getEmpty();
                }

                if (!$existing_var_type->isNullable()
                    && $key
                    && strpos($key, '[') === false
                    && $key !== '$_SESSION'
                ) {
                    foreach ($existing_var_type->getAtomicTypes() as $atomic) {
                        if (!$existing_var_type->hasMixed()
                            || $atomic instanceof Type\Atomic\TNonEmptyMixed
                        ) {
                            $failed_reconciliation = 2;

                            if ($code_location) {
                                if ($existing_var_type->from_docblock) {
                                    if (IssueBuffer::accepts(
                                        new DocblockTypeContradiction(
                                            'Cannot resolve types for ' . $key . ' with docblock-defined type '
                                                . $existing_var_type . ' and !isset assertion',
                                            $code_location
                                        ),
                                        $suppressed_issues
                                    )) {
                                        // fall through
                                    }
                                } else {
                                    if (IssueBuffer::accepts(
                                        new TypeDoesNotContainType(
                                            'Cannot resolve types for ' . $key . ' with type '
                                                . $existing_var_type . ' and !isset assertion',
                                            $code_location
                                        ),
                                        $suppressed_issues
                                    )) {
                                        // fall through
                                    }
                                }
                            }

                            return $existing_var_type->from_docblock
                                ? Type::getNull()
                                : Type::getEmpty();
                        }
                    }
                }

                return Type::getNull();
            } elseif ($assertion === 'array-key-exists') {
                return Type::getEmpty();
            } elseif (substr($assertion, 0, 9) === 'in-array-') {
                return $existing_var_type;
            }
        }

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($assertion === 'object' && !$existing_var_type->hasMixed()) {
            return self::reconcileObject(
                $existing_var_type,
                $key,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'scalar' && !$existing_var_type->hasMixed()) {
            return self::reconcileScalar(
                $existing_var_type,
                $key,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'bool' && !$existing_var_type->hasMixed()) {
            return self::reconcileBool(
                $existing_var_type,
                $key,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'numeric' && !$existing_var_type->hasMixed()) {
            return self::reconcileNumeric(
                $existing_var_type,
                $key,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'string' && !$existing_var_type->hasMixed()) {
            return self::reconcileString(
                $existing_var_type,
                $key,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'array' && !$existing_var_type->hasMixed()) {
            return self::reconcileArray(
                $existing_var_type,
                $key,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'falsy' || $assertion === 'empty') {
            return self::reconcileFalsyOrEmpty(
                $assertion,
                $existing_var_type,
                $key,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                $is_strict_equality
            );
        }

        if ($assertion === 'null' && !$existing_var_type->hasMixed()) {
            return self::reconcileNull(
                $existing_var_type,
                $key,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'non-empty-countable') {
            return self::reconcileNonEmptyCountable(
                $existing_var_type,
                $key,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                null
            );
        }

        if (substr($assertion, 0, 13) === 'has-at-least-') {
            return self::reconcileNonEmptyCountable(
                $existing_var_type,
                $key,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                (int) substr($assertion, 13)
            );
        }

        if ($assertion === 'callable') {
            return self::reconcileCallable(
                $existing_var_type
            );
        }

        if ($assertion === 'iterable' || $assertion === 'countable') {
            $existing_var_type->removeType('array');
        }

        if (!$is_equality
            && isset($existing_var_atomic_types['int'])
            && $existing_var_type->from_calculation
            && ($assertion === 'int' || $assertion === 'float')
        ) {
            $existing_var_type->removeType($assertion);

            if ($assertion === 'int') {
                $existing_var_type->addType(new Type\Atomic\TFloat);
            } else {
                $existing_var_type->addType(new Type\Atomic\TInt);
            }

            $existing_var_type->from_calculation = false;

            return $existing_var_type;
        }

        if ($assertion === 'false' && isset($existing_var_atomic_types['bool'])) {
            $existing_var_type->removeType('bool');
            $existing_var_type->addType(new TTrue);
        } elseif ($assertion === 'true' && isset($existing_var_atomic_types['bool'])) {
            $existing_var_type->removeType('bool');
            $existing_var_type->addType(new TFalse);
        }

        if (strtolower($assertion) === 'traversable'
            && isset($existing_var_atomic_types['iterable'])
        ) {
            /** @var Type\Atomic\TIterable */
            $iterable = $existing_var_atomic_types['iterable'];
            $existing_var_type->removeType('iterable');
            $existing_var_type->addType(new TArray(
                [
                    $iterable->type_params[0],
                    $iterable->type_params[1],
                ]
            ));
        } elseif (strtolower($assertion) === 'int'
            && isset($existing_var_type->getAtomicTypes()['array-key'])
        ) {
            $existing_var_type->removeType('array-key');
            $existing_var_type->addType(new TString);
        } elseif (substr($assertion, 0, 9) === 'getclass-') {
            $assertion = substr($assertion, 9);
        } elseif (!$is_equality) {
            $codebase = $statements_analyzer->getCodebase();

            // if there wasn't a direct hit, go deeper, eliminating subtypes
            if (!$existing_var_type->removeType($assertion)) {
                foreach ($existing_var_type->getAtomicTypes() as $part_name => $existing_var_type_part) {
                    if (!$existing_var_type_part->isObjectType() || strpos($assertion, '-')) {
                        continue;
                    }

                    $new_type_part = Atomic::create($assertion);

                    if (!$new_type_part instanceof TNamedObject) {
                        continue;
                    }

                    if (TypeAnalyzer::isAtomicContainedBy(
                        $codebase,
                        $existing_var_type_part,
                        $new_type_part,
                        false,
                        false
                    )) {
                        $existing_var_type->removeType($part_name);
                    } elseif (TypeAnalyzer::isAtomicContainedBy(
                        $codebase,
                        $new_type_part,
                        $existing_var_type_part,
                        false,
                        false
                    )) {
                        $existing_var_type->different = true;
                    }
                }
            }
        }

        if ($is_strict_equality
            && $assertion !== 'isset'
            && ($key !== '$this'
                || !($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer))
        ) {
            $assertion = Type::parseString($assertion, null, $template_type_map);

            if ($key
                && $code_location
                && !TypeAnalyzer::canExpressionTypesBeIdentical(
                    $statements_analyzer->getCodebase(),
                    $existing_var_type,
                    $assertion
                )
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!=' . $assertion,
                    true,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if (empty($existing_var_type->getAtomicTypes())) {
            if ($key !== '$this'
                || !($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
            ) {
                if ($key && $code_location && !$is_equality) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        '!' . $assertion,
                        false,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            $failed_reconciliation = 2;

            return new Type\Union([new Type\Atomic\TEmptyMixed]);
        }

        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileCallable(
        Type\Union $existing_var_type
    ) : Type\Union {
        foreach ($existing_var_type->getAtomicTypes() as $atomic_key => $type) {
            if ($type instanceof Type\Atomic\TLiteralString
                && \Psalm\Internal\Codebase\CallMap::inCallMap($type->value)
            ) {
                $existing_var_type->removeType($atomic_key);
            }

            if ($type->isCallableType()) {
                $existing_var_type->removeType($atomic_key);
            }
        }

        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileBool(
        Type\Union $existing_var_type,
        ?string $key,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_bool_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$type->as->hasBool()) {
                    $non_bool_types[] = $type;
                }

                $did_remove_type = true;
            } elseif (!$type instanceof TBool
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
                    '!bool',
                    !$did_remove_type,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_bool_types) {
            return new Type\Union($non_bool_types);
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileNonEmptyCountable(
        Type\Union $existing_var_type,
        ?string $key,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        ?int $min_count
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if (isset($existing_var_atomic_types['array'])) {
            $array_atomic_type = $existing_var_atomic_types['array'];
            $did_remove_type = false;

            if (($array_atomic_type instanceof Type\Atomic\TNonEmptyArray
                    || $array_atomic_type instanceof Type\Atomic\TNonEmptyList)
                && ($min_count === null
                    || $array_atomic_type->count >= $min_count)
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
            } elseif ($array_atomic_type instanceof Type\Atomic\ObjectLike) {
                $did_remove_type = true;

                foreach ($array_atomic_type->properties as $property_type) {
                    if (!$property_type->possibly_undefined) {
                        $did_remove_type = false;
                        break;
                    }
                }
            }

            if (!$is_equality
                && !$existing_var_type->hasMixed()
                && (!$did_remove_type || empty($existing_var_type->getAtomicTypes()))
            ) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        '!non-empty-countable',
                        !$did_remove_type,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }
        }

        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileNull(
        Type\Union $existing_var_type,
        ?string $key,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $did_remove_type = false;

        if ($existing_var_type->hasType('null')) {
            $did_remove_type = true;
            $existing_var_type->removeType('null');
        }

        if (!$did_remove_type || empty($existing_var_type->getAtomicTypes())) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!null',
                    !$did_remove_type,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($existing_var_type->getAtomicTypes()) {
            return $existing_var_type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileFalsyOrEmpty(
        string $assertion,
        Type\Union $existing_var_type,
        ?string $key,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        bool $is_strict_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $did_remove_type = $existing_var_type->hasDefinitelyNumericType(false)
            || $existing_var_type->isEmpty()
            || $existing_var_type->hasType('bool')
            || $existing_var_type->possibly_undefined
            || $existing_var_type->possibly_undefined_from_try
            || $existing_var_type->hasType('iterable');

        if ($is_strict_equality && $assertion === 'empty') {
            $existing_var_type->removeType('null');
            $existing_var_type->removeType('false');

            if ($existing_var_type->hasType('array')
                && $existing_var_type->getAtomicTypes()['array']->getId() === 'array<empty, empty>'
            ) {
                $existing_var_type->removeType('array');
            }

            if ($existing_var_type->hasMixed()) {
                $existing_var_type->removeType('mixed');

                if (!$existing_var_atomic_types['mixed'] instanceof Type\Atomic\TEmptyMixed) {
                    $existing_var_type->addType(new Type\Atomic\TNonEmptyMixed);
                }
            }

            if ($existing_var_type->hasScalar()) {
                $existing_var_type->removeType('scalar');

                if (!$existing_var_atomic_types['scalar'] instanceof Type\Atomic\TEmptyScalar) {
                    $existing_var_type->addType(new Type\Atomic\TNonEmptyScalar);
                }
            }

            if (isset($existing_var_atomic_types['string'])) {
                $existing_var_type->removeType('string');

                $existing_var_type->addType(new Type\Atomic\TNonEmptyString);
            }

            self::removeFalsyNegatedLiteralTypes(
                $existing_var_type,
                $did_remove_type
            );

            $existing_var_type->possibly_undefined = false;
            $existing_var_type->possibly_undefined_from_try = false;

            if ($existing_var_type->getAtomicTypes()) {
                return $existing_var_type;
            }

            $failed_reconciliation = 2;

            return Type::getMixed();
        }

        if ($existing_var_type->hasMixed()) {
            if ($existing_var_type->isMixed()
                && $existing_var_atomic_types['mixed'] instanceof Type\Atomic\TEmptyMixed
            ) {
                if ($code_location
                    && $key
                    && IssueBuffer::accepts(
                        new ParadoxicalCondition(
                            'Found a paradox when evaluating ' . $key
                                . ' of type ' . $existing_var_type->getId()
                                . ' and trying to reconcile it with a non-' . $assertion . ' assertion',
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
            } elseif ($existing_var_type->isMixed() && !$is_equality) {
                if ($code_location
                    && $key
                    && IssueBuffer::accepts(
                        new RedundantCondition(
                            'Found a redundant condition when evaluating ' . $key
                                . ' of type ' . $existing_var_type->getId()
                                . ' and trying to reconcile it with a non-' . $assertion . ' assertion',
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

        if ($existing_var_type->hasScalar()) {
            if (!$existing_var_atomic_types['scalar'] instanceof Type\Atomic\TNonEmptyScalar) {
                $did_remove_type = true;
                $existing_var_type->removeType('scalar');

                if (!$existing_var_atomic_types['scalar'] instanceof Type\Atomic\TEmptyScalar) {
                    $existing_var_type->addType(new Type\Atomic\TNonEmptyScalar);
                }
            } elseif ($existing_var_type->isSingle() && !$is_equality) {
                if ($code_location
                    && $key
                    && IssueBuffer::accepts(
                        new RedundantCondition(
                            'Found a redundant condition when evaluating ' . $key
                                . ' of type ' . $existing_var_type->getId()
                                . ' and trying to reconcile it with a non-' . $assertion . ' assertion',
                            $code_location
                        ),
                        $suppressed_issues
                    )
                ) {
                    // fall through
                }
            }

            if ($existing_var_type->isSingle()) {
                return $existing_var_type;
            }
        }

        if (isset($existing_var_atomic_types['string'])) {
            if (!$existing_var_atomic_types['string'] instanceof Type\Atomic\TNonEmptyString) {
                $did_remove_type = true;
                if (!$existing_var_atomic_types['string'] instanceof Type\Atomic\TLowercaseString) {
                    $existing_var_type->removeType('string');

                    $existing_var_type->addType(new Type\Atomic\TNonEmptyString);
                }
            } elseif ($existing_var_type->isSingle() && !$is_equality) {
                if ($code_location
                    && $key
                    && IssueBuffer::accepts(
                        new RedundantCondition(
                            'Found a redundant condition when evaluating ' . $key
                                . ' of type ' . $existing_var_type->getId()
                                . ' and trying to reconcile it with a non-' . $assertion . ' assertion',
                            $code_location
                        ),
                        $suppressed_issues
                    )
                ) {
                    // fall through
                }
            }

            if ($existing_var_type->isSingle()) {
                return $existing_var_type;
            }
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

        self::removeFalsyNegatedLiteralTypes(
            $existing_var_type,
            $did_remove_type
        );

        $existing_var_type->possibly_undefined = false;
        $existing_var_type->possibly_undefined_from_try = false;

        if ((!$did_remove_type || empty($existing_var_type->getAtomicTypes())) && !$existing_var_type->hasTemplate()) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!' . $assertion,
                    !$did_remove_type,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($existing_var_type->getAtomicTypes()) {
            return $existing_var_type;
        }

        $failed_reconciliation = 2;

        return Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileScalar(
        Type\Union $existing_var_type,
        ?string $key,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_scalar_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$type->as->hasScalar()) {
                    $non_scalar_types[] = $type;
                }

                $did_remove_type = true;
            } elseif (!($type instanceof Scalar)) {
                $non_scalar_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_scalar_types[] = $type;
                }
            }
        }

        if (!$did_remove_type || !$non_scalar_types) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!scalar',
                    !$did_remove_type,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_scalar_types) {
            $type = new Type\Union($non_scalar_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileObject(
        Type\Union $existing_var_type,
        ?string $key,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_object_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$type->as->hasObject()) {
                    $non_object_types[] = $type;
                }

                $did_remove_type = true;
            } elseif ($type instanceof TCallable) {
                $non_object_types[] = new Atomic\TCallableArray([
                    Type::getArrayKey(),
                    Type::getMixed()
                ]);
                $non_object_types[] = new Atomic\TCallableString();
                $did_remove_type = true;
            } elseif (!$type->isObjectType()) {
                $non_object_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_object_types[] = $type;
                }
            }
        }

        if (!$non_object_types || !$did_remove_type) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!object',
                    !$did_remove_type,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_object_types) {
            $type = new Type\Union($non_object_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileNumeric(
        Type\Union $existing_var_type,
        ?string $key,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_numeric_types = [];
        $did_remove_type = $existing_var_type->hasString()
            || $existing_var_type->hasScalar();

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$type->as->hasNumeric()) {
                    if ($type->as->hasMixed()) {
                        $did_remove_type = true;
                    }

                    $non_numeric_types[] = $type;
                }
            } elseif (!$type->isNumericType()) {
                $non_numeric_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_numeric_types[] = $type;
                }
            }
        }

        if (!$non_numeric_types || !$did_remove_type) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!numeric',
                    !$did_remove_type,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_numeric_types) {
            $type = new Type\Union($non_numeric_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileString(
        Type\Union $existing_var_type,
        ?string $key,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_string_types = [];
        $did_remove_type = $existing_var_type->hasScalar();

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$type->as->hasString()) {
                    if ($type->as->hasMixed()) {
                        $did_remove_type = true;
                    }

                    $non_string_types[] = $type;
                }
            } elseif ($type instanceof TArrayKey) {
                $non_string_types[] = new TInt();
                $did_remove_type = true;
            } elseif ($type instanceof TCallable) {
                $non_string_types[] = new Atomic\TCallableArray([
                    Type::getArrayKey(),
                    Type::getMixed()
                ]);
                $non_string_types[] = new Atomic\TCallableObject();
                $did_remove_type = true;
            } elseif ($type instanceof TNumeric) {
                $non_string_types[] = $type;
                $did_remove_type = true;
            } elseif (!$type instanceof TString) {
                $non_string_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_string_types[] = $type;
                }
            }
        }

        if (!$non_string_types || !$did_remove_type) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!string',
                    !$did_remove_type,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_string_types) {
            $type = new Type\Union($non_string_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileArray(
        Type\Union $existing_var_type,
        ?string $key,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_array_types = [];
        $did_remove_type = $existing_var_type->hasScalar();

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$type->as->hasArray()) {
                    if ($type->as->hasMixed()) {
                        $did_remove_type = true;
                    }

                    $non_array_types[] = $type;
                }
            } elseif ($type instanceof TCallable) {
                $non_array_types[] = new Atomic\TCallableString();
                $non_array_types[] = new Atomic\TCallableObject();
                $did_remove_type = true;
            } elseif ($type instanceof Atomic\TIterable) {
                if (!$type->type_params[0]->isMixed() || !$type->type_params[1]->isMixed()) {
                    $non_array_types[] = new Atomic\TGenericObject('Traversable', $type->type_params);
                } else {
                    $non_array_types[] = new TNamedObject('Traversable');
                }

                $did_remove_type = true;
            } elseif (!$type instanceof TArray
                && !$type instanceof ObjectLike
                && !$type instanceof Atomic\TList
            ) {
                $non_array_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_array_types[] = $type;
                }
            }
        }

        if ((!$non_array_types || !$did_remove_type)) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!array',
                    !$did_remove_type,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_array_types) {
            $type = new Type\Union($non_array_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @return void
     */
    private static function removeFalsyNegatedLiteralTypes(
        Type\Union $existing_var_type,
        bool &$did_remove_type
    ) {
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
            $array_atomic_type = $existing_var_type->getAtomicTypes()['array'];

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
            } elseif ($array_atomic_type instanceof Type\Atomic\TList
                && !$array_atomic_type instanceof Type\Atomic\TNonEmptyList
            ) {
                $did_remove_type = true;

                $existing_var_type->addType(
                    new Type\Atomic\TNonEmptyList(
                        $array_atomic_type->type_param
                    )
                );
            } elseif ($array_atomic_type instanceof Type\Atomic\ObjectLike
                && !$array_atomic_type->sealed
            ) {
                $did_remove_type = true;
            }
        }
    }

    /**
     * @param  string     $assertion
     * @param  int        $bracket_pos
     * @param  string     $old_var_type_string
     * @param  string|null $key
     * @param  CodeLocation|null $code_location
     * @param  string[]   $suppressed_issues
     *
     * @return Type\Union
     */
    private static function handleLiteralNegatedEquality(
        StatementsAnalyzer $statements_analyzer,
        string $assertion,
        int $bracket_pos,
        Type\Union $existing_var_type,
        string $old_var_type_string,
        ?string $key,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        bool $is_strict_equality
    ) {
        $scalar_type = substr($assertion, 0, $bracket_pos);

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $did_remove_type = false;
        $did_match_literal_type = false;

        $scalar_var_type = null;

        if ($scalar_type === 'int') {
            if ($existing_var_type->hasInt()) {
                if ($existing_int_types = $existing_var_type->getLiteralInts()) {
                    $did_match_literal_type = true;

                    if (isset($existing_int_types[$assertion])) {
                        $existing_var_type->removeType($assertion);

                        $did_remove_type = true;
                    }
                }
            } else {
                $scalar_value = substr($assertion, $bracket_pos + 1, -1);
                $scalar_var_type = Type::getInt(false, (int) $scalar_value);
            }
        } elseif ($scalar_type === 'string'
            || $scalar_type === 'class-string'
            || $scalar_type === 'interface-string'
            || $scalar_type === 'trait-string'
            || $scalar_type === 'callable-string'
        ) {
            if ($existing_var_type->hasString()) {
                if ($existing_string_types = $existing_var_type->getLiteralStrings()) {
                    $did_match_literal_type = true;

                    if (isset($existing_string_types[$assertion])) {
                        $existing_var_type->removeType($assertion);

                        $did_remove_type = true;
                    }
                }
            } elseif ($scalar_type === 'string') {
                $scalar_value = substr($assertion, $bracket_pos + 1, -1);
                $scalar_var_type = Type::getString($scalar_value);
            }
        } elseif ($scalar_type === 'float') {
            if ($existing_var_type->hasFloat()) {
                if ($existing_float_types = $existing_var_type->getLiteralFloats()) {
                    $did_match_literal_type = true;

                    if (isset($existing_float_types[$assertion])) {
                        $existing_var_type->removeType($assertion);

                        $did_remove_type = true;
                    }
                }
            } else {
                $scalar_value = substr($assertion, $bracket_pos + 1, -1);
                $scalar_var_type = Type::getFloat((float) $scalar_value);
            }
        }

        if ($key && $code_location) {
            if ($did_match_literal_type
                && (!$did_remove_type || count($existing_var_atomic_types) === 1)
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!' . $assertion,
                    !$did_remove_type,
                    $code_location,
                    $suppressed_issues
                );
            } elseif ($scalar_var_type
                && $is_strict_equality
                && ($key !== '$this'
                    || !($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer))
            ) {
                if (!TypeAnalyzer::canExpressionTypesBeIdentical(
                    $statements_analyzer->getCodebase(),
                    $existing_var_type,
                    $scalar_var_type
                )) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        '!=' . $assertion,
                        true,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }
        }

        return $existing_var_type;
    }
}
