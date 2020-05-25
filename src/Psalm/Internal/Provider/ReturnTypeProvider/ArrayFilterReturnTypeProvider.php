<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use function array_map;
use function assert;
use function count;
use function is_string;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Issue\InvalidReturnType;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Reconciler;

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
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $array_arg = isset($call_args[0]->value) ? $call_args[0]->value : null;

        $first_arg_array = $array_arg
            && ($first_arg_type = $statements_source->node_data->getType($array_arg))
            && $first_arg_type->hasType('array')
            && ($array_atomic_type = $first_arg_type->getAtomicTypes()['array'])
            && ($array_atomic_type instanceof Type\Atomic\TArray
                || $array_atomic_type instanceof Type\Atomic\ObjectLike
                || $array_atomic_type instanceof Type\Atomic\TList)
            ? $array_atomic_type
            : null;

        if (!$first_arg_array) {
            return Type::getArray();
        }

        if ($first_arg_array instanceof Type\Atomic\TArray) {
            $inner_type = $first_arg_array->type_params[1];
            $key_type = clone $first_arg_array->type_params[0];
        } elseif ($first_arg_array instanceof Type\Atomic\TList) {
            $inner_type = $first_arg_array->type_param;
            $key_type = Type::getInt();
        } else {
            $inner_type = $first_arg_array->getGenericValueType();
            $key_type = $first_arg_array->getGenericKeyType();
        }

        if (!isset($call_args[1])) {
            $inner_type = \Psalm\Internal\Type\AssertionReconciler::reconcile(
                '!falsy',
                clone $inner_type,
                '',
                $statements_source,
                $context->inside_loop,
                [],
                null,
                $statements_source->getSuppressedIssues()
            );

            if ($first_arg_array instanceof Type\Atomic\ObjectLike
                && $first_arg_array->is_list
                && $key_type->isSingleIntLiteral()
                && $key_type->getSingleIntLiteral()->value === 0
            ) {
                return new Type\Union([
                    new Type\Atomic\TList(
                        $inner_type
                    ),
                ]);
            }

            if ($key_type->getLiteralStrings()) {
                $key_type->addType(new Type\Atomic\TString);
            }

            if ($key_type->getLiteralInts()) {
                $key_type->addType(new Type\Atomic\TInt);
            }
        } elseif (!isset($call_args[2])) {
            $function_call_arg = $call_args[1];

            $second_arg_value = $function_call_arg->value;

            if ($second_arg_value instanceof PhpParser\Node\Scalar\String_
                && InternalCallMapHandler::inCallMap($second_arg_value->value)
            ) {
                $callables = InternalCallMapHandler::getCallablesFromCallMap($second_arg_value->value);

                if ($callables) {
                    $callable = clone $callables[0];

                    if ($callable->params !== null && $callable->return_type) {
                        $second_arg_value = new PhpParser\Node\Expr\Closure([
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
                                            $second_arg_value->value
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
                                ),
                            ],
                        ]);

                        $closure_atomic_type = new Type\Atomic\TFn(
                            'Closure',
                            $callable->params,
                            $callable->return_type
                        );

                        $statements_source->node_data->setType(
                            $second_arg_value,
                            new Type\Union([$closure_atomic_type])
                        );
                    }
                }
            }

            if (($second_arg_value instanceof PhpParser\Node\Expr\Closure
                    || $second_arg_value instanceof PhpParser\Node\Expr\ArrowFunction)
                && ($second_arg_type = $statements_source->node_data->getType($second_arg_value))
                && ($closure_types = $second_arg_type->getClosureTypes())
            ) {
                $closure_atomic_type = \reset($closure_types);
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

                if (count($second_arg_value->getStmts()) === 1 && count($second_arg_value->params)) {
                    $first_param = $second_arg_value->params[0];
                    $stmt = $second_arg_value->getStmts()[0];

                    if ($first_param->variadic === false
                        && $first_param->var instanceof PhpParser\Node\Expr\Variable
                        && is_string($first_param->var->name)
                        && $stmt instanceof PhpParser\Node\Stmt\Return_
                        && $stmt->expr
                    ) {
                        $codebase = $statements_source->getCodebase();

                        $assertions = AssertionFinder::scrapeAssertions(
                            $stmt->expr,
                            null,
                            $statements_source,
                            $codebase
                        );

                        if (isset($assertions['$' . $first_param->var->name])) {
                            $changed_var_ids = [];

                            $assertions = ['$inner_type' => $assertions['$' . $first_param->var->name]];

                            $reconciled_types = Reconciler::reconcileKeyedTypes(
                                $assertions,
                                $assertions,
                                ['$inner_type' => $inner_type],
                                $changed_var_ids,
                                ['$inner_type' => true],
                                $statements_source,
                                $statements_source->getTemplateTypeMap() ?: [],
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

        if (!$inner_type->getAtomicTypes()) {
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
