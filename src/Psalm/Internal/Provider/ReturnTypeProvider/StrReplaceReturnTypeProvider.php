<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\StatementsSource;

class StrReplaceReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return [
            'str_replace',
            'str_ireplace',
            'substr_replace',
            'preg_replace',
            'preg_replace_callback'
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
        if (isset($call_args[2]->value->inferredType)) {
            $subject_type = $call_args[2]->value->inferredType;

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
