<?php
namespace Psalm\Internal\Codebase;

use Psalm\Internal\Scanner\UnresolvedConstant;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Type;
use ReflectionProperty;
use function ctype_digit;

/**
 * @internal
 */
class ConstantTypeResolver
{
    public static function resolve(
        ClassLikes $classlikes,
        \Psalm\Internal\Scanner\UnresolvedConstantComponent $c,
        \Psalm\Internal\Analyzer\StatementsAnalyzer $statements_analyzer = null,
        array $visited_constant_ids = []
    ) : Type\Atomic {
        $c_id = \spl_object_id($c);

        if (isset($visited_constant_ids[$c_id])) {
            throw new \Psalm\Exception\CircularReferenceException('Found a circular reference');
        }

        if ($c instanceof UnresolvedConstant\ScalarValue) {
            return self::getLiteralTypeFromScalarValue($c->value);
        }

        if ($c instanceof UnresolvedConstant\UnresolvedBinaryOp) {
            $left = self::resolve(
                $classlikes,
                $c->left,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );
            $right = self::resolve(
                $classlikes,
                $c->right,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            if ($left instanceof Type\Atomic\TMixed || $right instanceof Type\Atomic\TMixed) {
                return new Type\Atomic\TMixed;
            }

            if ($c instanceof UnresolvedConstant\UnresolvedConcatOp) {
                if (($left instanceof Type\Atomic\TLiteralString
                        || $left instanceof Type\Atomic\TLiteralFloat
                        || $left instanceof Type\Atomic\TLiteralInt)
                    && ($right instanceof Type\Atomic\TLiteralString
                        || $right instanceof Type\Atomic\TLiteralFloat
                        || $right instanceof Type\Atomic\TLiteralInt)
                ) {
                    return new Type\Atomic\TLiteralString($left->value . $right->value);
                }

                return new Type\Atomic\TString();
            }

            if ($c instanceof UnresolvedConstant\UnresolvedAdditionOp
                || $c instanceof UnresolvedConstant\UnresolvedSubtractionOp
                || $c instanceof UnresolvedConstant\UnresolvedDivisionOp
                || $c instanceof UnresolvedConstant\UnresolvedMultiplicationOp
                || $c instanceof UnresolvedConstant\UnresolvedBitwiseOr
                || $c instanceof UnresolvedConstant\UnresolvedBitwiseXor
                || $c instanceof UnresolvedConstant\UnresolvedBitwiseAnd
            ) {
                if (($left instanceof Type\Atomic\TLiteralFloat || $left instanceof Type\Atomic\TLiteralInt)
                    && ($right instanceof Type\Atomic\TLiteralFloat || $right instanceof Type\Atomic\TLiteralInt)
                ) {
                    if ($c instanceof UnresolvedConstant\UnresolvedAdditionOp) {
                        return self::getLiteralTypeFromScalarValue($left->value + $right->value);
                    }

                    if ($c instanceof UnresolvedConstant\UnresolvedSubtractionOp) {
                        return self::getLiteralTypeFromScalarValue($left->value - $right->value);
                    }

                    if ($c instanceof UnresolvedConstant\UnresolvedDivisionOp) {
                        return self::getLiteralTypeFromScalarValue($left->value / $right->value);
                    }

                    if ($c instanceof UnresolvedConstant\UnresolvedBitwiseOr) {
                        return self::getLiteralTypeFromScalarValue($left->value | $right->value);
                    }

                    if ($c instanceof UnresolvedConstant\UnresolvedBitwiseXor) {
                        return self::getLiteralTypeFromScalarValue($left->value ^ $right->value);
                    }

                    if ($c instanceof UnresolvedConstant\UnresolvedBitwiseAnd) {
                        return self::getLiteralTypeFromScalarValue($left->value & $right->value);
                    }

                    return self::getLiteralTypeFromScalarValue($left->value * $right->value);
                }

                if ($left instanceof Type\Atomic\TKeyedArray && $right instanceof Type\Atomic\TKeyedArray) {
                    return new Type\Atomic\TKeyedArray($left->properties + $right->properties);
                }

                return new Type\Atomic\TMixed;
            }

            return new Type\Atomic\TMixed;
        }

        if ($c instanceof UnresolvedConstant\UnresolvedTernary) {
            $cond = self::resolve(
                $classlikes,
                $c->cond,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );
            $if = $c->if ? self::resolve(
                $classlikes,
                $c->if,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            ) : null;
            $else = self::resolve(
                $classlikes,
                $c->else,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            if ($cond instanceof Type\Atomic\TLiteralFloat
                || $cond instanceof Type\Atomic\TLiteralInt
                || $cond instanceof Type\Atomic\TLiteralString
            ) {
                if ($cond->value) {
                    return $if ? $if : $cond;
                }
            } elseif ($cond instanceof Type\Atomic\TFalse || $cond instanceof Type\Atomic\TNull) {
                return $else;
            } elseif ($cond instanceof Type\Atomic\TTrue) {
                return $if ? $if : $cond;
            }
        }

        if ($c instanceof UnresolvedConstant\ArrayValue) {
            $properties = [];
            $auto_key = 0;

            if (!$c->entries) {
                return new Type\Atomic\TArray([Type::getEmpty(), Type::getEmpty()]);
            }

            $is_list = true;

            foreach ($c->entries as $entry) {
                if ($entry instanceof UnresolvedConstant\ArraySpread) {
                    $spread_array = self::resolve(
                        $classlikes,
                        $entry->array,
                        $statements_analyzer,
                        $visited_constant_ids + [$c_id => true]
                    );

                    if ($spread_array instanceof Type\Atomic\TArray && $spread_array->type_params[1]->isEmpty()) {
                        continue;
                    }

                    if (!$spread_array instanceof Type\Atomic\TKeyedArray) {
                        return new Type\Atomic\TArray([Type::getArrayKey(), Type::getMixed()]);
                    }

                    foreach ($spread_array->properties as $spread_array_type) {
                        $properties[$auto_key++] = $spread_array_type;
                    }
                    continue;
                }

                if ($entry->key) {
                    $key_type = self::resolve(
                        $classlikes,
                        $entry->key,
                        $statements_analyzer,
                        $visited_constant_ids + [$c_id => true]
                    );

                    if (!$key_type instanceof Type\Atomic\TLiteralInt
                        || $key_type->value !== $auto_key
                    ) {
                        $is_list = false;
                    }
                } else {
                    $key_type = new Type\Atomic\TLiteralInt($auto_key);
                }

                if ($key_type instanceof Type\Atomic\TLiteralInt
                    || $key_type instanceof Type\Atomic\TLiteralString
                ) {
                    $key_value = $key_type->value;
                    if ($key_type instanceof Type\Atomic\TLiteralInt) {
                        $auto_key = $key_type->value + 1;
                    } elseif (ctype_digit($key_type->value)) {
                        $auto_key = ((int) $key_type->value) + 1;
                    }
                } else {
                    return new Type\Atomic\TArray([Type::getArrayKey(), Type::getMixed()]);
                }

                $value_type = new Type\Union([self::resolve(
                    $classlikes,
                    $entry->value,
                    $statements_analyzer,
                    $visited_constant_ids + [$c_id => true]
                )]);

                $properties[$key_value] = $value_type;
            }

            if (empty($properties)) {
                $resolved_type = new Type\Atomic\TArray([
                    new Type\Union([new Type\Atomic\TEmpty()]),
                    new Type\Union([new Type\Atomic\TEmpty()]),
                ]);
            } else {
                $resolved_type = new Type\Atomic\TKeyedArray($properties);

                $resolved_type->is_list = $is_list;
                $resolved_type->sealed = true;
            }

            return $resolved_type;
        }

        if ($c instanceof UnresolvedConstant\ClassConstant) {
            if ($c->name === 'class') {
                return new Type\Atomic\TLiteralClassString($c->fqcln);
            }

            $found_type = $classlikes->getClassConstantType(
                $c->fqcln,
                $c->name,
                ReflectionProperty::IS_PRIVATE,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            if ($found_type) {
                return \array_values($found_type->getAtomicTypes())[0];
            }
        }

        if ($c instanceof UnresolvedConstant\ArrayOffsetFetch) {
            $var_type = self::resolve(
                $classlikes,
                $c->array,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            $offset_type = self::resolve(
                $classlikes,
                $c->offset,
                $statements_analyzer,
                $visited_constant_ids + [$c_id => true]
            );

            if ($var_type instanceof Type\Atomic\TKeyedArray
                && ($offset_type instanceof Type\Atomic\TLiteralInt
                    || $offset_type instanceof Type\Atomic\TLiteralString)
            ) {
                $union = $var_type->properties[$offset_type->value] ?? null;

                if ($union && $union->isSingle()) {
                    return \array_values($union->getAtomicTypes())[0];
                }
            }
        }

        if ($c instanceof UnresolvedConstant\Constant) {
            if ($statements_analyzer) {
                $found_type = ConstFetchAnalyzer::getConstType(
                    $statements_analyzer,
                    $c->name,
                    $c->is_fully_qualified,
                    null
                );

                if ($found_type) {
                    return \array_values($found_type->getAtomicTypes())[0];
                }
            }
        }

        return new Type\Atomic\TMixed;
    }

    /**
     * @param  string|int|float|bool|null $value
     */
    private static function getLiteralTypeFromScalarValue($value) : Type\Atomic
    {
        if (\is_string($value)) {
            return new Type\Atomic\TLiteralString($value);
        } elseif (\is_int($value)) {
            return new Type\Atomic\TLiteralInt($value);
        } elseif (\is_float($value)) {
            return new Type\Atomic\TLiteralFloat($value);
        } elseif ($value === false) {
            return new Type\Atomic\TFalse;
        } elseif ($value === true) {
            return new Type\Atomic\TTrue;
        } else {
            return new Type\Atomic\TNull;
        }
    }
}
