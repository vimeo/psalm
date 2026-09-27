<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Closure;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Provider\ReturnTypeProvider\ClosureFromCallableReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\DateTimeModifyReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\DomNodeAppendChild;
use Psalm\Internal\Provider\ReturnTypeProvider\ImagickPixelColorReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\PdoStatementReturnTypeProvider;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type\Union;

use function is_subclass_of;

/**
 * @internal
 */
final class MethodReturnTypeProvider
{
    /**
     * @var array<
     *   int,
     *   array<Closure(MethodReturnTypeProviderEvent): ?Union>
     * >
     */
    private static array $handlers = [];

    public function __construct()
    {
        self::$handlers = [];

        $this->registerClass(DomNodeAppendChild::class);
        $this->registerClass(ImagickPixelColorReturnTypeProvider::class);
        $this->registerClass(PdoStatementReturnTypeProvider::class);
        $this->registerClass(ClosureFromCallableReturnTypeProvider::class);
        $this->registerClass(DateTimeModifyReturnTypeProvider::class);
    }

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, MethodReturnTypeProviderInterface::class, true)) {
            $callable = $class::getMethodReturnType(...);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerClosure($fq_classlike_name, $callable);
            }
        }
    }

    /**
     * @param Closure(MethodReturnTypeProviderEvent): ?Union $c
     * @psalm-external-mutation-free
     */
    public function registerClosure(int $fq_classlike_name, Closure $c): void
    {
        self::$handlers[$fq_classlike_name][] = $c;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function has(int $fq_classlike_name): bool
    {
        return isset(self::$handlers[$fq_classlike_name]);
    }

    /**
     * @param non-empty-list<Union>|null $template_type_parameters
     */
    public function getReturnType(
        StatementsSource $statements_source,
        int $fq_classlike_name,
        int $method_name,
        PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $stmt,
        Context $context,
        CodeLocation $code_location,
        ?array $template_type_parameters = null,
        ?int $called_fq_classlike_name = null,
        ?int $called_method_name = null,
    ): ?Union {
        foreach (self::$handlers[$fq_classlike_name] ?? [] as $class_handler) {
            $event = new MethodReturnTypeProviderEvent(
                $statements_source,
                $fq_classlike_name,
                $method_name,
                $stmt,
                $context,
                $code_location,
                $template_type_parameters,
                $called_fq_classlike_name,
                $called_method_name ?? null,
            );
            $result = $class_handler($event);

            if ($result) {
                return $result;
            }
        }

        return null;
    }
}
