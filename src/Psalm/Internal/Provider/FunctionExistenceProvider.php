<?php

namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\StatementsSource;
use \Psalm\Plugin\Hook\FunctionExistenceProviderInterface;

class FunctionExistenceProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     StatementsSource,
     *     string
     *   ) : ?bool>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @param  class-string<FunctionExistenceProviderInterface> $class
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

        foreach ($class::getFunctionIds() as $function_id) {
            self::$handlers[$function_id][] = $callable;
        }
    }

    /**
     * /**
     * @param \Closure(
     *     StatementsSource,
     *     string
     *   ) : ?bool $c
     *
     * @return void
     */
    public function registerClosure(string $function_id, \Closure $c)
    {
        self::$handlers[$function_id][] = $c;
    }

    public function has(string $function_id) : bool
    {
        return isset(self::$handlers[strtolower($function_id)]);
    }

    /**
     * @param  array<PhpParser\Node\Arg>  $call_args
     * @return ?bool
     */
    public function doesFunctionExist(
        StatementsSource $statements_source,
        string $function_id
    ) {
        foreach (self::$handlers[strtolower($function_id)] as $function_handler) {
            $function_exists = $function_handler(
                $statements_source,
                $function_id
            );

            if ($function_exists !== null) {
                return $function_exists;
            }
        }

        return null;
    }
}
