<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

use function array_values;
use function preg_match;

/**
 * @internal
 */
class DateReturnTypeProvider implements FunctionReturnTypeProviderInterface
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

        if (isset($call_args[0])) {
            $type = $source->node_data->getType($call_args[0]->value);
            if ($type !== null && $type->isSingle()) {
                $atomic_type = array_values($type->getAtomicTypes())[0];
                if ($atomic_type instanceof Type\Atomic\TLiteralString
                    && ($format_val = $atomic_type->value)
                    && preg_match('/[djNwzWmntLoYyBgGhHisuvZUI]+/', $format_val)
                ) {
                    return Type::getNumericString();
                }
            }
        }

        if (!isset($call_args[1])) {
            return Type::getString();
        }

        $type = $source->node_data->getType($call_args[1]->value);
        if ($type !== null && $type->isSingle()) {
            $atomic_type = array_values($type->getAtomicTypes())[0];
            if ($atomic_type instanceof Type\Atomic\TNumeric
                || $atomic_type instanceof Type\Atomic\TInt
            ) {
                return Type::getString();
            }
        }
        return Type::combineUnionTypes(Type::getString(), Type::getFalse());
    }
}
