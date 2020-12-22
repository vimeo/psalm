<?php
namespace Psalm\Test\Config\Plugin\Hook;

use PhpParser;
use Psalm\Plugin\Hook\PropertyExistenceProviderInterface;
use Psalm\Plugin\Hook\PropertyTypeProviderInterface;
use Psalm\Plugin\Hook\PropertyVisibilityProviderInterface;
use Psalm\Plugin\Hook\Event\PropertyExistenceProviderEvent;
use Psalm\Plugin\Hook\Event\PropertyTypeProviderEvent;
use Psalm\Plugin\Hook\Event\PropertyVisibilityProviderEvent;
use Psalm\Type;

class FooPropertyProvider implements
    PropertyExistenceProviderInterface,
    PropertyVisibilityProviderInterface,
    PropertyTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array
    {
        return ['Ns\Foo'];
    }

    public static function doesPropertyExist(PropertyExistenceProviderEvent $event): ?bool {
        $property_name = $event->getPropertyName();
        return $property_name === 'magic_property';
    }

    public static function isPropertyVisible(PropertyVisibilityProviderEvent $event): ?bool {
        return true;
    }

    public static function getPropertyType(PropertyTypeProviderEvent $event): ?Type\Union {
        return Type::getString();
    }
}
