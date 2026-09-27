<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Closure;
use Psalm\Plugin\EventHandler\Event\FunctionExistenceProviderEvent;
use Psalm\Plugin\EventHandler\FunctionExistenceProviderInterface;
use Psalm\StatementsSource;

use function is_subclass_of;

/**
 * @internal
 */
final class FunctionExistenceProvider
{
    /**
     * @var array<
     *   int,
     *   array<Closure(FunctionExistenceProviderEvent): ?bool>
     * >
     */
    private static array $handlers = [];

    /**
     * @psalm-mutation-free
     */
    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, FunctionExistenceProviderInterface::class, true)) {
            $callable = $class::doesFunctionExist(...);

            foreach ($class::getFunctionIds() as $function_id) {
                $this->registerClosure($function_id, $callable);
            }
        }
    }

    /**
     * @param Closure(FunctionExistenceProviderEvent): ?bool $c
     * @psalm-external-mutation-free
     */
    public function registerClosure(int $function_id, Closure $c): void
    {
        self::$handlers[$function_id][] = $c;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function has(int $function_id): bool
    {
        return isset(self::$handlers[$function_id]);
    }

    public function doesFunctionExist(
        StatementsSource $statements_source,
        int $function_id,
    ): ?bool {
        foreach (self::$handlers[$function_id] ?? [] as $function_handler) {
            $event = new FunctionExistenceProviderEvent(
                $statements_source,
                $function_id,
            );
            $function_exists = $function_handler($event);

            if ($function_exists !== null) {
                return $function_exists;
            }
        }

        return null;
    }
}
