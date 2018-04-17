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

        $can_create_objectlike = true;

        $array_keys = [];

        /** @var int $int_offset */
        foreach ($stmt->items as $int_offset => $item) {
            if ($item === null) {
                continue;
            }

            if ($item->key) {
                if (ExpressionChecker::analyze($statements_checker, $item->key, $context) === false) {
                    return false;
                }

                if (isset($item->key->inferredType)) {
                    if ($item_key_type) {
                        $item_key_type = Type::combineUnionTypes($item->key->inferredType, $item_key_type);
                    } else {
                        $item_key_type = $item->key->inferredType;
                    }
                }
            } else {
                $item_key_type = Type::getInt();
            }

            if (ExpressionChecker::analyze($statements_checker, $item->value, $context) === false) {
                return false;
            }

            if ($item->key instanceof PhpParser\Node\Scalar\String_
                || $item->key instanceof PhpParser\Node\Scalar\LNumber
                || !$item->key
            ) {
                $array_key = $item->key ? $item->key->value : $int_offset;

                if (isset($array_keys[$array_key])) {
                    if (IssueBuffer::accepts(
                        new DuplicateArrayKey(
                            'Key \'' . $array_key . '\' already exists on array',
                            new CodeLocation($statements_checker->getSource(), $item)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $array_keys[$array_key] = true;
            }

            if ($item_value_type && $item_value_type->isMixed() && !$can_create_objectlike) {
                continue;
            }

            if (isset($item->value->inferredType)) {
                if ($item->key instanceof PhpParser\Node\Scalar\String_
                    || $item->key instanceof PhpParser\Node\Scalar\LNumber
                    || !$item->key
                ) {
                    $array_key = $item->key ? $item->key->value : $int_offset;

                    $property_types[$array_key] = $item->value->inferredType;
                } else {
                    $can_create_objectlike = false;
                }

                if ($item_value_type) {
                    $item_value_type = Type::combineUnionTypes($item->value->inferredType, $item_value_type);
                } else {
                    $item_value_type = $item->value->inferredType;
                }
            } else {
                $item_value_type = Type::getMixed();

                if ($item->key instanceof PhpParser\Node\Scalar\String_
                    || $item->key instanceof PhpParser\Node\Scalar\LNumber
                    || !$item->key
                ) {
                    $array_key = $item->key ? $item->key->value : $int_offset;

                    $property_types[$array_key] = $item_value_type;
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
            $stmt->inferredType = new Type\Union([new Type\Atomic\ObjectLike($property_types)]);

            return null;
        }

        $stmt->inferredType = new Type\Union([
            new Type\Atomic\TArray([
                $item_key_type ?: new Type\Union([new TInt, new TString]),
                $item_value_type ?: Type::getMixed(),
            ]),
        ]);

        return null;
    }
}
