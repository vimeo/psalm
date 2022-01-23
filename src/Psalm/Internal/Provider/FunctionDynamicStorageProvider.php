<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Closure;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionDynamicStorageProviderEvent;
use Psalm\Plugin\EventHandler\FunctionDynamicStorageProviderInterface;
use Psalm\Storage\FunctionStorage;

use function strtolower;

/**
 * @internal
 */
final class FunctionDynamicStorageProvider
{
    /** @var array<lowercase-string, array<Closure(FunctionDynamicStorageProviderEvent): ?FunctionStorage>> */
    private static $handlers = [];

    /** @var array<lowercase-string, FunctionStorage> */
    private static $dynamic_storages = [];

    /**
     * @param class-string<FunctionDynamicStorageProviderInterface> $class
     */
    public function registerClass(string $class): void
    {
        $callable = Closure::fromCallable([$class, 'getFunctionStorage']);

        foreach ($class::getFunctionIds() as $function_id) {
            $this->registerClosure($function_id, $callable);
        }
    }

    /**
     * @param Closure(FunctionDynamicStorageProviderEvent): ?FunctionStorage $c
     */
    public function registerClosure(string $fq_function_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_function_name)][] = $c;
    }

    public function has(string $fq_function_name): bool
    {
        return isset(self::$handlers[strtolower($fq_function_name)]);
    }

    /**
     * @param list<PhpParser\Node\Arg> $call_args
     */
    public function getFunctionSignature(
        PhpParser\Node\Expr\FuncCall $stmt,
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ): ?FunctionStorage {
        $dynamic_storage_id = strtolower($statements_analyzer->getFilePath())
            . ':' . $stmt->getLine()
            . ':' . (int)$stmt->getAttribute('startFilePos')
            . ':dynamic-storage'
            . ':-:' . strtolower($function_id);

        if (isset(self::$dynamic_storages[$dynamic_storage_id])) {
            return self::$dynamic_storages[$dynamic_storage_id];
        }

        foreach (self::$handlers[strtolower($function_id)] ?? [] as $class_handler) {
            $event = new FunctionDynamicStorageProviderEvent(
                $statements_analyzer,
                $function_id,
                $call_args,
                $context,
                $code_location
            );

            $result = $class_handler($event);

            if ($result) {
                self::$dynamic_storages[$dynamic_storage_id] = $result;
                return $result;
            }
        }

        return null;
    }
}
