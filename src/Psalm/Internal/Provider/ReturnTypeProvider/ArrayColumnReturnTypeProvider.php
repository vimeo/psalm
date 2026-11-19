<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\SourceAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ArrayAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\PossiblyInvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

use function count;

/**
 * @internal
 */
final class ArrayColumnReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['array_column'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer
            || count($call_args) < 2
        ) {
            return Type::getMixed();
        }
        $context = $event->getContext();
        $code_location = $event->getCodeLocation();

        $value_column_name = null;
        $value_column_name_is_null = false;
        // calculate value column name
        if (($second_arg_type = $statements_source->node_data->getType($call_args[1]->value))) {
            if ($second_arg_type->isSingleIntLiteral()) {
                $value_column_name = $second_arg_type->getSingleIntLiteral()->value;
            } elseif ($second_arg_type->isSingleStringLiteral()) {
                $value_column_name = $second_arg_type->getSingleStringLiteral()->value;
            }
            $value_column_name_is_null = $second_arg_type->isNull();
        }

        $key_column_name = null;
        $key_column_name_is_null = false;
        $third_arg_type = null;
        // calculate key column name
        if (isset($call_args[2])) {
            $third_arg_type = $statements_source->node_data->getType($call_args[2]->value);

            if ($third_arg_type) {
                if ($third_arg_type->isSingleIntLiteral()) {
                    $key_column_name = $third_arg_type->getSingleIntLiteral()->value;
                } elseif ($third_arg_type->isSingleStringLiteral()) {
                    $key_column_name = $third_arg_type->getSingleStringLiteral()->value;
                }
                $key_column_name_is_null = $third_arg_type->isNull();
            }
        }


        $row_type = $row_shape = null;
        $input_array_not_empty = false;

        // calculate row shape
        if (($first_arg_type = $statements_source->node_data->getType($call_args[0]->value))
            && $first_arg_type->isSingle()
            && $first_arg_type->hasArray()
        ) {
            $input_array = $first_arg_type->getArray();
            if ($input_array instanceof TKeyedArray && !$input_array->fallback_params
                && ($value_column_name !== null || $value_column_name_is_null)
                && !($third_arg_type && !$key_column_name)
            ) {
                $properties = [];
                $ok = true;
                $last_custom_key = -1;
                $is_list = true;
                $had_possibly_undefined = false;

                // This incorrectly assumes that the array is sorted, may be problematic
                // Will be fixed when order is enforced
                $key = -1;
                foreach ($input_array->properties as $property) {
                    $row_shape = self::getRowShape(
                        $property,
                        $statements_source,
                        $context,
                        $code_location,
                    );
                    if (!$row_shape) {
                        continue;
                    }
                    if (!$row_shape instanceof TKeyedArray) {
                        if ($row_shape instanceof TArray && $row_shape->isEmptyArray()) {
                            continue;
                        }
                        $ok = false;
                        break;
                    }

                    if ($value_column_name !== null) {
                        if (isset($row_shape->properties[$value_column_name])) {
                            $result_element_type = $row_shape->properties[$value_column_name];
                        } elseif ($row_shape->fallback_params) {
                            $ok = false;
                            break;
                        } else {
                            continue;
                        }
                    } else {
                        $result_element_type = $property;
                    }

                    if ($key_column_name !== null) {
                        if (isset($row_shape->properties[$key_column_name])) {
                            $result_key_type = $row_shape->properties[$key_column_name];
                            if ($result_key_type->isSingleIntLiteral()) {
                                $key = $result_key_type->getSingleIntLiteral()->value;
                                if ($is_list && $last_custom_key != $key-1) {
                                    $is_list = false;
                                }
                                $last_custom_key = $key;
                            } elseif ($result_key_type->isSingleStringLiteral()) {
                                $key = $result_key_type->getSingleStringLiteral()->value;
                                $is_list = false;
                            } else {
                                $ok = false;
                                break;
                            }
                        } else {
                            $ok = false;
                            break;
                        }
                    } else {
                        /** @psalm-suppress StringIncrement Actually always an int in this branch */
                        ++$key;
                    }

                    $properties[$key] = $result_element_type->setPossiblyUndefined(
                        $property->possibly_undefined,
                    );

                    if (!$property->possibly_undefined
                        && $had_possibly_undefined
                    ) {
                        $is_list = false;
                    }

                    $had_possibly_undefined = $had_possibly_undefined || $property->possibly_undefined;
                }
                if ($ok) {
                    if (!$properties) {
                        return Type::getEmptyArray();
                    }
                    return new Union([TKeyedArray::make(
                        $properties,
                        null,
                        $input_array->fallback_params,
                        $is_list,
                    )]);
                }
            }

            if ($input_array instanceof TKeyedArray) {
                $row_type = $input_array->getGenericValueType();
            } elseif ($input_array instanceof TArray) {
                $row_type = $input_array->type_params[1];
            }

            $row_shape = self::getRowShape(
                $row_type,
                $statements_source,
                $context,
                $code_location,
            );

            $input_array_not_empty = $input_array instanceof TNonEmptyArray ||
                ($input_array instanceof TKeyedArray && $input_array->isNonEmpty());
        }


        $result_key_type = Type::getArrayKey();
        $result_element_type = null !== $row_type && $value_column_name_is_null ? $row_type : null;
        $have_at_least_one_res = false;
        // calculate results
        if ($row_shape instanceof TKeyedArray) {
            if ((null !== $value_column_name) && isset($row_shape->properties[$value_column_name])) {
                $result_element_type = $row_shape->properties[$value_column_name];
                // When the selected key is possibly_undefined, the resulting array can be empty
                if ($input_array_not_empty && $result_element_type->possibly_undefined !== true) {
                    $have_at_least_one_res = true;
                }
                //array_column skips undefined elements so resulting type is necessarily defined
                $result_element_type = $result_element_type->setPossiblyUndefined(false);
            } elseif (!$value_column_name_is_null) {
                $result_element_type = Type::getMixed();
            }

            if ((null !== $key_column_name) && isset($row_shape->properties[$key_column_name])) {
                $key_column_type = $row_shape->properties[$key_column_name];
                $codebase = $statements_source->getCodebase();
                $invalid_key_types = self::getTypesThatCannotBeKeys($codebase, $key_column_type);

                // array_column() skips the rows without the value column before it reads their index
                if ($value_column_name_is_null) {
                    $rows_may_be_skipped = false;
                    $rows_are_all_skipped = false;
                } elseif (null !== $value_column_name && isset($row_shape->properties[$value_column_name])) {
                    $rows_may_be_skipped = $row_shape->properties[$value_column_name]->possibly_undefined;
                    $rows_are_all_skipped = false;
                } else {
                    $rows_may_be_skipped = true;
                    $rows_are_all_skipped = null !== $value_column_name && $row_shape->fallback_params === null;
                }

                if ($invalid_key_types !== [] && !$rows_are_all_skipped) {
                    // a row without the index column is appended
                    $all_invalid = !$rows_may_be_skipped
                        && !$key_column_type->possibly_undefined
                        && count($invalid_key_types) === count($key_column_type->getAtomicTypes());

                    IssueBuffer::maybeAdd(
                        $all_invalid
                            ? new InvalidArgument(
                                'The index column has the type ' . $key_column_type->getId()
                                . ', which cannot be an array key',
                                $code_location,
                                'array_column',
                            )
                            : new PossiblyInvalidArgument(
                                'The index column has the type ' . $key_column_type->getId()
                                . ', which may not be an array key',
                                $code_location,
                                'array_column',
                            ),
                        $statements_source->getSuppressedIssues(),
                    );

                    if ($all_invalid && $codebase->analysis_php_version_id >= 8_00_00) {
                        return Type::getNever();
                    }
                }

                $result_key_type = self::castToArrayKey($codebase, $key_column_type);
            }
        }

        if ($third_arg_type && !$key_column_name_is_null) {
            $type = $have_at_least_one_res ?
                new TNonEmptyArray([$result_key_type, $result_element_type ?? Type::getMixed()])
                : new TArray([$result_key_type, $result_element_type ?? Type::getMixed()]);
        } else {
            $type = $have_at_least_one_res ?
                Type::getNonEmptyListAtomic($result_element_type ?? Type::getMixed())
                : Type::getListAtomic($result_element_type ?? Type::getMixed());
        }

        return new Union([$type]);
    }

    /**
     * The parts of the index column type that array_column() throws a TypeError for: arrays, and objects that
     * aren't cast to a string
     *
     * @return list<Atomic>
     */
    private static function getTypesThatCannotBeKeys(Codebase $codebase, Union $key_type): array
    {
        $invalid = [];

        foreach ($key_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TArray
                || $atomic_type instanceof TKeyedArray
                || ($atomic_type instanceof TNamedObject && !self::isCastToAString($codebase, $atomic_type))
            ) {
                $invalid[] = $atomic_type;
            }
        }

        return $invalid;
    }

    /**
     * Before PHP 8, an object with a __toString() in the index column is cast to a string; since then it is a
     * TypeError, like any other object
     */
    private static function isCastToAString(Codebase $codebase, TNamedObject $object): bool
    {
        return $codebase->analysis_php_version_id < 8_00_00
            && $codebase->classOrInterfaceExists($object->value)
            && $codebase->methodExists(new MethodIdentifier($object->value, '__tostring'));
    }

    /**
     * The type of the key array_column() sets for a value: null, bools, floats and, before PHP 8, objects with a
     * __toString() are cast to the array key they stand for. What can't be a key is left out (see
     * getTypesThatCannotBeKeys()), and a type that is nothing but that is an array key.
     */
    private static function castToArrayKey(Codebase $codebase, Union $key_type): Union
    {
        $atomic_types = [];

        foreach ($key_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TNull) {
                $atomic_types[] = Type::getAtomicStringFromLiteral('');
            } elseif ($atomic_type instanceof TFalse) {
                $atomic_types[] = new TLiteralInt(0);
            } elseif ($atomic_type instanceof TTrue) {
                $atomic_types[] = new TLiteralInt(1);
            } elseif ($atomic_type instanceof TBool) {
                $atomic_types[] = new TLiteralInt(0);
                $atomic_types[] = new TLiteralInt(1);
            } elseif ($atomic_type instanceof TFloat) {
                $atomic_types[] = new TInt();
            } elseif ($atomic_type instanceof TInt
                || $atomic_type instanceof TString
                || $atomic_type instanceof TArrayKey
                || $atomic_type instanceof TTemplateParam
            ) {
                $atomic_types[] = $atomic_type;
            } elseif ($atomic_type instanceof TNamedObject && self::isCastToAString($codebase, $atomic_type)) {
                $atomic_types[] = new TString();
            } elseif ($atomic_type instanceof TArray
                || $atomic_type instanceof TKeyedArray
                || $atomic_type instanceof TNamedObject
            ) {
                continue;
            } else {
                $atomic_types[] = new TArrayKey();
            }
        }

        if ($atomic_types === []) {
            return Type::getArrayKey();
        }

        return ArrayAnalyzer::castArrayKeyType(TypeCombiner::combine($atomic_types));
    }

    /**
     * @return TArray|TKeyedArray|TClassStringMap|null
     */
    private static function getRowShape(
        ?Union $row_type,
        SourceAnalyzer $statements_source,
        Context $context,
        CodeLocation $code_location,
    ): ?Atomic {
        if ($row_type && $row_type->isSingle()) {
            if ($row_type->hasArray()) {
                return $row_type->getArray();
            } elseif ($row_type->hasObjectType()) {
                $row_shape = GetObjectVarsReturnTypeProvider::getGetObjectVarsReturnType(
                    $row_type,
                    $statements_source,
                    $context,
                    $code_location,
                );

                // array_column() also reads the columns that __isset() says are set
                $object_type = $row_type->getSingleAtomic();
                if ($object_type instanceof TNamedObject
                    && $statements_source->getCodebase()->methodExists(
                        new MethodIdentifier($object_type->value, '__isset'),
                    )
                ) {
                    if ($row_shape instanceof TKeyedArray) {
                        return TKeyedArray::make(
                            $row_shape->properties,
                            null,
                            [Type::getString(), Type::getMixed()],
                        );
                    }

                    return new TArray([Type::getString(), Type::getMixed()]);
                }

                return $row_shape;
            }
        }
        return null;
    }
}
