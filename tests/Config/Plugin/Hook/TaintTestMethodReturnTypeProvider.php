<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Override;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

/** @psalm-suppress UnusedClass */
final class TaintTestMethodReturnTypeProvider implements MethodReturnTypeProviderInterface
{
    /**
     * @return list<lowercase-string>
     */
    #[Override]
    public static function getClassLikeNames(): array
    {
        return ['myservice'];
    }

    #[Override]
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        if ($event->getMethodNameLowercase() === 'getuserinput') {
            return Type::getString();
        }

        return null;
    }
}
