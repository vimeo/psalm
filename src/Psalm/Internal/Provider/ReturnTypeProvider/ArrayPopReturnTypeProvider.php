<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use PhpParser\Node\Expr\Variable;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

use function is_string;

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

        // The by-reference adjustment is only reliable for plain variables: it doesn't track array
        // offsets or unpacked arguments, and a property may be shared with an object alias. For
        // anything else, keep the historical inference (the first element of a list for array_shift,
        // the generic value type otherwise).
        $is_tracked = isset($call_args[0])
            && !$call_args[0]->unpack
            && $call_args[0]->value instanceof Variable
            && is_string($call_args[0]->value->name);

        return ArrayFirstLastReturnTypeProvider::getElementType(
            $statements_source,
            $call_args,
            $event->getFunctionId() === 'array_shift',
            $is_tracked,
        ) ?? Type::getMixed();
    }
}
