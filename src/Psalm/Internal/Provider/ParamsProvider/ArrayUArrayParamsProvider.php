<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ParamsProvider;

use Override;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\StrId;
use Psalm\Type;

use function array_fill;
use function assert;
use function count;
use function max;

/**
 * @internal
 */
final class ArrayUArrayParamsProvider implements FunctionParamsProviderInterface
{

    /**
     * @return array<int>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return [
            StrId::array_diff_ukey,
            StrId::array_diff_uassoc,
            StrId::array_intersect_ukey,
            StrId::array_intersect_uassoc,

            StrId::array_udiff_uassoc,
            StrId::array_uintersect_uassoc,

            StrId::array_udiff,
            StrId::array_udiff_assoc,
            StrId::array_uintersect,
            StrId::array_uintersect_assoc,
        ];
    }

    private static ?FunctionLikeParameter $arr = null;
    /**
     * @return ?list<FunctionLikeParameter>
     */
    #[Override]
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
        $cb = InternalCallMapHandler::getCallablesFromCallMap(StrId::array_udiff_uassoc)[0]->params;
        assert(isset($cb[2]) && isset($cb[3]));
        $valCb = $cb[2];
        $keyCb = $cb[3];
        $arr = self::$arr ??= new FunctionLikeParameter(
            StrId::array,
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

        if ($func === StrId::array_diff_ukey
            || $func === StrId::array_diff_uassoc
            || $func === StrId::array_intersect_ukey
            || $func === StrId::array_intersect_uassoc
        ) {
            // Key comparison
            $args = array_fill(0, max($array_cnt, 1), $arr);
            $args []= $keyCb;
        } elseif ($func === StrId::array_udiff_uassoc
            || $func === StrId::array_uintersect_uassoc
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
