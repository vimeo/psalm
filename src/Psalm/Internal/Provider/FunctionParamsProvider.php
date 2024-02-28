<?php

namespace Psalm\Internal\Provider;

use Closure;
use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Provider\ParamsProvider\ArrayFilterParamsProvider;
use Psalm\Internal\Provider\ParamsProvider\ArrayMultisortParamsProvider;
use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeParameter;

use function strtolower;

/**
 * @internal
 */
final class FunctionParamsProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(FunctionParamsProviderEvent): ?array<int, FunctionLikeParameter>>
     * >
     */
    private static array $handlers = [];

    public function __construct()
    {
        self::$handlers = [];

        $this->registerClass(ArrayFilterParamsProvider::class);
        $this->registerClass(ArrayMultisortParamsProvider::class);
    }

    /**
     * @param class-string<FunctionParamsProviderInterface> $class
     */
    public function registerClass(string $class): void
    {
        $callable = Closure::fromCallable([$class, 'getFunctionParams']);

        foreach ($class::getFunctionIds() as $function_id) {
            $this->registerClosure($function_id, $callable);
        }
    }

    /**
     * @param Closure(FunctionParamsProviderEvent): ?array<int, FunctionLikeParameter> $c
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
     * @param list<Arg> $call_args
     * @return  ?array<int, FunctionLikeParameter>
     */
    public function getFunctionParams(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ): ?array {
        foreach (self::$handlers[strtolower($function_id)] ?? [] as $class_handler) {
            $event = new FunctionParamsProviderEvent(
                $statements_source,
                $function_id,
                $call_args,
                $context,
                $code_location,
            );
            $result = $class_handler($event);

            if ($result) {
                return $result;
            }
        }

        return null;
    }
}
