<?php
namespace Psalm\Test\Config\Plugin\Hook;

use Psalm\Plugin\EventHandler\Event\PropertyExistenceProviderEvent;
use Psalm\Plugin\EventHandler\Event\PropertyTypeProviderEvent;
use Psalm\Plugin\EventHandler\Event\PropertyVisibilityProviderEvent;
use Psalm\Plugin\EventHandler\PropertyExistenceProviderInterface;
use Psalm\Plugin\EventHandler\PropertyTypeProviderInterface;
use Psalm\Plugin\EventHandler\PropertyVisibilityProviderInterface;
use Psalm\Type;

class FooPropertyProvider implements
    PropertyExistenceProviderInterface,
    PropertyVisibilityProviderInterface,
    PropertyTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array
    {
        return ['Ns\Foo'];
    }

    public static function doesPropertyExist(PropertyExistenceProviderEvent $event): ?bool
    {
        $property_name = $event->getPropertyName();
        return $property_name === 'magic_property';
    }

    public static function isPropertyVisible(PropertyVisibilityProviderEvent $event): ?bool
    {
        return true;
    }

    public static function getPropertyType(PropertyTypeProviderEvent $event): ?Type\Union
    {
        return Type::getString();
    }
}
