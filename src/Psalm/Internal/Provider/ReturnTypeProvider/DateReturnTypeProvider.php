<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

use function array_values;
use function date;
use function is_numeric;

/**
 * @internal
 */
final class DateReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['date', 'gmdate'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $source = $event->getStatementsSource();
        if (!$source instanceof StatementsAnalyzer) {
            return null;
        }

        $call_args = $event->getCallArgs();

        $format_type = Type::getString();
        if (isset($call_args[0])) {
            $type = $source->node_data->getType($call_args[0]->value);
            if ($type !== null
                && $type->isSingleStringLiteral()
                && is_numeric(date($type->getSingleStringLiteral()->value))
            ) {
                $format_type = Type::getNumericString();
            }
        }

        if (!isset($call_args[1])) {
            return $format_type;
        }

        $type = $source->node_data->getType($call_args[1]->value);
        if ($type !== null && $type->isSingle()) {
            $atomic_type = array_values($type->getAtomicTypes())[0];
            if ($atomic_type instanceof Type\Atomic\TNumeric
                || $atomic_type instanceof Type\Atomic\TInt
                || $atomic_type instanceof TLiteralInt
                || ($atomic_type instanceof TLiteralString && is_numeric($atomic_type->value))
            ) {
                return $format_type;
            }
        }
        return $format_type;
    }
}
