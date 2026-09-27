<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Closure;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Interner;
use Psalm\Plugin\EventHandler\Event\MethodVisibilityProviderEvent;
use Psalm\Plugin\EventHandler\MethodVisibilityProviderInterface;
use Psalm\StatementsSource;

use function is_subclass_of;

/**
 * @internal
 */
final class MethodVisibilityProvider
{
    /**
     * @var array<
     *   int,
     *   array<Closure(MethodVisibilityProviderEvent): ?bool>
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
     * @param class-string<LegacyMethodVisibilityProviderInterface>
     *     |class-string<MethodVisibilityProviderInterface> $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, MethodVisibilityProviderInterface::class, true)) {
            $callable = $class::isMethodVisible(...);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerClosure($fq_classlike_name, $callable);
            }
        }
    }

    /**
     * @param Closure(MethodVisibilityProviderEvent): ?bool $c
     * @psalm-external-mutation-free
     */
    public function registerClosure(int $fq_classlike_name, Closure $c): void
    {
        self::$handlers[Interner::lower($fq_classlike_name)][] = $c;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function has(int $fq_classlike_name): bool
    {
        return isset(self::$handlers[Interner::lower($fq_classlike_name)]);
    }

    public function isMethodVisible(
        StatementsSource $source,
        int $fq_classlike_name,
        int $method_name,
        Context $context,
        ?CodeLocation $code_location = null,
    ): ?bool {
        foreach (self::$handlers[Interner::lower($fq_classlike_name)] ?? [] as $method_handler) {
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
