<?php declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Type\ArrayType;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;

use function count;

class MinMaxReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['min', 'max'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union
    {
        $call_args = $event->getCallArgs();
        if (count($call_args) === 0) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        $nodeTypeProvider = $statements_source->getNodeTypeProvider();

        if (count($call_args) === 1
            && ($array_arg_type = $nodeTypeProvider->getType($call_args[0]->value))
            && $array_arg_type->isSingle()
            && $array_arg_type->hasArray()
            && ($array_type = ArrayType::infer($array_arg_type->getAtomicTypes()['array']))
        ) {
            return $array_type->value;
        }

        $return_type = null;

        foreach ($call_args as $arg) {
            if ($array_arg_type = $nodeTypeProvider->getType($arg->value)) {
                if (!$return_type) {
                    $return_type = $array_arg_type;
                } else {
                    $return_type = \Psalm\Type::combineUnionTypes(
                        $return_type,
                        $array_arg_type
                    );
                }
            } else {
                return Type::getMixed();
            }
        }

        return $return_type;
    }
}
