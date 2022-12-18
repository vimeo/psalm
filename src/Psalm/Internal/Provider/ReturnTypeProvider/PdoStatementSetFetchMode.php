<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\MethodParamsProviderEvent;
use Psalm\Plugin\EventHandler\MethodParamsProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;

/**
 * @internal
 */
class PdoStatementSetFetchMode implements MethodParamsProviderInterface
{
    public static function getClassLikeNames(): array
    {
        return ['PDOStatement'];
    }

    /**
     * @return ?array<int, FunctionLikeParameter>
     */
    public static function getMethodParams(MethodParamsProviderEvent $event): ?array
    {
        $statements_source = $event->getStatementsSource();
        $method_name_lowercase = $event->getMethodNameLowercase();
        $context = $event->getContext();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return null;
        }

        if ($method_name_lowercase === 'setfetchmode') {
            if (!$context
                || !$call_args
                || ExpressionAnalyzer::analyze(
                    $statements_source,
                    $call_args[0]->value,
                    $context,
                ) === false
            ) {
                return null;
            }

            if (($first_call_arg_type = $statements_source->node_data->getType($call_args[0]->value))
                && $first_call_arg_type->isSingleIntLiteral()
            ) {
                $params = [
                    new FunctionLikeParameter(
                        'mode',
                        false,
                        Type::getInt(),
                        Type::getInt(),
                        null,
                        null,
                        false,
                    ),
                ];

                $value = $first_call_arg_type->getSingleIntLiteral()->value;

                switch ($value) {
                    case 7: // PDO::FETCH_COLUMN
                        $params[] = new FunctionLikeParameter(
                            'colno',
                            false,
                            Type::getInt(),
                            Type::getInt(),
                            null,
                            null,
                            false,
                        );
                        break;

                    case 8: // PDO::FETCH_CLASS
                        $params[] = new FunctionLikeParameter(
                            'classname',
                            false,
                            Type::getClassString(),
                            Type::getClassString(),
                            null,
                            null,
                            false,
                        );

                        $params[] = new FunctionLikeParameter(
                            'ctorargs',
                            false,
                            Type::getArray(),
                            Type::getArray(),
                            null,
                            null,
                            true,
                        );
                        break;

                    case 9: // PDO::FETCH_INTO
                        $params[] = new FunctionLikeParameter(
                            'object',
                            false,
                            Type::getObject(),
                            Type::getObject(),
                            null,
                            null,
                            false,
                        );
                        break;
                }

                return $params;
            }
        }

        return null;
    }
}
