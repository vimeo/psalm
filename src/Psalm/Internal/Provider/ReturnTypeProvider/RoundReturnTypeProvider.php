<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;

use function array_slice;
use function count;
use function round;

use const PHP_ROUND_HALF_UP;

/**
 * @internal
 */
final class RoundReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['round'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union
    {
        $call_args = $event->getCallArgs();
        if (count($call_args) === 0) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        $nodeTypeProvider = $statements_source->getNodeTypeProvider();

        $float = new Type\Union([new Type\Atomic\TFloat()]);
        $num_arg = $nodeTypeProvider->getType($call_args[0]->value);
        $num_type = $num_arg?->isSingleAtomic() ? $num_arg->getSingleAtomic() : null;

        if (!$num_type instanceof Type\Atomic\TLiteralFloat && !$num_type instanceof Type\Atomic\TLiteralInt) {
            return $float;
        }

        $precision_val = 0;
        $mode_val = PHP_ROUND_HALF_UP;

        // the result is a literal only when the precision and the mode passed are known
        foreach (array_slice($call_args, 1, 2, true) as $offset => $arg) {
            $type = $nodeTypeProvider->getType($arg->value);

            if ($arg->unpack || $arg->name !== null || $type === null || !$type->isSingleIntLiteral()) {
                return $float;
            }

            if ($offset === 1) {
                $precision_val = $type->getSingleIntLiteral()->value;
            } else {
                /** @var positive-int|0 $mode_val */
                $mode_val = $type->getSingleIntLiteral()->value;
            }
        }

        return new Type\Union([new Type\Atomic\TLiteralFloat(round($num_type->value, $precision_val, $mode_val))]);
    }
}
