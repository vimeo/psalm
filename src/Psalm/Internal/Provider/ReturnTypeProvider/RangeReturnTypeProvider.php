<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\Internal\Type\TypeCombination;
use Psalm\StatementsSource;
use Psalm\Internal\Analyzer\TypeAnalyzer;

class RangeReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['range'];
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
        $all_ints = true;
        $all_strings = true;
        $all_floats = true;
        $all_numbers = true;

        foreach ($call_args as $call_arg) {
            $is_int = false;
            $is_float = false;
            $is_string = false;

            if (isset($call_arg->value->inferredType)) {
                if ($call_arg->value->inferredType->isInt()) {
                    $is_int = true;
                } elseif ($call_arg->value->inferredType->isFloat()) {
                    $is_float = true;
                } elseif ($call_arg->value->inferredType->isString()) {
                    $is_string = true;
                }
            }

            $all_ints = $all_ints && $is_int;

            $all_floats = $all_floats && $is_float;

            $all_strings = $all_strings && $is_string;

            $all_numbers = $all_numbers && ($is_int || $is_float);
        }

        if ($all_ints) {
            return new Type\Union([new Type\Atomic\TArray([Type::getInt(), Type::getInt()])]);
        }

        if ($all_strings) {
            return new Type\Union([new Type\Atomic\TArray([Type::getInt(), Type::getString()])]);
        }

        if ($all_floats) {
            return new Type\Union([new Type\Atomic\TArray([Type::getInt(), Type::getFloat()])]);
        }

        if ($all_numbers) {
            return new Type\Union([new Type\Atomic\TArray([
                Type::getInt(),
                new Type\Union([new Type\Atomic\TInt, new Type\Atomic\TFloat])
            ])]);
        }

        return new Type\Union([new Type\Atomic\TArray([
            Type::getInt(),
            new Type\Union([new Type\Atomic\TInt, new Type\Atomic\TFloat, new Type\Atomic\TString])
        ])]);
    }
}
