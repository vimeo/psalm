<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Override;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

/**
 * Types Box::get() as the receiver's type parameter, as given to the plugin, and Box::all() as a list of it
 *
 * @psalm-suppress UnusedClass registered as a plugin via test config, instantiated by reflection
 */
final class TemplateParameterProvider implements MethodReturnTypeProviderInterface
{
    /**
     * @return array<string>
     * @psalm-pure
     */
    #[Override]
    public static function getClassLikeNames(): array
    {
        return ['Ns\Box'];
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $type_parameter = $event->getTemplateTypeParameters()[0] ?? null;

        if ($type_parameter === null) {
            return null;
        }

        return match ($event->getMethodNameLowercase()) {
            'get' => $type_parameter,
            'all' => Type::getList($type_parameter),
            default => null,
        };
    }
}
