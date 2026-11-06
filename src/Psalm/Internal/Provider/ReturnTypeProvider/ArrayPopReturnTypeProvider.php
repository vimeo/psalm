<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use PhpParser\Node\Expr\ArrayDimFetch;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
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
            // an offset may have lost elements already: the array holding it keeps its type after the call
            !$first_arg->value instanceof ArrayDimFetch,
        ) ?? Type::getMixed();
    }
}
