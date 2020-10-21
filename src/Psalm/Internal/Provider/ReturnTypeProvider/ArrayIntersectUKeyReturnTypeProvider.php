<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Type\ArrayType;
use Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;

final class ArrayIntersectUKeyReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds(): array
    {
        return ['array_intersect_ukey'];
    }

    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ): ?Type\Union {
        if (count($call_args) >= 3
            && ($array_arg_type = $statements_source->getNodeTypeProvider()->getType($call_args[0]->value))
            && $array_arg_type->isSingle()
            && $array_arg_type->hasArray()
            && ($array_type = ArrayType::infer($array_arg_type->getAtomicTypes()['array']))
            && ($callable_arg_type = $statements_source->getNodeTypeProvider()->getType(end($call_args)->value))
            && $callable_arg_type->isSingle()
            && $callable_arg_type->hasCallableType()
            && ($callable_type = current($callable_arg_type->getAtomicTypes()))
            && array_key_exists(0, $callable_type->params)
            && ($callable_type->params[0] instanceof FunctionLikeParameter)
            /** @var Type\Union $callable_param_type */
            && ($callable_param_type = $callable_type->params[0]->type)
            //&& $callable_param_type == $array_type->key
            && ($callable_return_type = $callable_type->return_type)
            && $callable_return_type->isSingle()
            && $callable_return_type->hasInt()
        ) {
            var_dump($callable_type);die;

            return new Type\Union([
                new Type\Atomic\TArray([$array_type->key, $array_type->value])
            ]);
        }

        return new Type\Union([Type::getArray()]);
    }
}
