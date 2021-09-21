<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Type;

use function in_array;

class StrReplaceReturnTypeProvider implements \Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds() : array
    {
        return [
            'str_replace',
            'str_ireplace',
            'substr_replace',
            'preg_replace',
            'preg_replace_callback',
        ];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event) : Type\Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer
            || \count($call_args) < 3
        ) {
            return Type::getMixed();
        }

        if ($subject_type = $statements_source->node_data->getType($call_args[2]->value)) {
            if (!$subject_type->hasString() && $subject_type->hasArray()) {
                return Type::getArray();
            }

            $return_type = Type::getString();

            if (in_array($function_id, ['preg_replace', 'preg_replace_callback'], true)) {
                $return_type->addType(new Type\Atomic\TNull());

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
