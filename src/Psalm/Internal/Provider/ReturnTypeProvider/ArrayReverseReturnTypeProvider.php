<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;

class ArrayReverseReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_reverse'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        $first_arg = isset($call_args[0]->value) ? $call_args[0]->value : null;
        $preserve_keys_arg = isset($call_args[1]->value) ? $call_args[1]->value : null;

        $first_arg_array = $first_arg
            && isset($first_arg->inferredType)
            && $first_arg->inferredType->hasType('array')
            && ($array_atomic_type = $first_arg->inferredType->getTypes()['array'])
            && ($array_atomic_type instanceof Type\Atomic\TArray ||
                $array_atomic_type instanceof Type\Atomic\ObjectLike)
        ? $array_atomic_type
        : null;

        if (!$first_arg_array) {
            return Type::getArray();
        }

        if ($preserve_keys_arg instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($preserve_keys_arg->name->parts[0]) === 'true'
        ) {
            if ($first_arg_array instanceof Type\Atomic\TArray) {
                return new Type\Union([clone $first_arg_array]);
            }

            return new Type\Union([$first_arg_array->getGenericArrayType()]);
        }

        $key_type = Type::getArrayKey();

        if (!$preserve_keys_arg
            || ($preserve_keys_arg instanceof PhpParser\Node\Expr\ConstFetch
                && strtolower($preserve_keys_arg->name->parts[0]) === 'false')
        ) {
            $key_type = Type::getInt();
        }

        if ($first_arg_array instanceof Type\Atomic\TArray) {
            $value_type = clone $first_arg_array->type_params[1];
        } else {
            $value_type = $first_arg_array->getGenericValueType();
        }

        $array_atomic_type = $first_arg_array instanceof Type\Atomic\TNonEmptyArray
            || ($first_arg_array instanceof Type\Atomic\ObjectLike && $first_arg_array->sealed)
            ? new Type\Atomic\TNonEmptyArray([
                $key_type,
                $value_type,
            ])
            : new Type\Atomic\TArray([
                $key_type,
                $value_type,
            ]);

        return new Type\Union([$array_atomic_type]);
    }
}
