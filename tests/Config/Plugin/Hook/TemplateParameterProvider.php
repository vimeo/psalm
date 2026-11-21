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

use function array_slice;
use function array_values;

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
     * @psalm-impure
     */
    #[Override]
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        if ($event->getMethodNameLowercase() === 'related'
            || $event->getMethodNameLowercase() === 'withpivot'
            || $event->getMethodNameLowercase() === 'rebuilt'
        ) {
            $stmt = $event->getStmt();
            $receiver = $stmt instanceof MethodCall
                ? $event->getSource()->getNodeTypeProvider()->getType($stmt->var)
                : null;
            $receiver_atomic = $receiver?->isSingle() ? $receiver->getSingleAtomic() : null;

            if (!$receiver_atomic instanceof TGenericObject) {
                return null;
            }

            if ($event->getMethodNameLowercase() === 'related') {
                return $receiver_atomic->type_params[0];
            }

            if ($event->getMethodNameLowercase() === 'rebuilt') {
                // equal arguments in new unions: the `*` stays one
                $rebuilt = [];

                foreach ($receiver_atomic->type_params as $param) {
                    $rebuilt[] = new Union(array_values($param->getAtomicTypes()));
                }

                return new Union([$receiver_atomic->setTypeParams($rebuilt)]);
            }

            // the second type parameter is rebuilt, as a plugin refining a pivot class does
            $type_params = [
                $receiver_atomic->type_params[0],
                new Union([new TNamedObject('Ns\Pivot')]),
                ...array_slice($receiver_atomic->type_params, 2),
            ];

            return new Union([$receiver_atomic->setTypeParams($type_params)]);
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
