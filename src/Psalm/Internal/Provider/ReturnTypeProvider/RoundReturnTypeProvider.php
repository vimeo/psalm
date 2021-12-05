<?php declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;

use function array_values;
use function count;
use function round;

use const PHP_ROUND_HALF_UP;

class RoundReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['round'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union
    {
        $call_args = $event->getCallArgs();
        if (count($call_args) === 0) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        $nodeTypeProvider = $statements_source->getNodeTypeProvider();

        $num_arg = $nodeTypeProvider->getType($call_args[0]->value);

        if (count($call_args) > 1) {
            $precision_val = $call_args[1]->value;
        } else {
            $precision_val = 0;
        }

        if (count($call_args) > 2) {
            $mode_val = $call_args[2]->value;
        } else {
            $mode_val = PHP_ROUND_HALF_UP;
        }

        if ($num_arg !== null && $num_arg->isSingle()) {
            $num_type = array_values($num_arg->getAtomicTypes())[0];
            if ($num_type instanceof Type\Atomic\TLiteralFloat || $num_type instanceof Type\Atomic\TLiteralInt) {
                $rounded_val = round($num_type->value, $precision_val, $mode_val);
                return new Type\Union([new Type\Atomic\TLiteralFloat($rounded_val)]);
            }
        }

        return new Type\Union([new Type\Atomic\TFloat()]);
    }
}
