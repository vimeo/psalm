<?php

namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\StatementsSource;
use Psalm\Plugin\Hook\MethodExistenceProviderInterface;

class MethodExistenceProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     string,
     *     string,
     *     ?CodeLocation
     *   ) : ?bool>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param  class-string<MethodExistenceProviderInterface> $class
     * @psalm-suppress PossiblyUnusedParam
     * @return void
     */
    public function registerClass(string $class)
    {
        if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
            /** @psalm-suppress UndefinedMethod */
            $callable = \Closure::fromCallable([$class, 'doesFunctionExist']);
        } else {
            $callable = (new \ReflectionClass($class))->getMethod('doesFunctionExist')->getClosure(new $class);
        }

        foreach ($class::getClassLikeNames() as $fq_classlike_name) {
            self::$handlers[strtolower($fq_classlike_name)][] = $callable;
        }
    }

    /**
     * /**
     * @param \Closure(
     *     string,
     *     string,
     *     ?CodeLocation
     *   ) : ?bool $c
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
     * @param  array<PhpParser\Node\Arg>  $call_args
     * @return ?bool
     */
    public function doesMethodExist(
        string $fq_classlike_name,
        string $method_name,
        CodeLocation $code_location = null
    ) {
        foreach (self::$handlers[strtolower($fq_classlike_name)] as $method_handler) {
            $method_exists = $method_handler(
                $fq_classlike_name,
                $method_name,
                $code_location
            );

            if ($method_exists !== null) {
                return $method_exists;
            }
        }

        return null;
    }
}
