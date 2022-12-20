<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Closure;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\ArgTypeInferer;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\DynamicTemplateProvider;
use Psalm\Plugin\EventHandler\DynamicFunctionStorageProviderInterface;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;
use Psalm\Storage\FunctionStorage;

use function strtolower;

/**
 * For each function call analysis will be created individual FunctionStorage in plugin hook.
 * If it is created be aware, it shadows the FunctionStorage Psalm may generate during the scanning phase.
 *
 * @internal
 */
final class DynamicFunctionStorageProvider
{
    /** @var array<lowercase-string, array<Closure(DynamicFunctionStorageProviderEvent): ?DynamicFunctionStorage>> */
    private static array $handlers = [];

    /** @var array<lowercase-string, ?FunctionStorage> */
    private static array $dynamic_storages = [];

    /**
     * @param class-string<DynamicFunctionStorageProviderInterface> $class
     */
    public function registerClass(string $class): void
    {
        $callable = Closure::fromCallable([$class, 'getFunctionStorage']);

        foreach ($class::getFunctionIds() as $function_id) {
            $this->registerClosure($function_id, $callable);
        }
    }

    /**
     * @param Closure(DynamicFunctionStorageProviderEvent): ?DynamicFunctionStorage $c
     */
    public function registerClosure(string $fq_function_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_function_name)][] = $c;
    }

    public function has(string $fq_function_name): bool
    {
        return isset(self::$handlers[strtolower($fq_function_name)]);
    }

    public function getFunctionStorage(
        PhpParser\Node\Expr\FuncCall $stmt,
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        Context $context,
        CodeLocation $code_location
    ): ?FunctionStorage {
        if ($stmt->isFirstClassCallable()) {
            return null;
        }

        $dynamic_storage_id = strtolower($statements_analyzer->getFilePath())
            . ':' . $stmt->getLine()
            . ':' . (int)$stmt->getAttribute('startFilePos')
            . ':dynamic-storage'
            . ':-:' . strtolower($function_id);

        if (isset(self::$dynamic_storages[$dynamic_storage_id])) {
            return self::$dynamic_storages[$dynamic_storage_id];
        }

        foreach (self::$handlers[strtolower($function_id)] ?? [] as $class_handler) {
            $event = new DynamicFunctionStorageProviderEvent(
                new ArgTypeInferer($context, $statements_analyzer),
                new DynamicTemplateProvider('fn-' . strtolower($function_id)),
                $statements_analyzer,
                $function_id,
                $stmt,
                $context,
                $code_location,
            );

            $result = $class_handler($event);

            return self::$dynamic_storages[$dynamic_storage_id] = $result
                ? $result->toFunctionStorage($function_id)
                : null;
        }

        return null;
    }
}
