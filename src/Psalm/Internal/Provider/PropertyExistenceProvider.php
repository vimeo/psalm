<?php

namespace Psalm\Internal\Provider;

use Closure;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\EventHandler\Event\PropertyExistenceProviderEvent;
use Psalm\Plugin\EventHandler\PropertyExistenceProviderInterface;
use Psalm\StatementsSource;

use function is_subclass_of;
use function strtolower;

/**
 * @internal
 */
final class PropertyExistenceProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(PropertyExistenceProviderEvent): ?bool>
     * >
     */
    private static array $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param class-string<LegacyPropertyExistenceProviderInterface>
     *     |class-string<PropertyExistenceProviderInterface> $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, PropertyExistenceProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'doesPropertyExist']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerClosure($fq_classlike_name, $callable);
            }
        }
    }

    /**
     * @param Closure(PropertyExistenceProviderEvent): ?bool $c
     */
    public function registerClosure(string $fq_classlike_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    public function has(string $fq_classlike_name): bool
    {
        return isset(self::$handlers[strtolower($fq_classlike_name)]);
    }

    public function doesPropertyExist(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        ?StatementsSource $source = null,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ): ?bool {
        foreach (self::$handlers[strtolower($fq_classlike_name)] ?? [] as $property_handler) {
            $event = new PropertyExistenceProviderEvent(
                $fq_classlike_name,
                $property_name,
                $read_mode,
                $source,
                $context,
                $code_location,
            );
            $property_exists = $property_handler($event);

            if ($property_exists !== null) {
                return $property_exists;
            }
        }

        return null;
    }
}
