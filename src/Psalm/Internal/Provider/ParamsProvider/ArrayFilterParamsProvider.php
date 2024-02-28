<?php

namespace Psalm\Internal\Provider\ParamsProvider;

use PhpParser\Node\Expr\ConstFetch;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\PossiblyInvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function strtolower;

use const ARRAY_FILTER_USE_BOTH;
use const ARRAY_FILTER_USE_KEY;

/**
 * @internal
 */
class ArrayFilterParamsProvider implements FunctionParamsProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [
            'array_filter',
        ];
    }

    /**
     * @return ?list<FunctionLikeParameter>
     */
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array
    {
        $call_args = $event->getCallArgs();
        if (!isset($call_args[0]) || !isset($call_args[1])) {
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
        if ($call_args[1]->value instanceof ConstFetch
            && strtolower($call_args[1]->value->name->toString()) === 'null'
            && isset($call_args[2])
        ) {
            if ($code_location) {
                // using e.g. ARRAY_FILTER_USE_KEY as 3rd arg won't have any effect if the 2nd arg is null
                // as it will still filter on the values
                IssueBuffer::maybeAdd(
                    new InvalidArgument(
                        'The 3rd argument of array_filter is not used, when the 2nd argument is null',
                        $code_location,
                        'array_filter',
                    ),
                    $statements_source->getSuppressedIssues(),
                );
            }

            return null;
        }

        // currently only supports literal types and variables (but not function calls)
        // due to https://github.com/vimeo/psalm/issues/8905
        $first_arg_type = SimpleTypeInferer::infer(
            $statements_source->getCodebase(),
            $statements_source->node_data,
            $call_args[0]->value,
            $statements_source->getAliases(),
            $statements_source,
        );

        if (!$first_arg_type) {
            $extended_var_id = ExpressionIdentifier::getExtendedVarId(
                $call_args[0]->value,
                null,
                $statements_source,
            );

            $first_arg_type = $event->getContext()->vars_in_scope[$extended_var_id] ?? null;
        }

        $fallback = new TArray([Type::getArrayKey(), Type::getMixed()]);
        if (!$first_arg_type || $first_arg_type->isMixed()) {
            $first_arg_array = $fallback;
        } else {
            $first_arg_array = $first_arg_type->hasType('array')
                               && ($array_atomic_type = $first_arg_type->getArray())
                               && ($array_atomic_type instanceof TArray
                                   || $array_atomic_type instanceof TKeyedArray)
                ? $array_atomic_type
                : $fallback;
        }

        if ($first_arg_array instanceof TArray) {
            $inner_type = $first_arg_array->type_params[1];
            $key_type = $first_arg_array->type_params[0];
        } else {
            $inner_type = $first_arg_array->getGenericValueType();
            $key_type   = $first_arg_array->getGenericKeyType();
        }

        $has_both = false;
        if (isset($call_args[2])) {
            $mode_type = SimpleTypeInferer::infer(
                $statements_source->getCodebase(),
                $statements_source->node_data,
                $call_args[2]->value,
                $statements_source->getAliases(),
                $statements_source,
            );

            if (!$mode_type && $call_args[2]->value instanceof ConstFetch) {
                $mode_type = ConstFetchAnalyzer::getConstType(
                    $statements_source,
                    $call_args[2]->value->name->toString(),
                    true,
                    $event->getContext(),
                );
            } elseif (!$mode_type) {
                $extended_var_id = ExpressionIdentifier::getExtendedVarId(
                    $call_args[2]->value,
                    null,
                    $statements_source,
                );

                $mode_type = $event->getContext()->vars_in_scope[$extended_var_id] ?? null;
            }

            if (!$mode_type || !$mode_type->allIntLiterals()) {
                // if we have multiple possible types, keep the default args
                return null;
            }

            if ($mode_type->isSingleIntLiteral()) {
                $mode = $mode_type->getSingleIntLiteral()->value;
            } else {
                $mode = 0;
                foreach ($mode_type->getLiteralInts() as $atomic) {
                    if ($atomic->value === ARRAY_FILTER_USE_BOTH) {
                        // we have one which uses both keys and values and one that uses only keys/values
                        $has_both = true;
                        continue;
                    }

                    if ($atomic->value === ARRAY_FILTER_USE_KEY) {
                        // if one of them is ARRAY_FILTER_USE_KEY, all the other types will behave like mode 0
                        $inner_type = Type::combineUnionTypes(
                            $inner_type,
                            $key_type,
                            $statements_source->getCodebase(),
                        );

                        continue;
                    }

                    // to report an error later on
                    if ($mode === 0 && $atomic->value !== 0) {
                        $mode = $atomic->value;
                    }
                }
            }

            if ($mode > ARRAY_FILTER_USE_KEY || $mode < 0) {
                if ($code_location) {
                    IssueBuffer::maybeAdd(
                        new PossiblyInvalidArgument(
                            'The provided 3rd argument of array_filter contains a value of ' . $mode
                            . ', which will behave like 0 and filter on values only',
                            $code_location,
                            'array_filter',
                        ),
                        $statements_source->getSuppressedIssues(),
                    );
                }

                $mode = 0;
            }
        } else {
            $mode = 0;
        }

        $callback_arg_value = new FunctionLikeParameter(
            'value',
            false,
            $inner_type,
            null,
            null,
            null,
            false,
        );

        $callback_arg_key = new FunctionLikeParameter(
            'key',
            false,
            $key_type,
            null,
            null,
            null,
            false,
        );

        if ($mode === ARRAY_FILTER_USE_BOTH) {
            $callback_arg = [
                $callback_arg_value,
                $callback_arg_key,
            ];
        } elseif ($mode === ARRAY_FILTER_USE_KEY) {
            $callback_arg = [
                $callback_arg_key,
            ];
        } elseif ($has_both) {
            // if we have both + other flags, the 2nd arg is optional
            $callback_arg_key->is_optional = true;
            $callback_arg = [
                $callback_arg_value,
                $callback_arg_key,
            ];
        } else {
            $callback_arg = [
                $callback_arg_value,
            ];
        }

        $callable = new TCallable(
            'callable',
            $callback_arg,
            Type::getMixed(),
        );

        return [
            new FunctionLikeParameter(
                'array',
                false,
                Type::getArray(),
                Type::getArray(),
                null,
                null,
                false,
            ),
            new FunctionLikeParameter('callback', false, new Union([$callable])),
            new FunctionLikeParameter('mode', false, Type::getInt(), Type::getInt()),
        ];
    }
}
