<?php

namespace Psalm\Internal\Provider;

use Closure;
use Psalm\CodeLocation;
use Psalm\Plugin\EventHandler\Event\MethodExistenceProviderEvent;
use Psalm\Plugin\EventHandler\MethodExistenceProviderInterface;
use Psalm\StatementsSource;

use function strtolower;

/**
 * @internal
 */
final class MethodExistenceProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(MethodExistenceProviderEvent): ?bool>
     * >
     */
    private static array $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param class-string<MethodExistenceProviderInterface> $class
     */
    public function registerClass(string $class): void
    {
        $callable = Closure::fromCallable([$class, 'doesMethodExist']);

        foreach ($class::getClassLikeNames() as $fq_classlike_name) {
            $this->registerClosure($fq_classlike_name, $callable);
        }
    }

    /**
     * @param Closure(MethodExistenceProviderEvent): ?bool $c
     */
    public function registerClosure(string $fq_classlike_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    public function has(string $fq_classlike_name): bool
    {
        return isset(self::$handlers[strtolower($fq_classlike_name)]);
    }

    public function doesMethodExist(
        string $fq_classlike_name,
        string $method_name_lowercase,
        ?StatementsSource $source = null,
        ?CodeLocation $code_location = null
    ): ?bool {
        foreach (self::$handlers[strtolower($fq_classlike_name)] ?? [] as $method_handler) {
            $event = new MethodExistenceProviderEvent(
                $fq_classlike_name,
                $method_name_lowercase,
                $source,
                $code_location,
            );
            $method_exists = $method_handler($event);

            if ($method_exists !== null) {
                return $method_exists;
            }
        }

        return null;
    }
}
