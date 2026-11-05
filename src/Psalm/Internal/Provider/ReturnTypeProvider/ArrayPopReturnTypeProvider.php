<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

/**
 * @internal
 */
final class ArrayPopReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['array_pop', 'array_shift'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $first_arg = $event->getCallArgs()[0] ?? null;

        if (!$statements_source instanceof StatementsAnalyzer || !$first_arg || $first_arg->unpack) {
            return Type::getMixed();
        }

        $first_arg_type = $statements_source->node_data->getType($first_arg->value);

        if (!$first_arg_type) {
            return Type::getMixed();
        }

        return ArrayFirstLastReturnTypeProvider::getElementType(
            $statements_source->getCodebase(),
            $first_arg_type,
            $event->getFunctionId() === 'array_shift',
            self::isHeldByArrays($statements_source, $first_arg->value),
        ) ?? Type::getMixed();
    }

    /**
     * Whether the item lives in arrays only: an item of an ArrayAccess object is re-read through offsetGet(),
     * which doesn't see the call, so the item may have lost elements already.
     *
     * @psalm-capabilities read-props
     */
    private static function isHeldByArrays(StatementsAnalyzer $statements_source, Expr $item): bool
    {
        while ($item instanceof ArrayDimFetch) {
            $holder_type = $statements_source->node_data->getType($item->var);

            if (!$holder_type) {
                return false;
            }

            foreach ($holder_type->getAtomicTypes() as $holder_atomic_type) {
                if (!$holder_atomic_type instanceof TArray && !$holder_atomic_type instanceof TKeyedArray) {
                    return false;
                }
            }

            $item = $item->var;
        }

        return true;
    }
}
