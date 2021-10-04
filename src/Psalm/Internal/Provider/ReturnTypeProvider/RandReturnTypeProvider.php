<?php declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;

use function array_values;
use function count;

class RandReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['rand', 'mt_rand', 'random_int'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union
    {
        $call_args = $event->getCallArgs();
        if (count($call_args) === 0) {
            return Type::getInt();
        }

        if (count($call_args) !== 2) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        $nodeTypeProvider = $statements_source->getNodeTypeProvider();

        $first_arg = $nodeTypeProvider->getType($call_args[0]->value);
        $second_arg = $nodeTypeProvider->getType($call_args[1]->value);

        $min_value = null;
        if ($first_arg !== null && $first_arg->isSingle()) {
            $first_atomic_type = array_values($first_arg->getAtomicTypes())[0];
            if ($first_atomic_type instanceof Type\Atomic\TLiteralInt) {
                $min_value = $first_atomic_type->value;
            } elseif ($first_atomic_type instanceof Type\Atomic\TIntRange) {
                $min_value = $first_atomic_type->min_bound;
            } elseif ($first_atomic_type instanceof Type\Atomic\TPositiveInt) {
                $min_value = 1;
            }
        }

        $max_value = null;
        if ($second_arg !== null && $second_arg->isSingle()) {
            $second_atomic_type = array_values($second_arg->getAtomicTypes())[0];
            if ($second_atomic_type instanceof Type\Atomic\TLiteralInt) {
                $max_value = $second_atomic_type->value;
            } elseif ($second_atomic_type instanceof Type\Atomic\TIntRange) {
                $max_value = $second_atomic_type->max_bound;
            } elseif ($second_atomic_type instanceof Type\Atomic\TPositiveInt) {
                $max_value = null;
            }
        }

        return new Type\Union([new Type\Atomic\TIntRange($min_value, $max_value)]);
    }
}
