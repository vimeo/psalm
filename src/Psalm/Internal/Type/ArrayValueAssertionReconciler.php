<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Storage\Assertion\DoesNotHaveArrayValue;
use Psalm\Storage\Assertion\HasArrayValue;
use Psalm\Storage\Assertion\IsIdentical;
use Psalm\Storage\Assertion\IsNotIdentical;
use Psalm\Storage\Assertion\IsNotLooselyEqual;
use Psalm\Storage\Assertion\IsNotType;
use Psalm\Storage\Assertion\IsType;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_key_first;
use function count;
use function is_int;

/**
 * Narrows an array type from `in_array($singleton, $array, $strict)`.
 *
 * @internal
 */
final class ArrayValueAssertionReconciler
{
    /**
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    public static function reconcileHasArrayValue(
        HasArrayValue $assertion,
        Union $existing_var_type,
        StatementsAnalyzer $statements_analyzer,
        bool $inside_loop,
        int &$failed_reconciliation,
    ): Union {
        $kept_types = [];
        $changed = false;

        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TTemplateParam) {
                $template_failed = Reconciler::RECONCILIATION_OK;
                $reconciled_as = self::reconcileHasArrayValue(
                    $assertion,
                    $atomic_type->as,
                    $statements_analyzer,
                    $inside_loop,
                    $template_failed,
                );

                if ($template_failed === Reconciler::RECONCILIATION_EMPTY || $reconciled_as->isNever()) {
                    $changed = true;
                    continue;
                }

                if ($reconciled_as !== $atomic_type->as) {
                    $kept_types[] = $atomic_type->replaceAs($reconciled_as);
                    $changed = true;
                } else {
                    $kept_types[] = $atomic_type;
                }
            } elseif ($atomic_type instanceof TArray) {
                if ($atomic_type->isEmptyArray()) {
                    $changed = true;
                    continue;
                }

                if ($assertion->strict) {
                    $matched = self::matchStrictElement(
                        $assertion->value_type,
                        $atomic_type->type_params[1],
                        $statements_analyzer,
                        $inside_loop,
                    );

                    if ($matched === null) {
                        $changed = true;
                        continue;
                    }
                }

                if (!$atomic_type instanceof TNonEmptyArray) {
                    $kept_types[] = new TNonEmptyArray(
                        $atomic_type->type_params,
                        null,
                        null,
                        'non-empty-array',
                        $atomic_type->from_docblock,
                    );
                    $changed = true;
                } else {
                    $kept_types[] = $atomic_type;
                }
            } elseif ($atomic_type instanceof TKeyedArray) {
                $reconciled_keyed = self::reconcileHasArrayValueOnKeyedArray(
                    $assertion,
                    $atomic_type,
                    $statements_analyzer,
                    $inside_loop,
                );

                if ($reconciled_keyed === null) {
                    $changed = true;
                    continue;
                }

                if ($reconciled_keyed !== $atomic_type) {
                    $changed = true;
                }

                $kept_types[] = $reconciled_keyed;
            } else {
                $kept_types[] = $atomic_type;
            }
        }

        if (!$changed) {
            return $existing_var_type;
        }

        if ($kept_types === []) {
            $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

            return Type::getNever();
        }

        return $existing_var_type->setTypes($kept_types);
    }

    /**
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    public static function reconcileDoesNotHaveArrayValue(
        DoesNotHaveArrayValue $assertion,
        Union $existing_var_type,
        StatementsAnalyzer $statements_analyzer,
        bool $inside_loop,
        int &$failed_reconciliation,
    ): Union {
        $kept_types = [];
        $changed = false;

        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TTemplateParam) {
                $template_failed = Reconciler::RECONCILIATION_OK;
                $reconciled_as = self::reconcileDoesNotHaveArrayValue(
                    $assertion,
                    $atomic_type->as,
                    $statements_analyzer,
                    $inside_loop,
                    $template_failed,
                );

                if ($template_failed === Reconciler::RECONCILIATION_EMPTY || $reconciled_as->isNever()) {
                    $changed = true;
                    continue;
                }

                if ($reconciled_as !== $atomic_type->as) {
                    $kept_types[] = $atomic_type->replaceAs($reconciled_as);
                    $changed = true;
                } else {
                    $kept_types[] = $atomic_type;
                }
            } elseif ($atomic_type instanceof TArray) {
                if ($atomic_type->isEmptyArray()) {
                    $kept_types[] = $atomic_type;
                    continue;
                }

                $value_type = $atomic_type->type_params[1];
                $reconciled_value = self::excludeElement(
                    $assertion->value_type,
                    $assertion->strict,
                    $value_type,
                    $statements_analyzer,
                    $inside_loop,
                );

                if ($reconciled_value === null) {
                    $changed = true;

                    if (!$atomic_type instanceof TNonEmptyArray) {
                        $kept_types[] = Type::getEmptyArrayAtomic();
                    }

                    continue;
                }

                if ($reconciled_value->getId() !== $value_type->getId()) {
                    $new_params = [$atomic_type->type_params[0], $reconciled_value];
                    $kept_types[] = $atomic_type instanceof TNonEmptyArray
                        ? new TNonEmptyArray(
                            $new_params,
                            $atomic_type->count,
                            $atomic_type->min_count,
                            $atomic_type->value,
                            $atomic_type->from_docblock,
                        )
                        : new TArray($new_params, $atomic_type->from_docblock);
                    $changed = true;
                } else {
                    $kept_types[] = $atomic_type;
                }
            } elseif ($atomic_type instanceof TKeyedArray) {
                $reconciled_keyed = self::reconcileDoesNotHaveArrayValueOnKeyedArray(
                    $assertion,
                    $atomic_type,
                    $statements_analyzer,
                    $inside_loop,
                );

                if ($reconciled_keyed === null) {
                    $changed = true;
                    continue;
                }

                if ($reconciled_keyed !== $atomic_type) {
                    $changed = true;
                }

                $kept_types[] = $reconciled_keyed;
            } else {
                $kept_types[] = $atomic_type;
            }
        }

        if (!$changed) {
            return $existing_var_type;
        }

        if ($kept_types === []) {
            $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

            return Type::getNever();
        }

        return $existing_var_type->setTypes($kept_types);
    }

    private static function reconcileHasArrayValueOnKeyedArray(
        HasArrayValue $assertion,
        TKeyedArray $atomic_type,
        StatementsAnalyzer $statements_analyzer,
        bool $inside_loop,
    ): TKeyedArray|TArray|null {
        // Non-strict PHP equality has cross-type coercions (numeric strings, PHP 7.4 string==0, SimpleXMLElement),
        // so only enforce non-emptiness without eliminating properties as non-matching.
        if (!$assertion->strict) {
            if ($atomic_type->fallback_params === null && count($atomic_type->properties) === 1) {
                $only_key = array_key_first($atomic_type->properties);
                if ($atomic_type->properties[$only_key]->possibly_undefined) {
                    $properties = $atomic_type->properties;
                    $properties[$only_key] = $properties[$only_key]->setPossiblyUndefined(false, false);

                    return $atomic_type->setProperties($properties);
                }

                return $atomic_type;
            }

            if ($atomic_type->is_list
                && isset($atomic_type->properties[0])
                && $atomic_type->properties[0]->possibly_undefined
            ) {
                $properties = $atomic_type->properties;
                $properties[0] = $properties[0]->setPossiblyUndefined(false, false);

                return $atomic_type->setProperties($properties);
            }

            return $atomic_type;
        }

        $matching_props = [];

        foreach ($atomic_type->properties as $prop_key => $prop_type) {
            $stripped = $prop_type->setPossiblyUndefined(false, false);
            $matched = self::matchStrictElement(
                $assertion->value_type,
                $stripped,
                $statements_analyzer,
                $inside_loop,
            );

            if ($matched !== null) {
                $matching_props[$prop_key] = $matched;
            }
        }

        $fallback_matches = $atomic_type->fallback_params !== null
            && self::matchStrictElement(
                $assertion->value_type,
                $atomic_type->fallback_params[1],
                $statements_analyzer,
                $inside_loop,
            ) !== null;

        if ($matching_props === [] && !$fallback_matches) {
            return null;
        }

        if ($atomic_type->fallback_params === null && count($matching_props) === 1) {
            $only_key = array_key_first($matching_props);
            $properties = $atomic_type->properties;
            $properties[$only_key] = $matching_props[$only_key]->setPossiblyUndefined(false, false);

            if ($atomic_type->is_list && is_int($only_key)) {
                for ($i = 0; $i < $only_key; $i++) {
                    if (isset($properties[$i])) {
                        $properties[$i] = $properties[$i]->setPossiblyUndefined(false, false);
                    }
                }
            }

            return $atomic_type->setProperties($properties);
        }

        if ($atomic_type->is_list
            && isset($atomic_type->properties[0])
            && $atomic_type->properties[0]->possibly_undefined
        ) {
            $properties = $atomic_type->properties;
            $properties[0] = $properties[0]->setPossiblyUndefined(false, false);

            return $atomic_type->setProperties($properties);
        }

        return $atomic_type;
    }

    private static function reconcileDoesNotHaveArrayValueOnKeyedArray(
        DoesNotHaveArrayValue $assertion,
        TKeyedArray $atomic_type,
        StatementsAnalyzer $statements_analyzer,
        bool $inside_loop,
    ): TKeyedArray|TArray|null {
        $new_properties = [];
        $properties_changed = false;
        $drop_list_tail = false;
        $class_strings = $atomic_type->class_strings;

        foreach ($atomic_type->properties as $prop_key => $prop_type) {
            if ($drop_list_tail) {
                $properties_changed = true;
                continue;
            }

            $was_possibly_undefined = $prop_type->possibly_undefined;
            $was_possibly_undefined_from_try = $prop_type->possibly_undefined_from_try;
            $stripped = $prop_type->setPossiblyUndefined(false, false);

            $reconciled_prop = self::excludeElement(
                $assertion->value_type,
                $assertion->strict,
                $stripped,
                $statements_analyzer,
                $inside_loop,
            );

            if ($reconciled_prop === null) {
                if (!$was_possibly_undefined) {
                    return null;
                }

                $properties_changed = true;

                if ($class_strings !== null) {
                    unset($class_strings[$prop_key]);
                }

                if ($atomic_type->is_list) {
                    $drop_list_tail = true;
                }

                continue;
            }

            if ($reconciled_prop->getId() !== $stripped->getId()) {
                $new_properties[$prop_key] = $reconciled_prop->setPossiblyUndefined(
                    $was_possibly_undefined,
                    $was_possibly_undefined_from_try,
                );
                $properties_changed = true;
            } else {
                $new_properties[$prop_key] = $prop_type;
            }
        }

        $new_fallback = $atomic_type->fallback_params;
        $fallback_changed = false;

        if ($drop_list_tail) {
            if ($new_fallback !== null) {
                $new_fallback = null;
                $fallback_changed = true;
            }
        } elseif ($new_fallback !== null) {
            $reconciled_fallback = self::excludeElement(
                $assertion->value_type,
                $assertion->strict,
                $new_fallback[1],
                $statements_analyzer,
                $inside_loop,
            );

            if ($reconciled_fallback === null) {
                $new_fallback = null;
                $fallback_changed = true;
            } elseif ($reconciled_fallback->getId() !== $new_fallback[1]->getId()) {
                $new_fallback = [$new_fallback[0], $reconciled_fallback];
                $fallback_changed = true;
            }
        }

        if (!$properties_changed && !$fallback_changed) {
            return $atomic_type;
        }

        if ($new_properties === []) {
            if ($new_fallback === null) {
                return Type::getEmptyArrayAtomic();
            }

            return new TArray($new_fallback, $atomic_type->from_docblock);
        }

        $rebuilt = TKeyedArray::make(
            $new_properties,
            $class_strings !== null && $class_strings !== [] ? $class_strings : null,
            $new_fallback,
            $atomic_type->is_list,
            $atomic_type->from_docblock,
        );

        if ($atomic_type->is_callable && $rebuilt instanceof TKeyedArray) {
            $rebuilt = $rebuilt->setIsCallable(true);
        }

        return $rebuilt;
    }

    private static function matchStrictElement(
        Atomic $value_type,
        Union $element_type,
        StatementsAnalyzer $statements_analyzer,
        bool $inside_loop,
    ): ?Union {
        if ($element_type->isNever() || $element_type->isUnionEmpty()) {
            return null;
        }

        $element_assertion = $value_type instanceof TNull
            || $value_type instanceof TTrue
            || $value_type instanceof TFalse
            ? new IsType($value_type)
            : new IsIdentical($value_type);

        $failed = Reconciler::RECONCILIATION_OK;
        $reconciled = AssertionReconciler::reconcile(
            $element_assertion,
            $element_type,
            null,
            $statements_analyzer,
            $inside_loop,
            [],
            null,
            [],
            $failed,
        );

        if ($failed === Reconciler::RECONCILIATION_EMPTY
            || $reconciled->isNever()
            || $reconciled->isUnionEmpty()
        ) {
            return null;
        }

        return $reconciled;
    }

    private static function excludeElement(
        Atomic $value_type,
        bool $strict,
        Union $element_type,
        StatementsAnalyzer $statements_analyzer,
        bool $inside_loop,
    ): ?Union {
        if ($element_type->isNever() || $element_type->isUnionEmpty()) {
            return null;
        }

        $element_assertion = $strict
            ? ($value_type instanceof TNull
                || $value_type instanceof TTrue
                || $value_type instanceof TFalse
                ? new IsNotType($value_type)
                : new IsNotIdentical($value_type))
            : new IsNotLooselyEqual($value_type);

        $failed = Reconciler::RECONCILIATION_OK;
        $reconciled = AssertionReconciler::reconcile(
            $element_assertion,
            $element_type,
            null,
            $statements_analyzer,
            $inside_loop,
            [],
            null,
            [],
            $failed,
        );

        if ($failed === Reconciler::RECONCILIATION_EMPTY
            || $reconciled->isNever()
            || $reconciled->isUnionEmpty()
        ) {
            return null;
        }

        return $reconciled;
    }
}
