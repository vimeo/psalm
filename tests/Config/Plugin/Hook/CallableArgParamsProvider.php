<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\Hook;

use Override;
use PhpParser\Node\Expr\Array_;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Union;

use function array_merge;
use function ltrim;

/**
 * Gives forwardToCallable($callable, ...$args) the parameters of the callable its first argument names.
 *
 * @psalm-suppress UnusedClass registered as a plugin via test config, instantiated by reflection
 */
final class CallableArgParamsProvider implements FunctionParamsProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['forwardtocallable'];
    }

    /**
     * @return ?array<int, FunctionLikeParameter>
     */
    #[Override]
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();

        if (!$statements_source instanceof StatementsAnalyzer
            || !isset($call_args[0])
            || !$call_args[0]->value instanceof Array_
        ) {
            return null;
        }

        $function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
            $statements_source,
            $call_args[0]->value,
            $event->getContext(),
        );

        if (!isset($function_ids[0])) {
            return null;
        }

        $function_id = ltrim($function_ids[0], '$');

        if ($function_id === '') {
            return null;
        }

        $storage = $statements_source->getCodebase()->getFunctionLikeStorage($statements_source, $function_id);

        return array_merge(
            [new FunctionLikeParameter('callable', false, new Union([new TCallable()]))],
            $storage->params,
        );
    }
}
