<?php

namespace Psalm\Internal\Provider;

use Closure;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\EventHandler\Event\PropertyVisibilityProviderEvent;
use Psalm\Plugin\EventHandler\PropertyVisibilityProviderInterface;
use Psalm\StatementsSource;

use function strtolower;

/**
 * @internal
 */
class PropertyVisibilityProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(PropertyVisibilityProviderEvent): ?bool>
     * >
     */
    private static array $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param class-string<PropertyVisibilityProviderInterface> $class
     */
    public function registerClass(string $class): void
    {
        $callable = Closure::fromCallable([$class, 'isPropertyVisible']);

        foreach ($class::getClassLikeNames() as $fq_classlike_name) {
            $this->registerClosure($fq_classlike_name, $callable);
        }
    }

    /**
     * @param Closure(PropertyVisibilityProviderEvent): ?bool $c
     */
    public function registerClosure(string $fq_classlike_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    public function has(string $fq_classlike_name): bool
    {
        return isset(self::$handlers[strtolower($fq_classlike_name)]);
    }

    public function isPropertyVisible(
        StatementsSource $source,
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        Context $context,
        CodeLocation $code_location
    ): ?bool {
        foreach (self::$handlers[strtolower($fq_classlike_name)] ?? [] as $property_handler) {
            $event = new PropertyVisibilityProviderEvent(
                $source,
                $fq_classlike_name,
                $property_name,
                $read_mode,
                $context,
                $code_location,
            );
            $property_visible = $property_handler($event);

            if ($property_visible !== null) {
                return $property_visible;
            }
        }

        return null;
    }
}
