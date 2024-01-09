<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Union;
use UnexpectedValueException;

use function is_string;
use function strpos;
use function strtolower;

/**
 * @internal
 */
final class HighOrderFunctionArgHandler
{
    /**
     * Compiles TemplateResult for high-order function
     * by previous template args ($inferred_template_result).
     *
     * It's need for proper template replacement:
     *
     * ```
     * * template T
     * * return Closure(T): T
     * function id(): Closure { ... }
     *
     * * template A
     * * template B
     * *
     * * param list<A> $_items
     * * param callable(A): B $_ab
     * * return list<B>
     * function map(array $items, callable $ab): array { ... }
     *
     * // list<int>
     * $numbers = [1, 2, 3];
     *
     * $result = map($numbers, id());
     * // $result is list<int> because template T of id() was inferred by previous arg.
     * ```
     */
    public static function remapLowerBounds(
        StatementsAnalyzer $statements_analyzer,
        TemplateResult $inferred_template_result,
        HighOrderFunctionArgInfo $input_function,
        Union $container_function_type
    ): TemplateResult {
        // Try to infer container callable by $inferred_template_result
        $container_type = TemplateInferredTypeReplacer::replace(
            $container_function_type,
            $inferred_template_result,
            $statements_analyzer->getCodebase(),
        );

        $input_function_type = $input_function->getFunctionType();
        $input_function_template_result = $input_function->getTemplates();

        // Traverse side by side 'container' params and 'input' params.
        // This maps 'input' templates to 'container' templates.
        //
        // Example:
        // 'input'     => Closure(C:Bar, D:Bar): array{C:Bar, D:Bar}
        // 'container' => Closure(int, string): array{int, string}
        //
        // $remapped_lower_bounds will be: [
        //     'C' => ['Bar' => [int]],
        //     'D' => ['Bar' => [string]]
        // ].
        foreach ($input_function_type->getAtomicTypes() as $input_atomic) {
            if (!$input_atomic instanceof TClosure && !$input_atomic instanceof TCallable) {
                continue;
            }

            foreach ($container_type->getAtomicTypes() as $container_atomic) {
                if (!$container_atomic instanceof TClosure && !$container_atomic instanceof TCallable) {
                    continue;
                }

                foreach ($input_atomic->params ?? [] as $offset => $input_param) {
                    if (!isset($container_atomic->params[$offset])) {
                        continue;
                    }

                    TemplateStandinTypeReplacer::fillTemplateResult(
                        $input_param->type ?? Type::getMixed(),
                        $input_function_template_result,
                        $statements_analyzer->getCodebase(),
                        $statements_analyzer,
                        $container_atomic->params[$offset]->type,
                    );
                }
            }
        }

        return $input_function_template_result;
    }

    public static function enhanceCallableArgType(
        Context $context,
        PhpParser\Node\Expr $arg_expr,
        StatementsAnalyzer $statements_analyzer,
        HighOrderFunctionArgInfo $high_order_callable_info,
        TemplateResult $high_order_template_result
    ): void {
        // Psalm can infer simple callable/closure.
        // But can't infer first-class-callable or high-order function.
        if ($high_order_callable_info->getType() === HighOrderFunctionArgInfo::TYPE_CALLABLE) {
            return;
        }

        $fully_inferred_callable_type = TemplateInferredTypeReplacer::replace(
            $high_order_callable_info->getFunctionType(),
            $high_order_template_result,
            $statements_analyzer->getCodebase(),
        );

        // Some templates may not have been replaced.
        // They expansion makes error message better.
        $expanded = TypeExpander::expandUnion(
            $statements_analyzer->getCodebase(),
            $fully_inferred_callable_type,
            $context->self,
            $context->self,
            $context->parent,
            true,
            true,
            false,
            false,
            true,
        );

        $statements_analyzer->node_data->setType($arg_expr, $expanded);
    }

