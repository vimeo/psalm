<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Override;
use PhpParser\Node\Expr\MethodCall;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

/**
 * Types a call from what the receiver looks like to a plugin: a plugin only
 * knows arrays and objects, so a receiver (or a type parameter of it) that is
 * anything else — e.g. an unresolved class-template type variable — makes the
 * result `mixed`.
 *
 * @psalm-suppress UnusedClass registered as a plugin via test config, instantiated by reflection
 */
final class TypeVariableReceiverProvider implements MethodReturnTypeProviderInterface
{
    /**
     * @return array<string>
     * @psalm-pure
     */
    #[Override]
    public static function getClassLikeNames(): array
    {
        return ['Ns\Item', 'Ns\Collection'];
    }

    #[Override]
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $stmt = $event->getStmt();

        if (!$stmt instanceof MethodCall) {
            return null;
        }

        $receiver_type = $event->getSource()->getNodeTypeProvider()->getType($stmt->var);

        if ($receiver_type === null) {
            return null;
        }

        $method_name_lowercase = $event->getMethodNameLowercase();

        if ($method_name_lowercase === 'describe') {
            foreach ($receiver_type->getAtomicTypes() as $atomic_type) {
                if (!$atomic_type instanceof TNamedObject) {
                    return Type::getMixed();
                }
            }

            return Type::getString();
        }

        if ($method_name_lowercase === 'names') {
            foreach ($receiver_type->getAtomicTypes() as $atomic_type) {
                if (!$atomic_type instanceof TGenericObject) {
                    return Type::getMixed();
                }

                foreach ($atomic_type->type_params as $type_param) {
                    foreach ($type_param->getAtomicTypes() as $param_atomic_type) {
                        if (!$param_atomic_type instanceof TNamedObject) {
                            return Type::getMixed();
                        }
                    }
                }
            }

            return new Union([Type::getListAtomic(Type::getString())]);
        }

        return null;
    }
}
