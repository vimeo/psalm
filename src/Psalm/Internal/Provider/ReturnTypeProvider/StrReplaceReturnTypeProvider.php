<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

use function call_user_func;
use function count;

/**
 * @internal
 */
final class StrReplaceReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [
            'str_replace',
            'str_ireplace',
        ];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        if (!$statements_source instanceof StatementsAnalyzer
            || count($call_args) < 3
        ) {
            // use the defaults, it will already report an error for the invalid params
            return null;
        }

        if ($subject_type = $statements_source->node_data->getType($call_args[2]->value)) {
            if (!$subject_type->isSingleStringLiteral()) {
                return null;
            }

            $first_arg = $statements_source->node_data->getType($call_args[0]->value);
            $second_arg = $statements_source->node_data->getType($call_args[1]->value);
            if ($first_arg
                && $second_arg && $first_arg->isSingleStringLiteral()
                && $second_arg->isSingleStringLiteral()
            ) {
                /**
                 * @var string $replaced_string
                 */
                $replaced_string = call_user_func(
                    $function_id,
                    $first_arg->getSingleStringLiteral()->value,
                    $second_arg->getSingleStringLiteral()->value,
                    $subject_type->getSingleStringLiteral()->value,
                );
                return Type::getString($replaced_string);
            }
        }

        return null;
    }
}