    public static function getCallableArgInfo(
        Context $context,
        PhpParser\Node\Expr $input_arg_expr,
        StatementsAnalyzer $statements_analyzer,
        FunctionLikeParameter $container_param
    ): ?HighOrderFunctionArgInfo {
        if (!self::isSupported($container_param)) {
            return null;
        }

        $codebase = $statements_analyzer->getCodebase();

        try {
            if ($input_arg_expr instanceof PhpParser\Node\Expr\FuncCall) {
                $function_id = strtolower((string) $input_arg_expr->name->getAttribute('resolvedName'));

                if (empty($function_id)) {
                    return null;
                }

                $dynamic_storage = !$input_arg_expr->isFirstClassCallable()
                    ? $codebase->functions->dynamic_storage_provider->getFunctionStorage(
                        $input_arg_expr,
                        $statements_analyzer,
                        $function_id,
                        $context,
                        new CodeLocation($statements_analyzer, $input_arg_expr),
                    )
                    : null;

                return new HighOrderFunctionArgInfo(
                    $input_arg_expr->isFirstClassCallable()
                        ? HighOrderFunctionArgInfo::TYPE_FIRST_CLASS_CALLABLE
                        : HighOrderFunctionArgInfo::TYPE_CALLABLE,
                    $dynamic_storage ?? $codebase->functions->getStorage($statements_analyzer, $function_id),
                );
            }

            if ($input_arg_expr instanceof PhpParser\Node\Expr\MethodCall &&
                $input_arg_expr->var instanceof PhpParser\Node\Expr\Variable &&
                $input_arg_expr->name instanceof PhpParser\Node\Identifier &&
                is_string($input_arg_expr->var->name) &&
                isset($context->vars_in_scope['$' . $input_arg_expr->var->name])
            ) {
                $lhs_type = $context->vars_in_scope['$' . $input_arg_expr->var->name]->getSingleAtomic();

                if (!$lhs_type instanceof Type\Atomic\TNamedObject) {
                    return null;
                }

                $method_id = new MethodIdentifier(
                    $lhs_type->value,
                    strtolower((string)$input_arg_expr->name),
                );

                return new HighOrderFunctionArgInfo(
                    $input_arg_expr->isFirstClassCallable()
                        ? HighOrderFunctionArgInfo::TYPE_FIRST_CLASS_CALLABLE
                        : HighOrderFunctionArgInfo::TYPE_CALLABLE,
                    $codebase->methods->getStorage($method_id),
                );
            }

            if ($input_arg_expr instanceof PhpParser\Node\Expr\StaticCall &&
                $input_arg_expr->name instanceof PhpParser\Node\Identifier
            ) {
                $method_id = new MethodIdentifier(
                    (string)$input_arg_expr->class->getAttribute('resolvedName'),
                    strtolower($input_arg_expr->name->toString()),
                );

                return new HighOrderFunctionArgInfo(
                    $input_arg_expr->isFirstClassCallable()
                        ? HighOrderFunctionArgInfo::TYPE_FIRST_CLASS_CALLABLE
                        : HighOrderFunctionArgInfo::TYPE_CALLABLE,
                    $codebase->methods->getStorage($method_id),
                );
            }

            if ($input_arg_expr instanceof PhpParser\Node\Scalar\String_) {
                return self::fromLiteralString(Type::getString($input_arg_expr->value), $statements_analyzer);
            }

            if ($input_arg_expr instanceof PhpParser\Node\Expr\ConstFetch) {
                $constant = $context->constants[$input_arg_expr->name->toString()] ?? null;

                return null !== $constant
                    ? self::fromLiteralString($constant, $statements_analyzer)
                    : null;
            }

            if ($input_arg_expr instanceof PhpParser\Node\Expr\ClassConstFetch &&
                $input_arg_expr->name instanceof PhpParser\Node\Identifier
            ) {
                $storage = $codebase->classlikes
                    ->getStorageFor((string)$input_arg_expr->class->getAttribute('resolvedName'));

                $constant = null !== $storage
                    ? $storage->constants[$input_arg_expr->name->toString()] ?? null
                    : null;

                return null !== $constant && null !== $constant->type
                    ? self::fromLiteralString($constant->type, $statements_analyzer)
                    : null;
            }

            if ($input_arg_expr instanceof PhpParser\Node\Expr\New_ &&
                $input_arg_expr->class instanceof PhpParser\Node\Name
            ) {
                $class_storage = $codebase->classlikes
                    ->getStorageFor((string) $input_arg_expr->class->getAttribute('resolvedName'));

                $invoke_storage = $class_storage && isset($class_storage->methods['__invoke'])
                    ? $class_storage->methods['__invoke']
                    : null;

                if (!$invoke_storage) {
                    return null;
                }

                return new HighOrderFunctionArgInfo(
                    HighOrderFunctionArgInfo::TYPE_CLASS_CALLABLE,
                    $invoke_storage,
                    $class_storage,
                );
            }
        } catch (UnexpectedValueException $e) {
            return null;
        }

        return null;
    }

    private static function isSupported(FunctionLikeParameter $container_param): bool
    {
        if (!$container_param->type || !$container_param->type->hasCallableType()) {
            return false;
        }

        foreach ($container_param->type->getAtomicTypes() as $a) {
            // must check null explicitly, since no params (empty array) would not be handled correctly otherwise
            if (($a instanceof TClosure || $a instanceof TCallable) && $a->params === null) {
                return false;
            }

            if ($a instanceof Type\Atomic\TCallableArray ||
                $a instanceof Type\Atomic\TCallableString ||
                $a instanceof Type\Atomic\TCallableKeyedArray
            ) {
                return false;
            }
        }

        return true;
    }

    private static function fromLiteralString(
        Union $constant,
        StatementsAnalyzer $statements_analyzer
    ): ?HighOrderFunctionArgInfo {
        $literal = $constant->isSingle() ? $constant->getSingleAtomic() : null;

        if (!$literal instanceof Type\Atomic\TLiteralString || empty($literal->value)) {
            return null;
        }

        $codebase = $statements_analyzer->getCodebase();

        return new HighOrderFunctionArgInfo(
            HighOrderFunctionArgInfo::TYPE_STRING_CALLABLE,
            strpos($literal->value, '::') !== false
                ? $codebase->methods->getStorage(MethodIdentifier::wrap($literal->value))
                : $codebase->functions->getStorage($statements_analyzer, strtolower($literal->value)),
        );
    }
}
