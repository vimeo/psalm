<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\Plugin\Hook\Event\FunctionReturnTypeProviderEvent;
use Psalm\Type;

class HexdecReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds() : array
    {
        return ['hexdec'];
    }

    /**
     * @param  list<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event) : Type\Union {
        $statements_source = $event->getStatementsSource();
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return Type::getMixed();
        }

        return Type::getInt(true);
    }
}
