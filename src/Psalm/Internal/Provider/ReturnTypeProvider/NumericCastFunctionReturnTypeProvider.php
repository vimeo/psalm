<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Analyzer\Statements\Expression\CastAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Union;

use function count;

/**
 * intval() and floatval() cast their argument as (int) and (float) do, so they report what the casts report
 *
 * @internal
 */
final class NumericCastFunctionReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['intval', 'floatval', 'doubleval'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        $statements_source = $event->getStatementsSource();
        if ($call_args === [] || $call_args[0]->unpack || !$statements_source instanceof StatementsAnalyzer) {
            return null;
        }

        $value_type = $statements_source->getNodeTypeProvider()->getType($call_args[0]->value);
        if ($value_type === null) {
            return null;
        }

        if ($event->getFunctionId() === 'intval' && count($call_args) <= 2) {
            CastAnalyzer::castIntAttempt($statements_source, $value_type, $call_args[0]->value, true);
        } elseif ($event->getFunctionId() !== 'intval' && count($call_args) === 1) {
            CastAnalyzer::castFloatAttempt($statements_source, $value_type, $call_args[0]->value, true);
        }

        return null;
    }
}
