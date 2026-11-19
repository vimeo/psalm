<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

/**
 * array_udiff() and the other diff/intersect functions that compare with a callback return the entries of their
 * first array that the others don't have, like array_diff() and array_intersect()
 *
 * @internal
 */
final class ArrayUserCompareDiffReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return [
            'array_diff_ukey',
            'array_diff_uassoc',
            'array_intersect_ukey',
            'array_intersect_uassoc',
            'array_udiff',
            'array_udiff_assoc',
            'array_udiff_uassoc',
            'array_uintersect',
            'array_uintersect_assoc',
            'array_uintersect_uassoc',
        ];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $first_arg = $event->getCallArgs()[0]->value ?? null;

        if (!$statements_source instanceof StatementsAnalyzer
            || $first_arg === null
            || !($first_arg_type = $statements_source->node_data->getType($first_arg))
            || !$first_arg_type->isArray()
        ) {
            return null;
        }

        $arrays = [];

        foreach ($first_arg_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TKeyedArray) {
                $atomic_type = $atomic_type->getGenericArrayType();
            }

            if (!$atomic_type instanceof TArray) {
                return null;
            }

            // the entries that are left may be none
            $arrays[] = new TArray($atomic_type->type_params);
        }

        return TypeCombiner::combine($arrays, $statements_source->getCodebase());
    }
}
