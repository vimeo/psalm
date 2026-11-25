<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use Psalm\Internal\Analyzer\Statements\Expression\ArrayAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;

use function is_int;
use function max;

/**
 * Narrows ReflectionReference::fromArrayElement() to non-null when the array is a literal whose
 * element at the given key is a reference to a variable, a static property or an element of one.
 *
 * @internal
 */
final class ReflectionReferenceReturnTypeProvider implements MethodReturnTypeProviderInterface
{
    /**
     * @psalm-pure
     */
    #[Override]
    public static function getClassLikeNames(): array
    {
        return ['ReflectionReference'];
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $source = $event->getSource();
        if (!$source instanceof StatementsAnalyzer
            || $event->getMethodNameLowercase() !== 'fromarrayelement'
        ) {
            return null;
        }

        $call_args = $event->getCallArgs();
        $array_arg = self::getArg($call_args, 0, 'array');
        $key_arg = self::getArg($call_args, 1, 'key');
        if ($array_arg === null || $key_arg === null || !$array_arg->value instanceof Array_) {
            return null;
        }

        $key = self::getLiteralKey($source, $key_arg->value);
        if ($key === null) {
            return null;
        }

        // Since PHP 8.3, the implicit index after a leading negative key is that key + 1 instead of 0
        $negative_keys_count_up = $source->getCodebase()->analysis_php_version_id >= 8_03_00;

        // Rebuild the keys PHP assigns to the literal; a later duplicate key overwrites an earlier one
        $is_reference = null;
        $next_index = null;
        foreach ($array_arg->value->items as $item) {
            if ($item->unpack) {
                return null;
            }

            if ($item->key === null) {
                $item_key = $next_index ?? 0;
            } else {
                $item_key = self::getLiteralKey($source, $item->key);
                if ($item_key === null) {
                    return null;
                }
            }

            if (is_int($item_key)) {
                $next_index = $next_index === null && $negative_keys_count_up
                    ? $item_key + 1
                    : max($next_index ?? 0, $item_key + 1);
            }

            if ($item_key === $key) {
                $is_reference = $item->byRef && self::isLastingStorage($source, $item->value);
            }
        }

        // A missing key throws, and a by-value element or a reference that nothing else holds gives null:
        // all keep the stub's return type
        return $is_reference === true ? new Union([new TNamedObject('ReflectionReference')]) : null;
    }

    /**
     * Whether a reference to $expr stays held by the storage it points to, rather than by a temporary
     * (`&f()[0]`) or by nothing (`&$obj->magic`, `&$array_access['k']`)
     *
     * @psalm-capabilities read-props
     */
    private static function isLastingStorage(StatementsAnalyzer $source, Expr $expr): bool
    {
        if ($expr instanceof Variable || $expr instanceof StaticPropertyFetch) {
            return true;
        }

        if (!$expr instanceof ArrayDimFetch || !self::isLastingStorage($source, $expr->var)) {
            return false;
        }

        $container_type = $source->node_data->getType($expr->var);
        if ($container_type === null) {
            return false;
        }

        foreach ($container_type->getAtomicTypes() as $atomic) {
            if (!$atomic instanceof TArray && !$atomic instanceof TKeyedArray && !$atomic instanceof TNull) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<Arg> $call_args
     * @psalm-capabilities read-props
     */
    private static function getArg(array $call_args, int $position, string $name): ?Arg
    {
        foreach ($call_args as $i => $arg) {
            if ($arg->unpack) {
                return null;
            }

            if ($arg->name === null ? $i === $position : $arg->name->name === $name) {
                return $arg;
            }
        }

        return null;
    }

    /**
     * @psalm-capabilities read-props
     */
    private static function getLiteralKey(StatementsAnalyzer $source, Expr $expr): int|string|null
    {
        $type = $source->node_data->getType($expr);
        if ($type === null || !$type->isSingle()) {
            return null;
        }

        $atomic = $type->getSingleAtomic();
        if ($atomic instanceof TLiteralInt) {
            return $atomic->value;
        }

        if ($atomic instanceof TLiteralString) {
            // "1" is stored as 1, "01" stays a string
            $int = ArrayAnalyzer::getLiteralArrayKeyInt($atomic->value);

            return $int === false ? $atomic->value : $int;
        }

        return null;
    }
}
