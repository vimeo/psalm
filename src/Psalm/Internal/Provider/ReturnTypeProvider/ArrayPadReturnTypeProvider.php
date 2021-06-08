<?php declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Type\ArrayType;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Type;

use function count;

class ArrayPadReturnTypeProvider implements \Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_pad'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $type_provider = $statements_source->getNodeTypeProvider();

        if (count($call_args) >= 3
            && ($array_arg_type = $type_provider->getType($call_args[0]->value))
            && ($size_arg_type = $type_provider->getType($call_args[1]->value))
            && ($value_arg_type = $type_provider->getType($call_args[2]->value))
            && $array_arg_type->isSingle()
            && $array_arg_type->hasArray()
            && ($array_type = ArrayType::infer($array_arg_type->getAtomicTypes()['array']))
        ) {
            $codebase = $statements_source->getCodebase();
            $key_type = Type::combineUnionTypes($array_type->key, Type::getInt(), $codebase);
            $value_type = Type::combineUnionTypes($array_type->value, $value_arg_type, $codebase);
            $can_return_empty = (
                !$size_arg_type->isSingleIntLiteral()
                || $size_arg_type->getSingleIntLiteral()->value === 0
            );

            return new Type\Union([
                $array_type->is_list
                    ? (
                        $can_return_empty
                            ? new Type\Atomic\TList($value_type)
                            : new Type\Atomic\TNonEmptyList($value_type)
                    )
                    : (
                        $can_return_empty
                            ? new Type\Atomic\TArray([$key_type, $value_type])
                            : new Type\Atomic\TNonEmptyArray([$key_type, $value_type])
                    )
            ]);
        }

        return Type::getArray();
    }
}
