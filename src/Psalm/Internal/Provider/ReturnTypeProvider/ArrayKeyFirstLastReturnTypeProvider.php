<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;

/**
 * The key array_key_first() and array_key_last() return of a list held by a variable is a key of that list: writing
 * under it keeps it a list
 *
 * @internal
 */
final class ArrayKeyFirstLastReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['array_key_first', 'array_key_last'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $array_arg = $event->getCallArgs()[0] ?? null;

        if (!$statements_source instanceof StatementsAnalyzer
            || $array_arg === null
            || $array_arg->unpack
            || ($array_arg->name !== null && $array_arg->name->name !== 'array')
        ) {
            return null;
        }

        $list_var_id = ExpressionIdentifier::getExtendedVarId(
            $array_arg->value,
            $statements_source->getFQCLN(),
            $statements_source,
        );
        $array_type = $statements_source->node_data->getType($array_arg->value);

        if ($list_var_id === null
            || $array_type === null
            || !$array_type->isSingle()
            || !($list = $array_type->getSingleAtomic()) instanceof TKeyedArray
            || !$list->is_list
        ) {
            return null;
        }

        $key_type = $list->getGenericArrayType($list_var_id)->type_params[0];
        $event->getContext()->list_key_bound_lists[$list_var_id] = true;

        return $list->isNonEmpty()
            ? $key_type
            : $key_type->getBuilder()->addType(new TNull())->freeze();
    }
}
