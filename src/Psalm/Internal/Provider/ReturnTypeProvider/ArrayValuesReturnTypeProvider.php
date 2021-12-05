<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use UnexpectedValueException;

use function array_merge;
use function array_shift;

class ArrayValuesReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_values'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Type\Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $first_arg = $call_args[0]->value ?? null;

        if (!$first_arg) {
            return Type::getArray();
        }

        $first_arg_type = $statements_source->node_data->getType($first_arg);

        if (!$first_arg_type) {
            return Type::getArray();
        }

        $atomic_types = $first_arg_type->getAtomicTypes();

        $return_atomic_type = null;

        while ($atomic_type = array_shift($atomic_types)) {
            if ($atomic_type instanceof Type\Atomic\TTemplateParam) {
                $atomic_types = array_merge($atomic_types, $atomic_type->as->getAtomicTypes());
                continue;
            }

            if ($atomic_type instanceof Type\Atomic\TKeyedArray) {
                $atomic_type = $atomic_type->getGenericArrayType();
            }

            if ($atomic_type instanceof Type\Atomic\TArray) {
                if ($atomic_type instanceof Type\Atomic\TNonEmptyArray) {
                    $return_atomic_type = new Type\Atomic\TNonEmptyList(
                        clone $atomic_type->type_params[1]
                    );
                } else {
                    $return_atomic_type = new Type\Atomic\TList(
                        clone $atomic_type->type_params[1]
                    );
                }
            } elseif ($atomic_type instanceof Type\Atomic\TList) {
                $return_atomic_type = $atomic_type;
            } else {
                return Type::getArray();
            }
        }

        if (!$return_atomic_type) {
            throw new UnexpectedValueException('This should never happen');
        }

        return new Type\Union([$return_atomic_type]);
    }
}
