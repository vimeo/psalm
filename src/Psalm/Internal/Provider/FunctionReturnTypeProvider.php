<?php
namespace Psalm\Internal\Provider;

use const PHP_VERSION;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type;
use function strtolower;
use function version_compare;

class FunctionReturnTypeProvider
{
    /**
     * @var array<
     *   string,
     *   array<\Closure(
     *     StatementsSource,
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
        $this->registerClass(ReturnTypeProvider\ArrayReverseReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayUniqueReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayValuesReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ArrayFillReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\FilterVarReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\IteratorToArrayReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ParseUrlReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\StrReplaceReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\VersionCompareReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\MktimeReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\ExplodeReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\GetObjectVarsReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\GetClassMethodsReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\FirstArgStringReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\HexdecReturnTypeProvider::class);
        $this->registerClass(ReturnTypeProvider\SScanFReturnTypeProvider::class);
    }

    /**
     * @param  class-string<FunctionReturnTypeProviderInterface> $class
     *
     * @return void
     */
    public function registerClass(string $class)
    {
        $callable = \Closure::fromCallable([$class, 'getFunctionReturnType']);

        foreach ($class::getFunctionIds() as $function_id) {
            /** @psalm-suppress MixedTypeCoercion */
            $this->registerClosure($function_id, $callable);
        }
    }

    /**
     * /**
     * @param \Closure(
     *     StatementsSource,
     *     string,
     *     array<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation
     *   ) : ?Type\Union $c
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
     *
     * @return ?Type\Union
     */
    public function getReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) {
        foreach (self::$handlers[strtolower($function_id)] as $function_handler) {
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

        return null;
    }
}
