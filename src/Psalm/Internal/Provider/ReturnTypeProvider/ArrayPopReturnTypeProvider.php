<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

/**
 * The by-reference effect on the argument is handled by ArrayFunctionArgumentsAnalyzer.
 *
 * @internal
 */
final class ArrayPopReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
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

        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $call_args = $event->getCallArgs();

        // The by-reference adjustment only tracks plain variables and properties, not e.g. $a['k']
        // or unpacked arguments. For anything else, a previous call may already have removed
        // elements, so only the generic value type can be trusted.
        $is_tracked = isset($call_args[0])
            && !$call_args[0]->unpack
            && ExpressionIdentifier::getVarId(
                $call_args[0]->value,
                $statements_source->getFQCLN(),
                $statements_source,
            ) !== null;

        return ArrayFirstLastReturnTypeProvider::getElementType(
            $statements_source,
            $call_args,
            $event->getFunctionId() === 'array_shift',
            $is_tracked,
        ) ?? Type::getMixed();
    }
}
