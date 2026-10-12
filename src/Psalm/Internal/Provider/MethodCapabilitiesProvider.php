<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Closure;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\EventHandler\Event\MethodCapabilitiesProviderEvent;
use Psalm\Plugin\EventHandler\MethodCapabilitiesProviderInterface;
use Psalm\StatementsSource;

use function is_subclass_of;
use function strtolower;

/**
 * @internal
 */
final class MethodCapabilitiesProvider
{
    /**
     * @var array<lowercase-string, array<Closure(MethodCapabilitiesProviderEvent): ?int>>
     */
    private static array $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, MethodCapabilitiesProviderInterface::class, true)) {
            $callable = $class::getMethodCapabilities(...);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerClosure($fq_classlike_name, $callable);
            }
        }
    }

    /**
     * @param Closure(MethodCapabilitiesProviderEvent): ?int $c
     * @psalm-capabilities read-globals|write-globals
     */
    public function registerClosure(string $fq_classlike_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    /**
     * @psalm-capabilities read-globals
     */
    public function has(string $fq_classlike_name): bool
    {
        return isset(self::$handlers[strtolower($fq_classlike_name)]);
    }

    public function getMethodCapabilities(
        StatementsSource $statements_source,
        string $fq_classlike_name,
        string $method_name,
        string $called_fq_classlike_name,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        CodeLocation $code_location,
    ): ?int {
        foreach (self::$handlers[strtolower($fq_classlike_name)] ?? [] as $class_handler) {
            $result = $class_handler(new MethodCapabilitiesProviderEvent(
                $statements_source,
                $fq_classlike_name,
                strtolower($method_name),
                $called_fq_classlike_name,
                $stmt,
                $context,
                $code_location,
            ));

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}
