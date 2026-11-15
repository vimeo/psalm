<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ParamsProvider;

use Override;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

use const PREG_OFFSET_CAPTURE;
use const PREG_UNMATCHED_AS_NULL;

/**
 * With PREG_UNMATCHED_AS_NULL the groups that didn't match are null in the array the callback gets, and with
 * PREG_OFFSET_CAPTURE each of them is a pair of the text and its offset
 *
 * @internal
 */
final class PregReplaceCallbackParamsProvider implements FunctionParamsProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return [
            'preg_replace_callback',
        ];
    }

    /**
     * @return ?list<FunctionLikeParameter>
     */
    #[Override]
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array
    {
        $statements_source = $event->getStatementsSource();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return null;
        }

        // the params of the stub apply, unless the flags say otherwise
        $params = $statements_source->getCodebase()->functions->getStorage(
            $statements_source,
            'preg_replace_callback',
        )->params;

        $flags_arg = null;
        foreach ($event->getCallArgs() as $position => $call_arg) {
            if ($call_arg->unpack) {
                return $params;
            }

            if ($call_arg->name !== null ? $call_arg->name->toString() === 'flags' : $position === 5) {
                $flags_arg = $call_arg;
            }
        }

        $flags = $flags_arg !== null ? self::getFlags($event, $statements_source, $flags_arg) : null;

        if ($flags === null || ($flags & (PREG_UNMATCHED_AS_NULL | PREG_OFFSET_CAPTURE)) === 0) {
            return $params;
        }

        // a group that did not match is null, and with PREG_OFFSET_CAPTURE each group is a pair of the text and its
        // offset
        $group_text = ($flags & PREG_UNMATCHED_AS_NULL) !== 0
            ? new Union([new TString(), new TNull()])
            : Type::getString();
        $group = ($flags & PREG_OFFSET_CAPTURE) !== 0
            ? new Union([TKeyedArray::make([$group_text, Type::getInt()], null, null, true)])
            : $group_text;

        $matches = new Union([new TArray([Type::getArrayKey(), $group])]);

        foreach ($params as $position => $param) {
            if ($param->name !== 'callback' || !$param->type) {
                continue;
            }

            $callback_types = [];
            foreach ($param->type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TCallable && isset($atomic_type->params[0])) {
                    $callback_params = $atomic_type->params;
                    $callback_params[0] = $callback_params[0]->setType($matches);
                    $atomic_type = new TCallable(
                        $callback_params,
                        $atomic_type->return_type,
                        $atomic_type->purity,
                        $atomic_type->from_docblock,
                        $atomic_type->callable_id,
                    );
                }

                $callback_types[] = $atomic_type;
            }

            $params[$position] = $param->setType(new Union($callback_types));
        }

        return $params;
    }

    /**
     * The flags, when they are an int literal
     */
    private static function getFlags(
        FunctionParamsProviderEvent $event,
        StatementsAnalyzer $statements_source,
        Arg $flags_arg,
    ): ?int {
        $flags_type = SimpleTypeInferer::infer(
            $statements_source->getCodebase(),
            $statements_source->node_data,
            $flags_arg->value,
            $statements_source->getAliases(),
            $statements_source,
        );

        if (!$flags_type && $flags_arg->value instanceof ConstFetch) {
            $flags_type = ConstFetchAnalyzer::getConstType(
                $statements_source,
                $flags_arg->value->name->toString(),
                true,
                $event->getContext(),
            );
        }

        return $flags_type !== null && $flags_type->isSingleIntLiteral()
            ? $flags_type->getSingleIntLiteral()->value
            : null;
    }
}
