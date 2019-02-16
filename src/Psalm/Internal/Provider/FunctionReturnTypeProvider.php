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

        $this->registerClass(ReturnTypeProvider\ArrayColumnReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayFilterReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayMapReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayMergeReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayPointerAdjustmentReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayPopReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayRandReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayReduceReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArraySliceReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\FilterVarReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\IteratorToArrayReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ParseUrlReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\RangeReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\StrReplaceReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\VersionCompareReturnTypeProvider::class);
    }

    /**
     * @param  class-string<FunctionReturnTypeProviderInterface> $class
     * @psalm-suppress PossiblyUnusedParam
     * @return void
     */
    public function registerClass(string $class)
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

    /**
     * @return void
     */
    public function registerClosure(string $function_id, \Closure $c)
    {
        self::$handlers[$function_id] = $c;
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
