<?php

namespace Psalm\Internal\Provider\ParamsProvider;

use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;

use function in_array;

use const SORT_ASC;
use const SORT_DESC;
use const SORT_FLAG_CASE;
use const SORT_LOCALE_STRING;
use const SORT_NATURAL;
use const SORT_NUMERIC;
use const SORT_REGULAR;
use const SORT_STRING;

/**
 * @internal
 */
class ArrayMultisortParamsProvider implements FunctionParamsProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [
            'array_multisort',
        ];
    }

    /**
     * @return ?list<FunctionLikeParameter>
     */
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array
    {
        $call_args = $event->getCallArgs();
        if (!isset($call_args[0])) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        if (!($statements_source instanceof StatementsAnalyzer)) {
            // this is practically impossible
            // but the type in the caller is parent type StatementsSource
            // even though all callers provide StatementsAnalyzer
            return null;
        }

        $code_location = $event->getCodeLocation();
        $params = [];
        $previous_param = false;
        $last_array_index = 0;
        $last_by_ref_index = -1;
        $first_non_ref_index_after_by_ref = -1;
        foreach ($call_args as $key => $call_arg) {
            $param_type = SimpleTypeInferer::infer(
                $statements_source->getCodebase(),
                $statements_source->node_data,
                $call_arg->value,
                $statements_source->getAliases(),
                $statements_source,
            );

            if (!$param_type && $call_arg->value instanceof ConstFetch) {
                $param_type = ConstFetchAnalyzer::getConstType(
                    $statements_source,
                    $call_arg->value->name->toString(),
                    true,
                    $event->getContext(),
                );
            }

            // @todo currently assumes any function calls are for array types not for sort order/flags
            // actually need to check the return type
            // which isn't possible atm due to https://github.com/vimeo/psalm/issues/8905
            if (!$param_type && ($call_arg->value instanceof FuncCall || $call_arg->value instanceof MethodCall)) {
                if ($first_non_ref_index_after_by_ref < $last_by_ref_index) {
                    $first_non_ref_index_after_by_ref = $key;
                }

                $last_array_index = $key;
                $previous_param = 'array';
                $params[] = new FunctionLikeParameter(
                    'array' . ($last_array_index + 1),
                    // function calls will not be used by reference
                    false,
                    Type::getArray(),
                    $key === 0 ? Type::getArray() : null,
                );

                continue;
            }

            $extended_var_id = null;
            if (!$param_type) {
                $extended_var_id = ExpressionIdentifier::getExtendedVarId(
                    $call_arg->value,
                    null,
                    $statements_source,
                );

                $param_type = $event->getContext()->vars_in_scope[$extended_var_id] ?? null;
            }

            if (!$param_type) {
                return null;
            }

            if ($key === 0 && !$param_type->isArray()) {
                return null;
            }

            if ($param_type->isArray() && $extended_var_id) {
                $last_by_ref_index = $key;
                $last_array_index = $key;
                $previous_param = 'array';
                $params[] = new FunctionLikeParameter(
                    'array' . ($last_array_index + 1),
                    true,
                    $param_type,
                    $key === 0 ? Type::getArray() : null,
                );

                continue;
            }

            if ($param_type->allIntLiterals()) {
                $sort_order = [
                    SORT_ASC,
                    SORT_DESC,
                ];

                $sort_flags = [
                    SORT_REGULAR,
                    SORT_NUMERIC,
                    SORT_STRING,
                    SORT_LOCALE_STRING,
                    SORT_NATURAL,
                    SORT_STRING|SORT_FLAG_CASE,
                    SORT_NATURAL|SORT_FLAG_CASE,
                ];

                $sort_param = false;
                foreach ($param_type->getLiteralInts() as $atomic) {
                    if (in_array($atomic->value, $sort_order, true)) {
                        if ($sort_param === 'sort_order_flags') {
                            continue;
                        }

                        if ($sort_param === 'sort_order') {
                            continue;
                        }

                        if ($sort_param === 'sort_flags') {
                            $sort_param = 'sort_order_flags';
                            continue;
                        }

                        $sort_param = 'sort_order';

                        continue;
                    }

                    if (in_array($atomic->value, $sort_flags, true)) {
                        if ($sort_param === 'sort_order_flags') {
                            continue;
                        }

                        if ($sort_param === 'sort_flags') {
                            continue;
                        }

                        if ($sort_param === 'sort_order') {
                            $sort_param = 'sort_order_flags';
                            continue;
                        }

                        $sort_param = 'sort_flags';

                        continue;
                    }

                    if ($code_location) {
                        IssueBuffer::maybeAdd(
                            new InvalidArgument(
                                'Argument ' . ( $key + 1 )
                                . ' of array_multisort sort order/flag contains an invalid value of ' . $atomic->value,
                                $code_location,
                                'array_multisort',
                            ),
                            $statements_source->getSuppressedIssues(),
                        );
                    }
                }

                if ($sort_param === false) {
                    return null;
                }

                if (($sort_param === 'sort_order' || $sort_param === 'sort_order_flags')
                    && $previous_param !== 'array') {
                    if ($code_location) {
                        IssueBuffer::maybeAdd(
                            new InvalidArgument(
                                'Argument ' . ( $key + 1 )
                                . ' of array_multisort contains sort order flags'
                                . ' and can only be used after an array parameter',
                                $code_location,
                                'array_multisort',
                            ),
                            $statements_source->getSuppressedIssues(),
                        );
                    }

                    return null;
                }

                if ($sort_param === 'sort_flags' && $previous_param !== 'array' && $previous_param !== 'sort_order') {
                    if ($code_location) {
                        IssueBuffer::maybeAdd(
                            new InvalidArgument(
                                'Argument ' . ( $key + 1 )
                                . ' of array_multisort are sort flags'
                                . ' and cannot be used after a parameter with sort flags',
                                $code_location,
                                'array_multisort',
                            ),
                            $statements_source->getSuppressedIssues(),
                        );
                    }

                    return null;
                }

                if ($sort_param === 'sort_order_flags') {
                    $previous_param = 'sort_order';
                } else {
                    $previous_param = $sort_param;
                }

                $params[] = new FunctionLikeParameter(
                    'array' . ($last_array_index + 1) . '_' . $previous_param,
                    false,
                    Type::getInt(),
                );

                continue;
            }

            if (!$param_type->isArray()) {
                // too complex for now
                return null;
            }

            if ($first_non_ref_index_after_by_ref < $last_by_ref_index) {
                $first_non_ref_index_after_by_ref = $key;
            }

            $last_array_index = $key;
            $previous_param = 'array';
            $params[] = new FunctionLikeParameter(
                'array' . ($last_array_index + 1),
                false,
                Type::getArray(),
            );
        }

        if ($code_location) {
            if ($last_by_ref_index === - 1) {
                IssueBuffer::maybeAdd(
                    new InvalidArgument(
                        'At least 1 array argument of array_multisort must be a variable,'
                        . ' since the sorting happens by reference and otherwise this function call does nothing',
                        $code_location,
                        'array_multisort',
                    ),
                    $statements_source->getSuppressedIssues(),
                );
            } elseif ($first_non_ref_index_after_by_ref > $last_by_ref_index) {
                IssueBuffer::maybeAdd(
                    new InvalidArgument(
                        'All arguments of array_multisort after argument ' . $first_non_ref_index_after_by_ref
                        . ', which are after the last by reference passed array argument and its flags,'
                        . ' are redundant and can be removed, since the sorting happens by reference',
                        $code_location,
                        'array_multisort',
                    ),
                    $statements_source->getSuppressedIssues(),
                );
            }
        }

        return $params;
    }
}
