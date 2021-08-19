<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\NonDivArithmeticOpAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\StatementsSource;
use Psalm\Storage\ClassConstantStorage;
use Psalm\Type;

use function array_merge;
use function array_shift;
use function array_values;
use function count;
use function preg_match;
use function reset;
use function strtolower;

use const PHP_INT_MAX;

/**
 * This class takes a statement and return its type by analyzing each part of the statement if necessary
 */
class SimpleTypeInferer
{
    /**
     * @param   ?array<string, ClassConstantStorage> $existing_class_constants
     */
    public static function infer(
        \Psalm\Codebase $codebase,
        \Psalm\Internal\Provider\NodeDataProvider $nodes,
        PhpParser\Node\Expr $stmt,
        \Psalm\Aliases $aliases,
        \Psalm\FileSource $file_source = null,
        ?array $existing_class_constants = null,
        ?string $fq_classlike_name = null
    ): ?Type\Union {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
                $left = self::infer(
                    $codebase,
                    $nodes,
                    $stmt->left,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );
                $right = self::infer(
                    $codebase,
                    $nodes,
                    $stmt->right,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );

                if ($left
                    && $right
                ) {
                    if ($left->isSingleStringLiteral()
                        && $right->isSingleStringLiteral()
                    ) {
                        $result = $left->getSingleStringLiteral()->value . $right->getSingleStringLiteral()->value;

                        return Type::getString($result);
                    }

                    if ($left->isString()) {
                        $left_string_types = $left->getAtomicTypes();
                        $left_string_type = reset($left_string_types);
                        if ($left_string_type instanceof Type\Atomic\TNonEmptyString) {
                            return new Type\Union([new Type\Atomic\TNonEmptyString()]);
                        }
                    }

                    if ($right->isString()) {
                        $right_string_types = $right->getAtomicTypes();
                        $right_string_type = reset($right_string_types);
                        if ($right_string_type instanceof Type\Atomic\TNonEmptyString) {
                            return new Type\Union([new Type\Atomic\TNonEmptyString()]);
                        }
                    }
                }

                return Type::getString();
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Greater
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Smaller
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
            ) {
                return Type::getBool();
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
                return null;
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Spaceship) {
                return new Type\Union(
                    [
                        new Type\Atomic\TLiteralInt(-1),
                        new Type\Atomic\TLiteralInt(0),
                        new Type\Atomic\TLiteralInt(1)
                    ]
                );
            }

