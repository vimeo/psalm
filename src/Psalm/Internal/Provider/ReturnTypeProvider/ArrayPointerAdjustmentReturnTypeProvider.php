<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Type;

class ArrayPointerAdjustmentReturnTypeProvider implements \Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds() : array
    {
        return ['current', 'next', 'prev', 'reset', 'end'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event) : Type\Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return Type::getMixed();
        }

        $first_arg = isset($call_args[0]->value) ? $call_args[0]->value : null;

        if (!$first_arg) {
            return Type::getMixed();
        }

        $first_arg_type = $statements_source->node_data->getType($first_arg);

        if (!$first_arg_type) {
            return Type::getMixed();
        }

        $atomic_types = $first_arg_type->getAtomicTypes();

        $value_type = null;
        $definitely_has_items = false;

        while ($atomic_type = \array_shift($atomic_types)) {
            if ($atomic_type instanceof Type\Atomic\TTemplateParam) {
                $atomic_types = \array_merge($atomic_types, $atomic_type->as->getAtomicTypes());
                continue;
            }

            if ($atomic_type instanceof Type\Atomic\TArray) {
                $value_type = clone $atomic_type->type_params[1];
                $definitely_has_items = $atomic_type instanceof Type\Atomic\TNonEmptyArray;
            } elseif ($atomic_type instanceof Type\Atomic\TList) {
                $value_type = clone $atomic_type->type_param;
                $definitely_has_items = $atomic_type instanceof Type\Atomic\TNonEmptyList;
            } elseif ($atomic_type instanceof Type\Atomic\TKeyedArray) {
                $value_type = $atomic_type->getGenericValueType();
                $definitely_has_items = $atomic_type->getGenericArrayType() instanceof Type\Atomic\TNonEmptyArray;
            } else {
                return Type::getMixed();
            }
        }

        if (!$value_type) {
            throw new \UnexpectedValueException('This should never happen');
        }

        if ($value_type->isEmpty()) {
            $value_type = Type::getFalse();
        } elseif (($function_id !== 'reset' && $function_id !== 'end') || !$definitely_has_items) {
            $value_type->addType(new Type\Atomic\TFalse);

            $codebase = $statements_source->getCodebase();

            if ($codebase->config->ignore_internal_falsable_issues) {
                $value_type->ignore_falsable_issues = true;
            }
        }

        ArrayFetchAnalyzer::taintArrayFetch(
            $statements_source,
            $first_arg,
            null,
            $value_type,
            Type::getMixed()
        );

        return $value_type;
    }
}
