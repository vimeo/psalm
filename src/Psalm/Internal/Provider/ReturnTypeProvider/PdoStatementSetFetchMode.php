<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class PdoStatementSetFetchMode implements \Psalm\Plugin\Hook\MethodParamsProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['PDOStatement'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     *
     * @return ?array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public static function getMethodParams(
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args = null,
        StatementsSource $statements_source = null,
        Context $context = null,
        CodeLocation $code_location = null
    ) {
        if ($method_name_lowercase === 'setfetchmode') {
            if (!$context
                || !$call_args
                || !$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer
                || \Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer::analyze(
                    $statements_source,
                    $call_args[0]->value,
                    $context
                ) === false
            ) {
                return;
            }

            if (isset($call_args[0]->value->inferredType)
                && $call_args[0]->value->inferredType->isSingleIntLiteral()
            ) {
                $params = [
                    new \Psalm\Storage\FunctionLikeParameter(
                        'mode',
                        false,
                        Type::getInt(),
                        null,
                        null,
                        false
                    ),
                ];

                $value = $call_args[0]->value->inferredType->getSingleIntLiteral()->value;

                switch ($value) {
                    case \PDO::FETCH_COLUMN:
                        $params[] = new \Psalm\Storage\FunctionLikeParameter(
                            'colno',
                            false,
                            Type::getInt(),
                            null,
                            null,
                            false
                        );
                        break;

                    case \PDO::FETCH_CLASS:
                        $params[] = new \Psalm\Storage\FunctionLikeParameter(
                            'classname',
                            false,
                            Type::getClassString(),
                            null,
                            null,
                            false
                        );

                        $params[] = new \Psalm\Storage\FunctionLikeParameter(
                            'ctorargs',
                            false,
                            Type::getArray(),
                            null,
                            null,
                            true
                        );
                        break;

                    case \PDO::FETCH_INTO:
                        $params[] = new \Psalm\Storage\FunctionLikeParameter(
                            'object',
                            false,
                            Type::getObject(),
                            null,
                            null,
                            false
                        );
                        break;
                }

                return $params;
            }
        }
    }
}
