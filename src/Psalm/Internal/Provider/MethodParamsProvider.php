<?php

namespace Psalm\Internal\Provider;

use Closure;
use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Provider\ReturnTypeProvider\PdoStatementSetFetchMode;
use Psalm\Plugin\EventHandler\Event\MethodParamsProviderEvent;
use Psalm\Plugin\EventHandler\MethodParamsProviderInterface;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeParameter;

use function array_values;
use function is_subclass_of;
use function strtolower;

/**
 * @internal
 */
final class MethodParamsProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(MethodParamsProviderEvent): ?array<int, FunctionLikeParameter>>
     * >
     */
    private static array $handlers = [];

    public function __construct()
    {
        self::$handlers = [];

        $this->registerClass(PdoStatementSetFetchMode::class);
    }

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, MethodParamsProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'getMethodParams']);

            foreach ($class::getClassLikeNames() as $fq_classlike_name) {
                $this->registerClosure($fq_classlike_name, $callable);
            }
        }
    }

    /**
     * @param Closure(MethodParamsProviderEvent): ?array<int, FunctionLikeParameter> $c
     */
    public function registerClosure(string $fq_classlike_name, Closure $c): void
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    public function has(string $fq_classlike_name): bool
    {
        return isset(self::$handlers[strtolower($fq_classlike_name)]);
    }

    /**
     * @param ?list<Arg>  $call_args
     * @return  ?list<FunctionLikeParameter>
     */
    public function getMethodParams(
        string $fq_classlike_name,
        string $method_name_lowercase,
        ?array $call_args = null,
        ?StatementsSource $statements_source = null,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ): ?array {
        foreach (self::$handlers[strtolower($fq_classlike_name)] ?? [] as $class_handler) {
            $event = new MethodParamsProviderEvent(
                $fq_classlike_name,
                $method_name_lowercase,
                $call_args,
                $statements_source,
                $context,
                $code_location,
            );
            $result = $class_handler($event);

            if ($result !== null) {
                return array_values($result);
            }
        }

        return null;
    }
}
