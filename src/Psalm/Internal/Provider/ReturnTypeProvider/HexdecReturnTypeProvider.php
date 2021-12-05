<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;

class HexdecReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['hexdec'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Type\Union
    {
        $statements_source = $event->getStatementsSource();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        return Type::getInt(true);
    }
}
