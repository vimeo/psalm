<?php

namespace Psalm\Internal\Provider;

use Closure;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\EventHandler\Event\MethodVisibilityProviderEvent;
use Psalm\Plugin\EventHandler\MethodVisibilityProviderInterface;
use Psalm\StatementsSource;

use function is_subclass_of;
use function strtolower;

/**
 * @internal
 */
final class MethodVisibilityProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(MethodVisibilityProviderEvent): ?bool>
     * >
     */
    private static array $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param class-string<LegacyMethodVisibilityProviderInterface>
     *     |class-string<MethodVisibilityProviderInterface> $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, MethodVisibilityProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'isMethodVisible']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerClosure($fq_classlike_name, $callable);
            }
        }
    }

    /**
     * @param Closure(MethodVisibilityProviderEvent): ?bool $c
     */
    public function registerClosure(string $fq_classlike_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    public function has(string $fq_classlike_name): bool
    {
        return isset(self::$handlers[strtolower($fq_classlike_name)]);
    }

    public function isMethodVisible(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name,
        Context $context,
        ?CodeLocation $code_location = null
    ): ?bool {
        foreach (self::$handlers[strtolower($fq_classlike_name)] ?? [] as $method_handler) {
            $event = new MethodVisibilityProviderEvent(
                $source,
                $fq_classlike_name,
                $method_name,
                $context,
                $code_location,
            );
            $method_visible = $method_handler($event);

            if ($method_visible !== null) {
                return $method_visible;
            }
        }

        return null;
    }
}
