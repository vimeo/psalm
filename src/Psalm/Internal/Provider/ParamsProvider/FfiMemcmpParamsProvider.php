<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ParamsProvider;

use Override;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use Psalm\Plugin\EventHandler\Event\MethodParamsProviderEvent;
use Psalm\Plugin\EventHandler\MethodParamsProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

/**
 * `FFI::memcmp()` takes its pointers by reference only when it is given a variable (`ZEND_SEND_PREFER_REF`)
 *
 * @internal
 */
final class FfiMemcmpParamsProvider implements MethodParamsProviderInterface
{
    /**
     * @psalm-pure
     */
    #[Override]
    public static function getClassLikeNames(): array
    {
        return ['FFI'];
    }

    /**
     * @return ?array<int, FunctionLikeParameter>
     * @psalm-capabilities read-props
     */
    #[Override]
    public static function getMethodParams(MethodParamsProviderEvent $event): ?array
    {
        $call_args = $event->getCallArgs();

        if ($event->getMethodNameLowercase() !== 'memcmp' || $call_args === null) {
            return null;
        }

        $pointer_type = new Union([new TString(), new TNamedObject('FFI\\CData')]);

        $params = [];

        foreach (['ptr1', 'ptr2'] as $offset => $name) {
            $arg = $call_args[$offset] ?? null;

            foreach ($call_args as $call_arg) {
                if ($call_arg->name !== null && $call_arg->name->name === $name) {
                    $arg = $call_arg;
                    break;
                }
            }

            if ($arg !== null && $arg->name !== null && $arg->name->name !== $name) {
                $arg = null;
            }

            $params[] = new FunctionLikeParameter(
                $name,
                $arg !== null && (
                    $arg->value instanceof Variable
                    || $arg->value instanceof ArrayDimFetch
                    || $arg->value instanceof PropertyFetch
                    || $arg->value instanceof StaticPropertyFetch
                ),
                $pointer_type,
                null,
                null,
                null,
                false,
            );
        }

        $size_type = new Union([new TIntRange(0, null)]);

        $params[] = new FunctionLikeParameter('size', false, $size_type, Type::getInt(), null, null, false);

        return $params;
    }
}
