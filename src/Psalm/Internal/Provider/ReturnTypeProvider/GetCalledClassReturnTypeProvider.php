<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

/**
 * @internal
 */
final class GetCalledClassReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['get_called_class'];
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        if ($event->getCallArgs()) {
            return null;
        }

        $self = $event->getContext()->self;

        return new Union([
            new TClassString(
                $self ?? 'object',
                $self !== null ? new TNamedObject($self, true) : null,
            ),
        ]);
    }
}
