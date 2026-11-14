<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Type\ArrayType;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function array_slice;
use function count;

/**
 * @internal
 */
final class ArrayIntersectKeyReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['array_intersect_key'];
    }

    /** Only the keys the other arrays have can stay, so a sealed shape bounds the result's keys. */
    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $type_provider = $statements_source->getNodeTypeProvider();

        if (count($call_args) < 2
            || !($array_arg_type = $type_provider->getType($call_args[0]->value))
            || !$array_arg_type->isSingle()
            || !$array_arg_type->hasArray()
            || !($array_type = ArrayType::infer($array_arg_type->getArray()))
        ) {
            return null;
        }
        $codebase = $statements_source->getCodebase();
        $key_type = $array_type->key;
        $narrowed = false;

        foreach (array_slice($call_args, 1) as $call_arg) {
            if ($call_arg->unpack
                || $call_arg->name
                || !($other_arg_type = $type_provider->getType($call_arg->value))
                || !$other_arg_type->isSingle()
            ) {
                return null;
            }

            $other = $other_arg_type->getSingleAtomic();

            if (!$other instanceof TKeyedArray || $other->fallback_params !== null) {
                continue;
            }

            $kept = [];

            foreach ($other->getGenericKeyType()->getAtomicTypes() as $other_key) {
                if (UnionTypeComparator::isContainedBy($codebase, new Union([$other_key]), $key_type)) {
                    $kept[] = $other_key;
                }
            }

            if (!$kept) {
                return null;
            }

            $key_type = new Union($kept);
            $narrowed = true;
        }

        if (!$narrowed) {
            return null;
        }
        return new Union([new TArray([$key_type, $array_type->value])]);
    }
}
