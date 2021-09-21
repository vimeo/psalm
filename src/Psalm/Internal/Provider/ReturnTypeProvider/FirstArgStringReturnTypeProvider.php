<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Type;

class FirstArgStringReturnTypeProvider implements \Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds() : array
    {
        return [
            'crypt',
        ];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer
            || !$call_args
        ) {
            return Type::getMixed();
        }

        $return_type = Type::getString();

        if (($first_arg_type = $statements_source->node_data->getType($call_args[0]->value))
             && $first_arg_type->isString()
        ) {
            return $return_type;
        }

        $return_type->addType(new Type\Atomic\TNull);
        $return_type->ignore_nullable_issues = true;

        return $return_type;
    }
}
