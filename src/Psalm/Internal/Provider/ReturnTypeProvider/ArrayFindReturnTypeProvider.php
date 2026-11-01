<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;

/**
 * @internal
 */
final class ArrayFindReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['array_find', 'array_find_key'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();

        if (!$statements_source instanceof StatementsAnalyzer) {
            return null;
        }

        $array_arg = null;

        foreach ($event->getCallArgs() as $i => $arg) {
            if ($arg->unpack) {
                return null;
            }

            if (($arg->name === null && $i === 0) || $arg->name?->name === 'array') {
                $array_arg = $arg;
                break;
            }
        }

        $array_type = $array_arg ? $statements_source->node_data->getType($array_arg->value) : null;

        if (!$array_type) {
            return null;
        }

        $is_key = $event->getFunctionId() === 'array_find_key';
        $found_type = Type::getNever();

        foreach ($array_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TArray) {
                $part = $atomic_type->type_params[$is_key ? 0 : 1];
            } elseif ($atomic_type instanceof TKeyedArray) {
                $part = $is_key ? $atomic_type->getGenericKeyType() : $atomic_type->getGenericValueType();
            } else {
                return null;
            }

            $found_type = Type::combineUnionTypes($found_type, $part);
        }

        // no element may satisfy the callback
        $found_type = $found_type->getBuilder()->addType(new TNull);

        if ($statements_source->getCodebase()->config->ignore_internal_nullable_issues) {
            $found_type->ignore_nullable_issues = true;
        }

        return $found_type->freeze();
    }
}
