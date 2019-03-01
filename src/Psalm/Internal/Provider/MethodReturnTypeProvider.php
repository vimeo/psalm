<?php

namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\StatementsSource;
use Psalm\Plugin\Hook\MethodReturnTypeProviderInterface;

class MethodReturnTypeProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     StatementsSource,
     *     string,
     *     string,
     *     array<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation
     *   ) : ?Type\Union>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param  class-string<MethodReturnTypeProviderInterface> $class
     * @psalm-suppress PossiblyUnusedParam
     * @return void
     */
    public function registerClass(string $class)
    {
        if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
            /** @psalm-suppress UndefinedMethod */
            $callable = \Closure::fromCallable([$class, 'getMethodReturnType']);
        } else {
            $callable = (new \ReflectionClass($class))->getMethod('getMethodReturnType')->getClosure(new $class);

            if (!$callable) {
                throw new \UnexpectedValueException('Callable must not be null');
            }
        }

        foreach ($class::getClassLikeNames() as $fq_classlike_name) {
            /** @psalm-suppress MixedTypeCoercion */
            $this->registerClosure($fq_classlike_name, $callable);
        }
    }

    /**
     * @param  \Closure(
     *     StatementsSource,
     *     string,
     *     string,
     *     array<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation
     *   ) : ?Type\Union $c
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
     * @return  ?Type\Union
     */
    public function getReturnType(
        StatementsSource $statements_source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) {
        foreach (self::$handlers[strtolower($fq_classlike_name)] as $class_handler) {
            $result = $class_handler(
                $statements_source,
                $fq_classlike_name,
                $method_name_lowercase,
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
