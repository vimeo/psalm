<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ParamsProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;

use function array_fill;
use function assert;
use function count;
use function max;

/**
 * @internal
 */
class ArrayUArrayParamsProvider implements FunctionParamsProviderInterface
{

    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [
            'array_diff_ukey',
            'array_diff_uassoc',
            'array_intersect_ukey',
            'array_intersect_uassoc',

            'array_udiff_uassoc',
            'array_uintersect_uassoc',

            'array_udiff',
            'array_udiff_assoc',
            'array_uintersect',
            'array_uintersect_assoc',
        ];
    }

    private static ?FunctionLikeParameter $arr = null;
    /**
     * @return ?list<FunctionLikeParameter>
     */
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array
    {
        $statements_source = $event->getStatementsSource();
        if (!($statements_source instanceof StatementsAnalyzer)) {
            // this is practically impossible
            // but the type in the caller is parent type StatementsSource
            // even though all callers provide StatementsAnalyzer
            return null;
        }

        /** @psalm-suppress PossiblyNullPropertyFetch, PossiblyNullArrayAccess */
        $cb = InternalCallMapHandler::getCallablesFromCallMap('array_udiff_uassoc')[0]->params;
        assert(isset($cb[2]) && isset($cb[3]));
        $valCb = $cb[2];
        $keyCb = $cb[3];
        $arr = self::$arr ??= new FunctionLikeParameter(
            "array",
            false,
            Type::getArray(),
            null,
            null,
            null,
            false,
        );

        $func = $event->getFunctionId();
        $call_args = $event->getCallArgs();
        $array_cnt = count($call_args)-1;

        if ($func === 'array_diff_ukey'
            || $func === 'array_diff_uassoc'
            || $func === 'array_intersect_ukey'
            || $func === 'array_intersect_uassoc'
        ) {
            // Key comparison
            $args = array_fill(0, max($array_cnt, 1), $arr);
            $args []= $keyCb;
        } elseif ($func === 'array_udiff_uassoc'
            || $func === 'array_uintersect_uassoc'
        ) {
            // Key+value comparison
            $args = array_fill(0, max($array_cnt-1, 1), $arr);
            $args []= $valCb;
            $args []= $keyCb;
        } else {
            // Value comparison
            $array_cnt = max($array_cnt, 1);
            $args = array_fill(0, max($array_cnt, 1), $arr);
            $args []= $valCb;
        }

        return $args;
    }
}
