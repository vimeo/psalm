<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Override;
use PhpParser\Node\Expr\MethodCall;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Union;

/**
 * Types Box::get() as the receiver's type parameter, as given to the plugin, and Box::all() as a list of it;
 * Relation::related() as the first type parameter of the type of the receiver expression
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
        return ['Ns\Box', 'Ns\Relation'];
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        if ($event->getMethodNameLowercase() === 'related') {
            $stmt = $event->getStmt();
            $receiver = $stmt instanceof MethodCall
                ? $event->getSource()->getNodeTypeProvider()->getType($stmt->var)
                : null;
            $receiver_atomic = $receiver?->isSingle() ? $receiver->getSingleAtomic() : null;

            return $receiver_atomic instanceof TGenericObject ? $receiver_atomic->type_params[0] : null;
        }

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
