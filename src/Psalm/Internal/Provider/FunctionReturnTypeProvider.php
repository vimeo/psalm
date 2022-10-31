<?php

namespace Psalm\Internal\Provider;

use Closure;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayChunkReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayColumnReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayFillReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayFilterReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayMapReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayMergeReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayPadReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayPointerAdjustmentReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayPopReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayRandReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayReduceReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayReverseReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArraySliceReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArraySpliceReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ArrayUniqueReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\BasenameReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\DirnameReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ExplodeReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\FilterVarReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\FirstArgStringReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\GetClassMethodsReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\GetObjectVarsReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\HexdecReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\InArrayReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\IteratorToArrayReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\MbInternalEncodingReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\MinMaxReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\MktimeReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\ParseUrlReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\RandReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\RoundReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\StrReplaceReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\StrTrReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\TriggerErrorReturnTypeProvider;
use Psalm\Internal\Provider\ReturnTypeProvider\VersionCompareReturnTypeProvider;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type\Union;

use function is_subclass_of;
use function strtolower;

/**
 * @internal
 */
class FunctionReturnTypeProvider
{
    /**
     * @var array<
     *   lowercase-string,
     *   array<Closure(FunctionReturnTypeProviderEvent): ?Union>
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];

        $this->registerClass(ArrayChunkReturnTypeProvider::class);
        $this->registerClass(ArrayColumnReturnTypeProvider::class);
        $this->registerClass(ArrayFilterReturnTypeProvider::class);
        $this->registerClass(ArrayMapReturnTypeProvider::class);
        $this->registerClass(ArrayMergeReturnTypeProvider::class);
        $this->registerClass(ArrayPadReturnTypeProvider::class);
        $this->registerClass(ArrayPointerAdjustmentReturnTypeProvider::class);
        $this->registerClass(ArrayPopReturnTypeProvider::class);
        $this->registerClass(ArrayRandReturnTypeProvider::class);
        $this->registerClass(ArrayReduceReturnTypeProvider::class);
        $this->registerClass(ArraySliceReturnTypeProvider::class);
        $this->registerClass(ArraySpliceReturnTypeProvider::class);
        $this->registerClass(ArrayReverseReturnTypeProvider::class);
        $this->registerClass(ArrayUniqueReturnTypeProvider::class);
        $this->registerClass(ArrayFillReturnTypeProvider::class);
        $this->registerClass(FilterVarReturnTypeProvider::class);
        $this->registerClass(IteratorToArrayReturnTypeProvider::class);
        $this->registerClass(ParseUrlReturnTypeProvider::class);
        $this->registerClass(StrReplaceReturnTypeProvider::class);
        $this->registerClass(StrTrReturnTypeProvider::class);
        $this->registerClass(VersionCompareReturnTypeProvider::class);
        $this->registerClass(MktimeReturnTypeProvider::class);
        $this->registerClass(BasenameReturnTypeProvider::class);
        $this->registerClass(DirnameReturnTypeProvider::class);
        $this->registerClass(ExplodeReturnTypeProvider::class);
        $this->registerClass(GetObjectVarsReturnTypeProvider::class);
        $this->registerClass(GetClassMethodsReturnTypeProvider::class);
        $this->registerClass(FirstArgStringReturnTypeProvider::class);
        $this->registerClass(HexdecReturnTypeProvider::class);
        $this->registerClass(MinMaxReturnTypeProvider::class);
        $this->registerClass(TriggerErrorReturnTypeProvider::class);
        $this->registerClass(RandReturnTypeProvider::class);
        $this->registerClass(InArrayReturnTypeProvider::class);
        $this->registerClass(RoundReturnTypeProvider::class);
        $this->registerClass(MbInternalEncodingReturnTypeProvider::class);
    }

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, FunctionReturnTypeProviderInterface::class, true)) {
            $callable = Closure::fromCallable([$class, 'getFunctionReturnType']);

            foreach ($class::getFunctionIds() as $function_id) {
                $this->registerClosure($function_id, $callable);
            }
        }
    }

    /**
     * @param lowercase-string $function_id
     * @param Closure(FunctionReturnTypeProviderEvent): ?Union $c
     */
    public function registerClosure(string $function_id, Closure $c): void
    {
        self::$handlers[$function_id][] = $c;
    }

    public function has(string $function_id): bool
    {
        return isset(self::$handlers[strtolower($function_id)]);
    }

    /**
     * @param  non-empty-string $function_id
     */
    public function getReturnType(
        StatementsSource $statements_source,
        string $function_id,
        PhpParser\Node\Expr\FuncCall $stmt,
        Context $context,
        CodeLocation $code_location
    ): ?Union {
        foreach (self::$handlers[strtolower($function_id)] ?? [] as $function_handler) {
            $event = new FunctionReturnTypeProviderEvent(
                $statements_source,
                $function_id,
                $stmt,
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
