<?php

namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface;

class FunctionReturnTypeProvider
{
    /**
     * @var array<
     *   string,
     *   \Closure(
     *     StatementsAnalyzer,
     *     string,
     *     array<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation
     *   ) : Type\Union
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];

        $this->register(ReturnTypeProvider\ArrayColumnReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\ArrayFilterReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\ArrayMapReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\ArrayMergeReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\ArrayPointerAdjustmentReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\ArrayPopReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\ArrayRandReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\ArrayReduceReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\ArraySliceReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\FilterVarReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\IteratorToArrayReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\ParseUrlReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\RangeReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\StrReplaceReturnTypeProvider::class);
        $this->register(ReturnTypeProvider\VersionCompareReturnTypeProvider::class);
    }

    /**
     * @param  class-string<FunctionReturnTypeProviderInterface> $class
     * @psalm-suppress PossiblyUnusedParam
     * @return void
     */
    public function register(string $class)
    {
        if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
            /** @psalm-suppress UndefinedMethod */
            $callable = \Closure::fromCallable([$class, 'get']);
        } else {
            $callable = (new \ReflectionClass($class))->getMethod('get')->getClosure(new $class);
        }

        foreach ($class::getFunctionIds() as $function_id) {
            self::$handlers[$function_id] = $callable;
        }
    }

    public function has(string $function_id) : bool
    {
        return isset(self::$handlers[strtolower($function_id)]);
    }

    /**
     * @param  array<PhpParser\Node\Arg>  $call_args
     */
    public function getReturnType(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        return self::$handlers[strtolower($function_id)](
            $statements_analyzer,
            $function_id,
            $call_args,
            $context,
            $code_location
        );
    }
}
