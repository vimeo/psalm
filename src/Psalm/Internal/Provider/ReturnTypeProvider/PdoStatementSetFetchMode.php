<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\MethodParamsProviderEvent;
use Psalm\Type;

class PdoStatementSetFetchMode implements \Psalm\Plugin\EventHandler\MethodParamsProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['PDOStatement'];
    }

    /**
     * @return ?array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public static function getMethodParams(MethodParamsProviderEvent $event): ?array
    {
        $statements_source = $event->getStatementsSource();
        $method_name_lowercase = $event->getMethodNameLowercase();
        $context = $event->getContext();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return null;
        }

        if ($method_name_lowercase === 'setfetchmode') {
            if (!$context
                || !$call_args
                || \Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer::analyze(
                    $statements_source,
                    $call_args[0]->value,
                    $context
                ) === false
            ) {
                return null;
            }

            if (($first_call_arg_type = $statements_source->node_data->getType($call_args[0]->value))
                && $first_call_arg_type->isSingleIntLiteral()
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

                $value = $first_call_arg_type->getSingleIntLiteral()->value;

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

        return null;
    }
}
