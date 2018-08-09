<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DuplicateArrayKey;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TString;

class ArrayChecker
{
    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Array_  $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Array_ $stmt,
        Context $context
    ) {
        // if the array is empty, this special type allows us to match any other array type against it
        if (empty($stmt->items)) {
            $stmt->inferredType = Type::getEmptyArray();

            return null;
        }

        $item_key_type = null;

        $item_value_type = null;

        $property_types = [];
        $class_strings = [];

        $can_create_objectlike = true;

        $array_keys = [];

        $int_offset_diff = 0;

        /** @var int $int_offset */
        foreach ($stmt->items as $int_offset => $item) {
            if ($item === null) {
                continue;
            }

            $item_key_value = null;

            if ($item->key) {
                if (ExpressionChecker::analyze($statements_checker, $item->key, $context) === false) {
                    return false;
                }

                if (isset($item->key->inferredType)) {
                    $key_type = $item->key->inferredType;

                    if ($item->key instanceof PhpParser\Node\Scalar\String_
                        && preg_match('/^(0|[1-9][0-9]*)$/', $item->key->value)
                    ) {
                        $key_type = Type::getInt(false, (int) $item->key->value);
                    }

                    if ($item_key_type) {
                        $item_key_type = Type::combineUnionTypes($key_type, $item_key_type);
                    } else {
                        $item_key_type = $key_type;
                    }

                    if ($item->key->inferredType->isSingleStringLiteral()) {
                        $item_key_literal_type = $item->key->inferredType->getSingleStringLiteral();
                        $item_key_value = $item_key_literal_type->value;

                        if ($item_key_literal_type instanceof Type\Atomic\TLiteralClassString) {
                            $class_strings[$item_key_value] = true;
                        }
                    } elseif ($item->key->inferredType->isSingleIntLiteral()) {
                        $item_key_value = $item->key->inferredType->getSingleIntLiteral()->value;

                        if ($item_key_value > $int_offset + $int_offset_diff) {
                            $int_offset_diff = $item_key_value - ($int_offset + $int_offset_diff);
                        }
                    }
                }
            } else {
                $item_key_value = $int_offset + $int_offset_diff;
                $item_key_type = Type::getInt();
            }

            if ($item_key_value !== null) {
                if (isset($array_keys[$item_key_value])) {
                    if (IssueBuffer::accepts(
                        new DuplicateArrayKey(
                            'Key \'' . $item_key_value . '\' already exists on array',
                            new CodeLocation($statements_checker->getSource(), $item)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $array_keys[$item_key_value] = true;
            }

            if (ExpressionChecker::analyze($statements_checker, $item->value, $context) === false) {
                return false;
            }

            if ($item_value_type && $item_value_type->isMixed() && !$can_create_objectlike) {
                continue;
            }

            if (isset($item->value->inferredType)) {
                if ($item_key_value !== null) {
                    $property_types[$item_key_value] = $item->value->inferredType;
                } else {
                    $can_create_objectlike = false;
                }

                if ($item_value_type) {
                    $item_value_type = Type::combineUnionTypes($item->value->inferredType, clone $item_value_type);
                } else {
                    $item_value_type = $item->value->inferredType;
                }
            } else {
                $item_value_type = Type::getMixed();

                if ($item_key_value !== null) {
                    $property_types[$item_key_value] = $item_value_type;
                } else {
                    $can_create_objectlike = false;
                }
            }
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

        $array_type = new Type\Atomic\TArray([
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
