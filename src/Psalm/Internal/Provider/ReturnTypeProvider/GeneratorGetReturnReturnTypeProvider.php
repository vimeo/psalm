<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

/**
 * A generator declared with TReturn void returns null from `getReturn()`
 *
 * @internal
 */
final class GeneratorGetReturnReturnTypeProvider implements MethodReturnTypeProviderInterface
{
    /**
     * @psalm-pure
     */
    #[Override]
    public static function getClassLikeNames(): array
    {
        return ['Generator'];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        if ($event->getMethodNameLowercase() !== 'getreturn') {
            return null;
        }

        $template_type_parameters = $event->getTemplateTypeParameters();

        return $template_type_parameters !== null
            && isset($template_type_parameters[3])
            && $template_type_parameters[3]->isVoid()
            ? Type::getNull()
            : null;
    }
}
