<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Override;
use PhpParser\Node\Expr\MethodCall;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Union;

/**
 * Types Box::get() as the receiver's type parameter, as given to the plugin, Box::all() as a list of it and
 * Box::parameterId() as its id. Types the other methods from the receiver's type argument as the receiver
 * is typed (`*` included): Box::receiverConsumer() as a closure taking it, Box::boxes() as a list of boxes of it,
 * Box::boxConsumer() as a closure taking a box of it, Source::markedItems() and Source::markedConsumer() as a
 * Marker<int> intersected with a Holder of a list of it and of a closure taking a box of it
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
        return ['Ns\Box', 'Ns\Source'];
    }

    #[Override]
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $type_parameter = $event->getTemplateTypeParameters()[0] ?? null;
        $receiver_argument = self::getReceiverArgument($event);

        if ($type_parameter === null || $receiver_argument === null) {
            return null;
        }

        return match ($event->getMethodNameLowercase()) {
            'get' => $type_parameter,
            'all' => Type::getList($type_parameter),
            'parameterid' => Type::getString($type_parameter->getId()),
            'receiverconsumer' => self::consumer($receiver_argument),
            'boxes' => Type::getList(new Union([new TGenericObject('Ns\Box', [$receiver_argument])])),
            'boxconsumer' => self::consumer(new Union([new TGenericObject('Ns\Box', [$receiver_argument])])),
            'markeditems' => self::marked(Type::getList($receiver_argument)),
            'markedconsumer' => self::marked(
                self::consumer(new Union([new TGenericObject('Ns\Box', [$receiver_argument])])),
            ),
            default => null,
        };
    }

    private static function getReceiverArgument(MethodReturnTypeProviderEvent $event): ?Union
    {
        $stmt = $event->getStmt();
        $receiver = $stmt instanceof MethodCall ? $event->getSource()->getNodeTypeProvider()->getType($stmt->var) : null;

        foreach ($receiver?->getAtomicTypes() ?? [] as $atomic_type) {
            if ($atomic_type instanceof TGenericObject) {
                return $atomic_type->type_params[0];
            }
        }

        return null;
    }

    /**
     * @psalm-pure
     */
    private static function consumer(Union $value): Union
    {
        return new Union([new TClosure([new FunctionLikeParameter('value', false, $value)], Type::getVoid())]);
    }

    /**
     * @psalm-pure
     */
    private static function marked(Union $held): Union
    {
        $marker = new TGenericObject('Ns\Marker', [Type::getInt()]);

        return new Union([$marker->setIntersectionTypes(['Ns\Holder' => new TGenericObject('Ns\Holder', [$held])])]);
    }
}
