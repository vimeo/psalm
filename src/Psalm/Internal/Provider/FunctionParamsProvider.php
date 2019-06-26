<?php

namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\StatementsSource;
use Psalm\Plugin\Hook\FunctionParamsProviderInterface;
use function version_compare;
use const PHP_VERSION;
use function strtolower;

class FunctionParamsProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     StatementsSource,
     *     string,
     *     array<PhpParser\Node\Arg>,
     *     ?Context=,
     *     ?CodeLocation=
     *   ) : ?array<int, \Psalm\Storage\FunctionLikeParameter>>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param  class-string<FunctionParamsProviderInterface> $class
     * @psalm-suppress PossiblyUnusedParam
     * @return void
     */
    public function registerClass(string $class)
    {
        if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
            /**
             * @psalm-suppress UndefinedMethod
             * @var \Closure
             */
            $callable = \Closure::fromCallable([$class, 'getFunctionParams']);
        } else {
            $callable = (new \ReflectionClass($class))->getMethod('getFunctionParams')->getClosure(new $class);

            if (!$callable) {
                throw new \UnexpectedValueException('Callable must not be null');
            }
        }

        foreach ($class::getFunctionIds() as $function_id) {
            /** @psalm-suppress MixedTypeCoercion */
            $this->registerClosure($function_id, $callable);
        }
    }

    /**
     * @param  \Closure(
     *     StatementsSource,
     *     string,
     *     array<PhpParser\Node\Arg>,
     *     ?Context=,
     *     ?CodeLocation=
     *   ) : ?array<int, \Psalm\Storage\FunctionLikeParameter> $c
     *
     * @return void
     */
    public function registerClosure(string $fq_classlike_name, \Closure $c)
    {
        self::$handlers[strtolower($fq_classlike_name)][] = $c;
    }

    public function has(string $fq_classlike_name) : bool
    {
        return isset(self::$handlers[strtolower($fq_classlike_name)]);
    }

    /**
     * @param array<PhpParser\Node\Arg>  $call_args
     * @return  ?array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public function getFunctionParams(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context = null,
        CodeLocation $code_location = null
    ) {
        foreach (self::$handlers[strtolower($function_id)] as $class_handler) {
            $result = $class_handler(
                $statements_source,
                $function_id,
                $call_args,
                $context,
                $code_location
            );

            if ($result) {
                return $result;
            }
        }

        return null;
    }
}
