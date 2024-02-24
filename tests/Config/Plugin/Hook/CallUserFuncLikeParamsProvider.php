<?php

namespace Psalm\Test\Config\Plugin\Hook;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

class CallUserFuncLikeParamsProvider implements
    FunctionParamsProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['call_user_func_like'];
    }

    /**
     * @return ?array<int, FunctionLikeParameter>
     */
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array
    {
        $statements_source = $event->getStatementsSource();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return null;
        }

        $call_args = $event->getCallArgs();
        if (!isset($call_args[0])) {
            return null;
        }

        $function_call_arg = $call_args[0];

        $mapping_function_ids = array();
        if ($function_call_arg->value instanceof PhpParser\Node\Scalar\String_
            || $function_call_arg->value instanceof PhpParser\Node\Expr\Array_
            || $function_call_arg->value instanceof PhpParser\Node\Expr\BinaryOp\Concat
        ) {
            $mapping_function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
                $statements_source,
                $function_call_arg->value,
            );
        }

        if (!isset($mapping_function_ids[0])) {
            return null;
        }

        $codebase = $event->getStatementsSource()->getCodebase();
        $function_like_storage = $codebase->getFunctionLikeStorage($statements_source, $mapping_function_ids[0]);

        $callback_param_types = [];
        foreach ($function_like_storage->params as $function_like_parameter) {
            $param_type_union = $function_like_parameter->type;
            if (!$param_type_union) {
                $param_type_union = Type::getMixed();
            }

            if ($function_like_parameter->is_nullable || $function_like_parameter->is_optional) {
                $param_type_union = $param_type_union->setPossiblyUndefined(true);
            }

            $callback_param_types[] = $param_type_union;
        }

        if ($callback_param_types === []) {
            $callback_params = Type::getEmptyArrayAtomic();
        } else {
            $callback_params = new TKeyedArray(
                $callback_param_types,
            );
        }

        return array(
            new FunctionLikeParameter('callable', false, new Union([new TCallable()]), null, null, null, false),
            new FunctionLikeParameter('params', false, new Union([$callback_params]), null, null, null, false),
        );
    }
}
