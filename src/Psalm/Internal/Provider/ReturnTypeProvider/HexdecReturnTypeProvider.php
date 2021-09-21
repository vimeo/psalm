<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Type;

class HexdecReturnTypeProvider implements \Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds() : array
    {
        return ['hexdec'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event) : Type\Union
    {
        $statements_source = $event->getStatementsSource();
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return Type::getMixed();
        }

        return Type::getInt(true);
    }
}
