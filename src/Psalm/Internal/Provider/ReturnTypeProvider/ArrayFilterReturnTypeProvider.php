<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Issue\InvalidReturnType;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
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
    public static function get(
        StatementsAnalyzer $statements_analyzer,
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

            if ($function_call_arg->value instanceof PhpParser\Node\Expr\Closure
                && isset($function_call_arg->value->inferredType)
                && ($closure_atomic_type = $function_call_arg->value->inferredType->getTypes()['Closure'])
                && $closure_atomic_type instanceof Type\Atomic\Fn
            ) {
                $closure_return_type = $closure_atomic_type->return_type ?: Type::getMixed();

                if ($closure_return_type->isVoid()) {
                    IssueBuffer::accepts(
                        new InvalidReturnType(
                            'No return type could be found in the closure passed to array_filter',
                            $code_location
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );

                    return Type::getArray();
                }

                if (count($function_call_arg->value->stmts) === 1 && count($function_call_arg->value->params)) {
                    $first_param = $function_call_arg->value->params[0];
                    $stmt = $function_call_arg->value->stmts[0];

                    if ($first_param->variadic === false
                        && $first_param->var instanceof PhpParser\Node\Expr\Variable
                        && is_string($first_param->var->name)
                        && $stmt instanceof PhpParser\Node\Stmt\Return_
                        && $stmt->expr
                    ) {
                        $codebase = $statements_analyzer->getCodebase();

                        AssertionFinder::scrapeAssertions($stmt->expr, null, $statements_analyzer, $codebase);

                        $assertions = isset($stmt->expr->assertions) ? $stmt->expr->assertions : null;

                        if (isset($assertions['$' . $first_param->var->name])) {
                            $changed_var_ids = [];

                            $reconciled_types = Reconciler::reconcileKeyedTypes(
                                ['$inner_type' => $assertions['$' . $first_param->var->name]],
                                ['$inner_type' => $inner_type],
                                $changed_var_ids,
                                ['$inner_type' => true],
                                $statements_analyzer,
                                [],
                                false,
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
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

        return new Type\Union([
            new Type\Atomic\TArray([
                $key_type,
                $inner_type,
            ]),
        ]);
    }
}
