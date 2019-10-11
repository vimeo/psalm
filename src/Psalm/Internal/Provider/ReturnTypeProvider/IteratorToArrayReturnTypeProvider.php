<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use function assert;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Internal\Codebase\CallMap;
use Psalm\StatementsSource;
use Psalm\Type;

class IteratorToArrayReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return [
            'iterator_to_array',
        ];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        if (isset($call_args[0]->value->inferredType)
            && $call_args[0]->value->inferredType->hasObjectType()
        ) {
            $key_type = null;
            $value_type = null;

            $codebase = $statements_source->getCodebase();

            foreach ($call_args[0]->value->inferredType->getTypes() as $call_arg_atomic_type) {
                if ($call_arg_atomic_type instanceof Type\Atomic\TNamedObject
                    && TypeAnalyzer::isAtomicContainedBy(
                        $codebase,
                        $call_arg_atomic_type,
                        new Type\Atomic\TIterable([Type::getMixed(), Type::getMixed()])
                    )
                ) {
                    assert($statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer);

                    $has_valid_iterator = true;
                    ForeachAnalyzer::handleIterable(
                        $statements_source,
                        $call_arg_atomic_type,
                        $call_args[0]->value,
                        $codebase,
                        $context,
                        $key_type,
                        $value_type,
                        $has_valid_iterator
                    );
                }
            }

            if ($value_type) {
                if (isset($call_args[1]->value->inferredType)
                    && ((string) $call_args[1]->value->inferredType === 'false')
                ) {
                    return new Type\Union([
                        new Type\Atomic\TList($value_type),
                    ]);
                }

                return new Type\Union([
                    new Type\Atomic\TArray([
                        $key_type
                            && (!isset($call_args[1]->value)
                                || (isset($call_args[1]->value->inferredType)
                                    && ((string) $call_args[1]->value->inferredType === 'true')))
                            ? $key_type
                            : Type::getArrayKey(),
                        $value_type,
                    ]),
                ]);
            }
        }

        $callmap_callables = CallMap::getCallablesFromCallMap($function_id);

        assert($callmap_callables && $callmap_callables[0]->return_type);

        return $callmap_callables[0]->return_type;
    }
}
