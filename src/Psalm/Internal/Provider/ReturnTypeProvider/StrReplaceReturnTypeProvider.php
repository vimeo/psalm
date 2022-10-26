<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

use function count;
use function in_array;

/**
 * @internal
 */
class StrReplaceReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [
            'str_replace',
            'str_ireplace',
            'substr_replace',
            'preg_replace',
            'preg_replace_callback',
        ];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        if (!$statements_source instanceof StatementsAnalyzer
            || count($call_args) < 3
        ) {
            return Type::getMixed();
        }

        if ($subject_type = $statements_source->node_data->getType($call_args[2]->value)) {
            if (!$subject_type->hasString() && $subject_type->hasArray()) {
                return Type::getArray();
            }

            $return_type = Type::getString();

            if (in_array($function_id, ['str_replace', 'str_ireplace'], true)
                && $subject_type->isSingleLiteral()
            ) {
                $first_arg = $statements_source->node_data->getType($call_args[0]->value);
                $second_arg = $statements_source->node_data->getType($call_args[1]->value);
                if ($first_arg && $second_arg && $first_arg->isSingleLiteral() && $second_arg->isSingleLiteral()) {
                    $replaced_string = call_user_func(
                        $function_id,
                        $first_arg->getSingleLiteral()->value,
                        $second_arg->getSingleLiteral()->value,
                        $subject_type->getSingleLiteral()->value
                    );
                    $return_type = Type::getString($replaced_string);
                }
            } elseif (in_array($function_id, ['preg_replace', 'preg_replace_callback'], true)) {
                $return_type = new Union([new TString, new TNull()]);

                $codebase = $statements_source->getCodebase();

                if ($codebase->config->ignore_internal_nullable_issues) {
                    $return_type->ignore_nullable_issues = true;
                }
            }

            return $return_type;
        }

        return Type::getMixed();
    }
}
