<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class PdoStatementReturnTypeProvider implements \Psalm\Plugin\Hook\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['PDOStatement'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     *
     * @return ?Type\Union
     */
    public static function getMethodReturnType(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args,
        Context $context,
        CodeLocation $code_location,
        array $template_type_parameters = null,
        string $called_fq_classlike_name = null,
        string $called_method_name_lowercase = null
    ) {
        if ($method_name_lowercase === 'fetch'
            && \class_exists('PDO')
            && isset($call_args[0])
            && ($first_arg_type = $source->getNodeTypeProvider()->getType($call_args[0]->value))
            && $first_arg_type->isSingleIntLiteral()
        ) {
            $value = $first_arg_type->getSingleIntLiteral()->value;

            if ($value === \PDO::FETCH_ASSOC) {
                return Type::getArray();
            }
        }
    }
}
