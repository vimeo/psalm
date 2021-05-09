<?php
namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface as LegacyFunctionReturnTypeProviderInterface;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type;
use function strtolower;
use function is_subclass_of;

class FunctionReturnTypeProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<\Closure(FunctionReturnTypeProviderEvent) : ?Type\Union>
     * >
     */
    private static $handlers = [];

    /**
     * @var array<
     *   lowercase-string,
     *   array<\Closure(
     *     StatementsSource,
     *     non-empty-string,
     *     list<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation
     *   ) : ?Type\Union>
     * >
     */
    private static $legacy_handlers = [];

    public function __construct()
    {
        self::$handlers = [];
        self::$legacy_handlers = [];

        $this->registerClass(ReturnTypeProvider\ArrayChunkReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayColumnReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayFilterReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayMapReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayMergeReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayPadReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayPointerAdjustmentReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayPopReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayRandReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayReduceReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArraySliceReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArraySpliceReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayReverseReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayUniqueReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayValuesReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayFillReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\FilterVarReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\IteratorToArrayReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ParseUrlReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\StrReplaceReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\StrTrReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\VersionCompareReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\MktimeReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ExplodeReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\GetObjectVarsReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\GetClassMethodsReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\FirstArgStringReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\HexdecReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\MinMaxReturnTypeProvider::class);
    }

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, LegacyFunctionReturnTypeProviderInterface::class, true)) {
            $callable = \Closure::fromCallable([$class, 'getFunctionReturnType']);

            foreach ($class::getFunctionIds() as $function_id) {
                $this->registerLegacyClosure($function_id, $callable);
            }
        } elseif (is_subclass_of($class, FunctionReturnTypeProviderInterface::class, true)) {
            $callable = \Closure::fromCallable([$class, 'getFunctionReturnType']);

            foreach ($class::getFunctionIds() as $function_id) {
                $this->registerClosure($function_id, $callable);
            }
        }
    }

    /**
     * @param lowercase-string $function_id
     * @param \Closure(FunctionReturnTypeProviderEvent) : ?Type\Union $c
     */
    public function registerClosure(string $function_id, \Closure $c): void
    {
        self::$handlers[$function_id][] = $c;
    }

    /**
     * @param lowercase-string $function_id
     * @param \Closure(
     *     StatementsSource,
     *     non-empty-string,
     *     list<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation
     *   ) : ?Type\Union $c
     */
    public function registerLegacyClosure(string $function_id, \Closure $c): void
    {
        self::$legacy_handlers[$function_id][] = $c;
    }

    public function has(string $function_id) : bool
    {
        return isset(self::$handlers[strtolower($function_id)]) ||
            isset(self::$legacy_handlers[strtolower($function_id)]);
    }

    /**
     * @param  non-empty-string $function_id
     * @param  list<PhpParser\Node\Arg>  $call_args
     */
    public function getReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ): ?Type\Union {
        foreach (self::$legacy_handlers[strtolower($function_id)] ?? [] as $function_handler) {
            $return_type = $function_handler(
                $statements_source,
                $function_id,
                $call_args,
                $context,
                $code_location
            );

            if ($return_type) {
                return $return_type;
            }
        }

        foreach (self::$handlers[strtolower($function_id)] ?? [] as $function_handler) {
            $event = new FunctionReturnTypeProviderEvent(
                $statements_source,
                $function_id,
                $call_args,
                $context,
                $code_location
            );
            $return_type = $function_handler($event);

            if ($return_type) {
                return $return_type;
            }
        }

        return null;
    }
}
