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
    public static function remapLowerBounds(
        StatementsAnalyzer $statements_analyzer,
        TemplateResult $inferred_template_result,
        HighOrderFunctionArgInfo $input_function,
        Union $container_function_type
    ): TemplateResult {
        $container_type = TemplateInferredTypeReplacer::replace(
            $container_function_type,
            $inferred_template_result,
            $statements_analyzer->getCodebase(),
        );

        $input_function_type = $input_function->getFunctionType();
        $input_function_template_result = $input_function->getTemplates();

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

    public static function getCallableArgInfo(
        Context $context,
        PhpParser\Node\Expr $expr,
        StatementsAnalyzer $statements_analyzer
    ): ?HighOrderFunctionArgInfo {
        $codebase = $statements_analyzer->getCodebase();

        try {
            if ($expr instanceof PhpParser\Node\Expr\FuncCall) {
                $function_id = strtolower((string) $expr->name->getAttribute('resolvedName'));

                if (empty($function_id)) {
                    return null;
                }

                $dynamic_storage = !$expr->isFirstClassCallable()
                    ? $codebase->functions->dynamic_storage_provider->getFunctionStorage(
                        $expr,
                        $statements_analyzer,
                        $function_id,
                        $context,
                        new CodeLocation($statements_analyzer, $expr),
                    )
                    : null;

                return new HighOrderFunctionArgInfo(
                    $expr->isFirstClassCallable(),
                    $dynamic_storage ?? $codebase->functions->getStorage($statements_analyzer, $function_id),
                );
            }

            if ($expr instanceof PhpParser\Node\Expr\MethodCall &&
                $expr->var instanceof PhpParser\Node\Expr\Variable &&
                $expr->name instanceof PhpParser\Node\Identifier &&
                is_string($expr->var->name) &&
                isset($context->vars_in_scope['$' . $expr->var->name])
            ) {
                $lhs_type = $context->vars_in_scope['$' . $expr->var->name]->getSingleAtomic();

                if (!$lhs_type instanceof Type\Atomic\TNamedObject) {
                    return null;
                }

                $method_id = new MethodIdentifier(
                    $lhs_type->value,
                    strtolower((string)$expr->name),
                );

                return new HighOrderFunctionArgInfo(
                    $expr->isFirstClassCallable(),
                    $codebase->methods->getStorage($method_id),
                );
            }

            if ($expr instanceof PhpParser\Node\Expr\StaticCall &&
                $expr->name instanceof PhpParser\Node\Identifier
            ) {
                $method_id = new MethodIdentifier(
                    (string)$expr->class->getAttribute('resolvedName'),
                    strtolower($expr->name->name),
                );

                return new HighOrderFunctionArgInfo(
                    $expr->isFirstClassCallable(),
                    $codebase->methods->getStorage($method_id),
                );
            }

            if ($expr instanceof PhpParser\Node\Expr\ConstFetch) {
                $constant = $context->constants[$expr->name->toString()] ?? null;

                $literal = $constant && $constant->isSingle()
                    ? $constant->getSingleAtomic()
                    : null;

                if (!$literal instanceof Type\Atomic\TLiteralString || empty($literal->value)) {
                    return null;
                }

                return new HighOrderFunctionArgInfo(
                    true,
                    strpos($literal->value, '::') !== false
                        ? $codebase->methods->getStorage(MethodIdentifier::wrap($literal->value))
                        : $codebase->functions->getStorage($statements_analyzer, strtolower($literal->value)),
                );
            }

            if ($expr instanceof PhpParser\Node\Expr\ClassConstFetch &&
                $expr->name instanceof PhpParser\Node\Identifier
            ) {
                $method_id = new MethodIdentifier(
                    (string)$expr->class->getAttribute('resolvedName'),
                    strtolower($expr->name->name),
                );

                return new HighOrderFunctionArgInfo(
                    true,
                    $codebase->methods->getStorage($method_id),
                );
            }

            if ($expr instanceof PhpParser\Node\Expr\New_ &&
                $expr->class instanceof PhpParser\Node\Name
            ) {
                $class_storage = $codebase->classlikes
                    ->getStorageFor((string) $expr->class->getAttribute('resolvedName'));

                $invoke_storage = $class_storage && isset($class_storage->methods['__invoke'])
                    ? $class_storage->methods['__invoke']
                    : null;

                if (!$invoke_storage) {
                    return null;
                }

                return new HighOrderFunctionArgInfo(false, $invoke_storage, $class_storage);
            }
        } catch (UnexpectedValueException $e) {
            return null;
        }

        return null;
    }
}
