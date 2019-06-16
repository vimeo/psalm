<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DuplicateArrayKey;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Internal\Type\TypeCombination;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TString;

/**
 * @internal
 */
class ArrayAnalyzer
{
    /**
     * @param   StatementsAnalyzer           $statements_analyzer
     * @param   PhpParser\Node\Expr\Array_  $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Array_ $stmt,
        Context $context
    ) {
        // if the array is empty, this special type allows us to match any other array type against it
        if (empty($stmt->items)) {
            $stmt->inferredType = Type::getEmptyArray();

            return null;
        }

        $item_key_atomic_types = [];
        $item_value_atomic_types = [];

        $property_types = [];
        $class_strings = [];

        $can_create_objectlike = true;

        $array_keys = [];

        $int_offset_diff = 0;

        $codebase = $statements_analyzer->getCodebase();

        /** @var int $int_offset */
        foreach ($stmt->items as $int_offset => $item) {
            if ($item === null) {
                continue;
            }

            $item_key_value = null;

            if ($item->key) {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $item->key, $context) === false) {
                    return false;
                }

                if (isset($item->key->inferredType)) {
                    $key_type = $item->key->inferredType;

                    if ($key_type->isNull()) {
                        $key_type = Type::getString('');
                    }

                    if ($item->key instanceof PhpParser\Node\Scalar\String_
                        && preg_match('/^(0|[1-9][0-9]*)$/', $item->key->value)
                    ) {
                        $key_type = Type::getInt(false, (int) $item->key->value);
                    }

                    $item_key_atomic_types = array_merge($item_key_atomic_types, array_values($key_type->getTypes()));

                    if ($key_type->isSingleStringLiteral()) {
                        $item_key_literal_type = $key_type->getSingleStringLiteral();
                        $item_key_value = $item_key_literal_type->value;

                        if ($item_key_literal_type instanceof Type\Atomic\TLiteralClassString) {
                            $class_strings[$item_key_value] = true;
                        }
                    } elseif ($key_type->isSingleIntLiteral()) {
                        $item_key_value = $key_type->getSingleIntLiteral()->value;

                        if ($item_key_value > $int_offset + $int_offset_diff) {
                            $int_offset_diff = $item_key_value - $int_offset;
                        }
                    }
                }
            } else {
                $item_key_value = $int_offset + $int_offset_diff;
                $item_key_atomic_types[] = new Type\Atomic\TInt();
            }

            if ($item_key_value !== null) {
                if (isset($array_keys[$item_key_value])) {
                    if (IssueBuffer::accepts(
                        new DuplicateArrayKey(
                            'Key \'' . $item_key_value . '\' already exists on array',
                            new CodeLocation($statements_analyzer->getSource(), $item)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $array_keys[$item_key_value] = true;
            }

            if (ExpressionAnalyzer::analyze($statements_analyzer, $item->value, $context) === false) {
                return false;
            }

            if ($item->byRef) {
                $var_id = ExpressionAnalyzer::getArrayVarId(
                    $item->value,
                    $statements_analyzer->getFQCLN(),
                    $statements_analyzer
                );

                if ($var_id) {
                    $context->removeDescendents(
                        $var_id,
                        $context->vars_in_scope[$var_id] ?? null,
                        null,
                        $statements_analyzer
                    );

                    $context->vars_in_scope[$var_id] = Type::getMixed();
                }
            }

            if ($item_value_atomic_types && !$can_create_objectlike) {
                continue;
            }

            if (isset($item->value->inferredType)) {
                if ($item_key_value !== null && count($property_types) <= 50) {
                    $property_types[$item_key_value] = $item->value->inferredType;
                } else {
                    $can_create_objectlike = false;
                }

                $item_value_atomic_types = array_merge(
                    $item_value_atomic_types,
                    array_values($item->value->inferredType->getTypes())
                );
            } else {
                $item_value_atomic_types[] = new Type\Atomic\TMixed();

                if ($item_key_value !== null && count($property_types) <= 50) {
                    $property_types[$item_key_value] = Type::getMixed();
                } else {
                    $can_create_objectlike = false;
                }
            }
        }

        if ($item_key_atomic_types) {
            $item_key_type = TypeCombination::combineTypes(
                $item_key_atomic_types,
                $codebase,
                false,
                true,
                30
            );
        } else {
            $item_key_type = null;
        }

        if ($item_value_atomic_types) {
            $item_value_type = TypeCombination::combineTypes(
                $item_value_atomic_types,
                $codebase,
                false,
                true,
                30
            );
        } else {
            $item_value_type = null;
        }
        // if this array looks like an object-like array, let's return that instead
        if ($item_value_type
            && $item_key_type
            && ($item_key_type->hasString() || $item_key_type->hasInt())
            && $can_create_objectlike
        ) {
            $object_like = new Type\Atomic\ObjectLike($property_types, $class_strings);
            $object_like->sealed = true;

            $stmt->inferredType = new Type\Union([$object_like]);

            return null;
        }

        $array_type = new Type\Atomic\TNonEmptyArray([
            $item_key_type ?: new Type\Union([new TInt, new TString]),
            $item_value_type ?: Type::getMixed(),
        ]);

        $array_type->count = count($stmt->items);

        $stmt->inferredType = new Type\Union([
            $array_type,
        ]);

        return null;
    }
}
