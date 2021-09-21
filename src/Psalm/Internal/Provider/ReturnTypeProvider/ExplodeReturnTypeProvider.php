<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Type;

class ExplodeReturnTypeProvider implements \Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds() : array
    {
        return ['explode'];
    }

    /**
     * @param  list<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event) : Type\Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return Type::getMixed();
        }

        if (\count($call_args) >= 2) {
            $second_arg_type = $statements_source->node_data->getType($call_args[1]->value);

            $inner_type = new Type\Union([
                $second_arg_type && $second_arg_type->hasLowercaseString()
                    ? new Type\Atomic\TLowercaseString()
                    : new Type\Atomic\TString
            ]);

            $can_return_empty = isset($call_args[2])
                && (
                    !$call_args[2]->value instanceof PhpParser\Node\Scalar\LNumber
                    || $call_args[2]->value->value < 0
                );

            if ($call_args[0]->value instanceof PhpParser\Node\Scalar\String_) {
                if ($call_args[0]->value->value === '') {
                    return Type::getFalse();
                }

                return new Type\Union([
                    $can_return_empty
                        ? new Type\Atomic\TList($inner_type)
                        : new Type\Atomic\TNonEmptyList($inner_type)
                ]);
            } elseif (($first_arg_type = $statements_source->node_data->getType($call_args[0]->value))
                && $first_arg_type->hasString()
            ) {
                $can_be_false = true;
                if ($first_arg_type->isString()) {
                    $can_be_false = false;
                    foreach ($first_arg_type->getAtomicTypes() as $string_type) {
                        if (!($string_type instanceof Type\Atomic\TNonEmptyString)) {
                            $can_be_false = true;
                            break;
                        }
                    }
                }
                if ($can_be_false) {
                    $array_type = new Type\Union([
                        $can_return_empty
                            ? new Type\Atomic\TList($inner_type)
                            : new Type\Atomic\TNonEmptyList($inner_type),
                        new Type\Atomic\TFalse
                    ]);

                    if ($statements_source->getCodebase()->config->ignore_internal_falsable_issues) {
                        $array_type->ignore_falsable_issues = true;
                    }
                } else {
                    $array_type = new Type\Union([
                        $can_return_empty
                            ? new Type\Atomic\TList($inner_type)
                            : new Type\Atomic\TNonEmptyList($inner_type),
                    ]);
                }

                return $array_type;
            }
        }

        return Type::getMixed();
    }
}