            $stmt_left_type = self::infer(
                $codebase,
                $nodes,
                $stmt->left,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            $stmt_right_type = self::infer(
                $codebase,
                $nodes,
                $stmt->right,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            if (!$stmt_left_type || !$stmt_right_type) {
                return null;
            }

            $nodes->setType(
                $stmt->left,
                $stmt_left_type
            );

            $nodes->setType(
                $stmt->right,
                $stmt_right_type
            );

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mod
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Pow
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\ShiftRight
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\ShiftLeft
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd
            ) {
                NonDivArithmeticOpAnalyzer::analyze(
                    $file_source instanceof StatementsSource ? $file_source : null,
                    $nodes,
                    $stmt->left,
                    $stmt->right,
                    $stmt,
                    $result_type
                );

                if ($result_type) {
                    return $result_type;
                }

                return null;
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div
                && ($stmt_left_type->hasInt() || $stmt_left_type->hasFloat())
                && ($stmt_right_type->hasInt() || $stmt_right_type->hasFloat())
            ) {
                return Type::combineUnionTypes(Type::getFloat(), Type::getInt());
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            if (strtolower($stmt->name->parts[0]) === 'false') {
                return Type::getFalse();
            } elseif (strtolower($stmt->name->parts[0]) === 'true') {
                return Type::getTrue();
            } elseif (strtolower($stmt->name->parts[0]) === 'null') {
                return Type::getNull();
            } elseif ($stmt->name->parts[0] === '__NAMESPACE__') {
                return Type::getString($aliases->namespace);
            }

            return null;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Dir
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\File
        ) {
            return new Type\Union([new Type\Atomic\TNonEmptyString()]);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Line) {
            return Type::getInt();
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Class_
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Method
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Trait_
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Function_
        ) {
            return Type::getString();
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Namespace_) {
            return Type::getString($aliases->namespace);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            if ($stmt->class instanceof PhpParser\Node\Name
                && $stmt->name instanceof PhpParser\Node\Identifier
                && $fq_classlike_name
                && $stmt->class->parts !== ['static']
                && $stmt->class->parts !== ['parent']
            ) {
                if (isset($existing_class_constants[$stmt->name->name])
                    && $existing_class_constants[$stmt->name->name]->type
                ) {
                    if ($stmt->class->parts === ['self']) {
                        return clone $existing_class_constants[$stmt->name->name]->type;
                    }
                }

                if ($stmt->class->parts === ['self']) {
                    $const_fq_class_name = $fq_classlike_name;
                } else {
                    $const_fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                        $stmt->class,
                        $aliases
                    );
                }

                if (strtolower($const_fq_class_name) === strtolower($fq_classlike_name)
                    && isset($existing_class_constants[$stmt->name->name])
                    && $existing_class_constants[$stmt->name->name]->type
                ) {
                    return clone $existing_class_constants[$stmt->name->name]->type;
                }

                if (strtolower($stmt->name->name) === 'class') {
                    return Type::getLiteralClassString($const_fq_class_name, true);
                }

                if ($existing_class_constants === null
                    && $file_source instanceof StatementsAnalyzer
                ) {
                    try {
                        $foreign_class_constant = $codebase->classlikes->getClassConstantType(
                            $const_fq_class_name,
                            $stmt->name->name,
                            \ReflectionProperty::IS_PRIVATE,
                            $file_source
                        );

                        if ($foreign_class_constant) {
                            return clone $foreign_class_constant;
                        }

                        return null;
                    } catch (\InvalidArgumentException $e) {
                        return null;
                    } catch (\Psalm\Exception\CircularReferenceException $e) {
                        return null;
                    }
                }
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier && strtolower($stmt->name->name) === 'class') {
                return Type::getClassString();
            }

            return null;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            return Type::getString($stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            return Type::getInt(false, $stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            return Type::getFloat($stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Array_) {
            return self::inferArrayType(
                $codebase,
                $nodes,
                $stmt,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            return Type::getInt();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            return Type::getFloat();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            return Type::getBool();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            return Type::getString();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            return Type::getObject();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            return Type::getArray();
        }

        if ($stmt instanceof PhpParser\Node\Expr\UnaryMinus || $stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            $type_to_invert = self::infer(
                $codebase,
                $nodes,
                $stmt->expr,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            if (!$type_to_invert) {
                return null;
            }

            foreach ($type_to_invert->getAtomicTypes() as $type_part) {
                if ($type_part instanceof Type\Atomic\TLiteralInt
                    && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                ) {
                    $type_part->value = -$type_part->value;
                } elseif ($type_part instanceof Type\Atomic\TLiteralFloat
                    && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                ) {
                    $type_part->value = -$type_part->value;
                }
            }

            return $type_to_invert;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            if ($stmt->var instanceof PhpParser\Node\Expr\ClassConstFetch
                && $stmt->dim
            ) {
                $array_type = self::infer(
                    $codebase,
                    $nodes,
                    $stmt->var,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );

                $dim_type = self::infer(
                    $codebase,
                    $nodes,
                    $stmt->dim,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );

                if ($array_type !== null && $dim_type !== null) {
                    if ($dim_type->isSingleStringLiteral()) {
                        $dim_value = $dim_type->getSingleStringLiteral()->value;
                    } elseif ($dim_type->isSingleIntLiteral()) {
                        $dim_value = $dim_type->getSingleIntLiteral()->value;
                    } else {
                        return null;
                    }

                    foreach ($array_type->getAtomicTypes() as $array_atomic_type) {
                        if ($array_atomic_type instanceof Type\Atomic\TKeyedArray) {
                            if (isset($array_atomic_type->properties[$dim_value])) {
                                return clone $array_atomic_type->properties[$dim_value];
                            }

                            return null;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param   ?array<string, ClassConstantStorage> $existing_class_constants
     */
    private static function inferArrayType(
        \Psalm\Codebase $codebase,
        \Psalm\Internal\Provider\NodeDataProvider $nodes,
        PhpParser\Node\Expr\Array_ $stmt,
        \Psalm\Aliases $aliases,
        \Psalm\FileSource $file_source = null,
        ?array $existing_class_constants = null,
        ?string $fq_classlike_name = null
    ): ?Type\Union {
        if (count($stmt->items) === 0) {
            return Type::getEmptyArray();
        }

        $array_creation_info = new ArrayCreationInfo();

        foreach ($stmt->items as $item) {
            if ($item === null) {
                continue;
            }

            if (!self::handleArrayItem(
                $codebase,
                $nodes,
                $array_creation_info,
                $item,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            )) {
                return null;
            }
        }

        $item_key_type = null;
        if ($array_creation_info->item_key_atomic_types) {
            $item_key_type = TypeCombiner::combine(
                $array_creation_info->item_key_atomic_types,
                null,
                false,
                true,
                30
            );
        }

        $item_value_type = null;
        if ($array_creation_info->item_value_atomic_types) {
            $item_value_type = TypeCombiner::combine(
                $array_creation_info->item_value_atomic_types,
                null,
                false,
                true,
                30
            );
        }

        // if this array looks like an object-like array, let's return that instead
        if ($item_value_type
            && $item_key_type
            && ($item_key_type->hasString() || $item_key_type->hasInt())
            && $array_creation_info->can_create_objectlike
            && $array_creation_info->property_types
        ) {
            $objectlike = new Type\Atomic\TKeyedArray(
                $array_creation_info->property_types,
                $array_creation_info->class_strings
            );
            $objectlike->sealed = true;
            $objectlike->is_list = $array_creation_info->all_list;
            return new Type\Union([$objectlike]);
        }

        if (!$item_key_type || !$item_value_type) {
            return null;
        }

        return new Type\Union([
            new Type\Atomic\TNonEmptyArray([
                $item_key_type,
                $item_value_type,
            ]),
        ]);
    }

    /**
     * @param   ?array<string, ClassConstantStorage> $existing_class_constants
     */
    private static function handleArrayItem(
        \Psalm\Codebase $codebase,
        \Psalm\Internal\Provider\NodeDataProvider $nodes,
        ArrayCreationInfo $array_creation_info,
        PhpParser\Node\Expr\ArrayItem $item,
        \Psalm\Aliases $aliases,
        \Psalm\FileSource $file_source = null,
        ?array $existing_class_constants = null,
        ?string $fq_classlike_name = null
    ): bool {
        if ($item->unpack) {
            $unpacked_array_type = self::infer(
                $codebase,
                $nodes,
                $item->value,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            if (!$unpacked_array_type) {
                return false;
            }

            return self::handleUnpackedArray($array_creation_info, $unpacked_array_type);
        }

        $single_item_key_type = null;
        $item_is_list_item = false;
        $item_key_value = null;

        if ($item->key) {
            $single_item_key_type = self::infer(
                $codebase,
                $nodes,
                $item->key,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            if ($single_item_key_type) {
                $key_type = $single_item_key_type;
                if ($key_type->isNull()) {
                    $key_type = Type::getString('');
                }
                if ($item->key instanceof PhpParser\Node\Scalar\String_
                    && preg_match('/^(0|[1-9][0-9]*)$/', $item->key->value)
                    && (
                        (int) $item->key->value < PHP_INT_MAX ||
                        $item->key->value === (string) PHP_INT_MAX
                    )
                ) {
                    $key_type = Type::getInt(false, (int) $item->key->value);
                }

                $array_creation_info->item_key_atomic_types = array_merge(
                    $array_creation_info->item_key_atomic_types,
                    array_values($key_type->getAtomicTypes())
                );

                if ($key_type->isSingleStringLiteral()) {
                    $item_key_literal_type = $key_type->getSingleStringLiteral();
                    $item_key_value = $item_key_literal_type->value;

                    if ($item_key_literal_type instanceof Type\Atomic\TLiteralClassString) {
                        $array_creation_info->class_strings[$item_key_value] = true;
                    }
                } elseif ($key_type->isSingleIntLiteral()) {
                    $item_key_value = $key_type->getSingleIntLiteral()->value;

                    if ($item_key_value >= $array_creation_info->int_offset) {
                        if ($item_key_value === $array_creation_info->int_offset) {
                            $item_is_list_item = true;
                        }
                        $array_creation_info->int_offset = $item_key_value + 1;
                    }
                }
            }
        } else {
            $item_is_list_item = true;
            $item_key_value = $array_creation_info->int_offset++;
            $array_creation_info->item_key_atomic_types[] = new Type\Atomic\TLiteralInt($item_key_value);
        }

        $single_item_value_type = self::infer(
            $codebase,
            $nodes,
            $item->value,
            $aliases,
            $file_source,
            $existing_class_constants,
            $fq_classlike_name
        );

        if (!$single_item_value_type) {
            return false;
        }

        $array_creation_info->all_list = $array_creation_info->all_list && $item_is_list_item;

        if ($item->key instanceof PhpParser\Node\Scalar\String_
            || $item->key instanceof PhpParser\Node\Scalar\LNumber
            || !$item->key
        ) {
            if ($item_key_value !== null && count($array_creation_info->property_types) <= 50) {
                $array_creation_info->property_types[$item_key_value] = $single_item_value_type;
            } else {
                $array_creation_info->can_create_objectlike = false;
            }
        } else {
            $dim_type = $single_item_key_type;

            if (!$dim_type) {
                return false;
            }

            $dim_atomic_types = $dim_type->getAtomicTypes();

            if (count($dim_atomic_types) > 1
                || $dim_type->hasMixed()
                || count($array_creation_info->property_types) > 50
            ) {
                $array_creation_info->can_create_objectlike = false;
            } else {
                $atomic_type = array_shift($dim_atomic_types);

                if ($atomic_type instanceof Type\Atomic\TLiteralInt
                    || $atomic_type instanceof Type\Atomic\TLiteralString
                ) {
                    if ($atomic_type instanceof Type\Atomic\TLiteralClassString) {
                        $array_creation_info->class_strings[$atomic_type->value] = true;
                    }

                    $array_creation_info->property_types[$atomic_type->value] = $single_item_value_type;
                } else {
                    $array_creation_info->can_create_objectlike = false;
                }
            }
        }

        $array_creation_info->item_value_atomic_types = array_merge(
            $array_creation_info->item_value_atomic_types,
            array_values($single_item_value_type->getAtomicTypes())
        );

        return true;
    }

    private static function handleUnpackedArray(
        ArrayCreationInfo $array_creation_info,
        Type\Union $unpacked_array_type
    ): bool {
        foreach ($unpacked_array_type->getAtomicTypes() as $unpacked_atomic_type) {
            if ($unpacked_atomic_type instanceof Type\Atomic\TKeyedArray) {
                foreach ($unpacked_atomic_type->properties as $key => $property_value) {
                    if (\is_string($key)) {
                        // string keys are not supported in unpacked arrays
                        return false;
                    }

                    $new_int_offset = $array_creation_info->int_offset++;

                    $array_creation_info->item_key_atomic_types[] = new Type\Atomic\TLiteralInt($new_int_offset);
                    $array_creation_info->item_value_atomic_types = array_merge(
                        $array_creation_info->item_value_atomic_types,
                        array_values($property_value->getAtomicTypes())
                    );

                    $array_creation_info->array_keys[$new_int_offset] = true;
                    $array_creation_info->property_types[$new_int_offset] = $property_value;
                }
            } elseif ($unpacked_atomic_type instanceof Type\Atomic\TArray) {
                /** @psalm-suppress PossiblyUndefinedArrayOffset provably true, but Psalm can’t see it */
                if ($unpacked_atomic_type->type_params[1]->isEmpty()) {
                    continue;
                }
                $array_creation_info->can_create_objectlike = false;

                if ($unpacked_atomic_type->type_params[0]->hasString()) {
                    // string keys are not supported in unpacked arrays
                    return false;
                } elseif ($unpacked_atomic_type->type_params[0]->hasInt()) {
                    $array_creation_info->item_key_atomic_types[] = new Type\Atomic\TInt();
                }

                $array_creation_info->item_value_atomic_types = array_merge(
                    $array_creation_info->item_value_atomic_types,
                    array_values(
                        isset($unpacked_atomic_type->type_params[1])
                            ? $unpacked_atomic_type->type_params[1]->getAtomicTypes()
                            : [new Type\Atomic\TMixed()]
                    )
                );
            } elseif ($unpacked_atomic_type instanceof Type\Atomic\TList) {
                if ($unpacked_atomic_type->type_param->isEmpty()) {
                    continue;
                }
                $array_creation_info->can_create_objectlike = false;

                $array_creation_info->item_key_atomic_types[] = new Type\Atomic\TInt();

                $array_creation_info->item_value_atomic_types = array_merge(
                    $array_creation_info->item_value_atomic_types,
                    array_values($unpacked_atomic_type->type_param->getAtomicTypes())
                );
            }
        }
        return true;
    }
}
