<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Codebase\CallMap;
use Psalm\IssueBuffer;
use Psalm\Issue\InvalidReturnType;
use Psalm\StatementsSource;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Type;
use Psalm\Type\Reconciler;
use function array_map;
use function count;
use function is_string;
use function assert;

class ArrayFilterReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['array_filter'];
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
        $array_arg = isset($call_args[0]->value) ? $call_args[0]->value : null;

        $first_arg_array = $array_arg
            && isset($array_arg->inferredType)
            && $array_arg->inferredType->hasType('array')
            && ($array_atomic_type = $array_arg->inferredType->getTypes()['array'])
            && ($array_atomic_type instanceof Type\Atomic\TArray ||
                $array_atomic_type instanceof Type\Atomic\ObjectLike)
            ? $array_atomic_type
            : null;

        if (!$first_arg_array) {
            return Type::getArray();
        }

        if ($first_arg_array instanceof Type\Atomic\TArray) {
            $inner_type = $first_arg_array->type_params[1];
            $key_type = clone $first_arg_array->type_params[0];
        } else {
            $inner_type = $first_arg_array->getGenericValueType();
            $key_type = $first_arg_array->getGenericKeyType();
        }

        if (!isset($call_args[1])) {
            $inner_type->removeType('null');
            $inner_type->removeType('false');
        } elseif (!isset($call_args[2])) {
            $function_call_arg = $call_args[1];

            $first_arg_value = $function_call_arg->value;

            if ($first_arg_value instanceof PhpParser\Node\Scalar\String_
                && CallMap::inCallMap($first_arg_value->value)
            ) {
                $callables = CallMap::getCallablesFromCallMap($first_arg_value->value);

                if ($callables) {
                    $callable = clone $callables[0];

                    if ($callable->params !== null && $callable->return_type) {
                        $first_arg_value = new PhpParser\Node\Expr\Closure([
                            'params' => array_map(
                                function (\Psalm\Storage\FunctionLikeParameter $param) {
                                    return new PhpParser\Node\Param(
                                        new PhpParser\Node\Expr\Variable($param->name)
                                    );
                                },
                                $callable->params
                            ),
                            'stmts' => [
                                new PhpParser\Node\Stmt\Return_(
                                    new PhpParser\Node\Expr\FuncCall(
                                        new PhpParser\Node\Name\FullyQualified(
                                            $first_arg_value->value
                                        ),
                                        array_map(
                                            function (\Psalm\Storage\FunctionLikeParameter $param) {
                                                return new PhpParser\Node\Arg(
                                                    new PhpParser\Node\Expr\Variable($param->name)
                                                );
                                            },
                                            $callable->params
                                        )
                                    )
                                )
                            ],
                        ]);

                        $closure_atomic_type = new Type\Atomic\TFn(
                            'Closure',
                            $callable->params,
                            $callable->return_type
                        );

                        $first_arg_value->inferredType = new Type\Union([$closure_atomic_type]);
                    }
                }
            }

            if ($first_arg_value instanceof PhpParser\Node\Expr\Closure
                && isset($first_arg_value->inferredType)
                && ($closure_atomic_type = $first_arg_value->inferredType->getTypes()['Closure'])
                && $closure_atomic_type instanceof Type\Atomic\TFn
            ) {
                $closure_return_type = $closure_atomic_type->return_type ?: Type::getMixed();

                if ($closure_return_type->isVoid()) {
                    IssueBuffer::accepts(
                        new InvalidReturnType(
                            'No return type could be found in the closure passed to array_filter',
                            $code_location
                        ),
                        $statements_source->getSuppressedIssues()
                    );

                    return Type::getArray();
                }

                if (count($first_arg_value->stmts) === 1 && count($first_arg_value->params)) {
                    $first_param = $first_arg_value->params[0];
                    $stmt = $first_arg_value->stmts[0];

                    if ($first_param->variadic === false
                        && $first_param->var instanceof PhpParser\Node\Expr\Variable
                        && is_string($first_param->var->name)
                        && $stmt instanceof PhpParser\Node\Stmt\Return_
                        && $stmt->expr
                    ) {
                        $codebase = $statements_source->getCodebase();

                        AssertionFinder::scrapeAssertions(
                            $stmt->expr,
                            null,
                            $statements_source,
                            $codebase
                        );

                        $assertions = isset($stmt->expr->assertions) ? $stmt->expr->assertions : null;

                        if (isset($assertions['$' . $first_param->var->name])) {
                            $changed_var_ids = [];

                            assert($statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer);

                            $reconciled_types = Reconciler::reconcileKeyedTypes(
                                ['$inner_type' => $assertions['$' . $first_param->var->name]],
                                ['$inner_type' => $inner_type],
                                $changed_var_ids,
                                ['$inner_type' => true],
                                $statements_source,
                                [],
                                false,
                                new CodeLocation($statements_source, $stmt)
                            );

                            if (isset($reconciled_types['$inner_type'])) {
                                $inner_type = $reconciled_types['$inner_type'];
                            }
                        }
                    }
                }
            }

            return new Type\Union([
                new Type\Atomic\TArray([
                    $key_type,
                    $inner_type,
                ]),
            ]);
        }

        if (!$inner_type->getTypes()) {
            return Type::getEmptyArray();
        }

        return new Type\Union([
            new Type\Atomic\TArray([
                $key_type,
                $inner_type,
            ]),
        ]);
    }
}
